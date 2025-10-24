<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

require_once __DIR__ . '/config.php';

error_reporting(0);
ini_set('display_errors', 0);

$db_file = __DIR__ . '/../database/bilet.db';

// Eğer veritabanı doluysa, sadece eksikleri ekleyeceğiz
$new_install = !file_exists($db_file) || filesize($db_file) === 0;

try {
    $db->exec("PRAGMA foreign_keys = ON;");

    // USERS
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            balance REAL DEFAULT 0,
            firma_id INTEGER DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // FİRMALAR
    $db->exec("
        CREATE TABLE IF NOT EXISTS firmalar (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ad TEXT NOT NULL,
            logo_yolu TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // SEFERLER
    $db->exec("
        CREATE TABLE IF NOT EXISTS seferler (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            firma_id INTEGER NOT NULL,
            kalkis TEXT NOT NULL,
            varis TEXT NOT NULL,
            tarih TEXT NOT NULL,
            saat TEXT NOT NULL,
            fiyat REAL NOT NULL,
            koltuk_sayisi INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE CASCADE
        );
    ");

    // BİLETLER
    $db->exec("
        CREATE TABLE IF NOT EXISTS biletler (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            sefer_id INTEGER NOT NULL,
            koltuk_no INTEGER NOT NULL,
            fiyat REAL NOT NULL,
            durum TEXT DEFAULT 'aktif',
            total_price REAL DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (sefer_id) REFERENCES seferler(id) ON DELETE CASCADE
        );
    ");

    // KUPONLAR
    $db->exec("
        CREATE TABLE IF NOT EXISTS kuponlar (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kod TEXT UNIQUE NOT NULL,
            oran INTEGER NOT NULL,
            kullanim_limiti INTEGER DEFAULT 1,
            son_tarih TEXT NOT NULL,
            firma_id INTEGER DEFAULT NULL,
            global INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // BOOKED_SEATS (Yeni)
    $db->exec("
        CREATE TABLE IF NOT EXISTS booked_seats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bilet_id INTEGER NOT NULL,
            koltuk_no INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bilet_id) REFERENCES biletler(id) ON DELETE CASCADE
        );
    ");

    // USER_COUPONS (Yeni)
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            kupon_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (kupon_id) REFERENCES kuponlar(id) ON DELETE CASCADE
        );
    ");

    // ÖRNEK VERİLER (yalnızca yeni kurulumda)
    if ($new_install) {
        $db->exec("INSERT INTO firmalar (ad) VALUES ('Selçuk Tur')");
        $db->exec("
            INSERT INTO seferler (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi)
            VALUES (1, 'Bursa', 'Antalya', '2025-10-27', '09:00', 250, 30)
        ");
        $db->exec("
            INSERT INTO seferler (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi)
            VALUES (1, 'İzmir', 'İstanbul', '2025-10-28', '13:30', 300, 35)
        ");
    }

    echo "✅ Veritabanı başarıyla oluşturuldu veya eksikler tamamlandı.";

} catch (PDOException $e) {
    echo "❌ Hata: " . htmlspecialchars($e->getMessage());
}
?>
