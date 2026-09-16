<?php
// ============================================
// MODE DEBUG (activer avec ?debug=1)
// ============================================
$DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo '<div style="background:#111;color:#0f0;padding:15px;font-family:monospace;font-size:13px;border-bottom:3px solid #0f0;">';
    echo '<strong>🔍 MODE DEBUG ACTIF</strong> — PHP ' . PHP_VERSION . '<br><br>';
}

// ============================================
// CHARGEMENT AUTH
// ============================================
try {
    require_once __DIR__ . '/includes/auth.php';
} catch (Throwable $e) {
    if ($DEBUG) {
        echo '<span style="color:#f00;">❌ auth.php : ' . htmlspecialchars($e->getMessage()) . '</span>';
        echo '</div>'; exit;
    }
    http_response_code(500);
    exit('Erreur serveur');
}

if ($DEBUG) {
    echo '<strong>🔧 Fonctions :</strong><br>';
    $__funcs = ['getDbConnection','getCurrentUser','getCurrentUserId','isAdmin',
                'hasPermission','requirePermission'];
    foreach ($__funcs as $__fn) {
        echo (function_exists($__fn) ? '✅' : '❌') . ' ' . $__fn . '()<br>';
    }
    echo '<br><strong>📌 Constantes :</strong><br>';
    echo 'APP_NAME : ' . (defined('APP_NAME') ? APP_NAME : '❌') . '<br>';
    echo 'BASE_PATH : ' . (defined('BASE_PATH') ? '[' . BASE_PATH . ']' : '❌') . '<br>';
    echo '</div>';
}

// ============================================
// FALLBACKS
// ============================================
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion d\'invitations');
if (!defined('BASE_PATH')) define('BASE_PATH', '');

// ============================================
// PERMISSIONS
// ============================================
requirePermission('evenements.voir');

$user = getCurrentUser();
$pdo  = getDbConnection();

// ============================================
// FILTRES ET PAGINATION
// ============================================
$page             = max(1, (int)($_GET['page'] ?? 1));
$limit            = max(1, min(100, (int)($_GET['limit'] ?? 10)));
$offset           = ($page - 1) * $limit;
$filtre_statut    = $_GET['statut'] ?? '';
$filtre_recherche = trim($_GET['search'] ?? '');

// ============================================
// CONSTRUCTION REQUÊTE
// ============================================
$whereConditions = [];
$params = [];

