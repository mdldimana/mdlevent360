<?php
/**
 * Helper WhatsApp
 * Fonctions pour l'envoi de notifications WhatsApp
 * 
 * ⚠️ VERSION CORRIGÉE : Support Meta + Twilio + UltraMsg
 *    + Mise à jour automatique des statuts d'invitation
 */

require_once __DIR__ . '/../config/whatsapp.php';

// ============================================
// CONFIGURATION DES TEMPLATES META
// ============================================
if (!defined('META_TEMPLATE_INVITATION'))  define('META_TEMPLATE_INVITATION', 'hello_world');
if (!defined('META_TEMPLATE_CONFIRMATION')) define('META_TEMPLATE_CONFIRMATION', 'hello_world');
if (!defined('META_TEMPLATE_RAPPEL'))       define('META_TEMPLATE_RAPPEL', 'hello_world');
if (!defined('META_TEMPLATE_PRESENCE'))     define('META_TEMPLATE_PRESENCE', 'hello_world');
if (!defined('META_TEMPLATE_ANNULATION'))   define('META_TEMPLATE_ANNULATION', 'hello_world');
if (!defined('META_TEMPLATE_LANGUAGE'))     define('META_TEMPLATE_LANGUAGE', 'en_US');

// ============================================
// FONCTIONS PRINCIPALES
// ============================================

function getWhatsAppConfig() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE status IN ('ACTIF', 'active') ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Erreur récupération config WhatsApp: ' . $e->getMessage());
        return null;
    }
}

function getMetaTemplateName($type) {
    $templates = [
        'invitation'   => META_TEMPLATE_INVITATION,
        'confirmation' => META_TEMPLATE_CONFIRMATION,
        'rappel'       => META_TEMPLATE_RAPPEL,
        'present'      => META_TEMPLATE_PRESENCE,
        'presence'     => META_TEMPLATE_PRESENCE,
        'annulation'   => META_TEMPLATE_ANNULATION,
    ];
    return $templates[$type] ?? META_TEMPLATE_INVITATION;
}

/**
 * Envoie un message WhatsApp
 */
function sendWhatsAppMessage($to, $message, $invitationId = null, $templateName = null) {
    $to = cleanPhoneNumber($to);
    
    if (empty($to)) {
        return ['success' => false, 'message' => 'Numéro de téléphone invalide'];
    }
    
    $config = getWhatsAppConfig();
    if (!$config) {
        return ['success' => false, 'message' => 'Configuration WhatsApp non trouvée'];
    }
    
    switch (WHATSAPP_SERVICE) {
        case 'meta':
            $result = sendWhatsAppMeta($to, $message, $config, $templateName);
            break;
        case 'twilio':
            $result = sendWhatsAppTwilio($to, $message, $config);
            break;
        case 'ultramsg':
            $result = sendWhatsAppUltraMsg($to, $message, $config);
            break;
        default:
            $result = sendWhatsAppSimulation($to, $message, $config);
    }
    
    // Sauvegarder l'historique
    saveWhatsAppMessage($to, $message, $result, $invitationId, $templateName);
    
    // ⭐ Mettre à jour le statut de l'invitation si envoi réussi
    if (!empty($result['success']) && !empty($invitationId)) {
        updateInvitationWhatsAppStatus($invitationId, $templateName);
    }
    
    return $result;
}

/**
 * ⭐ Met à jour le statut WhatsApp de l'invitation
 */
