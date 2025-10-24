<?php
session_start();
$csrf = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Kayıt Ol | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css">
<style>
body {
    background: radial-gradient(circle at top, #1a1a1a, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0;
}
.auth-container {
    width: 100%;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 2rem;
    border-radius: 16px;
    width: 90%;
    max-width: 380px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0,212,255,0.2);
}
.auth-box h2 {
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
}
.auth-box input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: none;
    margin: 8px 0;
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.auth-box input::placeholder { color: #aaa; }
.auth-box button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}
.auth-box button:hover { opacity: 0.85; }
.auth-box p { margin-top: 10px; color: #bbb; font-size: 0.9rem; }
#result { margin-top: 15px; font-size: 0.9rem; min-height: 24px; }
.message-box {
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
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
        <h2>Yeni Hesap Oluştur</h2>

        <form id="registerForm" method="post" action="/backend/api/auth.php?action=register">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="text" name="name" placeholder="Ad Soyad" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Parola (min 6 karakter)" required minlength="6">
            <button type="submit">Kayıt Ol</button>
        </form>

        <div id="result"></div>

        <p>Hesabın var mı? <a href="login.php" style="color:#00d4ff;">Giriş yap</a></p>
    </div>
</div>

<script>
const resultBox = document.getElementById('result');

document.getElementById('registerForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    resultBox.innerHTML = "<p style='color:#ccc;'>⏳ Hesap oluşturuluyor...</p>";

    try {
        const resp = await fetch(form.action, { method: 'POST', body: data });
        const json = await resp.json();

        // Mesaj kutusu biçimi
        resultBox.innerHTML = `
            <div class="message-box ${json.ok ? 'message-ok' : 'message-error'}">
                ${json.message}
            </div>
        `;

        if (json.ok) {
            resultBox.innerHTML += "<p style='color:#00d4ff;margin-top:5px;'>Yönlendiriliyorsunuz...</p>";
            setTimeout(()=>{ location.href='login.php'; }, 1500);
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