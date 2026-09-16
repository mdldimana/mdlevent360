<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/whatsapp/index.php
require_once __DIR__ . '/../../../includes/auth.php';

// ========== FIX INFINITYFREE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

requirePermission('notifications.whatsapp');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ============================================
// RÉCUPÉRER LES ÉVÉNEMENTS (avec filtrage utilisateur)
// ============================================

$evenements = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT id, nom, date_evenement AS date_debut, date_evenement AS date_fin, lieu
            FROM evenements
            WHERE id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
              AND statut != 'ANNULE'
            ORDER BY date_evenement DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("
            SELECT id, nom, date_evenement AS date_debut, date_evenement AS date_fin, lieu
            FROM evenements
            WHERE statut != 'ANNULE'
            ORDER BY date_evenement DESC
        ");
    }
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement événements (whatsapp) : ' . $e->getMessage());
}

// ============================================
// FILTRES
// ============================================

$idEvenement     = (int)($_GET['evenement_id'] ?? 0);
$filtreWhatsapp  = $_GET['filtre_whatsapp'] ?? 'tous';
$recherche       = trim($_GET['recherche'] ?? '');

// ⭐ Vérifier l'accès à l'événement si non-admin
if ($idEvenement > 0 && !$isUserAdmin) {
    $allowed = false;
    foreach ($evenements as $ev) {
        if ((int)$ev['id'] === $idEvenement) { $allowed = true; break; }
    }
    if (!$allowed) {
        header('Location: ' . BASE_PATH . '/403.php');
        exit;
    }
}

// ============================================
// INVITATIONS
// ============================================

$invitations = [];
$stats = ['total' => 0, 'envoyes' => 0, 'non_envoyes' => 0, 'avec_telephone' => 0];

if ($idEvenement > 0) {
    try {
        $sql = "
            SELECT 
                i.id,
                i.code_unique,
                i.whatsapp_sent,
                i.whatsapp_sent_at,
                i.email_sent,
                i.telegram_sent,
                i.statut,
                i.created_at,
                inv.nom,
                inv.prenom,
                inv.email,
                inv.telephone,
                e.nom AS evenement_nom
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            WHERE i.id_evenement = :id_evenement
        ";

        $params = [':id_evenement' => $idEvenement];

        if ($filtreWhatsapp === 'envoye') {
            $sql .= " AND i.whatsapp_sent = 1";
        } elseif ($filtreWhatsapp === 'non_envoye') {
            $sql .= " AND (i.whatsapp_sent IS NULL OR i.whatsapp_sent = 0)";
        }

        if ($recherche !== '') {
            $sql .= " AND (inv.nom LIKE :recherche OR inv.prenom LIKE :recherche OR inv.telephone LIKE :recherche)";
            $params[':recherche'] = '%' . $recherche . '%';
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtStats = $pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent = 1 THEN 1 ELSE 0 END), 0) AS envoyes,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent IS NULL OR i.whatsapp_sent = 0 THEN 1 ELSE 0 END), 0) AS non_envoyes,
                COALESCE(SUM(CASE WHEN inv.telephone IS NOT NULL AND inv.telephone != '' THEN 1 ELSE 0 END), 0) AS avec_telephone
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            WHERE i.id_evenement = :id_evenement
        ");
        $stmtStats->execute([':id_evenement' => $idEvenement]);
        $row = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats = [
            'total'          => (int)($row['total'] ?? 0),
            'envoyes'        => (int)($row['envoyes'] ?? 0),
            'non_envoyes'    => (int)($row['non_envoyes'] ?? 0),
            'avec_telephone' => (int)($row['avec_telephone'] ?? 0),
        ];
    } catch (PDOException $e) {
        error_log('Erreur chargement invitations whatsapp : ' . $e->getMessage());
    }
}

// ============================================
// STATISTIQUES GLOBALES (avec filtrage utilisateur)
// ============================================

$statsGlobales = ['total_invitations' => 0, 'whatsapp_envoyes' => 0, 'whatsapp_restants' => 0];

