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
    echo '<strong>📁 Vérification des fichiers :</strong><br>';
    $__files = [
        '../../config/database.php',
        '../../includes/auth.php',
        '../../includes/sidebar.php',
    ];
    foreach ($__files as $__f) {
        $__p = __DIR__ . '/' . $__f;
        echo (file_exists($__p) ? '✅' : '❌') . ' ' . $__f . '<br>';
    }
    echo '<br>';
}

// ============================================
// CHARGEMENT CONFIG + AUTH
// ============================================
try {
    require_once __DIR__ . '/../../includes/auth.php';
} catch (Throwable $e) {
    if ($DEBUG) {
        echo '<span style="color:#f00;">❌ Erreur chargement auth.php : ' . htmlspecialchars($e->getMessage()) . '</span><br>';
        echo 'Fichier : ' . htmlspecialchars($e->getFile()) . ' ligne ' . $e->getLine() . '<br>';
        echo '</div>';
        exit;
    }
    http_response_code(500);
    exit('Erreur serveur');
}

if ($DEBUG) {
    echo '<strong>🔧 Fonctions disponibles :</strong><br>';
    $__funcs = ['getDbConnection','getCurrentUser','getCurrentUserId','isAdmin',
                'hasPermission','requirePermission','getEvenementsPourSelect'];
    foreach ($__funcs as $__fn) {
        echo (function_exists($__fn) ? '✅' : '❌') . ' ' . $__fn . '()<br>';
    }
    echo '<br><strong>📌 Constantes :</strong><br>';
    echo 'APP_NAME : ' . (defined('APP_NAME') ? APP_NAME : '❌ NON DÉFINIE') . '<br>';
    echo 'BASE_PATH : ' . (defined('BASE_PATH') ? '[' . BASE_PATH . ']' : '❌ NON DÉFINIE') . '<br>';
    echo '</div>';
}

// ============================================
// FALLBACKS (sécurité)
// ============================================
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion d\'invitations');
if (!defined('BASE_PATH')) define('BASE_PATH', '');

