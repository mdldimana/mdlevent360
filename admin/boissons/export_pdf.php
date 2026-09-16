<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';

// Fix InfinityFree
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/../../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

requirePermission('boissons.voir');

$user       = getCurrentUser();
$userId     = (int)getCurrentUserId();
$pdo        = getDbConnection();

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

// === Préférences par table ===
$tablesAvecPreferences = [];
$totalParBoisson       = [];
$totalInvitesAvecChoix = 0;
$totalInvitesSansChoix = 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.id AS table_id, t.nom AS table_nom, t.numero AS table_numero, t.zone AS table_zone,
            t.capacite_max, t.capacite_min,
            i.id AS invite_id, i.nom AS invite_nom, i.prenom AS invite_prenom,
            inv.id AS invitation_id,
            b.id AS boisson_id, b.nom AS boisson_nom, b.type AS boisson_type,
            pi.quantite
        FROM tables t
        LEFT JOIN invitations_tables it ON it.id_table = t.id
        LEFT JOIN invitations inv ON inv.id = it.id_invitation
        LEFT JOIN invites i ON i.id = inv.id_invite
        LEFT JOIN preferences_invitation pi ON pi.id_invitation = inv.id
        LEFT JOIN boissons b ON b.id = pi.id_boisson
        WHERE t.id_evenement = ?
        ORDER BY t.numero ASC, t.nom ASC, i.nom ASC, i.prenom ASC, b.nom ASC
    ");
    $stmt->execute([$evenementId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $tableId = (int)$row['table_id'];
        if (!isset($tablesAvecPreferences[$tableId])) {
            $tablesAvecPreferences[$tableId] = [
                'id' => $tableId,
                'nom' => $row['table_nom'] ?: ('Table ' . $row['table_numero']),
                'numero' => $row['table_numero'],
                'zone' => $row['table_zone'],
                'capacite_max' => (int)$row['capacite_max'],
                'invites' => [],
                'total_par_boisson' => [],
            ];
        }

        if (!empty($row['invite_id'])) {
            $inviteId = (int)$row['invite_id'];
            if (!isset($tablesAvecPreferences[$tableId]['invites'][$inviteId])) {
                $tablesAvecPreferences[$tableId]['invites'][$inviteId] = [
                    'id' => $inviteId,
                    'nom_complet' => trim(($row['invite_prenom'] ?? '') . ' ' . ($row['invite_nom'] ?? '')),
                    'boissons' => [],
                ];
            }
            if (!empty($row['boisson_id'])) {
                $boissonId = (int)$row['boisson_id'];
                $qte = max(1, (int)$row['quantite']);

                if (!isset($tablesAvecPreferences[$tableId]['invites'][$inviteId]['boissons'][$boissonId])) {
                    $tablesAvecPreferences[$tableId]['invites'][$inviteId]['boissons'][$boissonId] = [
                        'nom' => $row['boisson_nom'],
                        'type' => $row['boisson_type'],
                        'quantite' => 0,
                    ];
                }
                $tablesAvecPreferences[$tableId]['invites'][$inviteId]['boissons'][$boissonId]['quantite'] += $qte;

                if (!isset($tablesAvecPreferences[$tableId]['total_par_boisson'][$boissonId])) {
                    $tablesAvecPreferences[$tableId]['total_par_boisson'][$boissonId] = [
                        'nom' => $row['boisson_nom'],
                        'type' => $row['boisson_type'],
                        'quantite' => 0,
                    ];
                }
                $tablesAvecPreferences[$tableId]['total_par_boisson'][$boissonId]['quantite'] += $qte;

                if (!isset($totalParBoisson[$boissonId])) {
                    $totalParBoisson[$boissonId] = [
                        'nom' => $row['boisson_nom'],
                        'type' => $row['boisson_type'],
                        'quantite' => 0,
                        'nb_invites' => 0,
                    ];
                }
                $totalParBoisson[$boissonId]['quantite'] += $qte;
            }
        }
    }

    foreach ($tablesAvecPreferences as $t) {
        foreach ($t['invites'] as $inv) {
            if (!empty($inv['boissons'])) {
                $totalInvitesAvecChoix++;
                foreach ($inv['boissons'] as $bid => $b) {
                    if (isset($totalParBoisson[$bid])) {
                        $totalParBoisson[$bid]['nb_invites']++;
                    }
                }
            } else {
                $totalInvitesSansChoix++;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Erreur préférences PDF tables: ' . $e->getMessage());
}

// === Types de boissons ===
$typeLabels = [
    'SANS_ALCOOL' => 'Sans alcool',
    'ALCOOL'      => 'Alcool',
    'CHAUD'       => 'Boisson chaude',
    'AUTRE'       => 'Autre',
];
$typeIcons = [
    'SANS_ALCOOL' => '🥤',
    'ALCOOL'      => '🍷',
    'CHAUD'       => '☕',
    'AUTRE'       => '🍹',
];

uasort($totalParBoisson, fn($a, $b) => strcasecmp($a['nom'], $b['nom']));

// === Métadonnées ===
$reference      = 'REF-BOIS-TAB-' . strtoupper(substr(md5($evenementId . '-' . date('Ymd')), 0, 8));
$dateGeneration = date('d/m/Y à H:i');
$nomEvenement   = $evenement['nom'] ?? 'Événement';
$dateEvenement  = !empty($evenement['date_evenement']) ? date('d/m/Y', strtotime($evenement['date_evenement'])) : 'Non définie';
$lieuEvenement  = $evenement['lieu'] ?? 'Non défini';

// Nom de fichier PDF
$nomFichierPdf = 'Boissons-par-table_' . preg_replace('/[^A-Za-z0-9_-]/', '-', $nomEvenement) . '_' . date('Y-m-d') . '.pdf';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boissons par table - <?php echo htmlspecialchars($nomEvenement); ?> - <?php echo APP_NAME; ?></title>
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
    .toolbar-btn.primary:disabled {
        opacity: 0.6; cursor: wait; transform: none;
    }

    /* Nouveau style : bouton outline doré */
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
    .pdf-loading-text {
        font-size: 13px; color: #9a8a7f;
    }

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
    .pdf-toast.show {
        opacity: 1; transform: translateX(-50%) translateY(0);
    }
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

    /* Table card */
    .pdf-table-card {
        border: 1.5px solid #e5ddd3;
        border-radius: 14px;
        margin-bottom: 16px;
        overflow: hidden;
        background: white;
        page-break-inside: avoid;
    }
    .pdf-table-card .card-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 18px;
        background: linear-gradient(135deg, #1a1a1a, #2d2420);
        color: white;
        gap: 10px; flex-wrap: wrap;
    }
    .pdf-table-card .card-head .head-left {
        display: flex; align-items: center; gap: 10px;
    }
    .pdf-table-card .card-head .table-badge {
        width: 32px; height: 32px; border-radius: 10px;
        background: linear-gradient(135deg, #c17c60, #d4a574);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; color: white; flex-shrink: 0;
    }
    .pdf-table-card .card-head .head-title {
        font-family: 'Playfair Display', serif;
        font-size: 16px; font-weight: 700; line-height: 1.2;
    }
    .pdf-table-card .card-head .head-sub {
        font-size: 11px; color: rgba(255,255,255,0.65);
        margin-top: 2px;
    }
    .pdf-table-card .card-head .head-right {
        font-size: 11px; color: #d4a574;
        font-weight: 700; letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* Table interne */
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
    .pdf-table thead th:last-child { text-align: center; width: 15%; }
    .pdf-table tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0ebe5;
        color: #2d2420;
        vertical-align: middle;
    }
    .pdf-table tbody tr:last-child td { border-bottom: none; }
    .pdf-table tbody tr:nth-child(even) { background: #faf8f5; }
    .pdf-table .invite-name { font-weight: 700; color: #1a1a1a; font-size: 12.5px; }
    .pdf-table .boisson-list {
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .pdf-table .boisson-chip {
        background: linear-gradient(135deg, rgba(193,124,96,0.12), rgba(212,165,116,0.12));
        color: #c17c60;
        padding: 3px 10px; border-radius: 12px;
        font-size: 11px; font-weight: 600;
        border: 1px solid rgba(193,124,96,0.25);
        display: inline-flex; align-items: center; gap: 4px;
    }
    .pdf-table .boisson-chip .qty {
        background: rgba(193,124,96,0.25);
        padding: 1px 6px; border-radius: 8px;
        font-size: 10px; font-weight: 700;
    }
    .pdf-table .no-choice {
        color: #b8a99c; font-style: italic; font-size: 11.5px;
    }
    .pdf-table .qte-total {
        text-align: center; font-weight: 700;
        color: #c17c60; font-size: 14px;
    }

    /* Récap global */
    .pdf-recap-table {
        width: 100%; border-collapse: separate; border-spacing: 0;
        font-size: 12px; margin-top: 5px;
    }
    .pdf-recap-table thead th {
        background: linear-gradient(135deg, #1a1a1a, #2d2420);
        color: white;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .pdf-recap-table thead th:first-child { border-radius: 10px 0 0 0; width: 8%; text-align: center; }
    .pdf-recap-table thead th:nth-child(2) { width: 52%; }
    .pdf-recap-table thead th:nth-child(3) { width: 20%; }
    .pdf-recap-table thead th:last-child { border-radius: 0 10px 0 0; width: 20%; text-align: center; }
    .pdf-recap-table tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid #f0ebe5;
        color: #2d2420; vertical-align: middle;
    }
    .pdf-recap-table tbody tr:nth-child(even) { background: #faf8f5; }
    .pdf-recap-table .stat-qte {
        text-align: center; font-weight: 700;
        color: #c17c60; font-size: 15px;
    }
    .pdf-recap-table .stat-nb {
        text-align: center; font-weight: 600;
        color: #6a5a4a; font-size: 13px;
    }
    .pdf-recap-table .drink-type-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 10px;
        font-size: 10px; font-weight: 700;
        background: rgba(193,124,96,0.12);
        color: #c17c60;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        .pdf-table-card,
        .pdf-section,
        .pdf-table tr,
        .pdf-recap-table tr { page-break-inside: avoid; }
        .pdf-recap-table thead,
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
    <a href="table_preference.php?evenement=<?php echo $evenementId; ?>" class="toolbar-btn secondary">
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
            <div class="pdf-doc-type">📋 Boissons par table</div>
            <h2><?php echo htmlspecialchars($nomEvenement); ?></h2>
            <div class="event-info">
                <span><i class="bi bi-calendar-event"></i> <?php echo $dateEvenement; ?></span>
                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($lieuEvenement); ?></span>
                <span><i class="bi bi-table"></i> <?php echo count($tablesAvecPreferences); ?> table(s)</span>
                <span><i class="bi bi-people-fill"></i> <?php echo $totalInvitesAvecChoix + $totalInvitesSansChoix; ?> invité(s)</span>
            </div>
        </div>
    </div>

    <!-- CORPS -->
    <div class="pdf-body">

        <?php if (!empty($tablesAvecPreferences)): ?>

            <!-- RÉCAP GLOBAL PAR BOISSON -->
            <?php if (!empty($totalParBoisson)): ?>
            <div class="pdf-section">
                <div class="pdf-section-header">
                    <div class="pdf-section-icon">📊</div>
                    <div>
                        <div class="pdf-section-title">Récapitulatif global</div>
                        <div class="pdf-section-subtitle">Total des boissons demandées pour l'événement</div>
                    </div>
                </div>

                <table class="pdf-recap-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Boisson</th>
                            <th>Catégorie</th>
                            <th>Quantité totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($totalParBoisson as $b): ?>
                            <?php $type = $b['type'] ?? 'AUTRE'; ?>
                            <tr>
                                <td style="text-align:center; color:#9a8a7f; font-weight:700;"><?php echo $i++; ?></td>
                                <td>
                                    <div style="font-weight:700; color:#1a1a1a; font-size:13px;">
                                        <?php echo htmlspecialchars($b['nom']); ?>
                                    </div>
                                    <div style="font-size:11px; color:#9a8a7f; margin-top:2px;">
                                        <i class="bi bi-people-fill"></i>
                                        <?php echo (int)$b['nb_invites']; ?> invité(s) concerné(s)
                                    </div>
                                </td>
                                <td>
                                    <span class="drink-type-badge">
                                        <?php echo $typeIcons[$type] ?? '🍹'; ?>
                                        <?php echo htmlspecialchars($typeLabels[$type] ?? $type); ?>
                                    </span>
                                </td>
                                <td class="stat-qte"><?php echo (int)$b['quantite']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- DÉTAIL PAR TABLE -->
            <div class="pdf-section">
                <div class="pdf-section-header">
                    <div class="pdf-section-icon">🍽️</div>
                    <div>
                        <div class="pdf-section-title">Détail par table</div>
                        <div class="pdf-section-subtitle">
                            <?php echo count($tablesAvecPreferences); ?> table(s) — 
                            <?php echo $totalInvitesAvecChoix; ?> invité(s) avec choix
                        </div>
                    </div>
                </div>

                <?php foreach ($tablesAvecPreferences as $table): ?>
                    <div class="pdf-table-card">
                        <div class="card-head">
                            <div class="head-left">
                                <div class="table-badge"><i class="bi bi-table"></i></div>
                                <div>
                                    <div class="head-title">Table <?php echo htmlspecialchars($table['nom']); ?></div>
                                    <div class="head-sub">
                                        <?php if (!empty($table['zone'])): ?>
                                            <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($table['zone']); ?>
                                            &nbsp;•&nbsp;
                                        <?php endif; ?>
                                        <?php echo count($table['invites']); ?> invité(s)
                                        <?php if ($table['capacite_max'] > 0): ?>
                                            / <?php echo $table['capacite_max']; ?> places
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="head-right">
                                <?php
                                $totalTableQte = 0;
                                foreach ($table['total_par_boisson'] as $b) $totalTableQte += $b['quantite'];
                                echo $totalTableQte . ' boisson(s)';
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($table['invites'])): ?>
                            <table class="pdf-table">
                                <thead>
                                    <tr>
                                        <th style="width:40%;">Invité</th>
                                        <th>Boissons choisies</th>
                                        <th>Qté</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table['invites'] as $invite): ?>
                                        <tr>
                                            <td class="invite-name">
                                                <?php echo htmlspecialchars($invite['nom_complet']); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($invite['boissons'])): ?>
                                                    <div class="boisson-list">
                                                        <?php foreach ($invite['boissons'] as $b): ?>
                                                            <span class="boisson-chip">
                                                                <?php echo htmlspecialchars($b['nom']); ?>
                                                                <?php if ((int)$b['quantite'] > 1): ?>
                                                                    <span class="qty">×<?php echo (int)$b['quantite']; ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-choice">
                                                        <i class="bi bi-dash-circle"></i> Aucun choix
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="qte-total">
                                                <?php
                                                $q = 0;
                                                foreach ($invite['boissons'] as $b) $q += (int)$b['quantite'];
                                                echo $q > 0 ? $q : '—';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="padding: 20px; text-align:center; color:#9a8a7f; font-size:12px;">
                                <i class="bi bi-people"></i> Aucun invité assigné à cette table
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="pdf-empty">
                <div class="pdf-empty-icon">🍽️</div>
                <div class="pdf-empty-title">Aucune donnée disponible</div>
                <div class="pdf-empty-text">
                    Aucun invité n'a encore choisi de boisson, ou aucune table n'est associée à cet événement.
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
    // Le CSS @media print masque la toolbar et adapte la mise en page.
    // Résultat : PDF vectoriel net, texte sélectionnable.
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
            avoid: ['.pdf-table-card', '.pdf-recap-table tr', '.pdf-table tr', '.pdf-section-header']
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

console.log('📄 Boissons par table - Référence: <?php echo $reference; ?>');
</script>

</body>
</html>