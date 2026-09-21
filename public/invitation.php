<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ============================================================
// CONFIGURATION
// ============================================================
require_once __DIR__ . '/../config/database.php';

// Détection auto du chemin de base (compatible InfinityFree, mutualisé, local)
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Planning Events');

// ============================================================
// RÉCUPÉRATION DU CODE D'INVITATION
// ============================================================
$code   = $_GET['code']   ?? '';
$action = $_GET['action'] ?? '';

if (empty($code)) {
    http_response_code(400);
    die('Code d\'invitation manquant');
}

// ============================================================
// CONNEXION BDD
// ============================================================
try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    error_log('Erreur connexion invitation : ' . $e->getMessage());
    http_response_code(500);
    die('Erreur de connexion à la base de données.');
}

// ============================================================
// CHARGER LES HELPERS PARTAGÉS
// ============================================================
$helpersPath = __DIR__ . '/templates/invitations/_helpers.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
}

// ============================================================
// RÉCUPÉRER L'INVITATION
// ============================================================
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            inv.nom, inv.prenom, inv.email, inv.telephone,
            inv.nombre_personnes AS nb_places_max,
            e.nom AS evenement_nom,
            e.type_evenement,
            e.description AS evenement_description,
            e.date_evenement,
            e.heure_evenement,
            e.lieu,
            e.adresse AS evenement_adresse,
            e.fond AS evenement_image,
            e.modele_invitation,
            c.reponse,
            c.nombre_personnes AS nb_confirme,
            c.commentaire,
            c.date_confirmation,
            t.nom AS table_nom,
            t.numero AS table_numero,
            t.zone AS table_zone
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        LEFT JOIN confirmations c ON i.id = c.id_invitation
        LEFT JOIN invitations_tables it ON it.id_invitation = i.id
        LEFT JOIN tables t ON t.id = it.id_table
        WHERE i.code_unique = ?
        AND i.statut != 'ANNULEE'
    ");
    $stmt->execute([$code]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur chargement invitation : ' . $e->getMessage());
    http_response_code(500);
    die('Erreur lors du chargement de l\'invitation.');
}

if (!$invitation) {
    http_response_code(404);
    die('Invitation invalide ou annulée.');
}

// ============================================================
// VÉROUILLAGE DES MODIFICATIONS
// ============================================================
$isLocked = false;
if (($invitation['reponse'] ?? '') === 'CONFIRMEE') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM preferences_invitation WHERE id_invitation = ?");
        $stmt->execute([$invitation['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (($result['c'] ?? 0) > 0) {
            $isLocked = true;
        }
    } catch (PDOException $e) {
        // Ignorer
    }
}

// ============================================================
// TRAITEMENT DES ACTIONS POST
// ============================================================
$message     = '';
$messageType = '';

