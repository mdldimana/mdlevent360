<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('preferences.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// FILTRES
// ============================================

$filtre_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$filtre_boisson = isset($_GET['boisson']) ? (int)$_GET['boisson'] : 0;
$filtre_recherche = $_GET['search'] ?? '';

// ============================================
// CONSTRUCTION DE LA REQUÊTE
// ============================================

$whereConditions = [];
$params = [];

if ($filtre_evenement > 0) {
    $whereConditions[] = "e.id = ?";
    $params[] = $filtre_evenement;
}

if ($filtre_boisson > 0) {
    $whereConditions[] = "pi.id_boisson = ?";
    $params[] = $filtre_boisson;
}

if (!empty($filtre_recherche)) {
    $whereConditions[] = "(inv.nom LIKE ? OR inv.prenom LIKE ? OR inv.email LIKE ?)";
    $searchParam = '%' . $filtre_recherche . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// RÉCUPÉRATION DES PRÉFÉRENCES
// ============================================

$preferences = [];
$totalCount = 0;

try {
    $sql = "
        SELECT 
            pi.id,
            pi.quantite,
            pi.created_at as pref_date,
            inv.nom,
            inv.prenom,
            inv.email,
            inv.telephone,
            b.nom as boisson_nom,
            b.type as boisson_type,
            e.nom as evenement_nom,
            e.date_evenement,
            i.code_unique,
            i.statut as invitation_statut
        FROM preferences_invitation pi
        JOIN invitations i ON pi.id_invitation = i.id
        JOIN invites inv ON i.id_invite = inv.id
        JOIN boissons b ON pi.id_boisson = b.id
        JOIN evenements e ON i.id_evenement = e.id
        $whereClause
        ORDER BY pi.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $preferences = $stmt->fetchAll();
    $totalCount = count($preferences);
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des préférences';
}

// ============================================
// STATISTIQUES
// ============================================

$stats = [
    'total' => $totalCount,
    'boissons_populaires' => [],
    'evenements_actifs' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            b.id,
            b.nom,
            COUNT(pi.id) as total_choix,
            SUM(pi.quantite) as total_quantite
        FROM boissons b
        JOIN preferences_invitation pi ON b.id = pi.id_boisson
        GROUP BY b.id
        ORDER BY total_choix DESC
        LIMIT 10
    ");
    $stats['boissons_populaires'] = $stmt->fetchAll();
} catch (PDOException $e) {
    $stats['boissons_populaires'] = [];
}

try {
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT i.id_evenement) as total
        FROM preferences_invitation pi
        JOIN invitations i ON pi.id_invitation = i.id
    ");
    $result = $stmt->fetch();
    $stats['evenements_actifs'] = (int)($result['total'] ?? 0);
} catch (PDOException $e) {
    $stats['evenements_actifs'] = 0;
}

$topBoisson = !empty($stats['boissons_populaires']) ? $stats['boissons_populaires'][0] : null;

// ============================================
// RÉCUPÉRATION DES FILTRES
// ============================================

$evenements = [];
try {
    $stmt = $pdo->query("
        SELECT DISTINCT e.id, e.nom, e.date_evenement
        FROM evenements e
        JOIN invitations i ON e.id = i.id_evenement
        JOIN preferences_invitation pi ON i.id = pi.id_invitation
        ORDER BY e.nom
    ");
    $evenements = $stmt->fetchAll();
} catch (PDOException $e) {
    $evenements = [];
}

$boissons = [];
try {
    $stmt = $pdo->query("
        SELECT DISTINCT b.id, b.nom
        FROM boissons b
        JOIN preferences_invitation pi ON b.id = pi.id_boisson
        ORDER BY b.nom
    ");
    $boissons = $stmt->fetchAll();
} catch (PDOException $e) {
    $boissons = [];
}

$typeLabels = [
    'SANS_ALCOOL' => 'Sans alcool 🧃',
    'ALCOOL' => 'Alcool 🍷',
    'CHAUD' => 'Chaud ☕',
    'AUTRE' => 'Autre 🍹'
];

$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE' => 'Confirmée',
    'REFUSEE' => 'Refusée',
    'PRESENTE' => 'Présente',
    'ANNULEE' => 'Annulée'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar-wrapper {
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
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
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 5px 20px rgba(193, 124, 96, 0.35);
            font-size: 22px;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-toggle-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(193, 124, 96, 0.45);
        }

        .main-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            padding: 0;
            min-width: 0;
        }

        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 10px;
        }

        /* ========== TOP BAR ========== */
        .top-bar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 15px 30px;
            border-bottom: 1px solid rgba(193, 124, 96, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar .page-title h4 { font-weight: 700; color: #1a1a1a; margin: 0; font-size: 20px; }
        .top-bar .page-title h4 i { color: #c17c60; margin-right: 10px; }
        .top-bar .page-title small { color: #9a8a7f; font-size: 13px; display: block; margin-top: 2px; }
        .top-bar .user-info { display: flex; align-items: center; gap: 20px; }
        .top-bar .user-info .user-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 18px;
            box-shadow: 0 5px 15px rgba(193, 124, 96, 0.3);
        }
        .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 14px; }
        .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 12px; }
        .top-bar .user-info .role-badge {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; padding: 5px 15px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }

        .content-section { padding: 25px 30px; }

        /* ========== STATS ========== */
        .stats-row { margin-bottom: 25px; }
        .stat-mini-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .stat-mini-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 35px rgba(193, 124, 96, 0.12); 
        }
        .stat-mini-card .icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-mini-card .icon.blue { background: linear-gradient(135deg, #c17c60, #d4a574); color: white; }
        .stat-mini-card .icon.green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .stat-mini-card .icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
        .stat-mini-card .icon.purple { background: linear-gradient(135deg, #a18cd1, #c9b6e4); color: white; }
        .stat-mini-card .stat-number { font-size: 20px; font-weight: 700; color: #1a1a1a; line-height: 1.2; }
        .stat-mini-card .stat-label { font-size: 12px; color: #9a8a7f; }

        /* ========== TABLE ========== */
        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
        .filters-bar .filter-group label { font-size: 12px; font-weight: 600; color: #9a8a7f; margin: 0; white-space: nowrap; }
        .filters-bar .filter-group select,
        .filters-bar .filter-group input {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-size: 13px;
            background: rgba(255, 255, 255, 0.8);
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
        .filters-bar .btn-reset:hover { 
            background: rgba(255, 255, 255, 0.95); 
            color: #c17c60; 
        }

        /* ========== PREFERENCE ITEM ========== */
        .preference-item {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 14px 18px;
            border: 1px solid rgba(234, 227, 220, 0.5);
            transition: all 0.3s ease;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .preference-item:hover { 
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1); 
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .preference-item .guest { flex: 1; min-width: 150px; }
        .preference-item .guest .name { font-weight: 700; color: #1a1a1a; font-size: 14px; }
        .preference-item .guest .details { 
            font-size: 12px; 
            color: #9a8a7f; 
            margin-top: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .preference-item .guest .details i { color: #c17c60; }
        .preference-item .guest .details code {
            font-size: 10px; 
            color: #c17c60; 
            background: rgba(193, 124, 96, 0.1);
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 600;
        }
        .preference-item .drink { text-align: center; min-width: 110px; }
        .preference-item .drink .drink-name { font-weight: 600; color: #c17c60; font-size: 14px; }
        .preference-item .drink .drink-type { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
        .preference-item .event { text-align: center; min-width: 130px; }
        .preference-item .event .event-name { font-weight: 600; font-size: 13px; color: #1a1a1a; }
        .preference-item .event .event-date { font-size: 11px; color: #9a8a7f; margin-top: 2px; }
        .preference-item .event .event-date i { color: #c17c60; }
        .preference-item .quantity { font-size: 13px; color: #9a8a7f; min-width: 60px; text-align: center; }
        .preference-item .quantity .badge {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        /* ========== BADGES ========== */
        .badge-type {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-type.sans_alcool { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-type.alcool { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .badge-type.chaud { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-type.autre { background: rgba(107, 114, 128, 0.15); color: #374151; }

        .badge-statut {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        .badge-statut.confirmee { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-statut.en_attente { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-statut.refusee { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .badge-statut.presente { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-statut.annulee { background: rgba(107, 114, 128, 0.15); color: #374151; }

        /* ========== EMPTY STATE ========== */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 60px; color: #d4c5b2; }
        .empty-state h5 { color: #6a5a4a; margin-top: 15px; font-weight: 600; }
        .empty-state p { color: #b8a99c; }

        /* ========== CHART ========== */
        .chart-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            height: 100%;
        }
        .chart-container h6 {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
        }
        .chart-container h6 i { color: #c17c60; margin-right: 8px; }
        .chart-container canvas { max-height: 250px; }

        /* ========== ANIMATIONS ========== */
        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }
        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            html, body { overflow: visible; }
            .app-container { height: auto; min-height: 100vh; }

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
            .sidebar-wrapper.open { transform: translateX(0); }

            .sidebar-toggle-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .main-content { height: auto; min-height: 100vh; }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px;
                padding-left: 75px;
            }
            .top-bar .user-info { width: 100%; justify-content: space-between; flex-wrap: wrap; }
            .top-bar .user-info .user-name { display: none; }

            .content-section { padding: 15px 20px; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filters-bar .filter-group { flex-wrap: wrap; }
            .filters-bar .filter-group select,
            .filters-bar .filter-group input { flex: 1; min-width: 120px; }

            .table-container { padding: 15px; overflow-x: auto; }

            .preference-item { 
                flex-direction: column; 
                align-items: stretch; 
                text-align: center;
                gap: 10px;
            }
            .preference-item .guest { text-align: center; }
            .preference-item .guest .details {
                justify-content: center;
            }

            .stats-row .stat-mini-card { margin-bottom: 10px; }

            .row.g-4 { flex-direction: column; }
            .row.g-4 .col-lg-5,
            .row.g-4 .col-lg-7 { width: 100%; }

            .chart-container canvas { max-height: 200px; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; padding-left: 65px; }
            .top-bar .page-title h4 { font-size: 18px; }
            .top-bar .page-title small { font-size: 11px; }
            .top-bar .user-info .role-badge { font-size: 10px; padding: 3px 10px; }
            .top-bar .user-info .user-avatar { width: 38px; height: 38px; font-size: 15px; }
            .content-section { padding: 10px 15px; }
            .table-container { padding: 10px; border-radius: 12px; }
            .sidebar-toggle-btn { top: 12px; left: 12px; padding: 8px 12px; font-size: 18px; }

            .preference-item { padding: 12px; }
            .preference-item .guest .details { font-size: 11px; flex-direction: column; align-items: center; gap: 4px; }
            .preference-item .drink .drink-name { font-size: 13px; }
            .preference-item .event .event-name { font-size: 12px; }

            .stat-mini-card { padding: 14px 15px; gap: 10px; }
            .stat-mini-card .icon { width: 38px; height: 38px; font-size: 16px; }
            .stat-mini-card .stat-number { font-size: 16px; }
            .stat-mini-card .stat-label { font-size: 11px; }

            .filters-bar { padding: 12px; gap: 8px; }
            .filters-bar .filter-group label { font-size: 11px; min-width: 60px; }

            .chart-container { padding: 15px; }
            .chart-container canvas { max-height: 150px; }

            .empty-state i { font-size: 40px; }
            .empty-state h5 { font-size: 16px; }
            .empty-state p { font-size: 13px; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-container">

    <div class="sidebar-wrapper" id="sidebarWrapper">
        <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-cup"></i> Préférences des invités</h4>
                <small><i class="bi bi-list-ul"></i> <?php echo $stats['total']; ?> choix de boissons enregistrés</small>
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

        <div class="content-section">

            <div class="stats-row fade-in">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon blue"><i class="bi bi-cup"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Total des choix</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon orange"><i class="bi bi-calendar-event"></i></div>
                            <div>
                                <div class="stat-number"><?php echo $stats['evenements_actifs']; ?></div>
                                <div class="stat-label">Événements</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon green"><i class="bi bi-award"></i></div>
                            <div>
                                <div class="stat-number">
                                    <?php echo $topBoisson ? htmlspecialchars($topBoisson['nom']) : '-'; ?>
                                </div>
                                <div class="stat-label">Boisson la plus choisie</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-mini-card">
                            <div class="icon purple"><i class="bi bi-people"></i></div>
                            <div>
                                <div class="stat-number">
                                    <?php echo $topBoisson ? $topBoisson['total_choix'] : 0; ?>
                                </div>
                                <div class="stat-label">Choix pour la plus populaire</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4 fade-in">
                <?php if (!empty($stats['boissons_populaires'])): ?>
                    <div class="col-lg-5">
                        <div class="chart-container">
                            <h6><i class="bi bi-bar-chart"></i> Boissons les plus populaires</h6>
                            <canvas id="boissonsChart"></canvas>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-lg-<?php echo !empty($stats['boissons_populaires']) ? '7' : '12'; ?>">
                    <div class="table-container">

                        <div class="table-header">
                            <h5><i class="bi bi-list-ul"></i> Détail des préférences</h5>
                        </div>

                        <form method="GET" action="" class="filters-bar">
                            <div class="filter-group">
                                <label><i class="bi bi-search"></i> Recherche</label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, email..." style="min-width: 130px;">
                            </div>
                            <div class="filter-group">
                                <label>Événement</label>
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
                                <label>Boisson</label>
                                <select name="boisson">
                                    <option value="0">Toutes</option>
                                    <?php foreach ($boissons as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo $filtre_boisson == $b['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-filter"><i class="bi bi-filter"></i> Filtrer</button>
                            <a href="preferences.php" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                        </form>

                        <?php if (!empty($preferences)): ?>
                            <?php foreach ($preferences as $p): ?>
                                <div class="preference-item">
                                    <div class="guest">
                                        <div class="name">
                                            <?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?>
                                        </div>
                                        <div class="details">
                                            <?php if ($p['email']): ?>
                                                <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($p['email']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($p['telephone']): ?>
                                                <span><i class="bi bi-phone"></i> <?php echo htmlspecialchars($p['telephone']); ?></span>
                                            <?php endif; ?>
                                            <code><?php echo htmlspecialchars($p['code_unique']); ?></code>
                                            <span class="badge-statut <?php echo strtolower(str_replace('_', '_', $p['invitation_statut'])); ?>">
                                                <?php echo $statutLabels[$p['invitation_statut']] ?? $p['invitation_statut']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="drink">
                                        <div class="drink-name">
                                            <?php echo htmlspecialchars($p['boisson_nom']); ?>
                                        </div>
                                        <div class="drink-type">
                                            <span class="badge-type <?php echo strtolower(str_replace('_', '', $p['boisson_type'])); ?>">
                                                <?php echo $typeLabels[$p['boisson_type']] ?? $p['boisson_type']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="event">
                                        <div class="event-name"><?php echo htmlspecialchars($p['evenement_nom']); ?></div>
                                        <div class="event-date">
                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($p['date_evenement'])); ?>
                                        </div>
                                    </div>
                                    <div class="quantity">
                                        <span class="badge">x<?php echo $p['quantite']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-cup"></i>
                                <h5>Aucune préférence enregistrée</h5>
                                <p>Les invités n'ont pas encore choisi leurs boissons.</p>
                            </div>
                        <?php endif; ?>
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

    <?php if (!empty($stats['boissons_populaires'])): ?>
    const ctx = document.getElementById('boissonsChart').getContext('2d');
    const labels = <?php echo json_encode(array_column($stats['boissons_populaires'], 'nom')); ?>;
    const data = <?php echo json_encode(array_column($stats['boissons_populaires'], 'total_choix')); ?>;
    const quantites = <?php echo json_encode(array_column($stats['boissons_populaires'], 'total_quantite')); ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(193, 124, 96, 0.85)');
    gradient.addColorStop(1, 'rgba(212, 165, 116, 0.3)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre de choix',
                data: data,
                backgroundColor: gradient,
                borderColor: '#c17c60',
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 28,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 26, 0.95)',
                    titleColor: 'white',
                    bodyColor: 'rgba(255,255,255,0.85)',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        afterBody: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            return 'Total commandé: ' + quantites[index] + ' unités';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        stepSize: 1,
                        color: '#9a8a7f',
                        font: { family: 'Inter', size: 11 }
                    },
                    grid: { color: 'rgba(234, 227, 220, 0.5)' }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6a5a4a',
                        font: { family: 'Inter', size: 11 }
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>
</body>
</html>