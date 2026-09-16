<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/telegram/envoyer.php
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

requirePermission('notifications.telegram');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

// ============================================
// CHARGER LA CONFIGURATION TELEGRAM
// ============================================

$configFile = __DIR__ . '/../../../config/telegram.php';
$botToken   = '';

if (file_exists($configFile)) {
    try {
        $configLue = (function () use ($configFile) {
            include $configFile;
            return [
                'token' => defined('TELEGRAM_BOT_TOKEN') ? (string)TELEGRAM_BOT_TOKEN : '',
            ];
        })();
        $botToken = $configLue['token'];
    } catch (Throwable $e) {
        error_log('Erreur chargement config telegram : ' . $e->getMessage());
    }
}

// ============================================
// FONCTION DE TEST DU TOKEN (simplifiée)
// ============================================

function testTelegramToken(string $token): ?array {
    if (empty($token)) return null;

    $url = 'https://api.telegram.org/bot' . $token . '/getMe';

    // ---- cURL (le plus fiable) ----
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT      => APP_NAME . '/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['ok'])) {
                return $data;
            }
        }
    }

    // ---- Fallback : file_get_contents ----
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout'       => 15,
                'ignore_errors' => true,
                'user_agent'    => APP_NAME . '/1.0',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['ok'])) {
                return $data;
            }
        }
    }

    return null;
}

// ============================================
// FONCTION D'ENVOI TELEGRAM
// ============================================

function sendTelegramMessage(
    string $token,
    string $chatId,
    string $text,
    ?string $qrCodeUrl = null
): array {
    $chatId = trim($chatId);

    // Validation du chat_id
    if ($chatId === '' || !preg_match('/^-?\d+$/', $chatId)) {
        return [
            'success' => false,
            'error'   => 'Chat ID invalide : ' . htmlspecialchars($chatId) . ' (doit être un nombre entier)',
        ];
    }

    // Nettoyer le texte
    $cleanText = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Préparer l'URL et les données selon le type
    if ($qrCodeUrl) {
        $url      = 'https://api.telegram.org/bot' . $token . '/sendPhoto';
        $postData = [
            'chat_id'    => $chatId,
            'photo'      => $qrCodeUrl,
            'caption'    => $cleanText,
            'parse_mode' => 'HTML',
        ];
    } else {
        $url      = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $postData = [
            'chat_id'    => $chatId,
            'text'       => $cleanText,
            'parse_mode' => 'HTML',
        ];
    }

    // ---- Méthode 1 : cURL (recommandé) ----
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT      => APP_NAME . '/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response !== false) {
            $result = json_decode($response, true);
            if (is_array($result) && !empty($result['ok'])) {
                return [
                    'success'    => true,
                    'message_id' => $result['result']['message_id'] ?? null,
                    'method'     => 'cURL POST',
                ];
            }
            return [
                'success'    => false,
                'error'      => $result['description'] ?? 'Erreur inconnue',
                'error_code' => $result['error_code'] ?? null,
                'chat_id'    => $chatId,
                'method'     => $qrCodeUrl ? 'cURL sendPhoto' : 'cURL sendMessage',
            ];
        }
    }

    // ---- Méthode 2 : file_get_contents GET ----
    if (ini_get('allow_url_fopen')) {
        $fullUrl = $url . '?' . http_build_query($postData);

        $context = stream_context_create([
            'http' => [
                'timeout'       => 30,
                'ignore_errors' => true,
                'method'        => 'GET',
                'user_agent'    => APP_NAME . '/1.0',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($fullUrl, false, $context);

        if ($response !== false) {
            $result = json_decode($response, true);
            if (is_array($result) && !empty($result['ok'])) {
                return [
                    'success'    => true,
                    'message_id' => $result['result']['message_id'] ?? null,
                    'method'     => 'file_get_contents',
                ];
            }
            return [
                'success'    => false,
                'error'      => $result['description'] ?? 'Erreur inconnue',
                'error_code' => $result['error_code'] ?? null,
                'chat_id'    => $chatId,
                'method'     => 'file_get_contents',
            ];
        }
    }

    return [
        'success' => false,
        'error'   => 'Toutes les tentatives ont échoué. Impossible de contacter l\'API Telegram.',
        'chat_id' => $chatId,
    ];
}

// ============================================
// TEMPLATES DE MESSAGES
// ============================================

$templates = [
    'invitation' => [
        'sujet'   => '📨 Vous êtes invité à {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},

Nous avons le plaisir de vous inviter à l'événement :

🎉 {{EVENEMENT}}
📅 Date : {{DATE}}
📍 Lieu : {{LIEU}}

Votre code d'invitation unique : {{CODE}}

📷 QR Code : {{QR_CODE}}

🔗 Lien direct : {{LIEN_INVITATION}}

Nous avons hâte de vous retrouver !

Cordialement,
L'équipe organisatrice",
    ],
    'rappel' => [
        'sujet'   => '📢 Rappel : {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},

Ce message pour vous rappeler l'événement à venir :

🎉 {{EVENEMENT}}
📅 Date : {{DATE}}
📍 Lieu : {{LIEU}}

📷 QR Code : {{QR_CODE}}

🔗 Lien direct : {{LIEN_INVITATION}}

À très bientôt !

Cordialement,
L'équipe organisatrice",
    ],
    'modification' => [
        'sujet'   => '📝 Modification de l\'événement {{EVENEMENT}}',
        'message' => "Bonjour {{PRENOM}} {{NOM}},

Nous vous informons d'une modification concernant l'événement :

🎉 {{EVENEMENT}}

📅 Nouvelle date : {{DATE}}
📍 Nouveau lieu : {{LIEU}}

Votre code d'invitation reste valable : {{CODE}}

📷 QR Code : {{QR_CODE}}

🔗 Lien direct : {{LIEN_INVITATION}}

Nous restons à votre disposition pour toute question.

Cordialement,
L'équipe organisatrice",
    ],
    'personnalise' => [
        'sujet'   => 'Message personnalisé',
        'message' => "Bonjour {{PRENOM}} {{NOM}},

Message personnalisé...

📷 QR Code : {{QR_CODE}}

Cordialement,
L'équipe organisatrice",
    ],
];

$message      = '';
$messageType  = '';
$sendResult   = null;
$invitation_id = isset($_GET['invitation']) ? (int)$_GET['invitation'] : 0;

// ============================================
// RÉCUPÉRER L'INVITATION
// ============================================

$invitation = null;
if ($invitation_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                inv.nom, 
                inv.prenom, 
                inv.email, 
                inv.telephone,
                inv.telegram_chat_id,
                e.nom AS evenement_nom,
                e.date_evenement,
                e.heure_evenement,
                e.lieu,
                e.adresse AS evenement_adresse
            FROM invitations i
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invitation_id]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Erreur chargement invitation telegram : ' . $e->getMessage());
        $message = 'Erreur lors du chargement de l\'invitation';
        $messageType = 'danger';
    }
}

