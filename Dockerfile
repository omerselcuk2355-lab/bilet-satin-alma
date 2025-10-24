# PHP 8.2 + Apache tabanlı imaj
FROM php:8.2-apache

# Gerekli kütüphaneler (örneğin SQLite)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite aktif et
RUN a2enmod rewrite

# Projeyi container içine kopyala
COPY . /var/www/html/bilet/bilet-satin-alma/

# Apache kök dizinini proje konumuna yönlendir
RUN mkdir -p /var/www/html/bilet/bilet-satin-alma
RUN sed -i 's|/var/www/html|/var/www/html/bilet/bilet-satin-alma|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/|/var/www/html/bilet/bilet-satin-alma/|g' /etc/apache2/apache2.conf

# Çalışma dizini
WORKDIR /var/www/html/bilet/bilet-satin-alma/

# İzinleri ayarla
RUN chown -R www-data:www-data /var/www/html

# Web portunu aç
EXPOSE 80
