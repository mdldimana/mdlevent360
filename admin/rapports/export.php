<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('rapports.exporter');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

// Paramètres
$type = $_GET['type'] ?? 'invites';
$format = $_GET['format'] ?? 'pdf';

// Récupérer les données
$data = [];
$title = '';

switch ($type) {
    case 'invites':
        $title = 'Liste des invités';
        $stmt = $pdo->query("
            SELECT 
                i.id, i.nom, i.prenom, i.email, i.telephone, 
                c.nom as categorie, i.entreprise, i.nombre_personnes,
                COUNT(inv.id) as nb_invitations
            FROM invites i
            LEFT JOIN categories_invites c ON i.id_categorie = c.id
            LEFT JOIN invitations inv ON i.id = inv.id_invite
            WHERE i.actif = 1
            GROUP BY i.id
            ORDER BY i.nom
        ");
        $data = $stmt->fetchAll();
        break;
        
    case 'presences':
        $title = 'Liste des présences';
        $stmt = $pdo->query("
            SELECT 
                inv.nom, inv.prenom, inv.email, inv.telephone,
                e.nom as evenement,
                p.date_entree, p.heure_entree, p.nombre_present,
                u.nom as agent
            FROM presences p
            JOIN invitations i ON p.id_invitation = i.id
            JOIN invites inv ON i.id_invite = inv.id
            JOIN evenements e ON i.id_evenement = e.id
            LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id
            ORDER BY p.date_entree DESC, p.heure_entree DESC
        ");
        $data = $stmt->fetchAll();
        break;
        
    case 'boissons':
        $title = 'Statistiques des boissons';
        $stmt = $pdo->query("
            SELECT 
                b.nom,
                b.type,
                COUNT(pi.id) as total_choix,
                SUM(pi.quantite) as total_quantite
            FROM boissons b
            LEFT JOIN preferences_invitation pi ON b.id = pi.id_boisson
            GROUP BY b.id
            ORDER BY total_choix DESC
        ");
        $data = $stmt->fetchAll();
        break;
        
    case 'global':
    default:
        $title = 'Rapport global';
        // Statistiques globales
        $stats = [];
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM evenements WHERE statut != 'ANNULE'");
        $stats['evenements'] = $stmt->fetch()['count'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM invites WHERE actif = 1");
        $stats['invites'] = $stmt->fetch()['count'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM invitations");
        $stats['invitations'] = $stmt->fetch()['count'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(DISTINCT id_invitation) as count FROM presences");
        $stats['presences'] = $stmt->fetch()['count'] ?? 0;
        $data = $stats;
        break;
}

// ============================================
// EXPORT PDF
// ============================================

if ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $title; ?></title>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; padding: 30px; color: #1a1a2e; }
            h1 { color: #f7971e; border-bottom: 2px solid #f7971e; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
            th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #f7971e; }
            td { padding: 8px 10px; border-bottom: 1px solid #eee; }
            tr:hover { background: #fafafa; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #aaa; font-size: 12px; }
            .summary { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
            .summary span { margin-right: 20px; }
            @media print { body { padding: 20px; } }
        </style>
    </head>
    <body>
        <h1><?php echo $title; ?></h1>
        <p>Généré le <?php echo date('d/m/Y à H:i'); ?></p>
        
        <?php if ($type === 'invites' && !empty($data)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Catégorie</th>
                        <th>Personnes</th>
                        <th>Invitations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['nom']); ?></td>
                            <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                            <td><?php echo htmlspecialchars($row['categorie'] ?? '-'); ?></td>
                            <td><?php echo $row['nombre_personnes']; ?></td>
                            <td><?php echo $row['nb_invitations']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'presences' && !empty($data)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invité</th>
                        <th>Événement</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Personnes</th>
                        <th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?></td>
                            <td><?php echo htmlspecialchars($row['evenement']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['date_entree'])); ?></td>
                            <td><?php echo $row['heure_entree']; ?></td>
                            <td><?php echo $row['nombre_present']; ?></td>
                            <td><?php echo htmlspecialchars($row['agent'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'boissons' && !empty($data)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Boisson</th>
                        <th>Type</th>
                        <th>Total choix</th>
                        <th>Quantité totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['nom']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo $row['total_choix']; ?></td>
                            <td><?php echo $row['total_quantite']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'global'): ?>
            <div class="summary">
                <span>📊 <strong>Événements :</strong> <?php echo $data['evenements']; ?></span>
                <span>👥 <strong>Invités :</strong> <?php echo $data['invites']; ?></span>
                <span>📨 <strong>Invitations :</strong> <?php echo $data['invitations']; ?></span>
                <span>✅ <strong>Présents :</strong> <?php echo $data['presences']; ?></span>
            </div>
        <?php else: ?>
            <p>Aucune donnée disponible.</p>
        <?php endif; ?>
        
        <div class="footer">
            <?php echo APP_NAME; ?> • Tous droits réservés
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// EXPORT EXCEL (CSV)
// ============================================

if ($format === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes selon le type
    switch ($type) {
        case 'invites':
            fputcsv($output, ['#', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Catégorie', 'Personnes', 'Invitations']);
            foreach ($data as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['nom'],
                    $row['prenom'],
                    $row['email'],
                    $row['telephone'],
                    $row['categorie'] ?? '-',
                    $row['nombre_personnes'],
                    $row['nb_invitations']
                ]);
            }
            break;
            
        case 'presences':
            fputcsv($output, ['#', 'Invitée', 'Événement', 'Date', 'Heure', 'Personnes', 'Agent']);
            foreach ($data as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['prenom'] . ' ' . $row['nom'],
                    $row['evenement'],
                    date('d/m/Y', strtotime($row['date_entree'])),
                    $row['heure_entree'],
                    $row['nombre_present'],
                    $row['agent'] ?? '-'
                ]);
            }
            break;
            
        case 'boissons':
            fputcsv($output, ['#', 'Boisson', 'Type', 'Total choix', 'Quantité totale']);
            foreach ($data as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['nom'],
                    $row['type'],
                    $row['total_choix'],
                    $row['total_quantite']
                ]);
            }
            break;
            
        case 'global':
        default:
            fputcsv($output, ['Métrique', 'Valeur']);
            foreach ($data as $key => $value) {
                fputcsv($output, [ucfirst($key), $value]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>