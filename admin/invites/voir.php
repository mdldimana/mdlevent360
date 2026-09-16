<?php
// Inclure l'authentification
require_once __DIR__ . '/../../includes/auth.php';

// Vérifier les permissions
requirePermission('invites.voir');

// Récupérer les informations de l'utilisateur courant
$user = getCurrentUser();

// Connexion à la base
$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ⭐ VÉRIFICATION DES DROITS D'ACCÈS
if (!userCanAccessInvite($pdo, (int)$user['id'], $id)) {
    http_response_code(403);
    die('<div style="padding:50px;text-align:center;font-family:sans-serif;background:#f8f5f2;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <div style="font-size:80px;">🚫</div>
        <h1 style="color:#c17c60;margin:20px 0;">Accès refusé</h1>
        <p style="color:#6a5a4a;margin-bottom:30px;">Vous n\'avez pas les droits pour consulter cet invité.</p>
        <a href="index.php" style="background:linear-gradient(135deg,#c17c60,#d4a574);color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;">
            ← Retour à la liste
        </a>
    </div>');
}

// Récupérer l'invité avec son événement
$invite = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.nom as categorie_nom,
            e.nom as evenement_nom,
            e.date_evenement as evenement_date,
            e.heure_evenement as evenement_heure,
            e.lieu as evenement_lieu,
            e.id as evenement_id,
            COUNT(DISTINCT inv.id) as nb_invitations,
            COUNT(DISTINCT CASE WHEN inv.statut = 'CONFIRMEE' THEN inv.id END) as nb_confirmations,
            COUNT(DISTINCT CASE WHEN inv.statut = 'REFUSEE' THEN inv.id END) as nb_refus,
            COUNT(DISTINCT p.id) as nb_presences,
            COUNT(DISTINCT pi.id) as nb_boissons,
            COUNT(DISTINCT it.id) as a_table
        FROM invites i
        LEFT JOIN categories_invites c ON i.id_categorie = c.id
        LEFT JOIN evenements e ON e.id = i.id_evenement
        LEFT JOIN invitations inv ON i.id = inv.id_invite
        LEFT JOIN presences p ON inv.id = p.id_invitation
        LEFT JOIN preferences_invitation pi ON inv.id = pi.id_invitation
        LEFT JOIN invitations_tables it ON inv.id = it.id_invitation
        WHERE i.id = ?
        GROUP BY i.id
    ");
    $stmt->execute([$id]);
    $invite = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erreur voir invité: ' . $e->getMessage());
}

if (!$invite) {
    header('Location: index.php');
    exit;
}

// Récupérer les invitations de l'invité
$invitations = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            inv.id, inv.code_unique, inv.statut, inv.created_at, inv.nb_presents,
            e.nom as evenement_nom,
            e.date_evenement,
            (SELECT COUNT(*) FROM preferences_invitation pi WHERE pi.id_invitation = inv.id) as nb_boissons,
            (SELECT COUNT(*) FROM invitations_tables it WHERE it.id_invitation = inv.id) as a_table,
            t.nom as table_nom
        FROM invitations inv
        JOIN evenements e ON inv.id_evenement = e.id
        LEFT JOIN invitations_tables it ON it.id_invitation = inv.id
        LEFT JOIN tables t ON t.id = it.id_table
        WHERE inv.id_invite = ?
        ORDER BY inv.created_at DESC
    ");
    $stmt->execute([$id]);
    $invitations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur invitations: ' . $e->getMessage());
}

// ⭐ Récupérer les utilisateurs associés à l'événement
$utilisateursEvenement = [];
if (!empty($invite['evenement_id'])) {
    $utilisateursEvenement = getUtilisateursEvenement($pdo, (int)$invite['evenement_id']);
}

// Couleurs des statuts d'invitations
$statutColors = [
    'EN_ATTENTE' => 'warning',
    'CONFIRMEE' => 'success',
    'REFUSEE' => 'danger',
    'PRESENTE' => 'info',
    'ANNULEE' => 'secondary'
];

// Avatars couleurs
$avatarColors = ['green', 'orange', 'blue', 'pink', 'purple', 'red'];
$color = $avatarColors[($id % count($avatarColors))];
$initiales = strtoupper(substr($invite['prenom'] ?? '', 0, 1) . substr($invite['nom'] ?? '', 0, 1));

