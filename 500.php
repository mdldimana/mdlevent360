<?php
// Inclure la configuration pour avoir APP_NAME
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur Serveur - <?php echo APP_NAME ?? 'Gestion d\'invitations'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
        }
        .error-card {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .error-card .icon {
            font-size: 80px;
            color: #ff4757;
            margin-bottom: 20px;
        }
        .error-card h1 {
            font-size: 72px;
            font-weight: 800;
            color: #1a1a2e;
            margin: 0;
            line-height: 1;
        }
        .error-card h3 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 10px 0;
        }
        .error-card p {
            color: #888;
            margin-bottom: 30px;
            font-size: 15px;
        }
        .error-card .server-icon {
            font-size: 100px;
            color: #f7971e;
            opacity: 0.08;
            position: absolute;
            right: -20px;
            bottom: -30px;
        }
        .btn-home {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e !important;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 151, 30, 0.3);
            color: #1a1a2e !important;
        }
        .btn-retry {
            background: #f8f9fa;
            color: #666 !important;
            border: 1px solid #ddd;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-retry:hover {
            background: #e9ecef;
            color: #333 !important;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h1>500</h1>
        <h3>Erreur Serveur</h3>
        <p>
            Une erreur interne s'est produite sur le serveur.
            <br>
            <small class="text-muted">Veuillez réessayer plus tard ou contacter votre administrateur.</small>
        </p>
        <div class="actions">
            <a href="admin/dashboard.php" class="btn-home">
                <i class="bi bi-house"></i> Tableau de bord
            </a>
            <a href="javascript:location.reload()" class="btn-retry">
                <i class="bi bi-arrow-clockwise"></i> Réessayer
            </a>
        </div>
        <div class="server-icon">
            <i class="bi bi-hdd-stack"></i>
        </div>
    </div>
</body>
</html>