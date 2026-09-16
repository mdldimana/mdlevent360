<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// ⭐ Fix InfinityFree - définir BASE_PATH et APP_NAME si non définis
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $projectFolder);
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Gestion Invitations');
}

// Vérifier les permissions
requirePermission('evenements.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// FILTRES ET PAGINATION
// ============================================

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

$filtre_statut = $_GET['statut'] ?? '';
$filtre_recherche = $_GET['search'] ?? '';

// ============================================
// CONSTRUCTION DE LA REQUÊTE AVEC FILTRAGE PAR UTILISATEUR
// ============================================

$whereConditions = [];
$params = [];

// ⭐ FILTRAGE PAR UTILISATEUR (SUPER_ADMIN et ADMIN voient tout)
if (!isAdmin()) {
    $userId = (int)getCurrentUserId();
    $whereConditions[] = "e.id IN (
        SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
    )";
    $params[] = $userId;
}

if (!empty($filtre_statut)) {
    $whereConditions[] = "e.statut = ?";
    $params[] = $filtre_statut;
}

if (!empty($filtre_recherche)) {
    $whereConditions[] = "(e.nom LIKE ? OR e.description LIKE ? OR e.lieu LIKE ?)";
    $searchParam = '%' . $filtre_recherche . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================

$totalCount = 0;
try {
    $countSql = "SELECT COUNT(*) as total FROM evenements e $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int)($stmt->fetch()['total'] ?? 0);
} catch (PDOException $e) {
    error_log('Erreur count événements: ' . $e->getMessage());
}

$evenements = [];
try {
    $sql = "
        SELECT 
            e.*,
            COUNT(DISTINCT i.id) as nb_invitations,
            COUNT(DISTINCT p.id) as nb_presences,
            u.nom as createur_nom,
            u.prenom as createur_prenom,
            (SELECT COUNT(*) FROM evenements_utilisateurs eu2 WHERE eu2.id_evenement = e.id) as nb_utilisateurs_associes
        FROM evenements e
        LEFT JOIN invitations i ON e.id = i.id_evenement
        LEFT JOIN presences p ON i.id = p.id_invitation
        LEFT JOIN utilisateurs u ON e.created_by = u.id
        $whereClause
        GROUP BY e.id
        ORDER BY e.date_evenement DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $evenements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur liste événements: ' . $e->getMessage());
    $error = 'Erreur lors du chargement des événements';
}

$statuts = ['BROUILLON', 'ACTIF', 'TERMINE', 'ANNULE'];
$totalPages = max(1, (int)ceil($totalCount / $limit));

$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = 'index.php?' . http_build_query($queryParams);
if (!empty($queryParams)) {
    $baseUrl .= '&';
} else {
    $baseUrl = 'index.php?';
}

$success = $_GET['success'] ?? '';
$message = [
    'ajoute' => 'Événement créé avec succès ! 🎉',
    'modifie' => 'Événement modifié avec succès ! ✅',
    'supprime' => 'Événement supprimé avec succès ! 🗑️',
    'annule' => 'Événement annulé ! ❌'
];

$statutConfig = [
    'BROUILLON' => ['class' => 'brouillon', 'icon' => 'bi-pencil'],
    'ACTIF' => ['class' => 'actif', 'icon' => 'bi-check-circle-fill'],
    'TERMINE' => ['class' => 'termine', 'icon' => 'bi-clock-history'],
    'ANNULE' => ['class' => 'annule', 'icon' => 'bi-x-circle-fill']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow-x: hidden; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

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
        .stat-mini-card .icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }
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

        /* ========== BADGES STATUT ========== */
        .badge-statut {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-statut.brouillon { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-statut.actif { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-statut.termine { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-statut.annule { background: rgba(239, 68, 68, 0.15); color: #991b1b; }

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
        .btn-action.modifier { background: rgba(193, 124, 96, 0.12); color: #c17c60; }
        .btn-action.modifier:hover { background: #c17c60; color: white; }
        .btn-action.supprimer { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
        .btn-action.supprimer:hover { background: #ef4444; color: white; }

        /* ========== EVENT CARD ========== */
        .event-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 18px 20px;
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            transition: all 0.3s ease;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .event-card:hover {
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .event-card .event-info { flex: 1; min-width: 200px; }
        .event-card .event-title { font-weight: 700; color: #1a1a1a; font-size: 15px; margin-bottom: 4px; }
        .event-card .event-meta { font-size: 12px; color: #9a8a7f; display: flex; flex-direction: column; gap: 3px; }
        .event-card .event-meta span { display: flex; align-items: center; gap: 6px; }
        .event-card .event-meta i { color: #c17c60; width: 14px; }
        .event-card .event-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .event-card .event-stats .stat {
            font-size: 12px;
            color: #6a5a4a;
            background: rgba(251, 248, 245, 0.8);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid rgba(234, 227, 220, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .event-card .event-stats .stat i { color: #c17c60; }
        .event-card .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        /* ========== BADGE UTILISATEURS ========== */
        .badge-users {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.15));
            color: #c17c60;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(193, 124, 96, 0.2);
            margin-top: 6px;
        }

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
            .event-card { flex-direction: column; align-items: stretch; text-align: center; }
            .event-card .event-meta { align-items: center; }
            .event-card .event-stats { justify-content: center; }
            .event-card .actions { justify-content: center; }
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
            .event-card { padding: 15px; }
            .event-card .event-title { font-size: 14px; }
            .stat-mini-card { padding: 12px 14px; gap: 10px; }
            .stat-mini-card .icon { width: 38px; height: 38px; font-size: 17px; }
            .stat-mini-card .stat-number { font-size: 18px; }
            .stat-mini-card .stat-label { font-size: 10px; }
            .filters-bar { padding: 12px; gap: 8px; }
            .filters-bar .filter-group label { font-size: 10px; }
            .btn-action { width: 32px; height: 32px; font-size: 12px; }
            .badge-statut { font-size: 9px; padding: 3px 10px; }
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
                <h4><i class="bi bi-calendar-event-fill"></i> Gestion des événements</h4>
                <small><i class="bi bi-list-ul"></i> Liste et gestion des événements</small>
            </div>
            <div class="user-info">
                <span class="role-badge">
                    <i class="bi bi-shield-check"></i>
                    <?php 
                    $roles_user = $user['roles'] ?? [];
                    echo is_array($roles_user) ? implode(', ', $roles_user) : 'Aucun rôle';
                    ?>
                </span>
                <div>
                    <div class="user-name">
                        <?php echo htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')); ?>
                        <small>@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small>
                    </div>
                </div>
                <div class="user-avatar">
                    <?php 
                    $userInitiales = strtoupper(
                        substr($user['prenom'] ?? 'U', 0, 1) . 
                        substr($user['nom'] ?? 'N', 0, 1)
                    );
                    echo $userInitiales ?: 'U';
                    ?>
                </div>
            </div>
        </div>

        <div class="content-section">

            <?php if ($success && isset($message[$success])): ?>
                <div class="alert-success-custom fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $message[$success]; ?>
                </div>
            <?php endif; ?>

            <!-- Bannière info selon le rôle -->
            <?php if (!isAdmin()): ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Vous voyez uniquement les événements auxquels vous êtes <strong>associé</strong>.
                        Contactez un administrateur pour être ajouté à d'autres événements.
                    </div>
                </div>
            <?php else: ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        En tant qu'<strong>administrateur</strong>, vous voyez tous les événements de l'application.
                    </div>
                </div>
            <?php endif; ?>

            <?php 
            $stats_events = ['total' => 0, 'brouillon' => 0, 'actif' => 0, 'termine' => 0, 'annule' => 0];
            foreach ($evenements as $e) {
                $stats_events['total']++;
                $statut = strtolower($e['statut']);
                if (isset($stats_events[$statut])) $stats_events[$statut]++;
            }
            $stats_events['total'] = $totalCount;
            ?>

            <!-- STATISTIQUES -->
            <div class="stats-row fade-in">
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon primary"><i class="bi bi-calendar-event-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats_events['total']; ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon orange"><i class="bi bi-pencil-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats_events['brouillon']; ?></div>
                                <div class="stat-label">Brouillons</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon green"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats_events['actif']; ?></div>
                                <div class="stat-label">Actifs</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-6">
                        <div class="stat-mini-card">
                            <div class="icon blue"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats_events['termine']; ?></div>
                                <div class="stat-label">Terminés</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="table-container fade-in">

                <div class="table-header">
                    <h5><i class="bi bi-list-ul"></i> Liste des événements</h5>
                    <?php if (hasPermission('evenements.creer') || hasPermission('evenements.ajouter')): ?>
                        <a href="creer.php" class="btn-add">
                            <i class="bi bi-plus-circle-fill"></i> Nouvel événement
                        </a>
                    <?php endif; ?>
                </div>

                <form method="GET" action="" class="filters-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, lieu..." style="min-width: 150px;">
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-circle"></i> Statut</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <?php foreach ($statuts as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $filtre_statut == $s ? 'selected' : ''; ?>>
                                    <?php echo $s; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                    <a href="index.php" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                </form>

                <?php if (!empty($evenements)): ?>
                    <?php foreach ($evenements as $event): 
                        $statutClass = strtolower($event['statut']);
                        $config = $statutConfig[$event['statut']] ?? $statutConfig['BROUILLON'];
                    ?>
                        <div class="event-card">
                            <div class="event-info">
                                <div class="event-title">
                                    <?php echo htmlspecialchars($event['nom']); ?>
                                </div>
                                <div class="event-meta">
                                    <span>
                                        <i class="bi bi-calendar3"></i>
                                        <?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?>
                                        <?php if ($event['heure_evenement']): ?>
                                            à <?php echo date('H:i', strtotime($event['heure_evenement'])); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span>
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?php echo htmlspecialchars($event['lieu'] ?? 'Lieu non défini'); ?>
                                    </span>
                                </div>
                                <?php if (isAdmin() && $event['nb_utilisateurs_associes'] > 0): ?>
                                    <div class="badge-users">
                                        <i class="bi bi-people-fill"></i>
                                        <?php echo $event['nb_utilisateurs_associes']; ?> utilisateur(s) associé(s)
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="event-stats">
                                <div class="stat">
                                    <i class="bi bi-envelope-fill"></i>
                                    <?php echo $event['nb_invitations'] ?? 0; ?> invitations
                                </div>
                                <div class="stat">
                                    <i class="bi bi-person-check-fill"></i>
                                    <?php echo $event['nb_presences'] ?? 0; ?> présents
                                </div>
                            </div>
                            <div>
                                <span class="badge-statut <?php echo $statutClass; ?>">
                                    <i class="bi <?php echo $config['icon']; ?>"></i>
                                    <?php echo $event['statut']; ?>
                                </span>
                            </div>
                            <div class="actions">
                                <a href="voir.php?id=<?php echo $event['id']; ?>" 
                                   class="btn-action voir" title="Voir">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <?php if (hasPermission('evenements.modifier')): ?>
                                    <a href="modifier.php?id=<?php echo $event['id']; ?>" 
                                       class="btn-action modifier" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (hasPermission('evenements.supprimer')): ?>
                                    <a href="supprimer.php?id=<?php echo $event['id']; ?>" 
                                       class="btn-action supprimer" title="Supprimer"
                                       onclick="return confirm('Voulez-vous vraiment supprimer cet événement ?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-custom">
                            <div class="info">
                                Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> événements
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
                        <i class="bi bi-calendar-plus"></i>
                        <h5>Aucun événement</h5>
                        <?php if (isAdmin()): ?>
                            <p>Aucun événement dans l'application</p>
                            <?php if (hasPermission('evenements.creer') || hasPermission('evenements.ajouter')): ?>
                                <a href="creer.php" class="btn-add" style="margin-top: 15px; display: inline-flex;">
                                    <i class="bi bi-plus-circle-fill"></i> Créer un événement
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Vous n'êtes associé à aucun événement pour le moment</p>
                            <p style="font-size: 12px; margin-top: 8px; color: #b8a99c;">
                                <i class="bi bi-info-circle"></i>
                                Contactez un administrateur pour être ajouté à un événement
                            </p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebarToggle = document.getElementById('sidebarToggle');
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
</script>
</body>
</html>