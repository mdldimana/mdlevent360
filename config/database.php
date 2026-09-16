<?php
/**
 * Configuration de l'application et connexion à la base de données
 * Compatible local (XAMPP) et production (Coolify/VPS)
 */

// ==========================================
// 1. DÉTECTION DE L'ENVIRONNEMENT
// ==========================================
$isProduction = getenv('DB_HOST') !== false;

// ==========================================
// 2. CONFIGURATION DE LA BASE DE DONNÉES
// ==========================================
// En production (Coolify), utilise les variables d'environnement
// En local (XAMPP), utilise les valeurs par défaut
if ($isProduction) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'default');
    define('DB_USER', getenv('DB_USER') ?: 'mysql');
    define('DB_PASS', getenv('DB_PASS') ?: '');
} else {
    // Valeurs locales (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'gestion_invitations');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// ==========================================
// 3. CONFIGURATION DE L'APPLICATION
// ==========================================
define('APP_NAME', getenv('APP_NAME') ?: 'MdlEvent');

// URL dynamique selon l'environnement
if ($isProduction) {
    // En production, Coolify fournit COOLIFY_URL
    $appUrl = getenv('COOLIFY_URL') ?: 'http://187.124.213.124:8000';
    define('APP_URL', $appUrl);
} else {
    define('APP_URL', 'http://localhost/gestion_invitations');
}

define('APP_ENV', $isProduction ? 'production' : 'development');
define('APP_TIMEZONE', 'Africa/Kinshasa');
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 15);

// ==========================================
// 4. CHEMIN DE BASE POUR LES URLs
// ==========================================
if ($isProduction) {
    // En production, BASE_PATH est vide (racine)
    define('BASE_PATH', '');
} else {
    // En local
    define('BASE_PATH', '/gestion_invitations');
}

// ==========================================
// 5. CONFIGURATION DES UPLOADS
// ==========================================
define('MAX_UPLOAD_SIZE', 5242880);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'pdf', 'png', 'gif', 'webp']);

// ==========================================
// 6. CONFIGURATION WHATSAPP
// ==========================================
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_PHONE_ID', getenv('WHATSAPP_PHONE_ID') ?: '');

// ==========================================
// 7. CONFIGURATION TELEGRAM
// ==========================================
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot');

// ==========================================
// 8. CONFIGURATION DES TABLES
// ==========================================
define('TABLE_MAX_CAPACITY', 20);
define('TABLE_MIN_CAPACITY', 2);

// ==========================================
// 9. CONFIGURATION QR CODE
// ==========================================
define('QR_CODE_SIZE', 300);

// ==========================================
// 10. CONFIGURATION DES LOGS
// ==========================================
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
define('LOG_FILE', $logDir . '/app.log');
define('SECURITY_LOG_FILE', $logDir . '/security.log');

// ==========================================
// 11. FUSEAU HORAIRE ET ERREURS
// ==========================================
date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Production : ne PAS afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}

// ==========================================
// 12. FONCTION DE CONNEXION PDO
// ==========================================

function getDbConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] Database connection error: ' . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        
        if (APP_ENV === 'development') {
            die('<h1>Erreur de connexion à la base de données</h1>
                 <p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                 <p><strong>Hôte :</strong> ' . htmlspecialchars(DB_HOST) . '</p>
                 <p><strong>Base :</strong> ' . htmlspecialchars(DB_NAME) . '</p>
                 <p><strong>Utilisateur :</strong> ' . htmlspecialchars(DB_USER) . '</p>');
        } else {
            die('Erreur de connexion à la base de données. Veuillez réessayer.');
        }
    }
}

// ==========================================
// 13. FONCTIONS UTILITAIRES
// ==========================================

function testDbConnection() {
    try {
        $pdo = getDbConnection();
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function logMessage($message, $level = 'INFO') {
    $logEntry = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    error_log($logEntry, 3, LOG_FILE);
}

function secureOutput($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}