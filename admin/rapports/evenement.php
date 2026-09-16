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

$evenement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ========== VÉRIFICATION ACCÈS ==========
if ($evenement_id <= 0) {
    header('Location: index.php');
    exit;
}

// ⭐ Vérifier que l'utilisateur a accès à cet événement
if (!$isUserAdmin) {
    if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, $evenement_id)) {
        header('Location: ' . BASE_PATH . '/403.php');
        exit;
    }
}

// ============================================
// RÉCUPÉRATION DE L'ÉVÉNEMENT
// ============================================

$evenement = null;
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.nom AS createur_nom, u.prenom AS createur_prenom
        FROM evenements e
        LEFT JOIN utilisateurs u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$evenement_id]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération événement (rapports/evenement) : ' . $e->getMessage());
}

if (!$evenement) {
    header('Location: index.php');
    exit;
}

// ============================================
// STATISTIQUES DE L'ÉVÉNEMENT
// ============================================

$stats = [
    'invitations'      => 0,
    'confirmes'        => 0,
    'refuses'          => 0,
    'en_attente'       => 0,
    'present'          => 0,
    'tables'           => 0,
    'places'           => 0,
    'places_occupees'  => 0,
    'boissons_choisies'=> 0,
    'personnes_prevues'=> 0,
];

try {
    // Invitations par statut
    $stmt = $pdo->prepare("
        SELECT statut, COUNT(*) AS cnt
        FROM invitations
        WHERE id_evenement = ?
        GROUP BY statut
    ");
    $stmt->execute([$evenement_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statut = strtoupper($row['statut'] ?? '');
        $count  = (int)($row['cnt'] ?? 0);

        if ($statut === 'CONFIRMEE')       $stats['confirmes']  = $count;
        elseif ($statut === 'REFUSEE')     $stats['refuses']    = $count;
        elseif ($statut === 'EN_ATTENTE')  $stats['en_attente'] = $count;

        $stats['invitations'] += $count;
    }

    // Personnes prévues (somme des nb_personnes des invitations)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(inv.nombre_personnes), 0) AS total
        FROM invitations i
        INNER JOIN invites inv ON i.id_invite = inv.id
        WHERE i.id_evenement = ? AND i.statut != 'ANNULEE'
    ");
    $stmt->execute([$evenement_id]);
    $stats['personnes_prevues'] = (int)($stmt->fetch()['total'] ?? 0);

    // Présences
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id_invitation) AS cnt
        FROM presences p
        INNER JOIN invitations i ON p.id_invitation = i.id
        WHERE i.id_evenement = ?
    ");
    $stmt->execute([$evenement_id]);
    $stats['present'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Tables + places
    // ⭐ On sépare en 2 requêtes pour éviter les doublons
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS nb_tables, COALESCE(SUM(capacite_max), 0) AS nb_places
        FROM tables
        WHERE id_evenement = ?
    ");
    $stmt->execute([$evenement_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['tables'] = (int)($row['nb_tables'] ?? 0);
    $stats['places'] = (int)($row['nb_places'] ?? 0);

    // Places occupées (somme des personnes des invitations assignées à une table)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(inv.nombre_personnes), 0) AS total
        FROM invitations_tables it
        INNER JOIN invitations i ON i.id = it.id_invitation
        INNER JOIN invites inv ON inv.id = i.id_invite
        WHERE i.id_evenement = ?
    ");
    $stmt->execute([$evenement_id]);
    $stats['places_occupees'] = (int)($stmt->fetch()['total'] ?? 0);

    // Boissons choisies
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(pi.quantite), 0) AS cnt
        FROM preferences_invitation pi
        INNER JOIN invitations i ON pi.id_invitation = i.id
        WHERE i.id_evenement = ?
    ");
    $stmt->execute([$evenement_id]);
    $stats['boissons_choisies'] = (int)($stmt->fetch()['cnt'] ?? 0);

} catch (PDOException $e) {
    error_log('Erreur statistiques événement : ' . $e->getMessage());
}

// ============================================
// LISTE DES INVITATIONS
// ============================================

$invitations = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id, i.code_unique, i.statut, i.created_at,
            inv.nom, inv.prenom, inv.email, inv.telephone, inv.nombre_personnes,
            c.reponse, c.preference_alimentaire,
            p.date_entree, p.heure_entree
        FROM invitations i
        INNER JOIN invites inv ON i.id_invite = inv.id
        LEFT JOIN confirmations c ON i.id = c.id_invitation
        LEFT JOIN presences p ON i.id = p.id_invitation
        WHERE i.id_evenement = ?
        ORDER BY inv.nom ASC, inv.prenom ASC
        LIMIT 100
    ");
    $stmt->execute([$evenement_id]);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement invitations : ' . $e->getMessage());
}

// ============================================
// TOP BOISSONS DE L'ÉVÉNEMENT
// ============================================

$topBoissons = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.nom,
            COUNT(pi.id) AS total_choix,
            COALESCE(SUM(pi.quantite), 0) AS total_quantite
        FROM boissons b
        INNER JOIN preferences_invitation pi ON b.id = pi.id_boisson
        INNER JOIN invitations i ON pi.id_invitation = i.id
        WHERE i.id_evenement = ?
        GROUP BY b.id, b.nom
        ORDER BY total_choix DESC
        LIMIT 10
    ");
    $stmt->execute([$evenement_id]);
    $topBoissons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur top boissons : ' . $e->getMessage());
}

// ============================================
// LABELS
// ============================================

$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE'  => 'Confirmée',
    'REFUSEE'    => 'Refusée',
    'PRESENTE'   => 'Présente',
    'ANNULEE'    => 'Annulée',
];

