<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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

$eventFilter = '';
$eventParams = [];
if (!$isUserAdmin && !empty($accessibleEventIds)) {
    $placeholders = implode(',', array_fill(0, count($accessibleEventIds), '?'));
    $eventFilter = " AND e.id IN ($placeholders) ";
    $eventParams = $accessibleEventIds;
} elseif (!$isUserAdmin && empty($accessibleEventIds)) {
    $eventFilter = " AND 1 = 0 ";
}

// ============================================
// VARIABLES / FILTRES
// ============================================
$message_success = '';
$message_error   = '';

$filtre_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$filtre_statut    = $_GET['statut'] ?? 'avec_message';
$recherche        = trim($_GET['recherche'] ?? '');
$tri              = $_GET['tri'] ?? 'recent';

// ============================================
// ACTIONS POST
// ============================================

// --- Supprimer un message (mettre NULL dans invitations.message) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE invitations SET message = NULL WHERE id = ?");
            $stmt->execute([$id]);
            $message_success = 'Message supprimé avec succès.';
        } catch (PDOException $e) {
            $message_error = 'Erreur : ' . $e->getMessage();
        }
    }
}

// --- Suppression multiple ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmt = $pdo->prepare("UPDATE invitations SET message = NULL WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $message_success = count($ids) . ' message(s) supprimé(s).';
        } catch (PDOException $e) {
            $message_error = 'Erreur : ' . $e->getMessage();
        }
    }
}

// ============================================
// RÉCUPÉRATION DES ÉVÉNEMENTS (filtre)
// ============================================
$evenements = [];
try {
    if (!$isUserAdmin && !empty($accessibleEventIds)) {
        $placeholders = implode(',', array_fill(0, count($accessibleEventIds), '?'));
        $stmt = $pdo->prepare("SELECT id, nom FROM evenements WHERE id IN ($placeholders) ORDER BY date_evenement DESC");
        $stmt->execute($accessibleEventIds);
    } else {
        $stmt = $pdo->query("SELECT id, nom FROM evenements ORDER BY date_evenement DESC");
    }
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {}

// ============================================
// REQUÊTE PRINCIPALE : uniquement les messages présents
// ============================================
$sql = "
    SELECT 
        i.id,
        i.code_unique,
        i.message,
        i.statut,
        i.date_confirmation,
        i.created_at,
        i.nb_presents,
        inv.nom,
        inv.prenom,
        inv.email,
        inv.telephone,
        inv.nombre_personnes AS nb_places_max,
        e.nom AS evenement_nom,
        e.id AS evenement_id,
        e.date_evenement,
        c.commentaire AS commentaire_confirmation,
        c.nombre_personnes AS nb_confirme,
        c.reponse
    FROM invitations i
    JOIN invites inv ON i.id_invite = inv.id
    JOIN evenements e ON i.id_evenement = e.id
    LEFT JOIN confirmations c ON i.id = c.id_invitation
    WHERE (i.message IS NOT NULL AND i.message != '')
";

$params = [];

// Filtre événement
if ($filtre_evenement > 0) {
    $sql .= " AND e.id = ?";
    $params[] = $filtre_evenement;
}

// Filtre statut
if ($filtre_statut === 'confirme') {
    $sql .= " AND i.statut = 'CONFIRMEE'";
} elseif ($filtre_statut === 'refuse') {
    $sql .= " AND i.statut = 'REFUSEE'";
} elseif ($filtre_statut === 'attente') {
    $sql .= " AND i.statut = 'EN_ATTENTE'";
}
// 'avec_message' est implicite (déjà filtré plus haut)

// Droits
if (!$isUserAdmin && !empty($accessibleEventIds)) {
    $sql .= $eventFilter;
    $params = array_merge($params, $eventParams);
} elseif (!$isUserAdmin && empty($accessibleEventIds)) {
    $sql .= " AND 1 = 0 ";
}

// Recherche
if (!empty($recherche)) {
    $sql .= " AND (
        inv.nom LIKE ? OR 
        inv.prenom LIKE ? OR 
        inv.email LIKE ? OR 
        i.code_unique LIKE ? OR 
        i.message LIKE ?
    )";
    $search = '%' . $recherche . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

// Tri
switch ($tri) {
    case 'ancien':
        $sql .= " ORDER BY i.created_at ASC";
        break;
    case 'nom':
        $sql .= " ORDER BY inv.nom ASC, inv.prenom ASC";
        break;
    case 'evenement':
        $sql .= " ORDER BY e.date_evenement DESC, inv.nom ASC";
        break;
    case 'recent':
    default:
        $sql .= " ORDER BY i.created_at DESC";
        break;
}

// ============================================
// EXÉCUTION
// ============================================
$messages = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur requête messages : ' . $e->getMessage());
    $message_error = 'Erreur lors du chargement : ' . $e->getMessage();
}

