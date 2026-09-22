<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invites.creer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// RÉCUPÉRATION DES CATÉGORIES
// ============================================

$categories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categories_invites WHERE actif = 1 ORDER BY nom");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {}

// ============================================
// ⭐ RÉCUPÉRATION DES ÉVÉNEMENTS ACCESSIBLES
// ============================================

$evenementsDisponibles = getEvenementsPourSelect($pdo);

// Si un événement est passé en GET
$id_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

// ============================================
// VARIABLES
// ============================================

$error = '';
$nom = '';
$prenom = '';
$telephone = '';
$email = '';
$adresse = '';
$id_categorie = '';
$entreprise = '';
$nombre_personnes = 1;
$remarque = '';
$contact_preference = 'EMAIL';
$photo = '';
$creer_invitation = 1; // ⭐ Option activée par défaut

// ============================================
// DOSSIER D'UPLOAD
// ============================================

$uploadDir = __DIR__ . '/../../uploads/photos/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die('Impossible de créer le dossier : ' . htmlspecialchars($uploadDir, ENT_QUOTES, 'UTF-8'));
    }
}

if (!is_writable($uploadDir)) {
    die('Le dossier uploads/photos n\'est pas accessible en écriture. Chemin : ' . htmlspecialchars($uploadDir, ENT_QUOTES, 'UTF-8'));
}

