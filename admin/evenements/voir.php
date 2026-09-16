<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors','1');

require_once __DIR__ . '/../../includes/auth.php';

// ========== FIX BASE_PATH PORTABLE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME')) define('APP_NAME', 'MdlEvent');
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol.'://'.$host.$projectFolder);
}

requirePermission('evenements.voir');
$user = getCurrentUser();
$pdo = getDbConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) die('ID invalide');

$stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ?');
$stmt->execute([$id]);
$evenement = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evenement) die('Événement introuvable');

if (!userCanAccessEvenement($pdo, (int)$user['id'], $id)) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;"><div style="font-size:80px;">🚫</div><h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1><p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour consulter cet événement.</p><a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">← Retour</a></div>');
}

$uploadDirHost = __DIR__ . '/../../uploads/photos_host/'; $uploadDirHost = rtrim($uploadDirHost,'/\\').DIRECTORY_SEPARATOR;
$uploadDirFond = __DIR__ . '/../../uploads/fonds/'; $uploadDirFond = rtrim($uploadDirFond,'/\\').DIRECTORY_SEPARATOR;
$uploadDirPhotos = __DIR__ . '/../../uploads/photos/'; $uploadDirPhotos = rtrim($uploadDirPhotos,'/\\').DIRECTORY_SEPARATOR;
$uploadModeleDir = __DIR__ . '/../../uploads/modeles_invitation/'; $uploadModeleDir = rtrim($uploadModeleDir,'/\\').DIRECTORY_SEPARATOR;
if (!is_dir($uploadModeleDir)) @mkdir($uploadModeleDir,0775,true);

$baseUrl = BASE_PATH; // /gestion_invitations ou / sur InfinityFree
$photoUrl = ($baseUrl ?: '') . '/uploads/photos_host/';
$fondUrl = ($baseUrl ?: '') . '/uploads/fonds/';
$fondUrlAlt = ($baseUrl ?: '') . '/uploads/photos/';
$modeleUrl = ($baseUrl ?: '') . '/uploads/modeles_invitation/';
$basePublicUrl = ($baseUrl ?: '') . '/public/';

