<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
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

// Inclure les fonctions WhatsApp
require_once __DIR__ . '/../../../includes/whatsapp_helper.php';

$error   = '';
$success = '';

// ============================================
// RÉCUPÉRER LES ÉVÉNEMENTS (avec filtrage utilisateur)
// ============================================

$evenements = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT id, nom, date_evenement
            FROM evenements
            WHERE id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
              AND statut != 'ANNULE'
            ORDER BY date_evenement DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("
            SELECT id, nom, date_evenement
            FROM evenements
            WHERE statut != 'ANNULE'
            ORDER BY date_evenement DESC
        ");
    }
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement événements (whatsapp/envoyer) : ' . $e->getMessage());
}

// ============================================
// RÉCUPÉRER LES INVITÉS (avec filtrage utilisateur)
// ============================================

$invites = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT 
                i.id,
                i.nom,
                i.prenom,
                i.telephone,
                i.contact_preference,
                COUNT(DISTINCT inv.id) AS nb_invitations
            FROM invites i
            LEFT JOIN invitations inv ON i.id = inv.id_invite
            WHERE i.actif = 1
              AND (
                  inv.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
                  OR inv.id_evenement IS NULL
              )
            GROUP BY i.id, i.nom, i.prenom, i.telephone, i.contact_preference
            ORDER BY i.nom ASC, i.prenom ASC
            LIMIT 200
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                i.id,
                i.nom,
                i.prenom,
                i.telephone,
                i.contact_preference,
                COUNT(DISTINCT inv.id) AS nb_invitations
            FROM invites i
            LEFT JOIN invitations inv ON i.id = inv.id_invite
            WHERE i.actif = 1
            GROUP BY i.id, i.nom, i.prenom, i.telephone, i.contact_preference
            ORDER BY i.nom ASC, i.prenom ASC
            LIMIT 200
        ");
    }
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement invités (whatsapp/envoyer) : ' . $e->getMessage());
}

// ============================================
// CHARGER LES TEMPLATES WHATSAPP
// ============================================

