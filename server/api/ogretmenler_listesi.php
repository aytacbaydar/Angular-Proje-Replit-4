
<?php
require_once('../config.php');

function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Veritabanı bağlantı hatası']);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function getJsonData() {
    return json_decode(file_get_contents('php://input'), true);
}

function successResponse($data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function authorize() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        errorResponse('Yetkilendirme başlığı geçersiz', 401);
    }
    
    $token = $matches[1];
    
    if (empty($token)) {
        errorResponse('Yetkilendirme jetonu eksik', 401);
    }
    
    // Token doğrulama işlemi burada yapılabilir
    // Bu örnek için basitçe token'ı geçerli kabul ediyoruz
    
    return [
        'id' => 1, // Kullanıcı ID'si
        'rutbe' => 'admin', // Kullanıcı rütbesi (admin, öğretmen, öğrenci)
    ];
}

// API isteğini işle
try {
    // Yetkilendirme kontrolü
    $user = authorize();
    
    // Sadece admin ve öğretmenler erişebilir
    if ($user['rutbe'] !== 'admin' && $user['rutbe'] !== 'ogretmen') {
        errorResponse('Bu kaynağa erişim yetkiniz yok', 403);
    }
    
    $conn = getConnection();
    
    // Tüm öğretmenleri getir
    $query = "SELECT id, adi_soyadi, email, cep_telefonu, avatar, rutbe, aktif, created_at, brans 
              FROM kullanicilar 
              WHERE rutbe = 'ogretmen' AND aktif = 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        errorResponse('Sorgu hazırlama hatası: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        // Avatar URL'ini oluştur
        if (!empty($row['avatar'])) {
            $row['avatar'] = './uploads/avatars/' . $row['avatar'];
        } else {
            $row['avatar'] = './assets/images/default-avatar.png';
        }
        
        $teachers[] = $row;
    }
    
    $stmt->close();
    
    successResponse($teachers);
} catch (Exception $e) {
    errorResponse('Sunucu hatası: ' . $e->getMessage(), 500);
}
?>