if (!function_exists('getEvenementsPourSelect')) {
    function getEvenementsPourSelect(PDO $pdo): array {
        try {
            $stmt = $pdo->query("SELECT id, nom FROM evenements ORDER BY nom");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
}

// ============================================
// PERMISSIONS
// ============================================
requirePermission('invites.voir');

$user = getCurrentUser();
$pdo  = getDbConnection();

// ============================================
// FILTRES ET PAGINATION
// ============================================
$page              = max(1, (int)($_GET['page'] ?? 1));
$limit             = max(1, min(100, (int)($_GET['limit'] ?? 10)));
$offset            = ($page - 1) * $limit;
$filtre_categorie  = (int)($_GET['categorie'] ?? 0);
$filtre_recherche  = trim($_GET['search'] ?? '');
$filtre_evenement  = (int)($_GET['evenement'] ?? 0);

// ============================================
// CONSTRUCTION REQUÊTE
// ============================================
$whereConditions = [];
$params = [];

if (!isAdmin()) {
    $whereConditions[] = "i.id_evenement IN (
        SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
    )";
    $params[] = (int)getCurrentUserId();
}

if ($filtre_evenement > 0) {
    $whereConditions[] = "i.id_evenement = ?";
    $params[] = $filtre_evenement;
}

if ($filtre_categorie > 0) {
    $whereConditions[] = "i.id_categorie = ?";
    $params[] = $filtre_categorie;
}

if ($filtre_recherche !== '') {
    $whereConditions[] = "(i.nom LIKE ? OR i.prenom LIKE ? OR i.email LIKE ? OR i.telephone LIKE ?)";
    $sp = '%' . $filtre_recherche . '%';
    array_push($params, $sp, $sp, $sp, $sp);
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// COMPTAGE TOTAL
// ============================================
$totalCount = 0;
$error = '';
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invites i $whereClause");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $error = 'Erreur comptage : ' . $e->getMessage();
    if ($DEBUG) {
        echo '<div style="background:#300;color:#f88;padding:15px;font-family:monospace;">';
        echo '❌ COUNT SQL : ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo 'Requête : ' . htmlspecialchars($countSql ?? '');
        echo '</div>';
    }
}

// ============================================
// RÉCUPÉRATION DES INVITÉS (sans GROUP BY)
// ============================================
$invites = [];
try {
    // LIMIT/OFFSET en dur (déjà castés en int) → compatibilité maximale
    $sql = "
        SELECT 
            i.*,
            c.nom AS categorie_nom,
            e.nom AS evenement_nom,
            e.date_evenement AS evenement_date,
            (SELECT COUNT(*) FROM invitations inv WHERE inv.id_invite = i.id) AS nb_invitations
        FROM invites i
        LEFT JOIN categories_invites c ON i.id_categorie = c.id
        LEFT JOIN evenements e ON e.id = i.id_evenement
        $whereClause
        ORDER BY i.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erreur chargement des invités : ' . $e->getMessage();
    if ($DEBUG) {
        echo '<div style="background:#300;color:#f88;padding:15px;font-family:monospace;">';
        echo '❌ SELECT SQL : ' . htmlspecialchars($e->getMessage()) . '<br><br>';
        echo 'Requête complète :<br><pre>' . htmlspecialchars($sql) . '</pre>';
        echo 'Paramètres : <pre>' . print_r($params, true) . '</pre>';
        echo '</div>';
    }
}

// ============================================
// CATÉGORIES
// ============================================
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categories_invites WHERE actif = 1 ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($DEBUG) {
        echo '<div style="background:#330;color:#ff0;padding:10px;font-family:monospace;">';
        echo '⚠️ Catégories : ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

// ============================================
// ÉVÉNEMENTS
// ============================================
$evenementsDisponibles = [];
try {
    $evenementsDisponibles = getEvenementsPourSelect($pdo);
} catch (Throwable $e) {
    if ($DEBUG) {
        echo '<div style="background:#330;color:#ff0;padding:10px;font-family:monospace;">';
        echo '⚠️ Événements : ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

// ============================================
// PAGINATION
// ============================================
$totalPages = max(1, (int)ceil($totalCount / $limit));
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['debug']);
$baseUrl = 'index.php?' . http_build_query($queryParams);
if (!empty($queryParams)) $baseUrl .= '&';

$success = $_GET['success'] ?? '';
$message = [
    'ajoute'   => 'Invité ajouté avec succès ! 🎉',
    'modifie'  => 'Invité modifié avec succès ! ✅',
    'supprime' => 'Invité supprimé avec succès ! 🗑️'
];

// ============================================
// PHOTOS
// ============================================
$uploadDir = __DIR__ . '/../../uploads/photos/';
$basePath  = rtrim(BASE_PATH, '/') . '/uploads/photos/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invités - <?php echo htmlspecialchars(APP_NAME); ?></title>
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

        /* ========== LAYOUT ========== */
        .app-wrapper { display: flex; min-height: 100vh; width: 100%; }
        .sidebar-wrapper { flex-shrink: 0; width: 260px; min-height: 100vh; position: sticky; top: 0; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; min-height: 100vh; overflow-y: auto; padding: 0; min-width: 0; }
        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
        .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c17c60, #d4a574); border-radius: 10px; }

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
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);
            z-index: 150; opacity: 0; transition: opacity 0.3s ease;
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
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .user-info-banner i { color: #c17c60; font-size: 18px; flex-shrink: 0; }
        .user-info-banner strong { color: #c17c60; }

        /* ========== STATS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(193, 124, 96, 0.12);
            border-color: rgba(193, 124, 96, 0.3);
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white; flex-shrink: 0;
        }
        .stat-card .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-card .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card .stat-icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .stat-card .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-card .stat-label { font-size: 12px; color: #9a8a7f; margin-top: 2px; }

        /* ========== TABLE CONTAINER ========== */
        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .table-header h5 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 17px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-header h5 i { color: #c17c60; }

        .btn-add {
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
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }

        /* ========== FILTRES ========== */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(251, 248, 245, 0.7);
            border-radius: 14px;
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
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }
        .filters-bar .filter-group select:focus,
        .filters-bar .filter-group input:focus {
            border-color: #c17c60; outline: none;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        }
        .btn-filter {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border: none;
            padding: 8px 20px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.2);
        }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(193, 124, 96, 0.3); }
        .btn-reset {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            padding: 8px 20px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.3s ease;
        }
        .btn-reset:hover { background: white; color: #c17c60; border-color: #c17c60; }

        /* ========== GUEST CARD ========== */
        .guest-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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
        .guest-card:hover {
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .guest-info { display: flex; align-items: center; gap: 15px; }
        .guest-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 20px;
            flex-shrink: 0; overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .guest-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .guest-avatar.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .guest-avatar.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .guest-avatar.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .guest-avatar.pink { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .guest-avatar.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .guest-avatar.red { background: linear-gradient(135deg, #ef4444, #f87171); }

        .guest-name { font-weight: 700; color: #1a1a1a; font-size: 15px; margin-bottom: 3px; }
        .guest-details { font-size: 12px; color: #9a8a7f; display: flex; flex-direction: column; gap: 2px; }
        .guest-details i { margin-right: 5px; color: #c17c60; }
        .guest-meta { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

        /* ========== BADGES ========== */
        .badge-categorie {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-categorie.primary { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-categorie.success { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-categorie.warning { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-categorie.info { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-categorie.secondary { background: rgba(108, 117, 125, 0.15); color: #495057; }
        .badge-categorie.dark { background: rgba(33, 37, 41, 0.15); color: #212529; }
        .badge-categorie.light { background: rgba(248, 249, 250, 0.8); color: #6c757d; border: 1px solid #dee2e6; }

        .badge-evenement {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.15));
            color: #c17c60;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(193, 124, 96, 0.2);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .badge-evenement i { font-size: 12px; flex-shrink: 0; }

        .badge-invitations {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ========== BOUTONS ACTION ========== */
        .action-buttons { display: flex; gap: 6px; }
        .btn-action {
            width: 34px; height: 34px;
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
            margin-top: 25px;
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
            color: white; border-color: #c17c60;
        }
        .pagination-custom .pagination .active .page-link {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; border-color: #c17c60;
        }

        /* ========== ALERTE SUCCÈS ========== */
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
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .alert-success-custom i { font-size: 18px; color: #10b981; }

        /* ========== ALERTE ERREUR ========== */
        .alert-error-custom {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #991b1b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-error-custom i { font-size: 18px; color: #ef4444; flex-shrink: 0; margin-top: 2px; }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #b8a99c;
            font-size: 13px;
        }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== ANIMATIONS ========== */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }

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
            .table-container { padding: 18px; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filters-bar .filter-group { flex-wrap: wrap; }
            .filters-bar .filter-group select,
            .filters-bar .filter-group input { flex: 1; min-width: 120px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .guest-card { flex-direction: column; align-items: stretch; text-align: center; }
            .guest-info { justify-content: center; flex-wrap: wrap; }
            .guest-meta { justify-content: center; }
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
            .guest-card { padding: 15px; }
            .guest-avatar { width: 44px; height: 44px; font-size: 16px; }
            .guest-name { font-size: 14px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 14px 12px; gap: 10px; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 17px; }
            .stat-card .stat-number { font-size: 18px; }
            .stat-card .stat-label { font-size: 10px; }
            .pagination-custom { flex-direction: column; align-items: center; text-align: center; }
            .badge-evenement { max-width: 150px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
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
        <?php 
        $__sidebarPath = __DIR__ . '/../../includes/sidebar.php';
        if (file_exists($__sidebarPath)) {
            include_once $__sidebarPath;
        } else {
            echo '<div style="padding:20px;color:#c00;background:#fee;font-family:monospace;font-size:12px;">';
            echo '❌ Fichier sidebar.php introuvable :<br>' . htmlspecialchars($__sidebarPath);
            echo '</div>';
        }
        ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-people-fill"></i> Gestion des invités</h4>
                <small><i class="bi bi-list-ul"></i> Liste et gestion des invités</small>
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
                    $userInitiales = strtoupper(
                        substr($user['prenom'] ?? 'U', 0, 1) . 
                        substr($user['nom'] ?? 'N', 0, 1)
                    );
                    echo htmlspecialchars($userInitiales ?: 'U');
                    ?>
                </div>
            </div>
        </div>

        <div class="content-section">

            <?php if ($error): ?>
                <div class="alert-error-custom fade-in">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Erreur :</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success && isset($message[$success])): ?>
                <div class="alert-success-custom fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo htmlspecialchars($message[$success]); ?>
                </div>
            <?php endif; ?>

            <!-- Bannière info selon le rôle -->
            <?php if (!isAdmin()): ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Vous voyez uniquement les invités des événements auxquels vous êtes <strong>associé</strong>.
                        Contactez un administrateur pour être ajouté à d'autres événements.
                    </div>
                </div>
            <?php else: ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        En tant qu'<strong>administrateur</strong>, vous voyez tous les invités de l'application.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <?php 
            $stats_guests = ['total' => 0, 'avec_invitations' => 0, 'sans_invitations' => 0];
            foreach ($invites as $inv) {
                $stats_guests['total']++;
                if (($inv['nb_invitations'] ?? 0) > 0) $stats_guests['avec_invitations']++;
                else $stats_guests['sans_invitations']++;
            }
            $stats_guests['total'] = $totalCount;
            ?>

            <div class="stats-grid fade-in">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats_guests['total']; ?></div>
                        <div class="stat-label">Total invités</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-envelope-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats_guests['avec_invitations']; ?></div>
                        <div class="stat-label">Avec invitation</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-person-x-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats_guests['sans_invitations']; ?></div>
                        <div class="stat-label">Sans invitation</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-calendar-event-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo count($evenementsDisponibles); ?></div>
                        <div class="stat-label">Événements</div>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div class="table-container fade-in">

                <div class="table-header">
                    <h5><i class="bi bi-list-ul"></i> Liste des invités</h5>
                    <?php if (hasPermission('invites.creer')): ?>
                        <a href="creer.php" class="btn-add">
                            <i class="bi bi-plus-circle-fill"></i> Ajouter un invité
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Filtres -->
                <form method="GET" action="" class="filters-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, email..." style="min-width: 150px;">
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-calendar-event"></i> Événement</label>
                        <select name="evenement">
                            <option value="0">Tous</option>
                            <?php foreach ($evenementsDisponibles as $ev): ?>
                                <option value="<?php echo (int)$ev['id']; ?>" <?php echo $filtre_evenement == $ev['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ev['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-tags"></i> Catégorie</label>
                        <select name="categorie">
                            <option value="0">Toutes</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo $filtre_categorie == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                    <a href="index.php" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                </form>

                <?php if (!empty($invites)): ?>
                    <div class="row g-0">
                        <?php 
                        $avatarColors = ['green', 'orange', 'blue', 'pink', 'purple', 'red'];
                        $colorIndex = 0;
                        foreach ($invites as $invite): 
                            $color = $avatarColors[$colorIndex % count($avatarColors)];
                            $colorIndex++;
                            $initiales = strtoupper(
                                substr($invite['prenom'] ?? '', 0, 1) . 
                                substr($invite['nom'] ?? '', 0, 1)
                            );
                            
                            $photoUrl = '';
                            $hasPhoto = false;
                            if (!empty($invite['photo'])) {
                                $photoPath = $uploadDir . $invite['photo'];
                                if (is_file($photoPath)) {
                                    $photoUrl = $basePath . rawurlencode($invite['photo']);
                                    $hasPhoto = true;
                                }
                            }
                        ?>
                            <div class="col-12">
                                <div class="guest-card">
                                    <div class="guest-info">
                                        <div class="guest-avatar <?php echo $color; ?>">
                                            <?php if ($hasPhoto): ?>
                                                <img src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                                                     alt="Photo"
                                                     onerror="this.style.display='none'; this.parentElement.textContent='<?php echo htmlspecialchars($initiales ?: '?', ENT_QUOTES, 'UTF-8'); ?>';">
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($initiales ?: '?'); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="guest-name">
                                                <?php echo htmlspecialchars(($invite['prenom'] ?? '') . ' ' . ($invite['nom'] ?? '')); ?>
                                            </div>
                                            <div class="guest-details">
                                                <?php if (!empty($invite['email'])): ?>
                                                    <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($invite['email']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($invite['telephone'])): ?>
                                                    <span><i class="bi bi-phone-fill"></i> <?php echo htmlspecialchars($invite['telephone']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="guest-meta">
                                        <?php if (!empty($invite['categorie_nom'])): ?>
                                            <span class="badge-categorie">
                                                <?php echo htmlspecialchars($invite['categorie_nom']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($invite['evenement_nom'])): ?>
                                            <span class="badge-evenement" title="<?php echo htmlspecialchars($invite['evenement_nom']); ?>">
                                                <i class="bi bi-calendar-event-fill"></i>
                                                <?php echo htmlspecialchars($invite['evenement_nom']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-evenement" style="background: rgba(108,117,125,0.1); color: #6c757d; border-color: rgba(108,117,125,0.2);">
                                                <i class="bi bi-calendar-x-fill"></i> Aucun événement
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="badge-invitations">
                                            <i class="bi bi-envelope-fill"></i> <?php echo (int)($invite['nb_invitations'] ?? 0); ?>
                                        </span>
                                        
                                        <div class="action-buttons">
                                            <a href="voir.php?id=<?php echo (int)$invite['id']; ?>" 
                                               class="btn-action voir" title="Voir">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <?php if (hasPermission('invites.modifier')): ?>
                                                <a href="modifier.php?id=<?php echo (int)$invite['id']; ?>" 
                                                   class="btn-action modifier" title="Modifier">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (hasPermission('invites.supprimer')): ?>
                                                <a href="supprimer.php?id=<?php echo (int)$invite['id']; ?>" 
                                                   class="btn-action supprimer" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer cet invité ?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            <?php endif; ?>
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
                                Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> invités
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
                        <i class="bi bi-people"></i>
                        <h5>Aucun invité</h5>
                        <?php if (isAdmin()): ?>
                            <p>Aucun invité dans l'application</p>
                            <?php if (hasPermission('invites.creer')): ?>
                                <a href="creer.php" class="btn-add" style="margin-top: 15px;">
                                    <i class="bi bi-plus-circle-fill"></i> Ajouter un invité
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Vous n'avez aucun invité dans vos événements</p>
                            <p style="font-size: 12px; margin-top: 8px; color: #b8a99c;">
                                <i class="bi bi-info-circle"></i>
                                Ajoutez un invité en le rattachant à l'un de vos événements
                            </p>
                            <?php if (hasPermission('invites.creer')): ?>
                                <a href="creer.php" class="btn-add" style="margin-top: 15px;">
                                    <i class="bi bi-plus-circle-fill"></i> Ajouter un invité
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FOOTER -->
            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo htmlspecialchars(APP_NAME); ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ========== SIDEBAR MOBILE ==========
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

    // ========== AUTO-HIDE SUCCESS MESSAGE ==========
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