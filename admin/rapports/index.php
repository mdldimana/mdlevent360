<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// ========== FIX INFINITYFREE ==========
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

requirePermission('rapports.voir');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ============================================
// FILTRE PAR ÉVÉNEMENTS ACCESSIBLES
// ============================================

$accessibleEventIds = [];
if (!$isUserAdmin) {
    try {
        $stmt = $pdo->prepare("SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        $accessibleEventIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_evenement');
        $accessibleEventIds = array_map('intval', $accessibleEventIds);
    } catch (PDOException $e) {
        error_log('Erreur chargement événements accessibles : ' . $e->getMessage());
    }
}

// Helper : clause WHERE selon les droits
$eventFilter = '';
$eventParams = [];
if (!$isUserAdmin && !empty($accessibleEventIds)) {
    $placeholders = implode(',', array_fill(0, count($accessibleEventIds), '?'));
    $eventFilter = " AND e.id IN ($placeholders) ";
    $eventParams = $accessibleEventIds;
} elseif (!$isUserAdmin && empty($accessibleEventIds)) {
    // Non-admin sans aucun événement → tout à zéro
    $eventFilter = " AND 1 = 0 ";
}

// ============================================
// STATISTIQUES GLOBALES
// ============================================

$stats = [
    'evenements'         => 0,
    'invites'            => 0,
    'invitations'        => 0,
    'confirmes'          => 0,
    'refuses'            => 0,
    'en_attente'         => 0,
    'present'            => 0,
    'tables'             => 0,
    'places'             => 0,
    'boissons_choisies'  => 0,
];

try {
    // Événements actifs
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM evenements WHERE statut != 'ANNULE' AND id IN (" . (!empty($accessibleEventIds) ? implode(',', array_fill(0, count($accessibleEventIds), '?')) : '0') . ")");
        $stmt->execute(!empty($accessibleEventIds) ? $accessibleEventIds : []);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM evenements WHERE statut != 'ANNULE'");
    }
    $stats['evenements'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Invités (via invitations pour filtrer par événement)
    if (!$isUserAdmin) {
        $sql = "SELECT COUNT(DISTINCT inv.id) AS cnt 
                FROM invites inv 
                INNER JOIN invitations i ON i.id_invite = inv.id 
                INNER JOIN evenements e ON e.id = i.id_evenement 
                WHERE inv.actif = 1 " . $eventFilter;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM invites WHERE actif = 1");
    }
    $stats['invites'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Invitations
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt 
            FROM invitations i 
            INNER JOIN evenements e ON e.id = i.id_evenement 
            WHERE 1=1 " . $eventFilter
        );
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM invitations");
    }
    $stats['invitations'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Confirmations par statut
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT i.statut, COUNT(*) AS cnt 
            FROM invitations i 
            INNER JOIN evenements e ON e.id = i.id_evenement 
            WHERE 1=1 " . $eventFilter . "
            GROUP BY i.statut
        ");
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT statut, COUNT(*) AS cnt FROM invitations GROUP BY statut");
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statut = strtoupper($row['statut'] ?? '');
        $count  = (int)($row['cnt'] ?? 0);
        if ($statut === 'CONFIRMEE')      $stats['confirmes']  = $count;
        elseif ($statut === 'REFUSEE')    $stats['refuses']    = $count;
        elseif ($statut === 'EN_ATTENTE') $stats['en_attente'] = $count;
    }

    // Présents
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id_invitation) AS cnt 
            FROM presences p 
            INNER JOIN invitations i ON i.id = p.id_invitation 
            INNER JOIN evenements e ON e.id = i.id_evenement 
            WHERE 1=1 " . $eventFilter
        );
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT id_invitation) AS cnt FROM presences");
    }
    $stats['present'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Tables
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt 
            FROM tables t 
            INNER JOIN evenements e ON e.id = t.id_evenement 
            WHERE 1=1 " . $eventFilter
        );
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM tables");
    }
    $stats['tables'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Places totales
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(t.capacite_max), 0) AS total 
            FROM tables t 
            INNER JOIN evenements e ON e.id = t.id_evenement 
            WHERE 1=1 " . $eventFilter
        );
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COALESCE(SUM(capacite_max), 0) AS total FROM tables");
    }
    $stats['places'] = (int)($stmt->fetch()['total'] ?? 0);

    // Boissons choisies
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt 
            FROM preferences_invitation pi 
            INNER JOIN invitations i ON i.id = pi.id_invitation 
            INNER JOIN evenements e ON e.id = i.id_evenement 
            WHERE 1=1 " . $eventFilter
        );
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM preferences_invitation");
    }
    $stats['boissons_choisies'] = (int)($stmt->fetch()['cnt'] ?? 0);

} catch (PDOException $e) {
    error_log('Erreur statistiques : ' . $e->getMessage());
}

