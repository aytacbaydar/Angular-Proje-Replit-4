
<?php
require_once '../config.php';

// Gerekli yardımcı fonksiyonlar
function getConnection() {
    global $conn;
    if (!$conn) {
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            errorResponse("Veritabanı bağlantı hatası: " . $e->getMessage(), 500);
        }
    }
    return $conn;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function successResponse($data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
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

// GET isteği: Öğretmenleri listele
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Kullanıcıyı doğrula
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
        $stmt->execute();
        
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Avatar URL'lerini düzenle
        foreach ($teachers as &$teacher) {
            if (!empty($teacher['avatar'])) {
                $teacher['avatar'] = './uploads/avatars/' . $teacher['avatar'];
            } else {
                $teacher['avatar'] = './assets/images/default-avatar.png';
            }
        }
        
        successResponse($teachers);
        
    } catch (PDOException $e) {
        errorResponse('Veritabanı hatası: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        errorResponse('Beklenmeyen bir hata oluştu: ' . $e->getMessage(), 500);
    }
}
// Diğer HTTP metodlarını reddet
else {
    errorResponse('Bu endpoint sadece GET metodunu desteklemektedir', 405);
}
?>
