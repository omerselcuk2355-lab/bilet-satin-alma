<?php
session_start();

// 🚨 Yalnızca firma admin girebilir (Admin girmemeli)
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'firma_admin') {
    header('Location: /frontend/pages/index.php');
    exit;
}

$is_logged_in = isset($_SESSION['user']);
$user_name = htmlspecialchars($_SESSION['user']['name']);
$firma_id = $_SESSION['user']['firma_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma Admin Paneli | Selçuk Tur</title>
<link rel="stylesheet" href="/frontend/assets/css/main.css">
<style>
body { background: radial-gradient(circle at top, #0d0d0d, #000); color: #fff; font-family: 'Poppins', sans-serif; margin: 0; padding: 0; }
.container { max-width: 1000px; margin: 40px auto; padding: 20px; }
h1 { text-align: center; font-size: 2rem; background: linear-gradient(90deg,#00d4ff,#8e2de2);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent; margin-bottom: 30px; }
.btn-add { background: linear-gradient(90deg,#00d4ff,#8e2de2); color: #fff; border:none;padding:10px 18px;
    border-radius:8px;font-weight:500;cursor:pointer;display:block;margin:0 auto 25px auto;transition:0.3s; }
.btn-add:hover { opacity:.85; transform:scale(1.02);}
.card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px;
    padding:20px;margin-bottom:15px; box-shadow:0 0 10px rgba(0,212,255,0.2);transition:.3s;}
.card:hover { box-shadow:0 0 15px rgba(142,45,226,0.4); transform:translateY(-3px);}
.card h3 {margin:0;font-size:1.2rem;color:#00d4ff;}
.card p {margin:8px 0;color:#ccc;line-height:1.4;}
.btn {border:none;padding:7px 14px;border-radius:6px;cursor:pointer;font-weight:500;margin-right:5px;transition:.2s;}
.btn-edit {background:#ffc107;color:#222;}
.btn-delete {background:#dc3545;color:white;}
.btn-secondary {background:#007bff;color:white;}
.btn-edit:hover,.btn-delete:hover,.btn-secondary:hover {opacity:.8;}
nav {display:flex;align-items:center;justify-content:space-between;padding:15px 30px;background:rgba(0,0,0,0.7);border-bottom:1px solid rgba(255,255,255,0.1);}
nav .logo {display:flex;align-items:center;gap:10px;}
nav .logo img {width:50px;border-radius:8px;}
nav .links a {color:#fff;margin-left:15px;text-decoration:none;font-weight:500;transition:.2s;}
nav .links a:hover {color:#00d4ff;}
footer {text-align:center;margin-top:40px;color:#888;font-size:.9rem;}
.modal { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:none;align-items:center;justify-content:center;}
.modal.active {display:flex;}
.modal-content { background:#111;border-radius:12px;padding:25px;width:450px;max-height:90vh;overflow:auto;
    box-shadow:0 0 15px rgba(0,212,255,0.3);}
.modal-content h2 {margin-top:0;color:#00d4ff;text-align:center;}
.modal-content label {display:block;margin-top:10px;font-size:.9rem;color:#ccc;}
.modal-content input { width:100%;padding:8px;border:none;border-radius:6px;margin-top:5px;}
.modal-content button { margin-top:15px;width:100%;padding:10px;border:none;border-radius:8px;
    background:linear-gradient(90deg,#00d4ff,#8e2de2);color:#fff;font-weight:500;cursor:pointer;}
.modal-content button:hover {opacity:0.9;}
</style>
</head>
<body>

<nav>
  <div class="logo">
    <img src="/frontend/assets/img/logo.jpeg" alt="Selçuk Tur">
    <div style="font-size:1.3rem;font-weight:600;
        background:linear-gradient(90deg,#00d4ff,#8e2de2);
        -webkit-background-clip:text;-webkit-text-fill-color:transparent;">
        Selçuk Tur
    </div>
  </div>
  <div class="links">
    <a href="/frontend/pages/index.php">Ana Sayfa</a>
    <a href="/frontend/pages/firma_kuponlar.php">Kuponlar</a>
    <?php if ($is_logged_in): ?>
      <span style="color:#00d4ff;">Hoş geldin, <?= $user_name ?></span>
      <a href="/frontend/pages/logout.php" style="color:#ff5c5c;">Çıkış</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container">
  <h1>Firma Admin Paneli</h1>
  <button class="btn-add" onclick="modalAc()">+ Sefer Ekle</button>
  <div id="seferListesi"></div>
</div>

<!-- 🔹 Sefer Ekle/Güncelle Modal -->
<div id="ekleModal" class="modal">
  <div class="modal-content">
    <h2 id="modalBaslik">Yeni Sefer Ekle</h2>
    <form id="ekleForm">
      <input type="hidden" name="id" id="seferId">
      <label>Kalkış</label><input type="text" name="kalkis" id="kalkis" required>
      <label>Varış</label><input type="text" name="varis" id="varis" required>
      <label>Tarih</label><input type="date" name="tarih" id="tarih" required>
      <label>Saat</label><input type="time" name="saat" id="saat" required>
      <label>Fiyat (₺)</label><input type="number" name="fiyat" id="fiyat" required>
      <label>Koltuk Sayısı</label><input type="number" name="koltuk_sayisi" id="koltuk_sayisi" required value="40">
      <input type="hidden" name="firma_id" value="<?= $firma_id ?>">
      <button type="submit" id="submitBtn">Kaydet</button>
      <button type="button" style="background:#444;margin-top:8px;" onclick="modalKapat()">İptal</button>
    </form>
  </div>
</div>

<!-- 🔹 Bilet Görüntüleme Modal -->
<div id="biletModal" class="modal">
  <div class="modal-content">
    <h2>Biletler</h2>
    <div id="biletListesi">Yükleniyor...</div>
    <button type="button" style="background:#444;margin-top:8px;" onclick="biletModalKapat()">Kapat</button>
  </div>
</div>

<footer>© 2025 Selçuk Tur • Firma Yönetim Paneli</footer>

<script>
let duzenlemeModu = false;

function modalAc() {
  document.getElementById('ekleModal').classList.add('active');
  document.getElementById('modalBaslik').textContent = duzenlemeModu ? 'Seferi Düzenle' : 'Yeni Sefer Ekle';
  document.getElementById('submitBtn').textContent = duzenlemeModu ? 'Güncelle' : 'Kaydet';
}
function modalKapat() {
  document.getElementById('ekleModal').classList.remove('active');
  document.getElementById('ekleForm').reset();
  document.getElementById('seferId').value = '';
  duzenlemeModu = false;
}
function biletModalKapat() {
  document.getElementById('biletModal').classList.remove('active');
}

async function seferleriGetir() {
  const res = await fetch('/backend/api/firma.php?action=list');
  const data = await res.json();
  const div = document.getElementById('seferListesi');
  if (data.ok && data.data.length > 0) {
    div.innerHTML = data.data.map(s => `
      <div class="card">
        <h3>${s.kalkis} → ${s.varis}</h3>
        <p>Firma: <b>${s.firma_adi}</b><br>Tarih: ${s.tarih} • Saat: ${s.saat}<br>
        Fiyat: <b>${s.fiyat}₺</b> | Koltuk: ${s.koltuk_sayisi}</p>
        <div>
          <button class="btn btn-secondary" onclick="biletleriGoster(${s.id})">Biletleri Gör</button>
          <button class="btn btn-edit" onclick='seferDuzenle(${JSON.stringify(s)})'>Düzenle</button>
          <button class="btn btn-delete" onclick="seferSil(${s.id})">Sil</button>
        </div>
      </div>`).join('');
  } else div.innerHTML = "<p style='color:#999;text-align:center;'>Henüz sefer bulunmuyor.</p>";
}

async function biletleriGoster(sefer_id) {
  document.getElementById('biletModal').classList.add('active');
  const list = document.getElementById('biletListesi');
  list.innerHTML = "Yükleniyor...";
  try {
    const res = await fetch(`/backend/api/bilet.php?action=firma_list&sefer_id=${sefer_id}`);
    const data = await res.json();
    if (data.ok && data.data.length > 0) {
      list.innerHTML = data.data.map(b => `
        <div class="card" style="background:rgba(255,255,255,0.03);">
          <p><b>${b.ad_soyad}</b> (${b.email})<br>
          Koltuk: ${b.koltuk_no} • Fiyat: ${b.fiyat}₺<br>
          Durum: ${b.status === 'iptal' ? '❌ İptal Edildi' : '✅ Aktif'}</p>
          ${b.status !== 'iptal' ? `<button class="btn btn-delete" onclick="biletIptal(${b.id})">Bileti İptal Et</button>` : ''}
        </div>`).join('');
    } else list.innerHTML = "<p style='color:#999;text-align:center;'>Bu sefere ait bilet yok.</p>";
  } catch (err) {
    list.innerHTML = "<p style='color:red;'>Sunucu hatası!</p>";
  }
}

async function biletIptal(bilet_id) {
  if (!confirm("Bu bileti iptal etmek istiyor musunuz?")) return;
  const fd = new FormData();
  fd.append("id", bilet_id);
  const res = await fetch('/backend/api/bilet.php?action=cancel', { method: 'POST', body: fd });
  const data = await res.json();
  alert(data.message);
  if (data.ok) document.querySelector(`#biletListesi button[onclick='biletIptal(${bilet_id})']`)?.parentElement.remove();
}

function seferDuzenle(s) {
  duzenlemeModu = true;
  document.getElementById('seferId').value = s.id;
  document.getElementById('kalkis').value = s.kalkis;
  document.getElementById('varis').value = s.varis;
  document.getElementById('tarih').value = s.tarih;
  document.getElementById('saat').value = s.saat;
  document.getElementById('fiyat').value = s.fiyat;
  document.getElementById('koltuk_sayisi').value = s.koltuk_sayisi;
  modalAc();
}

async function seferSil(id) {
  if (!confirm("Bu seferi silmek istediğine emin misin?")) return;
  const formData = new FormData();
  formData.append("id", id);
  const res = await fetch('/backend/api/firma.php?action=delete', { method: 'POST', body: formData });
  const data = await res.json();
  alert(data.message);
  if (data.ok) seferleriGetir();
}

document.getElementById('ekleForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const action = duzenlemeModu ? 'update' : 'add';
  const res = await fetch(`/backend/api/firma.php?action=${action}`, { method: 'POST', body: formData });
  const data = await res.json();
  alert(data.message);
  if (data.ok) { modalKapat(); seferleriGetir(); }
});

seferleriGetir();
</script>
</body>
</html>