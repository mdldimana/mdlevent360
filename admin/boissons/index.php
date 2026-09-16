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
requirePermission('boissons.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();
$userId = (int)getCurrentUserId();
$isAdminUser = isAdmin();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// RÉCUPÉRATION DES ÉVÉNEMENTS ACCESSIBLES (EN PREMIER)
// ============================================

$evenements = [];
if (function_exists('getEvenementsPourSelect')) {
    try {
        $evenements = getEvenementsPourSelect($pdo);
    } catch (Throwable $e) {
        error_log('Erreur getEvenementsPourSelect: ' . $e->getMessage());
    }
}

// ============================================
// SÉLECTION DE L'ÉVÉNEMENT (PRIORITAIRE)
// ============================================

// Récupérer l'événement depuis GET ou depuis la session
$evenementSelectionneId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

// Si aucun événement en GET mais des événements disponibles, prendre le premier par défaut
if ($evenementSelectionneId === 0 && !empty($evenements)) {
    // Vérifier si un événement est en session
    if (isset($_SESSION['selected_evenement_id']) && $_SESSION['selected_evenement_id'] > 0) {
        $evenementSelectionneId = (int)$_SESSION['selected_evenement_id'];
    } else {
        // Prendre le premier événement de la liste
        $evenementSelectionneId = (int)$evenements[0]['id'];
    }
}

// Sauvegarder en session
if ($evenementSelectionneId > 0) {
    $_SESSION['selected_evenement_id'] = $evenementSelectionneId;
}

// Récupérer les infos complètes de l'événement sélectionné
$evenementSelectionne = null;
if ($evenementSelectionneId > 0) {
    foreach ($evenements as $ev) {
        if ((int)$ev['id'] === $evenementSelectionneId) {
            $evenementSelectionne = $ev;
            break;
        }
    }
}

// ⚠️ Si aucun événement accessible, on affiche un message
$aucunEvenement = empty($evenements);

// ============================================
// FILTRES ET PAGINATION
// ============================================

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$offset = ($page - 1) * $limit;

$filtre_recherche = $_GET['search'] ?? '';
$filtre_type = $_GET['type'] ?? '';

// ⭐ On force le filtre par événement sélectionné
$filtre_evenement = $evenementSelectionneId;

// ============================================
// RÉCUPÉRATION DES BOISSONS FILTRÉES
// ============================================

$whereConditions = [];
$params = [];

// ⭐ FILTRAGE PAR ÉVÉNEMENT (obligatoire maintenant)
if ($filtre_evenement > 0) {
    $whereConditions[] = "b.id IN (
        SELECT id_boisson FROM evenement_boissons WHERE id_evenement = ?
    )";
    $params[] = $filtre_evenement;
} else {
    // Si aucun événement, aucune boisson à afficher
    $whereConditions[] = "1=0";
}

// FILTRAGE PAR UTILISATEUR (sécurité supplémentaire)
if (!$isAdminUser && $filtre_evenement > 0) {
    // Vérifier que l'utilisateur a accès à cet événement
    $whereConditions[] = "? IN (
        SELECT id_utilisateur FROM evenements_utilisateurs WHERE id_evenement = ?
    )";
    $params[] = $userId;
    $params[] = $filtre_evenement;
}

if (!empty($filtre_recherche)) {
    $whereConditions[] = "(b.nom LIKE ? OR b.description LIKE ?)";
    $searchParam = '%' . $filtre_recherche . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($filtre_type)) {
    $whereConditions[] = "b.type = ?";
    $params[] = $filtre_type;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================

$totalCount = 0;
try {
    $countSql = "SELECT COUNT(DISTINCT b.id) as total FROM boissons b $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int)($stmt->fetch()['total'] ?? 0);
} catch (PDOException $e) {
    error_log('Erreur count boissons: ' . $e->getMessage());
}

$boissons = [];
try {
    $sql = "
        SELECT 
            b.*,
            COUNT(DISTINCT eb.id_evenement) as nb_evenements,
            COUNT(DISTINCT pi.id) as nb_preferences
        FROM boissons b
        LEFT JOIN evenement_boissons eb ON b.id = eb.id_boisson
        LEFT JOIN preferences_invitation pi ON b.id = pi.id_boisson
        $whereClause
        GROUP BY b.id
        ORDER BY b.nom ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $boissons = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur liste boissons: ' . $e->getMessage());
}

// ============================================
// TYPES DE BOISSONS
// ============================================

$types = ['SANS_ALCOOL', 'ALCOOL', 'CHAUD', 'AUTRE'];
$typeLabels = [
    'SANS_ALCOOL' => 'Sans alcool 🧃',
    'ALCOOL' => 'Alcool 🍷',
    'CHAUD' => 'Chaud ☕',
    'AUTRE' => 'Autre 🍹'
];

// ============================================
// PAGINATION
// ============================================

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
    'ajoute' => 'Boisson ajoutée avec succès ! 🍹',
    'modifie' => 'Boisson modifiée avec succès ! ✅',
    'supprime' => 'Boisson supprimée avec succès ! 🗑️',
    'associe' => 'Association mise à jour avec succès ! 🔗'
];

