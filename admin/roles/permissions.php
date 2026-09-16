<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('permissions.modifier');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer le rôle
$role = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, description FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$role) {
    header('Location: index.php');
    exit;
}

// Récupérer les permissions du rôle
$rolePermissions = [];
try {
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$id]);
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignorer
}

// Récupérer toutes les permissions groupées par module
$permissions = [];
try {
    $stmt = $pdo->query("
        SELECT id, nom, description, module 
        FROM permissions 
        ORDER BY module, nom
    ");
    $permissions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignorer
}

// Grouper les permissions par module
$permissionsByModule = [];
foreach ($permissions as $perm) {
    $module = $perm['module'] ?? 'Autres';
    if (!isset($permissionsByModule[$module])) {
        $permissionsByModule[$module] = [];
    }
    $permissionsByModule[$module][] = $perm;
}

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPermissions = $_POST['permissions'] ?? [];

    try {
        // Supprimer toutes les permissions du rôle
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$id]);

        // Ajouter les nouvelles permissions
        if (!empty($selectedPermissions)) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($selectedPermissions as $permId) {
                $stmt->execute([$id, $permId]);
            }
        }

        // Journaliser
        logAction($user['id'], 'UPDATE_ROLE_PERMISSIONS', 'roles', 
                  "Mise à jour des permissions du rôle {$role['nom']} (ID: $id)");

        $success = 'Permissions mises à jour avec succès !';

        // Recharger les permissions du rôle
        $rolePermissions = $selectedPermissions;

    } catch (PDOException $e) {
        $error = 'Erreur lors de la mise à jour : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permissions - <?php echo APP_NAME; ?></title>
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
            max-width: 900px;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .form-container .form-title i { color: #f7971e; margin-right: 10px; }
        .form-container .form-title .badge-role {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .module-group {
            margin-bottom: 25px;
            border: 1px solid #e1e5ee;
            border-radius: 12px;
            overflow: hidden;
        }
        .module-group .module-header {
            background: #f8f9fa;
            padding: 12px 20px;
            font-weight: 700;
            color: #1a1a2e;
            border-bottom: 1px solid #e1e5ee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .module-group .module-header .toggle-all {
            cursor: pointer;
            font-size: 13px;
            color: #f7971e;
            font-weight: 600;
        }
        .module-group .module-header .toggle-all:hover { text-decoration: underline; }
        .module-group .module-body {
            padding: 15px 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 8px;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .permission-item:hover { background: #f8f9fa; }
        .permission-item input[type="checkbox"] {
            accent-color: #f7971e;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .permission-item label {
            cursor: pointer;
            font-size: 13px;
            color: #555;
            margin: 0;
        }
        .permission-item label .perm-desc {
            font-size: 11px;
            color: #aaa;
            display: block;
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

        .alert-success-custom {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

            .module-group .module-body {
                grid-template-columns: 1fr;
            }
            .module-group .module-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
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
                flex-wrap: wrap;
                gap: 8px;
            }
            .form-container .form-title .badge-role {
                font-size: 12px;
                padding: 3px 10px;
            }

            .module-group .module-header {
                padding: 10px 15px;
                font-size: 14px;
            }
            .module-group .module-header .toggle-all {
                font-size: 12px;
            }
            .module-group .module-body {
                padding: 10px 15px;
                gap: 4px;
            }

            .permission-item {
                padding: 4px 8px;
            }
            .permission-item label {
                font-size: 12px;
            }
            .permission-item label .perm-desc {
                font-size: 10px;
            }
            .permission-item input[type="checkbox"] {
                width: 14px;
                height: 14px;
            }

            .btn-save, .btn-cancel {
                font-size: 14px;
                padding: 10px 20px;
            }

            .alert-success-custom, .alert-error {
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
            .module-group .module-header {
                font-size: 13px;
                padding: 8px 12px;
            }
            .module-group .module-body {
                padding: 8px 10px;
            }
            .permission-item label {
                font-size: 11px;
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
                <h4><i class="bi bi-key"></i> Gérer les permissions</h4>
                <small><i class="bi bi-shield"></i> Permissions du rôle</small>
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

                <h5 class="form-title d-flex flex-wrap align-items-center gap-2">
                    <span><i class="bi bi-key"></i> Permissions du rôle</span>
                    <span class="badge-role"><?php echo htmlspecialchars($role['nom']); ?></span>
                </h5>

                <?php if ($success): ?>
                    <div class="alert-success-custom">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php foreach ($permissionsByModule as $module => $perms): ?>
                        <div class="module-group">
                            <div class="module-header">
                                <span><i class="bi bi-folder"></i> <?php echo ucfirst($module); ?></span>
                                <span class="toggle-all" onclick="toggleModule(this, '<?php echo $module; ?>')">
                                    <i class="bi bi-check-all"></i> Tout sélectionner
                                </span>
                            </div>
                            <div class="module-body">
                                <?php foreach ($perms as $perm): ?>
                                    <div class="permission-item">
                                        <input type="checkbox" name="permissions[]" 
                                               value="<?php echo $perm['id']; ?>"
                                               id="perm_<?php echo $perm['id']; ?>"
                                               <?php echo in_array($perm['id'], $rolePermissions) ? 'checked' : ''; ?>>
                                        <label for="perm_<?php echo $perm['id']; ?>">
                                            <?php echo htmlspecialchars($perm['nom']); ?>
                                            <?php if (!empty($perm['description'])): ?>
                                                <span class="perm-desc"><?php echo htmlspecialchars($perm['description']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-save"></i> Enregistrer les permissions
                        </button>
                        <a href="index.php" class="btn btn-cancel">
                            <i class="bi bi-arrow-left"></i> Retour
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

    // ========== TOGGLE ALL PERMISSIONS ==========
    function toggleModule(btn, module) {
        const moduleGroup = btn.closest('.module-group');
        const checkboxes = moduleGroup.querySelectorAll('input[type="checkbox"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
        
        btn.innerHTML = allChecked ? 
            '<i class="bi bi-check-all"></i> Tout sélectionner' : 
            '<i class="bi bi-x-circle"></i> Tout désélectionner';
    }

    // Auto-hide success message
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert-success-custom');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    });
</script>
</body>
</html>