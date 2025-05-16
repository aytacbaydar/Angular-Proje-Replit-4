
<?php
// Öğretmenler listesi API'si
require_once '../config.php';

// GET isteği: Tüm öğretmenleri getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Kullanıcıyı doğrula
        $user = authorize();
        
        // Sadece yöneticiler ve öğretmenler tüm öğretmenleri görebilir
        if ($user['rutbe'] !== 'admin' && $user['rutbe'] !== 'ogretmen') {
            errorResponse('Bu bilgilere erişim yetkiniz yok', 403);
        }
        
        $conn = getConnection();
        
        // Tüm öğretmenleri getir
        $stmt = $conn->prepare("
            SELECT o.id, o.adi_soyadi, o.email, o.cep_telefonu, o.rutbe, o.aktif, o.avatar, o.created_at,
                   ob.brans
            FROM ogrenciler o
            LEFT JOIN ogretmen_bilgileri ob ON o.id = ob.ogretmen_id
            WHERE o.rutbe = 'ogretmen'
            ORDER BY o.id DESC
        ");
        $stmt->execute();
        
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
