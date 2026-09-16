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
requirePermission('tables.voir');

// Récupérer les informations de l'utilisateur courant
$user   = getCurrentUser();
$userId = (int)getCurrentUserId();

// Connexion à la base
$pdo = getDbConnection();

$evenement_id   = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$table_id_cible = isset($_GET['table'])     ? (int)$_GET['table']     : 0;

// ============================================
// RÉCUPÉRATION DE L'ÉVÉNEMENT
// ============================================

$evenement = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, date_evenement, lieu FROM evenements WHERE id = ? AND statut != 'ANNULE'");
    $stmt->execute([$evenement_id]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération événement (plan) : ' . $e->getMessage());
}

if (!$evenement) {
    header('Location: index.php');
    exit;
}

// ⭐ Vérifier l'accès à l'événement — redirection vers 403.php
if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, $evenement_id)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// ============================================
// ⭐ AJAX : MISE À JOUR DE POSITION (sécurisée)
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_position') {
    header('Content-Type: application/json; charset=utf-8');

    $table_id   = (int)($_POST['table_id']   ?? 0);
    $position_x = (int)($_POST['position_x'] ?? 0);
    $position_y = (int)($_POST['position_y'] ?? 0);

    // Bornes raisonnables
    $position_x = max(0, min($position_x, 10000));
    $position_y = max(0, min($position_y, 10000));

    // ⭐ Vérifier que la table appartient bien à cet événement
    try {
        $checkStmt = $pdo->prepare("SELECT id FROM tables WHERE id = ? AND id_evenement = ?");
        $checkStmt->execute([$table_id, $evenement_id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Table introuvable pour cet événement.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tables SET position_x = ?, position_y = ? WHERE id = ? AND id_evenement = ?");
        $stmt->execute([$position_x, $position_y, $table_id, $evenement_id]);

        if (function_exists('logAction')) {
            logAction(
                $userId,
                'UPDATE_TABLE_POSITION',
                'tables',
                "Mise à jour position table ID $table_id vers ($position_x, $position_y)"
            );
        }

        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        error_log('Erreur update position : ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
        exit;
    }
}

// ============================================
// RÉCUPÉRATION DES TABLES
// ============================================

$tables      = [];
$totalPlaces = 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            COALESCE(SUM(inv.nombre_personnes), 0) AS nb_personnes,
            GROUP_CONCAT(DISTINCT CONCAT(inv.prenom, ' ', inv.nom) SEPARATOR ', ') AS invites_noms
        FROM tables t
        LEFT JOIN invitations_tables it ON t.id = it.id_table
        LEFT JOIN invitations i ON it.id_invitation = i.id
        LEFT JOIN invites inv ON i.id_invite = inv.id
        WHERE t.id_evenement = ?
        GROUP BY t.id
        ORDER BY t.zone ASC, t.numero ASC, t.nom ASC
    ");
    $stmt->execute([$evenement_id]);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPlaces = array_sum(array_column($tables, 'capacite_max'));
} catch (PDOException $e) {
    error_log('Erreur chargement tables (plan) : ' . $e->getMessage());
}

// ============================================
// LIBELLÉS ET COULEURS
// ============================================

$zoneLabels = [
    'TERRASSE'         => 'Terrasse 🌿',
    'SALLE_PRINCIPALE' => 'Salle principale 🏠',
    'SALON'            => 'Salon 🛋️',
    'MEZZANINE'        => 'Mezzanine 🏗️',
    'VIP'              => 'VIP ⭐',
    'EXTERIEUR'        => 'Extérieur 🌳',
];

$typeColors = [
    'RONDE'     => '#c17c60',
    'CARREE'    => '#d4a574',
    'RECTANGLE' => '#e8c9a8',
    'OVALE'     => '#a18cd1',
    'BARRIERE'  => '#ff6b6b',
];

// Calculer les dimensions du plan
$maxX = 0;
$maxY = 0;
foreach ($tables as $t) {
    $px = (int)($t['position_x'] ?? 0);
    $py = (int)($t['position_y'] ?? 0);
    if ($px > $maxX) $maxX = $px;
    if ($py > $maxY) $maxY = $py;
}
$planWidth  = max(800,  $maxX + 300);
$planHeight = max(600,  $maxY + 300);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Plan de salle - <?php echo APP_NAME; ?></title>
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

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1040;
        }
        .sidebar-overlay.active { display: block; }

        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px; left: 15px;
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

        /* ========== MAIN CONTENT ========== */
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

        .content-section { padding: 25px 30px; }

        .plan-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .plan-container .plan-title {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .plan-container .plan-title i { color: #c17c60; margin-right: 10px; }

        .plan-canvas {
            position: relative;
            width: 100%;
            min-height: 600px;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(193,124,96,0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212,165,116,0.03) 0%, transparent 50%);
            border-radius: 12px;
            border: 1px dashed rgba(234, 227, 220, 0.6);
            overflow: auto;
            cursor: grab;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.5);
            touch-action: pan-x pan-y;
        }
        .plan-canvas:active { cursor: grabbing; }
        .plan-canvas .grid {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            background-image: 
                linear-gradient(rgba(193,124,96,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(193,124,96,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .plan-canvas .grid .origin {
            position: absolute;
            top: 30px; left: 30px;
            font-size: 10px;
            color: #d4c5b2;
            pointer-events: none;
        }

        .table-item {
            position: absolute;
            border-radius: 12px;
            padding: 10px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: grab;
            transition: all 0.15s ease;
            border: 2px solid rgba(234, 227, 220, 0.6);
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            min-width: 80px;
            min-height: 60px;
            z-index: 10;
            user-select: none;
            -webkit-user-select: none;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            touch-action: none;
            -webkit-touch-callout: none;
        }
        .table-item:active { cursor: grabbing; }
        .table-item:hover {
            z-index: 20;
            box-shadow: 0 8px 30px rgba(193, 124, 96, 0.2);
            border-color: #c17c60;
        }
        .table-item.dragging {
            z-index: 30;
            box-shadow: 0 15px 50px rgba(193, 124, 96, 0.3);
            transform: scale(1.05);
            opacity: 0.9;
        }
        .table-item .table-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 13px;
            pointer-events: none;
        }
        .table-item .table-capacity {
            font-size: 11px;
            color: #9a8a7f;
            pointer-events: none;
        }
        .table-item .table-invites {
            font-size: 9px;
            color: #6a5a4a;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-top: 2px;
            pointer-events: none;
        }
        .table-item .occupation-bar {
            width: 100%;
            height: 3px;
            background: rgba(240, 240, 240, 0.6);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 4px;
            pointer-events: none;
        }
        .table-item .occupation-bar .fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.6s ease;
        }
        .table-item .drag-handle {
            position: absolute;
            bottom: -8px; right: -8px;
            width: 20px; height: 20px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            box-shadow: 0 2px 8px rgba(193, 124, 96, 0.3);
        }
        .table-item:hover .drag-handle { opacity: 1; }
        .table-item .table-id {
            position: absolute;
            top: -8px; left: -8px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            opacity: 0.9;
            pointer-events: none;
        }

        .table-item.type-ronde { border-radius: 50%; width: 90px; height: 90px; }
        .table-item.type-carree { border-radius: 8px; width: 90px; height: 90px; }
        .table-item.type-rectangulaire { border-radius: 8px; width: 120px; height: 70px; }
        .table-item.type-ovale { border-radius: 50%; width: 110px; height: 70px; }
        .table-item.type-barriere { border-radius: 4px; width: 140px; height: 50px; }

        /* ========== RECHERCHE INVITÉS ========== */
        .guest-search {
            position: relative;
            min-width: 280px;
            max-width: 380px;
            flex: 1;
        }
        .guest-search .input-group-text {
            background: rgba(255, 255, 255, 0.9);
            border-right: none;
            color: #c17c60;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 10px 0 0 10px;
        }
        .guest-search input {
            border-left: none;
            border-right: none;
            background: rgba(255, 255, 255, 0.9);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-family: 'Inter', sans-serif;
        }
        .guest-search input:focus {
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
            background: white;
        }
        .guest-search .btn {
            background: rgba(255, 255, 255, 0.9);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 0 10px 10px 0;
            color: #9a8a7f;
        }
        .guest-search .btn:hover { color: #c17c60; }

        .guest-search-results {
            position: absolute;
            top: calc(100% + 5px);
            left: 0; right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            display: none;
            overflow: hidden;
            border: 1px solid rgba(234, 227, 220, 0.6);
        }
        .guest-search-results.show { display: block; }
        .guest-result {
            width: 100%;
            padding: 10px 14px;
            border: none;
            border-bottom: 1px solid rgba(234, 227, 220, 0.3);
            background: transparent;
            text-align: left;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .guest-result:hover { background: rgba(193, 124, 96, 0.06); }
        .guest-result strong {
            display: block;
            color: #1a1a1a;
            font-size: 13px;
        }
        .guest-result small {
            color: #9a8a7f;
            font-size: 11px;
        }

        .table-item.guest-match {
            animation: guestPulse 1s infinite;
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 5px rgba(220, 53, 69, 0.2),
                        0 10px 35px rgba(220, 53, 69, 0.35);
            z-index: 50;
        }
        .table-item.guest-dimmed {
            opacity: 0.25;
            filter: grayscale(0.7);
        }
        .table-item.target-table {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.3),
                        0 10px 35px rgba(16, 185, 129, 0.25) !important;
            z-index: 50;
            animation: targetPulse 1.5s ease-in-out 3;
        }

        @keyframes targetPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 15px rgba(16, 185, 129, 0.1);
            }
        }
        @keyframes guestPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(251, 248, 245, 0.7);
            border-radius: 12px;
            align-items: center;
            border: 1px solid rgba(234, 227, 220, 0.5);
        }
        .controls .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 18px;
            font-family: 'Inter', sans-serif;
        }
        .controls .btn-save-positions {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        }
        .controls .btn-save-positions:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .controls .btn-reset-positions {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
        }
        .controls .btn-reset-positions:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #c17c60;
        }
        .controls .btn-zoom-in, .controls .btn-zoom-out {
            background: rgba(255, 255, 255, 0.8);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            color: #6a5a4a;
            padding: 8px 15px;
        }
        .controls .btn-zoom-in:hover, .controls .btn-zoom-out:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #c17c60;
        }
        .controls .coords-info {
            font-size: 13px;
            color: #9a8a7f;
            margin-left: auto;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px 20px;
            background: rgba(251, 248, 245, 0.7);
            border-radius: 12px;
            margin-top: 15px;
            border: 1px solid rgba(234, 227, 220, 0.5);
        }
        .legend .item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #6a5a4a;
        }
        .legend .item .dot {
            width: 20px; height: 20px;
            border-radius: 4px;
        }

        /* ========== BOUTONS ========== */
        .btn-back, .btn-export, .btn-excel, .btn-print {
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
        }
        .btn-back {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
        }
        .btn-back:hover { background: rgba(255, 255, 255, 0.95); color: #c17c60; }
        .btn-export {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .btn-excel {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
        }
        .btn-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
            color: white;
        }
        .btn-print {
            background: linear-gradient(135deg, #6a5a4a, #8a7a6a);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(106, 90, 74, 0.25);
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(106, 90, 74, 0.35);
            color: white;
        }

        /* ========== ANIMATIONS ========== */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ========== TOAST ========== */
        .toast-notification {
            position: fixed;
            bottom: 30px; right: 30px;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 90%;
            font-family: 'Inter', sans-serif;
        }
        .toast-notification.show { opacity: 1; transform: translateY(0); }
        .toast-notification.success { background: rgba(16, 185, 129, 0.95); }
        .toast-notification.error   { background: rgba(239, 68, 68, 0.95); }

        /* ========== FOOTER ========== */
        .app-footer { text-align: center; padding: 30px 0 20px; color: #b8a99c; font-size: 13px; }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== MOTION REDUCE ========== */
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
                left: 0; top: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                flex-shrink: 0;
            }
            .sidebar-wrapper.open { transform: translateX(0); }
            .sidebar-toggle-btn { display: flex; align-items: center; justify-content: center; }
            .main-content { height: auto; min-height: 100vh; }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px 15px 75px;
            }
            .top-bar .user-info { width: 100%; justify-content: space-between; flex-wrap: wrap; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 15px 20px; }
            .plan-container { padding: 15px; }
            .plan-container .plan-title { flex-direction: column; align-items: flex-start; }
            .plan-container .plan-title .float-end { width: 100%; display: flex; flex-wrap: wrap; gap: 8px; }
            .plan-container .plan-title .float-end .btn,
            .plan-container .plan-title .float-end a {
                flex: 1; min-width: 80px; text-align: center;
                font-size: 12px; padding: 6px 12px;
            }
            .controls { flex-wrap: wrap; justify-content: center; }
            .controls .coords-info { margin-left: 0; width: 100%; text-align: center; font-size: 12px; }
            .controls .btn { font-size: 12px; padding: 6px 14px; }
            .guest-search { min-width: 100%; max-width: 100%; order: -1; }
            .table-item { min-width: 60px; min-height: 50px; padding: 8px 10px; }
            .table-item .table-name { font-size: 11px; }
            .table-item .table-capacity { font-size: 10px; }
            .table-item .table-invites { font-size: 8px; max-width: 80px; }
            .table-item.type-ronde { width: 70px; height: 70px; }
            .table-item.type-carree { width: 70px; height: 70px; }
            .table-item.type-rectangulaire { width: 90px; height: 60px; }
            .table-item.type-ovale { width: 80px; height: 60px; }
            .table-item.type-barriere { width: 100px; height: 45px; }
            .legend { gap: 10px; padding: 12px 15px; }
            .legend .item { font-size: 11px; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px 12px 65px; }
            .top-bar .page-title h4 { font-size: 18px; }
            .top-bar .page-title small { font-size: 11px; }
            .top-bar .user-info .role-badge { font-size: 10px; padding: 3px 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 15px; }
            .sidebar-toggle-btn { top: 12px; left: 12px; padding: 8px 12px; font-size: 18px; }
            .plan-container { padding: 10px; border-radius: 14px; }
            .plan-container .plan-title { font-size: 14px; }
            .plan-container .plan-title .float-end .btn,
            .plan-container .plan-title .float-end a {
                font-size: 11px; padding: 4px 10px; min-width: 60px;
            }
            .plan-canvas { min-height: 400px; }
            .controls { padding: 10px; gap: 6px; }
            .controls .btn { font-size: 11px; padding: 5px 10px; }
            .controls .coords-info { font-size: 11px; }
            .table-item { min-width: 50px; min-height: 40px; padding: 6px 8px; }
            .table-item .table-name { font-size: 9px; }
            .table-item .table-capacity { font-size: 8px; }
            .table-item .table-invites { font-size: 7px; max-width: 60px; }
            .table-item .table-id { font-size: 7px; padding: 1px 6px; top: -6px; left: -6px; }
            .table-item .drag-handle { width: 16px; height: 16px; font-size: 8px; bottom: -6px; right: -6px; }
            .table-item.type-ronde { width: 60px; height: 60px; }
            .table-item.type-carree { width: 60px; height: 60px; }
            .table-item.type-rectangulaire { width: 75px; height: 50px; }
            .table-item.type-ovale { width: 70px; height: 50px; }
            .table-item.type-barriere { width: 85px; height: 40px; }
            .legend { gap: 8px; padding: 10px 12px; flex-wrap: wrap; }
            .legend .item { font-size: 10px; }
            .legend .item .dot { width: 15px; height: 15px; }
            .toast-notification {
                font-size: 12px; padding: 10px 18px;
                bottom: 15px; right: 15px; left: 15px;
                max-width: 100%;
            }
            .app-footer { font-size: 11px; padding: 20px 0 15px; }
        }

        @media (max-width: 400px) {
            .plan-container .plan-title .float-end .btn,
            .plan-container .plan-title .float-end a {
                font-size: 10px; padding: 3px 8px; min-width: 50px;
            }
            .table-item.type-ronde { width: 50px; height: 50px; }
            .table-item.type-carree { width: 50px; height: 50px; }
            .table-item.type-rectangulaire { width: 65px; height: 45px; }
            .table-item.type-ovale { width: 60px; height: 45px; }
            .table-item.type-barriere { width: 75px; height: 35px; }
            .table-item .table-name { font-size: 8px; }
            .table-item .table-capacity { font-size: 7px; }
        }

        /* ========== IMPRESSION ========== */
        @media print {
            .sidebar-wrapper, .sidebar-overlay, .sidebar-toggle-btn,
            .controls, .drag-handle, .table-item .drag-handle, .legend .ms-auto,
            .guest-search, .guest-search-results {
                display: none !important;
            }
            .table-item { cursor: default !important; }
            .top-bar { display: none !important; }
            .main-content { height: auto !important; }
            .plan-canvas { min-height: 500px !important; border: 1px solid #ddd !important; }
            .app-container { display: block !important; }
            .content-section { padding: 10px !important; }
            .plan-container { box-shadow: none !important; border: 1px solid #ddd !important; }
            .plan-container .plan-title .float-end .btn { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu">
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
                <h4><i class="bi bi-grid-3x3-gap-fill"></i> Plan de salle</h4>
                <small><i class="bi bi-table"></i> <?php echo htmlspecialchars($evenement['nom']); ?></small>
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
            <div class="plan-container fade-in">

                <div class="plan-title">
                    <div>
                        <i class="bi bi-grid-3x3-gap-fill"></i> Plan de salle
                        <span class="text-muted small ms-3 d-none d-md-inline">
                            <?php echo count($tables); ?> table(s) • 
                            <?php echo (int)$totalPlaces; ?> places
                            <span class="ms-2" style="color: #c17c60;">
                                <i class="bi bi-arrows-move"></i> Glissez-déposez les tables
                            </span>
                        </span>
                    </div>
                    <div class="float-end d-flex gap-2 flex-wrap">
                        <a href="export_tables_pdf.php?evenement=<?php echo $evenement_id; ?>" class="btn-export" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                        
                        <button onclick="window.print()" class="btn-print">
                            <i class="bi bi-printer-fill"></i> Imprimer
                        </button>
                        <a href="index.php?evenement=<?php echo $evenement_id; ?>" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <!-- Contrôles -->
                <div class="controls">
                    <div class="guest-search">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="text"
                                id="guestSearch"
                                class="form-control"
                                placeholder="Rechercher un invité..."
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="clearGuestSearch"
                                title="Effacer la recherche"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div id="guestSearchResults" class="guest-search-results"></div>
                    </div>

                    <button class="btn btn-save-positions" id="savePositions">
                        <i class="bi bi-save-fill"></i> Sauvegarder
                    </button>
                    <button class="btn btn-reset-positions" id="resetPositions" onclick="if(confirm('Réinitialiser toutes les positions ?')) resetPositions()">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </button>
                    <button class="btn btn-zoom-in" id="zoomIn">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <button class="btn btn-zoom-out" id="zoomOut">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <span class="coords-info" id="coordsInfo">
                        <i class="bi bi-info-circle"></i> Cliquez sur une table
                    </span>
                </div>

                <!-- Plan -->
                <div class="plan-canvas" id="planCanvas" style="height: <?php echo $planHeight + 100; ?>px;">
                    <div class="grid">
                        <div class="origin">(0,0)</div>
                    </div>

                    <?php foreach ($tables as $t):
                        $nb_personnes  = (int)($t['nb_personnes'] ?? 0);
                        $capacite      = (int)($t['capacite_max'] ?? 4);
                        $pourcentage   = round(($nb_personnes / max(1, $capacite)) * 100);
                        $typeClass     = 'type-' . strtolower($t['type'] ?? 'rectangulaire');
                        $color         = $typeColors[$t['type']] ?? '#c17c60';
                        $isFull        = $nb_personnes >= $capacite;
                        $posX          = (int)($t['position_x'] ?? 50);
                        $posY          = (int)($t['position_y'] ?? 50);
                        $invitesRecherche = $t['invites_noms'] ?? '';
                        $isTarget      = ($table_id_cible > 0 && $t['id'] == $table_id_cible);
                    ?>
                        <div class="table-item <?php echo $typeClass; ?><?php echo $isTarget ? ' target-table' : ''; ?>"
                             data-id="<?php echo (int)$t['id']; ?>"
                             data-name="<?php echo htmlspecialchars($t['nom'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-guests="<?php echo htmlspecialchars($invitesRecherche, ENT_QUOTES, 'UTF-8'); ?>"
                             data-x="<?php echo $posX; ?>"
                             data-y="<?php echo $posY; ?>"
                             style="left: <?php echo $posX; ?>px; top: <?php echo $posY; ?>px; background: <?php echo $color; ?>15; border-color: <?php echo $isFull ? '#10b981' : $color; ?>40;"
                             title="<?php echo htmlspecialchars($t['nom']); ?> - <?php echo $nb_personnes; ?>/<?php echo $capacite; ?> personnes">
                            <span class="table-id">#<?php echo (int)$t['id']; ?></span>
                            <div class="table-name"><?php echo htmlspecialchars($t['nom']); ?></div>
                            <div class="table-capacity">
                                <i class="bi bi-people-fill"></i> <?php echo $nb_personnes; ?>/<?php echo $capacite; ?>
                            </div>
                            <div class="occupation-bar">
                                <div class="fill" style="width: <?php echo $pourcentage; ?>%; background: <?php echo $isFull ? '#10b981' : $color; ?>;"></div>
                            </div>
                            <?php if (!empty($t['invites_noms'])): ?>
                                <div class="table-invites" title="<?php echo htmlspecialchars($t['invites_noms']); ?>">
                                    <?php
                                    $invites = explode(', ', $t['invites_noms']);
                                    echo htmlspecialchars(implode(', ', array_slice($invites, 0, 2)));
                                    if (count($invites) > 2) echo ' +' . (count($invites) - 2);
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="drag-handle">
                                <i class="bi bi-arrows-move"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Légende -->
                <div class="legend">
                    <div class="item">
                        <span class="dot" style="background: rgba(193,124,96,0.15); border: 2px solid rgba(193,124,96,0.4);"></span>
                        Table disponible
                    </div>
                    <div class="item">
                        <span class="dot" style="background: rgba(16,185,129,0.15); border: 2px solid #10b981;"></span>
                        Table pleine
                    </div>
                    <?php foreach ($typeColors as $type => $color): ?>
                        <div class="item">
                            <span class="dot" style="background: <?php echo $color; ?>20; border: 2px solid <?php echo $color; ?>40;"></span>
                            <?php echo ucfirst($type); ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="item ms-auto">
                        <i class="bi bi-arrows-move" style="color: #c17c60;"></i> Glissez pour déplacer
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

<div class="toast-notification" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
// SIDEBAR MOBILE
// ============================================================
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

// ============================================================
// DRAG & DROP + RECHERCHE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const planCanvas = document.getElementById('planCanvas');
    let dragData   = null;
    let isDragging = false;
    let offsetX    = 0;
    let offsetY    = 0;
    let zoomLevel  = 1;
    let hasChanges = false;

    // ---------- RECHERCHE INVITÉS ----------
    const guestSearch        = document.getElementById('guestSearch');
    const clearGuestSearch   = document.getElementById('clearGuestSearch');
    const guestSearchResults = document.getElementById('guestSearchResults');

    function normalizeText(text) {
        return (text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function clearGuestResults() {
        document.querySelectorAll('.table-item').forEach(table => {
            table.classList.remove('guest-match', 'guest-dimmed');
        });
        guestSearchResults.innerHTML = '';
        guestSearchResults.classList.remove('show');
    }

    function focusTable(table) {
        document.querySelectorAll('.table-item').forEach(item => {
            item.classList.remove('guest-match', 'guest-dimmed');
        });
        table.classList.add('guest-match');
        table.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

        const tableName = table.dataset.name;
        const guests    = table.dataset.guests;
        document.getElementById('coordsInfo').innerHTML =
            `<i class="bi bi-geo-alt-fill" style="color: #dc3545;"></i> ${escapeHtml(tableName)} — ${escapeHtml(guests)}`;
        guestSearchResults.classList.remove('show');
    }

    function searchGuest() {
        const searchValue = normalizeText(guestSearch.value.trim());
        const tables = Array.from(document.querySelectorAll('.table-item'));

        if (searchValue.length < 2) {
            clearGuestResults();
            return;
        }

        const matches = tables.filter(table => {
            const guests = normalizeText(table.dataset.guests);
            return guests.includes(searchValue);
        });

        tables.forEach(table => {
            table.classList.remove('guest-match', 'guest-dimmed');
            if (matches.length > 0 && !matches.includes(table)) {
                table.classList.add('guest-dimmed');
            }
        });

        guestSearchResults.innerHTML = '';

        if (matches.length === 0) {
            guestSearchResults.innerHTML = `
                <div class="p-3 text-muted small">
                    <i class="bi bi-exclamation-circle"></i>
                    Aucun invité trouvé
                </div>
            `;
            guestSearchResults.classList.add('show');
            return;
        }

        matches.forEach(table => {
            const result = document.createElement('button');
            result.type = 'button';
            result.className = 'guest-result';
            result.innerHTML = `
                <strong>
                    <i class="bi bi-person-fill"></i>
                    ${escapeHtml(table.dataset.guests)}
                </strong>
                <small>
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    Table : ${escapeHtml(table.dataset.name)}
                </small>
            `;
            result.addEventListener('click', () => focusTable(table));
            guestSearchResults.appendChild(result);
        });

        guestSearchResults.classList.add('show');

        if (matches.length === 1) {
            matches[0].classList.add('guest-match');
            matches[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-geo-alt-fill" style="color: #dc3545;"></i> Invité trouvé à la table ${escapeHtml(matches[0].dataset.name)}`;
        } else {
            matches.forEach(table => table.classList.add('guest-match'));
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-search"></i> ${matches.length} table(s) trouvée(s)`;
        }
    }

    if (guestSearch) {
        guestSearch.addEventListener('input', searchGuest);
        guestSearch.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                guestSearch.value = '';
                clearGuestResults();
            }
        });
    }
    if (clearGuestSearch) {
        clearGuestSearch.addEventListener('click', () => {
            guestSearch.value = '';
            clearGuestResults();
            guestSearch.focus();
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-info-circle"></i> Cliquez sur une table`;
        });
    }
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.guest-search')) {
            guestSearchResults.classList.remove('show');
        }
    });

    // ---------- TOAST ----------
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast-notification ' + type + ' show';
        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ============================================================
    // ---------- DRAG (SOURIS + TACTILE) ----------
    // ============================================================

    function startDrag(clientX, clientY, target) {
        const rect = target.getBoundingClientRect();

        dragData   = target;
        isDragging = false;

        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;

        target.classList.add('dragging');
        target.style.zIndex = 30;
    }

    function moveDrag(clientX, clientY) {
        if (!dragData) return;

        isDragging = true;

        const planRect = planCanvas.getBoundingClientRect();

        let newX = clientX - planRect.left - offsetX;
        let newY = clientY - planRect.top  - offsetY;

        newX = Math.max(0, Math.min(newX, planCanvas.clientWidth  - 80));
        newY = Math.max(0, Math.min(newY, planCanvas.clientHeight - 60));

        dragData.style.left = newX + 'px';
        dragData.style.top  = newY + 'px';
        dragData.dataset.x  = Math.round(newX);
        dragData.dataset.y  = Math.round(newY);

        hasChanges = true;

        document.getElementById('coordsInfo').innerHTML =
            `<i class="bi bi-arrows-move"></i> Déplacement... (${Math.round(newX)}, ${Math.round(newY)})`;
    }

    function endDrag() {
        if (dragData) {
            dragData.classList.remove('dragging');
            dragData.style.zIndex = 10;

            const x = parseInt(dragData.dataset.x) || 0;
            const y = parseInt(dragData.dataset.y) || 0;

            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-info-circle"></i> Table #${dragData.dataset.id} : (${x}, ${y})`;

            if (hasChanges) {
                clearTimeout(window.autoSaveTimeout);
                window.autoSaveTimeout = setTimeout(saveAllPositions, 2000);
            }
        }

        dragData   = null;
        isDragging = false;
    }

    // Attacher les listeners à chaque table
    document.querySelectorAll('.table-item').forEach(item => {

        // ---- SOURIS ----
        item.addEventListener('mousedown', function(e) {
            if (e.target.closest('.stretched-link')) return;
            startDrag(e.clientX, e.clientY, this);
            e.preventDefault();
        });

        // ---- TACTILE ----
        item.addEventListener('touchstart', function(e) {
            if (e.target.closest('.stretched-link')) return;
            if (e.touches.length !== 1) return;

            const touch = e.touches[0];
            startDrag(touch.clientX, touch.clientY, this);
        }, { passive: true });

        // ---- DOUBLE-CLIC ----
        item.addEventListener('dblclick', function() {
            const id = this.dataset.id;
            const x  = parseInt(this.dataset.x) || 0;
            const y  = parseInt(this.dataset.y) || 0;
            showToast(`Table #${id} : (${x}, ${y})`, 'success');
        });

        // ---- CLIC SIMPLE ----
        item.addEventListener('click', function() {
            if (isDragging) return;
            const id = this.dataset.id;
            const x  = parseInt(this.dataset.x) || 0;
            const y  = parseInt(this.dataset.y) || 0;
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-info-circle"></i> Table #${id} : position (${x}, ${y})`;
        });
    });

    // ---- ÉVÉNEMENTS GLOBAUX : SOURIS ----
    document.addEventListener('mousemove', function(e) {
        if (!dragData) return;
        moveDrag(e.clientX, e.clientY);
    });

    document.addEventListener('mouseup', function() {
        endDrag();
    });

    // ---- ÉVÉNEMENTS GLOBAUX : TACTILE ----
    document.addEventListener('touchmove', function(e) {
        if (!dragData) return;
        if (e.touches.length !== 1) return;

        const touch = e.touches[0];
        moveDrag(touch.clientX, touch.clientY);

        if (isDragging) {
            e.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('touchend', function() {
        endDrag();
    });

    document.addEventListener('touchcancel', function() {
        endDrag();
    });

    // ---------- SAUVEGARDE ----------
    function saveAllPositions() {
        const tables = document.querySelectorAll('.table-item');
        if (tables.length === 0) {
            showToast('Aucune table à sauvegarder', 'error');
            return;
        }

        const promises = [];
        tables.forEach(table => {
            const id = table.dataset.id;
            const x  = parseInt(table.dataset.x) || 0;
            const y  = parseInt(table.dataset.y) || 0;

            const formData = new FormData();
            formData.append('action', 'update_position');
            formData.append('table_id', id);
            formData.append('position_x', x);
            formData.append('position_y', y);

            promises.push(
                fetch('plan.php?evenement=<?php echo $evenement_id; ?>', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json())
            );
        });

        Promise.all(promises)
            .then(results => {
                const allSuccess = results.every(r => r && r.success);
                if (allSuccess) {
                    showToast('✅ Toutes les positions ont été sauvegardées !');
                    hasChanges = false;
                } else {
                    showToast('⚠️ Erreur lors de la sauvegarde', 'error');
                }
            })
            .catch(error => {
                showToast('❌ Erreur lors de la sauvegarde', 'error');
                console.error('Error saving positions:', error);
            });
    }

    document.getElementById('savePositions').addEventListener('click', saveAllPositions);

    // ---------- RÉINITIALISATION ----------
    window.resetPositions = function() {
        document.querySelectorAll('.table-item').forEach(table => {
            const defaultX = 50 + (Math.random() * 100);
            const defaultY = 50 + (Math.random() * 100);
            table.dataset.x = Math.round(defaultX);
            table.dataset.y = Math.round(defaultY);
            table.style.left = Math.round(defaultX) + 'px';
            table.style.top  = Math.round(defaultY) + 'px';
        });
        hasChanges = true;
        showToast('🔄 Positions réinitialisées. Sauvegardez pour appliquer.');
    };

    // ---------- ZOOM ----------
    document.getElementById('zoomIn').addEventListener('click', function() {
        zoomLevel = Math.min(2, zoomLevel + 0.1);
        planCanvas.style.transform = `scale(${zoomLevel})`;
        planCanvas.style.transformOrigin = 'top left';
        document.getElementById('coordsInfo').innerHTML =
            `<i class="bi bi-zoom-in"></i> Zoom: ${Math.round(zoomLevel * 100)}%`;
    });

    document.getElementById('zoomOut').addEventListener('click', function() {
        zoomLevel = Math.max(0.5, zoomLevel - 0.1);
        planCanvas.style.transform = `scale(${zoomLevel})`;
        planCanvas.style.transformOrigin = 'top left';
        document.getElementById('coordsInfo').innerHTML =
            `<i class="bi bi-zoom-out"></i> Zoom: ${Math.round(zoomLevel * 100)}%`;
    });

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '0') {
            e.preventDefault();
            zoomLevel = 1;
            planCanvas.style.transform = 'scale(1)';
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-zoom-reset"></i> Zoom: 100%`;
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveAllPositions();
        }
    });

    // ---------- TABLE CIBLE ----------
    <?php if ($table_id_cible > 0): ?>
    setTimeout(function() {
        const targetTable = document.querySelector('.table-item.target-table');
        if (targetTable) {
            targetTable.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

            const tableName = targetTable.dataset.name;
            const guests    = targetTable.dataset.guests || 'Aucun invité';
            document.getElementById('coordsInfo').innerHTML =
                `<i class="bi bi-geo-alt-fill" style="color: #10b981;"></i> Table ${escapeHtml(tableName)} — ${escapeHtml(guests)}`;

            setTimeout(() => {
                targetTable.classList.remove('target-table');
                targetTable.classList.add('guest-match');
                setTimeout(() => targetTable.classList.remove('guest-match'), 5000);
            }, 5000);
        }
    }, 800);
    <?php endif; ?>

    // ---------- CONFIRMATION QUITTER ----------
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = 'Des modifications non sauvegardées. Voulez-vous quitter ?';
        }
    });

    const firstTable = document.querySelector('.table-item');
    if (firstTable && !<?php echo $table_id_cible > 0 ? 'true' : 'false'; ?>) {
        setTimeout(() => firstTable.scrollIntoView({ behavior: 'smooth', block: 'center' }), 500);
    }
});
</script>
</body>
</html>