function updateInvitationWhatsAppStatus($invitationId, $templateName = null) {
    if (empty($invitationId)) {
        return false;
    }
    
    try {
        $pdo = getDbConnection();
        
        // Vérifier si la colonne existe (au cas où)
        $stmt = $pdo->prepare("
            UPDATE invitations 
            SET whatsapp_sent = 1,
                whatsapp_sent_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$invitationId]);
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log('Erreur mise à jour WhatsApp invitation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Envoi via Meta WhatsApp Cloud API
 */
function sendWhatsAppMeta($to, $message, $config, $type = null) {
    $url = 'https://graph.facebook.com/v18.0/' . $config['phone_number_id'] . '/messages';
    
    $metaTemplateName = getMetaTemplateName($type ?? 'invitation');
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'template',
        'template'          => [
            'name'     => $metaTemplateName,
            'language' => [
                'code' => META_TEMPLATE_LANGUAGE,
            ],
        ],
    ];
    
    if ($metaTemplateName !== 'hello_world') {
        $data['template']['components'] = [
            [
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Invité'],
                    ['type' => 'text', 'text' => 'Événement'],
                ],
            ],
        ];
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['access_token'],
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curlError)) {
        return [
            'success'       => false,
            'message'       => 'Erreur cURL : ' . $curlError,
            'error_message' => $curlError,
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success'    => true,
            'message'    => 'Message envoyé avec succès',
            'data'       => $result,
            'message_id' => $result['messages'][0]['id'] ?? null,
        ];
    } else {
        $errorMsg = $result['error']['message'] ?? 'Erreur inconnue (HTTP ' . $httpCode . ')';
        return [
            'success'       => false,
            'message'       => 'Erreur Meta : ' . $errorMsg,
            'data'          => $result,
            'error_message' => $errorMsg,
        ];
    }
}

/**
 * Envoi via Twilio WhatsApp API
 */
function sendWhatsAppTwilio($to, $message, $config) {
    $accountSid = $config['business_account_id'] ?? '';
    
    if (empty($accountSid) && !empty($config['api_key']) && strpos($config['api_key'], 'AC') === 0) {
        $accountSid = $config['api_key'];
    }
    
    if (empty($accountSid)) {
        return [
            'success'       => false,
            'message'       => 'Account SID Twilio manquant',
            'error_message' => 'business_account_id est vide',
        ];
    }
    
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . urlencode($accountSid) . '/Messages.json';
    
    $fromNumber = $config['phone_number_id'] ?? '';
    if (strpos($fromNumber, 'whatsapp:') !== 0) {
        $fromNumber = 'whatsapp:' . $fromNumber;
    }
    
    $toNumber = $to;
    if (strpos($toNumber, 'whatsapp:') !== 0) {
        $toNumber = 'whatsapp:' . $toNumber;
    }
    
    $data = [
        'To'   => $toNumber,
        'From' => $fromNumber,
        'Body' => $message,
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $config['access_token']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curlError)) {
        return [
            'success'       => false,
            'message'       => 'Erreur cURL : ' . $curlError,
            'error_message' => $curlError,
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success'    => true,
            'message'    => 'Message envoyé avec succès via Twilio',
            'data'       => $result,
            'message_id' => $result['sid'] ?? null,
        ];
    } else {
        $errorMsg = $result['message'] ?? 'Erreur d\'envoi Twilio (HTTP ' . $httpCode . ')';
        return [
            'success'       => false,
            'message'       => 'Erreur Twilio : ' . $errorMsg,
            'data'          => $result,
            'error_message' => $errorMsg,
        ];
    }
}

/**
 * Envoi via UltraMsg API
 */
function sendWhatsAppUltraMsg($to, $message, $config) {
    $instanceId = $config['phone_number_id'] ?? '';
    
    if (empty($instanceId)) {
        return [
            'success'       => false,
            'message'       => 'Instance ID UltraMsg manquant',
            'error_message' => 'phone_number_id est vide',
        ];
    }
    
    $url = 'https://api.ultramsg.com/' . urlencode($instanceId) . '/messages/chat';
    
    $data = [
        'token'    => $config['access_token'],
        'to'       => $to,
        'body'     => $message,
        'priority' => '1',
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curlError)) {
        return [
            'success'       => false,
            'message'       => 'Erreur cURL : ' . $curlError,
            'error_message' => $curlError,
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['sent']) && ($result['sent'] === true 
        || $result['sent'] === 'true' 
        || $result['sent'] === 1 
        || $result['sent'] === '1')) {
        return [
            'success'    => true,
            'message'    => 'Message envoyé avec succès via UltraMsg',
            'data'       => $result,
            'message_id' => $result['id'] ?? null,
        ];
    } else {
        // ⭐ Extraire l'erreur (peut être un tableau)
$errorMsg = 'Erreur inconnue';
if (isset($result['error'])) {
    $errorMsg = is_array($result['error']) ? json_encode($result['error']) : (string)$result['error'];
} elseif (isset($result['message'])) {
    $errorMsg = is_array($result['message']) ? json_encode($result['message']) : (string)$result['message'];
}
        return [
            'success'       => false,
            'message'       => 'Erreur UltraMsg : ' . $errorMsg,
            'data'          => $result,
            'error_message' => $errorMsg,
        ];
    }
}

