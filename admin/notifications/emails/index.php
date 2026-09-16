<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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

requirePermission('notifications.email');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

function safeFetch($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: [];
}

// ========== ÉVÉNEMENTS ACCESSIBLES ==========
$evenements = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT id, nom, date_evenement AS date_debut, date_evenement AS date_fin, lieu 
            FROM evenements 
            WHERE id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur=?) 
              AND statut != 'ANNULE' 
            ORDER BY date_evenement DESC
        ");
        $stmt->execute([$userId]);
        $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $evenements = $pdo->query("
            SELECT id, nom, date_evenement AS date_debut, date_evenement AS date_fin, lieu 
            FROM evenements 
            WHERE statut != 'ANNULE' 
            ORDER BY date_evenement DESC
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log('Load événements (emails/index): ' . $e->getMessage());
    $evenements = [];
}

$idEvenement = (int)($_GET['evenement_id'] ?? 0);
$filtreEmail = $_GET['filtre_email'] ?? 'tous';
$recherche   = trim($_GET['recherche'] ?? '');

// ========== VÉRIFICATION ACCÈS ==========
if ($idEvenement > 0 && !$isUserAdmin) {
    $allowed = false;
    foreach ($evenements as $ev) {
        if ((int)$ev['id'] === $idEvenement) { $allowed = true; break; }
    }
    if (!$allowed) {
        http_response_code(403);
        echo '<div style="padding:40px;font-family:Inter">🚫 Accès refusé à cet événement<br><a href="index.php">Retour</a></div>';
        exit;
    }
}

// ========== INVITATIONS ==========
$invitations = [];
$stats = ['total' => 0, 'envoyes' => 0, 'non_envoyes' => 0, 'avec_email' => 0];

if ($idEvenement > 0) {
    try {
        $sql = "SELECT i.id, i.code_unique, i.email_sent, i.email_sent_at, 
                       i.whatsapp_sent, i.telegram_sent, i.statut, i.created_at, 
                       inv.nom, inv.prenom, inv.email, inv.telephone, 
                       e.nom AS evenement_nom 
                FROM invitations i 
                JOIN invites inv ON i.id_invite = inv.id 
                JOIN evenements e ON i.id_evenement = e.id 
                WHERE i.id_evenement = :id_evenement";
        $params = [':id_evenement' => $idEvenement];

        if ($filtreEmail === 'envoye') {
            $sql .= " AND i.email_sent = 1";
        } elseif ($filtreEmail === 'non_envoye') {
            $sql .= " AND (i.email_sent IS NULL OR i.email_sent = 0)";
        }

        if ($recherche !== '') {
            $sql .= " AND (inv.nom LIKE :rech OR inv.prenom LIKE :rech OR inv.email LIKE :rech)";
            $params[':rech'] = '%' . $recherche . '%';
        }

        $sql .= " ORDER BY i.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtStats = $pdo->prepare("
            SELECT 
                COUNT(*) AS total, 
                SUM(CASE WHEN i.email_sent = 1 THEN 1 ELSE 0 END) AS envoyes, 
                SUM(CASE WHEN i.email_sent IS NULL OR i.email_sent = 0 THEN 1 ELSE 0 END) AS non_envoyes, 
                SUM(CASE WHEN inv.email IS NOT NULL AND inv.email != '' THEN 1 ELSE 0 END) AS avec_email 
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            WHERE i.id_evenement = :id_evenement
        ");
        $stmtStats->execute([':id_evenement' => $idEvenement]);
        $row = safeFetch($stmtStats);
        $stats = [
            'total'       => (int)($row['total'] ?? 0),
            'envoyes'     => (int)($row['envoyes'] ?? 0),
            'non_envoyes' => (int)($row['non_envoyes'] ?? 0),
            'avec_email'  => (int)($row['avec_email'] ?? 0),
        ];
    } catch (PDOException $e) {
        error_log('Load invitations (emails/index): ' . $e->getMessage());
    }
}

