<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

define('BASE_URL', ''); // BASE_URL boşaltıldı

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (!empty($_SESSION['user'])) {
    header('Location: /frontend/pages/index.php'); // URL kısaltıldı
    exit;
}

// Logout sonrası bilgilendirme
$logout_success = (isset($_GET['logout']) && $_GET['logout'] === 'success');
if ($logout_success) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

// CSRF token oluştur
$csrf = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Giriş Yap | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css"> <style>
body {
    background: radial-gradient(circle at top, #1a1a1a, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
}
.auth-container {
    display: flex; justify-content: center; align-items: center;
    min-height: 100vh;
}
.auth-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 2rem; border-radius: 16px;
    width: 90%; max-width: 380px; text-align: center;
    box-shadow: 0 0 15px rgba(0,212,255,0.2);
}
.auth-box h2 {
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
}
.auth-box input {
    width: 100%; padding: 10px; margin: 8px 0;
    border: none; border-radius: 8px;
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.auth-box input::placeholder { color: #aaa; }
.auth-box button {
    width: 100%; padding: 10px; margin-top: 10px;
    border: none; border-radius: 8px;
    background: linear-gradient(90deg,#8e2de2,#00d4ff);
    color: #fff; font-weight: 600; cursor: pointer;
    transition: 0.3s;
}
.auth-box button:hover { opacity: 0.85; }
.auth-box p { margin-top: 10px; color: #bbb; }
.message-box {
    border-radius: 8px; padding: 10px; margin-top: 10px;
}
.message-ok {
    background: rgba(0,255,150,0.1);
    border: 1px solid #00ff99;
    color: #00ff99;
}
.message-error {
    background: rgba(255,0,0,0.1);
    border: 1px solid #ff5555;
    color: #ff5555;
}
</style>
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <h2>Giriş Yap</h2>

        <?php if ($logout_success): ?>
            <div class="message-box message-ok">✅ Başarıyla çıkış yaptınız.</div>
        <?php endif; ?>

        <form id="loginForm" method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Parola" required>
            <button type="submit">Giriş Yap</button>
        </form>

        <div id="result"></div>

        <p>Hesabın yok mu? <a href="register.php" style="color:#00d4ff;">Kayıt ol</a></p>
    </div>
</div>

<script>
const form = document.getElementById('loginForm');
const resultBox = document.getElementById('result');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    resultBox.innerHTML = "<p style='color:#ccc;'>⏳ Giriş yapılıyor...</p>";

    const data = new FormData(form);

    try {
        const resp = await fetch('/backend/api/auth.php?action=login', { // URL kısaltıldı
            method: 'POST',
            body: data
        });
        const json = await resp.json();

        resultBox.innerHTML = `
            <div class="message-box ${json.ok ? 'message-ok' : 'message-error'}">
                ${json.message}
            </div>
        `;

        if (json.ok) {
            setTimeout(() => {
                location.href = '/frontend/pages/index.php?login=success'; // URL kısaltıldı
            }, 1200);
        }
    } catch (err) {
        resultBox.innerHTML = `
            <div class="message-box message-error">
                Sunucu hatası: ${err.message}
            </div>
        `;
    }
});
</script>
</body>
</html>