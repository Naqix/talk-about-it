FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
