<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invitations.creer');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// ============================================
// ⭐ RÉCUPÉRATION DES ÉVÉNEMENTS ACCESSIBLES
// ============================================
$evenements = getEvenementsPourSelect($pdo);

// ============================================
// VARIABLES
// ============================================
$error = '';
$success = '';
$id_evenement = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$selected_invites = [];

// ============================================
// ⭐ RÉCUPÉRATION DES INVITÉS ACCESSIBLES (filtrés par événement)
// ============================================
$invites = [];
$invites_existants = [];

if ($id_evenement > 0) {
    // Vérifier l'accès à cet événement
    if (!userCanAccessEvenement($pdo, (int)$user['id'], $id_evenement)) {
        $error = 'Vous n\'avez pas accès à cet événement.';
        $id_evenement = 0;
    } else {
        // Invités de cet événement uniquement
        try {
            $stmt = $pdo->prepare("
                SELECT id, nom, prenom, email, telephone 
                FROM invites 
                WHERE actif = 1 AND id_evenement = ?
                ORDER BY nom, prenom
            ");
            $stmt->execute([$id_evenement]);
            $invites = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erreur chargement invités: ' . $e->getMessage());
        }

        // Invités déjà invités à cet événement
        try {
            $stmt = $pdo->prepare("SELECT id_invite FROM invitations WHERE id_evenement = ?");
            $stmt->execute([$id_evenement]);
            $invites_existants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('Erreur chargement invitations existantes: ' . $e->getMessage());
        }
    }
} else {
    // Pas d'événement sélectionné → on ne charge pas les invités
    $invites = [];
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_evenement = (int)($_POST['id_evenement'] ?? 0);
    $selected_invites = $_POST['invites'] ?? [];
    // ⭐ Statut forcé à EN_ATTENTE (pas de choix utilisateur)
    $statut = 'EN_ATTENTE';

    $errors = [];

    // ⭐ VALIDATION ÉVÉNEMENT
    if ($id_evenement <= 0) {
        $errors[] = 'Veuillez sélectionner un événement.';
    } elseif (!userCanAccessEvenement($pdo, (int)$user['id'], $id_evenement)) {
        $errors[] = 'Vous n\'avez pas accès à cet événement.';
    }

    if (empty($selected_invites)) {
        $errors[] = 'Veuillez sélectionner au moins un invité.';
    }

    // ⭐ VALIDATION : tous les invités doivent appartenir à cet événement
    if (empty($errors) && $id_evenement > 0) {
        $placeholders = implode(',', array_fill(0, count($selected_invites), '?'));
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM invites 
            WHERE id IN ($placeholders) AND id_evenement = ?
        ");
        $checkStmt->execute(array_merge($selected_invites, [$id_evenement]));
        if ((int)$checkStmt->fetchColumn() !== count($selected_invites)) {
            $errors[] = 'Certains invités ne font pas partie de cet événement.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $createdCount = 0;
            $stmt = $pdo->prepare("
                INSERT INTO invitations (id_evenement, id_invite, code_unique, statut, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            foreach ($selected_invites as $invite_id) {
                $invite_id = (int)$invite_id;
                
                // Générer un code unique
                $code_unique = 'INV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
                
                $checkStmt = $pdo->prepare("SELECT id FROM invitations WHERE code_unique = ?");
                $checkStmt->execute([$code_unique]);
                while ($checkStmt->fetch()) {
                    $code_unique = 'INV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
                    $checkStmt->execute([$code_unique]);
                }

                // Vérifier que l'invité n'est pas déjà invité
                $checkStmt = $pdo->prepare("
                    SELECT id FROM invitations WHERE id_evenement = ? AND id_invite = ?
                ");
                $checkStmt->execute([$id_evenement, $invite_id]);
                if (!$checkStmt->fetch()) {
                    $stmt->execute([$id_evenement, $invite_id, $code_unique, $statut]);
                    $createdCount++;
                }
            }

            $pdo->commit();

            logAction($user['id'], 'CREATE_INVITATIONS', 'invitations', 
                      "Création de $createdCount invitation(s) pour l'événement ID: $id_evenement");

            header('Location: index.php?success=ajoute&count=' . $createdCount);
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Erreur lors de la création : ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Recharger les invités si un événement est sélectionné
if ($id_evenement > 0 && empty($invites)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, nom, prenom, email, telephone 
            FROM invites 
            WHERE actif = 1 AND id_evenement = ?
            ORDER BY nom, prenom
        ");
        $stmt->execute([$id_evenement]);
        $invites = $stmt->fetchAll();
    } catch (PDOException $e) {}

    try {
        $stmt = $pdo->prepare("SELECT id_invite FROM invitations WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $invites_existants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer des invitations - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&display=swap" rel="stylesheet">
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
            max-width: 950px;
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

        /* ⭐ Info statut badge */
        .info-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(193, 124, 96, 0.08);
            border: 1px solid rgba(193, 124, 96, 0.2);
            border-radius: 10px;
            font-size: 13px;
            color: #6a5a4a;
            margin-bottom: 18px;
        }

        .info-status-badge i {
            color: #c17c60;
            font-size: 16px;
        }

        .info-status-badge strong {
            color: #c17c60;
            font-weight: 700;
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
        .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
            color: #92400e;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .alert-warning-custom i { font-size: 20px; flex-shrink: 0; color: #f59e0b; }
        .alert-warning-custom strong { color: #78350f; }

        /* ========== ⭐ ÉTAPE 1 : SÉLECTION ÉVÉNEMENT ========== */
        .step-card {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.05), rgba(212, 165, 116, 0.05));
            border: 2px solid rgba(193, 124, 96, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .step-card .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
            margin-right: 10px;
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.3);
        }
        .step-card .step-title {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .step-card .form-select {
            font-weight: 600;
            font-size: 15px;
            padding: 12px 15px;
            border-color: #c17c60;
            background: white;
        }

        /* ========== ⭐ ÉTAPE 2 : SÉLECTION INVITÉS ========== */
        .guest-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-height: 450px;
            overflow-y: auto;
            padding: 5px;
            margin-top: 10px;
        }
        .guest-grid::-webkit-scrollbar { width: 6px; }
        .guest-grid::-webkit-scrollbar-track { background: rgba(251, 248, 245, 0.5); border-radius: 10px; }
        .guest-grid::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c17c60, #d4a574); border-radius: 10px; }

        .guest-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.7);
        }
        .guest-item:hover {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.04);
            transform: translateX(3px);
        }
        .guest-item.checked {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.08);
            box-shadow: 0 2px 8px rgba(193, 124, 96, 0.1);
        }
        .guest-item input[type="checkbox"] {
            accent-color: #c17c60;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .guest-item .guest-info { flex: 1; min-width: 0; }
        .guest-item .guest-info .guest-name { font-weight: 600; color: #1a1a1a; font-size: 14px; }
        .guest-item .guest-info .guest-contact {
            font-size: 11px;
            color: #9a8a7f;
            margin-top: 3px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .guest-item .guest-info .guest-contact i { color: #c17c60; margin-right: 4px; }
        .guest-item .guest-status {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            flex-shrink: 0;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .guest-item .guest-status.already { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .guest-item .guest-status.available { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .guest-item.opacity-50 { opacity: 0.5; cursor: not-allowed; background: rgba(251, 248, 245, 0.5); }
        .guest-item.opacity-50:hover { transform: none; border-color: rgba(234, 227, 220, 0.5); background: rgba(251, 248, 245, 0.5); }

        .select-all {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            border: 1.5px solid rgba(193, 124, 96, 0.3);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            color: #c17c60;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .select-all:hover {
            background: rgba(193, 124, 96, 0.1);
            border-color: #c17c60;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.2);
        }

        .selected-count {
            font-size: 12px;
            color: #c17c60;
            white-space: nowrap;
            background: rgba(193, 124, 96, 0.1);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            border: 1px solid rgba(193, 124, 96, 0.2);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state-inline {
            text-align: center;
            padding: 40px 20px;
            background: rgba(251, 248, 245, 0.5);
            border-radius: 16px;
            border: 2px dashed rgba(234, 227, 220, 0.6);
        }
        .empty-state-inline i {
            font-size: 50px;
            color: #d4c5b2;
            display: block;
            margin-bottom: 12px;
        }
        .empty-state-inline p { color: #9a8a7f; margin: 0 0 15px; font-weight: 500; }
        .empty-state-inline small { color: #b8a99c; display: block; margin-bottom: 15px; }

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
            .guest-grid { grid-template-columns: 1fr; max-height: 350px; }
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
            .guest-grid { max-height: 280px; gap: 6px; }
            .guest-item { padding: 10px 12px; flex-wrap: wrap; }
            .guest-item .guest-info .guest-name { font-size: 13px; }
            .btn-save, .btn-cancel { width: 100%; justify-content: center; padding: 10px 16px; font-size: 13px; }
            .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
            .select-all, .selected-count { font-size: 11px; padding: 5px 10px; }
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
                <h4><i class="bi bi-plus-circle"></i> Créer des invitations</h4>
                <small><i class="bi bi-envelope"></i> Générer des invitations en masse</small>
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

                <h5 class="form-title"><i class="bi bi-envelope-plus"></i> Créer des invitations</h5>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <?php if (empty($evenements)): ?>
                    <div class="alert-warning-custom">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Attention :</strong> Vous n'êtes associé à aucun événement.
                            <br>
                            Contactez un administrateur pour être ajouté à un événement avant de créer des invitations.
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="invitationForm">
                    
                    <!-- ⭐ ÉTAPE 1 : SÉLECTION DE L'ÉVÉNEMENT -->
                    <div class="step-card">
                        <div class="step-title">
                            <span class="step-number">1</span>
                            Sélectionnez l'événement
                        </div>
                        <select class="form-select" name="id_evenement" id="id_evenement" required>
                            <option value="">-- Choisissez un événement --</option>
                            <?php foreach ($evenements as $e): ?>
                                <option value="<?php echo $e['id']; ?>" 
                                        <?php echo $id_evenement == $e['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['nom']); ?> 
                                    — <?php echo date('d/m/Y', strtotime($e['date_evenement'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            Les invités affichés ci-dessous seront uniquement ceux de cet événement.
                        </div>
                    </div>

                    <!-- ⭐ ÉTAPE 2 : INVITÉS (uniquement si événement sélectionné) -->
                    <?php if ($id_evenement > 0): ?>
                        <div class="step-card">
                            <div class="step-title">
                                <span class="step-number">2</span>
                                Sélectionnez les invités
                            </div>

                            <!-- ⭐ Statut automatique : EN_ATTENTE -->
                            <input type="hidden" name="statut" value="EN_ATTENTE">
                            <div class="info-status-badge">
                                <i class="bi bi-clock-history"></i>
                                Les invitations seront créées avec le statut <strong>En attente</strong>.
                            </div>

                            <!-- Barre d'action -->
                            <?php if (!empty($invites)): ?>
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="selected-count" id="selectedCount">0 sélectionné(s)</span>
                                    </div>
                                    <button type="button" class="select-all" id="selectAllBtn">
                                        <i class="bi bi-check-all"></i> Tout sélectionner
                                    </button>
                                </div>

                                <!-- Grille des invités -->
                                <div class="guest-grid" id="guestGrid">
                                    <?php foreach ($invites as $invite): 
                                        $alreadyInvited = in_array($invite['id'], $invites_existants);
                                    ?>
                                        <label class="guest-item <?php echo $alreadyInvited ? 'opacity-50' : ''; ?> <?php echo in_array($invite['id'], $selected_invites) ? 'checked' : ''; ?>">
                                            <input type="checkbox" name="invites[]" value="<?php echo $invite['id']; ?>" 
                                                   id="invite_<?php echo $invite['id']; ?>"
                                                   class="guest-checkbox"
                                                   <?php echo $alreadyInvited ? 'disabled' : ''; ?>
                                                   <?php echo in_array($invite['id'], $selected_invites) ? 'checked' : ''; ?>>
                                            <div class="guest-info">
                                                <div class="guest-name">
                                                    <?php echo htmlspecialchars($invite['prenom'] . ' ' . $invite['nom']); ?>
                                                </div>
                                                <div class="guest-contact">
                                                    <?php if ($invite['email']): ?>
                                                        <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($invite['email']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($invite['telephone']): ?>
                                                        <span><i class="bi bi-phone-fill"></i> <?php echo htmlspecialchars($invite['telephone']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($alreadyInvited): ?>
                                                <span class="guest-status already">Déjà invité</span>
                                            <?php else: ?>
                                                <span class="guest-status available">Disponible</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-inline">
                                    <i class="bi bi-people"></i>
                                    <p>Aucun invité dans cet événement</p>
                                    <small>Ajoutez d'abord des invités à cet événement</small>
                                    <a href="../invites/creer.php?evenement=<?php echo $id_evenement; ?>" class="btn-save" style="font-size: 13px; padding: 8px 18px;">
                                        <i class="bi bi-plus-circle"></i> Ajouter des invités
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Message d'invite à sélectionner un événement -->
                        <div class="empty-state-inline">
                            <i class="bi bi-calendar-event"></i>
                            <p>Sélectionnez d'abord un événement</p>
                            <small>Les invités de l'événement apparaîtront ici</small>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <button type="submit" class="btn btn-save" id="submitBtn"
                                <?php echo ($id_evenement <= 0 || empty($invites)) ? 'disabled' : ''; ?>>
                            <i class="bi bi-save-fill"></i> Créer les invitations
                        </button>
                        <a href="index.php" class="btn btn-cancel">
                            <i class="bi bi-arrow-left"></i> Annuler
                        </a>
                    </div>
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

// ========== COMPTEUR DES INVITÉS SÉLECTIONNÉS ==========
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.guest-checkbox:checked');
    const count = checkboxes.length;
    const counter = document.getElementById('selectedCount');
    if (counter) counter.textContent = count + ' sélectionné(s)';
    
    // Mettre à jour la classe "checked" sur les labels
    document.querySelectorAll('.guest-item').forEach(item => {
        const cb = item.querySelector('.guest-checkbox');
        if (cb && cb.checked) item.classList.add('checked');
        else item.classList.remove('checked');
    });
}

document.querySelectorAll('.guest-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

// ========== TOUT SÉLECTIONNER / DÉSÉLECTIONNER ==========
const selectAllBtn = document.getElementById('selectAllBtn');
if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.guest-checkbox:not(:disabled)');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
        
        updateSelectedCount();
        this.innerHTML = allChecked ? 
            '<i class="bi bi-check-all"></i> Tout sélectionner' : 
            '<i class="bi bi-x-circle"></i> Tout désélectionner';
    });
}

// ========== CHANGEMENT D'ÉVÉNEMENT ==========
const eventSelect = document.getElementById('id_evenement');
if (eventSelect) {
    eventSelect.addEventListener('change', function() {
        const eventId = this.value;
        if (eventId) {
            window.location.href = 'creer.php?evenement=' + eventId;
        } else {
            window.location.href = 'creer.php';
        }
    });
}

// ========== INITIALISATION ==========
updateSelectedCount();

// ========== CONFIRMATION AVANT SOUMISSION ==========
const form = document.getElementById('invitationForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const selected = document.querySelectorAll('.guest-checkbox:checked');
        if (selected.length === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins un invité.');
            return false;
        }
        
        if (!confirm('Voulez-vous vraiment créer ' + selected.length + ' invitation(s) ?')) {
            e.preventDefault();
            return false;
        }
    });
}
</script>
</body>
</html>