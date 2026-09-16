<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // en prod on n'affiche pas, on log
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Fix InfinityFree - définir BASE_PATH et APP_NAME si non définis (PHP 8.3 est strict)
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $projectFolder); // /gestion_invitations en local, '' en ligne
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Gestion Invitations');
}

// Vérifier les permissions
requirePermission('invitations.voir');
$user = getCurrentUser();
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Vérification des droits d'accès
if (!function_exists('userCanAccessInvitation') || !userCanAccessInvitation($pdo, (int)$user['id'], $id)) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour consulter cette invitation.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">← Retour à la liste</a>
    </div>');
}

// Récupération invitation
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            inv.id as invite_id,
            inv.nom,
            inv.prenom,
            inv.email as invite_email,
            inv.telephone as invite_telephone,
            inv.adresse as invite_adresse,
            inv.entreprise as invite_entreprise,
            inv.nombre_personnes as invite_nb_personnes,
            inv.photo as invite_photo,
            e.id as evenement_id,
            e.nom as evenement_nom,
            e.date_evenement,
            e.heure_evenement,
            e.lieu as evenement_lieu,
            e.adresse as evenement_adresse
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur voir invitation (base): ' . $e->getMessage());
    die('<div style="padding:30px;background:#f8d7da;color:#721c24;font-family:monospace;">Erreur DB: '.htmlspecialchars($e->getMessage()).'</div>');
}

if (!$invitation) {
    header('Location: index.php');
    exit;
}

// Données annexes
$confirmation = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM confirmations WHERE id_invitation = ? LIMIT 1");
    $stmt->execute([$id]);
    $confirmation = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('Table confirmations: ' . $e->getMessage()); }

$presence = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM presences WHERE id_invitation = ? LIMIT 1");
    $stmt->execute([$id]);
    $presence = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('Table presences: ' . $e->getMessage()); }

if ($confirmation) {
    $invitation['reponse'] = $confirmation['reponse'] ?? null;
    $invitation['confirme_nb_personnes'] = $confirmation['nombre_personnes'] ?? null;
    $invitation['preference_alimentaire'] = $confirmation['preference_alimentaire'] ?? null;
    $invitation['commentaire'] = $confirmation['commentaire'] ?? null;
    $invitation['date_confirmation'] = $confirmation['date_confirmation'] ?? null;
}
if ($presence) {
    $invitation['date_entree'] = $presence['date_entree'] ?? null;
    $invitation['heure_entree'] = $presence['heure_entree'] ?? null;
    $invitation['nombre_present'] = $presence['nombre_present'] ?? null;
}

$preferences = [];
try {
    $stmt = $pdo->prepare("SELECT b.nom, b.image, pi.quantite FROM preferences_invitation pi JOIN boissons b ON pi.id_boisson = b.id WHERE pi.id_invitation = ? ORDER BY b.nom");
    $stmt->execute([$id]);
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('Erreur préférences: ' . $e->getMessage()); }

$tableAssignee = null;
if (function_exists('getTableInvitation')) {
    try { $tableAssignee = getTableInvitation($pdo, $id); } catch (Throwable $e) { error_log('Erreur getTableInvitation: ' . $e->getMessage()); }
}

// PHOTO INVITE - FIX INFINITYFREE
$uploadDir = __DIR__ . '/../../uploads/photos/';
$photoUrl = '';
$hasPhoto = false;
if (!empty($invitation['invite_photo'])) {
    $photoPath = $uploadDir . $invitation['invite_photo'];
    if (is_file($photoPath)) {
        // BASE_PATH est maintenant défini même en prod
        $photoUrl = BASE_PATH . '/uploads/photos/' . rawurlencode($invitation['invite_photo']);
        $hasPhoto = true;
    }
}

