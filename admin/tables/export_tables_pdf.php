<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';

// Fix InfinityFree
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

requirePermission('tables.voir');

$user   = getCurrentUser();
$userId = (int)getCurrentUserId();
$pdo    = getDbConnection();

$evenementId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
if ($evenementId <= 0) die('Erreur : identifiant événement invalide.');

if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, $evenementId)) {
    http_response_code(403);
    die('Accès refusé à cet événement.');
}

// === Événement ===
$evenement = null;
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.nom AS createur_nom, u.prenom AS createur_prenom
        FROM evenements e
        LEFT JOIN utilisateurs u ON u.id = e.created_by
        WHERE e.id = ?
    ");
    $stmt->execute([$evenementId]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur événement PDF tables: ' . $e->getMessage());
}
if (!$evenement) die('Erreur : événement introuvable.');

// === Tables + invités ===
$tables = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            COALESCE(SUM(inv.nombre_personnes), 0) AS nb_personnes,
            GROUP_CONCAT(DISTINCT CONCAT(inv.prenom, ' ', inv.nom) SEPARATOR ', ') AS invites_noms
        FROM tables t
        LEFT JOIN invitations_tables it ON t.id = it.id_table
        LEFT JOIN invitations i ON it.id_invitation = i.id
        LEFT JOIN invites inv ON i.id_invite = inv.id
        WHERE t.id_evenement = ?
        GROUP BY t.id
        ORDER BY t.zone ASC, t.numero ASC, t.nom ASC
    ");
    $stmt->execute([$evenementId]);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur tables PDF: ' . $e->getMessage());
}

// === Stats ===
$stats = ['total' => 0, 'occupees' => 0, 'libres' => 0, 'capacite' => 0, 'occupees_places' => 0];
foreach ($tables as $t) {
    $stats['total']++;
    $stats['capacite'] += (int)($t['capacite_max'] ?? 0);
    $nb = (int)($t['nb_personnes'] ?? 0);
    $stats['occupees_places'] += $nb;
    if ($nb > 0) $stats['occupees']++; else $stats['libres']++;
}

// === Zones ===
$zoneLabels = [
    'TERRASSE'         => 'Terrasse',
    'SALLE_PRINCIPALE' => 'Salle principale',
    'SALON'            => 'Salon',
    'MEZZANINE'        => 'Mezzanine',
    'VIP'              => 'VIP',
    'EXTERIEUR'        => 'Extérieur',
];

// === Métadonnées ===
$reference      = 'REF-TAB-' . strtoupper(substr(md5($evenementId . '-' . date('Ymd')), 0, 8));
$dateGeneration = date('d/m/Y à H:i');
$nomEvenement   = $evenement['nom'] ?? 'Événement';
$dateEvenement  = !empty($evenement['date_evenement']) ? date('d/m/Y', strtotime($evenement['date_evenement'])) : 'Non définie';
$lieuEvenement  = $evenement['lieu'] ?? 'Non défini';

