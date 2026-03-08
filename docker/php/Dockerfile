FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    sqlite-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Copy application code (vendor from local via volume mount)
COPY . .

EXPOSE 9000
CMD ["php-fpm"]
