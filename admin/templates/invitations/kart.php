<?php
/**
 * Template : KART
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom'] ?? ''); ?></title></head>
<body>
<h1>Modèle <?php echo htmlspecialchars('KARTING'); ?></h1>
<p>Invité : <?php echo htmlspecialchars($guestName ?? ''); ?></p>
</body>
</html>
