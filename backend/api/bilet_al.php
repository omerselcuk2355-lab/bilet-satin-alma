<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Lütfen giriş yapınız.']);
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

$sefer_id = $_POST['sefer_id'] ?? 0;
$koltuk_no = $_POST['koltuk_no'] ?? 0;
$kupon = trim($_POST['kupon'] ?? '');

if (!$sefer_id || !$koltuk_no) {
    echo json_encode(['ok' => false, 'message' => 'Eksik bilgi gönderildi.']);
    exit;
}

// 1. Sefer bilgisi
$stmt = $db->prepare("SELECT * FROM seferler WHERE id = :id");
$stmt->execute([':id' => $sefer_id]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sefer) {
    echo json_encode(['ok' => false, 'message' => 'Sefer bulunamadı.']);
    exit;
}

// 2. Koltuk dolu mu?
$check = $db->prepare("SELECT COUNT(*) FROM biletler WHERE sefer_id = :sid AND koltuk_no = :no");
$check->execute([':sid' => $sefer_id, ':no' => $koltuk_no]);
if ($check->fetchColumn() > 0) {
    echo json_encode(['ok' => false, 'message' => 'Bu koltuk zaten dolu.']);
    exit;
}

// 3. Kullanıcı bakiyesi
$user_stmt = $db->prepare("SELECT kredi FROM users WHERE id = :id");
$user_stmt->execute([':id' => $user_id]);
$kredi = (float)$user_stmt->fetchColumn();
$fiyat = (float)$sefer['fiyat'];

// 4. Kupon indirimi (isteğe bağlı)
if ($kupon !== '') {
    $kup_stmt = $db->prepare("SELECT * FROM kuponlar WHERE kod = :kod AND son_tarih >= DATE('now')");
    $kup_stmt->execute([':kod' => $kupon]);
    $kup = $kup_stmt->fetch(PDO::FETCH_ASSOC);
    if ($kup) {
        $fiyat *= (1 - ($kup['oran'] / 100));
    }
}

// 5. Kredi yeterli mi?
if ($kredi < $fiyat) {
    echo json_encode(['ok' => false, 'message' => 'Yetersiz bakiye.']);
    exit;
}

// 6. Bilet kaydı ve bakiye düşürme
$db->beginTransaction();
try {
    $insert = $db->prepare("INSERT INTO biletler (user_id, sefer_id, koltuk_no, fiyat, alis_tarihi)
                            VALUES (:uid, :sid, :no, :fiyat, DATE('now'))");
    $insert->execute([
        ':uid' => $user_id,
        ':sid' => $sefer_id,
        ':no' => $koltuk_no,
        ':fiyat' => $fiyat
    ]);

    $update = $db->prepare("UPDATE users SET kredi = kredi - :fiyat WHERE id = :id");
    $update->execute([':fiyat' => $fiyat, ':id' => $user_id]);

    $db->commit();

    echo json_encode(['ok' => true, 'message' => 'Bilet başarıyla satın alındı.']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['ok' => false, 'message' => 'İşlem hatası: ' . $e->getMessage()]);
}
?>
