<?php
// ============================================================
// MODIFICATION D'UN RÔLE
// ============================================================

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('roles.modifier');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================================
// RÉCUPÉRATION DU RÔLE À MODIFIER
// ============================================================
$roleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($roleId <= 0) {
    header('Location: index.php?error=id_manquant');
    exit;
}

$role = null;
try {
    $stmt = $pdo->prepare("
        SELECT id, nom, description, actif, created_at
        FROM roles
        WHERE id = ?
    ");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur chargement rôle : ' . $e->getMessage());
    header('Location: index.php?error=chargement');
    exit;
}

if (!$role) {
    header('Location: index.php?error=introuvable');
    exit;
}

// Empêcher la modification du rôle SUPER_ADMIN (optionnel)
$isSuperAdmin = (strtoupper($role['nom']) === 'SUPER_ADMIN');

// ============================================================
// RÉCUPÉRATION DES PERMISSIONS ACTUELLES DU RÔLE
// ============================================================
$rolePermissions = [];
try {
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $rolePermissions = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission_id');
    $rolePermissions = array_map('intval', $rolePermissions);
} catch (PDOException $e) {
    // Ignorer
}

// ============================================================
// STATISTIQUES DU RÔLE
// ============================================================
$nbUtilisateurs = 0;
$nbPermissions = count($rolePermissions);
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM utilisateur_roles WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $nbUtilisateurs = (int)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {
    // Ignorer
}

// ============================================================
// TRAITEMENT DES ACTIONS
// ============================================================
$error = '';

// Valeurs par défaut
$nom = $role['nom'];
$description = $role['description'] ?? '';
$actif = (int)$role['actif'];

// ---------- SUPPRESSION ----------
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    if ($isSuperAdmin) {
        $error = 'Le rôle SUPER_ADMIN ne peut pas être supprimé.';
    } elseif ($nbUtilisateurs > 0) {
        $error = "Impossible de supprimer ce rôle : $nbUtilisateurs utilisateur(s) y sont associés.";
    } else {
        try {
            $pdo->beginTransaction();

            // Supprimer les permissions associées
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);

            // Supprimer le rôle
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);

            $pdo->commit();

            if (function_exists('logAction')) {
                logAction($user['id'], 'DELETE_ROLE', 'roles', "Suppression du rôle #$roleId ({$role['nom']})");
            }

            header('Location: index.php?success=supprime');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erreur lors de la suppression : ' . $e->getMessage();
        }
    }
}

// ---------- MODIFICATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'supprimer')) {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;
    $selectedPermissions = $_POST['permissions'] ?? [];

    // Validation
    $errors = [];

    if (empty($nom)) {
        $errors[] = 'Le nom du rôle est requis';
    }
    if (strlen($nom) < 3) {
        $errors[] = 'Le nom doit contenir au moins 3 caractères';
    }

    // Vérifier l'unicité (sauf pour ce rôle)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE nom = ? AND id != ?");
            $stmt->execute([$nom, $roleId]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom de rôle existe déjà";
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la vérification';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Mise à jour du rôle
            $stmt = $pdo->prepare("
                UPDATE roles
                SET nom = ?, description = ?, actif = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $description, $actif, $roleId]);

            // Mise à jour des permissions
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);

            if (!empty($selectedPermissions)) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($selectedPermissions as $permId) {
                    $stmt->execute([$roleId, (int)$permId]);
                }
            }

            $pdo->commit();

            if (function_exists('logAction')) {
                logAction($user['id'], 'UPDATE_ROLE', 'roles', "Modification du rôle #$roleId ({$nom})");
            }

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

