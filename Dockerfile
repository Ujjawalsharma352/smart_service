FROM php:8.2-apache

# Apache rewrite enable
RUN a2enmod rewrite

# MySQL extension
RUN docker-php-ext-install mysqli

# Project copy
COPY . /var/www/html/

# Permission fix
RUN chown -R www-data:www-data /var/www/html