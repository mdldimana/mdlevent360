<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';

// ========== FIX INFINITYFREE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

requirePermission('presences.annuler');

$user = getCurrentUser();
$userId = (int)($user['id'] ?? 0);
$pdo = getDbConnection();
$isUserAdmin = function_exists('isAdmin') ? isAdmin() : true;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$presence = null;
$error = '';

try {
    $stmt = $pdo->prepare("
        SELECT p.*, i.code_unique, i.id as invitation_id, i.id_evenement, i.nb_presents, i.statut,
               inv.nom, inv.prenom, inv.nombre_personnes as nb_places,
               e.nom as evenement_nom
        FROM presences p
        JOIN invitations i ON p.id_invitation = i.id
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $presence = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Load presence annuler: '.$e->getMessage());
}

if (!$presence) { header('Location: index.php'); exit; }

// PROTECTION: Non-admin ne peut annuler que ses événements
if (!$isUserAdmin) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM evenements_utilisateurs WHERE id_utilisateur=? AND id_evenement=? LIMIT 1");
        $stmt->execute([$userId, $presence['id_evenement']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo '<div style="padding:40px;font-family:Inter,sans-serif;background:#fdf0ed;min-height:100vh;">
            <h2 style="color:#991b1b">🚫 Accès refusé</h2>
            <p>Vous n\'êtes pas associé à l\'événement "'.htmlspecialchars($presence['evenement_nom']).'"</p>
            <p><a href="index.php" style="background:#c17c60;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">Retour</a></p></div>';
            exit;
        }
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Supprimer la présence
        $stmt = $pdo->prepare("DELETE FROM presences WHERE id = ?");
        $stmt->execute([$id]);

        // 2. Recalculer le vrai total restant (SUM et non 0)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(nombre_present),0) as total FROM presences WHERE id_invitation = ?");
        $stmt->execute([$presence['invitation_id']]);
        $nouveau_total = (int)($stmt->fetch()['total'] ?? 0);

        // 3. Recalculer statut intelligemment
        $nb_places = (int)($presence['nb_places'] ?? 1);
        if ($nouveau_total <= 0) {
            // Regarder si confirmation existe
            try {
                $stmt = $pdo->prepare("SELECT reponse FROM confirmations WHERE id_invitation = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$presence['invitation_id']]);
                $conf = $stmt->fetch();
                $nouveau_statut = $conf ? $conf['reponse'] : 'EN_ATTENTE';
                if (!in_array($nouveau_statut, ['CONFIRMEE','EN_ATTENTE','REFUSEE'], true)) $nouveau_statut = 'CONFIRMEE';
            } catch (PDOException $e) {
                $nouveau_statut = 'CONFIRMEE';
            }
        } else {
            $nouveau_statut = ($nouveau_total >= $nb_places) ? 'PRESENTE' : 'PARTIELLE';
        }

        $stmt = $pdo->prepare("UPDATE invitations SET nb_presents = ?, statut = ? WHERE id = ?");
        $stmt->execute([$nouveau_total, $nouveau_statut, $presence['invitation_id']]);

        $pdo->commit();

        if (function_exists('logAction')) {
            logAction($userId, 'CANCEL_CHECK_IN', 'presences', "Annulation présence {$presence['code_unique']} - reste {$nouveau_total}/{$nb_places}");
        }

        header('Location: index.php?success=annulee');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Erreur lors de l\'annulation : ' . $e->getMessage();
        error_log('Annuler presence: '.$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Annuler présence - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:#fcfaf8;color:#1a1a1a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-card{background:#ffffff;border:1px solid #f0ebe5;border-radius:20px;padding:36px 32px;max-width:480px;width:100%;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,0.08)}
.modal-card .icon{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:32px;color:#92400e;border:1px solid #fde68a}
.modal-card h4{font-weight:800;color:#1a1a1a;font-size:20px;margin-bottom:10px}
.modal-card p{color:#6a5a4a;font-size:13px;line-height:1.6}
.modal-card .guest-name{font-weight:800;color:#1a1a1a;font-size:16px;display:block;margin:8px 0}
.modal-card .code-badge{display:inline-block;background:#fcfaf8;border:1px solid #f0ebe5;color:#c17c60;padding:4px 10px;border-radius:8px;font-size:11px;font-family:monospace;margin-top:6px}
.modal-card .info-box{background:#fcfaf8;border:1px solid #f0ebe5;border-radius:12px;padding:12px 14px;margin:16px 0;text-align:left;font-size:12px}
.modal-card .info-box .row-line{display:flex;justify-content:space-between;padding:4px 0}
.modal-card .btn-confirm{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border:none;padding:11px 24px;border-radius:10px;font-weight:700;font-size:13px;transition:all 0.2s}
.modal-card .btn-confirm:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(193,124,96,0.3);color:white}
.modal-card .btn-cancel{background:white;border:1.5px solid #e5ddd3;color:#6a5a4a;padding:11px 24px;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s}
.modal-card .btn-cancel:hover{background:#fcfaf8;border-color:#c17c60;color:#c17c60}
.error-box{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;display:flex;align-items:center;gap:8px}
</style>
</head>
<body>

<div class="modal-card">
    <div class="icon"><i class="bi bi-person-x"></i></div>
    <h4>Annuler la présence ?</h4>

    <?php if ($error): ?>
        <div class="error-box"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <p>Voulez-vous vraiment annuler l'entrée de</p>
    <span class="guest-name"><?php echo htmlspecialchars($presence['prenom'].' '.$presence['nom']); ?></span>
    <span class="code-badge"><?php echo htmlspecialchars($presence['code_unique']); ?> • <?php echo htmlspecialchars($presence['evenement_nom']); ?></span>

    <div class="info-box">
        <div class="row-line"><span style="color:#9a8a7f">Entrée le</span><strong><?php echo date('d/m/Y à H:i', strtotime($presence['date_entree'].' '.$presence['heure_entree'])); ?></strong></div>
        <div class="row-line"><span style="color:#9a8a7f">Présent(s)</span><strong><?php echo (int)$presence['nombre_present']; ?> personne(s)</strong></div>
        <div class="row-line"><span style="color:#9a8a7f">Total événement</span><strong><?php echo (int)$presence['nb_presents']; ?>/<?php echo (int)$presence['nb_places']; ?></strong></div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #f0ebe5;font-size:11px;color:#c17c60;display:flex;gap:6px;align-items:center;"><i class="bi bi-info-circle-fill"></i> </div>
    </div>

    <form method="POST" action="">
        <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
            <button type="submit" class="btn-confirm"><i class="bi bi-check-lg"></i> Oui, annuler</button>
            <a href="index.php" class="btn-cancel"><i class="bi bi-x-lg"></i> Retour</a>
        </div>
    </form>
</div>

</body>
</html>