if (!empty($filtre_statut)) {
    $whereConditions[] = "e.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_recherche !== '') {
    $whereConditions[] = "(e.nom LIKE ? OR e.description LIKE ? OR e.lieu LIKE ?)";
    $sp = '%' . $filtre_recherche . '%';
    array_push($params, $sp, $sp, $sp);
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// COMPTAGE
// ============================================
$totalCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM evenements e $whereClause");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    if ($DEBUG) {
        echo '<div style="background:#300;color:#f88;padding:15px;font-family:monospace;">';
        echo '❌ COUNT : ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

// ============================================
// RÉCUPÉRATION DES ÉVÉNEMENTS (sans GROUP BY strict)
// ============================================
$evenements = [];
try {
    $sql = "
        SELECT 
            e.*,
            (SELECT COUNT(*) FROM invitations i WHERE i.id_evenement = e.id) AS nb_invitations,
            (SELECT COUNT(*) FROM presences p 
                INNER JOIN invitations i2 ON i2.id = p.id_invitation 
                WHERE i2.id_evenement = e.id) AS nb_presences,
            u.nom AS createur_nom,
            u.prenom AS createur_prenom
        FROM evenements e
        LEFT JOIN utilisateurs u ON e.created_by = u.id
        $whereClause
        ORDER BY e.date_evenement DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des événements';
    if ($DEBUG) {
        echo '<div style="background:#300;color:#f88;padding:15px;font-family:monospace;">';
        echo '❌ SELECT : ' . htmlspecialchars($e->getMessage()) . '<br><br>';
        echo 'Requête :<pre>' . htmlspecialchars($sql) . '</pre>';
        echo 'Params :<pre>' . print_r($params, true) . '</pre>';
        echo '</div>';
    }
}

$statuts = ['BROUILLON', 'ACTIF', 'TERMINE', 'ANNULE'];
$totalPages = max(1, (int)ceil($totalCount / $limit));

$queryParams = $_GET;
unset($queryParams['page'], $queryParams['debug']);
$baseUrl = 'index.php?' . http_build_query($queryParams);
if (!empty($queryParams)) $baseUrl .= '&';

$success = $_GET['success'] ?? '';
$message = [
    'ajoute'   => 'Événement créé avec succès ! 🎉',
    'modifie'  => 'Événement modifié avec succès ! ✅',
    'supprime' => 'Événement supprimé avec succès ! 🗑️',
    'annule'   => 'Événement annulé ! ❌'
];

$statutColors = [
    'BROUILLON' => 'warning',
    'ACTIF'     => 'success',
    'TERMINE'   => 'info',
    'ANNULE'    => 'danger'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            -webkit-font-smoothing: antialiased;
        }
        .main-content { height: 100vh; overflow-y: auto; padding: 0; }
        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 10px;
        }

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
            flex-wrap: wrap;
            gap: 10px;
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
        .stat-mini-card:hover { transform: translateY(-3px); box-shadow: 0 10px 35px rgba(0,0,0,0.08); }
        .stat-mini-card .icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .stat-mini-card .icon.blue { background: linear-gradient(135deg, #c17c60, #d4a574); color: white; }
        .stat-mini-card .icon.orange { background: linear-gradient(135deg, #d4a574, #e8c9a8); color: white; }
        .stat-mini-card .icon.green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .stat-mini-card .icon.purple { background: linear-gradient(135deg, #a18cd1, #c9b6e4); color: white; }
        .stat-mini-card .stat-number { font-size: 22px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-mini-card .stat-label { font-size: 13px; color: #9a8a7f; }

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
        .table-container .table-header .btn-add {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border: none;
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
            border-color: #c17c60; outline: none;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        }
        .filters-bar .btn-filter {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border: none;
            padding: 8px 20px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.2);
        }
        .filters-bar .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(193, 124, 96, 0.3); }
        .filters-bar .btn-reset {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            padding: 8px 20px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .filters-bar .btn-reset:hover { background: rgba(255, 255, 255, 0.95); color: #c17c60; }

        .badge-statut {
            padding: 5px 14px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.05em; text-transform: uppercase;
            display: inline-block;
        }
        .badge-statut.brouillon { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-statut.actif { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-statut.termine { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-statut.annule { background: rgba(239, 68, 68, 0.15); color: #991b1b; }

        .btn-action {
            padding: 7px 12px; border-radius: 10px;
            border: none; transition: all 0.3s ease;
            font-size: 14px; text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover { transform: scale(1.08); }
        .btn-action.voir { background: rgba(193, 124, 96, 0.12); color: #c17c60; }
        .btn-action.voir:hover { background: #c17c60; color: white; }
        .btn-action.modifier { background: rgba(212, 165, 116, 0.15); color: #a86a50; }
        .btn-action.modifier:hover { background: #d4a574; color: white; }
        .btn-action.supprimer { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
        .btn-action.supprimer:hover { background: #dc2626; color: white; }

        .event-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(234, 227, 220, 0.5);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .event-card:hover {
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .event-card .event-title { font-weight: 700; color: #1a1a1a; font-size: 15px; }
        .event-card .event-meta { font-size: 13px; color: #9a8a7f; line-height: 1.7; margin-top: 4px; }
        .event-card .event-meta i { color: #c17c60; margin-right: 6px; }
        .event-card .event-stats { display: flex; gap: 15px; flex-wrap: wrap; }
        .event-card .event-stats .stat {
            font-size: 13px; color: #6a5a4a;
            background: rgba(251, 248, 245, 0.8);
            padding: 6px 14px; border-radius: 20px;
            border: 1px solid rgba(234, 227, 220, 0.5);
        }
        .event-card .event-stats .stat i { color: #c17c60; margin-right: 6px; }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 60px; color: #d4c5b2; }
        .empty-state h5 { color: #6a5a4a; margin-top: 15px; font-weight: 600; }
        .empty-state p { color: #b8a99c; }

        .pagination-custom {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 20px; flex-wrap: wrap; gap: 15px;
        }
        .pagination-custom .info { font-size: 13px; color: #9a8a7f; }
        .pagination-custom .pagination { margin: 0; gap: 4px; }
        .pagination-custom .pagination .page-link {
            border-radius: 10px;
            border: 1px solid rgba(234, 227, 220, 0.6);
            color: #6a5a4a;
            padding: 6px 14px;
            font-size: 13px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.7);
        }
        .pagination-custom .pagination .page-link:hover {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border-color: #c17c60;
        }
        .pagination-custom .pagination .active .page-link {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border-color: #c17c60;
        }

        .alert-success-custom {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #065f46;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .alert-success-custom i { font-size: 18px; color: #10b981; }

        .alert-error-custom {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #991b1b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert-error-custom i { font-size: 18px; color: #ef4444; flex-shrink: 0; margin-top: 2px; }

        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 992px) {
            html, body { overflow: visible; }
            .main-content { height: auto; }
            .top-bar { flex-direction: column; align-items: flex-start; gap: 15px; padding: 15px 20px; }
            .top-bar .user-info { width: 100%; justify-content: space-between; }
            .content-section { padding: 15px 20px; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .table-container { padding: 15px; }
            .pagination-custom { flex-direction: column; align-items: center; }
            .stats-row .stat-mini-card { margin-bottom: 10px; }
        }
        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 10px 15px; }
            .table-container { padding: 10px; overflow-x: auto; }
            .event-card { padding: 15px; }
            .event-card .event-stats { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>

<div class="container-fluid" style="padding: 0; height: 100vh; overflow: hidden;">
    <div class="row" style="height: 100%; margin: 0;">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2" style="padding: 0; height: 100%;">
            <?php 
            // ✅ CORRECTION : le chemin était 'a/includes/sidebar.php' (manquait un /)
            $__sidebarPath = __DIR__ . '/includes/sidebar.php';
            if (file_exists($__sidebarPath)) {
                include_once $__sidebarPath;
            } else {
                echo '<div style="padding:20px;color:#c00;background:#fee;font-family:monospace;font-size:12px;">';
                echo '❌ sidebar.php introuvable :<br>' . htmlspecialchars($__sidebarPath);
                echo '</div>';
            }
            ?>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div class="col-md-9 col-lg-10 main-content" style="padding: 0;">

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="page-title">
                    <h4><i class="bi bi-calendar-event"></i> Événements</h4>
                    <small><i class="bi bi-list-ul"></i> Liste et gestion des événements</small>
                </div>
                <div class="user-info">
                    <span class="role-badge">
                        <i class="bi bi-shield-check"></i>
                        <?php 
                        $roles_user = $user['roles'] ?? [];
                        echo is_array($roles_user) ? htmlspecialchars(implode(', ', $roles_user)) : 'Aucun rôle';
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
                        echo htmlspecialchars($initiales ?: 'U');
                        ?>
                    </div>
                </div>
            </div>

            <!-- CONTENU -->
            <div class="content-section">

                <?php if (!empty($error)): ?>
                    <div class="alert-error-custom fade-in">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Message de succès -->
                <?php if ($success && isset($message[$success])): ?>
                    <div class="alert-success-custom fade-in">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo htmlspecialchars($message[$success]); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistiques -->
                <?php 
                $stats_events = ['total' => 0, 'brouillon' => 0, 'actif' => 0, 'termine' => 0, 'annule' => 0];
                foreach ($evenements as $e) {
                    $stats_events['total']++;
                    $statut = strtolower($e['statut'] ?? '');
                    if (isset($stats_events[$statut])) $stats_events[$statut]++;
                }
                $stats_events['total'] = $totalCount;
                ?>

                <div class="stats-row fade-in">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon blue"><i class="bi bi-calendar-event"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats_events['total']; ?></div>
                                    <div class="stat-label">Total</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon orange"><i class="bi bi-pencil"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats_events['brouillon']; ?></div>
                                    <div class="stat-label">Brouillons</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon green"><i class="bi bi-check-circle"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats_events['actif']; ?></div>
                                    <div class="stat-label">Actifs</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon purple"><i class="bi bi-clock"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats_events['termine']; ?></div>
                                    <div class="stat-label">Terminés</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste -->
                <div class="table-container fade-in">

                    <div class="table-header">
                        <h5><i class="bi bi-list-ul"></i> Liste des événements</h5>
                        <?php if (hasPermission('evenements.creer')): ?>
                            <a href="creer.php" class="btn-add">
                                <i class="bi bi-plus-circle"></i> Nouvel événement
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Filtres -->
                    <form method="GET" action="" class="filters-bar">
                        <div class="filter-group">
                            <label><i class="bi bi-search"></i> Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, lieu..." style="min-width: 150px;">
                        </div>
                        <div class="filter-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="">Tous</option>
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $filtre_statut == $s ? 'selected' : ''; ?>>
                                        <?php echo $s; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter">
                            <i class="bi bi-filter"></i> Filtrer
                        </button>
                        <a href="index.php" class="btn-reset">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </a>
                    </form>

                    <?php if (!empty($evenements)): ?>
                        <div class="row g-0">
                            <?php foreach ($evenements as $event): ?>
                                <div class="col-12">
                                    <div class="event-card">
                                        <div class="row align-items-center g-3">
                                            <div class="col-md-5">
                                                <div class="event-title">
                                                    <?php echo htmlspecialchars($event['nom'] ?? ''); ?>
                                                </div>
                                                <div class="event-meta">
                                                    <i class="bi bi-calendar"></i>
                                                    <?php 
                                                    echo !empty($event['date_evenement']) 
                                                        ? date('d/m/Y', strtotime($event['date_evenement'])) 
                                                        : '—'; 
                                                    ?>
                                                    <?php if (!empty($event['heure_evenement'])): ?>
                                                        à <?php echo date('H:i', strtotime($event['heure_evenement'])); ?>
                                                    <?php endif; ?>
                                                    <br>
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?php echo htmlspecialchars($event['lieu'] ?? 'Lieu non défini'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="event-stats">
                                                    <div class="stat">
                                                        <i class="bi bi-envelope"></i>
                                                        <?php echo (int)($event['nb_invitations'] ?? 0); ?> invitations
                                                    </div>
                                                    <div class="stat">
                                                        <i class="bi bi-person-check"></i>
                                                        <?php echo (int)($event['nb_presences'] ?? 0); ?> présents
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="badge-statut <?php echo strtolower($event['statut'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($event['statut'] ?? ''); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-2 text-md-end">
                                                <div class="d-flex gap-1 justify-content-md-end flex-wrap">
                                                    <a href="voir.php?id=<?php echo (int)$event['id']; ?>" 
                                                       class="btn-action voir" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if (hasPermission('evenements.modifier')): ?>
                                                        <a href="modifier.php?id=<?php echo (int)$event['id']; ?>" 
                                                           class="btn-action modifier" title="Modifier">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('evenements.supprimer')): ?>
                                                        <a href="supprimer.php?id=<?php echo (int)$event['id']; ?>" 
                                                           class="btn-action supprimer" title="Supprimer"
                                                           onclick="return confirm('Voulez-vous vraiment supprimer cet événement ?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-custom">
                                <div class="info">
                                    Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> événements
                                    (Page <?php echo $page; ?> sur <?php echo $totalPages; ?>)
                                </div>
                                <nav>
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars($baseUrl); ?>page=<?php echo $page - 1; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php 
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        for ($i = $startPage; $i <= $endPage; $i++): 
                                        ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo htmlspecialchars($baseUrl); ?>page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars($baseUrl); ?>page=<?php echo $page + 1; ?>">
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
                            <p>Commencez par créer votre premier événement</p>
                            <?php if (hasPermission('evenements.creer')): ?>
                                <a href="creer.php" class="btn-add mt-3" style="display: inline-block;">
                                    <i class="bi bi-plus-circle"></i> Créer un événement
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div style="text-align: center; padding: 30px 0 20px; color: #b8a99c; font-size: 13px;">
                    <i class="bi bi-heart-fill" style="color: #c17c60;"></i>
                    <?php echo htmlspecialchars(APP_NAME); ?> • Tous droits réservés • <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.fade-in').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.animation = 'fadeInUp 0.6s ease forwards';
        el.style.animationDelay = (i * 0.1) + 's';
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