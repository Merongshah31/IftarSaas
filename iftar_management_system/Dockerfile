FROM php:8.3-cli

WORKDIR /var/www/html

# System deps + PostgreSQL extension for Supabase
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Copy app source
COPY . .

# Render injects PORT at runtime
EXPOSE 10000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-10000}"]
