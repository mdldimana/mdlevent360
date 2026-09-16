<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invites.supprimer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'invité
$invite = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM invites WHERE id = ?");
    $stmt->execute([$id]);
    $invite = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$invite) {
    header('Location: index.php');
    exit;
}

// Vérifier s'il y a des invitations liées
$nbInvitations = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invitations WHERE id_invite = ?");
    $stmt->execute([$id]);
    $nbInvitations = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    // Ignorer
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Supprimer l'invité (les invitations seront supprimées en cascade)
        $stmt = $pdo->prepare("DELETE FROM invites WHERE id = ?");
        $stmt->execute([$id]);

        // Journaliser
        logAction($user['id'], 'DELETE_GUEST', 'invites', 
                  "Suppression de l'invité '{$invite['prenom']} {$invite['nom']}' (ID: $id)");

        header('Location: index.php?success=supprime');
        exit;

    } catch (PDOException $e) {
        $error = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }
        .modal-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-card .icon { font-size: 60px; margin-bottom: 20px; color: #ff4757; }
        .modal-card h4 { font-weight: 700; color: #1a1a2e; }
        .modal-card p { color: #666; }
        .modal-card .guest-name {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 18px;
        }
        .modal-card .warning-text {
            background: #fff3cd;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            color: #856404;
            margin: 15px 0;
        }
        .modal-card .btn-confirm {
            background: #ff4757;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .modal-card .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 71, 87, 0.3);
        }
        .modal-card .btn-cancel {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
        }
        .modal-card .btn-cancel:hover { background: #e9ecef; color: #333; }
    </style>
</head>
<body>

<div class="modal-card">
    <div class="icon">
        <i class="bi bi-person-x"></i>
    </div>
    <h4>Supprimer l'invité</h4>
    <p>
        Voulez-vous vraiment supprimer 
        <span class="guest-name"><?php echo htmlspecialchars($invite['prenom'] . ' ' . $invite['nom']); ?></span> ?
    </p>

    <?php if ($nbInvitations > 0): ?>
        <div class="warning-text">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Cet invité a <strong><?php echo $nbInvitations; ?> invitation(s)</strong>.
            Elles seront également supprimées.
        </div>
    <?php endif; ?>

    <p class="text-muted small">Cette action est irréversible.</p>

    <form method="POST" action="">
        <div class="d-flex gap-3 justify-content-center mt-4">
            <button type="submit" class="btn btn-confirm">
                <i class="bi bi-trash"></i> Supprimer
            </button>
            <a href="index.php" class="btn btn-cancel">
                <i class="bi bi-x"></i> Annuler
            </a>
        </div>
    </form>
</div>

</body>
</html>