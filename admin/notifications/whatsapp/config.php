<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/whatsapp/config.php
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

requirePermission('notifications.whatsapp');

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$pdo    = getDbConnection();

// ============================================
// VARIABLES
// ============================================

$message     = '';
$messageType = '';

$whatsapp_api_url     = 'https://graph.facebook.com/v18.0/';
$whatsapp_phone_id    = '';
$whatsapp_token       = '';
$whatsapp_business_id = '';
$whatsapp_webhook     = '';
$whatsapp_service     = 'meta';
$configStatus         = 'active';

// ⭐ Pour éviter de ré-afficher le token après enregistrement
$tokenDejaConfigure = false;

// ============================================
// CHARGER LA CONFIGURATION ACTUELLE DEPUIS LA BASE
// ============================================

try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        $whatsapp_api_url     = $config['api_key']             ?? 'https://graph.facebook.com/v18.0/';
        $whatsapp_phone_id    = $config['phone_number_id']     ?? '';
        $whatsapp_token       = $config['access_token']        ?? '';
        $whatsapp_business_id = $config['business_account_id'] ?? '';
        $whatsapp_webhook     = $config['webhook_url']         ?? '';
        $whatsapp_service     = $config['service']             ?? 'meta';
        $configStatus         = $config['status']              ?? 'active';

        $tokenDejaConfigure = !empty($whatsapp_token);
    }
} catch (PDOException $e) {
    error_log('Erreur chargement config WhatsApp : ' . $e->getMessage());
}

// ============================================
// SERVICES DISPONIBLES
// ============================================

$services = [
    'meta'       => 'Meta WhatsApp Cloud API',
    'twilio'     => 'Twilio WhatsApp API',
    'ultramsg'   => 'UltraMsg API',
    'simulation' => 'Mode Simulation (test)',
];

// ============================================
// FONCTION DE TEST
// ============================================