/**
 * Simulation d'envoi (pour développement)
 */
function sendWhatsAppSimulation($to, $message, $config) {
    $logMessage = "[SIMULATION] WhatsApp à $to : " . substr($message, 0, 100) . '...';
    if (function_exists('logMessage')) {
        logMessage($logMessage, 'INFO');
    } else {
        error_log($logMessage);
    }
    
    return [
        'success'    => true,
        'message'    => 'Message envoyé en simulation',
        'data'       => ['simulated' => true, 'to' => $to],
        'message_id' => 'sim_' . uniqid(),
    ];
}

// ============================================
// FONCTIONS DE BASE DE DONNÉES
// ============================================

function saveWhatsAppMessage($to, $message, $result, $invitationId = null, $templateName = null) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_messages 
            (invitation_id, to_phone, template_name, message_content, status, message_id, sent_at, error_message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())
        ");
        
        $status = $result['success'] ? 'sent' : 'failed';
        $messageId = $result['message_id'] ?? null;
        $errorMessage = $result['error_message'] ?? null;
        
        $stmt->execute([
            $invitationId,
            $to,
            $templateName,
            substr($message, 0, 5000),
            $status,
            $messageId,
            $errorMessage,
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log('Erreur sauvegarde message WhatsApp: ' . $e->getMessage());
        return false;
    }
}

function updateWhatsAppMessageStatus($messageId, $status, $deliveredAt = null, $readAt = null) {
    try {
        $pdo = getDbConnection();
        
        $sql = "UPDATE whatsapp_messages SET status = ?";
        $params = [$status];
        
        if ($deliveredAt) {
            $sql .= ", delivered_at = ?";
            $params[] = $deliveredAt;
        }
        
        if ($readAt) {
            $sql .= ", read_at = ?";
            $params[] = $readAt;
        }
        
        $sql .= " WHERE message_id = ?";
        $params[] = $messageId;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Erreur mise à jour statut WhatsApp: ' . $e->getMessage());
        return false;
    }
}

function getWhatsAppHistory($limit = 100, $offset = 0, $filters = []) {
    try {
        $pdo = getDbConnection();
        
        $sql = "
            SELECT 
                wm.*,
                inv.nom as invite_nom,
                inv.prenom as invite_prenom,
                e.nom as evenement_nom
            FROM whatsapp_messages wm
            LEFT JOIN invitations i ON wm.invitation_id = i.id
            LEFT JOIN invites inv ON i.id_invite = inv.id
            LEFT JOIN evenements e ON i.id_evenement = e.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND wm.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['to_phone'])) {
            $sql .= " AND wm.to_phone LIKE ?";
            $params[] = '%' . $filters['to_phone'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(wm.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(wm.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY wm.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Erreur récupération historique WhatsApp: ' . $e->getMessage());
        return [];
    }
}

function getWhatsAppTotalCount($filters = []) {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT COUNT(*) as total FROM whatsapp_messages WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
        
    } catch (PDOException $e) {
        error_log('Erreur comptage WhatsApp: ' . $e->getMessage());
        return 0;
    }
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

function cleanPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strpos($phone, '0') === 0) {
        $phone = '+243' . substr($phone, 1);
    }
    
    if (strpos($phone, '+') !== 0) {
        if (strlen($phone) === 9 || strlen($phone) === 10) {
            $phone = '+243' . $phone;
        }
    }
    
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) < 9) {
        return '';
    }
    
    return $phone;
}

