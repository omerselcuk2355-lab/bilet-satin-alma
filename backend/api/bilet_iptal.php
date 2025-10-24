<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// 🔒 Oturum kontrolü
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

// 👤 Kullanıcı bilgileri
$user = $_SESSION['user'];
$user_id = $user['id'];
$user_role = $user['role'] ?? 'user'; // default güvenlik için

// 🔐 Yetki kontrolü (yalnızca user veya firma admin)
if (!in_array($user_role, ['user', 'firma_admin'])) {
    echo json_encode(['ok' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
    exit;
}

$bilet_id = $_POST['bilet_id'] ?? null;
if (!$bilet_id) {
    echo json_encode(['ok' => false, 'message' => 'Bilet ID gönderilmedi.']);
    exit;
}

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔍 Bilet ve sefer bilgisi
    $stmt = $db->prepare("
        SELECT b.id, b.user_id, b.sefer_id, b.fiyat, b.durum, 
               s.tarih, s.saat
        FROM biletler b
        JOIN seferler s ON s.id = b.sefer_id
        WHERE b.id = :bid
    ");
    $stmt->execute([':bid' => $bilet_id]);
    $bilet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bilet) {
        echo json_encode(['ok' => false, 'message' => 'Bilet bulunamadı.']);
        exit;
    }

    // 🔐 Firma admin kendi firmasına ait biletleri iptal edebilmeli
    if ($user_role === 'firma_admin') {
        // firma_admin’in firmasına ait sefer mi?
        $firmaCheck = $db->prepare("
            SELECT f.id FROM seferler s
            JOIN firmalar f ON s.firma_id = f.id
            WHERE s.id = :sid AND f.id = :fid
        ");
        $firmaCheck->execute([':sid' => $bilet['sefer_id'], ':fid' => $user['firma_id']]);
        if (!$firmaCheck->fetch()) {
            echo json_encode(['ok' => false, 'message' => 'Bu bilet size bağlı bir firmaya ait değil.']);
            exit;
        }
    }

    // User sadece kendi biletini iptal edebilir
    if ($user_role === 'user' && $bilet['user_id'] != $user_id) {
        echo json_encode(['ok' => false, 'message' => 'Bu bilet size ait değil.']);
        exit;
    }

    if ($bilet['durum'] === 'iptal') {
        echo json_encode(['ok' => false, 'message' => 'Bu bilet zaten iptal edilmiş.']);
        exit;
    }

    // 🕐 1 saat kuralı
    $kalkisStr = $bilet['tarih'] . ' ' . $bilet['saat'];
    $kalkis = new DateTime($kalkisStr, new DateTimeZone('Europe/Istanbul'));
    $now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));

    if ($kalkis->getTimestamp() - $now->getTimestamp() < 3600) {
        echo json_encode(['ok' => false, 'message' => 'Kalkışa 1 saatten az kaldığı için iptal yapılamaz.']);
        exit;
    }

    // 💳 Transaction
    $db->beginTransaction();

    // Bilet iptali
    $upd = $db->prepare("UPDATE biletler SET durum = 'iptal' WHERE id = :id");
    $upd->execute([':id' => $bilet_id]);

    // Kullanıcı bakiyesini iade et (sadece user rolü)
    if ($user_role === 'user') {
        $fiyat = (float)$bilet['fiyat'];
        $refund = $db->prepare("UPDATE users SET balance = balance + :f WHERE id = :uid");
        $refund->execute([':f' => $fiyat, ':uid' => $user_id]);
    }

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Bilet başarıyla iptal edildi. ' .
            ($user_role === 'user' ? $bilet['fiyat'] . '₺ iade edildi.' : '')
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['ok' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
