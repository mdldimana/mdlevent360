<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('presences.enregistrer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: controle.php');
    exit;
}

// Vérifier l'invitation
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.code_unique,
            i.statut,
            i.nb_presents,
            inv.nom,
            inv.prenom,
            inv.nombre_personnes as nb_places,
            e.nom as evenement_nom,
            p.id as presence_id
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        LEFT JOIN presences p ON i.id = p.id_invitation
        WHERE i.code_unique = ?
    ");
    $stmt->execute([$code]);
    $invitation = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$invitation) {
    header('Location: controle.php?error=invalide');
    exit;
}

// Vérifier si toutes les places sont déjà prises
if ($invitation['nb_presents'] >= $invitation['nb_places']) {
    header('Location: controle.php?error=complet');
    exit;
}

if ($invitation['statut'] == 'ANNULEE' || $invitation['statut'] == 'REFUSEE') {
    header('Location: controle.php?error=invalide');
    exit;
}

// Enregistrer la présence
try {
    $nombre_present = 1; // Une personne à la fois
    $nouveau_total = $invitation['nb_presents'] + $nombre_present;
    $nouveau_statut = ($nouveau_total >= $invitation['nb_places']) ? 'PRESENTE' : 'PARTIELLE';
    
    // Démarrer la transaction
    $pdo->beginTransaction();
    
    // Enregistrer la présence
    $stmt = $pdo->prepare("
        INSERT INTO presences (id_invitation, nombre_present, date_entree, heure_entree, utilisateur_id, created_at)
        VALUES (?, ?, CURDATE(), CURTIME(), ?, NOW())
    ");
    $stmt->execute([$invitation['id'], $nombre_present, $user['id']]);
    
    // Mettre à jour le compteur et le statut de l'invitation
    $stmt = $pdo->prepare("
        UPDATE invitations 
        SET nb_presents = ?, statut = ? 
        WHERE id = ?
    ");
    $stmt->execute([$nouveau_total, $nouveau_statut, $invitation['id']]);
    
    $pdo->commit();
    
    // Journaliser
    logAction($user['id'], 'CHECK_IN', 'presences', 
              "Enregistrement de la présence pour {$invitation['code_unique']} ({$nouveau_total}/{$invitation['nb_places']})");
    
    // Rediriger avec les infos
    header('Location: controle.php?success=enregistree&code=' . $code);
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: controle.php?error=erreur');
    exit;
}
?>