try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_invitations,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent = 1 THEN 1 ELSE 0 END), 0) AS whatsapp_envoyes,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent IS NULL OR i.whatsapp_sent = 0 THEN 1 ELSE 0 END), 0) AS whatsapp_restants
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            WHERE inv.telephone IS NOT NULL AND inv.telephone != ''
              AND i.statut != 'ANNULEE'
              AND i.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_invitations,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent = 1 THEN 1 ELSE 0 END), 0) AS whatsapp_envoyes,
                COALESCE(SUM(CASE WHEN i.whatsapp_sent IS NULL OR i.whatsapp_sent = 0 THEN 1 ELSE 0 END), 0) AS whatsapp_restants
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            WHERE inv.telephone IS NOT NULL AND inv.telephone != ''
              AND i.statut != 'ANNULEE'
        ");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $statsGlobales = [
        'total_invitations' => (int)($result['total_invitations'] ?? 0),
        'whatsapp_envoyes'  => (int)($result['whatsapp_envoyes'] ?? 0),
        'whatsapp_restants' => (int)($result['whatsapp_restants'] ?? 0),
    ];
} catch (PDOException $e) {
    error_log('Erreur stats globales whatsapp : ' . $e->getMessage());
}

// ============================================
// CONFIGURATION WHATSAPP
// ============================================

$configWhatsapp = [
    'api_key'             => '',
    'phone_number_id'     => '',
    'business_account_id' => '',
    'webhook_verify_token'=> '',
    'actif'               => 0,
];

