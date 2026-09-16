<?php
declare(strict_types=1);

// ============================================
// CONFIGURATION DES URLs ABSOLUES - CORRIGÉE
// ============================================

// Détecter BASE_PATH si non défini
if (!defined('BASE_PATH')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';

    if (preg_match('#^(.*?)/admin/#', $scriptPath, $matches)) {
        define('BASE_PATH', $matches[1]);
    } elseif (preg_match('#^(.*?)/public/#', $scriptPath, $matches)) {
        define('BASE_PATH', $matches[1]);
    } else {
        define('BASE_PATH', '');
    }
}

// S'assurer que APP_NAME est défini
if (!defined('APP_NAME')) {
    define('APP_NAME', 'MdlEvent');
}

// ============================================
// FONCTIONS D'URLS
// ============================================

if (!function_exists('adminUrl')) {
    function adminUrl(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/admin/' . ltrim($path, '/');
    }
}

if (!function_exists('appUrl')) {
    function appUrl(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/' . ltrim($path, '/');
    }
}

// ============================================
// DÉTECTION DU LOGO
// ============================================
$logoPath = '';
$logoFullPath = '';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

$possiblePaths = [
    ($_SERVER['DOCUMENT_ROOT'] ?? '') . $basePath . '/assets/images/logo.png',
    ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/images/logo.png',
    __DIR__ . '/../assets/images/logo.png',
    __DIR__ . '/assets/images/logo.png',
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $logoFullPath = $path;
        break;
    }
}

if (!empty($logoFullPath)) {
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $relativePath = str_replace($docRoot, '', $logoFullPath);
    $relativePath = str_replace('\\', '/', $relativePath);
    $logoPath = $relativePath;
}

// ============================================
// STATISTIQUES AVEC FILTRAGE PAR UTILISATEUR
// ============================================
$stats = array_merge([
    'evenements'         => 0,
    'invites'            => 0,
    'invitations'        => 0,
    'present'            => 0,
    'tables'             => 0,
    'tables_assignees'   => 0,
], $stats ?? []);

try {
    $pdo = getDbConnection();

    // ⭐ Déterminer si l'utilisateur est admin (voit tout)
    $isAdminUser = function_exists('isAdmin') ? isAdmin() : false;
    $currentUserId = function_exists('getCurrentUserId') ? (int)getCurrentUserId() : 0;

    if ($isAdminUser) {
        // ===== ADMIN : Voit toutes les statistiques =====
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM evenements WHERE statut != 'ANNULE'");
        $stats['evenements'] = (int)($stmt->fetch()['count'] ?? 0);

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM invites WHERE actif = 1");
        $stats['invites'] = (int)($stmt->fetch()['count'] ?? 0);

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM invitations");
        $stats['invitations'] = (int)($stmt->fetch()['count'] ?? 0);

        $stmt = $pdo->query("SELECT COUNT(DISTINCT id_invitation) AS count FROM presences");
        $stats['present'] = (int)($stmt->fetch()['count'] ?? 0);

        // STATISTIQUES TABLES
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM tables WHERE actif = 1");
        $stats['tables'] = (int)($stmt->fetch()['count'] ?? 0);

        // Tables assignées (nombre de tables ayant au moins 1 invitation)
        $stmt = $pdo->query("SELECT COUNT(DISTINCT id_table) AS count FROM invitations_tables");
        $stats['tables_assignees'] = (int)($stmt->fetch()['count'] ?? 0);

    } else {
        // ===== NON-ADMIN : Voit uniquement ses événements =====

        // Nombre d'événements auxquels il est associé
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.id) AS count 
            FROM evenements e
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
            WHERE eu.id_utilisateur = ? AND e.statut != 'ANNULE'
        ");
        $stmt->execute([$currentUserId]);
        $stats['evenements'] = (int)($stmt->fetch()['count'] ?? 0);

        // Nombre d'invités
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS count 
            FROM invites 
            WHERE actif = 1 AND id_evenement IN (
                SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
            )
        ");
        $stmt->execute([$currentUserId]);
        $stats['invites'] = (int)($stmt->fetch()['count'] ?? 0);

        // Nombre d'invitations
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT inv.id) AS count 
            FROM invitations inv
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
            WHERE eu.id_utilisateur = ?
        ");
        $stmt->execute([$currentUserId]);
        $stats['invitations'] = (int)($stmt->fetch()['count'] ?? 0);

        // Nombre de présences dans ses événements
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id_invitation) AS count 
            FROM presences p
            INNER JOIN invitations inv ON inv.id = p.id_invitation
            INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
            WHERE eu.id_utilisateur = ?
        ");
        $stmt->execute([$currentUserId]);
        $stats['present'] = (int)($stmt->fetch()['count'] ?? 0);

        // STATISTIQUES TABLES (filtrées par événement)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS count 
            FROM tables 
            WHERE actif = 1 AND id_evenement IN (
                SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
            )
        ");
        $stmt->execute([$currentUserId]);
        $stats['tables'] = (int)($stmt->fetch()['count'] ?? 0);

        // Tables assignées (filtrées par événement)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT it.id_table) AS count 
            FROM invitations_tables it
            INNER JOIN tables t ON t.id = it.id_table
            WHERE t.id_evenement IN (
                SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
            )
        ");
        $stmt->execute([$currentUserId]);
        $stats['tables_assignees'] = (int)($stmt->fetch()['count'] ?? 0);
    }
} catch (PDOException $e) {
    error_log('Sidebar stats error: ' . $e->getMessage());
}

