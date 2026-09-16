<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';

// ========== FIX INFINITYFREE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

requirePermission('notifications.voir');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ========== FILTRE EVENEMENTS UTILISATEUR ==========
$eventFilterSql = "";
$eventParams = [];
if (!$isUserAdmin) {
    $eventFilterSql = " AND i.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)";
    $eventParams = [$userId];
}

$stats = [
    'emails_envoyes'   => 0,
    'whatsapp_envoyes' => 0,
    'telegram_envoyes' => 0,
    'total_attente'    => 0,
    'emails_ouverts'   => 0,
    'whatsapp_lus'     => 0,
];

try {
    // EMAILS
    $sql = "SELECT COUNT(*) AS cnt FROM invitations i WHERE i.email_sent = 1 $eventFilterSql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $stats['emails_envoyes'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // WHATSAPP
    $sql = "SELECT COUNT(*) AS cnt FROM invitations i WHERE i.whatsapp_sent = 1 $eventFilterSql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $stats['whatsapp_envoyes'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // TELEGRAM
    $sql = "SELECT COUNT(*) AS cnt FROM invitations i WHERE i.telegram_sent = 1 $eventFilterSql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $stats['telegram_envoyes'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // EN ATTENTE
    $sql = "SELECT COUNT(*) AS cnt FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            WHERE i.email_sent = 0 AND i.statut != 'ANNULEE' 
            AND inv.email IS NOT NULL AND inv.email != '' $eventFilterSql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $stats['total_attente'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // EMAILS OUVERTS
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt 
            FROM journal_activites ja 
            JOIN invitations i ON ja.id_reference = i.id AND ja.type_reference = 'invitation' 
            WHERE ja.action = 'EMAIL_OPENED' 
            AND i.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
        ");
        $stmt->execute([$userId]);
        $stats['emails_ouverts'] = (int)($stmt->fetch()['cnt'] ?? 0);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM journal_activites WHERE action='EMAIL_OPENED'");
        $stats['emails_ouverts'] = (int)($stmt->fetch()['cnt'] ?? 0);
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM journal_activites WHERE action='WHATSAPP_READ'");
    $stats['whatsapp_lus'] = (int)($stmt->fetch()['cnt'] ?? 0);

} catch (PDOException $e) {
    error_log('Stats notif: ' . $e->getMessage());
}

// ========== INVITATIONS SANS NOTIFICATION ==========
$invitationsSansNotif = [];
try {
    $sql = "SELECT i.id, i.code_unique, inv.nom, inv.prenom, inv.email, 
                   e.nom AS evenement_nom, i.created_at
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            WHERE i.email_sent = 0 AND i.statut != 'ANNULEE' 
            AND inv.email IS NOT NULL AND inv.email != '' $eventFilterSql
            ORDER BY i.created_at DESC 
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $invitationsSansNotif = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// ========== DERNIERS ENVOIS ==========
$derniersEnvois = [];
try {
    $sql = "SELECT i.id, i.code_unique, i.email_sent_at, 
                   inv.nom, inv.prenom, inv.email, e.nom AS evenement_nom
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            WHERE i.email_sent = 1 $eventFilterSql
            ORDER BY i.email_sent_at DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventParams);
    $derniersEnvois = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$tauxOuverture = $stats['emails_envoyes'] > 0 
    ? round(($stats['emails_ouverts'] / $stats['emails_envoyes']) * 100, 1) 
    : 0;
$totalEnvoye = $stats['emails_envoyes'] + $stats['whatsapp_envoyes'] + $stats['telegram_envoyes'];
$nbEnAttente = $stats['total_attente'];

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
<title>Notifications - <?php echo APP_NAME; ?></title>
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

    /* ========== LAYOUT (comme evenements/index) ========== */
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

    /* ========== BANNIÈRE INFO ========== */
    .info-banner {
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.08), rgba(212, 165, 116, 0.08));
        border: 1px solid rgba(193, 124, 96, 0.2);
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #6a5a4a;
    }
    .info-banner i { color: #c17c60; font-size: 18px; flex-shrink: 0; }
    .info-banner strong { color: #c17c60; }

    /* ========== STATS ========== */
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 16px;
        padding: 18px 20px;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }
    .stat-card:hover {
        border-color: rgba(193, 124, 96, 0.3);
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.1);
        transform: translateY(-3px);
    }
    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: white; flex-shrink: 0;
    }
    .stat-icon.email    { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-icon.whatsapp { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-icon.telegram { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
    .stat-icon.attente  { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .stat-icon.growth   { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .stat-num { font-size: 22px; font-weight: 800; line-height: 1; color: #1a1a1a; }
    .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #9a8a7f; font-weight: 600; margin-top: 2px; }

    /* ========== CARDS BOX ========== */
    .card-box {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        padding: 22px;
        height: 100%;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }
    .card-box .card-head {
        font-weight: 700;
        font-size: 15px;
        color: #1a1a1a;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-box .card-head i { color: #c17c60; }

    /* ========== BOUTONS CANAUX ========== */
    .btn-channel {
        border-radius: 12px;
        padding: 14px 16px;
        font-weight: 600;
        font-size: 13px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        color: #1a1a1a;
        background: rgba(255, 255, 255, 0.9);
    }
    .btn-channel:hover {
        border-color: #c17c60;
        background: rgba(253, 248, 245, 0.95);
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(193, 124, 96, 0.15);
        color: #1a1a1a;
    }
    .btn-channel i.main-icon { font-size: 22px; color: #c17c60; flex-shrink: 0; }

    /* ========== BOUTONS ACTION ========== */
    .btn-send {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 11px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .btn-send:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(193, 124, 96, 0.3);
        color: white;
    }
    .btn-send.secondary {
        background: rgba(255, 255, 255, 0.9);
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        color: #6a5a4a;
    }
    .btn-send.secondary:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
        box-shadow: none;
    }

    /* ========== INVITATION ITEM ========== */
    .invitation-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        gap: 10px;
        flex-wrap: wrap;
    }
    .invitation-item:last-child { border-bottom: none; }
    .invitation-item .name { font-weight: 700; color: #1a1a1a; font-size: 13px; }
    .invitation-item .details {
        font-size: 11px;
        color: #9a8a7f;
        margin-top: 2px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .invitation-item .details i { color: #c17c60; margin-right: 3px; }
    .invitation-item code {
        font-size: 10px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* ========== PROGRESS ========== */
    .progress-custom {
        height: 6px;
        border-radius: 10px;
        background: rgba(234, 227, 220, 0.5);
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-custom .bar {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, #c17c60, #d4a574);
        transition: width 0.6s ease;
    }

    /* ========== EMPTY ========== */
    .empty {
        text-align: center;
        padding: 40px 20px;
        color: #9a8a7f;
    }
    .empty i {
        font-size: 42px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 12px;
    }
    .empty h6 { color: #6a5a4a; font-weight: 700; margin-bottom: 4px; }
    .empty p { font-size: 12px; }

    /* ========== BULK BAR ========== */
    .bulk-bar {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #1a1a1a;
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        z-index: 300;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s;
    }
    .bulk-bar.show { opacity: 1; pointer-events: auto; }
    .bulk-bar .count { font-weight: 700; font-size: 13px; }
    .bulk-bar button {
        border: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
    }
    .bulk-bar .btn-primary-gold {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
    }
    .bulk-bar .btn-cancel {
        background: rgba(255, 255, 255, 0.15);
        color: white;
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
        .card-box { padding: 18px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-box { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-card { padding: 12px 14px; gap: 10px; }
        .stat-icon { width: 40px; height: 40px; font-size: 17px; }
        .stat-num { font-size: 18px; }
        .stat-label { font-size: 10px; }
        .info-banner { font-size: 12px; padding: 10px 12px; }
        .invitation-item { flex-direction: column; align-items: stretch; }
        .invitation-item > div:last-child { justify-content: center; }
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
        <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-bell-fill"></i> Notifications</h4>
                <small>
                    <i class="bi bi-send"></i> <?php echo $totalEnvoye; ?> envoyé(s)
                    • <i class="bi bi-clock-history"></i> <?php echo $nbEnAttente; ?> en attente
                    <?php if ($isUserAdmin): ?>
                        • <i class="bi bi-shield-check"></i> Admin
                    <?php else: ?>
                        • <i class="bi bi-funnel"></i> Filtré
                    <?php endif; ?>
                </small>
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

            <?php if (!$isUserAdmin): ?>
                <div class="info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Vous voyez uniquement les notifications de <strong>vos événements associés</strong>
                        (<?php echo $totalEnvoye; ?> envoyés, <?php echo $nbEnAttente; ?> en attente).
                    </div>
                </div>
            <?php else: ?>
                <div class="info-banner">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        Mode admin • <strong><?php echo $totalEnvoye; ?> notifications</strong> envoyées au total
                        • Accès complet
                    </div>
                </div>
            <?php endif; ?>

            <!-- STATS PRINCIPALES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon email"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['emails_envoyes']; ?></div>
                            <div class="stat-label">Emails envoyés</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon whatsapp"><i class="bi bi-whatsapp"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['whatsapp_envoyes']; ?></div>
                            <div class="stat-label">WhatsApp</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon telegram"><i class="bi bi-telegram"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['telegram_envoyes']; ?></div>
                            <div class="stat-label">Telegram</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon attente"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['total_attente']; ?></div>
                            <div class="stat-label">En attente</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAUX D'OUVERTURE -->
            <?php if ($stats['emails_envoyes'] > 0): ?>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="stat-card" style="justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:15px;">
                            <div class="stat-icon growth">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#9a8a7f;font-weight:600">
                                    Taux d'ouverture
                                </div>
                                <div class="stat-num"><?php echo $tauxOuverture; ?>%</div>
                                <div style="font-size:11px;color:#9a8a7f">
                                    <?php echo $stats['emails_ouverts']; ?> ouverts sur <?php echo $stats['emails_envoyes']; ?> envoyés
                                </div>
                            </div>
                        </div>
                        <div style="width:200px">
                            <div class="progress-custom">
                                <div class="bar" style="width: <?php echo $tauxOuverture; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- CANAUX DISPONIBLES -->
                <div class="col-lg-4">
                    <div class="card-box">
                        <div class="card-head"><i class="bi bi-megaphone-fill"></i> Canaux disponibles</div>
                        <div class="d-grid gap-2">
                            <?php if (hasPermission('notifications.email')): ?>
                                <a href="emails/index.php" class="btn-channel">
                                    <i class="bi bi-envelope-fill main-icon"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size:13px">Email</div>
                                        <div style="font-size:11px;color:#9a8a7f">Envoyer invitations par email</div>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto" style="font-size:14px;color:#b8a99c"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (hasPermission('notifications.whatsapp')): ?>
                                <a href="whatsapp/index.php" class="btn-channel">
                                    <i class="bi bi-whatsapp main-icon"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size:13px">WhatsApp</div>
                                        <div style="font-size:11px;color:#9a8a7f">Messages WhatsApp</div>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto" style="font-size:14px;color:#b8a99c"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (hasPermission('notifications.telegram')): ?>
                                <a href="telegram/index.php" class="btn-channel">
                                    <i class="bi bi-telegram main-icon"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size:13px">Telegram</div>
                                        <div style="font-size:11px;color:#9a8a7f">Messages Telegram</div>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto" style="font-size:14px;color:#b8a99c"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- EN ATTENTE D'EMAIL -->
                <div class="col-lg-8">
                    <div class="card-box">
                        <div class="card-head">
                            <i class="bi bi-clock-history"></i> En attente d'email
                            <span style="background:rgba(245, 158, 11, 0.15);color:#92400e;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;margin-left:8px">
                                <?php echo $nbEnAttente; ?>
                            </span>
                        </div>
                        <?php if (!empty($invitationsSansNotif)): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;cursor:pointer;color:#6a5a4a;">
                                    <input type="checkbox" id="checkAll" style="width:16px;height:16px;accent-color:#c17c60;">
                                    Tout sélectionner
                                </label>
                                <span id="selectedInfo" style="font-size:11px;color:#9a8a7f"></span>
                            </div>
                            <?php foreach ($invitationsSansNotif as $inv): ?>
                                <div class="invitation-item" data-id="<?php echo $inv['id']; ?>">
                                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                                        <input type="checkbox" class="notif-checkbox" value="<?php echo $inv['id']; ?>" style="width:16px;height:16px;accent-color:#c17c60;flex-shrink:0;">
                                        <div style="min-width:0;">
                                            <div class="name"><?php echo htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']); ?></div>
                                            <div class="details">
                                                <span><i class="bi bi-calendar-event"></i><?php echo htmlspecialchars($inv['evenement_nom']); ?></span>
                                                <span><i class="bi bi-envelope"></i><?php echo htmlspecialchars($inv['email']); ?></span>
                                                <code><?php echo htmlspecialchars($inv['code_unique']); ?></code>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:6px;flex-shrink:0;">
                                        <?php if (hasPermission('notifications.email')): ?>
                                            <a href="emails/envoyer.php?invitation=<?php echo $inv['id']; ?>" class="btn-send">
                                                <i class="bi bi-envelope-fill"></i> Envoyer
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo htmlspecialchars(BASE_PATH); ?>/public/invitation.php?code=<?php echo htmlspecialchars($inv['code_unique']); ?>" 
                                           class="btn-send secondary" 
                                           target="_blank"
                                           rel="noopener">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($nbEnAttente > count($invitationsSansNotif)): ?>
                                <div class="text-center mt-3">
                                    <a href="emails/index.php" class="btn-send secondary" style="padding:8px 16px;">
                                        Voir les <?php echo $nbEnAttente - count($invitationsSansNotif); ?> autres
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty">
                                <i class="bi bi-check-circle-fill"></i>
                                <h6>Tous les emails envoyés !</h6>
                                <p>Aucune invitation en attente.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DERNIERS ENVOIS -->
            <?php if (!empty($derniersEnvois)): ?>
            <div class="row g-3 mt-2">
                <div class="col-12">
                    <div class="card-box">
                        <div class="card-head">
                            <i class="bi bi-clock-fill"></i> Derniers emails envoyés
                            <span style="background:rgba(16, 185, 129, 0.15);color:#065f46;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;margin-left:8px">
                                <?php echo count($derniersEnvois); ?>
                            </span>
                        </div>
                        <?php foreach ($derniersEnvois as $envoi): ?>
                            <div class="invitation-item">
                                <div>
                                    <div class="name"><?php echo htmlspecialchars($envoi['prenom'] . ' ' . $envoi['nom']); ?></div>
                                    <div class="details">
                                        <span><i class="bi bi-calendar-event"></i><?php echo htmlspecialchars($envoi['evenement_nom']); ?></span>
                                        <span><i class="bi bi-envelope"></i><?php echo htmlspecialchars($envoi['email']); ?></span>
                                        <span style="background:rgba(16, 185, 129, 0.15);color:#065f46;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;margin-left:6px">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?php echo $envoi['email_sent_at'] ? date('d/m/Y H:i', strtotime($envoi['email_sent_at'])) : '-'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div><code><?php echo htmlspecialchars($envoi['code_unique']); ?></code></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- BARRE DE SÉLECTION GROUPÉE -->
<div class="bulk-bar" id="bulkBar">
    <span class="count" id="bulkCount">0 sélectionné(s)</span>
    <div style="display:flex;gap:8px;">
        <button class="btn-cancel" onclick="clearSelection()">Annuler</button>
        <button class="btn-primary-gold" onclick="bulkSend()">
            <i class="bi bi-envelope-fill"></i> Envoyer sélection
        </button>
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

// ========== SÉLECTION GROUPÉE ==========
const checkAll     = document.getElementById('checkAll');
const checkboxes   = document.querySelectorAll('.notif-checkbox');
const bulkBar      = document.getElementById('bulkBar');
const bulkCount    = document.getElementById('bulkCount');
const selectedInfo = document.getElementById('selectedInfo');

function updateBulk() {
    const selected = document.querySelectorAll('.notif-checkbox:checked');
    const count    = selected.length;

    document.querySelectorAll('.invitation-item').forEach(row => {
        const cb = row.querySelector('.notif-checkbox');
        if (cb) {
            row.style.background = cb.checked ? 'rgba(253, 248, 245, 0.8)' : 'transparent';
        }
    });

    if (count > 0) {
        bulkBar.classList.add('show');
        bulkCount.textContent = count + ' sélectionné(s)';
        if (selectedInfo) selectedInfo.textContent = count + ' coché(s)';
    } else {
        bulkBar.classList.remove('show');
        if (selectedInfo) selectedInfo.textContent = '';
    }

    if (checkAll) checkAll.checked = (count === checkboxes.length && count > 0);
}

if (checkAll) {
    checkAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = checkAll.checked);
        updateBulk();
    });
}

checkboxes.forEach(cb => cb.addEventListener('change', updateBulk));

function clearSelection() {
    checkboxes.forEach(cb => cb.checked = false);
    if (checkAll) checkAll.checked = false;
    updateBulk();
}

function bulkSend() {
    const ids = Array.from(document.querySelectorAll('.notif-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;
    if (!confirm('Envoyer ' + ids.length + ' email(s) ?')) return;
    window.location.href = 'emails/envoyer_groupe.php?ids=' + ids.join(',');
}
</script>
</body>
</html>