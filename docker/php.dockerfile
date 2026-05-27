FROM php:8.2-fpm-alpine

# Instala extensões e dependências essenciais do Laravel
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo_mysql bcmath gd

# Instala o Composer dentro do container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
