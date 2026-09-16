<?php
/**
 * Helper WhatsApp
 * Fonctions pour l'envoi de notifications WhatsApp
 * Utilise les tables whatsapp_config et whatsapp_messages
 */

require_once __DIR__ . '/../config/whatsapp.php';

// ============================================
// FONCTIONS PRINCIPALES
// ============================================

/**
 * Récupère la configuration WhatsApp active
 */
function getWhatsAppConfig() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Erreur récupération config WhatsApp: ' . $e->getMessage());
        return null;
    }
}

/**
 * Envoie un message WhatsApp
 * 
 * @param string $to Numéro de téléphone du destinataire (format international)
 * @param string $message Contenu du message
 * @param int|null $invitationId ID de l'invitation associée
 * @param string $templateName Nom du template utilisé
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function sendWhatsAppMessage($to, $message, $invitationId = null, $templateName = null) {
    // Nettoyer le numéro de téléphone
    $to = cleanPhoneNumber($to);
    
    if (empty($to)) {
        return ['success' => false, 'message' => 'Numéro de téléphone invalide'];
    }
    
    // Récupérer la configuration
    $config = getWhatsAppConfig();
    if (!$config) {
        return ['success' => false, 'message' => 'Configuration WhatsApp non trouvée'];
    }
    
    // Envoyer selon le service configuré
    switch (WHATSAPP_SERVICE) {
        case 'meta':
            $result = sendWhatsAppMeta($to, $message, $config);
            break;
        case 'twilio':
            $result = sendWhatsAppTwilio($to, $message, $config);
            break;
        case 'ultramsg':
            $result = sendWhatsAppUltraMsg($to, $message, $config);
            break;
        default:
            // Mode simulation pour le développement
            $result = sendWhatsAppSimulation($to, $message, $config);
    }
    
    // Enregistrer dans la base de données
    saveWhatsAppMessage($to, $message, $result, $invitationId, $templateName);
    
    return $result;
}

/**
 * Envoi via Meta WhatsApp Cloud API
 */
function sendWhatsAppMeta($to, $message, $config) {
    $url = 'https://graph.facebook.com/v18.0/' . $config['phone_number_id'] . '/messages';
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => $message]
    ];
    
    // Ajouter un aperçu de lien si présent dans le message
    if (strpos($message, 'http') !== false) {
        $data['text']['preview_url'] = true;
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['access_token']
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true, 
            'message' => 'Message envoyé avec succès', 
            'data' => $result,
            'message_id' => $result['messages'][0]['id'] ?? null
        ];
    } else {
        return [
            'success' => false, 
            'message' => $result['error']['message'] ?? 'Erreur d\'envoi', 
            'data' => $result,
            'error_message' => $result['error']['message'] ?? null
        ];
    }
}

/**
 * Envoi via Twilio WhatsApp API
 */
function sendWhatsAppTwilio($to, $message, $config) {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $config['api_key'] . '/Messages.json';
    
    $data = [
        'To' => 'whatsapp:' . $to,
        'From' => 'whatsapp:' . $config['phone_number_id'],
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, $config['api_key'] . ':' . $config['access_token']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true, 
            'message' => 'Message envoyé avec succès', 
            'data' => $result,
            'message_id' => $result['sid'] ?? null
        ];
    } else {
        return [
            'success' => false, 
            'message' => $result['error']['message'] ?? 'Erreur d\'envoi', 
            'data' => $result,
            'error_message' => $result['error']['message'] ?? null
        ];
    }
}

/**
 * Envoi via UltraMsg API
 */
function sendWhatsAppUltraMsg($to, $message, $config) {
    $url = 'https://api.ultramsg.com/' . $config['api_key'] . '/messages/chat';
    
    $data = [
        'token' => $config['access_token'],
        'to' => $to,
        'body' => $message,
        'priority' => '1'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['sent']) && $result['sent'] === true) {
        return [
            'success' => true, 
            'message' => 'Message envoyé avec succès', 
            'data' => $result,
            'message_id' => $result['message_id'] ?? null
        ];
    } else {
        return [
            'success' => false, 
            'message' => $result['error'] ?? 'Erreur d\'envoi', 
            'data' => $result,
            'error_message' => $result['error'] ?? null
        ];
    }
}

/**
 * Simulation d'envoi (pour développement)
 */
function sendWhatsAppSimulation($to, $message, $config) {
    // Journaliser en simulation
    $logMessage = "[SIMULATION] WhatsApp à $to : " . substr($message, 0, 100) . '...';
    logMessage($logMessage, 'INFO');
    
    return [
        'success' => true, 
        'message' => 'Message envoyé en simulation', 
        'data' => ['simulated' => true, 'to' => $to],
        'message_id' => 'sim_' . uniqid()
    ];
}

// ============================================
// FONCTIONS DE BASE DE DONNÉES
// ============================================

