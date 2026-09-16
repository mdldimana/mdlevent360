<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// ⭐ Fix InfinityFree - définir BASE_PATH et APP_NAME si non définis
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

// Vérifier les permissions
requirePermission('presences.voir');

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$isUserAdmin = isAdmin();

$pdo = getDbConnection();

// ============================================
// FILTRES ET PAGINATION
// ============================================

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(5, min(50, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$filtre_evenement = (int)($_GET['evenement'] ?? 0);
$filtre_recherche = trim($_GET['search'] ?? '');
$filtre_date      = trim($_GET['date'] ?? '');

// ============================================
// CONSTRUCTION DE LA REQUÊTE
// ============================================

$whereConditions = [];
$params = [];

// ⭐ FILTRAGE PAR UTILISATEUR (admins voient tout)
if (!$isUserAdmin) {
    $whereConditions[] = "e.id IN (
        SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
    )";
    $params[] = $userId;
}

if ($filtre_evenement > 0) {
    $whereConditions[] = "e.id = ?";
    $params[] = $filtre_evenement;
}

if (!empty($filtre_recherche)) {
    $whereConditions[] = "(inv.nom LIKE ? OR inv.prenom LIKE ? OR i.code_unique LIKE ?)";
    $like = '%' . $filtre_recherche . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($filtre_date)) {
    $whereConditions[] = "DATE(p.date_entree) = ?";
    $params[] = $filtre_date;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// COMPTAGE
// ============================================

$totalCount = 0;
try {
    $countSql = "
        SELECT COUNT(*) AS total 
        FROM presences p
        JOIN invitations i ON p.id_invitation = i.id
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int)($stmt->fetch()['total'] ?? 0);
} catch (PDOException $e) {
    error_log('Count presences: ' . $e->getMessage());
}

// ============================================
// CHARGEMENT DES PRÉSENCES
// ============================================

$presences = [];
try {
    $sql = "
        SELECT 
            p.*, 
            i.code_unique, 
            i.statut AS invitation_statut,
            inv.nom, 
            inv.prenom, 
            inv.email, 
            inv.telephone, 
            inv.nombre_personnes AS nb_places,
            e.nom AS evenement_nom, 
            e.date_evenement,
            u.nom AS agent_nom, 
            u.prenom AS agent_prenom
        FROM presences p
        JOIN invitations i ON p.id_invitation = i.id
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id
        $whereClause
        ORDER BY p.date_entree DESC, p.heure_entree DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $execParams = $params;
    $execParams[] = $limit;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $presences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Load presences: ' . $e->getMessage());
}

// ============================================
// ÉVÉNEMENTS POUR LE FILTRE
// ============================================

$evenements = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT e.id, e.nom 
            FROM evenements e 
            WHERE e.id IN (
                SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
            ) AND e.statut != 'ANNULE' 
            ORDER BY e.nom
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("
            SELECT id, nom 
            FROM evenements 
            WHERE statut != 'ANNULE' 
            ORDER BY nom
        ");
    }
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Load événements: ' . $e->getMessage());
}

// ============================================
// PAGINATION
// ============================================

$totalPages = max(1, (int)ceil($totalCount / $limit));
$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = 'index.php?' . http_build_query($queryParams);
$baseUrl = !empty($queryParams) ? $baseUrl . '&' : 'index.php?';

$success = $_GET['success'] ?? '';
$messages = [
    'enregistree' => 'Présence enregistrée avec succès ! ✅',
    'annulee'     => 'Présence annulée avec succès ! 🔄',
];

// ============================================
// STATS AUJOURD'HUI
// ============================================

