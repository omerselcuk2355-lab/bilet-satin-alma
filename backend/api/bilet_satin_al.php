<?php
session_start();
require_once __DIR__ . '/../includes/config.php'; // doğru bağlantı yolu

header('Content-Type: application/json; charset=utf-8');

// 🔐 Oturum kontrolü
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user']['id'] ?? null;

// 🔒 Rol kontrolü: sadece "user" rolü bilet alabilir
$role = $_SESSION['user']['role'] ?? 'user';
if ($role !== 'user') {
    echo json_encode([
        'ok' => false,
        'message' => 'Sadece yolcu (user) rolündeki hesaplar bilet satın alabilir.'
    ]);
    exit;
}

$sefer_id = $_POST['sefer_id'] ?? null;
$koltuk_no = $_POST['koltuk_no'] ?? null;
$kupon_kodu = $_POST['kupon_kodu'] ?? null;

if (!$sefer_id || !$koltuk_no) {
    echo json_encode(['ok' => false, 'message' => 'Eksik bilgi gönderildi.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1️⃣ Sefer bilgisi
    $stmt = $db->prepare("SELECT fiyat FROM seferler WHERE id = ?");
    $stmt->execute([$sefer_id]);
    $sefer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sefer) throw new Exception("Sefer bulunamadı.");

    $fiyat = $sefer['fiyat'];

    // 2️⃣ Kupon kontrolü
    if ($kupon_kodu) {
        $stmt = $db->prepare("SELECT oran, kullanim_limiti, son_tarih FROM kuponlar WHERE kod = ?");
        $stmt->execute([$kupon_kodu]);
        $kupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kupon && strtotime($kupon['son_tarih']) > time() && $kupon['kullanim_limiti'] > 0) {
            $indirim = $fiyat * ($kupon['oran'] / 100);
            $fiyat -= $indirim;
        } else {
            throw new Exception("Kupon geçersiz veya süresi dolmuş.");
        }
    }

    // 3️⃣ Kullanıcının bakiyesi yeterli mi?
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception("Kullanıcı bulunamadı.");
    if ($user['balance'] < $fiyat) throw new Exception("Yetersiz bakiye.");

    // 4️⃣ Koltuk dolu mu?
    $stmt = $db->prepare("SELECT COUNT(*) FROM biletler WHERE sefer_id = ? AND koltuk_no = ? AND durum = 'aktif'");
    $stmt->execute([$sefer_id, $koltuk_no]);
    if ($stmt->fetchColumn() > 0) throw new Exception("Bu koltuk zaten dolu.");

    // 5️⃣ Bilet kaydı
    $stmt = $db->prepare("
        INSERT INTO biletler (user_id, sefer_id, koltuk_no, fiyat)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $sefer_id, $koltuk_no, $fiyat]);

    // 6️⃣ Kullanıcı bakiyesini düşür
    $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$fiyat, $user_id]);

    // 7️⃣ Kupon limiti azalt
    if ($kupon_kodu && isset($kupon)) {
        $stmt = $db->prepare("UPDATE kuponlar SET kullanim_limiti = kullanim_limiti - 1 WHERE kod = ?");
        $stmt->execute([$kupon_kodu]);
    }

    $db->commit();
    echo json_encode(['ok' => true, 'message' => '🎟️ Bilet başarıyla satın alındı!']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
?>