$statutLabels = ['EN_ATTENTE'=>'En attente','CONFIRMEE'=>'Confirmée','REFUSEE'=>'Refusée','PRESENTE'=>'Présente','ANNULEE'=>'Annulée'];
$statutConfig = ['EN_ATTENTE'=>['icon'=>'bi-hourglass-split','class'=>'en_attente'],'CONFIRMEE'=>['icon'=>'bi-check-circle-fill','class'=>'confirmee'],'REFUSEE'=>['icon'=>'bi-x-circle-fill','class'=>'refusee'],'PRESENTE'=>['icon'=>'bi-person-check-fill','class'=>'presente'],'ANNULEE'=>['icon'=>'bi-slash-circle-fill','class'=>'annulee']];
$alimentaireLabels = ['STANDARD'=>'Standard','VEGETARIEN'=>'Végétarien','VEGETALIEN'=>'Végétalien','AUTRE'=>'Autre'];
$configStatut = $statutConfig[$invitation['statut']] ?? $statutConfig['EN_ATTENTE'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'invitation - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}html,body{height:100%;overflow-x:hidden}
        body{font-family:'Inter',system-ui,-apple-system,sans-serif;background:#f8f5f2;color:#1a1a1a;-webkit-font-smoothing:antialiased}
        .app-wrapper{display:flex;min-height:100vh;width:100%}
        .sidebar-wrapper{flex-shrink:0;width:260px;min-height:100vh;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:100}
        .main-content{flex:1;min-height:100vh;overflow-y:auto;min-width:0}
        .top-bar{background:rgba(255,255,255,0.95);backdrop-filter:none;padding:15px 30px;border-bottom:1px solid rgba(193,124,96,0.15);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;flex-wrap:wrap;gap:10px}
        .top-bar .page-title h4{font-weight:700;color:#1a1a1a;margin:0;font-size:20px}
        .top-bar .page-title h4 i{color:#c17c60;margin-right:10px}
        .top-bar .page-title small{color:#9a8a7f;font-size:12px;display:block;margin-top:2px}
        .top-bar .user-info{display:flex;align-items:center;gap:15px;flex-wrap:wrap}
        .top-bar .user-info .user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;flex-shrink:0}
        .top-bar .user-info .user-name{font-weight:600;color:#1a1a1a;font-size:13px}
        .top-bar .user-info .user-name small{display:block;color:#b8a99c;font-weight:400;font-size:11px}
        .top-bar .user-info .role-badge{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap}
        .sidebar-toggle-btn{display:none;position:fixed;top:12px;left:12px;z-index:200;background:linear-gradient(135deg,#c17c60,#d4a574);border:none;border-radius:12px;padding:8px 12px;box-shadow:0 5px 20px rgba(193,124,96,0.35);font-size:20px;cursor:pointer;color:white}
        .sidebar-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:150}
        .sidebar-overlay.active{display:block}
        .content-section{padding:25px 30px}
        .detail-card{background:rgba(255,255,255,0.98);border-radius:20px;padding:30px;box-shadow:0 8px 32px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);max-width:1100px;margin:0 auto 20px}
        .detail-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:25px;padding-bottom:20px;border-bottom:2px dashed rgba(193,124,96,0.15);flex-wrap:wrap;gap:20px}
        .detail-header .header-left{display:flex;align-items:center;gap:18px}
        .detail-header .invitation-icon{width:70px;height:70px;border-radius:20px;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-size:30px;flex-shrink:0}
        .detail-header .header-info h4{font-weight:700;color:#1a1a1a;margin:0 0 5px;font-size:22px}
        .detail-header .header-info .code-badge{display:inline-flex;align-items:center;gap:6px;font-family:'Courier New',monospace;background:linear-gradient(135deg,rgba(193,124,96,0.1),rgba(212,165,116,0.1));color:#c17c60;padding:5px 14px;border-radius:10px;font-size:13px;font-weight:700;border:1px solid rgba(193,124,96,0.2)}
        .detail-header .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start}
        .btn-action{padding:10px 20px;border-radius:12px;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:all 0.3s ease;border:none;cursor:pointer}
        .btn-primary-custom{background:linear-gradient(135deg,#c17c60,#d4a574);color:white}
        .btn-success-custom{background:linear-gradient(135deg,#10b981,#34d399);color:white}
        .btn-outline-custom{background:rgba(255,255,255,0.8);color:#6a5a4a;border:1.5px solid rgba(234,227,220,0.6)}
        .btn-danger-custom{background:rgba(239,68,68,0.1);color:#dc2626;border:1.5px solid rgba(239,68,68,0.2)}
        .badge-statut{padding:8px 18px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;display:inline-flex;align-items:center;gap:8px}
        .badge-statut.en_attente{background:rgba(245,158,11,0.15);color:#92400e}
        .badge-statut.confirmee{background:rgba(16,185,129,0.15);color:#065f46}
        .badge-statut.refusee{background:rgba(239,68,68,0.15);color:#991b1b}
        .badge-statut.presente{background:rgba(59,130,246,0.15);color:#1e40af}
        .badge-statut.annulee{background:rgba(107,114,128,0.15);color:#374151}
        .section-title{font-weight:700;color:#1a1a1a;margin-bottom:15px;margin-top:25px;padding-bottom:12px;border-bottom:2px dashed rgba(193,124,96,0.15);font-size:15px;display:flex;align-items:center;gap:10px}
        .section-title i{color:#c17c60}
        .section-title .badge-count{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;font-size:11px;padding:3px 10px;border-radius:12px;margin-left:8px}
        .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}
        .info-item{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:rgba(251,248,245,0.5);border-radius:12px;border:1px solid rgba(234,227,220,0.4)}
        .info-item .info-icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,rgba(193,124,96,0.1),rgba(212,165,116,0.1));display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .info-item .info-icon i{color:#c17c60;font-size:16px}
        .info-item .info-content{flex:1;min-width:0}
        .info-item .info-label{font-size:11px;color:#9a8a7f;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;margin-bottom:3px}
        .info-item .info-value{color:#1a1a1a;font-weight:500;font-size:14px;word-break:break-word}
        .guest-hero{display:flex;align-items:center;gap:18px;padding:20px;background:linear-gradient(135deg,rgba(193,124,96,0.05),rgba(212,165,116,0.05));border-radius:16px;border:2px solid rgba(193,124,96,0.15);margin-bottom:20px}
        .guest-hero .guest-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:30px;flex-shrink:0;overflow:hidden;border:3px solid white}
        .guest-hero .guest-avatar img{width:100%;height:100%;object-fit:cover}
        .guest-hero .guest-info h3{font-weight:700;color:#1a1a1a;margin:0 0 5px;font-size:20px}
        .guest-hero .guest-info .contact-line{color:#9a8a7f;font-size:13px;display:flex;align-items:center;gap:8px;margin-top:3px}
        .boissons-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
        .boisson-card{background:rgba(251,248,245,0.6);border:1.5px solid rgba(234,227,220,0.5);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px}
        .boisson-card .boisson-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;flex-shrink:0}
        .boisson-card .boisson-name{font-weight:600;color:#1a1a1a;font-size:14px}
        .boisson-card .boisson-qty{font-size:12px;color:#c17c60;font-weight:700;margin-top:2px}
        .table-assignee{display:flex;align-items:center;gap:16px;padding:20px;background:linear-gradient(135deg,rgba(16,185,129,0.05),rgba(52,211,153,0.05));border:2px solid rgba(16,185,129,0.2);border-radius:16px}
        .table-assignee .table-icon{width:55px;height:55px;border-radius:14px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;color:white;font-size:24px;flex-shrink:0}
        .table-empty{display:flex;align-items:center;gap:16px;padding:20px;background:rgba(251,248,245,0.5);border:2px dashed rgba(234,227,220,0.6);border-radius:16px;color:#9a8a7f}
        .confirmation-box{background:rgba(251,248,245,0.6);border-radius:16px;padding:20px;border:1px solid rgba(234,227,220,0.5)}
        .badge-reponse{padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:6px}
        .badge-reponse.confirmee{background:rgba(16,185,129,0.15);color:#065f46}
        .badge-reponse.refusee{background:rgba(239,68,68,0.15);color:#991b1b}
        .app-footer{text-align:center;padding:30px 0 20px;color:#b8a99c;font-size:13px}
        .fade-in{animation:fadeInUp 0.6s ease forwards;opacity:0}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        @media (max-width:992px){
            .sidebar-toggle-btn{display:flex !important}
            .app-wrapper{display:block;width:100%}
            .main-content,body.sidebar-open .main-content{width:100% !important;margin-left:0 !important;transform:none !important;filter:none !important;opacity:1 !important}
            .sidebar-wrapper{position:fixed !important;top:0 !important;left:0 !important;width:min(280px,85vw) !important;height:100dvh !important;transform:translate3d(-105%,0,0);transition:transform 0.28s ease !important;z-index:2000 !important;border-radius:0 18px 18px 0}
            .sidebar-wrapper.open{transform:translate3d(0,0,0) !important}
            .sidebar-overlay{position:fixed !important;inset:0 !important;display:block !important;visibility:hidden;opacity:0;background:rgba(0,0,0,0.2) !important;backdrop-filter:none !important;pointer-events:none;z-index:1900 !important}
            .sidebar-overlay.active{visibility:visible;opacity:1;pointer-events:auto}
            .top-bar{padding:12px 15px 12px 70px}
            .content-section{padding:15px}
            .detail-card{padding:20px}
        }
    </style>
</head>
<body>
<button class="sidebar-toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-wrapper">
    <div class="sidebar-wrapper" id="sidebarWrapper"><?php include_once __DIR__ . '/../../includes/sidebar.php'; ?></div>
    <div class="main-content" id="mainContent">
        <div class="top-bar">
            <div class="page-title"><h4><i class="bi bi-envelope-paper-fill"></i> Détails de l'invitation</h4><small>Informations complètes - Dossier: <?php echo htmlspecialchars(BASE_PATH ?: '/ (racine)'); ?></small></div>
            <div class="user-info">
                <span class="role-badge"><i class="bi bi-shield-check"></i> <?php echo is_array($user['roles'] ?? null) ? implode(', ', $user['roles']) : 'Aucun rôle'; ?></span>
                <div><div class="user-name"><?php echo htmlspecialchars(($user['prenom'] ?? '').' '.($user['nom'] ?? '')); ?><small>@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small></div></div>
                <div class="user-avatar"><?php echo strtoupper(substr($user['prenom'] ?? 'U',0,1).substr($user['nom'] ?? 'N',0,1)) ?: 'U'; ?></div>
            </div>
        </div>
        <div class="content-section">
            <div class="detail-card fade-in">
                <div class="detail-header">
                    <div class="header-left">
                        <div class="invitation-icon"><i class="bi bi-envelope-paper-fill"></i></div>
                        <div class="header-info"><h4>Invitation #<?php echo $invitation['id']; ?></h4><div class="code-badge"><i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($invitation['code_unique']); ?></div></div>
                    </div>
                    <div class="actions">
                        <span class="badge-statut <?php echo $configStatut['class']; ?>"><i class="bi <?php echo $configStatut['icon']; ?>"></i> <?php echo $statutLabels[$invitation['statut']] ?? $invitation['statut']; ?></span>
                        <a href="qr.php?code=<?php echo $invitation['code_unique']; ?>" class="btn-action btn-success-custom" target="_blank"><i class="bi bi-qr-code"></i> QR Code</a>
                        <?php if (function_exists('hasPermission') && hasPermission('invitations.modifier')): ?><a href="modifier.php?id=<?php echo $invitation['id']; ?>" class="btn-action btn-primary-custom"><i class="bi bi-pencil-fill"></i> Modifier</a><?php endif; ?>
                        <a href="index.php" class="btn-action btn-outline-custom"><i class="bi bi-arrow-left"></i> Retour</a>
                    </div>
                </div>

                <div class="guest-hero">
                    <div class="guest-avatar">
                        <?php if ($hasPhoto): ?><img src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" onerror="this.style.display='none'; this.parentElement.textContent='<?php echo htmlspecialchars(strtoupper(substr($invitation['prenom'] ?? 'U', 0, 1) . substr($invitation['nom'] ?? 'N', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>';">
                        <?php else: ?><?php echo strtoupper(substr($invitation['prenom'] ?? 'U', 0, 1) . substr($invitation['nom'] ?? 'N', 0, 1)); ?><?php endif; ?>
                    </div>
                    <div class="guest-info">
                        <h3><?php echo htmlspecialchars(trim(($invitation['prenom'] ?? '') . ' ' . ($invitation['nom'] ?? ''))); ?></h3>
                        <?php if (!empty($invitation['invite_email'])): ?><div class="contact-line"><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($invitation['invite_email']); ?></div><?php endif; ?>
                        <?php if (!empty($invitation['invite_telephone'])): ?><div class="contact-line"><i class="bi bi-phone-fill"></i> <?php echo htmlspecialchars($invitation['invite_telephone']); ?></div><?php endif; ?>
                    </div>
                </div>

                <h6 class="section-title"><i class="bi bi-person-fill"></i> Informations de l'invité</h6>
                <div class="info-grid">
                    <div class="info-item"><div class="info-icon"><i class="bi bi-building"></i></div><div class="info-content"><div class="info-label">Entreprise</div><div class="info-value"><?php echo htmlspecialchars($invitation['invite_entreprise'] ?? 'Non renseignée'); ?></div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div><div class="info-content"><div class="info-label">Adresse</div><div class="info-value"><?php echo htmlspecialchars($invitation['invite_adresse'] ?? 'Non renseignée'); ?></div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="bi bi-people-fill"></i></div><div class="info-content"><div class="info-label">Places autorisées</div><div class="info-value"><?php echo $invitation['invite_nb_personnes'] ?? 1; ?> personne(s)</div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="bi bi-calendar-plus-fill"></i></div><div class="info-content"><div class="info-label">Créé le</div><div class="info-value"><?php echo isset($invitation['created_at']) ? date('d/m/Y à H:i', strtotime($invitation['created_at'])) : '-'; ?></div></div></div>
                </div>

                <h6 class="section-title"><i class="bi bi-calendar-event-fill"></i> Événement</h6>
                <div class="info-grid">
                    <div class="info-item"><div class="info-icon"><i class="bi bi-tag-fill"></i></div><div class="info-content"><div class="info-label">Événement</div><div class="info-value"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="bi bi-calendar3"></i></div><div class="info-content"><div class="info-label">Date</div><div class="info-value"><?php echo date('d/m/Y', strtotime($invitation['date_evenement'])); ?></div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="bi bi-geo-fill"></i></div><div class="info-content"><div class="info-label">Lieu</div><div class="info-value"><?php echo htmlspecialchars($invitation['evenement_lieu'] ?? 'Non défini'); ?></div></div></div>
                </div>

                <h6 class="section-title"><i class="bi bi-table"></i> Table assignée</h6>
                <?php if ($tableAssignee): ?>
                    <div class="table-assignee"><div class="table-icon"><i class="bi bi-table"></i></div><div class="table-info" style="flex:1;"><h6>Table <?php echo htmlspecialchars($tableAssignee['table_numero'] ?? $tableAssignee['table_nom'] ?? '-'); ?></h6></div></div>
                <?php else: ?><div class="table-empty"><div class="table-icon"><i class="bi bi-table"></i></div><div><strong>Aucune table assignée</strong><p style="margin:3px 0 0;font-size:12px;">Cet invité n'est pas encore placé</p></div></div><?php endif; ?>

                <?php if (!empty($preferences)): ?>
                    <h6 class="section-title"><i class="bi bi-cup-straw"></i> Boissons <span class="badge-count"><?php echo count($preferences); ?></span></h6>
                    <div class="boissons-grid"><?php foreach ($preferences as $pref): ?><div class="boisson-card"><div class="boisson-icon"><i class="bi bi-cup-hot-fill"></i></div><div><div class="boisson-name"><?php echo htmlspecialchars($pref['nom']); ?></div><div class="boisson-qty">x<?php echo $pref['quantite']; ?></div></div></div><?php endforeach; ?></div>
                <?php endif; ?>

                <?php if (!empty($invitation['reponse'])): ?>
                    <h6 class="section-title"><i class="bi bi-check-circle-fill"></i> Confirmation</h6>
                    <div class="confirmation-box"><div class="info-grid"><div class="info-item"><div class="info-icon"><i class="bi bi-check2-circle"></i></div><div class="info-content"><div class="info-label">Réponse</div><div class="info-value"><span class="badge-reponse <?php echo strtolower($invitation['reponse']); ?>"><?php echo $invitation['reponse']=='CONFIRMEE'?'✅ Confirmée':'❌ Refusée'; ?></span></div></div></div></div></div>
                <?php endif; ?>

                <div style="margin-top:30px;padding-top:25px;border-top:2px dashed rgba(193,124,96,0.15);display:flex;flex-wrap:wrap;gap:12px;">
                    <?php if (($invitation['statut'] ?? '')!='ANNULEE' && function_exists('hasPermission') && hasPermission('invitations.annuler')): ?><a href="supprimer.php?id=<?php echo $invitation['id']; ?>" class="btn-action btn-danger-custom" onclick="return confirm('Annuler cette invitation ?')"><i class="bi bi-x-circle-fill"></i> Annuler</a><?php endif; ?>
                    <?php if (function_exists('hasPermission') && hasPermission('presences.enregistrer')): ?><a href="../presences/controle.php?code=<?php echo $invitation['code_unique']; ?>" class="btn-action btn-success-custom"><i class="bi bi-qr-code-scan"></i> Contrôle entrée</a><?php endif; ?>
                    <a href="qr.php?code=<?php echo $invitation['code_unique']; ?>" class="btn-action btn-outline-custom" target="_blank"><i class="bi bi-printer-fill"></i> Imprimer QR</a>
                </div>
            </div>
            <div class="app-footer"><i class="bi bi-heart-fill" style="color:#c17c60"></i> <?php echo APP_NAME; ?> • <?php echo date('Y'); ?></div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebarToggle=document.getElementById('sidebarToggle'),sidebarWrapper=document.getElementById('sidebarWrapper'),sidebarOverlay=document.getElementById('sidebarOverlay');
function openSidebar(){sidebarWrapper.classList.add('open');sidebarOverlay.classList.add('active');document.body.classList.add('sidebar-open');}
function closeSidebar(){sidebarWrapper.classList.remove('open');sidebarOverlay.classList.remove('active');document.body.classList.remove('sidebar-open');}
if(sidebarToggle){sidebarToggle.addEventListener('click',(e)=>{e.stopPropagation();if(sidebarWrapper.classList.contains('open'))closeSidebar();else openSidebar();});}
if(sidebarOverlay){sidebarOverlay.addEventListener('click',closeSidebar);}
window.addEventListener('resize',()=>{if(window.innerWidth>992)closeSidebar();});
</script>
</body>
</html>
