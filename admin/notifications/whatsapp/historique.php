<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/whatsapp/historique.php
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

require_once __DIR__ . '/../../../includes/whatsapp_helper.php';

// ============================================
// FILTRES ET PAGINATION
// ============================================

$status    = trim($_GET['status']    ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');
$search    = trim($_GET['search']    ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 20;
$offset    = ($page - 1) * $limit;

// Validation du statut
$statusValides = ['sent', 'delivered', 'read', 'failed'];
if ($status !== '' && !in_array($status, $statusValides, true)) {
    $status = '';
}

// Construire les filtres
$filters = [];
if ($status !== '')   $filters['status']    = $status;
if ($search !== '')   $filters['to_phone']  = $search;
if ($dateFrom !== '') $filters['date_from'] = $dateFrom;
if ($dateTo !== '')   $filters['date_to']   = $dateTo;

// ⭐ Filtrage par utilisateur (si non admin)
if (!$isUserAdmin) {
    $filters['user_id'] = $userId;
}

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================

$messages    = [];
$total       = 0;
$totalPages  = 1;
$stats       = ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0];

try {
    $messages = getWhatsAppHistory($limit, $offset, $filters) ?: [];
    $total    = (int)getWhatsAppTotalCount($filters);
    $totalPages = max(1, (int)ceil($total / $limit));
    $stats    = getWhatsAppStats($filters) ?: $stats;
} catch (Throwable $e) {
    error_log('Erreur chargement historique WhatsApp : ' . $e->getMessage());
}

// ============================================
// STATUTS
// ============================================

$statusColors = [
    'sent'      => 'success',
    'failed'    => 'danger',
    'delivered' => 'info',
    'read'      => 'primary',
];

$statusLabels = [
    'sent'      => 'Envoyé',
    'failed'    => 'Échoué',
    'delivered' => 'Délivré',
    'read'      => 'Lu',
];

$statusIcons = [
    'sent'      => 'bi-check-circle-fill',
    'failed'    => 'bi-x-circle-fill',
    'delivered' => 'bi-envelope-check-fill',
    'read'      => 'bi-eye-fill',
];

// ============================================
// CONSTRUCTION DE L'URL DE BASE POUR LA PAGINATION
// ============================================

$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = 'historique.php?' . http_build_query($queryParams);
$baseUrl = !empty($queryParams) ? $baseUrl . '&' : 'historique.php?';

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
<title>Historique WhatsApp - <?php echo APP_NAME; ?></title>
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
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        padding: 18px 20px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(37, 211, 102, 0.1);
        border-color: rgba(37, 211, 102, 0.2);
    }
    .stat-card .icon-wrap {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 18px;
        color: white;
        margin-bottom: 10px;
    }
    .stat-card .number { font-size: 24px; font-weight: 800; line-height: 1; color: #1a1a1a; }
    .stat-card .label {
        font-size: 10px; color: #9a8a7f; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em;
        margin-top: 4px;
    }

    .stat-card.primary .icon-wrap { background: linear-gradient(135deg, #25D366, #128C7E); }
    .stat-card.success .icon-wrap { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-card.info    .icon-wrap { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
    .stat-card.purple  .icon-wrap { background: linear-gradient(135deg, #a855f7, #d8b4fe); }
    .stat-card.danger  .icon-wrap { background: linear-gradient(135deg, #ef4444, #f87171); }
    .stat-card.orange  .icon-wrap { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

    /* ========== TABLE ========== */
    .table-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    .table-container .table { margin-bottom: 0; }
    .table-container .table thead th {
        background: rgba(252, 250, 248, 0.9);
        color: #6a5a4a;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-bottom: 1.5px solid rgba(240, 235, 229, 0.8);
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table-container .table td {
        vertical-align: middle;
        font-size: 13px;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        color: #1a1a1a;
    }
    .table-container .table tr:last-child td { border-bottom: none; }
    .table-container .table tbody tr:hover { background: rgba(37, 211, 102, 0.03); }
    .table-container code {
        font-size: 10px;
        background: rgba(37, 211, 102, 0.1);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #128C7E;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* ========== BADGE STATUT ========== */
    .badge-statut {
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 10px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .badge-statut.sent      { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .badge-statut.delivered { background: rgba(14, 165, 233, 0.15); color: #0c4a6e; }
    .badge-statut.read      { background: rgba(168, 85, 247, 0.15); color: #6b21a8; }
    .badge-statut.failed    { background: rgba(239, 68, 68, 0.12); color: #991b1b; }

    /* ========== BOUTONS ========== */
    .btn-whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        padding: 9px 20px;
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
    .btn-filtrer {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        padding: 7px 16px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .btn-filtrer:hover { transform: translateY(-1px); color: white; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); }
    .btn-reset {
        background: rgba(255, 255, 255, 0.9);
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        color: #6a5a4a;
        padding: 7px 14px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        transition: all 0.25s ease;
    }
    .btn-reset:hover { background: white; color: #25D366; border-color: #25D366; }

    .btn-action {
        width: 32px; height: 32px;
        border-radius: 9px;
        border: none;
        transition: all 0.25s ease;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 211, 102, 0.1);
        color: #25D366;
        cursor: pointer;
    }
    .btn-action:hover { background: linear-gradient(135deg, #25D366, #128C7E); color: white; transform: translateY(-2px); }

    /* ========== FILTRES ========== */
    .filters-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        padding: 12px;
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 12px;
        align-items: center;
    }
    .filters-bar .form-control,
    .filters-bar .form-select {
        border-radius: 9px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 7px 12px;
        font-size: 12px;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
    }
    .filters-bar .form-control:focus,
    .filters-bar .form-select:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.08);
        outline: none;
        background: white;
    }

    /* ========== PAGINATION ========== */
    .pagination-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 18px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination-custom .info { font-size: 12px; color: #9a8a7f; }
    .pagination-custom .pagination { margin: 0; gap: 4px; }
    .pagination-custom .pagination .page-link {
        border-radius: 9px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        color: #6a5a4a;
        padding: 6px 12px;
        font-size: 12px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }
    .pagination-custom .pagination .page-link:hover {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border-color: #25D366;
    }
    .pagination-custom .pagination .active .page-link {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border-color: #25D366;
    }
    .pagination-custom .pagination .disabled .page-link {
        opacity: 0.4;
        pointer-events: none;
    }

    /* ========== EMPTY ========== */
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
    .empty-state p { font-size: 13px; margin: 0; }

    /* ========== MODAL ========== */
    .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
        border-bottom: 2px dashed rgba(37, 211, 102, 0.15);
        padding: 18px 22px;
    }
    .modal-title {
        font-weight: 700;
        font-size: 15px;
        color: #1a1a1a;
    }
    .modal-body {
        padding: 20px 22px;
    }
    .modal-body pre {
        white-space: pre-wrap;
        font-family: 'Inter', sans-serif;
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        padding: 16px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.6;
        color: #1a1a1a;
        margin: 0;
    }
    .modal-footer {
        border-top: 1px solid rgba(240, 235, 229, 0.8);
        padding: 14px 22px;
    }

    /* ========== FOOTER ========== */
    .app-footer {
        text-align: center;
        padding: 30px 0 20px;
        color: #b8a99c;
        font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #25D366; }

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
        .table-container { padding: 18px; }
        .filters-bar { flex-direction: column; align-items: stretch; }
        .filters-bar .form-control,
        .filters-bar .form-select { width: 100%; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .table-container { padding: 14px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .stat-card { padding: 12px 10px; }
        .stat-card .number { font-size: 18px; }
        .stat-card .icon-wrap { width: 34px; height: 34px; font-size: 15px; }
        .stat-card .label { font-size: 9px; }
        .pagination-custom { flex-direction: column; align-items: center; text-align: center; }
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
                <h4><i class="bi bi-clock-history"></i> Historique WhatsApp</h4>
                <small><i class="bi bi-list-ul"></i> Historique des notifications envoyées</small>
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

            <!-- BOUTON NOUVEAU MESSAGE -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:15px">
                        <i class="bi bi-list-ul me-2" style="color:#25D366"></i>Messages envoyés
                    </h5>
                    <small style="color:#9a8a7f;font-size:12px">
                        <?php echo $total; ?> message(s) au total
                    </small>
                </div>
                <a href="envoyer.php" class="btn-whatsapp">
                    <i class="bi bi-plus-circle-fill"></i> Nouveau message
                </a>
            </div>

            <!-- STATISTIQUES -->
            <div class="row g-3 mb-4">
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card primary">
                        <div class="icon-wrap"><i class="bi bi-envelope-fill"></i></div>
                        <div class="number"><?php echo (int)($stats['total'] ?? 0); ?></div>
                        <div class="label">Total</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card success">
                        <div class="icon-wrap"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="number"><?php echo (int)($stats['sent'] ?? 0); ?></div>
                        <div class="label">Envoyés</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card info">
                        <div class="icon-wrap"><i class="bi bi-envelope-check-fill"></i></div>
                        <div class="number"><?php echo (int)($stats['delivered'] ?? 0); ?></div>
                        <div class="label">Délivrés</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card purple">
                        <div class="icon-wrap"><i class="bi bi-eye-fill"></i></div>
                        <div class="number"><?php echo (int)($stats['read'] ?? 0); ?></div>
                        <div class="label">Lus</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card danger">
                        <div class="icon-wrap"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="number"><?php echo (int)($stats['failed'] ?? 0); ?></div>
                        <div class="label">Échoués</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 fade-in">
                    <div class="stat-card orange">
                        <div class="icon-wrap"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="number">
                            <?php
                            $taux = ((int)($stats['sent'] ?? 0)) > 0
                                ? round((((int)($stats['delivered'] ?? 0)) / ((int)$stats['sent'])) * 100)
                                : 0;
                            echo $taux;
                            ?>%
                        </div>
                        <div class="label">Taux délivré</div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU -->
            <div class="table-container fade-in">

                <!-- FILTRES -->
                <form method="GET" action="" class="filters-bar">
                    <input type="text" name="search" class="form-control" style="flex:1;min-width:140px"
                           placeholder="Rechercher un téléphone..."
                           value="<?php echo htmlspecialchars($search); ?>">

                    <select name="status" class="form-select" style="min-width:130px">
                        <option value="">Tous les statuts</option>
                        <option value="sent"      <?php echo $status === 'sent'      ? 'selected' : ''; ?>>Envoyé</option>
                        <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Délivré</option>
                        <option value="read"      <?php echo $status === 'read'      ? 'selected' : ''; ?>>Lu</option>
                        <option value="failed"    <?php echo $status === 'failed'    ? 'selected' : ''; ?>>Échoué</option>
                    </select>

                    <input type="date" name="date_from" class="form-control" style="min-width:130px"
                           value="<?php echo htmlspecialchars($dateFrom); ?>">

                    <input type="date" name="date_to" class="form-control" style="min-width:130px"
                           value="<?php echo htmlspecialchars($dateTo); ?>">

                    <button type="submit" class="btn-filtrer">
                        <i class="bi bi-funnel-fill"></i> Filtrer
                    </button>
                    <a href="historique.php" class="btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </form>

                <!-- TABLE -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Destinataire</th>
                                <th>Template</th>
                                <th>Statut</th>
                                <th>Contenu</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>Aucun message trouvé</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $msg):
                                    $msgStatus = $msg['status'] ?? 'sent';
                                ?>
                                    <tr>
                                        <td style="white-space:nowrap;font-size:12px;color:#9a8a7f">
                                            <?php echo !empty($msg['created_at']) ? date('d/m/Y H:i', strtotime($msg['created_at'])) : '—'; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($msg['invite_nom'])): ?>
                                                <strong style="font-size:13px"><?php echo htmlspecialchars(($msg['invite_prenom'] ?? '') . ' ' . $msg['invite_nom']); ?></strong><br>
                                            <?php endif; ?>
                                            <small style="color:#9a8a7f;font-family:monospace">
                                                <i class="bi bi-telephone-fill" style="color:#25D366"></i>
                                                <?php echo htmlspecialchars($msg['to_phone'] ?? '—'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($msg['template_name'])): ?>
                                                <span style="background:rgba(107,114,128,0.12);color:#374151;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700">
                                                    <?php echo htmlspecialchars($msg['template_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="background:rgba(37,211,102,0.12);color:#128C7E;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700">
                                                    Personnalisé
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-statut <?php echo htmlspecialchars($msgStatus); ?>">
                                                <i class="bi <?php echo $statusIcons[$msgStatus] ?? 'bi-circle-fill'; ?>"></i>
                                                <?php echo $statusLabels[$msgStatus] ?? $msgStatus; ?>
                                            </span>
                                            <?php if (!empty($msg['delivered_at'])): ?>
                                                <br><small style="color:#9a8a7f;font-size:10px">
                                                    <i class="bi bi-check2-all"></i>
                                                    <?php echo date('H:i', strtotime($msg['delivered_at'])); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($msg['read_at'])): ?>
                                                <br><small style="color:#a855f7;font-size:10px">
                                                    <i class="bi bi-eye-fill"></i>
                                                    <?php echo date('H:i', strtotime($msg['read_at'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            // ⭐ On passe le contenu en JSON pour éviter l'injection JS
                                            $msgJson = htmlspecialchars(json_encode($msg['message_content'] ?? '', JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                            ?>
                                            <button type="button"
                                                    class="btn-action"
                                                    title="Voir le message"
                                                    onclick="showMessage(<?php echo $msgJson; ?>)">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($msgStatus === 'failed' && !empty($msg['error_message'])): ?>
                                                <span style="color:#991b1b;cursor:help;font-size:16px"
                                                      title="<?php echo htmlspecialchars($msg['error_message']); ?>">
                                                    <i class="bi bi-info-circle-fill"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-custom">
                        <div class="info">
                            <i class="bi bi-info-circle"></i>
                            Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                            • <?php echo $total; ?> message(s)
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
                                $endPage   = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
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
            </div>

            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-chat-text-fill" style="color:#25D366"></i>
                    Contenu du message
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <pre id="messageContent"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-reset" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Fermer
                </button>
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

// ========== AFFICHER LE MESSAGE ==========
function showMessage(message) {
    document.getElementById('messageContent').textContent = message || '(Message vide)';
    new bootstrap.Modal(document.getElementById('messageModal')).show();
}
</script>
</body>
</html>