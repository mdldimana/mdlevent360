<?php
// Configuration WhatsApp - EXEMPLE (à copier vers config/whatsapp.php)
// ⚠️ Ne jamais commiter le vrai fichier config/whatsapp.php

if (!defined('WHATSAPP_SERVICE')) {
    define('WHATSAPP_SERVICE', 'ultramsg');  // ou 'meta', 'twilio'
}

$whatsappTemplates = [
    'invitation' => [
        'name'     => 'invitation_event',
        'subject'  => 'Invitation à l\'événement',
        'template' => "Bonjour {nom} {prenom},\n\nNous avons le plaisir de vous inviter...\n\nLien : {url_validation}",
    ],
    // ... autres templates
];