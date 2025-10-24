<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Güvenlik kontrolü
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/config.php'; // config.php dosyanızın yolunu kontrol edin.

header('Content-Type: application/json; charset=utf-8');

function clean_input($str) {
    // Trim ve boşlukları temizleme
    return trim($str ?? '');
}

$kalkis = clean_input($_GET['kalkis'] ?? '');
$varis 	= clean_input($_GET['varis'] ?? '');
$tarih 	= clean_input($_GET['tarih'] ?? '');

// 💡 Debug için arama parametrelerini saklayalım
$debug_params = [
    'raw_kalkis' => $kalkis,
    'raw_varis' => $varis,
    'raw_tarih' => $tarih,
];

try {
    // =========================================================
    // 🔹 1. Kalkış ve Varış Noktalarını Harf Duyarsız Hale Getir
    // =========================================================
    // Hem Türkçe karakter desteği için mb_strtolower kullanılır
    $search_kalkis = mb_strtolower($kalkis, 'UTF-8');
    $search_varis  = mb_strtolower($varis, 'UTF-8');
    
    // =========================================================
    // 🔹 2. Tarih Formatını Normalize Et (YYYY-MM-DD)
    // =========================================================
    $search_tarih = '';
    if (!empty($tarih)) {
        // YYYY-MM-DD formatını dener (Tarayıcı input[type="date"]'den gelen standart format)
        $tarih_obj = date_create_from_format('Y-m-d', $tarih); 
        if (!$tarih_obj) {
            // DD.MM.YYYY formatını dener
            $tarih_obj = date_create_from_format('d.m.Y', $tarih); 
        }
        if (!$tarih_obj) {
            // DD-MM-YYYY formatını dener
            $tarih_obj = date_create_from_format('d-m-Y', $tarih); 
        }
        
        if ($tarih_obj) {
            $search_tarih = $tarih_obj->format('Y-m-d');
        } else {
            // Hiçbir format eşleşmezse, veritabanındaki hatalı kayıtlara karşı olduğu gibi kullanırız.
            $search_tarih = $tarih; 
        }
    }
    
    // Debug parametrelerine normalize edilmiş değerleri ekle
    $debug_params['search_kalkis'] = $search_kalkis;
    $debug_params['search_varis'] = $search_varis;
    $debug_params['search_tarih'] = $search_tarih; // Nihai arama tarihi bu!

    // =========================================================
    // 🔹 3. SQL Sorgusunu Hazırla (Nihai Sorgu: Kalkış, Varış ve Tarih ile)
    // =========================================================
    
    $query = "SELECT 
                s.id, s.kalkis, s.varis, s.tarih, s.saat, s.fiyat, s.koltuk_sayisi, 
                f.ad AS firma_adi
              FROM seferler s
              JOIN firmalar f ON s.firma_id = f.id
              WHERE LOWER(s.kalkis) = :kalkis 
                AND LOWER(s.varis) = :varis 
                AND s.tarih = :tarih"; // <<< TARİH ŞARTI GERİ EKLENDİ!

    $stmt = $db->prepare($query);
    
    // Sorguyu çalıştır
    $stmt->execute([
        ':kalkis' => $search_kalkis,
        ':varis' => $search_varis,
        ':tarih' => $search_tarih // Normalize edilmiş tarih kullanılıyor
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Başarılı yanıt
    echo json_encode([
        'ok' => true,
        'count' => count($rows),
        'seferler' => $rows,
        'debug' => $debug_params // Arama parametrelerini görmek için
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Veritabanı hatası
    echo json_encode([
        'ok' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage(),
        'seferler' => [],
        'debug' => $debug_params
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>