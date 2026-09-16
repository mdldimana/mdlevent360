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

// ============================================
// FILTRES
// ============================================

$filtre_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

// ============================================
// RÉCUPÉRATION DE L'ÉVÉNEMENT SÉLECTIONNÉ
// ============================================

$evenement = null;
if ($filtre_evenement > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, nom, date_evenement FROM evenements WHERE id = ? AND statut != 'ANNULE'");
        $stmt->execute([$filtre_evenement]);
        $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur récupération événement (tables/index) : ' . $e->getMessage());
    }
}

// ⭐ Vérifier l'accès à l'événement — redirection vers 403.php
if ($evenement && function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, $filtre_evenement)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// ============================================
// RÉCUPÉRATION DES TABLES
// ============================================

$tables = [];
$error  = null;

if ($evenement) {
    try {
        $sql = "
            SELECT 
                t.*,
                COUNT(DISTINCT it.id_invitation) AS nb_occupation,
                GROUP_CONCAT(DISTINCT inv.nom SEPARATOR '||') AS invites_noms,
                GROUP_CONCAT(DISTINCT inv.prenom SEPARATOR '||') AS invites_prenoms
            FROM tables t
            LEFT JOIN invitations_tables it ON t.id = it.id_table
            LEFT JOIN invitations i ON it.id_invitation = i.id
            LEFT JOIN invites inv ON i.id_invite = inv.id
            WHERE t.id_evenement = ?
            GROUP BY t.id
            ORDER BY t.zone ASC, t.numero ASC, t.nom ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$filtre_evenement]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur chargement tables : ' . $e->getMessage());
        $error = 'Erreur lors du chargement des tables.';
    }
}

// ============================================
// RÉCUPÉRATION DES ÉVÉNEMENTS ACCESSIBLES
// ============================================

$evenements = [];
if (function_exists('getEvenementsPourSelect')) {
    try {
        $evenements = getEvenementsPourSelect($pdo);
    } catch (Throwable $e) {
        error_log('Erreur getEvenementsPourSelect (tables/index) : ' . $e->getMessage());
    }
}

// ============================================
// STATISTIQUES
// ============================================

$stats = [
    'total'            => 0,
    'occupees'         => 0,
    'libres'           => 0,
    'capacite_totale'  => 0,
    'places_occupees'  => 0,
];

foreach ($tables as $t) {
    $stats['total']++;
    $stats['capacite_totale'] += (int)($t['capacite_max'] ?? 0);
    $nb_occupation = (int)($t['nb_occupation'] ?? 0);
    $stats['places_occupees'] += $nb_occupation;
    if ($nb_occupation > 0) {
        $stats['occupees']++;
    } else {
        $stats['libres']++;
    }
}

// ============================================
// ZONES
// ============================================

$zoneLabels = [
    'TERRASSE'         => 'Terrasse 🌿',
    'SALLE_PRINCIPALE' => 'Salle principale 🏠',
    'SALON'            => 'Salon 🛋️',
    'MEZZANINE'        => 'Mezzanine 🏗️',
    'VIP'              => 'VIP ⭐',
    'EXTERIEUR'        => 'Extérieur 🌳',
];

// ============================================
// MESSAGES DE SUCCÈS
// ============================================