// Nom de fichier PDF
$nomFichierPdf = 'Plan-des-tables_' . preg_replace('/[^A-Za-z0-9_-]/', '-', $nomEvenement) . '_' . date('Y-m-d') . '.pdf';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tables - <?php echo htmlspecialchars($nomEvenement); ?> - <?php echo APP_NAME; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
        --primary: #c17c60;
        --primary-light: #d4a574;
        --dark: #1a1a1a;
        --gray: #6a5a4a;
        --gray-light: #9a8a7f;
        --border: #e5ddd3;
        --bg-light: #faf8f5;
    }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: #e8e4dd;
        color: var(--dark);
        padding: 20px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        -webkit-font-smoothing: antialiased;
    }

    /* Toolbar */
    .toolbar {
        position: fixed; top: 20px; right: 20px;
        display: flex; gap: 10px; z-index: 1000;
        animation: slideInRight 0.5s ease;
    }
    @keyframes slideInRight { from { opacity: 0; transform: translateX(30px);} to { opacity: 1; transform: translateX(0);} }
    .toolbar-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px; border-radius: 12px; border: none;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: all 0.3s ease; font-family: 'Inter', sans-serif;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15); text-decoration: none;
    }
    .toolbar-btn.primary {
        background: linear-gradient(135deg, #c17c60, #d4a574); color: white;
    }
    .toolbar-btn.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(193,124,96,0.4); }
    .toolbar-btn.primary:disabled { opacity: 0.6; cursor: wait; transform: none; }

    .toolbar-btn.outline {
        background: white; color: var(--primary); border: 1.5px solid var(--primary);
    }
    .toolbar-btn.outline:hover {
        background: var(--primary); color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(193,124,96,0.35);
    }

    .toolbar-btn.secondary {
        background: white; color: var(--gray); border: 1.5px solid var(--border);
    }
    .toolbar-btn.secondary:hover {
        background: var(--bg-light); color: var(--primary); border-color: var(--primary);
        transform: translateY(-2px);
    }

    /* Overlay de chargement */
    .pdf-loading-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        align-items: center; justify-content: center;
        backdrop-filter: blur(3px);
    }
    .pdf-loading-overlay.active { display: flex; }
    .pdf-loading-box {
        background: white; border-radius: 18px;
        padding: 35px 50px; text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        animation: fadeInUp 0.3s ease;
    }
    @keyframes fadeInUp { from { opacity:0; transform: translateY(15px);} to { opacity:1; transform: translateY(0);} }
    .pdf-loading-spinner {
        width: 50px; height: 50px; margin: 0 auto 18px;
        border: 4px solid #e5ddd3;
        border-top-color: #c17c60;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .pdf-loading-title {
        font-family: 'Playfair Display', serif;
        font-size: 18px; font-weight: 700;
        color: #1a1a1a; margin-bottom: 6px;
    }
    .pdf-loading-text { font-size: 13px; color: #9a8a7f; }

    /* Toast notification */
    .pdf-toast {
        position: fixed; bottom: 30px; left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: linear-gradient(135deg, #1a1a1a, #2d2420);
        color: white;
        padding: 14px 28px; border-radius: 12px;
        font-size: 14px; font-weight: 600;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        display: flex; align-items: center; gap: 10px;
        opacity: 0; transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        z-index: 10000;
        border-left: 4px solid #c17c60;
    }
    .pdf-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    .pdf-toast i { color: #d4a574; font-size: 18px; }

    /* Document */
    .pdf-document {
        width: 210mm; min-height: 297mm;
        background: white;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        display: flex; flex-direction: column;
    }

    /* Header */
    .pdf-header {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2420 100%);
        color: white;
        padding: 35px 45px 30px;
        position: relative;
        overflow: hidden;
    }
    .pdf-header::before {
        content: ''; position: absolute; top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(193,124,96,0.3) 0%, transparent 70%);
        pointer-events: none;
    }
    .pdf-header::after {
        content: ''; position: absolute; bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #c17c60, #d4a574, #c17c60);
    }
    .pdf-header-top {
        display: flex; justify-content: space-between; align-items: flex-start;
        position: relative; z-index: 1; margin-bottom: 25px;
    }
    .pdf-brand { display: flex; align-items: center; gap: 15px; }
    .pdf-brand-text h1 {
        font-family: 'Playfair Display', serif;
        font-size: 22px; font-weight: 700;
        letter-spacing: 0.5px; line-height: 1.2;
    }
    .pdf-brand-text p {
        font-size: 11px; text-transform: uppercase;
        letter-spacing: 2px; color: rgba(255,255,255,0.6);
        margin-top: 2px;
    }
    .pdf-doc-meta {
        text-align: right; font-size: 11px;
        color: rgba(255,255,255,0.7); line-height: 1.8;
    }
    .pdf-doc-meta .ref {
        font-family: 'Courier New', monospace;
        background: rgba(255,255,255,0.1);
        padding: 4px 10px; border-radius: 6px;
        font-weight: 600; letter-spacing: 1px;
        color: #d4a574; display: inline-block; margin-bottom: 4px;
    }
    .pdf-title-section { position: relative; z-index: 1; }
    .pdf-doc-type {
        display: inline-block;
        background: rgba(193,124,96,0.2);
        border: 1px solid rgba(193,124,96,0.4);
        color: #d4a574;
        padding: 6px 16px; border-radius: 20px;
        font-size: 11px; font-weight: 700;
        letter-spacing: 1.5px; text-transform: uppercase;
        margin-bottom: 12px;
    }
    .pdf-title-section h2 {
        font-family: 'Playfair Display', serif;
        font-size: 32px; font-weight: 700;
        line-height: 1.2; margin-bottom: 8px;
        letter-spacing: -0.5px;
    }
    .pdf-title-section .event-info {
        display: flex; gap: 20px; flex-wrap: wrap;
        font-size: 13px; color: rgba(255,255,255,0.75);
        margin-top: 10px;
    }
    .pdf-title-section .event-info span {
        display: inline-flex; align-items: center; gap: 6px;
    }
    .pdf-title-section .event-info i { color: #c17c60; font-size: 13px; }

    /* Body */
    .pdf-body { padding: 30px 45px 45px; flex: 1; }

    /* Section */
    .pdf-section { margin-bottom: 20px; }
    .pdf-section-header {
        display: flex; align-items: center; gap: 12px;
        padding-bottom: 12px; border-bottom: 2px solid #e5ddd3;
        margin-bottom: 20px; position: relative;
    }
    .pdf-section-header::after {
        content: ''; position: absolute; bottom: -2px; left: 0;
        width: 60px; height: 2px;
        background: linear-gradient(90deg, #c17c60, #d4a574);
    }
    .pdf-section-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 18px; flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(193,124,96,0.2);
    }
    .pdf-section-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 700;
        color: #1a1a1a; letter-spacing: -0.3px;
    }
    .pdf-section-subtitle {
        font-size: 11px; color: #9a8a7f;
        font-weight: 500; text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* GRILLE : 2 tables par ligne */
    .tables-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        align-items: start;
    }

    /* Carte de table */
    .pdf-table-card {
        border: 1.5px solid #e5ddd3;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        page-break-inside: avoid;
        break-inside: avoid;
        display: flex;
        flex-direction: column;
    }

    .pdf-table-card .card-head {
        padding: 10px 14px;
        background: linear-gradient(135deg, #1a1a1a, #2d2420);
        color: white;
    }
    .pdf-table-card .card-head .head-top {
        display: flex; justify-content: space-between; align-items: center;
        gap: 8px; flex-wrap: wrap;
    }
    .pdf-table-card .card-head .head-left {
        display: flex; align-items: center; gap: 8px;
        min-width: 0; flex: 1;
    }
    .pdf-table-card .card-head .table-badge {
        width: 28px; height: 28px; border-radius: 8px;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; color: white; flex-shrink: 0;
    }
    .pdf-table-card .card-head .head-title {
        font-family: 'Playfair Display', serif;
        font-size: 14px; font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pdf-table-card .card-head .head-title .num {
        color: #d4a574;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 500;
    }
    .pdf-table-card .card-head .head-right {
        font-size: 10px;
        color: #d4a574;
        font-weight: 700;
        letter-spacing: 0.5px;
        white-space: nowrap;
        background: rgba(212, 165, 116, 0.15);
        padding: 3px 8px;
        border-radius: 10px;
        border: 1px solid rgba(212, 165, 116, 0.3);
    }

    .pdf-table-card .card-head .head-sub {
        font-size: 10px;
        color: rgba(255,255,255,0.6);
        margin-top: 4px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .pdf-table-card .card-head .head-sub i { color: #c17c60; margin-right: 3px; }

    /* Liste invités */
    .pdf-table-card .invites-list {
        padding: 8px 12px 12px;
        flex: 1;
    }
    .pdf-table-card .invite-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        border-bottom: 1px dashed #f0ebe5;
        font-size: 11.5px;
    }
    .pdf-table-card .invite-row:last-child {
        border-bottom: none;
    }
    .pdf-table-card .invite-row .invite-num {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: rgba(193, 124, 96, 0.12);
        color: #c17c60;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .pdf-table-card .invite-row .invite-name {
        font-weight: 600;
        color: #2d2420;
        font-size: 11.5px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pdf-table-card .no-invites {
        text-align: center;
        padding: 18px 12px;
        color: #b8a99c;
        font-size: 11px;
        font-style: italic;
    }
    .pdf-table-card .no-invites i {
        display: block;
        font-size: 22px;
        margin-bottom: 6px;
        color: #d4c5b2;
    }

    /* Empty */
    .pdf-empty {
        text-align: center; padding: 60px 40px;
        background: #faf8f5; border-radius: 16px;
        border: 2px dashed #e5ddd3;
    }
    .pdf-empty-icon { font-size: 60px; margin-bottom: 15px; opacity: 0.4; }
    .pdf-empty-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 700;
        color: #6a5a4a; margin-bottom: 8px;
    }
    .pdf-empty-text { font-size: 13px; color: #9a8a7f; }

    /* Footer */
    .pdf-footer {
        padding: 20px 45px 25px;
        border-top: 2px solid #e5ddd3;
        display: flex; justify-content: space-between; align-items: center;
        font-size: 10px; color: #9a8a7f;
        background: #faf8f5;
    }
    .pdf-footer-left { display: flex; flex-direction: column; gap: 3px; }
    .pdf-footer-left strong {
        color: #6a5a4a; font-size: 11px;
        display: flex; align-items: center; gap: 6px;
    }
    .pdf-footer-left strong i { color: #c17c60; }
    .pdf-footer-right { text-align: right; }
    .pdf-footer-right .badge {
        display: inline-block;
        font-family: 'Playfair Display', serif;
        font-weight: 700; color: #c17c60;
        font-size: 12px; margin-bottom: 2px;
    }
    .pdf-footer-right .ref {
        font-family: 'Courier New', monospace;
        font-size: 10px; color: #9a8a7f;
    }

    /* Print */
    @media print {
        @page { size: A4; margin: 0; }
        body { background: white; padding: 0; display: block; margin: 0; }
        .toolbar,
        .pdf-loading-overlay,
        .pdf-toast { display: none !important; }
        .pdf-document {
            width: 210mm !important;
            min-height: auto;
            box-shadow: none;
            margin: 0;
            display: block;
        }
        .pdf-header { page-break-inside: avoid; }
        .pdf-section { page-break-inside: avoid; }
        .tables-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
        }
        .pdf-table-card {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .pdf-footer { position: static; }
    }

    /* Responsive (écran) */
    @media screen and (max-width: 850px) {
        body { padding: 10px; }
        .pdf-document { width: 100%; min-height: auto; }
        .pdf-header { padding: 25px 20px; }
        .pdf-header-top { flex-direction: column; gap: 15px; }
        .pdf-doc-meta { text-align: left; }
        .pdf-title-section h2 { font-size: 24px; }
        .pdf-body { padding: 20px; }
        .tables-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .toolbar {
            position: static; margin-bottom: 15px;
            width: 100%; justify-content: center; flex-wrap: wrap;
        }
        .toolbar-btn { flex: 1; min-width: 140px; justify-content: center; }
    }
</style>
</head>
<body>

<!-- Overlay de chargement -->
<div class="pdf-loading-overlay" id="pdfLoading">
    <div class="pdf-loading-box">
        <div class="pdf-loading-spinner"></div>
        <div class="pdf-loading-title">Génération du PDF...</div>
        <div class="pdf-loading-text">Veuillez patienter quelques secondes</div>
    </div>
</div>

<!-- Toast -->
<div class="pdf-toast" id="pdfToast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="pdfToastText">PDF téléchargé avec succès</span>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <a href="index.php?evenement=<?php echo $evenementId; ?>" class="toolbar-btn secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <button onclick="imprimerPdf()" class="toolbar-btn outline">
        <i class="bi bi-printer-fill"></i> Imprimer
    </button>
    <button onclick="telechargerPdf()" class="toolbar-btn primary" id="btnDownloadPdf">
        <i class="bi bi-download"></i> Télécharger le PDF
    </button>
</div>

<div class="pdf-document" id="pdfContent">

    <!-- EN-TÊTE -->
    <div class="pdf-header">
        <div class="pdf-header-top">
            <div class="pdf-brand">
                <div class="pdf-brand-text">
                    <h1><?php echo strtoupper(APP_NAME); ?></h1>
                    <p>Gestion événementielle</p>
                </div>
            </div>
            <div class="pdf-doc-meta">
                <div class="ref"><?php echo $reference; ?></div>
                <div>Généré le <?php echo $dateGeneration; ?></div>
                <div>Par <?php echo htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')); ?></div>
            </div>
        </div>

        <div class="pdf-title-section">
            <div class="pdf-doc-type">🪑 Plan des tables</div>
            <h2><?php echo htmlspecialchars($nomEvenement); ?></h2>
            <div class="event-info">
                <span><i class="bi bi-calendar-event"></i> <?php echo $dateEvenement; ?></span>
                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($lieuEvenement); ?></span>
                <span><i class="bi bi-table"></i> <?php echo (int)$stats['total']; ?> table(s)</span>
                <span><i class="bi bi-people-fill"></i> <?php echo (int)$stats['occupees_places']; ?> / <?php echo (int)$stats['capacite']; ?> places</span>
            </div>
        </div>
    </div>

    <!-- CORPS -->
    <div class="pdf-body">

        <?php if (!empty($tables)): ?>

            <div class="pdf-section">
                <div class="pdf-section-header">
                    <div class="pdf-section-icon">🍽️</div>
                    <div>
                        <div class="pdf-section-title">Détail des tables</div>
                        <div class="pdf-section-subtitle">
                            <?php echo (int)$stats['total']; ?> table(s) — 
                            <?php echo (int)$stats['occupees']; ?> occupée(s) • 
                            <?php echo (int)$stats['libres']; ?> libre(s)
                        </div>
                    </div>
                </div>

                <div class="tables-grid">
                    <?php foreach ($tables as $t):
                        $nbOcc   = (int)($t['nb_personnes'] ?? 0);
                        $capMax  = max(1, (int)($t['capacite_max'] ?? 4));
                        $invites = !empty($t['invites_noms']) ? explode(', ', $t['invites_noms']) : [];
                        $zoneLabel = $zoneLabels[$t['zone'] ?? 'SALLE_PRINCIPALE'] ?? ($t['zone'] ?? 'Salle');
                    ?>
                        <div class="pdf-table-card">
                            <div class="card-head">
                                <div class="head-top">
                                    <div class="head-left">
                                        <div class="table-badge"><i class="bi bi-table"></i></div>
                                        <div class="head-title">
                                            <?php echo htmlspecialchars($t['nom'] ?? 'Table'); ?>
                                            <?php if (!empty($t['numero'])): ?>
                                                <span class="num">(#<?php echo htmlspecialchars($t['numero']); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="head-right">
                                        <?php echo $nbOcc; ?>/<?php echo $capMax; ?>
                                    </div>
                                </div>
                                <div class="head-sub">
                                    <span><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($zoneLabel); ?></span>
                                    <span><i class="bi bi-shapes"></i><?php echo htmlspecialchars($t['type'] ?? 'Standard'); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($invites)): ?>
                                <div class="invites-list">
                                    <?php foreach ($invites as $idx => $inv): ?>
                                        <div class="invite-row">
                                            <div class="invite-num"><?php echo $idx + 1; ?></div>
                                            <div class="invite-name"><?php echo htmlspecialchars(trim($inv)); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-invites">
                                    <i class="bi bi-person-x"></i>
                                    Aucun invité assigné
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>

            <div class="pdf-empty">
                <div class="pdf-empty-icon">🪑</div>
                <div class="pdf-empty-title">Aucune table</div>
                <div class="pdf-empty-text">
                    Cet événement n'a encore aucune table configurée.
                </div>
            </div>

        <?php endif; ?>

    </div>

    <!-- PIED DE PAGE -->
    <div class="pdf-footer">
        <div class="pdf-footer-left">
            <strong>
                <i class="bi bi-shield-check"></i>
                <?php echo APP_NAME; ?>
            </strong>
            <span>Document généré automatiquement — Ne pas modifier manuellement</span>
        </div>
        <div class="pdf-footer-right">
            <div class="badge">Document officiel</div>
            <div class="ref"><?php echo $reference; ?></div>
        </div>
    </div>

</div>

<!-- Bibliothèque html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
const PDF_FILENAME = <?php echo json_encode($nomFichierPdf); ?>;

function afficherToast(message, success = true) {
    const toast = document.getElementById('pdfToast');
    const text  = document.getElementById('pdfToastText');
    const icon  = toast.querySelector('i');
    text.textContent = message;
    icon.className = success ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill';
    toast.style.borderLeftColor = success ? '#c17c60' : '#e74c3c';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

/* ============================================
   OPTION 1 : IMPRESSION (meilleure qualité)
   ============================================ */
function imprimerPdf() {
    // On masque la toolbar et on déclenche l'impression navigateur
    // → le rendu est vectoriel, texte sélectionnable, parfait.
    window.print();
}

/* ============================================
   OPTION 2 : TÉLÉCHARGEMENT DIRECT PDF
   ============================================ */
async function telechargerPdf() {
    const btn     = document.getElementById('btnDownloadPdf');
    const overlay = document.getElementById('pdfLoading');
    const element = document.getElementById('pdfContent');

    if (!element) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
    overlay.classList.add('active');

    // Sauvegarde des styles actuels du body
    const bodyPadding = document.body.style.padding;
    const bodyBg      = document.body.style.background;
    const bodyDisplay = document.body.style.display;

    // Prépare le body pour un rendu propre sans décalage
    document.body.style.padding    = '0';
    document.body.style.background = '#ffffff';
    document.body.style.display    = 'block';

    // Force la largeur exacte A4 du document
    const oldWidth   = element.style.width;
    const oldShadow  = element.style.boxShadow;
    const oldMargin  = element.style.margin;
    element.style.width     = '210mm';
    element.style.boxShadow = 'none';
    element.style.margin    = '0 auto';

    // Petite pause pour appliquer les styles
    await new Promise(r => setTimeout(r, 250));

    const options = {
        margin:      0,
        filename:    PDF_FILENAME,
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            scrollX: 0,
            scrollY: 0,
            windowWidth: element.scrollWidth,
            windowHeight: element.scrollHeight
        },
        jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait',
            compress: true
        },
        pagebreak: {
            mode: ['css', 'legacy'],
            avoid: ['.pdf-table-card', '.pdf-section-header', '.pdf-header', '.pdf-section']
        }
    };

    try {
        await html2pdf().set(options).from(element).save();
        afficherToast('PDF téléchargé avec succès !', true);
    } catch (err) {
        console.error('Erreur génération PDF:', err);
        afficherToast('Erreur lors de la génération du PDF', false);
    } finally {
        // Restaure les styles
        document.body.style.padding    = bodyPadding;
        document.body.style.background = bodyBg;
        document.body.style.display    = bodyDisplay;
        element.style.width     = oldWidth;
        element.style.boxShadow = oldShadow;
        element.style.margin    = oldMargin;

        overlay.classList.remove('active');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download"></i> Télécharger le PDF';
    }
}

// Raccourci Ctrl+P / Cmd+P → impression directe (plus propre)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        imprimerPdf();
    }
});

console.log('📄 Plan des tables - Référence: <?php echo $reference; ?>');
console.log('🪑 <?php echo count($tables); ?> table(s)');
</script>

</body>
</html>