// ============================================
// STATISTIQUES PAR ÉVÉNEMENT
// ============================================

$evenementsStats = [];
try {
    if (!$isUserAdmin) {
        $sql = "
            SELECT 
                e.id,
                e.nom,
                e.date_evenement,
                e.statut,
                COUNT(DISTINCT i.id) AS nb_invitations,
                COUNT(DISTINCT CASE WHEN i.statut = 'CONFIRMEE' THEN i.id END) AS nb_confirmes,
                COUNT(DISTINCT CASE WHEN i.statut = 'REFUSEE' THEN i.id END) AS nb_refuses,
                COUNT(DISTINCT CASE WHEN i.statut = 'PRESENTE' THEN i.id END) AS nb_presents,
                COUNT(DISTINCT p.id) AS nb_presences,
                COUNT(DISTINCT t.id) AS nb_tables
            FROM evenements e
            LEFT JOIN invitations i ON e.id = i.id_evenement
            LEFT JOIN presences p ON i.id = p.id_invitation
            LEFT JOIN tables t ON e.id = t.id_evenement
            WHERE e.statut != 'ANNULE' " . $eventFilter . "
            GROUP BY e.id, e.nom, e.date_evenement, e.statut
            ORDER BY e.date_evenement DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("
            SELECT 
                e.id,
                e.nom,
                e.date_evenement,
                e.statut,
                COUNT(DISTINCT i.id) AS nb_invitations,
                COUNT(DISTINCT CASE WHEN i.statut = 'CONFIRMEE' THEN i.id END) AS nb_confirmes,
                COUNT(DISTINCT CASE WHEN i.statut = 'REFUSEE' THEN i.id END) AS nb_refuses,
                COUNT(DISTINCT CASE WHEN i.statut = 'PRESENTE' THEN i.id END) AS nb_presents,
                COUNT(DISTINCT p.id) AS nb_presences,
                COUNT(DISTINCT t.id) AS nb_tables
            FROM evenements e
            LEFT JOIN invitations i ON e.id = i.id_evenement
            LEFT JOIN presences p ON i.id = p.id_invitation
            LEFT JOIN tables t ON e.id = t.id_evenement
            WHERE e.statut != 'ANNULE'
            GROUP BY e.id, e.nom, e.date_evenement, e.statut
            ORDER BY e.date_evenement DESC
            LIMIT 10
        ");
    }
    $evenementsStats = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur stats événements : ' . $e->getMessage());
}

// ============================================
// TOP BOISSONS
// ============================================

$topBoissons = [];
try {
    if (!$isUserAdmin) {
        $sql = "
            SELECT 
                b.nom,
                COUNT(pi.id) AS total_choix,
                COALESCE(SUM(pi.quantite), 0) AS total_quantite
            FROM boissons b
            INNER JOIN preferences_invitation pi ON b.id = pi.id_boisson
            INNER JOIN invitations i ON i.id = pi.id_invitation
            INNER JOIN evenements e ON e.id = i.id_evenement
            WHERE 1=1 " . $eventFilter . "
            GROUP BY b.id, b.nom
            ORDER BY total_choix DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("
            SELECT 
                b.nom,
                COUNT(pi.id) AS total_choix,
                COALESCE(SUM(pi.quantite), 0) AS total_quantite
            FROM boissons b
            INNER JOIN preferences_invitation pi ON b.id = pi.id_boisson
            GROUP BY b.id, b.nom
            ORDER BY total_choix DESC
            LIMIT 10
        ");
    }
    $topBoissons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur top boissons : ' . $e->getMessage());
}

// ============================================
// STATISTIQUES PAR ZONE
// ============================================

$zoneLabels = [
    'TERRASSE'         => 'Terrasse',
    'SALLE_PRINCIPALE' => 'Salle principale',
    'SALON'            => 'Salon',
    'MEZZANINE'        => 'Mezzanine',
    'VIP'              => 'VIP',
    'EXTERIEUR'        => 'Extérieur',
];

$zoneStats = [];
try {
    // ⭐ Requête corrigée : la sous-requête utilise inv.nombre_personnes depuis la table invites
    if (!$isUserAdmin) {
        $sql = "
            SELECT 
                sub.zone,
                COUNT(*) AS nb_tables,
                SUM(sub.capacite_max) AS nb_places,
                SUM(sub.nb_occupes) AS nb_occupes
            FROM (
                SELECT 
                    t.id,
                    t.zone,
                    t.capacite_max,
                    COALESCE(SUM(inv.nombre_personnes), 0) AS nb_occupes
                FROM tables t
                LEFT JOIN invitations_tables it ON t.id = it.id_table
                LEFT JOIN invitations i ON it.id_invitation = i.id
                LEFT JOIN invites inv ON i.id_invite = inv.id
                INNER JOIN evenements e ON e.id = t.id_evenement
                WHERE 1=1 " . $eventFilter . "
                GROUP BY t.id, t.zone, t.capacite_max
            ) AS sub
            GROUP BY sub.zone
            ORDER BY sub.zone
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($eventParams);
    } else {
        $stmt = $pdo->query("
            SELECT 
                sub.zone,
                COUNT(*) AS nb_tables,
                SUM(sub.capacite_max) AS nb_places,
                SUM(sub.nb_occupes) AS nb_occupes
            FROM (
                SELECT 
                    t.id,
                    t.zone,
                    t.capacite_max,
                    COALESCE(SUM(inv.nombre_personnes), 0) AS nb_occupes
                FROM tables t
                LEFT JOIN invitations_tables it ON t.id = it.id_table
                LEFT JOIN invitations i ON it.id_invitation = i.id
                LEFT JOIN invites inv ON i.id_invite = inv.id
                GROUP BY t.id, t.zone, t.capacite_max
            ) AS sub
            GROUP BY sub.zone
            ORDER BY sub.zone
        ");
    }
    $zoneStats = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur stats zones : ' . $e->getMessage());
}

// ============================================
// ID PREMIER ÉVÉNEMENT (pour lien "Par événement")
// ============================================

$evenement_id = 0;
if (!empty($evenementsStats)) {
    $evenement_id = (int)$evenementsStats[0]['id'];
} elseif (!empty($accessibleEventIds)) {
    $evenement_id = (int)$accessibleEventIds[0];
}

// ============================================
// CALCULS POURCENTAGES
// ============================================

$totalInvitations = (int)$stats['invitations'];
$confirmes        = (int)$stats['confirmes'];
$refuses          = (int)$stats['refuses'];

$pourcentageRefus        = $totalInvitations > 0 ? round(($refuses / $totalInvitations) * 100) : 0;
$pourcentageConfirmation = $totalInvitations > 0 ? round(($confirmes / $totalInvitations) * 100) : 0;
$classeTaux              = $pourcentageConfirmation > 50 ? 'up' : 'neutral';

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
<title>Rapports - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        top: 12px; left: 12px;
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

    /* ========== STAT CARDS ========== */
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.12);
        border-color: rgba(193, 124, 96, 0.25);
    }
    .stat-card .stat-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: white;
        margin-bottom: 12px;
    }
    .stat-card .stat-icon.blue   { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-card .stat-icon.green  { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-card .stat-icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .stat-card .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #d8b4fe); }
    .stat-card .stat-icon.red    { background: linear-gradient(135deg, #ef4444, #f87171); }
    .stat-card .stat-icon.pink   { background: linear-gradient(135deg, #ec4899, #f472b6); }
    .stat-card .stat-icon.cyan   { background: linear-gradient(135deg, #06b6d4, #22d3ee); }
    .stat-card .stat-icon.gold   { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

    .stat-card .stat-number { font-size: 26px; font-weight: 800; color: #1a1a1a; line-height: 1; margin: 4px 0; }
    .stat-card .stat-label {
        color: #9a8a7f;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .stat-card .stat-change {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .stat-card .stat-change.up      { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .stat-card .stat-change.down    { background: rgba(239, 68, 68, 0.12);  color: #991b1b; }
    .stat-card .stat-change.neutral { background: rgba(245, 158, 11, 0.15); color: #92400e; }

    /* ========== CARDS RAPPORT ========== */
    .card-rapport {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        height: 100%;
    }
    .card-rapport .card-header-custom {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-rapport .card-header-custom i { color: #c17c60; }

    .chart-container { position: relative; height: 260px; }

    /* ========== EVENT ITEM ========== */
    .event-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        gap: 10px;
        flex-wrap: wrap;
    }
    .event-item:last-child { border-bottom: none; }
    .event-item .event-info { flex: 1; min-width: 150px; }
    .event-item .event-info .name { font-weight: 700; color: #1a1a1a; font-size: 13px; }
    .event-item .event-info .date { font-size: 11px; color: #9a8a7f; margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .event-item .event-info .date i { color: #c17c60; }
    .event-item .event-stats { display: flex; gap: 14px; font-size: 12px; color: #6a5a4a; font-weight: 600; }
    .event-item .event-stats i { color: #c17c60; margin-right: 3px; }

    /* ========== BADGES ZONE ========== */
    .badge-zone {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: inline-block;
    }
    .badge-zone.terrasse         { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-zone.salleprincipale  { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
    .badge-zone.salon            { background: rgba(245, 158, 11, 0.15); color: #92400e; }
    .badge-zone.mezzanine        { background: rgba(107, 114, 128, 0.15); color: #374151; }
    .badge-zone.vip              { background: rgba(239, 68, 68, 0.12); color: #991b1b; }
    .badge-zone.exterieur        { background: rgba(16, 185, 129, 0.15); color: #065f46; }

    /* ========== PROGRESS ========== */
    .progress-custom { height: 6px; border-radius: 10px; background: rgba(234, 227, 220, 0.5); overflow: hidden; }
    .progress-custom .progress-bar {
        border-radius: 10px;
        background: linear-gradient(90deg, #c17c60, #d4a574);
        transition: width 0.6s ease;
    }
    .progress-custom .progress-bar.blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }

    /* ========== RAPPORT CARDS (liens) ========== */
    .rapport-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 14px;
        padding: 16px 12px;
        text-align: center;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        transition: all 0.3s ease;
        cursor: pointer;
        height: 100%;
        display: block;
        color: inherit;
        text-decoration: none;
    }
    .rapport-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(193, 124, 96, 0.12);
        border-color: #c17c60;
        color: inherit;
    }
    .rapport-card .icon-wrap {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 20px; color: white;
        margin-bottom: 10px;
    }
    .rapport-card .icon-wrap.blue   { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .rapport-card .icon-wrap.green  { background: linear-gradient(135deg, #10b981, #34d399); }
    .rapport-card .icon-wrap.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .rapport-card .icon-wrap.purple { background: linear-gradient(135deg, #a855f7, #d8b4fe); }
    .rapport-card .icon-wrap.pink   { background: linear-gradient(135deg, #ec4899, #f472b6); }
    .rapport-card .icon-wrap.gold   { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .rapport-card .title { font-weight: 700; color: #1a1a1a; font-size: 12px; }
    .rapport-card .desc  { font-size: 10px; color: #9a8a7f; margin-top: 3px; }

    /* ========== BOUTONS EXPORT ========== */
    .btn-export {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        cursor: pointer;
    }
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }
    .btn-export.excel { background: linear-gradient(135deg, #10b981, #34d399); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25); }
    .btn-export.excel:hover { box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35); }
    .btn-export.gray  { background: linear-gradient(135deg, #6b7280, #9ca3af); box-shadow: 0 4px 15px rgba(107, 114, 128, 0.25); }
    .btn-export.gray:hover { box-shadow: 0 8px 24px rgba(107, 114, 128, 0.35); }
    .btn-export.purple { background: linear-gradient(135deg, #a855f7, #d8b4fe); box-shadow: 0 4px 15px rgba(168, 85, 247, 0.25); }
    .btn-export.purple:hover { box-shadow: 0 8px 24px rgba(168, 85, 247, 0.35); }
    .btn-export.dark  { background: linear-gradient(135deg, #1a1a1a, #374151); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25); }
    .btn-export.dark:hover { box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35); }

    /* ========== EMPTY ========== */
    .empty-inline {
        text-align: center;
        padding: 30px 15px;
        color: #9a8a7f;
        font-size: 12px;
    }
    .empty-inline i {
        font-size: 32px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 8px;
    }

    /* ========== ANIMATIONS ========== */
    .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) {
        .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
    }

    /* ========== FOOTER ========== */
    .app-footer {
        text-align: center;
        padding: 30px 0 20px;
        color: #b8a99c;
        font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #c17c60; }

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
        .card-rapport { padding: 18px; }
        .event-item { flex-direction: column; align-items: flex-start; }
        .event-item .event-stats { width: 100%; }
        .chart-container { height: 220px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-rapport { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-card { padding: 14px; }
        .stat-card .stat-number { font-size: 20px; }
        .stat-card .stat-icon { width: 40px; height: 40px; font-size: 17px; }
        .chart-container { height: 200px; }
        .btn-export { font-size: 11px; padding: 8px 14px; }
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
                <h4><i class="bi bi-bar-chart-fill"></i> Tableau de bord des rapports</h4>
                <small><i class="bi bi-stats"></i> Statistiques globales de l'application</small>
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

            <!-- RAPPORTS DISPONIBLES -->
            <div class="card-rapport fade-in mb-4">
                <div class="card-header-custom">
                    <i class="bi bi-folder-fill"></i> Rapports disponibles
                </div>
                <div class="row g-3">
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="evenement.php?id=<?php echo (int)$evenement_id; ?>" class="rapport-card">
                            <div class="icon-wrap blue"><i class="bi bi-calendar-event-fill"></i></div>
                            <div class="title">Par événement</div>
                            <div class="desc">Statistiques détaillées</div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="invites.php" class="rapport-card">
                            <div class="icon-wrap green"><i class="bi bi-people-fill"></i></div>
                            <div class="title">Invités</div>
                            <div class="desc">Liste complète</div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="confirmations.php" class="rapport-card">
                            <div class="icon-wrap orange"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="title">Confirmations</div>
                            <div class="desc">Réponses des invités</div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="presences.php" class="rapport-card">
                            <div class="icon-wrap purple"><i class="bi bi-person-check-fill"></i></div>
                            <div class="title">Présences</div>
                            <div class="desc">Entrées enregistrées</div>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="boissons.php" class="rapport-card">
                            <div class="icon-wrap pink"><i class="bi bi-cup-straw"></i></div>
                            <div class="title">Boissons</div>
                            <div class="desc">Statistiques des choix</div>
                        </a>
                    </div>
                    
                </div>
            </div>

            <!-- STATS PRINCIPALES - Ligne 1 -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-calendar-event-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['evenements']; ?></div>
                        <div class="stat-label">Événements</div>
                        <div class="stat-change up"><i class="bi bi-check-circle-fill"></i> Actifs</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['invites']; ?></div>
                        <div class="stat-label">Invités</div>
                        <div class="stat-change up"><i class="bi bi-person-plus-fill"></i> Enregistrés</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-envelope-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['invitations']; ?></div>
                        <div class="stat-label">Invitations</div>
                        <div class="stat-change neutral"><i class="bi bi-clock-fill"></i> <?php echo (int)$stats['en_attente']; ?> en attente</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['present']; ?></div>
                        <div class="stat-label">Présents</div>
                        <div class="stat-change up"><i class="bi bi-check-circle-fill"></i> <?php echo (int)$stats['confirmes']; ?> confirmés</div>
                    </div>
                </div>
            </div>

            <!-- STATS PRINCIPALES - Ligne 2 -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon pink"><i class="bi bi-table"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['tables']; ?></div>
                        <div class="stat-label">Tables</div>
                        <div class="stat-change neutral"><i class="bi bi-people-fill"></i> <?php echo (int)$stats['places']; ?> places</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="bi bi-cup-hot-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['boissons_choisies']; ?></div>
                        <div class="stat-label">Boissons choisies</div>
                        <div class="stat-change up"><i class="bi bi-check-circle-fill"></i> Préférences</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['refuses']; ?></div>
                        <div class="stat-label">Refusés</div>
                        <div class="stat-change down"><i class="bi bi-arrow-down"></i> <?php echo $pourcentageRefus; ?>%</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['confirmes']; ?></div>
                        <div class="stat-label">Confirmés</div>
                        <div class="stat-change <?php echo $classTaux ?? 'neutral'; ?>">
                            <i class="bi bi-arrow-up"></i> <?php echo $pourcentageConfirmation; ?>%
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">

                <!-- GRAPHIQUE ÉVÉNEMENTS -->
                <div class="col-lg-6 fade-in">
                    <div class="card-rapport">
                        <div class="card-header-custom">
                            <i class="bi bi-bar-chart-line-fill"></i> Statistiques par événement
                        </div>
                        <?php if (!empty($evenementsStats)): ?>
                            <div class="chart-container">
                                <canvas id="eventsChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-bar-chart"></i>
                                Aucun événement à afficher.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TOP BOISSONS -->
                <div class="col-lg-6 fade-in">
                    <div class="card-rapport">
                        <div class="card-header-custom">
                            <i class="bi bi-cup-hot-fill"></i> Boissons les plus populaires
                        </div>
                        <?php if (!empty($topBoissons)): ?>
                            <?php $maxChoix = max(1, (int)$topBoissons[0]['total_choix']); ?>
                            <?php foreach ($topBoissons as $index => $b): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="fw-bold me-2" style="width:25px;color:#c17c60">#<?php echo $index + 1; ?></span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span style="font-size:13px;font-weight:600"><?php echo htmlspecialchars($b['nom']); ?></span>
                                            <span style="font-size:11px;color:#9a8a7f"><?php echo (int)$b['total_choix']; ?> choix</span>
                                        </div>
                                        <div class="progress-custom">
                                            <div class="progress-bar" style="width: <?php echo ((int)$b['total_choix'] / $maxChoix) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-cup"></i>
                                Aucune boisson choisie.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DERNIERS ÉVÉNEMENTS -->
                <div class="col-lg-6 fade-in">
                    <div class="card-rapport">
                        <div class="card-header-custom">
                            <i class="bi bi-clock-history"></i> Derniers événements
                        </div>
                        <?php if (!empty($evenementsStats)): ?>
                            <?php foreach ($evenementsStats as $e): ?>
                                <div class="event-item">
                                    <div class="event-info">
                                        <div class="name"><?php echo htmlspecialchars($e['nom']); ?></div>
                                        <div class="date">
                                            <i class="bi bi-calendar-fill"></i>
                                            <?php echo !empty($e['date_evenement']) ? date('d/m/Y', strtotime($e['date_evenement'])) : '—'; ?>
                                            <?php
                                            $statutEvent = $e['statut'] ?? '';
                                            $badgeClass = $statutEvent === 'ACTIF' ? 'success' : 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>" style="font-size:9px;padding:3px 8px;border-radius:20px">
                                                <?php echo htmlspecialchars($statutEvent); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="event-stats">
                                        <span><i class="bi bi-envelope-fill"></i> <?php echo (int)$e['nb_invitations']; ?></span>
                                        <span><i class="bi bi-check-circle-fill"></i> <?php echo (int)$e['nb_confirmes']; ?></span>
                                        <span><i class="bi bi-person-check-fill"></i> <?php echo (int)$e['nb_presences']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-calendar-event"></i>
                                Aucun événement.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STATS ZONES -->
                <div class="col-lg-6 fade-in">
                    <div class="card-rapport">
                        <div class="card-header-custom">
                            <i class="bi bi-grid-3x3-gap-fill"></i> Répartition par zone
                        </div>
                        <?php if (!empty($zoneStats)): ?>
                            <?php foreach ($zoneStats as $z):
                                $zone      = $z['zone'] ?? '';
                                $zoneLabel = $zoneLabels[$zone] ?? $zone;
                                $nbTables  = (int)($z['nb_tables'] ?? 0);
                                $nbPlaces  = (int)($z['nb_places'] ?? 0);
                                $nbOccupes = (int)($z['nb_occupes'] ?? 0);
                                $pourcentage = $nbPlaces > 0 ? round(($nbOccupes / $nbPlaces) * 100) : 0;
                                $zoneClass = strtolower(str_replace('_', '', $zone));
                            ?>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge-zone <?php echo htmlspecialchars($zoneClass); ?>" style="min-width:110px">
                                        <?php echo htmlspecialchars($zoneLabel); ?>
                                    </span>
                                    <div class="flex-grow-1 mx-2">
                                        <div class="d-flex justify-content-between" style="font-size:11px;color:#6a5a4a">
                                            <span><?php echo $nbTables; ?> tables</span>
                                            <span><?php echo $nbOccupes; ?>/<?php echo $nbPlaces; ?> places</span>
                                        </div>
                                        <div class="progress-custom mt-1">
                                            <div class="progress-bar blue" style="width: <?php echo $pourcentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <span style="font-size:11px;color:#9a8a7f;font-weight:600"><?php echo $pourcentage; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-table"></i>
                                Aucune zone configurée.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- EXPORTS -->
               <!-- EXPORTS -->
<div class="col-12 fade-in">
    <div class="card-rapport">
        <div class="card-header-custom">
            <i class="bi bi-download"></i> Exporter les rapports
        </div>
        <?php if (hasPermission('rapports.exporter')): ?>
            <div class="d-flex flex-wrap gap-2">
                <!-- ⭐ Nouveaux exports stylés (même design que les boissons) -->
                <a href="export_pdf/export_invites.php?evenement=<?php echo (int)$evenement_id; ?>" 
                   class="btn-export" target="_blank" rel="noopener">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Invités (PDF)
                </a>
                <a href="export_pdf/export_presences.php?evenement=<?php echo (int)$evenement_id; ?>" 
                   class="btn-export gray" target="_blank" rel="noopener">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Présences (PDF)
                </a>
                <a href="export_pdf/export_boissons.php?evenement=<?php echo (int)$evenement_id; ?>" 
                   class="btn-export purple" target="_blank" rel="noopener">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Boissons (PDF)
                </a>
                <a href="export_pdf/export_global.php?evenement=<?php echo (int)$evenement_id; ?>" 
                   class="btn-export dark" target="_blank" rel="noopener">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Rapport global (PDF)
                </a>
            </div>
        <?php endif; ?>
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

// ========== CHART.JS ==========
<?php if (!empty($evenementsStats)): ?>
(function() {
    const canvas = document.getElementById('eventsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const labels      = <?php echo json_encode(array_column($evenementsStats, 'nom'), JSON_UNESCAPED_UNICODE); ?>;
    const invitations = <?php echo json_encode(array_map('intval', array_column($evenementsStats, 'nb_invitations'))); ?>;
    const confirmes   = <?php echo json_encode(array_map('intval', array_column($evenementsStats, 'nb_confirmes'))); ?>;
    const presents    = <?php echo json_encode(array_map('intval', array_column($evenementsStats, 'nb_presences'))); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Invitations',
                    data: invitations,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 6,
                },
                {
                    label: 'Confirmés',
                    data: confirmes,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 6,
                },
                {
                    label: 'Présents',
                    data: presents,
                    backgroundColor: 'rgba(193, 124, 96, 0.6)',
                    borderColor: '#c17c60',
                    borderWidth: 2,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16,
                        font: { size: 11, family: 'Inter' },
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11, family: 'Inter' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, family: 'Inter' } }
                }
            }
        }
    });
})();
<?php endif; ?>
</script>
</body>
</html>