<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';

// ========== FIX BASE_PATH POUR INFINITYFREE ==========
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'MdlEvent');
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol.'://'.$host.$projectFolder);
}

requirePermission('evenements.ajouter');

$user = getCurrentUser();
$pdo = getDbConnection();

$uploadDir = __DIR__ . '/../../uploads/photos_host/';
$uploadDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR;
$uploadFondDir = __DIR__ . '/../../uploads/fonds/';
$uploadFondDir = rtrim($uploadFondDir, '/\\') . DIRECTORY_SEPARATOR;
$uploadModeleDir = __DIR__ . '/../../uploads/modeles_invitation/';
$uploadModeleDir = rtrim($uploadModeleDir, '/\\') . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
if (!is_dir($uploadFondDir)) { mkdir($uploadFondDir, 0775, true); }
if (!is_dir($uploadModeleDir)) { mkdir($uploadModeleDir, 0775, true); }

$error = '';
$success = '';
$nom = '';
$type_evenement = 'mariage_religieux';
$description = '';
$date_evenement = '';
$heure_evenement = '';
$lieu = '';
$adresse = '';
$statut = 'BROUILLON';
$whatsapp_enabled = 0;
$telegram_enabled = 0;
$tables_enabled = 0;
$modele_invitation = 'classique';
$statuts = ['BROUILLON', 'ACTIF', 'TERMINE', 'ANNULE'];

$typesEvenement = [
    'mariage_religieux' => '💒 Mariage religieux',
    'mariage_civil' => '📜 Mariage civil',
    'mariage_coutumier' => '🌍 Mariage coutumier',
    'defile_mode' => '👗 Défilé de mode',
    'concert' => '🎵 Concert',
    'anniversaire' => '🎂 Anniversaire',
    'bapteme' => '⛪ Baptême',
    'communion' => '✝ Communion',
    'soiree' => '🎉 Soirée',
    'conference' => '🎤 Conférence',
    'autre' => '📌 Autre'
];

// ========== CHARGER LES MODÈLES ==========
$modelesInvitation = [];
try {
    $stmt = $pdo->query('SELECT id, code, nom, description, apercu, actif FROM modeles_invitation WHERE actif = 1 ORDER BY ordre ASC, nom ASC');
    $modelesInvitation = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Erreur chargement modèles : ' . $e->getMessage());
    $modelesInvitation = [];
}

// Si table vide, fallback sur fichiers dans uploads/modeles_invitation/
if (empty($modelesInvitation)) {
    if (is_dir($uploadModeleDir)) {
        $files = glob($uploadModeleDir . '*.png');
        $files = array_merge($files, glob($uploadModeleDir . '*.jpg'), glob($uploadModeleDir . '*.jpeg'));
        foreach ($files as $file) {
            $base = pathinfo($file, PATHINFO_FILENAME);
            $modelesInvitation[] = [
                'code' => $base,
                'nom' => ucfirst(str_replace(['_','-'], ' ', $base)),
                'description' => 'Modèle ' . $base,
                'apercu' => basename($file),
                'actif' => 1,
            ];
        }
    }
    // Fallback templates PHP
    if (empty($modelesInvitation)) {
        $templateDir = __DIR__ . '/templates/invitations/';
        if (is_dir($templateDir)) {
            $files = glob($templateDir . '*.php');
            foreach ($files as $file) {
                $base = basename($file, '.php');
                if ($base === '_helpers') continue;
                // Cherche un png du même nom dans uploads/modeles_invitation
                $png = $uploadModeleDir . $base . '.png';
                $apercu = file_exists($png) ? $base . '.png' : '';
                $modelesInvitation[] = [
                    'code' => $base,
                    'nom' => ucfirst($base),
                    'description' => '',
                    'apercu' => $apercu,
                    'actif' => 1,
                ];
            }
        }
    }
}

// Associer automatiquement l'apercu si vide mais fichier existe
foreach ($modelesInvitation as &$m) {
    if (empty($m['apercu'])) {
        $possible = $uploadModeleDir . $m['code'] . '.png';
        if (file_exists($possible)) $m['apercu'] = $m['code'] . '.png';
        else {
            $possible = $uploadModeleDir . $m['code'] . '.jpg';
            if (file_exists($possible)) $m['apercu'] = $m['code'] . '.jpg';
        }
    }
}
unset($m);

$stmt = $pdo->query('SELECT id, nom, prenom, username, email FROM utilisateurs WHERE actif = 1 ORDER BY nom, prenom');
$tousUtilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$rolesDisponibles = function_exists('getRolesSpecifiquesDisponibles') ? getRolesSpecifiquesDisponibles() : ['coordinateur'=>'Coordinateur','agent'=>'Agent','controleur'=>'Contrôleur'];
$idsUtilisateursAssocies = [];
$rolesSpecifiques = [];

