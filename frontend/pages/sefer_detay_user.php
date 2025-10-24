<?php
session_start();
require_once __DIR__ . '/../../backend/includes/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: /frontend/pages/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$sefer_id = $_GET['id'] ?? 0;

// Kullanıcının mevcut bakiyesini al
$stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = (float)$stmt->fetchColumn();

// Sefer bilgisi
$stmt = $db->prepare("SELECT s.*, f.ad AS firma_adi FROM seferler s JOIN firmalar f ON s.firma_id=f.id WHERE s.id = ?");
$stmt->execute([$sefer_id]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sefer) {
    die("<p style='color:red;text-align:center;'>Sefer bulunamadı.</p>");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bilet Al | <?= htmlspecialchars($sefer['kalkis'] . ' → ' . $sefer['varis']) ?></title>
<link rel="stylesheet" href="/frontend/assets/css/main.css">
<style>
body {
    background: radial-gradient(circle at top, #0d0d0d, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    text-align: center;
}
.sefer-info {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 0 10px rgba(0,212,255,0.3);
}
.koltuklar {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 10px;
    justify-content: center;
    margin: 25px auto;
}
.koltuk {
    width: 40px;
    height: 40px;
    background: #444;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .2s;
    user-select: none;
}
.koltuk:hover { background: #00d4ff; }
.koltuk.secili { background: linear-gradient(90deg,#00d4ff,#8e2de2); }
.koltuk.dolu { background: #dc3545; cursor: not-allowed; }
form {
    margin-top: 20px;
}
input[type="text"], input[type="number"] {
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 8px;
    color: #fff;
    padding: 10px;
    width: 220px;
    margin-right: 10px;
}
button {
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    border: none;
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
}
button:hover { opacity: 0.85; }
.msg { margin-top:15px; }
.balance-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 0 10px rgba(0,212,255,0.2);
}
</style>
</head>
<body>

<nav>
    <div class="logo" style="display:flex;align-items:center;gap:10px;">
        <img src="/frontend/assets/img/logo.jpeg" alt="Selçuk Tur" width="60">
        <div style="font-size:1.3rem;font-weight:600;background:linear-gradient(90deg,#00d4ff,#8e2de2);
        -webkit-background-clip:text;-webkit-text-fill-color:transparent;">Selçuk Tur</div>
    </div>
    <div class="links">
        <a href="/frontend/pages/index.php">Ana Sayfa</a>
        <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış</a>
    </div>
</nav>

<div class="container">
    <div class="sefer-info">
        <h2><?= htmlspecialchars($sefer['kalkis']) ?> → <?= htmlspecialchars($sefer['varis']) ?></h2>
        <p>
            Firma: <b><?= htmlspecialchars($sefer['firma_adi']) ?></b><br>
            Tarih: <?= htmlspecialchars($sefer['tarih']) ?> • Saat: <?= htmlspecialchars($sefer['saat']) ?><br>
            Fiyat: <b><?= htmlspecialchars($sefer['fiyat']) ?> ₺</b> • Koltuk: <?= (int)$sefer['koltuk_sayisi'] ?><br>
            <b>Bakiyeniz:</b> <span id="userBalance"><?= number_format($balance, 2) ?> ₺</span>
        </p>
    </div>

    <h3>🎟️ Koltuk Seç</h3>
    <div id="koltuklar" class="koltuklar"></div>

    <form id="biletForm">
        <input type="hidden" name="sefer_id" value="<?= (int)$sefer['id'] ?>">
        <input type="hidden" name="koltuk_no" id="koltuk_no">
        <input type="text" name="kupon_kodu" placeholder="Kupon Kodu (isteğe bağlı)">
        <button type="submit">Satın Al</button>
    </form>

    <div id="msg" class="msg"></div>

    <div class="balance-box">
        <h3>💳 Bakiye Yükle</h3>
        <form id="bakiyeForm">
            <input type="number" name="miktar" id="miktar" placeholder="Yüklenecek Tutar (₺)" min="1" step="1" required>
            <button type="submit">Yükle</button>
        </form>
        <div id="bakiyeMsg" style="margin-top:10px;"></div>
    </div>
</div>

<footer style="text-align:center;margin-top:30px;color:#888;">© 2025 Selçuk Tur</footer>

<script>
const seferId = <?= (int)$sefer['id'] ?>;
const koltuklarDiv = document.getElementById("koltuklar");
const msg = document.getElementById("msg");
const bakiyeMsg = document.getElementById("bakiyeMsg");
let secili = null;

// 🧩 Koltukları yükle
async function yukleKoltuklar() {
    const res = await fetch(`/backend/api/koltuk_durum.php?sefer_id=${seferId}`); // URL kısaltıldı
    const data = await res.json();
    const dolu = data.dolu || [];
    koltuklarDiv.innerHTML = '';
    for (let i = 1; i <= <?= (int)$sefer['koltuk_sayisi'] ?>; i++) {
        const div = document.createElement('div');
        div.className = 'koltuk';
        div.textContent = i;
        if (dolu.includes(i)) div.classList.add('dolu');
        div.onclick = () => {
            if (div.classList.contains('dolu')) return;
            document.querySelectorAll('.koltuk').forEach(k => k.classList.remove('secili'));
            div.classList.add('secili');
            secili = i;
            document.getElementById('koltuk_no').value = i;
        };
        koltuklarDiv.appendChild(div);
    }
}

// 🎟️ Bilet satın alma işlemi
document.getElementById("biletForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!secili) {
        msg.innerHTML = "<p style='color:#ff5c5c;'>Lütfen bir koltuk seçin.</p>";
        return;
    }

    const formData = new FormData(e.target);
    msg.innerHTML = "<p style='color:#ccc;'>İşlem yapılıyor...</p>";

    try {
        const res = await fetch("/backend/api/bilet_satin_al.php", { // URL kısaltıldı
            method: "POST",
            body: formData
        });
        const data = await res.json();
        msg.innerHTML = `<p style='color:${data.ok ? "#00ff99" : "#ff5c5c"};'>${data.message}</p>`;
        if (data.ok) {
            yukleKoltuklar();
            // Bilet satın alındıysa bakiyeyi güncelle
            guncelBakiyeyiCek();
        }
    } catch (err) {
        msg.innerHTML = "<p style='color:#ff5c5c;'>Sunucu hatası.</p>";
    }
});

// 💳 Bakiye yükleme
document.getElementById("bakiyeForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const miktar = document.getElementById("miktar").value;
    bakiyeMsg.innerHTML = "<p style='color:#ccc;'>İşlem yapılıyor...</p>";

    try {
        const res = await fetch("/backend/api/bakiye_yukle.php", { // URL kısaltıldı
            method: "POST",
            body: new FormData(e.target)
        });
        const data = await res.json();
        bakiyeMsg.innerHTML = `<p style='color:${data.ok ? "#00ff99" : "#ff5c5c"};'>${data.message}</p>`;
        if (data.ok) {
            guncelBakiyeyiCek();
            e.target.reset();
        }
    } catch (err) {
        bakiyeMsg.innerHTML = "<p style='color:#ff5c5c;'>Sunucu hatası.</p>";
    }
});

// 🪙 Güncel bakiye bilgisi çek
async function guncelBakiyeyiCek() {
    const res = await fetch("/backend/api/get_balance.php"); // URL kısaltıldı
    const data = await res.json();
    if (data.ok) document.getElementById("userBalance").textContent = data.balance.toFixed(2) + " ₺";
}

yukleKoltuklar();
</script>
</body>
</html>