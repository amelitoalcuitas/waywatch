FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./

# Install dependencies (include dev for local development)
RUN composer install --no-interaction --prefer-dist

# Copy application code
COPY . .

EXPOSE 9000
CMD ["php-fpm"]
