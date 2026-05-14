# --- Stage 1: Install PHP Dependencies ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.* ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# --- Stage 2: Build Frontend Assets ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
COPY --from=composer-builder /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Production Environment (Apache) ---
FROM php:8.3-apache

# 1. Install dependencies using apt (Debian) instead of apk (Alpine)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

# 2. Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# 3. Set Document Root to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 4. Copy application code and assets
COPY . .
COPY --from=frontend-builder /app/public/build ./public/build

# 5. Bring in vendor and composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=composer-builder /app/vendor ./vendor
RUN composer dump-autoload --optimize --no-dev

# 6. Set Permissions
RUN chown -R www-data:www-data storage bootstrap/cache
# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD php artisan migrate --force && apache2-foreground