if ($isLocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Les préférences ont déjà été enregistrées. Vous ne pouvez plus les modifier.';
    $messageType = 'warning';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- ACTION : CONFIRMER ----------
    if ($action === 'confirmer') {
        $reponse          = $_POST['reponse'] ?? '';
        $nombre_personnes = (int)($_POST['nombre_personnes'] ?? 1);
        $message_invite   = trim((string)($_POST['message_invite'] ?? ''));

        $max_personnes = (int)($invitation['nb_places_max'] ?? 1);

        if ($nombre_personnes > $max_personnes) {
            $message = 'Le nombre de personnes ne peut pas dépasser ' . $max_personnes . '.';
            $messageType = 'danger';
        } elseif (in_array($reponse, ['CONFIRMEE', 'REFUSEE'], true)) {
            try {
                // Enregistrer le message (si la colonne existe)
                if ($message_invite !== '') {
                    try {
                        $checkCol = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'message'");
                        if ($checkCol && $checkCol->rowCount() > 0) {
                            $stmt = $pdo->prepare("UPDATE invitations SET message = ? WHERE id = ?");
                            $stmt->execute([$message_invite, $invitation['id']]);
                        }
                    } catch (PDOException $e) {}
                }

                // Vérifier si une confirmation existe déjà
                $checkStmt = $pdo->prepare("SELECT id FROM confirmations WHERE id_invitation = ?");
                $checkStmt->execute([$invitation['id']]);
                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($exists) {
                    $stmt = $pdo->prepare("
                        UPDATE confirmations
                        SET reponse = ?, nombre_personnes = ?, commentaire = ?, date_confirmation = NOW()
                        WHERE id_invitation = ?
                    ");
                    $stmt->execute([$reponse, $nombre_personnes, $message_invite, $invitation['id']]);
                    $message = 'Votre réponse a été mise à jour avec succès !';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO confirmations (id_invitation, reponse, nombre_personnes, commentaire, date_confirmation)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$invitation['id'], $reponse, $nombre_personnes, $message_invite]);
                    $message = 'Votre réponse a été enregistrée avec succès !';
                }

                // Mettre à jour le statut de l'invitation
                $stmt = $pdo->prepare("UPDATE invitations SET statut = ?, date_confirmation = NOW() WHERE id = ?");
                $stmt->execute([$reponse, $invitation['id']]);

                $messageType = 'success';

                // Recharger l'invitation (avec les infos de table)
                $stmt = $pdo->prepare("
                    SELECT 
                        i.*,
                        inv.nom, inv.prenom, inv.email, inv.telephone,
                        inv.nombre_personnes AS nb_places_max,
                        e.nom AS evenement_nom,
                        e.type_evenement,
                        e.description AS evenement_description,
                        e.date_evenement,
                        e.heure_evenement,
                        e.lieu,
                        e.adresse AS evenement_adresse,
                        e.fond AS evenement_image,
                        e.modele_invitation,
                        c.reponse,
                        c.nombre_personnes AS nb_confirme,
                        c.commentaire,
                        c.date_confirmation,
                        t.nom AS table_nom,
                        t.numero AS table_numero,
                        t.zone AS table_zone
                    FROM invitations i
                    JOIN invites inv ON i.id_invite = inv.id
                    JOIN evenements e ON i.id_evenement = e.id
                    LEFT JOIN confirmations c ON i.id = c.id_invitation
                    LEFT JOIN invitations_tables it ON it.id_invitation = i.id
                    LEFT JOIN tables t ON t.id = it.id_table
                    WHERE i.id = ?
                ");
                $stmt->execute([$invitation['id']]);
                $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                error_log('Erreur confirmation : ' . $e->getMessage());
                $message = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = 'Veuillez choisir une option valide.';
            $messageType = 'danger';
        }
    }

    // ---------- ACTION : PRÉFÉRENCES BOISSONS ----------
    if ($action === 'preferences' && !$isLocked) {
        $boissons_choisies = $_POST['boissons'] ?? [];

        if (count($boissons_choisies) > 2) {
            $message = 'Vous ne pouvez sélectionner que 2 boissons maximum.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM preferences_invitation WHERE id_invitation = ?");
                $stmt->execute([$invitation['id']]);

                if (!empty($boissons_choisies)) {
                    $stmt = $pdo->prepare("INSERT INTO preferences_invitation (id_invitation, id_boisson, quantite) VALUES (?, ?, 1)");
                    foreach ($boissons_choisies as $boissonId) {
                        $stmt->execute([$invitation['id'], (int)$boissonId]);
                    }
                }

                $message = 'Vos préférences ont été enregistrées avec succès ! 🍹';
                $messageType = 'success';
                $isLocked = true;

            } catch (PDOException $e) {
                error_log('Erreur préférences : ' . $e->getMessage());
                $message = 'Erreur lors de l\'enregistrement des préférences.';
                $messageType = 'danger';
            }
        }
    }
}

// ============================================================
// CHARGEMENT DES PHOTOS HÔTES
// ============================================================
$photosHost = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, photo, titre, description
        FROM photo_host
        WHERE id_evenement = ? AND actif = 1
        ORDER BY ordre ASC, id ASC
    ");
    $stmt->execute([$invitation['id_evenement']]);
    $photosHost = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur photos host : ' . $e->getMessage());
}

// ============================================================
// PRÉFÉRENCES BOISSONS (pour affichage)
// ============================================================
$preferencesBoissons = [];
if (($invitation['reponse'] ?? '') === 'CONFIRMEE') {
    try {
        $stmt = $pdo->prepare("SELECT id_boisson, quantite FROM preferences_invitation WHERE id_invitation = ?");
        $stmt->execute([$invitation['id']]);
        $preferencesBoissons = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } catch (PDOException $e) {}
}

// ============================================================
// BOISSONS DISPONIBLES (groupées par type)
// ============================================================
$boissons        = [];
$boissonsGrouped = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.nom, b.description, b.type, eb.choix_multiple
        FROM evenement_boissons eb
        JOIN boissons b ON eb.id_boisson = b.id
        WHERE eb.id_evenement = ? AND eb.actif = 1
        ORDER BY b.type ASC, b.nom ASC
    ");
    $stmt->execute([$invitation['id_evenement']]);
    $boissons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($boissons as $b) {
        $type = $b['type'] ?? 'Autres';
        if (!isset($boissonsGrouped[$type])) {
            $boissonsGrouped[$type] = [];
        }
        $boissonsGrouped[$type][] = $b;
    }
} catch (PDOException $e) {
    error_log('Erreur boissons : ' . $e->getMessage());
}

// ============================================================
// VARIABLES PRÉPARÉES POUR LES TEMPLATES
// ============================================================

