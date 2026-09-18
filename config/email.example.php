<?php
// Configuration SMTP - EXEMPLE (à copier vers config/email.php)
// ⚠️ Ne jamais commiter le vrai fichier config/email.php

if (!defined('SMTP_HOST'))       define('SMTP_HOST', 'smtp.example.com');
if (!defined('SMTP_PORT'))       define('SMTP_PORT', 587);
if (!defined('SMTP_USERNAME'))   define('SMTP_USERNAME', 'your-email@example.com');
if (!defined('SMTP_PASSWORD'))   define('SMTP_PASSWORD', 'your-password');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'noreply@example.com');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME', 'MdlEvent');
if (!defined('SMTP_SECURE'))     define('SMTP_SECURE', 'tls');