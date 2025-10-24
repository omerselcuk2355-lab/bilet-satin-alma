<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user'])) {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    $isApi = (stripos($accept, 'application/json') !== false)
        || ($xhr === 'xmlhttprequest')
        || (strpos($uri, '/api/') !== false);

    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode(['ok' => false, 'message' => 'Giriş gerekli']);
        exit;
    }

    header('Location: /bilet-satin-alma/frontend/pages/login.php', true, 302);
    exit;
}
?>