// ============================================
// STATISTIQUES
// ============================================
$stats = [
    'total'      => count($messages),
    'confirmes'  => 0,
    'refuses'    => 0,
    'attente'    => 0,
    'evenements' => 0,
];

$evenementsSet = [];
foreach ($messages as $m) {
    if ($m['statut'] === 'CONFIRMEE')      $stats['confirmes']++;
    elseif ($m['statut'] === 'REFUSEE')    $stats['refuses']++;
    elseif ($m['statut'] === 'EN_ATTENTE') $stats['attente']++;
    $evenementsSet[$m['evenement_id']] = true;
}
$stats['evenements'] = count($evenementsSet);

// ============================================
// EXPORT CSV
// ============================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="messages_invites_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, [
        'ID',
        'Code unique',
        'Invité',
        'Email',
        'Téléphone',
        'Événement',
        'Statut',
        'Message',
        'Date envoi'
    ], ';');
    
    foreach ($messages as $m) {
        fputcsv($output, [
            $m['id'],
            $m['code_unique'],
            trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? '')),
            $m['email'],
            $m['telephone'],
            $m['evenement_nom'],
            $m['statut'],
            $m['message'],
            $m['created_at'],
        ], ';');
    }
    
    fclose($output);
    exit;
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================
function getInitials($prenom, $nom) {
    $p = mb_substr(trim((string)$prenom), 0, 1);
    $n = mb_substr(trim((string)$nom), 0, 1);
    return mb_strtoupper($p . $n);
}

function getAvatarColor($str) {
    $colors = ['#e8b4b8', '#a8b89a', '#c9a961', '#b8a4d4', '#d4a4a4', '#a4c4d4', '#d4c4a4'];
    $hash = crc32((string)$str);
    return $colors[abs($hash) % count($colors)];
}