$statutColors = [
    'EN_ATTENTE' => 'warning',
    'CONFIRMEE'  => 'success',
    'REFUSEE'    => 'danger',
    'PRESENTE'   => 'info',
    'ANNULEE'    => 'secondary',
];

$statutIcons = [
    'EN_ATTENTE' => 'bi-clock-fill',
    'CONFIRMEE'  => 'bi-check-circle-fill',
    'REFUSEE'    => 'bi-x-circle-fill',
    'PRESENTE'   => 'bi-person-check-fill',
    'ANNULEE'    => 'bi-slash-circle-fill',
];

$alimentaireLabels = [
    'STANDARD'   => 'Standard',
    'VEGETARIEN' => 'Végétarien',
    'VEGETALIEN' => 'Végétalien',
    'AUTRE'      => 'Autre',
];

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
<title>Statistiques - <?php echo htmlspecialchars($evenement['nom']); ?> - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

    /* ========== STATS ========== */
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s ease;
        height: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 3px;
        background: linear-gradient(90deg, #c17c60, #d4a574);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(193, 124, 96, 0.1);
        border-color: rgba(193, 124, 96, 0.2);
    }
    .stat-card:hover::before { opacity: 1; }

    .stat-card .stat-icon-wrap {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 20px;
        color: white;
        margin-bottom: 10px;
    }
    .stat-card .stat-icon-wrap.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
    .stat-card .stat-icon-wrap.green  { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-card .stat-icon-wrap.red    { background: linear-gradient(135deg, #ef4444, #f87171); }
    .stat-card .stat-icon-wrap.blue   { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-card .stat-icon-wrap.purple { background: linear-gradient(135deg, #a855f7, #d8b4fe); }
    .stat-card .stat-icon-wrap.pink   { background: linear-gradient(135deg, #ec4899, #f472b6); }
    .stat-card .stat-icon-wrap.cyan   { background: linear-gradient(135deg, #06b6d4, #22d3ee); }
    .stat-card .stat-icon-wrap.gold   { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

    .stat-card .stat-number {
        font-size: 28px;
        font-weight: 800;
        color: #1a1a1a;
        line-height: 1;
        margin: 4px 0;
    }
    .stat-card .stat-label {
        color: #9a8a7f;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }

    /* ========== CARD RAPPORT ========== */
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
        flex-wrap: wrap;
    }
    .card-rapport .card-header-custom i { color: #c17c60; }

    /* ========== TABLE ========== */
    .table-responsive-custom {
        max-height: 500px;
        overflow-y: auto;
        border-radius: 12px;
    }
    .table-responsive-custom::-webkit-scrollbar { width: 5px; }
    .table-responsive-custom::-webkit-scrollbar-track { background: #f8f5f2; }
    .table-responsive-custom::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c17c60, #d4a574); border-radius: 10px; }

    .table-custom { margin-bottom: 0; }
    .table-custom thead th {
        background: rgba(252, 250, 248, 0.95);
        color: #6a5a4a;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-bottom: 1.5px solid rgba(240, 235, 229, 0.8);
        padding: 12px 10px;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .table-custom tbody td {
        padding: 10px;
        font-size: 12px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        color: #1a1a1a;
    }
    .table-custom tbody tr:last-child td { border-bottom: none; }
    .table-custom tbody tr:hover { background: rgba(193, 124, 96, 0.03); }
    .table-custom code {
        font-size: 10px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }
    .table-custom .invite-name { font-weight: 700; color: #1a1a1a; font-size: 12.5px; }
    .table-custom .invite-sub { font-size: 11px; color: #9a8a7f; margin-top: 2px; }

    /* ========== BADGES ========== */
    .badge-statut {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .badge-statut.en_attente { background: rgba(245, 158, 11, 0.15); color: #92400e; }
    .badge-statut.confirmee  { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-statut.refusee    { background: rgba(239, 68, 68, 0.12); color: #991b1b; }
    .badge-statut.presente   { background: rgba(59, 130, 246, 0.12); color: #1e40af; }
    .badge-statut.annulee    { background: rgba(107, 114, 128, 0.12); color: #374151; }

    /* ========== BOUTONS ========== */
    .btn-back {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }
    .btn-back:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
    }

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
        font-size: 13px;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
    }
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }
    .btn-export.excel {
        background: linear-gradient(135deg, #10b981, #34d399);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    }
    .btn-export.excel:hover {
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
    }

    /* ========== TOP BOISSONS CARD ========== */
    .top-boisson-card {
        background: rgba(255, 255, 255, 0.9);
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        border-radius: 12px;
        padding: 14px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .top-boisson-card:hover {
        transform: translateY(-3px);
        border-color: rgba(193, 124, 96, 0.3);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
    }
    .top-boisson-card .boisson-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.15));
        display: inline-flex;
        align-items: center; justify-content: center;
        color: #c17c60;
        font-size: 18px;
        margin-bottom: 8px;
    }
    .top-boisson-card .boisson-num {
        font-size: 22px;
        font-weight: 800;
        color: #c17c60;
        line-height: 1;
    }
    .top-boisson-card .boisson-name {
        font-size: 12px;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 4px;
        word-break: break-word;
        line-height: 1.3;
    }
    .top-boisson-card .boisson-qty {
        font-size: 10px;
        color: #9a8a7f;
        margin-top: 4px;
    }

    /* ========== EMPTY ========== */
    .empty-inline {
        text-align: center;
        padding: 40px 20px;
        color: #9a8a7f;
        font-size: 13px;
    }
    .empty-inline i {
        font-size: 42px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 10px;
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
        .stat-card .stat-number { font-size: 22px; }
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
        .stat-card .stat-icon-wrap { width: 38px; height: 38px; font-size: 17px; }
        .stat-card .stat-label { font-size: 10px; }
        .btn-back, .btn-export { width: 100%; justify-content: center; }
        .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
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
                <h4><i class="bi bi-bar-chart-fill"></i> Statistiques événement</h4>
                <small>
                    <i class="bi bi-calendar-event"></i>
                    <?php echo htmlspecialchars($evenement['nom']); ?>
                    <?php if (!empty($evenement['date_evenement'])): ?>
                        • <?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?>
                    <?php endif; ?>
                </small>
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

            <!-- STATS LIGNE 1 -->
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap orange"><i class="bi bi-envelope-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['invitations']; ?></div>
                        <div class="stat-label">Invitations</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['confirmes']; ?></div>
                        <div class="stat-label">Confirmés</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap red"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['refuses']; ?></div>
                        <div class="stat-label">Refusés</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap purple"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['present']; ?></div>
                        <div class="stat-label">Présents</div>
                    </div>
                </div>
            </div>

            <!-- STATS LIGNE 2 -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap pink"><i class="bi bi-table"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['tables']; ?></div>
                        <div class="stat-label">Tables</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap blue"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number">
                            <?php echo (int)$stats['places_occupees']; ?>
                            <span style="font-size:14px;color:#9a8a7f">/ <?php echo (int)$stats['places']; ?></span>
                        </div>
                        <div class="stat-label">Places occupées</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap gold"><i class="bi bi-cup-hot-fill"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['boissons_choisies']; ?></div>
                        <div class="stat-label">Boissons choisies</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon-wrap cyan"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-number"><?php echo (int)$stats['en_attente']; ?></div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
            </div>

            <!-- TOP BOISSONS -->
            <?php if (!empty($topBoissons)): ?>
                <div class="card-rapport fade-in mb-4">
                    <div class="card-header-custom">
                        <i class="bi bi-cup-hot-fill"></i> Boissons les plus choisies
                    </div>
                    <div class="row g-3">
                        <?php foreach ($topBoissons as $b): ?>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                <div class="top-boisson-card">
                                    <div class="boisson-icon"><i class="bi bi-cup-straw"></i></div>
                                    <div class="boisson-num"><?php echo (int)($b['total_choix'] ?? 0); ?></div>
                                    <div class="boisson-name"><?php echo htmlspecialchars($b['nom'] ?? '—'); ?></div>
                                    <div class="boisson-qty">
                                        <?php echo (int)($b['total_quantite'] ?? 0); ?> unité(s)
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LISTE DES INVITATIONS -->
            <div class="card-rapport fade-in">
                <div class="card-header-custom">
                    <span style="flex:1">
                        <i class="bi bi-list-ul"></i> Détail des invitations
                        <span style="background:rgba(193,124,96,0.12);color:#c17c60;padding:2px 10px;border-radius:20px;font-size:11px;margin-left:8px;font-weight:700">
                            <?php echo count($invitations); ?>
                        </span>
                    </span>
                </div>

                <?php if (!empty($invitations)): ?>
                    <div class="table-responsive-custom">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Invité</th>
                                    <th>Code</th>
                                    <th style="text-align:center">Statut</th>
                                    <th style="text-align:center">Pers.</th>
                                    <th>Préférence</th>
                                    <th>Entrée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invitations as $inv):
                                    $statut = $inv['statut'] ?? 'EN_ATTENTE';
                                    $statutClass = strtolower($statut);
                                    $statutLabel = $statutLabels[$statut] ?? $statut;
                                    $statutIcon  = $statutIcons[$statut] ?? 'bi-circle-fill';
                                    $prefLabel = $alimentaireLabels[$inv['preference_alimentaire'] ?? ''] ?? '—';
                                    $tsEntree = null;
                                    if (!empty($inv['date_entree'])) {
                                        $tsEntree = strtotime($inv['date_entree'] . ' ' . ($inv['heure_entree'] ?? '00:00'));
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="invite-name">
                                                <?php echo htmlspecialchars(trim(($inv['prenom'] ?? '') . ' ' . ($inv['nom'] ?? ''))); ?>
                                            </div>
                                            <?php if (!empty($inv['email']) || !empty($inv['telephone'])): ?>
                                                <div class="invite-sub">
                                                    <?php if (!empty($inv['email'])): ?>
                                                        <i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($inv['email']); ?>
                                                    <?php elseif (!empty($inv['telephone'])): ?>
                                                        <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($inv['telephone']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($inv['code_unique'] ?? '—'); ?></code>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="badge-statut <?php echo $statutClass; ?>">
                                                <i class="bi <?php echo $statutIcon; ?>"></i>
                                                <?php echo htmlspecialchars($statutLabel); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center; font-weight:700; color:#c17c60">
                                            <?php echo (int)($inv['nombre_personnes'] ?? 1); ?>
                                        </td>
                                        <td style="font-size:11px; color:#6a5a4a">
                                            <?php echo htmlspecialchars($prefLabel); ?>
                                        </td>
                                        <td style="font-size:11px; color:#9a8a7f; white-space:nowrap">
                                            <?php if ($tsEntree): ?>
                                                <i class="bi bi-check-circle-fill" style="color:#10b981"></i>
                                                <?php echo date('d/m/Y H:i', $tsEntree); ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-inline">
                        <i class="bi bi-inbox"></i>
                        Aucune invitation pour cet événement.
                    </div>
                <?php endif; ?>
            </div>

            <!-- ACTIONS -->
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="index.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <?php if (hasPermission('rapports.exporter')): ?>
                    <a href="export_pdf/export_global.php?evenement=<?php echo $evenement_id; ?>" 
                       class="btn-export" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Exporter en PDF
                    </a>
                    <a href="export.php?type=evenement&id=<?php echo $evenement_id; ?>&format=excel" 
                       class="btn-export excel" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-excel-fill"></i> Exporter en Excel
                    </a>
                <?php endif; ?>
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
</script>
</body>
</html>