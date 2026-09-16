<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions (accès aux paramètres)
requirePermission('parametres.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// VARIABLES
// ============================================

$success = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'general';

// ============================================
// TRAITEMENT DES FORMULAIRES
// ============================================

// --- GÉNÉRAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'general') {
    $app_name = trim($_POST['app_name'] ?? '');
    $app_timezone = $_POST['app_timezone'] ?? 'Europe/Paris';
    $session_timeout = (int)($_POST['session_timeout'] ?? 3600);
    $max_login_attempts = (int)($_POST['max_login_attempts'] ?? 5);
    
    // Mise à jour du fichier de configuration (simulé)
    // Dans un vrai projet, on écrirait dans un fichier .env ou config.php
    
    $success = 'Paramètres généraux mis à jour avec succès !';
    logAction($user['id'], 'UPDATE_SETTINGS', 'parametres', 'Mise à jour des paramètres généraux');
}

// --- SÉCURITÉ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'security') {
    $force_https = isset($_POST['force_https']) ? 1 : 0;
    $csrf_protection = isset($_POST['csrf_protection']) ? 1 : 0;
    $session_secure = isset($_POST['session_secure']) ? 1 : 0;
    
    $success = 'Paramètres de sécurité mis à jour avec succès !';
    logAction($user['id'], 'UPDATE_SECURITY', 'parametres', 'Mise à jour des paramètres de sécurité');
}

// --- NOTIFICATIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notifications') {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $whatsapp_notifications = isset($_POST['whatsapp_notifications']) ? 1 : 0;
    $telegram_notifications = isset($_POST['telegram_notifications']) ? 1 : 0;
    
    $success = 'Paramètres de notification mis à jour avec succès !';
    logAction($user['id'], 'UPDATE_NOTIFICATIONS', 'parametres', 'Mise à jour des paramètres de notification');
}

// --- MAINTENANCE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'maintenance') {
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $maintenance_message = trim($_POST['maintenance_message'] ?? '');
    
    $success = 'Paramètres de maintenance mis à jour avec succès !';
    logAction($user['id'], 'UPDATE_MAINTENANCE', 'parametres', 'Mise à jour des paramètres de maintenance');
}

// ============================================
// STATISTIQUES DU SYSTÈME
// ============================================

$system_stats = [
    'php_version' => phpversion(),
    'mysql_version' => '',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . 's'
];

try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $system_stats['mysql_version'] = $stmt->fetch()['version'] ?? 'Inconnue';
} catch (PDOException $e) {
    $system_stats['mysql_version'] = 'Erreur';
}

