<?php
/**
 * Système d'authentification
 * Gestion des sessions, login, logout et vérification des permissions
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration (définit notamment BASE_PATH)
require_once __DIR__ . '/../config/database.php';

// ==========================================
// 1. CONSTANTES DE SESSION ET CHEMIN
// ==========================================

define('SESSION_KEY_USER_ID', 'user_id');
define('SESSION_KEY_USERNAME', 'username');
define('SESSION_KEY_USER_NOM', 'user_nom');
define('SESSION_KEY_USER_PRENOM', 'user_prenom');
define('SESSION_KEY_USER_EMAIL', 'user_email');
define('SESSION_KEY_ROLES', 'user_roles');
define('SESSION_KEY_PERMISSIONS', 'user_permissions');
define('SESSION_KEY_LOGIN_TIME', 'login_time');
define('SESSION_KEY_LAST_ACTIVITY', 'last_activity');

// ==========================================
// 2. FONCTIONS DE BASE
// ==========================================

/**
 * Vérifie si un utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION[SESSION_KEY_USER_ID]) && !empty($_SESSION[SESSION_KEY_USER_ID]);
}

/**
 * Vérifie si la session est expirée (inactivité)
 */
function isSessionExpired() {
    if (!isset($_SESSION[SESSION_KEY_LAST_ACTIVITY])) {
        return true;
    }
    $inactivity = time() - $_SESSION[SESSION_KEY_LAST_ACTIVITY];
    return $inactivity > SESSION_TIMEOUT;
}

/**
 * Met à jour le timestamp de dernière activité
 */
function updateLastActivity() {
    $_SESSION[SESSION_KEY_LAST_ACTIVITY] = time();
}

/**
 * Récupère l'utilisateur actuellement connecté
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION[SESSION_KEY_USER_ID],
        'username' => $_SESSION[SESSION_KEY_USERNAME],
        'nom' => $_SESSION[SESSION_KEY_USER_NOM] ?? '',
        'prenom' => $_SESSION[SESSION_KEY_USER_PRENOM] ?? '',
        'email' => $_SESSION[SESSION_KEY_USER_EMAIL] ?? '',
        'roles' => $_SESSION[SESSION_KEY_ROLES] ?? [],
        'permissions' => $_SESSION[SESSION_KEY_PERMISSIONS] ?? []
    ];
}

/**
 * Récupère l'ID de l'utilisateur connecté
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION[SESSION_KEY_USER_ID] : null;
}

// ==========================================
// 3. FONCTIONS D'URL ET REDIRECTION
// ==========================================

/**
 * Génère une URL absolue depuis la racine du site
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

/**
 * Redirige vers une URL absolue
 */
function redirect(string $path, array $params = []): void
{
    $url = url($path);
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Redirige vers la page de login
 */
function redirectToLogin($redirect = null, $message = null): void
{
    if ($message) {
        $_SESSION['login_message'] = $message;
    }
    
    $params = [];
    if ($redirect === null && isset($_SERVER['REQUEST_URI'])) {
        $redirect = $_SERVER['REQUEST_URI'];
    }
    
    if ($redirect) {
        $params['redirect'] = $redirect;
    }
    
    redirect('login.php', $params);
}

// ==========================================
// 4. FONCTIONS DE LOGIN
// ==========================================

/**
 * Authentifie un utilisateur
 */
function loginUser($username, $password) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, nom, prenom, username, email, mot_de_passe, actif 
            FROM utilisateurs 
            WHERE (username = ? OR email = ?) AND actif = 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logFailedAttempt($username, 'Utilisateur inexistant');
            return ['success' => false, 'message' => 'Identifiants incorrects'];
        }
        
        if (!password_verify($password, $user['mot_de_passe'])) {
            logFailedAttempt($username, 'Mot de passe incorrect');
            return ['success' => false, 'message' => 'Identifiants incorrects'];
        }
        
        if ($user['actif'] != 1) {
            logFailedAttempt($username, 'Compte désactivé');
            return ['success' => false, 'message' => 'Votre compte est désactivé'];
        }
        
        session_regenerate_id(true);
        
        $_SESSION[SESSION_KEY_USER_ID] = $user['id'];
        $_SESSION[SESSION_KEY_USERNAME] = $user['username'];
        $_SESSION[SESSION_KEY_USER_NOM] = $user['nom'];
        $_SESSION[SESSION_KEY_USER_PRENOM] = $user['prenom'];
        $_SESSION[SESSION_KEY_USER_EMAIL] = $user['email'];
        $_SESSION[SESSION_KEY_LOGIN_TIME] = time();
        $_SESSION[SESSION_KEY_LAST_ACTIVITY] = time();
        
        loadUserPermissions($user['id']);
        
        $updateStmt = $pdo->prepare("UPDATE utilisateurs SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        logAction($user['id'], 'LOGIN', 'authentification', 'Connexion réussie');
        
        return ['success' => true, 'message' => 'Connexion réussie'];
        
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur est survenue'];
    }
}

