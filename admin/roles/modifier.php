<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('utilisateurs.modifier', 'index.php');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'utilisateur
$utilisateur = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom, username, email, actif FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $utilisateur = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$utilisateur) {
    header('Location: index.php');
    exit;
}

// Récupérer le rôle actuel
$currentRole = null;
try {
    $stmt = $pdo->prepare("SELECT role_id FROM utilisateur_roles WHERE utilisateur_id = ?");
    $stmt->execute([$id]);
    $currentRole = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignorer
}

// Récupérer les rôles
$roles = [];
try {
    $stmt = $pdo->query("SELECT id, nom, description FROM roles WHERE actif = 1 ORDER BY nom");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignorer
}

$error = '';
$nom = $utilisateur['nom'];
$prenom = $utilisateur['prenom'];
$email = $utilisateur['email'];
$actif = $utilisateur['actif'];
$selectedRole = $currentRole;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $selectedRole = $_POST['role'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    $errors = [];

    if (empty($nom)) $errors[] = 'Le nom est requis';
    if (empty($prenom)) $errors[] = 'Le prénom est requis';
    if (empty($email)) $errors[] = "L'email est requis";
    if (empty($selectedRole)) $errors[] = 'Veuillez sélectionner un rôle';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";

    // Vérifier le mot de passe si modifié
    if (!empty($password)) {
        if ($password !== $password_confirm) $errors[] = 'Les mots de passe ne correspondent pas';
        if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    // Vérifier l'unicité (email)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé par un autre utilisateur";
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la vérification';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Mise à jour de l'utilisateur
            if (!empty($password)) {
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET nom = ?, prenom = ?, email = ?, mot_de_passe = ?, actif = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $prenom, $email, $hashedPassword, $actif, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET nom = ?, prenom = ?, email = ?, actif = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $prenom, $email, $actif, $id]);
            }

            // Mise à jour du rôle
            $stmt = $pdo->prepare("DELETE FROM utilisateur_roles WHERE utilisateur_id = ?");
            $stmt->execute([$id]);
            
            if (!empty($selectedRole)) {
                $stmt = $pdo->prepare("INSERT INTO utilisateur_roles (utilisateur_id, role_id) VALUES (?, ?)");
                $stmt->execute([$id, $selectedRole]);
            }

            $pdo->commit();

            // Journaliser
            logAction($user['id'], 'UPDATE_USER', 'utilisateurs', "Modification de l'utilisateur {$utilisateur['username']} (ID: $id)");

            header('Location: index.php?success=modifie');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
        }

        /* ========== LAYOUT PRINCIPAL ========== */
        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ========== SIDEBAR ========== */
        .sidebar-wrapper {
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .sidebar-wrapper .sidebar {
            width: 260px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            padding: 20px 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: white;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            font-size: 22px;
            color: #1a1a2e;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-toggle-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(247, 151, 30, 0.2);
        }

        .sidebar-toggle-btn i {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            padding: 0;
            min-width: 0;
        }

        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f9fa; }
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            border-radius: 10px;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 15px 30px;
            border-bottom: 2px solid rgba(247, 151, 30, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-bar .page-title h4 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .top-bar .page-title h4 i { color: #f7971e; margin-right: 10px; }
        .top-bar .page-title small { color: #999; font-size: 13px; display: block; margin-top: 2px; }
        .top-bar .user-info { display: flex; align-items: center; gap: 20px; }
        .top-bar .user-info .user-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 18px;
            box-shadow: 0 5px 15px rgba(247, 151, 30, 0.3);
        }
        .top-bar .user-info .user-name { font-weight: 600; color: #1a1a2e; font-size: 14px; }
        .top-bar .user-info .user-name small { display: block; color: #aaa; font-weight: 400; font-size: 12px; }
        .top-bar .user-info .role-badge {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e; padding: 5px 15px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }

        .content-section { padding: 25px 30px; }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            max-width: 800px;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .form-container .form-title i { color: #f7971e; margin-right: 10px; }

        .form-label { font-weight: 600; color: #555; font-size: 14px; }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 2px solid #e1e5ee;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #f7971e;
            box-shadow: 0 0 0 3px rgba(247, 151, 30, 0.1);
        }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .btn-save {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border: none;
            font-weight: 700;
            padding: 10px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
        }
        .btn-cancel {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover {
            background: #e9ecef;
            color: #333;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .role-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 2px solid #e1e5ee;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }
        .role-option:hover {
            border-color: #f7971e;
            background: rgba(247, 151, 30, 0.05);
        }
        .role-option input[type="radio"] {
            accent-color: #f7971e;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        .role-option .role-option-content { flex: 1; }
        .role-option .role-option-content .role-name { font-weight: 600; color: #1a1a2e; }
        .role-option .role-option-content .role-desc { font-size: 12px; color: #999; }

        /* ========== ANIMATION D'ENTRÉE - CORRIGÉE ========== */
        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #ccc;
            font-size: 13px;
        }

        .app-footer i.bi-heart-fill {
            color: #ff6b6b;
        }

        /* ========== SUPPRESSION DES ANIMATIONS POUR LES UTILISATEURS QUI PRÉFÈRENT RÉDUIRE LES MOUVEMENTS ========== */
        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            html, body { overflow: visible; }
            
            .app-container {
                height: auto;
                min-height: 100vh;
            }

            .sidebar-wrapper {
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                flex-shrink: 0;
            }

            .sidebar-wrapper.open {
                transform: translateX(0);
            }

            .sidebar-wrapper .sidebar {
                height: 100vh;
                box-shadow: 5px 0 30px rgba(0, 0, 0, 0.15);
            }

            .sidebar-toggle-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .main-content {
                height: auto;
                min-height: 100vh;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px;
                padding-left: 75px;
            }

            .top-bar .user-info {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .top-bar .user-info .user-name {
                display: none;
            }

            .content-section { padding: 15px 20px; }

            .form-container { 
                padding: 20px;
                max-width: 100%;
            }

            .role-option {
                padding: 10px 14px;
                font-size: 13px;
            }

            .btn-save, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
            .d-flex.gap-3 {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .top-bar { 
                padding: 12px 15px;
                padding-left: 65px;
            }
            .top-bar .page-title h4 { font-size: 18px; }
            .top-bar .page-title small { font-size: 11px; }
            .top-bar .user-info .role-badge { 
                font-size: 10px; 
                padding: 3px 10px;
            }
            .top-bar .user-info .user-avatar {
                width: 38px;
                height: 38px;
                font-size: 15px;
            }
            .content-section { padding: 10px 15px; }

            .sidebar-wrapper .sidebar {
                width: 280px;
            }

            .sidebar-toggle-btn {
                top: 12px;
                left: 12px;
                padding: 8px 12px;
                font-size: 18px;
            }

            .form-container { 
                padding: 15px;
                border-radius: 14px;
            }
            .form-container .form-title { 
                font-size: 18px;
                margin-bottom: 18px;
            }

            .form-label {
                font-size: 13px;
            }
            .form-control, .form-select {
                font-size: 13px;
                padding: 8px 12px;
            }

            .btn-save, .btn-cancel {
                font-size: 14px;
                padding: 10px 20px;
            }

            .role-option {
                padding: 8px 12px;
                font-size: 12px;
                gap: 8px;
            }
            .role-option input[type="radio"] {
                width: 16px;
                height: 16px;
            }
            .role-option .role-option-content .role-name {
                font-size: 13px;
            }
            .role-option .role-option-content .role-desc {
                font-size: 11px;
            }

            .alert-error {
                font-size: 13px;
                padding: 12px 15px;
            }

            .app-footer {
                font-size: 11px;
                padding: 20px 0 15px;
            }
        }

        @media (max-width: 400px) {
            .form-container .form-title {
                font-size: 16px;
            }
            .btn-save, .btn-cancel {
                font-size: 13px;
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>

<!-- ========== BOUTON TOGGLE SIDEBAR (MOBILE) ========== -->
<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
</button>

<!-- ========== OVERLAY SIDEBAR (MOBILE) ========== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ========== CONTENEUR PRINCIPAL ========== -->
<div class="app-container">

    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar-wrapper" id="sidebarWrapper">
        <div class="sidebar">
            <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>
    </div>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <div class="main-content" id="mainContent">

        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-pencil"></i> Modifier l'utilisateur</h4>
                <small><i class="bi bi-person"></i> Modifier les informations du compte</small>
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

                <h5 class="form-title"><i class="bi bi-pencil"></i> Modifier - <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h5>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" placeholder="Prénom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>" placeholder="Nom" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope"></i> Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email" required>
                    </div>

                    <!-- Rôle -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield"></i> Rôle <span class="text-danger">*</span></label>
                        <div class="role-checkboxes">
                            <?php foreach ($roles as $role): ?>
                                <label class="role-option">
                                    <input type="radio" name="role" value="<?php echo $role['id']; ?>" 
                                           <?php echo $selectedRole == $role['id'] ? 'checked' : ''; ?>
                                           required>
                                    <div class="role-option-content">
                                        <div class="role-name"><?php echo htmlspecialchars($role['nom']); ?></div>
                                        <?php if (!empty($role['description'])): ?>
                                            <div class="role-desc"><?php echo htmlspecialchars($role['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Mot de passe (optionnel) -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock"></i> Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="password" placeholder="Laisser vide pour ne pas changer">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock"></i> Confirmer le mot de passe</label>
                            <input type="password" class="form-control" name="password_confirm" placeholder="Confirmez le nouveau mot de passe">
                        </div>
                    </div>
                    <small class="text-muted d-block mb-3">Laissez les champs vides pour conserver le mot de passe actuel.</small>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" <?php echo $actif ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="actif">
                                <i class="bi bi-check-circle"></i> Actif (peut se connecter)
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ========== TOGGLE SIDEBAR MOBILE ==========
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebarWrapper');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebarWrapper.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
        const icon = sidebarToggle.querySelector('i');
        if (sidebarWrapper.classList.contains('open')) {
            icon.className = 'bi bi-x-lg';
        } else {
            icon.className = 'bi bi-list';
        }
    }

    function closeSidebar() {
        sidebarWrapper.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        const icon = sidebarToggle.querySelector('i');
        icon.className = 'bi bi-list';
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);

    // Fermer la sidebar en appuyant sur Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Fermer la sidebar lors du redimensionnement > 992px
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });
</script>
</body>
</html>