function getPhotosHost(PDO $pdo, int $eventId): array {
    $stmt = $pdo->prepare('SELECT * FROM photo_host WHERE id_evenement = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$eventId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
$photos = getPhotosHost($pdo,$id);
$photosActives = array_filter($photos, fn($p) => (int)$p['actif'] === 1);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM invitations WHERE id_evenement = ?'); $stmt->execute([$id]);
$totalInvites = (int)$stmt->fetchColumn();

function statutBadge(string $s): string {
    return match($s) {
        'ACTIF' => '<span class="badge bg-success">ACTIF</span>',
        'BROUILLON' => '<span class="badge bg-secondary">BROUILLON</span>',
        'TERMINE' => '<span class="badge bg-primary">TERMINÉ</span>',
        'ANNULE' => '<span class="badge bg-danger">ANNULÉ</span>',
        default => '<span class="badge bg-light text-dark">'.htmlspecialchars($s).'</span>'
    };
}
$typesEvenement = ['mariage_religieux'=>'💒 Mariage religieux','mariage_civil'=>'📜 Mariage civil','mariage_coutumier'=>'🌍 Mariage coutumier','defile_mode'=>'👗 Défilé de mode','concert'=>'🎵 Concert','anniversaire'=>'🎂 Anniversaire','bapteme'=>'⛪ Baptême','communion'=>'✝ Communion','soiree'=>'🎉 Soirée','conference'=>'🎤 Conférence','autre'=>'📌 Autre'];
function getTypeLabel(string $type): string { global $typesEvenement; return $typesEvenement[$type] ?? '📋 '.ucfirst($type); }

$utilisateursAssocies = function_exists('getUtilisateursEvenement') ? getUtilisateursEvenement($pdo,$id) : [];

$fondFileName = !empty($evenement['fond']) ? basename($evenement['fond']) : '';
$fondExists = !empty($fondFileName) && file_exists($uploadDirFond.$fondFileName);
$fondExistsAlt = !empty($fondFileName) && file_exists($uploadDirPhotos.$fondFileName);
$fondFinalUrl = $fondExists ? $fondUrl.rawurlencode($fondFileName) : ($fondExistsAlt ? $fondUrlAlt.rawurlencode($fondFileName) : '');

// Modele invitation preview
$modeleCode = $evenement['modele_invitation'] ?? 'classique';
$modeleApercuUrl = '';
$modeleApercuFile = '';
foreach (['png','jpg','jpeg','webp'] as $ext) {
    $cand = $uploadModeleDir.$modeleCode.'.'.$ext;
    if (file_exists($cand)) { $modeleApercuUrl = $modeleUrl.rawurlencode($modeleCode.'.'.$ext); $modeleApercuFile = $modeleCode.'.'.$ext; break; }
}
if ($modeleApercuUrl === '' && !empty($evenement['modele_invitation'])) {
    // Si le modele a un apercu dans la BDD
    try {
        $stmt = $pdo->prepare('SELECT apercu FROM modeles_invitation WHERE code = ? LIMIT 1');
        $stmt->execute([$modeleCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['apercu']) && file_exists($uploadModeleDir.basename($row['apercu']))) {
            $modeleApercuUrl = $modeleUrl.rawurlencode(basename($row['apercu']));
            $modeleApercuFile = basename($row['apercu']);
        }
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($evenement['nom'])?> - <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}html,body{height:100%;overflow-x:hidden}body{font-family:'Inter',system-ui,sans-serif;background:#f8f5f2;color:#1a1a1a;min-height:100vh;-webkit-font-smoothing:antialiased}
.app-wrapper{display:flex;min-height:100vh;width:100%}.sidebar-wrapper{flex-shrink:0;width:260px;min-height:100vh;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:100}.main-content{flex:1;min-height:100vh;overflow-y:auto;padding:0;min-width:0}
.top-bar{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);padding:15px 30px;border-bottom:1px solid rgba(193,124,96,0.15);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;flex-wrap:wrap;gap:10px}
.top-bar .page-title h4{font-weight:700;color:#1a1a1a;margin:0;font-size:20px}.top-bar .page-title h4 i{color:#c17c60;margin-right:10px}.top-bar .page-title small{color:#9a8a7f;font-size:12px;display:block;margin-top:2px}
.top-bar .user-info{display:flex;align-items:center;gap:15px;flex-wrap:wrap}.top-bar .user-info .user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;box-shadow:0 5px 15px rgba(193,124,96,0.3);flex-shrink:0}.top-bar .user-info .user-name{font-weight:600;color:#1a1a1a;font-size:13px}.top-bar .user-info .user-name small{display:block;color:#b8a99c;font-weight:400;font-size:11px}.top-bar .user-info .role-badge{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap}
.sidebar-toggle-btn{display:none;position:fixed;top:12px;left:12px;z-index:200;background:linear-gradient(135deg,#c17c60,#d4a574);border:none;border-radius:12px;padding:8px 12px;box-shadow:0 5px 20px rgba(193,124,96,0.35);font-size:20px;cursor:pointer;color:white;transition:all 0.3s ease}.sidebar-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:150;opacity:0;transition:opacity 0.3s ease}.sidebar-overlay.active{display:block;opacity:1}
.content-section{padding:25px 30px}.card-custom{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:20px;padding:25px;box-shadow:0 8px 32px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);margin-bottom:20px}.card-title{font-weight:700;color:#1a1a1a;margin-bottom:15px;padding-bottom:12px;border-bottom:2px dashed rgba(193,124,96,0.15);font-size:17px}.card-title i{color:#c17c60;margin-right:8px}
.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f5f5f5}.info-row:last-child{border:none}.info-label{color:#9a8a7f;font-size:13px;font-weight:600}.info-value{color:#1a1a1a;font-weight:500;text-align:right;word-break:break-word;max-width:60%}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin-bottom:20px}.stat-card{background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:16px;padding:20px;text-align:center;box-shadow:0 5px 25px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);transition:all 0.3s ease}.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 35px rgba(193,124,96,0.12)}.stat-card .num{font-size:28px;font-weight:700;color:#c17c60}.stat-card .label{font-size:12px;color:#9a8a7f;margin-top:4px}
.photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}.photo-card{position:relative;border-radius:16px;overflow:hidden;aspect-ratio:4/3;background:#f8f9fa;border:2px solid rgba(234,227,220,0.6);cursor:pointer;transition:all 0.3s ease}.photo-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(193,124,96,0.2);border-color:#c17c60}.photo-card img{width:100%;height:100%;object-fit:cover;display:block}.photo-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.8));padding:40px 12px 12px 12px;color:white;transform:translateY(10px);opacity:0;transition:all 0.3s ease}.photo-card:hover .photo-overlay{transform:translateY(0);opacity:1}.badge-order{position:absolute;top:8px;right:8px;background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700}.badge-off{position:absolute;top:8px;left:8px;background:rgba(108,117,125,0.9);color:white;border-radius:20px;padding:2px 10px;font-size:10px}
.btn-save{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border:none;font-weight:700;padding:10px 20px;border-radius:12px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(193,124,96,0.25);font-size:14px}.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(193,124,96,0.35);color:white}.btn-cancel{background:rgba(255,255,255,0.8);color:#6a5a4a;border:1.5px solid rgba(234,227,220,0.6);font-weight:600;padding:10px 20px;border-radius:12px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;font-size:14px}.btn-cancel:hover{background:rgba(255,255,255,0.95);color:#c17c60}
.fond-preview-section{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:20px;padding:25px;box-shadow:0 8px 32px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);margin-bottom:20px}.fond-preview-section .fond-image-container{width:100%;max-height:500px;display:flex;align-items:center;justify-content:center;background:#f0f2f5;border-radius:12px;border:2px solid #e1e5ee;overflow:hidden;padding:10px}.fond-preview-section .fond-image{width:100%;height:auto;max-height:480px;object-fit:contain;display:block;background:#ffffff;border-radius:8px}.fond-preview-section .fond-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;background:#f8f9fa;border-radius:12px;border:2px dashed #ddd;color:#aaa}.fond-preview-section .fond-placeholder i{font-size:50px;margin-bottom:10px}
.users-section{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:20px;padding:25px;box-shadow:0 8px 32px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);margin-bottom:20px}
.user-card{display:flex;align-items:center;gap:12px;background:rgba(251,248,245,0.6);border:1.5px solid rgba(234,227,220,0.6);border-radius:12px;padding:14px 16px;transition:all 0.3s ease}.user-card:hover{border-color:#c17c60;background:rgba(193,124,96,0.05);transform:translateY(-2px);box-shadow:0 4px 12px rgba(193,124,96,0.1)}.user-card .user-avatar{width:45px;height:45px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;flex-shrink:0;box-shadow:0 3px 10px rgba(193,124,96,0.25)}.user-card .user-info{flex:1;min-width:0}.user-card .user-name{font-weight:600;color:#1a1a1a;font-size:14px;margin-bottom:2px}.user-card .user-username{color:#9a8a7f;font-size:12px}.user-card .user-role{display:inline-block;padding:3px 10px;border-radius:12px;font-size:10px;font-weight:600;background:linear-gradient(135deg,#c17c60,#d4a574);color:white;margin-top:5px;text-transform:uppercase;letter-spacing:0.05em}.user-card .user-role.no-role{background:rgba(154,138,127,0.2);color:#6a5a4a;text-transform:none;letter-spacing:0}
.no-users-message{text-align:center;padding:30px;color:#9a8a7f;background:rgba(251,248,245,0.5);border-radius:12px;border:2px dashed rgba(234,227,220,0.6)}
#lightbox{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.92);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}#lightbox.active{display:flex}#lightbox img{max-width:90%;max-height:85vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}#lightbox .close-lightbox{position:absolute;top:20px;right:30px;color:white;font-size:40px;cursor:pointer;line-height:1}#lightbox .nav-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:white;font-size:30px;width:50px;height:50px;border-radius:50%;cursor:pointer;transition:all 0.3s ease}#lightbox .nav-btn:hover{background:rgba(255,255,255,0.3)}#lightbox .prev{left:30px}#lightbox .next{right:30px}#lightbox .caption{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.6);color:white;padding:8px 20px;border-radius:20px;font-size:14px;text-align:center;max-width:80%}
.fade-in{animation:fadeInUp 0.6s ease forwards;opacity:0}@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:992px){.sidebar-toggle-btn{display:flex !important;align-items:center;justify-content:center}.app-wrapper{display:block;width:100%}.main-content,body.sidebar-open .main-content{width:100% !important;min-width:0 !important;margin-left:0 !important;transform:none !important;filter:none !important;opacity:1 !important}.sidebar-wrapper{position:fixed !important;top:0 !important;left:0 !important;width:min(280px,85vw) !important;height:100dvh !important;min-height:100dvh !important;margin:0 !important;transform:translate3d(-105%,0,0);transition:transform 0.28s ease !important;z-index:2000 !important;overflow-y:auto;overflow-x:hidden;border-radius:0 18px 18px 0;will-change:transform}.sidebar-wrapper.open{transform:translate3d(0,0,0) !important}.sidebar-overlay{position:fixed !important;inset:0 !important;display:block !important;visibility:hidden;opacity:0;background:rgba(0,0,0,0.5) !important;backdrop-filter:blur(4px) !important;-webkit-backdrop-filter:blur(4px) !important;pointer-events:none;transition:opacity 0.28s ease,visibility 0.28s ease;z-index:1900 !important}.sidebar-overlay.active{visibility:visible;opacity:1;pointer-events:auto}.top-bar{padding:12px 15px 12px 70px;flex-direction:row;flex-wrap:wrap}body.sidebar-open{overflow-x:hidden !important;overflow-y:auto !important}.content-section{padding:15px 15px}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}.stats{grid-template-columns:repeat(2,1fr);gap:10px}.stat-card{padding:15px}.stat-card .num{font-size:22px}.info-row{flex-direction:column;align-items:flex-start;gap:3px}.info-value{text-align:left;max-width:100% !important}.fond-preview-section .fond-image-container{max-height:300px}.fond-preview-section .fond-image{max-height:280px}}
@media (max-width:576px){.top-bar{padding:10px 12px 10px 60px;flex-direction:column;align-items:stretch;gap:8px}.top-bar .page-title h4{font-size:0.95rem}.top-bar .page-title small{font-size:10px}.top-bar .user-info{justify-content:flex-end;gap:10px}.top-bar .user-info .user-avatar{width:32px;height:32px;font-size:13px}.top-bar .user-info .user-name{display:none}.content-section{padding:10px 12px}.card-custom{padding:15px}.card-title{font-size:15px}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px}.stats{grid-template-columns:1fr 1fr;gap:8px}.stat-card{padding:12px}.stat-card .num{font-size:18px}.stat-card .label{font-size:10px}.btn-save,.btn-cancel{width:100%;justify-content:center;padding:10px 16px;font-size:13px}.d-flex.gap-2{flex-direction:column}.sidebar-toggle-btn{top:8px;left:8px;padding:6px 10px;font-size:17px}.sidebar-wrapper{width:min(260px,90vw) !important}.fond-preview-section{padding:15px}.fond-preview-section .fond-image-container{max-height:200px;padding:5px}.fond-preview-section .fond-image{max-height:190px}.users-section{padding:15px}.user-card{padding:12px}.user-card .user-avatar{width:38px;height:38px;font-size:14px}.user-card .user-name{font-size:13px}}
</style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
<div class="sidebar-wrapper" id="sidebarWrapper"><?php include_once __DIR__ . '/../../includes/sidebar.php'; ?></div>

<div class="main-content" id="mainContent">
<div class="top-bar">
<div class="page-title"><h4><i class="bi bi-eye"></i> <?= htmlspecialchars($evenement['nom'])?></h4><small><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($evenement['lieu'])?> • <?= date('d/m/Y', strtotime($evenement['date_evenement']))?> • <?= BASE_PATH ?: '/' ?></small></div>
<div class="user-info"><span class="role-badge"><i class="bi bi-shield-check"></i> <?= is_array($user['roles'] ?? null) ? implode(', ', $user['roles']) : 'Aucun rôle' ?></span><div><div class="user-name"><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?><small>@<?= htmlspecialchars($user['username'] ?? '') ?></small></div></div><div class="user-avatar"><?= strtoupper(substr($user['prenom'] ?? 'U',0,1).substr($user['nom'] ?? 'N',0,1)) ?: 'U' ?></div></div>
</div>

<div class="content-section">

<div class="fond-preview-section fade-in">
<h5 class="card-title"><i class="bi bi-image"></i> Photo de fond <?php if($fondFileName) echo '<small class="text-muted" style="font-size:11px">'.htmlspecialchars($fondFileName).' - '.($fondExists?'✅':'❌').'</small>'; ?></h5>
<?php if ($fondFinalUrl): ?>
<div class="fond-image-container"><img src="<?= htmlspecialchars($fondFinalUrl) ?>" alt="Photo de fond" class="fond-image" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=fond-placeholder><i class=bi bi-exclamation-triangle></i><p>Fond introuvable: <?= htmlspecialchars($fondFileName) ?></p><small><?= htmlspecialchars($fondFinalUrl) ?></small></div>'"></div>
<small class="text-muted d-block mt-2"><i class="bi bi-check-circle text-success"></i> <?= htmlspecialchars($fondFileName) ?> • <?= htmlspecialchars($fondFinalUrl) ?></small>
<?php else: ?>
<div class="fond-placeholder"><i class="bi bi-image"></i><p class="text-muted mb-0">Aucune photo de fond définie</p><?php if($fondFileName): ?><small style="color:#dc2626">Fichier demandé: <?= htmlspecialchars($fondFileName) ?> - non trouvé dans <?= htmlspecialchars($uploadDirFond) ?> ni <?= htmlspecialchars($uploadDirPhotos) ?></small><?php endif; ?></div>
<?php endif; ?>
</div>

<!-- MODELE D'INVITATION -->
<div class="card-custom fade-in">
<h5 class="card-title"><i class="bi bi-palette-fill"></i> Modèle d'invitation • <?= htmlspecialchars($modeleCode) ?> <?php if($modeleApercuFile) echo '<small class="text-muted" style="font-size:11px">✅ '.$modeleApercuFile.'</small>'; else echo '<small style="color:#dc2626;font-size:11px">❌ pas d\'apercu trouvé</small>'; ?></h5>
<?php if($modeleApercuUrl): ?>
<div style="max-width:400px;border:2px solid #e5ddd3;border-radius:16px;overflow:hidden;background:#f8f5f2"><img src="<?= htmlspecialchars($modeleApercuUrl) ?>" alt="Modèle <?= htmlspecialchars($modeleCode) ?>" style="width:100%;height:auto;display:block" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div style="display:none;padding:30px;text-align:center;color:#9a8a7f"><i class="bi bi-image" style="font-size:40px;display:block;margin-bottom:10px"></i>Image introuvable<br><small><?= htmlspecialchars($modeleApercuUrl) ?></small></div></div>
<small class="text-muted d-block mt-2"><?= htmlspecialchars($modeleApercuUrl) ?></small>
<?php else: ?>
<div class="text-muted"><i class="bi bi-image"></i> Aucun aperçu pour <code><?= htmlspecialchars($modeleCode) ?></code> - Mets un fichier <code><?= htmlspecialchars($modeleCode) ?>.png</code> dans <code>uploads/modeles_invitation/</code><br>Dossier: <?= is_dir($uploadModeleDir) ? '✅ '.count(glob($uploadModeleDir.'*.png')?:[]).' PNG' : '❌ introuvable' ?></div>
<?php endif; ?>
</div>

<div class="users-section fade-in">
<h5 class="card-title"><i class="bi bi-people"></i> Utilisateurs associés <span class="badge" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;font-size:12px;padding:4px 12px;border-radius:20px;margin-left:8px;"><?= count($utilisateursAssocies) ?></span></h5>
<?php if (!empty($utilisateursAssocies)): ?>
<div class="row g-3">
<?php foreach ($utilisateursAssocies as $u): 
$initiales = strtoupper(substr($u['prenom'] ?? 'U', 0, 1) . substr($u['nom'] ?? 'N', 0, 1));
$roleLabel = ''; if (!empty($u['role_specifique'])) { $roles = function_exists('getRolesSpecifiquesDisponibles') ? getRolesSpecifiquesDisponibles() : []; $roleLabel = $roles[$u['role_specifique']] ?? $u['role_specifique']; }
?>
<div class="col-md-6 col-lg-4"><div class="user-card"><div class="user-avatar"><?= $initiales ?: 'U' ?></div><div class="user-info"><div class="user-name"><?= htmlspecialchars(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?></div><div class="user-username">@<?= htmlspecialchars($u['username'] ?? '') ?></div><?php if (!empty($roleLabel)): ?><span class="user-role"><?= htmlspecialchars($roleLabel) ?></span><?php else: ?><span class="user-role no-role">Aucun rôle spécifique</span><?php endif; ?></div></div></div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="no-users-message"><i class="bi bi-person-x"></i><p class="mb-0">Aucun utilisateur associé à cet événement</p></div>
<?php endif; ?>
</div>

<div class="stats fade-in">
<div class="stat-card"><div class="num"><?= count($photos)?></div><div class="label"><i class="bi bi-images"></i> Photos totales</div></div>
<div class="stat-card"><div class="num"><?= count($photosActives)?></div><div class="label"><i class="bi bi-eye"></i> Photos actives</div></div>
<div class="stat-card"><div class="num"><?= $totalInvites?></div><div class="label"><i class="bi bi-people"></i> Invités</div></div>
<div class="stat-card"><div class="num"><?= statutBadge($evenement['statut'])?></div><div class="label">Statut</div></div>
</div>

<div class="row fade-in">
<div class="col-lg-7">
<div class="card-custom"><h5 class="card-title"><i class="bi bi-info-circle"></i> Informations</h5>
<div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= htmlspecialchars($evenement['nom'])?></span></div>
<div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= getTypeLabel($evenement['type_evenement'] ?? 'autre')?></span></div>
<div class="info-row"><span class="info-label">Date</span><span class="info-value"><?= date('d/m/Y', strtotime($evenement['date_evenement']))?> à <?= htmlspecialchars($evenement['heure_evenement']?? '')?></span></div>
<div class="info-row"><span class="info-label">Lieu</span><span class="info-value"><?= htmlspecialchars($evenement['lieu'])?></span></div>
<?php if($evenement['adresse']):?><div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= htmlspecialchars($evenement['adresse'])?></span></div><?php endif;?>
<div class="info-row"><span class="info-label">Description</span><span class="info-value" style="max-width:60%;white-space:pre-wrap;text-align:right"><?= htmlspecialchars($evenement['description']?: '—')?></span></div>
<div class="info-row"><span class="info-label">Statut</span><span class="info-value"><?= statutBadge($evenement['statut'])?></span></div>
<div class="info-row"><span class="info-label">Modèle</span><span class="info-value"><code><?= htmlspecialchars($evenement['modele_invitation'] ?? 'classique') ?></code></span></div>
</div>

<div class="card-custom"><h5 class="card-title"><i class="bi bi-gear"></i> Modules</h5><div class="d-flex gap-3 flex-wrap">
<span class="badge <?= $evenement['whatsapp_enabled'] ? 'bg-success' : 'bg-light text-muted border'?> p-2"><i class="bi bi-whatsapp"></i> WhatsApp <?= $evenement['whatsapp_enabled'] ? 'ON' : 'OFF'?></span>
<span class="badge <?= $evenement['telegram_enabled'] ? 'bg-info' : 'bg-light text-muted border'?> p-2"><i class="bi bi-telegram"></i> Telegram <?= $evenement['telegram_enabled'] ? 'ON' : 'OFF'?></span>
<span class="badge <?= $evenement['tables_enabled'] ? 'bg-warning text-dark' : 'bg-light text-muted border'?> p-2"><i class="bi bi-table"></i> Tables <?= $evenement['tables_enabled'] ? 'ON' : 'OFF'?></span>
</div></div>
</div>

<div class="col-lg-5">
<div class="card-custom"><h5 class="card-title"><i class="bi bi-link-45deg"></i> Liens rapides</h5><div class="d-grid gap-2">
<a href="../invitations/index.php?evenement=<?= $id?>" class="btn btn-outline-dark"><i class="bi bi-envelope"></i> Gérer les invitations (<?= $totalInvites?>)</a>
<?php if (function_exists('hasPermission') && hasPermission('evenements.modifier')): ?><a href="modifier.php?id=<?= $id?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Modifier l'événement</a><?php endif; ?>
<a href="<?= $basePublicUrl ?>index.php?code=<?= htmlspecialchars($evenement['code_evenement'] ?? '') ?>" target="_blank" class="btn btn-outline-success"><i class="bi bi-box-arrow-up-right"></i> Voir la page publique</a>
</div></div>

<div class="card-custom"><h5 class="card-title"><i class="bi bi-tools"></i> Actions</h5><div class="d-flex gap-2 flex-wrap">
<?php if (function_exists('hasPermission') && hasPermission('evenements.modifier')): ?><a href="modifier.php?id=<?= $id?>" class="btn-save"><i class="bi bi-pencil"></i> Modifier</a><?php endif; ?>
<a href="index.php" class="btn-cancel"><i class="bi bi-arrow-left"></i> Retour</a>
</div></div>
</div>
</div>

<div class="card-custom fade-in">
<div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0" style="border:none;padding:0;"><i class="bi bi-images"></i> Photos des mariés - <?= count($photosActives)?> active(s) / <?= count($photos)?> total</h5><?php if(count($photos)>0):?><small class="text-muted">Cliquez pour agrandir</small><?php endif;?></div>

<?php if(empty($photos)):?>
<div class="text-center py-5 text-muted"><i class="bi bi-image" style="font-size:50px;color:#d4c5b2"></i><p class="mt-2">Aucune photo pour cet événement #<?= $id ?></p><?php if (function_exists('hasPermission') && hasPermission('evenements.modifier')): ?><a href="modifier.php?id=<?= $id?>" class="btn-save mt-2">Ajouter des photos</a><?php endif; ?></div>
<?php else:?>
<div class="photo-grid">
<?php foreach($photos as $idx => $p):
$filename = basename($p['photo']); $fullPath = $uploadDirHost . $filename; $exists = file_exists($fullPath); $url = $photoUrl . rawurlencode($filename);
?>
<div class="photo-card" onclick="openLightbox(<?= $idx?>)" data-index="<?= $idx?>" style="<?= !(int)$p['actif'] ? 'opacity:0.5;filter:grayscale(0.8);' : '' ?>">
<?php if($exists):?><img src="<?= htmlspecialchars($url)?>" loading="lazy" alt="<?= htmlspecialchars($p['titre']?: 'Photo '.$idx)?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div style="display:none;width:100%;height:100%;background:#ffe0e0;flex-direction:column;align-items:center;justify-content:center;color:#c00;padding:10px;text-align:center"><i class="bi bi-exclamation-triangle" style="font-size:24px"></i><small style="font-size:10px;word-break:break-all"><?= htmlspecialchars($url) ?><br>404</small></div>
<?php else:?><div style="width:100%;height:100%;background:#ffe0e0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#c00"><i class="bi bi-exclamation-triangle" style="font-size:30px"></i><small style="font-size:10px;padding:5px;word-break:break-all"><?= htmlspecialchars($filename)?> manquant<br><?= htmlspecialchars($fullPath) ?></small></div><?php endif;?>
<span class="badge-order">#<?= $p['ordre']?></span><?php if(!(int)$p['actif']):?><span class="badge-off">INACTIF</span><?php endif;?>
<div class="photo-overlay"><?php if($p['titre']):?><div style="font-weight:600;font-size:14px"><?= htmlspecialchars($p['titre'])?></div><?php endif;?><?php if($p['description']):?><div style="font-size:11px;opacity:.8;margin-top:2px"><?= htmlspecialchars($p['description'])?></div><?php endif;?></div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
</div>

<div style="text-align: center; padding: 30px 0 20px; color: #b8a99c; font-size: 13px;"><i class="bi bi-heart-fill" style="color: #c17c60;"></i> <?= APP_NAME ?> • <?= date('Y'); ?></div>
</div>
</div>
</div>

<div id="lightbox"><span class="close-lightbox" onclick="closeLightbox()">&times;</span><button class="nav-btn prev" onclick="navLightbox(-1)"><i class="bi bi-chevron-left"></i></button><img id="lightboxImg" src=""><button class="nav-btn next" onclick="navLightbox(1)"><i class="bi bi-chevron-right"></i></button><div class="caption" id="lightboxCaption"></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebarToggle = document.getElementById('sidebarToggle'); const sidebarWrapper = document.getElementById('sidebarWrapper'); const sidebarOverlay = document.getElementById('sidebarOverlay');
function openSidebar() { sidebarWrapper.classList.add('open'); sidebarOverlay.classList.add('active'); document.body.classList.add('sidebar-open'); }
function closeSidebar() { sidebarWrapper.classList.remove('open'); sidebarOverlay.classList.remove('active'); document.body.classList.remove('sidebar-open'); }
if (sidebarToggle) sidebarToggle.addEventListener('click', function(e) { e.stopPropagation(); sidebarWrapper.classList.contains('open') ? closeSidebar() : openSidebar(); });
if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) closeSidebar(); });
document.querySelectorAll('.sidebar-wrapper .nav-link:not([data-bs-toggle="collapse"])').forEach(function(link) { link.addEventListener('click', function() { if (window.innerWidth <= 992) closeSidebar(); }); });
window.addEventListener('resize', function () { if (window.innerWidth > 992) closeSidebar(); });

const photos = <?= json_encode(array_map(fn($p)=>['url' => $photoUrl. rawurlencode(basename($p['photo'])),'titre' => $p['titre'],'desc' => $p['description'],'actif' => (int)$p['actif'],'exists' => file_exists($uploadDirHost. basename($p['photo']))], $photos), JSON_UNESCAPED_SLASHES)?>;
let currentIdx = 0; const lb = document.getElementById('lightbox'); const lbImg = document.getElementById('lightboxImg'); const lbCap = document.getElementById('lightboxCaption');
function updateBodyScroll() { const sidebarIsOpen = sidebarWrapper.classList.contains('open'); const lightboxIsOpen = lb.classList.contains('active'); document.body.style.overflow = lightboxIsOpen ? 'hidden' : ''; if (sidebarIsOpen) document.body.classList.add('sidebar-open'); else document.body.classList.remove('sidebar-open'); }
function openLightbox(idx) { currentIdx = idx; if (!photos[idx].exists) { alert('Fichier manquant: ' + photos[idx].url); return; } if (!photos[idx].actif) { if (!confirm('Photo INACTIVE. Afficher quand même ?')) return; } lbImg.src = photos[idx].url; lbCap.innerHTML = (photos[idx].titre ? '<b>' + photos[idx].titre + '</b> ' : '') + (photos[idx].desc ? photos[idx].desc : ''); lb.classList.add('active'); updateBodyScroll(); }
function closeLightbox() { lb.classList.remove('active'); updateBodyScroll(); }
function navLightbox(dir) { let nextIdx = (currentIdx + dir + photos.length) % photos.length; let tries = 0; while (tries < photos.length) { if (photos[nextIdx].exists) break; nextIdx = (nextIdx + dir + photos.length) % photos.length; tries++; } openLightbox(nextIdx); }
lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
document.addEventListener('keydown', (e) => { if (!lb.classList.contains('active')) return; if (e.key === 'Escape') closeLightbox(); if (e.key === 'ArrowLeft') navLightbox(-1); if (e.key === 'ArrowRight') navLightbox(1); });
</script>
</body>
</html>