$success = $_GET['success'] ?? '';
$messages = [
    'ajoute'   => 'Table ajoutée avec succès ! 🪑',
    'modifie'  => 'Table modifiée avec succès ! ✅',
    'supprime' => 'Table supprimée avec succès ! 🗑️',
    'assigne'  => 'Invités assignés avec succès ! 📋',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables - <?php echo APP_NAME; ?></title>
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

        /* ========== MESSAGES ========== */
        .alert-success-custom {
            background: rgba(16, 185, 129, 0.1);
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
        .alert-success-custom i { color: #10b981; font-size: 18px; }
        .alert-error-custom {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error-custom i { color: #dc2626; font-size: 18px; }

        /* ========== FILTRES ========== */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px 18px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            align-items: center;
        }
        .filters-bar .filter-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filters-bar .filter-group label {
            font-size: 12px;
            font-weight: 700;
            color: #6a5a4a;
            margin: 0;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .filters-bar .filter-group label i { color: #c17c60; margin-right: 4px; }
        .filters-bar .filter-group select {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-size: 13px;
            background: white;
            color: #1a1a1a;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .filters-bar .filter-group select:focus {
            border-color: #c17c60;
            outline: none;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        }

        /* ========== BOUTONS ========== */
        .btn-add {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .btn-add.secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            box-shadow: none;
        }
        .btn-add.secondary:hover {
            background: white;
            color: #c17c60;
            border-color: #c17c60;
        }

        /* ⭐ NOUVEAU BOUTON EXPORT PDF */
        .btn-export-pdf {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.25);
            cursor: pointer;
        }
        .btn-export-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
            color: white;
        }
        .btn-export-pdf i { font-size: 14px; }

        /* ========== STATS ========== */
        .stats-row { margin-bottom: 25px; }
        .stat-mini-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
        .stat-mini-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(193, 124, 96, 0.12);
            border-color: rgba(193, 124, 96, 0.2);
        }
        .stat-mini-card .icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white; flex-shrink: 0;
        }
        .stat-mini-card .icon.blue   { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-mini-card .icon.green  { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-mini-card .icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .stat-mini-card .icon.purple { background: linear-gradient(135deg, #a855f7, #d8b4fe); }
        .stat-mini-card .stat-number { font-size: 22px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-mini-card .stat-label { font-size: 12px; color: #9a8a7f; margin-top: 2px; }

        /* ========== TABLE CARDS ========== */
        .table-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
            position: relative;
            overflow: hidden;
        }
        .table-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px; height: 100%;
            background: linear-gradient(180deg, #c17c60, #d4a574);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .table-card:hover {
            box-shadow: 0 12px 40px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }
        .table-card:hover::before { opacity: 1; }

        .table-card .table-info { flex: 1; min-width: 180px; }
        .table-card .table-info .name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .table-card .table-info .details {
            font-size: 12px;
            color: #9a8a7f;
            margin-top: 4px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .table-card .table-info .details i { color: #c17c60; margin-right: 4px; }

        .table-card .table-occupation { text-align: center; min-width: 110px; }
        .table-card .table-occupation .occupation-text {
            font-size: 12px;
            color: #6a5a4a;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .table-card .table-occupation .occupation-bar {
            height: 6px;
            background: rgba(234, 227, 220, 0.5);
            border-radius: 10px;
            overflow: hidden;
        }
        .table-card .table-occupation .occupation-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #c17c60, #d4a574);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .table-card .invites {
            font-size: 12px;
            color: #9a8a7f;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-style: italic;
        }

        .table-card .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-action {
            padding: 7px 11px;
            border-radius: 10px;
            border: none;
            transition: all 0.25s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-action.assigner { background: rgba(16, 185, 129, 0.12); color: #065f46; }
        .btn-action.assigner:hover { background: rgba(16, 185, 129, 0.2); }
        .btn-action.modifier { background: rgba(245, 158, 11, 0.12); color: #92400e; }
        .btn-action.modifier:hover { background: rgba(245, 158, 11, 0.2); }
        .btn-action.supprimer { background: rgba(239, 68, 68, 0.12); color: #991b1b; }
        .btn-action.supprimer:hover { background: rgba(239, 68, 68, 0.2); }

        /* ========== BADGES ZONE ========== */
        .badge-zone {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-zone.terrasse         { background: rgba(16, 185, 129, 0.12); color: #065f46; }
        .badge-zone.salleprincipale  { background: rgba(59, 130, 246, 0.12); color: #1e40af; }
        .badge-zone.salon            { background: rgba(245, 158, 11, 0.12); color: #92400e; }
        .badge-zone.mezzanine        { background: rgba(107, 114, 128, 0.12); color: #374151; }
        .badge-zone.vip              { background: rgba(239, 68, 68, 0.12); color: #991b1b; }
        .badge-zone.exterieur        { background: rgba(16, 185, 129, 0.12); color: #065f46; }

        /* ========== EMPTY STATE ========== */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 60px; color: #d4c5b2; }
        .empty-state h5 { color: #6a5a4a; margin-top: 15px; font-weight: 700; }
        .empty-state p { color: #9a8a7f; }

        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* ========== FOOTER ========== */
        .app-footer { text-align: center; padding: 30px 0 20px; color: #b8a99c; font-size: 13px; }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== ANIMATIONS ========== */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

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
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filters-bar .filter-group { flex-wrap: wrap; }
            .filters-bar .filter-group select { flex: 1; min-width: 120px; }
            .filters-bar .btn-add,
            .filters-bar .btn-export-pdf { width: 100%; justify-content: center; }
            .table-card { flex-direction: column; align-items: stretch; text-align: center; }
            .table-card .table-info .name { justify-content: center; }
            .table-card .table-info .details { justify-content: center; }
            .table-card .invites { max-width: 100%; white-space: normal; }
            .table-card .actions { justify-content: center; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 12px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .table-container { padding: 15px; border-radius: 14px; }
            .stat-mini-card { padding: 12px 14px; gap: 10px; }
            .stat-mini-card .icon { width: 38px; height: 38px; font-size: 17px; }
            .stat-mini-card .stat-number { font-size: 18px; }
            .stat-mini-card .stat-label { font-size: 10px; }
            .table-card { padding: 12px; }
            .btn-action { padding: 6px 10px; font-size: 13px; }
            .btn-add, .btn-export-pdf { font-size: 12px; padding: 8px 14px; }
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
                <h4><i class="bi bi-table"></i> Gestion des tables</h4>
                <small><i class="bi bi-list-ul"></i> Placement des invités</small>
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

            <?php if ($success && isset($messages[$success])): ?>
                <div class="alert-success-custom fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $messages[$success]; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-error-custom fade-in">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- FILTRES / BOUTONS -->
            <div class="filters-bar fade-in">
                <div class="filter-group">
                    <label><i class="bi bi-calendar-event-fill"></i> Événement</label>
                    <select id="selectEvenement" style="min-width: 220px;">
                        <option value="0">-- Sélectionnez un événement --</option>
                        <?php foreach ($evenements as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php echo $filtre_evenement == $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($filtre_evenement > 0 && $evenement): ?>

                    <?php if (hasPermission('tables.creer')): ?>
                        <a href="creer.php?evenement=<?php echo $filtre_evenement; ?>" class="btn-add">
                            <i class="bi bi-plus-circle-fill"></i> Ajouter une table
                        </a>
                    <?php endif; ?>

                    <a href="plan.php?evenement=<?php echo $filtre_evenement; ?>" class="btn-add secondary">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Plan de salle
                    </a>

                    <!-- ⭐ NOUVEAU BOUTON EXPORT PDF -->
                    <a href="export_tables_pdf.php?evenement=<?php echo $filtre_evenement; ?>"
                       class="btn-export-pdf"
                       target="_blank"
                       rel="noopener">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        Exporter en PDF
                    </a>

                <?php endif; ?>
            </div>

            <?php if ($filtre_evenement > 0 && $evenement): ?>

                <!-- STATS -->
                <div class="stats-row fade-in">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon blue"><i class="bi bi-table"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats['total']; ?></div>
                                    <div class="stat-label">Total tables</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon green"><i class="bi bi-person-check-fill"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats['occupees']; ?></div>
                                    <div class="stat-label">Tables occupées</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon orange"><i class="bi bi-person-x-fill"></i></div>
                                <div>
                                    <div class="stat-number"><?php echo (int)$stats['libres']; ?></div>
                                    <div class="stat-label">Tables libres</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-mini-card">
                                <div class="icon purple"><i class="bi bi-people-fill"></i></div>
                                <div>
                                    <div class="stat-number">
                                        <?php echo (int)$stats['places_occupees']; ?>
                                        /
                                        <?php echo (int)$stats['capacite_totale']; ?>
                                    </div>
                                    <div class="stat-label">Places occupées</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LISTE DES TABLES -->
                <?php if (!empty($tables)): ?>
                    <?php foreach ($tables as $t):
                        $nbOcc       = (int)($t['nb_occupation'] ?? 0);
                        $capMax      = max(1, (int)($t['capacite_max'] ?? 4));
                        $percent     = min(100, ($nbOcc / $capMax) * 100);
                        $zoneKey     = strtolower(str_replace('_', '', $t['zone'] ?? 'SALLE_PRINCIPALE'));
                        $zoneLabel   = $zoneLabels[$t['zone'] ?? 'SALLE_PRINCIPALE'] ?? ($t['zone'] ?? 'Salle');
                    ?>
                        <div class="table-card fade-in">
                            <div class="table-info">
                                <div class="name">
                                    <?php echo htmlspecialchars($t['nom'] ?? 'Table sans nom'); ?>
                                    <?php if (!empty($t['numero'])): ?>
                                        <span style="color:#9a8a7f; font-weight:500;">(#<?php echo htmlspecialchars($t['numero']); ?>)</span>
                                    <?php endif; ?>
                                    <span class="badge-zone <?php echo $zoneKey; ?>">
                                        <?php echo $zoneLabel; ?>
                                    </span>
                                </div>
                                <div class="details">
                                    <span><i class="bi bi-people-fill"></i> <?php echo (int)($t['capacite_min'] ?? 1); ?> - <?php echo (int)($t['capacite_max'] ?? 4); ?> personnes</span>
                                    <span><i class="bi bi-shapes"></i> <?php echo htmlspecialchars($t['type'] ?? 'Standard'); ?></span>
                                </div>
                            </div>

                            <div class="table-occupation">
                                <div class="occupation-text">
                                    <?php echo $nbOcc; ?>/<?php echo $capMax; ?> places
                                </div>
                                <div class="occupation-bar">
                                    <div class="fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>

                            <div class="invites">
                                <?php
                                if (!empty($t['invites_noms'])) {
                                    $prenoms = !empty($t['invites_prenoms']) ? explode('||', $t['invites_prenoms']) : [];
                                    $noms    = explode('||', $t['invites_noms']);
                                    $invites = [];
                                    for ($i = 0; $i < count($prenoms) && $i < count($noms); $i++) {
                                        $invites[] = trim($prenoms[$i] . ' ' . $noms[$i]);
                                    }
                                    echo htmlspecialchars(implode(', ', array_slice($invites, 0, 3)));
                                    if (count($invites) > 3) {
                                        echo ' <strong>+' . (count($invites) - 3) . ' autres</strong>';
                                    }
                                } else {
                                    echo '<span style="color:#b8a99c;">Aucun invité assigné</span>';
                                }
                                ?>
                            </div>

                            <div class="actions">
                                <?php if (hasPermission('tables.assigner')): ?>
                                    <a href="assigner.php?id=<?php echo (int)$t['id']; ?>"
                                       class="btn-action assigner" title="Assigner des invités">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (hasPermission('tables.modifier')): ?>
                                    <a href="modifier.php?id=<?php echo (int)$t['id']; ?>"
                                       class="btn-action modifier" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (hasPermission('tables.supprimer')): ?>
                                    <a href="supprimer.php?id=<?php echo (int)$t['id']; ?>"
                                       class="btn-action supprimer" title="Supprimer"
                                       onclick="return confirm('Voulez-vous vraiment supprimer cette table ?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-container fade-in">
                        <div class="empty-state">
                            <i class="bi bi-table"></i>
                            <h5>Aucune table</h5>
                            <p>Commencez par créer des tables pour cet événement</p>
                            <?php if (hasPermission('tables.creer')): ?>
                                <a href="creer.php?evenement=<?php echo $filtre_evenement; ?>" class="btn-add mt-3" style="display: inline-flex;">
                                    <i class="bi bi-plus-circle-fill"></i> Créer une table
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="table-container fade-in">
                    <div class="empty-state">
                        <i class="bi bi-calendar-event"></i>
                        <h5>Sélectionnez un événement</h5>
                        <p>Choisissez un événement pour voir et gérer ses tables.</p>
                    </div>
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

// ========== ANIMATION DES BARRES D'OCCUPATION ==========
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.occupation-bar .fill').forEach(function(bar) {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function() {
            bar.style.width = width;
        }, 400);
    });

    // Auto-hide message de succès
    const alert = document.querySelector('.alert-success-custom');
    if (alert) {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    }

    // Auto-submit sur changement d'événement
    const select = document.getElementById('selectEvenement');
    if (select) {
        select.addEventListener('change', function() {
            const eventId = this.value;
            if (eventId > 0) {
                window.location.href = 'index.php?evenement=' + eventId;
            } else {
                window.location.href = 'index.php';
            }
        });
    }
});
</script>
</body>
</html>