// ============================================
// PAGE ACTIVE
// ============================================
$current_page   = basename($_SERVER['PHP_SELF']);
$current_dir    = basename(dirname($_SERVER['PHP_SELF']));
$current_subdir = basename(dirname(dirname($_SERVER['PHP_SELF'])));

if (!function_exists('isMenuActive')) {
    function isMenuActive($dir, $page = null) {
        global $current_dir, $current_page, $current_subdir;

        if ($page && $current_page == $page) return true;
        if ($dir && $current_dir == $dir) return true;
        if ($dir && $current_subdir == $dir) return true;
        return false;
    }
}

// ⭐ Déterminer le rôle admin pour les menus
$isAdminMenu = function_exists('isAdmin') ? isAdmin() : false;
$userCanManageUsers    = function_exists('hasPermission') ? hasPermission('utilisateurs.voir') : $isAdminMenu;
$userCanManageRoles    = function_exists('hasPermission') ? hasPermission('roles.voir')        : $isAdminMenu;
$userCanViewJournal    = function_exists('hasPermission') ? hasPermission('journal.voir')      : $isAdminMenu;
$userCanViewRapports   = function_exists('hasPermission') ? hasPermission('rapports.voir')     : true;
$userCanViewParametres = function_exists('hasPermission') ? hasPermission('parametres.voir')   : $isAdminMenu;
$userCanViewModeles = function_exists('hasPermission') ? hasPermission('modeles.voir')   : $isAdminMenu;

// ============================================
// ⭐ ÉVÉNEMENT PAR DÉFAUT POUR LE LIEN SPLASH
// ============================================

$defaultEventIdForSplash = 0;

