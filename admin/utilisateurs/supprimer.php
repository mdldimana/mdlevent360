<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('utilisateurs.desactiver');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'utilisateur
$utilisateur = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom, username, actif FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $utilisateur = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$utilisateur) {
    header('Location: index.php');
    exit;
}

// Ne pas permettre de désactiver soi-même
if ($id == $user['id']) {
    header('Location: index.php?error=impossible');
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actif = isset($_POST['actif']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET actif = ? WHERE id = ?");
        $stmt->execute([$actif, $id]);

        $actionLabel = $actif ? 'activé' : 'désactivé';
        logAction($user['id'], 'TOGGLE_USER', 'utilisateurs', 
                  "Utilisateur {$utilisateur['username']} $actionLabel (ID: $id)");

        header('Location: index.php?success=' . ($actif ? 'active' : 'desactive'));
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
    <title><?php echo $utilisateur['actif'] ? 'Désactiver' : 'Activer'; ?> - <?php echo APP_NAME; ?></title>
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
        .modal-card .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .modal-card .icon.warning { color: #f7971e; }
        .modal-card .icon.danger { color: #ff4757; }
        .modal-card .icon.success { color: #38ef7d; }
        .modal-card h4 { font-weight: 700; color: #1a1a2e; }
        .modal-card p { color: #666; }
        .modal-card .user-name {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 18px;
        }
        .modal-card .btn-confirm {
            background: <?php echo $utilisateur['actif'] ? '#ff4757' : '#38ef7d'; ?>;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .modal-card .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
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
        .modal-card .btn-cancel:hover {
            background: #e9ecef;
            color: #333;
        }
    </style>
</head>
<body>

<div class="modal-card">
    <div class="icon <?php echo $utilisateur['actif'] ? 'danger' : 'success'; ?>">
        <i class="bi <?php echo $utilisateur['actif'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
    </div>
    <h4><?php echo $utilisateur['actif'] ? 'Désactiver' : 'Activer'; ?> l'utilisateur</h4>
    <p>
        Voulez-vous vraiment <?php echo $utilisateur['actif'] ? 'désactiver' : 'activer'; ?> 
        <span class="user-name"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></span> ?
    </p>
    <p class="text-muted small">
        <?php echo $utilisateur['actif'] 
            ? 'L\'utilisateur ne pourra plus se connecter.' 
            : 'L\'utilisateur pourra à nouveau se connecter.'; ?>
    </p>

    <form method="POST" action="">
        <input type="hidden" name="actif" value="<?php echo $utilisateur['actif'] ? 0 : 1; ?>">
        <div class="d-flex gap-3 justify-content-center mt-4">
            <button type="submit" class="btn btn-confirm">
                <i class="bi <?php echo $utilisateur['actif'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                <?php echo $utilisateur['actif'] ? 'Désactiver' : 'Activer'; ?>
            </button>
            <a href="index.php" class="btn btn-cancel">
                <i class="bi bi-x"></i> Annuler
            </a>
        </div>
    </form>
</div>

</body>
</html>