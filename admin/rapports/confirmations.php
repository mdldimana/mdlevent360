<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
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

requirePermission('rapports.voir');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ============================================
// FILTRAGE PAR ÉVÉNEMENTS ACCESSIBLES
// ============================================

$accessibleEventIds = [];
if (!$isUserAdmin) {
    try {
        $stmt = $pdo->prepare("SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        $accessibleEventIds = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_evenement'));
    } catch (PDOException $e) {
        error_log('Erreur chargement événements accessibles : ' . $e->getMessage());
    }
}

// ============================================
// FILTRES
// ============================================

$filtre_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$filtre_reponse   = trim($_GET['reponse'] ?? '');
$filtre_recherche = trim($_GET['search'] ?? '');

// Validation réponse
$reponsesValides = ['CONFIRMEE', 'REFUSEE'];
if ($filtre_reponse !== '' && !in_array($filtre_reponse, $reponsesValides, true)) {
    $filtre_reponse = '';
}

// Vérifier l'accès à l'événement demandé
if ($filtre_evenement > 0 && !$isUserAdmin) {
    if (!in_array($filtre_evenement, $accessibleEventIds, true)) {
        header('Location: ' . BASE_PATH . '/403.php');
        exit;
    }
}

// ============================================
// CONSTRUCTION DE LA REQUÊTE
// ============================================

$whereConditions = [];
$params = [];

// ⭐ Filtrage utilisateur
if (!$isUserAdmin) {
    if (!empty($accessibleEventIds)) {
        $placeholders = implode(',', array_fill(0, count($accessibleEventIds), '?'));
        $whereConditions[] = "i.id_evenement IN ($placeholders)";
        $params = array_merge($params, $accessibleEventIds);
    } else {
        $whereConditions[] = "1 = 0";
    }
}

if ($filtre_evenement > 0) {
    $whereConditions[] = "i.id_evenement = ?";
    $params[] = $filtre_evenement;
}

if ($filtre_reponse !== '') {
    $whereConditions[] = "c.reponse = ?";
    $params[] = $filtre_reponse;
}

if ($filtre_recherche !== '') {
    $whereConditions[] = "(inv.nom LIKE ? OR inv.prenom LIKE ? OR inv.email LIKE ?)";
    $searchParam = '%' . $filtre_recherche . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// RÉCUPÉRATION DES CONFIRMATIONS
// ============================================

$confirmations = [];
try {
    $sql = "
        SELECT 
            c.id, c.reponse, c.nombre_personnes, c.preference_alimentaire,
            c.commentaire, c.date_confirmation,
            inv.nom, inv.prenom, inv.email, inv.telephone,
            i.code_unique,
            e.nom AS evenement_nom,
            e.date_evenement
        FROM confirmations c
        INNER JOIN invitations i ON c.id_invitation = i.id
        INNER JOIN invites inv ON i.id_invite = inv.id
        INNER JOIN evenements e ON i.id_evenement = e.id
        $whereClause
        ORDER BY c.date_confirmation DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $confirmations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur confirmations : ' . $e->getMessage());
}

// ============================================
// STATS
// ============================================

$stats = [
    'total'      => count($confirmations),
    'confirmees' => 0,
    'refusees'   => 0,
    'personnes'  => 0,
];

foreach ($confirmations as $c) {
    if (($c['reponse'] ?? '') === 'CONFIRMEE') {
        $stats['confirmees']++;
        $stats['personnes'] += (int)($c['nombre_personnes'] ?? 0);
    } elseif (($c['reponse'] ?? '') === 'REFUSEE') {
        $stats['refusees']++;
    }
}

// ============================================
// RÉCUPÉRATION DES ÉVÉNEMENTS POUR LE FILTRE
// ============================================

$evenements = [];
try {
    if (!$isUserAdmin) {
        if (!empty($accessibleEventIds)) {
            $placeholders = implode(',', array_fill(0, count($accessibleEventIds), '?'));
            $stmt = $pdo->prepare("
                SELECT id, nom 
                FROM evenements 
                WHERE id IN ($placeholders) AND statut != 'ANNULE'
                ORDER BY nom
            ");
            $stmt->execute($accessibleEventIds);
        } else {
            $stmt = null;
        }
    } else {
        $stmt = $pdo->query("
            SELECT id, nom 
            FROM evenements 
            WHERE statut != 'ANNULE' 
            ORDER BY nom
        ");
    }
    if ($stmt) {
        $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log('Erreur événements confirmations : ' . $e->getMessage());
}

$reponses = $reponsesValides;
$reponseLabels = [
    'CONFIRMEE' => 'Confirmée',
    'REFUSEE'   => 'Refusée',
];
$reponseIcons = [
    'CONFIRMEE' => 'bi-check-circle-fill',
    'REFUSEE'   => 'bi-x-circle-fill',
];

$alimentaireLabels = [
    'STANDARD'    => 'Standard',
    'VEGETARIEN'  => 'Végétarien',
    'VEGETALIEN'  => 'Végétalien',
    'AUTRE'       => 'Autre',
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
<title>Confirmations - <?php echo APP_NAME; ?></title>
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
        padding: 18px 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.1);
        border-color: rgba(193, 124, 96, 0.2);
    }
    .stat-mini .icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: white; flex-shrink: 0;
    }
    .stat-mini .icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .stat-mini .icon.green  { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-mini .icon.red    { background: linear-gradient(135deg, #ef4444, #f87171); }
    .stat-mini .icon.blue   { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-mini .stat-number { font-size: 22px; font-weight: 800; color: #1a1a1a; line-height: 1; }
    .stat-mini .stat-label {
        font-size: 11px; color: #9a8a7f;
        font-weight: 600; text-transform: uppercase;
        letter-spacing: 0.05em; margin-top: 3px;
    }

    /* ========== CARD ========== */
    .card-rapport {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    .card-rapport .card-header-custom {
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
    .card-rapport .card-header-custom i { color: #c17c60; }

    /* ========== FILTRES ========== */
    .filters-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        padding: 14px;
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 14px;
        align-items: center;
    }
    .filters-bar .filter-group { display: flex; align-items: center; gap: 8px; }
    .filters-bar .filter-group label {
        font-size: 11px; font-weight: 700; color: #6a5a4a;
        margin: 0; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .filters-bar .filter-group label i { color: #c17c60; margin-right: 4px; }
    .filters-bar .filter-group select,
    .filters-bar .filter-group input {
        padding: 8px 14px;
        border-radius: 10px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        font-family: 'Inter', sans-serif;
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .filters-bar .filter-group select:focus,
    .filters-bar .filter-group input:focus {
        border-color: #c17c60;
        box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        outline: none;
        background: white;
    }
    .filters-bar .btn-filter {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        padding: 9px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(193, 124, 96, 0.2);
        cursor: pointer;
    }
    .filters-bar .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(193, 124, 96, 0.3);
        color: white;
    }
    .filters-bar .btn-reset {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 9px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filters-bar .btn-reset:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
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
        white-space: nowrap;
    }
    .table-custom tbody td {
        padding: 12px 10px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        color: #1a1a1a;
    }
    .table-custom tbody tr:last-child td { border-bottom: none; }
    .table-custom tbody tr:hover { background: rgba(193, 124, 96, 0.03); }
    .table-custom code {
        font-size: 10px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }
    .table-custom .invite-name { font-weight: 700; color: #1a1a1a; font-size: 13px; }
    .table-custom .invite-sub { font-size: 11px; color: #9a8a7f; margin-top: 2px; }
    .table-custom .comment-cell {
        max-width: 220px;
        font-size: 12px;
        color: #6a5a4a;
        line-height: 1.5;
    }

    /* ========== BADGES ========== */
    .badge-reponse {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .badge-reponse.confirmee { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-reponse.refusee   { background: rgba(239, 68, 68, 0.12); color: #991b1b; }

    .badge-pers {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(59, 130, 246, 0.12);
        color: #1e40af;
    }
    .badge-pref {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        background: rgba(245, 158, 11, 0.15);
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    /* ========== BOUTONS ========== */
    .btn-export {
        padding: 6px 14px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 11px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    .btn-export.pdf {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        box-shadow: 0 3px 10px rgba(193, 124, 96, 0.2);
    }
    .btn-export.pdf:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(193, 124, 96, 0.3);
        color: white;
    }
    .btn-export.excel {
        background: linear-gradient(135deg, #10b981, #34d399);
        color: white;
        box-shadow: 0 3px 10px rgba(16, 185, 129, 0.2);
    }
    .btn-export.excel:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        color: white;
    }
    .btn-export.secondary {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
    }
    .btn-export.secondary:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
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
    .empty-state h6 { color: #6a5a4a; font-weight: 700; margin-bottom: 6px; }
    .empty-state p { font-size: 13px; margin: 0; }

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
        .card-rapport { padding: 18px; }
        .filters-bar { flex-direction: column; align-items: stretch; }
        .filters-bar .filter-group { flex-wrap: wrap; }
        .filters-bar .filter-group select,
        .filters-bar .filter-group input { flex: 1; min-width: 120px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-rapport { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-mini { padding: 12px 14px; gap: 10px; }
        .stat-mini .icon { width: 38px; height: 38px; font-size: 17px; }
        .stat-mini .stat-number { font-size: 18px; }
        .stat-mini .stat-label { font-size: 10px; }
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
        <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-check-circle-fill"></i> Confirmations</h4>
                <small>
                    <i class="bi bi-list-ul"></i> <?php echo $stats['total']; ?> réponse(s)
                    <?php if (!$isUserAdmin): ?>
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

            <!-- FILTRES -->
            <form method="GET" action="" class="filters-bar fade-in">
                <div class="filter-group">
                    <label><i class="bi bi-search"></i> Recherche</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>"
                           placeholder="Nom, email..." style="min-width: 160px">
                </div>
                <div class="filter-group">
                    <label><i class="bi bi-calendar-event-fill"></i> Événement</label>
                    <select name="evenement">
                        <option value="0">Tous</option>
                        <?php foreach ($evenements as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php echo $filtre_evenement === (int)$e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="bi bi-funnel-fill"></i> Réponse</label>
                    <select name="reponse">
                        <option value="">Toutes</option>
                        <?php foreach ($reponses as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $filtre_reponse === $r ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reponseLabels[$r] ?? $r); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel-fill"></i> Filtrer
                </button>
                <a href="confirmations.php" class="btn-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                </a>
            </form>

            <!-- STATS -->
            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-3 col-6">
                    <div class="stat-mini">
                        <div class="icon orange"><i class="bi bi-envelope-paper-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo (int)$stats['total']; ?></div>
                            <div class="stat-label">Total réponses</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini">
                        <div class="icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo (int)$stats['confirmees']; ?></div>
                            <div class="stat-label">Confirmées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini">
                        <div class="icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo (int)$stats['refusees']; ?></div>
                            <div class="stat-label">Refusées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini">
                        <div class="icon blue"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo (int)$stats['personnes']; ?></div>
                            <div class="stat-label">Personnes attendues</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU -->
            <div class="card-rapport fade-in">
                <div class="card-header-custom">
                    <span style="flex:1">
                        <i class="bi bi-check-circle-fill"></i> Liste des confirmations
                        <span style="background:rgba(193,124,96,0.12);color:#c17c60;padding:2px 10px;border-radius:20px;font-size:11px;margin-left:8px;font-weight:700">
                            <?php echo (int)$stats['total']; ?>
                        </span>
                    </span>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <a href="export_pdf/export_confirmations.php?evenement=<?php echo $filtre_evenement; ?>&reponse=<?php echo urlencode($filtre_reponse); ?>&search=<?php echo urlencode($filtre_recherche); ?>" 
   class="btn-export pdf" target="_blank" rel="noopener">
    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
</a>
                        <a href="export.php?type=confirmations&format=excel" 
                           class="btn-export excel" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-excel-fill"></i> Excel
                        </a>
                    </div>
                </div>

                <?php if (!empty($confirmations)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Invité</th>
                                    <th>Événement</th>
                                    <th style="text-align:center">Réponse</th>
                                    <th style="text-align:center">Pers.</th>
                                    <th>Préférence</th>
                                    <th>Date</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($confirmations as $c):
                                    $reponse = $c['reponse'] ?? 'CONFIRMEE';
                                    $reponseClass = $reponse === 'CONFIRMEE' ? 'confirmee' : 'refusee';
                                    $reponseIcon  = $reponseIcons[$reponse] ?? 'bi-circle-fill';
                                    $reponseLabel = $reponseLabels[$reponse] ?? $reponse;
                                    $prefLabel = $alimentaireLabels[$c['preference_alimentaire'] ?? ''] ?? '—';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="invite-name">
                                                <?php echo htmlspecialchars(trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''))); ?>
                                            </div>
                                            <?php if (!empty($c['email'])): ?>
                                                <div class="invite-sub">
                                                    <i class="bi bi-envelope-fill"></i>
                                                    <?php echo htmlspecialchars($c['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($c['code_unique'])): ?>
                                                <div class="invite-sub">
                                                    <code><?php echo htmlspecialchars($c['code_unique']); ?></code>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;font-size:12px">
                                                <?php echo htmlspecialchars($c['evenement_nom'] ?? '—'); ?>
                                            </div>
                                            <div class="invite-sub">
                                                <i class="bi bi-calendar-fill"></i>
                                                <?php echo !empty($c['date_evenement']) ? date('d/m/Y', strtotime($c['date_evenement'])) : '—'; ?>
                                            </div>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="badge-reponse <?php echo $reponseClass; ?>">
                                                <i class="bi <?php echo $reponseIcon; ?>"></i>
                                                <?php echo htmlspecialchars($reponseLabel); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="badge-pers">
                                                <i class="bi bi-people-fill"></i>
                                                <?php echo (int)($c['nombre_personnes'] ?? 0); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($prefLabel !== '—'): ?>
                                                <span class="badge-pref">
                                                    <?php echo htmlspecialchars($prefLabel); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#b8a99c;font-style:italic;font-size:11px">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:11px;color:#9a8a7f;white-space:nowrap">
                                            <?php echo !empty($c['date_confirmation']) ? date('d/m/Y H:i', strtotime($c['date_confirmation'])) : '—'; ?>
                                        </td>
                                        <td class="comment-cell">
                                            <?php if (!empty($c['commentaire'])): ?>
                                                <?php echo nl2br(htmlspecialchars(mb_strimwidth($c['commentaire'], 0, 100, '...'))); ?>
                                            <?php else: ?>
                                                <span style="color:#b8a99c;font-style:italic">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h6>Aucune confirmation trouvée</h6>
                        <p>Aucune réponse ne correspond aux filtres sélectionnés.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BOUTON RETOUR -->
            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="index.php" class="btn-export secondary">
                    <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                </a>
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