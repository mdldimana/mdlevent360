<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/emails/config.php
require_once __DIR__ . '/../../../includes/auth.php';

// ========== FIX INFINITYFREE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

// Vérifier les permissions
requirePermission('notifications.email');

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$pdo    = getDbConnection();

// ========== VARIABLES ==========
$message     = '';
$messageType = '';
$testResult  = '';

$smtp_host     = '';
$smtp_port     = '';
$smtp_username = '';
$smtp_password = '';
$smtp_secure   = 'tls';
$from_email    = '';
$from_name     = '';

// ========== CHARGEMENT DE LA CONFIGURATION ACTUELLE ==========

$configFile = __DIR__ . '/../../../config/email.php';

if (file_exists($configFile)) {
    try {
        // ⭐ On inclut le fichier dans un scope isolé via une closure
        $configLue = (function() use ($configFile) {
            include $configFile;
            return [
                'host'   => defined('SMTP_HOST')       ? (string)SMTP_HOST       : '',
                'port'   => defined('SMTP_PORT')       ? (string)SMTP_PORT       : '',
                'user'   => defined('SMTP_USERNAME')   ? (string)SMTP_USERNAME   : '',
                'pass'   => defined('SMTP_PASSWORD')   ? (string)SMTP_PASSWORD   : '',
                'secure' => defined('SMTP_SECURE')     ? (string)SMTP_SECURE     : 'tls',
                'from'   => defined('SMTP_FROM_EMAIL') ? (string)SMTP_FROM_EMAIL : '',
                'name'   => defined('SMTP_FROM_NAME')  ? (string)SMTP_FROM_NAME  : APP_NAME,
            ];
        })();

        $smtp_host     = $configLue['host'];
        $smtp_port     = $configLue['port'];
        $smtp_username = $configLue['user'];
        $smtp_password = $configLue['pass'];
        $smtp_secure   = $configLue['secure'];
        $from_email    = $configLue['from'];
        $from_name     = $configLue['name'];
    } catch (Throwable $e) {
        error_log('Erreur chargement config email : ' . $e->getMessage());
    }
}

// ⭐ On retient si un mot de passe existe déjà (avant tout POST)
$motDePasseDejaConfigure = !empty($smtp_password);

