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
FROM php:8.3-apache-alpine

# Install Postgres and system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache intl

# Enable Apache mod_rewrite for Laravel/Slim/etc routing
RUN a2enmod rewrite || sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf

# Set Document Root to public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/httpd.conf /etc/apache2/conf.d/*.conf

WORKDIR /var/www/html

# 1. Copy application code
COPY . .

# 2. Assets from Stage 2
COPY --from=frontend-builder /app/public/build ./public/build

# 3. Vendor from Stage 1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=composer-builder /app/vendor ./vendor
RUN composer dump-autoload --optimize --no-dev

# 4. Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Render uses port 80 or 10000 usually, but we'll stick to 80 for Apache
EXPOSE 80

CMD ["apache2-foreground"]
