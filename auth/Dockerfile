FROM php:8.2-apache

# Project copy
COPY . /var/www/html/

# MySQL extension enable
RUN docker-php-ext-install mysqli

# Apache port
EXPOSE 80