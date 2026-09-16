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

// ⭐ VÉRIFICATION PERMISSION
requirePermission('boissons.modifier');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// ⭐ RESTRICTION ADMIN
if (!isAdmin()) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🔒</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Seul un administrateur peut associer des boissons aux événements.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour à la liste
        </a>
    </div>');
}

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// ============================================
// VARIABLES
// ============================================

$error = '';

// Récupérer la boisson
$boisson = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, type FROM boissons WHERE id = ?");
    $stmt->execute([$id]);
    $boisson = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erreur chargement boisson: ' . $e->getMessage());
}

if (!$boisson) {
    header('Location: index.php');
    exit;
}

// Récupérer les événements où la boisson est associée (avec choix_multiple)
$evenementsAssocies = [];
$choixMultipleActuel = 0;
try {
    $stmt = $pdo->prepare("SELECT id_evenement, choix_multiple FROM evenement_boissons WHERE id_boisson = ?");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evenementsAssocies[] = (int)$row['id_evenement'];
        if ((int)$row['choix_multiple'] === 1) {
            $choixMultipleActuel = 1;
        }
    }
} catch (PDOException $e) {
    error_log('Erreur associations: ' . $e->getMessage());
}

// ⭐ Récupérer TOUS les événements (admin seulement)
$evenements = [];
try {
    $stmt = $pdo->query("
        SELECT id, nom, date_evenement, statut 
        FROM evenements 
        WHERE statut != 'ANNULE' 
        ORDER BY date_evenement DESC, nom ASC
    ");
    $evenements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur liste événements: ' . $e->getMessage());
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evenementsSelectionnes = isset($_POST['evenements']) && is_array($_POST['evenements']) 
        ? array_map('intval', $_POST['evenements']) 
        : [];
    $choix_multiple = isset($_POST['choix_multiple']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        // Supprimer les associations actuelles
        $stmt = $pdo->prepare("DELETE FROM evenement_boissons WHERE id_boisson = ?");
        $stmt->execute([$id]);

        // Ajouter les nouvelles associations
        if (!empty($evenementsSelectionnes)) {
            $stmt = $pdo->prepare("
                INSERT INTO evenement_boissons (id_evenement, id_boisson, choix_multiple, actif) 
                VALUES (?, ?, ?, 1)
            ");
            foreach ($evenementsSelectionnes as $eventId) {
                $eventId = (int)$eventId;
                if ($eventId > 0) {
                    $stmt->execute([$eventId, $id, $choix_multiple]);
                }
            }
        }

        $pdo->commit();

        if (function_exists('logAction')) {
            logAction($user['id'], 'ASSOCIATE_DRINK', 'boissons', 
                      "Association de la boisson '{$boisson['nom']}' (ID: $id) à " . count($evenementsSelectionnes) . " événement(s)");
        }

        header('Location: index.php?success=associe');
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erreur association: ' . $e->getMessage());
        $error = 'Erreur lors de l\'association : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associer la boisson - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: rgba(26, 26, 26, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }
        .modal-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 35px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== HEADER ========== */
        .modal-header-custom {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.2);
        }
        .modal-header-custom .header-icon {
            font-size: 26px;
            color: white;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            padding: 12px;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(193, 124, 96, 0.25);
            flex-shrink: 0;
        }
        .modal-header-custom h4 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 20px;
        }
        .modal-header-custom small {
            color: #9a8a7f;
            font-size: 13px;
            display: block;
            margin-top: 2px;
        }
        .modal-header-custom small strong {
            color: #c17c60;
        }

        /* ========== INFO BOISSON ========== */
        .boisson-info-card {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.08), rgba(212, 165, 116, 0.08));
            border: 1.5px solid rgba(193, 124, 96, 0.2);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .boisson-info-card i {
            font-size: 22px;
            color: #c17c60;
        }
        .boisson-info-card .info-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
        }
        .boisson-info-card .info-desc {
            color: #9a8a7f;
            font-size: 12px;
        }

        /* ========== SECTION ÉVÉNEMENTS ========== */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-header .section-title {
            font-weight: 700;
            color: #6a5a4a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .section-header .section-title i { color: #c17c60; }

        .counter-badge {
            background: rgba(193, 124, 96, 0.15);
            color: #c17c60;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .select-all {
            background: rgba(255, 255, 255, 0.9);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            padding: 6px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6a5a4a;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .select-all:hover {
            background: rgba(193, 124, 96, 0.1);
            border-color: #c17c60;
            color: #c17c60;
        }

        .event-grid {
            max-height: 350px;
            overflow-y: auto;
            margin: 12px 0;
            padding-right: 5px;
        }
        .event-grid::-webkit-scrollbar { width: 5px; }
        .event-grid::-webkit-scrollbar-track {
            background: rgba(251, 248, 245, 0.5);
            border-radius: 10px;
        }
        .event-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            border-radius: 10px;
        }

        .event-item {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.7);
        }
        .event-item:hover {
            background: rgba(251, 248, 245, 0.9);
            border-color: #c17c60;
            transform: translateX(3px);
        }
        .event-item.checked {
            background: rgba(193, 124, 96, 0.08);
            border-color: #c17c60;
            box-shadow: 0 2px 8px rgba(193, 124, 96, 0.1);
        }
        .event-item input[type="checkbox"] {
            accent-color: #c17c60;
            width: 18px;
            height: 18px;
            margin-right: 12px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .event-item .event-info {
            flex: 1;
            min-width: 0;
        }
        .event-item .event-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .event-item .event-meta {
            font-size: 12px;
            color: #9a8a7f;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .event-item .event-meta i { color: #c17c60; margin-right: 4px; }
        .event-item .event-status {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }
        .event-item .event-status.actif { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .event-item .event-status.brouillon { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .event-item .event-status.termine { background: rgba(59, 130, 246, 0.15); color: #1e40af; }

        /* ========== EMPTY STATE ========== */
        .empty-inline {
            text-align: center;
            padding: 40px 20px;
            color: #9a8a7f;
            background: rgba(251, 248, 245, 0.5);
            border-radius: 12px;
            border: 2px dashed rgba(234, 227, 220, 0.6);
        }
        .empty-inline i {
            font-size: 45px;
            color: #d4c5b2;
            display: block;
            margin-bottom: 12px;
        }
        .empty-inline p { margin: 0; font-size: 13px; }

        /* ========== CHECKBOX PERSONNALISÉ ========== */
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #c17c60;
        }

        /* ========== ALERT ========== */
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
            font-size: 14px;
        }
        .alert-error i {
            color: #dc2626;
            font-size: 18px;
            flex-shrink: 0;
        }

        .form-text {
            color: #9a8a7f;
            font-size: 12px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 576px) {
            body { padding: 10px; }
            .modal-card { padding: 20px; border-radius: 20px; }
            .modal-header-custom h4 { font-size: 17px; }
            .modal-header-custom .header-icon { font-size: 20px; padding: 10px; }
            .event-item { flex-wrap: wrap; }
            .event-item .event-status { margin-top: 6px; }
            .btn-save, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
            .d-flex.gap-3 {
                flex-direction: column;
                gap: 10px !important;
            }
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<div class="modal-card">
    
    <!-- EN-TÊTE -->
    <div class="modal-header-custom">
        <div class="header-icon">
            <i class="bi bi-link-45deg"></i>
        </div>
        <div>
            <h4>Associer la boisson</h4>
            <small>Attribuer <strong><?php echo htmlspecialchars($boisson['nom']); ?></strong> à des événements</small>
        </div>
    </div>

    <!-- INFO BOISSON -->
    <div class="boisson-info-card">
        <i class="bi bi-cup-straw"></i>
        <div>
            <div class="info-name"><?php echo htmlspecialchars($boisson['nom']); ?></div>
            <div class="info-desc">
                <?php 
                $typeLabels = ['SANS_ALCOOL' => 'Sans alcool 🧃', 'ALCOOL' => 'Alcool 🍷', 'CHAUD' => 'Chaud ☕', 'AUTRE' => 'Autre 🍹'];
                echo $typeLabels[$boisson['type']] ?? $boisson['type']; 
                ?>
            </div>
        </div>
    </div>

    <!-- ERREUR -->
    <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        
        <!-- SECTION ÉVÉNEMENTS -->
        <div class="section-header">
            <div class="section-title">
                <i class="bi bi-calendar-event-fill"></i> 
                Événements disponibles
                <span class="counter-badge" id="counter">
                    <?php echo count($evenementsAssocies); ?> / <?php echo count($evenements); ?>
                </span>
            </div>
            <?php if (!empty($evenements)): ?>
                <button type="button" class="select-all" id="selectAllBtn">
                    <i class="bi bi-check-all"></i> Tout sélectionner
                </button>
            <?php endif; ?>
        </div>

        <div class="event-grid" id="eventGrid">
            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $event): 
                    $isChecked = in_array((int)$event['id'], $evenementsAssocies);
                    $statutClass = strtolower($event['statut']);
                ?>
                    <label class="event-item <?php echo $isChecked ? 'checked' : ''; ?>" for="event_<?php echo $event['id']; ?>">
                        <input type="checkbox" 
                               name="evenements[]" 
                               value="<?php echo $event['id']; ?>" 
                               id="event_<?php echo $event['id']; ?>"
                               class="event-checkbox"
                               <?php echo $isChecked ? 'checked' : ''; ?>>
                        <div class="event-info">
                            <div class="event-name"><?php echo htmlspecialchars($event['nom']); ?></div>
                            <div class="event-meta">
                                <span><i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?></span>
                            </div>
                        </div>
                        <span class="event-status <?php echo $statutClass; ?>">
                            <?php echo htmlspecialchars($event['statut']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-inline">
                    <i class="bi bi-calendar-x"></i>
                    <p>Aucun événement disponible</p>
                    <small>Créez d'abord un événement pour pouvoir y associer des boissons.</small>
                </div>
            <?php endif; ?>
        </div>

        <!-- OPTION CHOIX MULTIPLE -->
        <div class="mt-3 p-3" style="background: rgba(251, 248, 245, 0.5); border-radius: 12px; border: 1px solid rgba(234, 227, 220, 0.5);">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="choix_multiple" id="choix_multiple" 
                       <?php echo $choixMultipleActuel ? 'checked' : ''; ?>>
                <label class="form-check-label" for="choix_multiple">
                    <i class="bi bi-check-all" style="color: #c17c60;"></i> Choix multiple
                </label>
                <div class="form-text">Permet aux invités de choisir plusieurs quantités de cette boisson.</div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="d-flex gap-3 mt-4 flex-wrap">
            <button type="submit" class="btn-save">
                <i class="bi bi-save-fill"></i> Enregistrer les associations
            </button>
            <a href="index.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </form>
</div>

<script>
// ========== SÉLECTION TOUT / RIEN ==========
const selectAllBtn = document.getElementById('selectAllBtn');
const eventCheckboxes = document.querySelectorAll('.event-checkbox');
const counter = document.getElementById('counter');
const totalEvents = <?php echo count($evenements); ?>;

function updateCounter() {
    const checked = document.querySelectorAll('.event-checkbox:checked').length;
    if (counter) counter.textContent = checked + ' / ' + totalEvents;
}

if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="evenements[]"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            const parent = cb.closest('.event-item');
            if (parent) {
                if (cb.checked) parent.classList.add('checked');
                else parent.classList.remove('checked');
            }
        });
        this.innerHTML = allChecked ? 
            '<i class="bi bi-check-all"></i> Tout sélectionner' : 
            '<i class="bi bi-x-circle"></i> Tout désélectionner';
        updateCounter();
    });
}

// ========== GESTION VISUELLE DES ITEMS ==========
document.querySelectorAll('.event-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const parent = this.closest('.event-item');
        if (parent) {
            if (this.checked) parent.classList.add('checked');
            else parent.classList.remove('checked');
        }
        updateCounter();
    });
});

// Initialiser le compteur
updateCounter();
</script>

</body>
</html>