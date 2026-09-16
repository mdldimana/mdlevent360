<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';
requirePermission('evenements.supprimer');

$user = getCurrentUser();
$pdo = getDbConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) {
    header('Location: index.php?success=erreur');
    exit;
}

// ⭐ VÉRIFICATION DES DROITS D'ACCÈS
if (!userCanAccessEvenement($pdo, (int)$user['id'], $id)) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour supprimer cet événement.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour à la liste
        </a>
    </div>');
}

// Vérifier que l'événement existe
$stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ?');
$stmt->execute([$id]);
$evenement = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evenement) {
    header('Location: index.php?success=erreur');
    exit;
}

/*
|--------------------------------------------------------------------------
| CHEMINS
|--------------------------------------------------------------------------
*/
$uploadDirHost = __DIR__ . '/../../uploads/photos_host/';
$uploadDirFond = __DIR__ . '/../../uploads/fonds/';
$uploadDirHost = rtrim($uploadDirHost, '/\\') . DIRECTORY_SEPARATOR;
$uploadDirFond = rtrim($uploadDirFond, '/\\') . DIRECTORY_SEPARATOR;

/*
|--------------------------------------------------------------------------
| SUPPRESSION (uniquement en POST pour sécurité)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        header('Location: index.php?success=erreur');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $nomEvenement = $evenement['nom'];
        
        // 1. Supprimer les photos du disque
        $stmt = $pdo->prepare('SELECT photo FROM photo_host WHERE id_evenement = ?');
        $stmt->execute([$id]);
        $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($photos as $photo) {
            $filePath = $uploadDirHost . basename($photo);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // 2. Supprimer la photo de fond du disque
        if (!empty($evenement['fond'])) {
            $fondPath = $uploadDirFond . basename($evenement['fond']);
            if (file_exists($fondPath)) {
                @unlink($fondPath);
            }
        }
        
        // 3. Supprimer les photos de la BDD (CASCADE devrait le faire, mais on assure)
        $pdo->prepare('DELETE FROM photo_host WHERE id_evenement = ?')->execute([$id]);
        
        // 4. Supprimer les associations utilisateurs
        $pdo->prepare('DELETE FROM evenements_utilisateurs WHERE id_evenement = ?')->execute([$id]);
        
        // 5. Supprimer l'événement (CASCADE supprimera invitations, etc.)
        $pdo->prepare('DELETE FROM evenements WHERE id = ?')->execute([$id]);
        
        $pdo->commit();
        
        // Log
        if (function_exists('logAction')) {
            logAction($user['id'] ?? null, 'DELETE_EVENT', 'evenements', "Suppression événement '$nomEvenement' (ID: $id)");
        }
        
        header('Location: index.php?success=supprime');
        exit;
        
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erreur suppression événement: ' . $e->getMessage());
        header('Location: index.php?success=erreur');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| AFFICHAGE DE LA CONFIRMATION
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }
        .delete-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            max-width: 550px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .delete-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            color: #dc2626;
            animation: pulse 2s ease infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .delete-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        .delete-subtitle {
            color: #6a5a4a;
            font-size: 15px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .event-info {
            background: rgba(251, 248, 245, 0.8);
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }
        .event-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: #6a5a4a;
            font-size: 14px;
        }
        .event-info-item:not(:last-child) {
            border-bottom: 1px dashed rgba(234, 227, 220, 0.6);
        }
        .event-info-item i {
            color: #c17c60;
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .event-info-item strong {
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .warning-box {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            color: #991b1b;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
        }
        .warning-box i {
            color: #dc2626;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .delete-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            font-weight: 700;
            padding: 14px 30px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            font-size: 14px;
            cursor: pointer;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
            color: white;
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.9);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            font-weight: 600;
            padding: 14px 30px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: white;
            color: #c17c60;
            border-color: #c17c60;
        }
        
        @media (max-width: 576px) {
            .delete-card { padding: 25px; }
            .delete-icon { width: 80px; height: 80px; font-size: 35px; }
            .delete-title { font-size: 20px; }
            .delete-actions { flex-direction: column; }
            .btn-delete, .btn-cancel { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="delete-card">
    <div class="delete-icon">
        <i class="bi bi-exclamation-triangle-fill"></i>
    </div>
    
    <h1 class="delete-title">Confirmer la suppression</h1>
    <p class="delete-subtitle">
        Vous êtes sur le point de supprimer définitivement cet événement.
    </p>
    
    <div class="event-info">
        <div class="event-info-item">
            <i class="bi bi-tag-fill"></i>
            <span><strong><?php echo htmlspecialchars($evenement['nom']); ?></strong></span>
        </div>
        <div class="event-info-item">
            <i class="bi bi-calendar-event"></i>
            <span><?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?></span>
        </div>
        <div class="event-info-item">
            <i class="bi bi-geo-alt-fill"></i>
            <span><?php echo htmlspecialchars($evenement['lieu']); ?></span>
        </div>
        <div class="event-info-item">
            <i class="bi bi-circle-fill" style="font-size:8px;color:#c17c60;"></i>
            <span>Statut : <strong><?php echo htmlspecialchars($evenement['statut']); ?></strong></span>
        </div>
    </div>
    
    <div class="warning-box">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div>
            <strong>Attention :</strong> Cette action est irréversible.
            Toutes les invitations, présences, photos et associations utilisateurs
            liées à cet événement seront également supprimées.
        </div>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
        <div class="delete-actions">
            <a href="index.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Annuler
            </a>
            <button type="submit" class="btn-delete" onclick="return confirm('Êtes-vous vraiment sûr de vouloir supprimer cet événement ?');">
                <i class="bi bi-trash-fill"></i> Supprimer définitivement
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>