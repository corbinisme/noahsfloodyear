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

# Ensure the directories exist inside the image
RUN mkdir -p /var/www/html/Content \
    && mkdir -p /var/www/html/sites/default/files

# Declare the two mount points as volumes
VOLUME ["/var/www/html/Content", "/var/www/html/sites/default/files"]

# Optional: set working directory to Drupal root
WORKDIR /var/www/html

# Minimal entrypoint (no settings.php manipulation)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]