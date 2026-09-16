<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invitations.modifier');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ⭐ VÉRIFICATION DES DROITS D'ACCÈS
if (!userCanAccessInvitation($pdo, (int)$user['id'], $id)) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour modifier cette invitation.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour à la liste
        </a>
    </div>');
}

// Récupérer l'invitation
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               inv.nom AS invite_nom, 
               inv.prenom AS invite_prenom,
               inv.email AS invite_email,
               inv.telephone AS invite_telephone,
               e.nom as evenement_nom,
               e.date_evenement
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invitation = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erreur voir invitation: ' . $e->getMessage());
}

if (!$invitation) {
    header('Location: index.php');
    exit;
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

$error = '';
$statut = $invitation['statut'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statut = $_POST['statut'] ?? $invitation['statut'];

    try {
        $stmt = $pdo->prepare("UPDATE invitations SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $id]);

        logAction($user['id'], 'UPDATE_INVITATION', 'invitations', 
                  "Modification du statut de l'invitation {$invitation['code_unique']} vers $statut (ID: $id)");

        header('Location: index.php?success=modifie');
        exit;

    } catch (PDOException $e) {
        $error = 'Erreur lors de la modification : ' . $e->getMessage();
    }
}

$statuts = ['EN_ATTENTE', 'CONFIRMEE', 'REFUSEE', 'PRESENTE', 'ANNULEE'];
$statutLabels = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE' => 'Confirmée',
    'REFUSEE' => 'Refusée',
    'PRESENTE' => 'Présente',
    'ANNULEE' => 'Annulée'
];

// Couleurs et icônes par statut
$statutConfig = [
    'EN_ATTENTE' => ['color' => '#f59e0b', 'icon' => 'bi-hourglass-split', 'bg' => 'rgba(245, 158, 11, 0.15)', 'text' => '#92400e'],
    'CONFIRMEE' => ['color' => '#10b981', 'icon' => 'bi-check-circle-fill', 'bg' => 'rgba(16, 185, 129, 0.15)', 'text' => '#065f46'],
    'REFUSEE' => ['color' => '#ef4444', 'icon' => 'bi-x-circle-fill', 'bg' => 'rgba(239, 68, 68, 0.15)', 'text' => '#991b1b'],
    'PRESENTE' => ['color' => '#3b82f6', 'icon' => 'bi-person-check-fill', 'bg' => 'rgba(59, 130, 246, 0.15)', 'text' => '#1e40af'],
    'ANNULEE' => ['color' => '#6c757d', 'icon' => 'bi-slash-circle-fill', 'bg' => 'rgba(108, 117, 125, 0.15)', 'text' => '#495057']
];
$config = $statutConfig[$statut] ?? $statutConfig['EN_ATTENTE'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.7), rgba(60, 50, 45, 0.6));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }
        
        .modal-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInUp 0.4s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ========== HEADER ========== */
        .modal-header-custom {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        }
        .modal-header-custom .icon-wrapper {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(193, 124, 96, 0.3);
        }
        .modal-header-custom h4 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
        }
        .modal-header-custom p {
            color: #9a8a7f;
            font-size: 13px;
            margin: 3px 0 0;
        }

        /* ========== INFO CARD ========== */
        .invite-info {
            background: rgba(251, 248, 245, 0.7);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .invite-info .guest-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .invite-info .guest-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.25);
        }
        .invite-info .guest-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 16px;
            line-height: 1.2;
        }
        .invite-info .guest-contact {
            font-size: 12px;
            color: #9a8a7f;
            margin-top: 2px;
        }
        
        .info-divider {
            height: 1px;
            background: rgba(234, 227, 220, 0.6);
            margin: 12px 0;
        }
        
        .info-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 4px 0;
        }
        .info-line .label {
            color: #9a8a7f;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-line .label i { color: #c17c60; font-size: 14px; }
        .info-line .value {
            color: #1a1a1a;
            font-weight: 600;
        }
        .code-badge {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.1), rgba(212, 165, 116, 0.1));
            color: #c17c60;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(193, 124, 96, 0.2);
        }

        /* ========== STATUT ACTUEL ========== */
        .current-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: <?php echo $config['bg']; ?>;
            color: <?php echo $config['text']; ?>;
        }
        .current-status i { font-size: 14px; }

        /* ========== FORM ========== */
        .form-label {
            font-weight: 600;
            color: #6a5a4a;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }
        .form-label i { color: #c17c60; }

        .statut-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 25px;
        }
        .statut-option {
            position: relative;
        }
        .statut-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .statut-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border: 2px solid rgba(234, 227, 220, 0.6);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 600;
            color: #6a5a4a;
        }
        .statut-option label:hover {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.05);
            transform: translateY(-2px);
        }
        .statut-option input[type="radio"]:checked + label {
            border-color: #c17c60;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.1), rgba(212, 165, 116, 0.1));
            color: #c17c60;
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.15);
        }
        .statut-option label .statut-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: rgba(193, 124, 96, 0.1);
            color: #c17c60;
        }
        .statut-option input[type="radio"]:checked + label .statut-icon {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
        }

        /* ========== BOUTONS ========== */
        .actions-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-save {
            flex: 1;
            min-width: 140px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            border: none;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35);
            color: white;
        }
        .btn-cancel {
            flex: 1;
            min-width: 140px;
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-weight: 600;
            padding: 14px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #c17c60;
            border-color: #c17c60;
        }

        /* ========== ALERT ========== */
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
        }
        .alert-error i { color: #dc2626; font-size: 18px; flex-shrink: 0; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 576px) {
            body { padding: 15px; }
            .modal-card { padding: 25px 20px; border-radius: 20px; }
            .modal-header-custom .icon-wrapper { width: 48px; height: 48px; font-size: 20px; }
            .modal-header-custom h4 { font-size: 17px; }
            .statut-options { grid-template-columns: 1fr; }
            .actions-row { flex-direction: column; }
            .btn-save, .btn-cancel { width: 100%; }
        }
    </style>