// 1) Priorité : événement en session
if (isset($_SESSION['selected_evenement_id']) && (int)$_SESSION['selected_evenement_id'] > 0) {
    $defaultEventIdForSplash = (int)$_SESSION['selected_evenement_id'];
} else {
    // 2) Sinon : premier événement accessible
    try {
        if ($isAdminMenu) {
            $stmt = $pdo->query("
                SELECT id FROM evenements 
                WHERE statut != 'ANNULE' 
                ORDER BY date_evenement ASC 
                LIMIT 1
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT e.id 
                FROM evenements e
                INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
                WHERE eu.id_utilisateur = ? AND e.statut != 'ANNULE'
                ORDER BY e.date_evenement ASC
                LIMIT 1
            ");
            $stmt->execute([$currentUserId]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $defaultEventIdForSplash = (int)$row['id'];
        }
    } catch (PDOException $e) {
        error_log('Erreur événement par défaut splash : ' . $e->getMessage());
    }
}

// URL finale du lien splash
$splashUrl = appUrl('public/splash.php' . ($defaultEventIdForSplash > 0 ? '?evenement=' . $defaultEventIdForSplash : ''));
?>
<!-- SIDEBAR -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <?php if (!empty($logoPath)): ?>
                <div class="brand-logo-img">
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo APP_NAME; ?>" onerror="this.parentElement.innerHTML='<div class=\'brand-logo\'><i class=\'bi bi-stars\'></i></div>'">
                </div>
            <?php else: ?>
                <div class="brand-logo">
                    <i class="bi bi-stars"></i>
                </div>
            <?php endif; ?>
            <h4><?php echo APP_NAME; ?></h4>
            <small>L'expert événementiel</small>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <ul class="nav flex-column">
        <!-- SECTION 1 : PRINCIPAL -->
        <li class="nav-section-title">Navigation</li>

        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo adminUrl('dashboard.php'); ?>">
                <i class="bi bi-speedometer2"></i> Tableau de bord
                <span class="badge">Live</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('evenements') ? 'active' : ''; ?>" href="<?php echo adminUrl('evenements/index.php'); ?>">
                <i class="bi bi-calendar-event"></i> Événements
                <span class="badge"><?php echo $stats['evenements']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('tables') ? 'active' : ''; ?>" href="<?php echo adminUrl('tables/index.php'); ?>">
                <i class="bi bi-table"></i> Tables
                <span class="badge"><?php echo $stats['tables']; ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('invites') ? 'active' : ''; ?>" href="<?php echo adminUrl('invites/index.php'); ?>">
                <i class="bi bi-people"></i> Invités
                <span class="badge"><?php echo $stats['invites']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('invitations') ? 'active' : ''; ?>" href="<?php echo adminUrl('invitations/index.php'); ?>">
                <i class="bi bi-envelope"></i> Invitations
                <span class="badge"><?php echo $stats['invitations']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('boissons') ? 'active' : ''; ?>" href="<?php echo adminUrl('boissons/index.php'); ?>">
                <i class="bi bi-cup"></i> Boissons
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('presences') ? 'active' : ''; ?>" href="<?php echo adminUrl('presences/index.php'); ?>">
                <i class="bi bi-qr-code"></i> Présences
                <span class="badge"><?php echo $stats['present']; ?></span>
            </a>
        </li>

        <hr class="nav-divider">

        <!-- SECTION 2 : COMMUNICATION -->
        <li class="nav-section-title">Communication</li>

        <!-- NOTIFICATIONS -->
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('notifications') ? 'active' : ''; ?>" href="#notificationsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo isMenuActive('notifications') ? 'true' : 'false'; ?>">
                <i class="bi bi-bell"></i> Notifications
                <span class="badge badge-new">Nouveau</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px; width: auto;"></i>
            </a>
            <ul class="nav flex-column collapse <?php echo isMenuActive('notifications') ? 'show' : ''; ?>" id="notificationsSubmenu" style="padding-left: 20px;">
                <li class="nav-item">
                    <a class="nav-link <?php echo isMenuActive('notifications', 'index.php') ? 'active' : ''; ?>" href="<?php echo adminUrl('notifications/index.php'); ?>" style="font-size: 12px; padding: 5px 14px;">
                        <i class="bi bi-grid"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isMenuActive('emails') ? 'active' : ''; ?>" href="<?php echo adminUrl('notifications/emails/index.php'); ?>" style="font-size: 12px; padding: 5px 14px;">
                        <i class="bi bi-envelope"></i> Emails
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isMenuActive('whatsapp') ? 'active' : ''; ?>" href="<?php echo adminUrl('notifications/whatsapp/index.php'); ?>" style="font-size: 12px; padding: 5px 14px;">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isMenuActive('telegram') ? 'active' : ''; ?>" href="<?php echo adminUrl('notifications/telegram/index.php'); ?>" style="font-size: 12px; padding: 5px 14px;">
                        <i class="bi bi-telegram"></i> Telegram
                    </a>
                </li>
            </ul>
        </li>

        <hr class="nav-divider">

        <!-- SECTION 3 : ADMINISTRATION -->
        <li class="nav-section-title">Administration</li>
        <?php if ($userCanViewModeles): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('modeles') ? 'active' : ''; ?>" href="<?php echo adminUrl('modeles/index.php'); ?>">
                <i class="bi bi-gear"></i> Modèles d'invitation
            </a>
        </li>
        <?php endif; ?>
        <?php if ($userCanViewRapports): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('rapports') ? 'active' : ''; ?>" href="<?php echo adminUrl('rapports/index.php'); ?>">
                <i class="bi bi-file-earmark"></i> Rapports
            </a>
        </li>
        <?php endif; ?>

        <?php if ($userCanManageUsers): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('utilisateurs') ? 'active' : ''; ?>" href="<?php echo adminUrl('utilisateurs/index.php'); ?>">
                <i class="bi bi-person-gear"></i> Utilisateurs
            </a>
        </li>
        <?php endif; ?>

        <?php if ($userCanManageRoles): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('roles') ? 'active' : ''; ?>" href="<?php echo adminUrl('roles/index.php'); ?>">
                <i class="bi bi-shield-lock"></i> Rôles & Permissions
            </a>
        </li>
        <?php endif; ?>

        <?php if ($userCanViewJournal): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('journal') ? 'active' : ''; ?>" href="<?php echo adminUrl('journal/index.php'); ?>">
                <i class="bi bi-clock-history"></i> Journal d'activité
            </a>
        </li>
        <?php endif; ?>

        <?php if ($userCanViewParametres): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo isMenuActive('parametres') ? 'active' : ''; ?>" href="<?php echo adminUrl('parametres/index.php'); ?>">
                <i class="bi bi-gear"></i> Paramètres
            </a>
        </li>
        <?php endif; ?>

        <hr class="nav-divider">

        <!-- SECTION 4 : SITE PUBLIC -->
        <li class="nav-section-title">Site Public</li>

        <li class="nav-item">
            <a class="nav-link" href="<?php echo appUrl('public/index.php'); ?>" target="_blank">
                <i class="bi bi-globe2"></i> Voir le site public
                <span class="badge badge-success">
                    <i class="bi bi-box-arrow-up-right"></i>
                </span>
            </a>
        </li>

        <!-- ⭐ LIEN SPLASH AVEC ÉVÉNEMENT PAR DÉFAUT -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo htmlspecialchars($splashUrl); ?>" target="_blank">
                <i class="bi bi-play-circle"></i> Écran Splash
                <span class="badge badge-gold">
                    <i class="bi bi-star-fill"></i> Festif
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?php echo appUrl('public/invitation_demo.php'); ?>" target="_blank">
                <i class="bi bi-envelope-paper"></i> Invitation Démo
                <span class="badge badge-demo">
                    <i class="bi bi-eye"></i> Démo
                </span>
            </a>
        </li>

        <hr class="nav-divider">

        <!-- SECTION 5 : COMPTE -->
        <li class="nav-section-title">Compte</li>

        <li class="nav-item">
            <a class="nav-link text-danger" href="<?php echo appUrl('logout.php'); ?>">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </li>
    </ul>
</div>

<style>
/* ============================================
   SIDEBAR - DESIGN MdlEvent
   ============================================ */

.sidebar-toggle {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 1050;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #c17c60, #d4a574);
    color: white;
    font-size: 22px;
    box-shadow: 0 4px 20px rgba(193, 124, 96, 0.35);
    cursor: pointer;
    transition: all 0.3s ease;
    align-items: center;
    justify-content: center;
}

.sidebar-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 28px rgba(193, 124, 96, 0.45);
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1030;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
    opacity: 1;
}

.sidebar {
    height: 100vh;
    background: linear-gradient(180deg, #ffffff 0%, #faf8f5 50%, #f5f0eb 100%);
    padding: 0;
    position: sticky;
    top: 0;
    box-shadow: 4px 0 25px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border-right: 1px solid rgba(193, 124, 96, 0.15);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    min-width: 260px;
    max-width: 260px;
    z-index: 1040;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-right: 12px;
    flex-shrink: 0;
}

.sidebar-brand {
    padding: 20px 20px 15px;
    text-align: center;
    border-bottom: 2px solid rgba(193, 124, 96, 0.15);
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #fdf8f5, #faf0ea);
    flex: 1;
}

.sidebar-brand::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #c17c60, #d4a574, #c17c60);
    background-size: 200% 100%;
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0%, 100% { background-position: 0% 50%; }
    50%      { background-position: 100% 50%; }
}

.brand-logo-img {
    width: 70px;
    height: 70px;
    margin: 0 auto 10px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    box-shadow: 0 8px 25px rgba(193, 124, 96, 0.2);
    transition: all 0.3s ease;
    overflow: hidden;
    border: 2px solid rgba(193, 124, 96, 0.1);
}

.brand-logo-img:hover {
    transform: scale(1.05) rotate(-3deg);
    box-shadow: 0 12px 32px rgba(193, 124, 96, 0.3);
}

.brand-logo-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
}

