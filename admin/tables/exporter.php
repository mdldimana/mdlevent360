<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('tables.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$evenement_id = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$format = $_GET['format'] ?? 'pdf';

// Récupérer l'événement
$evenement = null;
try {
    $stmt = $pdo->prepare("SELECT id, nom, date_evenement, lieu FROM evenements WHERE id = ? AND statut != 'ANNULE'");
    $stmt->execute([$evenement_id]);
    $evenement = $stmt->fetch();
} catch (PDOException $e) {}

if (!$evenement) {
    header('Location: index.php');
    exit;
}

// Récupérer les tables avec les positions
$tables = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            COALESCE(SUM(inv.nombre_personnes), 0) as nb_personnes,
            GROUP_CONCAT(DISTINCT CONCAT(inv.prenom, ' ', inv.nom) SEPARATOR ', ') as invites_noms
        FROM tables t
        LEFT JOIN invitations_tables it ON t.id = it.id_table
        LEFT JOIN invitations i ON it.id_invitation = i.id
        LEFT JOIN invites inv ON i.id_invite = inv.id
        WHERE t.id_evenement = ?
        GROUP BY t.id
        ORDER BY t.zone, t.numero
    ");
    $stmt->execute([$evenement_id]);
    $tables = $stmt->fetchAll();
} catch (PDOException $e) {}

$zoneLabels = [
    'TERRASSE' => 'Terrasse',
    'SALLE_PRINCIPALE' => 'Salle principale',
    'SALON' => 'Salon',
    'MEZZANINE' => 'Mezzanine',
    'VIP' => 'VIP',
    'EXTERIEUR' => 'Extérieur'
];

$typeLabels = [
    'RONDE' => 'Ronde',
    'CARREE' => 'Carrée',
    'RECTANGLE' => 'Rectangulaire',
    'OVALE' => 'Ovale',
    'BARRIERE' => 'Barrière'
];

// ============================================
// EXPORT EN PDF (Version Panels 3 par ligne)
// ============================================