function testWhatsAppConfig(string $phoneId, string $token): array {
    if (empty($phoneId) || empty($token)) {
        return ['success' => false, 'message' => 'ID téléphone et Token requis pour le test'];
    }

    $url = 'https://graph.facebook.com/v18.0/' . urlencode($phoneId);

    // ---- cURL ----
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            return ['success' => false, 'message' => 'Erreur cURL : ' . $curlError];
        }

        if ($httpCode === 200 && $response !== false) {
            return ['success' => true, 'message' => 'Connexion à l\'API WhatsApp réussie !'];
        }

        $result = json_decode((string)$response, true);
        $error  = $result['error']['message'] ?? 'Erreur inconnue (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'Erreur de connexion : ' . $error];
    }

    return ['success' => false, 'message' => 'cURL n\'est pas disponible sur ce serveur.'];
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    // ---------- SAUVEGARDER ----------
    if ($action === 'save') {
        $whatsapp_api_url     = trim($_POST['whatsapp_api_url']     ?? '');
        $whatsapp_phone_id    = trim($_POST['whatsapp_phone_id']    ?? '');
        $whatsapp_token       = trim($_POST['whatsapp_token']       ?? '');
        $whatsapp_business_id = trim($_POST['whatsapp_business_id'] ?? '');
        $whatsapp_webhook     = trim($_POST['whatsapp_webhook']     ?? '');
        $whatsapp_service     = $_POST['whatsapp_service']           ?? 'meta';
        $configStatus         = $_POST['config_status']             ?? 'active';

        // ⭐ Si le token est vide, on garde l'ancien
        $tokenModifie = !empty($whatsapp_token);
        if (!$tokenModifie && $tokenDejaConfigure) {
            $whatsapp_token = $config['access_token'] ?? '';
        }

        // Validation
        $errors = [];

        if (empty($whatsapp_api_url))     $errors[] = "L'URL de l'API WhatsApp est requise.";
        if (empty($whatsapp_phone_id))    $errors[] = "L'ID du téléphone est requis.";
        if (empty($whatsapp_token))       $errors[] = "Le token d'accès est requis.";
        if (!in_array($whatsapp_service, array_keys($services), true)) {
            $errors[] = 'Service invalide.';
        }
        if (!in_array($configStatus, ['active', 'inactive'], true)) {
            $errors[] = 'Statut invalide.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Désactiver les anciennes configurations
                $stmt = $pdo->prepare("UPDATE whatsapp_config SET status = 'inactive' WHERE status = 'active'");
                $stmt->execute();

                // Insérer la nouvelle configuration
                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_config 
                    (api_key, phone_number_id, business_account_id, access_token, webhook_url, service, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $stmt->execute([
                    $whatsapp_api_url,
                    $whatsapp_phone_id,
                    $whatsapp_business_id,
                    $whatsapp_token,
                    $whatsapp_webhook,
                    $whatsapp_service,
                    $configStatus,
                ]);

                $pdo->commit();

                $message = '✅ Configuration WhatsApp enregistrée avec succès !';
                if (!$tokenModifie && $tokenDejaConfigure) {
                    $message .= ' (token inchangé)';
                }
                $messageType = 'success';

                if (function_exists('logAction')) {
                    logAction(
                        $userId,
                        'UPDATE_WHATSAPP_CONFIG',
                        'notifications',
                        "Mise à jour de la configuration WhatsApp (service: $whatsapp_service)"
                    );
                }

                // Après enregistrement, on ne réaffiche jamais le token
                $whatsapp_token     = '';
                $tokenDejaConfigure = true;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Erreur sauvegarde config WhatsApp : ' . $e->getMessage());
                $message = '❌ Erreur lors de l\'enregistrement. Veuillez réessayer.';
                $messageType = 'danger';
            }
        } else {
            $message = implode('<br>', array_map('htmlspecialchars', $errors));
            $messageType = 'danger';
        }
    }

    // ---------- TESTER ----------
    elseif ($action === 'test') {
        $phoneId = trim($_POST['whatsapp_phone_id'] ?? '');
        $token   = trim($_POST['whatsapp_token']    ?? '');

        // Si le token est vide, on utilise celui de la config existante
        if (empty($token) && $tokenDejaConfigure) {
            $token = $config['access_token'] ?? '';
        }

        if (empty($token)) {
            $message = '❌ Aucun token disponible pour le test.';
            $messageType = 'danger';
        } else {
            $result = testWhatsAppConfig($phoneId, $token);

            if (!empty($result['success'])) {
                $message = '✅ ' . $result['message'];
                $messageType = 'success';
            } else {
                $message = '❌ ' . $result['message'];
                $messageType = 'danger';
            }
        }
    }
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
<title>Configuration WhatsApp - <?php echo APP_NAME; ?></title>
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
    .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #25D366, #128C7E); border-radius: 10px; }

    /* ========== TOP BAR ========== */
    .top-bar {
        background: rgba(255, 255, 255, 0.95);
        padding: 15px 30px;
        border-bottom: 1px solid rgba(37, 211, 102, 0.15);
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
    .top-bar .page-title h4 i { color: #25D366; margin-right: 10px; }
    .top-bar .page-title small { color: #9a8a7f; font-size: 12px; display: block; margin-top: 2px; }
    .top-bar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .top-bar .user-info .user-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #25D366, #128C7E);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 16px;
        box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        flex-shrink: 0;
    }
    .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 13px; }
    .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 11px; }
    .top-bar .user-info .role-badge {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 700; white-space: nowrap;
    }

    /* ========== SIDEBAR TOGGLE ========== */
    .sidebar-toggle-btn {
        display: none;
        position: fixed;
        top: 12px; left: 12px;
        z-index: 200;
        background: linear-gradient(135deg, #25D366, #128C7E);
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        box-shadow: 0 5px 20px rgba(37, 211, 102, 0.35);
        font-size: 20px;
        cursor: pointer;
        color: white;
        transition: all 0.3s ease;
    }
    .sidebar-toggle-btn:hover { transform: scale(1.05); box-shadow: 0 8px 30px rgba(37, 211, 102, 0.45); }
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
        padding: 26px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        max-width: 820px;
        margin: 0 auto;
    }
    .card-custom .card-title {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 22px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(37, 211, 102, 0.15);
        font-size: 17px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-custom .card-title i { color: #25D366; }

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
    .form-label i { color: #25D366; margin-right: 4px; }
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
        border-color: #25D366;
        box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.08);
        outline: none;
        background: white;
    }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text i { color: #25D366; }
    .form-text code {
        background: rgba(37, 211, 102, 0.1);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #128C7E;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }

    /* ========== BOUTONS ========== */
    .btn-save {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        font-weight: 700;
        padding: 11px 26px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.25);
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.35);
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
        color: #25D366;
        border-color: #25D366;
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

    /* ========== INFO BOX ========== */
    .info-box {
        background: linear-gradient(135deg, rgba(37, 211, 102, 0.05), rgba(18, 140, 126, 0.05));
        border-left: 4px solid #25D366;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .info-box strong {
        font-size: 13px;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .info-box strong i { color: #25D366; }
    .info-box ul {
        font-size: 12px;
        color: #6a5a4a;
        padding-left: 20px;
        margin: 0;
        line-height: 1.7;
    }
    .info-box a { color: #25D366; text-decoration: none; font-weight: 700; }
    .info-box a:hover { text-decoration: underline; }

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

    /* ========== STATUS BADGE ========== */
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .status-badge.active   { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .status-badge.inactive { background: rgba(239, 68, 68, 0.12); color: #991b1b; }

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
    .app-footer i.bi-heart-fill { color: #25D366; }

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
                <h4><i class="bi bi-whatsapp"></i> Configuration WhatsApp</h4>
                <small><i class="bi bi-sliders2"></i> Paramètres WhatsApp Business API</small>
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
                    <i class="bi bi-whatsapp"></i> Configuration WhatsApp Business API
                </h5>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-custom">
                        <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($configStatus === 'active'): ?>
                    <div class="alert-custom alert-success" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#065f46">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            Configuration <span class="status-badge active">Active</span>
                            <span style="font-size:12px;margin-left:8px;opacity:0.8">
                                Service : <?php echo htmlspecialchars($services[$whatsapp_service] ?? $whatsapp_service); ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert-custom alert-warning" style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);color:#92400e">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            Configuration <span class="status-badge inactive">Inactive</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong><i class="bi bi-info-circle-fill"></i> WhatsApp Business API</strong>
                    <ul>
                        <li>Créez un compte sur <a href="https://business.facebook.com/" target="_blank" rel="noopener">Meta Business</a></li>
                        <li>Obtenez un <strong>Numéro de téléphone business</strong> et un <strong>Token d'accès</strong></li>
                        <li>Les messages sont envoyés via l'API officielle Meta</li>
                    </ul>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="save">

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-server"></i> Service <span class="text-danger">*</span></label>
                        <select class="form-select" name="whatsapp_service" required>
                            <?php foreach ($services as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $whatsapp_service === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choisissez le service WhatsApp que vous utilisez</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-link-45deg"></i> API URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="whatsapp_api_url"
                               value="<?php echo htmlspecialchars($whatsapp_api_url ?: 'https://graph.facebook.com/v18.0/'); ?>"
                               placeholder="https://graph.facebook.com/v18.0/" required>
                        <div class="form-text">URL de l'API WhatsApp Business</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-phone-fill"></i> ID du téléphone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="whatsapp_phone_id"
                               value="<?php echo htmlspecialchars($whatsapp_phone_id); ?>"
                               placeholder="Ex: 123456789012345" required>
                        <div class="form-text">ID du numéro de téléphone WhatsApp Business</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-key-fill"></i> Token d'accès
                            <?php if ($tokenDejaConfigure): ?>
                                <span class="badge-configure">
                                    <i class="bi bi-check-circle-fill"></i> CONFIGURÉ
                                </span>
                            <?php else: ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <input type="password"
                               class="form-control"
                               name="whatsapp_token"
                               value=""
                               placeholder="<?php echo $tokenDejaConfigure ? '••••••••••• Laisser vide pour conserver' : 'EAAB...'; ?>"
                               autocomplete="new-password"
                               <?php echo $tokenDejaConfigure ? '' : 'required'; ?>>
                        <?php if ($tokenDejaConfigure): ?>
                            <div class="form-text">
                                <i class="bi bi-shield-check" style="color:#10b981"></i>
                                Un token est déjà enregistré. Laissez vide pour le conserver.
                            </div>
                        <?php else: ?>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i>
                                Token d'accès permanent de l'API WhatsApp Business
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-building"></i> Business ID</label>
                        <input type="text" class="form-control" name="whatsapp_business_id"
                               value="<?php echo htmlspecialchars($whatsapp_business_id); ?>"
                               placeholder="ID de l'entreprise Meta">
                        <div class="form-text">ID de votre entreprise Meta Business</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-link"></i> Webhook URL</label>
                        <input type="text" class="form-control" name="whatsapp_webhook"
                               value="<?php echo htmlspecialchars($whatsapp_webhook); ?>"
                               placeholder="https://votre-domaine.com/webhook/whatsapp">
                        <div class="form-text">URL de réception des messages (optionnel)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-toggle-on"></i> Statut</label>
                        <select class="form-select" name="config_status">
                            <option value="active"   <?php echo $configStatus === 'active'   ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactive" <?php echo $configStatus === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                        <div class="form-text">Activez ou désactivez la configuration</div>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>

                <!-- TEST CONNEXION -->
                <div class="mt-4 pt-3" style="border-top:2px dashed rgba(37,211,102,0.15)">
                    <h6 class="fw-bold mb-3" style="font-size:14px">
                        <i class="bi bi-bug-fill" style="color:#25D366"></i> Tester la connexion
                    </h6>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="test">
                        <input type="hidden" name="whatsapp_phone_id" value="<?php echo htmlspecialchars($whatsapp_phone_id); ?>">
                        <button type="submit" class="btn-test">
                            <i class="bi bi-plug-fill"></i> Tester la connexion
                        </button>
                    </form>
                </div>

                <!-- AIDE -->
                <div class="mt-4 pt-3" style="border-top:2px dashed rgba(37,211,102,0.15)">
                    <h6 class="fw-bold mb-2" style="font-size:14px">
                        <i class="bi bi-question-circle-fill" style="color:#25D366"></i> Comment obtenir vos identifiants
                    </h6>
                    <ol class="small" style="color:#9a8a7f;padding-left:20px;line-height:1.8">
                        <li>Créez un compte <a href="https://business.facebook.com/" target="_blank" rel="noopener" style="color:#25D366;font-weight:700">Meta Business</a></li>
                        <li>Configurez un <strong>Numéro de téléphone WhatsApp Business</strong></li>
                        <li>Générez un <strong>Token d'accès</strong> dans le portail développeur Meta</li>
                        <li>L'<strong>ID du téléphone</strong> est disponible dans l'URL de votre compte</li>
                    </ol>
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