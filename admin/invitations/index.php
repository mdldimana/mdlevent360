<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invitations.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// FILTRES ET PAGINATION
// ============================================

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$offset = ($page - 1) * $limit;

$filtre_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$filtre_statut = $_GET['statut'] ?? '';
$filtre_recherche = $_GET['search'] ?? '';

// ============================================
// RÉCUPÉRATION DES INVITATIONS ACCESSIBLES (filtrées par utilisateur)
// ============================================

$toutesInvitations = getInvitationsAccessibles($pdo, [
    'id_evenement' => $filtre_evenement ?: null,
    'statut' => $filtre_statut ?: null,
    'search' => $filtre_recherche ?: null,
]);

// Pagination manuelle
$totalCount = count($toutesInvitations);
$invitations = array_slice($toutesInvitations, $offset, $limit);

// ============================================
// RÉCUPÉRATION DES FILTRES
// ============================================

// ⭐ Événements accessibles à l'utilisateur
$evenements = getEvenementsPourSelect($pdo);

$statuts = ['EN_ATTENTE', 'CONFIRMEE', 'REFUSEE', 'PRESENTE', 'ANNULEE'];

// ============================================
// STATISTIQUES
// ============================================

$stats = [
    'total' => $totalCount,
    'en_attente' => 0,
    'confirmee' => 0,
    'presente' => 0,
    'avec_boissons' => 0,
    'avec_table' => 0,
];

foreach ($toutesInvitations as $inv) {
    $statut = strtolower($inv['statut']);
    if (isset($stats[$statut])) $stats[$statut]++;
    if (!empty($inv['nb_boissons']) && $inv['nb_boissons'] > 0) $stats['avec_boissons']++;
    if (!empty($inv['a_table'])) $stats['avec_table']++;
}

// ============================================
// PAGINATION
// ============================================

$totalPages = max(1, ceil($totalCount / $limit));

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
    'ajoute' => 'Invitation(s) créée(s) avec succès ! 🎉',
    'modifie' => 'Invitation modifiée avec succès ! ✅',
    'supprime' => 'Invitation supprimée avec succès ! 🗑️',
    'annule' => 'Invitation annulée ! ❌'
];

$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE' => 'Confirmée',
    'REFUSEE' => 'Refusée',
    'PRESENTE' => 'Présente',
    'ANNULEE' => 'Annulée'
];