/**
 * Charge les rôles et permissions de l'utilisateur en session
 */
function loadUserPermissions($userId) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            SELECT r.nom 
            FROM roles r
            JOIN utilisateur_roles ur ON r.id = ur.role_id
            WHERE ur.utilisateur_id = ? AND r.actif = 1
        ");
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.nom 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN utilisateur_roles ur ON rp.role_id = ur.role_id
            WHERE ur.utilisateur_id = ?
        ");
        $stmt->execute([$userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $_SESSION[SESSION_KEY_ROLES] = $roles;
        $_SESSION[SESSION_KEY_PERMISSIONS] = $permissions;
        
    } catch (PDOException $e) {
        error_log('Load permissions error: ' . $e->getMessage());
        $_SESSION[SESSION_KEY_ROLES] = [];
        $_SESSION[SESSION_KEY_PERMISSIONS] = [];
    }
}

// ==========================================
// 5. FONCTIONS DE LOGOUT
// ==========================================

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAction(getCurrentUserId(), 'LOGOUT', 'authentification', 'Déconnexion');
    }
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

// ==========================================
// 6. FONCTIONS DE VÉRIFICATION DES PERMISSIONS
// ==========================================

/**
 * Vérifie si l'utilisateur connecté possède une permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    
    if (isSessionExpired()) {
        logoutUser();
        return false;
    }
    
    updateLastActivity();
    
    if (in_array('SUPER_ADMIN', $_SESSION[SESSION_KEY_ROLES] ?? [])) {
        return true;
    }
    
    return in_array($permission, $_SESSION[SESSION_KEY_PERMISSIONS] ?? []);
}

/**
 * Vérifie si l'utilisateur possède un rôle
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    if (isSessionExpired()) {
        logoutUser();
        return false;
    }
    
    updateLastActivity();
    
    return in_array($role, $_SESSION[SESSION_KEY_ROLES] ?? []);
}

/**
 * Vérifie si l'utilisateur a un rôle admin (SUPER_ADMIN ou ADMIN)
 */
function isAdmin() {
    return hasRole('SUPER_ADMIN') || hasRole('ADMIN');
}

/**
 * Vérifie une permission et redirige si non autorisé
 */
function requirePermission($permission, $redirect = null) {
    if ($redirect === null) $redirect = '403.php';
    
    if (!isLoggedIn()) {
        redirectToLogin(null, 'Veuillez vous connecter pour accéder à cette page.');
    }
    
    if (isSessionExpired()) {
        logoutUser();
        redirectToLogin(null, 'Votre session a expiré. Veuillez vous reconnecter.');
    }
    
    if (!hasPermission($permission)) {
        logAction(
            getCurrentUserId(),
            'ACCESS_DENIED',
            'security',
            'Tentative d\'accès à ' . $permission . ' sur ' . $_SERVER['REQUEST_URI']
        );
        redirect($redirect);
    }
    
    updateLastActivity();
}

