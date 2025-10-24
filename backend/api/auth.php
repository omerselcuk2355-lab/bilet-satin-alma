<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// response helper
function json_res($ok, $msg = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action !== 'logout') {
    json_res(false, 'Sadece POST kabul edilir.');
}

// REGISTER
if ($action === 'register') {
    $name = clean_str($_POST['name'] ?? '');
    $email = strtolower(clean_str($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf'] ?? '';

    if (!verify_csrf($csrf)) json_res(false, 'Geçersiz token.');

    if (!$name || !$email || !$password) json_res(false, 'Tüm alanları doldurun.');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_res(false, 'Geçerli bir e-posta girin.');

    // parola politikası: en az 6 karakter (istediğinizi sıkılaştırın)
    if (strlen($password) < 6) json_res(false, 'Parola en az 6 karakter olmalı.');

    // check existing email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) json_res(false, 'Bu e-posta zaten kayıtlı.');

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $insert = $db->prepare("INSERT INTO users (name, email, password, role, balance) VALUES (:name, :email, :password, 'user', 0)");
    try {
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed
        ]);
        json_res(true, 'Kayıt başarılı. Giriş yapabilirsiniz.');
    } catch (PDOException $e) {
        json_res(false, 'Kayıt başarısız: ' . $e->getMessage());
    }
}

// LOGIN
if ($action === 'login') {
    $email = strtolower(clean_str($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf'] ?? '';

    if (!verify_csrf($csrf)) json_res(false, 'Geçersiz token.');

    if (!login_attempt_allowed()) {
        json_res(false, 'Çok fazla başarısız giriş denemesi. Bir süre sonra tekrar deneyin.');
    }

    if (!$email || !$password) {
        login_attempt_register();
        json_res(false, 'E-posta ve parola gerekli.');
    }

    $stmt = $db->prepare("SELECT id, password, role, name, balance, firma_id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        login_attempt_register();
        json_res(false, 'E-posta veya parola hatalı.');
    }

    // başarılı giriş
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $email,
        'name' => $user['name'],
        'role' => $user['role'],
        'balance' => $user['balance'],
        'firma_id' => $user['firma_id']
    ];

    login_attempt_reset();

    json_res(true, 'Giriş başarılı.', ['user' => $_SESSION['user']]);
}

// LOGOUT
if ($action === 'logout') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    // yönlendirme isteyen frontend için JSON dönüyoruz
    json_res(true, 'Çıkış yapıldı.');
}

json_res(false, 'Geçersiz işlem.');