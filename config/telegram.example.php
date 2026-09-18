<?php
// Configuration Telegram - EXEMPLE (à copier vers config/telegram.php)
// ⚠️ Ne jamais commiter le vrai fichier config/telegram.php

if (!defined('TELEGRAM_BOT_TOKEN')) {
    define('TELEGRAM_BOT_TOKEN', 'VOTRE_TOKEN_ICI');
}

if (!defined('TELEGRAM_API_URL')) {
    define('TELEGRAM_API_URL', 'https://api.telegram.org/bot');
}