<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('journal.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// PARAMÈTRES DE FILTRAGE ET PAGINATION
// ============================================

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

$filtre_action = $_GET['action'] ?? '';
$filtre_module = $_GET['module'] ?? '';
$filtre_utilisateur = $_GET['utilisateur'] ?? '';
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';
$search = $_GET['search'] ?? '';

// ============================================
// CONSTRUCTION DE LA REQUÊTE
// ============================================

$whereConditions = [];
$params = [];

if (!empty($filtre_action)) {
    $whereConditions[] = "ja.action = ?";
    $params[] = $filtre_action;
}

if (!empty($filtre_module)) {
    $whereConditions[] = "ja.module = ?";
    $params[] = $filtre_module;
}

if (!empty($filtre_utilisateur)) {
    $whereConditions[] = "ja.utilisateur_id = ?";
    $params[] = $filtre_utilisateur;
}

if (!empty($filtre_date_debut)) {
    $whereConditions[] = "DATE(ja.date_action) >= ?";
    $params[] = $filtre_date_debut;
}

if (!empty($filtre_date_fin)) {
    $whereConditions[] = "DATE(ja.date_action) <= ?";
    $params[] = $filtre_date_fin;
}

if (!empty($search)) {
    $whereConditions[] = "(ja.description LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.username LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================

// Nombre total d'enregistrements pour la pagination
$totalCount = 0;
try {
    $countSql = "
        SELECT COUNT(*) as total
        FROM journal_activites ja
        LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $totalCount = $result['total'] ?? 0;
} catch (PDOException $e) {
    // Ignorer
}

// Récupération des activités
$activites = [];
try {
    $sql = "
        SELECT 
            ja.id,
            ja.action,
            ja.module,
            ja.description,
            ja.adresse_ip,
            ja.user_agent,
            ja.date_action,
            u.id as utilisateur_id,
            u.nom,
            u.prenom,
            u.username
        FROM journal_activites ja
        LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
        $whereClause
        ORDER BY ja.date_action DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $activites = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des activités';
}

// ============================================
// RÉCUPÉRATION DES FILTRES (pour les dropdowns)
// ============================================

// Actions disponibles
$actions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT action FROM journal_activites ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignorer
}

// Modules disponibles
$modules = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT module FROM journal_activites WHERE module IS NOT NULL ORDER BY module");
    $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignorer
}

// Utilisateurs disponibles
$utilisateurs = [];
try {
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.nom, u.prenom, u.username 
        FROM journal_activites ja
        JOIN utilisateurs u ON ja.utilisateur_id = u.id
        ORDER BY u.nom
    ");
    $utilisateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignorer
}

// ============================================
// STATISTIQUES RAPIDES
// ============================================

$stats = [
    'total' => 0,
    'today' => 0,
    'week' => 0,
    'month' => 0
];

try {
    // Total
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM journal_activites");
    $stats['total'] = (int)($stmt->fetch()['count'] ?? 0);
    
    // Aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM journal_activites WHERE DATE(date_action) = CURDATE()");
    $stats['today'] = (int)($stmt->fetch()['count'] ?? 0);
    
    // Cette semaine
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM journal_activites WHERE YEARWEEK(date_action) = YEARWEEK(CURDATE())");
    $stats['week'] = (int)($stmt->fetch()['count'] ?? 0);
    
    // Ce mois
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM journal_activites WHERE MONTH(date_action) = MONTH(CURDATE()) AND YEAR(date_action) = YEAR(CURDATE())");
    $stats['month'] = (int)($stmt->fetch()['count'] ?? 0);
    
} catch (PDOException $e) {
    // Ignorer les erreurs
}

// ============================================
// PAGINATION
// ============================================

$totalPages = max(1, ceil($totalCount / $limit));

