<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 🚫 JSON çıktısını korumak için PHP uyarılarını bastır
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// 🚨 Oturum kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Sadece admin erişebilir
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi.']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {

        // 🔹 FİRMA LİSTELE
        case 'firma_list':
            $stmt = $db->query("SELECT id, ad FROM firmalar ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        // 🔹 FİRMA EKLE
        case 'firma_add':
            $ad = trim($_POST['ad'] ?? '');
            if (!$ad) {
                echo json_encode(['ok' => false, 'message' => 'Firma adı boş olamaz.']);
                exit;
            }

            $check = $db->prepare("SELECT COUNT(*) FROM firmalar WHERE ad = ?");
            $check->execute([$ad]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'message' => 'Bu firma zaten mevcut.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO firmalar (ad) VALUES (?)");
            $stmt->execute([$ad]);

            echo json_encode(['ok' => true, 'message' => 'Firma başarıyla eklendi.']);
            break;

        // 🔹 FİRMA SİL
        case 'firma_delete':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz firma ID.']);
                exit;
            }

            // Bağlı sefer veya admin varsa uyar
            $check = $db->prepare("SELECT COUNT(*) FROM seferler WHERE firma_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'message' => 'Bu firmaya bağlı seferler var, önce onları silin.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM firmalar WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['ok' => true, 'message' => 'Firma başarıyla silindi.']);
            break;

        // 🔹 FİRMA ADMIN LİSTELE
        case 'admin_list':
            $sql = "
                SELECT u.id, u.name, u.email, f.ad AS firma_adi
                FROM users u
                LEFT JOIN firmalar f ON u.firma_id = f.id
                WHERE u.role = 'firma_admin'
                ORDER BY u.id ASC
            ";
            $stmt = $db->query($sql);
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $admins], JSON_UNESCAPED_UNICODE);
            break;

        // 🔹 FİRMA ADMIN EKLE
        case 'admin_add':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $firma_id = $_POST['firma_id'] ?? null;

            if (!$name || !$email || !$password || !$firma_id) {
                echo json_encode(['ok' => false, 'message' => 'Tüm alanları doldurmanız gerekiyor.']);
                exit;
            }

            // Aynı e-posta var mı?
            $check = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'message' => 'Bu e-posta adresi zaten kayıtlı.']);
                exit;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (name, email, password, role, firma_id)
                VALUES (?, ?, ?, 'firma_admin', ?)
            ");
            $stmt->execute([$name, $email, $hashed, $firma_id]);

            echo json_encode(['ok' => true, 'message' => 'Firma admin başarıyla oluşturuldu.']);
            break;

        // 🔹 FİRMA ADMIN SİL
        case 'admin_delete':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz admin ID.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'firma_admin'");
            $stmt->execute([$id]);

            echo json_encode(['ok' => true, 'message' => 'Firma admin başarıyla silindi.']);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Hata: ' . htmlspecialchars($e->getMessage())]);
}
