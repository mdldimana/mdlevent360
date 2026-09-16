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
requirePermission('tables.assigner');

// Récupérer les informations de l'utilisateur courant
$user   = getCurrentUser();
$userId = (int)getCurrentUserId();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================
// RÉCUPÉRATION DE LA TABLE
// ============================================

$table = null;
try {
    $stmt = $pdo->prepare("
        SELECT t.*, e.nom AS evenement_nom
        FROM tables t
        JOIN evenements e ON t.id_evenement = e.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération table (assigner) : ' . $e->getMessage());
}

if (!$table) {
    header('Location: index.php');
    exit;
}

// ⭐ Vérifier l'accès à l'événement — redirection vers 403.php
if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, (int)$table['id_evenement'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
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
// FONCTION DE RECHARGEMENT DES DONNÉES
// ============================================

function chargerDonnees(PDO $pdo, int $idTable, int $idEvenement): array {
    $assignedGuests = [];
    $availableGuests = [];
    $totalAssigned = 0;

    try {
        // Invités assignés
        $stmt = $pdo->prepare("
            SELECT 
                it.id_invitation,
                i.code_unique,
                inv.nom,
                inv.prenom,
                inv.email,
                inv.telephone,
                inv.nombre_personnes AS nb_personnes
            FROM invitations_tables it
            JOIN invitations i ON it.id_invitation = i.id
            JOIN invites inv ON i.id_invite = inv.id
            WHERE it.id_table = ?
            ORDER BY inv.prenom ASC, inv.nom ASC
        ");
        $stmt->execute([$idTable]);
        $assignedGuests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalAssigned = array_sum(array_column($assignedGuests, 'nb_personnes'));

        // Invités disponibles
        $stmt = $pdo->prepare("
            SELECT 
                i.id,
                i.code_unique,
                inv.nom,
                inv.prenom,
                inv.email,
                inv.telephone,
                inv.nombre_personnes AS nb_personnes
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            LEFT JOIN invitations_tables it ON i.id = it.id_invitation
            WHERE i.id_evenement = ? 
              AND it.id_invitation IS NULL
              AND i.statut != 'ANNULEE' 
              AND i.statut != 'REFUSEE'
            ORDER BY inv.prenom ASC, inv.nom ASC
        ");
        $stmt->execute([$idEvenement]);
        $availableGuests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur chargement données (assigner) : ' . $e->getMessage());
    }

    return [$assignedGuests, $availableGuests, $totalAssigned];
}

// ============================================
// CHARGEMENT INITIAL
// ============================================

[$assignedGuests, $availableGuests, $totalAssigned] = chargerDonnees($pdo, $id, (int)$table['id_evenement']);

// ============================================
// TRAITEMENT DE L'ASSIGNATION
// ============================================

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Ajouter des invités ----
    if ($action === 'assign') {
        $invitations = $_POST['invitations'] ?? [];
        $invitations = array_filter(array_map('intval', (array)$invitations));

        if (empty($invitations)) {
            $error = 'Veuillez sélectionner au moins un invité.';
        } else {
            // Calculer le nombre total de personnes après assignation
            $totalPersonnes = $totalAssigned;
            foreach ($invitations as $invId) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT inv.nombre_personnes 
                        FROM invitations i
                        JOIN invites inv ON i.id_invite = inv.id
                        WHERE i.id = ?
                    ");
                    $stmt->execute([$invId]);
                    $nb = $stmt->fetchColumn();
                    $totalPersonnes += ($nb ? (int)$nb : 1);
                } catch (PDOException $e) {
                    error_log('Erreur calcul personnes : ' . $e->getMessage());
                }
            }

            if ($totalPersonnes > (int)$table['capacite_max']) {
                $error = 'Le nombre total de personnes (' . $totalPersonnes . ') dépasse la capacité maximum de la table (' . (int)$table['capacite_max'] . ').';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("
                        INSERT INTO invitations_tables (id_invitation, id_table, assignee_par, date_assignation)
                        VALUES (?, ?, ?, NOW())
                    ");
                    foreach ($invitations as $invId) {
                        $stmt->execute([$invId, $id, $userId]);
                    }
                    $pdo->commit();

                    if (function_exists('logAction')) {
                        logAction(
                            $userId,
                            'ASSIGN_TABLE',
                            'tables',
                            "Assignation de " . count($invitations) . " invitation(s) à la table '{$table['nom']}'"
                        );
                    }

                    $success = count($invitations) . ' invitation(s) assignée(s) avec succès !';

                    // Recharger les données
                    [$assignedGuests, $availableGuests, $totalAssigned] = chargerDonnees($pdo, $id, (int)$table['id_evenement']);

                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('Erreur assignation table : ' . $e->getMessage());
                    $error = 'Erreur lors de l\'assignation. Veuillez réessayer.';
                }
            }
        }
    }

    // ---- Retirer un invité ----
    if ($action === 'remove') {
        $invitation_id = (int)($_POST['invitation_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM invitations_tables WHERE id_invitation = ? AND id_table = ?");
            $stmt->execute([$invitation_id, $id]);

            if (function_exists('logAction')) {
                logAction(
                    $userId,
                    'UNASSIGN_TABLE',
                    'tables',
                    "Retrait d'un invité de la table '{$table['nom']}'"
                );
            }

            $success = 'Invité retiré de la table.';

            // Recharger les données
            [$assignedGuests, $availableGuests, $totalAssigned] = chargerDonnees($pdo, $id, (int)$table['id_evenement']);

        } catch (PDOException $e) {
            error_log('Erreur retrait invité : ' . $e->getMessage());
            $error = 'Erreur lors du retrait. Veuillez réessayer.';
        }
    }
}

$capaciteMax  = (int)$table['capacite_max'];
$tablePleine  = $totalAssigned >= $capaciteMax;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigner des invités - <?php echo APP_NAME; ?></title>
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
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);
            z-index: 150; opacity: 0; transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* ========== CONTENT ========== */
        .content-section { padding: 25px 30px; }

        .form-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 1100px;
            margin: 0 auto;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-container .form-title i { color: #c17c60; }

        /* ========== CARTE INFO ========== */
        .card-info {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
            border: 2px solid rgba(193, 124, 96, 0.15);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .card-info .item {
            font-size: 13px;
            color: #6a5a4a;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .card-info .item strong {
            color: #9a8a7f;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .card-info .item .value {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 14px;
        }
        .card-info .item .badge-occ {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-occ.occupee { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-occ.libre   { background: rgba(107, 114, 128, 0.15); color: #374151; }
        .badge-occ.pleine  { background: rgba(239, 68, 68, 0.15); color: #991b1b; }

        /* ========== ALERTES ========== */
        .alert-error, .alert-success-custom {
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            font-size: 14px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }
        .alert-error i { color: #dc2626; font-size: 18px; }
        .alert-success-custom {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #065f46;
        }
        .alert-success-custom i { color: #10b981; font-size: 18px; }

        /* ========== COLONNES ========== */
        .cols-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .col-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .col-header h6 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .col-header h6 i { color: #c17c60; font-size: 16px; }
        .col-header .count-badge {
            background: rgba(193, 124, 96, 0.12);
            color: #c17c60;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .col-header .count-badge.green {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
        }

        /* ⭐ BARRE DE RECHERCHE */
        .search-box {
            position: relative;
            margin-bottom: 12px;
        }
        .search-box input {
            width: 100%;
            padding: 10px 38px 10px 38px;
            border-radius: 12px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            background: rgba(255, 255, 255, 0.9);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
            background: white;
        }
        .search-box input::placeholder {
            color: #b8a99c;
            font-style: italic;
        }
        .search-box .search-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #c17c60;
            font-size: 15px;
            pointer-events: none;
        }
        .search-box .clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(193, 124, 96, 0.15);
            border: none;
            color: #c17c60;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s ease;
        }
        .search-box .clear-btn:hover {
            background: rgba(193, 124, 96, 0.25);
        }
        .search-box.has-value .clear-btn {
            display: flex;
        }

        .search-results-info {
            font-size: 11px;
            color: #9a8a7f;
            margin-bottom: 8px;
            padding: 0 4px;
            display: none;
        }
        .search-results-info.visible { display: block; }
        .search-results-info strong { color: #c17c60; }

        /* ========== LISTES ========== */
        .guest-grid {
            max-height: 500px;
            overflow-y: auto;
            padding: 5px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .guest-grid::-webkit-scrollbar { width: 5px; }
        .guest-grid::-webkit-scrollbar-track { background: transparent; }
        .guest-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 10px;
        }

        .guest-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.6);
            transition: all 0.25s ease;
            cursor: pointer;
            gap: 10px;
        }
        .guest-item:hover {
            background: rgba(193, 124, 96, 0.05);
            border-color: rgba(193, 124, 96, 0.3);
            transform: translateX(2px);
        }
        .guest-item.selected {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.12), rgba(212, 165, 116, 0.12));
            border-color: #c17c60;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.12);
        }
        .guest-item input[type="checkbox"] {
            accent-color: #c17c60;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .guest-item .guest-info { flex: 1; min-width: 0; }
        .guest-item .guest-info .name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .guest-item .guest-info .name .badge-pax {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }
        .guest-item .guest-info .details {
            font-size: 11px;
            color: #9a8a7f;
            margin-top: 3px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .guest-item .guest-info .details i { color: #c17c60; margin-right: 3px; }
        .guest-item .guest-code {
            font-family: 'Courier New', monospace;
            font-size: 10.5px;
            color: #c17c60;
            background: rgba(193, 124, 96, 0.1);
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .guest-item mark {
            background: linear-gradient(135deg, #fde68a, #fef3c7);
            color: #92400e;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 700;
        }

        /* Assigned */
        .assigned-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border: 1.5px solid rgba(16, 185, 129, 0.25);
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(52, 211, 153, 0.05));
            transition: all 0.25s ease;
            gap: 10px;
        }
        .assigned-item:hover {
            border-color: rgba(16, 185, 129, 0.4);
            transform: translateX(2px);
        }
        .assigned-item .guest-info { flex: 1; min-width: 0; }
        .assigned-item .guest-info .name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .assigned-item .guest-info .name .badge-pax {
            background: rgba(16, 185, 129, 0.2);
            color: #065f46;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }
        .assigned-item .guest-info .details {
            font-size: 11px;
            color: #9a8a7f;
            margin-top: 3px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .assigned-item .guest-info .details i { color: #10b981; margin-right: 3px; }
        .assigned-item .guest-code {
            font-family: 'Courier New', monospace;
            font-size: 10.5px;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .btn-remove {
            background: rgba(239, 68, 68, 0.12);
            color: #991b1b;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .btn-remove:hover {
            background: rgba(239, 68, 68, 0.22);
            transform: scale(1.05);
        }

        /* Empty states */
        .empty-col {
            text-align: center;
            padding: 30px 15px;
            color: #9a8a7f;
            font-size: 12px;
            background: rgba(251, 248, 245, 0.5);
            border: 2px dashed rgba(234, 227, 220, 0.6);
            border-radius: 12px;
        }
        .empty-col i {
            font-size: 32px;
            color: #d4c5b2;
            display: block;
            margin-bottom: 8px;
        }

        /* Boutons */
        .btn-save {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            font-weight: 700;
            padding: 11px 26px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none;
        }
        .btn-cancel {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-weight: 600;
            padding: 11px 26px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.95); color: #c17c60; }
        .btn-plan {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
            border: none;
            font-weight: 700;
            padding: 11px 26px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
        }
        .btn-plan:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.35);
            color: white;
        }

        /* Footer */
        .app-footer { text-align: center; padding: 30px 0 20px; color: #b8a99c; font-size: 13px; }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* Animations */
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
            .form-container { padding: 20px; }
            .cols-wrapper { grid-template-columns: 1fr; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 12px; }
            .form-container { padding: 15px; }
            .form-container .form-title { font-size: 15px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .btn-save, .btn-cancel, .btn-plan { width: 100%; justify-content: center; padding: 10px 16px; font-size: 13px; }
            .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
            .card-info { padding: 14px; gap: 10px; }
            .card-info .item { min-width: calc(50% - 5px); }
            .guest-item, .assigned-item { flex-wrap: wrap; }
            .guest-item .guest-code, .assigned-item .guest-code { width: 100%; text-align: center; }
            .assigned-item form { width: 100%; display: flex; justify-content: flex-end; }
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
                <h4><i class="bi bi-person-plus-fill"></i> Assigner des invités</h4>
                <small><i class="bi bi-table"></i> Table : <?php echo htmlspecialchars($table['nom']); ?></small>
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
            <div class="form-container fade-in">

                <h5 class="form-title"><i class="bi bi-person-plus-fill"></i> Assigner des invités à la table</h5>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-success-custom">
                        <i class="bi bi-check-circle-fill"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <!-- INFO TABLE -->
                <div class="card-info">
                    <div class="item">
                        <strong>Table</strong>
                        <span class="value"><?php echo htmlspecialchars($table['nom']); ?></span>
                    </div>
                    <div class="item">
                        <strong>Capacité</strong>
                        <span class="value"><?php echo (int)$table['capacite_min']; ?> - <?php echo $capaciteMax; ?> personnes</span>
                    </div>
                    <div class="item">
                        <strong>Zone</strong>
                        <span class="value"><?php echo htmlspecialchars($zoneLabels[$table['zone'] ?? 'SALLE_PRINCIPALE'] ?? ($table['zone'] ?? 'Salle')); ?></span>
                    </div>
                    <div class="item">
                        <strong>Occupation</strong>
                        <span class="badge-occ <?php echo $tablePleine ? 'pleine' : ($totalAssigned > 0 ? 'occupee' : 'libre'); ?>">
                            <i class="bi bi-people-fill"></i>
                            <?php echo $totalAssigned; ?> / <?php echo $capaciteMax; ?> personnes
                        </span>
                    </div>
                    <div class="item">
                        <strong>Événement</strong>
                        <span class="value"><?php echo htmlspecialchars($table['evenement_nom']); ?></span>
                    </div>
                </div>

                <div class="cols-wrapper">

                    <!-- ========== COLONNE GAUCHE : DISPONIBLES ========== -->
                    <div>
                        <div class="col-header">
                            <h6><i class="bi bi-people-fill"></i> Invités disponibles</h6>
                            <span class="count-badge" id="countAvailable"><?php echo count($availableGuests); ?></span>
                        </div>

                        <!-- ⭐ BARRE DE RECHERCHE -->
                        <div class="search-box" id="searchBoxAvailable">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text"
                                   id="searchAvailable"
                                   placeholder="Rechercher par nom, prénom ou code..."
                                   autocomplete="off">
                            <button type="button" class="clear-btn" id="clearAvailable" aria-label="Effacer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="search-results-info" id="infoAvailable"></div>

                        <?php if (!empty($availableGuests)): ?>
                            <form method="POST" action="" id="formAssign">
                                <input type="hidden" name="action" value="assign">

                                <div class="guest-grid" id="listAvailable">
                                    <?php foreach ($availableGuests as $guest):
                                        $nomComplet = trim(($guest['prenom'] ?? '') . ' ' . ($guest['nom'] ?? ''));
                                    ?>
                                        <label class="guest-item" data-name="<?php echo htmlspecialchars(mb_strtolower($nomComplet . ' ' . ($guest['code_unique'] ?? ''))); ?>">
                                            <input type="checkbox"
                                                   name="invitations[]"
                                                   value="<?php echo (int)$guest['id']; ?>"
                                                   onchange="toggleSelected(this)">
                                            <div class="guest-info">
                                                <div class="name">
                                                    <span class="guest-name"><?php echo htmlspecialchars($nomComplet); ?></span>
                                                    <span class="badge-pax">+<?php echo (int)($guest['nb_personnes'] ?? 1); ?> pers.</span>
                                                </div>
                                                <div class="details">
                                                    <?php if (!empty($guest['email'])): ?>
                                                        <span><i class="bi bi-envelope-fill"></i><?php echo htmlspecialchars($guest['email']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($guest['telephone'])): ?>
                                                        <span><i class="bi bi-phone-fill"></i><?php echo htmlspecialchars($guest['telephone']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="guest-code"><?php echo htmlspecialchars($guest['code_unique'] ?? '—'); ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="d-flex gap-3 mt-3 flex-wrap align-items-center">
                                    <button type="submit" class="btn-save" id="btnAssign" <?php echo $tablePleine ? 'disabled' : ''; ?>>
                                        <i class="bi bi-person-plus-fill"></i>
                                        <span id="btnAssignLabel">Assigner la sélection</span>
                                    </button>
                                    <?php if ($tablePleine): ?>
                                        <span style="color:#991b1b; font-size:13px; font-weight:600;">
                                            <i class="bi bi-exclamation-circle-fill"></i> Table pleine
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="empty-col">
                                <i class="bi bi-people"></i>
                                <p style="margin:0;">Aucun invité disponible à assigner.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Message "aucun résultat" pour la recherche -->
                        <div class="empty-col" id="noResultAvailable" style="display:none;">
                            <i class="bi bi-search"></i>
                            <p style="margin:0;">Aucun invité ne correspond à votre recherche.</p>
                        </div>
                    </div>

                    <!-- ========== COLONNE DROITE : ASSIGNÉS ========== -->
                    <div>
                        <div class="col-header">
                            <h6><i class="bi bi-person-check-fill"></i> Invités assignés</h6>
                            <span class="count-badge green" id="countAssigned"><?php echo count($assignedGuests); ?></span>
                        </div>

                        <!-- ⭐ BARRE DE RECHERCHE -->
                        <div class="search-box" id="searchBoxAssigned">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text"
                                   id="searchAssigned"
                                   placeholder="Rechercher un invité assigné..."
                                   autocomplete="off">
                            <button type="button" class="clear-btn" id="clearAssigned" aria-label="Effacer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="search-results-info" id="infoAssigned"></div>

                        <?php if (!empty($assignedGuests)): ?>
                            <div class="guest-grid" id="listAssigned">
                                <?php foreach ($assignedGuests as $guest):
                                    $nomComplet = trim(($guest['prenom'] ?? '') . ' ' . ($guest['nom'] ?? ''));
                                ?>
                                    <div class="assigned-item" data-name="<?php echo htmlspecialchars(mb_strtolower($nomComplet . ' ' . ($guest['code_unique'] ?? ''))); ?>">
                                        <div class="guest-info">
                                            <div class="name">
                                                <span class="guest-name"><?php echo htmlspecialchars($nomComplet); ?></span>
                                                <span class="badge-pax">+<?php echo (int)($guest['nb_personnes'] ?? 1); ?> pers.</span>
                                            </div>
                                            <div class="details">
                                                <?php if (!empty($guest['email'])): ?>
                                                    <span><i class="bi bi-envelope-fill"></i><?php echo htmlspecialchars($guest['email']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($guest['telephone'])): ?>
                                                    <span><i class="bi bi-phone-fill"></i><?php echo htmlspecialchars($guest['telephone']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="guest-code"><?php echo htmlspecialchars($guest['code_unique'] ?? '—'); ?></div>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="invitation_id" value="<?php echo (int)$guest['id_invitation']; ?>">
                                            <button type="submit" class="btn-remove"
                                                    onclick="return confirm('Retirer cet invité de la table ?')"
                                                    title="Retirer">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-col">
                                <i class="bi bi-person-x"></i>
                                <p style="margin:0;">Aucun invité assigné à cette table.</p>
                            </div>
                        <?php endif; ?>

                        <div class="empty-col" id="noResultAssigned" style="display:none;">
                            <i class="bi bi-search"></i>
                            <p style="margin:0;">Aucun invité assigné ne correspond à votre recherche.</p>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS BAS DE PAGE -->
                <div class="d-flex gap-3 mt-4 pt-3 flex-wrap" style="border-top: 2px dashed rgba(193,124,96,0.15);">
                    <a href="index.php?evenement=<?php echo (int)$table['id_evenement']; ?>" class="btn-cancel">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <a href="plan.php?evenement=<?php echo (int)$table['id_evenement']; ?>" class="btn-plan">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Voir le plan
                    </a>
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
// ⭐ RECHERCHE D'INVITÉS
// ============================================================

/**
 * Filtre une liste d'éléments selon un terme de recherche.
 * @param {HTMLElement} container - Le conteneur de la liste
 * @param {string} term - Le terme de recherche
 * @returns {number} - Nombre de résultats visibles
 */
function filterList(container, term) {
    if (!container) return 0;
    const items = container.querySelectorAll('[data-name]');
    const normalized = term.toLowerCase().trim();
    let visible = 0;

    items.forEach(item => {
        const name = (item.dataset.name || '').toLowerCase();
        if (!normalized || name.includes(normalized)) {
            item.style.display = '';
            visible++;
        } else {
            item.style.display = 'none';
        }
    });

    return visible;
}

/**
 * Met en évidence le terme de recherche dans un texte.
 */
function highlightMatches(container, term) {
    if (!container) return;
    const normalized = term.trim();

    // Restaurer les textes originaux
    container.querySelectorAll('.guest-name').forEach(el => {
        if (el.dataset.original) {
            el.innerHTML = el.dataset.original;
        }
    });

    if (!normalized) return;

    // Échapper les caractères spéciaux regex
    const escaped = normalized.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp('(' + escaped + ')', 'gi');

    container.querySelectorAll('.guest-name').forEach(el => {
        if (!el.dataset.original) {
            el.dataset.original = el.innerHTML;
        }
        el.innerHTML = el.dataset.original.replace(regex, '<mark>$1</mark>');
    });
}

// ---- Recherche : Invités disponibles ----
const searchAvailable   = document.getElementById('searchAvailable');
const clearAvailable    = document.getElementById('clearAvailable');
const searchBoxAvail    = document.getElementById('searchBoxAvailable');
const listAvailable     = document.getElementById('listAvailable');
const noResultAvailable = document.getElementById('noResultAvailable');
const infoAvailable     = document.getElementById('infoAvailable');
const countAvailable    = document.getElementById('countAvailable');

if (searchAvailable) {
    searchAvailable.addEventListener('input', function() {
        const term = this.value;
        const total = listAvailable ? listAvailable.querySelectorAll('[data-name]').length : 0;
        const visible = filterList(listAvailable, term);

        // Toggle clear button
        searchBoxAvail.classList.toggle('has-value', term.length > 0);

        // Highlight
        highlightMatches(listAvailable, term);

        // No result
        if (noResultAvailable) {
            noResultAvailable.style.display = (visible === 0 && total > 0) ? 'block' : 'none';
        }

        // Info
        if (infoAvailable) {
            if (term.trim()) {
                infoAvailable.innerHTML = '<strong>' + visible + '</strong> résultat(s) sur ' + total;
                infoAvailable.classList.add('visible');
            } else {
                infoAvailable.classList.remove('visible');
            }
        }

        // Count badge
        if (countAvailable) {
            countAvailable.textContent = term.trim() ? visible + '/' + total : total;
        }
    });
}

if (clearAvailable) {
    clearAvailable.addEventListener('click', function() {
        searchAvailable.value = '';
        searchAvailable.dispatchEvent(new Event('input'));
        searchAvailable.focus();
    });
}

// ---- Recherche : Invités assignés ----
const searchAssigned   = document.getElementById('searchAssigned');
const clearAssigned    = document.getElementById('clearAssigned');
const searchBoxAssigned = document.getElementById('searchBoxAssigned');
const listAssigned     = document.getElementById('listAssigned');
const noResultAssigned = document.getElementById('noResultAssigned');
const infoAssigned     = document.getElementById('infoAssigned');
const countAssigned    = document.getElementById('countAssigned');

if (searchAssigned) {
    searchAssigned.addEventListener('input', function() {
        const term = this.value;
        const total = listAssigned ? listAssigned.querySelectorAll('[data-name]').length : 0;
        const visible = filterList(listAssigned, term);

        searchBoxAssigned.classList.toggle('has-value', term.length > 0);
        highlightMatches(listAssigned, term);

        if (noResultAssigned) {
            noResultAssigned.style.display = (visible === 0 && total > 0) ? 'block' : 'none';
        }

        if (infoAssigned) {
            if (term.trim()) {
                infoAssigned.innerHTML = '<strong>' + visible + '</strong> résultat(s) sur ' + total;
                infoAssigned.classList.add('visible');
            } else {
                infoAssigned.classList.remove('visible');
            }
        }

        if (countAssigned) {
            countAssigned.textContent = term.trim() ? visible + '/' + total : total;
        }
    });
}

if (clearAssigned) {
    clearAssigned.addEventListener('click', function() {
        searchAssigned.value = '';
        searchAssigned.dispatchEvent(new Event('input'));
        searchAssigned.focus();
    });
}

// ============================================================
// SÉLECTION VISUELLE DES CHECKBOX + COMPTEUR BOUTON
// ============================================================
function toggleSelected(checkbox) {
    const item = checkbox.closest('.guest-item');
    if (item) {
        item.classList.toggle('selected', checkbox.checked);
    }
    updateAssignButton();
}

function updateAssignButton() {
    const btn = document.getElementById('btnAssign');
    const label = document.getElementById('btnAssignLabel');
    if (!btn || !label) return;

    const checked = document.querySelectorAll('#listAvailable input[type="checkbox"]:checked').length;
    if (checked > 0) {
        label.textContent = 'Assigner la sélection (' + checked + ')';
    } else {
        label.textContent = 'Assigner la sélection';
    }
}

// Réinitialiser les états visuels si erreur de soumission
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide success message
    const alertSuccess = document.querySelector('.alert-success-custom');
    if (alertSuccess) {
        setTimeout(() => {
            alertSuccess.style.transition = 'opacity 0.5s ease';
            alertSuccess.style.opacity = '0';
            setTimeout(() => alertSuccess.remove(), 500);
        }, 5000);
    }

    // Restaurer les cases cochées après un submit raté
    document.querySelectorAll('#listAvailable input[type="checkbox"]').forEach(cb => {
        if (cb.checked) {
            const item = cb.closest('.guest-item');
            if (item) item.classList.add('selected');
        }
    });
    updateAssignButton();
});
</script>
</body>
</html>