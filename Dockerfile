# Dockerfile for PHP + Apache (Render.com)
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy source code to Apache document root
COPY . /var/www/html/

# Set proper permissions (optional, adjust as needed)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY . /var/www/html/
# Expose port 80
EXPOSE 80

# (Optional) Install additional PHP extensions if needed
# RUN docker-php-ext-install mysqli pdo pdo_mysql

# (Optional) Set environment variables
# ENV ENV_VAR_NAME=value

# Start Apache in the foreground
CMD ["apache2-foreground"]
