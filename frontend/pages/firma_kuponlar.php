<?php
require_once __DIR__ . '/../../backend/includes/config.php';

// 🔒 Firma admin veya admin erişimi zorunlu
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['firma_admin', 'admin'])) {
    header('Location: /frontend/pages/index.php'); // URL kısaltıldı
    exit;
}

$user = $_SESSION['user'];
$user_name = htmlspecialchars($user['name']);
$firma_id = $user['firma_id'] ?? null;
$role = $user['role'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kupon Yönetimi | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css"> <style>
body {
    background: radial-gradient(circle at top, #0d0d0d, #000);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0; padding: 0;
}
.container { max-width: 900px; margin: 40px auto; padding: 20px; }
h1 {
    text-align: center;
    font-size: 2rem;
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    margin-bottom: 30px;
}
.btn-add {
    background: linear-gradient(90deg,#00d4ff,#8e2de2);
    color: #fff; border: none; padding: 10px 18px;
    border-radius: 8px; font-weight: 500; cursor: pointer;
    display: block; margin: 0 auto 25px auto; transition: 0.3s;
}
.btn-add:hover { opacity: .85; transform: scale(1.02);}
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

nav { display:flex; align-items:center; justify-content:space-between; padding:15px 30px; background:rgba(0,0,0,0.7); border-bottom:1px solid rgba(255,255,255,0.1);}
nav .logo { display:flex; align-items:center; gap:10px;}
nav .logo img { width:50px; border-radius:8px;}
nav .links a {
    color:#fff; margin-left:15px; text-decoration:none; font-weight:500; transition:0.2s;
    padding:6px 10px; border-radius:6px;
}
nav .links a:hover { color:#00d4ff; background:rgba(255,255,255,0.05);}
nav .active { background:linear-gradient(90deg,#00d4ff,#8e2de2); color:#fff !important; }

footer { text-align:center; margin-top:40px; color:#888; font-size:.9rem; }

.modal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:none; align-items:center; justify-content:center; }
.modal.active { display:flex; }
.modal-content { background:#111; border-radius:12px; padding:25px; width:400px; box-shadow:0 0 15px rgba(0,212,255,0.3);}
.modal-content h2 { margin-top:0; color:#00d4ff; text-align:center;}
.modal-content label { display:block; margin-top:10px; font-size:.9rem; color:#ccc;}
.modal-content input { width:100%; padding:8px; border:none; border-radius:6px; margin-top:5px;}
.modal-content button { margin-top:15px; width:100%; padding:10px; border:none; border-radius:8px; background:linear-gradient(90deg,#00d4ff,#8e2de2); color:#fff; font-weight:500; cursor:pointer;}
.modal-content button:hover { opacity:0.9;}
</style>
</head>
<body>

<nav>
    <div class="logo">
        <img src="/frontend/assets/img/logo.jpeg" alt="Selçuk Tur"> <div style="font-size:1.3rem;font-weight:600;
            background:linear-gradient(90deg,#00d4ff,#8e2de2);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;">
            Selçuk Tur
        </div>
    </div>

    <div class="links">
        <?php if ($role === 'firma_admin'): ?>
            <a href="/frontend/pages/firma_panel.php">Sefer Yönetimi</a> <?php else: ?>
            <a href="/frontend/pages/admin_panel.php">Admin Paneli</a> <?php endif; ?>
        <a href="/frontend/pages/firma_kuponlar.php" class="active">Kupon Yönetimi</a> <a href="/frontend/pages/index.php">Ana Sayfa</a> <span style="color:#00d4ff;">Hoş geldin, <?= $user_name ?></span>
        <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış</a> </div>
</nav>

<div class="container">
    <h1>Kupon Yönetimi <?= $role === 'admin' ? '(Tüm Firmalar)' : '' ?></h1>
    <button class="btn-add" onclick="modalAc()">+ Kupon Ekle</button>
    <div id="kuponListesi"></div>
</div>

<div id="kuponModal" class="modal">
  <div class="modal-content">
    <h2 id="modalBaslik">Yeni Kupon Ekle</h2>
    <form id="kuponForm">
      <input type="hidden" name="id" id="kuponId">
      <label>Kupon Kodu</label>
      <input type="text" name="kod" id="kod" required>
      <label>İndirim Oranı (%)</label>
      <input type="number" name="oran" id="oran" required min="1" max="100">
      <label>Kullanım Limiti</label>
      <input type="number" name="kullanim_limiti" id="kullanim_limiti" required min="1" value="1">
      <label>Son Kullanma Tarihi</label>
      <input type="date" name="son_tarih" id="son_tarih" required>

      <?php if ($role === 'admin'): ?>
      <label>Hedef Firma ID (isteğe bağlı)</label>
      <input type="number" name="firma_id" id="firma_id" placeholder="1">
      <?php endif; ?>

      <button type="submit" id="submitBtn">Kaydet</button>
      <button type="button" style="background:#444;margin-top:8px;" onclick="modalKapat()">İptal</button>
    </form>
  </div>
</div>

<footer>© 2025 Selçuk Tur • Kupon Yönetimi</footer>

<script>
let duzenlemeModu = false;

function modalAc() {
    document.getElementById('kuponModal').classList.add('active');
    document.getElementById('modalBaslik').textContent = duzenlemeModu ? 'Kuponu Düzenle' : 'Yeni Kupon Ekle';
    document.getElementById('submitBtn').textContent = duzenlemeModu ? 'Güncelle' : 'Kaydet';
}
function modalKapat() {
    document.getElementById('kuponModal').classList.remove('active');
    document.getElementById('kuponForm').reset();
    document.getElementById('kuponId').value = '';
    duzenlemeModu = false;
}

async function kuponlariGetir() {
    const res = await fetch('/backend/api/kupon.php?action=list'); // URL kısaltıldı
    const data = await res.json();
    const div = document.getElementById('kuponListesi');

    if (data.ok && data.data.length > 0) {
        div.innerHTML = data.data.map(k => `
            <div class="card">
                <h3>${k.kod}</h3>
                <p>
                    Oran: <b>${k.oran}%</b><br>
                    Limit: ${k.kullanim_limiti} • Son Tarih: ${k.son_tarih}<br>
                    Durum: ${k.global == 1 ? "🌍 Global" : "🏢 Firma Özel"}<br>
                    ${k.firma_adi ? `<small>Firma: <b>${k.firma_adi}</b></small>` : ""}
                </p>
                <div>
                    <button class="btn btn-edit" onclick='kuponDuzenle(${JSON.stringify(k)})'>Düzenle</button>
                    <button class="btn btn-delete" onclick="kuponSil(${k.id})">Sil</button>
                </div>
            </div>
        `).join('');
    } else {
        div.innerHTML = "<p style='color:#999;text-align:center;'>Henüz kupon bulunmuyor.</p>";
    }
}

function kuponDuzenle(k) {
    duzenlemeModu = true;
    document.getElementById('kuponId').value = k.id;
    document.getElementById('kod').value = k.kod;
    document.getElementById('oran').value = k.oran;
    document.getElementById('kullanim_limiti').value = k.kullanim_limiti;
    document.getElementById('son_tarih').value = k.son_tarih;
    modalAc();
}

async function kuponSil(id) {
    if (!confirm("Bu kuponu silmek istediğine emin misin?")) return;
    const formData = new FormData();
    formData.append("id", id);
    const res = await fetch('/backend/api/kupon.php?action=delete', { method: 'POST', body: formData }); // URL kısaltıldı
    const data = await res.json();
    alert(data.message);
    if (data.ok) kuponlariGetir();
}

document.getElementById('kuponForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const action = duzenlemeModu ? 'update' : 'add';
    const res = await fetch(`/backend/api/kupon.php?action=${action}`, { method: 'POST', body: formData }); // URL kısaltıldı
    const data = await res.json();
    alert(data.message);
    if (data.ok) {
        modalKapat();
        kuponlariGetir();
    }
});

kuponlariGetir();
</script>
</body>
</html>