function uploadErrorMessage(int $code): string {
    return match($code) {
        UPLOAD_ERR_INI_SIZE => 'Dépasse upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE => 'Dépasse MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL => 'Envoi partiel.',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pas de dossier tmp.',
        UPLOAD_ERR_CANT_WRITE => 'Écriture impossible.',
        UPLOAD_ERR_EXTENSION => 'Bloqué par extension PHP.',
        default => 'Erreur inconnue code '.$code
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string)($_POST['nom'] ?? ''));
    $type_evenement = trim((string)($_POST['type_evenement'] ?? 'mariage_religieux'));
    $description = trim((string)($_POST['description'] ?? ''));
    $date_evenement = trim((string)($_POST['date_evenement'] ?? ''));
    $heure_evenement = trim((string)($_POST['heure_evenement'] ?? ''));
    $lieu = trim((string)($_POST['lieu'] ?? ''));
    $adresse = trim((string)($_POST['adresse'] ?? ''));
    $statut = trim((string)($_POST['statut'] ?? 'BROUILLON'));
    $whatsapp_enabled = isset($_POST['whatsapp_enabled']) ? 1 : 0;
    $telegram_enabled = isset($_POST['telegram_enabled']) ? 1 : 0;
    $tables_enabled = isset($_POST['tables_enabled']) ? 1 : 0;
    $modele_invitation = trim((string)($_POST['modele_invitation'] ?? 'classique'));

    $templateDir = __DIR__ . '/templates/invitations/';
    $templatePath = $templateDir . $modele_invitation . '.php';
    if (!preg_match('/^[a-z0-9_-]+$/i', $modele_invitation) || !file_exists($templatePath)) {
        if (!empty($modelesInvitation)) $modele_invitation = $modelesInvitation[0]['code'];
        else $modele_invitation = 'classique';
    }

    $idsUtilisateursAssocies = isset($_POST['utilisateurs_associes']) && is_array($_POST['utilisateurs_associes']) ? array_map('intval', $_POST['utilisateurs_associes']) : [];
    $rolesSpecifiquesPost = $_POST['role_specifique'] ?? [];
    $errors = [];
    if ($nom === '') $errors[] = 'Le nom est obligatoire.';
    if ($date_evenement === '') $errors[] = 'La date est obligatoire.';
    if ($lieu === '') $errors[] = 'Le lieu est obligatoire.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $fondFileName = null;
            $hasFond = isset($_FILES['fond']) && $_FILES['fond']['error'] === UPLOAD_ERR_OK;
            if ($hasFond) {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['fond']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['fond']['size'] <= 5 * 1024 * 1024) {
                    $fondFileName = 'fond_' . uniqid() . '.' . $ext;
                    $dest = $uploadFondDir . $fondFileName;
                    if (!move_uploaded_file($_FILES['fond']['tmp_name'], $dest)) $fondFileName = null;
                }
            }
            $stmt = $pdo->prepare('INSERT INTO evenements (nom, type_evenement, description, date_evenement, heure_evenement, lieu, adresse, statut, whatsapp_enabled, telegram_enabled, tables_enabled, modele_invitation, fond, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$nom, $type_evenement, $description, $date_evenement, $heure_evenement, $lieu, $adresse, $statut, $whatsapp_enabled, $telegram_enabled, $tables_enabled, $modele_invitation, $fondFileName, $user['id'] ?? null]);
            $eventId = (int)$pdo->lastInsertId();
            $nbUtilisateursAssocies = 0;
            if (!empty($idsUtilisateursAssocies)) {
                $stmtAssoc = $pdo->prepare('INSERT INTO evenements_utilisateurs (id_evenement, id_utilisateur, role_specifique) VALUES (?, ?, ?)');
                foreach ($idsUtilisateursAssocies as $uid) {
                    $uid = (int)$uid; if ($uid <= 0) continue;
                    $roleSpecifique = null;
                    if (isset($rolesSpecifiquesPost[$uid]) && trim((string)$rolesSpecifiquesPost[$uid]) !== '') $roleSpecifique = trim((string)$rolesSpecifiquesPost[$uid]);
                    $stmtAssoc->execute([$eventId, $uid, $roleSpecifique]); $nbUtilisateursAssocies++;
                }
            }
            $photoMessages = [];
            $hasFile = isset($_FILES['photos_host']) && is_array($_FILES['photos_host']['name']) && !empty(array_filter($_FILES['photos_host']['name']));
            if ($hasFile) {
                $files = $_FILES['photos_host'];
                $allowed = ['jpg','jpeg','png','gif','webp'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) { $photoMessages[] = '❌ '.$files['name'][$i].' : '.uploadErrorMessage($files['error'][$i]); continue; }
                    if ($files['size'][$i] > 10 * 1024 * 1024) { $photoMessages[] = '❌ Trop lourd: '.$files['name'][$i]; continue; }
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) { $photoMessages[] = '❌ Extension non autorisée: '.$files['name'][$i]; continue; }
                    $newName = 'photo_'.uniqid().'_'.$i.'.'.$ext;
                    $dest = $uploadDir . $newName;
                    if (!move_uploaded_file($files['tmp_name'][$i], $dest)) { $photoMessages[] = '❌ Move échoué: '.$files['name'][$i]; continue; }
                    $titre = trim((string)($_POST['titre_'.$i] ?? ''));
                    $descPhoto = trim((string)($_POST['description_'.$i] ?? ''));
                    $pdo->prepare('INSERT INTO photo_host (id_evenement, photo, titre, description, ordre, actif) VALUES (?,?,?,?,?,1)')->execute([$eventId, $newName, $titre, $descPhoto, $i]);
                }
            }
            $pdo->commit();
            if (function_exists('logAction')) logAction($user['id'] ?? null, 'CREATE_EVENT', 'evenements', "Création événement '$nom' (ID: $eventId)");
            $success = '✅ Événement créé ! ID: '.$eventId;
            if ($fondFileName) $success .= '<br>🖼 Fond ajoutée';
            if ($nbUtilisateursAssocies > 0) $success .= '<br>👥 ' . $nbUtilisateursAssocies . ' utilisateur(s) associé(s)';
            if (!empty($photoMessages)) $success .= '<br>'.implode('<br>', $photoMessages);
            $nom = $type_evenement = $description = $date_evenement = $heure_evenement = $lieu = $adresse = '';
            $statut = 'BROUILLON'; $whatsapp_enabled = $telegram_enabled = $tables_enabled = 0;
            $modele_invitation = 'classique'; $idsUtilisateursAssocies = []; $rolesSpecifiques = [];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = '❌ Erreur: '.$e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
        $rolesSpecifiques = $rolesSpecifiquesPost;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;overflow-x:hidden}
