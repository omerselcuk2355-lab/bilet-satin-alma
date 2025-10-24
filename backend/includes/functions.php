<?php
if (!defined('SECURE_ACCESS')) define('SECURE_ACCESS', true);

// Basit CSRF token oluşturma/denetleme
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Temizleme yardımcıları
function clean_str($s) {
    return trim(htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

// Basit brute-force koruması (session tabanlı)
function login_attempt_register() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }
    $_SESSION['login_attempts']++;
}

function login_attempt_allowed() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $max = 5; // izin verilen deneme sayısı
    $window = 300; // seconds => 5 dakika
    if (!isset($_SESSION['login_attempts'])) return true;
    if (time() - ($_SESSION['first_attempt_time'] ?? 0) > $window) {
        // pencereyi sıfırla
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
        return true;
    }
    return $_SESSION['login_attempts'] < $max;
}

function login_attempt_reset() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}
?>
