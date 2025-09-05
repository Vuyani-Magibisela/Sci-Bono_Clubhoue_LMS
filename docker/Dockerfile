# Sci-Bono Clubhouse LMS - Production Dockerfile
# Phase 7: API Development & Testing - Production Deployment

FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    libicu-dev \
    mysql-client \
    nano \
    supervisor \
    cron \
    logrotate \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN a2enmod rewrite ssl headers
RUN a2enmod expires
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Configure PHP for production
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Create application directories
RUN mkdir -p /var/www/html/logs \
    /var/www/html/storage \
    /var/www/html/storage/cache \
    /var/www/html/storage/sessions \
    /var/www/html/storage/uploads \
    /var/www/html/tests/reports \
    /var/www/html/tests/coverage

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/tests/reports \
    && chmod -R 777 /var/www/html/tests/coverage

# Copy application code
COPY . /var/www/html/

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy cron jobs
COPY docker/cron/crontab /etc/cron.d/sci-bono-lms
RUN chmod 0644 /etc/cron.d/sci-bono-lms
RUN crontab /etc/cron.d/sci-bono-lms

# Copy entrypoint script
COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/app/Controllers/PerformanceDashboardController.php?action=health || exit 1

# Expose ports
EXPOSE 80 443

# Set environment variables
ENV APACHE_DOCUMENT_ROOT /var/www/html
ENV APACHE_LOG_DIR /var/log/apache2
ENV PHP_MEMORY_LIMIT 256M
ENV PHP_MAX_EXECUTION_TIME 300
ENV PHP_UPLOAD_MAX_FILESIZE 50M
ENV PHP_POST_MAX_SIZE 50M

# Use supervisor to manage multiple processes
CMD ["/usr/local/bin/entrypoint.sh"]