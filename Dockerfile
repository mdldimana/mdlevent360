FROM php:8.2-apache

# Installer les dépendances système pour GD
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql mysqli gd zip

# Activer mod_rewrite
RUN a2enmod rewrite

# Utiliser /app comme DocumentRoot (comme Nixpacks)
RUN sed -ri -e 's!/var/www/html!/app!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/app!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Créer les dossiers dans /app
RUN mkdir -p /app/logs \
             /app/uploads/fonds \
             /app/uploads/modeles_invitation \
             /app/uploads/photos \
             /app/uploads/photos_host

RUN chmod -R 777 /app/logs /app/uploads

# Copier ton code dans /app
COPY . /app/

# Permissions
RUN chown -R www-data:www-data /app

EXPOSE 80