.brand-logo {
    width: 55px;
    height: 55px;
    margin: 0 auto 8px;
    background: linear-gradient(135deg, #c17c60, #d4a574);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 25px rgba(193, 124, 96, 0.25);
    transition: all 0.3s ease;
}

.brand-logo:hover {
    transform: scale(1.05) rotate(-5deg);
}

.brand-logo i {
    font-size: 26px;
    color: white;
}

.sidebar-brand h4 {
    color: #1a1a1a;
    font-weight: 700;
    margin: 4px 0 2px;
    font-family: 'Georgia', serif;
    font-size: 17px;
    letter-spacing: 0.5px;
}

.sidebar-brand small {
    color: #9a8a7f;
    font-size: 10px;
    display: block;
    font-weight: 500;
}

.sidebar-close {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    padding: 8px;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.sidebar-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(193, 124, 96, 0.3);
    border-radius: 10px;
}
.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(193, 124, 96, 0.5);
}

.sidebar .nav {
    padding: 10px 12px 20px;
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
}

.sidebar .nav-section-title {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #b8a99c;
    padding: 8px 14px 4px;
    font-weight: 700;
}

.sidebar .nav-item {
    display: block;
    width: 100%;
}

.sidebar .nav .collapse {
    padding-left: 10px;
}

.sidebar .nav .collapse .nav-link {
    font-size: 12px;
    padding: 5px 14px;
    border-left: 2px solid transparent;
    border-radius: 0 8px 8px 0;
    margin: 1px 0;
}

