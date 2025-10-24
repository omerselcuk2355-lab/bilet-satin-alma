<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

$seferler = [
    [1, 'İstanbul', 'Ankara', '2025-10-25', '09:00', 450, 40],
    [1, 'Ankara', 'İzmir', '2025-10-26', '10:30', 480, 40],
    [1, 'Bursa', 'Antalya', '2025-10-27', '08:00', 520, 40],
    [1, 'İzmir', 'İstanbul', '2025-10-25', '14:00', 470, 40],
];

try {
    $stmt = $db->prepare("INSERT INTO seferler (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($seferler as $s) {
        $stmt->execute($s);
    }
    echo "✅ 4 örnek sefer başarıyla eklendi!";
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>
