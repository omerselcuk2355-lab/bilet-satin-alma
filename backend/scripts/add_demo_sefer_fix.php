<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

$db->prepare("INSERT INTO seferler 
    (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi)
    VALUES (1, 'İstanbul', 'Ankara', '2025-10-25', '09:00', 450, 40)")
   ->execute();

echo "✅ Demo sefer eklendi.";
?>
