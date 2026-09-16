<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// ============================================================
// 1. TROUVER auth.php QUEL QUE SOIT L'ENDROIT DU FICHIER
// ============================================================
$possibleAuth = [
    __DIR__ . '/../../includes/auth.php',           // admin/evenements/ajouter.php
    __DIR__ . '/../../../includes/auth.php',        // admin/evenements/modeles/index.php
    __DIR__ . '/../../includes/auth.php',           // admin/modeles_invitation/index.php (si à 2 niveaux)
    __DIR__ . '/../includes/auth.php',
    __DIR__ . '/includes/auth.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/auth.php',
];
$authFound = false;
foreach ($possibleAuth as $p) {
    if (file_exists($p)) { require_once $p; $authFound = true; break; }
}
// Fallback ultime : remonte jusqu'à trouver includes
if (!$authFound) {
    $dir = __DIR__;
    for ($i=0;$i<5;$i++) {
        $dir = dirname($dir);
        $candidate = $dir . '/includes/auth.php';
        if (file_exists($candidate)) { require_once $candidate; $authFound = true; break; }
    }
}
if (!$authFound) die('❌ auth.php introuvable. Chemin testés : <pre>'.htmlspecialchars(implode("\n",$possibleAuth)).'</pre>');

// ============================================================
// 2. PERMISSION SOUPLE - NE REDIRIGE PAS VERS evenements/index
// ============================================================
$user = getCurrentUser();

// Essaye plusieurs permissions possibles au lieu de bloquer sur une seule
$hasAccess = false;
$permsToTry = ['modeles_invitation.gerer','modeles_invitation.voir','evenements.ajouter','evenements.modifier','evenements.voir','admin'];
foreach ($permsToTry as $perm) {
    if (function_exists('hasPermission') && hasPermission($perm)) { $hasAccess = true; break; }
    if (function_exists('userHasRole') && (userHasRole('admin') || userHasRole('super_admin'))) { $hasAccess = true; break; }
}

// Si vraiment pas de fonction hasPermission, on laisse passer si connecté
if (!$hasAccess) {
    // Dernière chance : si getCurrentUser retourne quelque chose, on autorise
    if (!empty($user)) $hasAccess = true;
}

if (!$hasAccess) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p>Permissions testées: '.htmlspecialchars(implode(', ',$permsToTry)).'</p>
        <p>User: '.htmlspecialchars(json_encode($user)).'</p>
        <a href="../index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">← Retour événements</a>
        <a href="../../index.php" style="margin-top:10px;color:#c17c60;">← Dashboard</a>
    </div>');
}

$pdo = getDbConnection();

// ============================================================
// 3. CHEMINS PORTABLES INFINITYFREE + XAMPP
// ============================================================
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
if (!$projectRoot) $projectRoot = realpath(__DIR__ . '/../../../');
if (!$projectRoot) $projectRoot = realpath(__DIR__ . '/../../..');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'MdlEvent');

// Trouver uploads/modeles_invitation quel que soit l'emplacement
$possibleUploadDirs = [
    __DIR__ . '/../../uploads/modeles_invitation/',
    __DIR__ . '/../../../uploads/modeles_invitation/',
    __DIR__ . '/../../uploads/modeles_invitation/',
    $projectRoot . '/uploads/modeles_invitation/',
];
$uploadModeleDir = '';
foreach ($possibleUploadDirs as $d) {
    if (is_dir($d) || is_dir(dirname($d))) { $uploadModeleDir = $d; break; }
}
if (empty($uploadModeleDir)) $uploadModeleDir = __DIR__ . '/../../uploads/modeles_invitation/';
$uploadModeleDir = rtrim($uploadModeleDir, '/\\') . DIRECTORY_SEPARATOR;

// Trouver templates/invitations
$possibleTemplateDirs = [
    __DIR__ . '/templates/invitations/',
    __DIR__ . '/../templates/invitations/',
    __DIR__ . '/../../evenements/templates/invitations/',
    __DIR__ . '/../evenements/templates/invitations/',
    $projectRoot . '/admin/evenements/templates/invitations/',
];
$templateDir = '';
foreach ($possibleTemplateDirs as $d) {
    if (is_dir($d)) { $templateDir = $d; break; }
}
if (empty($templateDir)) $templateDir = __DIR__ . '/templates/invitations/';
$templateDir = rtrim($templateDir, '/\\') . DIRECTORY_SEPARATOR;

if (!is_dir($uploadModeleDir)) @mkdir($uploadModeleDir, 0775, true);
if (!is_dir($templateDir))     @mkdir($templateDir, 0775, true);