function getWhatsAppTemplate($type, $data = []) {
    global $whatsappTemplates;
    
    if (!isset($whatsappTemplates[$type])) {
        return null;
    }
    
    $template = $whatsappTemplates[$type]['template'];
    
    foreach ($data as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    
    return $template;
}

/**
 * Envoie une notification WhatsApp à un invité
 */
function sendWhatsAppToInvite($inviteId, $type = 'invitation', $data = [], $invitationId = null) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                inv.id,
                inv.nom,
                inv.prenom,
                inv.telephone,
                inv.contact_preference,
                i.id as invitation_id,
                i.code_unique,
                i.nb_presents,
                e.nom as evenement_nom,
                e.date_evenement,
                e.heure_evenement,
                e.lieu
            FROM invites inv
            LEFT JOIN invitations i ON inv.id = i.id_invite
            LEFT JOIN evenements e ON i.id_evenement = e.id
            WHERE inv.id = ?
        ");
        $stmt->execute([$inviteId]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            return ['success' => false, 'message' => 'Invité non trouvé'];
        }
        
        if (empty($invite['telephone'])) {
            return ['success' => false, 'message' => 'Numéro de téléphone manquant'];
        }
        
        $templateData = [
            'nom'            => $invite['nom'],
            'prenom'         => $invite['prenom'],
            'evenement'      => $invite['evenement_nom'] ?? 'Événement',
            'date'           => $invite['date_evenement'] ? date('d/m/Y', strtotime($invite['date_evenement'])) : 'À confirmer',
            'heure'          => $invite['heure_evenement'] ?? 'À confirmer',
            'lieu'           => $invite['lieu'] ?? 'À confirmer',
            'code_unique'    => $invite['code_unique'] ?? '',
            'url_validation' => (defined('APP_URL') ? APP_URL : '') . '/public/invitation.php?code=' . ($invite['code_unique'] ?? ''),
            'nb_personnes'   => $data['nombre_personnes'] ?? $invite['nb_presents'] ?? 1,
        ];
        
        $templateData = array_merge($templateData, $data);
        
        $message = getWhatsAppTemplate($type, $templateData);
        
        if (!$message) {
            return ['success' => false, 'message' => 'Template non trouvé'];
        }
        
        $invitationId = $invitationId ?: $invite['invitation_id'];
        
        $result = sendWhatsAppMessage($invite['telephone'], $message, $invitationId, $type);
        
        if ($result['success'] && function_exists('logAction')) {
            logAction(
                $_SESSION['user_id'] ?? 1,
                'WHATSAPP_SEND',
                'notifications',
                "Envoi WhatsApp à {$invite['prenom']} {$invite['nom']} (ID: {$invite['id']}) - Type: $type"
            );
        }
        
        return $result;
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

/**
 * Envoie une notification WhatsApp groupée
 */
function sendWhatsAppBulk($inviteIds, $type = 'invitation', $data = [], $invitationId = null) {
    $results = [];
    $successCount = 0;
    $failCount = 0;
    
    foreach ($inviteIds as $inviteId) {
        $result = sendWhatsAppToInvite($inviteId, $type, $data, $invitationId);
        $results[] = [
            'invite_id' => $inviteId,
            'success'   => $result['success'],
            'message'   => $result['message'],
        ];
        
        if ($result['success']) {
            $successCount++;
        } else {
            $failCount++;
        }
        
        usleep(500000);
    }
    
    return [
        'success'       => $successCount > 0,
        'total'         => count($inviteIds),
        'success_count' => $successCount,
        'fail_count'    => $failCount,
        'results'       => $results,
    ];
}

/**
 * Récupère les statistiques WhatsApp
 */
function getWhatsAppStats() {
    try {
        $pdo = getDbConnection();
        
        $stats = [];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages");
        $stats['total'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE status = 'sent'");
        $stats['sent'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE status = 'failed'");
        $stats['failed'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE delivered_at IS NOT NULL");
        $stats['delivered'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE read_at IS NOT NULL");
        $stats['read'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log('Erreur statistiques WhatsApp: ' . $e->getMessage());
        return ['total' => 0, 'sent' => 0, 'failed' => 0, 'delivered' => 0, 'read' => 0];
    }
}