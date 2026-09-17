FROM php:8.2-apache

# Installer les dépendances système pour GD + Composer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zlib1g-dev \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql mysqli gd zip

# Activer mod_rewrite
RUN a2enmod rewrite

# Utiliser /app comme DocumentRoot
RUN sed -ri -e 's!/var/www/html!/app!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/app!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ==========================================
# INSTALLER COMPOSER + DÉPENDANCES
# ==========================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier composer.json et composer.lock
COPY composer.json composer.lock ./

# Installer les dépendances SANS scripts pour éviter les erreurs
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ==========================================
# CRÉER LES DOSSIERS
# ==========================================
RUN mkdir -p /app/logs \
             /app/uploads/photos \
             /app/uploads/photos_host \
             /app/uploads/fonds \
             /app/uploads/modeles_invitation

# Permissions initiales
RUN chown -R www-data:www-data /app/uploads /app/logs && \
    chmod -R 755 /app/uploads /app/logs

# ==========================================
# COPIER LE CODE
# ==========================================
COPY . /app/

# Permissions finales
RUN chown -R www-data:www-data /app

# ==========================================
# SCRIPT D'ENTRÉE (permissions automatiques)
# ==========================================
RUN printf '#!/bin/bash\n\
set -e\n\
\n\
echo "🔧 Correction des permissions du volume..."\n\
mkdir -p /app/uploads/photos /app/uploads/photos_host /app/uploads/fonds /app/uploads/modeles_invitation /app/logs\n\
chown -R www-data:www-data /app/uploads /app/logs\n\
chmod -R 755 /app/uploads /app/logs\n\
\n\
echo "✅ Permissions OK"\n\
echo "🚀 Démarrage Apache..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]