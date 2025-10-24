<?php
session_start();

$is_logged_in = isset($_SESSION['user']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user']['name']) : '';
$user_role = $is_logged_in ? ($_SESSION['user']['role'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selçuk Tur | Yolculuğun Ötesine...</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <nav>
        <div class="logo" style="display:flex;align-items:center;gap:10px;">
            <img src="../assets/img/logo.jpeg" alt="Selçuk Tur" width="70">
            <div style="font-size:1.4rem;font-weight:600;background:linear-gradient(90deg,#00d4ff,#8e2de2);
                         -webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                Selçuk Tur
            </div>
        </div>

        <div class="links">
            <a href="/frontend/pages/index.php">Ana Sayfa</a>

            <?php if ($is_logged_in): ?>
                <span style="color:#00d4ff;">Hoş geldin, <?= $user_name ?></span>

                <?php if ($user_role === 'admin'): ?>
                    <a href="/frontend/pages/admin_panel.php" style="color:#ffd700;font-weight:600;">⚙️ Admin Panel</a>

                <?php elseif ($user_role === 'firma_admin'): ?>
                    <a href="/frontend/pages/firma_panel.php" style="color:#00ff99;font-weight:600;">🏢 Firma Panel</a>
                    <a href="/frontend/pages/account.php" style="color:#00d4ff;font-weight:600;">👤 Hesabım</a>

                <?php elseif ($user_role === 'user'): ?>
                    <a href="/frontend/pages/account.php" style="color:#00d4ff;font-weight:600;">👤 Hesabım</a>
                <?php endif; ?>

                <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış</a>
            <?php else: ?>
                <a href="/frontend/pages/login.php">Giriş</a>
                <a href="/frontend/pages/register.php">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (isset($_GET['login']) && $_GET['login'] === 'success' && $is_logged_in): ?>
        <div style="
            background:rgba(0,255,150,0.1);
            border:1px solid #00ff99;
            color:#00ff99;
            text-align:center;
            padding:10px;
            border-radius:8px;
            margin:15px auto;
            width:fit-content;
            font-family:'Poppins',sans-serif;">
            ✅ Giriş başarılı! Hoş geldin <?= $user_name ?> 🚀
        </div>
    <?php endif; ?>

    <section class="hero">
        <h1>Yolculuğun Ötesine 🚀</h1>
        <p>Galaksinin dört bir yanına güvenli ve konforlu yolculuk.</p>

        <form id="searchForm" class="search-box">
            <input type="text" name="kalkis" placeholder="Kalkış Noktası (örn. Bursa)" required>
            <input type="text" name="varis" placeholder="Varış Noktası (örn. Antalya)" required>
            <input type="date" name="tarih" required>
            <button type="submit">Sefer Ara</button>
        </form>

        <div id="seferSonuclari" style="margin-top: 2rem; text-align:center;"></div>
    </section>

    <footer>
        © 2025 Selçuk Tur • Yolculuğun Ötesine...
    </footer>

    <script>
    document.getElementById("searchForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const form = e.target;
        const sonucDiv = document.getElementById("seferSonuclari");
        sonucDiv.innerHTML = "<p style='color:#bbb;'>🔍 Seferler aranıyor...</p>";

        const kalkis = form.kalkis.value.trim();
        const varis = form.varis.value.trim();
        let tarih = form.tarih.value;

        if (tarih.includes('.')) {
            const p = tarih.split('.');
            tarih = `${p[2]}-${p[1].padStart(2,'0')}-${p[0].padStart(2,'0')}`;
        }

        const params = new URLSearchParams({ kalkis, varis, tarih });

        try {
            const resp = await fetch("/backend/api/sefer.php?" + params);
            const data = await resp.json();

            if (data.ok && data.count > 0) {
                let html = "<h3 style='color:#00d4ff;'>🚍 Uygun Seferler</h3><div style='display:flex;flex-direction:column;gap:1rem;align-items:center;margin-top:1rem;'>";
                data.seferler.forEach(s => {
                    html += `
                        <div style="width:90%;max-width:500px;background:rgba(255,255,255,0.1);
                            padding:15px;border-radius:12px;box-shadow:0 0 10px rgba(0,212,255,0.3);text-align:left;">
                            <h4 style="margin:0 0 5px 0;color:#fff;">${s.kalkis} → ${s.varis}</h4>
                            <p style="margin:4px 0;color:#ccc;">
                                Firma: <b>${s.firma_adi}</b><br>
                                Tarih: ${s.tarih} • Saat: ${s.saat}<br>
                                Fiyat: <b>${s.fiyat}₺</b>
                            </p>
                            <button onclick="detay(${s.id})" style="
                                background:linear-gradient(90deg,#00d4ff,#8e2de2);
                                color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;">
                                Bilet Al
                            </button>
                        </div>`;
                });
                html += "</div>";
                sonucDiv.innerHTML = html;
            } else {
                sonucDiv.innerHTML = "<p style='color:#ff5c5c;'>❌ Uygun sefer bulunamadı.</p>";
            }
        } catch (err) {
            sonucDiv.innerHTML = "<p style='color:#ff5c5c;'>⚠️ Sunucu hatası: " + err.message + "</p>";
        }
    });

    function detay(id) {
    <?php if (!$is_logged_in): ?>
        location.href = '/frontend/pages/login.php';
    <?php elseif ($user_role === 'firma_admin'): ?>
        location.href = `/frontend/pages/sefer_detay.php?id=${id}`;
    <?php elseif ($user_role === 'user'): ?>
        location.href = `/frontend/pages/sefer_detay_user.php?id=${id}`;
    <?php elseif ($user_role === 'admin'): ?>
        alert('Admin kullanıcıları bilet satın alamaz.');
    <?php endif; ?>
    }
    </script>
</body>
</html>
