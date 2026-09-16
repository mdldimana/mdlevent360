<?php
/**
 * Configuration de l'application et connexion à la base de données
 */

// ==========================================
// 1. CONFIGURATION DE LA BASE DE DONNÉES
// ==========================================
// Exemple de configuration - à copier vers database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mdlevent360');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');

// ==========================================
// 2. CONFIGURATION DE L'APPLICATION
// ==========================================
define('APP_NAME', 'MdlEvent');
define('APP_URL', 'http://localhost/gestion_invitations');
define('APP_ENV', 'development'); // development | production
define('APP_TIMEZONE', 'Africa/Kinshasa');
define('SESSION_TIMEOUT', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 15); // minutes

// ==========================================
// 3. CHEMIN DE BASE POUR LES URLs (AJOUTÉ)
// ==========================================
define('BASE_PATH', '/gestion_invitations'); // ← Sans slash final

// ==========================================
// 4. CONFIGURATION DES UPLOADS
// ==========================================
define('MAX_UPLOAD_SIZE', 5242880); // 5 Mo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'pdf', 'png', 'gif', 'webp']);

// ==========================================
// 5. CONFIGURATION WHATSAPP
// ==========================================
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_PHONE_ID', '');

// ==========================================
// 6. CONFIGURATION TELEGRAM
// ==========================================
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot');

// ==========================================
// 7. CONFIGURATION DES TABLES
// ==========================================
define('TABLE_MAX_CAPACITY', 20);
define('TABLE_MIN_CAPACITY', 2);

// ==========================================
// 8. CONFIGURATION QR CODE
// ==========================================
define('QR_CODE_SIZE', 300);

// ==========================================
// 9. CONFIGURATION DES LOGS
// ==========================================
define('LOG_FILE', __DIR__ . '/../logs/app.log');
define('SECURITY_LOG_FILE', __DIR__ . '/../logs/security.log');

// ==========================================
// 10. FUSEAU HORAIRE ET ERREURS
// ==========================================
date_default_timezone_set(APP_TIMEZONE);

// Activer l'affichage des erreurs en développement
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// ==========================================
// 11. FONCTION DE CONNEXION PDO
// ==========================================

/**
 * Connexion à la base de données avec PDO
 * 
 * @return PDO|null Retourne l'objet PDO ou null en cas d'erreur
 */
function getDbConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        // Journaliser l'erreur
        error_log('[' . date('Y-m-d H:i:s') . '] Database connection error: ' . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        
        // Afficher un message approprié
        if (APP_ENV === 'development') {
            die('<h1>Erreur de connexion à la base de données</h1>
                 <p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                 <p><strong>Fichier :</strong> ' . htmlspecialchars($e->getFile()) . '</p>
                 <p><strong>Ligne :</strong> ' . htmlspecialchars($e->getLine()) . '</p>
                 <p>Veuillez vérifier que :</p>
                 <ul>
                     <li>MySQL est démarré (XAMPP)</li>
                     <li>Les identifiants sont corrects</li>
                     <li>La base de données "' . DB_NAME . '" existe</li>
                 </ul>');
        } else {
            die('Une erreur est survenue. Veuillez réessayer plus tard.');
        }
        return null;
    }
}

// ==========================================
// 12. FONCTIONS UTILITAIRES SUPPLÉMENTAIRES
// ==========================================

/**
 * Test rapide de la connexion à la base de données
 * 
 * @return bool True si la connexion est réussie
 */
function testDbConnection() {
    try {
        $pdo = getDbConnection();
        if ($pdo) {
            $pdo->query('SELECT 1');
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Écrit un message dans le journal d'application
 * 
 * @param string $message Message à journaliser
 * @param string $level Niveau de log (INFO, WARNING, ERROR)
 */
function logMessage($message, $level = 'INFO') {
    $logEntry = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    error_log($logEntry, 3, LOG_FILE);
}

/**
 * Sécurise une donnée avant affichage
 * 
 * @param string $data Donnée à sécuriser
 * @return string Donnée sécurisée
 */
function secureOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}