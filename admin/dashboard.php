<?php
// Inclure l'authentification
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que l'utilisateur est connecté
requireLogin('login.php');

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();
$userId = (int)getCurrentUserId();

// Connexion à la base pour les statistiques
$pdo = getDbConnection();

// ============================================
// STATISTIQUES AVEC FILTRAGE PAR UTILISATEUR
// ============================================

$stats = [
    'evenements'   => 0,
    'invites'      => 0,
    'invitations'  => 0,
    'confirmes'    => 0,
    'refuses'      => 0,
    'en_attente'   => 0,
    'present'      => 0,
    'absent'       => 0
];

try {
    if (isAdmin()) {
        // ===== ADMIN : Toutes les statistiques =====
        $stmt = $pdo->query("SELECT COUNT(*) FROM evenements WHERE statut != 'ANNULE'");
        $stats['evenements'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM invites WHERE actif = 1");
        $stats['invites'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM invitations");
        $stats['invitations'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT statut, COUNT(*) AS total FROM invitations GROUP BY statut");

        $correspondanceStatuts = [
            'CONFIRME' => 'confirmes', 'CONFIRMÉ' => 'confirmes', 'CONFIRMEE' => 'confirmes',
            'CONFIRMÉE' => 'confirmes', 'CONFIRMED' => 'confirmes', 'ACCEPTE' => 'confirmes',
            'ACCEPTÉ' => 'confirmes', 'OUI' => 'confirmes', 'PRESENTE' => 'confirmes',
            'REFUSE' => 'refuses', 'REFUSÉ' => 'refuses', 'REFUSEE' => 'refuses',
            'REFUSÉE' => 'refuses', 'REFUSED' => 'refuses', 'NON' => 'refuses',
            'EN_ATTENTE' => 'en_attente', 'EN ATTENTE' => 'en_attente', 'ATTENTE' => 'en_attente',
            'PENDING' => 'en_attente', 'ENCOURS' => 'en_attente', 'EN_COURS' => 'en_attente'
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statut = strtoupper(trim((string) $row['statut']));
            if (isset($correspondanceStatuts[$statut])) {
                $cle = $correspondanceStatuts[$statut];
                $stats[$cle] += (int) $row['total'];
            }
        }

        $stmt = $pdo->query("SELECT COUNT(DISTINCT id_invitation) FROM presences");
        $stats['present'] = (int) $stmt->fetchColumn();

    } else {
        // ===== NON-ADMIN : Statistiques filtrées =====

        // Événements accessibles
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.id)
            FROM evenements e
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
            WHERE e.statut != 'ANNULE' AND eu.id_utilisateur = ?
        ");
        $stmt->execute([$userId]);
        $stats['evenements'] = (int) $stmt->fetchColumn();

        // Invités accessibles
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT i.id)
            FROM invites i
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = i.id_evenement
            WHERE i.actif = 1 AND eu.id_utilisateur = ?
        ");
        $stmt->execute([$userId]);
        $stats['invites'] = (int) $stmt->fetchColumn();

        // Invitations accessibles
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT inv.id)
            FROM invitations inv
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
            WHERE eu.id_utilisateur = ?
        ");
        $stmt->execute([$userId]);
        $stats['invitations'] = (int) $stmt->fetchColumn();

        // Statistiques par statut (filtrées)
        $stmt = $pdo->prepare("
            SELECT inv.statut, COUNT(*) AS total
            FROM invitations inv
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
            WHERE eu.id_utilisateur = ?
            GROUP BY inv.statut
        ");
        $stmt->execute([$userId]);

        $correspondanceStatuts = [
            'CONFIRME' => 'confirmes', 'CONFIRMÉ' => 'confirmes', 'CONFIRMEE' => 'confirmes',
            'CONFIRMÉE' => 'confirmes', 'CONFIRMED' => 'confirmes', 'ACCEPTE' => 'confirmes',
            'ACCEPTÉ' => 'confirmes', 'OUI' => 'confirmes', 'PRESENTE' => 'confirmes',
            'REFUSE' => 'refuses', 'REFUSÉ' => 'refuses', 'REFUSEE' => 'refuses',
            'REFUSÉE' => 'refuses', 'REFUSED' => 'refuses', 'NON' => 'refuses',
            'EN_ATTENTE' => 'en_attente', 'EN ATTENTE' => 'en_attente', 'ATTENTE' => 'en_attente',
            'PENDING' => 'en_attente', 'ENCOURS' => 'en_attente', 'EN_COURS' => 'en_attente'
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statut = strtoupper(trim((string) $row['statut']));
            if (isset($correspondanceStatuts[$statut])) {
                $cle = $correspondanceStatuts[$statut];
                $stats[$cle] += (int) $row['total'];
            }
        }

        // Présents (filtrés)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id_invitation)
            FROM presences p
            INNER JOIN invitations inv ON inv.id = p.id_invitation
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
            WHERE eu.id_utilisateur = ?
        ");
        $stmt->execute([$userId]);
        $stats['present'] = (int) $stmt->fetchColumn();
    }

} catch (PDOException $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// ============================================
// CALCUL DES POURCENTAGES
// ============================================

$totalInvitations = $stats['invitations'] > 0 ? $stats['invitations'] : 1;
$tauxConfirmation = round(($stats['confirmes'] / $totalInvitations) * 100);
$tauxRefus = round(($stats['refuses'] / $totalInvitations) * 100);
$tauxEnAttente = round(($stats['en_attente'] / $totalInvitations) * 100);
$tauxPresence = $stats['confirmes'] > 0 ? round(($stats['present'] / $stats['confirmes']) * 100) : 0;
$tauxAbsence = $stats['confirmes'] > 0 ? round((($stats['confirmes'] - $stats['present']) / $stats['confirmes']) * 100) : 0;

$stats['absent'] = max(0, $stats['confirmes'] - $stats['present']);

// ============================================
// ÉVÉNEMENTS RÉCENTS (FILTRÉS par utilisateur)
// ============================================

$evenementsRecents = [];
try {
    if (isAdmin()) {
        $stmt = $pdo->query("
            SELECT id, nom, date_evenement, statut 
            FROM evenements 
            WHERE statut != 'ANNULE' 
            ORDER BY date_evenement DESC 
            LIMIT 5
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT e.id, e.nom, e.date_evenement, e.statut 
            FROM evenements e
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
            WHERE e.statut != 'ANNULE' AND eu.id_utilisateur = ?
            ORDER BY e.date_evenement DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
    }
    $evenementsRecents = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard evenementsRecents error: ' . $e->getMessage());
}

// ============================================
// DERNIERS INVITÉS (FILTRÉS par utilisateur)
// ============================================

$derniersInvites = [];
try {
    if (isAdmin()) {
        $stmt = $pdo->query("
            SELECT i.id, i.nom, i.prenom, i.telephone, i.email, i.created_at,
                   e.nom AS evenement_nom
            FROM invites i
            LEFT JOIN evenements e ON e.id = i.id_evenement
            WHERE i.actif = 1 
            ORDER BY i.created_at DESC 
            LIMIT 5
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT i.id, i.nom, i.prenom, i.telephone, i.email, i.created_at,
                   e.nom AS evenement_nom
            FROM invites i
            LEFT JOIN evenements e ON e.id = i.id_evenement
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = i.id_evenement
            WHERE i.actif = 1 AND eu.id_utilisateur = ?
            ORDER BY i.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
    }
    $derniersInvites = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard derniersInvites error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* ========== CONFETTIS ========== */
        .confetti-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }
        .confetti {
            position: absolute;
            width: 10px; height: 10px;
            top: -10px;
            animation: confettiFall linear forwards;
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg) scale(0.5); opacity: 0; }
        }

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
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);
            z-index: 150; opacity: 0; transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* ========== WELCOME BANNER ========== */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.08) 0%, rgba(212, 165, 116, 0.08) 100%);
            border: 2px solid rgba(193, 124, 96, 0.15);
            border-radius: 20px;
            padding: 30px 35px;
            margin: 25px 30px 0 30px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .welcome-banner::before {
            content: '🎉';
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 80px;
            opacity: 0.15;
        }
        .welcome-banner .welcome-text { position: relative; z-index: 1; }
        .welcome-banner .welcome-text h3 {
            font-weight: 700;
            margin: 0;
            color: #1a1a1a;
            font-size: 24px;
        }
        .welcome-banner .welcome-text h3 span {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome-banner .welcome-text p {
            color: #6a5a4a;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .welcome-banner .welcome-text .permissions-count {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .welcome-banner .welcome-text .permissions-count span {
            background: rgba(255, 255, 255, 0.7);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            color: #6a5a4a;
            border: 1px solid rgba(193, 124, 96, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .welcome-banner .welcome-text .permissions-count span i { color: #c17c60; }

        /* ========== STATS ========== */
        .stats-section { padding: 25px 30px; }

        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(193, 124, 96, 0.15);
            border-color: rgba(193, 124, 96, 0.3);
        }

        .stat-card .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card .stat-icon.primary { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .stat-card .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-card .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .stat-card .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card .stat-icon.pink { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .stat-card .stat-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }
        .stat-card .stat-icon.teal { background: linear-gradient(135deg, #14b8a6, #5eead4); }

        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin: 5px 0;
            line-height: 1;
        }
        .stat-card .stat-label {
            color: #9a8a7f;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-card .stat-change {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .stat-card .stat-change.up { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .stat-card .stat-change.down { background: rgba(239, 68, 68, 0.15); color: #991b1b; }
        .stat-card .stat-change.neutral { background: rgba(245, 158, 11, 0.15); color: #92400e; }

        /* Progress bar */
        .progress-festive {
            height: 8px;
            border-radius: 10px;
            background: rgba(234, 227, 220, 0.5);
            margin-top: 12px;
            overflow: hidden;
        }
        .progress-festive .progress-bar {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #c17c60, #d4a574, #c17c60);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
            transition: width 1s ease;
        }
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ========== CONTENT SECTION ========== */
        .content-section { padding: 0 30px 30px 30px; }

        .content-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.4);
            height: 100%;
            transition: all 0.3s ease;
        }
        .content-card:hover {
            box-shadow: 0 10px 35px rgba(193, 124, 96, 0.1);
            border-color: rgba(193, 124, 96, 0.2);
        }

        .content-card .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(193, 124, 96, 0.15);
        }
        .content-card .card-header-custom h6 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .content-card .card-header-custom h6 i { color: #c17c60; }
        .content-card .card-header-custom a {
            color: #c17c60;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .content-card .card-header-custom a:hover { color: #a86a50; gap: 8px; }

        /* ========== EVENT ITEM ========== */
        .event-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(234, 227, 220, 0.4);
            transition: all 0.3s ease;
        }
        .event-item:last-child { border-bottom: none; }
        .event-item:hover {
            padding-left: 10px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.04), rgba(212, 165, 116, 0.02));
            border-radius: 10px;
        }

        .event-item .event-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.1);
        }
        .event-item .event-dot.active { background: #10b981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15); }
        .event-item .event-dot.brouillon { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15); }
        .event-item .event-dot.termine { background: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .event-item .event-dot.annule { background: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15); }

        .event-item .event-info { flex: 1; min-width: 0; }
        .event-item .event-info .event-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .event-item .event-info .event-date {
            font-size: 12px;
            color: #9a8a7f;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .event-item .event-info .event-date i { color: #c17c60; font-size: 11px; }

        .event-item .event-status {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }
        .event-item .event-status.active { background: rgba(16, 185, 129, 0.15); color: #065f46; }
        .event-item .event-status.brouillon { background: rgba(245, 158, 11, 0.15); color: #92400e; }
        .event-item .event-status.termine { background: rgba(59, 130, 246, 0.15); color: #1e40af; }
        .event-item .event-status.annule { background: rgba(239, 68, 68, 0.15); color: #991b1b; }

        /* ========== GUEST ITEM ========== */
        .guest-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(234, 227, 220, 0.4);
            transition: all 0.3s ease;
        }
        .guest-item:last-child { border-bottom: none; }
        .guest-item:hover {
            padding-left: 10px;
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.04), rgba(212, 165, 116, 0.02));
            border-radius: 10px;
        }

        .guest-item .guest-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 15px;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .guest-item .guest-avatar.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .guest-item .guest-avatar.orange { background: linear-gradient(135deg, #c17c60, #d4a574); }
        .guest-item .guest-avatar.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .guest-item .guest-avatar.pink { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .guest-item .guest-avatar.purple { background: linear-gradient(135deg, #a855f7, #c084fc); }

        .guest-item .guest-info { flex: 1; min-width: 0; }
        .guest-item .guest-info .guest-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .guest-item .guest-info .guest-contact {
            font-size: 11px;
            color: #9a8a7f;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .guest-item .guest-info .guest-contact i { color: #c17c60; margin-right: 3px; }
        .guest-item .guest-info .guest-contact .guest-event {
            color: #c17c60;
            font-weight: 600;
        }

        .guest-item .guest-time {
            font-size: 11px;
            color: #b8a99c;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ========== EMPTY STATE ========== */
        .empty-inline {
            text-align: center;
            padding: 30px 20px;
            color: #9a8a7f;
        }
        .empty-inline i {
            font-size: 45px;
            color: #d4c5b2;
            display: block;
            margin-bottom: 12px;
        }
        .empty-inline p { font-size: 13px; margin-bottom: 12px; }

        /* ========== FOOTER ========== */
        .app-footer {
            text-align: center;
            padding: 30px 0 20px;
            color: #b8a99c;
            font-size: 13px;
        }
        .app-footer i.bi-heart-fill { color: #c17c60; }

        /* ========== ANIMATIONS ========== */
        .fade-in {
            opacity: 1;
            animation: fadeInUp 0.6s ease both;
        }
        .fade-in:nth-child(1) { animation-delay: 0.05s; }
        .fade-in:nth-child(2) { animation-delay: 0.1s; }
        .fade-in:nth-child(3) { animation-delay: 0.15s; }
        .fade-in:nth-child(4) { animation-delay: 0.2s; }
        .fade-in:nth-child(5) { animation-delay: 0.25s; }
        .fade-in:nth-child(6) { animation-delay: 0.3s; }
        .fade-in:nth-child(7) { animation-delay: 0.35s; }
        .fade-in:nth-child(8) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
                overflow-y: auto; overflow-x: hidden; border-radius: 0 18px 18px 0;
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
            .top-bar { padding: 12px 15px 12px 70px; }
            body.sidebar-open { overflow-x: hidden !important; overflow-y: auto !important; }
            .top-bar .page-title h4 { font-size: 1rem; }
            .top-bar .user-info .user-name { display: none; }
            .top-bar .user-info .role-badge { font-size: 9px; padding: 3px 10px; }
            .stats-section { padding: 15px; }
            .content-section { padding: 0 15px 15px 15px; }
            .welcome-banner { margin: 15px 15px 0 15px; padding: 22px; }
            .welcome-banner::before { font-size: 60px; right: 20px; }
        }

        @media (max-width: 576px) {
            .top-bar { padding: 10px 12px 10px 60px; flex-direction: column; align-items: stretch; gap: 8px; }
            .top-bar .page-title h4 { font-size: 0.95rem; }
            .top-bar .user-info { justify-content: flex-end; gap: 10px; }
            .top-bar .user-info .user-avatar { width: 32px; height: 32px; font-size: 13px; }
            .stats-section { padding: 10px 12px; }
            .content-section { padding: 0 12px 12px 12px; }
            .stat-card { padding: 18px; }
            .stat-card .stat-number { font-size: 24px; }
            .stat-card .stat-icon { width: 45px; height: 45px; font-size: 20px; }
            .welcome-banner { margin: 10px 12px 0 12px; padding: 18px; }
            .welcome-banner::before { display: none; }
            .welcome-banner .welcome-text h3 { font-size: 18px; }
            .welcome-banner .welcome-text .permissions-count span { font-size: 11px; }
            .sidebar-toggle-btn { top: 8px; left: 8px; padding: 6px 10px; font-size: 17px; }
            .guest-item .guest-time { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in { animation: none !important; opacity: 1 !important; transform: none !important; }
            .progress-festive .progress-bar { animation: none !important; }
            .confetti { animation: none !important; display: none !important; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- CONFETTIS -->
<div class="confetti-container" id="confettiContainer"></div>

<div class="app-wrapper">

    <div class="sidebar-wrapper" id="sidebarWrapper">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <div class="main-content" id="mainContent">

        <div class="top-bar">
            <div class="page-title">
                <h4><i class="bi bi-speedometer2"></i> Tableau de bord</h4>
                <small><i class="bi bi-calendar3"></i> <?php echo date('l d F Y'); ?> • Aujourd'hui</small>
            </div>
            <div class="user-info">
                <span class="role-badge">
                    <i class="bi bi-shield-check"></i>
                    <?php echo is_array($user['roles'] ?? null) ? implode(', ', $user['roles']) : 'Aucun rôle'; ?>
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

        <!-- WELCOME BANNER -->
        <div class="welcome-banner fade-in">
            <div class="welcome-text">
                <h3>🎊 Bienvenue, <span><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span> !</h3>
                <p>
                    <?php if (isAdmin()): ?>
                        En tant qu'administrateur, vous avez accès à <strong>toutes les données</strong> de l'application.
                    <?php else: ?>
                        Vous voyez uniquement les données des événements auxquels vous êtes <strong>associé</strong>.
                    <?php endif; ?>
                </p>
                <div class="permissions-count">
                    <span><i class="bi bi-check-circle-fill"></i> <?php echo count($user['permissions'] ?? []); ?> permissions</span>
                    <span><i class="bi bi-star-fill"></i> <?php echo count($user['roles'] ?? []); ?> rôles</span>
                    <span><i class="bi bi-calendar2-event-fill"></i> <?php echo $stats['evenements'] ?? 0; ?> événements</span>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="stats-section">
            <div class="row g-4">

                <!-- Événements -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="bi bi-calendar-event-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['evenements'] ?? 0; ?></div>
                        <div class="stat-label">Événements actifs</div>
                    </div>
                </div>

                <!-- Invités -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['invites'] ?? 0; ?></div>
                        <div class="stat-label">Invités enregistrés</div>
                    </div>
                </div>

                <!-- Confirmés -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['confirmes'] ?? 0; ?></div>
                        <div class="stat-label">Confirmés</div>
                        <div class="progress-festive">
                            <div class="progress-bar" style="width: <?php echo $tauxConfirmation; ?>%"></div>
                        </div>
                        <div style="font-size:11px; color:#9a8a7f; margin-top:8px; font-weight:600;">
                            <?php echo $tauxConfirmation; ?>% de taux de confirmation
                        </div>
                    </div>
                </div>

                <!-- Présents -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['present'] ?? 0; ?></div>
                        <div class="stat-label">Présents</div>
                        <div class="stat-change <?php echo $tauxPresence > 50 ? 'up' : 'neutral'; ?>">
                            <i class="bi bi-arrow-up-short"></i> <?php echo $tauxPresence; ?>% des confirmés
                        </div>
                    </div>
                </div>

                <!-- En attente -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon pink"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-number"><?php echo $stats['en_attente'] ?? 0; ?></div>
                        <div class="stat-label">En attente</div>
                        <div class="stat-change neutral">
                            <i class="bi bi-clock-fill"></i> <?php echo $tauxEnAttente; ?>% du total
                        </div>
                    </div>
                </div>

                <!-- Refusés -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['refuses'] ?? 0; ?></div>
                        <div class="stat-label">Refusés</div>
                        <div class="stat-change down">
                            <i class="bi bi-arrow-down-short"></i> <?php echo $tauxRefus; ?>%
                        </div>
                    </div>
                </div>

                <!-- Absents -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-person-x-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['absent'] ?? 0; ?></div>
                        <div class="stat-label">Absents</div>
                        <div class="stat-change down">
                            <i class="bi bi-arrow-down-short"></i> <?php echo $tauxAbsence; ?>% des confirmés
                        </div>
                    </div>
                </div>

                <!-- Total invitations -->
                <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon teal"><i class="bi bi-envelope-fill"></i></div>
                        <div class="stat-number"><?php echo $stats['invitations'] ?? 0; ?></div>
                        <div class="stat-label">Total invitations</div>
                        <div class="stat-change up">
                            <i class="bi bi-arrow-up-short"></i> Toutes confondues
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CONTENU -->
        <div class="content-section">
            <div class="row g-4">

                <!-- Événements récents -->
                <div class="col-lg-6 fade-in">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h6>
                                <i class="bi bi-calendar3"></i> 
                                Événements récents
                                <?php if (!isAdmin()): ?>
                                    <span style="font-size: 10px; background: rgba(193,124,96,0.15); color: #c17c60; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-transform: none;">
                                        Filtré
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <a href="evenements/index.php"><i class="bi bi-arrow-right"></i> Voir tout</a>
                        </div>
                        <?php if (!empty($evenementsRecents)): ?>
                            <?php foreach ($evenementsRecents as $event): 
                                $statutClass = strtolower($event['statut']);
                            ?>
                                <div class="event-item">
                                    <div class="event-dot <?php echo $statutClass; ?>"></div>
                                    <div class="event-info">
                                        <div class="event-name"><?php echo htmlspecialchars($event['nom']); ?></div>
                                        <div class="event-date">
                                            <i class="bi bi-calendar-event"></i> 
                                            <?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?>
                                        </div>
                                    </div>
                                    <span class="event-status <?php echo $statutClass; ?>">
                                        <?php echo htmlspecialchars($event['statut']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-calendar-plus"></i>
                                <p>
                                    <?php if (isAdmin()): ?>
                                        Aucun événement pour le moment
                                    <?php else: ?>
                                        Aucun événement associé à votre compte
                                    <?php endif; ?>
                                </p>
                                <?php if (hasPermission('evenements.creer') || hasPermission('evenements.ajouter')): ?>
                                    <a href="admin/evenements/creer.php" class="btn" style="background: linear-gradient(135deg, #c17c60, #d4a574); color: white; border: none; font-weight: 600; font-size: 13px; padding: 8px 18px; border-radius: 10px;">
                                        <i class="bi bi-plus-circle"></i> Créer un événement
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Derniers invités -->
                <div class="col-lg-6 fade-in">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h6>
                                <i class="bi bi-person-plus-fill"></i> 
                                Derniers invités
                                <?php if (!isAdmin()): ?>
                                    <span style="font-size: 10px; background: rgba(193,124,96,0.15); color: #c17c60; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-transform: none;">
                                        Filtré
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <a href="invites/index.php"><i class="bi bi-arrow-right"></i> Voir tout</a>
                        </div>
                        <?php if (!empty($derniersInvites)): ?>
                            <?php 
                            $colors = ['green', 'orange', 'blue', 'pink', 'purple'];
                            $i = 0;
                            foreach ($derniersInvites as $invite): 
                                $color = $colors[$i % count($colors)];
                                $initiales = strtoupper(substr($invite['prenom'] ?? '', 0, 1) . substr($invite['nom'] ?? '', 0, 1));
                            ?>
                                <div class="guest-item">
                                    <div class="guest-avatar <?php echo $color; ?>">
                                        <?php echo $initiales ?: '?'; ?>
                                    </div>
                                    <div class="guest-info">
                                        <div class="guest-name"><?php echo htmlspecialchars($invite['prenom'] . ' ' . $invite['nom']); ?></div>
                                        <div class="guest-contact">
                                            <?php if ($invite['email']): ?>
                                                <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($invite['email']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($invite['telephone']): ?>
                                                <span><i class="bi bi-phone-fill"></i> <?php echo htmlspecialchars($invite['telephone']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($invite['evenement_nom'])): ?>
                                                <span class="guest-event">
                                                    <i class="bi bi-calendar-event-fill"></i> <?php echo htmlspecialchars($invite['evenement_nom']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="guest-time">
                                        <i class="bi bi-clock"></i> <?php echo date('d/m/Y', strtotime($invite['created_at'])); ?>
                                    </div>
                                </div>
                            <?php 
                            $i++;
                            endforeach; 
                            ?>
                        <?php else: ?>
                            <div class="empty-inline">
                                <i class="bi bi-people"></i>
                                <p>
                                    <?php if (isAdmin()): ?>
                                        Aucun invité enregistré
                                    <?php else: ?>
                                        Aucun invité dans vos événements
                                    <?php endif; ?>
                                </p>
                                <?php if (hasPermission('invites.creer')): ?>
                                    <a href="admin/invites/creer.php" class="btn" style="background: linear-gradient(135deg, #c17c60, #d4a574); color: white; border: none; font-weight: 600; font-size: 13px; padding: 8px 18px; border-radius: 10px;">
                                        <i class="bi bi-plus-circle"></i> Ajouter un invité
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <div class="app-footer">
            <i class="bi bi-heart-fill"></i>
            <?php echo APP_NAME; ?> • Tous droits réservés • <?php echo date('Y'); ?>
            <br>
            <small style="color: #d4c5b2;">✨ Fait avec passion pour vos événements ✨</small>
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
        if (sidebarWrapper.classList.contains('open')) closeSidebar();
        else openSidebar();
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

// ========== CONFETTIS ==========
function createConfetti() {
    const container = document.getElementById('confettiContainer');
    if (!container) return;
    const colors = ['#c17c60', '#d4a574', '#f472b6', '#60a5fa', '#34d399', '#fbbf24', '#c084fc', '#f87171', '#5eead4'];
    
    for (let i = 0; i < 15; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        const color = colors[Math.floor(Math.random() * colors.length)];
        const size = Math.random() * 8 + 4;
        const left = Math.random() * 100;
        const duration = Math.random() * 3 + 2;
        const delay = Math.random() * 2;
        
        confetti.style.left = left + '%';
        confetti.style.width = size + 'px';
        confetti.style.height = size + 'px';
        confetti.style.background = color;
        confetti.style.animationDuration = duration + 's';
        confetti.style.animationDelay = delay + 's';
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        
        container.appendChild(confetti);
        setTimeout(() => { confetti.remove(); }, (duration + delay) * 1000 + 100);
    }
}

setTimeout(() => {
    createConfetti();
    setInterval(createConfetti, 7000);
}, 800);

// ========== ANIMATION DES NOMBRES ==========
function animateNumbers() {
    const numbers = document.querySelectorAll('.stat-number');
    numbers.forEach(el => {
        const target = parseInt(el.textContent);
        if (target > 0) {
            let current = 0;
            const increment = Math.ceil(target / 30);
            const interval = setInterval(() => {
                current += increment;
                if (current >= target) { current = target; clearInterval(interval); }
                el.textContent = current;
            }, 30);
        }
    });
}
setTimeout(animateNumbers, 600);

// ========== ANIMATION DES BARRES DE PROGRÈS ==========
document.querySelectorAll('.progress-bar').forEach(bar => {
    const width = bar.style.width;
    bar.style.width = '0%';
    setTimeout(() => { bar.style.width = width; }, 500);
});
</script>
</body>
</html>