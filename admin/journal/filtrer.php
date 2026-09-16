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
// RÉCUPÉRATION DES VALEURS DE FILTRE
// ============================================

$filtres = [
    'action' => $_GET['action'] ?? '',
    'module' => $_GET['module'] ?? '',
    'utilisateur_id' => $_GET['utilisateur_id'] ?? '',
    'date_debut' => $_GET['date_debut'] ?? '',
    'date_fin' => $_GET['date_fin'] ?? '',
    'search' => $_GET['search'] ?? '',
    'ip' => $_GET['ip'] ?? '',
    'sort' => $_GET['sort'] ?? 'date_action',
    'order' => $_GET['order'] ?? 'DESC',
    'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 50
];

// ============================================
// CONSTRUCTION DE LA REQUÊTE SQL
// ============================================

$whereConditions = [];
$params = [];

// Filtre par action
if (!empty($filtres['action'])) {
    $whereConditions[] = "ja.action = ?";
    $params[] = $filtres['action'];
}

// Filtre par module
if (!empty($filtres['module'])) {
    $whereConditions[] = "ja.module = ?";
    $params[] = $filtres['module'];
}

// Filtre par utilisateur
if (!empty($filtres['utilisateur_id'])) {
    $whereConditions[] = "ja.utilisateur_id = ?";
    $params[] = $filtres['utilisateur_id'];
}

// Filtre par date de début
if (!empty($filtres['date_debut'])) {
    $whereConditions[] = "DATE(ja.date_action) >= ?";
    $params[] = $filtres['date_debut'];
}

// Filtre par date de fin
if (!empty($filtres['date_fin'])) {
    $whereConditions[] = "DATE(ja.date_action) <= ?";
    $params[] = $filtres['date_fin'];
}

// Filtre par IP
if (!empty($filtres['ip'])) {
    $whereConditions[] = "ja.adresse_ip LIKE ?";
    $params[] = '%' . $filtres['ip'] . '%';
}

// Filtre par recherche (description, nom, username)
if (!empty($filtres['search'])) {
    $searchParam = '%' . $filtres['search'] . '%';
    $whereConditions[] = "(
        ja.description LIKE ? OR 
        u.nom LIKE ? OR 
        u.prenom LIKE ? OR 
        u.username LIKE ? OR
        ja.action LIKE ?
    )";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Tri
$allowedSorts = ['date_action', 'action', 'module', 'utilisateur_id'];
$sort = in_array($filtres['sort'], $allowedSorts) ? $filtres['sort'] : 'date_action';
$order = strtoupper($filtres['order']) === 'ASC' ? 'ASC' : 'DESC';

// ============================================
// EXÉCUTION DE LA REQUÊTE
// ============================================

$activites = [];
$totalCount = 0;

try {
    // Compter le total
    $countSql = "
        SELECT COUNT(*) as total
        FROM journal_activites ja
        LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int)($stmt->fetch()['total'] ?? 0);

    // Récupérer les résultats
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
        ORDER BY ja.$sort $order
        LIMIT ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$filtres['limit']]));
    $activites = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Erreur lors de la recherche : ' . $e->getMessage();
}

// ============================================
// RÉCUPÉRATION DES DONNÉES POUR LES FILTRES
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
// STATISTIQUES DE LA RECHERCHE
// ============================================

$stats = [
    'total' => $totalCount,
    'affichage' => count($activites),
    'actions' => 0,
    'utilisateurs' => 0
];

// Compter les actions uniques dans les résultats
$uniqueActions = [];
foreach ($activites as $a) {
    if (!in_array($a['action'], $uniqueActions)) {
        $uniqueActions[] = $a['action'];
    }
}
$stats['actions'] = count($uniqueActions);

