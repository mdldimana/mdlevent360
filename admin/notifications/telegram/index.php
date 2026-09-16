<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/telegram/index.php
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

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ============================================
// FONCTION DE TEST ROBUSTE DU TOKEN TELEGRAM
// ============================================

function testTelegramToken(string $token): ?array {
    if (empty($token)) return null;

    $url = 'https://api.telegram.org/bot' . $token . '/getMe';

    // ---- MÉTHODE 1 : cURL (le plus fiable) ----
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

    // ---- MÉTHODE 2 : file_get_contents (fallback) ----
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
// CHARGER LA CONFIG TELEGRAM
// ============================================

$configFile = __DIR__ . '/../../../config/telegram.php';
$botToken   = '';

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
// VÉRIFIER LE BOT
// ============================================

$botInfo = null;
$botOk   = false;
$botUsername = '';

if (!empty($botToken)) {
    $botInfo = testTelegramToken($botToken);
    if (is_array($botInfo) && !empty($botInfo['ok'])) {
        $botOk = true;
        $botUsername = $botInfo['result']['username'] ?? 'inconnu';
    }
}

// ============================================
// STATISTIQUES
// ============================================

$stats = ['total' => 0, 'envoyes' => 0, 'en_attente' => 0];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN i.telegram_sent = 1 THEN 1 ELSE 0 END), 0) AS envoyes
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        WHERE inv.telegram_chat_id IS NOT NULL
          AND i.statut != 'ANNULEE'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['total']      = (int)($result['total'] ?? 0);
    $stats['envoyes']    = (int)($result['envoyes'] ?? 0);
    $stats['en_attente'] = max(0, $stats['total'] - $stats['envoyes']);
} catch (PDOException $e) {
    error_log('Stats telegram : ' . $e->getMessage());
}

// ============================================
// INVITATIONS EN ATTENTE D'ENVOI TELEGRAM
// ============================================

$invitations = [];
$totalEnAttente = 0;