/**
 * Vérifie si l'utilisateur est authentifié
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        redirectToLogin(null, 'Veuillez vous connecter pour accéder à cette page.');
    }
    
    if (isSessionExpired()) {
        logoutUser();
        redirectToLogin(null, 'Votre session a expiré. Veuillez vous reconnecter.');
    }
    
    updateLastActivity();
}

// ==========================================
// 7. FONCTIONS DE GESTION DES ÉVÉNEMENTS PAR UTILISATEUR
// ==========================================

/**
 * Vérifie si l'utilisateur a accès à un événement
 */
function userCanAccessEvenement(PDO $pdo, int $userId, int $evenementId): bool {
    if (isAdmin()) return true;
    
    $stmt = $pdo->prepare('
        SELECT 1 FROM evenements_utilisateurs 
        WHERE id_evenement = ? AND id_utilisateur = ?
        LIMIT 1
    ');
    $stmt->execute([$evenementId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Récupère la liste des événements accessibles à l'utilisateur
 */
function getEvenementsAccessibles(PDO $pdo): array {
    $userId = (int)getCurrentUserId();
    
    if (isAdmin()) {
        $stmt = $pdo->query('SELECT * FROM evenements ORDER BY date_evenement DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $stmt = $pdo->prepare('
        SELECT DISTINCT e.* 
        FROM evenements e
        INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
        WHERE eu.id_utilisateur = ?
        ORDER BY e.date_evenement DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les utilisateurs associés à un événement
 */
function getUtilisateursEvenement(PDO $pdo, int $evenementId): array {
    $stmt = $pdo->prepare('
        SELECT u.id, u.nom, u.prenom, u.username, u.email, 
               eu.role_specifique, eu.created_at AS assigned_at
        FROM utilisateurs u
        INNER JOIN evenements_utilisateurs eu ON eu.id_utilisateur = u.id
        WHERE eu.id_evenement = ?
        ORDER BY u.nom, u.prenom
    ');
    $stmt->execute([$evenementId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Associe un utilisateur à un événement
 */
function associerUtilisateurEvenement(PDO $pdo, int $evenementId, int $userId, ?string $roleSpecifique = null): bool {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO evenements_utilisateurs (id_evenement, id_utilisateur, role_specifique) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE role_specifique = VALUES(role_specifique)
        ');
        $stmt->execute([$evenementId, $userId, $roleSpecifique]);
        return true;
    } catch (Throwable $e) {
        error_log('Erreur association utilisateur-événement: ' . $e->getMessage());
        return false;
    }
}

/**
 * Retire un utilisateur d'un événement
 */
function retirerUtilisateurEvenement(PDO $pdo, int $evenementId, int $userId): bool {
    $stmt = $pdo->prepare('DELETE FROM evenements_utilisateurs WHERE id_evenement = ? AND id_utilisateur = ?');
    return $stmt->execute([$evenementId, $userId]);
}

/**
 * Récupère les IDs des utilisateurs associés à un événement
 */
function getIdsUtilisateursEvenement(PDO $pdo, int $evenementId): array {
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM evenements_utilisateurs WHERE id_evenement = ?');
    $stmt->execute([$evenementId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Synchronise les utilisateurs associés à un événement
 */
function syncUtilisateursEvenement(PDO $pdo, int $evenementId, array $userIds): int {
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM evenements_utilisateurs WHERE id_evenement = ?')->execute([$evenementId]);
        
        $count = 0;
        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId > 0 && associerUtilisateurEvenement($pdo, $evenementId, $userId)) {
                $count++;
            }
        }
        
        $pdo->commit();
        return $count;
        
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erreur sync utilisateurs événement: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Filtre les événements pour un utilisateur (clause WHERE)
 */
function getEvenementAccessFilter(string $alias = 'e'): array {
    if (isAdmin()) {
        return ['where' => '1=1', 'params' => []];
    }
    
    $userId = (int)getCurrentUserId();
    $where = "{$alias}.id IN (
        SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
    )";
    
    return ['where' => $where, 'params' => [$userId]];
}

/**
 * Récupère les rôles spécifiques des utilisateurs d'un événement
 */
function getRolesSpecifiquesEvenement(PDO $pdo, int $evenementId): array {
    $stmt = $pdo->prepare('
        SELECT id_utilisateur, role_specifique 
        FROM evenements_utilisateurs 
        WHERE id_evenement = ?
    ');
    $stmt->execute([$evenementId]);
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[(int)$row['id_utilisateur']] = $row['role_specifique'];
    }
    return $result;
}

/**
 * Récupère la liste des rôles spécifiques disponibles
 */
function getRolesSpecifiquesDisponibles(): array {
    return [
        'ORGANISATEUR' => '🎯 Organisateur',
        'MAITRE_CEREMONIE' => '🎤 Maître de cérémonie',
        'CONTROLEUR_ENTREE' => '🚪 Contrôleur d\'entrée',
        'CHEF_SECURITE' => '🛡️ Chef de sécurité',
        'AGENT_SECURITE' => '👮 Agent de sécurité',
        'PHOTOGRAPHE' => '📷 Photographe',
        'VIDEASTE' => '🎥 Vidéaste',
        'DJ' => '🎧 DJ',
        'SERVEUR' => '🍽️ Serveur',
        'PLACEMENT' => '🪑 Placement',
        'ACCUEIL' => '🤝 Accueil',
        'TRESORIER' => '💰 Trésorier',
        'INVITE' => '👤 Invité',
        'VIP' => '⭐ VIP'
    ];
}

// ==========================================
// 8. FONCTIONS DE GESTION DES INVITÉS PAR ÉVÉNEMENT
// ==========================================

/**
 * Récupère la liste des invités accessibles à l'utilisateur
 */
function getInvitesAccessibles(PDO $pdo, array $filtres = []): array {
    $userId = (int)getCurrentUserId();
    $whereConditions = [];
    $params = [];
    
    // ⭐ FILTRAGE PAR UTILISATEUR
    if (!isAdmin()) {
        $whereConditions[] = "i.id_evenement IN (
            SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
        )";
        $params[] = $userId;
    }
    
    // Filtres additionnels
    if (!empty($filtres['search'])) {
        $whereConditions[] = "(i.nom LIKE ? OR i.prenom LIKE ? OR i.telephone LIKE ? OR i.email LIKE ?)";
        $search = '%' . $filtres['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    if (!empty($filtres['id_categorie'])) {
        $whereConditions[] = "i.id_categorie = ?";
        $params[] = (int)$filtres['id_categorie'];
    }
    
    if (!empty($filtres['id_evenement'])) {
        $whereConditions[] = "i.id_evenement = ?";
        $params[] = (int)$filtres['id_evenement'];
    }
    
    if (isset($filtres['actif'])) {
        $whereConditions[] = "i.actif = ?";
        $params[] = (int)$filtres['actif'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "
        SELECT i.*, 
            c.nom AS categorie_nom,
            e.nom AS evenement_nom,
            e.date_evenement AS evenement_date,
            (SELECT COUNT(*) FROM invitations inv WHERE inv.id_invite = i.id) AS a_invitation
        FROM invites i
        LEFT JOIN categories_invites c ON i.id_categorie = c.id
        LEFT JOIN evenements e ON e.id = i.id_evenement
        $whereClause
        ORDER BY i.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les événements pour un select (filtré par utilisateur)
 */
function getEvenementsPourSelect(PDO $pdo): array {
    $userId = (int)getCurrentUserId();
    
    if (isAdmin()) {
        $stmt = $pdo->query('
            SELECT id, nom, date_evenement 
            FROM evenements 
            WHERE statut != "ANNULE" 
            ORDER BY date_evenement DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $stmt = $pdo->prepare('
        SELECT DISTINCT e.id, e.nom, e.date_evenement
        FROM evenements e
        INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = e.id
        WHERE eu.id_utilisateur = ? AND e.statut != "ANNULE"
        ORDER BY e.date_evenement DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Vérifie si l'utilisateur a accès à un invité
 */
function userCanAccessInvite(PDO $pdo, int $userId, int $inviteId): bool {
    if (isAdmin()) return true;
    
    $stmt = $pdo->prepare('
        SELECT 1 FROM invites i
        INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = i.id_evenement
        WHERE i.id = ? AND eu.id_utilisateur = ?
        LIMIT 1
    ');
    $stmt->execute([$inviteId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Vérifie si l'utilisateur peut créer un invité dans cet événement
 */
function userCanAddInviteToEvenement(PDO $pdo, int $userId, int $evenementId): bool {
    if (isAdmin()) return true;
    
    $stmt = $pdo->prepare('
        SELECT 1 FROM evenements_utilisateurs 
        WHERE id_evenement = ? AND id_utilisateur = ?
        LIMIT 1
    ');
    $stmt->execute([$evenementId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Compte les invités accessibles à l'utilisateur
 */
function countInvitesAccessibles(PDO $pdo): int {
    $userId = (int)getCurrentUserId();
    
    if (isAdmin()) {
        return (int)$pdo->query('SELECT COUNT(*) FROM invites WHERE actif = 1')->fetchColumn();
    }
    
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM invites i
        WHERE i.actif = 1 AND i.id_evenement IN (
            SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
        )
    ');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ==========================================
// 9. FONCTIONS DE GESTION DES INVITATIONS
// ==========================================

/**
 * Récupère les invitations accessibles à l'utilisateur
 */
function getInvitationsAccessibles(PDO $pdo, array $filtres = []): array {
    $userId = (int)getCurrentUserId();
    $whereConditions = [];
    $params = [];
    
    // ⭐ FILTRAGE PAR UTILISATEUR
    if (!isAdmin()) {
        $whereConditions[] = "inv.id_evenement IN (
            SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
        )";
        $params[] = $userId;
    }
    
    if (!empty($filtres['id_evenement'])) {
        $whereConditions[] = "inv.id_evenement = ?";
        $params[] = (int)$filtres['id_evenement'];
    }
    
    if (!empty($filtres['statut'])) {
        $whereConditions[] = "inv.statut = ?";
        $params[] = $filtres['statut'];
    }
    
    if (!empty($filtres['search'])) {
        $whereConditions[] = "(i.nom LIKE ? OR i.prenom LIKE ? OR inv.code_unique LIKE ?)";
        $s = '%' . $filtres['search'] . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "
        SELECT inv.*,
            i.nom AS invite_nom, 
            i.prenom AS invite_prenom, 
            i.telephone AS invite_telephone,
            i.email AS invite_email,
            i.nombre_personnes,
            e.nom AS evenement_nom,
            e.date_evenement AS evenement_date,
            (SELECT COUNT(*) FROM preferences_invitation pi WHERE pi.id_invitation = inv.id) AS nb_boissons,
            (SELECT COUNT(*) FROM invitations_tables it WHERE it.id_invitation = inv.id) AS a_table
        FROM invitations inv
        INNER JOIN invites i ON i.id = inv.id_invite
        INNER JOIN evenements e ON e.id = inv.id_evenement
        $whereClause
        ORDER BY inv.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Vérifie si l'utilisateur a accès à une invitation
 */
function userCanAccessInvitation(PDO $pdo, int $userId, int $invitationId): bool {
    if (isAdmin()) return true;
    
    $stmt = $pdo->prepare('
        SELECT 1 FROM invitations inv
        INNER JOIN evenements_utilisateurs eu ON eu.id_evenement = inv.id_evenement
        WHERE inv.id = ? AND eu.id_utilisateur = ?
        LIMIT 1
    ');
    $stmt->execute([$invitationId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Compte les invitations accessibles à l'utilisateur
 */
function countInvitationsAccessibles(PDO $pdo): int {
    $userId = (int)getCurrentUserId();
    
    if (isAdmin()) {
        return (int)$pdo->query('SELECT COUNT(*) FROM invitations')->fetchColumn();
    }
    
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM invitations inv
        WHERE inv.id_evenement IN (
            SELECT id_evenement FROM evenements_utilisateurs WHERE id_utilisateur = ?
        )
    ');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Récupère une invitation par son code unique
 */
function getInvitationByCode(PDO $pdo, string $codeUnique): ?array {
    $stmt = $pdo->prepare('
        SELECT inv.*, 
            i.nom AS invite_nom, i.prenom AS invite_prenom,
            i.telephone, i.email, i.nombre_personnes,
            e.nom AS evenement_nom, e.date_evenement, e.heure_evenement, e.lieu
        FROM invitations inv
        INNER JOIN invites i ON i.id = inv.id_invite
        INNER JOIN evenements e ON e.id = inv.id_evenement
        WHERE inv.code_unique = ?
        LIMIT 1
    ');
    $stmt->execute([$codeUnique]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

// ==========================================
// 10. FONCTIONS DE GESTION DES BOISSONS
// ==========================================

/**
 * Récupère les boissons disponibles d'un événement
 */
function getBoissonsEvenement(PDO $pdo, int $evenementId): array {
    $stmt = $pdo->prepare('
        SELECT eb.*, 
            b.nom AS boisson_nom, 
            b.description AS boisson_description, 
            b.image AS boisson_image
        FROM evenement_boissons eb
        INNER JOIN boissons b ON b.id = eb.id_boisson
        WHERE eb.id_evenement = ? AND eb.actif = 1
        ORDER BY b.nom ASC
    ');
    $stmt->execute([$evenementId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les préférences de boisson d'une invitation
 */
function getPreferencesInvitation(PDO $pdo, int $invitationId): array {
    $stmt = $pdo->prepare('
        SELECT pi.*, 
            b.nom AS boisson_nom,
            b.description AS boisson_description,
            b.image AS boisson_image
        FROM preferences_invitation pi
        INNER JOIN boissons b ON b.id = pi.id_boisson
        WHERE pi.id_invitation = ?
        ORDER BY b.nom ASC
    ');
    $stmt->execute([$invitationId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Vérifie si une boisson est disponible dans un événement
 */
function isBoissonDisponibleEvenement(PDO $pdo, int $evenementId, int $boissonId): bool {
    $stmt = $pdo->prepare('
        SELECT 1 FROM evenement_boissons 
        WHERE id_evenement = ? AND id_boisson = ? AND actif = 1
        LIMIT 1
    ');
    $stmt->execute([$evenementId, $boissonId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Ajoute une préférence de boisson à une invitation
 */
function ajouterPreferenceBoisson(PDO $pdo, int $invitationId, int $boissonId, int $quantite = 1): bool {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO preferences_invitation (id_invitation, id_boisson, quantite) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)
        ');
        $stmt->execute([$invitationId, $boissonId, $quantite]);
        return true;
    } catch (Throwable $e) {
        error_log('Erreur ajout préférence boisson: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une préférence de boisson
 */
function supprimerPreferenceBoisson(PDO $pdo, int $invitationId, int $boissonId): bool {
    $stmt = $pdo->prepare('DELETE FROM preferences_invitation WHERE id_invitation = ? AND id_boisson = ?');
    return $stmt->execute([$invitationId, $boissonId]);
}

// ==========================================
// 11. FONCTIONS DE GESTION DES TABLES
// ==========================================

/**
 * Récupère la table assignée à une invitation
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int $invitationId ID de l'invitation
 * @return array|null
 */
function getTableInvitation(PDO $pdo, int $invitationId): ?array {
    try {
        $stmt = $pdo->prepare('
            SELECT it.id, it.id_invitation, it.id_table, 
                   it.assignee_par, it.date_assignation, it.notes,
                   t.id AS table_id, 
                   t.nom AS table_nom, 
                   t.numero AS table_numero,
                   t.capacite_min, 
                   t.capacite_max,
                   t.type AS table_type, 
                   t.zone AS table_zone,
                   t.forme AS table_forme,
                   u.nom AS assigne_par_nom, 
                   u.prenom AS assigne_par_prenom
            FROM invitations_tables it
            INNER JOIN tables t ON t.id = it.id_table
            LEFT JOIN utilisateurs u ON u.id = it.assignee_par
            WHERE it.id_invitation = ?
            LIMIT 1
        ');
        $stmt->execute([$invitationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('Erreur getTableInvitation: ' . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les tables d'un événement
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int $evenementId ID de l'événement
 * @return array Liste des tables
 */
function getTablesEvenement(PDO $pdo, int $evenementId): array {
    $stmt = $pdo->prepare('
        SELECT t.*, 
            (SELECT COUNT(*) FROM invitations_tables it 
             INNER JOIN invitations inv ON inv.id = it.id_invitation 
             WHERE it.id_table = t.id) AS nb_occupants
        FROM tables t
        WHERE t.id_evenement = ? 
        ORDER BY t.numero ASC, t.nom ASC
    ');
    $stmt->execute([$evenementId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Assigne une table à une invitation
 */
function assignerTableInvitation(PDO $pdo, int $invitationId, int $tableId, ?int $assigneePar = null, ?string $notes = null): bool {
    try {
        // Supprimer l'ancienne assignation (1 invitation = 1 table)
        $pdo->prepare('DELETE FROM invitations_tables WHERE id_invitation = ?')->execute([$invitationId]);
        
        $stmt = $pdo->prepare('
            INSERT INTO invitations_tables (id_invitation, id_table, assignee_par, date_assignation, notes) 
            VALUES (?, ?, ?, NOW(), ?)
        ');
        $stmt->execute([$invitationId, $tableId, $assigneePar ?? getCurrentUserId(), $notes]);
        return true;
    } catch (Throwable $e) {
        error_log('Erreur assignation table: ' . $e->getMessage());
        return false;
    }
}

/**
 * Retire la table assignée à une invitation
 */
function retirerTableInvitation(PDO $pdo, int $invitationId): bool {
    $stmt = $pdo->prepare('DELETE FROM invitations_tables WHERE id_invitation = ?');
    return $stmt->execute([$invitationId]);
}

/**
 * Vérifie si une table est disponible (capacité non dépassée)
 * Utilise capacite_max
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int $tableId ID de la table
 * @return bool
 */
function isTableDisponible(PDO $pdo, int $tableId): bool {
    $stmt = $pdo->prepare('
        SELECT t.capacite_max, 
            (SELECT COUNT(*) FROM invitations_tables it 
             INNER JOIN invitations inv ON inv.id = it.id_invitation 
             WHERE it.id_table = t.id) AS occupants
        FROM tables t
        WHERE t.id = ?
    ');
    $stmt->execute([$tableId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) return false;
    return (int)$result['occupants'] < (int)$result['capacite_max'];
}

// ==========================================
// 12. FONCTIONS DE LOG
// ==========================================

/**
 * Journalise une action dans la base de données
 */
function logAction($userId, $action, $module, $description) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO journal_activites 
            (utilisateur_id, action, module, description, adresse_ip, user_agent, date_action)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->execute([$userId, $action, $module, $description, $ip, $userAgent]);
        
    } catch (PDOException $e) {
        error_log('Log error: ' . $e->getMessage());
    }
}

/**
 * Journalise une tentative de connexion échouée
 */
function logFailedAttempt($username, $reason) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO journal_activites 
            (action, module, description, adresse_ip, user_agent, date_action)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $description = "Tentative de connexion échouée - Utilisateur: $username - Raison: $reason";
        
        $stmt->execute(['LOGIN_FAILED', 'authentification', $description, $ip, $userAgent]);
        
    } catch (PDOException $e) {
        error_log('Log error: ' . $e->getMessage());
    }
}

// ==========================================
// 13. FONCTIONS DE MOT DE PASSE
// ==========================================

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// ==========================================
// 14. PROTECTION CSRF
// ==========================================

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>