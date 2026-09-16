<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('roles.modifier');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer le rôle
$role = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, description, actif FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$role) {
    header('Location: index.php');
    exit;
}

// Ne pas permettre de modifier SUPER_ADMIN
if ($role['nom'] == 'SUPER_ADMIN') {
    header('Location: index.php?error=super_admin');
    exit;
}

$error = '';
$nom = $role['nom'];
$description = $role['description'];
$actif = $role['actif'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];

    if (empty($nom)) $errors[] = 'Le nom du rôle est requis';
    if (strlen($nom) < 2) $errors[] = 'Le nom doit contenir au moins 2 caractères';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE nom = ? AND id != ?");
            $stmt->execute([strtoupper($nom), $id]);
            if ($stmt->fetch()) {
                $errors[] = "Un rôle avec ce nom existe déjà";
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la vérification';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE roles 
                SET nom = ?, description = ?, actif = ? 
                WHERE id = ?
            ");
            $stmt->execute([strtoupper($nom), $description, $actif, $id]);

            logAction($user['id'], 'UPDATE_ROLE', 'roles', 
                      "Modification du rôle {$role['nom']} vers $nom (ID: $id)");

            header('Location: index.php?success=modifie');
            exit;

        } catch (PDOException $e) {
            $error = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .main-content {
            height: 100vh;
            overflow-y: auto;
            padding: 0;
        }
        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 10px;
        }

        /* ========== TOP BAR ========== */
        .top-bar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 15px 30px;
            border-bottom: 1px solid rgba(193, 124, 96, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-bar .page-title h4 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 20px;
        }
        .top-bar .page-title h4 i { color: #c17c60; margin-right: 10px; }
        .top-bar .page-title small { color: #9a8a7f; font-size: 13px; display: block; margin-top: 2px; }
        .top-bar .user-info { display: flex; align-items: center; gap: 20px; }
        .top-bar .user-info .user-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 18px;
            box-shadow: 0 5px 15px rgba(193, 124, 96, 0.3);
        }
        .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 14px; }
        .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 12px; }
        .top-bar .user-info .role-badge {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; padding: 5px 15px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }

        .content-section { padding: 25px 30px; }

        /* ========== FORM CONTAINER ========== */
        .form-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 700px;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            font-size: 18px;
        }
        .form-container .form-title i { color: #c17c60; margin-right: 10px; }

        /* ========== FORMULAIRES ========== */
        .form-label { 
            font-weight: 600; 
            color: #6a5a4a; 
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-label i {
            color: #c17c60;
            margin-right: 6px;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.8);
            color: #1a1a1a;
        }
        .form-control:focus {
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
            background: white;
        }
        .form-text, small.text-muted {
            font-size: 12px;
            color: #9a8a7f !important;
        }

        /* ========== CHECKBOX ========== */
        .form-check-input:checked {
            background-color: #c17c60;
            border-color: #c17c60;
        }
        .form-check-input:focus {
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.15);
        }
        .form-check-label {
            font-size: 13px;
            color: #6a5a4a;
            font-weight: 500;
        }

        /* ========== BOUTONS ========== */
        .btn-save {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .btn-cancel {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover { 
            background: rgba(255, 255, 255, 0.95); 
            color: #c17c60; 
        }

        /* ========== ALERT ========== */
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .alert-error i { 
            color: #dc2626; 
            font-size: 18px; 
            flex-shrink: 0;
        }

        /* ========== ANIMATIONS ========== */
        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #b8a99c;
            font-size: 13px;
        }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            html, body { overflow: visible; }
            .main-content { height: auto; }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px;
            }
            .top-bar .user-info { width: 100%; justify-content: space-between; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 15px 20px; }
            .form-container { padding: 20px; }
        }
        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; }
            .top-bar .page-title h4 { font-size: 18px; }
            .content-section { padding: 10px 15px; }
            .form-container { padding: 15px; }
            .btn-save, .btn-cancel { 
                width: 100%; 
                justify-content: center; 
            }
            .d-flex.gap-3 { 
                flex-direction: column; 
                gap: 10px !important; 
            }
        }
    </style>
</head>
<body>

<div class="container-fluid" style="padding: 0; height: 100vh; overflow: hidden;">
    <div class="row" style="height: 100%; margin: 0;">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2" style="padding: 0; height: 100%;">
            <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div class="col-md-9 col-lg-10 main-content" style="padding: 0;">

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="page-title">
                    <h4><i class="bi bi-pencil"></i> Modifier le rôle</h4>
                    <small><i class="bi bi-shield"></i> Modifier les informations du rôle</small>
                </div>
                <div class="user-info">
                    <span class="role-badge">
                        <i class="bi bi-shield-check"></i>
                        <?php 
                        $roles_user = $user['roles'] ?? [];
                        echo is_array($roles_user) ? implode(', ', $roles_user) : 'Aucun rôle';
                        ?>
                    </span>
                    <div>
                        <div class="user-name">
                            <?php echo htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')); ?>
                            <small>@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small>
                        </div>
                    </div>
                    <div class="user-avatar">
                        <?php 
                        $initiales = strtoupper(
                            substr($user['prenom'] ?? 'U', 0, 1) . 
                            substr($user['nom'] ?? 'N', 0, 1)
                        );
                        echo $initiales ?: 'U';
                        ?>
                    </div>
                </div>
            </div>

            <!-- CONTENU -->
            <div class="content-section">
                <div class="form-container fade-in">

                    <h5 class="form-title">
                        <i class="bi bi-pencil"></i> 
                        Modifier - <?php echo htmlspecialchars($role['nom']); ?>
                    </h5>

                    <?php if ($error): ?>
                        <div class="alert-error">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div><?php echo $error; ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-tag"></i> Nom du rôle <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>" 
                                   placeholder="Ex: MODERATEUR" required>
                            <small class="text-muted">Le nom sera automatiquement converti en majuscules</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-info-circle"></i> Description</label>
                            <textarea class="form-control" name="description" rows="3" 
                                      placeholder="Description du rôle"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" <?php echo $actif ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="actif">
                                    <i class="bi bi-check-circle" style="color: #c17c60;"></i> Actif
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-4 flex-wrap">
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="index.php" class="btn btn-cancel">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="app-footer">
                    <i class="bi bi-heart-fill"></i>
                    <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>