// ========== STATS GLOBALES ==========
$statsGlobales = ['total_invitations' => 0, 'emails_envoyes' => 0, 'emails_restants' => 0];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_invitations, 
                   SUM(CASE WHEN i.email_sent = 1 THEN 1 ELSE 0 END) AS emails_envoyes, 
                   SUM(CASE WHEN i.email_sent IS NULL OR i.email_sent = 0 THEN 1 ELSE 0 END) AS emails_restants 
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            WHERE inv.email IS NOT NULL AND inv.email != '' 
              AND i.statut != 'ANNULEE' 
              AND i.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur=?)
        ");
        $stmt->execute([$userId]);
        $r = safeFetch($stmt);
    } else {
        $r = safeFetch($pdo->query("
            SELECT COUNT(*) AS total_invitations, 
                   SUM(CASE WHEN i.email_sent = 1 THEN 1 ELSE 0 END) AS emails_envoyes, 
                   SUM(CASE WHEN i.email_sent IS NULL OR i.email_sent = 0 THEN 1 ELSE 0 END) AS emails_restants 
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            WHERE inv.email IS NOT NULL AND inv.email != '' 
              AND i.statut != 'ANNULEE'
        "));
    }
    $statsGlobales = [
        'total_invitations' => (int)($r['total_invitations'] ?? 0),
        'emails_envoyes'    => (int)($r['emails_envoyes'] ?? 0),
        'emails_restants'   => (int)($r['emails_restants'] ?? 0),
    ];
} catch (PDOException $e) {
    error_log('Stats globales (emails/index): ' . $e->getMessage());
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
<title>Emails - <?php echo APP_NAME; ?></title>
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

    /* ========== STATS ========== */
    .stat-mini {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 16px;
        padding: 18px 20px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }
    .stat-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.1);
        border-color: rgba(193, 124, 96, 0.3);
    }
    .stat-mini .number { font-size: 26px; font-weight: 800; color: #1a1a1a; line-height: 1; }
    .stat-mini .label {
        font-size: 11px; text-transform: uppercase;
        letter-spacing: 0.08em; color: #9a8a7f;
        font-weight: 600; margin-top: 6px;
    }

    /* ========== CARD CUSTOM ========== */
    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }

    /* ========== FILTRE BOX ========== */
    .filter-box {
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
        border: 1px solid rgba(193, 124, 96, 0.15);
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 20px;
    }
    .filter-box label {
        font-weight: 700;
        font-size: 11px;
        color: #6a5a4a;
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .filter-box .form-control,
    .filter-box .form-select {
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        border-radius: 10px;
        padding: 9px 12px;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        font-family: 'Inter', sans-serif;
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .filter-box .form-control:focus,
    .filter-box .form-select:focus {
        border-color: #c17c60;
        box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        outline: none;
        background: white;
    }

    /* ========== BOUTONS ========== */
    .btn-primary-custom {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
    }
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }

    /* ========== BADGES EMAIL ========== */
    .badge-email-sent {
        background: rgba(16, 185, 129, 0.15); color: #065f46;
        padding: 4px 10px; border-radius: 20px;
        font-weight: 700; font-size: 10px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-email-pending {
        background: rgba(245, 158, 11, 0.15); color: #92400e;
        padding: 4px 10px; border-radius: 20px;
        font-weight: 700; font-size: 10px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-email-none {
        background: rgba(239, 68, 68, 0.15); color: #991b1b;
        padding: 4px 10px; border-radius: 20px;
        font-weight: 700; font-size: 10px;
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* ========== TABLE ========== */
    .table-custom { margin-bottom: 0; }
    .table-custom thead th {
        background: rgba(252, 250, 248, 0.9);
        color: #6a5a4a;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-bottom: 1.5px solid rgba(240, 235, 229, 0.8);
        padding: 12px 10px;
    }
    .table-custom tbody td {
        padding: 12px 10px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        color: #1a1a1a;
    }
    .table-custom tbody tr:hover { background: rgba(253, 248, 245, 0.5); }
    .table-custom tbody tr:last-child td { border-bottom: none; }
    .table-custom code {
        font-size: 10px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* ========== BOUTONS ACTION ========== */
    .btn-action {
        width: 34px; height: 34px;
        border-radius: 10px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        background: rgba(255, 255, 255, 0.9);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6a5a4a;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.25s ease;
    }
    .btn-action:hover {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border-color: #c17c60;
        transform: translateY(-2px);
    }

    /* ========== EMPTY ========== */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #9a8a7f;
    }
    .empty-state i {
        font-size: 48px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 12px;
    }
    .empty-state h6 { color: #6a5a4a; font-weight: 700; margin-bottom: 4px; }
    .empty-state p { font-size: 12px; }

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
        .filter-box { padding: 14px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-mini { padding: 12px 14px; }
        .stat-mini .number { font-size: 20px; }
        .stat-mini .label { font-size: 10px; }
        .table-custom thead th { font-size: 9px; padding: 8px 6px; }
        .table-custom tbody td { font-size: 12px; padding: 8px 6px; }
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
                <h4><i class="bi bi-envelope-fill"></i> Emails</h4>
                <small>
                    <i class="bi bi-envelope-paper"></i> <?php echo $statsGlobales['total_invitations']; ?> invitations
                    • <i class="bi bi-check-circle"></i> <?php echo $statsGlobales['emails_envoyes']; ?> envoyés
                    • <i class="bi bi-clock-history"></i> <?php echo $statsGlobales['emails_restants']; ?> en attente
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

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0" style="font-size:14px">
                        <i class="bi bi-gear-fill" style="color:#c17c60"></i> Configuration
                    </h6>
                    <small style="color:#9a8a7f;font-size:11px">Paramètres d'envoi</small>
                </div>
                <a href="config.php" class="btn-primary-custom">
                    <i class="bi bi-sliders2"></i> Config
                </a>
            </div>

            <!-- STATS GLOBALES -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-6">
                    <div class="stat-mini">
                        <div class="number"><?php echo $statsGlobales['total_invitations']; ?></div>
                        <div class="label">Total avec email</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="stat-mini">
                        <div class="number" style="color:#10b981"><?php echo $statsGlobales['emails_envoyes']; ?></div>
                        <div class="label">Envoyés</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="stat-mini">
                        <div class="number" style="color:#c17c60"><?php echo $statsGlobales['emails_restants']; ?></div>
                        <div class="label">En attente</div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="filter-box">
                <form method="GET" id="formFiltres">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label>Événement</label>
                            <select name="evenement_id" id="evenement_id" class="form-select" required>
                                <option value="">-- Choisissez --</option>
                                <?php foreach ($evenements as $ev): ?>
                                    <option value="<?php echo $ev['id']; ?>" <?php echo $idEvenement == (int)$ev['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ev['nom']); ?> (<?php echo date('d/m/Y', strtotime($ev['date_debut'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Statut</label>
                            <select name="filtre_email" class="form-select">
                                <option value="tous" <?php echo $filtreEmail === 'tous' ? 'selected' : ''; ?>>Tous</option>
                                <option value="envoye" <?php echo $filtreEmail === 'envoye' ? 'selected' : ''; ?>>✅ Envoyé</option>
                                <option value="non_envoye" <?php echo $filtreEmail === 'non_envoye' ? 'selected' : ''; ?>>⏳ Non envoyé</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Recherche</label>
                            <input type="text" name="recherche" class="form-control" placeholder="Nom, email..." value="<?php echo htmlspecialchars($recherche); ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn-primary-custom w-100" style="justify-content:center">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- STATS ÉVÉNEMENT SÉLECTIONNÉ -->
            <?php if ($idEvenement > 0): ?>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="stat-mini" style="padding:14px">
                        <div class="number" style="font-size:20px"><?php echo $stats['total']; ?></div>
                        <div class="label">Total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini" style="padding:14px;border-left:3px solid #10b981">
                        <div class="number" style="font-size:20px;color:#10b981"><?php echo $stats['envoyes']; ?></div>
                        <div class="label">Envoyés</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini" style="padding:14px;border-left:3px solid #c17c60">
                        <div class="number" style="font-size:20px;color:#c17c60"><?php echo $stats['non_envoyes']; ?></div>
                        <div class="label">En attente</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini" style="padding:14px;border-left:3px solid #6366f1">
                        <div class="number" style="font-size:20px;color:#6366f1"><?php echo $stats['avec_email']; ?></div>
                        <div class="label">Avec email</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- LISTE -->
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="font-size:13px">
                        <i class="bi bi-list-ul me-1" style="color:#c17c60"></i> Invitations
                        <?php if ($idEvenement > 0): ?>
                            <span style="background:rgba(193, 124, 96, 0.1);color:#c17c60;padding:2px 8px;border-radius:20px;font-size:10px;margin-left:6px;font-weight:700">
                                <?php echo count($invitations); ?>
                            </span>
                        <?php endif; ?>
                    </h6>
                </div>

                <?php if ($idEvenement > 0 && !empty($invitations)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invité</th>
                                    <th>Email</th>
                                    <th>Statut</th>
                                    <th>Envoi</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($invitations as $inv): ?>
                                <tr>
                                    <td style="color:#9a8a7f;font-size:11px;font-weight:600"><?php echo $i++; ?></td>
                                    <td>
                                        <strong style="font-size:13px"><?php echo htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']); ?></strong><br>
                                        <code><?php echo htmlspecialchars($inv['code_unique']); ?></code>
                                    </td>
                                    <td style="font-size:12px">
                                        <?php if (!empty($inv['email'])): ?>
                                            <i class="bi bi-envelope" style="color:#c17c60"></i>
                                            <?php echo htmlspecialchars($inv['email']); ?>
                                        <?php else: ?>
                                            <span style="color:#b8a99c;font-style:italic">Pas d'email</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($inv['email_sent'] == 1): ?>
                                            <span class="badge-email-sent"><i class="bi bi-check-circle-fill"></i> Envoyé</span>
                                        <?php else: ?>
                                            <span class="badge-email-pending"><i class="bi bi-clock-fill"></i> Attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:11px;color:#9a8a7f">
                                        <?php if (!empty($inv['email_sent_at'])): ?>
                                            <i class="bi bi-calendar-check"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($inv['email_sent_at'])); ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if ($inv['email_sent'] != 1 && !empty($inv['email'])): ?>
                                                <a href="envoyer.php?invitation=<?php echo $inv['id']; ?>" class="btn-action" title="Envoyer">
                                                    <i class="bi bi-send-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo htmlspecialchars(BASE_PATH); ?>/public/invitation.php?code=<?php echo urlencode($inv['code_unique']); ?>" 
                                               class="btn-action" 
                                               title="Voir"
                                               target="_blank"
                                               rel="noopener">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($idEvenement > 0): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h6>Aucune invitation</h6>
                        <p>Aucune invitation pour cet événement avec ces filtres.</p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-plus"></i>
                        <h6>Sélectionnez un événement</h6>
                        <p>Choisissez un événement pour voir et gérer ses emails.</p>
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
// ========== SIDEBAR MOBILE (identique à evenements/index) ==========
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

// ========== AUTO-SUBMIT AU CHANGEMENT D'ÉVÉNEMENT ==========
document.getElementById('evenement_id')?.addEventListener('change', function() {
    document.getElementById('formFiltres').submit();
});
</script>
</body>
</html>