.sidebar .nav .collapse .nav-link:hover {
    border-left-color: #c17c60;
    background: rgba(193, 124, 96, 0.05);
}

.sidebar .nav .collapse .nav-link.active {
    border-left-color: #c17c60;
    background: rgba(193, 124, 96, 0.1);
    color: #c17c60;
}

.sidebar .nav .collapse .nav-link.active i {
    color: #c17c60;
}

.sidebar .nav .collapse .nav-link i {
    font-size: 14px;
    width: 20px;
    text-align: center;
}

.sidebar .nav-link {
    color: #5a4a3a;
    padding: 9px 14px;
    border-radius: 10px;
    margin: 1px 0;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    width: 100%;
    text-decoration: none;
}

.sidebar .nav-link i {
    font-size: 17px;
    width: 24px;
    text-align: center;
    transition: all 0.3s ease;
    color: #9a8a7f;
}

.sidebar .nav-link:hover {
    color: #1a1a1a;
    background: linear-gradient(135deg, rgba(193, 124, 96, 0.08), rgba(212, 165, 116, 0.05));
    transform: translateX(3px);
}

.sidebar .nav-link:hover i {
    color: #c17c60;
}

.sidebar .nav-link.active {
    color: #1a1a1a;
    background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(212, 165, 116, 0.08));
    border: 1px solid rgba(193, 124, 96, 0.2);
    box-shadow: 0 4px 15px rgba(193, 124, 96, 0.1);
    font-weight: 600;
}

.sidebar .nav-link.active i {
    color: #c17c60;
}

.sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 24px;
    background: linear-gradient(180deg, #c17c60, #d4a574);
    border-radius: 0 4px 4px 0;
}