// ============================================================
// RÉCUPÉRATION DES PERMISSIONS DISPONIBLES (groupées par module)
// ============================================================
$permissionsGrouped = [];
try {
    $stmt = $pdo->query("
        SELECT id, nom, description, module
        FROM permissions
        ORDER BY module ASC, nom ASC
    ");
    $allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPermissions as $perm) {
        $module = $perm['module'] ?: 'autres';
        if (!isset($permissionsGrouped[$module])) {
            $permissionsGrouped[$module] = [];
        }
        $permissionsGrouped[$module][] = $perm;
    }
} catch (PDOException $e) {
    error_log('Erreur chargement permissions : ' . $e->getMessage());
}

// Label des modules
$moduleLabels = [
    'evenements'    => '📅 Événements',
    'invites'       => '👥 Invités',
    'invitations'   => '✉️ Invitations',
    'boissons'      => '🍹 Boissons',
    'preferences'   => '⭐ Préférences',
    'presences'     => '✅ Présences',
    'rapports'      => '📊 Rapports',
    'utilisateurs'  => '👤 Utilisateurs',
    'roles'         => '🛡️ Rôles',
    'permissions'   => '🔑 Permissions',
    'journal'       => '📜 Journal',
    'whatsapp'      => '💬 WhatsApp',
    'telegram'      => '📨 Telegram',
    'tables'        => '🪑 Tables',
    'notifications' => '🔔 Notifications',
    'dashboard'     => '📊 Dashboard',
    'modeles'       => '🎨 Modèles',
    'parametres'    => '⚙️ Paramètres',
    'autres'        => '📦 Autres',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un rôle - <?php echo APP_NAME; ?></title>
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

        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

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
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
        }
        .sidebar-overlay.active { display: block; }

        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px; left: 15px;
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
            max-width: 1000px;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .form-container .form-title i {
            color: #f7971e;
            margin-right: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
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

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-delete:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
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

        .role-meta {
            background: linear-gradient(135deg, rgba(247, 151, 30, 0.08), rgba(255, 210, 0, 0.05));
            border: 1px solid rgba(247, 151, 30, 0.2);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        .role-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        .role-meta .meta-item i {
            color: #f7971e;
        }
        .role-meta .meta-item strong {
            color: #1a1a2e;
        }

        /* ========== PERMISSIONS ========== */
        .permissions-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .permissions-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .permissions-section-header h6 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
            font-size: 15px;
        }
        .permissions-counter {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
        }
        .permissions-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-mini {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-mini:hover {
            background: #f7971e;
            color: white;
            border-color: #f7971e;
        }

        .permission-module {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        .permission-module:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(247, 151, 30, 0.08);
            border-color: rgba(247, 151, 30, 0.2);
        }
        .permission-module-title {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .permission-module-title .module-select {
            font-size: 11px;
            font-weight: 600;
            color: #f7971e;
            cursor: pointer;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(247, 151, 30, 0.1);
            border: none;
        }
        .permission-module-title .module-select:hover {
            background: rgba(247, 151, 30, 0.2);
        }

        .permission-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 8px;
        }
        .permission-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px 12px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        .permission-item:hover {
            border-color: #f7971e;
            background: rgba(247, 151, 30, 0.03);
        }
        .permission-item input[type="checkbox"] {
            accent-color: #f7971e;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 2px;
            cursor: pointer;
        }
        .permission-item .perm-name {
            font-size: 12px;
            font-weight: 600;
            color: #1a1a2e;
            word-break: break-all;
        }
        .permission-item .perm-desc {
            font-size: 10px;
            color: #999;
            display: block;
            margin-top: 2px;
        }

        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #ccc;
            font-size: 13px;
        }
        .app-footer i.bi-heart-fill { color: #ff6b6b; }

        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }

        @media (max-width: 992px) {
            html, body { overflow: visible; }
            .app-container { height: auto; min-height: 100vh; }
            .sidebar-wrapper {
                position: fixed;
                left: 0; top: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar-wrapper.open { transform: translateX(0); }
            .sidebar-wrapper .sidebar { height: 100vh; }
            .sidebar-toggle-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .main-content { height: auto; min-height: 100vh; }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 20px 15px 75px;
            }
            .top-bar .user-info {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 15px 20px; }
            .form-container { padding: 20px; }
            .btn-save, .btn-cancel, .btn-delete {
                width: 100%;
                justify-content: center;
            }
            .permission-checkboxes {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px 12px 65px; }
            .top-bar .page-title h4 { font-size: 18px; }
            .top-bar .page-title small { font-size: 11px; }
            .top-bar .user-info .role-badge { font-size: 10px; padding: 3px 10px; }
            .top-bar .user-info .user-avatar { width: 38px; height: 38px; font-size: 15px; }
            .content-section { padding: 10px 15px; }
            .sidebar-wrapper .sidebar { width: 280px; }
            .sidebar-toggle-btn { top: 12px; left: 12px; padding: 8px 12px; font-size: 18px; }
            .form-container { padding: 15px; border-radius: 14px; }
            .form-container .form-title { font-size: 18px; margin-bottom: 18px; }
            .form-label { font-size: 13px; }
            .form-control, .form-select { font-size: 13px; padding: 8px 12px; }
            .btn-save, .btn-cancel, .btn-delete { font-size: 14px; padding: 10px 20px; }
            .alert-error { font-size: 13px; padding: 12px 15px; }
            .permission-module { padding: 12px; }
            .permission-module-title { font-size: 13px; }
            .permission-item { padding: 6px 10px; }
            .permission-item .perm-name { font-size: 11px; }
            .app-footer { font-size: 11px; padding: 20px 0 15px; }
        }

        @media (max-width: 400px) {
            .form-container .form-title { font-size: 16px; }
            .btn-save, .btn-cancel, .btn-delete { font-size: 13px; padding: 8px 16px; }
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
                <h4><i class="bi bi-pencil-square"></i> Modifier un rôle</h4>
                <small><i class="bi bi-shield-lock"></i> Édition du rôle "<?php echo htmlspecialchars($role['nom']); ?>"</small>
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

                <h5 class="form-title"><i class="bi bi-pencil-square"></i> Modifier le rôle</h5>

                <!-- Métadonnées du rôle -->
                <div class="role-meta">
                    <div class="meta-item">
                        <i class="bi bi-hash"></i>
                        ID: <strong>#<?php echo (int)$role['id']; ?></strong>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-calendar-plus"></i>
                        Créé le <strong><?php echo date('d/m/Y', strtotime($role['created_at'])); ?></strong>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-people"></i>
                        <strong><?php echo $nbUtilisateurs; ?></strong> utilisateur(s)
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-key"></i>
                        <strong><?php echo $nbPermissions; ?></strong> permission(s)
                    </div>
                </div>

                <?php if ($isSuperAdmin): ?>
                    <div class="alert-error" style="background:#fff3cd; border-color:#ffeaa7; color:#856404;">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>⚠️ Le rôle <strong>SUPER_ADMIN</strong> ne peut pas être supprimé. Ses permissions peuvent être modifiées avec précaution.</span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="roleForm">
                    <!-- Nom -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield"></i> Nom du rôle <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nom" 
                               value="<?php echo htmlspecialchars($nom); ?>" 
                               placeholder="Ex: ADMIN, MANAGER, INVITE..." 
                               required
                               <?php echo $isSuperAdmin ? 'readonly' : ''; ?>>
                        <small class="text-muted">Le nom doit être unique</small>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-text-paragraph"></i> Description</label>
                        <textarea class="form-control" name="description" rows="2" 
                                  placeholder="Description du rôle..."><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <!-- Actif -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" 
                                   <?php echo $actif ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="actif">
                                <i class="bi bi-check-circle"></i> Rôle actif
                            </label>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- PERMISSIONS -->
                    <!-- ============================================ -->
                    <div class="permissions-section">
                        <div class="permissions-section-header">
                            <h6><i class="bi bi-key-fill" style="color:#f7971e;"></i> Permissions du rôle</h6>
                            <div class="permissions-actions">
                                <button type="button" class="btn-mini" onclick="selectAllPerms()">
                                    <i class="bi bi-check-all"></i> Tout cocher
                                </button>
                                <button type="button" class="btn-mini" onclick="unselectAllPerms()">
                                    <i class="bi bi-x-circle"></i> Tout décocher
                                </button>
                            </div>
                            <span class="permissions-counter">
                                <i class="bi bi-check2-circle"></i> 
                                <span id="permsCount"><?php echo $nbPermissions; ?></span> sélectionnée(s)
                            </span>
                        </div>

                        <?php if (!empty($permissionsGrouped)): ?>
                            <?php foreach ($permissionsGrouped as $module => $perms): ?>
                                <div class="permission-module">
                                    <div class="permission-module-title">
                                        <span><?php echo $moduleLabels[$module] ?? ucfirst($module); ?></span>
                                        <button type="button" class="module-select" onclick="toggleModule('<?php echo htmlspecialchars($module); ?>')">
                                            <i class="bi bi-check-all"></i> Sélectionner
                                        </button>
                                    </div>
                                    <div class="permission-checkboxes">
                                        <?php foreach ($perms as $perm): 
                                            $isChecked = in_array((int)$perm['id'], $rolePermissions, true);
                                        ?>
                                            <label class="permission-item" data-module="<?php echo htmlspecialchars($module); ?>">
                                                <input type="checkbox" name="permissions[]" 
                                                       value="<?php echo (int)$perm['id']; ?>"
                                                       class="perm-checkbox"
                                                       data-module="<?php echo htmlspecialchars($module); ?>"
                                                       onchange="updatePermsCount()"
                                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                                <div>
                                                    <div class="perm-name"><?php echo htmlspecialchars($perm['nom']); ?></div>
                                                    <?php if (!empty($perm['description'])): ?>
                                                        <span class="perm-desc"><?php echo htmlspecialchars($perm['description']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Aucune permission disponible.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Boutons -->
                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                        <a href="index.php" class="btn btn-cancel">
                            <i class="bi bi-arrow-left"></i> Annuler
                        </a>
                        <?php if (!$isSuperAdmin && $nbUtilisateurs === 0): ?>
                            <button type="button" class="btn btn-delete" onclick="confirmDelete()">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Formulaire caché pour suppression -->
                <form method="POST" action="" id="deleteForm" style="display:none;">
                    <input type="hidden" name="action" value="supprimer">
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
        icon.className = sidebarWrapper.classList.contains('open') ? 'bi bi-x-lg' : 'bi bi-list';
    }

    function closeSidebar() {
        sidebarWrapper.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        sidebarToggle.querySelector('i').className = 'bi bi-list';
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) closeSidebar();
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebarWrapper.classList.contains('open')) closeSidebar();
    });

    // ========== COMPTEUR DE PERMISSIONS ==========
    function updatePermsCount() {
        const checked = document.querySelectorAll('.perm-checkbox:checked');
        document.getElementById('permsCount').textContent = checked.length;
    }

    // ========== TOUT COCHER ==========
    function selectAllPerms() {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
        updatePermsCount();
    }

    // ========== TOUT DÉCOCHER ==========
    function unselectAllPerms() {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        updatePermsCount();
    }

    // ========== SÉLECTION PAR MODULE ==========
    function toggleModule(module) {
        const checkboxes = document.querySelectorAll(`.perm-checkbox[data-module="${module}"]`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        updatePermsCount();
    }

    // ========== CONFIRMATION DE SUPPRESSION ==========
    function confirmDelete() {
        if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce rôle ?\n\nCette action est irréversible.')) {
            document.getElementById('deleteForm').submit();
        }
    }

    // ========== INITIALISATION ==========
    document.addEventListener('DOMContentLoaded', function() {
        updatePermsCount();
    });
</script>
</body>
</html>