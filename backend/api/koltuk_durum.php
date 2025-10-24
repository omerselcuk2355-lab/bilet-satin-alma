<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$sefer_id = (int)($_GET['sefer_id'] ?? 0);
if (!$sefer_id) {
    echo json_encode(['ok' => false, 'dolu' => []]);
    exit;
}

$stmt = $db->prepare("SELECT koltuk_no FROM biletler WHERE sefer_id = ? AND durum = 'aktif'");
$stmt->execute([$sefer_id]);
$dolu = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'koltuk_no'));

echo json_encode(['ok' => true, 'dolu' => $dolu]);