// Configuration par statut (couleur + icône)
$statutConfig = [
    'EN_ATTENTE' => ['class' => 'en_attente', 'icon' => 'hourglass-split'],
    'CONFIRMEE' => ['class' => 'confirmee', 'icon' => 'check-circle-fill'],
    'REFUSEE' => ['class' => 'refusee', 'icon' => 'x-circle-fill'],
    'PRESENTE' => ['class' => 'presente', 'icon' => 'person-check-fill'],
    'ANNULEE' => ['class' => 'annulee', 'icon' => 'slash-circle-fill']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations - <?php echo APP_NAME; ?></title>
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
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
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
        .stat-card .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .stat-card .stat-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }
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

        /* ========== CARTE INVITATION ========== */
        .invitation-card {
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
        .invitation-card:hover {
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateY(-2px);
        }

        .invitation-card .invite-info { flex: 1; min-width: 200px; }
        .invitation-card .invite-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .invitation-card .invite-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(193, 124, 96, 0.2);
        }
        .invitation-card .invite-info .name {
            font-weight: 700; color: #1a1a1a; font-size: 15px;
        }
        .invitation-card .invite-info .details {
            font-size: 12px; color: #9a8a7f;
            display: flex; flex-wrap: wrap; gap: 10px;
            margin-bottom: 6px;
        }
        .invitation-card .invite-info .details i { color: #c17c60; margin-right: 4px; }
        .invitation-card .invite-info .code {
            font-size: 11px;
            font-family: 'Courier New', monospace;
            color: #c17c60;
            background: rgba(193, 124, 96, 0.1);
            padding: 3px 10px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 700;
            border: 1px solid rgba(193, 124, 96, 0.15);
        }

        .invitation-card .event-info {
            text-align: center;
            min-width: 160px;
            padding: 0 15px;
            border-left: 1px dashed rgba(234, 227, 220, 0.6);
            border-right: 1px dashed rgba(234, 227, 220, 0.6);
        }
        .invitation-card .event-info .event-name {
            font-weight: 600; font-size: 13px; color: #1a1a1a;
        }
        .invitation-card .event-info .event-date {
            font-size: 11px; color: #9a8a7f; margin-top: 3px;
        }
        .invitation-card .event-info .event-date i { color: #c17c60; margin-right: 4px; }
        
        /* Badges dans la carte */
        .badges-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
            justify-content: center;
        }
        .mini-badge {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .mini-badge.boissons { background: rgba(59, 130, 246, 0.12); color: #1e40af; }
        .mini-badge.table { background: rgba(16, 185, 129, 0.12); color: #065f46; }
        .mini-badge.none { background: rgba(154, 138, 127, 0.1); color: #9a8a7f; }

        .invitation-card .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        /* ========== BADGES STATUT ========== */
        .badge-statut {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
        }
        .badge-statut.en_attente { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-statut.confirmee { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-statut.refusee { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .badge-statut.presente { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-statut.annulee { background: rgba(107, 114, 128, 0.15); color: #374151; }

        /* ========== BOUTONS ACTION ========== */
        .btn-action {
            width: 36px; height: 36px;
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
        .btn-action.qr { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .btn-action.qr:hover { background: #10b981; color: white; }
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

        /* ========== ALERT SUCCÈS ========== */
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
            .invitation-card { flex-direction: column; align-items: stretch; text-align: center; }
            .invitation-card .invite-header { justify-content: center; }
            .invitation-card .invite-info .details { justify-content: center; }
            .invitation-card .event-info { border-left: none; border-right: none; border-top: 1px dashed rgba(234, 227, 220, 0.6); border-bottom: 1px dashed rgba(234, 227, 220, 0.6); padding: 15px 0; }
            .invitation-card .actions { justify-content: center; }
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
            .invitation-card { padding: 15px; }
            .invitation-card .invite-info .name { font-size: 14px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 12px 14px; gap: 10px; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 17px; }
            .stat-card .stat-number { font-size: 18px; }
            .stat-card .stat-label { font-size: 10px; }
            .pagination-custom { flex-direction: column; align-items: center; text-align: center; }
            .user-info-banner { font-size: 12px; padding: 10px 12px; }
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
        <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-envelope-paper-fill"></i> Gestion des invitations</h4>
                <small><i class="bi bi-list-ul"></i> Liste et gestion des invitations</small>
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

            <!-- Bannière info selon le rôle -->
            <?php if (!isAdmin()): ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Vous voyez uniquement les invitations des événements auxquels vous êtes <strong>associé</strong>.
                    </div>
                </div>
            <?php else: ?>
                <div class="user-info-banner fade-in">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        En tant qu'<strong>administrateur</strong>, vous voyez toutes les invitations de l'application.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-grid fade-in">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-envelope-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total invitations</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats['en_attente']; ?></div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats['confirmee']; ?></div>
                        <div class="stat-label">Confirmées</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-cup-straw"></i></div>
                    <div>
                        <div class="stat-number"><?php echo $stats['avec_boissons']; ?></div>
                        <div class="stat-label">Avec boissons</div>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div class="table-container fade-in">

                <div class="table-header">
                    <h5><i class="bi bi-envelope-paper-fill"></i> Liste des invitations</h5>
                    <?php if (hasPermission('invitations.creer')): ?>
                        <a href="creer.php" class="btn-add">
                            <i class="bi bi-plus-circle-fill"></i> Créer des invitations
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Filtres -->
                <form method="GET" action="" class="filters-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filtre_recherche); ?>" placeholder="Nom, code..." style="min-width: 150px;">
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-calendar-event"></i> Événement</label>
                        <select name="evenement">
                            <option value="0">Tous</option>
                            <?php foreach ($evenements as $e): ?>
                                <option value="<?php echo $e['id']; ?>" <?php echo $filtre_evenement == $e['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="bi bi-shield"></i> Statut</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <?php foreach ($statuts as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $filtre_statut == $s ? 'selected' : ''; ?>>
                                    <?php echo $statutLabels[$s] ?? $s; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                    <a href="index.php" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                </form>

                <?php if (!empty($invitations)): ?>
                    <?php foreach ($invitations as $inv): 
                        $statutClass = strtolower($inv['statut']);
                        $config = $statutConfig[$inv['statut']] ?? $statutConfig['EN_ATTENTE'];
                        $inviteInitiales = strtoupper(substr($inv['invite_prenom'] ?? 'U', 0, 1) . substr($inv['invite_nom'] ?? 'N', 0, 1));
                    ?>
                        <div class="invitation-card">
                            <!-- Invité -->
                            <div class="invite-info">
                                <div class="invite-header">
                                    <div class="invite-avatar"><?php echo $inviteInitiales ?: '?'; ?></div>
                                    <div>
                                        <div class="name">
                                            <?php echo htmlspecialchars(trim(($inv['invite_prenom'] ?? '') . ' ' . ($inv['invite_nom'] ?? ''))); ?>
                                        </div>
                                        <div class="details">
                                            <?php if (!empty($inv['invite_email'])): ?>
                                                <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($inv['invite_email']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($inv['invite_telephone'])): ?>
                                                <span><i class="bi bi-phone-fill"></i> <?php echo htmlspecialchars($inv['invite_telephone']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="code">
                                    <i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($inv['code_unique']); ?>
                                </div>
                            </div>
                            
                            <!-- Événement -->
                            <div class="event-info">
                                <div class="event-name"><?php echo htmlspecialchars($inv['evenement_nom']); ?></div>
                                <div class="event-date">
                                    <i class="bi bi-calendar-fill"></i> <?php echo date('d/m/Y', strtotime($inv['evenement_date'])); ?>
                                </div>
                                <span class="badge-statut <?php echo $statutClass; ?>">
                                    <i class="bi <?php echo $config['icon']; ?>"></i>
                                    <?php echo $statutLabels[$inv['statut']] ?? $inv['statut']; ?>
                                </span>
                                
                                <!-- Mini badges -->
                                <div class="badges-row">
                                    <?php if (!empty($inv['nb_boissons']) && $inv['nb_boissons'] > 0): ?>
                                        <span class="mini-badge boissons">
                                            <i class="bi bi-cup-straw"></i> <?php echo $inv['nb_boissons']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($inv['a_table'])): ?>
                                        <span class="mini-badge table">
                                            <i class="bi bi-table"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (empty($inv['nb_boissons']) && empty($inv['a_table'])): ?>
                                        <span class="mini-badge none">
                                            <i class="bi bi-dash-circle"></i> Non complété
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="actions">
                                <a href="voir.php?id=<?php echo $inv['id']; ?>" 
                                   class="btn-action voir" title="Voir">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="qr.php?code=<?php echo urlencode($inv['code_unique']); ?>"  
                                   class="btn-action qr" title="QR Code" target="_blank">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                                <?php if (hasPermission('invitations.modifier')): ?>
                                    <a href="modifier.php?id=<?php echo $inv['id']; ?>" 
                                       class="btn-action modifier" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (hasPermission('invitations.supprimer')): ?>
                                    <a href="supprimer.php?id=<?php echo $inv['id']; ?>" 
                                       class="btn-action supprimer" title="Supprimer"
                                       onclick="return confirm('Voulez-vous vraiment supprimer cette invitation ?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-custom">
                            <div class="info">
                                Affichage de <?php echo min($limit, $totalCount); ?> sur <?php echo $totalCount; ?> invitations
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
                        <i class="bi bi-envelope-paper"></i>
                        <h5>Aucune invitation</h5>
                        <?php if (isAdmin()): ?>
                            <p>Aucune invitation dans l'application</p>
                            <?php if (hasPermission('invitations.creer')): ?>
                                <a href="creer.php" class="btn-add" style="margin-top: 15px;">
                                    <i class="bi bi-plus-circle-fill"></i> Créer des invitations
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Vous n'avez aucune invitation dans vos événements</p>
                            <?php if (hasPermission('invitations.creer')): ?>
                                <a href="creer.php" class="btn-add" style="margin-top: 15px;">
                                    <i class="bi bi-plus-circle-fill"></i> Créer des invitations
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
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