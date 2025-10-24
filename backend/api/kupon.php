<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Oturum geçersiz.']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$firma_id = (int)($_SESSION['user']['firma_id'] ?? 0);

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        // 🔹 KUPOANLARI LİSTELE
        case 'list':
            if ($role === 'admin') {
                // Admin tüm kuponları görebilir
                $stmt = $db->query("
                    SELECT k.id, k.kod, k.oran, k.kullanim_limiti, k.son_tarih, k.global, f.ad AS firma_adi
                    FROM kuponlar k
                    LEFT JOIN firmalar f ON k.firma_id = f.id
                    ORDER BY k.son_tarih ASC
                ");
            } else {
                // Firma admin sadece kendi kuponlarını görebilir
                $stmt = $db->prepare("
                    SELECT id, kod, oran, kullanim_limiti, son_tarih, global
                    FROM kuponlar
                    WHERE (firma_id = :fid OR global = 1)
                    ORDER BY son_tarih ASC
                ");
                $stmt->execute([':fid' => $firma_id]);
            }

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        // 🔹 KUPOAN EKLE
        case 'add':
            $kod   = strtoupper(trim($_POST['kod'] ?? ''));
            $oran  = (int)($_POST['oran'] ?? 0);
            $limit = (int)($_POST['kullanim_limiti'] ?? 1);
            $tarih = trim($_POST['son_tarih'] ?? '');
            $hedef_firma = (int)($_POST['firma_id'] ?? $firma_id);

            if (!$kod || !$oran || !$tarih) {
                echo json_encode(['ok' => false, 'message' => 'Tüm alanları doldurun.']);
                exit;
            }

            // Aynı kodu önle
            $check = $db->prepare("SELECT COUNT(*) FROM kuponlar WHERE kod = ?");
            $check->execute([$kod]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'message' => 'Bu kupon kodu zaten mevcut.']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO kuponlar (kod, oran, kullanim_limiti, son_tarih, firma_id, global)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$kod, $oran, $limit, $tarih, $hedef_firma]);
            echo json_encode(['ok' => true, 'message' => 'Kupon başarıyla eklendi.']);
            break;

        // 🔹 KUPOAN GÜNCELLE
        case 'update':
            $id    = (int)($_POST['id'] ?? 0);
            $kod   = strtoupper(trim($_POST['kod'] ?? ''));
            $oran  = (int)($_POST['oran'] ?? 0);
            $limit = (int)($_POST['kullanim_limiti'] ?? 1);
            $tarih = trim($_POST['son_tarih'] ?? '');

            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz kupon ID.']);
                exit;
            }

            // Firma admin kendi kuponunu güncelleyebilir, admin her şeyi
            if ($role === 'firma_admin') {
                $check = $db->prepare("SELECT firma_id FROM kuponlar WHERE id = ?");
                $check->execute([$id]);
                $owner = $check->fetchColumn();
                if ($owner != $firma_id) {
                    echo json_encode(['ok' => false, 'message' => 'Bu kupon size ait değil.']);
                    exit;
                }
            }

            $stmt = $db->prepare("
                UPDATE kuponlar
                SET kod=?, oran=?, kullanim_limiti=?, son_tarih=?
                WHERE id=?
            ");
            $stmt->execute([$kod, $oran, $limit, $tarih, $id]);
            echo json_encode(['ok' => true, 'message' => 'Kupon başarıyla güncellendi.']);
            break;

        // 🔹 KUPOAN SİL
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz kupon ID.']);
                exit;
            }

            if ($role === 'firma_admin') {
                $check = $db->prepare("SELECT firma_id FROM kuponlar WHERE id = ?");
                $check->execute([$id]);
                $owner = $check->fetchColumn();
                if ($owner != $firma_id) {
                    echo json_encode(['ok' => false, 'message' => 'Bu kupon size ait değil.']);
                    exit;
                }
            }

            $stmt = $db->prepare("DELETE FROM kuponlar WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Kupon başarıyla silindi.']);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Hata: ' . htmlspecialchars($e->getMessage())]);
}
