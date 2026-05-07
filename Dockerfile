# --- Stage 1: Node.js for Frontend Assets ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 2: PHP Application ---
FROM dunglas/frankenphp:1.2-php8.3-alpine

# Install System dependencies & PHP extensions for PostgreSQL
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip intl opcache

WORKDIR /var/www/html

# Copy application code
COPY . .
# Copy compiled assets from Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for Laravel storage/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Use the non-root user for security
USER www-data

EXPOSE 8000

# FrankenPHP handles the web server
CMD ["frankenphp", "php-server", "--listen", ":8000"]