// Nom de l'hôte (on enlève le "& XXX" si présent)
$hostName = $invitation['evenement_nom'] ?? APP_NAME;
$hostName = preg_replace('/\s*&\s*.*$/', '', $hostName);
$hostName = preg_replace('/\s*&amp;\s*.*$/', '', $hostName);
$host1    = trim($hostName);

// Type d'événement
$typesLabels = [
    'mariage_religieux' => 'Mariage Religieux',
    'mariage_civil'     => 'Mariage Civil',
    'mariage_coutumier' => 'Mariage Coutumier',
    'defile_mode'       => 'Défilé de Mode',
    'concert'           => 'Concert',
    'anniversaire'      => 'Anniversaire',
    'bapteme'           => 'Baptême',
    'communion'         => 'Communion',
    'soiree'            => 'Soirée',
    'conference'        => 'Conférence',
    'autre'             => 'Événement',
];

$eventTypeRaw   = $invitation['type_evenement'] ?? 'autre';
$eventType      = $typesLabels[$eventTypeRaw] ?? ucfirst(str_replace('_', ' ', $eventTypeRaw));
$eventTypeLower = strtolower($eventType);

// Nom de l'invité
$guestName = trim(($invitation['prenom'] ?? '') . ' ' . ($invitation['nom'] ?? ''));

// Date et heure
$eventDate = !empty($invitation['date_evenement']) 
    ? date('d/m/Y', strtotime($invitation['date_evenement'])) 
    : '';
$eventTime = !empty($invitation['heure_evenement']) 
    ? date('H:i', strtotime($invitation['heure_evenement'])) 
    : '';

// Lieu / Adresse
$lieuDisplay    = $invitation['lieu'] ?? '';
$adresseDisplay = $invitation['evenement_adresse'] ?? '';
$eventLieu      = trim($lieuDisplay);
$eventAddress   = trim($lieuDisplay . ' — ' . $adresseDisplay, ' —');

// Nom de l'application
$appName = defined('APP_NAME') ? APP_NAME : 'Planning Events';

// URL complète pour le QR Code
$fullUrl = getFullUrl();

// Image de fond de la page
$eventImageRaw  = $invitation['evenement_image'] ?? '';
$pageBackground = !empty($eventImageRaw) ? getFondUrl($eventImageRaw) : '';

// Code unique (utilisé dans les formulaires)
$code = $invitation['code_unique'];

// ============================================================
// INFORMATIONS DE LA TABLE (si assignée)
// ============================================================
$tableNom     = $invitation['table_nom']    ?? null;
$tableNumero  = $invitation['table_numero'] ?? null;
$tableZone    = $invitation['table_zone']   ?? null;
$hasTable     = !empty($tableNom) || !empty($tableNumero);

// ============================================================
// STATUTS ET LABELS
// ============================================================
$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE'  => '✅ Confirmée',
    'REFUSEE'    => '❌ Refusée',
    'PRESENTE'   => '✅ Présente',
    'ANNULEE'    => 'Annulée'
];

$statutColors = [
    'EN_ATTENTE' => 'secondary',
    'CONFIRMEE'  => 'success',
    'REFUSEE'    => 'danger',
    'PRESENTE'   => 'info',
    'ANNULEE'    => 'dark'
];

// ============================================================
// CHOISIR LE TEMPLATE À CHARGER
// ============================================================
$modeleCode = $invitation['modele_invitation'] ?? 'classique';

// Sécurité : n'autoriser que a-z, A-Z, 0-9, tirets et underscores
if (!preg_match('/^[a-z0-9_-]+$/i', $modeleCode)) {
    $modeleCode = 'classique';
}

// Chemin du template
$templatePath = __DIR__ . '/templates/invitations/' . $modeleCode . '.php';

// Fallback si introuvable
if (!file_exists($templatePath)) {
    error_log("Modèle d'invitation introuvable : {$modeleCode}.php → fallback sur classique");
    $modeleCode   = 'classique';
    $templatePath = __DIR__ . '/templates/invitations/classique.php';
    if (!file_exists($templatePath)) {
        http_response_code(500);
        die('Aucun modèle d\'invitation disponible. Vérifiez le dossier templates/invitations/.');
    }
}

// ============================================================
// INCLURE LE TEMPLATE
// ============================================================
// À ce stade, TOUTES les variables sont disponibles :
//   $invitation, $guestName, $host1, $eventDate, $eventTime,
//   $lieuDisplay, $adresseDisplay, $eventType, $eventDescription,
//   $photosHost, $boissons, $boissonsGrouped, $preferencesBoissons,
//   $isLocked, $message, $messageType, $code, $fullUrl,
//   $pageBackground, $appName, $statutLabels, $statutColors,
//   $tableNom, $tableNumero, $tableZone, $hasTable, ...
//
// Le template n'a plus qu'à afficher le HTML.

include $templatePath;