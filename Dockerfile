# --- Stage 1: Install PHP Dependencies (needed for Ziggy) ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.* ./
# Install without scripts first to get the vendor files
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# --- Stage 2: Build Frontend Assets ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
# Copy everything from current dir
COPY . .
# IMPORTANT: Copy vendor from Stage 1 so Ziggy is visible to Vite/Rolldown
COPY --from=composer-builder /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Production Environment ---
FROM dunglas/frankenphp:1.2-php8.3-alpine

# Install Postgres and Zip extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

WORKDIR /var/www/html

# 1. Copy application code
COPY . .

# 2. Bring in compiled assets
COPY --from=frontend-builder /app/public/build ./public/build

# 3. Bring in vendor and composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=composer-builder /app/vendor ./vendor
RUN composer dump-autoload --optimize --no-dev

# 4. Permissions Fixes
# Ensure the binary is executable and reset permissions
RUN chmod +x /usr/local/bin/frankenphp && \
    chown -R www-data:www-data storage bootstrap/cache

# --- THE CRITICAL FIX FOR RENDER ---
# We bypass the default entrypoint script to avoid the "Operation not permitted" error.
ENTRYPOINT ["/usr/local/bin/frankenphp"]

EXPOSE 8000

# Start the server directly
CMD ["php-server", "--listen", ":8000"]
