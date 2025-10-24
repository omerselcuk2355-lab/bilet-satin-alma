<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Çıkış Yapılıyor | Selçuk Tur</title>
<style>
body {
    background: radial-gradient(circle at top, #1a1a1a, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.logout-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 2rem;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0,212,255,0.2);
}
</style>
</head>
<body>

<div class="logout-box">
    <h2>🚪 Çıkış yapılıyor...</h2>
    <p>Lütfen bekleyin, yönlendiriliyorsunuz.</p>
</div>

<script>
(async ()=>{
    try {
        const resp = await fetch("/backend/api/auth.php?action=logout", { method: "POST" });
        const json = await resp.json();
        if (json.ok) {
            setTimeout(()=> location.href="/frontend/pages/login.php?logout=success", 1000);
        } else {
            alert(json.message || "Çıkış işlemi başarısız!");
            location.href="/frontend/pages/login.php";
        }
    } catch (err) {
        alert("Sunucu hatası: " + err.message);
        location.href="/frontend/pages/login.php";
    }
})();
</script>

</body>
</html>