if (!$invitation) {
    header('Location: index.php');
    exit;
}

// ============================================
// GÉNÉRATION DES LIENS
// ============================================

$lienInvitation = APP_URL . '/public/invitation.php?code=' . urlencode($invitation['code_unique']);
$qrCodeUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($lienInvitation);
$lienQR         = APP_URL . '/public/qr.php?code=' . urlencode($invitation['code_unique']);

// ============================================
// VÉRIFIER SI LE BOT EST ACTIF
// ============================================

$botActive = false;
$botInfo   = null;
if (!empty($botToken)) {
    $botInfo   = testTelegramToken($botToken);
    $botActive = is_array($botInfo) && !empty($botInfo['ok']);
}

// ============================================
// TRAITEMENT DE L'ENVOI
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet           = trim($_POST['sujet'] ?? '');
    $message_content = trim($_POST['message'] ?? '');
    $envoyer_qr      = isset($_POST['envoyer_qr']) ? 1 : 0;

    if (empty($sujet)) {
        $message = 'Veuillez saisir un sujet.';
        $messageType = 'danger';
    } elseif (empty($message_content)) {
        $message = 'Veuillez saisir un message.';
        $messageType = 'danger';
    } elseif (empty($invitation['telegram_chat_id'])) {
        $message = 'Cet invité n\'a pas de chat ID Telegram.';
        $messageType = 'danger';
    } elseif (empty($botToken)) {
        $message = 'Bot Telegram non configuré. Vérifiez config/telegram.php';
        $messageType = 'danger';
    } elseif (!$botActive) {
        $message = 'Le bot Telegram n\'est pas actif. Vérifiez le token.';
        $messageType = 'danger';
    } else {
        $chat_id = trim((string)$invitation['telegram_chat_id']);

        if ($chat_id === '' || !preg_match('/^-?\d+$/', $chat_id)) {
            $message = 'Chat ID Telegram invalide : ' . htmlspecialchars($chat_id) . ' (doit être un nombre entier)';
            $messageType = 'danger';
        } else {
            // Remplacer les variables
            $message_content = str_replace(
                ['{{PRENOM}}', '{{NOM}}', '{{CODE}}', '{{LIEN_INVITATION}}', '{{EVENEMENT}}', '{{DATE}}', '{{LIEU}}', '{{QR_CODE}}'],
                [
                    $invitation['prenom'],
                    $invitation['nom'],
                    $invitation['code_unique'],
                    '🔗 ' . $lienInvitation,
                    $invitation['evenement_nom'],
                    date('d/m/Y', strtotime($invitation['date_evenement'])),
                    $invitation['lieu'],
                    '🔗 ' . $lienQR,
                ],
                $message_content
            );

            $qrImage    = $envoyer_qr ? $qrCodeUrl : null;
            $sendResult = sendTelegramMessage($botToken, $chat_id, $message_content, $qrImage);

            if (!empty($sendResult['success'])) {
                if (function_exists('logAction')) {
                    logAction(
                        $userId,
                        'SEND_TELEGRAM',
                        'notifications',
                        "Message Telegram envoyé à $chat_id pour l'invitation {$invitation['code_unique']}"
                    );
                }

                try {
                    $stmt = $pdo->prepare("UPDATE invitations SET telegram_sent = 1, telegram_sent_at = NOW() WHERE id = ?");
                    $stmt->execute([$invitation['id']]);
                } catch (PDOException $e) {
                    error_log('Erreur mise à jour telegram_sent : ' . $e->getMessage());
                }

                $message = "Message Telegram envoyé avec succès à <strong>" . htmlspecialchars($chat_id) . "</strong> !";
                $messageType = 'success';

                header('Refresh: 2; url=index.php');
            } else {
                $errorMsg  = $sendResult['error'] ?? 'Erreur inconnue';
                $errorCode = $sendResult['error_code'] ?? null;
                $method    = $sendResult['method'] ?? 'inconnue';

                $message = 'Erreur lors de l\'envoi : ' . htmlspecialchars($errorMsg);

                if ($errorCode !== null) {
                    $message .= ' (Code Telegram : ' . (int)$errorCode . ')';
                }

                $message .= '<br>Méthode utilisée : ' . htmlspecialchars($method);
                $message .= '<br>Chat ID : ' . htmlspecialchars($chat_id);

                if ($errorCode === 403) {
                    $botUsername = htmlspecialchars($botInfo['result']['username'] ?? '');
                    $message .= '<br><br><strong>Solution :</strong> L\'utilisateur doit démarrer le bot avant de recevoir des messages.';
                    if ($botUsername) {
                        $message .= '<br>Partagez ce lien : <a href="https://t.me/' . $botUsername . '" target="_blank" rel="noopener">https://t.me/' . $botUsername . '</a>';
                    }
                } elseif ($errorCode === 404) {
                    $message .= '<br><br><strong>Solution :</strong> Le chat ID n\'existe pas ou n\'est pas valide.';
                    $message .= '<br>Assurez-vous que l\'utilisateur a démarré le bot.';
                } elseif ($errorCode === 400 && strpos((string)$errorMsg, 'parse') !== false) {
                    $message .= '<br><br><strong>Solution :</strong> Problème de format HTML. Essayez de retirer les balises HTML du message.';
                }

                $messageType = 'danger';
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
<title>Envoi Telegram - <?php echo APP_NAME; ?></title>
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
    .top-bar .page-title h4 i { color: #0088cc; margin-right: 10px; }
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
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.4);
        max-width: 820px;
        margin: 0 auto;
    }
    .form-container .form-title {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-container .form-title i { color: #0088cc; }

    /* ========== INFO CARD ========== */
    .info-card {
        background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
        border: 1px solid rgba(193, 124, 96, 0.15);
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 20px;
    }
    .info-card .info-row {
        display: flex;
        padding: 6px 0;
        border-bottom: 1px solid rgba(248, 245, 242, 0.8);
        font-size: 13px;
        gap: 10px;
        align-items: center;
    }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-row .label {
        width: 130px;
        font-weight: 700;
        color: #9a8a7f;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        flex-shrink: 0;
    }
    .info-card .info-row .label i { color: #c17c60; margin-right: 4px; }
    .info-card .info-row .value { flex: 1; color: #1a1a1a; font-size: 13px; }
    .info-card .info-row .value code {
        font-size: 11px;
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 2px 6px;
        border-radius: 6px;
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
    .form-label i { color: #c17c60; margin-right: 4px; }
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
    .form-control:focus {
        border-color: #0088cc;
        box-shadow: 0 0 0 4px rgba(0, 136, 204, 0.08);
        outline: none;
        background: white;
    }
    textarea.form-control { resize: vertical; min-height: 180px; line-height: 1.6; }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text code {
        background: rgba(193, 124, 96, 0.1);
        border: 1px solid rgba(193, 124, 96, 0.2);
        color: #c17c60;
        padding: 1px 6px;
        border-radius: 5px;
        font-size: 11px;
    }

    /* ========== BOUTONS ========== */
    .btn-send {
        background: linear-gradient(135deg, #0088cc, #005f8a);
        color: white;
        border: none;
        font-weight: 700;
        padding: 11px 26px;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(0, 136, 204, 0.25);
        cursor: pointer;
    }
    .btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 136, 204, 0.35);
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
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-weight: 600;
        padding: 11px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-cancel:hover {
        background: white;
        color: #c17c60;
        border-color: #c17c60;
    }

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
        border-color: #0088cc;
        background: rgba(0, 136, 204, 0.05);
        color: #0088cc;
    }
    .btn-template.active {
        border-color: #0088cc;
        background: linear-gradient(135deg, rgba(0, 136, 204, 0.1), rgba(0, 95, 138, 0.1));
        color: #0088cc;
        box-shadow: 0 4px 12px rgba(0, 136, 204, 0.15);
    }

    /* ========== ALERTES ========== */
    .alert-custom {
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .alert-custom .alert-content {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.5;
    }
    .alert-custom .alert-content i { margin-top: 2px; flex-shrink: 0; }

    /* ========== PREVIEW ========== */
    .preview-box {
        background: rgba(252, 250, 248, 0.8);
        border: 1px solid rgba(240, 235, 229, 0.8);
        border-radius: 12px;
        padding: 14px;
        font-size: 13px;
        white-space: pre-wrap;
        font-family: 'Inter', sans-serif;
        max-height: 220px;
        overflow-y: auto;
        line-height: 1.6;
        color: #6a5a4a;
    }

    /* ========== BOT STATUS ========== */
    .bot-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .bot-status.online {
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .bot-status.offline {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* ========== QR PREVIEW ========== */
    .qr-preview {
        background: linear-gradient(135deg, rgba(0, 136, 204, 0.04), rgba(0, 95, 138, 0.04));
        border: 2px dashed rgba(0, 136, 204, 0.25);
        border-radius: 14px;
        padding: 16px;
        text-align: center;
    }
    .qr-preview img {
        max-width: 150px;
        border-radius: 10px;
        border: 2px solid #0088cc;
        background: white;
        padding: 4px;
    }
    .qr-preview .qr-link {
        font-size: 11px;
        color: #9a8a7f;
        margin-top: 8px;
    }
    .qr-preview .qr-link a { color: #0088cc; text-decoration: none; }
    .qr-preview .qr-link a:hover { text-decoration: underline; }

    /* ========== SWITCH ========== */
    .form-check-input:checked {
        background-color: #0088cc;
        border-color: #0088cc;
    }
    .form-check-input:focus {
        box-shadow: 0 0 0 4px rgba(0, 136, 204, 0.15);
        border-color: #0088cc;
    }
    .form-check-label { font-size: 13px; color: #6a5a4a; font-weight: 600; }
    .form-check-label i { color: #0088cc; margin-right: 4px; }

    /* ========== INFO BOX ========== */
    .info-box {
        background: rgba(59, 130, 246, 0.06);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 12px;
        color: #1e40af;
        line-height: 1.6;
    }
    .info-box i { color: #3b82f6; margin-right: 4px; }
    .info-box a { color: #3b82f6; font-weight: 700; }

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
        .info-card .info-row { flex-direction: column; gap: 2px; align-items: flex-start; }
        .info-card .info-row .label { width: 100%; }
        .btn-send, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-3 { flex-direction: column; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .form-container { padding: 15px; border-radius: 14px; }
        .form-container .form-title { font-size: 15px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-template { font-size: 10px; padding: 4px 10px; }
        .qr-preview img { max-width: 120px; }
        .preview-box { font-size: 12px; max-height: 180px; }
        .bot-status { font-size: 11px; padding: 6px 12px; }
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
                <h4><i class="bi bi-telegram"></i> Envoyer un message Telegram</h4>
                <small><i class="bi bi-send-fill"></i> Envoi d'invitation avec QR Code</small>
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
            <div class="form-container fade-in">

                <h5 class="form-title">
                    <i class="bi bi-telegram"></i> Envoyer l'invitation par Telegram
                </h5>

                <!-- BOT STATUS -->
                <div class="mb-3">
                    <?php if ($botActive): ?>
                        <span class="bot-status online">
                            <i class="bi bi-check-circle-fill"></i>
                            Bot actif : @<?php echo htmlspecialchars($botInfo['result']['username'] ?? ''); ?>
                        </span>
                    <?php else: ?>
                        <span class="bot-status offline">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            Bot inactif
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-custom">
                        <div class="alert-content">
                            <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                            <div><?php echo $message; ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- INFO CARD -->
                <div class="info-card">
                    <div class="info-row">
                        <span class="label"><i class="bi bi-person-fill"></i> Invité</span>
                        <span class="value"><strong><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="bi bi-telegram"></i> Chat ID</span>
                        <span class="value">
                            <?php
                            $chatIdDisplay = trim((string)($invitation['telegram_chat_id'] ?? ''));
                            if ($chatIdDisplay === '' || !preg_match('/^-?\d+$/', $chatIdDisplay)) {
                                echo '<span style="color:#991b1b">⚠️ ' . htmlspecialchars($chatIdDisplay ?: 'Non renseigné') . ' (invalide)</span>';
                            } else {
                                echo '<code>' . htmlspecialchars($chatIdDisplay) . '</code>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="bi bi-calendar-event-fill"></i> Événement</span>
                        <span class="value"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="bi bi-calendar-fill"></i> Date</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($invitation['date_evenement'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="bi bi-geo-alt-fill"></i> Lieu</span>
                        <span class="value"><?php echo htmlspecialchars($invitation['lieu']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="bi bi-qr-code"></i> Code</span>
                        <span class="value"><code><?php echo htmlspecialchars($invitation['code_unique']); ?></code></span>
                    </div>
                </div>

                <!-- QR PREVIEW -->
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-qr-code"></i> QR Code</label>
                    <div class="qr-preview">
                        <img src="<?php echo htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code" id="qrPreview">
                        <div class="qr-link">
                            Lien du QR : <a href="<?php echo htmlspecialchars($lienQR, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($lienQR); ?></a>
                        </div>
                    </div>
                </div>

                <form method="POST" action="">

                    <!-- TEMPLATES -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-file-text-fill"></i> Modèle de message</label>
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
                        <label class="form-label"><i class="bi bi-tag-fill"></i> Sujet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sujet" id="sujet"
                               value="<?php echo htmlspecialchars(str_replace('{{EVENEMENT}}', $invitation['evenement_nom'], $templates['invitation']['sujet'])); ?>"
                               required maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-align-left"></i> Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message" rows="10" required><?php
                            echo htmlspecialchars(str_replace(
                                ['{{PRENOM}}', '{{NOM}}', '{{EVENEMENT}}', '{{DATE}}', '{{LIEU}}', '{{CODE}}', '{{LIEN_INVITATION}}', '{{QR_CODE}}'],
                                [
                                    $invitation['prenom'],
                                    $invitation['nom'],
                                    $invitation['evenement_nom'],
                                    date('d/m/Y', strtotime($invitation['date_evenement'])),
                                    $invitation['lieu'],
                                    $invitation['code_unique'],
                                    '🔗 ' . $lienInvitation,
                                    '🔗 ' . $lienQR,
                                ],
                                $templates['invitation']['message']
                            ));
                        ?></textarea>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i>
                            Variables : <code>{{PRENOM}}</code> <code>{{NOM}}</code> <code>{{EVENEMENT}}</code>
                            <code>{{DATE}}</code> <code>{{LIEU}}</code> <code>{{CODE}}</code>
                            <code>{{LIEN_INVITATION}}</code> <code>{{QR_CODE}}</code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-eye-fill"></i> Aperçu</label>
                        <div class="preview-box" id="preview"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="envoyer_qr" id="envoyer_qr" checked>
                            <label class="form-check-label" for="envoyer_qr">
                                <i class="bi bi-qr-code"></i> Envoyer le QR Code en image
                            </label>
                            <div class="form-text">Si décoché, seul le lien sera envoyé.</div>
                        </div>
                    </div>

                    <!-- ASTUCE -->
                    <div class="info-box mb-3">
                        <i class="bi bi-lightbulb-fill"></i>
                        <strong>Astuce :</strong> L'utilisateur doit avoir démarré une conversation avec le bot avant de recevoir des messages.
                        <?php if ($botActive && !empty($botInfo['result']['username'])): ?>
                            <br>Lien vers le bot : <a href="https://t.me/<?php echo htmlspecialchars($botInfo['result']['username']); ?>" target="_blank" rel="noopener">https://t.me/<?php echo htmlspecialchars($botInfo['result']['username']); ?></a>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn-send" <?php echo empty($invitation['telegram_chat_id']) || !$botActive ? 'disabled' : ''; ?>>
                            <i class="bi bi-send-fill"></i> Envoyer Telegram
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>

                    <?php if (empty($invitation['telegram_chat_id'])): ?>
                        <div class="text-danger small mt-3">
                            <i class="bi bi-exclamation-triangle-fill"></i> Cet invité n'a pas de chat ID Telegram.
                        </div>
                    <?php endif; ?>
                    <?php if (!$botActive): ?>
                        <div class="text-warning small mt-2" style="color:#92400e !important">
                            <i class="bi bi-exclamation-triangle-fill"></i> Le bot Telegram n'est pas actif.
                        </div>
                    <?php endif; ?>
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

// ============================================
// TEMPLATES & VARIABLES
// ============================================
const templates = <?php echo json_encode($templates, JSON_UNESCAPED_UNICODE); ?>;
const invitation = <?php echo json_encode([
    'prenom'         => $invitation['prenom'],
    'nom'            => $invitation['nom'],
    'evenement_nom'  => $invitation['evenement_nom'],
    'date_evenement' => date('d/m/Y', strtotime($invitation['date_evenement'])),
    'lieu'           => $invitation['lieu'],
    'code_unique'    => $invitation['code_unique'],
    'lienInvitation' => $lienInvitation,
    'lienQR'         => $lienQR,
], JSON_UNESCAPED_UNICODE); ?>;

const sujetInput   = document.getElementById('sujet');
const messageInput = document.getElementById('message');
const previewDiv   = document.getElementById('preview');

function replaceVariables(text) {
    return text
        .replace(/\{\{PRENOM\}\}/g, invitation.prenom)
        .replace(/\{\{NOM\}\}/g, invitation.nom)
        .replace(/\{\{EVENEMENT\}\}/g, invitation.evenement_nom)
        .replace(/\{\{DATE\}\}/g, invitation.date_evenement)
        .replace(/\{\{LIEU\}\}/g, invitation.lieu)
        .replace(/\{\{CODE\}\}/g, invitation.code_unique)
        .replace(/\{\{LIEN_INVITATION\}\}/g, '🔗 ' + invitation.lienInvitation)
        .replace(/\{\{QR_CODE\}\}/g, '🔗 ' + invitation.lienQR);
}

function updatePreview() {
    previewDiv.textContent = replaceVariables(messageInput.value);
}

function loadTemplate(name) {
    if (name === 'personnalise') return;
    const tpl = templates[name];
    if (tpl) {
        sujetInput.value = tpl.sujet.replace(/\{\{EVENEMENT\}\}/g, invitation.evenement_nom);
        messageInput.value = tpl.message;
        updatePreview();
    }
}

document.querySelectorAll('.btn-template').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-template').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        loadTemplate(this.dataset.template);
    });
});

messageInput.addEventListener('input', updatePreview);
updatePreview();

// ============================================
// VALIDATION AVANT ENVOI
// ============================================
document.querySelector('form').addEventListener('submit', function(e) {
    const chatId = '<?php echo addslashes(trim((string)($invitation['telegram_chat_id'] ?? ''))); ?>';

    if (!chatId) {
        e.preventDefault();
        alert('Cet invité n\'a pas de chat ID Telegram.');
        return false;
    }
    if (!/^-?\d+$/.test(chatId)) {
        e.preventDefault();
        alert('Chat ID Telegram invalide : "' + chatId + '" (doit être un nombre entier)');
        return false;
    }
    if (!confirm('Confirmez-vous l\'envoi du message Telegram à ' + chatId + ' ?')) {
        e.preventDefault();
        return false;
    }
});
</script>
</body>
</html>