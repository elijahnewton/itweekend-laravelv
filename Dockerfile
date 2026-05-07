# --- Stage 1: Build Frontend Assets ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 2: Production PHP Environment ---
FROM dunglas/frankenphp:1.2-php8.3-alpine

# Install Postgres and Zip extensions (Core requirements)
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

WORKDIR /var/www/html

# Copy application code
COPY . .
# Bring in the compiled assets from Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Install Composer dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for the web server user
RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8000

# Start FrankenPHP
CMD ["frankenphp", "php-server", "--listen", ":8000"]