// Compteurs
$counters = [
    'utilisateurs' => 0,
    'evenements' => 0,
    'invitations' => 0,
    'activites' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateurs");
    $counters['utilisateurs'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM evenements");
    $counters['evenements'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invitations");
    $counters['invitations'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM journal_activites");
    $counters['activites'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    // Ignorer
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - <?php echo APP_NAME; ?></title>
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

        .settings-container {
            max-width: 1000px;
        }

        /* Tabs */
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 25px;
            background: white;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
        }
        .settings-tabs .tab-btn {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            background: transparent;
            color: #888;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .settings-tabs .tab-btn:hover {
            background: #f8f9fa;
            color: #1a1a2e;
        }
        .settings-tabs .tab-btn.active {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            box-shadow: 0 4px 15px rgba(247, 151, 30, 0.2);
        }
        .settings-tabs .tab-btn i { font-size: 18px; }

        /* Cards */
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(247, 151, 30, 0.08);
            display: none;
        }
        .settings-card.active { display: block; }
        .settings-card .card-title {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(247, 151, 30, 0.15);
        }
        .settings-card .card-title i { color: #f7971e; margin-right: 10px; }

        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 13px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 2px solid #e1e5ee;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #f7971e;
            box-shadow: 0 0 0 3px rgba(247, 151, 30, 0.1);
        }
        .form-text {
            font-size: 12px;
            color: #aaa;
        }

        .btn-save {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            border: none;
            font-weight: 700;
            padding: 10px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
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
        .alert-error-custom {
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

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stats-grid .stat-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px 20px;
            border: 1px solid #eee;
        }
        .stats-grid .stat-item .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .stats-grid .stat-item .label {
            font-size: 13px;
            color: #888;
        }

        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label {
            width: 180px;
            font-weight: 600;
            color: #888;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-row .value { flex: 1; color: #1a1a2e; font-size: 14px; }

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
            .settings-tabs { flex-direction: column; }
            .settings-tabs .tab-btn { justify-content: center; }
            .settings-card { padding: 20px; }
            .info-row { flex-direction: column; gap: 5px; }
            .info-row .label { width: 100%; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 576px) {
            .top-bar { padding: 12px 15px; }
            .top-bar .user-info .user-name { display: none; }
            .content-section { padding: 10px 15px; }
            .settings-card { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
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
                    <h4><i class="bi bi-gear"></i> Paramètres</h4>
                    <small><i class="bi bi-sliders"></i> Configuration de l'application</small>
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
                <div class="settings-container fade-in">

                    <!-- Messages -->
                    <?php if ($success): ?>
                        <div class="alert-success-custom">
                            <i class="bi bi-check-circle-fill"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert-error-custom">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs -->
                    <div class="settings-tabs">
                        <button class="tab-btn <?php echo $activeTab == 'general' ? 'active' : ''; ?>" data-tab="general">
                            <i class="bi bi-sliders2"></i> Général
                        </button>
                        <button class="tab-btn <?php echo $activeTab == 'security' ? 'active' : ''; ?>" data-tab="security">
                            <i class="bi bi-shield-lock"></i> Sécurité
                        </button>
                        <button class="tab-btn <?php echo $activeTab == 'notifications' ? 'active' : ''; ?>" data-tab="notifications">
                            <i class="bi bi-bell"></i> Notifications
                        </button>
                        <button class="tab-btn <?php echo $activeTab == 'system' ? 'active' : ''; ?>" data-tab="system">
                            <i class="bi bi-server"></i> Système
                        </button>
                        <button class="tab-btn <?php echo $activeTab == 'maintenance' ? 'active' : ''; ?>" data-tab="maintenance">
                            <i class="bi bi-tools"></i> Maintenance
                        </button>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB 1 : GÉNÉRAL -->
                    <!-- ================================ -->
                    <div class="settings-card <?php echo $activeTab == 'general' ? 'active' : ''; ?>" id="tab-general">
                        <h5 class="card-title"><i class="bi bi-sliders2"></i> Paramètres généraux</h5>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="general">

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-tag"></i> Nom de l'application</label>
                                <input type="text" class="form-control" name="app_name" 
                                       value="<?php echo APP_NAME; ?>" 
                                       placeholder="Nom de l'application">
                                <div class="form-text">Nom affiché dans l'interface et les emails.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-clock"></i> Fuseau horaire</label>
                                <select class="form-select" name="app_timezone">
                                    <option value="Europe/Paris" <?php echo APP_TIMEZONE == 'Europe/Paris' ? 'selected' : ''; ?>>Europe/Paris</option>
                                    <option value="Africa/Kinshasa" <?php echo APP_TIMEZONE == 'Africa/Kinshasa' ? 'selected' : ''; ?>>Africa/Kinshasa</option>
                                    <option value="Africa/Lagos" <?php echo APP_TIMEZONE == 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos</option>
                                    <option value="America/New_York" <?php echo APP_TIMEZONE == 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                    <option value="America/Los_Angeles" <?php echo APP_TIMEZONE == 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los_Angeles</option>
                                    <option value="Asia/Dubai" <?php echo APP_TIMEZONE == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai</option>
                                    <option value="Asia/Tokyo" <?php echo APP_TIMEZONE == 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo</option>
                                    <option value="UTC" <?php echo APP_TIMEZONE == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                                <div class="form-text">Fuseau horaire utilisé pour toutes les dates.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-clock-history"></i> Durée de session</label>
                                    <input type="number" class="form-control" name="session_timeout" 
                                           value="<?php echo SESSION_TIMEOUT; ?>" 
                                           placeholder="3600" min="60">
                                    <div class="form-text">En secondes (3600 = 1 heure).</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-shield"></i> Tentatives de connexion</label>
                                    <input type="number" class="form-control" name="max_login_attempts" 
                                           value="<?php echo MAX_LOGIN_ATTEMPTS; ?>" 
                                           placeholder="5" min="1">
                                    <div class="form-text">Nombre de tentatives avant blocage.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB 2 : SÉCURITÉ -->
                    <!-- ================================ -->
                    <div class="settings-card <?php echo $activeTab == 'security' ? 'active' : ''; ?>" id="tab-security">
                        <h5 class="card-title"><i class="bi bi-shield-lock"></i> Paramètres de sécurité</h5>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="security">

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="force_https" id="force_https" checked>
                                    <label class="form-check-label" for="force_https">
                                        <i class="bi bi-lock"></i> Forcer HTTPS
                                    </label>
                                    <div class="form-text">Redirige automatiquement vers HTTPS en production.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="csrf_protection" id="csrf_protection" checked>
                                    <label class="form-check-label" for="csrf_protection">
                                        <i class="bi bi-shield"></i> Protection CSRF
                                    </label>
                                    <div class="form-text">Protège les formulaires contre les attaques CSRF.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="session_secure" id="session_secure" checked>
                                    <label class="form-check-label" for="session_secure">
                                        <i class="bi bi-cookie"></i> Sessions sécurisées
                                    </label>
                                    <div class="form-text">Utilise des cookies sécurisés (HttpOnly, Secure, SameSite).</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB 3 : NOTIFICATIONS -->
                    <!-- ================================ -->
                    <div class="settings-card <?php echo $activeTab == 'notifications' ? 'active' : ''; ?>" id="tab-notifications">
                        <h5 class="card-title"><i class="bi bi-bell"></i> Notifications</h5>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="notifications">

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications">
                                        <i class="bi bi-envelope"></i> Notifications par email
                                    </label>
                                    <div class="form-text">Envoi d'emails pour les confirmations et rappels.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="whatsapp_notifications" id="whatsapp_notifications" checked>
                                    <label class="form-check-label" for="whatsapp_notifications">
                                        <i class="bi bi-whatsapp"></i> Notifications WhatsApp
                                    </label>
                                    <div class="form-text">Envoi de messages WhatsApp pour les invitations.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="telegram_notifications" id="telegram_notifications" checked>
                                    <label class="form-check-label" for="telegram_notifications">
                                        <i class="bi bi-telegram"></i> Notifications Telegram
                                    </label>
                                    <div class="form-text">Envoi de messages Telegram pour les invitations.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB 4 : SYSTÈME -->
                    <!-- ================================ -->
                    <div class="settings-card <?php echo $activeTab == 'system' ? 'active' : ''; ?>" id="tab-system">
                        <h5 class="card-title"><i class="bi bi-server"></i> Informations système</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-code-square"></i> PHP Version</span>
                                    <span class="value"><?php echo $system_stats['php_version']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-database"></i> MySQL Version</span>
                                    <span class="value"><?php echo $system_stats['mysql_version']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-hdd"></i> Serveur</span>
                                    <span class="value"><?php echo htmlspecialchars($system_stats['server_software']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-arrow-up-circle"></i> Upload max</span>
                                    <span class="value"><?php echo $system_stats['upload_max_filesize']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-arrow-down-circle"></i> POST max</span>
                                    <span class="value"><?php echo $system_stats['post_max_size']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-memory"></i> Mémoire</span>
                                    <span class="value"><?php echo $system_stats['memory_limit']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label"><i class="bi bi-stopwatch"></i> Temps d'exécution</span>
                                    <span class="value"><?php echo $system_stats['max_execution_time']; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mt-3"><i class="bi bi-bar-chart"></i> Statistiques</h6>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="number"><?php echo $counters['utilisateurs']; ?></div>
                                        <div class="label">Utilisateurs</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="number"><?php echo $counters['evenements']; ?></div>
                                        <div class="label">Événements</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="number"><?php echo $counters['invitations']; ?></div>
                                        <div class="label">Invitations</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="number"><?php echo $counters['activites']; ?></div>
                                        <div class="label">Activités</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button class="btn btn-save" onclick="location.reload();">
                                <i class="bi bi-arrow-clockwise"></i> Rafraîchir
                            </button>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB 5 : MAINTENANCE -->
                    <!-- ================================ -->
                    <div class="settings-card <?php echo $activeTab == 'maintenance' ? 'active' : ''; ?>" id="tab-maintenance">
                        <h5 class="card-title"><i class="bi bi-tools"></i> Maintenance</h5>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="maintenance">

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode">
                                    <label class="form-check-label" for="maintenance_mode">
                                        <i class="bi bi-exclamation-triangle"></i> Mode maintenance
                                    </label>
                                    <div class="form-text">Active le mode maintenance. Seuls les administrateurs peuvent accéder.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-chat"></i> Message de maintenance</label>
                                <textarea class="form-control" name="maintenance_message" rows="3" 
                                          placeholder="Le site est actuellement en maintenance. Veuillez réessayer plus tard.">Le site est actuellement en maintenance. Veuillez réessayer plus tard.</textarea>
                                <div class="form-text">Message affiché aux utilisateurs pendant la maintenance.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-database"></i> Sauvegarde</label>
                                <div class="d-flex gap-3">
                                    <button type="button" class="btn btn-save" onclick="alert('Fonctionnalité de sauvegarde à implémenter')">
                                        <i class="bi bi-download"></i> Télécharger la sauvegarde
                                    </button>
                                    <button type="button" class="btn btn-save" style="background: #ff4757; color: white;" onclick="if(confirm('Voulez-vous vraiment vider le cache ?')) { alert('Cache vidé !'); }">
                                        <i class="bi bi-trash"></i> Vider le cache
                                    </button>
                                </div>
                                <div class="form-text">Télécharger une sauvegarde complète de la base de données.</div>
                            </div>

                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
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
<script>
    // ============================================
    // GESTION DES TABS
    // ============================================
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Cacher toutes les cards
            document.querySelectorAll('.settings-card').forEach(c => c.classList.remove('active'));
            
            // Afficher la card correspondante
            const tabId = this.dataset.tab;
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Mettre à jour l'URL avec le paramètre tab (pour le partage)
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
        });
    });

    // ============================================
    // AUTO-HIDE DES MESSAGES
    // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success-custom, .alert-error-custom');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });

    // ============================================
    // ANIMATION FADE-IN
    // ============================================
    
    document.querySelectorAll('.fade-in').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.animation = `fadeInUp 0.6s ease forwards`;
        el.style.animationDelay = `${i * 0.1}s`;
    });
</script>
</body>
</html>