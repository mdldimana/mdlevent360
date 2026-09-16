<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('journal.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer l'activité
$activite = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            ja.*,
            u.nom,
            u.prenom,
            u.username,
            u.email
        FROM journal_activites ja
        LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
        WHERE ja.id = ?
    ");
    $stmt->execute([$id]);
    $activite = $stmt->fetch();
} catch (PDOException $e) {
    // Ignorer
}

if (!$activite) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'activité - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Réutilisation des styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
        }
        .main-content {
            height: 100vh;
            overflow-y: auto;
            padding: 0;
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

        .detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            max-width: 800px;
        }
        .detail-card .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .detail-card .header .icon {
            font-size: 48px;
            color: #f7971e;
        }
        .detail-card .header h4 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .detail-card .header .badge {
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
            width: 150px;
            font-weight: 600;
            color: #888;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-row .value { flex: 1; color: #1a1a2e; font-size: 14px; }

        .badge-action {
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-action.LOGIN { background: #cce5ff; color: #004085; }
        .badge-action.LOGOUT { background: #e2e3e5; color: #383d41; }
        .badge-action.LOGIN_FAILED { background: #f8d7da; color: #721c24; }
        .badge-action.CREATE_USER { background: #d4edda; color: #155724; }
        .badge-action.UPDATE_USER { background: #fff3cd; color: #856404; }
        .badge-action.TOGGLE_USER { background: #f8d7da; color: #721c24; }
        .badge-action.CREATE_ROLE { background: #d4edda; color: #155724; }
        .badge-action.UPDATE_ROLE { background: #fff3cd; color: #856404; }
        .badge-action.TOGGLE_ROLE { background: #f8d7da; color: #721c24; }
        .badge-action.UPDATE_ROLE_PERMISSIONS { background: #cce5ff; color: #004085; }
        .badge-action.ACCESS_DENIED { background: #f8d7da; color: #721c24; }
        .badge-action.default { background: #e9ecef; color: #495057; }

        .btn-back {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-back:hover { background: #e9ecef; color: #333; }

        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
            .content-section { padding: 15px 20px; }
            .detail-card { padding: 20px; }
            .info-row { flex-direction: column; gap: 5px; }
            .info-row .label { width: 100%; }
        }
        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 10px 15px; }
            .detail-card { padding: 15px; }
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
                    <h4><i class="bi bi-clock-history"></i> Détails de l'activité</h4>
                    <small><i class="bi bi-eye"></i> Informations complètes</small>
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
                <div class="detail-card fade-in">

                    <div class="header">
                        <div class="icon">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div>
                            <h4>Activité #<?php echo $activite['id']; ?></h4>
                            <div>
                                <span class="badge-action <?php echo htmlspecialchars($activite['action']); ?> default">
                                    <?php echo htmlspecialchars($activite['action']); ?>
                                </span>
                                <?php if ($activite['module']): ?>
                                    <span class="badge bg-light text-dark ms-2">
                                        <?php echo htmlspecialchars($activite['module']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-person"></i> Utilisateur</span>
                        <span class="value">
                            <?php if ($activite['utilisateur_id']): ?>
                                <strong><?php echo htmlspecialchars($activite['prenom'] . ' ' . $activite['nom']); ?></strong>
                                <span class="text-muted">(@<?php echo htmlspecialchars($activite['username']); ?>)</span>
                            <?php else: ?>
                                <span class="text-muted">Système</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-tag"></i> Action</span>
                        <span class="value"><?php echo htmlspecialchars($activite['action']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-folder"></i> Module</span>
                        <span class="value"><?php echo htmlspecialchars($activite['module'] ?? 'Non spécifié'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-align-left"></i> Description</span>
                        <span class="value"><?php echo nl2br(htmlspecialchars($activite['description'])); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-clock"></i> Date</span>
                        <span class="value"><?php echo date('d/m/Y à H:i:s', strtotime($activite['date_action'])); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-wifi"></i> Adresse IP</span>
                        <span class="value"><code><?php echo htmlspecialchars($activite['adresse_ip'] ?? 'Inconnue'); ?></code></span>
                    </div>

                    <div class="info-row">
                        <span class="label"><i class="bi bi-browser-chrome"></i> Navigateur</span>
                        <span class="value" style="font-size: 12px; color: #888;">
                            <?php echo htmlspecialchars($activite['user_agent'] ?? 'Inconnu'); ?>
                        </span>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <a href="index.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Retour au journal
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div style="text-align: center; padding: 30px 0 20px; color: #ccc; font-size: 13px;">
                    <i class="bi bi-heart-fill" style="color: #ff6b6b;"></i>
                    <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>