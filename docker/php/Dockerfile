FROM php:8.2-fpm

# 必要なライブラリをインストール
RUN apt-get update && apt-get install -y \
    zip unzip curl libzip-dev libonig-dev libxml2-dev libpq-dev \
    libicu-dev g++ \
    && docker-php-ext-install pdo_mysql mbstring zip intl

# 作業ディレクトリを設定
WORKDIR /var/www

# Composer をコピー
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