.sidebar .nav-link .badge {
    margin-left: auto;
    background: linear-gradient(135deg, #c17c60, #d4a574);
    color: white;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    border: none;
    flex-shrink: 0;
}

.sidebar .nav-link .badge-new {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
}

.sidebar .nav-link .badge-success {
    background: #28a745 !important;
    color: white !important;
    font-size: 9px;
    padding: 2px 6px;
}

.sidebar .nav-link .badge-success i {
    color: white;
    font-size: 10px;
}

.sidebar .nav-link .badge-gold {
    background: linear-gradient(135deg, #c17c60, #d4a574) !important;
    color: white !important;
    font-size: 9px;
    padding: 2px 6px;
}

.sidebar .nav-link .badge-demo {
    background: rgba(193, 124, 96, 0.15) !important;
    color: #c17c60 !important;
    font-size: 9px;
    padding: 2px 6px;
    border: 1px solid rgba(193, 124, 96, 0.2);
}

.sidebar .nav-link.text-danger {
    color: #ff6b6b;
}

.sidebar .nav-link.text-danger i {
    color: #ff6b6b;
}

.sidebar .nav-link.text-danger:hover {
    background: rgba(255, 107, 107, 0.1);
    color: #e74c3c;
}

.sidebar .nav-link.text-danger:hover i {
    color: #e74c3c;
}

.sidebar .nav-divider {
    border: none;
    border-top: 2px dashed rgba(193, 124, 96, 0.12);
    margin: 10px 14px;
}

.sidebar .nav-link[data-bs-toggle="collapse"] {
    cursor: pointer;
}

.sidebar .nav-link[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.3s ease;
}

.sidebar .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

.sidebar .nav-link[data-bs-toggle="collapse"]:hover .bi-chevron-down {
    color: #c17c60;
}

@media (max-width: 992px) {
    .sidebar-toggle {
        display: flex !important;
    }

    .sidebar-close {
        display: block !important;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: -300px;
        height: 100vh;
        width: 280px;
        max-width: 85vw;
        border-right: none;
        border-radius: 0 20px 20px 0;
        box-shadow: 8px 0 40px rgba(0, 0, 0, 0.15);
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1040;
        min-width: auto;
        max-width: 280px;
        overflow-y: auto;
    }

    .sidebar.open {
        left: 0;
    }

    .sidebar-overlay.active {
        display: block !important;
    }

    .sidebar-header {
        padding-right: 8px;
    }

    .sidebar-brand {
        padding: 16px 16px 12px;
        text-align: center;
    }

    .brand-logo-img {
        width: 55px;
        height: 55px;
        margin: 0 auto 6px;
    }

    .brand-logo {
        width: 44px;
        height: 44px;
        margin: 0 auto 6px;
    }

    .brand-logo i {
        font-size: 20px;
    }

    .sidebar-brand h4 {
        font-size: 15px;
        margin: 2px 0 1px;
    }

    .sidebar-brand small {
        font-size: 9px;
    }

    .sidebar .nav {
        padding: 8px 10px 16px;
    }

    .sidebar .nav-section-title {
        padding: 6px 14px 2px;
        font-size: 8px;
    }

    .sidebar .nav-link {
        padding: 8px 12px;
        font-size: 12px;
        gap: 8px;
    }

    .sidebar .nav-link i {
        font-size: 15px;
        width: 20px;
    }

    .sidebar .nav-link .badge {
        font-size: 9px;
        padding: 1px 6px;
    }

    .sidebar .nav .collapse .nav-link {
        font-size: 11px;
        padding: 4px 10px;
    }

    .sidebar .nav-divider {
        margin: 8px 12px;
    }
}

@media (max-width: 576px) {
    .sidebar-toggle {
        top: 10px;
        left: 10px;
        width: 38px;
        height: 38px;
        font-size: 18px;
        border-radius: 10px;
    }

    .sidebar {
        width: 260px;
        max-width: 90vw;
        border-radius: 0 16px 16px 0;
    }

    .sidebar-brand {
        padding: 12px 12px 10px;
    }

    .brand-logo-img {
        width: 45px;
        height: 45px;
    }

    .brand-logo {
        width: 36px;
        height: 36px;
    }

    .brand-logo i {
        font-size: 17px;
    }

    .sidebar-brand h4 {
        font-size: 13px;
    }

    .sidebar-brand small {
        font-size: 8px;
    }

    .sidebar .nav-link {
        padding: 6px 10px;
        font-size: 11px;
        gap: 6px;
    }

    .sidebar .nav-link i {
        font-size: 13px;
        width: 18px;
    }

    .sidebar .nav-link .badge {
        font-size: 8px;
        padding: 1px 4px;
    }

    .sidebar .nav .collapse .nav-link {
        font-size: 10px;
        padding: 3px 8px;
    }

    .sidebar .nav-section-title {
        font-size: 7px;
        padding: 4px 12px 2px;
    }

    .sidebar .nav-divider {
        margin: 6px 10px;
    }
}

@media (max-width: 400px) {
    .sidebar {
        width: 100%;
        max-width: 100vw;
        border-radius: 0;
        left: -100%;
    }

    .sidebar.open {
        left: 0;
    }

    .sidebar-toggle {
        top: 8px;
        left: 8px;
        width: 34px;
        height: 34px;
        font-size: 16px;
    }
}

@media (min-width: 993px) {
    .sidebar-overlay {
        display: none !important;
    }

    .sidebar-toggle {
        display: none !important;
    }

    .sidebar-close {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');
    const toggleBtn  = document.getElementById('sidebarToggle');
    const closeBtn   = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    const toggleSubmenu = document.querySelector('[data-bs-toggle="collapse"][href="#notificationsSubmenu"]');
    if (toggleSubmenu) {
        toggleSubmenu.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                target.classList.toggle('show');
            }
        });
    }

    const navLinks = document.querySelectorAll('.sidebar .nav-link:not([data-bs-toggle="collapse"])');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });
});
</script>