</head>
<body>

<div class="modal-card">
    
    <!-- HEADER -->
    <div class="modal-header-custom">
        <div class="icon-wrapper">
            <i class="bi bi-pencil-square"></i>
        </div>
        <div>
            <h4>Modifier le statut</h4>
            <p>Changez le statut de cette invitation</p>
        </div>
    </div>

    <!-- INFO INVITATION -->
    <div class="invite-info">
        <div class="guest-row">
            <div class="guest-avatar">
                <?php echo strtoupper(substr($invitation['invite_prenom'] ?? 'U', 0, 1) . substr($invitation['invite_nom'] ?? 'N', 0, 1)); ?>
            </div>
            <div>
                <div class="guest-name">
                    <?php echo htmlspecialchars($invitation['invite_prenom'] . ' ' . $invitation['invite_nom']); ?>
                </div>
                <div class="guest-contact">
                    <?php if (!empty($invitation['invite_email'])): ?>
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($invitation['invite_email']); ?>
                    <?php elseif (!empty($invitation['invite_telephone'])): ?>
                        <i class="bi bi-phone"></i> <?php echo htmlspecialchars($invitation['invite_telephone']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="info-divider"></div>
        
        <div class="info-line">
            <span class="label"><i class="bi bi-upc-scan"></i> Code unique</span>
            <span class="code-badge"><?php echo htmlspecialchars($invitation['code_unique']); ?></span>
        </div>
        <div class="info-line">
            <span class="label"><i class="bi bi-calendar-event"></i> Événement</span>
            <span class="value"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></span>
        </div>
        <div class="info-line">
            <span class="label"><i class="bi bi-shield-fill"></i> Statut actuel</span>
            <span class="current-status">
                <i class="bi <?php echo $config['icon']; ?>"></i>
                <?php echo $statutLabels[$statut] ?? $statut; ?>
            </span>
        </div>
    </div>

    <!-- ERREUR -->
    <?php if ($error): ?>
        <div class="alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE -->
    <form method="POST" action="">
        <label class="form-label">
            <i class="bi bi-shield-check"></i> Nouveau statut
        </label>
        
        <div class="statut-options">
            <?php foreach ($statuts as $s): 
                $cfg = $statutConfig[$s] ?? $statutConfig['EN_ATTENTE'];
            ?>
                <div class="statut-option">
                    <input type="radio" 
                           name="statut" 
                           value="<?php echo $s; ?>" 
                           id="statut_<?php echo $s; ?>"
                           <?php echo $statut == $s ? 'checked' : ''; ?>>
                    <label for="statut_<?php echo $s; ?>">
                        <span class="statut-icon">
                            <i class="bi <?php echo $cfg['icon']; ?>"></i>
                        </span>
                        <?php echo $statutLabels[$s] ?? $s; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions-row">
            <a href="index.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Annuler
            </a>
            <button type="submit" class="btn-save">
                <i class="bi bi-save-fill"></i> Enregistrer
            </button>
        </div>
    </form>

</div>

</body>
</html>