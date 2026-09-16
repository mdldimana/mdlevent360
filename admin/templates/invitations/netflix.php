<?php
/**
 * Template : NETFLIX
 * À personnaliser
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
</head>
<body>
    <h1>Modèle <?php echo htmlspecialchars('Netflix'); ?></h1>
    <p>Invité : <?php echo htmlspecialchars($guestName); ?></p>
    <p>Événement : <?php echo htmlspecialchars($invitation['evenement_nom']); ?></p>
    <p>Date : <?php echo htmlspecialchars($eventDate); ?></p>
    <p>Lieu : <?php echo htmlspecialchars($lieuDisplay); ?></p>
</body>
</html>
