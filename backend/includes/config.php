<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

$db_path = __DIR__ . '/../database/bilet.db';

if (!is_dir(dirname($db_path))) {
    mkdir(dirname($db_path), 0755, true);
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    error_log('DB bağlantı hatası: ' . $e->getMessage());
    die(json_encode(['ok' => false, 'message' => 'Sistem hatası. Lütfen daha sonra tekrar deneyin.'], JSON_UNESCAPED_UNICODE));
}
?>
