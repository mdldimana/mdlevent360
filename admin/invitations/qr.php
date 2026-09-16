<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure la configuration + auth
require_once __DIR__ . '/../../config/database.php';
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
if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $scheme . '://' . $host . $projectFolder);
}

// Vérifier les permissions
requirePermission('invitations.voir');

$user = getCurrentUser();
$pdo = getDbConnection();

// Récupérer le code d'invitation
$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">❌</div>
        <h1 style="color:#c17c60;margin:20px 0;">Code manquant</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Aucun code d\'invitation n\'a été fourni.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour aux invitations
        </a>
    </div>');
}

// ⭐ Vérifier l'invitation + récupérer les infos complètes
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id, 
            i.code_unique, 
            i.statut,
            i.id_evenement,
            inv.id as invite_id,
            inv.nom, 
            inv.prenom, 
            inv.email,
            inv.telephone,
            inv.photo as invite_photo,
            e.nom as evenement_nom,
            e.date_evenement,
            e.heure_evenement,
            e.lieu,
            e.adresse,
            e.fond as evenement_image
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE i.code_unique = ?
    ");
    $stmt->execute([$code]);
    $invitation = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erreur QR invitation: ' . $e->getMessage());
}

if (!$invitation) {
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Invitation invalide</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Ce code d\'invitation n\'existe pas ou a été supprimé.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour aux invitations
        </a>
    </div>');
}

// ⭐ VÉRIFICATION DES DROITS D'ACCÈS (défensive)
if (function_exists('userCanAccessInvitation') && !userCanAccessInvitation($pdo, (int)$user['id'], (int)$invitation['id'])) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour consulter cette invitation.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour aux invitations
        </a>
    </div>');
}

// ⭐ Statut spécial pour invitation annulée
$estAnnulee = ($invitation['statut'] === 'ANNULEE');

// ⭐ URL de l'invitation (avec fallback)
$baseForUrl = rtrim(APP_URL, '/');
$invitationUrl = $baseForUrl . '/public/invitation.php?code=' . urlencode($code);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($invitationUrl) . '&color=1a1a2e&bgcolor=ffffff&qzone=1';

// ============================================
// GÉNÉRATION DU QR CODE EN BASE64
// ============================================
$qrCodeBase64 = '';
if (function_exists('curl_init')) {
    try {
        $ch = curl_init($qrCodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $imageData) {
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        }
    } catch (Throwable $e) {
        error_log('Erreur curl QR: ' . $e->getMessage());
    }
}

if (empty($qrCodeBase64)) {
    $qrCodeBase64 = $qrCodeUrl;
}