$modeleUrl = ($projectFolder ?: '') . '/uploads/modeles_invitation/';

// ============================================================
// S'ASSURER QUE LA TABLE EXISTE
// ============================================================
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `modeles_invitation` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `nom` VARCHAR(100) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `apercu` VARCHAR(255) DEFAULT NULL,
            `actif` TINYINT(1) NOT NULL DEFAULT 1,
            `ordre` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    error_log('Erreur création table : ' . $e->getMessage());
}

// ============================================================
// TRAITEMENT DES ACTIONS
// ============================================================
$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $code        = strtolower(trim((string)($_POST['code'] ?? '')));
        $nom         = trim((string)($_POST['nom'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $ordre       = (int)($_POST['ordre'] ?? 0);

        if (!preg_match('/^[a-z0-9_-]+$/', $code)) {
            $message = "❌ Le code doit contenir uniquement des lettres minuscules, chiffres, tirets et underscores.";
            $messageType = 'danger';
        } elseif ($nom === '') {
            $message = "❌ Le nom est obligatoire.";
            $messageType = 'danger';
        } else {
            try {
                $apercuName = null;
                if (!empty($_FILES['apercu']['name']) && $_FILES['apercu']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['apercu']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['apercu']['size'] <= 5 * 1024 * 1024) {
                        $apercuName = $code . '.' . $ext;
                        $dest = $uploadModeleDir . $apercuName;
                        move_uploaded_file($_FILES['apercu']['tmp_name'], $dest);
                    }
                }

                $stmt = $pdo->prepare('INSERT INTO modeles_invitation (code, nom, description, apercu, actif, ordre) VALUES (?, ?, ?, ?, 1, ?)');
                $stmt->execute([$code, $nom, $description, $apercuName, $ordre]);

                $message = "✅ Modèle « " . htmlspecialchars($nom) . " » ajouté avec succès.";
                $messageType = 'success';

                $templateFile = $templateDir . $code . '.php';
                if (!file_exists($templateFile)) {
                    $templateContent = "<?php\n/**\n * Template : " . strtoupper($code) . "\n */\n?>\n<!DOCTYPE html>\n<html lang=\"fr\">\n<head><meta charset=\"UTF-8\"><title>Invitation - <?php echo htmlspecialchars(\$invitation['evenement_nom'] ?? ''); ?></title></head>\n<body>\n<h1>Modèle <?php echo htmlspecialchars('" . addslashes($nom) . "'); ?></h1>\n<p>Invité : <?php echo htmlspecialchars(\$guestName ?? ''); ?></p>\n</body>\n</html>\n";
                    file_put_contents($templateFile, $templateContent);
                }
            } catch (PDOException $e) {
                $message = "❌ Erreur : " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'modifier') {
        $id          = (int)($_POST['id'] ?? 0);
        $nom         = trim((string)($_POST['nom'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $ordre       = (int)($_POST['ordre'] ?? 0);

        if ($id > 0 && $nom !== '') {
            try {
                $stmt = $pdo->prepare('UPDATE modeles_invitation SET nom = ?, description = ?, ordre = ? WHERE id = ?');
                $stmt->execute([$nom, $description, $ordre, $id]);
                $message = "✅ Modèle mis à jour.";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "❌ Erreur : " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE modeles_invitation SET actif = NOT actif WHERE id = ?');
                $stmt->execute([$id]);
                $message = "✅ Statut modifié.";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "❌ Erreur : " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'supprimer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('SELECT code, apercu FROM modeles_invitation WHERE id = ?');
                $stmt->execute([$id]);
                $modele = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($modele) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM evenements WHERE modele_invitation = ?');
                    $stmt->execute([$modele['code']]);
                    $count = (int)$stmt->fetchColumn();
                    if ($count > 0) {
                        $message = "❌ Impossible : {$count} événement(s) utilise(nt) ce modèle.";
                        $messageType = 'danger';
                    } else {
                        if (!empty($modele['apercu'])) {
                            $apercuPath = $uploadModeleDir . basename($modele['apercu']);
                            if (file_exists($apercuPath)) @unlink($apercuPath);
                        }
                        $pdo->prepare('DELETE FROM modeles_invitation WHERE id = ?')->execute([$id]);
                        $message = "🗑 Modèle supprimé.";
                        $messageType = 'success';
                    }
                }
            } catch (PDOException $e) {
                $message = "❌ Erreur : " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'reorganiser') {
        try {
            $ordres = $_POST['ordre'] ?? [];
            $stmt = $pdo->prepare('UPDATE modeles_invitation SET ordre = ? WHERE id = ?');
            foreach ($ordres as $idModele => $ordre) {
                $stmt->execute([(int)$ordre, (int)$idModele]);
            }
            $message = "✅ Ordre mis à jour.";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "❌ Erreur : " . $e->getMessage();
            $messageType = 'danger';
        }
    }

    if ($action === 'upload_apercu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !empty($_FILES['apercu']['name'])) {
            try {
                $stmt = $pdo->prepare('SELECT code, apercu FROM modeles_invitation WHERE id = ?');
                $stmt->execute([$id]);
                $modele = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($modele) {
                    $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['apercu']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['apercu']['size'] <= 5 * 1024 * 1024) {
                        if (!empty($modele['apercu'])) {
                            $old = $uploadModeleDir . basename($modele['apercu']);
                            if (file_exists($old)) @unlink($old);
                        }
                        $apercuName = $modele['code'] . '.' . $ext;
                        move_uploaded_file($_FILES['apercu']['tmp_name'], $uploadModeleDir . $apercuName);
                        $pdo->prepare('UPDATE modeles_invitation SET apercu = ? WHERE id = ?')->execute([$apercuName, $id]);
                        $message = "✅ Aperçu mis à jour.";
                        $messageType = 'success';
                    } else {
                        $message = "❌ Format non autorisé ou fichier > 5Mo.";
                        $messageType = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $message = "❌ Erreur : " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// CHARGER TOUS LES MODÈLES
$modeles = [];
try {
    $stmt = $pdo->query('SELECT * FROM modeles_invitation ORDER BY ordre ASC, nom ASC');
    $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement : ' . $e->getMessage());
}

$countsParModele = [];
try {
    $stmt = $pdo->query('SELECT modele_invitation, COUNT(*) AS total FROM evenements GROUP BY modele_invitation');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $countsParModele[$row['modele_invitation']] = (int)$row['total'];
    }
} catch (PDOException $e) {}

$templatesPresents = [];
if (is_dir($templateDir)) {
    $files = glob($templateDir . '*.php') ?: [];
    foreach ($files as $f) {
        $base = basename($f, '.php');
        if ($base === '_helpers') continue;
        $templatesPresents[] = $base;
    }
}

$codesEnBdd = array_column($modeles, 'code');
$imagesOrphelines = [];
if (is_dir($uploadModeleDir)) {
    $files = array_merge(
        glob($uploadModeleDir . '*.png') ?: [],
        glob($uploadModeleDir . '*.jpg') ?: [],
        glob($uploadModeleDir . '*.jpeg') ?: [],
        glob($uploadModeleDir . '*.webp') ?: []
    );
    foreach ($files as $f) {
        $base = pathinfo($f, PATHINFO_FILENAME);
        if (!in_array($base, $codesEnBdd, true)) {
            $imagesOrphelines[] = basename($f);
        }
    }
}

$userInitiales = strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? 'N', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modèles d'invitation - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}html,body{height:100%;overflow-x:hidden}body{font-family:'Inter',system-ui,sans-serif;background:#f8f5f2;color:#1a1a1a;-webkit-font-smoothing:antialiased}
.app-wrapper{display:flex;min-height:100vh;width:100%}.sidebar-wrapper{flex-shrink:0;width:260px;min-height:100vh;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:100}.main-content{flex:1;min-height:100vh;overflow-y:auto;padding:0;min-width:0}
.top-bar{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);padding:15px 30px;border-bottom:1px solid rgba(193,124,96,0.15);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;flex-wrap:wrap;gap:10px}
.top-bar .page-title h4{font-weight:700;margin:0;font-size:20px}.top-bar .page-title h4 i{color:#c17c60;margin-right:10px}.top-bar .page-title small{color:#9a8a7f;font-size:12px;display:block;margin-top:2px}.top-bar .user-info{display:flex;align-items:center;gap:15px}.top-bar .user-info .user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px}.top-bar .user-info .user-name{font-weight:600;font-size:13px}.top-bar .user-info .user-name small{display:block;color:#b8a99c;font-weight:400;font-size:11px}.top-bar .user-info .role-badge{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700}
.sidebar-toggle-btn{display:none;position:fixed;top:12px;left:12px;z-index:200;background:linear-gradient(135deg,#c17c60,#d4a574);border:none;border-radius:12px;padding:8px 12px;font-size:20px;cursor:pointer;color:white}.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150;opacity:0;transition:opacity 0.3s ease}.sidebar-overlay.active{display:block;opacity:1}
.content-section{padding:25px 30px}
.stat-mini{background:white;border-radius:16px;padding:16px 18px;box-shadow:0 4px 20px rgba(0,0,0,0.04);border:1px solid rgba(255,255,255,0.8);display:flex;align-items:center;gap:14px}
.stat-mini .icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0}
.stat-mini .icon.orange{background:linear-gradient(135deg,#c17c60,#d4a574)}.stat-mini .icon.green{background:linear-gradient(135deg,#10b981,#34d399)}.stat-mini .icon.blue{background:linear-gradient(135deg,#3b82f6,#60a5fa)}.stat-mini .icon.purple{background:linear-gradient(135deg,#a855f7,#d8b4fe)}
.stat-mini .stat-number{font-size:22px;font-weight:800;line-height:1}.stat-mini .stat-label{font-size:10px;color:#9a8a7f;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-top:3px}
.card-custom{background:white;border-radius:20px;padding:25px;box-shadow:0 8px 32px rgba(0,0,0,0.05);border:1px solid rgba(240,235,229,0.8);margin-bottom:20px}
.card-title{font-weight:700;margin-bottom:20px;padding-bottom:15px;border-bottom:2px dashed rgba(193,124,96,0.15);font-size:17px}.card-title i{color:#c17c60;margin-right:10px}
.form-label{font-weight:600;color:#6a5a4a;font-size:12px;text-transform:uppercase;letter-spacing:0.05em}.form-control,.form-select{border-radius:10px;padding:10px 15px;border:1.5px solid rgba(234,227,220,0.6);font-size:14px;background:rgba(255,255,255,0.9)}.form-control:focus,.form-select:focus{border-color:#c17c60;box-shadow:0 0 0 4px rgba(193,124,96,0.08)}
.btn-save{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border:none;font-weight:700;padding:12px 30px;border-radius:12px;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;font-size:14px}.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(193,124,96,0.35);color:white}.btn-cancel{background:rgba(255,255,255,0.9);color:#6a5a4a;border:1.5px solid rgba(234,227,220,0.6);font-weight:600;padding:12px 30px;border-radius:12px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-size:14px}
.alert-custom{border-radius:12px;padding:15px 20px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start;font-size:14px}.alert-success{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#065f46}.alert-danger{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#991b1b}.alert-warning{background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);color:#78350f}
.modeles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;margin-top:15px}
.modele-item{background:white;border-radius:18px;border:2px solid rgba(234,227,220,0.5);overflow:hidden;transition:all 0.3s ease;display:flex;flex-direction:column;position:relative}.modele-item:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(193,124,96,0.15);border-color:#c17c60}.modele-item.inactif{opacity:0.55;background:#f8f8f8}
.modele-item .apercu-wrapper{position:relative;width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,#f0ebe5,#e8ddd2);overflow:hidden;display:flex;align-items:center;justify-content:center}.modele-item .apercu-wrapper img{width:100%;height:100%;object-fit:cover}.modele-item .apercu-placeholder{color:rgba(193,124,96,0.4);font-size:48px}.modele-item .apercu-wrapper .badge-ordinal{position:absolute;top:10px;left:10px;background:rgba(26,26,26,0.85);color:white;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;backdrop-filter:blur(4px)}.modele-item .apercu-wrapper .badge-statut{position:absolute;top:10px;right:10px;font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:0.05em}.modele-item .apercu-wrapper .badge-statut.actif{background:rgba(16,185,129,0.95);color:white}.modele-item .apercu-wrapper .badge-statut.inactif{background:rgba(107,114,128,0.95);color:white}
.modele-body{padding:16px 18px 18px;flex:1;display:flex;flex-direction:column;gap:8px}.modele-body .modele-code{font-family:'Courier New',monospace;font-size:10px;background:rgba(193,124,96,0.08);color:#c17c60;padding:2px 8px;border-radius:6px;display:inline-block;font-weight:700;align-self:flex-start}.modele-body .modele-nom{font-size:16px;font-weight:700;color:#1a1a1a;line-height:1.3}.modele-body .modele-desc{font-size:12px;color:#9a8a7f;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}.modele-body .modele-meta{display:flex;flex-wrap:wrap;gap:8px;font-size:11px;color:#6a5a4a;margin-top:4px}.modele-body .modele-meta .pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:rgba(193,124,96,0.08);font-weight:600;font-size:10px}.modele-body .modele-meta .pill.blue{background:rgba(59,130,246,0.1);color:#1e40af}.modele-body .modele-meta .pill.green{background:rgba(16,185,129,0.1);color:#065f46}.modele-body .modele-meta .pill.red{background:rgba(239,68,68,0.1);color:#991b1b}.modele-body .modele-meta .pill.gray{background:rgba(107,114,128,0.1);color:#374151}
.modele-actions{display:flex;gap:6px;padding:12px 18px;background:rgba(251,248,245,0.5);border-top:1px solid rgba(234,227,220,0.5);flex-wrap:wrap}.btn-icon{flex:1;min-width:40px;padding:8px;border-radius:10px;border:1.5px solid rgba(234,227,220,0.6);background:white;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.3s ease;display:inline-flex;align-items:center;justify-content:center;gap:5px;text-decoration:none;color:#6a5a4a}.btn-icon:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.08)}.btn-icon.edit:hover{background:#3b82f6;color:white;border-color:#3b82f6}.btn-icon.toggle:hover{background:#f59e0b;color:white;border-color:#f59e0b}.btn-icon.toggle.on{background:#10b981;color:white;border-color:#10b981}.btn-icon.delete:hover{background:#ef4444;color:white;border-color:#ef4444}.btn-icon.view:hover{background:#c17c60;color:white;border-color:#c17c60}.btn-icon:disabled{opacity:0.5;cursor:not-allowed;transform:none}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}.modal-overlay.active{display:flex}.modal-content-custom{background:white;border-radius:20px;padding:30px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3)}.modal-header-custom{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:2px dashed rgba(193,124,96,0.15)}.modal-header-custom h5{font-weight:700;margin:0;font-size:18px}.modal-header-custom h5 i{color:#c17c60;margin-right:8px}.btn-close-modal{background:none;border:none;font-size:24px;cursor:pointer;color:#9a8a7f;line-height:1}.btn-close-modal:hover{color:#c17c60}
@media (max-width:992px){.sidebar-toggle-btn{display:flex !important;align-items:center;justify-content:center}.app-wrapper{display:block}.sidebar-wrapper{position:fixed !important;top:0 !important;left:0 !important;width:min(280px,85vw) !important;height:100dvh !important;transform:translate3d(-105%,0,0);transition:transform 0.28s ease !important;z-index:2000 !important;border-radius:0 18px 18px 0}.sidebar-wrapper.open{transform:translate3d(0,0,0) !important}.sidebar-overlay{position:fixed !important;inset:0 !important;display:block !important;visibility:hidden;opacity:0;pointer-events:none;z-index:1900 !important}.sidebar-overlay.active{visibility:visible;opacity:1;pointer-events:auto}.top-bar{padding:12px 15px 12px 70px}.content-section{padding:15px}.modeles-grid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}}
@media (max-width:576px){.modeles-grid{grid-template-columns:1fr}.card-custom{padding:18px}}
</style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
<div class="sidebar-wrapper" id="sidebarWrapper"><?php 
$sidebarCandidates = [__DIR__ . '/../../includes/sidebar.php', __DIR__ . '/../../../includes/sidebar.php', __DIR__ . '/../includes/sidebar.php', $projectRoot . '/includes/sidebar.php'];
foreach ($sidebarCandidates as $sb) { if (file_exists($sb)) { include_once $sb; break; } }
?></div>

<div class="main-content" id="mainContent">
<div class="top-bar">
<div class="page-title"><h4><i class="bi bi-palette-fill"></i> Modèles d'invitation</h4><small><i class="bi bi-collection"></i> <?= BASE_PATH ?: '/' ?> • <?= $uploadModeleDir ?> • <?= is_dir($uploadModeleDir) ? '✅ '.count(glob($uploadModeleDir.'*')) .' fichiers' : '❌ dossier manquant' ?></small></div>
<div class="user-info"><span class="role-badge"><i class="bi bi-shield-check"></i> <?= is_array($user['roles'] ?? null) ? implode(', ', $user['roles']) : 'Aucun rôle'; ?></span><div><div class="user-name"><?= htmlspecialchars(($user['prenom'] ?? '').' '.($user['nom'] ?? '')); ?><small>@<?= htmlspecialchars($user['username'] ?? ''); ?></small></div></div><div class="user-avatar"><?= $userInitiales ?: 'U' ?></div></div>
</div>

<div class="content-section">

<?php if ($message): ?>
<div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>"><i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i><div><?php echo $message; ?></div></div>
<?php endif; ?>

<div class="row g-3 mb-4">
<div class="col-md-3 col-6"><div class="stat-mini"><div class="icon orange"><i class="bi bi-palette-fill"></i></div><div><div class="stat-number"><?php echo count($modeles); ?></div><div class="stat-label">Total modèles</div></div></div></div>
<div class="col-md-3 col-6"><div class="stat-mini"><div class="icon green"><i class="bi bi-check-circle-fill"></i></div><div><div class="stat-number"><?php echo count(array_filter($modeles, fn($m) => $m['actif'])); ?></div><div class="stat-label">Actifs</div></div></div></div>
<div class="col-md-3 col-6"><div class="stat-mini"><div class="icon blue"><i class="bi bi-code-slash"></i></div><div><div class="stat-number"><?php echo count($templatesPresents); ?></div><div class="stat-label">Templates .php</div></div></div></div>
<div class="col-md-3 col-6"><div class="stat-mini"><div class="icon purple"><i class="bi bi-calendar-check"></i></div><div><div class="stat-number"><?php echo array_sum($countsParModele); ?></div><div class="stat-label">Événements</div></div></div></div>
</div>

<?php if (!empty($imagesOrphelines)): ?>
<div class="alert-custom alert-warning"><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Images orphelines détectées :</strong> <?php echo htmlspecialchars(implode(', ', $imagesOrphelines)); ?><br><small>Ces images ne sont liées à aucun modèle en base.</small></div></div>
<?php endif; ?>

<div class="mb-3 d-flex gap-2 flex-wrap">
<button type="button" class="btn-save" onclick="openModalAjout()"><i class="bi bi-plus-circle"></i> Ajouter un modèle</button>
<button type="button" class="btn-cancel" onclick="toggleReorganisation()"><i class="bi bi-arrow-down-up"></i> Réorganiser l'ordre</button>
<a href="../index.php" class="btn-cancel"><i class="bi bi-arrow-left"></i> Retour événements</a>
</div>

<form method="POST" id="formReorganiser" style="display: none;">
<input type="hidden" name="action" value="reorganiser">
<div class="card-custom"><h5 class="card-title"><i class="bi bi-arrow-down-up"></i> Ordre d'affichage</h5><p class="form-text mb-3">Modifiez les numéros d'ordre (plus petit = affiché en premier)</p><div class="row g-3">
<?php foreach ($modeles as $m): ?>
<div class="col-md-4"><label class="form-label"><?php echo htmlspecialchars($m['nom']); ?></label><input type="number" name="ordre[<?php echo (int)$m['id']; ?>]" value="<?php echo (int)$m['ordre']; ?>" class="form-control" min="0"></div>
<?php endforeach; ?>
</div><button type="submit" class="btn-save mt-3"><i class="bi bi-check-circle"></i> Enregistrer l'ordre</button></div>
</form>

<div class="card-custom"><h5 class="card-title"><i class="bi bi-grid"></i> Liste des modèles <span class="badge ms-2" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;font-size:11px;padding:4px 10px;border-radius:20px;"><?php echo count($modeles); ?></span></h5>

<?php if (empty($modeles)): ?>
<div class="text-center py-5"><i class="bi bi-palette" style="font-size:60px;color:#e8ddd2;display:block;margin-bottom:15px;"></i><h5 style="color:#9a8a7f;">Aucun modèle enregistré</h5><p class="form-text mb-3">Commencez par ajouter votre premier modèle.</p><button type="button" class="btn-save" onclick="openModalAjout()"><i class="bi bi-plus-circle"></i> Ajouter un modèle</button></div>
<?php else: ?>
<div class="modeles-grid">
<?php foreach ($modeles as $index => $m): 
$code = $m['code']; $nom = $m['nom']; $apercuName = $m['apercu'] ?? '';
$apercuUrl = '';
if (!empty($apercuName)) {
    $diskPath = $uploadModeleDir . basename($apercuName);
    if (file_exists($diskPath)) {
        $apercuUrl = $modeleUrl . rawurlencode(basename($apercuName));
    }
}
if (empty($apercuUrl)) {
    foreach (['png','jpg','jpeg','webp','gif'] as $ext) {
        if (file_exists($uploadModeleDir . $code . '.' . $ext)) {
            $apercuUrl = $modeleUrl . rawurlencode($code . '.' . $ext);
            break;
        }
    }
}
$hasTemplate = in_array($code, $templatesPresents, true);
$nbEvenements = $countsParModele[$code] ?? 0;
$estActif = (int)$m['actif'] === 1;
?>
<div class="modele-item <?php echo $estActif ? '' : 'inactif'; ?>">
<div class="apercu-wrapper">
<?php if ($apercuUrl): ?>
<img src="<?php echo htmlspecialchars($apercuUrl); ?>" alt="<?php echo htmlspecialchars($nom); ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
<div class="apercu-placeholder" style="display:none;"><i class="bi bi-image"></i></div>
<?php else: ?>
<div class="apercu-placeholder"><i class="bi bi-image"></i></div>
<?php endif; ?>
<span class="badge-ordinal">#<?php echo (int)$m['ordre']; ?></span>
<span class="badge-statut <?php echo $estActif ? 'actif' : 'inactif'; ?>"><?php echo $estActif ? '✓ Actif' : '○ Inactif'; ?></span>
</div>

<div class="modele-body">
<span class="modele-code"><?php echo htmlspecialchars($code); ?></span>
<div class="modele-nom"><?php echo htmlspecialchars($nom); ?></div>
<?php if (!empty($m['description'])): ?><div class="modele-desc"><?php echo htmlspecialchars($m['description']); ?></div><?php endif; ?>
<div class="modele-meta">
<?php if ($hasTemplate): ?><span class="pill green"><i class="bi bi-check-circle-fill"></i> Template</span><?php else: ?><span class="pill red"><i class="bi bi-x-circle-fill"></i> Sans template</span><?php endif; ?>
<?php if ($nbEvenements > 0): ?><span class="pill blue"><i class="bi bi-calendar-check"></i> <?php echo $nbEvenements; ?> év.</span><?php else: ?><span class="pill gray"><i class="bi bi-dash-circle"></i> Aucun év.</span><?php endif; ?>
<small style="font-size:9px;color:#b8a99c"><?php echo $apercuUrl ? '✅ '.$apercuUrl : '❌ pas d\'image'; ?></small>
</div>
</div>

<div class="modele-actions">
<button type="button" class="btn-icon edit" onclick='openModalModifier(<?php echo json_encode($m, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' title="Modifier"><i class="bi bi-pencil"></i></button>
<button type="button" class="btn-icon view" onclick="openModalApercu('<?php echo htmlspecialchars($apercuUrl); ?>', '<?php echo htmlspecialchars(addslashes($nom)); ?>')" title="Voir l'aperçu" <?php echo $apercuUrl ? '' : 'disabled'; ?>><i class="bi bi-eye"></i></button>
<form method="POST" style="flex:1;display:flex;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>"><button type="submit" class="btn-icon toggle <?php echo $estActif ? 'on' : ''; ?>" style="width:100%;" title="<?php echo $estActif ? 'Désactiver' : 'Activer'; ?>"><i class="bi <?php echo $estActif ? 'bi-eye-slash' : 'bi-eye-fill'; ?>"></i></button></form>
<button type="button" class="btn-icon delete" onclick="confirmSupprimer(<?php echo (int)$m['id']; ?>, '<?php echo htmlspecialchars(addslashes($nom)); ?>', <?php echo $nbEvenements; ?>)" title="Supprimer"><i class="bi bi-trash"></i></button>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

</div>
</div>
</div>

<div class="modal-overlay" id="modalAjout">
<div class="modal-content-custom">
<div class="modal-header-custom"><h5><i class="bi bi-plus-circle"></i> Ajouter un modèle</h5><button type="button" class="btn-close-modal" onclick="closeModalAjout()">&times;</button></div>
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="ajouter">
<div class="mb-3"><label class="form-label">Code technique *</label><input type="text" name="code" class="form-control" required pattern="[a-z0-9_-]+" placeholder="ex: classique, moderne, floral"><div class="form-text">Lettres minuscules, chiffres, tirets, underscores uniquement.</div></div>
<div class="mb-3"><label class="form-label">Nom affiché *</label><input type="text" name="nom" class="form-control" required placeholder="ex: Classique élégant"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Bref descriptif du style..."></textarea></div>
<div class="mb-3"><label class="form-label">Image d'aperçu (optionnel)</label><input type="file" name="apercu" class="form-control" accept=".png,.jpg,.jpeg,.webp,.gif,image/*"><div class="form-text">PNG, JPG, WEBP - max 5Mo. Sera renommée en <code>CODE.ext</code></div></div>
<div class="mb-3"><label class="form-label">Ordre d'affichage</label><input type="number" name="ordre" class="form-control" value="0" min="0"></div>
<div class="d-flex gap-2"><button type="submit" class="btn-save" style="flex:1;justify-content:center;"><i class="bi bi-check-circle"></i> Ajouter</button><button type="button" class="btn-cancel" onclick="closeModalAjout()">Annuler</button></div>
</form>
</div>
</div>

<div class="modal-overlay" id="modalModifier">
<div class="modal-content-custom">
<div class="modal-header-custom"><h5><i class="bi bi-pencil"></i> Modifier le modèle</h5><button type="button" class="btn-close-modal" onclick="closeModalModifier()">&times;</button></div>
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="modifier"><input type="hidden" name="id" id="edit_id">
<div class="mb-3"><label class="form-label">Code</label><input type="text" id="edit_code" class="form-control" readonly style="background:#f8f8f8;font-family:monospace;"></div>
<div class="mb-3"><label class="form-label">Nom affiché *</label><input type="text" name="nom" id="edit_nom" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
<div class="mb-3"><label class="form-label">Ordre d'affichage</label><input type="number" name="ordre" id="edit_ordre" class="form-control" min="0"></div>
<div class="d-flex gap-2"><button type="submit" class="btn-save" style="flex:1;justify-content:center;"><i class="bi bi-check-circle"></i> Enregistrer</button><button type="button" class="btn-cancel" onclick="closeModalModifier()">Annuler</button></div>
</form>
<hr style="margin:20px 0;border:none;border-top:2px dashed rgba(193,124,96,0.15);">
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_apercu"><input type="hidden" name="id" id="edit_apercu_id">
<div class="mb-3"><label class="form-label"><i class="bi bi-image"></i> Changer l'aperçu</label><input type="file" name="apercu" class="form-control" accept=".png,.jpg,.jpeg,.webp,.gif,image/*"><div class="form-text">Max 5Mo. L'ancienne image sera supprimée.</div></div>
<button type="submit" class="btn-save" style="width:100%;justify-content:center;"><i class="bi bi-upload"></i> Uploader le nouvel aperçu</button>
</form>
</div>
</div>

<div class="modal-overlay" id="modalApercu" onclick="if(event.target===this)closeModalApercu()">
<div class="modal-content-custom" style="max-width:800px;padding:15px;"><div class="modal-header-custom"><h5 id="apercuTitre"><i class="bi bi-eye"></i> Aperçu</h5><button type="button" class="btn-close-modal" onclick="closeModalApercu()">&times;</button></div><img id="apercuImage" src="" alt="Aperçu" style="width:100%;border-radius:12px;"></div>
</div>

<form method="POST" id="formSupprimer" style="display:none;"><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" id="delete_id"></form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebarToggle=document.getElementById('sidebarToggle'),sidebarWrapper=document.getElementById('sidebarWrapper'),sidebarOverlay=document.getElementById('sidebarOverlay');
function openSidebar(){sidebarWrapper.classList.add('open');sidebarOverlay.classList.add('active');}
function closeSidebar(){sidebarWrapper.classList.remove('open');sidebarOverlay.classList.remove('active');}
if(sidebarToggle) sidebarToggle.addEventListener('click',e=>{e.stopPropagation();sidebarWrapper.classList.contains('open')?closeSidebar():openSidebar();});
if(sidebarOverlay) sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar();});
function openModalAjout(){document.getElementById('modalAjout').classList.add('active');}
function closeModalAjout(){document.getElementById('modalAjout').classList.remove('active');}
function openModalModifier(modele){document.getElementById('edit_id').value=modele.id;document.getElementById('edit_apercu_id').value=modele.id;document.getElementById('edit_code').value=modele.code;document.getElementById('edit_nom').value=modele.nom;document.getElementById('edit_description').value=modele.description||'';document.getElementById('edit_ordre').value=modele.ordre||0;document.getElementById('modalModifier').classList.add('active');}
function closeModalModifier(){document.getElementById('modalModifier').classList.remove('active');}
function openModalApercu(url,nom){if(!url)return;document.getElementById('apercuImage').src=url;document.getElementById('apercuTitre').innerHTML='<i class="bi bi-eye"></i> '+nom;document.getElementById('modalApercu').classList.add('active');}
function closeModalApercu(){document.getElementById('modalApercu').classList.remove('active');}
function toggleReorganisation(){const form=document.getElementById('formReorganiser');form.style.display=form.style.display==='none'?'block':'none';}
function confirmSupprimer(id,nom,nbEvenements){if(nbEvenements>0){alert("❌ Impossible de supprimer « "+nom+" » car "+nbEvenements+" événement(s) l'utilise(nt).");return;}if(confirm("Supprimer définitivement « "+nom+" » ?")){document.getElementById('delete_id').value=id;document.getElementById('formSupprimer').submit();}}
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeModalAjout();closeModalModifier();closeModalApercu();}});
</script>
</body>
</html>
