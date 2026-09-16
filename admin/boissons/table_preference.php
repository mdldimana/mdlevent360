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
// RÉCUPÉRATION DES ÉVÉNEMENTS ACCESSIBLES
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
// SÉLECTION DE L'ÉVÉNEMENT
// ============================================

$evenementSelectionneId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

if ($evenementSelectionneId === 0 && !empty($evenements)) {
    if (isset($_SESSION['selected_evenement_id']) && $_SESSION['selected_evenement_id'] > 0) {
        $evenementSelectionneId = (int)$_SESSION['selected_evenement_id'];
    } else {
        $evenementSelectionneId = (int)$evenements[0]['id'];
    }
}

if ($evenementSelectionneId > 0) {
    $_SESSION['selected_evenement_id'] = $evenementSelectionneId;
}

// Vérifier les droits d'accès à l'événement
$evenementSelectionne = null;
if ($evenementSelectionneId > 0) {
    foreach ($evenements as $ev) {
        if ((int)$ev['id'] === $evenementSelectionneId) {
            $evenementSelectionne = $ev;
            break;
        }
    }
}

$aucunEvenement = empty($evenements);
$accesRefuse = (!$aucunEvenement && !$evenementSelectionne);

// ============================================
// RÉCUPÉRATION DES PRÉFÉRENCES PAR TABLE
// ============================================

$tablesAvecPreferences = [];
$totalInvitesAvecChoix = 0;
$totalInvitesSansChoix = 0;

