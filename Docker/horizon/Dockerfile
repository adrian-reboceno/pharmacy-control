FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    bash \
    curl \
    unzip \
    git \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    autoconf \
    g++ \
    make \
    openssl-dev \
    pcre-dev

# Extensiones PHP
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    pcntl \
    bcmath \
    zip \
    intl \
    opcache

# MongoDB via PECL
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Redis via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

CMD ["php", "artisan", "horizon"]
