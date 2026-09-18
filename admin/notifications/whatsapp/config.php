<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// admin/notifications/whatsapp/config.php
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

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$pdo    = getDbConnection();

// ============================================
// VARIABLES
// ============================================

$message     = '';
$messageType = '';

$whatsapp_api_url     = 'https://graph.facebook.com/v18.0/';
$whatsapp_phone_id    = '';
$whatsapp_token       = '';
$whatsapp_business_id = '';
$whatsapp_webhook     = '';
$whatsapp_service     = 'meta';
$configStatus         = 'ACTIF';

$tokenDejaConfigure = false;

// ============================================
// CHARGER LA CONFIGURATION ACTUELLE
// ============================================

try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE status = 'ACTIF' ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        $whatsapp_api_url     = $config['api_key']             ?? 'https://graph.facebook.com/v18.0/';
        $whatsapp_phone_id    = $config['phone_number_id']     ?? '';
        $whatsapp_token       = $config['access_token']        ?? '';
        $whatsapp_business_id = $config['business_account_id'] ?? '';
        $whatsapp_webhook     = $config['webhook_url']         ?? '';
        $whatsapp_service     = $config['service']             ?? 'meta';
        $configStatus         = $config['status']              ?? 'ACTIF';

        $tokenDejaConfigure = !empty($whatsapp_token);
    }
} catch (PDOException $e) {
    error_log('Erreur chargement config WhatsApp : ' . $e->getMessage());
}

// ============================================
// SERVICES DISPONIBLES
// ============================================

$services = [
    'meta'       => 'Meta WhatsApp Cloud API',
    'twilio'     => 'Twilio WhatsApp API',
    'ultramsg'   => 'UltraMsg API',
    'simulation' => 'Mode Simulation (test)',
];

// ============================================
// RÉGÉNÉRATION DU FICHIER config/whatsapp.php
// ============================================

/**
 * Régénère le fichier de configuration WhatsApp
 * pour que les constantes soient à jour dans le code.
 */
