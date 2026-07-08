# ---- Étape 1 : Build des dépendances PHP avec Composer ----
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Étape 2 : Image finale avec PHP-FPM + Nginx ----
FROM php:8.2-fpm-alpine

# Dépendances système + extensions PHP nécessaires pour Laravel
RUN apk add --no-cache \
        nginx \
        supervisor \
        libpng-dev \
        libzip-dev \
        zip \
        unzip \
        oniguruma-dev \
        curl-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd

WORKDIR /var/www/html

# Copie de l'app avec les dépendances déjà installées
COPY --from=vendor /app ./

# Permissions Laravel (storage & cache doivent être writables)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
# Configuration Supervisor (lance nginx + php-fpm ensemble)
COPY docker/supervisord.conf /etc/supervisord.conf
# Script de démarrage (migrations, cache config, etc.)
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]