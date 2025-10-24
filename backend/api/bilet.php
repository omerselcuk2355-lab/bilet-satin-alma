<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 🔒 Güvenlik – canlı ortamda hata bastır
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// 🔐 Oturum kontrolü
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$firma_id = $_SESSION['user']['firma_id'] ?? null;

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {

        /* =======================================================
           🎫 Firma Admini - Seferine Ait Biletleri Listeleme
        ======================================================= */
        case 'firma_list':
            if ($role !== 'firma_admin') {
                echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi.']);
                exit;
            }

            $sefer_id = (int)($_GET['sefer_id'] ?? 0);
            if (!$sefer_id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz sefer ID.']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT 
                    b.id, 
                    u.name AS ad_soyad, 
                    u.email, 
                    b.koltuk_no, 
                    b.fiyat, 
                    b.durum
                FROM biletler b
                JOIN users u ON b.user_id = u.id
                JOIN seferler s ON b.sefer_id = s.id
                WHERE b.sefer_id = ? AND s.firma_id = ?
                ORDER BY b.id DESC
            ");
            $stmt->execute([$sefer_id, $firma_id]);
            $biletler = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'data' => $biletler], JSON_UNESCAPED_UNICODE);
            break;

        /* =======================================================
           ❌ Firma Admini - Bilet İptali + Kredi İadesi
        ======================================================= */
        case 'cancel':
            if ($role !== 'firma_admin') {
                echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi.']);
                exit;
            }

            $bilet_id = (int)($_POST['id'] ?? 0);
            if (!$bilet_id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz bilet ID.']);
                exit;
            }

            // Firma bu bilete sahip mi?
            $stmt = $db->prepare("
                SELECT 
                    b.id, b.user_id, b.fiyat, s.firma_id, b.durum
                FROM biletler b
                JOIN seferler s ON b.sefer_id = s.id
                WHERE b.id = ? AND s.firma_id = ?
            ");
            $stmt->execute([$bilet_id, $firma_id]);
            $bilet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bilet) {
                echo json_encode(['ok' => false, 'message' => 'Bu bilet bu firmaya ait değil.']);
                exit;
            }

            if ($bilet['durum'] === 'iptal') {
                echo json_encode(['ok' => false, 'message' => 'Bu bilet zaten iptal edilmiş.']);
                exit;
            }

            // Transaction başlat
            $db->beginTransaction();
            try {
                // 1️⃣ Bilet durumunu iptal et
                $updateBilet = $db->prepare("UPDATE biletler SET durum = 'iptal' WHERE id = ?");
                $updateBilet->execute([$bilet_id]);

                // 2️⃣ Kullanıcının bakiyesine iade ekle
                $updateUser = $db->prepare("UPDATE users SET balance = balance + :fiyat WHERE id = :uid");
                $updateUser->execute([
                    ':fiyat' => (float)$bilet['fiyat'],
                    ':uid' => (int)$bilet['user_id']
                ]);

                $db->commit();

                echo json_encode([
                    'ok' => true,
                    'message' => 'Bilet başarıyla iptal edildi. Kullanıcının hesabına ' . $bilet['fiyat'] . '₺ iade edildi.'
                ], JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['ok' => false, 'message' => 'İptal işlemi başarısız: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        /* =======================================================
           💺 Kullanıcı - Seferde Dolu Koltukları Listeleme
        ======================================================= */
        case 'dolu_koltuklar':
            $sefer_id = (int)($_GET['sefer_id'] ?? 0);
            if ($sefer_id <= 0) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz sefer ID.']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT koltuk_no 
                FROM biletler 
                WHERE sefer_id = ? 
                AND durum = 'aktif'
            ");
            $stmt->execute([$sefer_id]);
            $koltuklar = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'ok' => true,
                'koltuklar' => array_map('intval', $koltuklar)
            ], JSON_UNESCAPED_UNICODE);
            break;

        /* =======================================================
           🧩 Geçersiz İşlem
        ======================================================= */
        default:
            echo json_encode(['ok' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Hata: ' . htmlspecialchars($e->getMessage())]);
}
?>