// Générer l'URL de base pour la pagination
$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = 'index.php?' . http_build_query($queryParams);
if (!empty($queryParams)) {
    $baseUrl .= '&';
} else {
    $baseUrl = 'index.php?';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'activités - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
        }

        /* ========== LAYOUT PRINCIPAL ========== */
        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ========== SIDEBAR ========== */
        .sidebar-wrapper {
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .sidebar-wrapper .sidebar {
            width: 260px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            padding: 20px 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: white;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            font-size: 22px;
            color: #1a1a2e;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-toggle-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(247, 151, 30, 0.2);
        }

        .sidebar-toggle-btn i {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            padding: 0;
            min-width: 0;
        }

        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f9fa; }
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            border-radius: 10px;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 15px 30px;
            border-bottom: 2px solid rgba(247, 151, 30, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-bar .page-title h4 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .top-bar .page-title h4 i { color: #f7971e; margin-right: 10px; }
        .top-bar .page-title small { color: #999; font-size: 13px; display: block; margin-top: 2px; }
        .top-bar .user-info { display: flex; align-items: center; gap: 20px; }
        .top-bar .user-info .user-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 18px;
            box-shadow: 0 5px 15px rgba(247, 151, 30, 0.3);
        }
        .top-bar .user-info .user-name { font-weight: 600; color: #1a1a2e; font-size: 14px; }
        .top-bar .user-info .user-name small { display: block; color: #aaa; font-weight: 400; font-size: 12px; }
        .top-bar .user-info .role-badge {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e; padding: 5px 15px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }

        .content-section { padding: 25px 30px; }

        /* Stats cards */
        .stats-row { margin-bottom: 25px; }
        .stat-mini-card {
            background: white;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        .stat-mini-card:hover { transform: translateY(-3px); box-shadow: 0 10px 35px rgba(0,0,0,0.08); }
        .stat-mini-card .icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-mini-card .icon.blue { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; }
        .stat-mini-card .icon.green { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .stat-mini-card .icon.orange { background: linear-gradient(135deg, #f7971e, #ffd200); color: white; }
        .stat-mini-card .icon.pink { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        .stat-mini-card .stat-number { font-size: 22px; font-weight: 700; color: #1a1a2e; line-height: 1; }
        .stat-mini-card .stat-label { font-size: 13px; color: #999; }

        /* Table container */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
        }

        /* Filtres */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .filters-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filters-bar .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #888;
            margin: 0;
            white-space: nowrap;
        }
        .filters-bar .filter-group select,
        .filters-bar .filter-group input {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 13px;
            background: white;
            transition: all 0.3s ease;
        }
        .filters-bar .filter-group select:focus,
        .filters-bar .filter-group input:focus {
            border-color: #f7971e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(247, 151, 30, 0.1);
        }
        .filters-bar .btn-filter {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border: none;
            padding: 6px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .filters-bar .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(247, 151, 30, 0.3);
        }
        .filters-bar .btn-reset {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 6px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .filters-bar .btn-reset:hover {
            background: #e9ecef;
            color: #333;
        }

        /* Table */
        .table-container table thead th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid rgba(247, 151, 30, 0.1);
            padding: 12px 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table-container table tbody td {
            padding: 10px 15px;
            vertical-align: middle;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-container table tbody tr:hover { background: #fafafa; }
        .table-container table tbody .no-data td { padding: 40px; text-align: center; color: #aaa; }

        .badge-action {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        .badge-action.LOGIN { background: #cce5ff; color: #004085; }
        .badge-action.LOGOUT { background: #e2e3e5; color: #383d41; }
        .badge-action.LOGIN_FAILED { background: #f8d7da; color: #721c24; }
        .badge-action.CREATE_USER { background: #d4edda; color: #155724; }
        .badge-action.UPDATE_USER { background: #fff3cd; color: #856404; }
        .badge-action.TOGGLE_USER { background: #f8d7da; color: #721c24; }
        .badge-action.CREATE_ROLE { background: #d4edda; color: #155724; }
        .badge-action.UPDATE_ROLE { background: #fff3cd; color: #856404; }
        .badge-action.TOGGLE_ROLE { background: #f8d7da; color: #721c24; }
        .badge-action.UPDATE_ROLE_PERMISSIONS { background: #cce5ff; color: #004085; }
        .badge-action.ACCESS_DENIED { background: #f8d7da; color: #721c24; }
        .badge-action.default { background: #e9ecef; color: #495057; }

        .badge-module {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
            white-space: nowrap;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-cell .avatar-mini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }
        .user-cell .user-name {
            font-weight: 600;
            color: #1a1a2e;
        }
        .user-cell .user-username {
            font-size: 11px;
            color: #aaa;
        }

        .ip-cell {
            font-family: monospace;
            font-size: 12px;
            color: #888;
        }
        .date-cell {
            font-size: 12px;
            color: #888;
            white-space: nowrap;
        }

        /* Pagination */
        .pagination-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .pagination-custom .info {
            font-size: 13px;
            color: #888;
        }
        .pagination-custom .pagination {
            margin: 0;
        }
        .pagination-custom .pagination .page-link {
            border-radius: 8px;
            border: 1px solid #eee;
            color: #555;
            padding: 6px 14px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .pagination-custom .pagination .page-link:hover {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border-color: #f7971e;
        }
        .pagination-custom .pagination .active .page-link {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border-color: #f7971e;
        }

        /* ========== ANIMATION D'ENTRÉE - CORRIGÉE ========== */
        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #ccc;
            font-size: 13px;
        }

        .app-footer i.bi-heart-fill {
            color: #ff6b6b;
        }

        /* ========== SUPPRESSION DES ANIMATIONS POUR LES UTILISATEURS QUI PRÉFÈRENT RÉDUIRE LES MOUVEMENTS ========== */
        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            html, body { overflow: visible; }
            
            .app-container {
                height: auto;
                min-height: 100vh;
            }

            .sidebar-wrapper {
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                flex-shrink: 0;
            }

            .sidebar-wrapper.open {
                transform: translateX(0);
            }

            .sidebar-wrapper .sidebar {
                height: 100vh;
                box-shadow: 5px 0 30px rgba(0, 0, 0, 0.15);
            }

            .sidebar-toggle-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .main-content {
                height: auto;
                min-height: 100vh;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px;
                padding-left: 75px;
            }

            .top-bar .user-info {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .top-bar .user-info .user-name {
                display: none;
            }

            .content-section { padding: 15px 20px; }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filters-bar .filter-group {
                flex-wrap: wrap;
            }
            .filters-bar .filter-group select,
            .filters-bar .filter-group input {
                flex: 1;
                min-width: 120px;
            }
            .filters-bar .filter-group input[type="date"] {
                min-width: 130px;
            }
            .filters-bar .btn-filter,
            .filters-bar .btn-reset {
                width: 100%;
                text-align: center;
                justify-content: center;
            }

            .table-container { 
                padding: 15px;
                overflow-x: auto;
            }

            .stats-row .stat-mini-card { 
                margin-bottom: 10px;
            }

            .pagination-custom { 
                flex-direction: column; 
                align-items: center;
            }

            .table-container table thead th {
                font-size: 11px;
                padding: 8px 10px;
            }
            .table-container table tbody td {
                font-size: 12px;
                padding: 8px 10px;
            }
            .user-cell .user-name {
                font-size: 13px;
            }
            .user-cell .user-username {
                font-size: 10px;
            }
            .ip-cell {
                font-size: 11px;
            }
            .date-cell {
                font-size: 11px;
            }
            .badge-action {
                font-size: 10px;
                padding: 2px 10px;
            }
            .badge-module {
                font-size: 9px;
                padding: 1px 8px;
            }
        }

        @media (max-width: 576px) {
            .top-bar { 
                padding: 12px 15px;
                padding-left: 65px;
            }
            .top-bar .page-title h4 { font-size: 18px; }
            .top-bar .page-title small { font-size: 11px; }
            .top-bar .user-info .role-badge { 
                font-size: 10px; 
                padding: 3px 10px;
            }
            .top-bar .user-info .user-avatar {
                width: 38px;
                height: 38px;
                font-size: 15px;
            }
            .content-section { padding: 10px 15px; }

            .sidebar-wrapper .sidebar {
                width: 280px;
            }

            .sidebar-toggle-btn {
                top: 12px;
                left: 12px;
                padding: 8px 12px;
                font-size: 18px;
            }

            .table-container { 
                padding: 10px;
                border-radius: 12px;
            }

            .stat-mini-card { 
                padding: 14px 15px;
                gap: 10px;
            }
            .stat-mini-card .icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }
            .stat-mini-card .stat-number { 
                font-size: 18px;
            }
            .stat-mini-card .stat-label {
                font-size: 11px;
            }

            .filters-bar { 
                padding: 12px;
                gap: 8px;
            }
            .filters-bar .filter-group label {
                font-size: 11px;
                min-width: 60px;
            }
            .filters-bar .filter-group select,
            .filters-bar .filter-group input {
                font-size: 12px;
                padding: 5px 10px;
            }
            .filters-bar .btn-filter,
            .filters-bar .btn-reset {
                font-size: 12px;
                padding: 5px 14px;
            }

            .table-container table thead th {
                font-size: 10px;
                padding: 6px 8px;
            }
            .table-container table tbody td {
                font-size: 11px;
                padding: 6px 8px;
            }
            .user-cell .avatar-mini {
                width: 22px;
                height: 22px;
                font-size: 10px;
            }
            .user-cell .user-name {
                font-size: 12px;
            }
            .user-cell .user-username {
                font-size: 9px;
            }
            .ip-cell {
                font-size: 10px;
            }
            .date-cell {
                font-size: 10px;
            }
            .badge-action {
                font-size: 9px;
                padding: 1px 8px;
            }
            .badge-module {
                font-size: 8px;
                padding: 1px 6px;
            }

            .pagination-custom .info {
                font-size: 12px;
            }
            .pagination-custom .pagination .page-link {
                font-size: 12px;
                padding: 4px 10px;
            }

            .app-footer {
                font-size: 11px;
                padding: 20px 0 15px;
            }
        }

        @media (max-width: 400px) {
            .stat-mini-card .stat-number {
                font-size: 16px;
            }
            .stat-mini-card .icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            .filters-bar .filter-group label {
                font-size: 10px;
                min-width: 50px;
            }
            .filters-bar .filter-group select,
            .filters-bar .filter-group input {
                font-size: 11px;
                padding: 4px 8px;
            }
            .table-container table thead th {
                font-size: 9px;
                padding: 4px 6px;
            }
            .table-container table tbody td {
                font-size: 10px;
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>

<!-- ========== BOUTON TOGGLE SIDEBAR (MOBILE) ========== -->
<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
</button>

<!-- ========== OVERLAY SIDEBAR (MOBILE) ========== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ========== CONTENEUR PRINCIPAL ========== -->
<div class="app-container">

    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar-wrapper" id="sidebarWrapper">
        <div class="sidebar">
            <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>
    </div>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <div class="main-content" id="mainContent">

        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-clock-history"></i> Journal d'activités</h4>
                <small><i class="bi bi-list-check"></i> Historique des actions</small>
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
                    $initiales = strtoupper(
                        substr($user['prenom'] ?? 'U', 0, 1) . 
                        substr($user['nom'] ?? 'N', 0, 1)
                    );
                    echo $initiales ?: 'U';
                    ?>
                </div>
            </div>
        </div>

        <!-- CONTENU -->
        <div class="content-section">

            <!-- Statistiques -->
            <div class="stats-row fade-in">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon blue"><i class="bi bi-database"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                                <div class="stat-label">Total activités</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon green"><i class="bi bi-calendar-day"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['today'] ?? 0; ?></div>
                                <div class="stat-label">Aujourd'hui</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon orange"><i class="bi bi-calendar-week"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['week'] ?? 0; ?></div>
                                <div class="stat-label">Cette semaine</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon pink"><i class="bi bi-calendar-month"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['month'] ?? 0; ?></div>
                                <div class="stat-label">Ce mois</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container fade-in">

                <!-- Filtres -->
                <form method="GET" action="" class="filters-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher..." style="min-width: 150px;">
                    </div>
                    <div class="filter-group">
                        <label>Action</label>
                        <select name="action">
                            <option value="">Toutes</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" 
                                        <?php echo $filtre_action == $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Module</label>
                        <select name="module">
                            <option value="">Tous</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module); ?>" 
                                        <?php echo $filtre_module == $module ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($module); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Utilisateur</label>
                        <select name="utilisateur">
                            <option value="">Tous</option>
                            <?php foreach ($utilisateurs as $u): ?>
                                <option value="<?php echo $u['id']; ?>" 
                                        <?php echo $filtre_utilisateur == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom'] . ' (@' . $u['username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Du</label>
                        <input type="date" name="date_debut" value="<?php echo $filtre_date_debut; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Au</label>
                        <input type="date" name="date_fin" value="<?php echo $filtre_date_fin; ?>">
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-filter"></i> Filtrer
                    </button>
                    <a href="index.php" class="btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </a>
                </form>

                <!-- Tableau -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="min-width: 120px;">Utilisateur</th>
                                <th style="min-width: 110px;">Action</th>
                                <th>Description</th>
                                <th style="min-width: 90px;">Module</th>
                                <th style="min-width: 100px;">IP</th>
                                <th style="min-width: 130px;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activites)): ?>
                                <?php foreach ($activites as $activite): ?>
                                    <tr>
                                        <td><?php echo $activite['id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar-mini">
                                                    <?php 
                                                    $init = strtoupper(
                                                        substr($activite['prenom'] ?? '', 0, 1) . 
                                                        substr($activite['nom'] ?? '', 0, 1)
                                                    );
                                                    echo $init ?: '?';
                                                    ?>
                                                </div>
                                                <div>
                                                    <div class="user-name">
                                                        <?php echo htmlspecialchars(($activite['prenom'] ?? '') . ' ' . ($activite['nom'] ?? '')); ?>
                                                    </div>
                                                    <?php if ($activite['username']): ?>
                                                        <div class="user-username">@<?php echo htmlspecialchars($activite['username']); ?></div>
                                                    <?php else: ?>
                                                        <div class="user-username">Système</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-action <?php echo htmlspecialchars($activite['action']); ?> default">
                                                <?php echo htmlspecialchars($activite['action']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 12px; color: #555;">
                                            <?php echo htmlspecialchars($activite['description']); ?>
                                        </td>
                                        <td>
                                            <?php if ($activite['module']): ?>
                                                <span class="badge-module">
                                                    <?php echo htmlspecialchars($activite['module']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 11px;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="ip-cell"><?php echo htmlspecialchars($activite['adresse_ip'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <?php echo date('d/m/Y H:i', strtotime($activite['date_action'])); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="no-data">
                                    <td colspan="7">
                                        <i class="bi bi-inbox" style="font-size: 30px; display: block; margin-bottom: 10px;"></i>
                                        Aucune activité enregistrée
                                        <?php if (!empty($search) || !empty($filtre_action) || !empty($filtre_module) || !empty($filtre_utilisateur) || !empty($filtre_date_debut) || !empty($filtre_date_fin)): ?>
                                            <br><small>avec les filtres actuels</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-custom">
                        <div class="info">
                            Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> entrées
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
            </div>

            <!-- Footer -->
            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ========== TOGGLE SIDEBAR MOBILE ==========
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebarWrapper');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebarWrapper.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
        const icon = sidebarToggle.querySelector('i');
        if (sidebarWrapper.classList.contains('open')) {
            icon.className = 'bi bi-x-lg';
        } else {
            icon.className = 'bi bi-list';
        }
    }

    function closeSidebar() {
        sidebarWrapper.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        const icon = sidebarToggle.querySelector('i');
        icon.className = 'bi bi-list';
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });

    // ========== AUTO-SUBMIT FILTERS ON CHANGE ==========
    document.querySelectorAll('.filters-bar select, .filters-bar input[type="date"]').forEach(el => {
        el.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
</script>
</body>
</html>