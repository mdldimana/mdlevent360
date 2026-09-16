<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol . '://' . $host . $projectFolder);
}

requirePermission('notifications.email');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ========== PHPMailer AUTOLOAD SAFE ==========
$autoloadPaths = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    $projectRoot . '/vendor/autoload.php',
];
$phpmailerLoaded = false;
foreach ($autoloadPaths as $p) {
    if (file_exists($p)) { require_once $p; $phpmailerLoaded = true; break; }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ========== CONFIG EMAIL ==========
if (!defined('SMTP_HOST')) {
    $cfg = __DIR__ . '/../../../config/email.php';
    if (file_exists($cfg)) {
        require_once $cfg;
    } else {
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', 587);
        define('SMTP_USERNAME', '');
        define('SMTP_PASSWORD', '');
        define('SMTP_FROM_EMAIL', '');
        define('SMTP_FROM_NAME', APP_NAME);
        define('SMTP_SECURE', 'tls');
    }
}

// ========== TEMPLATES ==========
$templates = [
    'invitation' => [
        'sujet'   => '📨 Vous êtes invité à {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},\n\nNous avons le plaisir de vous inviter à :\n\n🎉 {{EVENEMENT}}\n📅 Date : {{DATE}}\n📍 Lieu : {{LIEU}}\n\nVotre code : {{CODE}}\n\n📷 QR Code :\n{{QR_CODE}}\n\n🔗 Lien : {{LIEN_INVITATION}}\n\nCordialement,",
    ],
    'rappel' => [
        'sujet'   => '📢 Rappel : {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},\n\nRappel pour :\n🎉 {{EVENEMENT}}\n📅 {{DATE}}\n📍 {{LIEU}}\n\n📷 {{QR_CODE}}\n🔗 {{LIEN_INVITATION}}\n\nÀ bientôt !",
    ],
    'modification' => [
        'sujet'   => '📝 Modification {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},\n\nModification pour {{EVENEMENT}}\n📅 {{DATE}}\n📍 {{LIEU}}\nCode : {{CODE}}\nQR : {{QR_CODE}}\nLien : {{LIEN_INVITATION}}",
    ],
    'personnalise' => [
        'sujet'   => 'Message personnalisé',
        'message' => "Bonjour {{PRENOM}} {{NOM}},\n\nMessage personnalisé...\n\nQR : {{QR_CODE}}\n\nCordialement,",
    ],
];

$message = '';
$messageType = '';
$invitation_id = (int)($_GET['invitation'] ?? 0);
if ($invitation_id <= 0) {
    header('Location: index.php');
    exit;
}

// ========== RÉCUPÉRATION INVITATION (SQL sécurisé) ==========
$invitation = null;
try {
    // ⭐ Requête préparée avec paramètre au lieu de concaténation
    $sql = "SELECT i.*, inv.nom, inv.prenom, inv.email, inv.telephone, 
                   e.nom AS evenement_nom, e.date_evenement, e.heure_evenement, 
                   e.lieu, e.adresse AS evenement_adresse, e.id AS evenement_id 
            FROM invitations i 
            JOIN invites inv ON i.id_invite = inv.id 
            JOIN evenements e ON i.id_evenement = e.id 
            WHERE i.id = ?";
    $params = [$invitation_id];

    if (!$isUserAdmin) {
        $sql .= " AND e.id IN (SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?)";
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    error_log('Erreur récupération invitation (envoyer email) : ' . $e->getMessage());
}

if (!$invitation) {
    http_response_code(403);
    echo '<div style="padding:40px;font-family:Inter">
            <h3>🚫 Invitation non trouvée ou accès refusé</h3>
            <p>Vous n\'êtes pas associé à cet événement.</p>
            <a href="index.php" style="background:#c17c60;color:white;padding:10px 20px;border-radius:8px;text-decoration:none">Retour</a>
          </div>';
    exit;
}

// ========== LIEN ET QR CODE ==========
$lien = APP_URL . '/public/invitation.php?code=' . urlencode($invitation['code_unique']);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($lien);

// ========== TRAITEMENT ENVOI ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet       = trim($_POST['sujet'] ?? '');
    $msg_content = trim($_POST['message'] ?? '');

    if (empty($sujet)) {
        $message = 'Le sujet est requis.';
        $messageType = 'danger';
    } elseif (empty($msg_content)) {
        $message = 'Le message est requis.';
        $messageType = 'danger';
    } elseif (empty($invitation['email'])) {
        $message = 'Aucun email renseigné pour cet invité.';
        $messageType = 'danger';
    } elseif (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
        $message = 'SMTP non configuré. Vérifiez config/email.php.';
        $messageType = 'danger';
    } else {
        $email = $invitation['email'];
        $nom   = $invitation['prenom'] . ' ' . $invitation['nom'];
        $code  = $invitation['code_unique'];

        // ========== REMPLACEMENT DES VARIABLES ==========
        $msg_content = str_replace(
            ['{{PRENOM}}', '{{NOM}}', '{{CODE}}', '{{LIEN_INVITATION}}', '{{EVENEMENT}}', '{{DATE}}', '{{LIEU}}', '{{QR_CODE}}'],
            [
                $invitation['prenom'],
                $invitation['nom'],
                $code,
                '<a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '</a>',
                $invitation['evenement_nom'],
                date('d/m/Y', strtotime($invitation['date_evenement'])),
                $invitation['lieu'],
                '<img src="' . htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR Code" style="max-width:200px;border-radius:8px;border:2px solid #c17c60">',
            ],
            $msg_content
        );

        // ========== HTML EMAIL ==========
        $htmlContent = "<div style='max-width:600px;margin:0 auto;padding:20px;background:#fcfaf8;border-radius:14px;border:1px solid #f0ebe5'>
                            <div style='background:#1a1a1a;padding:18px;text-align:center;border-radius:12px'>
                                <h2 style='color:#d4a574;margin:0'>" . htmlspecialchars(APP_NAME) . "</h2>
                            </div>
                            <div style='background:white;padding:24px;border-radius:12px;margin-top:12px;border:1px solid #f0ebe5'>
                                " . nl2br($msg_content) . "
                                <hr style='border:none;border-top:1px dashed #f0ebe5;margin:18px 0'>
                                <p style='font-size:11px;color:#9a8a7f;text-align:center'>Envoyé par " . htmlspecialchars(APP_NAME) . "</p>
                            </div>
                        </div>";

        $textContent = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $msg_content));

        // ========== ENVOI ==========
        $sendSuccess = false;

        if ($phpmailerLoaded && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $nom);

                $mail->isHTML(true);
                $mail->Subject = $sujet;
                $mail->Body    = $htmlContent;
                $mail->AltBody = $textContent;

                $mail->send();
                $sendSuccess = true;

                // ⭐ Nettoyage PHPMailer
                $mail->clearAllRecipients();
                $mail->clearReplyTos();
                $mail->clearAttachments();
                unset($mail);

            } catch (Exception $e) {
                error_log('PHPMailer error (envoyer email) : ' . $e->getMessage());
                $message = "❌ Erreur d'envoi : " . htmlspecialchars($e->getMessage());
                $messageType = 'danger';
            }
        } else {
            // Fallback mail()
            $headers = "MIME-Version: 1.0\r\n"
                     . "Content-type: text/html; charset=UTF-8\r\n"
                     . "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";

            if (@mail($email, $sujet, $htmlContent, $headers)) {
                $sendSuccess = true;
            } else {
                $message = "❌ Échec de l'envoi avec la fonction mail() de PHP.";
                $messageType = 'danger';
            }
        }

        if ($sendSuccess) {
            if (function_exists('logAction')) {
                logAction($userId, 'SEND_EMAIL', 'notifications', "Email à $email pour {$invitation['code_unique']}");
            }

            try {
                $pdo->prepare("UPDATE invitations SET email_sent = 1, email_sent_at = NOW(), date_envoi = NOW() WHERE id = ?")
                    ->execute([$invitation['id']]);

                $message = "✅ Email envoyé à <strong>" . htmlspecialchars($email) . "</strong> !";
                $messageType = 'success';
            } catch (PDOException $e) {
                error_log('Erreur mise à jour invitation après envoi : ' . $e->getMessage());
                $message = "✅ Email envoyé mais erreur lors de la mise à jour du statut.";
                $messageType = 'warning';
            }
        }
    }
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
<title>Envoyer un email - <?php echo APP_NAME; ?></title>
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
    .content-section { padding: 25px 30px; }

    .form-container {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        padding: 26px;
        max-width: 820px;
        margin: 0 auto;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    }
    .form-container .form-title {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-container .form-title i { color: #c17c60; }

    /* ========== INFO CARD ========== */
    .info-card {
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
        border: 1px solid rgba(193, 124, 96, 0.15);
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 18px;
    }
    .info-card .info-row {
        display: flex;
        padding: 6px 0;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        font-size: 13px;
        gap: 10px;
    }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-row .label {
        width: 110px;
        font-weight: 700;
        color: #9a8a7f;
        flex-shrink: 0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding-top: 2px;
    }
    .info-card .info-row .value { color: #1a1a1a; word-break: break-word; }
    .info-card code {
        font-size: 11px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* ========== QR PREVIEW ========== */
    .qr-preview {
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
        border: 2px dashed rgba(193, 124, 96, 0.3);
        border-radius: 14px;
        padding: 16px;
        text-align: center;
        margin-bottom: 18px;
    }
    .qr-preview img {
        max-width: 140px;
        border-radius: 8px;
        border: 2px solid #c17c60;
        background: white;
        padding: 4px;
    }
    .qr-preview .qr-label {
        font-weight: 700;
        font-size: 12px;
        margin-bottom: 10px;
        display: block;
        color: #6a5a4a;
    }
    .qr-preview .qr-note {
        font-size: 11px;
        color: #9a8a7f;
        margin-top: 8px;
    }

    /* ========== TEMPLATES ========== */
    .btn-template {
        background: rgba(255, 255, 255, 0.9);
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        font-family: 'Inter', sans-serif;
        color: #6a5a4a;
    }
    .btn-template:hover {
        border-color: #c17c60;
        background: rgba(253, 248, 245, 0.9);
        color: #c17c60;
    }
    .btn-template.active {
        border-color: #c17c60;
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.12), rgba(212, 165, 116, 0.12));
        color: #c17c60;
        box-shadow: 0 4px 12px rgba(193, 124, 96, 0.15);
    }

    /* ========== FORM ========== */
    .form-label-custom {
        font-weight: 700;
        font-size: 12px;
        color: #6a5a4a;
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .form-control {
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        border-radius: 10px;
        font-size: 13px;
        padding: 10px 14px;
        font-family: 'Inter', sans-serif;
        background: rgba(255, 255, 255, 0.9);
        color: #1a1a1a;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #c17c60;
        box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
        outline: none;
        background: white;
    }
    textarea.form-control { resize: vertical; min-height: 160px; line-height: 1.6; }

    /* ========== PREVIEW BOX ========== */
    .preview-box {
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 12px;
        padding: 14px;
        font-size: 13px;
        white-space: pre-wrap;
        max-height: 220px;
        overflow-y: auto;
        font-family: 'Inter', sans-serif;
        color: #6a5a4a;
        line-height: 1.6;
    }

    /* ========== BOUTONS ========== */
    .btn-send {
        background: linear-gradient(135deg, #c17c60, #d4a574);
        color: white;
        border: none;
        font-weight: 700;
        padding: 11px 26px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
        color: white;
    }
    .btn-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none;
    }
    .btn-cancel {
        background: rgba(255, 255, 255, 0.9);
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        color: #6a5a4a;
        font-weight: 600;
        padding: 11px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    .btn-cancel:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
    }

    /* ========== ALERT ========== */
    .alert-custom {
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 13px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-weight: 500;
    }
    .alert-custom.success {
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #065f46;
    }
    .alert-custom.danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #991b1b;
    }
    .alert-custom.warning {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #92400e;
    }
    .alert-custom i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

    /* ========== FOOTER ========== */
    .app-footer {
        text-align: center;
        padding: 30px 0 20px;
        color: #b8a99c;
        font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #c17c60; }

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
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .form-container { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-send, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-2 { flex-direction: column; gap: 8px !important; }
        .info-card .info-row { flex-direction: column; gap: 2px; }
        .info-card .info-row .label { width: auto; }
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
                <h4><i class="bi bi-envelope-fill"></i> Envoyer un email</h4>
                <small>
                    <i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($invitation['evenement_nom']); ?>
                    • <?php echo $phpmailerLoaded ? '<i class="bi bi-check-circle-fill" style="color:#10b981"></i> PHPMailer OK' : '<i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b"></i> mail() fallback'; ?>
                </small>
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
            <div class="form-container">

                <h5 class="form-title">
                    <i class="bi bi-send-fill"></i> Envoi invitation
                </h5>

                <?php if ($message): ?>
                    <div class="alert-custom <?php echo $messageType; ?>">
                        <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill'); ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <!-- QR PREVIEW -->
                <div class="qr-preview">
                    <span class="qr-label"><i class="bi bi-qr-code"></i> QR Code inclus dans l'email</span>
                    <img src="<?php echo htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code">
                    <div class="qr-note">Le QR Code sera intégré automatiquement</div>
                </div>

                <!-- INFO CARD -->
                <div class="info-card">
                    <div class="info-row">
                        <span class="label">Invité</span>
                        <span class="value"><strong><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email</span>
                        <span class="value">
                            <?php if (!empty($invitation['email'])): ?>
                                <i class="bi bi-envelope-fill" style="color:#c17c60"></i>
                                <?php echo htmlspecialchars($invitation['email']); ?>
                            <?php else: ?>
                                <span style="color:#991b1b">⚠ Aucun email renseigné</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Événement</span>
                        <span class="value"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Code</span>
                        <span class="value"><code><?php echo htmlspecialchars($invitation['code_unique']); ?></code></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Lien</span>
                        <span class="value" style="font-size:11px;word-break:break-all">
                            <a href="<?php echo htmlspecialchars($lien, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color:#c17c60">
                                <?php echo htmlspecialchars($lien); ?>
                            </a>
                        </span>
                    </div>
                </div>

                <!-- FORMULAIRE -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label-custom">Modèle</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn-template active" data-template="invitation">
                                <i class="bi bi-envelope-paper-fill"></i> Invitation
                            </button>
                            <button type="button" class="btn-template" data-template="rappel">
                                <i class="bi bi-bell-fill"></i> Rappel
                            </button>
                            <button type="button" class="btn-template" data-template="modification">
                                <i class="bi bi-pencil-fill"></i> Modification
                            </button>
                            <button type="button" class="btn-template" data-template="personnalise">
                                <i class="bi bi-pencil-square"></i> Personnalisé
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Sujet *</label>
                        <input type="text" class="form-control" name="sujet" id="sujet"
                               value="<?php echo htmlspecialchars(str_replace('{{EVENEMENT}}', $invitation['evenement_nom'], $templates['invitation']['sujet'])); ?>"
                               required maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Message *</label>
                        <textarea class="form-control" name="message" id="message" rows="8" required><?php
                            echo htmlspecialchars(str_replace(
                                ['{{PRENOM}}', '{{NOM}}', '{{EVENEMENT}}', '{{DATE}}', '{{LIEU}}', '{{CODE}}', '{{LIEN_INVITATION}}', '{{QR_CODE}}'],
                                [
                                    $invitation['prenom'],
                                    $invitation['nom'],
                                    $invitation['evenement_nom'],
                                    date('d/m/Y', strtotime($invitation['date_evenement'])),
                                    $invitation['lieu'],
                                    $invitation['code_unique'],
                                    APP_URL . '/public/invitation.php?code=' . $invitation['code_unique'],
                                    '[QR Code]',
                                ],
                                $templates['invitation']['message']
                            ));
                        ?></textarea>
                        <small style="color:#9a8a7f;font-size:11px;display:block;margin-top:6px">
                            Variables : <code>{{PRENOM}}</code> <code>{{NOM}}</code> <code>{{EVENEMENT}}</code> <code>{{DATE}}</code> <code>{{LIEU}}</code> <code>{{CODE}}</code> <code>{{LIEN_INVITATION}}</code> <code>{{QR_CODE}}</code>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Aperçu</label>
                        <div class="preview-box" id="preview"></div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap pt-2" style="border-top:1px dashed rgba(193,124,96,0.15);margin-top:10px;">
                        <button type="submit" class="btn-send" <?php echo empty($invitation['email']) ? 'disabled' : ''; ?>>
                            <i class="bi bi-send-fill"></i> Envoyer l'email
                        </button>
                        <a href="index.php?evenement_id=<?php echo (int)$invitation['evenement_id']; ?>" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>
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

// ========== TEMPLATES & VARIABLES ==========
const templates = <?php echo json_encode($templates, JSON_UNESCAPED_UNICODE); ?>;
const inv = <?php echo json_encode([
    'prenom'        => $invitation['prenom'],
    'nom'           => $invitation['nom'],
    'evenement_nom' => $invitation['evenement_nom'],
    'date'          => date('d/m/Y', strtotime($invitation['date_evenement'])),
    'lieu'          => $invitation['lieu'],
    'code'          => $invitation['code_unique'],
    'lien'          => APP_URL . '/public/invitation.php?code=' . $invitation['code_unique'],
], JSON_UNESCAPED_UNICODE); ?>;

const sujetInput   = document.getElementById('sujet');
const messageInput = document.getElementById('message');
const previewDiv   = document.getElementById('preview');

function replaceVars(t) {
    return t
        .replace(/\{\{PRENOM\}\}/g, inv.prenom)
        .replace(/\{\{NOM\}\}/g, inv.nom)
        .replace(/\{\{EVENEMENT\}\}/g, inv.evenement_nom)
        .replace(/\{\{DATE\}\}/g, inv.date)
        .replace(/\{\{LIEU\}\}/g, inv.lieu)
        .replace(/\{\{CODE\}\}/g, inv.code)
        .replace(/\{\{LIEN_INVITATION\}\}/g, inv.lien)
        .replace(/\{\{QR_CODE\}\}/g, '[QR Code]');
}

function updatePreview() {
    previewDiv.textContent = replaceVars(messageInput.value);
}

document.querySelectorAll('.btn-template').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-template').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const tpl = this.dataset.template;
        if (tpl !== 'personnalise' && templates[tpl]) {
            sujetInput.value = templates[tpl].sujet.replace('{{EVENEMENT}}', inv.evenement_nom);
            messageInput.value = templates[tpl].message;
            updatePreview();
        }
    });
});

messageInput.addEventListener('input', updatePreview);
updatePreview();
</script>
</body>
</html>