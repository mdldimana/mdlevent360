<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// ⭐ Fix InfinityFree - définir BASE_PATH et APP_NAME si non définis
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

// Vérifier les permissions
requirePermission('tables.modifier');

// Récupérer les informations de l'utilisateur courant
$user   = getCurrentUser();
$userId = (int)getCurrentUserId();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================
// RÉCUPÉRATION DE LA TABLE
// ============================================

$table = null;
try {
    $stmt = $pdo->prepare("
        SELECT t.*, e.nom AS evenement_nom
        FROM tables t
        JOIN evenements e ON t.id_evenement = e.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération table (modifier) : ' . $e->getMessage());
}

if (!$table) {
    header('Location: index.php');
    exit;
}

// ⭐ Vérifier l'accès à l'événement — redirection vers 403.php
if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, (int)$table['id_evenement'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// ============================================
// TYPES ET ZONES
// ============================================

$types = ['RONDE', 'CARREE', 'RECTANGLE', 'OVALE', 'BARRIERE'];
$typeLabels = [
    'RONDE'     => 'Ronde ⭕',
    'CARREE'    => 'Carrée ⬛',
    'RECTANGLE' => 'Rectangulaire ▬',
    'OVALE'     => 'Ovale 🥚',
    'BARRIERE'  => 'Barrière 🚧'
];

$zones = ['TERRASSE', 'SALLE_PRINCIPALE', 'SALON', 'MEZZANINE', 'VIP', 'EXTERIEUR'];
$zoneLabels = [
    'TERRASSE'         => 'Terrasse 🌿',
    'SALLE_PRINCIPALE' => 'Salle principale 🏠',
    'SALON'            => 'Salon 🛋️',
    'MEZZANINE'        => 'Mezzanine 🏗️',
    'VIP'              => 'VIP ⭐',
    'EXTERIEUR'        => 'Extérieur 🌳'
];

// ============================================
// VARIABLES
// ============================================

$error         = '';
$nom           = $table['nom'];
$numero        = $table['numero'];
$capacite_min  = (int)$table['capacite_min'];
$capacite_max  = (int)$table['capacite_max'];
$type          = $table['type'];
$zone          = $table['zone'];
$position_x    = (int)$table['position_x'];
$position_y    = (int)$table['position_y'];

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom          = trim($_POST['nom'] ?? '');
    $numero       = trim($_POST['numero'] ?? '');
    $capacite_min = (int)($_POST['capacite_min'] ?? 1);
    $capacite_max = (int)($_POST['capacite_max'] ?? 4);
    $type         = $_POST['type'] ?? 'RECTANGLE';
    $zone         = $_POST['zone'] ?? 'SALLE_PRINCIPALE';
    $position_x   = (int)($_POST['position_x'] ?? 0);
    $position_y   = (int)($_POST['position_y'] ?? 0);

    // Validation
    $errors = [];

    if (empty($nom)) {
        $errors[] = 'Le nom de la table est requis.';
    } elseif (mb_strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (mb_strlen($nom) > 100) {
        $errors[] = 'Le nom ne doit pas dépasser 100 caractères.';
    }

    if (!empty($numero) && mb_strlen($numero) > 20) {
        $errors[] = 'Le numéro ne doit pas dépasser 20 caractères.';
    }

    if ($capacite_min < 1) {
        $errors[] = 'La capacité minimum doit être au moins 1.';
    }
    if ($capacite_max < $capacite_min) {
        $errors[] = 'La capacité maximum doit être supérieure ou égale à la capacité minimum.';
    }
    if ($capacite_max > 20) {
        $errors[] = 'La capacité maximum ne peut pas dépasser 20 personnes.';
    }

    if (!in_array($type, $types, true)) {
        $errors[] = 'Type de table invalide.';
    }
    if (!in_array($zone, $zones, true)) {
        $errors[] = 'Zone invalide.';
    }

    // Vérifier l'unicité du nom pour cet événement
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM tables
                WHERE id_evenement = ? AND nom = ? AND id != ?
            ");
            $stmt->execute([(int)$table['id_evenement'], $nom, $id]);
            if ($stmt->fetch()) {
                $errors[] = "Une table avec ce nom existe déjà pour cet événement.";
            }
        } catch (PDOException $e) {
            error_log('Erreur vérification unicité table (modifier) : ' . $e->getMessage());
            $errors[] = 'Erreur lors de la vérification d\'unicité.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE tables
                SET nom = ?, numero = ?, capacite_min = ?, capacite_max = ?,
                    type = ?, zone = ?, position_x = ?, position_y = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nom,
                $numero,
                $capacite_min,
                $capacite_max,
                $type,
                $zone,
                $position_x,
                $position_y,
                $id
            ]);

            // Journaliser
            if (function_exists('logAction')) {
                logAction(
                    $userId,
                    'UPDATE_TABLE',
                    'tables',
                    "Modification de la table '$nom' (ID: $id)"
                );
            }

            header('Location: index.php?evenement=' . (int)$table['id_evenement'] . '&success=modifie');
            exit;

        } catch (PDOException $e) {
            error_log('Erreur modification table : ' . $e->getMessage());
            $error = 'Erreur lors de la modification de la table. Veuillez réessayer.';
        }
    } else {
        $error = implode("\n", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une table - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow-x: hidden; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .app-wrapper { display: flex; min-height: 100vh; width: 100%; }
        .sidebar-wrapper { flex-shrink: 0; width: 260px; min-height: 100vh; position: sticky; top: 0; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; min-height: 100vh; overflow-y: auto; padding: 0; min-width: 0; }
        .main-content::-webkit-scrollbar { width: 6px; }
        .main-content::-webkit-scrollbar-track { background: #f8f5f2; }
        .main-content::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c17c60, #d4a574); border-radius: 10px; }

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
            z-index: 50;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-bar .page-title h4 { font-weight: 700; color: #1a1a1a; margin: 0; font-size: 20px; }
        .top-bar .page-title h4 i { color: #c17c60; margin-right: 10px; }
        .top-bar .page-title small { color: #9a8a7f; font-size: 12px; display: block; margin-top: 2px; }
        .top-bar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .top-bar .user-info .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 16px;
            box-shadow: 0 5px 15px rgba(193, 124, 96, 0.3);
            flex-shrink: 0;
        }
        .top-bar .user-info .user-name { font-weight: 600; color: #1a1a1a; font-size: 13px; }
        .top-bar .user-info .user-name small { display: block; color: #b8a99c; font-weight: 400; font-size: 11px; }
        .top-bar .user-info .role-badge {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white; padding: 4px 12px; border-radius: 20px;
            font-size: 10px; font-weight: 700; white-space: nowrap;
        }

        /* ========== SIDEBAR TOGGLE ========== */
        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 200;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border: none;
            border-radius: 12px;
            padding: 8px 12px;
            box-shadow: 0 5px 20px rgba(193, 124, 96, 0.35);
            font-size: 20px;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
        }
        .sidebar-toggle-btn:hover { transform: scale(1.05); box-shadow: 0 8px 30px rgba(193, 124, 96, 0.45); }
        .sidebar-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);
            z-index: 150; opacity: 0; transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* ========== CONTENT ========== */
        .content-section { padding: 25px 30px; }

        .form-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 900px;
            margin: 0 auto;
        }
        .form-container .form-title {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-container .form-title i { color: #c17c60; }

        /* ========== FORMULAIRES ========== */
        .form-label {
            font-weight: 600;
            color: #6a5a4a;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-label i { color: #c17c60; margin-right: 6px; }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.8);
            color: #1a1a1a;
        }
        .form-control:focus, .form-select:focus {
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.08);
            background: white;
        }
        .form-text { font-size: 12px; color: #9a8a7f; margin-top: 4px; }

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
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.95); color: #c17c60; }

        /* ========== ALERTES ========== */
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
        .alert-error i { color: #dc2626; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
        .alert-error ul { margin: 0; padding-left: 18px; }
        .alert-error li { margin-bottom: 4px; }
        .alert-error li:last-child { margin-bottom: 0; }

        /* ========== ⭐ SECTION ÉVÉNEMENT ========== */
        .event-info-container {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
            border: 2px solid rgba(193, 124, 96, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .event-info-container .icon-badge {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(193, 124, 96, 0.25);
        }
        .event-info-container .event-text {
            flex: 1;
            min-width: 200px;
        }
        .event-info-container .event-text small {
            display: block;
            color: #9a8a7f;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .event-info-container .event-text h5 {
            margin: 0;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 16px;
        }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #b8a99c;
            font-size: 13px;
        }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== ANIMATIONS ========== */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        @media (prefers-reduced-motion: reduce) {
            .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .sidebar-toggle-btn { display: flex !important; align-items: center; justify-content: center; }
            .app-wrapper { display: block; width: 100%; }
            .main-content, body.sidebar-open .main-content {
                width: 100% !important; min-width: 0 !important; margin-left: 0 !important;
                transform: none !important; filter: none !important; opacity: 1 !important;
            }
            .sidebar-wrapper {
                position: fixed !important; top: 0 !important; left: 0 !important;
                width: min(280px, 85vw) !important; height: 100dvh !important;
                margin: 0 !important; transform: translate3d(-105%, 0, 0);
                transition: transform 0.28s ease !important; z-index: 2000 !important;
                overflow-y: auto; overflow-x: hidden; border-radius: 0 18px 18px 0;
            }
            .sidebar-wrapper.open { transform: translate3d(0, 0, 0) !important; }
            .sidebar-overlay {
                position: fixed !important; inset: 0 !important;
                display: block !important; visibility: hidden; opacity: 0;
                background: rgba(0, 0, 0, 0.5) !important;
                pointer-events: none;
                transition: opacity 0.28s ease, visibility 0.28s ease;
                z-index: 1900 !important;
            }
            .sidebar-overlay.active { visibility: visible; opacity: 1; pointer-events: auto; }
            .top-bar { padding: 12px 15px 12px 70px; flex-direction: row; flex-wrap: wrap; }
            body.sidebar-open { overflow-x: hidden !important; overflow-y: auto !important; }
            .content-section { padding: 15px; }
            .top-bar .page-title h4 { font-size: 1rem; }
            .top-bar .user-info .user-name { display: none; }
            .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
            .form-container { padding: 20px; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 12px; }
            .form-container { padding: 15px; }
            .form-container .form-title { font-size: 15px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .btn-save, .btn-cancel { width: 100%; justify-content: center; padding: 10px 16px; font-size: 13px; }
            .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
            .event-info-container { padding: 15px; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">

    <div class="sidebar-wrapper" id="sidebarWrapper">
        <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-pencil-fill"></i> Modifier une table</h4>
                <small><i class="bi bi-table"></i> <?php echo htmlspecialchars($table['nom']); ?> — <?php echo htmlspecialchars($table['evenement_nom']); ?></small>
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
                    $userInitiales = strtoupper(
                        substr($user['prenom'] ?? 'U', 0, 1) .
                        substr($user['nom'] ?? 'N', 0, 1)
                    );
                    echo $userInitiales ?: 'U';
                    ?>
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="form-container fade-in">

                <h5 class="form-title"><i class="bi bi-pencil-fill"></i> Modifier — <?php echo htmlspecialchars($table['nom']); ?></h5>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <?php
                            $errorLines = array_filter(array_map('trim', explode("\n", $error)));
                            if (count($errorLines) > 1):
                            ?>
                                <strong>Veuillez corriger les erreurs suivantes :</strong>
                                <ul class="mt-2">
                                    <?php foreach ($errorLines as $line): ?>
                                        <li><?php echo htmlspecialchars($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php echo htmlspecialchars($errorLines[0] ?? $error); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ⭐ INFO ÉVÉNEMENT -->
                <div class="event-info-container">
                    <div class="icon-badge">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div class="event-text">
                        <small>Événement associé</small>
                        <h5><?php echo htmlspecialchars($table['evenement_nom']); ?></h5>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><i class="bi bi-tag-fill"></i> Nom de la table <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>"
                                   required maxlength="100">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-hash"></i> Numéro</label>
                            <input type="text" class="form-control" name="numero" value="<?php echo htmlspecialchars($numero); ?>"
                                   maxlength="20">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-people-fill"></i> Capacité minimum</label>
                            <input type="number" class="form-control" name="capacite_min" value="<?php echo $capacite_min; ?>"
                                   min="1" max="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-people-fill"></i> Capacité maximum <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="capacite_max" value="<?php echo $capacite_max; ?>"
                                   min="1" max="20" required>
                            <div class="form-text">Nombre maximum de personnes à cette table.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-shapes"></i> Type de table</label>
                            <select class="form-select" name="type">
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $type == $t ? 'selected' : ''; ?>>
                                        <?php echo $typeLabels[$t] ?? $t; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-geo-alt-fill"></i> Zone</label>
                            <select class="form-select" name="zone">
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?php echo $z; ?>" <?php echo $zone == $z ? 'selected' : ''; ?>>
                                        <?php echo $zoneLabels[$z] ?? $z; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-arrow-right"></i> Position X</label>
                            <input type="number" class="form-control" name="position_x" value="<?php echo $position_x; ?>">
                            <div class="form-text">Position horizontale sur le plan (en pixels).</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-arrow-down"></i> Position Y</label>
                            <input type="number" class="form-control" name="position_y" value="<?php echo $position_y; ?>">
                            <div class="form-text">Position verticale sur le plan (en pixels).</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-save-fill"></i> Enregistrer les modifications
                        </button>
                        <a href="index.php?evenement=<?php echo (int)$table['id_evenement']; ?>" class="btn btn-cancel">
                            <i class="bi bi-arrow-left"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>

            <div class="app-footer">
                <i class="bi bi-heart-fill"></i>
                <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ========== SIDEBAR MOBILE ==========
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarWrapper = document.getElementById('sidebarWrapper');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebarWrapper.classList.add('open');
    sidebarOverlay.classList.add('active');
    document.body.classList.add('sidebar-open');
}
function closeSidebar() {
    sidebarWrapper.classList.remove('open');
    sidebarOverlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebarWrapper.classList.contains('open')) closeSidebar();
        else openSidebar();
    });
}
if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebarWrapper.classList.contains('open')) closeSidebar();
});

document.querySelectorAll('.sidebar-wrapper .nav-link:not([data-bs-toggle="collapse"])').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) closeSidebar();
    });
});

window.addEventListener('resize', function () {
    if (window.innerWidth > 992) closeSidebar();
});
</script>
</body>
</html>