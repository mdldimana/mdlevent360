<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../../includes/auth.php';

// Fix InfinityFree
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

requirePermission('rapports.voir');

$user        = getCurrentUser();
$userId      = (int)getCurrentUserId();
$isUserAdmin = isAdmin();
$pdo = getDbConnection();

$evenementId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

// ========== FILTRAGE PAR UTILISATEUR ==========
if ($evenementId > 0 && !$isUserAdmin) {
    if (function_exists('userCanAccessEvenement') && !userCanAccessEvenement($pdo, $userId, $evenementId)) {
        http_response_code(403);
        die('Accès refusé à cet événement.');
    }
}

// ========== ÉVÉNEMENT (optionnel) ==========
$evenement = null;
if ($evenementId > 0) {
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
        error_log('Erreur événement export invites : ' . $e->getMessage());
    }
    if (!$evenement) die('Erreur : événement introuvable.');
}

// ========== RÉCUPÉRATION DES INVITÉS ==========
$invites = [];
try {
    if ($evenementId > 0) {
        $stmt = $pdo->prepare("
            SELECT 
                inv.id, inv.nom, inv.prenom, inv.email, inv.telephone,
                inv.entreprise, inv.adresse, inv.nombre_personnes,
                inv.contact_preference, inv.created_at AS date_creation,
                c.nom AS categorie_nom,
                i.code_unique, i.statut AS invitation_statut,
                t.nom AS table_nom, t.numero AS table_numero, t.zone AS table_zone
            FROM invites inv
            LEFT JOIN invitations i ON i.id_invite = inv.id AND i.id_evenement = :ev
            LEFT JOIN categories_invites c ON c.id = inv.id_categorie
            LEFT JOIN invitations_tables it ON it.id_invitation = i.id
            LEFT JOIN tables t ON t.id = it.id_table
            WHERE inv.id_evenement = :ev2 OR inv.id IN (
                SELECT id_invite FROM invitations WHERE id_evenement = :ev3
            )
            ORDER BY inv.nom ASC, inv.prenom ASC
        ");
        $stmt->execute([':ev' => $evenementId, ':ev2' => $evenementId, ':ev3' => $evenementId]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log('Erreur récupération invités export : ' . $e->getMessage());
}

// ========== STATS (pour l'en-tête uniquement) ==========
$totalInvites = count($invites);
$totalPersonnes = 0;
foreach ($invites as $inv) {
    $totalPersonnes += (int)($inv['nombre_personnes'] ?? 1);
}

// ========== MÉTADONNÉES ==========
$reference      = 'REF-INV-' . strtoupper(substr(md5($evenementId . '-' . date('Ymd')), 0, 8));
$dateGeneration = date('d/m/Y à H:i');
$nomEvenement   = $evenement['nom'] ?? 'Tous les événements';
$dateEvenement  = !empty($evenement['date_evenement']) ? date('d/m/Y', strtotime($evenement['date_evenement'])) : '—';
$lieuEvenement  = $evenement['lieu'] ?? '—';

$nomFichierPdf = 'Invites_' . preg_replace('/[^A-Za-z0-9_-]/', '-', $nomEvenement) . '_' . date('Y-m-d') . '.pdf';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liste des invités - <?php echo htmlspecialchars($nomEvenement); ?> - <?php echo APP_NAME; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
        --primary: #c17c60;
        --primary-dark: #a86a50;
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
    .pdf-body { padding: 35px 45px 45px; flex: 1; }

    .pdf-section { margin-bottom: 22px; }

    .pdf-section-header {
        display: flex; align-items: center; gap: 12px;
        padding-bottom: 12px; border-bottom: 2px solid #e5ddd3;
        margin-bottom: 18px; position: relative;
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

    /* Tableau générique */
    .pdf-table {
        width: 100%; border-collapse: collapse; font-size: 12px;
    }
    .pdf-table thead th {
        background: #faf8f5;
        color: #6a5a4a;
        padding: 10px 14px;
        text-align: left;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #e5ddd3;
    }
    .pdf-table tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0ebe5;
        color: #2d2420;
        vertical-align: middle;
    }
    .pdf-table tbody tr:last-child td { border-bottom: none; }
    .pdf-table tbody tr:nth-child(even) { background: #faf8f5; }
    .pdf-table .invite-name { font-weight: 700; color: #1a1a1a; font-size: 12.5px; }
    .pdf-table .invite-sub { font-size: 11px; color: #9a8a7f; margin-top: 2px; }
    .pdf-table .contact-line {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; color: #6a5a4a; margin-top: 2px;
    }
    .pdf-table .contact-line i { color: #c17c60; }
    .pdf-table .no-contact {
        color: #b8a99c; font-style: italic; font-size: 11.5px;
    }
    .pdf-table .badge-table {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 10px;
        font-size: 10px; font-weight: 700;
        background: rgba(193,124,96,0.12);
        color: #c17c60;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .pdf-table .col-center { text-align: center; }
    .pdf-table .col-pers {
        text-align: center; font-weight: 700;
        color: #c17c60; font-size: 13px;
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
        }
        .pdf-section,
        .pdf-table tr { page-break-inside: avoid; }
        .pdf-table thead { display: table-header-group; }
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
        .pdf-body { padding: 25px 20px; }
        .pdf-table { font-size: 11px; }
        .pdf-table thead th, .pdf-table tbody td { padding: 8px 8px; }
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
    <a href="../index.php" class="toolbar-btn secondary">
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
            <div class="pdf-doc-type">👥 Liste des invités</div>
            <h2><?php echo htmlspecialchars($nomEvenement); ?></h2>
            <div class="event-info">
                <span><i class="bi bi-calendar-event"></i> <?php echo $dateEvenement; ?></span>
                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($lieuEvenement); ?></span>
                <span><i class="bi bi-people-fill"></i> <?php echo $totalInvites; ?> invité(s)</span>
                <span><i class="bi bi-person-plus-fill"></i> <?php echo $totalPersonnes; ?> personne(s) au total</span>
            </div>
        </div>
    </div>

    <!-- CORPS -->
    <div class="pdf-body">

        <?php if (!empty($invites)): ?>

            <!-- LISTE DÉTAILLÉE : UNE LIGNE PAR INVITÉ -->
            <div class="pdf-section">
                <div class="pdf-section-header">
                    <div class="pdf-section-icon">👥</div>
                    <div>
                        <div class="pdf-section-title">Liste des invités</div>
                        <div class="pdf-section-subtitle"><?php echo $totalInvites; ?> invité(s) enregistré(s)</div>
                    </div>
                </div>

                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th style="width:5%; text-align:center;">#</th>
                            <th style="width:28%;">Invité</th>
                            <th style="width:25%;">Contact</th>
                            <th style="width:17%;">Catégorie</th>
                            <th style="width:15%; text-align:center;">Table</th>
                            <th style="width:10%; text-align:center;">Pers.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($invites as $inv): ?>
                            <tr>
                                <td class="col-center" style="color:#9a8a7f; font-weight:700;">
                                    <?php echo $i++; ?>
                                </td>
                                <td>
                                    <div class="invite-name">
                                        <?php echo htmlspecialchars(trim(($inv['prenom'] ?? '') . ' ' . ($inv['nom'] ?? ''))); ?>
                                    </div>
                                    <?php if (!empty($inv['entreprise'])): ?>
                                        <div class="invite-sub">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($inv['entreprise']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($inv['email'])): ?>
                                        <div class="contact-line">
                                            <i class="bi bi-envelope-fill"></i>
                                            <?php echo htmlspecialchars($inv['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($inv['telephone'])): ?>
                                        <div class="contact-line">
                                            <i class="bi bi-telephone-fill"></i>
                                            <?php echo htmlspecialchars($inv['telephone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($inv['email']) && empty($inv['telephone'])): ?>
                                        <span class="no-contact"><i class="bi bi-dash-circle"></i> Aucun contact</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($inv['categorie_nom'])): ?>
                                        <span class="badge-table"><?php echo htmlspecialchars($inv['categorie_nom']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#b8a99c; font-style:italic; font-size:11px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-center">
                                    <?php if (!empty($inv['table_nom'])): ?>
                                        <span class="badge-table">
                                            <i class="bi bi-table"></i>
                                            <?php echo htmlspecialchars($inv['table_nom']); ?>
                                        </span>
                                        <?php if (!empty($inv['table_numero'])): ?>
                                            <div style="font-size:10px; color:#9a8a7f; margin-top:2px;">#<?php echo htmlspecialchars($inv['table_numero']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#b8a99c; font-style:italic; font-size:11px;">Non placé</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-pers">
                                    <?php echo (int)($inv['nombre_personnes'] ?? 1); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="pdf-empty">
                <div class="pdf-empty-icon">👥</div>
                <div class="pdf-empty-title">Aucun invité</div>
                <div class="pdf-empty-text">
                    Aucun invité enregistré pour cet événement.
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

function imprimerPdf() {
    window.print();
}

async function telechargerPdf() {
    const btn     = document.getElementById('btnDownloadPdf');
    const overlay = document.getElementById('pdfLoading');
    const element = document.getElementById('pdfContent');

    if (!element) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
    overlay.classList.add('active');

    const bodyPadding = document.body.style.padding;
    const bodyBg      = document.body.style.background;
    const bodyDisplay = document.body.style.display;

    document.body.style.padding    = '0';
    document.body.style.background = '#ffffff';
    document.body.style.display    = 'block';

    const oldWidth   = element.style.width;
    const oldShadow  = element.style.boxShadow;
    const oldMargin  = element.style.margin;
    element.style.width     = '210mm';
    element.style.boxShadow = 'none';
    element.style.margin    = '0 auto';

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
            avoid: ['.pdf-table tr', '.pdf-section-header']
        }
    };

    try {
        await html2pdf().set(options).from(element).save();
        afficherToast('PDF téléchargé avec succès !', true);
    } catch (err) {
        console.error('Erreur génération PDF:', err);
        afficherToast('Erreur lors de la génération du PDF', false);
    } finally {
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

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        imprimerPdf();
    }
});

console.log('📄 Liste des invités - Référence: <?php echo $reference; ?>');
</script>

</body>
</html>