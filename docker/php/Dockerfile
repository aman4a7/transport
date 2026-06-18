FROM php:8.4-fpm

# ── System dependencies ──
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    netcat-openbsd \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Composer ──
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Application ──
# Dev: code + vendor are provided via volume mount in docker-compose.yml
# Production: COPY backend/ . && RUN composer install --no-dev --optimize-autoloader
WORKDIR /var/www/backend

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
