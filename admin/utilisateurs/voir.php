<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('utilisateurs.voir', 'index.php');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'utilisateur avec ses rôles
$utilisateur = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.nom,
            u.prenom,
            u.username,
            u.email,
            u.actif,
            u.last_login,
            u.created_at,
            GROUP_CONCAT(r.nom SEPARATOR ', ') as roles
        FROM utilisateurs u
        LEFT JOIN utilisateur_roles ur ON u.id = ur.utilisateur_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$id]);
    $utilisateur = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$utilisateur) {
    header('Location: index.php');
    exit;
}

// Récupérer le journal des actions de l'utilisateur
$activites = [];
try {
    $stmt = $pdo->prepare("
        SELECT action, module, description, date_action, adresse_ip
        FROM journal_activites
        WHERE utilisateur_id = ?
        ORDER BY date_action DESC
        LIMIT 20
    ");
    $stmt->execute([$id]);
    $activites = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignorer
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails - <?php echo APP_NAME; ?></title>
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

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            max-width: 900px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 32px;
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
            flex-shrink: 0;
        }
        .profile-info h4 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .profile-info .username {
            color: #f7971e;
            font-weight: 600;
        }
        .profile-info .email {
            color: #888;
            font-size: 14px;
        }
        .profile-info .status-badge {
            margin-top: 5px;
            display: inline-block;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label {
            width: 160px;
            font-weight: 600;
            color: #888;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-row .value {
            flex: 1;
            color: #1a1a2e;
            font-size: 14px;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-role {
            background: linear-gradient(135deg, rgba(247, 151, 30, 0.15), rgba(255, 210, 0, 0.1));
            color: #1a1a2e;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(247, 151, 30, 0.15);
        }

        .btn-edit {
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
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
            color: #1a1a2e;
        }
        .btn-back {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: #e9ecef;
            color: #333;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 13px;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .activity-icon {
            width: 30px;
            color: #f7971e;
            flex-shrink: 0;
        }
        .activity-item .activity-desc { flex: 1; color: #555; }
        .activity-item .activity-date { color: #aaa; font-size: 12px; }

        /* ========== ANIMATION D'ENTRÉE - CORRIGÉE ========== */
        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }

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

            .profile-card {
                padding: 20px;
                max-width: 100%;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            .info-row .label {
                width: 100%;
            }
            .profile-info .status-badge {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
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

            .profile-card {
                padding: 15px;
                border-radius: 14px;
            }
            .profile-avatar {
                width: 64px;
                height: 64px;
                font-size: 24px;
            }
            .profile-info h4 {
                font-size: 18px;
            }
            .profile-info .email {
                font-size: 13px;
            }
            .info-row .label {
                font-size: 13px;
            }
            .info-row .value {
                font-size: 13px;
            }
            .btn-edit, .btn-back {
                font-size: 13px;
                padding: 6px 16px;
                width: 100%;
                justify-content: center;
            }
            .activity-item {
                font-size: 12px;
                flex-wrap: wrap;
                gap: 5px;
            }
            .activity-item .activity-date {
                font-size: 11px;
                margin-left: 30px;
            }
            .app-footer {
                font-size: 11px;
                padding: 20px 0 15px;
            }
        }

        @media (max-width: 400px) {
            .profile-avatar {
                width: 56px;
                height: 56px;
                font-size: 20px;
            }
            .profile-info h4 {
                font-size: 16px;
            }
            .btn-edit, .btn-back {
                font-size: 12px;
                padding: 5px 14px;
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
                <h4><i class="bi bi-person"></i> Détails de l'utilisateur</h4>
                <small><i class="bi bi-eye"></i> Informations du compte</small>
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
            <div class="profile-card fade-in">

                <!-- En-tête du profil -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        $init = strtoupper(
                            substr($utilisateur['prenom'] ?? '', 0, 1) . 
                            substr($utilisateur['nom'] ?? '', 0, 1)
                        );
                        echo $init ?: 'U';
                        ?>
                    </div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h4>
                        <div class="username">@<?php echo htmlspecialchars($utilisateur['username']); ?></div>
                        <div class="email"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($utilisateur['email']); ?></div>
                        <div class="status-badge">
                            <?php if ($utilisateur['actif']): ?>
                                <span class="badge-active"><i class="bi bi-check-circle"></i> Actif</span>
                            <?php else: ?>
                                <span class="badge-inactive"><i class="bi bi-x-circle"></i> Inactif</span>
                            <?php endif; ?>
                            <span class="badge-role">
                                <i class="bi bi-shield"></i>
                                <?php echo htmlspecialchars($utilisateur['roles'] ?? 'Aucun rôle'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informations -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="label"><i class="bi bi-person"></i> Nom complet</span>
                            <span class="value"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="bi bi-person-badge"></i> Identifiant</span>
                            <span class="value"><code><?php echo htmlspecialchars($utilisateur['username']); ?></code></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="bi bi-envelope"></i> Email</span>
                            <span class="value"><?php echo htmlspecialchars($utilisateur['email']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="label"><i class="bi bi-shield"></i> Rôle</span>
                            <span class="value">
                                <span class="badge-role">
                                    <?php echo htmlspecialchars($utilisateur['roles'] ?? 'Aucun rôle'); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="bi bi-clock-history"></i> Dernière connexion</span>
                            <span class="value">
                                <?php 
                                $lastLogin = $utilisateur['last_login'] ?? null;
                                echo $lastLogin ? date('d/m/Y à H:i', strtotime($lastLogin)) : 'Jamais';
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="bi bi-calendar"></i> Date de création</span>
                            <span class="value">
                                <?php echo date('d/m/Y à H:i', strtotime($utilisateur['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-3 mt-4 pt-3 border-top flex-wrap">
                    <a href="index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <?php if (hasPermission('utilisateurs.modifier')): ?>
                        <a href="modifier.php?id=<?php echo $utilisateur['id']; ?>" class="btn-edit">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                    <?php endif; ?>
                    <?php if (hasPermission('utilisateurs.desactiver') && $utilisateur['id'] != $user['id']): ?>
                        <a href="supprimer.php?id=<?php echo $utilisateur['id']; ?>" 
                           class="btn-edit" style="background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white;"
                           onclick="return confirm('Voulez-vous vraiment <?php echo $utilisateur['actif'] ? 'désactiver' : 'activer'; ?> cet utilisateur ?')">
                            <i class="bi <?php echo $utilisateur['actif'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                            <?php echo $utilisateur['actif'] ? 'Désactiver' : 'Activer'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dernières activités -->
            <div class="profile-card mt-4 fade-in" style="max-width: 900px;">
                <h6 class="mb-3"><i class="bi bi-clock-history"></i> Dernières activités</h6>
                <?php if (!empty($activites)): ?>
                    <?php foreach ($activites as $activite): ?>
                        <div class="activity-item">
                            <span class="activity-icon"><i class="bi bi-record-circle"></i></span>
                            <span class="activity-desc">
                                <strong><?php echo htmlspecialchars($activite['action']); ?></strong>
                                <?php echo htmlspecialchars($activite['description']); ?>
                            </span>
                            <span class="activity-date">
                                <i class="bi bi-clock"></i>
                                <?php echo date('d/m/Y H:i', strtotime($activite['date_action'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Aucune activité enregistrée</p>
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