// Photo
$uploadDir = __DIR__ . '/../../uploads/photos/';
$photoUrl = '';
$hasPhoto = false;
if (!empty($invite['photo'])) {
    $photoPath = $uploadDir . $invite['photo'];
    if (is_file($photoPath)) {
        $photoUrl = BASE_PATH . '/uploads/photos/' . rawurlencode($invite['photo']);
        $hasPhoto = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails invité - <?php echo APP_NAME; ?></title>
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

        /* ========== LAYOUT ========== */
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
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 150;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* ========== CONTENT ========== */
        .content-section { padding: 25px 30px; }

        /* ========== CARTE PRINCIPALE ========== */
        .detail-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 1100px;
            margin: 0 auto 20px;
        }

        /* ========== EN-TÊTE PROFIL ========== */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            flex-wrap: wrap;
            gap: 20px;
        }
        .profile-info { display: flex; align-items: center; gap: 20px; }
        .profile-avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 34px;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(193, 124, 96, 0.25);
            overflow: hidden;
            border: 3px solid white;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-avatar.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .profile-avatar.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .profile-avatar.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .profile-avatar.pink { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .profile-avatar.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .profile-avatar.red { background: linear-gradient(135deg, #ef4444, #f87171); }

        .profile-title h3 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 5px;
            font-size: 24px;
        }
        .profile-title .sub {
            color: #9a8a7f;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .profile-title .sub span { display: flex; align-items: center; gap: 6px; }
        .profile-title .sub i { color: #c17c60; width: 16px; }

        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        .btn-edit, .btn-back {
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-edit {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            box-shadow: 0 4px 15px rgba(193, 124, 96, 0.25);
        }
        .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(193, 124, 96, 0.35); color: white; }
        .btn-back {
            background: rgba(255, 255, 255, 0.8);
            color: #6a5a4a;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
        }
        .btn-back:hover { background: white; color: #c17c60; border-color: #c17c60; }

        /* ========== BADGES ========== */
        .badge-categorie {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-categorie.primary { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-categorie.success { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .badge-categorie.warning { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .badge-categorie.info { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .badge-categorie.secondary { background: rgba(108, 117, 125, 0.15); color: #495057; }
        .badge-categorie.dark { background: rgba(33, 37, 41, 0.15); color: #212529; }
        .badge-categorie.light { background: rgba(248, 249, 250, 0.8); color: #6c757d; border: 1px solid #dee2e6; }

        .badge-evenement {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.15));
            color: #c17c60;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(193, 124, 96, 0.2);
        }
        .badge-evenement i { font-size: 14px; }

        .badge-preference {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-preference.email { background: rgba(108, 117, 125, 0.15); color: #495057; }
        .badge-preference.whatsapp { background: rgba(37, 211, 102, 0.15); color: #0d7a3f; }
        .badge-preference.telegram { background: rgba(0, 136, 204, 0.15); color: #005a8a; }
        .badge-preference.sms { background: rgba(245, 158, 11, 0.15); color: #92400e; }

        /* ========== STATISTIQUES ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 16px;
            padding: 18px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.3);
        }
        .stat-card .stat-icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
        }
        .stat-card .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; }
        .stat-card .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .stat-card .stat-icon.orange { background: linear-gradient(135deg, #c17c60, #d4a574); color: white; }
        .stat-card .stat-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); color: white; }
        .stat-card .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #c084fc); color: white; }

        .stat-card .stat-number {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: #9a8a7f;
            font-weight: 500;
        }

        /* ========== SECTIONS ========== */
        .section-title {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: #c17c60; }

        /* ========== INFO ROWS ========== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(251, 248, 245, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(234, 227, 220, 0.4);
            transition: all 0.3s ease;
        }
        .info-item:hover {
            background: rgba(251, 248, 245, 0.8);
            border-color: rgba(193, 124, 96, 0.2);
        }
        .info-item .info-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.1), rgba(212, 165, 116, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-item .info-icon i { color: #c17c60; font-size: 16px; }
        .info-item .info-content { flex: 1; min-width: 0; }
        .info-item .info-label {
            font-size: 11px;
            color: #9a8a7f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-item .info-value {
            color: #1a1a1a;
            font-weight: 500;
            font-size: 14px;
            word-break: break-word;
        }

        /* ========== TABLE INVITATIONS ========== */
        .invitations-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .invitations-table thead th {
            background: rgba(251, 248, 245, 0.7);
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9a8a7f;
            font-weight: 700;
            border-bottom: 2px solid rgba(193, 124, 96, 0.1);
        }
        .invitations-table thead th:first-child { border-radius: 12px 0 0 0; }
        .invitations-table thead th:last-child { border-radius: 0 12px 0 0; }
        .invitations-table tbody tr {
            transition: all 0.3s ease;
        }
        .invitations-table tbody tr:hover { background: rgba(193, 124, 96, 0.03); }
        .invitations-table tbody td {
            padding: 14px 15px;
            border-bottom: 1px solid rgba(234, 227, 220, 0.4);
            font-size: 14px;
            color: #1a1a1a;
            vertical-align: middle;
        }
        .invitations-table tbody tr:last-child td { border-bottom: none; }

        .code-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.1), rgba(212, 165, 116, 0.1));
            color: #c17c60;
            padding: 4px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(193, 124, 96, 0.15);
        }

        .statut-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-block;
        }
        .statut-badge.warning { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .statut-badge.success { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .statut-badge.danger { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .statut-badge.info { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .statut-badge.secondary { background: rgba(108, 117, 125, 0.15); color: #495057; }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: rgba(251, 248, 245, 0.5);
            border-radius: 16px;
            border: 2px dashed rgba(234, 227, 220, 0.6);
        }
        .empty-state i {
            font-size: 45px;
            color: #d4c5b2;
            margin-bottom: 12px;
            display: block;
        }
        .empty-state p { color: #9a8a7f; margin: 0 0 15px; }

        /* ========== UTILISATEURS ASSOCIÉS ========== */
        .user-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(251, 248, 245, 0.7);
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 12px;
            padding: 8px 14px 8px 8px;
            transition: all 0.3s ease;
        }
        .user-chip:hover {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193, 124, 96, 0.1);
        }
        .user-chip .chip-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .user-chip .chip-info { display: flex; flex-direction: column; gap: 1px; }
        .user-chip .chip-name { font-weight: 600; color: #1a1a1a; font-size: 13px; line-height: 1.2; }
        .user-chip .chip-role {
            font-size: 10px;
            color: #c17c60;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

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
                overflow-y: auto; overflow-x: hidden;
                border-radius: 0 18px 18px 0;
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
            .detail-card { padding: 20px; }
            .profile-header { flex-direction: column; align-items: stretch; }
            .profile-info { flex-direction: column; text-align: center; align-items: center; }
            .profile-actions { justify-content: center; }
            .top-bar .page-title h4 { font-size: 1rem; }
            .top-bar .user-info .user-name { display: none; }
            .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .info-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .content-section { padding: 10px 12px; }
            .detail-card { padding: 15px; }
            .profile-avatar { width: 70px; height: 70px; font-size: 26px; }
            .profile-title h3 { font-size: 18px; }
            .profile-actions { flex-direction: column; }
            .btn-edit, .btn-back { width: 100%; justify-content: center; padding: 10px 16px; font-size: 13px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 14px 12px; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 16px; }
            .stat-card .stat-number { font-size: 20px; }
            .stat-card .stat-label { font-size: 11px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .invitations-table { font-size: 12px; }
            .invitations-table thead th { padding: 8px 10px; font-size: 10px; }
            .invitations-table tbody td { padding: 10px; }
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
                <h4><i class="bi bi-person-badge"></i> Détails de l'invité</h4>
                <small><i class="bi bi-eye"></i> Informations complètes et statistiques</small>
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

            <!-- CARTE PRINCIPALE -->
            <div class="detail-card fade-in">

                <!-- EN-TÊTE PROFIL -->
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar <?php echo $color; ?>">
                            <?php if ($hasPhoto): ?>
                                <img src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" onerror="this.parentElement.innerHTML='<?php echo htmlspecialchars($initiales ?: '?', ENT_QUOTES, 'UTF-8'); ?>'">
                            <?php else: ?>
                                <?php echo $initiales ?: '?'; ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-title">
                            <h3><?php echo htmlspecialchars($invite['prenom'] . ' ' . $invite['nom']); ?></h3>
                            <div class="sub">
                                <?php if ($invite['email']): ?>
                                    <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($invite['email']); ?></span>
                                <?php endif; ?>
                                <?php if ($invite['telephone']): ?>
                                    <span><i class="bi bi-phone"></i> <?php echo htmlspecialchars($invite['telephone']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <?php if (hasPermission('invites.modifier')): ?>
                            <a href="modifier.php?id=<?php echo $invite['id']; ?>" class="btn-edit">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <!-- ⭐ BADGE ÉVÉNEMENT -->
                <?php if (!empty($invite['evenement_nom'])): ?>
                <div class="mb-4">
                    <div class="badge-evenement" style="font-size: 13px; padding: 8px 18px;">
                        <i class="bi bi-calendar-event"></i>
                        <strong><?php echo htmlspecialchars($invite['evenement_nom']); ?></strong>
                        <span style="opacity: 0.7;">•</span>
                        <span><?php echo date('d/m/Y', strtotime($invite['evenement_date'])); ?></span>
                        <?php if (!empty($invite['evenement_heure'])): ?>
                            <span style="opacity: 0.7;">•</span>
                            <span><?php echo date('H:i', strtotime($invite['evenement_heure'])); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($invite['evenement_lieu'])): ?>
                            <span style="opacity: 0.7;">•</span>
                            <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($invite['evenement_lieu']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- STATISTIQUES -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-envelope-fill"></i></div>
                        <div class="stat-number"><?php echo $invite['nb_invitations'] ?? 0; ?></div>
                        <div class="stat-label">Invitations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-number"><?php echo $invite['nb_confirmations'] ?? 0; ?></div>
                        <div class="stat-label">Confirmés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-number"><?php echo $invite['nb_presences'] ?? 0; ?></div>
                        <div class="stat-label">Présents</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-number"><?php echo $invite['nb_refus'] ?? 0; ?></div>
                        <div class="stat-label">Refusés</div>
                    </div>
                </div>

                <!-- INFORMATIONS PERSONNELLES -->
                <h6 class="section-title"><i class="bi bi-info-circle-fill"></i> Informations personnelles</h6>
                <div class="info-grid mb-4">
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-person-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Prénom</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['prenom']); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-person-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Nom</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['nom']); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['email'] ?? 'Non renseigné'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-phone-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Téléphone</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['telephone'] ?? 'Non renseigné'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-tags-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Catégorie</div>
                            <div class="info-value">
                                <?php if ($invite['categorie_nom']): ?>
                                    <span class="badge-categorie <?php echo strtolower($invite['categorie_nom']); ?>">
                                        <?php echo htmlspecialchars($invite['categorie_nom']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #b8a99c;">Non catégorisé</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-building"></i></div>
                        <div class="info-content">
                            <div class="info-label">Entreprise</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['entreprise'] ?? 'Non renseignée'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Nombre de personnes</div>
                            <div class="info-value"><?php echo $invite['nombre_personnes']; ?> personne(s)</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-chat-dots-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Préférence de contact</div>
                            <div class="info-value">
                                <span class="badge-preference <?php echo strtolower($invite['contact_preference'] ?? 'EMAIL'); ?>">
                                    <?php echo $invite['contact_preference'] ?? 'EMAIL'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Adresse</div>
                            <div class="info-value"><?php echo htmlspecialchars($invite['adresse'] ?? 'Non renseignée'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-calendar-plus-fill"></i></div>
                        <div class="info-content">
                            <div class="info-label">Créé le</div>
                            <div class="info-value"><?php echo date('d/m/Y à H:i', strtotime($invite['created_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- REMARQUES -->
                <?php if (!empty($invite['remarque'])): ?>
                <h6 class="section-title"><i class="bi bi-chat-left-text-fill"></i> Remarques</h6>
                <div class="info-item mb-4" style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2);">
                    <div class="info-icon" style="background: rgba(245, 158, 11, 0.15);">
                        <i class="bi bi-exclamation-circle-fill" style="color: #f59e0b;"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-value" style="font-weight: 400; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($invite['remarque'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ⭐ UTILISATEURS ASSOCIÉS À L'ÉVÉNEMENT -->
                <?php if (!empty($utilisateursEvenement)): ?>
                <h6 class="section-title">
                    <i class="bi bi-people-fill"></i> 
                    Équipe organisatrice de l'événement
                    <span class="badge" style="background: linear-gradient(135deg, #c17c60, #d4a574); color: white; font-size: 11px; padding: 3px 10px; border-radius: 12px; margin-left: 8px;">
                        <?php echo count($utilisateursEvenement); ?>
                    </span>
                </h6>
                <div class="user-chips mb-4">
                    <?php 
                    $rolesDispo = getRolesSpecifiquesDisponibles();
                    foreach ($utilisateursEvenement as $u): 
                        $uInitiales = strtoupper(substr($u['prenom'] ?? 'U', 0, 1) . substr($u['nom'] ?? 'N', 0, 1));
                        $roleLabel = '';
                        if (!empty($u['role_specifique']) && isset($rolesDispo[$u['role_specifique']])) {
                            $roleLabel = $rolesDispo[$u['role_specifique']];
                        }
                    ?>
                        <div class="user-chip">
                            <div class="chip-avatar"><?php echo $uInitiales ?: 'U'; ?></div>
                            <div class="chip-info">
                                <div class="chip-name"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></div>
                                <div class="chip-role">
                                    <?php echo $roleLabel ?: 'Organisateur'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- INVITATIONS -->
                <h6 class="section-title"><i class="bi bi-envelope-paper-fill"></i> Invitations</h6>
                <?php if (!empty($invitations)): ?>
                    <div style="overflow-x: auto;">
                        <table class="invitations-table">
                            <thead>
                                <tr>
                                    <th>Événement</th>
                                    <th>Code</th>
                                    <th>Statut</th>
                                    <th>Boissons</th>
                                    <th>Table</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invitations as $inv): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #1a1a1a;"><?php echo htmlspecialchars($inv['evenement_nom']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="code-badge"><?php echo htmlspecialchars($inv['code_unique']); ?></span>
                                        </td>
                                        <td>
                                            <span class="statut-badge <?php echo $statutColors[$inv['statut']] ?? 'secondary'; ?>">
                                                <?php echo str_replace('_', ' ', $inv['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($inv['nb_boissons']) && $inv['nb_boissons'] > 0): ?>
                                                <span style="color: #c17c60; font-weight: 600;">
                                                    <i class="bi bi-cup-straw"></i> <?php echo $inv['nb_boissons']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #b8a99c; font-size: 12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($inv['table_nom'])): ?>
                                                <span style="color: #c17c60; font-weight: 600;">
                                                    <i class="bi bi-table"></i> <?php echo htmlspecialchars($inv['table_nom']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #b8a99c; font-size: 12px;">Non assigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #6a5a4a; font-size: 13px;">
                                            <?php echo date('d/m/Y', strtotime($inv['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-envelope"></i>
                        <p>Aucune invitation pour cet invité</p>
                        <?php if (hasPermission('invitations.creer')): ?>
                            <a href="../invitations/creer.php?invite=<?php echo $invite['id']; ?>" class="btn-edit" style="font-size: 13px; padding: 8px 16px;">
                                <i class="bi bi-plus-circle"></i> Créer une invitation
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ACTIONS SUPPLÉMENTAIRES -->
                <div class="mt-4 pt-4" style="border-top: 2px dashed rgba(193, 124, 96, 0.1); display: flex; flex-wrap: wrap; gap: 12px;">
                    <?php if (hasPermission('invitations.creer')): ?>
                        <a href="../invitations/creer.php?invite=<?php echo $invite['id']; ?>" class="btn-edit">
                            <i class="bi bi-plus-circle-fill"></i> Inviter à un événement
                        </a>
                    <?php endif; ?>
                    <?php if (hasPermission('invites.supprimer')): ?>
                        <a href="supprimer.php?id=<?php echo $invite['id']; ?>" 
                           class="btn-back" 
                           style="color: #dc2626; border-color: rgba(220, 38, 38, 0.2);"
                           onclick="return confirm('Supprimer cet invité ?')">
                            <i class="bi bi-trash-fill"></i> Supprimer
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- FOOTER -->
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
        if (sidebarWrapper.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
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
</script>
</body>
</html>