if ($format === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Plan de salle - <?php echo htmlspecialchars($evenement['nom']); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Arial, sans-serif; 
                padding: 30px;
                background: white;
                color: #1a1a2e;
            }

            /* ===== EN-TÊTE ===== */
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #f7971e;
            }
            .header h1 { 
                font-size: 28px; 
                color: #1a1a2e;
                font-weight: 800;
            }
            .header h1 span { color: #f7971e; }
            .header .subtitle { color: #888; font-size: 16px; margin-top: 5px; }
            .header .meta { color: #aaa; font-size: 12px; margin-top: 10px; }

            /* ===== RÉSUMÉ ===== */
            .summary {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
                padding: 12px 20px;
                background: #f8f9fa;
                border-radius: 10px;
                margin-bottom: 25px;
                justify-content: center;
            }
            .summary .item { font-size: 13px; }
            .summary .item strong { color: #1a1a2e; }
            .summary .item .dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 5px;
            }
            .summary .item .dot.green { background: #28a745; }
            .summary .item .dot.orange { background: #f7971e; }
            .summary .item .dot.gray { background: #ccc; }

            /* ===== ZONES ===== */
            .zone-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .zone-section .zone-title {
                font-weight: 700;
                font-size: 18px;
                color: #1a1a2e;
                padding: 8px 15px;
                background: #f8f9fa;
                border-left: 4px solid #f7971e;
                margin-bottom: 15px;
            }

            /* ===== GRILLE 3 COLONNES ===== */
            .table-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }

            /* ===== PANEL ===== */
            .table-panel {
                background: white;
                border-radius: 12px;
                border: 1px solid #e8e5e0;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                transition: all 0.2s ease;
                break-inside: avoid;
            }
            .table-panel:hover {
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            }

            .table-panel .panel-header {
                background: linear-gradient(135deg, #f7971e, #ffd200);
                padding: 10px 14px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 2px solid rgba(255,255,255,0.2);
            }
            .table-panel .panel-header .table-name {
                font-weight: 700;
                font-size: 15px;
                color: #1a1a2e;
            }
            .table-panel .panel-header .table-status {
                font-size: 11px;
                font-weight: 600;
                padding: 2px 12px;
                border-radius: 20px;
                color: white;
            }
            .table-panel .panel-header .table-status.full { background: #28a745; }
            .table-panel .panel-header .table-status.partial { background: #f7971e; }
            .table-panel .panel-header .table-status.empty { background: #adb5bd; }

            .table-panel .panel-body {
                padding: 10px 14px 14px;
                min-height: 80px;
            }
            .table-panel .panel-body .guest-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 3px 6px;
                border-radius: 4px;
                font-size: 13px;
                color: #1a1a2e;
                border-bottom: 1px dashed #f5f5f5;
            }
            .table-panel .panel-body .guest-item:last-child {
                border-bottom: none;
            }
            .table-panel .panel-body .guest-item .bullet {
                color: #f7971e;
                font-weight: 700;
                font-size: 10px;
            }
            .table-panel .panel-body .guest-item .guest-name {
                font-weight: 500;
            }
            .table-panel .panel-body .empty-message {
                color: #ccc;
                font-style: italic;
                font-size: 13px;
                text-align: center;
                padding: 15px 0;
            }

            .table-panel .panel-footer {
                background: #f8f9fa;
                padding: 6px 14px;
                font-size: 11px;
                color: #888;
                border-top: 1px solid #f0ede8;
                display: flex;
                justify-content: space-between;
            }
            .table-panel .panel-footer .capacity {
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .table-panel .panel-footer .capacity .bar {
                width: 50px;
                height: 4px;
                background: #e9ecef;
                border-radius: 10px;
                overflow: hidden;
            }
            .table-panel .panel-footer .capacity .bar .fill {
                height: 100%;
                border-radius: 10px;
                background: linear-gradient(90deg, #f7971e, #ffd200);
                transition: width 0.3s ease;
            }

            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #aaa;
                font-size: 12px;
            }

            .no-print { display: none; }

            @media print {
                body { padding: 15px; }
                .no-print { display: none !important; }
                .table-grid { gap: 12px; }
                .table-panel { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; }
            }

            @media (max-width: 900px) {
                .table-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 600px) {
                .table-grid { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📋 <span>Plan de salle</span></h1>
            <div class="subtitle"><?php echo htmlspecialchars($evenement['nom']); ?></div>
            <div class="meta">
                <?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?>
                <?php if ($evenement['lieu']): ?> • <?php echo htmlspecialchars($evenement['lieu']); ?><?php endif; ?>
                • Généré le <?php echo date('d/m/Y à H:i'); ?>
            </div>
        </div>

        <?php 
        $totalTables = count($tables);
        $totalPlaces = array_sum(array_column($tables, 'capacite_max'));
        $totalOccupied = array_sum(array_column($tables, 'nb_personnes'));
        $tablesFull = 0;
        $tablesEmpty = 0;
        $tablesPartial = 0;
        foreach ($tables as $t) {
            if ($t['nb_personnes'] >= $t['capacite_max']) $tablesFull++;
            elseif ($t['nb_personnes'] == 0) $tablesEmpty++;
            else $tablesPartial++;
        }
        ?>

        <div class="summary">
            <div class="item"><strong><?php echo $totalTables; ?></strong> tables</div>
            <div class="item"><strong><?php echo $totalPlaces; ?></strong> places</div>
            <div class="item"><strong><?php echo $totalOccupied; ?></strong> personnes</div>
            <div class="item"><span class="dot green"></span> <?php echo $tablesFull; ?> pleines</div>
            <div class="item"><span class="dot orange"></span> <?php echo $tablesPartial; ?> partielles</div>
            <div class="item"><span class="dot gray"></span> <?php echo $tablesEmpty; ?> vides</div>
        </div>

        <?php 
        $zones = [];
        foreach ($tables as $t) {
            $zone = $t['zone'] ?? 'SALLE_PRINCIPALE';
            if (!isset($zones[$zone])) $zones[$zone] = [];
            $zones[$zone][] = $t;
        }
        ?>

        <?php foreach ($zones as $zone => $tablesZone): ?>
            <div class="zone-section">
                <div class="zone-title"><?php echo $zoneLabels[$zone] ?? $zone; ?></div>
                <div class="table-grid">
                    <?php foreach ($tablesZone as $t): 
                        $nb = (int)($t['nb_personnes'] ?? 0);
                        $cap = (int)($t['capacite_max'] ?? 4);
                        $pourcentage = round(($nb / max(1, $cap)) * 100);
                        $status = $nb >= $cap ? 'full' : ($nb > 0 ? 'partial' : 'empty');
                        $statusLabel = $status == 'full' ? 'Pleine' : ($status == 'partial' ? 'Partielle' : 'Vide');
                        
                        $invitesList = [];
                        if (!empty($t['invites_noms'])) {
                            $invitesList = explode(', ', $t['invites_noms']);
                        }
                    ?>
                        <div class="table-panel">
                            <div class="panel-header">
                                <span class="table-name"><?php echo htmlspecialchars($t['nom']); ?></span>
                                <span class="table-status <?php echo $status; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($invitesList)): ?>
                                    <?php 
                                    // Limiter l'affichage à 6 invités par panel
                                    $displayInvites = array_slice($invitesList, 0, 6);
                                    $remaining = count($invitesList) - 6;
                                    foreach ($displayInvites as $invite): 
                                        $invite = trim($invite);
                                        if (!empty($invite)):
                                    ?>
                                        <div class="guest-item">
                                            <span class="bullet">▸</span>
                                            <span class="guest-name"><?php echo htmlspecialchars($invite); ?></span>
                                        </div>
                                    <?php endif; endforeach; ?>
                                    <?php if ($remaining > 0): ?>
                                        <div class="guest-item" style="color: #aaa; font-style: italic; border-bottom: none;">
                                            <span class="bullet">+</span>
                                            <span class="guest-name"><?php echo $remaining; ?> autre<?php echo $remaining > 1 ? 's' : ''; ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="empty-message">Aucun invité assigné</div>
                                <?php endif; ?>
                            </div>
                            <div class="panel-footer">
                                <span class="capacity">
                                    <i class="bi bi-people"></i> <?php echo $nb; ?>/<?php echo $cap; ?>
                                    <span class="bar">
                                        <span class="fill" style="width: <?php echo $pourcentage; ?>%;"></span>
                                    </span>
                                </span>
                                <span>
                                    <?php echo $typeLabels[$t['type']] ?? $t['type']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="footer">
            <?php echo APP_NAME; ?> • Plan de salle généré automatiquement • Tous droits réservés
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px; padding: 20px;">
            <button onclick="window.print()" style="background: #f7971e; color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; cursor: pointer;">
                🖨️ Imprimer / PDF
            </button>
            <a href="plan.php?evenement=<?php echo $evenement_id; ?>" style="background: #f8f9fa; color: #666; border: 1px solid #ddd; padding: 12px 30px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-block; margin-left: 10px;">
                ↩️ Retour au plan
            </a>
        </div>

        <script>
            <?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>
                window.onload = function() { window.print(); }
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// EXPORT EN CSV
// ============================================

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plan_salle_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Table', 'Zone', 'Type', 'Capacité', 'Occupée', 'Invités', 'Statut']);
    
    foreach ($tables as $t) {
        $nb = (int)($t['nb_personnes'] ?? 0);
        $cap = (int)($t['capacite_max'] ?? 4);
        $status = $nb >= $cap ? 'Pleine' : ($nb > 0 ? 'Partielle' : 'Vide');
        
        fputcsv($output, [
            $t['nom'],
            $zoneLabels[$t['zone']] ?? $t['zone'],
            $typeLabels[$t['type']] ?? $t['type'],
            $cap,
            $nb,
            $t['invites_noms'] ?? '',
            $status
        ]);
    }
    
    fclose($output);
    exit;
}

// ============================================
// EXPORT EN EXCEL (XLS)
// ============================================

if ($format === 'xls') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="plan_salle_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Plan de salle - <?php echo htmlspecialchars($evenement['nom']); ?></title>
        <style>
            table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
            th { background: #f7971e; color: white; padding: 10px; border: 1px solid #ddd; }
            td { padding: 8px; border: 1px solid #ddd; }
            .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .summary { margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="header">Plan de salle - <?php echo htmlspecialchars($evenement['nom']); ?></div>
        <div class="summary">
            Date: <?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?>
            <?php if ($evenement['lieu']): ?> • Lieu: <?php echo htmlspecialchars($evenement['lieu']); ?><?php endif; ?>
            • Généré: <?php echo date('d/m/Y H:i'); ?>
        </div>
        <br>
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Zone</th>
                    <th>Type</th>
                    <th>Capacité</th>
                    <th>Occupée</th>
                    <th>Invités</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $t): 
                    $nb = (int)($t['nb_personnes'] ?? 0);
                    $cap = (int)($t['capacite_max'] ?? 4);
                    $status = $nb >= $cap ? 'Pleine' : ($nb > 0 ? 'Partielle' : 'Vide');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['nom']); ?></td>
                        <td><?php echo $zoneLabels[$t['zone']] ?? $t['zone']; ?></td>
                        <td><?php echo $typeLabels[$t['type']] ?? $t['type']; ?></td>
                        <td><?php echo $cap; ?></td>
                        <td><?php echo $nb; ?></td>
                        <td><?php echo htmlspecialchars($t['invites_noms'] ?? ''); ?></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <div>
            <strong>Résumé:</strong>
            Total tables: <?php echo count($tables); ?> • 
            Total places: <?php echo array_sum(array_column($tables, 'capacite_max')); ?> • 
            Total occupées: <?php echo array_sum(array_column($tables, 'nb_personnes')); ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>