// ========== TRAITEMENT DU FORMULAIRE ==========

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['test_connection'])) {
    $smtp_host     = trim($_POST['smtp_host'] ?? '');
    $smtp_port     = trim($_POST['smtp_port'] ?? '');
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $smtp_secure   = $_POST['smtp_secure'] ?? 'tls';
    $from_email    = trim($_POST['from_email'] ?? '');
    $from_name     = trim($_POST['from_name'] ?? APP_NAME);

    // ⭐ Si le mot de passe est vide, on conserve l'ancien (celui du fichier de config)
    $motDePasseModifie = !empty($smtp_password);
    if (!$motDePasseModifie) {
        $ancienMotDePasse = '';
        if (file_exists($configFile)) {
            try {
                $ancienMotDePasse = (function() use ($configFile) {
                    include $configFile;
                    return defined('SMTP_PASSWORD') ? (string)SMTP_PASSWORD : '';
                })();
            } catch (Throwable $e) {
                error_log('Erreur relecture ancien mot de passe : ' . $e->getMessage());
            }
        }
        $smtp_password = $ancienMotDePasse;

        if (empty($smtp_password)) {
            $message = '❌ Aucun mot de passe enregistré. Veuillez en saisir un.';
            $messageType = 'danger';
        }
    }

    // Validation
    $errors = [];

    if (empty($message)) {
        if (empty($smtp_host))     $errors[] = 'Le serveur SMTP est requis.';
        if (empty($smtp_port))     $errors[] = 'Le port SMTP est requis.';
        if (!is_numeric($smtp_port) || (int)$smtp_port < 1 || (int)$smtp_port > 65535) {
            $errors[] = 'Le port SMTP doit être un nombre entre 1 et 65535.';
        }
        if (empty($smtp_username)) $errors[] = "Le nom d'utilisateur SMTP est requis.";
        if (empty($smtp_password)) $errors[] = 'Le mot de passe SMTP est requis.';
        if (empty($from_email))    $errors[] = "L'email expéditeur est requis.";
        if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email expéditeur n'est pas valide.";
        }
        if (!in_array($smtp_secure, ['tls', 'ssl', ''], true)) {
            $errors[] = 'Type de sécurité invalide.';
        }
    }

    if (empty($message) && empty($errors)) {
        // ⭐ Créer le dossier config/ s'il n'existe pas
        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            if (!@mkdir($configDir, 0755, true) && !is_dir($configDir)) {
                $message = "❌ Impossible de créer le dossier config/.";
                $messageType = 'danger';
            }
        }

        if (empty($message)) {
            // ⭐ Utiliser var_export() pour échapper correctement les valeurs
            $configContent  = "<?php\n";
            $configContent .= "// Configuration SMTP pour l'envoi d'emails\n";
            $configContent .= "// Fichier généré automatiquement le " . date('d/m/Y à H:i') . "\n";
            $configContent .= "// ⚠️ Ne pas modifier manuellement - Utilisez l'interface d'administration\n\n";
            $configContent .= "if (!defined('SMTP_HOST'))       define('SMTP_HOST', "       . var_export($smtp_host, true)     . ");\n";
            $configContent .= "if (!defined('SMTP_PORT'))       define('SMTP_PORT', "       . (int)$smtp_port                  . ");\n";
            $configContent .= "if (!defined('SMTP_USERNAME'))   define('SMTP_USERNAME', "   . var_export($smtp_username, true) . ");\n";
            $configContent .= "if (!defined('SMTP_PASSWORD'))   define('SMTP_PASSWORD', "   . var_export($smtp_password, true) . ");\n";
            $configContent .= "if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', " . var_export($from_email, true)    . ");\n";
            $configContent .= "if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME', "  . var_export($from_name, true)     . ");\n";
            $configContent .= "if (!defined('SMTP_SECURE'))     define('SMTP_SECURE', "     . var_export($smtp_secure, true)   . ");\n";

            if (@file_put_contents($configFile, $configContent) !== false) {
                $message = '✅ Configuration enregistrée avec succès !';
                if (!$motDePasseModifie) {
                    $message .= ' (mot de passe inchangé)';
                }
                $messageType = 'success';

                if (function_exists('logAction')) {
                    $action = $motDePasseModifie ? 'UPDATE_EMAIL_CONFIG' : 'UPDATE_EMAIL_CONFIG_KEEP_PASS';
                    logAction(
                        $userId,
                        $action,
                        'notifications',
                        "Mise à jour de la configuration email par " . ($user['username'] ?? 'inconnu')
                    );
                }

                // Après enregistrement, on ne réaffiche jamais le mot de passe
                $smtp_password = '';
                $motDePasseDejaConfigure = true;

            } else {
                $message = "❌ Erreur lors de l'enregistrement du fichier. Vérifiez les permissions du dossier config/.";
                $messageType = 'danger';
            }
        }
    } elseif (!empty($errors)) {
        $message = implode('<br>', array_map('htmlspecialchars', $errors));
        $messageType = 'danger';
    }
}

