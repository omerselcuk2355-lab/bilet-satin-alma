<?php
session_start();

// 🔒 Sadece admin erişimi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /frontend/pages/index.php'); // URL kısaltıldı
    exit;
}

$user_name = htmlspecialchars($_SESSION['user']['name']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Paneli | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css"> <style>
body {
    background: radial-gradient(circle at top, #0d0d0d, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0; padding: 0;
}
.container { max-width: 1100px; margin: 40px auto; padding: 20px; }
h1 {
    text-align: center;
    font-size: 2rem;
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    margin-bottom: 30px;
}
.tabs { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; }
.tab-btn {
    background: rgba(255,255,255,0.1); color: #fff; border: none;
    padding: 10px 20px; border-radius: 8px; font-weight: 500; cursor: pointer;
    transition: 0.3s;
}
.tab-btn.active { background: linear-gradient(90deg,#00d4ff,#8e2de2); }
.tab-btn:hover { opacity: .85; }

.card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px; padding: 20px; margin-bottom: 15px;
    box-shadow: 0 0 10px rgba(0,212,255,0.2); transition: 0.3s;
}
.card:hover { box-shadow: 0 0 15px rgba(142,45,226,0.4); transform: translateY(-3px);}
.card h3 { margin: 0; font-size: 1.2rem; color: #00d4ff;}
.card p { margin: 8px 0; color: #ccc; line-height: 1.4;}

.btn { border:none; padding:7px 14px; border-radius:6px; cursor:pointer; font-weight:500; margin-right:5px; transition:0.2s;}
.btn-edit { background:#ffc107; color:#222;}
.btn-delete { background:#dc3545; color:white;}
.btn-edit:hover,.btn-delete:hover { opacity:.8;}

.btn-add {
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    color: #fff; border: none; padding: 10px 18px; border-radius: 8px;
    font-weight: 500; cursor: pointer; display: block;
    margin: 0 auto 25px auto; transition: 0.3s;
}
.btn-add:hover { opacity: .85; transform: scale(1.02); }

nav {
    display:flex; align-items:center; justify-content:space-between;
    padding:15px 30px; background:rgba(0,0,0,0.7);
    border-bottom:1px solid rgba(255,255,255,0.1);
}
nav .logo { display:flex; align-items:center; gap:10px; }
nav .logo img { width:50px; border-radius:8px; }
nav .links a {
    color:#fff; margin-left:15px; text-decoration:none;
    font-weight:500; transition:0.2s; padding:6px 10px; border-radius:6px;
}
nav .links a:hover { color:#00d4ff; background:rgba(255,255,255,0.05);}
nav .active { background:linear-gradient(90deg,#00d4ff,#8e2de2); color:#fff !important; }

footer { text-align:center; margin-top:40px; color:#888; font-size:.9rem; }

/* Modal */
.modal { position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.7); display:none; align-items:center; justify-content:center; }
.modal.active { display:flex; }
.modal-content { background:#111; border-radius:12px; padding:25px; width:400px;
    box-shadow:0 0 15px rgba(0,212,255,0.3);}
.modal-content h2 { margin-top:0; color:#00d4ff; text-align:center;}
.modal-content label { display:block; margin-top:10px; font-size:.9rem; color:#ccc;}
.modal-content input, .modal-content select {
    width:100%; padding:8px; border:none; border-radius:6px; margin-top:5px;
}
.modal-content button {
    margin-top:15px; width:100%; padding:10px; border:none; border-radius:8px;
    background:linear-gradient(90deg,#00d4ff,#8e2de2); color:#fff; font-weight:500; cursor:pointer;
}
.modal-content button:hover { opacity:0.9; }
</style>
</head>
<body>

<nav>
    <div class="logo">
        <img src="/frontend/assets/img/logo.jpeg" alt="Selçuk Tur"> <div style="font-size:1.3rem;font-weight:600;
            background:linear-gradient(90deg,#00d4ff,#8e2de2);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;">
            Selçuk Tur Admin
        </div>
    </div>
    <div class="links">
        <a href="/frontend/pages/admin_panel.php" class="active">Admin Paneli</a> <a href="/frontend/pages/firma_kuponlar.php">Kupon Yönetimi</a> <a href="/frontend/pages/index.php">Ana Sayfa</a> <span style="color:#00d4ff;">Hoş geldin, <?= $user_name ?></span>
        <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış</a> </div>
</nav>

<div class="container">
    <h1>Yönetim Paneli</h1>

    <div class="tabs">
        <button class="tab-btn active" id="tabFirmalar" onclick="sekmeGoster('firmalar')">🏢 Firmalar</button>
        <button class="tab-btn" id="tabAdmins" onclick="sekmeGoster('admins')">👤 Firma Adminleri</button>
    </div>

    <div id="panelFirmalar">
        <button class="btn-add" onclick="modalAc('firma')">+ Firma Ekle</button>
        <div id="firmaListesi"></div>
    </div>

    <div id="panelAdmins" style="display:none;">
        <button class="btn-add" onclick="modalAc('admin')">+ Firma Admin Ekle</button>
        <div id="adminListesi"></div>
    </div>
</div>

<div id="firmaModal" class="modal">
  <div class="modal-content">
    <h2>Yeni Firma Ekle</h2>
    <form id="firmaForm">
      <label>Firma Adı</label>
      <input type="text" name="ad" id="firmaAd" required>
      <button type="submit">Kaydet</button>
      <button type="button" style="background:#444;margin-top:8px;" onclick="modalKapat('firma')">İptal</button>
    </form>
  </div>
</div>

<div id="adminModal" class="modal">
  <div class="modal-content">
    <h2>Yeni Firma Admin Ekle</h2>
    <form id="adminForm">
      <label>Ad Soyad</label>
      <input type="text" name="name" required>
      <label>E-Posta</label>
      <input type="email" name="email" required>
      <label>Şifre</label>
      <input type="password" name="password" required>
      <label>Bağlı Firma</label>
      <select name="firma_id" id="adminFirma"></select>
      <button type="submit">Kaydet</button>
      <button type="button" style="background:#444;margin-top:8px;" onclick="modalKapat('admin')">İptal</button>
    </form>
  </div>
</div>

<footer>© 2025 Selçuk Tur • Yönetim Paneli</footer>

<script>
// 🧱 Mevcut kodların tamamı olduğu gibi bırakıldı
document.addEventListener("DOMContentLoaded", () => {
    let aktifSekme = 'firmalar';

    function sekmeGoster(ad) {
        document.getElementById('panelFirmalar').style.display = (ad === 'firmalar') ? 'block' : 'none';
        document.getElementById('panelAdmins').style.display = (ad === 'admins') ? 'block' : 'none';
        document.getElementById('tabFirmalar').classList.toggle('active', ad === 'firmalar');
        document.getElementById('tabAdmins').classList.toggle('active', ad === 'admins');
    }

    window.sekmeGoster = sekmeGoster;

    function modalAc(tip) { document.getElementById(tip + 'Modal').classList.add('active'); }
    function modalKapat(tip) { document.getElementById(tip + 'Modal').classList.remove('active'); document.getElementById(tip + 'Form').reset(); }
    window.modalAc = modalAc; window.modalKapat = modalKapat;

    async function firmalariGetir() {
        const res = await fetch('/backend/api/admin.php?action=firma_list'); // URL kısaltıldı
        const data = await res.json();
        const div = document.getElementById('firmaListesi');
        const select = document.getElementById('adminFirma');
        if (!div || !select) return;
        select.innerHTML = '';
        if (data.ok && data.data.length > 0) {
            div.innerHTML = data.data.map(f => `
                <div class="card">
                    <h3>${f.ad}</h3>
                    <button class="btn btn-delete" onclick="firmaSil(${f.id})">Sil</button>
                </div>`).join('');
            data.data.forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.id; opt.textContent = f.ad;
                select.appendChild(opt);
            });
        } else {
            div.innerHTML = "<p style='color:#999;text-align:center;'>Henüz firma yok.</p>";
            const opt = document.createElement('option');
            opt.textContent = "Firma bulunamadı"; opt.value = "";
            select.appendChild(opt);
        }
    }

    async function firmaSil(id) {
        if (!confirm("Bu firmayı silmek istediğine emin misin?")) return;
        const fd = new FormData();
        fd.append("id", id);
        const res = await fetch('/backend/api/admin.php?action=firma_delete', { method: 'POST', body: fd }); // URL kısaltıldı
        const data = await res.json();
        alert(data.message);
        if (data.ok) firmalariGetir();
    }
    window.firmaSil = firmaSil;

    async function adminleriGetir() {
        const res = await fetch('/backend/api/admin.php?action=admin_list'); // URL kısaltıldı
        const data = await res.json();
        const div = document.getElementById('adminListesi');
        if (!div) return;
        if (data.ok && data.data.length > 0) {
            div.innerHTML = data.data.map(a => `
                <div class="card">
                    <h3>${a.name}</h3>
                    <p>${a.email} • Firma: ${a.firma_adi || 'Atanmamış'}</p>
                    <button class="btn btn-delete" onclick="adminSil(${a.id})">Sil</button>
                </div>`).join('');
        } else {
            div.innerHTML = "<p style='color:#999;text-align:center;'>Henüz firma admini yok.</p>";
        }
    }

    async function adminSil(id) {
        if (!confirm("Bu firma adminini silmek istediğine emin misin?")) return;
        const fd = new FormData(); fd.append("id", id);
        const res = await fetch('/backend/api/admin.php?action=admin_delete', { method: 'POST', body: fd }); // URL kısaltıldı
        const data = await res.json(); alert(data.message);
        if (data.ok) adminleriGetir();
    }
    window.adminSil = adminSil;

    const firmaForm = document.getElementById('firmaForm');
    if (firmaForm) {
        firmaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(firmaForm);
            const res = await fetch('/backend/api/admin.php?action=firma_add', { method:'POST', body:fd }); // URL kısaltıldı
            const data = await res.json(); alert(data.message);
            if (data.ok) { modalKapat('firma'); firmalariGetir(); }
        });
    }

    const adminForm = document.getElementById('adminForm');
    if (adminForm) {
        adminForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(adminForm);
            const res = await fetch('/backend/api/admin.php?action=admin_add', { method:'POST', body:fd }); // URL kısaltıldı
            const data = await res.json(); alert(data.message);
            if (data.ok) { modalKapat('admin'); adminleriGetir(); }
        });
    }

    firmalariGetir();
    adminleriGetir();
});
</script>
</body>
</html>