$statsToday = 0;
try {
    $sqlToday = "
        SELECT COUNT(*) AS cnt 
        FROM presences p 
        JOIN invitations i ON p.id_invitation = i.id 
        JOIN evenements e ON i.id_evenement = e.id 
        WHERE DATE(p.date_entree) = CURDATE()
    ";
    if (!$isUserAdmin) {
        $sqlToday .= " AND e.id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)";
        $stmt = $pdo->prepare($sqlToday);
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query($sqlToday);
    }
    $statsToday = (int)($stmt->fetch()['cnt'] ?? 0);
} catch (PDOException $e) {
    error_log('Stats today: ' . $e->getMessage());
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
<title>Présences - <?php echo APP_NAME; ?></title>
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

    /* ========== BANNIÈRE INFO ========== */
    .user-info-banner {
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
    .user-info-banner i { color: #c17c60; font-size: 18px; flex-shrink: 0; }
    .user-info-banner strong { color: #c17c60; }

    /* ========== STATS ========== */
    .stats-row { margin-bottom: 25px; }
    .stat-mini-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.4);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-mini-card:hover { transform: translateY(-3px); box-shadow: 0 10px 35px rgba(193, 124, 96, 0.12); }
    .stat-mini-card .icon {
        width: 45px; height: 45px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        color: white;
    }
    .stat-mini-card .icon.primary { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .stat-mini-card .icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .stat-mini-card .icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-mini-card .icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-mini-card .stat-number { font-size: 22px; font-weight: 700; color: #1a1a1a; line-height: 1; }
    .stat-mini-card .stat-label { font-size: 12px; color: #9a8a7f; margin-top: 2px; }

    /* ========== TABLE CONTAINER ========== */
    .table-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    .table-container .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .table-container .table-header h5 { font-weight: 700; color: #1a1a1a; margin: 0; font-size: 17px; }
    .table-container .table-header h5 i { color: #c17c60; margin-right: 8px; }
    .table-container .table-header .btn-add {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        font-weight: 600;
        padding: 10px 22px;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        font-size: 14px;
    }
    .table-container .table-header .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }

    /* ========== FILTRES ========== */
    .filters-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        padding: 15px;
        background: rgba(251, 248, 245, 0.7);
        border-radius: 12px;
        align-items: center;
        border: 1px solid rgba(234, 227, 220, 0.5);
    }
    .filters-bar .filter-group { display: flex; align-items: center; gap: 8px; }
    .filters-bar .filter-group label {
        font-size: 11px; font-weight: 700; color: #9a8a7f;
        margin: 0; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .filters-bar .filter-group select,
    .filters-bar .filter-group input {
        padding: 8px 14px;
        border-radius: 10px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        color: #1a1a1a;
    }
    .filters-bar .filter-group select:focus,
    .filters-bar .filter-group input:focus {
        border-color: #c17c60;
        outline: none;
        box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
    }
    .filters-bar .btn-filter {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(193, 124, 96, 0.2);
    }
    .filters-bar .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(193, 124, 96, 0.3);
    }
    .filters-bar .btn-reset {
        background: rgba(255, 255, 255, 0.8);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filters-bar .btn-reset:hover { background: rgba(255, 255, 255, 0.95); color: #c17c60; }

    /* ========== BOUTONS ACTION ========== */
    .btn-action {
        width: 36px; height: 36px;
        border-radius: 10px;
        border: none;
        transition: all 0.3s ease;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-action:hover { transform: scale(1.1); }
    .btn-action.voir { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
    .btn-action.voir:hover { background: #3b82f6; color: white; }
    .btn-action.supprimer { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
    .btn-action.supprimer:hover { background: #ef4444; color: white; }

    /* ========== PRESENCE CARD ========== */
    .presence-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 16px;
        padding: 16px 18px;
        border: 1.5px solid rgba(234, 227, 220, 0.5);
        transition: all 0.3s ease;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }
    .presence-card:hover {
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
        border-color: rgba(193, 124, 96, 0.3);
        transform: translateY(-2px);
    }
    .presence-card.selected {
        border-color: #c17c60;
        background: rgba(193, 124, 96, 0.05);
    }
    .presence-card .presence-info { flex: 1; min-width: 200px; display: flex; align-items: center; gap: 14px; }
    .presence-card .presence-checkbox {
        width: 20px; height: 20px;
        accent-color: #c17c60;
        cursor: pointer;
        flex-shrink: 0;
    }
    .presence-card .guest-avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }
    .presence-card .guest-name { font-weight: 700; color: #1a1a1a; font-size: 14px; }
    .presence-card .guest-details { font-size: 11px; color: #9a8a7f; margin-top: 2px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .presence-card .guest-details code { font-size: 10px; background: rgba(193, 124, 96, 0.1); border: 1px solid rgba(193, 124, 96, 0.2); color: #c17c60; padding: 2px 6px; border-radius: 6px; }
    .presence-card .badge-count { background: rgba(251, 248, 245, 0.8); border: 1px solid rgba(234, 227, 220, 0.5); padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; color: #6a5a4a; }

    .presence-card .presence-event { text-align: center; min-width: 140px; }
    .presence-card .event-name { font-weight: 600; font-size: 12px; color: #1a1a1a; }
    .presence-card .event-date { font-size: 11px; color: #9a8a7f; }
    .presence-card .event-date i { color: #c17c60; margin-right: 4px; }

    .presence-card .presence-time { text-align: center; min-width: 80px; }
    .presence-card .time { font-weight: 700; font-size: 14px; color: #c17c60; }
    .presence-card .date { font-size: 11px; color: #9a8a7f; }

    .presence-card .presence-status { text-align: center; min-width: 100px; }
    .badge-complete { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; }
    .badge-complete.ok { background: rgba(16, 185, 129, 0.15); color: #065f46; }

    .presence-card .actions { display: flex; gap: 6px; flex-wrap: wrap; }

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
    .bulk-bar button { border: none; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .bulk-bar .btn-del { background: #dc2626; color: white; }
    .bulk-bar .btn-cancel { background: rgba(255, 255, 255, 0.15); color: white; }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: rgba(251, 248, 245, 0.5);
        border-radius: 16px;
        border: 2px dashed rgba(234, 227, 220, 0.6);
    }
    .empty-state i { font-size: 60px; color: #d4c5b2; display: block; margin-bottom: 15px; }
    .empty-state h5 { color: #6a5a4a; font-weight: 700; margin-bottom: 8px; }
    .empty-state p { color: #9a8a7f; font-size: 14px; margin-bottom: 15px; }

    /* ========== PAGINATION ========== */
    .pagination-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .pagination-custom .info { font-size: 13px; color: #9a8a7f; }
    .pagination-custom .pagination { margin: 0; gap: 4px; }
    .pagination-custom .pagination .page-link {
        border-radius: 10px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        color: #6a5a4a;
        padding: 6px 14px;
        font-size: 13px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.8);
    }
    .pagination-custom .pagination .page-link:hover {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border-color: #c17c60;
    }
    .pagination-custom .pagination .active .page-link {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border-color: #c17c60;
    }

    /* ========== ALERT ========== */
    .alert-success-custom {
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #065f46;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-success-custom i { font-size: 18px; color: #10b981; }

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
            overflow-y: auto; overflow-x: hidden;
            border-radius: 0 18px 18px 0;
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
        .table-container { padding: 18px; }
        .filters-bar { flex-direction: column; align-items: stretch; }
        .filters-bar .filter-group { flex-wrap: wrap; }
        .filters-bar .filter-group select,
        .filters-bar .filter-group input { flex: 1; min-width: 120px; }
        .stats-row .stat-mini-card { margin-bottom: 10px; }
        .presence-card { flex-direction: column; align-items: stretch; text-align: center; }
        .presence-card .presence-info { justify-content: center; }
        .presence-card .presence-event,
        .presence-card .presence-time,
        .presence-card .presence-status { min-width: auto; }
        .presence-card .actions { justify-content: center; }
        .pagination-custom { flex-direction: column; align-items: center; text-align: center; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .table-container { padding: 12px; border-radius: 12px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .table-header { flex-direction: column; align-items: stretch; text-align: center; }
        .table-header .btn-add { justify-content: center; }
        .presence-card { padding: 15px; }
        .presence-card .guest-name { font-size: 13px; }
        .stat-mini-card { padding: 12px 14px; gap: 10px; }
        .stat-mini-card .icon { width: 38px; height: 38px; font-size: 17px; }
        .stat-mini-card .stat-number { font-size: 18px; }
        .stat-mini-card .stat-label { font-size: 10px; }
        .filters-bar { padding: 12px; gap: 8px; }
        .filters-bar .filter-group label { font-size: 10px; }
        .btn-action { width: 32px; height: 32px; font-size: 12px; }
        .pagination-custom .pagination .page-link { padding: 4px 10px; font-size: 12px; }
        .user-info-banner { font-size: 12px; padding: 10px 12px; }
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
                <h4><i class="bi bi-person-check-fill"></i> Gestion des présences</h4>
                <small><i class="bi bi-list-ul"></i> Liste et gestion des présences</small>
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

            <?php if ($success && isset($messages[$success])): ?>
                <div class="alert-success-custom fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $messages[$success]; ?>
                </div>
            <?php endif; ?>

            <?php if (!$isUserAdmin): ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Vous voyez uniquement les présences des événements auxquels vous êtes <strong>associé</strong>.
                    </div>
                </div>
            <?php else: ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        En tant qu'<strong>administrateur</strong>, vous voyez toutes les présences de l'application.
                    </div>
                </div>
            <?php endif; ?>

            <div class="stats-row fade-in">
                <div class="row g-3">
                    <div class="col-xl-4 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon primary"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $totalCount; ?></div>
                                <div class="stat-label">Total entrées</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon green"><i class="bi bi-calendar-check-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $statsToday; ?></div>
                                <div class="stat-label">Aujourd'hui</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-12">
                        <div class="stat-mini-card">
                            <div class="icon blue"><i class="bi bi-calendar-event-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo count($evenements); ?></div>
                                <div class="stat-label">Événements</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container fade-in">

                <div class="table-header">
                    <h5><i class="bi bi-list-check"></i> Entrées • <?php echo $totalCount; ?></h5>
                    <?php if (hasPermission('presences.enregistrer')): ?>
                        <a href="controle.php" class="btn-add">
                            <i class="bi bi-qr-code-scan"></i> Scanner QR
                        </a>
                    <?php endif; ?>
                </div>

                <form method="GET" action="" class="filters-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, code..." style="min-width: 150px;">
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-calendar-event"></i> Événement</label>
                        <select name="evenement">
                            <option value="0">Tous</option>
                            <?php foreach ($evenements as $e): ?>
                                <option value="<?php echo $e['id']; ?>" <?php echo $filtre_evenement == $e['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-calendar"></i> Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filtre_date); ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-list-ol"></i> Par page</label>
                        <select name="limit" onchange="this.form.submit()">
                            <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                    <a href="index.php" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                </form>

                <?php if (!empty($presences)): ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:15px;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer;color:#6a5a4a;">
                            <input type="checkbox" id="checkAll" style="width:18px;height:18px;accent-color:#c17c60;"> 
                            Tout sélectionner
                        </label>
                        <span style="font-size:12px;color:#9a8a7f;" id="selectedInfo"></span>
                    </div>

                    <?php foreach ($presences as $p): 
                        $guestInitiales = strtoupper(substr($p['prenom'] ?? 'U', 0, 1) . substr($p['nom'] ?? 'N', 0, 1));
                    ?>
                        <div class="presence-card" data-id="<?php echo $p['id']; ?>">
                            <div class="presence-info">
                                <input type="checkbox" class="presence-checkbox" value="<?php echo $p['id']; ?>">
                                <div class="guest-avatar"><?php echo $guestInitiales ?: '?'; ?></div>
                                <div style="min-width:0;">
                                    <div class="guest-name"><?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?></div>
                                    <div class="guest-details">
                                        <code><?php echo htmlspecialchars($p['code_unique']); ?></code>
                                        <?php if (!empty($p['email'])): ?>
                                            <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($p['email']); ?></span>
                                        <?php endif; ?>
                                        <?php if (($p['nb_places'] ?? 1) > 1): ?>
                                            <span class="badge-count"><i class="bi bi-people-fill"></i> <?php echo $p['nb_places']; ?> pers.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="presence-event">
                                <div class="event-name"><?php echo htmlspecialchars($p['evenement_nom']); ?></div>
                                <div class="event-date">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo $p['date_evenement'] ? date('d/m/Y', strtotime($p['date_evenement'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="presence-time">
                                <div class="time">
                                    <?php 
                                    if (!empty($p['heure_entree'])) {
                                        echo date('H:i', strtotime($p['heure_entree']));
                                    } elseif (!empty($p['date_entree'])) {
                                        echo date('H:i', strtotime($p['date_entree']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </div>
                                <div class="date">
                                    <?php echo !empty($p['date_entree']) ? date('d/m/Y', strtotime($p['date_entree'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="presence-status">
                                <span class="badge-complete ok">
                                    <i class="bi bi-check-circle-fill"></i> Entré
                                </span>
                            </div>
                            <div class="actions">
                                <?php if (hasPermission('presences.annuler')): ?>
                                    <a href="annuler.php?id=<?php echo $p['id']; ?>" 
                                       class="btn-action supprimer" 
                                       title="Annuler" 
                                       onclick="return confirm('Annuler cette présence ?')">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-custom">
                            <div class="info">
                                Affichage de <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCount); ?> 
                                sur <?php echo $totalCount; ?> présence(s)
                                (Page <?php echo $page; ?> sur <?php echo $totalPages; ?>)
                            </div>
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php 
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    for ($i = $startPage; $i <= $endPage; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-person-x"></i>
                        <h5>Aucune présence</h5>
                        <?php if (isAdmin()): ?>
                            <p>Aucune entrée enregistrée dans l'application</p>
                        <?php else: ?>
                            <p>Aucune entrée pour vos événements</p>
                        <?php endif; ?>
                        <?php if (hasPermission('presences.enregistrer')): ?>
                            <a href="controle.php" class="btn-add" style="margin-top: 15px; display: inline-flex;">
                                <i class="bi bi-qr-code-scan"></i> Scanner un QR Code
                            </a>
                        <?php endif; ?>
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

<div class="bulk-bar" id="bulkBar">
    <span class="count" id="bulkCount">0 sélectionné(s)</span>
    <div style="display:flex;gap:8px;">
        <button class="btn-cancel" onclick="clearSelection()">Annuler</button>
        <button class="btn-del" onclick="bulkCancel()">Annuler présences</button>
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

// ========== AUTO-HIDE SUCCESS ==========
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert-success-custom');
    if (alert) {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    }
});

// ========== SÉLECTION GROUPÉE ==========
const checkAll        = document.getElementById('checkAll');
const checkboxes      = document.querySelectorAll('.presence-checkbox');
const bulkBar         = document.getElementById('bulkBar');
const bulkCount       = document.getElementById('bulkCount');
const selectedInfo    = document.getElementById('selectedInfo');

function updateBulk() {
    const selected = document.querySelectorAll('.presence-checkbox:checked');
    const count    = selected.length;

    document.querySelectorAll('.presence-card').forEach(card => {
        const cb = card.querySelector('.presence-checkbox');
        card.classList.toggle('selected', cb && cb.checked);
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

function bulkCancel() {
    const ids = Array.from(document.querySelectorAll('.presence-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;
    if (!confirm('Annuler ' + ids.length + ' présence(s) ?')) return;
    window.location.href = 'annuler_groupe.php?ids=' + ids.join(',');
}
</script>
</body>
</html>