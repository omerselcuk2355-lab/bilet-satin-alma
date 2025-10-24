<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 🔒 Kullanıcı oturumu kontrolü
if (!isset($_SESSION['user'])) {
    header("Location: /frontend/pages/login.php"); // URL kısaltıldı
    exit;
}

// 🔌 Veritabanı bağlantısı
require_once __DIR__ . '/../../backend/includes/config.php';

// 👤 Kullanıcı bilgileri
$user_id = $_SESSION['user']['id'];
$user_name = htmlspecialchars($_SESSION['user']['name'] ?? 'Kullanıcı');

// 💰 Kullanıcının bakiyesini sorgula
$stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesabım | Selçuk Tur</title>
    <link rel="stylesheet" href="/frontend/assets/css/main.css"> </head>
<body>

<nav>
    <div class="logo" style="display:flex;align-items:center;gap:10px;">
        <img src="/frontend/assets/img/logo.jpeg" width="60" alt=""> <span style="font-weight:600;font-size:1.3rem;">Selçuk Tur</span>
    </div>
    <div class="links">
        <a href="/frontend/pages/index.php">Ana Sayfa</a> <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış Yap</a> </div>
</nav>

<section class="hero" style="text-align:center;">
    <h2>👋 Merhaba, <?php echo $user_name; ?>!</h2>
    <h3>💰 Mevcut Bakiye: 
        <span id="bakiye" style="color:#00d4ff;">
            <?php echo number_format($balance, 2); ?> ₺
        </span>
    </h3>

    <form id="bakiyeForm" style="margin-top:20px;">
        <label>Bakiye Yükle (₺):</label><br>
        <input type="number" name="miktar" min="1" required 
            style="padding:8px;border-radius:5px;border:1px solid #444;background:#111;color:#fff;">
        <button type="submit" style="
            background:linear-gradient(90deg,#00d4ff,#8e2de2);
            color:#fff;border:none;padding:8px 16px;
            border-radius:6px;cursor:pointer;margin-left:8px;">
            Yükle
        </button>
    </form>

    <div id="bakiyeMesaj" style="margin-top:10px;color:#ddd;"></div>

    <hr style="width:60%;margin:30px auto;border-color:#333;">

    <h3>🎟️ Satın Alınan Biletler</h3>

    <?php
    $bilet_stmt = $db->prepare("
        SELECT b.id, s.kalkis, s.varis, s.tarih, s.saat, b.koltuk_no, b.fiyat, b.durum
        FROM biletler b
        JOIN seferler s ON b.sefer_id = s.id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
    ");
    $bilet_stmt->execute([$user_id]);
    $biletler = $bilet_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($biletler):
        echo "<table style='margin:20px auto;border-collapse:collapse;width:95%;max-width:900px;color:#fff;'>";
        echo "<tr style='background:#222;'>
                <th>ID</th>
                <th>Rota</th>
                <th>Tarih</th>
                <th>Saat</th>
                <th>Koltuk</th>
                <th>Fiyat</th>
                <th>Durum</th>
                <th>İşlem</th>
                <th>PDF</th>
              </tr>";

        foreach ($biletler as $b) {
            echo "<tr style='text-align:center;background:#111;border-bottom:1px solid #333;'>";
            echo "<td>{$b['id']}</td>";
            echo "<td>{$b['kalkis']} → {$b['varis']}</td>";
            echo "<td>{$b['tarih']}</td>";
            echo "<td>{$b['saat']}</td>";
            echo "<td>{$b['koltuk_no']}</td>";
            echo "<td>{$b['fiyat']} ₺</td>";
            echo "<td style='color:" . ($b['durum']=='aktif'?'#00d4ff':'#ff5c5c') . ";'>{$b['durum']}</td>";

            // 🧾 İptal butonu
            if ($b['durum'] === 'aktif') {
                echo "<td>
                        <button onclick='biletIptalEt({$b['id']})'
                            style=\"background:#ff5c5c;border:none;color:#fff;
                                        padding:6px 10px;border-radius:5px;cursor:pointer;\">
                            İptal Et
                        </button>
                      </td>";
            } else {
                echo "<td><span style='color:gray;'>-</span></td>";
            }

            // 🧩 PDF butonu
            echo "<td>
                        <a href='/backend/api/bilet_pdf.php?id={$b['id']}' target='_blank'
                        style=\"background:#00d4ff;color:#000;padding:6px 10px;
                                     border-radius:5px;text-decoration:none;font-weight:600;\">
                            İndir
                        </a>
                      </td>"; // URL kısaltıldı

            echo "</tr>";
        }

        echo "</table>";
    else:
        echo "<p style='color:#aaa;'>Henüz hiç bilet satın alınmamış.</p>";
    endif;
    ?>
</section>

<footer style="text-align:center;margin-top:40px;color:#555;">
    © 2025 Selçuk Tur • Yolculuğun Ötesine...
</footer>

<script>
// 💰 Bakiye Yükleme
document.getElementById('bakiyeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const resp = await fetch('/backend/api/bakiye_yukle.php', { // URL kısaltıldı
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    const msg = document.getElementById('bakiyeMesaj');
    msg.innerText = data.message;
    msg.style.color = data.ok ? "#00d4ff" : "#ff5c5c";
    if (data.ok) {
        const eklenen = parseFloat(formData.get('miktar'));
        const bakiyeEl = document.getElementById('bakiye');
        const mevcut = parseFloat(bakiyeEl.innerText);
        bakiyeEl.innerText = (mevcut + eklenen).toFixed(2) + " ₺";
        e.target.reset();
    }
});

// 🎟️ Bilet İptal Etme
function biletIptalEt(biletId) {
    if (!confirm("Bu bileti iptal etmek istediğinize emin misiniz?")) return;

    fetch("/backend/api/bilet_iptal.php", { // URL kısaltıldı
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "bilet_id=" + encodeURIComponent(biletId)
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.ok) location.reload();
        })
        .catch(err => alert("Hata: " + err.message));
}
</script>

</body>
</html>