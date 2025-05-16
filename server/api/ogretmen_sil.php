
<?php
// Öğretmen silme API'si
require_once '../config.php';

// POST isteği: Öğretmeni sil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Kullanıcıyı doğrula
        $user = authorize();
        
        // Sadece yöneticiler öğretmen silebilir
        if ($user['rutbe'] !== 'admin') {
            errorResponse('Bu işlemi yapma yetkiniz yok', 403);
        }
        
        // Input verisini al
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            errorResponse('Geçersiz öğretmen ID', 400);
        }
        
        $teacherId = intval($data['id']);
        
        $conn = getConnection();
        $conn->beginTransaction();
        
        // Önce öğretmen detay bilgilerini sil
        $stmt = $conn->prepare("DELETE FROM ogretmen_bilgileri WHERE ogretmen_id = ?");
        $stmt->execute([$teacherId]);
        
        // Sonra öğretmeni sil
        $stmt = $conn->prepare("DELETE FROM ogrenciler WHERE id = ? AND rutbe = 'ogretmen'");
        $stmt->execute([$teacherId]);
        
        // Silinen satır sayısı 0 ise, öğretmen bulunamadı
        if ($stmt->rowCount() === 0) {
            $conn->rollBack();
            errorResponse('Öğretmen bulunamadı', 404);
        }
        
        $conn->commit();
        
        successResponse(['message' => 'Öğretmen başarıyla silindi']);
        
    } catch (PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        errorResponse('Veritabanı hatası: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        errorResponse('Beklenmeyen bir hata oluştu: ' . $e->getMessage(), 500);
    }
}
// Diğer HTTP metodlarını reddet
else {
    errorResponse('Bu endpoint sadece POST metodunu desteklemektedir', 405);
}
?>