$baseUrl = '/gestion_invitations/uploads/photos/';

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $id_categorie = $_POST['id_categorie'] ?? '';
    $entreprise = trim($_POST['entreprise'] ?? '');
    $nombre_personnes = (int)($_POST['nombre_personnes'] ?? 1);
    $remarque = trim($_POST['remarque'] ?? '');
    $contact_preference = $_POST['contact_preference'] ?? 'EMAIL';
    
    // ⭐ Récupérer l'événement sélectionné
    $id_evenement = (int)($_POST['id_evenement'] ?? 0);
    
    // ⭐ Option de création automatique d'invitation
    $creer_invitation = isset($_POST['creer_invitation']) ? 1 : 0;
    
    // ⭐ Statut FORCÉ à EN_ATTENTE (pas de choix utilisateur)
    $statut_invitation = 'EN_ATTENTE';

    // Validation
    $errors = [];

    // ⭐ VALIDATION ÉVÉNEMENT
    if ($id_evenement <= 0) {
        $errors[] = 'L\'événement est obligatoire.';
    } elseif (!userCanAddInviteToEvenement($pdo, (int)$user['id'], $id_evenement)) {
        $errors[] = 'Vous n\'avez pas accès à cet événement.';
    }

    if (empty($nom)) $errors[] = 'Le nom est requis';
    if (empty($prenom)) $errors[] = 'Le prénom est requis';
    if (empty($email) && empty($telephone)) $errors[] = 'Au moins un moyen de contact (email ou téléphone) est requis';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
    if ($nombre_personnes < 1) $errors[] = 'Le nombre de personnes doit être au moins 1';
    if ($nombre_personnes > 100) $errors[] = 'Le nombre de personnes ne peut pas dépasser 100';

    // ⭐ Vérifier l'unicité de l'email DANS LE MÊME ÉVÉNEMENT
    if (!empty($email) && empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM invites WHERE email = ? AND id_evenement = ?");
            $stmt->execute([$email, $id_evenement]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé pour un invité de cet événement";
            }
        } catch (PDOException $e) {}
    }

    // ⭐ Vérifier l'unicité du téléphone DANS LE MÊME ÉVÉNEMENT
    if (!empty($telephone) && empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM invites WHERE telephone = ? AND id_evenement = ?");
            $stmt->execute([$telephone, $id_evenement]);
            if ($stmt->fetch()) {
                $errors[] = "Ce téléphone est déjà utilisé pour un invité de cet événement";
            }
        } catch (PDOException $e) {}
    }

    // ============================================
    // TRAITEMENT DE LA PHOTO
    // ============================================
    
    $photoPath = '';
    $newFileName = '';

    if (
        empty($errors) &&
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['photo'];

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'La photo dépasse la taille maximale autorisée par le serveur.',
            UPLOAD_ERR_FORM_SIZE  => 'La photo dépasse la taille maximale autorisée par le formulaire.',
            UPLOAD_ERR_PARTIAL    => 'La photo a été téléchargée partiellement.',
            UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire de PHP est introuvable.',
            UPLOAD_ERR_CANT_WRITE => 'PHP ne peut pas écrire le fichier sur le disque.',
            UPLOAD_ERR_EXTENSION  => 'Une extension PHP a interrompu le téléchargement.'
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $uploadErrors[$file['error']] ?? 'Erreur inconnue lors du téléchargement de la photo.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Le fichier reçu n\'est pas un téléchargement valide.';
        } else {
            $fileSize = (int) $file['size'];
            $fileTmp = $file['tmp_name'];

            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'La photo ne doit pas dépasser 5 Mo.';
            }

            $imageInfo = @getimagesize($fileTmp);
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];

            $mimeType = $imageInfo['mime'] ?? '';

            if ($imageInfo === false || !isset($allowedMimeTypes[$mimeType])) {
                $errors[] = 'Le fichier sélectionné n\'est pas une image valide. Formats acceptés : JPG, PNG, GIF, WEBP.';
            }

            if (empty($errors)) {
                $extension = $allowedMimeTypes[$mimeType];
                $newFileName = 'invite_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $destination = $uploadDir . $newFileName;

                if (!move_uploaded_file($fileTmp, $destination)) {
                    $errors[] = 'Erreur lors du déplacement de la photo. Vérifiez les permissions du dossier.';
                } else {
                    $photoPath = $newFileName;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // ⭐ INSERTION DE L'INVITÉ
            $stmt = $pdo->prepare("
                INSERT INTO invites (
                    id_evenement, nom, prenom, telephone, email, adresse, 
                    id_categorie, entreprise, nombre_personnes, 
                    remarque, contact_preference, photo, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $id_evenement,
                $nom,
                $prenom,
                $telephone,
                $email,
                $adresse,
                $id_categorie ?: null,
                $entreprise,
                $nombre_personnes,
                $remarque,
                $contact_preference,
                $photoPath
            ]);

            $inviteId = (int)$pdo->lastInsertId();

            // ⭐ CRÉATION AUTOMATIQUE DE L'INVITATION
            $invitationCreee = false;
            $codeUnique = null;
            
            if ($creer_invitation && $inviteId > 0) {
                // Générer un code unique
                $codeUnique = 'INV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
                
                // Vérifier l'unicité (au cas où)
                $maxTentatives = 5;
                $tentatives = 0;
                while ($tentatives < $maxTentatives) {
                    $checkStmt = $pdo->prepare("SELECT id FROM invitations WHERE code_unique = ?");
                    $checkStmt->execute([$codeUnique]);
                    if (!$checkStmt->fetch()) break;
                    $codeUnique = 'INV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
                    $tentatives++;
                }
                
                // Insérer l'invitation avec statut EN_ATTENTE
                $stmtInv = $pdo->prepare("
                    INSERT INTO invitations (id_evenement, id_invite, code_unique, statut, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtInv->execute([$id_evenement, $inviteId, $codeUnique, $statut_invitation]);
                $invitationCreee = true;
            }

            $pdo->commit();

            // Journaliser
            $logMessage = "Création de l'invité '$prenom $nom' (ID: $inviteId) pour l'événement #$id_evenement";
            if ($invitationCreee) {
                $logMessage .= " + invitation automatique (code: $codeUnique)";
            }
            logAction($user['id'], 'CREATE_GUEST', 'invites', $logMessage);

            // ⭐ Message de succès enrichi
            if ($invitationCreee) {
                $_SESSION['flash_message'] = "✅ Invité créé avec succès ! Invitation générée automatiquement (Code: $codeUnique)";
            }
            
            header('Location: index.php?success=ajoute' . ($invitationCreee ? '&invitation=1' : ''));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            
            // Supprimer la photo si erreur
            if (!empty($photoPath)) {
                $uploadedFile = $uploadDir . $photoPath;
                if (is_file($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }
            $error = 'Erreur lors de la création : ' . $e->getMessage();
        }
    } else {
        if (!empty($photoPath)) {
            $uploadedFile = $uploadDir . $photoPath;
            if (is_file($uploadedFile)) {
                unlink($uploadedFile);
            }
        }
        $error = implode('<br>', $errors);
    }
}

// ============================================
// PRÉFÉRENCES DE CONTACT
// ============================================

$contactPreferences = ['EMAIL', 'WHATSAPP', 'TELEGRAM', 'SMS'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un invité - <?php echo APP_NAME; ?></title>
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
        .alert-error i { color: #dc2626; font-size: 18px; flex-shrink: 0; }

        .alert-warning-custom {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            color: #92400e;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .alert-warning-custom i { font-size: 18px; flex-shrink: 0; }

        /* ========== ⭐ SECTION ÉVÉNEMENT ========== */
        .event-select-container {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
            border: 2px solid rgba(193, 124, 96, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .event-select-container .form-select {
            font-weight: 600;
            font-size: 15px;
            padding: 12px 15px;
            border-color: #c17c60;
            background: white;
        }
        .event-select-container .form-label {
            color: #1a1a1a;
            font-size: 15px;
            margin-bottom: 10px;
        }

        /* ========== ⭐ SECTION INVITATION AUTOMATIQUE ========== */
        .invitation-auto-section {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(52, 211, 153, 0.05));
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .invitation-auto-section.disabled {
            background: rgba(251, 248, 245, 0.5);
            border-color: rgba(234, 227, 220, 0.6);
            opacity: 0.7;
        }
        .invitation-toggle-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 5px;
            flex-wrap: wrap;
        }
        .invitation-toggle-header .title-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 200px;
        }
        .invitation-toggle-header .icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #10b981, #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        .invitation-auto-section.disabled .icon-badge {
            background: rgba(154, 138, 127, 0.3);
            box-shadow: none;
        }
        .invitation-toggle-header .title-text {
            flex: 1;
        }
        .invitation-toggle-header .title-text h6 {
            margin: 0;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
        }
        .invitation-toggle-header .title-text p {
            margin: 2px 0 0;
            color: #9a8a7f;
            font-size: 12px;
        }

        /* Switch personnalisé */
        .form-switch-custom {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }
        .form-switch-custom input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .form-switch-custom .switch-track {
            width: 50px;
            height: 28px;
            background: rgba(154, 138, 127, 0.3);
            border-radius: 20px;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .form-switch-custom .switch-track::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        .form-switch-custom input:checked + .switch-track {
            background: linear-gradient(135deg, #10b981, #34d399);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .form-switch-custom input:checked + .switch-track::after {
            transform: translateX(22px);
        }
        .form-switch-custom .switch-label {
            font-size: 13px;
            font-weight: 600;
            color: #6a5a4a;
        }

        /* ⭐ Info statut automatique */
        .invitation-auto-info {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed rgba(16, 185, 129, 0.3);
        }
        .info-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 10px;
            font-size: 13px;
            color: #065f46;
        }
        .info-status-badge i {
            color: #10b981;
            font-size: 16px;
        }
        .info-status-badge strong {
            color: #10b981;
            font-weight: 700;
        }

        /* ========== PRÉFÉRENCES DE CONTACT ========== */
        .preference-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.8);
        }
        .preference-option:hover {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.05);
        }
        .preference-option input[type="radio"] {
            accent-color: #c17c60;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        .preference-option label {
            cursor: pointer;
            margin: 0;
            font-weight: 500;
            color: #6a5a4a;
            font-size: 13px;
        }
        .preference-option .pref-icon {
            font-size: 18px;
            width: 26px;
            text-align: center;
        }

        /* ========== PHOTO UPLOAD ========== */
        .photo-upload {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px dashed rgba(234, 227, 220, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(251, 248, 245, 0.7);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .photo-preview.has-photo {
            border-color: #c17c60;
            border-style: solid;
            box-shadow: 0 8px 20px rgba(193, 124, 96, 0.2);
        }
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-preview .placeholder {
            font-size: 40px;
            color: #d4c5b2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .photo-preview .placeholder span {
            font-size: 11px;
            color: #b8a99c;
        }
        .photo-upload .upload-actions { flex: 1; min-width: 200px; }
        .photo-upload .upload-actions .btn-upload {
            background: rgba(255, 255, 255, 0.8);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6a5a4a;
        }
        .photo-upload .upload-actions .btn-upload:hover {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.05);
            color: #c17c60;
            transform: translateY(-2px);
        }
        .photo-upload .upload-actions .btn-remove-photo {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1.5px solid rgba(239, 68, 68, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .photo-upload .upload-actions .btn-remove-photo:hover {
            background: rgba(239, 68, 68, 0.15);
            transform: translateY(-2px);
        }
        .photo-upload .upload-actions .file-input { display: none; }

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
            .photo-preview { width: 90px; height: 90px; }
            .photo-upload { flex-direction: column; align-items: center; }
            .invitation-toggle-header { flex-direction: column; align-items: stretch; }
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
                <h4><i class="bi bi-person-plus-fill"></i> Ajouter un invité</h4>
                <small><i class="bi bi-people"></i> Créer un nouvel invité</small>
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

                <h5 class="form-title"><i class="bi bi-person-plus-fill"></i> Nouvel invité</h5>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <?php if (empty($evenementsDisponibles)): ?>
                    <div class="alert-warning-custom">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Attention :</strong> Vous n'êtes associé à aucun événement.
                            <br>
                            Contactez un administrateur pour être ajouté à un événement avant de pouvoir créer des invités.
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    
                    <!-- ⭐ SÉLECTION DE L'ÉVÉNEMENT -->
                    <div class="event-select-container">
                        <label class="form-label">
                            <i class="bi bi-calendar-event-fill"></i> Événement * 
                            <span style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;font-size:9px;padding:2px 8px;border-radius:10px;margin-left:5px;font-weight:700;">OBLIGATOIRE</span>
                        </label>
                        <?php if (empty($evenementsDisponibles)): ?>
                            <div class="form-text text-danger">
                                <i class="bi bi-x-circle"></i> Aucun événement disponible
                            </div>
                        <?php else: ?>
                            <select class="form-select" name="id_evenement" required>
                                <option value="">— Sélectionnez un événement —</option>
                                <?php foreach ($evenementsDisponibles as $ev): ?>
                                    <option value="<?php echo $ev['id']; ?>" 
                                            <?php echo $id_evenement == $ev['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ev['nom']); ?>
                                        — <?php echo date('d/m/Y', strtotime($ev['date_evenement'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle"></i> 
                                L'invité sera rattaché à cet événement.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informations personnelles -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-fill"></i> Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" placeholder="Prénom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-fill"></i> Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom); ?>" placeholder="Nom" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-envelope-fill"></i> Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email">
                            <div class="form-text">Au moins un moyen de contact</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-phone-fill"></i> Téléphone</label>
                            <input type="text" class="form-control" name="telephone" value="<?php echo htmlspecialchars($telephone); ?>" placeholder="+243 8X XXX XXX">
                            <div class="form-text">Au moins un moyen de contact</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-tags-fill"></i> Catégorie</label>
                            <select class="form-select" name="id_categorie">
                                <option value="">Sans catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $id_categorie == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-building"></i> Entreprise</label>
                            <input type="text" class="form-control" name="entreprise" value="<?php echo htmlspecialchars($entreprise); ?>" placeholder="Entreprise">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                        <input type="text" class="form-control" name="adresse" value="<?php echo htmlspecialchars($adresse); ?>" placeholder="Adresse complète">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-people-fill"></i> Nombre de personnes <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="nombre_personnes" value="<?php echo $nombre_personnes; ?>" min="1" max="100" required>
                            <div class="form-text">Personnes autorisées par invitation</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-chat-dots-fill"></i> Préférence de contact</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php foreach ($contactPreferences as $pref): 
                                    $icon = $pref == 'EMAIL' ? 'bi-envelope-fill' : ($pref == 'WHATSAPP' ? 'bi-whatsapp' : ($pref == 'TELEGRAM' ? 'bi-telegram' : 'bi-chat-fill'));
                                    $color = $pref == 'EMAIL' ? '#6c757d' : ($pref == 'WHATSAPP' ? '#25D366' : ($pref == 'TELEGRAM' ? '#0088cc' : '#4CAF50'));
                                ?>
                                    <div class="preference-option">
                                        <input type="radio" name="contact_preference" value="<?php echo $pref; ?>" 
                                               id="pref_<?php echo $pref; ?>"
                                               <?php echo $contact_preference == $pref ? 'checked' : ''; ?>>
                                        <label for="pref_<?php echo $pref; ?>">
                                            <span class="pref-icon" style="color: <?php echo $color; ?>;">
                                                <i class="bi <?php echo $icon; ?>"></i>
                                            </span>
                                            <?php echo $pref; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ⭐ SECTION INVITATION AUTOMATIQUE (sans choix de statut) -->
                    <div class="invitation-auto-section" id="invitationSection">
                        <div class="invitation-toggle-header">
                            <div class="title-wrapper">
                                <div class="icon-badge">
                                    <i class="bi bi-envelope-check-fill"></i>
                                </div>
                                <div class="title-text">
                                    <h6>Créer automatiquement l'invitation</h6>
                                    <p>Un code unique sera généré pour cet invité</p>
                                </div>
                            </div>
                            <label class="form-switch-custom">
                                <input type="checkbox" name="creer_invitation" id="creer_invitation" value="1" checked>
                                <span class="switch-track"></span>
                            </label>
                        </div>

                        <!-- ⭐ Statut automatique : EN_ATTENTE -->
                        <input type="hidden" name="statut_invitation" value="EN_ATTENTE">
                        <div class="invitation-auto-info">
                            <div class="info-status-badge">
                                <i class="bi bi-clock-history"></i>
                                L'invitation sera créée avec le statut <strong>En attente</strong>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION PHOTO -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-image-fill"></i> Photo</label>
                        <div class="photo-upload">
                            <div class="photo-preview" id="photoPreview">
                                <div class="placeholder">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Pas de photo</span>
                                </div>
                            </div>
                            <div class="upload-actions">
                                <button type="button" class="btn-upload" id="btnUploadPhoto">
                                    <i class="bi bi-cloud-upload-fill"></i> Choisir une photo
                                </button>
                                <button type="button" class="btn-remove-photo" id="btnRemovePhoto" style="display: none;">
                                    <i class="bi bi-trash-fill"></i> Supprimer
                                </button>
                                <input type="file" class="file-input" name="photo" id="photoInput" accept="image/*">
                                <div class="form-text mt-2">
                                    Formats acceptés : JPG, PNG, GIF, WEBP • Taille max : 5 Mo
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-chat-left-text-fill"></i> Remarques</label>
                        <textarea class="form-control" name="remarque" rows="2" placeholder="Remarques particulières"><?php echo htmlspecialchars($remarque); ?></textarea>
                    </div>

                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn btn-save" <?php echo empty($evenementsDisponibles) ? 'disabled' : ''; ?>>
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                        <a href="index.php" class="btn btn-cancel">
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

// ========== ⭐ TOGGLE SECTION INVITATION ==========
const creerInvitationCheckbox = document.getElementById('creer_invitation');
const invitationSection = document.getElementById('invitationSection');

if (creerInvitationCheckbox) {
    creerInvitationCheckbox.addEventListener('change', function() {
        if (this.checked) {
            invitationSection.classList.remove('disabled');
        } else {
            invitationSection.classList.add('disabled');
        }
    });
}

// ========== GESTION DE LA PHOTO ==========
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const btnUpload = document.getElementById('btnUploadPhoto');
    const btnRemove = document.getElementById('btnRemovePhoto');

    photoInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.innerHTML = `<img src="${e.target.result}" alt="Photo">`;
                photoPreview.classList.add('has-photo');
                btnRemove.style.display = 'inline-flex';
            };
            reader.readAsDataURL(file);
        }
    });

    btnUpload.addEventListener('click', function() {
        photoInput.click();
    });

    btnRemove.addEventListener('click', function() {
        photoInput.value = '';
        photoPreview.innerHTML = `
            <div class="placeholder">
                <i class="bi bi-person-circle"></i>
                <span>Pas de photo</span>
            </div>
        `;
        photoPreview.classList.remove('has-photo');
        btnRemove.style.display = 'none';
    });
});
</script>
</body>
</html>