function regenerateWhatsAppConfigFile(string $serviceKey): bool {
    $targetFile = __DIR__ . '/../../../config/whatsapp.php';

    // Sécurité : service valide
    $validServices = ['meta', 'twilio', 'ultramsg', 'simulation'];
    if (!in_array($serviceKey, $validServices, true)) {
        $serviceKey = 'meta';
    }

    // Génération du contenu
    $content  = "<?php\n";
    $content .= "/**\n";
    $content .= " * Configuration WhatsApp\n";
    $content .= " * ⚠️ Généré automatiquement - Ne pas modifier manuellement\n";
    $content .= " * Dernière mise à jour : " . date('d/m/Y à H:i:s') . "\n";
    $content .= " */\n\n";
    $content .= "// ============================================\n";
    $content .= "// SERVICE WHATSAPP ACTIF\n";
    $content .= "// ============================================\n";
    $content .= "if (!defined('WHATSAPP_SERVICE')) {\n";
    $content .= "    define('WHATSAPP_SERVICE', '" . addslashes($serviceKey) . "');\n";
    $content .= "}\n\n";
    $content .= "// ============================================\n";
    $content .= "// TEMPLATES DE MESSAGES\n";
    $content .= "// ============================================\n";
    $content .= "\$whatsappTemplates = [\n";

    // Templates
    $templates = [
        'invitation' => [
            'name'     => 'invitation_event',
            'subject'  => "Invitation à l'événement",
            'template' => "Bonjour {nom} {prenom},\n\nNous avons le plaisir de vous inviter à l'événement \"{evenement}\" qui aura lieu le {date} à {heure}.\n\nLieu : {lieu}\nNombre de personnes : {nb_personnes}\nCode d'accès : {code_unique}\n\nVeuillez confirmer votre présence via le lien ci-dessous :\n{url_validation}\n\nNous avons hâte de vous accueillir !",
        ],
        'confirmation' => [
            'name'     => 'confirmation_event',
            'subject'  => 'Invitation confirmée',
            'template' => "Bonjour {nom} {prenom},\n\nNous confirmons votre participation à l'événement \"{evenement}\" du {date}.\n\nLieu : {lieu}\nNombre de personnes : {nb_personnes}\n\nN'oubliez pas de scanner votre QR code à l'entrée.\n\nÀ très bientôt !",
        ],
        'rappel' => [
            'name'     => 'rappel_event',
            'subject'  => 'Rappel - Événement',
            'template' => "Bonjour {nom} {prenom},\n\nCe message pour vous rappeler l'événement \"{evenement}\" qui aura lieu demain le {date} à {heure}.\n\nLieu : {lieu}\n\nN'oubliez pas votre QR code pour l'entrée.\n\nÀ demain !",
        ],
        'present' => [
            'name'     => 'presence_confirmee',
            'subject'  => 'Présence enregistrée',
            'template' => "Bonjour {nom} {prenom},\n\nVotre présence à l'événement \"{evenement}\" a été enregistrée avec succès !\n\nBonne journée et profitez bien de l'événement.",
        ],
        'annulation' => [
            'name'     => 'annulation_event',
            'subject'  => "Annulation d'invitation",
            'template' => "Bonjour {nom} {prenom},\n\nNous accusons réception de votre annulation pour l'événement \"{evenement}\".\n\nNous espérons vous revoir à une prochaine occasion.\n\nCordialement.",
        ],
    ];

    $first = true;
    foreach ($templates as $key => $tpl) {
        if (!$first) $content .= ",\n";
        $first = false;

        $content .= "    '" . $key . "' => [\n";
        $content .= "        'name'     => '" . addslashes($tpl['name']) . "',\n";
        $content .= "        'subject'  => '" . addslashes($tpl['subject']) . "',\n";
        $content .= "        'template' => \"" . addcslashes($tpl['template'], '"$') . "\"\n";
        $content .= "    ]";
    }

    $content .= "\n];\n";

    // Écriture
    $dir = dirname($targetFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $written = @file_put_contents($targetFile, $content, LOCK_EX);

    // Invalider opcache
    if ($written !== false && function_exists('opcache_invalidate')) {
        @opcache_invalidate($targetFile, true);
    }

    return $written !== false;
}

// ============================================
// FONCTION DE TEST (MULTI-SERVICE)
// ============================================

function testWhatsAppConfig(string $service, array $config): array {
    if (empty($config['phone_number_id']) || empty($config['access_token'])) {
        return ['success' => false, 'message' => 'Configuration incomplète (ID téléphone et Token requis)'];
    }

    switch ($service) {

        // ==========================================
        // TWILIO
        // ==========================================
        case 'twilio':
            $accountSid = $config['business_account_id'] ?? '';
            if (empty($accountSid)) {
                return ['success' => false, 'message' => 'Account SID Twilio manquant'];
            }

            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . urlencode($accountSid) . '.json';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $accountSid . ':' . $config['access_token'],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT      => (defined('APP_NAME') ? APP_NAME : 'MdlEvent') . '/1.0',
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!empty($curlError)) {
                return ['success' => false, 'message' => 'Erreur cURL : ' . $curlError];
            }

            if ($httpCode === 200) {
                $data = json_decode((string)$response, true);
                $name = $data['friendly_name'] ?? 'compte Twilio';
                return ['success' => true, 'message' => 'Connexion Twilio réussie ! Compte : ' . $name];
            }

            $result = json_decode((string)$response, true);
            $error  = $result['message'] ?? 'Erreur inconnue (HTTP ' . $httpCode . ')';
            return ['success' => false, 'message' => 'Erreur Twilio : ' . $error];

        // ==========================================
        // ULTRAMSG
        // ==========================================
        case 'ultramsg':
            $instanceId = $config['phone_number_id'] ?? '';
            $token      = $config['access_token']    ?? '';

            if (empty($instanceId) || empty($token)) {
                return ['success' => false, 'message' => 'Instance ID ou Token UltraMsg manquant'];
            }

            $url = 'https://api.ultramsg.com/' . urlencode($instanceId) . '/instance/status?token=' . urlencode($token);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!empty($curlError)) {
                return ['success' => false, 'message' => 'Erreur cURL : ' . $curlError];
            }

            if ($httpCode === 200) {
                $data = json_decode((string)$response, true);
                $status = $data['status'] ?? 'unknown';
                if ($status === 'authenticated' || $status === 'connected') {
                    return ['success' => true, 'message' => 'Connexion UltraMsg réussie ! Status : ' . $status];
                }
                return ['success' => false, 'message' => 'UltraMsg non connecté (status: ' . $status . ')'];
            }

            return ['success' => false, 'message' => 'Erreur UltraMsg (HTTP ' . $httpCode . ')'];

        // ==========================================
        // META WHATSAPP CLOUD API
        // ==========================================
        case 'meta':
            $url = 'https://graph.facebook.com/v18.0/' . urlencode($config['phone_number_id']);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $config['access_token']],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT      => (defined('APP_NAME') ? APP_NAME : 'MdlEvent') . '/1.0',
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!empty($curlError)) {
                return ['success' => false, 'message' => 'Erreur cURL : ' . $curlError];
            }

            if ($httpCode === 200) {
                return ['success' => true, 'message' => 'Connexion Meta WhatsApp réussie !'];
            }

            $result = json_decode((string)$response, true);
            $error  = $result['error']['message'] ?? 'Erreur inconnue (HTTP ' . $httpCode . ')';
            return ['success' => false, 'message' => 'Erreur Meta : ' . $error];

        // ==========================================
        // AUTRES / SIMULATION
        // ==========================================
        default:
            return ['success' => true, 'message' => 'Mode simulation — aucune connexion requise'];
    }
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    // ---------- SAUVEGARDER ----------
    if ($action === 'save') {
        $whatsapp_api_url     = trim($_POST['whatsapp_api_url']     ?? '');
        $whatsapp_phone_id    = trim($_POST['whatsapp_phone_id']    ?? '');
        $whatsapp_token       = trim($_POST['whatsapp_token']       ?? '');
        $whatsapp_business_id = trim($_POST['whatsapp_business_id'] ?? '');
        $whatsapp_webhook     = trim($_POST['whatsapp_webhook']     ?? '');
        $whatsapp_service     = $_POST['whatsapp_service']           ?? 'meta';
        $configStatus         = $_POST['config_status']             ?? 'ACTIF';

        // Si le token est vide, on garde l'ancien
        $tokenModifie = !empty($whatsapp_token);
        if (!$tokenModifie) {
            try {
                $stmtTk = $pdo->query("SELECT access_token FROM whatsapp_config WHERE status = 'ACTIF' ORDER BY id DESC LIMIT 1");
                $rowTk = $stmtTk->fetch(PDO::FETCH_ASSOC);
                $whatsapp_token = $rowTk['access_token'] ?? '';
            } catch (PDOException $e) {
                $whatsapp_token = '';
            }
        }

        // Validation
        $errors = [];

        if (empty($whatsapp_api_url))     $errors[] = "L'URL de l'API WhatsApp est requise.";
        if (empty($whatsapp_phone_id))    $errors[] = "L'ID du téléphone est requis.";
        if (empty($whatsapp_token))       $errors[] = "Le token d'accès est requis.";
        if (!in_array($whatsapp_service, array_keys($services), true)) {
            $errors[] = 'Service invalide.';
        }
        if (!in_array($configStatus, ['ACTIF', 'INACTIF'], true)) {
            $errors[] = 'Statut invalide.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE whatsapp_config SET status = 'INACTIF' WHERE status = 'ACTIF'");
                $stmt->execute();

                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_config 
                    (api_key, phone_number_id, business_account_id, access_token, webhook_url, service, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $stmt->execute([
                    $whatsapp_api_url,
                    $whatsapp_phone_id,
                    $whatsapp_business_id,
                    $whatsapp_token,
                    $whatsapp_webhook,
                    $whatsapp_service,
                    $configStatus,
                ]);

                $pdo->commit();

                // ⭐ RÉGÉNÉRER config/whatsapp.php
                $regenerated = regenerateWhatsAppConfigFile($whatsapp_service);

                $message = '✅ Configuration WhatsApp enregistrée avec succès !';
                if (!$tokenModifie) {
                    $message .= ' (token inchangé)';
                }
                if ($regenerated) {
                    $message .= '<br><small>Fichier de configuration régénéré ✅</small>';
                } else {
                    $message .= '<br><small style="color:#dc3545">⚠️ Impossible de régénérer config/whatsapp.php (permissions)</small>';
                }
                $messageType = 'success';

                if (function_exists('logAction')) {
                    logAction(
                        $userId,
                        'UPDATE_WHATSAPP_CONFIG',
                        'notifications',
                        "Mise à jour de la configuration WhatsApp (service: $whatsapp_service)"
                    );
                }

                $whatsapp_token     = '';
                $tokenDejaConfigure = true;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Erreur sauvegarde config WhatsApp : ' . $e->getMessage());
                $message = '❌ Erreur lors de l\'enregistrement : ' . htmlspecialchars($e->getMessage());
                $messageType = 'danger';
            }
        } else {
            $message = implode('<br>', array_map('htmlspecialchars', $errors));
            $messageType = 'danger';
        }
    }

    // ---------- TESTER ----------
    elseif ($action === 'test') {
        $phoneId    = trim($_POST['whatsapp_phone_id']    ?? '');
        $businessId = trim($_POST['whatsapp_business_id'] ?? '');
        $token      = trim($_POST['whatsapp_token']       ?? '');
        $service    = $_POST['whatsapp_service']          ?? 'meta';

        // Si le token est vide, on le recharge depuis la BDD
        if (empty($token)) {
            try {
                $stmtTk = $pdo->query("SELECT access_token FROM whatsapp_config WHERE status = 'ACTIF' ORDER BY id DESC LIMIT 1");
                $rowTk = $stmtTk->fetch(PDO::FETCH_ASSOC);
                $token = $rowTk['access_token'] ?? '';
            } catch (PDOException $e) {
                $token = '';
            }
        }

        if (empty($token)) {
            $message = '❌ Aucun token disponible. Enregistrez d\'abord un token d\'accès.';
            $messageType = 'danger';
        } else {
            $result = testWhatsAppConfig($service, [
                'phone_number_id'     => $phoneId,
                'access_token'        => $token,
                'business_account_id' => $businessId,
            ]);

            if (!empty($result['success'])) {
                $message = '✅ ' . $result['message'];
                $messageType = 'success';
            } else {
                $message = '❌ ' . $result['message'];
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
<title>Configuration WhatsApp - <?php echo APP_NAME; ?></title>
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
    .app-wrapper { display: flex; min-height: 100vh; width: 100%; }
    .sidebar-wrapper { flex-shrink: 0; width: 260px; min-height: 100vh; position: sticky; top: 0; height: 100vh; overflow-y: auto; z-index: 100; }
    .main-content { flex: 1; min-height: 100vh; overflow-y: auto; padding: 0; min-width: 0; }
    .main-content::-webkit-scrollbar { width: 6px; }
    .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
    .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #25D366, #128C7E); border-radius: 10px; }

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
    .sidebar-toggle-btn:hover { transform: scale(1.05); }
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
    .sidebar-overlay.ACTIF { display: block; opacity: 1; }

    .content-section { padding: 25px 30px; }
    .card-custom {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        max-width: 820px;
        margin: 0 auto;
    }
    .card-custom .card-title {
        font-weight: 700; color: #1a1a1a;
        margin-bottom: 22px; padding-bottom: 14px;
        border-bottom: 2px dashed rgba(37, 211, 102, 0.15);
        font-size: 17px; display: flex; align-items: center; gap: 10px;
    }
    .card-custom .card-title i { color: #25D366; }

    .form-label {
        font-weight: 700; color: #6a5a4a;
        font-size: 12px; text-transform: uppercase;
        letter-spacing: 0.05em; margin-bottom: 6px; display: block;
    }
    .form-label i { color: #25D366; margin-right: 4px; }
    .form-control, .form-select {
        border-radius: 10px; padding: 10px 14px;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif; font-size: 13px;
        background: rgba(255, 255, 255, 0.9); color: #1a1a1a;
    }
    .form-control:focus, .form-select:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.08);
        outline: none; background: white;
    }
    .form-text { font-size: 11px; color: #9a8a7f; margin-top: 4px; }
    .form-text i { color: #25D366; }
    .form-text code {
        background: rgba(37, 211, 102, 0.1);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #128C7E; padding: 1px 6px;
        border-radius: 5px; font-size: 11px;
    }

    .btn-save {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white; border: none;
        font-weight: 700; padding: 11px 26px;
        border-radius: 10px; transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.25);
        font-size: 13px; display: inline-flex;
        align-items: center; gap: 8px; cursor: pointer;
    }
    .btn-save:hover { transform: translateY(-2px); color: white; }
    .btn-test {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: white; border: none;
        font-weight: 600; padding: 11px 22px;
        border-radius: 10px; transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
        font-size: 13px; display: inline-flex;
        align-items: center; gap: 8px; cursor: pointer;
    }
    .btn-test:hover { transform: translateY(-2px); color: white; }
    .btn-cancel {
        background: rgba(255, 255, 255, 0.9);
        color: #6a5a4a;
        border: 1.5px solid rgba(234, 227, 220, 0.6);
        font-weight: 600; padding: 11px 20px;
        border-radius: 10px; transition: all 0.3s ease;
        text-decoration: none; font-size: 13px;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-cancel:hover { background: white; color: #25D366; border-color: #25D366; }

    .alert-custom {
        border-radius: 12px; padding: 14px 18px;
        display: flex; align-items: flex-start; gap: 10px;
        font-size: 13px; margin-bottom: 16px;
    }
    .alert-custom i { font-size: 16px; flex-shrink: 0; margin-top: 2px; }

    .info-box {
        background: linear-gradient(135deg, rgba(37, 211, 102, 0.05), rgba(18, 140, 126, 0.05));
        border-left: 4px solid #25D366;
        border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;
    }
    .info-box strong {
        font-size: 13px; color: #1a1a1a;
        display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
    }
    .info-box strong i { color: #25D366; }
    .info-box ul {
        font-size: 12px; color: #6a5a4a;
        padding-left: 20px; margin: 0; line-height: 1.7;
    }
    .info-box a { color: #25D366; text-decoration: none; font-weight: 700; }
    .info-box a:hover { text-decoration: underline; }

    .badge-configure {
        background: rgba(16, 185, 129, 0.15);
        color: #065f46; font-size: 9px;
        padding: 2px 8px; border-radius: 10px;
        margin-left: 6px; font-weight: 700;
        text-transform: none; letter-spacing: 0;
    }

    .status-badge {
        display: inline-block; padding: 3px 12px;
        border-radius: 20px; font-size: 11px;
        font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .status-badge.ACTIF   { background: rgba(16, 185, 129, 0.15); color: #065f46; }
    .status-badge.INACTIF { background: rgba(239, 68, 68, 0.12); color: #991b1b; }

    .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) {
        .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
    }

    .app-footer {
        text-align: center; padding: 30px 0 20px;
        color: #b8a99c; font-size: 13px;
    }
    .app-footer i.bi-heart-fill { color: #25D366; }

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
        .sidebar-overlay.ACTIF { visibility: visible; opacity: 1; pointer-events: auto; }
        .top-bar { padding: 12px 15px 12px 70px; flex-direction: row; flex-wrap: wrap; }
        body.sidebar-open { overflow-x: hidden !important; overflow-y: auto !important; }
        .content-section { padding: 15px; }
        .top-bar .page-title h4 { font-size: 1rem; }
        .top-bar .user-info .user-name { display: none; }
        .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
        .card-custom { padding: 20px; }
    }

    @media (max-width: 576px) {
        .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
        .top-bar .page-title h4 { font-size: 0.95rem; }
        .top-bar .user-info { justify-content: flex-end; gap: 10px; }
        .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
        .content-section { padding: 10px 12px; }
        .card-custom { padding: 15px; border-radius: 14px; }
        .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
        .btn-save, .btn-test, .btn-cancel { width: 100%; justify-content: center; }
        .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
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
                <h4><i class="bi bi-whatsapp"></i> Configuration WhatsApp</h4>
                <small><i class="bi bi-sliders2"></i> Paramètres WhatsApp Business API</small>
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
            <div class="card-custom fade-in">

                <h5 class="card-title">
                    <i class="bi bi-whatsapp"></i> Configuration WhatsApp Business API
                </h5>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-custom">
                        <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($configStatus === 'ACTIF'): ?>
                    <div class="alert-custom alert-success" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#065f46">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            Configuration <span class="status-badge ACTIF">ACTIF</span>
                            <span style="font-size:12px;margin-left:8px;opacity:0.8">
                                Service : <?php echo htmlspecialchars($services[$whatsapp_service] ?? $whatsapp_service); ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert-custom alert-warning" style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);color:#92400e">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            Configuration <span class="status-badge INACTIF">INACTIF</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong><i class="bi bi-info-circle-fill"></i> WhatsApp Business API</strong>
                    <ul>
                        <li>Créez un compte sur <a href="https://business.facebook.com/" target="_blank" rel="noopener">Meta Business</a>, <a href="https://www.twilio.com/" target="_blank" rel="noopener">Twilio</a> ou <a href="https://ultramsg.com/" target="_blank" rel="noopener">UltraMsg</a></li>
                        <li>Obtenez un <strong>Numéro de téléphone business</strong> et un <strong>Token d'accès</strong></li>
                        <li>Les messages sont envoyés via l'API officielle</li>
                    </ul>
                </div>

                <form method="POST" action="" id="whatsappForm">
                    <input type="hidden" name="action" value="save">

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-server"></i> Service <span class="text-danger">*</span></label>
                        <select class="form-select" name="whatsapp_service" required>
                            <?php foreach ($services as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $whatsapp_service === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choisissez le service WhatsApp que vous utilisez</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-link-45deg"></i> API URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="whatsapp_api_url"
                               value="<?php echo htmlspecialchars($whatsapp_api_url ?: 'https://graph.facebook.com/v18.0/'); ?>"
                               placeholder="https://graph.facebook.com/v18.0/" required>
                        <div class="form-text">URL de l'API WhatsApp Business</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-phone-fill"></i> ID du téléphone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="whatsapp_phone_id"
                               value="<?php echo htmlspecialchars($whatsapp_phone_id); ?>"
                               placeholder="Ex: 123456789012345 ou +14155238886" required>
                        <div class="form-text">ID du numéro (Meta : Phone Number ID / Twilio : numéro WhatsApp / UltraMsg : instance ID)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-key-fill"></i> Token d'accès
                            <?php if ($tokenDejaConfigure): ?>
                                <span class="badge-configure">
                                    <i class="bi bi-check-circle-fill"></i> CONFIGURÉ
                                </span>
                            <?php else: ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <input type="password"
                               class="form-control"
                               name="whatsapp_token"
                               id="whatsapp_token"
                               value=""
                               placeholder="<?php echo $tokenDejaConfigure ? '••••••••••• Laisser vide pour conserver' : 'EAAB...'; ?>"
                               autocomplete="new-password"
                               <?php echo $tokenDejaConfigure ? '' : 'required'; ?>>
                        <?php if ($tokenDejaConfigure): ?>
                            <div class="form-text">
                                <i class="bi bi-shield-check" style="color:#10b981"></i>
                                Un token est déjà enregistré. Laissez vide pour le conserver.
                            </div>
                        <?php else: ?>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i>
                                Token d'accès (Meta : Bearer Token / Twilio : Auth Token / UltraMsg : Token)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-building"></i> Business ID / Account SID</label>
                        <input type="text" class="form-control" name="whatsapp_business_id"
                               value="<?php echo htmlspecialchars($whatsapp_business_id); ?>"
                               placeholder="Meta : Business ID / Twilio : Account SID (ACxxx)">
                        <div class="form-text">Meta : Business Account ID / Twilio : Account SID / UltraMsg : (vide)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-link"></i> Webhook URL</label>
                        <input type="text" class="form-control" name="whatsapp_webhook"
                               value="<?php echo htmlspecialchars($whatsapp_webhook); ?>"
                               placeholder="https://votre-domaine.com/webhook/whatsapp">
                        <div class="form-text">URL de réception des messages (optionnel)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-toggle-on"></i> Statut</label>
                        <select class="form-select" name="config_status">
                            <option value="ACTIF"   <?php echo $configStatus === 'ACTIF'   ? 'selected' : ''; ?>>Actif</option>
                            <option value="INACTIF" <?php echo $configStatus === 'INACTIF' ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                        <div class="form-text">Activez ou désactivez la configuration</div>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>

                <!-- TEST CONNEXION -->
                <div class="mt-4 pt-3" style="border-top:2px dashed rgba(37,211,102,0.15)">
                    <h6 class="fw-bold mb-3" style="font-size:14px">
                        <i class="bi bi-bug-fill" style="color:#25D366"></i> Tester la connexion
                    </h6>
                    <form method="POST" action="" id="testForm">
                        <input type="hidden" name="action" value="test">
                        <input type="hidden" name="whatsapp_phone_id" value="<?php echo htmlspecialchars($whatsapp_phone_id); ?>">
                        <input type="hidden" name="whatsapp_business_id" value="<?php echo htmlspecialchars($whatsapp_business_id); ?>">
                        <input type="hidden" name="whatsapp_service" value="<?php echo htmlspecialchars($whatsapp_service); ?>">
                        <input type="hidden" name="whatsapp_token" value="">
                        <button type="submit" class="btn-test">
                            <i class="bi bi-plug-fill"></i> Tester la connexion
                        </button>
                    </form>
                </div>

                <!-- AIDE -->
                <div class="mt-4 pt-3" style="border-top:2px dashed rgba(37,211,102,0.15)">
                    <h6 class="fw-bold mb-2" style="font-size:14px">
                        <i class="bi bi-question-circle-fill" style="color:#25D366"></i> Comment obtenir vos identifiants
                    </h6>
                    <ol class="small" style="color:#9a8a7f;padding-left:20px;line-height:1.8">
                        <li><strong>Meta</strong> : <a href="https://business.facebook.com/" target="_blank" rel="noopener" style="color:#25D366;font-weight:700">Meta Business</a> → Phone Number ID + Business ID + Token</li>
                        <li><strong>Twilio</strong> : <a href="https://www.twilio.com/" target="_blank" rel="noopener" style="color:#25D366;font-weight:700">Twilio Console</a> → Numéro WhatsApp (+1415...) + Account SID (ACxxx) + Auth Token</li>
                        <li><strong>UltraMsg</strong> : <a href="https://ultramsg.com/" target="_blank" rel="noopener" style="color:#25D366;font-weight:700">UltraMsg</a> → Instance ID (instanceXXXXX) + Token</li>
                    </ol>
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
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebarWrapper = document.getElementById('sidebarWrapper');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebarWrapper.classList.add('open');
    sidebarOverlay.classList.add('ACTIF');
    document.body.classList.add('sidebar-open');
}
function closeSidebar() {
    sidebarWrapper.classList.remove('open');
    sidebarOverlay.classList.remove('ACTIF');
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
</script>
</body>
</html>