// Compter les utilisateurs uniques dans les résultats
$uniqueUsers = [];
foreach ($activites as $a) {
    if ($a['utilisateur_id'] && !in_array($a['utilisateur_id'], $uniqueUsers)) {
        $uniqueUsers[] = $a['utilisateur_id'];
    }
}
$stats['utilisateurs'] = count($uniqueUsers);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche avancée - Journal - <?php echo APP_NAME; ?></title>
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
        .main-content {
            height: 100vh;
            overflow-y: auto;
            padding: 0;
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

        /* Filtres avancés */
        .filters-advanced {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            margin-bottom: 25px;
        }
        .filters-advanced .filter-title {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .filters-advanced .filter-title i { color: #f7971e; margin-right: 10px; }

        .filters-advanced .form-label {
            font-weight: 600;
            color: #555;
            font-size: 13px;
        }
        .filters-advanced .form-control,
        .filters-advanced .form-select {
            border-radius: 10px;
            padding: 8px 15px;
            border: 2px solid #e1e5ee;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .filters-advanced .form-control:focus,
        .filters-advanced .form-select:focus {
            border-color: #f7971e;
            box-shadow: 0 0 0 3px rgba(247, 151, 30, 0.1);
        }
        .filters-advanced .btn-search {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border: none;
            font-weight: 700;
            padding: 10px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }
        .filters-advanced .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
        }
        .filters-advanced .btn-reset {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .filters-advanced .btn-reset:hover {
            background: #e9ecef;
            color: #333;
        }

        /* Résultats */
        .results-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
        }

        .results-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .results-stats .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }
        .results-stats .stat-item .number {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 18px;
        }
        .results-stats .stat-item .label { color: #888; }

        .badge-action {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
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

        .user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-cell .avatar-mini {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 12px;
            flex-shrink: 0;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        .no-results i { font-size: 60px; color: #ddd; }
        .no-results h5 { color: #666; margin-top: 15px; }
        .no-results p { color: #aaa; }

        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            html, body { overflow: visible; }
            .main-content { height: auto; }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px;
            }
            .top-bar .user-info { width: 100%; justify-content: space-between; }
            .content-section { padding: 15px 20px; }
            .filters-advanced { padding: 20px; }
            .results-container { padding: 15px; overflow-x: auto; }
        }
        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 10px 15px; }
            .filters-advanced { padding: 15px; }
            .results-stats { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

<div class="container-fluid" style="padding: 0; height: 100vh; overflow: hidden;">
    <div class="row" style="height: 100%; margin: 0;">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2" style="padding: 0; height: 100%;">
            <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div class="col-md-9 col-lg-10 main-content" style="padding: 0;">

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="page-title">
                    <h4><i class="bi bi-search"></i> Recherche avancée</h4>
                    <small><i class="bi bi-filter"></i> Filtrage personnalisé du journal</small>
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

                <!-- Filtres avancés -->
                <div class="filters-advanced fade-in">
                    <h5 class="filter-title"><i class="bi bi-sliders"></i> Critères de recherche</h5>

                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><i class="bi bi-tag"></i> Action</label>
                                <select class="form-select" name="action">
                                    <option value="">Toutes les actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action); ?>" 
                                                <?php echo $filtres['action'] == $action ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="bi bi-folder"></i> Module</label>
                                <select class="form-select" name="module">
                                    <option value="">Tous les modules</option>
                                    <?php foreach ($modules as $module): ?>
                                        <option value="<?php echo htmlspecialchars($module); ?>" 
                                                <?php echo $filtres['module'] == $module ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($module); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="bi bi-person"></i> Utilisateur</label>
                                <select class="form-select" name="utilisateur_id">
                                    <option value="">Tous les utilisateurs</option>
                                    <?php foreach ($utilisateurs as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" 
                                                <?php echo $filtres['utilisateur_id'] == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom'] . ' (@' . $u['username'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="bi bi-wifi"></i> Adresse IP</label>
                                <input type="text" class="form-control" name="ip" 
                                       value="<?php echo htmlspecialchars($filtres['ip']); ?>" 
                                       placeholder="Ex: 192.168.1.1">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-calendar-start"></i> Date début</label>
                                <input type="date" class="form-control" name="date_debut" 
                                       value="<?php echo $filtres['date_debut']; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-calendar-end"></i> Date fin</label>
                                <input type="date" class="form-control" name="date_fin" 
                                       value="<?php echo $filtres['date_fin']; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-search"></i> Recherche</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($filtres['search']); ?>" 
                                       placeholder="Texte dans la description...">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-arrow-down-up"></i> Trier par</label>
                                <select class="form-select" name="sort">
                                    <option value="date_action" <?php echo $filtres['sort'] == 'date_action' ? 'selected' : ''; ?>>Date</option>
                                    <option value="action" <?php echo $filtres['sort'] == 'action' ? 'selected' : ''; ?>>Action</option>
                                    <option value="module" <?php echo $filtres['sort'] == 'module' ? 'selected' : ''; ?>>Module</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-arrow-up"></i> Ordre</label>
                                <select class="form-select" name="order">
                                    <option value="DESC" <?php echo $filtres['order'] == 'DESC' ? 'selected' : ''; ?>>Plus récent d'abord</option>
                                    <option value="ASC" <?php echo $filtres['order'] == 'ASC' ? 'selected' : ''; ?>>Plus ancien d'abord</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-list-ul"></i> Résultats par page</label>
                                <select class="form-select" name="limit">
                                    <option value="20" <?php echo $filtres['limit'] == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $filtres['limit'] == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $filtres['limit'] == 100 ? 'selected' : ''; ?>>100</option>
                                    <option value="200" <?php echo $filtres['limit'] == 200 ? 'selected' : ''; ?>>200</option>
                                    <option value="500" <?php echo $filtres['limit'] == 500 ? 'selected' : ''; ?>>500</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <button type="submit" class="btn-search">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="filtrer.php" class="btn-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Résultats -->
                <div class="results-container fade-in">

                    <?php if (!empty($filtres['action']) || !empty($filtres['module']) || !empty($filtres['utilisateur_id']) || 
                              !empty($filtres['search']) || !empty($filtres['ip']) || !empty($filtres['date_debut']) || !empty($filtres['date_fin'])): ?>
                        
                        <!-- Statistiques -->
                        <div class="results-stats">
                            <div class="stat-item">
                                <span class="number"><?php echo $stats['total']; ?></span>
                                <span class="label">résultats trouvés</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php echo $stats['affichage']; ?></span>
                                <span class="label">affichés</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php echo $stats['actions']; ?></span>
                                <span class="label">actions distinctes</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php echo $stats['utilisateurs']; ?></span>
                                <span class="label">utilisateurs</span>
                            </div>
                        </div>

                        <!-- Tableau des résultats -->
                        <?php if (!empty($activites)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>Module</th>
                                            <th>IP</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                                            <small class="text-muted">@<?php echo htmlspecialchars($activite['username'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge-action <?php echo htmlspecialchars($activite['action']); ?> default">
                                                        <?php echo htmlspecialchars($activite['action']); ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 13px; color: #555; max-width: 300px; word-break: break-word;">
                                                    <?php 
                                                    $desc = htmlspecialchars($activite['description']);
                                                    if (strlen($desc) > 80) {
                                                        echo substr($desc, 0, 80) . '...';
                                                    } else {
                                                        echo $desc;
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($activite['module']): ?>
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo htmlspecialchars($activite['module']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code style="font-size: 12px; background: #f8f9fa; padding: 2px 8px; border-radius: 4px;">
                                                        <?php echo htmlspecialchars($activite['adresse_ip'] ?? '-'); ?>
                                                    </code>
                                                </td>
                                                <td style="font-size: 13px; color: #888; white-space: nowrap;">
                                                    <?php echo date('d/m/Y H:i', strtotime($activite['date_action'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-results">
                                <i class="bi bi-search"></i>
                                <h5>Aucun résultat</h5>
                                <p>Aucune activité ne correspond à vos critères de recherche.</p>
                                <a href="filtrer.php" class="btn btn-sm btn-outline-secondary mt-3">
                                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser les filtres
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Message initial -->
                        <div class="no-results">
                            <i class="bi bi-sliders"></i>
                            <h5>Filtrage avancé</h5>
                            <p>Utilisez les filtres ci-dessus pour rechercher dans le journal d'activités.</p>
                            <div class="text-muted small mt-3">
                                <i class="bi bi-info-circle"></i>
                                Vous pouvez filtrer par action, module, utilisateur, date, IP ou mot-clé.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div style="text-align: center; padding: 30px 0 20px; color: #ccc; font-size: 13px;">
                    <i class="bi bi-heart-fill" style="color: #ff6b6b;"></i>
                    <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Animation fade-in
    document.querySelectorAll('.fade-in').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.animation = `fadeInUp 0.6s ease forwards`;
        el.style.animationDelay = `${i * 0.1}s`;
    });

    // Auto-submit on filter change (optionnel - décommenter pour auto-submit)
    /*
    document.querySelectorAll('.filters-advanced select, .filters-advanced input').forEach(el => {
        el.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
    */
</script>
</body>
</html>