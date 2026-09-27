# Local development image: mirrors the production stack (PHP 8.3 + Apache + SQLite;
# pdo_sqlite ships with the official image).
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd exif \
 && a2enmod rewrite headers expires deflate \
 && rm -rf /var/lib/apt/lists/*

COPY apache.conf /etc/apache2/conf-available/garage.conf
COPY php.ini /usr/local/etc/php/conf.d/garage.ini
RUN a2enconf garage