try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($config) {
        $configWhatsapp = [
            'api_key'             => $config['api_key']             ?? '',
            'phone_number_id'     => $config['phone_number_id']     ?? '',
            'business_account_id' => $config['business_account_id'] ?? '',
            'webhook_verify_token'=> $config['webhook_verify_token']?? '',
            'actif'               => (int)($config['actif'] ?? 0),
        ];
    }
} catch (PDOException $e) {
    error_log('Erreur chargement config WhatsApp : ' . $e->getMessage());
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
<title>WhatsApp - <?php echo APP_NAME; ?></title>
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
    .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #25D366, #128C7E); border-radius: 10px; }

    /* ========== TOP BAR ========== */
    .top-bar {
        background: rgba(255, 255, 255, 0.95);
        padding: 15px 30px;
        border-bottom: 1px solid rgba(37, 211, 102, 0.15);
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
    .top-bar .page-title h4 i { color: #25D366; margin-right: 10px; }
    .top-bar .page-title small { color: #9a8a7f; font-size: 12px; display: block; margin-top: 2px; }
    .top-bar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .top-bar .user-info .user-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #25D366, #128C7E);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 16px;
        box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        flex-shrink: 0;
    }
    .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 13px; }
    .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 11px; }
    .top-bar .user-info .role-badge {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 700; white-space: nowrap;
    }

    /* ========== SIDEBAR TOGGLE ========== */
    .sidebar-toggle-btn {
        display: none;
        position: fixed;
        top: 12px; left: 12px;
        z-index: 200;
        background: linear-gradient(135deg, #25D366, #128C7E);
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        box-shadow: 0 5px 20px rgba(37, 211, 102, 0.35);
        font-size: 20px;
        cursor: pointer;
        color: white;
        transition: all 0.3s ease;
    }
    .sidebar-toggle-btn:hover { transform: scale(1.05); box-shadow: 0 8px 30px rgba(37, 211, 102, 0.45); }
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
    .stat-mini {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        padding: 18px 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(37, 211, 102, 0.12);
        border-color: rgba(37, 211, 102, 0.2);
    }
    .stat-mini .number { font-size: 28px; font-weight: 800; color: #1a1a1a; line-height: 1; }
    .stat-mini .label {
        font-size: 11px; color: #9a8a7f; font-weight: 600;
        margin-top: 6px; text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .stat-mini .icon-wrap {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 22px;
        color: white;
        margin-bottom: 10px;
    }
    .stat-mini .icon-wrap.green   { background: linear-gradient(135deg, #25D366, #128C7E); }
    .stat-mini .icon-wrap.blue    { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-mini .icon-wrap.orange  { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

    /* ========== CARDS ========== */
    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }

    /* ========== FILTRE ========== */
    .filter-section {
        background: linear-gradient(135deg, rgba(37, 211, 102, 0.05), rgba(18, 140, 126, 0.05));
        border: 1px solid rgba(37, 211, 102, 0.15);
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 20px;
    }
    .filter-section .form-label {
        font-weight: 700;
        font-size: 11px;
        color: #6a5a4a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: block;
    }
    .filter-section .form-label i { color: #25D366; margin-right: 4px; }
    .filter-section .form-control,
    .filter-section .form-select {
        border-radius: 10px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 10px 14px;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .filter-section .form-control:focus,
    .filter-section .form-select:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.08);
        outline: none;
        background: white;
    }

    /* ========== BOUTONS ========== */
    .btn-whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.25);
        cursor: pointer;
    }
    .btn-whatsapp:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.35);
        color: white;
    }
    .btn-whatsapp.btn-sm {
        padding: 7px 14px;
        font-size: 12px;
    }

    .btn-outline-whatsapp {
        background: rgba(255, 255, 255, 0.9);
        color: #25D366;
        border: 1.5px solid rgba(37, 211, 102, 0.4);
        padding: 8px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-outline-whatsapp:hover {
        background: white;
        border-color: #25D366;
        color: #128C7E;
    }

    /* ========== BADGES ========== */
    .badge-whatsapp-sent {
        background: rgba(16, 185, 129, 0.15); color: #065f46;
        padding: 4px 12px; border-radius: 20px;
        font-weight: 700; font-size: 11px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-whatsapp-pending {
        background: rgba(245, 158, 11, 0.15); color: #92400e;
        padding: 4px 12px; border-radius: 20px;
        font-weight: 700; font-size: 11px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-whatsapp-none {
        background: rgba(239, 68, 68, 0.12); color: #991b1b;
        padding: 4px 12px; border-radius: 20px;
        font-weight: 700; font-size: 11px;
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* ========== CONFIG STATUS ========== */
    .config-status {
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .config-status.active {
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .config-status.inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* ========== TABLE ========== */
    .table-custom { margin-bottom: 0; }
    .table-custom thead th {
        background: rgba(252, 250, 248, 0.9);
        color: #6a5a4a;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-bottom: 1.5px solid rgba(240, 235, 229, 0.8);
        padding: 12px 10px;
    }
    .table-custom tbody td {
        padding: 12px 10px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        color: #1a1a1a;
    }
    .table-custom tbody tr:hover { background: rgba(37, 211, 102, 0.03); }
    .table-custom tbody tr:last-child td { border-bottom: none; }
    .table-custom code {
        font-size: 10px;
        background: rgba(37, 211, 102, 0.1);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #128C7E;
        padding: 2px 6px;
        border-radius: 6px;
    }
    .table-custom a { color: #25D366; text-decoration: none; font-weight: 600; }
    .table-custom a:hover { text-decoration: underline; }

    /* ========== BOUTONS ACTION ========== */
    .btn-action {
        width: 34px; height: 34px;
        border-radius: 10px;
        border: none;
        transition: all 0.25s ease;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-action:hover { transform: translateY(-2px); }
    .btn-action.send {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);
    }
    .btn-action.send:hover { box-shadow: 0 6px 18px rgba(37, 211, 102, 0.4); }
    .btn-action.view {
        background: rgba(59, 130, 246, 0.12);
        color: #3b82f6;
    }
    .btn-action.view:hover { background: #3b82f6; color: white; }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #9a8a7f;
    }
    .empty-state i {
        font-size: 48px;
        color: #d4c5b2;
        display: block;
        margin-bottom: 12px;
    }
    .empty-state h5 { color: #6a5a4a; font-weight: 700; margin-bottom: 6px; font-size: 16px; }
    .empty-state p { font-size: 13px; margin-bottom: 14px; }

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
    .app-footer i.bi-heart-fill { color: #25D366; }

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
        .card-custom { padding: 18px; }
        .filter-section { padding: 14px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-mini { padding: 14px 12px; }
        .stat-mini .number { font-size: 22px; }
        .stat-mini .icon-wrap { width: 40px; height: 40px; font-size: 18px; }
        .stat-mini .label { font-size: 10px; }
        .table-custom thead th { font-size: 9px; padding: 8px 6px; }
        .table-custom tbody td { font-size: 12px; padding: 8px 6px; }
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
        <?php include_once __DIR__ . '/../../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-whatsapp"></i> WhatsApp</h4>
                <small><i class="bi bi-send-fill"></i> Gestion des invitations par WhatsApp</small>
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

            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:15px">
                        <i class="bi bi-gear-fill me-2" style="color:#25D366"></i>Configuration
                    </h5>
                    <small style="color:#9a8a7f;font-size:12px">Paramètres d'envoi WhatsApp</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="config-status <?php echo $configWhatsapp['actif'] === 1 ? 'active' : 'inactive'; ?>">
                        <i class="bi bi-<?php echo $configWhatsapp['actif'] === 1 ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                        <?php echo $configWhatsapp['actif'] === 1 ? 'API active' : 'API inactive'; ?>
                    </span>
                    <a href="config.php" class="btn-whatsapp">
                        <i class="bi bi-sliders2"></i> Configuration
                    </a>
                </div>
            </div>

            <!-- STATS GLOBALES -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap blue"><i class="bi bi-people-fill"></i></div>
                        <div class="number"><?php echo $statsGlobales['total_invitations']; ?></div>
                        <div class="label">Total avec téléphone</div>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="number" style="color:#25D366"><?php echo $statsGlobales['whatsapp_envoyes']; ?></div>
                        <div class="label">WhatsApp envoyés</div>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="stat-mini">
                        <div class="icon-wrap orange"><i class="bi bi-clock-history"></i></div>
                        <div class="number" style="color:#c17c60"><?php echo $statsGlobales['whatsapp_restants']; ?></div>
                        <div class="label">En attente d'envoi</div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="filter-section fade-in">
                <form method="GET" action="" id="formFiltres">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">
                                <i class="bi bi-calendar-event-fill"></i> Événement
                            </label>
                            <select name="evenement_id" id="evenement_id" class="form-select" required>
                                <option value="">-- Choisissez un événement --</option>
                                <?php foreach ($evenements as $event): ?>
                                    <option value="<?php echo (int)$event['id']; ?>"
                                            <?php echo $idEvenement === (int)$event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['nom']); ?>
                                        (<?php echo date('d/m/Y', strtotime($event['date_debut'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-funnel-fill"></i> Statut WhatsApp
                            </label>
                            <select name="filtre_whatsapp" class="form-select">
                                <option value="tous" <?php echo $filtreWhatsapp === 'tous' ? 'selected' : ''; ?>>Tous</option>
                                <option value="envoye" <?php echo $filtreWhatsapp === 'envoye' ? 'selected' : ''; ?>>✅ Envoyé</option>
                                <option value="non_envoye" <?php echo $filtreWhatsapp === 'non_envoye' ? 'selected' : ''; ?>>⏳ Non envoyé</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-search"></i> Recherche
                            </label>
                            <input type="text" name="recherche" class="form-control" placeholder="Nom, prénom, téléphone..." value="<?php echo htmlspecialchars($recherche); ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn-whatsapp w-100 justify-content-center">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- STATS ÉVÉNEMENT SÉLECTIONNÉ -->
            <?php if ($idEvenement > 0): ?>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-mini" style="padding:14px">
                            <div class="number" style="font-size:20px"><?php echo $stats['total']; ?></div>
                            <div class="label">Total invités</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini" style="padding:14px;border-left:3px solid #25D366">
                            <div class="number" style="font-size:20px;color:#25D366"><?php echo $stats['envoyes']; ?></div>
                            <div class="label">Envoyés</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini" style="padding:14px;border-left:3px solid #f59e0b">
                            <div class="number" style="font-size:20px;color:#f59e0b"><?php echo $stats['non_envoyes']; ?></div>
                            <div class="label">En attente</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini" style="padding:14px;border-left:3px solid #3b82f6">
                            <div class="number" style="font-size:20px;color:#3b82f6"><?php echo $stats['avec_telephone']; ?></div>
                            <div class="label">Avec téléphone</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LISTE -->
            <div class="card-custom fade-in">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0" style="font-size:14px">
                        <i class="bi bi-list-ul me-2" style="color:#25D366"></i> Invitations
                        <?php if ($idEvenement > 0 && !empty($invitations)): ?>
                            <span style="background:rgba(37,211,102,0.12);color:#128C7E;padding:2px 10px;border-radius:20px;font-size:11px;margin-left:6px;font-weight:700">
                                <?php echo count($invitations); ?>
                            </span>
                        <?php endif; ?>
                    </h6>
                    <?php if ($idEvenement > 0 && !empty($invitations)): ?>
                        <a href="envoyer.php?evenement_id=<?php echo $idEvenement; ?>" class="btn-whatsapp btn-sm">
                            <i class="bi bi-send-fill"></i> Envoyer tout
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($idEvenement > 0 && !empty($invitations)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invité</th>
                                    <th>Téléphone</th>
                                    <th>Statut WhatsApp</th>
                                    <th>Date d'envoi</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($invitations as $inv): ?>
                                    <tr>
                                        <td style="color:#9a8a7f;font-weight:600"><?php echo $i++; ?></td>
                                        <td>
                                            <strong style="font-size:13px"><?php echo htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']); ?></strong><br>
                                            <code><?php echo htmlspecialchars($inv['code_unique']); ?></code>
                                        </td>
                                        <td>
                                            <?php if (!empty($inv['telephone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($inv['telephone']); ?>">
                                                    <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($inv['telephone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color:#b8a99c;font-style:italic">Non renseigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int)$inv['whatsapp_sent'] === 1): ?>
                                                <span class="badge-whatsapp-sent">
                                                    <i class="bi bi-check-circle-fill"></i> Envoyé
                                                </span>
                                            <?php elseif (!empty($inv['telephone'])): ?>
                                                <span class="badge-whatsapp-pending">
                                                    <i class="bi bi-clock-fill"></i> En attente
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-whatsapp-none">
                                                    <i class="bi bi-exclamation-circle-fill"></i> Pas de téléphone
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:11px;color:#9a8a7f">
                                            <?php if ((int)$inv['whatsapp_sent'] === 1 && !empty($inv['whatsapp_sent_at'])): ?>
                                                <i class="bi bi-calendar-check"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($inv['whatsapp_sent_at'])); ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ((int)$inv['whatsapp_sent'] !== 1 && !empty($inv['telephone'])): ?>
                                                    <a href="envoyer.php?invitation_id=<?php echo (int)$inv['id']; ?>" class="btn-action send" title="Envoyer">
                                                        <i class="bi bi-send-fill"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo htmlspecialchars(BASE_PATH); ?>/public/invitation.php?code=<?php echo urlencode($inv['code_unique']); ?>"
                                                   class="btn-action view"
                                                   title="Voir"
                                                   target="_blank"
                                                   rel="noopener">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($idEvenement > 0): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>Aucune invitation</h5>
                        <p>Aucune invitation trouvée avec les filtres actuels.</p>
                        <a href="?evenement_id=<?php echo $idEvenement; ?>" class="btn-outline-whatsapp">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-plus"></i>
                        <h5>Sélectionnez un événement</h5>
                        <p>Choisissez un événement ci-dessus pour voir les invitations.</p>
                    </div>
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

// ========== AUTO-SUBMIT ÉVÉNEMENT ==========
const selectEvenement = document.getElementById('evenement_id');
if (selectEvenement) {
    selectEvenement.addEventListener('change', function() {
        document.getElementById('formFiltres').submit();
    });
}
</script>
</body>
</html>