// ========== TEST DE CONNEXION ==========

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    // Recharger les valeurs POST pour le test
    $smtp_host     = trim($_POST['smtp_host'] ?? '');
    $smtp_port     = trim($_POST['smtp_port'] ?? '');
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $smtp_secure   = $_POST['smtp_secure'] ?? 'tls';
    $from_email    = trim($_POST['from_email'] ?? '');
    $from_name     = trim($_POST['from_name'] ?? APP_NAME);

    // ⭐ Si mot de passe vide, utiliser celui de la config existante
    if (empty($smtp_password) && file_exists($configFile)) {
        try {
            $smtp_password = (function() use ($configFile) {
                include $configFile;
                return defined('SMTP_PASSWORD') ? (string)SMTP_PASSWORD : '';
            })();
        } catch (Throwable $e) {
            error_log('Test SMTP - relecture password : ' . $e->getMessage());
        }
    }

    if (empty($smtp_password)) {
        $testResult = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> ⚠️ Aucun mot de passe disponible. Saisissez-en un ou enregistrez d\'abord la configuration.</div>';
    } else {
        // Charger PHPMailer
        $autoloadPaths = [
            __DIR__ . '/../../../vendor/autoload.php',
            __DIR__ . '/../../../../vendor/autoload.php',
            $projectRoot . '/vendor/autoload.php',
        ];

        $phpmailerLoaded = false;
        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $phpmailerLoaded = true;
                break;
            }
        }

        if ($phpmailerLoaded && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_username;
                $mail->Password   = $smtp_password;
                $mail->SMTPSecure = $smtp_secure;
                $mail->Port       = (int)$smtp_port;
                $mail->SMTPDebug  = 0;
                $mail->Timeout    = 10;

                $mail->smtpConnect();

                $testResult = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> ✅ Connexion SMTP réussie !</div>';

                $mail->smtpClose();
                unset($mail);

            } catch (\PHPMailer\PHPMailer\Exception $e) {
                error_log('Test SMTP échoué : ' . $e->getMessage());
                $testResult = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ❌ Erreur de connexion : '
                            . htmlspecialchars($mail->ErrorInfo ?? $e->getMessage())
                            . '</div>';
            } catch (Throwable $e) {
                error_log('Test SMTP échoué (générique) : ' . $e->getMessage());
                $testResult = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ❌ Erreur inattendue : '
                            . htmlspecialchars($e->getMessage())
                            . '</div>';
            }
        } else {
            $testResult = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> ⚠️ PHPMailer n\'est pas installé. Exécutez <code>composer require phpmailer/phpmailer</code></div>';
        }
    }
}

// ========== FOURNISSEURS SMTP PRÉDÉFINIS ==========

$providers = [
    'gmail' => [
        'host'        => 'smtp.gmail.com',
        'port'        => 587,
        'secure'      => 'tls',
        'description' => 'Gmail (mot de passe application)',
    ],
    'sendinblue' => [
        'host'        => 'smtp-relay.sendinblue.com',
        'port'        => 587,
        'secure'      => 'tls',
        'description' => 'Sendinblue (300/jour gratuit)',
    ],
    'mailgun' => [
        'host'        => 'smtp.mailgun.org',
        'port'        => 587,
        'secure'      => 'tls',
        'description' => 'Mailgun (5000/mois gratuit)',
    ],
    'outlook' => [
        'host'        => 'smtp-mail.outlook.com',
        'port'        => 587,
        'secure'      => 'tls',
        'description' => 'Outlook / Microsoft 365',
    ],
    'custom' => [
        'host'        => '',
        'port'        => 587,
        'secure'      => 'tls',
        'description' => 'Personnalisé',
    ],
];