if (!empty($botToken) && $botOk) {
    try {
        $stmt = $pdo->query("
            SELECT 
                i.id,
                i.code_unique,
                i.telegram_sent_at,
                i.telegram_sent,
                i.statut,
                inv.nom,
                inv.prenom,
                inv.telegram_chat_id,
                e.nom AS evenement_nom,
                e.date_evenement
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            WHERE (i.telegram_sent IS NULL OR i.telegram_sent = 0)
              AND inv.telegram_chat_id IS NOT NULL
              AND i.statut != 'ANNULEE'
            ORDER BY i.created_at DESC
            LIMIT 20
        ");
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Liste invitations telegram : ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) AS count
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            WHERE (i.telegram_sent IS NULL OR i.telegram_sent = 0)
              AND inv.telegram_chat_id IS NOT NULL
              AND i.statut != 'ANNULEE'
        ");
        $totalEnAttente = (int)($stmt->fetch()['count'] ?? 0);
    } catch (PDOException $e) {
        error_log('Comptage invitations telegram : ' . $e->getMessage());
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
<title>Telegram - <?php echo APP_NAME; ?></title>
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

    /* ========== STATS ========== */
    .stat-mini {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        padding: 18px 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0, 136, 204, 0.1);
        border-color: rgba(0, 136, 204, 0.2);
    }
    .stat-mini .number { font-size: 28px; font-weight: 800; color: #1a1a1a; line-height: 1; }
    .stat-mini .label {
        font-size: 11px; color: #9a8a7f; font-weight: 600;
        margin-top: 6px; text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .stat-mini .icon-wrap {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 22px;
        color: white;
        margin-bottom: 10px;
    }
    .stat-mini .icon-wrap.blue   { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
    .stat-mini .icon-wrap.green  { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-mini .icon-wrap.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

    /* ========== CARD ========== */
    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
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
        flex-wrap: wrap;
    }
    .card-custom .card-header-custom i { color: #c17c60; }

    /* ========== BOUTONS ========== */
    .btn-telegram {
        background: linear-gradient(135deg, #0088cc, #005f8a);
        color: white;
        border: none;
        padding: 9px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 15px rgba(0, 136, 204, 0.25);
        cursor: pointer;
    }
    .btn-telegram:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 136, 204, 0.35);
        color: white;
    }
    .btn-telegram.small {
        padding: 6px 14px;
        font-size: 12px;
    }

    /* ========== INVITATION ITEM ========== */
    .invitation-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        flex-wrap: wrap;
        gap: 10px;
    }
    .invitation-item:last-child { border-bottom: none; }
    .invitation-item .info { flex: 1; min-width: 150px; }
    .invitation-item .info .name {
        font-weight: 700;
        color: #1a1a1a;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .invitation-item .info .details {
        font-size: 11px;
        color: #9a8a7f;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .invitation-item .info .details i { color: #c17c60; }
    .invitation-item .info .details code {
        font-size: 10px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* ========== BADGES ========== */
    .badge-pill-custom {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .badge-pill-custom.telegram { background: rgba(0, 136, 204, 0.12); color: #0088cc; }
    .badge-pill-custom.success  { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-pill-custom.warning  { background: rgba(245, 158, 11, 0.15); color: #92400e; }
    .badge-pill-custom.danger   { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
    .badge-pill-custom.secondary{ background: rgba(107, 114, 128, 0.12); color: #374151; }

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
    .bot-status a { color: inherit; font-weight: 700; text-decoration: underline; }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state i {
        font-size: 48px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 12px;
    }
    .empty-state h6 { color: #6a5a4a; font-weight: 700; margin-bottom: 4px; }
    .empty-state p { color: #9a8a7f; font-size: 12px; margin: 0 0 12px; }

    /* ========== ANIMATIONS ========== */
    .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in:nth-child(1) { animation-delay: 0.1s; }
    .fade-in:nth-child(2) { animation-delay: 0.2s; }
    .fade-in:nth-child(3) { animation-delay: 0.3s; }

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
        .card-custom { padding: 18px; }
        .invitation-item { flex-direction: column; align-items: flex-start; }
        .invitation-item .info { width: 100%; }
        .invitation-item > div:last-child { width: 100%; }
        .invitation-item .btn-telegram { width: 100%; justify-content: center; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-mini { padding: 14px 12px; }
        .stat-mini .number { font-size: 22px; }
        .stat-mini .icon-wrap { width: 40px; height: 40px; font-size: 18px; }
        .stat-mini .label { font-size: 10px; }
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
                <h4><i class="bi bi-telegram"></i> Telegram</h4>
                <small><i class="bi bi-send-fill"></i> Gestion des messages Telegram</small>
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

            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:15px">
                        <i class="bi bi-gear-fill me-2" style="color:#0088cc"></i>Configuration
                    </h5>
                    <small style="color:#9a8a7f;font-size:12px">Paramètres du bot Telegram</small>
                </div>
                <a href="config.php" class="btn-telegram">
                    <i class="bi bi-sliders2"></i> Configuration
                </a>
            </div>

            <!-- STATUT DU BOT -->
            <div class="mb-4">
                <?php if (!empty($botToken) && $botOk): ?>
                    <span class="bot-status online">
                        <i class="bi bi-check-circle-fill"></i>
                        Bot actif : @<?php echo htmlspecialchars($botUsername); ?>
                    </span>
                <?php elseif (!empty($botToken)): ?>
                    <span class="bot-status offline">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Bot non accessible. Vérifiez le token ou la connexion réseau.
                    </span>
                <?php else: ?>
                    <span class="bot-status offline">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Bot non configuré.
                        <a href="config.php" class="ms-2">Configurer</a>
                    </span>
                <?php endif; ?>
            </div>

            <!-- STATISTIQUES -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-6 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap blue"><i class="bi bi-telegram"></i></div>
                        <div class="number"><?php echo $stats['total']; ?></div>
                        <div class="label">Avec Telegram</div>
                    </div>
                </div>
                <div class="col-md-4 col-6 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="number" style="color:#0088cc"><?php echo $stats['envoyes']; ?></div>
                        <div class="label">Messages envoyés</div>
                    </div>
                </div>
                <div class="col-md-4 col-6 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap orange"><i class="bi bi-clock-history"></i></div>
                        <div class="number" style="color:#c17c60"><?php echo $totalEnAttente; ?></div>
                        <div class="label">En attente d'envoi</div>
                    </div>
                </div>
            </div>

            <!-- INVITATIONS EN ATTENTE -->
            <div class="card-custom fade-in">
                <div class="card-header-custom">
                    <i class="bi bi-clock-history"></i> Messages en attente d'envoi
                    <span class="badge-pill-custom warning ms-2">
                        <i class="bi bi-hourglass-split"></i> <?php echo $totalEnAttente; ?>
                    </span>
                </div>

                <?php if (!empty($invitations)): ?>
                    <?php foreach ($invitations as $inv): ?>
                        <div class="invitation-item">
                            <div class="info">
                                <div class="name">
                                    <?php echo htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']); ?>
                                    <span class="badge-pill-custom telegram">
                                        <i class="bi bi-telegram"></i>
                                        <?php echo htmlspecialchars($inv['telegram_chat_id']); ?>
                                    </span>
                                    <span class="badge-pill-custom <?php echo $inv['statut'] === 'CONFIRMEE' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($inv['statut']); ?>
                                    </span>
                                </div>
                                <div class="details">
                                    <span><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($inv['evenement_nom']); ?></span>
                                    <code><?php echo htmlspecialchars($inv['code_unique']); ?></code>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="envoyer.php?invitation=<?php echo urlencode((string)$inv['id']); ?>" class="btn-telegram small">
                                    <i class="bi bi-send-fill"></i> Envoyer
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($totalEnAttente > count($invitations)): ?>
                        <div class="text-center mt-3">
                            <small style="color:#9a8a7f;font-size:12px">
                                <i class="bi bi-info-circle"></i>
                                + <?php echo $totalEnAttente - count($invitations); ?> autres invitations en attente
                            </small>
                        </div>
                    <?php endif; ?>

                <?php elseif (!empty($botToken) && $botOk): ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill" style="color:#10b981"></i>
                        <h6>Tous les messages ont été envoyés</h6>
                        <p>Aucun message Telegram en attente.</p>
                    </div>

                <?php elseif (!empty($botToken)): ?>
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b"></i>
                        <h6>Bot non accessible</h6>
                        <p>Le bot est configuré mais inaccessible. Vérifiez la connexion réseau.</p>
                        <a href="config.php" class="btn-telegram">
                            <i class="bi bi-gear-fill"></i> Vérifier la configuration
                        </a>
                    </div>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-exclamation-circle-fill" style="color:#ef4444"></i>
                        <h6>Bot non configuré</h6>
                        <p>Configurez d'abord votre bot Telegram.</p>
                        <a href="config.php" class="btn-telegram">
                            <i class="bi bi-gear-fill"></i> Configurer le bot
                        </a>
                    </div>
                <?php endif; ?>
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