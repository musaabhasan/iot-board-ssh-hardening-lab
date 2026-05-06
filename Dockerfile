FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY public/ /var/www/html/
COPY src/ /var/www/src/
COPY config/ /var/www/config/
COPY database/ /var/www/database/

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

