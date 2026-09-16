<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invitations.supprimer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'invitation
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT i.*, inv.nom, inv.prenom, e.nom as evenement_nom
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invitation = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$invitation) {
    header('Location: index.php');
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'delete';
    
    try {
        if ($action === 'delete') {
            // Suppression définitive
            $stmt = $pdo->prepare("DELETE FROM invitations WHERE id = ?");
            $stmt->execute([$id]);
            
            logAction($user['id'], 'DELETE_INVITATION', 'invitations', 
                      "Suppression de l'invitation {$invitation['code_unique']} (ID: $id)");
            
            header('Location: index.php?success=supprime');
        } else {
            // Annulation (changement de statut)
            $stmt = $pdo->prepare("UPDATE invitations SET statut = 'ANNULEE' WHERE id = ?");
            $stmt->execute([$id]);
            
            logAction($user['id'], 'CANCEL_INVITATION', 'invitations', 
                      "Annulation de l'invitation {$invitation['code_unique']} (ID: $id)");
            
            header('Location: index.php?success=annule');
        }
        exit;
    } catch (PDOException $e) {
        $error = 'Erreur lors de l\'opération';
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
        .modal-card .invite-name {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 18px;
        }
        .modal-card .code {
            font-family: monospace;
            color: #f7971e;
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 6px;
        }
        .modal-card .actions-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .btn-delete {
            background: #ff4757;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 71, 87, 0.3);
        }
        .btn-cancel-invite {
            background: #f7971e;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-cancel-invite:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
        }
        .btn-back {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-back:hover { background: #e9ecef; color: #333; }
    </style>
</head>
<body>

<div class="modal-card">
    <div class="icon">
        <i class="bi bi-envelope-x"></i>
    </div>
    <h4>Gérer l'invitation</h4>
    <p>
        Que souhaitez-vous faire pour 
        <span class="invite-name"><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></span> ?
    </p>
    <p class="text-muted small">
        Événement : <?php echo htmlspecialchars($invitation['evenement_nom']); ?>
        <br>
        Code : <span class="code"><?php echo htmlspecialchars($invitation['code_unique']); ?></span>
    </p>

    <form method="POST" action="">
        <div class="actions-btns">
            <button type="submit" name="action" value="cancel" class="btn-cancel-invite">
                <i class="bi bi-x-circle"></i> Annuler
            </button>
            <button type="submit" name="action" value="delete" class="btn-delete" 
                    onclick="return confirm('Suppression définitive. Continuer ?')">
                <i class="bi bi-trash"></i> Supprimer
            </button>
            <a href="index.php" class="btn-back">
                <i class="bi bi-x"></i> Retour
            </a>
        </div>
    </form>
</div>

</body>
</html>