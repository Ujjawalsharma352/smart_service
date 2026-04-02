FROM php:8.1-apache

# Only required extensions
RUN docker-php-ext-install mysqli

# Copy files
COPY . /var/www/html/

# Enable rewrite
RUN a2enmod rewrite