function timeAgo($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'à l\'instant';
    if ($diff < 3600)   return 'il y a ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'il y a ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'il y a ' . floor($diff / 86400) . ' j';
    return date('d/m/Y', $timestamp);
}

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
<title>Messages des invités - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
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
    .stat-card .stat-icon.dark   { background: linear-gradient(135deg, #1a1a1a, #374151); }

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

    /* ========== FILTRES ========== */
    .filter-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #9a8a7f;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid rgba(193, 124, 96, 0.2);
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #1a1a1a;
        background: white;
        transition: all 0.3s ease;
    }
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #c17c60;
        box-shadow: 0 0 0 3px rgba(193, 124, 96, 0.12);
    }

    /* ========== MESSAGE CARDS ========== */
    .message-card {
        background: white;
        border-radius: 18px;
        border: 1px solid rgba(193, 124, 96, 0.15);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        margin-bottom: 16px;
    }
    .message-card:hover {
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.15);
        border-color: rgba(193, 124, 96, 0.3);
    }
    .message-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #c17c60, #d4a574);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .message-card:hover::before { opacity: 1; }

    .message-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        border-bottom: 1px solid rgba(193, 124, 96, 0.08);
        flex-wrap: wrap;
    }

    .message-avatar {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 800;
        font-size: 17px;
        flex-shrink: 0;
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .message-user { flex: 1; min-width: 180px; }
    .message-user-name {
        font-size: 15px;
        font-weight: 700;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .message-user-meta {
        font-size: 12px;
        color: #9a8a7f;
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .message-user-meta i { color: #c17c60; margin-right: 4px; }

    .message-badges { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .badge-confirmed { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-refused   { background: rgba(239, 68, 68, 0.12);  color: #991b1b; }
    .badge-pending   { background: rgba(245, 158, 11, 0.15); color: #92400e; }
    .badge-event     { background: rgba(193, 124, 96, 0.12); color: #c17c60; }

    .message-actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }

    .message-body {
        padding: 20px 24px 22px;
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.04), rgba(212, 165, 116, 0.02));
        position: relative;
    }
    .message-body::before {
        content: '"';
        position: absolute;
        top: 4px;
        left: 14px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 72px;
        color: #c17c60;
        opacity: 0.18;
        line-height: 1;
        pointer-events: none;
    }
    .message-text {
        font-family: 'Cormorant Garamond', serif;
        font-size: 17px;
        font-style: italic;
        line-height: 1.75;
        color: #1a1a1a;
        padding-left: 24px;
        word-wrap: break-word;
    }

    /* ========== BOUTONS ========== */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 10px;
        border: none;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-primary {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }
    .btn-outline {
        background: white;
        border: 1.5px solid rgba(193, 124, 96, 0.25);
        color: #1a1a1a;
    }
    .btn-outline:hover {
        border-color: #c17c60;
        color: #c17c60;
    }
    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
    }
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.35);
        color: white;
    }
    .btn-sm { padding: 6px 10px; font-size: 12px; border-radius: 8px; }

    /* ========== BULK ACTIONS ========== */
    .bulk-actions {
        background: linear-gradient(135deg, #1a1a1a, #374151);
        color: white;
        border-radius: 16px;
        padding: 16px 22px;
        margin-bottom: 16px;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }
    .bulk-actions.visible { display: flex; }
    .bulk-actions-info { display: flex; align-items: center; gap: 12px; font-size: 14px; }
    .bulk-actions-info strong { color: #d4a574; font-size: 18px; font-weight: 800; }

    /* ========== CHECKBOX ========== */
    .message-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #c17c60;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* ========== EMPTY ========== */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        border: 1px solid rgba(193, 124, 96, 0.15);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }
    .empty-state i {
        font-size: 64px;
        color: #f5e6dc;
        margin-bottom: 20px;
    }
    .empty-state h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 24px;
        color: #1a1a1a;
        margin-bottom: 8px;
    }
    .empty-state p { color: #9a8a7f; font-size: 14px; }

    /* ========== ALERTES ========== */
    .alert-custom {
        padding: 14px 20px;
        border-radius: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        border-left: 4px solid;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }
    .alert-success {
        background: rgba(16, 185, 129, 0.08);
        border-color: #10b981;
        color: #065f46;
    }
    .alert-error {
        background: rgba(239, 68, 68, 0.08);
        border-color: #ef4444;
        color: #991b1b;
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
    .app-footer i { color: #c17c60; }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 992px) {
        .sidebar-toggle-btn { display: flex !important; align-items: center; justify-content: center; }
        .app-wrapper { display: block; width: 100%; }
        .main-content, body.sidebar-open .main-content {
            width: 100% !important; min-width: 0 !important; margin-left: 0 !important;
        }
        .sidebar-wrapper {
            position: fixed !important; top: 0 !important; left: 0 !important;
            width: min(280px, 85vw) !important; height: 100dvh !important;
            margin: 0 !important; transform: translate3d(-105%, 0, 0);
            transition: transform 0.28s ease !important; z-index: 2000 !important;
            overflow-y: auto; border-radius: 0 18px 18px 0;
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
        .top-bar { padding: 12px 15px 12px 70px; }
        .content-section { padding: 15px; }
        .top-bar .page-title h4 { font-size: 1rem; }
        .top-bar .user-info .user-name { display: none; }
        .message-header { flex-direction: column; align-items: flex-start; }
        .message-actions { margin-left: 0; width: 100%; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .content-section { padding: 10px 12px; }
        .stat-card { padding: 14px; }
        .stat-card .stat-number { font-size: 20px; }
        .stat-card .stat-icon { width: 40px; height: 40px; font-size: 17px; }
        .message-card { border-radius: 14px; }
        .message-header { padding: 14px; gap: 10px; }
        .message-avatar { width: 42px; height: 42px; font-size: 14px; }
        .message-body { padding: 16px 18px; }
        .message-text { font-size: 15px; padding-left: 18px; }
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
                <h4><i class="bi bi-chat-dots-fill"></i> Messages des invités</h4>
                <small><i class="bi bi-chat-quote"></i> Tous les mots laissés par vos invités</small>
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

            <!-- Alertes -->
            <?php if ($message_success): ?>
                <div class="alert-custom alert-success fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo htmlspecialchars($message_success); ?>
                </div>
            <?php endif; ?>
            <?php if ($message_error): ?>
                <div class="alert-custom alert-error fade-in">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?php echo htmlspecialchars($message_error); ?>
                </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon dark"><i class="bi bi-chat-dots-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['total']; ?></div>
                        <div class="stat-label">Messages reçus</div>
                        <div class="stat-change neutral"><i class="bi bi-chat-quote-fill"></i> Tous les messages</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['confirmes']; ?></div>
                        <div class="stat-label">Confirmés</div>
                        <div class="stat-change up"><i class="bi bi-check"></i> Présents</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="bi bi-clock-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['attente']; ?></div>
                        <div class="stat-label">En attente</div>
                        <div class="stat-change neutral"><i class="bi bi-hourglass"></i> À venir</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['refuses']; ?></div>
                        <div class="stat-label">Refusés</div>
                        <div class="stat-change down"><i class="bi bi-x"></i> Absents</div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="card-rapport fade-in mb-4">
                <div class="card-header-custom">
                    <i class="bi bi-funnel-fill"></i> Filtres et recherche
                </div>
                <form method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-search"></i> Recherche</label>
                                <input type="text" name="recherche" value="<?php echo htmlspecialchars($recherche); ?>" placeholder="Nom, email, code, message...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-calendar-event"></i> Événement</label>
                                <select name="evenement">
                                    <option value="0">Tous les événements</option>
                                    <?php foreach ($evenements as $ev): ?>
                                        <option value="<?php echo $ev['id']; ?>" <?php echo $filtre_evenement == $ev['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ev['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-filter"></i> Statut</label>
                                <select name="statut">
                                    <option value="avec_message" <?php echo $filtre_statut === 'avec_message' ? 'selected' : ''; ?>>Tous</option>
                                    <option value="confirme" <?php echo $filtre_statut === 'confirme' ? 'selected' : ''; ?>>Confirmés</option>
                                    <option value="attente" <?php echo $filtre_statut === 'attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="refuse" <?php echo $filtre_statut === 'refuse' ? 'selected' : ''; ?>>Refusés</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-sort-down"></i> Tri</label>
                                <select name="tri">
                                    <option value="recent" <?php echo $tri === 'recent' ? 'selected' : ''; ?>>Plus récents</option>
                                    <option value="ancien" <?php echo $tri === 'ancien' ? 'selected' : ''; ?>>Plus anciens</option>
                                    <option value="nom" <?php echo $tri === 'nom' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                                    <option value="evenement" <?php echo $tri === 'evenement' ? 'selected' : ''; ?>>Par événement</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-12 d-flex gap-2">
                            <button type="submit" class="btn-action btn-primary" title="Filtrer">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($recherche) || $filtre_evenement > 0 || $filtre_statut !== 'avec_message'): ?>
                                <a href="message.php" class="btn-action btn-outline" title="Réinitialiser">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                            <a href="?export=csv&<?php echo http_build_query(['evenement' => $filtre_evenement, 'statut' => $filtre_statut, 'recherche' => $recherche]); ?>" class="btn-action btn-outline" title="Export CSV">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- BULK ACTIONS -->
            <div class="bulk-actions" id="bulkActions">
                <div class="bulk-actions-info">
                    <i class="bi bi-check-square-fill" style="color: #d4a574;"></i>
                    <strong id="selectedCount">0</strong> message(s) sélectionné(s)
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-action btn-outline" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white;" onclick="deselectAll()">
                        <i class="bi bi-x"></i> Annuler
                    </button>
                    <button type="button" class="btn-action btn-danger" onclick="deleteSelected()">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>

            <!-- Formulaire suppression multiple -->
            <form method="POST" id="bulkForm" style="display: none;">
                <input type="hidden" name="action" value="supprimer_multiple">
                <div id="bulkIds"></div>
            </form>

            <!-- LISTE -->
            <?php if (empty($messages)): ?>
                <div class="empty-state fade-in">
                    <i class="bi bi-chat-square-text"></i>
                    <h3>Aucun message</h3>
                    <p>Aucun message ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <div class="messages-container">
                    <?php foreach ($messages as $m): ?>
                        <?php 
                        $initials = getInitials($m['prenom'] ?? '', $m['nom'] ?? '');
                        $avatarColor = getAvatarColor($m['code_unique']);
                        $guestFullName = trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''));
                        
                        $statutBadge = '';
                        if ($m['statut'] === 'CONFIRMEE') {
                            $statutBadge = '<span class="badge badge-confirmed"><i class="bi bi-check-circle-fill"></i> Confirmé</span>';
                        } elseif ($m['statut'] === 'REFUSEE') {
                            $statutBadge = '<span class="badge badge-refused"><i class="bi bi-x-circle-fill"></i> Refusé</span>';
                        } else {
                            $statutBadge = '<span class="badge badge-pending"><i class="bi bi-clock-fill"></i> En attente</span>';
                        }
                        ?>
                        
                        <div class="message-card fade-in">
                            <div class="message-header">
                                <input type="checkbox" class="message-checkbox" value="<?php echo $m['id']; ?>" onchange="updateSelection()">
                                
                                <div class="message-avatar" style="background: <?php echo $avatarColor; ?>;">
                                    <?php echo $initials; ?>
                                </div>
                                
                                <div class="message-user">
                                    <div class="message-user-name">
                                        <?php echo htmlspecialchars($guestFullName); ?>
                                    </div>
                                    <div class="message-user-meta">
                                        <span><i class="bi bi-calendar-event-fill"></i> <?php echo htmlspecialchars($m['evenement_nom']); ?></span>
                                        <span><i class="bi bi-hash"></i> <?php echo htmlspecialchars($m['code_unique']); ?></span>
                                        <span><i class="bi bi-clock"></i> <?php echo timeAgo($m['created_at']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="message-badges">
                                    <?php echo $statutBadge; ?>
                                    <?php if (!empty($m['nb_confirme'])): ?>
                                        <span class="badge badge-event"><i class="bi bi-people-fill"></i> <?php echo (int)$m['nb_confirme']; ?> pers.</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="message-actions">
                                    <form method="POST" onsubmit="return confirm('Supprimer ce message définitivement ?');" style="display:inline;">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn-action btn-danger btn-sm" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="message-body">
                                <div class="message-text">
                                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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

// ========== SÉLECTION MULTIPLE ==========
function updateSelection() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    const count = checkboxes.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    if (count > 0) {
        bulkActions.classList.add('visible');
        selectedCount.textContent = count;
    } else {
        bulkActions.classList.remove('visible');
    }
}

function deselectAll() {
    document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = false);
    updateSelection();
}

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    const count = checkboxes.length;
    if (count === 0) return;
    if (!confirm(`Supprimer définitivement ${count} message(s) ?`)) return;
    
    const form = document.getElementById('bulkForm');
    const idsContainer = document.getElementById('bulkIds');
    idsContainer.innerHTML = '';
    checkboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        idsContainer.appendChild(input);
    });
    form.submit();
}

// Animation d'entrée
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.message-card');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, Math.min(i * 40, 400));
    });
});
</script>

</body>
</html>