if ($evenementSelectionne && !$accesRefuse) {
    try {
        // Récupérer toutes les tables de l'événement avec leurs occupants et leurs choix
        $stmt = $pdo->prepare("
            SELECT 
                t.id AS table_id,
                t.nom AS table_nom,
                t.numero AS table_numero,
                t.zone AS table_zone,
                t.capacite_max,
                t.capacite_min,
                i.id AS invite_id,
                i.nom AS invite_nom,
                i.prenom AS invite_prenom,
                i.photo AS invite_photo,
                inv.id AS invitation_id,
                inv.code_unique,
                inv.statut AS invitation_statut,
                b.id AS boisson_id,
                b.nom AS boisson_nom,
                b.type AS boisson_type,
                pi.quantite
            FROM tables t
            LEFT JOIN invitations_tables it ON it.id_table = t.id
            LEFT JOIN invitations inv ON inv.id = it.id_invitation
            LEFT JOIN invites i ON i.id = inv.id_invite
            LEFT JOIN preferences_invitation pi ON pi.id_invitation = inv.id
            LEFT JOIN boissons b ON b.id = pi.id_boisson
            WHERE t.id_evenement = ?
            ORDER BY t.numero ASC, t.nom ASC, i.nom ASC, i.prenom ASC, b.nom ASC
        ");
        $stmt->execute([$evenementSelectionneId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser par table
        foreach ($rows as $row) {
            $tableId = (int)$row['table_id'];
            
            if (!isset($tablesAvecPreferences[$tableId])) {
                $tablesAvecPreferences[$tableId] = [
                    'id' => $tableId,
                    'nom' => $row['table_nom'] ?: ('Table ' . $row['table_numero']),
                    'numero' => $row['table_numero'],
                    'zone' => $row['table_zone'],
                    'capacite_max' => (int)$row['capacite_max'],
                    'capacite_min' => (int)$row['capacite_min'],
                    'invites' => [],
                ];
            }

            // Ajouter l'invité s'il existe
            if (!empty($row['invite_id'])) {
                $inviteId = (int)$row['invite_id'];
                
                if (!isset($tablesAvecPreferences[$tableId]['invites'][$inviteId])) {
                    $tablesAvecPreferences[$tableId]['invites'][$inviteId] = [
                        'id' => $inviteId,
                        'nom_complet' => trim(($row['invite_prenom'] ?? '') . ' ' . ($row['invite_nom'] ?? '')),
                        'boissons' => [],
                    ];
                }

                // Ajouter la boisson si elle existe
                if (!empty($row['boisson_id'])) {
                    $boissonId = (int)$row['boisson_id'];
                    $tablesAvecPreferences[$tableId]['invites'][$inviteId]['boissons'][$boissonId] = [
                        'nom' => $row['boisson_nom'],
                        'type' => $row['boisson_type'],
                        'quantite' => (int)$row['quantite'],
                    ];
                }
            }
        }

        // Compter les invités avec/sans choix
        foreach ($tablesAvecPreferences as $table) {
            foreach ($table['invites'] as $invite) {
                if (!empty($invite['boissons'])) {
                    $totalInvitesAvecChoix++;
                } else {
                    $totalInvitesSansChoix++;
                }
            }
        }

    } catch (PDOException $e) {
        error_log('Erreur préférences par table: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences par table - <?php echo APP_NAME; ?></title>
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

        /* ========== ÉTAPE 1 : SÉLECTEUR D'ÉVÉNEMENT ========== */
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
        .event-selector-card .step-title { flex: 1; min-width: 200px; }
        .event-selector-card .step-title h5 { font-weight: 700; color: #1a1a1a; margin: 0 0 4px; font-size: 18px; }
        .event-selector-card .step-title p { color: #9a8a7f; font-size: 13px; margin: 0; }

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

        /* ========== STATS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
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
        .stat-card .stat-icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .stat-card .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card .stat-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-card .stat-label { font-size: 12px; color: #9a8a7f; margin-top: 2px; }

        /* ========== BOUTONS D'ACTION ========== */
        .action-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ⭐ NOUVEAU BOUTON EXPORT BOISSONS PAR TABLE */
        .btn-export-boissons {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.3);
            font-size: 14px;
            cursor: pointer;
        }
        .btn-export-boissons:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.4);
            color: white;
        }
        .btn-export-boissons i { font-size: 16px; }

        .btn-back-boissons {
            background: rgba(255, 255, 255, 0.9);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-back-boissons:hover {
            background: white;
            color: #c17c60;
            border-color: #c17c60;
        }

        /* ========== SECTION TABLES ========== */
        .tables-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .table-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .table-card:hover {
            box-shadow: 0 12px 40px rgba(193, 124, 96, 0.12);
            border-color: rgba(193, 124, 96, 0.3);
        }
        .table-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #c17c60, #d4a574);
        }

        .table-card .table-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            flex-wrap: wrap;
            gap: 12px;
        }
        .table-card .table-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .table-card .table-title .table-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(193, 124, 96, 0.25);
        }
        .table-card .table-title h5 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 18px;
        }
        .table-card .table-title .table-meta {
            font-size: 12px;
            color: #9a8a7f;
            margin-top: 2px;
        }
        .table-card .table-info-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .table-card .table-info-badges .badge-info {
            background: rgba(193, 124, 96, 0.12);
            color: #c17c60;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ========== LISTE DES INVITÉS ========== */
        .invites-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .invite-row {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 18px;
            background: rgba(251, 248, 245, 0.6);
            border-radius: 14px;
            border: 1.5px solid rgba(234, 227, 220, 0.4);
            transition: all 0.3s ease;
        }
        .invite-row:hover {
            background: rgba(251, 248, 245, 0.9);
            border-color: rgba(193, 124, 96, 0.25);
            transform: translateX(3px);
        }

        .invite-row .invite-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
            min-width: 180px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 6px;
        }
        .invite-row .invite-name::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            flex-shrink: 0;
        }

        .invite-row .invite-boissons {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .boisson-tag {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.15));
            color: #c17c60;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(193, 124, 96, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .boisson-tag .qty {
            background: rgba(193, 124, 96, 0.25);
            color: #c17c60;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .no-choice {
            color: #b8a99c;
            font-style: italic;
            font-size: 13px;
            padding: 6px 14px;
        }

        /* ========== EMPTY STATE ========== */
        .empty-inline {
            text-align: center;
            padding: 30px;
            color: #9a8a7f;
            background: rgba(251, 248, 245, 0.5);
            border-radius: 12px;
            border: 2px dashed rgba(234, 227, 220, 0.6);
        }
        .empty-inline i {
            font-size: 40px;
            color: #d4c5b2;
            display: block;
            margin-bottom: 10px;
        }
        .empty-inline p { margin: 0; font-size: 13px; }

        /* ========== EMPTY STATE PRINCIPAL ========== */
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
            .top-bar { padding: 12px 15px 12px 70px; }
            body.sidebar-open { overflow-x: hidden !important; overflow-y: auto !important; }
            .content-section { padding: 15px; }
            .top-bar .page-title h4 { font-size: 1rem; }
            .top-bar .user-info .user-name { display: none; }
            .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .table-card { padding: 18px; }
            .invite-row { flex-direction: column; gap: 10px; }
            .invite-row .invite-name { min-width: auto; width: 100%; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 12px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .event-selector-card { padding: 18px; }
            .event-selector-card .step-title h5 { font-size: 16px; }
            .event-select-input { padding: 12px 15px; font-size: 14px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 12px 14px; gap: 10px; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 17px; }
            .stat-card .stat-number { font-size: 18px; }
            .stat-card .stat-label { font-size: 10px; }
            .table-card { padding: 15px; }
            .table-card .table-title h5 { font-size: 16px; }
            .invite-row { padding: 12px 14px; }
            .invite-row .invite-name { font-size: 14px; }
            .boisson-tag { font-size: 12px; padding: 5px 10px; }
            .action-bar { flex-direction: column; }
            .btn-export-boissons, .btn-back-boissons { width: 100%; justify-content: center; }
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

        <div class="top-bar no-print">
            <div class="page-title">
                <h4><i class="bi bi-table"></i> Préférences par table</h4>
                <small><i class="bi bi-list-ul"></i> Boissons choisies par les invités, regroupées par table</small>
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

            <!-- SI AUCUN ÉVÉNEMENT -->
            <?php if ($aucunEvenement): ?>
                <div class="empty-state-event fade-in">
                    <i class="bi bi-calendar-x"></i>
                    <h4>Aucun événement disponible</h4>
                    <p>
                        <?php if ($isAdminUser): ?>
                            Vous devez d'abord <strong>créer un événement</strong> avant de pouvoir voir les préférences par table.
                        <?php else: ?>
                            Vous n'êtes associé à <strong>aucun événement</strong> actuellement.
                            Contactez un administrateur.
                        <?php endif; ?>
                    </p>
                    <?php if ($isAdminUser && hasPermission('evenements.creer')): ?>
                        <a href="../evenements/creer.php" 
                           style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                            <i class="bi bi-plus-circle-fill"></i> Créer un événement
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif ($accesRefuse): ?>
                <div class="empty-state-event fade-in">
                    <i class="bi bi-shield-x"></i>
                    <h4>Accès refusé</h4>
                    <p>Vous n'avez pas accès à cet événement.</p>
                    <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
                        ← Retour
                    </a>
                </div>
            <?php else: ?>

                <!-- ⭐ ÉTAPE 1 : SÉLECTEUR D'ÉVÉNEMENT -->
                <div class="event-selector-card fade-in no-print">
                    <div class="step-header">
                        <div class="step-number">
                            <i class="bi bi-1-circle-fill"></i>
                        </div>
                        <div class="step-title">
                            <h5>Choisissez un événement</h5>
                            <p>Sélectionnez l'événement pour voir les préférences par table.</p>
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
                </div>

                <!-- STATS -->
                <div class="stats-grid fade-in no-print">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-table"></i></div>
                        <div>
                            <div class="stat-number"><?php echo count($tablesAvecPreferences); ?></div>
                            <div class="stat-label">Tables</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo $totalInvitesAvecChoix; ?></div>
                            <div class="stat-label">Avec choix</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div>
                            <div class="stat-number"><?php echo $totalInvitesSansChoix; ?></div>
                            <div class="stat-label">Sans choix</div>
                        </div>
                    </div>
                </div>

                <!-- ⭐ BARRE D'ACTION : EXPORT BOISSONS PAR TABLE -->
                <div class="action-bar fade-in no-print">
                    <a href="export_pdf.php?evenement=<?php echo $evenementSelectionneId; ?>" 
                       class="btn-export-boissons"
                       target="_blank"
                       rel="noopener">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        Exporter les boissons par table
                    </a>
                    <a href="index.php?evenement=<?php echo $evenementSelectionneId; ?>" class="btn-back-boissons">
                        <i class="bi bi-arrow-left"></i> Retour aux boissons
                    </a>
                </div>

                <!-- ⭐ SECTION TABLES -->
                <div class="tables-container fade-in">
                    <?php if (!empty($tablesAvecPreferences)): ?>
                        <?php foreach ($tablesAvecPreferences as $table): ?>
                            <div class="table-card">
                                <div class="table-header-custom">
                                    <div class="table-title">
                                        <div class="table-icon">
                                            <i class="bi bi-table"></i>
                                        </div>
                                        <div>
                                            <h5>Table <?php echo htmlspecialchars($table['nom']); ?></h5>
                                            <div class="table-meta">
                                                <?php if (!empty($table['zone'])): ?>
                                                    <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($table['zone']); ?>
                                                    &nbsp;•&nbsp;
                                                <?php endif; ?>
                                                <i class="bi bi-people-fill"></i> 
                                                <?php echo count($table['invites']); ?> invité(s)
                                                <?php if ($table['capacite_max'] > 0): ?>
                                                    / <?php echo $table['capacite_max']; ?> places
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-info-badges">
                                        <span class="badge-info">
                                            <i class="bi bi-cup-straw"></i> 
                                            <?php 
                                            $totalBoissons = 0;
                                            foreach ($table['invites'] as $inv) {
                                                $totalBoissons += count($inv['boissons']);
                                            }
                                            echo $totalBoissons;
                                            ?> boisson(s)
                                        </span>
                                    </div>
                                </div>

                                <?php if (!empty($table['invites'])): ?>
                                    <div class="invites-list">
                                        <?php foreach ($table['invites'] as $invite): ?>
                                            <div class="invite-row">
                                                <div class="invite-name">
                                                    <?php echo htmlspecialchars($invite['nom_complet']); ?>
                                                </div>
                                                <div class="invite-boissons">
                                                    <?php if (!empty($invite['boissons'])): ?>
                                                        <?php foreach ($invite['boissons'] as $boisson): ?>
                                                            <span class="boisson-tag">
                                                                <i class="bi bi-cup-hot-fill"></i>
                                                                <?php echo htmlspecialchars($boisson['nom']); ?>
                                                                <?php if ($boisson['quantite'] > 1): ?>
                                                                    <span class="qty">×<?php echo $boisson['quantite']; ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="no-choice">
                                                            <i class="bi bi-dash-circle"></i> Aucun choix
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-inline">
                                        <i class="bi bi-people"></i>
                                        <p>Aucun invité assigné à cette table</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-inline" style="padding: 60px 20px;">
                            <i class="bi bi-table" style="font-size: 60px;"></i>
                            <h5 style="color: #6a5a4a; margin-top: 15px;">Aucune table avec préférences</h5>
                            <p>
                                Aucun invité n'a encore choisi de boisson, ou aucune table n'a été créée pour cet événement.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

            <div class="app-footer no-print">
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
        window.location.href = 'table_preference.php?evenement=' + encodeURIComponent(eventId);
    }
}
</script>
</body>
</html>