$whatsappTemplates = [];
$whatsappConfigFile = __DIR__ . '/../../../config/whatsapp.php';
if (file_exists($whatsappConfigFile)) {
    try {
        include $whatsappConfigFile;
    } catch (Throwable $e) {
        error_log('Erreur chargement templates WhatsApp : ' . $e->getMessage());
    }
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'envoyer') {
    $inviteIds = $_POST['invite_ids'] ?? [];
    $type      = trim($_POST['type'] ?? 'invitation');
    $messagePersonnalise = trim($_POST['message_personnalise'] ?? '');

    // ⭐ Sécuriser les IDs
    $inviteIds = array_filter(array_map('intval', (array)$inviteIds));

    if (empty($inviteIds)) {
        $error = 'Veuillez sélectionner au moins un invité.';
    } else {
        // ⭐ Vérifier que les invités appartiennent bien à l'utilisateur
        if (!$isUserAdmin) {
            try {
                $placeholders = implode(',', array_fill(0, count($inviteIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT DISTINCT i.id
                    FROM invites i
                    LEFT JOIN invitations inv ON i.id = inv.id_invite
                    WHERE i.id IN ($placeholders)
                      AND (
                          inv.id_evenement IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)
                          OR inv.id_evenement IS NULL
                      )
                ");
                $params = array_merge($inviteIds, [$userId]);
                $stmt->execute($params);
                $allowedIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
                $inviteIds = array_intersect($inviteIds, $allowedIds);

                if (empty($inviteIds)) {
                    $error = 'Vous n\'avez pas accès à ces invités.';
                }
            } catch (PDOException $e) {
                error_log('Erreur vérification accès invités : ' . $e->getMessage());
                $error = 'Erreur lors de la vérification des accès.';
            }
        }

        if (empty($error)) {
            // ---- Message personnalisé ----
            if (!empty($messagePersonnalise)) {
                $results = [];
                foreach ($inviteIds as $inviteId) {
                    try {
                        $stmt = $pdo->prepare("SELECT nom, prenom, telephone FROM invites WHERE id = ?");
                        $stmt->execute([$inviteId]);
                        $invite = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($invite && !empty($invite['telephone'])) {
                            $message = str_replace(
                                ['{nom}', '{prenom}'],
                                [$invite['nom'], $invite['prenom']],
                                $messagePersonnalise
                            );

                            $result = sendWhatsAppMessage($invite['telephone'], $message);
                            $results[] = [
                                'invite_id' => $inviteId,
                                'success'   => !empty($result['success']),
                                'message'   => $result['message'] ?? ($result['error'] ?? 'Erreur inconnue'),
                            ];
                        }
                    } catch (Throwable $e) {
                        error_log('Erreur envoi WhatsApp personnalisé : ' . $e->getMessage());
                        $results[] = [
                            'invite_id' => $inviteId,
                            'success'   => false,
                            'message'   => 'Erreur : ' . $e->getMessage(),
                        ];
                    }
                }

                $successCount = count(array_filter($results, fn($r) => $r['success']));
                $failCount    = count($results) - $successCount;

                $success = "Message personnalisé envoyé à $successCount invité(s) sur " . count($results);
                if ($failCount > 0) {
                    $success .= " ($failCount échec(s))";
                }

                if (function_exists('logAction')) {
                    logAction(
                        $userId,
                        'SEND_WHATSAPP_CUSTOM',
                        'notifications',
                        "Envoi WhatsApp personnalisé à $successCount invité(s)"
                    );
                }
            }
            // ---- Templates ----
            else {
                try {
                    $result = sendWhatsAppBulk($inviteIds, $type);

                    if (!empty($result['success'])) {
                        $success = "Notification envoyée à {$result['success_count']} invité(s) sur {$result['total']}";
                        if (!empty($result['fail_count'])) {
                            $success .= " ({$result['fail_count']} échec(s))";
                        }

                        if (function_exists('logAction')) {
                            logAction(
                                $userId,
                                'SEND_WHATSAPP_BULK',
                                'notifications',
                                "Envoi WhatsApp bulk type '$type' à {$result['success_count']} invité(s)"
                            );
                        }
                    } else {
                        $error = "Échec de l'envoi. Vérifiez la configuration WhatsApp.";
                    }
                } catch (Throwable $e) {
                    error_log('Erreur envoi WhatsApp bulk : ' . $e->getMessage());
                    $error = 'Erreur lors de l\'envoi.';
                }
            }
        }
    }
}

// ============================================
// CONFIGURATION STATUTS
// ============================================

$statutColors = [
    'EN_ATTENTE' => 'secondary',
    'CONFIRMEE'  => 'success',
    'REFUSEE'    => 'danger',
    'PRESENTE'   => 'info',
    'ANNULEE'    => 'dark',
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
<title>Envoyer WhatsApp - <?php echo APP_NAME; ?></title>
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

    .card-form {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }

    /* ========== FORM ========== */
    .form-label {
        font-weight: 700;
        font-size: 12px;
        color: #6a5a4a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: block;
    }
    .form-label i { color: #25D366; margin-right: 4px; }
    .form-control, .form-select {
        border-radius: 10px;
        padding: 10px 14px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.08);
        outline: none;
        background: white;
    }
    textarea.form-control { resize: vertical; min-height: 140px; line-height: 1.6; }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text code {
        background: rgba(37, 211, 102, 0.1);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #128C7E;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }

    /* ========== SELECTED COUNT ========== */
    .selected-count {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 6px;
    }

    /* ========== INVITÉS LISTE ========== */
    .invites-container {
        max-height: 500px;
        overflow-y: auto;
        border: 1.5px solid rgba(234, 227, 220, 0.4);
        border-radius: 12px;
        padding: 6px;
        background: rgba(252, 250, 248, 0.5);
    }
    .invites-container::-webkit-scrollbar { width: 5px; }
    .invites-container::-webkit-scrollbar-track { background: transparent; }
    .invites-container::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #25D366, #128C7E);
        border-radius: 10px;
    }

    .invite-item {
        padding: 10px 12px;
        border-radius: 10px;
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
    }
    .invite-item:last-child { border-bottom: none; }
    .invite-item:hover { background: rgba(37, 211, 102, 0.05); }
    .invite-item.selected { background: rgba(37, 211, 102, 0.08); }

    .invite-item .form-check-input {
        width: 18px;
        height: 18px;
        accent-color: #25D366;
        cursor: pointer;
        flex-shrink: 0;
    }
    .invite-item .form-check-label {
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 10px;
    }
    .invite-item strong {
        font-size: 13px;
        color: #1a1a1a;
    }
    .invite-item small { font-size: 11px; }
    .invite-item .invite-contact { color: #9a8a7f; }
    .invite-item .invite-contact i { color: #25D366; margin-right: 3px; }

    /* ========== BADGES PRÉFÉRENCE ========== */
    .badge-preference {
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .badge-preference.email    { background: rgba(107, 114, 128, 0.15); color: #374151; }
    .badge-preference.whatsapp { background: rgba(37, 211, 102, 0.15);  color: #065f46; }
    .badge-preference.telegram { background: rgba(0, 136, 204, 0.15);   color: #0e5a80; }
    .badge-preference.sms      { background: rgba(245, 158, 11, 0.15);  color: #92400e; }

    /* ========== TEMPLATES ========== */
    .template-card {
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        border-radius: 12px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.25s ease;
        background: rgba(255, 255, 255, 0.9);
        height: 100%;
    }
    .template-card:hover {
        border-color: #25D366;
        background: rgba(253, 248, 245, 0.9);
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(37, 211, 102, 0.1);
    }
    .template-card.active {
        border-color: #25D366;
        background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(18, 140, 126, 0.1));
        box-shadow: 0 6px 18px rgba(37, 211, 102, 0.15);
    }
    .template-card .template-title {
        font-weight: 700;
        color: #1a1a1a;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .template-card .template-title i { color: #25D366; }
    .template-card .template-preview {
        font-size: 11px;
        color: #9a8a7f;
        max-height: 50px;
        overflow: hidden;
        margin-top: 4px;
        line-height: 1.4;
    }

    /* ========== BOUTONS ========== */
    .btn-whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        font-weight: 700;
        padding: 11px 30px;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.25);
        cursor: pointer;
    }
    .btn-whatsapp:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.35);
        color: white;
    }
    .btn-whatsapp:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none;
    }
    .btn-cancel {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 11px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-cancel:hover {
        background: white;
        color: #25D366;
        border-color: #25D366;
    }

    /* ========== ALERTES ========== */
    .alert-custom {
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .alert-custom i { font-size: 16px; flex-shrink: 0; margin-top: 2px; }

    /* ========== SEARCH ========== */
    .search-wrapper {
        position: relative;
        margin-bottom: 12px;
    }
    .search-wrapper input {
        padding-left: 38px;
    }
    .search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #25D366;
        font-size: 14px;
        pointer-events: none;
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
        .card-form { padding: 18px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-form { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-whatsapp, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-2 { flex-direction: column; gap: 8px !important; }
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
                <h4><i class="bi bi-whatsapp"></i> Envoyer WhatsApp</h4>
                <small><i class="bi bi-send-fill"></i> Envoyez des notifications WhatsApp aux invités</small>
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

            <!-- BOUTON RETOUR -->
            <div class="mb-3">
                <a href="index.php" class="btn-cancel">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- MESSAGE -->
            <?php if ($error): ?>
                <div class="alert-custom alert-danger fade-in">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-custom alert-success fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="whatsappForm">
                <input type="hidden" name="action" value="envoyer">

                <div class="row g-4">
                    <!-- SÉLECTION DES INVITÉS -->
                    <div class="col-lg-6">
                        <div class="card-form">
                            <h6 class="fw-bold mb-3" style="font-size:14px">
                                <i class="bi bi-people-fill" style="color:#25D366"></i> Sélectionner les invités
                                <span class="selected-count" id="selectedCount">0</span>
                            </h6>

                            <div class="search-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control" id="searchInvite" placeholder="Rechercher un invité...">
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:11px;color:#9a8a7f">
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:600;">
                                    <input type="checkbox" id="checkAllInvites" style="width:16px;height:16px;accent-color:#25D366;">
                                    Tout sélectionner
                                </label>
                                <span><?php echo count($invites); ?> invité(s)</span>
                            </div>

                            <div class="invites-container" id="invitesContainer">
                                <?php if (empty($invites)): ?>
                                    <div class="text-center py-4" style="color:#9a8a7f">
                                        <i class="bi bi-people" style="font-size:32px;color:#d4c5b2;display:block;margin-bottom:8px"></i>
                                        <p style="font-size:12px;margin:0">Aucun invité disponible.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($invites as $invite):
                                        $hasPhone = !empty($invite['telephone']);
                                    ?>
                                        <div class="invite-item"
                                             data-search="<?php echo htmlspecialchars(mb_strtolower($invite['nom'] . ' ' . $invite['prenom'] . ' ' . ($invite['telephone'] ?? ''))); ?>">
                                            <div class="form-check d-flex align-items-start gap-2 m-0">
                                                <input class="form-check-input invite-checkbox"
                                                       type="checkbox"
                                                       name="invite_ids[]"
                                                       value="<?php echo (int)$invite['id']; ?>"
                                                       id="invite_<?php echo (int)$invite['id']; ?>"
                                                       <?php echo $hasPhone ? '' : 'disabled'; ?>>
                                                <label class="form-check-label m-0" for="invite_<?php echo (int)$invite['id']; ?>">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($invite['prenom'] . ' ' . $invite['nom']); ?></strong>
                                                        <br>
                                                        <small class="invite-contact">
                                                            <?php if ($hasPhone): ?>
                                                                <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($invite['telephone']); ?>
                                                            <?php else: ?>
                                                                <span style="color:#991b1b"><i class="bi bi-exclamation-triangle-fill"></i> Pas de téléphone</span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                    <div class="d-flex gap-1 flex-wrap justify-content-end">
                                                        <?php if (!empty($invite['contact_preference'])): ?>
                                                            <span class="badge-preference <?php echo strtolower($invite['contact_preference']); ?>">
                                                                <?php echo htmlspecialchars($invite['contact_preference']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ((int)$invite['nb_invitations'] > 0): ?>
                                                            <span style="background:rgba(107,114,128,0.15);color:#374151;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700">
                                                                <?php echo (int)$invite['nb_invitations']; ?> inv.
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- MESSAGE -->
                    <div class="col-lg-6">
                        <div class="card-form">
                            <h6 class="fw-bold mb-3" style="font-size:14px">
                                <i class="bi bi-chat-text-fill" style="color:#25D366"></i> Message
                            </h6>

                            <label class="form-label">
                                <i class="bi bi-file-text-fill"></i> Template de message
                            </label>
                            <div class="row g-2 mb-3">
                                <?php
                                $templatesList = [
                                    'invitation'   => ['label' => 'Invitation',   'icon' => 'bi-envelope-paper-fill'],
                                    'confirmation' => ['label' => 'Confirmation', 'icon' => 'bi-check-circle-fill'],
                                    'rappel'       => ['label' => 'Rappel',       'icon' => 'bi-bell-fill'],
                                    'present'      => ['label' => 'Présence',     'icon' => 'bi-person-check-fill'],
                                    'annulation'   => ['label' => 'Annulation',   'icon' => 'bi-x-circle-fill'],
                                ];
                                foreach ($templatesList as $key => $tpl):
                                ?>
                                    <div class="col-6">
                                        <div class="template-card template-option <?php echo $key === 'invitation' ? 'active' : ''; ?>"
                                             data-template="<?php echo htmlspecialchars($key); ?>"
                                             onclick="selectTemplate('<?php echo htmlspecialchars($key, ENT_QUOTES); ?>')">
                                            <div class="template-title">
                                                <i class="bi <?php echo $tpl['icon']; ?>"></i>
                                                <?php echo htmlspecialchars($tpl['label']); ?>
                                            </div>
                                            <div class="template-preview">
                                                <?php
                                                $preview = $whatsappTemplates[$key]['template'] ?? '';
                                                echo htmlspecialchars(mb_substr($preview, 0, 60)) . (mb_strlen($preview) > 60 ? '...' : '');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <input type="hidden" name="type" id="selectedTemplate" value="invitation">

                            <label class="form-label">
                                <i class="bi bi-pencil-square"></i> Ou message personnalisé
                            </label>
                            <textarea class="form-control"
                                      name="message_personnalise"
                                      id="customMessage"
                                      rows="6"
                                      placeholder="Votre message personnalisé... Utilisez {nom} et {prenom} pour personnaliser"></textarea>

                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle-fill"></i>
                                Variables disponibles : <code>{nom}</code> <code>{prenom}</code> <code>{evenement}</code> <code>{date}</code> <code>{heure}</code> <code>{lieu}</code> <code>{code_unique}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOUTON D'ENVOI -->
                <div class="d-flex gap-3 mt-4 flex-wrap justify-content-end">
                    <a href="index.php" class="btn-cancel">
                        <i class="bi bi-x-lg"></i> Annuler
                    </a>
                    <button type="submit" class="btn-whatsapp" id="sendButton" disabled>
                        <i class="bi bi-send-fill"></i> Envoyer WhatsApp
                    </button>
                </div>
            </form>

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

// ========== COMPTEUR DE SÉLECTION ==========
const checkboxes = document.querySelectorAll('.invite-checkbox:not(:disabled)');
const sendButton = document.getElementById('sendButton');
const selectedCountSpan = document.getElementById('selectedCount');
const checkAllInvites = document.getElementById('checkAllInvites');

function updateCount() {
    const checked = document.querySelectorAll('.invite-checkbox:checked').length;
    selectedCountSpan.textContent = checked;
    sendButton.disabled = checked === 0;

    // Highlight visuel des items sélectionnés
    document.querySelectorAll('.invite-item').forEach(item => {
        const cb = item.querySelector('.invite-checkbox');
        item.classList.toggle('selected', cb && cb.checked);
    });

    if (checkAllInvites) {
        checkAllInvites.checked = checked === checkboxes.length && checked > 0;
    }
}

checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

// ========== TOUT SÉLECTIONNER ==========
if (checkAllInvites) {
    checkAllInvites.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });
}

// ========== RECHERCHE ==========
const searchInvite = document.getElementById('searchInvite');
if (searchInvite) {
    searchInvite.addEventListener('input', function() {
        const search = this.value.toLowerCase().trim();
        document.querySelectorAll('.invite-item').forEach(item => {
            const data = item.dataset.search || '';
            item.style.display = data.includes(search) ? '' : 'none';
        });
    });
}

// ========== TEMPLATES ==========
function selectTemplate(type) {
    document.getElementById('selectedTemplate').value = type;

    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.toggle('active', card.dataset.template === type);
    });

    // Vider le message personnalisé
    document.getElementById('customMessage').value = '';
}

// ========== MESSAGE PERSONNALISÉ ==========
const customMessage = document.getElementById('customMessage');
if (customMessage) {
    customMessage.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            document.querySelectorAll('.template-card').forEach(card => {
                card.classList.remove('active');
            });
            document.getElementById('selectedTemplate').value = 'personnalise';
        }
    });
}

// ========== VALIDATION FORMULAIRE ==========
document.getElementById('whatsappForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.invite-checkbox:checked').length;
    if (checked === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner au moins un invité.');
        return false;
    }
    if (!confirm('Envoyer WhatsApp à ' + checked + ' invité(s) ?')) {
        e.preventDefault();
        return false;
    }
});
</script>
</body>
</html>