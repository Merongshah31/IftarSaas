FROM php:8.4-cli

WORKDIR /var/www/html

# System deps + PostgreSQL extension for Supabase
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copy app source
COPY . .

# Ensure framework cache/view/session directories exist for runtime on Render
RUN mkdir -p storage/framework/views \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render injects PORT at runtime
EXPOSE 10000

CMD ["sh", "-c", "php artisan package:discover --ansi && php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-10000}"]
