<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// ⭐ Fix InfinityFree - définir BASE_PATH et APP_NAME si non définis
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $projectFolder);
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Gestion Invitations');
}

// Vérifier les permissions
requirePermission('boissons.creer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// VARIABLES
// ============================================

$error = '';
$nom = '';
$description = '';
$type = 'SANS_ALCOOL';
$actif = 1;

// ============================================
// TYPES DE BOISSONS
// ============================================

$types = ['SANS_ALCOOL', 'ALCOOL', 'CHAUD', 'AUTRE'];
$typeLabels = [
    'SANS_ALCOOL' => 'Sans alcool 🧃',
    'ALCOOL' => 'Alcool 🍷',
    'CHAUD' => 'Chaud ☕',
    'AUTRE' => 'Autre 🍹'
];

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'SANS_ALCOOL';
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];

    if (empty($nom)) $errors[] = 'Le nom de la boisson est requis';
    if (strlen($nom) < 2) $errors[] = 'Le nom doit contenir au moins 2 caractères';
    if (!in_array($type, $types)) $errors[] = 'Type invalide';

    // Vérifier l'unicité du nom
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM boissons WHERE nom = ?");
            $stmt->execute([$nom]);
            if ($stmt->fetch()) {
                $errors[] = "Une boisson avec ce nom existe déjà";
            }
        } catch (PDOException $e) {
            error_log('Erreur vérif unicité: ' . $e->getMessage());
            $errors[] = 'Erreur lors de la vérification';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO boissons (nom, description, type, actif, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nom, $description, $type, $actif]);
            $boissonId = (int)$pdo->lastInsertId();

            if (function_exists('logAction')) {
                logAction($user['id'], 'CREATE_DRINK', 'boissons', 
                          "Création de la boisson '$nom' (ID: $boissonId)");
            }

            header('Location: index.php?success=ajoute');
            exit;

        } catch (PDOException $e) {
            error_log('Erreur création boisson: ' . $e->getMessage());
            $error = 'Erreur lors de la création : ' . $e->getMessage();
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
    <title>Ajouter une boisson - <?php echo APP_NAME; ?></title>
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
            background: rgba(255, 255, 255, 0.95);
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
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 150;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* ========== CONTENT ========== */
        .content-section { padding: 25px 30px; }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 700px;
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

        /* ========== BANNIÈRE INFO ========== */
        .user-info-banner {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.08), rgba(212, 165, 116, 0.08));
            border: 1px solid rgba(193, 124, 96, 0.2);
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #6a5a4a;
        }
        .user-info-banner i { color: #c17c60; font-size: 18px; flex-shrink: 0; }
        .user-info-banner strong { color: #c17c60; }

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

        /* ========== SWITCH ========== */
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
        }
        .alert-error i { color: #dc2626; font-size: 18px; flex-shrink: 0; }

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
                overflow-y: auto; overflow-x: hidden;
                border-radius: 0 18px 18px 0;
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
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
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
                <h4><i class="bi bi-plus-circle-fill"></i> Ajouter une boisson</h4>
                <small><i class="bi bi-cup-straw"></i> Créer une nouvelle boisson</small>
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

                <h5 class="form-title"><i class="bi bi-plus-circle-fill"></i> Nouvelle boisson</h5>

                <!-- ⭐ BANNIÈRE EXPLICATIVE -->
                <div class="user-info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Les boissons sont créées dans le <strong>catalogue général</strong>. 
                        Vous pourrez ensuite les <strong>associer à vos événements</strong> lors de leur création ou modification.
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tag-fill"></i> Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>" 
                               placeholder="Ex: Coca-Cola, Fanta, Eau minérale..." required>
                        <div class="form-text">Le nom apparaîtra dans le catalogue des boissons disponibles.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-align-left"></i> Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Description de la boisson (optionnel)"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tags-fill"></i> Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="type">
                            <?php foreach ($types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $type == $t ? 'selected' : ''; ?>>
                                    <?php echo $typeLabels[$t] ?? $t; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Catégorie de la boisson pour faciliter le tri et la recherche.</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" <?php echo $actif ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="actif">
                                <i class="bi bi-check-circle-fill" style="color: #c17c60;"></i> Boisson active
                            </label>
                        </div>
                        <div class="form-text">Les boissons inactives ne pourront pas être associées aux événements.</div>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                        <a href="index.php" class="btn-cancel">
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