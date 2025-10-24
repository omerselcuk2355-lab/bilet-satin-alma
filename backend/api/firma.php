<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Oturum geçersiz. Giriş yapın.']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$firma_id = (int)($_SESSION['user']['firma_id'] ?? 0);

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            if ($role === 'firma_admin') {
                $stmt = $db->prepare("
                    SELECT s.id, f.ad AS firma_adi, s.kalkis, s.varis, s.tarih, s.saat, s.fiyat, s.koltuk_sayisi
                    FROM seferler s
                    JOIN firmalar f ON s.firma_id = f.id
                    WHERE s.firma_id = ?
                    ORDER BY s.tarih ASC
                ");
                $stmt->execute([$firma_id]);
            } elseif ($role === 'admin') {
                $stmt = $db->query("
                    SELECT s.id, f.ad AS firma_adi, s.kalkis, s.varis, s.tarih, s.saat, s.fiyat, s.koltuk_sayisi
                    FROM seferler s
                    JOIN firmalar f ON s.firma_id = f.id
                    ORDER BY s.tarih ASC
                ");
            } else {
                echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi.']);
                exit;
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        case 'add':
            $kalkis = trim($_POST['kalkis'] ?? '');
            $varis = trim($_POST['varis'] ?? '');
            $tarih = $_POST['tarih'] ?? '';
            $saat = $_POST['saat'] ?? '';
            $fiyat = (float)($_POST['fiyat'] ?? 0);
            $koltuk_sayisi = (int)($_POST['koltuk_sayisi'] ?? 40);

            if (empty($kalkis) || empty($varis) || empty($tarih) || empty($saat)) {
                echo json_encode(['ok' => false, 'message' => 'Eksik bilgi gönderildi.']);
                exit;
            }

            if ($role === 'firma_admin' && !$firma_id) {
                echo json_encode(['ok' => false, 'message' => 'Bu kullanıcıya ait firma tanımı yok.']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO seferler (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firma_id, $kalkis, $varis, $tarih, $saat, $fiyat, $koltuk_sayisi]);
            echo json_encode(['ok' => true, 'message' => 'Sefer başarıyla eklendi.']);
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $kalkis = trim($_POST['kalkis'] ?? '');
            $varis = trim($_POST['varis'] ?? '');
            $tarih = $_POST['tarih'] ?? '';
            $saat = $_POST['saat'] ?? '';
            $fiyat = (float)($_POST['fiyat'] ?? 0);
            $koltuk_sayisi = (int)($_POST['koltuk_sayisi'] ?? 40);

            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz sefer ID.']);
                exit;
            }

            if ($role === 'firma_admin') {
                $check = $db->prepare("SELECT firma_id FROM seferler WHERE id = ?");
                $check->execute([$id]);
                $owner = $check->fetchColumn();
                if ($owner != $firma_id) {
                    echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi: Bu sefer size ait değil.']);
                    exit;
                }
            }

            $stmt = $db->prepare("
                UPDATE seferler
                SET kalkis=?, varis=?, tarih=?, saat=?, fiyat=?, koltuk_sayisi=?
                WHERE id=? AND firma_id=?
            ");
            $stmt->execute([$kalkis, $varis, $tarih, $saat, $fiyat, $koltuk_sayisi, $id, $firma_id]);
            echo json_encode(['ok' => true, 'message' => 'Sefer başarıyla güncellendi.']);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'Geçersiz sefer ID.']);
                exit;
            }

            if ($role === 'firma_admin') {
                $check = $db->prepare("SELECT firma_id FROM seferler WHERE id = ?");
                $check->execute([$id]);
                $owner = $check->fetchColumn();
                if ($owner != $firma_id) {
                    echo json_encode(['ok' => false, 'message' => 'Erişim reddedildi: Bu sefer size ait değil.']);
                    exit;
                }
            }

            $stmt = $db->prepare("DELETE FROM seferler WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Sefer başarıyla silindi.']);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
