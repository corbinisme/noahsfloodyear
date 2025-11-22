FROM php:8.3-apache

# Install Drupal-required PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libzip-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
       gd \
       intl \
       opcache \
       pdo_mysql \
       zip \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# Set working directory to Drupal root and install PHP dependencies
WORKDIR /var/www/html

# Copy Composer definition files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy the rest of the application code into the container
COPY . /var/www/html

# Ensure the directories exist inside the image
RUN mkdir -p /var/www/html/Content \
    && mkdir -p /var/www/html/sites/default/files

# Declare the two mount points as volumes
VOLUME ["/var/www/html/Content", "/var/www/html/sites/default/files"]

# Minimal entrypoint (no settings.php manipulation)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]