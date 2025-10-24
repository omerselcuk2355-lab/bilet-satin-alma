# 🚌 Bilet Satın Alma Sistemi (Docker + PHP 8.2 + SQLite)

**Selçuk Tur | Yolculuğun Ötesine 🚀**

Bu proje, PHP ve SQLite kullanılarak geliştirilmiş **çok rollü bir bilet satın alma sistemidir**.  
Uygulama, **Docker** ortamında kolayca çalıştırılabilir ve **Admin, Firma Admini, Kullanıcı** rollerini destekler.

---

## 🚀 Özellikler
- 👤 3 kullanıcı rolü (Admin / Firma Admini / User)
- 🎫 Sefer arama, koltuk seçme ve bilet satın alma
- 💳 Kupon ve bakiye sistemi
- 🏢 Firma bazlı yönetim paneli
- 📄 PDF bilet çıktısı
- 🧩 SQLite veritabanı
- 🐳 Docker ile tek komutta çalıştırılabilir yapı

---

## 🧑‍💻 Test Hesapları

| Rol | E-posta | Şifre |
|------|------------------|------------|
| 🧠 **Admin** | `johan@test.com` | `johan123!` |
| 🏢 **Firma Admini** | `selcuk@test.com` | `selcuk123!` |
| 👤 **Kullanıcı** | `user@test.com` | `user123!` |

---

## 🚌 Varsayılan Seferler

| Kalkış | Varış | Tarih |
|--------|--------|--------|
| Bursa | Antalya | 27-10-2025 |
| İzmir | İstanbul | 28-10-2025 |

---

## ⚙️ Kurulum (Docker Üzerinden)

1. Projeyi klonla:
   ```bash
   git clone https://github.com/omerselcuk2355-lab/bilet-satin-alma.git
   cd bilet-satin-alma

    Docker container’ını başlat:

docker compose up --build -d

Tarayıcıdan aç:

    http://localhost:8080/frontend/pages/index.php

🧠 Proje Yapısı

bilet-satin-alma/
│
├── backend/
│   ├── api/              # Tüm API uç noktaları (bilet, sefer, bakiye, vb.)
│   ├── includes/         # config.php, tcpdf gibi yardımcı dosyalar
│   └── database/         # SQLite veritabanı (bilet.db)
│
├── frontend/
│   ├── assets/           # CSS, JS, görseller
│   ├── pages/            # Tüm kullanıcı arayüzleri
│   └── index.php         # Ana sayfa (sefer arama)
│
├── docker-compose.yml     # Docker servis tanımı
├── Dockerfile             # PHP + Apache yapılandırması
└── README.md              # Bu dosya 🙂

🧩 Teknolojiler

    PHP 8.2 (Apache üzerinde)

    SQLite3

    HTML5 / CSS3 / JS

    Docker Desktop

🔒 Güvenlik Notları

    LFI / RFI, SQL Injection ve Session Hijacking korumaları aktif.

    Kullanıcılar yalnızca kendi biletlerine erişebilir.

    Firma adminleri yalnızca kendi seferlerini yönetebilir.

🧭 Geliştirici

👨‍💻 Ömer Selçuk
📧 omerselcuk2355@gmail.com
