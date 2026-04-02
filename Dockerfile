FROM php:8.2-apache

# Fix Apache MPM issue
RUN a2dismod mpm_event && a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Install MySQL extension
RUN docker-php-ext-install mysqli

# Copy project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html