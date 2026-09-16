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

requirePermission('presences.enregistrer');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();

$pdo = getDbConnection();

$error        = '';
$success      = '';
$invitation   = null;
$searchResults = [];
$code             = trim($_GET['code'] ?? '');
$search_nom       = trim($_GET['search_nom'] ?? '');
$search_prenom    = trim($_GET['search_prenom'] ?? '');
$search_evenement = (int)($_GET['search_evenement'] ?? 0);

$activeTab = 'camera';
if (!empty($code) || !empty($search_nom) || !empty($search_prenom) || $search_evenement > 0) {
    $activeTab = 'manual';
}

$errorMessages = [
    'complet'      => 'Toutes les places sont déjà occupées ✅',
    'invalide'     => 'Invitation non valide ou annulée.',
    'deja_present' => 'Déjà enregistrée.',
    'erreur'       => 'Erreur lors de l\'enregistrement.',
];
if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) $error = $errorMessages[$_GET['error']];
if (isset($_GET['success']) && $_GET['success'] === 'enregistree') $success = 'Présence enregistrée avec succès ! 🎉';

// ========== FONCTION VERIF ACCES EVENEMENT ==========
function userCanAccessEvent(PDO $pdo, int $userId, int $eventId, bool $isAdmin): bool {
    if ($isAdmin) return true;
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM evenements_utilisateurs WHERE id_utilisateur=? AND id_evenement=? LIMIT 1");
        $stmt->execute([$userId, $eventId]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        error_log('userCanAccessEvent: ' . $e->getMessage());
        return false;
    }
}

// ========== RECHERCHE PAR CODE ==========
if (!empty($code)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                i.id, i.code_unique, i.statut, i.nb_presents, i.id_evenement,
                inv.nom, inv.prenom, inv.email, inv.telephone, inv.nombre_personnes AS nb_places,
                e.nom AS evenement_nom, e.date_evenement, e.lieu,
                t.id AS table_id, t.nom AS table_nom, t.numero AS table_numero, t.zone AS table_zone
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            JOIN evenements e ON i.id_evenement = e.id 
            LEFT JOIN invitations_tables it ON i.id = it.id_invitation
            LEFT JOIN tables t ON it.id_table = t.id
            WHERE i.code_unique = ?
            LIMIT 1
        ");
        $stmt->execute([$code]); 
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invitation) {
            $error = "Aucune invitation trouvée pour : $code";
        } else {
            if (!userCanAccessEvent($pdo, $userId, (int)$invitation['id_evenement'], $isUserAdmin)) {
                $error = "🚫 Accès refusé : vous n'êtes pas associé à cet événement.";
                $invitation = null;
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, MAX(date_entree) AS last_date, MAX(heure_entree) AS last_heure FROM presences WHERE id_invitation=?");
                $stmt->execute([$invitation['id']]);
                $pres = $stmt->fetch();
                $invitation['total_entre'] = (int)($pres['cnt'] ?? 0);
                $invitation['presence_id'] = $invitation['total_entre'] > 0 ? 1 : null;
                $invitation['date_entree']  = $pres['last_date'] ?? null;
                $invitation['heure_entree'] = $pres['last_heure'] ?? null;
            }
        }
    } catch (PDOException $e) {
        error_log('Recherche code: ' . $e->getMessage());
        $error = 'Erreur vérification. Veuillez réessayer.';
    }
}