/**
 * Enregistre un message WhatsApp dans la base de données
 */
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
            substr($message, 0, 5000), // Limiter la taille du message
            $status,
            $messageId,
            $errorMessage
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log('Erreur sauvegarde message WhatsApp: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut de livraison d'un message
 */
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

/**
 * Récupère l'historique des messages WhatsApp
 */
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

/**
 * Récupère le nombre total de messages
 */
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

/**
 * Nettoie un numéro de téléphone
 */
function cleanPhoneNumber($phone) {
    // Supprimer tous les caractères non numériques
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Si le numéro commence par 0, le remplacer par +243 (RDC)
    if (strpos($phone, '0') === 0) {
        $phone = '+243' . substr($phone, 1);
    }
    
    // Si le numéro ne commence pas par +, ajouter +243 (par défaut RDC)
    if (strpos($phone, '+') !== 0) {
        if (strlen($phone) === 9 || strlen($phone) === 10) {
            $phone = '+243' . $phone;
        }
    }
    
    // Vérifier que le numéro est valide (au moins 10 chiffres)
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) < 9) {
        return '';
    }
    
    return $phone;
}

/**
 * Récupère un template WhatsApp
 */
function getWhatsAppTemplate($type, $data = []) {
    global $whatsappTemplates;
    
    if (!isset($whatsappTemplates[$type])) {
        return null;
    }
    
    $template = $whatsappTemplates[$type]['template'];
    
    // Remplacer les variables
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
        
        // Récupérer les infos de l'invité
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
        
        // Vérifier la préférence de contact
        if ($invite['contact_preference'] !== 'WHATSAPP' && $invite['contact_preference'] !== 'SMS') {
            return ['success' => false, 'message' => 'L\'invité ne souhaite pas être contacté par WhatsApp'];
        }
        
        // Vérifier le numéro de téléphone
        if (empty($invite['telephone'])) {
            return ['success' => false, 'message' => 'Numéro de téléphone manquant'];
        }
        
        // Préparer les données du template
        $templateData = [
            'nom' => $invite['nom'],
            'prenom' => $invite['prenom'],
            'evenement' => $invite['evenement_nom'] ?? 'Événement',
            'date' => $invite['date_evenement'] ? date('d/m/Y', strtotime($invite['date_evenement'])) : 'À confirmer',
            'heure' => $invite['heure_evenement'] ?? 'À confirmer',
            'lieu' => $invite['lieu'] ?? 'À confirmer',
            'code_unique' => $invite['code_unique'] ?? '',
            'url_validation' => APP_URL . '/public/validation.php?code=' . ($invite['code_unique'] ?? ''),
            'nb_personnes' => $data['nombre_personnes'] ?? $invite['nb_presents'] ?? 1,
        ];
        
        // Fusionner avec les données supplémentaires
        $templateData = array_merge($templateData, $data);
        
        // Récupérer le template
        $message = getWhatsAppTemplate($type, $templateData);
        
        if (!$message) {
            return ['success' => false, 'message' => 'Template non trouvé'];
        }
        
        // Utiliser l'ID d'invitation fourni ou celui récupéré
        $invitationId = $invitationId ?: $invite['invitation_id'];
        
        // Envoyer le message
        $result = sendWhatsAppMessage($invite['telephone'], $message, $invitationId, $type);
        
        // Journaliser dans l'application
        if ($result['success']) {
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
            'success' => $result['success'],
            'message' => $result['message']
        ];
        
        if ($result['success']) {
            $successCount++;
        } else {
            $failCount++;
        }
        
        // Pause pour éviter le rate limiting
        usleep(200000); // 0.2 seconde entre chaque envoi
    }
    
    return [
        'success' => $successCount > 0,
        'total' => count($inviteIds),
        'success_count' => $successCount,
        'fail_count' => $failCount,
        'results' => $results
    ];
}

/**
 * Récupère les statistiques WhatsApp
 */
function getWhatsAppStats() {
    try {
        $pdo = getDbConnection();
        
        $stats = [];
        
        // Total messages
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages");
        $stats['total'] = $stmt->fetch()['total'] ?? 0;
        
        // Messages envoyés
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE status = 'sent'");
        $stats['sent'] = $stmt->fetch()['total'] ?? 0;
        
        // Messages échoués
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE status = 'failed'");
        $stats['failed'] = $stmt->fetch()['total'] ?? 0;
        
        // Délivrés
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE delivered_at IS NOT NULL");
        $stats['delivered'] = $stmt->fetch()['total'] ?? 0;
        
        // Lus
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE read_at IS NOT NULL");
        $stats['read'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log('Erreur statistiques WhatsApp: ' . $e->getMessage());
        return ['total' => 0, 'sent' => 0, 'failed' => 0, 'delivered' => 0, 'read' => 0];
    }
}