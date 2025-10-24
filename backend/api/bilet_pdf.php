<?php
error_reporting(0);
ini_set('display_errors', 0);

// Çıktı tamponlarını ve header'ları sıfırla
while (ob_get_level()) ob_end_clean();
if (!headers_sent()) header_remove();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

// Oturum ve yetki kontrolü
if (empty($_SESSION['user'])) {
    http_response_code(403);
    exit('Erişim reddedildi.');
}

$user = $_SESSION['user'];
if (($user['role'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Erişim reddedildi.');
}

$user_id  = (int) $user['id'];
$bilet_id = (int) ($_GET['id'] ?? 0);
if ($bilet_id <= 0) {
    http_response_code(400);
    exit('Geçersiz bilet ID.');
}

// Bilet bilgilerini getir
$stmt = $db->prepare("
    SELECT b.id, b.user_id, b.koltuk_no, b.fiyat, b.created_at AS olusturma_tarihi,
           s.kalkis, s.varis, s.tarih, s.saat, f.ad AS firma_adi
    FROM biletler b
    JOIN seferler s ON b.sefer_id = s.id
    JOIN firmalar f ON s.firma_id = f.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$bilet_id, $user_id]);
$bilet = $stmt->fetch();

if (!$bilet) {
    http_response_code(404);
    exit('Bilet bulunamadı.');
}

// PDF oluştur
while (ob_get_level()) ob_end_clean();

$pdf = new TCPDF();
$pdf->SetCreator('Selçuk Tur');
$pdf->SetAuthor('Selçuk Tur');
$pdf->SetTitle('Bilet #' . $bilet['id']);
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 12);

$html = '
<h2 style="text-align:center;">' . htmlspecialchars($bilet['firma_adi']) . ' Bilet</h2>
<hr>
<table cellspacing="6" cellpadding="4" border="0">
<tr><td><b>Firma:</b></td><td>' . htmlspecialchars($bilet['firma_adi']) . '</td></tr>
<tr><td><b>Yolcu:</b></td><td>' . htmlspecialchars($user['name']) . '</td></tr>
<tr><td><b>Rota:</b></td><td>' . htmlspecialchars($bilet['kalkis']) . ' → ' . htmlspecialchars($bilet['varis']) . '</td></tr>
<tr><td><b>Tarih:</b></td><td>' . htmlspecialchars($bilet['tarih']) . ' - ' . htmlspecialchars($bilet['saat']) . '</td></tr>
<tr><td><b>Koltuk No:</b></td><td>' . (int) $bilet['koltuk_no'] . '</td></tr>
<tr><td><b>Fiyat:</b></td><td>' . number_format($bilet['fiyat'], 2) . ' ₺</td></tr>
<tr><td><b>Oluşturma Tarihi:</b></td><td>' . htmlspecialchars($bilet['olusturma_tarihi']) . '</td></tr>
</table>
<hr>
<p style="text-align:center;font-style:italic;">' . htmlspecialchars($bilet['firma_adi']) . ' - www.selcuktur.com</p>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('bilet_' . $bilet['id'] . '.pdf', 'I');
exit;
?>
