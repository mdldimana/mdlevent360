<?php
/**
 * Configuration WhatsApp
 * Utilise les tables whatsapp_config et whatsapp_messages
 * 
 * ⚠️ Ne pas modifier manuellement - Utilisez l'interface d'administration
 * Fichier généré automatiquement
 */

// ============================================
// CONFIGURATION WHATSAPP
// ============================================

// Service utilisé: 'meta', 'twilio', 'ultramsg', 'simulation'
if (!defined('WHATSAPP_SERVICE')) {
    define('WHATSAPP_SERVICE', 'twilio');
}

// ============================================
// CONFIGURATION DES TEMPLATES
// ============================================

$whatsappTemplates = [
    // Template pour les invitations
    'invitation' => [
        'name'    => 'invitation_event',
        'subject' => 'Invitation à l\'événement',
        'template' => "Bonjour {nom} {prenom},

Nous avons le plaisir de vous inviter à l'événement \"{evenement}\" qui aura lieu le {date} à {heure}.

Lieu : {lieu}
Nombre de personnes : {nb_personnes}
Code d'accès : {code_unique}

Veuillez confirmer votre présence via le lien ci-dessous :
{url_validation}

Nous avons hâte de vous accueillir !"
    ],

    // Template pour les confirmations
    'confirmation' => [
        'name'    => 'confirmation_event',
        'subject' => 'Invitation confirmée',
        'template' => "Bonjour {nom} {prenom},

Nous confirmons votre participation à l'événement \"{evenement}\" du {date}.

Lieu : {lieu}
Nombre de personnes : {nb_personnes}

N'oubliez pas de scanner votre QR code à l'entrée.

À très bientôt !"
    ],

    // Template pour les rappels
    'rappel' => [
        'name'    => 'rappel_event',
        'subject' => 'Rappel - Événement',
        'template' => "Bonjour {nom} {prenom},

Ce message pour vous rappeler l'événement \"{evenement}\" qui aura lieu demain le {date} à {heure}.

Lieu : {lieu}

N'oubliez pas votre QR code pour l'entrée.

À demain !"
    ],

    // Template pour les présences
    'present' => [
        'name'    => 'presence_confirmee',
        'subject' => 'Présence enregistrée',
        'template' => "Bonjour {nom} {prenom},

Votre présence à l'événement \"{evenement}\" a été enregistrée avec succès !

Bonne journée et profitez bien de l'événement."
    ],

    // Template pour les annulations
    'annulation' => [
        'name'    => 'annulation_event',
        'subject' => 'Annulation d\'invitation',
        'template' => "Bonjour {nom} {prenom},

Nous accusons réception de votre annulation pour l'événement \"{evenement}\".

Nous espérons vous revoir à une prochaine occasion.

Cordialement."
    ]
];