FROM php:8.1-apache

#必要な拡張をインストール
RUN apt-get update && apt-get install -y unzip libzip-dev libpng-dev libonig-dev \
&& docker-php-ext-install pdo_mysql zip

#composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#Apacheのmod_rewrite有効化
RUN a2enmod rewrite

#DocumentRootをCakePHPに合わせてwebrootへ
RUN sed -i 's!/var/www/html!/var/www/html/webroot!' /etc/apache2/sites-available/000-default.conf

# 必要なライブラリ＆拡張を追加（← intlを追加）
RUN apt-get update && apt-get install -y \
	unzip \
	libzip-dev \
	libpng-dev \
	libonig-dev \
	libicu-dev \
	&& docker-php-ext-install pdo_mysql zip intl