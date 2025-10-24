<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_res($ok, $msg = '', $data = []) {
    echo json_encode(['ok' => $ok, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔒 Yetki Kontrolü: Sadece 'admin' rolü
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    json_res(false, 'Bu işleme yetkiniz yok.');
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if (!$action) json_res(false, 'Geçersiz işlem.');

try {
    // 1️⃣ FİRMA ADMİNLERİNİ LİSTELE (veya tüm kullanıcıları filtrele)
    if ($action === 'list') {
        $stmt = $db->query("
            SELECT u.id, u.name, u.email, u.role, f.ad AS firma_adi
            FROM users u
            LEFT JOIN firmalar f ON u.firma_id = f.id
            WHERE u.role IN ('firma_admin', 'user') -- Sadece firma admin ve user'ları gösterelim. Admin hariç.
            ORDER BY u.role DESC, u.id DESC
        ");
        json_res(true, 'Kullanıcılar listelendi.', ['users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    
    // Geri kalan işlemler POST olmalı
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_res(false, 'Sadece POST kabul edilir.');
    }

    // 2️⃣ YENİ KULLANICI OLUŞTUR / FİRMA ADMİN ATA
    if ($action === 'create_admin') {
        $name = clean_str($_POST['name'] ?? '');
        $email = strtolower(clean_str($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = clean_str($_POST['role'] ?? 'user'); // 'firma_admin' veya 'user' olabilir
        $firma_id = intval($_POST['firma_id'] ?? 0); // Sadece role 'firma_admin' ise gerekli

        if (!$name || !$email || !$password) json_res(false, 'Tüm alanları doldurun.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_res(false, 'Geçerli bir e-posta girin.');
        if (strlen($password) < 6) json_res(false, 'Parola en az 6 karakter olmalı.');

        // E-posta kontrolü
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) json_res(false, 'Bu e-posta zaten kayıtlı.');

        // Firma Admin ataması kontrolü
        if ($role === 'firma_admin' && $firma_id <= 0) {
            json_res(false, 'Firma Admin rolü için bir firma seçilmelidir.');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $firma_assign = $role === 'firma_admin' ? $firma_id : NULL;

        $insert = $db->prepare("INSERT INTO users (name, email, password, role, balance, firma_id) 
                                VALUES (:name, :email, :password, :role, 0, :firma_id)");
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed,
            ':role' => $role,
            ':firma_id' => $firma_assign
        ]);
        
        $message = $role === 'firma_admin' ? 'Yeni Firma Admin başarıyla oluşturuldu ve firmaya atandı.' : 'Yeni kullanıcı başarıyla oluşturuldu.';
        json_res(true, $message);
    }
    
    // 3️⃣ KULLANICI ROL/FİRMA GÜNCELLE
    if ($action === 'update_user') {
        $id = intval($_POST['id'] ?? 0);
        $role = clean_str($_POST['role'] ?? 'user');
        $firma_id = intval($_POST['firma_id'] ?? 0);
        
        if ($id <= 0) json_res(false, 'Geçersiz kullanıcı ID.');
        if (!in_array($role, ['user', 'firma_admin'])) json_res(false, 'Geçersiz rol.');
        
        // Firma Admin kontrolü
        $firma_assign = NULL;
        if ($role === 'firma_admin') {
            if ($firma_id <= 0) json_res(false, 'Firma Admin rolü için bir firma seçilmelidir.');
            $firma_assign = $firma_id;
        }

        $update = $db->prepare("UPDATE users SET role = :role, firma_id = :firma_id WHERE id = :id AND role != 'admin'");
        $update->execute([
            ':role' => $role,
            ':firma_id' => $firma_assign,
            ':id' => $id
        ]);

        json_res(true, 'Kullanıcı rolü/ataması güncellendi.');
    }


} catch (PDOException $e) {
    json_res(false, 'Veritabanı hatası: ' . $e->getMessage());
}

json_res(false, 'Tanımsız işlem.');
?>