$userInitiales = strtoupper(
    substr($user['prenom'] ?? 'U', 0, 1) .
    substr($user['nom'] ?? 'N', 0, 1)
);
$roles_user = $user['roles'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuration Email - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; overflow-x: hidden; }
    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: #f8f5f2;
        color: #1a1a1a;
        -webkit-font-smoothing: antialiased;
    }

    /* ========== LAYOUT ========== */
    .app-wrapper { display: flex; min-height: 100vh; width: 100%; }
    .sidebar-wrapper { flex-shrink: 0; width: 260px; min-height: 100vh; position: sticky; top: 0; height: 100vh; overflow-y: auto; z-index: 100; }
    .main-content { flex: 1; min-height: 100vh; overflow-y: auto; padding: 0; min-width: 0; }
    .main-content::-webkit-scrollbar { width: 6px; }
    .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
    .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c17c60, #d4a574); border-radius: 10px; }

    /* ========== TOP BAR ========== */
    .top-bar {
        background: rgba(255, 255, 255, 0.95);
        padding: 15px 30px;
        border-bottom: 1px solid rgba(193, 124, 96, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 50;
        flex-wrap: wrap;
        gap: 10px;
    }
    .top-bar .page-title h4 { font-weight: 700; color: #1a1a1a; margin: 0; font-size: 20px; }
    .top-bar .page-title h4 i { color: #c17c60; margin-right: 10px; }
    .top-bar .page-title small { color: #9a8a7f; font-size: 12px; display: block; margin-top: 2px; }
    .top-bar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .top-bar .user-info .user-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 16px;
        box-shadow: 0 5px 15px rgba(193, 124, 96, 0.3);
        flex-shrink: 0;
    }
    .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 13px; }
    .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 11px; }
    .top-bar .user-info .role-badge {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 700; white-space: nowrap;
    }

    /* ========== SIDEBAR TOGGLE ========== */
    .sidebar-toggle-btn {
        display: none;
        position: fixed;
        top: 12px;
        left: 12px;
        z-index: 200;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        box-shadow: 0 5px 20px rgba(193, 124, 96, 0.35);
        font-size: 20px;
        cursor: pointer;
        color: white;
        transition: all 0.3s ease;
    }
    .sidebar-toggle-btn:hover { transform: scale(1.05); box-shadow: 0 8px 30px rgba(193, 124, 96, 0.45); }
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 150;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active { display: block; opacity: 1; }

    /* ========== CONTENT ========== */
    .content-section { padding: 25px 30px; }

    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        max-width: 820px;
        margin: 0 auto;
    }
    .card-custom .card-title {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 24px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        font-size: 17px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-custom .card-title i { color: #c17c60; }

    /* ========== FORM ========== */
    .form-label {
        font-weight: 700;
        color: #6a5a4a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: block;
    }
    .form-label i { color: #c17c60; margin-right: 4px; }
    .form-control, .form-select {
        border-radius: 10px;
        padding: 10px 14px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
    }
    .form-control:focus, .form-select:focus {
        border-color: #c17c60;
        box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        outline: none;
        background: white;
    }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text i { color: #c17c60; }
    .form-text code {
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }

    /* ========== BOUTONS ========== */
    .btn-save {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        font-weight: 700;
        padding: 11px 26px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }
    .btn-test {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: white;
        border: none;
        font-weight: 600;
        padding: 11px 22px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .btn-test:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.35);
        color: white;
    }
    .btn-cancel {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-weight: 600;
        padding: 11px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-cancel:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
    }

    /* ========== ALERTES ========== */
    .alert-custom {
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .alert-custom i { font-size: 16px; flex-shrink: 0; margin-top: 2px; }

    /* ========== PROVIDER CARDS ========== */
    .provider-card {
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        background: rgba(255, 255, 255, 0.9);
    }
    .provider-card:hover {
        border-color: #c17c60;
        background: rgba(253, 248, 245, 0.9);
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(193, 124, 96, 0.1);
    }
    .provider-card.active {
        border-color: #c17c60;
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.1), rgba(212, 165, 116, 0.1));
        box-shadow: 0 4px 16px rgba(193, 124, 96, 0.15);
    }
    .provider-card .provider-name {
        font-weight: 700;
        color: #1a1a1a;
        font-size: 13px;
    }
    .provider-card .provider-name i { color: #c17c60; margin-right: 4px; }
    .provider-card .provider-desc {
        font-size: 10px;
        color: #9a8a7f;
        margin-top: 4px;
        line-height: 1.3;
    }

    /* ========== INFO LIST ========== */
    .info-list {
        background: rgba(252, 250, 248, 0.6);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 12px;
        padding: 16px 20px;
        margin-top: 20px;
    }
    .info-list h6 {
        font-weight: 700;
        font-size: 13px;
        color: #6a5a4a;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .info-list h6 i { color: #c17c60; }
    .info-list ul { margin: 0; padding-left: 20px; }
    .info-list li { font-size: 12px; color: #9a8a7f; margin-bottom: 6px; line-height: 1.5; }
    .info-list li:last-child { margin-bottom: 0; }
    .info-list a { color: #c17c60; text-decoration: none; }
    .info-list a:hover { text-decoration: underline; }
    .info-list code {
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }
    .info-list strong { color: #c17c60; }

    /* ========== BADGE CONFIGURÉ ========== */
    .badge-configure {
        background: rgba(16, 185, 129, 0.15);
        color: #065f46;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 6px;
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0;
    }

    /* ========== ANIMATIONS ========== */
    .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    /* ========== FOOTER ========== */
    .app-footer {
        text-align: center;
        padding: 30px 0 20px;
        color: #b8a99c;
        font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #c17c60; }

    @media (prefers-reduced-motion: reduce) {
        .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 992px) {
        .sidebar-toggle-btn { display: flex !important; align-items: center; justify-content: center; }
        .app-wrapper { display: block; width: 100%; }
        .main-content, body.sidebar-open .main-content {
            width: 100% !important; min-width: 0 !important; margin-left: 0 !important;
            transform: none !important; filter: none !important; opacity: 1 !important;
        }
        .sidebar-wrapper {
            position: fixed !important; top: 0 !important; left: 0 !important;
            width: min(280px, 85vw) !important; height: 100dvh !important;
            margin: 0 !important; transform: translate3d(-105%, 0, 0);
            transition: transform 0.28s ease !important; z-index: 2000 !important;
            overflow-y: auto; overflow-x: hidden; border-radius: 0 18px 18px 0;
        }
        .sidebar-wrapper.open { transform: translate3d(0, 0, 0) !important; }
        .sidebar-overlay {
            position: fixed !important; inset: 0 !important;
            display: block !important; visibility: hidden; opacity: 0;
            background: rgba(0, 0, 0, 0.5) !important;
            pointer-events: none;
            transition: opacity 0.28s ease, visibility 0.28s ease;
            z-index: 1900 !important;
        }
        .sidebar-overlay.active { visibility: visible; opacity: 1; pointer-events: auto; }
        .top-bar { padding: 12px 15px 12px 70px; flex-direction: row; flex-wrap: wrap; }
        body.sidebar-open { overflow-x: hidden !important; overflow-y: auto !important; }
        .content-section { padding: 15px; }
        .top-bar .page-title h4 { font-size: 1rem; }
        .top-bar .user-info .user-name { display: none; }
        .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
        .card-custom { padding: 20px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-save, .btn-test, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
        .provider-card { padding: 10px 12px; }
        .provider-card .provider-name { font-size: 12px; }
        .provider-card .provider-desc { font-size: 9px; }
    }
</style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">

    <div class="sidebar-wrapper" id="sidebarWrapper">
        <?php include_once __DIR__ . '/../../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-gear-fill"></i> Configuration Email</h4>
                <small><i class="bi bi-sliders2"></i> Paramètres SMTP pour l'envoi d'emails</small>
            </div>
            <div class="user-info">
                <span class="role-badge">
                    <i class="bi bi-shield-check"></i>
                    <?php echo is_array($roles_user) ? implode(', ', $roles_user) : 'Aucun rôle'; ?>
                </span>
                <div>
                    <div class="user-name">
                        <?php echo htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')); ?>
                        <small>@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small>
                    </div>
                </div>
                <div class="user-avatar">
                    <?php echo $userInitiales ?: 'U'; ?>
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="card-custom fade-in">

                <h5 class="card-title">
                    <i class="bi bi-envelope-gear-fill"></i> Configuration SMTP
                </h5>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-custom">
                        <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($testResult): ?>
                    <div class="alert-custom <?php echo str_contains($testResult, 'success') ? 'alert-success' : (str_contains($testResult, 'warning') ? 'alert-warning' : 'alert-danger'); ?>">
                        <?php
                        $testResult = preg_replace('#<div class="alert[^"]*">(.*?)</div>#s', '$1', $testResult);
                        echo $testResult;
                        ?>
                    </div>
                <?php endif; ?>

                <!-- FOURNISSEURS -->
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-cloud-fill"></i> Fournisseur SMTP</label>
                    <div class="row g-2" id="providerList">
                        <?php foreach ($providers as $key => $provider): ?>
                            <div class="col-6 col-md-4">
                                <div class="provider-card <?php echo ($smtp_host === $provider['host'] && $key !== 'custom') ? 'active' : ''; ?>"
                                     data-provider="<?php echo $key; ?>"
                                     data-host="<?php echo htmlspecialchars($provider['host']); ?>"
                                     data-port="<?php echo (int)$provider['port']; ?>"
                                     data-secure="<?php echo htmlspecialchars($provider['secure']); ?>">
                                    <div class="provider-name">
                                        <?php
                                        $icons = [
                                            'gmail'      => 'bi-google',
                                            'sendinblue' => 'bi-send-fill',
                                            'mailgun'    => 'bi-envelope-paper-fill',
                                            'outlook'    => 'bi-microsoft',
                                            'custom'     => 'bi-pencil-square',
                                        ];
                                        ?>
                                        <i class="bi <?php echo $icons[$key] ?? 'bi-cloud-fill'; ?>"></i>
                                        <?php echo ucfirst($key); ?>
                                    </div>
                                    <div class="provider-desc"><?php echo htmlspecialchars($provider['description']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><i class="bi bi-server"></i> Serveur SMTP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="smtp_host" id="smtp_host"
                                   value="<?php echo htmlspecialchars($smtp_host); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-hash"></i> Port <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="smtp_port" id="smtp_port"
                                   value="<?php echo $smtp_port !== '' ? (int)$smtp_port : 587; ?>" min="1" max="65535" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-fill"></i> Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="smtp_username" id="smtp_username"
                                   value="<?php echo htmlspecialchars($smtp_username); ?>" autocomplete="off" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-lock-fill"></i> Mot de passe
                                <?php if ($motDePasseDejaConfigure): ?>
                                    <span class="badge-configure">
                                        <i class="bi bi-check-circle-fill"></i> CONFIGURÉ
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password"
                                   class="form-control"
                                   name="smtp_password"
                                   id="smtp_password"
                                   value=""
                                   placeholder="<?php echo $motDePasseDejaConfigure ? '••••••••••• Laisser vide pour conserver' : 'Saisissez le mot de passe'; ?>"
                                   autocomplete="new-password"
                                   <?php echo $motDePasseDejaConfigure ? '' : 'required'; ?>>
                            <?php if ($motDePasseDejaConfigure): ?>
                                <div class="form-text">
                                    <i class="bi bi-shield-check" style="color:#10b981"></i>
                                    Un mot de passe est déjà enregistré. Laissez vide pour le conserver.
                                </div>
                            <?php else: ?>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i>
                                    Pour Gmail, utilisez un <strong>mot de passe d'application</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-shield-lock-fill"></i> Sécurité</label>
                            <select class="form-select" name="smtp_secure" id="smtp_secure">
                                <option value="tls" <?php echo $smtp_secure === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $smtp_secure === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value=""    <?php echo $smtp_secure === ''    ? 'selected' : ''; ?>>Aucun</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-send-fill"></i> Email expéditeur <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="from_email" id="from_email"
                                   value="<?php echo htmlspecialchars($from_email); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-badge-fill"></i> Nom expéditeur</label>
                        <input type="text" class="form-control" name="from_name" id="from_name"
                               value="<?php echo htmlspecialchars($from_name !== '' ? $from_name : APP_NAME); ?>">
                        <div class="form-text">Nom affiché dans les emails envoyés.</div>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                        <button type="submit" name="test_connection" value="1" class="btn-test">
                            <i class="bi bi-plug-fill"></i> Tester la connexion
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>

                <!-- INFORMATIONS -->
                <div class="info-list">
                    <h6><i class="bi bi-info-circle-fill"></i> Informations</h6>
                    <ul>
                        <li>Le fichier de configuration est stocké dans <code>config/email.php</code></li>
                        <li>Pour Gmail : créez un <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">mot de passe d'application</a></li>
                        <li>Sendinblue offre <strong>300 emails gratuits</strong> par jour</li>
                        <li>Mailgun offre <strong>5000 emails</strong> gratuits par mois</li>
                    </ul>
                </div>

            </div>

            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ========== SIDEBAR MOBILE ==========
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebarWrapper = document.getElementById('sidebarWrapper');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebarWrapper.classList.add('open');
    sidebarOverlay.classList.add('active');
    document.body.classList.add('sidebar-open');
}
function closeSidebar() {
    sidebarWrapper.classList.remove('open');
    sidebarOverlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebarWrapper.classList.contains('open')) closeSidebar();
        else openSidebar();
    });
}
if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) closeSidebar();
});

document.querySelectorAll('.sidebar-wrapper .nav-link:not([data-bs-toggle="collapse"])').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) closeSidebar();
    });
});

window.addEventListener('resize', function () {
    if (window.innerWidth > 992) closeSidebar();
});

// ========== SÉLECTION DES FOURNISSEURS ==========
document.querySelectorAll('.provider-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.provider-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');

        if (this.dataset.provider !== 'custom') {
            document.getElementById('smtp_host').value = this.dataset.host;
            document.getElementById('smtp_port').value = this.dataset.port;
            document.getElementById('smtp_secure').value = this.dataset.secure;
        }

        // Focus sur le username pour Gmail
        if (this.dataset.provider === 'gmail') {
            document.getElementById('smtp_username').focus();
        }
    });
});
</script>
</body>
</html>