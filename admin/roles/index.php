<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('roles.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// Récupérer la liste des rôles avec le nombre d'utilisateurs et de permissions
$roles = [];
try {
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.nom,
            r.description,
            r.actif,
            r.created_at,
            COUNT(DISTINCT ur.utilisateur_id) as nb_utilisateurs,
            COUNT(DISTINCT rp.permission_id) as nb_permissions
        FROM roles r
        LEFT JOIN utilisateur_roles ur ON r.id = ur.role_id
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        GROUP BY r.id
        ORDER BY r.nom ASC
    ");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des rôles';
}

// Message de succès
$success = $_GET['success'] ?? '';
$message = [
    'ajoute' => 'Rôle ajouté avec succès !',
    'modifie' => 'Rôle modifié avec succès !',
    'supprime' => 'Rôle supprimé avec succès !',
    'permissions' => 'Permissions mises à jour avec succès !',
    'active' => 'Rôle activé avec succès !',
    'desactive' => 'Rôle désactivé avec succès !'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rôles - <?php echo APP_NAME; ?></title>
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

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
        }
        .table-container .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .table-container .table-header h5 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .table-container .table-header .btn-add {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border: none;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .table-container .table-header .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
            color: #1a1a2e;
        }
        .table-container table thead th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid rgba(247, 151, 30, 0.1);
            padding: 12px 15px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-container table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-container table tbody tr:hover { background: #fafafa; }

        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-permission {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.15), rgba(0, 242, 254, 0.1));
            color: #005f8a;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid rgba(79, 172, 254, 0.15);
            display: inline-block;
            margin: 1px;
        }

        .btn-action {
            padding: 5px 10px;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover { transform: scale(1.1); }
        .btn-action.voir { background: #cce5ff; color: #004085; }
        .btn-action.modifier { background: #fff3cd; color: #856404; }
        .btn-action.permissions { background: #d4edda; color: #155724; }
        .btn-action.supprimer { background: #f8d7da; color: #721c24; }

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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state i { font-size: 60px; color: #ddd; }
        .empty-state h5 { color: #666; margin-top: 15px; }
        .empty-state p { color: #aaa; }

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

            .table-container { 
                padding: 15px;
                overflow-x: auto;
            }
            .table-container .table-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .table-container .table-header .btn-add {
                justify-content: center;
            }
            .table-container table {
                font-size: 13px;
            }
            .table-container table thead th,
            .table-container table tbody td {
                padding: 8px 10px;
                white-space: nowrap;
            }

            .badge-permission {
                font-size: 9px;
                padding: 2px 8px;
            }
            .btn-action {
                padding: 4px 8px;
                font-size: 12px;
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

            .table-container { 
                padding: 10px;
                border-radius: 12px;
            }
            .table-container table {
                font-size: 12px;
            }
            .table-container table thead th,
            .table-container table tbody td {
                padding: 6px 8px;
            }
            .table-container .table-header h5 {
                font-size: 16px;
            }
            .table-container .table-header .btn-add {
                font-size: 13px;
                padding: 6px 16px;
            }

            .badge-active, .badge-inactive {
                font-size: 10px;
                padding: 3px 8px;
            }
            .badge-permission {
                font-size: 8px;
                padding: 1px 6px;
            }

            .btn-action {
                padding: 3px 6px;
                font-size: 11px;
                border-radius: 6px;
            }

            .empty-state i { font-size: 40px; }
            .empty-state h5 { font-size: 16px; }
            .empty-state p { font-size: 13px; }

            .alert-success-custom {
                font-size: 13px;
                padding: 12px 15px;
            }

            .app-footer {
                font-size: 11px;
                padding: 20px 0 15px;
            }
        }

        @media (max-width: 400px) {
            .table-container table thead th,
            .table-container table tbody td {
                padding: 4px 6px;
                font-size: 11px;
            }
            .btn-action {
                font-size: 10px;
                padding: 2px 5px;
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
                <h4><i class="bi bi-shield-lock"></i> Gestion des rôles</h4>
                <small><i class="bi bi-shield"></i> Liste et gestion des rôles</small>
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

            <!-- Message de succès -->
            <?php if ($success && isset($message[$success])): ?>
                <div class="alert-success-custom fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $message[$success]; ?>
                </div>
            <?php endif; ?>

            <!-- Table des rôles -->
            <div class="table-container fade-in">
                <div class="table-header">
                    <h5><i class="bi bi-shield-lock"></i> Liste des rôles</h5>
                    <?php if (hasPermission('roles.creer')): ?>
                        <a href="creer.php" class="btn-add">
                            <i class="bi bi-plus-circle"></i> Ajouter un rôle
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($roles)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                    <th>Utilisateurs</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?php echo $role['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($role['nom']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($role['description'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge-permission">
                                                <i class="bi bi-key"></i> <?php echo $role['nb_permissions']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-permission" style="background: rgba(247, 151, 30, 0.1); border-color: rgba(247, 151, 30, 0.2);">
                                                <i class="bi bi-people"></i> <?php echo $role['nb_utilisateurs']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($role['actif']): ?>
                                                <span class="badge-active"><i class="bi bi-check-circle"></i> Actif</span>
                                            <?php else: ?>
                                                <span class="badge-inactive"><i class="bi bi-x-circle"></i> Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a href="voir.php?id=<?php echo $role['id']; ?>" 
                                                   class="btn-action voir" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (hasPermission('roles.modifier')): ?>
                                                    <a href="modifier.php?id=<?php echo $role['id']; ?>" 
                                                       class="btn-action modifier" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('permissions.modifier')): ?>
                                                    <a href="permissions.php?id=<?php echo $role['id']; ?>" 
                                                       class="btn-action permissions" title="Gérer les permissions">
                                                        <i class="bi bi-key"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('roles.supprimer') && $role['nom'] != 'SUPER_ADMIN'): ?>
                                                    <a href="supprimer.php?id=<?php echo $role['id']; ?>" 
                                                       class="btn-action supprimer" title="<?php echo $role['actif'] ? 'Désactiver' : 'Activer'; ?>"
                                                       onclick="return confirm('Voulez-vous vraiment <?php echo $role['actif'] ? 'désactiver' : 'activer'; ?> ce rôle ?')">
                                                        <i class="bi <?php echo $role['actif'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-shield-lock"></i>
                        <h5>Aucun rôle</h5>
                        <p>Commencez par créer votre premier rôle</p>
                        <?php if (hasPermission('roles.creer')): ?>
                            <a href="creer.php" class="btn-add mt-3" style="display: inline-flex;">
                                <i class="bi bi-plus-circle"></i> Ajouter un rôle
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

    // Fermer avec Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Fermer si redimension > 992px
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        }
    });

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