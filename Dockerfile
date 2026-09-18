FROM php:8.2-apache

# Installer les dependances systeme pour GD + Composer
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
# INSTALLER COMPOSER + DEPENDANCES
# ==========================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier composer.json et composer.lock
COPY composer.json composer.lock ./

# Installer les dependances SANS scripts pour eviter les erreurs
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ==========================================
# CREER LES DOSSIERS
# ==========================================
RUN mkdir -p /app/logs \
             /app/uploads/photos \
             /app/uploads/photos_host \
             /app/uploads/fonds \
             /app/uploads/modeles_invitation \
             /app/config

# Permissions initiales
RUN chown -R www-data:www-data /app/uploads /app/logs /app/config && \
    chmod -R 755 /app/uploads /app/logs /app/config

# ==========================================
# COPIER LE CODE
# ==========================================
COPY . /app/

# Permissions finales
RUN chown -R www-data:www-data /app

# ==========================================
# CREER config/whatsapp.php S'IL N'EXISTE PAS
# ==========================================
# Ce fichier est dans .gitignore, donc absent du repo.
# On le cree automatiquement pour eviter les fatal errors.
RUN if [ ! -f /app/config/whatsapp.php ]; then \
        printf '%s' '<?php
/**
 * Configuration WhatsApp
 * Fichier genere automatiquement - Ne pas modifier manuellement
 */

if (!defined("WHATSAPP_SERVICE")) {
    define("WHATSAPP_SERVICE", "ultramsg");
}

$whatsappTemplates = [
    "invitation" => [
        "name"     => "invitation_event",
        "subject"  => "Invitation a l evenement",
        "template" => "Bonjour {nom} {prenom},\n\nNous avons le plaisir de vous inviter a l evenement \"{evenement}\" qui aura lieu le {date} a {heure}.\n\nLieu : {lieu}\nNombre de personnes : {nb_personnes}\nCode d acces : {code_unique}\n\nVeuillez confirmer votre presence via le lien ci-dessous :\n{url_validation}\n\nNous avons hate de vous accueillir !",
    ],
    "confirmation" => [
        "name"     => "confirmation_event",
        "subject"  => "Invitation confirmee",
        "template" => "Bonjour {nom} {prenom},\n\nNous confirmons votre participation a l evenement \"{evenement}\" du {date}.\n\nLieu : {lieu}\nNombre de personnes : {nb_personnes}\n\nA tres bientot !",
    ],
    "rappel" => [
        "name"     => "rappel_event",
        "subject"  => "Rappel",
        "template" => "Bonjour {nom} {prenom},\n\nRappel : {evenement} demain le {date} a {heure}.\n\nLieu : {lieu}\n\nA demain !",
    ],
    "present" => [
        "name"     => "present_event",
        "subject"  => "Presence",
        "template" => "Bonjour {nom} {prenom},\n\nVotre presence a {evenement} est enregistree avec succes !",
    ],
    "annulation" => [
        "name"     => "annulation_event",
        "subject"  => "Annulation",
        "template" => "Bonjour {nom} {prenom},\n\nNous avons recu votre annulation pour {evenement}.\n\nCordialement.",
    ],
];
' > /app/config/whatsapp.php; \
        chown www-data:www-data /app/config/whatsapp.php; \
        chmod 644 /app/config/whatsapp.php; \
    fi

# ==========================================
# SCRIPT D'ENTREE (permissions automatiques)
# ==========================================
RUN printf '#!/bin/bash\n\
set -e\n\
\n\
echo "Correction des permissions du volume..."\n\
mkdir -p /app/uploads/photos /app/uploads/photos_host /app/uploads/fonds /app/uploads/modeles_invitation /app/logs\n\
chown -R www-data:www-data /app/uploads /app/logs\n\
chmod -R 755 /app/uploads /app/logs\n\
\n\
echo "Permissions OK"\n\
echo "Demarrage Apache..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]