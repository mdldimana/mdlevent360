FROM php:8.2-apache

# Installer les dépendances système pour GD
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer GD avec support JPEG
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli gd zip

# Activer mod_rewrite
RUN a2enmod rewrite

# Créer les dossiers nécessaires
RUN mkdir -p /var/www/html/logs \
             /var/www/html/uploads/fonds \
             /var/www/html/uploads/modeles_invitation \
             /var/www/html/uploads/photos \
             /var/www/html/uploads/photos_host

# Permissions
RUN chmod -R 777 /var/www/html/logs /var/www/html/uploads

# Copier ton code
COPY . /var/www/html/

# Permissions Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80