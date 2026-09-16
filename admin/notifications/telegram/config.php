<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/telegram/config.php
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

requirePermission('notifications.telegram');

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$pdo    = getDbConnection();

// ============================================
// CHARGEMENT DE LA CONFIG TELEGRAM
// ============================================

$configFile = __DIR__ . '/../../../config/telegram.php';
$configDir  = dirname($configFile);

// Créer le dossier config/ s'il n'existe pas
if (!is_dir($configDir)) {
    if (!@mkdir($configDir, 0755, true) && !is_dir($configDir)) {
        error_log('Impossible de créer le dossier config/ : ' . $configDir);
    }
}

// Créer le fichier s'il n'existe pas
if (!is_file($configFile)) {
    $configContent  = "<?php\n\n";
    $configContent .= "if (!defined('TELEGRAM_BOT_TOKEN')) {\n";
    $configContent .= "    define('TELEGRAM_BOT_TOKEN', '');\n";
    $configContent .= "}\n\n";
    $configContent .= "if (!defined('TELEGRAM_API_URL')) {\n";
    $configContent .= "    define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);\n";
    $configContent .= "}\n";
    @file_put_contents($configFile, $configContent);
}

$botToken = '';
if (file_exists($configFile)) {
    try {
        $configLue = (function () use ($configFile) {
            include $configFile;
            return [
                'token' => defined('TELEGRAM_BOT_TOKEN') ? (string)TELEGRAM_BOT_TOKEN : '',
            ];
        })();
        $botToken = $configLue['token'];
    } catch (Throwable $e) {
        error_log('Erreur chargement config telegram : ' . $e->getMessage());
    }
}

// ============================================
// FONCTION DE TEST DU TOKEN (simplifiée)
// ============================================

function testTelegramToken(string $token): ?array {
    if (empty($token)) return null;

    $url = 'https://api.telegram.org/bot' . $token . '/getMe';

    // ---- cURL (prioritaire) ----
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT      => APP_NAME . '/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['ok'])) {
                return $data;
            }
        }
    }

    // ---- Fallback : file_get_contents ----
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout'       => 15,
                'ignore_errors' => true,
                'user_agent'    => APP_NAME . '/1.0',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['ok'])) {
                return $data;
            }
        }
    }

    return null;
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- SAUVEGARDER LE TOKEN ----
    if ($action === 'save_token') {
        $newToken = trim($_POST['bot_token'] ?? '');

        if (empty($newToken)) {
            $message = 'Veuillez entrer un token.';
            $messageType = 'danger';
        } else {
            $test = testTelegramToken($newToken);

            if (is_array($test) && !empty($test['ok'])) {
                // ⭐ Utiliser var_export() pour échapper correctement
                $configContent  = "<?php\n";
                $configContent .= "// Configuration Telegram\n";
                $configContent .= "// Fichier généré automatiquement le " . date('d/m/Y à H:i') . "\n";
                $configContent .= "// ⚠️ Ne pas modifier manuellement - Utilisez l'interface d'administration\n\n";
                $configContent .= "if (!defined('TELEGRAM_BOT_TOKEN')) {\n";
                $configContent .= "    define('TELEGRAM_BOT_TOKEN', " . var_export($newToken, true) . ");\n";
                $configContent .= "}\n\n";
                $configContent .= "if (!defined('TELEGRAM_API_URL')) {\n";
                $configContent .= "    define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);\n";
                $configContent .= "}\n";

                if (@file_put_contents($configFile, $configContent) !== false) {
                    $botUsername = htmlspecialchars($test['result']['username'] ?? '');
                    $message = "Token sauvegardé avec succès ! Bot actif : @" . $botUsername;
                    $messageType = 'success';
                    $botToken = $newToken;

                    if (function_exists('logAction')) {
                        logAction(
                            $userId,
                            'UPDATE_TELEGRAM_CONFIG',
                            'notifications',
                            "Mise à jour du token Telegram par " . ($user['username'] ?? 'inconnu')
                        );
                    }
                } else {
                    $message = "Erreur lors de la sauvegarde du fichier de configuration.";
                    $messageType = 'danger';
                }
            } else {
                $message = "Token invalide ou inaccessible. Vérifiez votre token et réessayez.";
                $messageType = 'danger';
            }
        }
    }

    // ---- TESTER LE TOKEN SANS SAUVEGARDER ----
    if ($action === 'test_token') {
        $testToken = trim($_POST['bot_token'] ?? '');

        if (empty($testToken)) {
            $message = 'Veuillez entrer un token à tester.';
            $messageType = 'warning';
        } else {
            $test = testTelegramToken($testToken);

            if (is_array($test) && !empty($test['ok'])) {
                $botUsername = htmlspecialchars($test['result']['username'] ?? '');
                $message = "Token valide ! Bot : @" . $botUsername;
                $messageType = 'success';
            } else {
                $message = "Token invalide ou inaccessible.";
                $messageType = 'danger';
            }
        }
    }
}

