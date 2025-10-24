<?php
session_start();
require_once __DIR__ . '/../../backend/includes/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: /frontend/pages/login.php"); // URL kısaltıldı
    exit;
}

$sefer_id = $_GET['sefer_id'] ?? null;
if (!$sefer_id) {
    die("Geçersiz istek.");
}

// 🔹 Sefer bilgilerini çek
$stmt = $db->prepare("SELECT s.*, f.firma_adi 
                      FROM seferler s 
                      JOIN firmalar f ON s.firma_id = f.id 
                      WHERE s.id = ?");
$stmt->execute([$sefer_id]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sefer) {
    die("Sefer bulunamadı.");
}

$user_name = htmlspecialchars($_SESSION['user']['name']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Bilet Al | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css"> <style>
body {
    background: #f5f7fa;
    font-family: 'Poppins', sans-serif;
}
.container {
    width: 90%;
    max-width: 900px;
    margin: 3rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
h2 {
    text-align: center;
    color: #333;
}
.sefer-info {
    margin-bottom: 1.5rem;
    background: #f0f0f0;
    border-radius: 12px;
    padding: 1rem;
}
.koltuklar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    justify-items: center;
    margin-top: 1rem;
}
.koltuk {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.koltuk.dolu {
    background: #ff7b7b;
    cursor: not-allowed;
}
.koltuk.secilen {
    background: #4caf50;
    color: white;
}
.actions {
    text-align: center;
    margin-top: 2rem;
}
input[type="text"] {
    padding: 0.6rem;
    width: 250px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-top: 1rem;
}
button {
    padding: 0.8rem 2rem;
    border: none;
    background: #007bff;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}
button:hover {
    background: #0056b3;
}
#mesaj {
    text-align: center;
    margin-top: 1rem;
}
</style>
</head>
<body>
<div class="container">
    <h2>Bilet Satın Al</h2>
    <div class="sefer-info">
        <p><b>Firma:</b> <?= htmlspecialchars($sefer['firma_adi']) ?></p>
        <p><b>Kalkış:</b> <?= htmlspecialchars($sefer['kalkis']) ?> → <b>Varış:</b> <?= htmlspecialchars($sefer['varis']) ?></p>
        <p><b>Tarih:</b> <?= htmlspecialchars($sefer['tarih']) ?> | <b>Fiyat:</b> <?= number_format($sefer['fiyat'], 2) ?> ₺</p>
    </div>

    <h3>Koltuk Seçimi</h3>
    <div class="koltuklar" id="koltuklar"></div>

    <div class="actions">
        <label>Kupon Kodu:</label><br>
        <input type="text" id="kupon_kodu" placeholder="Varsa giriniz"><br>
        <p><b>Toplam Tutar:</b> <span id="toplam"><?= number_format($sefer['fiyat'], 2) ?></span> ₺</p>
        <button id="satinal">Satın Al</button>
        <div id="mesaj"></div>
    </div>
</div>

<script>
const seferId = <?= (int)$sefer_id ?>;
const fiyat = <?= (float)$sefer['fiyat'] ?>;
let secilenKoltuk = null;

const koltuklarDiv = document.getElementById("koltuklar");
for (let i = 1; i <= 24; i++) {
    const div = document.createElement("div");
    div.classList.add("koltuk");
    div.textContent = i;

    div.addEventListener("click", () => {
        if (div.classList.contains("dolu")) return;
        document.querySelectorAll(".koltuk.secilen").forEach(k => k.classList.remove("secilen"));
        div.classList.add("secilen");
        secilenKoltuk = i;
    });
    koltuklarDiv.appendChild(div);
}

document.getElementById("satinal").addEventListener("click", async () => {
    if (!secilenKoltuk) {
        alert("Lütfen bir koltuk seçiniz.");
        return;
    }

    const kupon = document.getElementById("kupon_kodu").value.trim();
    const formData = new FormData();
    formData.append("sefer_id", seferId);
    formData.append("koltuk_no", secilenKoltuk);
    formData.append("kupon_kodu", kupon);

    const res = await fetch("/backend/api/bilet_satin_al.php", { // URL kısaltıldı
        method: "POST",
        body: formData
    });

    const data = await res.json();
    const msgDiv = document.getElementById("mesaj");

    if (data.ok) {
        msgDiv.innerHTML = `<p style="color:green;">✅ ${data.message}</p>`;
        setTimeout(() => {
            window.location.href = "/frontend/pages/account.php"; // URL kısaltıldı
        }, 1500);
    } else {
        msgDiv.innerHTML = `<p style="color:red;">❌ ${data.message}</p>`;
    }
});
</script>
</body>
</html>