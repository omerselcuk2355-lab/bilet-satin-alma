<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$miktar = floatval($_POST['miktar'] ?? 0);

if ($miktar <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Geçersiz miktar.']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$miktar, $user_id]);
    echo json_encode(['ok' => true, 'message' => "💳 {$miktar}₺ başarıyla yüklendi!"]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>