// ============================================
// COMPTER LES PRÉFÉRENCES DE L'ÉVÉNEMENT SÉLECTIONNÉ
// ============================================

$nbPreferences = 0;
try {
    if ($evenementSelectionneId > 0) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM preferences_invitation pi
            INNER JOIN invitations inv ON inv.id = pi.id_invitation
            WHERE inv.id_evenement = ?
        ");
        $stmt->execute([$evenementSelectionneId]);
        $nbPreferences = (int)($stmt->fetch()['count'] ?? 0);
    }
} catch (PDOException $e) {
    error_log('Erreur count preferences: ' . $e->getMessage());
}

// ⭐ STATS DE L'ÉVÉNEMENT SÉLECTIONNÉ
$statsEvenement = [
    'invites' => 0,
    'invitations' => 0,
    'boissons' => $totalCount,
];

if ($evenementSelectionneId > 0) {
    try {
        // Nombre d'invités de l'événement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invites WHERE id_evenement = ? AND actif = 1");
        $stmt->execute([$evenementSelectionneId]);
        $statsEvenement['invites'] = (int)$stmt->fetchColumn();

        // Nombre d'invitations
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invitations WHERE id_evenement = ?");
        $stmt->execute([$evenementSelectionneId]);
        $statsEvenement['invitations'] = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Erreur stats événement: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boissons - <?php echo APP_NAME; ?></title>
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

        /* ========== ÉTAPE 1 : SÉLECTEUR D'ÉVÉNEMENT (PRIORITAIRE) ========== */
        .event-selector-card {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.08), rgba(212, 165, 116, 0.08));
            border: 2px solid rgba(193, 124, 96, 0.25);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        .event-selector-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, #c17c60, #d4a574);
        }
        .event-selector-card .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .event-selector-card .step-number {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(193, 124, 96, 0.25);
        }
        .event-selector-card .step-title {
            flex: 1;
            min-width: 200px;
        }
        .event-selector-card .step-title h5 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 4px;
            font-size: 18px;
        }
        .event-selector-card .step-title p {
            color: #9a8a7f;
            font-size: 13px;
            margin: 0;
        }
        .event-selector-card .step-title p strong {
            color: #c17c60;
        }

        .event-select-input {
            width: 100%;
            padding: 14px 18px;
            border-radius: 14px;
            border: 2px solid rgba(193, 124, 96, 0.3);
            background: white;
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .event-select-input:focus {
            outline: none;
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.12);
        }
        .event-select-input:hover {
            border-color: #c17c60;
        }

        /* ========== STATS ÉVÉNEMENT ========== */
        .event-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .event-stat {
            background: white;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            transition: all 0.3s ease;
        }
        .event-stat:hover {
            border-color: rgba(193, 124, 96, 0.4);
            transform: translateY(-2px);
        }
        .event-stat .stat-icon {
            font-size: 20px;
            color: #c17c60;
            margin-bottom: 4px;
        }
        .event-stat .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .event-stat .stat-label {
            font-size: 11px;
            color: #9a8a7f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

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

        /* ========== ONGLETS ========== */
        .tabs-container {
            display: flex;
            gap: 5px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 6px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            flex-wrap: wrap;
        }
        .tabs-container .tab-btn {
            padding: 10px 24px;
            border-radius: 12px;
            border: none;
            background: transparent;
            color: #9a8a7f;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
            text-decoration: none;
        }
        .tabs-container .tab-btn:hover {
            background: rgba(251, 248, 245, 0.8);
            color: #1a1a1a;
        }
        .tabs-container .tab-btn.active {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        }
        .tabs-container .tab-btn i { font-size: 18px; }
        .tabs-container .tab-btn .badge-tab {
            background: rgba(255,255,255,0.3);
            color: inherit;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .tabs-container .tab-btn.active .badge-tab {
            background: rgba(0,0,0,0.15);
        }

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
        
        /* ⭐ ACTIONS HEADER */
        .header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
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

        /* ⭐ BOUTONS EXPORT & PRÉFÉRENCES */
        .btn-export {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.25);
            font-size: 13px;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);
            color: white;
        }

        .btn-tables {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
            font-size: 13px;
        }
        .btn-tables:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
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
        .filters-bar .btn-reset:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #c17c60;
        }

        /* ========== BOUTONS ACTION ========== */
        .btn-action {
            padding: 7px 12px;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover { transform: scale(1.08); }
        .btn-action.voir { background: rgba(193, 124, 96, 0.12); color: #c17c60; }
        .btn-action.voir:hover { background: #c17c60; color: white; }
        .btn-action.modifier { background: rgba(212, 165, 116, 0.15); color: #a86a50; }
        .btn-action.modifier:hover { background: #d4a574; color: white; }
        .btn-action.associer { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .btn-action.associer:hover { background: #10b981; color: white; }
        .btn-action.supprimer { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
        .btn-action.supprimer:hover { background: #dc2626; color: white; }

        /* ========== CARTE BOISSON ========== */
        .boisson-card {
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
        .boisson-card:hover {
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .boisson-card .boisson-info { flex: 1; min-width: 150px; }
        .boisson-card .boisson-info .name { font-weight: 700; color: #1a1a1a; font-size: 15px; }
        .boisson-card .boisson-info .desc { font-size: 13px; color: #9a8a7f; margin-top: 2px; }
        .boisson-card .boisson-stats { display: flex; gap: 12px; flex-wrap: wrap; }
        .boisson-card .boisson-stats .stat {
            font-size: 12px;
            color: #6a5a4a;
            background: rgba(251, 248, 245, 0.8);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid rgba(234, 227, 220, 0.5);
        }
        .boisson-card .boisson-stats .stat i { color: #c17c60; margin-right: 5px; }
        .boisson-card .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        /* ========== BADGES TYPE ========== */
        .badge-type {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-left: 8px;
            letter-spacing: 0.03em;
        }
        .badge-type.sans_alcool { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-type.alcool { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .badge-type.chaud { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-type.autre { background: rgba(107, 114, 128, 0.15); color: #374151; }

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

        /* ⭐ EMPTY STATE SPÉCIAL : AUCUN ÉVÉNEMENT */
        .empty-state-event {
            text-align: center;
            padding: 80px 30px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(251, 191, 36, 0.08));
            border-radius: 20px;
            border: 2px dashed rgba(245, 158, 11, 0.3);
            margin-bottom: 25px;
        }
        .empty-state-event i {
            font-size: 70px;
            color: #f59e0b;
            display: block;
            margin-bottom: 20px;
        }
        .empty-state-event h4 {
            color: #92400e;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .empty-state-event p {
            color: #78350f;
            font-size: 14px;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

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
            .boisson-card { flex-direction: column; align-items: stretch; text-align: center; }
            .boisson-card .boisson-stats { justify-content: center; }
            .boisson-card .actions { justify-content: center; }
            .pagination-custom { flex-direction: column; align-items: center; text-align: center; }
            .header-actions { width: 100%; }
            .header-actions .btn-add,
            .header-actions .btn-export,
            .header-actions .btn-tables { flex: 1; justify-content: center; }
            .event-selector-card { padding: 20px; }
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
            .table-header .btn-add,
            .table-header .btn-export,
            .table-header .btn-tables { justify-content: center; width: 100%; }
            .header-actions { flex-direction: column; width: 100%; }
            .boisson-card { padding: 15px; }
            .boisson-card .boisson-info .name { font-size: 14px; }
            .boisson-card .boisson-info .desc { font-size: 12px; }
            .boisson-card .boisson-stats .stat { font-size: 11px; padding: 4px 10px; }
            .tabs-container { padding: 4px; gap: 3px; }
            .tabs-container .tab-btn { font-size: 11px; padding: 6px 10px; }
            .tabs-container .tab-btn .badge-tab { font-size: 10px; padding: 1px 8px; }
            .badge-type { font-size: 10px; padding: 2px 10px; margin-left: 5px; }
            .filters-bar { padding: 12px; gap: 8px; }
            .filters-bar .filter-group label { font-size: 11px; }
            .btn-action { padding: 4px 8px; font-size: 12px; }
            .pagination-custom .pagination .page-link { padding: 4px 10px; font-size: 12px; }
            .user-info-banner { font-size: 12px; padding: 10px 12px; }
            .event-selector-card { padding: 18px; }
            .event-selector-card .step-title h5 { font-size: 16px; }
            .event-selector-card .step-title p { font-size: 12px; }
            .event-select-input { padding: 12px 15px; font-size: 14px; }
            .event-stats { grid-template-columns: repeat(2, 1fr); }
            .event-stat { padding: 12px; }
            .event-stat .stat-value { font-size: 18px; }
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
                <h4><i class="bi bi-cup-straw"></i> Gestion des boissons</h4>
                <small><i class="bi bi-list-ul"></i> Sélectionnez un événement pour voir ses boissons</small>
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

            <!-- ⭐ SI AUCUN ÉVÉNEMENT ACCESSIBLE -->
            <?php if ($aucunEvenement): ?>
                <div class="empty-state-event fade-in">
                    <i class="bi bi-calendar-x"></i>
                    <h4>Aucun événement disponible</h4>
                    <p>
                        <?php if ($isAdminUser): ?>
                            Vous devez d'abord <strong>créer un événement</strong> avant de pouvoir y associer des boissons.
                        <?php else: ?>
                            Vous n'êtes associé à <strong>aucun événement</strong> actuellement. 
                            Contactez un administrateur pour être ajouté à un événement.
                        <?php endif; ?>
                    </p>
                    <?php if ($isAdminUser && hasPermission('evenements.creer')): ?>
                        <a href="../evenements/creer.php" 
                           style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                            <i class="bi bi-plus-circle-fill"></i> Créer un événement
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>

                <!-- ⭐ ÉTAPE 1 : SÉLECTEUR D'ÉVÉNEMENT (PRIORITAIRE) -->
                <div class="event-selector-card fade-in">
                    <div class="step-header">
                        <div class="step-number">
                            <i class="bi bi-1-circle-fill"></i>
                        </div>
                        <div class="step-title">
                            <h5>Choisissez un événement</h5>
                            <p>Sélectionnez l'événement dont vous souhaitez gérer les boissons et préférences.</p>
                        </div>
                    </div>

                    <select class="event-select-input" id="eventSelector" onchange="changeEvenement(this.value)">
                        <?php foreach ($evenements as $ev): ?>
                            <option value="<?php echo $ev['id']; ?>" 
                                    <?php echo $evenementSelectionneId == $ev['id'] ? 'selected' : ''; ?>>
                                📅 <?php echo htmlspecialchars($ev['nom']); ?> 
                                — <?php echo date('d/m/Y', strtotime($ev['date_evenement'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Stats de l'événement sélectionné -->
                    <?php if ($evenementSelectionne): ?>
                        <div class="event-stats">
                            <div class="event-stat">
                                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                                <div class="stat-value"><?php echo $statsEvenement['invites']; ?></div>
                                <div class="stat-label">Invités</div>
                            </div>
                            <div class="event-stat">
                                <div class="stat-icon"><i class="bi bi-envelope-fill"></i></div>
                                <div class="stat-value"><?php echo $statsEvenement['invitations']; ?></div>
                                <div class="stat-label">Invitations</div>
                            </div>
                            <div class="stat-icon-wrapper event-stat">
                                <div class="stat-icon"><i class="bi bi-cup-straw"></i></div>
                                <div class="stat-value"><?php echo $statsEvenement['boissons']; ?></div>
                                <div class="stat-label">Boissons</div>
                            </div>
                            <div class="event-stat">
                                <div class="stat-icon"><i class="bi bi-heart-fill"></i></div>
                                <div class="stat-value"><?php echo $nbPreferences; ?></div>
                                <div class="stat-label">Préférences</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ⭐ ÉTAPE 2 : ACTIONS (Export PDF / Préférences par table) -->
                <?php if ($evenementSelectionne): ?>
                    <div class="user-info-banner fade-in" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(52, 211, 153, 0.08)); border-color: rgba(16, 185, 129, 0.2);">
                        <i class="bi bi-lightning-charge-fill" style="color: #10b981;"></i>
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 15px; flex-wrap: wrap;">
                            <div>
                                <strong style="color: #065f46;">Étape 2 :</strong> 
                                Consultez ou exportez les données de <strong style="color: #10b981;"><?php echo htmlspecialchars($evenementSelectionne['nom']); ?></strong>
                            </div>
                            <div class="header-actions">
                                <a href="export_pdf.php?evenement=<?php echo $evenementSelectionneId; ?>" 
                                   class="btn-export" target="_blank"
                                   title="Exporter la liste des boissons en PDF">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
                                </a>
                                <a href="table_preference.php?evenement=<?php echo $evenementSelectionneId; ?>" 
                                   class="btn-tables"
                                   title="Voir les préférences par table">
                                    <i class="bi bi-table"></i> Préférences par table
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ⭐ ÉTAPE 3 : LISTE DES BOISSONS -->
               
                <div class="table-container fade-in">

                    <div class="table-header">
                        <h5>
                            <i class="bi bi-cup-straw"></i> 
                            Boissons de <span style="color:#c17c60;"><?php echo htmlspecialchars($evenementSelectionne['nom']); ?></span>
                        </h5>
                        
                        <?php if ($isAdminUser && hasPermission('boissons.creer')): ?>
                            <a href="creer.php" class="btn-add">
                                <i class="bi bi-plus-circle-fill"></i> Créer une boisson
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="" class="filters-bar">
                        <input type="hidden" name="evenement" value="<?php echo $evenementSelectionneId; ?>">
                        <div class="filter-group">
                            <label><i class="bi bi-search"></i> Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom..." style="min-width: 150px;">
                        </div>
                        <div class="filter-group">
                            <label><i class="bi bi-tag"></i> Type</label>
                            <select name="type">
                                <option value="">Tous</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $filtre_type == $t ? 'selected' : ''; ?>>
                                        <?php echo $typeLabels[$t] ?? $t; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                        <a href="index.php?evenement=<?php echo $evenementSelectionneId; ?>" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                    </form>

                    <?php if (!empty($boissons)): ?>
                        <?php foreach ($boissons as $b): ?>
                            <div class="boisson-card">
                                <div class="boisson-info">
                                    <div class="name">
                                        <?php echo htmlspecialchars($b['nom']); ?>
                                        <span class="badge-type <?php echo strtolower(str_replace('_', '', $b['type'])); ?>">
                                            <?php echo $typeLabels[$b['type']] ?? $b['type']; ?>
                                        </span>
                                    </div>
                                    <div class="desc"><?php echo htmlspecialchars($b['description'] ?? 'Aucune description'); ?></div>
                                </div>
                                <div class="boisson-stats">
                                    <div class="stat">
                                        <i class="bi bi-person"></i>
                                        <?php echo $b['nb_preferences']; ?> choix
                                    </div>
                                    <div class="stat">
                                        <i class="bi <?php echo $b['actif'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>" style="color: <?php echo $b['actif'] ? '#10b981' : '#dc2626'; ?>;"></i>
                                        <?php echo $b['actif'] ? 'Actif' : 'Inactif'; ?>
                                    </div>
                                </div>
                                <div class="actions">
                                    <?php if ($isAdminUser): ?>
                                        <?php if (hasPermission('boissons.modifier')): ?>
                                            <a href="associer.php?id=<?php echo $b['id']; ?>" 
                                               class="btn-action associer" title="Associer aux événements">
                                                <i class="bi bi-link"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('boissons.modifier')): ?>
                                            <a href="modifier.php?id=<?php echo $b['id']; ?>" 
                                               class="btn-action modifier" title="Modifier">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('boissons.supprimer')): ?>
                                            <a href="supprimer.php?id=<?php echo $b['id']; ?>" 
                                               class="btn-action supprimer" title="Supprimer"
                                               onclick="return confirm('Voulez-vous vraiment supprimer cette boisson ?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="btn-action voir" title="Consultation uniquement">
                                            <i class="bi bi-eye-fill"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-custom">
                                <div class="info">
                                    Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> boissons
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
                            <i class="bi bi-cup-straw"></i>
                            <h5>Aucune boisson pour cet événement</h5>
                            <p>
                                <?php if ($isAdminUser): ?>
                                    Ajoutez des boissons à cet événement pour qu'elles apparaissent ici.
                                <?php else: ?>
                                    Cet événement n'a pas encore de boissons associées.
                                <?php endif; ?>
                            </p>
                            <?php if ($isAdminUser && hasPermission('boissons.modifier')): ?>
                                <a href="index.php" class="btn-add" style="margin-top: 15px; display: inline-flex;">
                                    <i class="bi bi-link"></i> Voir toutes les boissons du catalogue
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

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

// ========== CHANGEMENT D'ÉVÉNEMENT ==========
function changeEvenement(eventId) {
    if (eventId && eventId > 0) {
        // Rediriger vers la même page avec le nouvel événement
        window.location.href = 'index.php?evenement=' + encodeURIComponent(eventId);
    }
}

// ========== AUTO-HIDE ALERT ==========
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