// ========== RECHERCHE MANUELLE ==========
if (!empty($search_nom) || !empty($search_prenom) || $search_evenement > 0) {
    try {
        $conds  = [];
        $params = [];
        if (!empty($search_nom))       { $conds[] = "inv.nom LIKE ?";       $params[] = '%' . $search_nom . '%'; }
        if (!empty($search_prenom))    { $conds[] = "inv.prenom LIKE ?";    $params[] = '%' . $search_prenom . '%'; }
        if ($search_evenement > 0)     { $conds[] = "i.id_evenement = ?";   $params[] = $search_evenement; }

        if (!$isUserAdmin) {
            $conds[]  = "i.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)";
            $params[] = $userId;
        }

        if (!empty($conds)) {
            $where = "WHERE " . implode(" AND ", $conds);
            $stmt = $pdo->prepare("
                SELECT i.id, i.code_unique, i.statut, i.nb_presents, i.id_evenement,
                    inv.nom, inv.prenom, inv.email, inv.nombre_personnes AS nb_places,
                    e.nom AS evenement_nom, 
                    t.id AS table_id, t.nom AS table_nom, t.numero AS table_numero
                FROM invitations i 
                JOIN invites inv ON i.id_invite = inv.id 
                JOIN evenements e ON i.id_evenement = e.id 
                LEFT JOIN invitations_tables it ON i.id = it.id_invitation
                LEFT JOIN tables t ON it.id_table = t.id
                $where 
                ORDER BY inv.nom ASC, inv.prenom ASC
                LIMIT 15
            ");
            $stmt->execute($params);
            $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Recherche manuelle: ' . $e->getMessage());
        $error = 'Erreur recherche. Veuillez réessayer.';
    }
}

// ========== ENREGISTREMENT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enregistrer') {
    $invitation_id  = (int)($_POST['invitation_id'] ?? 0);
    $nombre_present = max(1, (int)($_POST['nombre_present'] ?? 1));
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            SELECT i.id, i.id_evenement, i.statut, i.code_unique, i.nb_presents, 
                   inv.nombre_personnes AS nb_places 
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            WHERE i.id = ?
        ");
        $stmt->execute([$invitation_id]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inv) {
            $error = 'Invitation non trouvée';
            $pdo->rollBack();
        } elseif (!userCanAccessEvent($pdo, $userId, (int)$inv['id_evenement'], $isUserAdmin)) {
            $error = 'Accès refusé à cet événement';
            $pdo->rollBack();
        } elseif (in_array($inv['statut'], ['ANNULEE', 'REFUSEE'], true)) {
            $error = 'Invitation ' . strtolower($inv['statut']);
            $pdo->rollBack();
        } else {
            $nouveau_total = (int)$inv['nb_presents'] + $nombre_present;
            if ($nouveau_total > (int)$inv['nb_places']) {
                $error = 'Dépassement places max : ' . $inv['nb_places'];
                $pdo->rollBack();
            } else {
                $nouveau_statut = ($nouveau_total >= (int)$inv['nb_places']) ? 'PRESENTE' : 'PARTIELLE';
                $stmt = $pdo->prepare("
                    INSERT INTO presences (id_invitation, nombre_present, date_entree, heure_entree, utilisateur_id, created_at) 
                    VALUES (?, ?, CURDATE(), CURTIME(), ?, NOW())
                ");
                $stmt->execute([$invitation_id, $nombre_present, $userId]);

                $stmt = $pdo->prepare("UPDATE invitations SET nb_presents=?, statut=? WHERE id=?");
                $stmt->execute([$nouveau_total, $nouveau_statut, $invitation_id]);
                $pdo->commit();

                if (function_exists('logAction')) {
                    logAction($userId, 'CHECK_IN', 'presences', "Check-in {$inv['code_unique']} ($nouveau_total/{$inv['nb_places']})");
                }
                $success = 'Présence enregistrée ! ' . ($nouveau_total >= $inv['nb_places'] ? '🎉 Complète !' : '');
                
                // Recharger
                $stmt = $pdo->prepare("
                    SELECT i.id, i.code_unique, i.statut, i.nb_presents, i.id_evenement,
                           inv.nom, inv.prenom, inv.email, inv.telephone, inv.nombre_personnes AS nb_places,
                           e.nom AS evenement_nom, e.date_evenement, e.lieu,
                           t.id AS table_id, t.nom AS table_nom, t.numero AS table_numero, t.zone AS table_zone
                    FROM invitations i 
                    JOIN invites inv ON i.id_invite = inv.id 
                    JOIN evenements e ON i.id_evenement = e.id 
                    LEFT JOIN invitations_tables it ON i.id = it.id_invitation
                    LEFT JOIN tables t ON it.id_table = t.id
                    WHERE i.id = ?
                ");
                $stmt->execute([$invitation_id]);
                $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

                $activeTab = 'manual';
                $search_nom = '';
                $search_prenom = '';
                $search_evenement = 0;
                $code = '';
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Enregistrement présence: ' . $e->getMessage());
        $error = 'Erreur lors de l\'enregistrement. Veuillez réessayer.';
    }
}

if ($invitation) {
    $search_nom = '';
    $search_prenom = '';
    $search_evenement = 0;
}

$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE'  => 'Confirmée',
    'REFUSEE'    => 'Refusée',
    'PRESENTE'   => '✅ Complète',
    'PARTIELLE'  => '⏳ Partielle',
    'ANNULEE'    => 'Annulée',
];
$statutColors = [
    'EN_ATTENTE' => 'secondary',
    'CONFIRMEE'  => 'success',
    'REFUSEE'    => 'danger',
    'PRESENTE'   => 'info',
    'PARTIELLE'  => 'warning',
    'ANNULEE'    => 'dark',
];
$zoneLabels = [
    'TERRASSE'         => 'Terrasse 🌿',
    'SALLE_PRINCIPALE' => 'Salle principale 🏠',
    'SALON'            => 'Salon 🛋️',
    'MEZZANINE'        => 'Mezzanine 🏗️',
    'VIP'              => 'VIP ⭐',
    'EXTERIEUR'        => 'Extérieur 🌳',
];

$evenements = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT id, nom FROM evenements 
            WHERE id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur=?) 
              AND statut != 'ANNULE' 
            ORDER BY nom
        ");
        $stmt->execute([$userId]);
        $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $evenements = $pdo->query("SELECT id, nom FROM evenements WHERE statut != 'ANNULE' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Load événements: ' . $e->getMessage());
}

$planBaseUrl = BASE_PATH . '/admin/tables/plan.php';

$userInitiales = strtoupper(
    substr($user['prenom'] ?? 'U', 0, 1) . 
    substr($user['nom'] ?? 'N', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Contrôle - <?php echo APP_NAME; ?></title>
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

    /* ========== LAYOUT (comme evenements/index) ========== */
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
    .content-section { padding: 20px 24px; max-width: 980px; margin: 0 auto; width: 100%; }

    .scanner-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }

    /* Onglets */
    .scan-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        padding: 6px;
        border-radius: 14px;
        margin-bottom: 20px;
    }
    .scan-tab {
        border: 0;
        background: transparent;
        padding: 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        color: #9a8a7f;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .scan-tab.active {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        box-shadow: 0 2px 8px rgba(193, 124, 96, 0.25);
    }

    /* Résultat */
    .result-highlight {
        border: 2px solid #c17c60;
        box-shadow: 0 12px 30px rgba(193, 124, 96, 0.12);
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
    }
    .result-highlight::before {
        content: 'RÉSULTAT TROUVÉ';
        position: absolute;
        top: -10px; left: 20px;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.1em;
        padding: 4px 12px;
        border-radius: 20px;
    }

    .table-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(253, 248, 245, 0.8);
        border: 1.5px solid #eadcd1;
        border-radius: 12px;
        padding: 8px 14px;
        margin-top: 10px;
        font-weight: 600;
        text-decoration: none;
        color: #1a1a1a;
        transition: all 0.2s;
    }
    .table-badge:hover { transform: translateY(-1px); border-color: #c17c60; background: white; color: #1a1a1a; }
    .table-badge .num {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .progress-ring {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        padding: 6px 14px 6px 6px;
        border-radius: 40px;
    }
    .progress-ring .bar {
        width: 120px;
        height: 6px;
        background: #f0ebe5;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-ring .bar .fill {
        height: 100%;
        background: linear-gradient(90deg, #c17c60, #d4a574);
        border-radius: 10px;
        transition: width 0.6s ease;
    }

    /* Scanner */
    #reader {
        width: 100%;
        min-height: 480px;
        height: 58vh;
        max-height: 620px;
        border-radius: 20px;
        overflow: hidden;
        background: #111;
        border: 1px solid #1a1a1a;
    }
    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    .qr-overlay { position: relative; border-radius: 20px; overflow: hidden; }
    .scan-frame {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 5;
    }
    .scan-frame.show { display: flex; }
    .scan-frame .box {
        width: 84%;
        max-width: 380px;
        aspect-ratio: 1/1;
        border: 2.5px solid #c17c60;
        border-radius: 28px;
        box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.6);
        position: relative;
    }
    .scan-frame .box .corner {
        position: absolute;
        width: 24px;
        height: 24px;
        border-color: #d4a574;
        border-style: solid;
        border-width: 3px;
    }
    .corner-tl { top: -2px; left: -2px; border-right: 0; border-bottom: 0; border-radius: 10px 0 0 0; }
    .corner-tr { top: -2px; right: -2px; border-left: 0; border-bottom: 0; border-radius: 0 10px 0 0; }
    .corner-bl { bottom: -2px; left: -2px; border-right: 0; border-top: 0; border-radius: 0 0 0 10px; }
    .corner-br { bottom: -2px; right: -2px; border-left: 0; border-top: 0; border-radius: 0 0 10px 0; }

    #scan-line {
        position: absolute;
        left: 8%; right: 8%;
        height: 3px;
        background: linear-gradient(90deg, transparent, #d4a574, transparent);
        animation: scanMove 2.4s ease-in-out infinite;
        border-radius: 4px;
    }
    @keyframes scanMove { 0%{top:14%} 50%{top:86%} 100%{top:14%} }

    .scan-hint {
        position: absolute;
        bottom: 10%;
        left: 50%;
        transform: translateX(-50%);
        color: rgba(255, 255, 255, 0.9);
        font-size: 12px;
        font-weight: 600;
        background: rgba(0, 0, 0, 0.5);
        padding: 6px 16px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* Drop zone */
    .drop-zone {
        border: 2px dashed #e5ddd3;
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        background: rgba(252, 250, 248, 0.8);
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        cursor: pointer;
        transition: 0.2s;
        flex-wrap: wrap;
    }
    .drop-zone:hover, .drop-zone.dragover {
        border-color: #c17c60;
        background: rgba(253, 248, 245, 0.9);
    }

    /* Boutons */
    .btn-gold {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: 0;
        font-weight: 700;
        border-radius: 10px;
        padding: 11px 18px;
        transition: 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .btn-gold:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(193, 124, 96, 0.25);
        color: white;
    }

    /* Résultats recherche */
    .search-item {
        padding: 12px 14px;
        border-bottom: 1px solid #f0ebe5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        background: white;
    }
    .search-item:hover { background: rgba(252, 250, 248, 0.8); }

    .table-link-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(253, 248, 245, 0.9);
        border: 1px solid #eadcd1;
        border-radius: 10px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 600;
        color: #1a1a1a;
        text-decoration: none;
    }
    .table-link-badge:hover { background: white; border-color: #c17c60; color: #1a1a1a; }

    .btn-new-search {
        background: rgba(252, 250, 248, 0.9);
        border: 1px solid #e5ddd3;
        color: #6a5a4a;
        font-weight: 600;
        border-radius: 10px;
        padding: 9px 18px;
        text-decoration: none;
        font-size: 13px;
        transition: 0.2s;
    }
    .btn-new-search:hover { background: white; color: #c17c60; border-color: #c17c60; }

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
        .content-section { padding: 14px; }
        .top-bar .page-title h4 { font-size: 1rem; }
        .top-bar .user-info .user-name { display: none; }
        #reader { min-height: 360px; height: 52vh; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px; }
        .scanner-card { padding: 14px; border-radius: 14px; }
        #reader { min-height: 280px; height: 44vh; }
        .scan-tabs .scan-tab { font-size: 11px; padding: 10px 6px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
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
                <h4><i class="bi bi-qr-code-scan"></i> Contrôle d'entrée</h4>
                <small>
                    <?php if ($isUserAdmin): ?>
                        <i class="bi bi-shield-check"></i> Admin • Tous événements
                    <?php else: ?>
                        <i class="bi bi-funnel"></i> Filtré • <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                    <?php endif; ?>
                    • Scan • Photo QR • Recherche
                </small>
            </div>
            <div class="user-info">
                <span class="role-badge">
                    <i class="bi bi-shield-check"></i>
                    <?php echo $isUserAdmin ? 'ADMIN' : 'USER'; ?>
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

            <?php if ($invitation): ?>
            <div class="result-highlight" id="resultBlock">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#c17c60,#d4a574);display:grid;place-items:center;font-weight:800;color:white;font-size:18px">
                                <?php echo strtoupper(substr($invitation['prenom'], 0, 1) . substr($invitation['nom'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold fs-5 lh-1"><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></div>
                                <div class="small" style="color:#9a8a7f"><?php echo htmlspecialchars($invitation['email'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div class="mt-2 fw-semibold" style="color:#c17c60">
                            <i class="bi bi-calendar-event"></i> 
                            <?php echo htmlspecialchars($invitation['evenement_nom']); ?> • <?php echo date('d/m/Y', strtotime($invitation['date_evenement'])); ?>
                        </div>
                        <code style="margin-top:6px;display:inline-block;background:#fcfaf8;border:1px solid #f0ebe5;padding:4px 10px;border-radius:8px;font-size:11px;color:#c17c60;">
                            <?php echo htmlspecialchars($invitation['code_unique']); ?>
                        </code>
                        
                        <?php 
                        $nb_places   = (int)($invitation['nb_places'] ?? 1);
                        $nb_presents = (int)($invitation['nb_presents'] ?? 0);
                        $pourcentage = $nb_places > 0 ? round(($nb_presents / $nb_places) * 100) : 0;
                        $reste       = $nb_places - $nb_presents;
                        ?>
                        <div class="mt-3">
                            <div class="progress-ring">
                                <span class="fw-bold" style="font-size:13px"><?php echo $nb_presents; ?>/<?php echo $nb_places; ?></span>
                                <div class="bar"><div class="fill" style="width: <?php echo $pourcentage; ?>%"></div></div>
                                <span class="small" style="color:#9a8a7f"><?php echo $pourcentage; ?>%</span>
                            </div>
                            <?php if ($reste > 0): ?>
                                <div class="small mt-1" style="color:#9a8a7f"><i class="bi bi-clock-history"></i> Encore <?php echo $reste; ?> à venir</div>
                            <?php else: ?>
                                <div class="small mt-1" style="color:#10b981"><i class="bi bi-check-circle-fill"></i> Toutes les personnes présentes !</div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($invitation['table_id']) && !empty($invitation['table_nom'])): ?>
                            <a href="<?php echo $planBaseUrl; ?>?evenement=<?php echo $invitation['id_evenement']; ?>&table=<?php echo $invitation['table_id']; ?>" class="table-badge" target="_blank" rel="noopener">
                                <i class="bi bi-table" style="color:#c17c60"></i> <?php echo htmlspecialchars($invitation['table_nom']); ?>
                                <?php if (!empty($invitation['table_numero'])): ?><span class="num">#<?php echo htmlspecialchars($invitation['table_numero']); ?></span><?php endif; ?>
                                <?php if (!empty($invitation['table_zone'])): ?><span style="font-size:11px;color:#9a8a7f"><?php echo $zoneLabels[$invitation['table_zone']] ?? $invitation['table_zone']; ?></span><?php endif; ?>
                                <span style="margin-left:6px;background:#1a1a1a;color:white;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700">
                                    <i class="bi bi-arrow-right-circle"></i> Plan
                                </span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <?php if ($invitation['statut'] === 'PARTIELLE'): ?>
                            <span style="background:#fef3c7;color:#92400e;padding:6px 14px;border-radius:20px;font-weight:700;font-size:12px">⏳ Partielle (<?php echo $nb_presents; ?>/<?php echo $nb_places; ?>)</span>
                        <?php elseif ($invitation['statut'] === 'PRESENTE'): ?>
                            <span style="background:#d1fae5;color:#065f46;padding:6px 14px;border-radius:20px;font-weight:700;font-size:12px">✅ Complète</span>
                        <?php else: ?>
                            <span class="badge bg-<?php echo $statutColors[$invitation['statut']] ?? 'secondary'; ?> fs-6 px-3 py-2 rounded-pill">
                                <?php echo $statutLabels[$invitation['statut']] ?? $invitation['statut']; ?>
                            </span>
                        <?php endif; ?>

                        <?php if (($invitation['total_entre'] ?? 0) > 0 && $invitation['statut'] === 'PRESENTE'): ?>
                            <div class="small mt-2" style="color:#10b981">
                                <i class="bi bi-check-circle-fill"></i> 
                                Entrée le <?php echo date('d/m/Y H:i', strtotime(($invitation['date_entree'] ?? '').' '.($invitation['heure_entree'] ?? ''))); ?>
                            </div>
                        <?php elseif ($reste > 0 && !in_array($invitation['statut'], ['ANNULEE', 'REFUSEE'], true)): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="enregistrer">
                                <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                <div class="input-group mb-2">
                                    <span class="input-group-text" style="background:#fcfaf8;border-color:#e5ddd3">👤</span>
                                    <input type="number" class="form-control" name="nombre_present" value="1" min="1" max="<?php echo $reste; ?>" style="border-color:#e5ddd3">
                                    <span class="input-group-text" style="background:#fcfaf8;font-size:12px;border-color:#e5ddd3">/ <?php echo $reste; ?></span>
                                </div>
                                <button class="btn-gold w-100 py-2">
                                    <i class="bi bi-box-arrow-in-right"></i> Enregistrer (<?php echo $reste; ?> restant<?php echo $reste > 1 ? 's' : ''; ?>)
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center mt-3 pt-3" style="border-top:1px dashed #f0ebe5">
                    <a href="?reset=1" class="btn-new-search">
                        <i class="bi bi-arrow-counterclockwise"></i> Nouvelle recherche
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="scanner-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3" style="background:#fee2e2;border-color:#fecaca;color:#991b1b">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success rounded-3" style="background:#d1fae5;border-color:#a7f3d0;color:#065f46">
                        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <div class="scan-tabs">
                    <button class="scan-tab <?php echo $activeTab === 'camera' ? 'active' : ''; ?>" id="tab-camera" onclick="switchTab('camera')">
                        <i class="bi bi-camera"></i> Caméra
                    </button>
                    <button class="scan-tab <?php echo $activeTab === 'upload' ? 'active' : ''; ?>" id="tab-upload" onclick="switchTab('upload')">
                        <i class="bi bi-image"></i> Photo QR
                    </button>
                    <button class="scan-tab <?php echo $activeTab === 'manual' ? 'active' : ''; ?>" id="tab-manual" onclick="switchTab('manual')">
                        <i class="bi bi-people"></i> Recherche
                    </button>
                </div>

                <div id="reader-container" style="<?php echo $activeTab === 'camera' ? 'display:block' : 'display:none'; ?>">
                    <div class="qr-overlay">
                        <div id="reader"></div>
                        <div class="scan-frame <?php echo $activeTab === 'camera' ? 'show' : ''; ?>" id="scanFrame">
                            <div class="box">
                                <div class="corner corner-tl"></div>
                                <div class="corner corner-tr"></div>
                                <div class="corner corner-bl"></div>
                                <div class="corner corner-br"></div>
                                <div id="scan-line"></div>
                                <div class="scan-hint"><i class="bi bi-qr-code me-1"></i> Placez le QR au centre</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        <button class="btn-gold flex-fill py-3" id="btnStart">
                            <i class="bi bi-camera-video"></i> Démarrer caméra
                        </button>
                        <button class="btn btn-dark rounded-3 px-4" id="btnStop" style="display:none">
                            <i class="bi bi-stop-circle"></i> Stop
                        </button>
                        <button class="btn btn-outline-secondary rounded-3 px-4" id="btnFlash" style="border-color:#e5ddd3">
                            <i class="bi bi-lightning-fill"></i> Flash
                        </button>
                    </div>
                    <div class="small mt-2" style="color:#9a8a7f">
                        <i class="bi bi-lightbulb"></i> Autorisez la caméra et placez le QR dans le cadre.
                    </div>
                </div>

                <div id="upload-container" style="<?php echo $activeTab === 'upload' ? 'display:block' : 'display:none'; ?>">
                    <div class="drop-zone" id="dropZone" onclick="document.getElementById('qrFile').click()">
                        <i class="bi bi-cloud-arrow-up" style="color:#c17c60;font-size:28px"></i>
                        <div class="text-start">
                            <div class="fw-bold">Charger une photo du QR</div>
                            <div class="small" style="color:#9a8a7f">Clique ou glisse ici (JPG, PNG, WEBP)</div>
                        </div>
                        <img id="preview" style="max-width:80px;border-radius:10px;display:none">
                    </div>
                    <input type="file" id="qrFile" accept="image/*" hidden>
                    <div id="uploadProgress" style="display:none" class="mt-2">
                        <div class="spinner-border spinner-border-sm" style="color:#c17c60"></div> Analyse en cours...
                    </div>
                    <div id="uploadResult" class="mt-2"></div>
                </div>

                <div id="manual-container" style="<?php echo $activeTab === 'manual' ? 'display:block' : 'display:none'; ?>">
                    <form method="GET" class="row g-2">
                        <div class="col-12">
                            <label class="small fw-bold" style="font-size:12px">
                                Rechercher un invité (filtré à vos événements)
                            </label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search_nom" value="<?php echo htmlspecialchars($search_nom); ?>" placeholder="Nom" style="border-color:#e5ddd3">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search_prenom" value="<?php echo htmlspecialchars($search_prenom); ?>" placeholder="Prénom" style="border-color:#e5ddd3">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="search_evenement" style="border-color:#e5ddd3">
                                        <option value="0">Tous mes événements</option>
                                        <?php foreach ($evenements as $ev): ?>
                                            <option value="<?php echo $ev['id']; ?>" <?php echo $search_evenement == $ev['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ev['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn-gold w-100" type="submit" style="height:100%"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr class="my-2" style="border-color:#f0ebe5">
                            <label class="small fw-bold" style="font-size:12px">Ou par code</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#fcfaf8;border-color:#e5ddd3">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text" class="form-control" name="code" id="codeInput" value="<?php echo htmlspecialchars($code); ?>" placeholder="INV-2026-XXXX" style="border-color:#e5ddd3">
                                <button class="btn-gold" type="submit">
                                    <i class="bi bi-search"></i> Vérifier
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if ($searchResults): ?>
                        <div class="mt-4 border rounded-4 overflow-hidden" style="border-color:#f0ebe5!important;background:rgba(252,250,248,0.6)">
                            <div class="p-2 px-3 fw-bold small bg-white" style="border-bottom:1px solid #f0ebe5">
                                <i class="bi bi-people-fill me-1" style="color:#c17c60"></i> 
                                <?php echo count($searchResults); ?> résultat(s)
                            </div>
                            <?php foreach ($searchResults as $r): ?>
                                <div class="search-item">
                                    <div>
                                        <div class="fw-bold" style="font-size:14px"><?php echo htmlspecialchars($r['prenom'].' '.$r['nom']); ?></div>
                                        <small style="color:#9a8a7f">
                                            <?php echo htmlspecialchars($r['evenement_nom']); ?> • 
                                            <code style="font-size:10px;background:#fdf8f5;border:1px solid #eadcd1;color:#c17c60;padding:2px 6px;border-radius:6px">
                                                <?php echo htmlspecialchars($r['code_unique']); ?>
                                            </code>
                                            <?php if (!empty($r['table_id'])): ?>
                                                <a href="<?php echo $planBaseUrl; ?>?evenement=<?php echo $r['id_evenement']; ?>&table=<?php echo $r['table_id']; ?>" target="_blank" rel="noopener" class="table-link-badge">
                                                    <i class="bi bi-table"></i> <?php echo htmlspecialchars($r['table_nom']); ?>
                                                    <?php if (!empty($r['table_numero'])): ?>#<?php echo htmlspecialchars($r['table_numero']); ?><?php endif; ?>
                                                </a>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="?code=<?php echo urlencode($r['code_unique']); ?>" class="btn btn-sm btn-dark rounded-pill" style="background:#1a1a1a">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($search_nom) || !empty($search_prenom) || $search_evenement > 0): ?>
                        <div class="small mt-3 text-center py-3" style="color:#9a8a7f">
                            <i class="bi bi-search fs-4 d-block mb-2"></i>Aucun invité trouvé.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ========== SIDEBAR (identique à evenements/index) ==========
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

// ========== SCANNER QR ==========
let html5QrCode = null;
let isScanning = false;
let flashOn = false;

function switchTab(t) {
    document.querySelectorAll('.scan-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + t).classList.add('active');
    document.getElementById('reader-container').style.display = t === 'camera' ? 'block' : 'none';
    document.getElementById('upload-container').style.display = t === 'upload' ? 'block' : 'none';
    document.getElementById('manual-container').style.display = t === 'manual' ? 'block' : 'none';
    const sf = document.getElementById('scanFrame');
    if (t === 'camera' && isScanning) sf.classList.add('show');
    else sf.classList.remove('show');
    if (t !== 'camera' && isScanning) stopCamera();
}

function extractCode(txt) {
    try {
        const u = new URL(txt);
        const c = u.searchParams.get('code');
        if (c) return c;
    } catch (e) {}
    const m = txt.match(/INV-[A-Z0-9-]+/i);
    return m ? m[0] : txt.trim();
}

function playSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 880;
        o.type = 'sine';
        g.gain.value = 0.2;
        o.start();
        setTimeout(() => o.stop(), 150);
    } catch (e) {}
}

function onScanSuccess(d) {
    const code = extractCode(d);
    if (navigator.vibrate) navigator.vibrate(200);
    playSound();
    stopCamera();
    window.location.href = '?code=' + encodeURIComponent(code);
}

document.getElementById('btnStart').addEventListener('click', async () => {
    if (isScanning) return;
    html5QrCode = new Html5Qrcode("reader");
    const config = {
        fps: 15,
        qrbox: (w, h) => {
            let s = Math.floor(Math.min(w, h) * 0.88);
            return { width: s, height: s };
        }
    };
    try {
        await html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, () => {});
        isScanning = true;
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnStop').style.display = 'inline-flex';
        document.getElementById('scanFrame').classList.add('show');
    } catch (err) {
        alert('Impossible d\'accéder à la caméra : ' + err);
    }
});

function stopCamera() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            isScanning = false;
            document.getElementById('btnStart').style.display = 'inline-flex';
            document.getElementById('btnStop').style.display = 'none';
            document.getElementById('scanFrame').classList.remove('show');
        });
    }
}

document.getElementById('btnStop').addEventListener('click', stopCamera);

document.getElementById('btnFlash').addEventListener('click', async () => {
    if (!html5QrCode || !isScanning) {
        alert('Démarrez d\'abord la caméra');
        return;
    }
    flashOn = !flashOn;
    try {
        await html5QrCode.applyVideoConstraints({ advanced: [{ torch: flashOn }] });
        const btn = document.getElementById('btnFlash');
        btn.style.background = flashOn ? '#1a1a1a' : '';
        btn.style.color = flashOn ? 'white' : '';
        btn.innerHTML = flashOn
            ? '<i class="bi bi-lightning-fill"></i> Flash ON'
            : '<i class="bi bi-lightning-fill"></i> Flash';
    } catch (e) {}
});

document.getElementById('qrFile').addEventListener('change', e => {
    if (e.target.files.length > 0) handleFile(e.target.files[0]);
});

const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => {
    e.preventDefault();
    dz.classList.add('dragover');
});
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
});

function handleFile(f) {
    if (!f) return;
    const p = document.getElementById('preview');
    p.src = URL.createObjectURL(f);
    p.style.display = 'block';
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('uploadResult').innerHTML = '';
    const timeout = setTimeout(() => {
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadResult').innerHTML = '<div class="alert alert-warning rounded-3 py-2 small">⏱ Scan trop long.</div>';
    }, 10000);
    new Html5Qrcode("reader").scanFile(f, true).then(t => {
        clearTimeout(timeout);
        document.getElementById('uploadProgress').style.display = 'none';
        const c = extractCode(t);
        document.getElementById('uploadResult').innerHTML = `<div class="alert alert-success rounded-3 py-2 small">✅ QR: <code>${c}</code></div>`;
        playSound();
        setTimeout(() => location.href = '?code=' + encodeURIComponent(c), 600);
    }).catch(() => {
        clearTimeout(timeout);
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadResult').innerHTML = '<div class="alert alert-danger rounded-3 py-2 small">❌ Aucun QR trouvé.</div>';
    });
}

window.addEventListener('load', () => {
    const res = document.getElementById('resultBlock');
    if (res) {
        setTimeout(() => {
            res.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});
</script>
</body>
</html>