// Statut avec configuration complète
$statutConfig = [
    'EN_ATTENTE' => ['label' => 'En attente', 'dot' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#92400e', 'icon' => 'hourglass-split'],
    'CONFIRMEE' => ['label' => 'Confirmée', 'dot' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#065f46', 'icon' => 'check-circle-fill'],
    'REFUSEE' => ['label' => 'Refusée', 'dot' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#991b1b', 'icon' => 'x-circle-fill'],
    'PRESENTE' => ['label' => 'Présente', 'dot' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.15)', 'color' => '#1e40af', 'icon' => 'person-check-fill'],
    'ANNULEE' => ['label' => 'Annulée', 'dot' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.15)', 'color' => '#1f2937', 'icon' => 'slash-circle-fill']
];
$statut = $statutConfig[$invitation['statut']] ?? $statutConfig['EN_ATTENTE'];

// ⭐ Fonction pour obtenir la photo de fond (avec fallback)
function getFondUrl($fondPath, $projectFolder = '') {
    if (empty($fondPath)) return '';
    if (preg_match('/^https?:\/\//', $fondPath)) return $fondPath;
    $fileName = basename($fondPath);
    if (strpos($fondPath, 'fonds/') !== false || strpos($fondPath, 'fond') !== false) {
        return $projectFolder . '/uploads/fonds/' . $fileName;
    }
    return $projectFolder . '/uploads/photos/' . $fileName;
}

$pageBackground = !empty($invitation['evenement_image']) ? getFondUrl($invitation['evenement_image'], $projectFolder) : '';

// ⭐ Photo de l'invité
$uploadDir = __DIR__ . '/../../uploads/photos/';
$invitePhotoUrl = '';
if (!empty($invitation['invite_photo']) && is_file($uploadDir . $invitation['invite_photo'])) {
    $invitePhotoUrl = BASE_PATH . '/uploads/photos/' . rawurlencode($invitation['invite_photo']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f8f5f2;
            <?php if ($pageBackground): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(248, 245, 242, 0.4), rgba(248, 245, 242, 0.6));
            pointer-events: none;
            z-index: 0;
        }

        .invitation-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }
        .ticket-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            position: relative;
            animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes cardIn{ from{opacity:0; transform: translateY(20px) scale(0.98);} to{opacity:1; transform: translateY(0) scale(1);} }

        .ticket-header {
            background: rgba(17, 17, 25, 0.85);
            padding: 28px 32px 80px;
            position: relative;
            overflow: hidden;
        }
        .ticket-header::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(193,124,96,0.2) 0%, transparent 70%);
            top: -150px; right: -100px;
            pointer-events: none;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
            gap: 10px;
            flex-wrap: wrap;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 700;
            letter-spacing: -0.02em;
            font-size: 14px;
        }
        .brand i {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: white;
            font-size: 18px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: <?php echo $statut['bg']; ?>;
            color: <?php echo $statut['color']; ?>;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .status-badge .dot {
            width: 6px;
            height: 6px;
            background: <?php echo $statut['dot']; ?>;
            border-radius: 50%;
            box-shadow: 0 0 0 3px <?php echo $statut['bg']; ?>;
        }

        .header-info { margin-top: 22px; position: relative; z-index: 1; }
        .header-info h2 {
            color: white;
            font-family: Georgia, serif;
            font-weight: 400;
            font-size: 26px;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .header-info p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            margin: 8px 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .header-info p i {
            color: #c17c60;
            width: 14px;
        }
        
        .invite-avatar-wrapper {
            margin: 22px auto 0;
            width: 80px;
            height: 80px;
            position: relative;
            z-index: 3;
            margin-bottom: -40px;
        }
        .invite-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 28px;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .invite-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .qr-section {
            padding: 0 32px;
            margin-top: 10px;
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .qr-frame {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 16px 40px rgba(17,17,25,0.08), 0 0 0 1px rgba(17,17,25,0.06);
            display: inline-block;
            position: relative;
        }
        .qr-frame::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 24px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(193,124,96,0.5), rgba(212,165,116,0.3));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
        .qr-frame img {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            display: block;
        }
        
        .qr-frame.annulee::before {
            content: '';
            position: absolute;
            inset: 18px;
            background: rgba(107, 114, 128, 0.85);
            border-radius: 12px;
            z-index: 1;
        }
        .qr-frame.annulee::after {
            content: '\f057';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            inset: 18px;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.4), rgba(107, 114, 128, 0.6));
        }

        .ticket-body {
            padding: 22px 32px 28px;
            text-align: center;
        }
        .invite-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #1a1a1a;
            margin: 0;
        }
        .invite-email {
            font-size: 13px;
            color: #8a8a9a;
            margin-top: 2px;
        }

        .code-pill {
            margin: 18px auto 0;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(246, 245, 242, 0.7);
            border: 1px solid rgba(229, 226, 222, 0.5);
            padding: 8px 14px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: 0.08em;
        }
        .code-pill button {
            border: 0;
            background: rgba(255, 255, 255, 0.8);
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6b6b7a;
        }
        .code-pill button:hover {
            background: #c17c60;
            color: white;
        }

        .annulee-message {
            margin-top: 20px;
            padding: 14px 18px;
            background: rgba(107, 114, 128, 0.1);
            border: 1.5px solid rgba(107, 114, 128, 0.2);
            border-radius: 12px;
            color: #4b5563;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .annulee-message i {
            font-size: 18px;
            color: #6b7280;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 22px;
        }
        .btn-pro {
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-pro:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-primary-gold {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            box-shadow: 0 8px 20px rgba(193,124,96,0.3);
        }
        .btn-primary-gold:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(193,124,96,0.35);
            color: white;
        }
        .btn-secondary-light {
            background: rgba(17, 17, 25, 0.85);
            color: white;
        }
        .btn-secondary-light:hover:not(:disabled) {
            background: rgba(17, 17, 25, 0.95);
            color: white;
            transform: translateY(-1px);
        }

        .btn-back-custom {
            grid-column: span 2;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(236, 233, 228, 0.5);
            color: #6b6b7a;
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-back-custom:hover {
            background: rgba(250, 250, 248, 0.9);
            color: #6b6b7a;
        }

        .footer-link {
            margin-top: 16px;
            font-size: 12px;
            color: rgba(107, 107, 122, 0.9);
            text-align: center;
            background: rgba(255, 255, 255, 0.7);
            padding: 10px 14px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .footer-link a {
            color: #c17c60;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .footer-link a:hover {
            color: #a86a50;
        }

        @media (max-width: 480px) {
            body { padding: 16px; }
            .ticket-header { padding: 24px 24px 70px; }
            .ticket-body, .qr-section { padding-left: 20px; padding-right: 20px; }
            .qr-frame img {
                width: 180px;
                height: 180px;
            }
            .actions {
                grid-template-columns: 1fr;
            }
            .btn-back-custom {
                grid-column: span 1;
            }
            .header-info h2 { font-size: 22px; }
            .invite-avatar-wrapper { width: 70px; height: 70px; margin-bottom: -35px; }
            .invite-avatar { width: 70px; height: 70px; font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="invitation-wrapper">
    <div class="ticket-card" id="ticketCard">
        <div class="ticket-header">
            <div class="header-top">
                <div class="brand">
                    <i class="fas fa-star"></i> 
                    <?php echo strtoupper(APP_NAME); ?>
                </div>
                <div class="status-badge">
                    <span class="dot"></span>
                    <?php echo $statut['label']; ?>
                </div>
            </div>
            <div class="header-info">
                <h2><?php echo htmlspecialchars($invitation['evenement_nom']); ?></h2>
                <p>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($invitation['lieu'] ?: 'Lieu à confirmer'); ?></span>
                    <span style="opacity:0.3;">•</span>
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($invitation['date_evenement'])); ?></span>
                    <?php if (!empty($invitation['heure_evenement'])): ?>
                    <span style="opacity:0.3;">•</span>
                    <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($invitation['heure_evenement'])); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="invite-avatar-wrapper">
            <div class="invite-avatar">
                <?php if ($invitePhotoUrl): ?>
                    <img src="<?php echo htmlspecialchars($invitePhotoUrl); ?>" 
                         alt="Photo"
                         onerror="this.style.display='none'; this.parentElement.textContent='<?php echo htmlspecialchars(strtoupper(substr($invitation['prenom'] ?? 'U', 0, 1) . substr($invitation['nom'] ?? 'N', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>';">
                <?php else: ?>
                    <?php echo strtoupper(substr($invitation['prenom'] ?? 'U', 0, 1) . substr($invitation['nom'] ?? 'N', 0, 1)); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-frame <?php echo $estAnnulee ? 'annulee' : ''; ?>">
                <img 
                    src="<?php echo $qrCodeUrl; ?>" 
                    alt="QR Code" 
                    id="qrImage"
                    crossorigin="anonymous"
                    onerror="this.src='<?php echo $qrCodeBase64; ?>'"
                    onload="this.onerror=null;"
                >
            </div>
        </div>

        <div class="ticket-body">
            <h3 class="invite-name"><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></h3>
            <?php if (!empty($invitation['email'])): ?>
                <div class="invite-email"><?php echo htmlspecialchars($invitation['email']); ?></div>
            <?php endif; ?>

            <div class="code-pill">
                <span id="codeText"><?php echo htmlspecialchars($invitation['code_unique']); ?></span>
                <button onclick="copyCode()" title="Copier"><i class="fas fa-copy"></i></button>
            </div>

            <?php if ($estAnnulee): ?>
                <div class="annulee-message">
                    <i class="fas fa-info-circle"></i>
                    <div style="text-align: left;">
                        <strong>Invitation annulée</strong><br>
                        <span style="font-size: 12px; opacity: 0.8;">Ce QR code n'est plus valide pour le contrôle d'entrée.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="actions">
                <button class="btn-pro btn-primary-gold" onclick="downloadQR()" <?php echo $estAnnulee ? 'disabled' : ''; ?>>
                    <i class="fas fa-qrcode"></i> QR Code
                </button>
                <button class="btn-pro btn-secondary-light" onclick="downloadCard()">
                    <i class="fas fa-image"></i> Carte JPG
                </button>
                <a href="voir.php?id=<?php echo $invitation['id']; ?>" class="btn-back-custom">
                    <i class="fas fa-arrow-left"></i> Voir l'invitation complète
                </a>
            </div>
        </div>
    </div>

    <div class="footer-link">
        <i class="fas fa-link" style="opacity:0.5;"></i>
        <span id="linkText" style="word-break: break-all;"><?php echo htmlspecialchars($invitationUrl); ?></span>
        <a href="#" onclick="copyLink(); return false;"><i class="fas fa-copy"></i> Copier</a>
    </div>
</div>

<script>
let qrImageBase64 = '<?php echo $qrCodeBase64; ?>';

function copyCode(){
    const code = document.getElementById('codeText').innerText;
    navigator.clipboard.writeText(code);
    toast('Code copié');
}

function copyLink(){
    const link = document.getElementById('linkText').innerText;
    navigator.clipboard.writeText(link);
    toast('Lien copié');
}

function downloadQR(){
    const img = document.getElementById('qrImage');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const size = 600;
    canvas.width = size;
    canvas.height = size;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    const tempImg = new Image();
    tempImg.crossOrigin = 'anonymous';
    const imgSrc = (qrImageBase64 && qrImageBase64.startsWith('data:image')) 
        ? qrImageBase64 
        : img.src;
    tempImg.src = imgSrc;
    tempImg.onload = function() {
        const margin = 40;
        const qrSize = size - (margin * 2);
        ctx.drawImage(tempImg, margin, margin, qrSize, qrSize);
        ctx.fillStyle = '#111119';
        ctx.font = 'bold 16px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('<?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?>', size/2, size - 10);
        const link = document.createElement('a');
        link.download = 'qr-invitation-<?php echo $invitation['code_unique']; ?>.png';
        link.href = canvas.toDataURL('image/png', 1.0);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        toast('QR Code téléchargé !');
    };
    tempImg.onerror = function() {
        toast('Erreur lors du téléchargement, veuillez réessayer');
    };
}

async function downloadCard() {
    const btn = document.querySelector('.btn-secondary-light');
    const card = document.getElementById('ticketCard');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
    try {
        await new Promise(resolve => setTimeout(resolve, 300));
        const canvas = await html2canvas(card, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            width: card.offsetWidth,
            height: card.offsetHeight,
            windowWidth: card.scrollWidth,
            windowHeight: card.scrollHeight,
            onclone: function(doc) {
                const imgs = doc.querySelectorAll('img');
                imgs.forEach(img => {
                    if (img.src && !img.src.startsWith('data:image')) {
                        img.crossOrigin = 'anonymous';
                    }
                });
            }
        });
        const link = document.createElement('a');
        const name = '<?php echo htmlspecialchars($invitation['prenom'] . '_' . $invitation['nom']); ?>';
        link.download = `carte_qr_${name}_${Date.now()}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        toast('Carte téléchargée en JPG !');
    } catch (error) {
        console.error('Erreur:', error);
        toast('Erreur lors du téléchargement');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-image"></i> Carte JPG';
}

function toast(msg){
    let t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(17,17,25,0.9);color:white;padding:10px 18px;border-radius:100px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.2);';
    document.body.appendChild(t);
    setTimeout(()=>{ 
        t.style.opacity='0'; 
        t.style.transform='translateX(-50%) translateY(10px)'; 
        t.style.transition='all .3s'; 
        setTimeout(()=>t.remove(),300);
    }, 1800);
}

document.addEventListener('DOMContentLoaded', function() {
    const img = document.getElementById('qrImage');
    if (img.complete && img.naturalHeight > 0) {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth || 500;
            canvas.height = img.naturalHeight || 500;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            qrImageBase64 = canvas.toDataURL('image/png');
        } catch(e) {}
    } else {
        img.src = qrImageBase64;
    }
});
</script>

</body>
</html>