// ============================================
// STATUT DU BOT
// ============================================

$botInfo = null;
$botOk   = false;
if (!empty($botToken)) {
    $botInfo = testTelegramToken($botToken);
    $botOk   = is_array($botInfo) && !empty($botInfo['ok']);
}

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
<title>Configuration Telegram - <?php echo APP_NAME; ?></title>
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
    .top-bar .page-title h4 i { color: #0088cc; margin-right: 10px; }
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
        top: 12px; left: 12px;
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

    /* ========== CARDS ========== */
    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        margin-bottom: 20px;
    }
    .card-custom .card-header-custom {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-custom .card-header-custom i { color: #0088cc; }

    /* ========== BOUTONS ========== */
    .btn-telegram {
        background: linear-gradient(135deg, #0088cc, #005f8a);
        color: white;
        border: none;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(0, 136, 204, 0.25);
        cursor: pointer;
    }
    .btn-telegram:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 136, 204, 0.35);
        color: white;
    }

    .btn-test {
        background: linear-gradient(135deg, #10b981, #34d399);
        color: white;
        border: none;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
        cursor: pointer;
    }
    .btn-test:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
        color: white;
    }

    .btn-cancel {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-cancel:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
    }

    /* ========== FORM ========== */
    .form-label {
        font-weight: 700;
        font-size: 12px;
        color: #6a5a4a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: block;
    }
    .form-label i { color: #0088cc; margin-right: 4px; }
    .form-control {
        border-radius: 10px;
        padding: 10px 14px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #0088cc;
        box-shadow: 0 0 0 4px rgba(0, 136, 204, 0.08);
        outline: none;
        background: white;
    }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text i { color: #0088cc; }

    /* ========== BOT STATUS ========== */
    .bot-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
    }
    .bot-status.online {
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .bot-status.offline {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .bot-status a { color: inherit; font-weight: 700; }

    /* ========== INFO BOX ========== */
    .info-box {
        background: linear-gradient(135deg, rgba(0, 136, 204, 0.06), rgba(0, 95, 138, 0.06));
        border-left: 4px solid #0088cc;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .info-box h6 {
        font-weight: 700;
        font-size: 13px;
        color: #1a1a1a;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .info-box h6 i { color: #0088cc; }
    .info-box ol {
        font-size: 12px;
        color: #6a5a4a;
        padding-left: 20px;
        margin: 0;
        line-height: 1.7;
    }
    .info-box ol code {
        background: rgba(0, 136, 204, 0.12);
        color: #0088cc;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 700;
    }

    /* ========== STEPS ========== */
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #0088cc, #005f8a);
        color: white;
        border-radius: 50%;
        font-weight: 700;
        font-size: 15px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 136, 204, 0.25);
    }
    .step-block h6 {
        font-weight: 700;
        font-size: 13px;
        color: #1a1a1a;
        margin-bottom: 4px;
    }
    .step-block p {
        font-size: 12px;
        color: #9a8a7f;
        margin: 0;
        line-height: 1.5;
    }
    .step-block p code {
        background: rgba(193, 124, 96, 0.1);
        color: #c17c60;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }

    /* ========== ALERT ========== */
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

    /* ========== ANIMATIONS ========== */
    .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) {
        .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
    }

    /* ========== FOOTER ========== */
    .app-footer {
        text-align: center;
        padding: 30px 0 20px;
        color: #b8a99c;
        font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #c17c60; }

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
        .card-custom { padding: 18px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-telegram, .btn-test, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-2 { flex-direction: column; gap: 8px !important; }
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
                <h4><i class="bi bi-gear-fill"></i> Configuration Telegram</h4>
                <small><i class="bi bi-sliders2"></i> Paramètres du bot Telegram</small>
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

            <!-- BOUTON RETOUR -->
            <div class="mb-3">
                <a href="index.php" class="btn-cancel">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- MESSAGE -->
            <?php if ($message): ?>
                <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?> fade-in">
                    <i class="bi <?php
                        echo $messageType === 'success' ? 'bi-check-circle-fill' : (
                            $messageType === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill'
                        );
                    ?>"></i>
                    <div><?php echo $message; ?></div>
                </div>
            <?php endif; ?>

            <!-- STATUT DU BOT -->
            <div class="card-custom fade-in">
                <div class="card-header-custom">
                    <i class="bi bi-info-circle-fill"></i> Statut du bot
                </div>
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <?php if ($botOk): ?>
                            <span class="bot-status online">
                                <i class="bi bi-check-circle-fill"></i>
                                Bot actif : @<?php echo htmlspecialchars($botInfo['result']['username'] ?? 'inconnu'); ?>
                            </span>
                            <div class="mt-3" style="font-size:12px;color:#9a8a7f">
                                <div><i class="bi bi-hash" style="color:#0088cc"></i> ID : <?php echo htmlspecialchars((string)($botInfo['result']['id'] ?? '')); ?></div>
                                <div class="mt-1"><i class="bi bi-person-fill" style="color:#0088cc"></i> Nom : <?php echo htmlspecialchars($botInfo['result']['first_name'] ?? ''); ?></div>
                            </div>
                        <?php else: ?>
                            <span class="bot-status offline">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                Bot non accessible
                            </span>
                            <div class="mt-2" style="font-size:12px;color:#9a8a7f">
                                Vérifiez votre token ou la connexion à l'API Telegram.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <a href="index.php" class="btn-telegram">
                            <i class="bi bi-send-fill"></i> Voir les messages
                        </a>
                    </div>
                </div>
            </div>

            <!-- CONFIGURATION DU TOKEN -->
            <div class="card-custom fade-in">
                <div class="card-header-custom">
                    <i class="bi bi-key-fill"></i> Configurer le token du bot
                </div>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="bot_token" class="form-label">
                                    <i class="bi bi-telegram"></i> Token du bot
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="bot_token"
                                       name="bot_token"
                                       placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                                       value="<?php echo htmlspecialchars($botToken); ?>"
                                       autocomplete="off"
                                       required>
                                <div class="form-text">
                                    <i class="bi bi-info-circle-fill"></i>
                                    Obtenez votre token depuis <strong>@BotFather</strong> sur Telegram
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" name="action" value="save_token" class="btn-telegram">
                                    <i class="bi bi-save-fill"></i> Sauvegarder
                                </button>
                                <button type="submit" name="action" value="test_token" class="btn-test">
                                    <i class="bi bi-check-circle-fill"></i> Tester le token
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-4">
                        <div class="info-box">
                            <h6><i class="bi bi-question-circle-fill"></i> Comment obtenir un token ?</h6>
                            <ol>
                                <li>Ouvrez <strong>Telegram</strong></li>
                                <li>Recherchez <strong>@BotFather</strong></li>
                                <li>Envoyez <code>/newbot</code></li>
                                <li>Choisissez un nom</li>
                                <li>Choisissez un username</li>
                                <li>Copiez le token généré</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GUIDE D'UTILISATION -->
            <div class="card-custom fade-in">
                <div class="card-header-custom">
                    <i class="bi bi-book-fill"></i> Guide d'utilisation
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="d-flex gap-3 step-block">
                            <span class="step-number">1</span>
                            <div>
                                <h6>Configurer le bot</h6>
                                <p>Entrez le token de votre bot Telegram et sauvegardez.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 step-block">
                            <span class="step-number">2</span>
                            <div>
                                <h6>Envoyer des messages</h6>
                                <p>Les invitations avec un <code>telegram_chat_id</code> seront automatiquement envoyées.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 step-block">
                            <span class="step-number">3</span>
                            <div>
                                <h6>Suivre les envois</h6>
                                <p>Consultez les statistiques et l'historique des envois sur la page principale.</p>
                            </div>
                        </div>
                    </div>
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
</script>
</body>
</html>