body{font-family:'Inter',system-ui,sans-serif;background:#f8f5f2;color:#1a1a1a;-webkit-font-smoothing:antialiased}
.app-wrapper{display:flex;min-height:100vh;width:100%}
.sidebar-wrapper{flex-shrink:0;width:260px;min-height:100vh;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:100;transition:transform 0.3s ease}
.main-content{flex:1;min-height:100vh;overflow-y:auto;padding:0;min-width:0}
.top-bar{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);padding:15px 30px;border-bottom:1px solid rgba(193,124,96,0.15);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;flex-wrap:wrap;gap:10px;width:100%;max-width:100vw;overflow-x:hidden}
.top-bar .page-title h4{font-weight:700;color:#1a1a1a;margin:0;font-size:20px}
.top-bar .page-title h4 i{color:#c17c60;margin-right:10px}
.top-bar .page-title small{color:#9a8a7f;font-size:12px;display:block;margin-top:2px}
.top-bar .user-info{display:flex;align-items:center;gap:15px;flex-wrap:wrap}
.top-bar .user-info .user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#c17c60,#d4a574);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;box-shadow:0 5px 15px rgba(193,124,96,0.3);flex-shrink:0}
.top-bar .user-info .user-name{font-weight:600;color:#1a1a1a;font-size:13px}
.top-bar .user-info .user-name small{display:block;color:#b8a99c;font-weight:400;font-size:11px}
.top-bar .user-info .role-badge{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap}
.sidebar-toggle-btn{display:none;position:fixed;top:12px;left:12px;z-index:200;background:linear-gradient(135deg,#c17c60,#d4a574);border:none;border-radius:12px;padding:8px 12px;box-shadow:0 5px 20px rgba(193,124,96,0.35);font-size:20px;cursor:pointer;color:white;transition:all 0.3s ease}
.sidebar-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:150;opacity:0;transition:opacity 0.3s ease}
.sidebar-overlay.active{display:block;opacity:1}
.content-section{padding:25px 30px;width:100%;max-width:100vw;overflow-x:hidden}
.card-custom{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:20px;padding:30px;box-shadow:0 8px 32px rgba(0,0,0,0.06);border:1px solid rgba(255,255,255,0.4);max-width:1100px;margin:0 auto}
.card-title{font-weight:700;color:#1a1a1a;margin-bottom:25px;padding-bottom:15px;border-bottom:2px dashed rgba(193,124,96,0.15);font-size:18px}
.card-title i{color:#c17c60;margin-right:10px}
.form-label{font-weight:600;color:#6a5a4a;font-size:13px;text-transform:uppercase;letter-spacing:0.05em}
.form-label i{color:#c17c60;margin-right:6px}
.form-control,.form-select{border-radius:10px;padding:10px 15px;border:1.5px solid rgba(234,227,220,0.6);transition:all 0.3s ease;font-family:'Inter',sans-serif;font-size:14px;background:rgba(255,255,255,0.8);color:#1a1a1a}
.form-control:focus,.form-select:focus{border-color:#c17c60;box-shadow:0 0 0 4px rgba(193,124,96,0.08);background:white}
.btn-save{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border:none;font-weight:700;padding:12px 30px;border-radius:12px;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;text-decoration:none;box-shadow:0 4px 15px rgba(193,124,96,0.25);font-size:14px}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(193,124,96,0.35);color:white}
.btn-cancel{background:rgba(255,255,255,0.8);color:#6a5a4a;border:1.5px solid rgba(234,227,220,0.6);font-weight:600;padding:12px 30px;border-radius:12px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;font-size:14px}
.alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#991b1b;border-radius:12px;padding:15px 20px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start}
.alert-success{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#065f46;border-radius:12px;padding:15px 20px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start}
.switch-group{display:flex;gap:20px;flex-wrap:wrap}
.form-check-input:checked{background-color:#c17c60;border-color:#c17c60}
.photos-section{margin-top:30px;padding-top:20px;border-top:2px dashed rgba(193,124,96,0.15)}
.photos-section h6{font-weight:700;color:#1a1a1a;margin-bottom:15px;font-size:15px}
.photos-section h6 i{color:#c17c60;margin-right:8px}
.photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:15px;margin-bottom:20px}
.photo-item{position:relative;border-radius:16px;overflow:hidden;border:2px solid rgba(234,227,220,0.6);background:rgba(251,248,245,0.7);aspect-ratio:1/1;transition:all 0.3s ease}
.photo-item:hover{border-color:#c17c60;transform:translateY(-2px);box-shadow:0 8px 20px rgba(193,124,96,0.15)}
.photo-item img{width:100%;height:100%;object-fit:cover;display:block}
.photo-badge{position:absolute;top:8px;right:8px;background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;box-shadow:0 2px 8px rgba(193,124,96,0.3)}
.upload-area{border:2px dashed rgba(193,124,96,0.3);border-radius:16px;padding:30px 20px;text-align:center;background:rgba(251,248,245,0.5);margin-top:15px;transition:all 0.3s ease}
.upload-area:hover{border-color:#c17c60;background:rgba(193,124,96,0.03)}
.btn-outline-primary{border:1.5px solid rgba(193,124,96,0.3);color:#c17c60;border-radius:10px;padding:8px 18px;font-weight:600;font-size:13px;transition:all 0.3s ease;background:rgba(255,255,255,0.6)}
.btn-outline-primary:hover{background:linear-gradient(135deg,#c17c60,#d4a574);color:white;border-color:#c17c60;transform:translateY(-2px);box-shadow:0 4px 12px rgba(193,124,96,0.25)}
.form-actions{display:flex;gap:15px;margin-top:30px;padding-top:20px;border-top:2px dashed rgba(193,124,96,0.1);flex-wrap:wrap}
.fond-container{background:rgba(251,248,245,0.6);border-radius:12px;padding:20px;border:2px dashed rgba(193,124,96,0.2);margin-bottom:20px}
.fond-preview{max-width:200px;max-height:150px;object-fit:cover;border-radius:10px;border:2px solid rgba(234,227,220,0.6);margin-top:10px}
.users-section{margin-top:30px;padding-top:20px;border-top:2px dashed rgba(193,124,96,0.15)}
.users-section h6{font-weight:700;color:#1a1a1a;margin-bottom:15px;font-size:15px}
.users-section h6 i{color:#c17c60;margin-right:8px}
.user-checkbox-item{background:rgba(251,248,245,0.5);border:1.5px solid rgba(234,227,220,0.6);border-radius:12px;padding:14px 16px;transition:all 0.3s ease;display:flex;flex-direction:column;gap:10px}
.user-checkbox-item:hover{border-color:#c17c60;background:rgba(193,124,96,0.05)}
.user-checkbox-item.checked{border-color:#c17c60;background:rgba(193,124,96,0.08);box-shadow:0 2px 8px rgba(193,124,96,0.1)}
.user-header{display:flex;align-items:center;gap:10px;cursor:pointer}
.user-checkbox-item input[type="checkbox"]{width:18px;height:18px;accent-color:#c17c60;cursor:pointer;flex-shrink:0}
.user-checkbox-item .user-name{font-weight:600;color:#1a1a1a;font-size:14px}
.user-checkbox-item .user-username{color:#9a8a7f;font-size:12px}
.role-select-container{display:none;margin-top:5px}
.user-checkbox-item.checked .role-select-container{display:block}
.role-select-container label{font-size:11px;color:#9a8a7f;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;margin-bottom:4px;display:block}
.role-select-container .form-select-sm{font-size:13px;padding:6px 10px;border-radius:8px;border:1.5px solid rgba(234,227,220,0.6);background:rgba(255,255,255,0.9);color:#1a1a1a}
.user-badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;background:linear-gradient(135deg,#c17c60,#d4a574);color:white}
.no-users-message{text-align:center;padding:30px;color:#9a8a7f;background:rgba(251,248,245,0.5);border-radius:12px;border:2px dashed rgba(234,227,220,0.6)}
.modele-section{margin-top:30px;padding-top:20px;border-top:2px dashed rgba(193,124,96,0.15)}
.modele-section h6{font-weight:700;color:#1a1a1a;margin-bottom:15px;font-size:15px}
.modele-section h6 i{color:#c17c60;margin-right:8px}
.modele-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:10px}
.modele-card{position:relative;background:rgba(251,248,245,0.7);border:2px solid rgba(234,227,220,0.6);border-radius:16px;padding:0;cursor:pointer;transition:all 0.35s cubic-bezier(0.25,0.46,0.45,0.94);overflow:hidden;display:flex;flex-direction:column}
.modele-card:hover{border-color:#c17c60;transform:translateY(-4px);box-shadow:0 12px 30px rgba(193,124,96,0.18)}
.modele-card.selected{border-color:#c17c60;background:rgba(193,124,96,0.06);box-shadow:0 8px 24px rgba(193,124,96,0.22)}
.modele-radio{position:absolute;opacity:0;pointer-events:none}
.modele-preview{position:relative;width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,#f0ebe5,#e8ddd2);overflow:hidden;display:flex;align-items:center;justify-content:center}
.modele-preview img{width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.5s ease}
.modele-card:hover .modele-preview img{transform:scale(1.05)}
.modele-preview-placeholder{display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:rgba(193,124,96,0.5);font-size:48px;background:linear-gradient(135deg,rgba(193,124,96,0.05),rgba(212,165,116,0.1))}
.modele-check{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.95);color:#c17c60;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 12px rgba(0,0,0,0.15);opacity:0;transform:scale(0.5);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1)}
.modele-card.selected .modele-check{opacity:1;transform:scale(1)}
.modele-info{padding:14px 16px 16px;text-align:left}
.modele-nom{font-size:14px;font-weight:700;color:#1a1a1a;margin-bottom:4px}
.modele-desc{font-size:11px;color:#9a8a7f;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
@media (max-width:992px){.sidebar-toggle-btn{display:flex !important;align-items:center;justify-content:center}.app-wrapper{display:block;width:100%}.main-content{width:100% !important;min-width:0 !important;margin-left:0 !important}.sidebar-wrapper{position:fixed !important;top:0 !important;left:0 !important;width:min(280px,85vw) !important;height:100dvh !important;min-height:100dvh !important;margin:0 !important;transform:translate3d(-105%,0,0);transition:transform 0.28s ease !important;z-index:2000 !important;overflow-y:auto;overflow-x:hidden;border-radius:0 18px 18px 0;will-change:transform}.sidebar-wrapper.open{transform:translate3d(0,0,0) !important}.sidebar-overlay{position:fixed !important;inset:0 !important;display:block !important;visibility:hidden;opacity:0;background:rgba(0,0,0,0.5) !important;backdrop-filter:blur(4px) !important;-webkit-backdrop-filter:blur(4px) !important;pointer-events:none;transition:opacity 0.28s ease,visibility 0.28s ease;z-index:1900 !important}.sidebar-overlay.active{visibility:visible;opacity:1;pointer-events:auto}.top-bar{padding:12px 15px 12px 70px;flex-direction:row;flex-wrap:wrap}.content-section{padding:15px 15px}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}.top-bar .page-title h4{font-size:1rem}.top-bar .user-info .user-name{display:none}.top-bar .user-info .role-badge{font-size:9px;padding:3px 10px}.card-custom{padding:20px}.modele-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}}
@media (max-width:576px){.top-bar{padding:10px 12px 10px 60px;flex-direction:column;align-items:stretch;gap:8px}.top-bar .page-title h4{font-size:0.95rem}.top-bar .page-title small{font-size:10px}.top-bar .user-info{justify-content:flex-end;gap:10px}.top-bar .user-info .user-avatar{width:32px;height:32px;font-size:13px}.content-section{padding:10px 12px}.card-custom{padding:15px}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px}.btn-save,.btn-cancel{width:100%;justify-content:center}.form-actions{flex-direction:column}.sidebar-toggle-btn{top:8px;left:8px;padding:6px 10px;font-size:17px}.sidebar-wrapper{width:min(260px,90vw) !important}.modele-grid{grid-template-columns:repeat(2,1fr);gap:10px}.modele-info{padding:10px 12px 12px}.modele-nom{font-size:13px}.modele-desc{font-size:10px}}
</style>
</head>
<body>
<button class="sidebar-toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
<div class="sidebar-wrapper" id="sidebarWrapper"><?php include_once __DIR__ . '/../../includes/sidebar.php'; ?></div>
<div class="main-content" id="mainContent">
<div class="top-bar">
<div class="page-title"><h4><i class="bi bi-plus-circle"></i> Ajouter un événement</h4><small><i class="bi bi-calendar-event"></i> Créer un nouvel événement • <?php echo BASE_PATH ?: '/'; ?> • <?php echo count($modelesInvitation); ?> modèle(s)</small></div>
<div class="user-info"><span class="role-badge"><i class="bi bi-shield-check"></i> <?php echo is_array($user['roles'] ?? null) ? implode(', ', $user['roles']) : 'Aucun rôle'; ?></span><div><div class="user-name"><?php echo htmlspecialchars(($user['prenom'] ?? '').' '.($user['nom'] ?? '')); ?><small>@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small></div></div><div class="user-avatar"><?php echo strtoupper(substr($user['prenom'] ?? 'U',0,1).substr($user['nom'] ?? 'N',0,1)) ?: 'U'; ?></div></div>
</div>

<div class="content-section">
<div class="card-custom">
<h5 class="card-title"><i class="bi bi-plus-circle"></i> Ajouter un événement</h5>
<?php if ($error): ?><div class="alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?php echo $error; ?></div></div><?php endif; ?>
<?php if ($success): ?><div class="alert-success"><i class="bi bi-check-circle-fill"></i><div><?php echo $success; ?></div></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="mb-3"><label class="form-label"><i class="bi bi-tag"></i> Nom *</label><input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required></div>
<div class="mb-3"><label class="form-label"><i class="bi bi-calendar-event"></i> Type d'événement</label><select class="form-select" name="type_evenement"><?php foreach ($typesEvenement as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo $type_evenement == $value ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label"><i class="bi bi-text-paragraph"></i> Description</label><textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label"><i class="bi bi-calendar3"></i> Date *</label><input type="date" class="form-control" name="date_evenement" value="<?php echo $date_evenement; ?>" required></div><div class="col-md-6 mb-3"><label class="form-label"><i class="bi bi-clock"></i> Heure</label><input type="time" class="form-control" name="heure_evenement" value="<?php echo $heure_evenement; ?>"></div></div>
<div class="mb-3"><label class="form-label"><i class="bi bi-geo-alt"></i> Lieu *</label><input type="text" class="form-control" name="lieu" value="<?php echo htmlspecialchars($lieu); ?>" required></div>
<div class="mb-3"><label class="form-label"><i class="bi bi-house"></i> Adresse</label><input type="text" class="form-control" name="adresse" value="<?php echo htmlspecialchars($adresse); ?>"></div>
<div class="mb-3"><label class="form-label"><i class="bi bi-circle"></i> Statut</label><select class="form-select" name="statut"><?php foreach ($statuts as $s): ?><option value="<?php echo $s; ?>" <?php echo $statut==$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>

<div class="modele-section">
<h6><i class="bi bi-palette-fill"></i> Modèle d'invitation * <span class="user-badge ms-2"><?php echo count($modelesInvitation); ?> disponible(s)</span></h6>
<div class="form-text mb-3"><i class="bi bi-info-circle"></i> Choisissez le design - Aperçu depuis <code>uploads/modeles_invitation/</code> - Dossier: <?php echo is_dir($uploadModeleDir) ? '✅ OK' : '❌ introuvable'; ?> - Fichiers: <?php echo count(glob($uploadModeleDir.'*.png'))+count(glob($uploadModeleDir.'*.jpg')); ?></div>
<?php if (empty($modelesInvitation)): ?>
<div class="no-users-message"><i class="bi bi-palette" style="font-size:36px;display:block;margin-bottom:10px;"></i>Aucun modèle disponible.<br><small>Mets des images dans <code>uploads/modeles_invitation/</code></small></div>
<?php else: ?>
<div class="modele-grid" id="modeleGrid">
<?php foreach ($modelesInvitation as $modele): 
$isSelected = $modele_invitation === $modele['code'];
$apercuFileName = $modele['apercu'] ?? '';
$apercuFullPath = $uploadModeleDir . $apercuFileName;
$apercuUrl = '';
if ($apercuFileName && file_exists($apercuFullPath)) {
    // URL portable
    $apercuUrl = BASE_PATH . '/uploads/modeles_invitation/' . rawurlencode($apercuFileName);
    if (empty(BASE_PATH)) $apercuUrl = '/uploads/modeles_invitation/' . rawurlencode($apercuFileName);
    // Si on est en localhost sans BASE_PATH, on essaie chemin relatif
    if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false && empty(BASE_PATH)) {
        $apercuUrl = '/gestion_invitations/uploads/modeles_invitation/' . rawurlencode($apercuFileName);
        // Test si fichier accessible via URL relative
        if (!file_exists(__DIR__ . '/../../uploads/modeles_invitation/' . $apercuFileName)) {
            $apercuUrl = '../../uploads/modeles_invitation/' . rawurlencode($apercuFileName);
        }
    }
}
?>
<label class="modele-card <?php echo $isSelected ? 'selected' : ''; ?>" data-code="<?php echo htmlspecialchars($modele['code']); ?>">
<input type="radio" name="modele_invitation" value="<?php echo htmlspecialchars($modele['code']); ?>" <?php echo $isSelected ? 'checked' : ''; ?> class="modele-radio">
<div class="modele-preview">
<?php if ($apercuUrl): ?>
<img src="<?php echo htmlspecialchars($apercuUrl); ?>" alt="<?php echo htmlspecialchars($modele['nom']); ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
<div class="modele-preview-placeholder" style="display:none"><i class="bi bi-image"></i></div>
<?php else: ?>
<div class="modele-preview-placeholder"><i class="bi bi-image"></i><br><small style="font-size:10px;margin-top:4px;display:block"><?php echo htmlspecialchars($modele['code']); ?><br>Pas d'image</small></div>
<?php endif; ?>
<div class="modele-check"><i class="bi bi-check-circle-fill"></i></div>
</div>
<div class="modele-info"><div class="modele-nom"><?php echo htmlspecialchars($modele['nom']); ?></div><?php if (!empty($modele['description'])): ?><div class="modele-desc"><?php echo htmlspecialchars($modele['description']); ?></div><?php endif; ?><small style="font-size:9px;color:#b8a99c"><?php echo htmlspecialchars($modele['code']); ?> • <?php echo $apercuFileName ? '✅ '.$apercuFileName : '❌ pas d\'apercu'; ?></small></div>
</label>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="fond-container mt-4"><label class="form-label"><i class="bi bi-image"></i> Photo de fond</label><div class="form-text mb-2">JPG, PNG, WEBP - max 5Mo</div><input type="file" class="form-control" name="fond" accept=".jpg,.jpeg,.png,.gif,.webp,image/*" id="fondInput"><div id="fondPreviewContainer" style="display:none;" class="mt-2"><img id="fondPreview" class="fond-preview" alt="Aperçu fond"><button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeFond()"><i class="bi bi-x"></i> Supprimer</button></div></div>

<div class="mb-3"><label class="form-label"><i class="bi bi-broadcast"></i> Communications</label><div class="switch-group"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" <?php echo $whatsapp_enabled?'checked':''; ?>><label class="form-check-label" for="whatsapp_enabled"><i class="bi bi-whatsapp" style="color:#25D366;"></i> WhatsApp</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="telegram_enabled" id="telegram_enabled" <?php echo $telegram_enabled?'checked':''; ?>><label class="form-check-label" for="telegram_enabled"><i class="bi bi-telegram" style="color:#0088cc;"></i> Telegram</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="tables_enabled" id="tables_enabled" <?php echo $tables_enabled?'checked':''; ?>><label class="form-check-label" for="tables_enabled"><i class="bi bi-table" style="color:#c17c60;"></i> Tables</label></div></div></div>

<div class="users-section">
<h6><i class="bi bi-people"></i> Utilisateurs associés <span class="user-badge ms-2" id="userCountBadge"><?php echo count($idsUtilisateursAssocies); ?></span></h6>
<div class="form-text mb-3"><i class="bi bi-info-circle"></i> Sélectionnez les utilisateurs pour cet événement.</div>
<?php if (empty($tousUtilisateurs)): ?><div class="no-users-message"><i class="bi bi-person-x" style="font-size:36px;display:block;margin-bottom:10px;"></i>Aucun utilisateur actif</div>
<?php else: ?>
<input type="hidden" name="utilisateurs_associes_submit" value="1">
<div class="row g-3">
<?php foreach ($tousUtilisateurs as $u): 
$isChecked = in_array($u['id'], $idsUtilisateursAssocies);
$roleActuel = $rolesSpecifiques[$u['id']] ?? '';
?>
<div class="col-md-6"><div class="user-checkbox-item <?php echo $isChecked ? 'checked' : ''; ?>" data-user-id="<?php echo $u['id']; ?>"><label class="user-header" for="user_<?php echo $u['id']; ?>"><input type="checkbox" name="utilisateurs_associes[]" value="<?php echo $u['id']; ?>" id="user_<?php echo $u['id']; ?>" class="user-checkbox" <?php echo $isChecked ? 'checked' : ''; ?>><div style="flex:1;min-width:0;"><div class="user-name"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></div><div class="user-username">@<?php echo htmlspecialchars($u['username']); ?></div></div></label><div class="role-select-container"><label><i class="bi bi-tag-fill"></i> Rôle spécifique</label><select class="form-select form-select-sm" name="role_specifique[<?php echo $u['id']; ?>]"><option value="">— Aucun rôle —</option><?php foreach ($rolesDisponibles as $roleValue => $roleLabel): ?><option value="<?php echo $roleValue; ?>" <?php echo $roleActuel === $roleValue ? 'selected' : ''; ?>><?php echo $roleLabel; ?></option><?php endforeach; ?></select></div></div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="photos-section"><h6><i class="bi bi-images"></i> Photos des mariés (optionnel)</h6><div class="upload-area" id="uploadArea"><i class="bi bi-cloud-upload"></i><p id="uploadText"><strong>Sélectionnez vos photos</strong></p><div class="form-text">JPG, PNG, GIF, WEBP - max 10Mo</div><div style="margin-top:15px;"><input type="file" id="photoInput" name="photos_host[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,image/*" style="display:none"><button type="button" class="btn-outline-primary" id="btnChoose"><i class="bi bi-plus-circle"></i> Choisir des photos</button><span id="fileCount" class="ms-2" style="color:#9a8a7f;"></span></div></div><div id="photoPreview" class="photo-grid mt-3" style="display:none;"></div><div id="photoFields" style="display:none;" class="mt-2"></div></div>

<div class="form-actions"><button type="submit" class="btn-save"><i class="bi bi-save"></i> Créer l'événement</button><a href="index.php" class="btn-cancel"><i class="bi bi-arrow-left"></i> Retour</a></div>
</form>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebarToggle=document.getElementById('sidebarToggle'),sidebarWrapper=document.getElementById('sidebarWrapper'),sidebarOverlay=document.getElementById('sidebarOverlay');
function openSidebar(){sidebarWrapper.classList.add('open');sidebarOverlay.classList.add('active');document.body.classList.add('sidebar-open');}
function closeSidebar(){sidebarWrapper.classList.remove('open');sidebarOverlay.classList.remove('active');document.body.classList.remove('sidebar-open');}
if(sidebarToggle) sidebarToggle.addEventListener('click',function(e){e.stopPropagation();sidebarWrapper.classList.contains('open')?closeSidebar():openSidebar();});
if(sidebarOverlay) sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&sidebarWrapper.classList.contains('open'))closeSidebar();});
window.addEventListener('resize',function(){if(window.innerWidth>992)closeSidebar();});

function updateUserCount(){const checked=document.querySelectorAll('.user-checkbox:checked').length;const badge=document.getElementById('userCountBadge');if(badge) badge.textContent=checked;}
document.querySelectorAll('.user-checkbox-item').forEach(function(item){const checkbox=item.querySelector('input[type="checkbox"]');if(checkbox){checkbox.addEventListener('change',function(){if(this.checked){item.classList.add('checked');}else{item.classList.remove('checked');const select=item.querySelector('select[name^="role_specifique"]');if(select) select.value='';}updateUserCount();});}});
updateUserCount();

document.querySelectorAll('.modele-card').forEach(function(card){
const radio=card.querySelector('.modele-radio');if(!radio) return;
card.addEventListener('click',function(e){if(e.target.tagName==='INPUT') return;document.querySelectorAll('.modele-card').forEach(c=>c.classList.remove('selected'));card.classList.add('selected');radio.checked=true;card.style.transform='scale(0.97)';setTimeout(()=>card.style.transform='',150);});
radio.addEventListener('change',function(){if(this.checked){document.querySelectorAll('.modele-card').forEach(c=>c.classList.remove('selected'));card.classList.add('selected');}});
});

const fondInput=document.getElementById('fondInput'),fondPreviewContainer=document.getElementById('fondPreviewContainer'),fondPreview=document.getElementById('fondPreview');
if(fondInput){fondInput.addEventListener('change',function(){if(this.files&&this.files[0]){const reader=new FileReader();reader.onload=function(e){fondPreview.src=e.target.result;fondPreviewContainer.style.display='block';};reader.readAsDataURL(this.files[0]);}else{fondPreviewContainer.style.display='none';}});}
function removeFond(){fondInput.value='';fondPreviewContainer.style.display='none';fondPreview.src='';}

let dt=new DataTransfer();const photoInput=document.getElementById('photoInput'),btnChoose=document.getElementById('btnChoose'),fileCount=document.getElementById('fileCount'),uploadText=document.getElementById('uploadText'),preview=document.getElementById('photoPreview'),fields=document.getElementById('photoFields');
if(btnChoose) btnChoose.addEventListener('click',()=>photoInput.click());
if(photoInput){photoInput.addEventListener('change',(e)=>{for(let f of e.target.files){dt.items.add(f);}photoInput.files=dt.files;renderPreview();});}
function renderPreview(){if(dt.files.length===0){preview.style.display='none';fields.style.display='none';fileCount.textContent='';uploadText.innerHTML='<strong>Sélectionnez vos photos</strong>';return;}fileCount.textContent=dt.files.length+' fichier(s)';uploadText.innerHTML='<strong style="color:#10b981;">'+dt.files.length+' photo(s) prête(s)</strong>';preview.innerHTML='';fields.innerHTML='';preview.style.display='grid';fields.style.display='block';Array.from(dt.files).forEach((file,i)=>{const reader=new FileReader();reader.onload=(ev)=>{const div=document.createElement('div');div.className='photo-item';div.innerHTML=`<img src="${ev.target.result}"><div class="photo-badge">${i+1}</div><button type="button" onclick="removeNewFile(${i})" style="position:absolute;top:5px;left:5px;background:#dc2626;color:white;border:none;border-radius:50%;width:26px;height:26px;font-size:16px;cursor:pointer;z-index:10;box-shadow:0 2px 8px rgba(220,38,38,0.3);">×</button><div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.8));color:white;font-size:11px;padding:12px 6px 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${file.name}</div>`;preview.appendChild(div);};reader.readAsDataURL(file);const row=document.createElement('div');row.className='row g-2 mb-2';row.innerHTML=`<div class="col-6"><input type="text" class="form-control form-control-sm" name="titre_${i}" placeholder="Titre pour ${file.name}"></div><div class="col-6"><input type="text" class="form-control form-control-sm" name="description_${i}" placeholder="Description"></div>`;fields.appendChild(row);});}
function removeNewFile(index){const newDt=new DataTransfer();Array.from(dt.files).forEach((f,i)=>{if(i!==index) newDt.items.add(f);});dt=newDt;photoInput.files=dt.files;renderPreview();}
</script>
</body>
</html>
