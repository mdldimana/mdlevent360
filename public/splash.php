<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ========== FIX COOLIFY / INFINITYFREE - DOIT ETRE AVANT TOUT ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot  = realpath(__DIR__ . '/../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');

// FIX CRITIQUE : Coolify envoie "mdlevent360.com,www.mdlevent360.com" dans HTTP_HOST
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Prend X-Forwarded-Host si présent (Coolify/Proxy)
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = explode(',', $host)[0]; // Garde seulement le premier domaine
    $host = trim($host);
    $host = explode(':', $host)[0]; // Enlève le port si présent
    // Si host contient déjà http, on le nettoie
    $host = str_replace(['https://', 'http://'], '', $host);
    define('APP_URL', $protocol . '://' . $host . $projectFolder);
    // Pour les images, on utilise un chemin relatif qui marche partout
    define('BASE_URL_REL', $projectFolder ?: '');
}

require_once __DIR__ . '/../config/database.php';

$evenementId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$pdo = getDbConnection();

function getHostPhotos(PDO $pdo, int $eventId): array
{
    try {
        if ($eventId <= 0) {
            $stmt = $pdo->query("SELECT photo FROM photo_host WHERE actif = 1 ORDER BY ordre ASC, id ASC");
        } else {
            $stmt = $pdo->prepare("SELECT photo FROM photo_host WHERE id_evenement = ? AND actif = 1 ORDER BY ordre ASC, id ASC");
            $stmt->execute([$eventId]);
        }
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'photo');
    } catch (PDOException $e) {
        error_log('Erreur getHostPhotos : ' . $e->getMessage());
        return [];
    }
}

$hostPhotos = getHostPhotos($pdo, $evenementId);
$hostPhotoUrls = [];
foreach ($hostPhotos as $photo) {
    if ($photo) {
        // FIX: Utilise chemin relatif, pas APP_URL complet
        $hostPhotoUrls[] = (BASE_URL_REL) . '/uploads/photos_host/' . rawurlencode(basename($photo));
    }
}
if (empty($hostPhotoUrls)) {
    $hostPhotoUrls = ['default'];
}

// ========== ENDPOINT AJAX ==========
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    $evenementId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;

    try {
        $sql = "
            SELECT
                i.id AS invitation_id, i.id_evenement, i.statut, i.nb_presents,
                inv.id AS invite_id, inv.nom, inv.prenom, inv.photo,
                inv.nombre_personnes AS nb_places_max,
                e.nom AS evenement_nom
            FROM invitations i
            INNER JOIN invites inv ON i.id_invite = inv.id
            INNER JOIN evenements e ON i.id_evenement = e.id
            WHERE i.statut IN ('PRESENTE', 'PARTIELLE')
        ";
        $params = [];
        if ($evenementId > 0) {
            $sql .= " AND i.id_evenement = ?";
            $params[] = $evenementId;
        }
        $sql .= " ORDER BY i.id DESC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updatedHostPhotos = getHostPhotos($pdo, $evenementId);
        $updatedHostPhotoUrls = [];
        foreach ($updatedHostPhotos as $photo) {
            if ($photo) {
                $updatedHostPhotoUrls[] = (BASE_URL_REL) . '/uploads/photos_host/' . rawurlencode(basename($photo));
            }
        }
        if (empty($updatedHostPhotoUrls)) {
            $updatedHostPhotoUrls = ['default'];
        }

        echo json_encode([
            'success'     => true,
            'count'       => count($invites),
            'invites'     => $invites,
            'host_photos' => $updatedHostPhotoUrls,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } catch (PDOException $e) {
        error_log('Erreur AJAX splash : ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du chargement des données.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ========== CHARGEMENT INITIAL ==========
$invitesPresents = [];
$evenementInfo = null;

try {
    if ($evenementId > 0) {
        $stmt = $pdo->prepare("SELECT nom, date_evenement, lieu FROM evenements WHERE id = ?");
        $stmt->execute([$evenementId]);
        $evenementInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $sql = "
        SELECT 
            i.id AS invitation_id, i.id_evenement, i.statut, i.nb_presents,
            inv.id AS invite_id, inv.nom, inv.prenom, inv.photo,
            inv.nombre_personnes AS nb_places_max,
            e.nom AS evenement_nom
        FROM invitations i
        INNER JOIN invites inv ON i.id_invite = inv.id
        INNER JOIN evenements e ON i.id_evenement = e.id
        WHERE i.statut IN ('PRESENTE', 'PARTIELLE')
    ";
    $params = [];
    if ($evenementId > 0) {
        $sql .= " AND i.id_evenement = ?";
        $params[] = $evenementId;
    }
    $sql .= " ORDER BY i.id DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invitesPresents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    error_log('Erreur chargement initial splash : ' . $e->getMessage());
    $invitesPresents = [];
}

$eventDisplayName = $evenementInfo['nom'] ?? ($invitesPresents[0]['evenement_nom'] ?? 'Événement');
$photosBaseUrl = BASE_URL_REL . '/uploads/photos/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenue - <?php echo htmlspecialchars(APP_NAME); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; min-height: 100vh; height: 100vh; display: flex; align-items: center; justify-content: center; background: #0f0e17; overflow: hidden; }
    .hero-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; background: url('fond.jpg') center center / cover no-repeat; filter: brightness(0.5) saturate(1.2); }
    .hero-bg::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(145deg, rgba(15, 14, 23, 0.85) 0%, rgba(247, 151, 30, 0.06) 45%, rgba(15, 14, 23, 0.80) 100%); z-index: 1; }
    .waves-container { position: fixed; bottom: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; overflow: hidden; }
    .wave { position: absolute; bottom: -20%; left: -50%; width: 200%; height: 200%; border-radius: 45%; opacity: 0.08; animation: waveMove 15s infinite linear; }
    .wave:nth-child(1) { background: radial-gradient(ellipse at center, rgba(247, 151, 30, 0.3) 0%, transparent 70%); animation-duration: 12s; bottom: -15%; }
    .wave:nth-child(2) { background: radial-gradient(ellipse at center, rgba(255, 210, 0, 0.2) 0%, transparent 70%); animation-duration: 18s; animation-delay: -3s; bottom: -25%; width: 250%; height: 250%; }
    .wave:nth-child(3) { background: radial-gradient(ellipse at center, rgba(247, 151, 30, 0.15) 0%, transparent 70%); animation-duration: 22s; animation-delay: -6s; bottom: -10%; width: 180%; height: 180%; }
    .wave:nth-child(4) { background: radial-gradient(ellipse at center, rgba(255, 107, 107, 0.08) 0%, transparent 70%); animation-duration: 14s; animation-delay: -8s; bottom: -30%; width: 220%; height: 220%; }
    @keyframes waveMove { 0% { transform: translateX(0) translateY(0) rotate(0deg) scale(1); } 25% { transform: translateX(5%) translateY(-3%) rotate(5deg) scale(1.02); } 50% { transform: translateX(-5%) translateY(-5%) rotate(-3deg) scale(0.98); } 75% { transform: translateX(8%) translateY(-2%) rotate(4deg) scale(1.01); } 100% { transform: translateX(0) translateY(0) rotate(0deg) scale(1); } }
    .particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; overflow: hidden; }
    .particle-advanced { position: absolute; opacity: 0; animation: floatParticleAdvanced linear infinite; }
    .particle-advanced.heart { font-size: 20px; }
    .particle-advanced.heart:nth-child(1) { left: 5%; animation-duration: 20s; animation-delay: 0s; font-size: 22px; }
    .particle-advanced.heart:nth-child(2) { left: 15%; animation-duration: 25s; animation-delay: 2s; font-size: 16px; }
    .particle-advanced.heart:nth-child(3) { left: 25%; animation-duration: 18s; animation-delay: 4s; font-size: 28px; }
    .particle-advanced.heart:nth-child(4) { left: 35%; animation-duration: 22s; animation-delay: 1s; font-size: 14px; }
    .particle-advanced.heart:nth-child(5) { left: 45%; animation-duration: 26s; animation-delay: 3s; font-size: 30px; }
    .particle-advanced.heart:nth-child(6) { left: 55%; animation-duration: 19s; animation-delay: 5s; font-size: 18px; }
    .particle-advanced.heart:nth-child(7) { left: 65%; animation-duration: 23s; animation-delay: 0.5s; font-size: 24px; }
    .particle-advanced.heart:nth-child(8) { left: 75%; animation-duration: 21s; animation-delay: 2.5s; font-size: 16px; }
    .particle-advanced.heart:nth-child(9) { left: 85%; animation-duration: 27s; animation-delay: 4.5s; font-size: 26px; }
    .particle-advanced.heart:nth-child(10) { left: 95%; animation-duration: 18s; animation-delay: 1.5s; font-size: 20px; }
    .particle-advanced.shooting-star { position: absolute; width: 4px; height: 4px; background: radial-gradient(circle, #ffd200, transparent); border-radius: 50%; box-shadow: 0 0 20px rgba(247, 151, 30, 0.6); animation: shootingStar linear infinite; }
    .particle-advanced.shooting-star::after { content: ''; position: absolute; top: 50%; right: 0; width: 80px; height: 1px; background: linear-gradient(to left, rgba(247, 151, 30, 0.3), transparent); transform: translateY(-50%); }
    .particle-advanced.shooting-star:nth-child(11) { top: 10%; left: 80%; animation-duration: 6s; animation-delay: 0s; }
    .particle-advanced.shooting-star:nth-child(12) { top: 20%; left: 60%; animation-duration: 8s; animation-delay: 3s; }
    .particle-advanced.shooting-star:nth-child(13) { top: 5%; left: 90%; animation-duration: 7s; animation-delay: 5s; }
    .particle-advanced.shooting-star:nth-child(14) { top: 30%; left: 70%; animation-duration: 9s; animation-delay: 2s; }
    .particle-advanced.shooting-star:nth-child(15) { top: 15%; left: 50%; animation-duration: 5s; animation-delay: 7s; }
    @keyframes floatParticleAdvanced { 0% { transform: translateY(100vh) rotate(0deg) scale(0.3); opacity: 0; } 8% { opacity: 0.8; } 15% { transform: translateY(85vh) rotate(12deg) scale(1); opacity: 1; } 85% { opacity: 0.7; } 100% { transform: translateY(-10vh) rotate(-18deg) scale(0.5); opacity: 0; } }
    @keyframes shootingStar { 0% { transform: translate(0, 0) scale(0); opacity: 0; } 5% { opacity: 1; transform: translate(-100px, 100px) scale(1); } 10% { opacity: 1; transform: translate(-200px, 200px) scale(0.8); } 15% { opacity: 0; transform: translate(-300px, 300px) scale(0); } 100% { opacity: 0; transform: translate(-400px, 400px) scale(0); } }
    .hero-content { position: relative; z-index: 2; display: flex; width: 100%; height: 100vh; max-width: 100vw; padding: 30px 50px; gap: 40px; align-items: center; justify-content: center; }
    .left-column { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; max-width: 45%; padding-right: 20px; border-right: 1px solid rgba(255, 255, 255, 0.05); }
    .event-title-wrapper { position: relative; display: inline-block; padding: 20px 30px; margin: 4px 0 8px; flex-shrink: 0; }
    .event-title { font-family: 'Playfair Display', serif; font-size: 44px; font-weight: 900; color: white; text-shadow: 0 4px 30px rgba(0, 0, 0, 0.4); line-height: 1.1; position: relative; display: inline-block; padding: 8px 20px; }
    .event-title::before { content: ''; position: absolute; inset: -10px -20px; background: radial-gradient(ellipse at center, rgba(247, 151, 30, 0.25) 0%, rgba(255, 107, 107, 0.15) 40%, transparent 75%); border-radius: 50%; z-index: -1; animation: titleAura 4s ease-in-out infinite; filter: blur(20px); }
    @keyframes titleAura { 0%, 100% { transform: scale(1); opacity: 0.6; } 50% { transform: scale(1.15); opacity: 1; } }
    .event-title::after { content: '❤'; position: absolute; top: -20px; right: -10px; font-size: 20px; color: #ff6b6b; text-shadow: 0 0 20px rgba(255, 107, 107, 0.8); animation: heartFloat 3s ease-in-out infinite; pointer-events: none; }
    @keyframes heartFloat { 0%, 100% { transform: translate(0, 0) scale(1) rotate(-10deg); opacity: 0.6; } 50% { transform: translate(5px, -10px) scale(1.3) rotate(15deg); opacity: 1; } }
    .event-title .highlight { background: linear-gradient(135deg, #f7971e 0%, #ffd200 25%, #ff6b6b 50%, #ffd200 75%, #f7971e 100%); background-size: 300% 300%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-style: italic; animation: titleGradient 6s ease-in-out infinite; position: relative; display: inline-block; filter: drop-shadow(0 0 15px rgba(255, 210, 0, 0.4)); }
    @keyframes titleGradient { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
    .title-hearts { position: absolute; inset: 0; pointer-events: none; z-index: 0; overflow: visible; }
    .floating-heart { position: absolute; bottom: -10px; left: var(--x, 50%); font-size: 18px; color: #ff6b6b; text-shadow: 0 0 15px rgba(255, 107, 107, 0.7), 0 0 30px rgba(255, 107, 107, 0.4); opacity: 0; animation: floatHeartUp 4s ease-out infinite; animation-delay: var(--delay, 0s); pointer-events: none; user-select: none; }
    .floating-heart:nth-child(even) { color: #ffd200; text-shadow: 0 0 15px rgba(255, 210, 0, 0.7), 0 0 30px rgba(255, 210, 0, 0.4); font-size: 15px; }
    .floating-heart:nth-child(3n) { color: #ff4757; font-size: 20px; }
    @keyframes floatHeartUp { 0% { transform: translateY(0) translateX(0) scale(0.5) rotate(0deg); opacity: 0; } 15% { opacity: 0.9; transform: translateY(-15px) translateX(5px) scale(1) rotate(10deg); } 50% { opacity: 1; transform: translateY(-60px) translateX(-8px) scale(1.1) rotate(-15deg); } 85% { opacity: 0.6; transform: translateY(-100px) translateX(10px) scale(0.8) rotate(20deg); } 100% { transform: translateY(-130px) translateX(-5px) scale(0.4) rotate(-10deg); opacity: 0; } }
    .event-title .highlight::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.5) 50%, transparent 100%); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; animation: titleShine 4s ease-in-out infinite; pointer-events: none; }
    @keyframes titleShine { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    .host-photo-slider { position: relative; width: 380px; height: 380px; margin-bottom: 15px; flex-shrink: 0; }
    .host-photo-wrapper { width: 100%; height: 100%; border-radius: 50%; overflow: hidden; border: 4px solid rgba(247, 151, 30, 0.5); box-shadow: 0 0 80px rgba(247, 151, 30, 0.2); animation: logoPulse 4s ease-in-out infinite; position: relative; background: linear-gradient(135deg, #2d2b3f, #1a1a2e); transition: all 0.5s ease; }
    .host-photo-wrapper:hover { transform: scale(1.03); border-color: rgba(247, 151, 30, 0.9); box-shadow: 0 0 100px rgba(247, 151, 30, 0.35); }
    @keyframes logoPulse { 0%, 100% { transform: scale(1); box-shadow: 0 0 60px rgba(247, 151, 30, 0.15); } 50% { transform: scale(1.04); box-shadow: 0 0 90px rgba(247, 151, 30, 0.3); } }
    .host-photo-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; position: absolute; top: 0; left: 0; }
    .host-photo-wrapper:hover img { transform: scale(1.05); }
    .host-photo-wrapper .logo-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f7971e, #ffd200); color: white; font-size: 60px; }
    .host-photo-fade-in { animation: hostFadeIn 1.2s ease-in-out both; }
    .host-photo-fade-out { animation: hostFadeOut 1.2s ease-in-out both; }
    @keyframes hostFadeIn { 0% { opacity: 0; transform: scale(0.92); } 100% { opacity: 1; transform: scale(1); } }
    @keyframes hostFadeOut { 0% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(1.08); } }
    .host-photo-indicator { display: flex; gap: 6px; justify-content: center; margin-top: 6px; flex-shrink: 0; }
    .host-photo-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); transition: all 0.4s ease; border: none; padding: 0; cursor: default; }
    .host-photo-dot.active { background: #f7971e; box-shadow: 0 0 15px rgba(247, 151, 30, 0.4); transform: scale(1.2); }
    .divider { display: flex; align-items: center; justify-content: center; gap: 14px; margin: 8px auto 10px; max-width: 200px; width: 100%; flex-shrink: 0; }
    .divider .line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, rgba(247, 151, 30, 0.2), transparent); }
    .divider .diamond { color: #ffd200; font-size: 10px; opacity: 0.4; }
    .welcome-message { font-family: 'Playfair Display', serif; font-size: 20px; font-style: italic; color: rgba(255, 255, 255, 0.65); max-width: 450px; margin: 4px auto 10px; line-height: 1.6; text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2); flex-shrink: 0; }
    .welcome-message .heart-icon { color: #ff6b6b; display: inline-block; animation: heartBeat 1.8s ease-in-out infinite; font-size: 20px; }
    @keyframes heartBeat { 0%, 100% { transform: scale(1); } 14% { transform: scale(1.35); } 28% { transform: scale(1); } 42% { transform: scale(1.25); } 56% { transform: scale(1); } }
    .event-date { display: inline-flex; align-items: center; gap: 10px; padding: 5px 20px; background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(16px); border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.04); margin: 4px auto 6px; color: rgba(255, 255, 255, 0.4); font-size: 12px; font-weight: 300; letter-spacing: 0.5px; flex-shrink: 0; }
    .event-date i { color: #ffd200; font-size: 14px; }
    .event-date strong { color: white; font-weight: 500; }
    .event-date .sep { color: rgba(255, 255, 255, 0.08); }
    .right-column { flex: 1.2; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-left: 20px; max-width: 55%; }
    .logo-badge { display: inline-flex; align-items: center; gap: 14px; padding: 12px 40px; background: linear-gradient(135deg, rgba(247, 151, 30, 0.25), rgba(255, 210, 0, 0.15)); border: 2px solid rgba(255, 210, 0, 0.5); border-radius: 50px; font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 700; letter-spacing: 4px; color: #ffd200; text-transform: uppercase; margin-bottom: 24px; flex-shrink: 0; text-shadow: 0 0 30px rgba(255, 210, 0, 0.6), 0 4px 20px rgba(0, 0, 0, 0.5); box-shadow: 0 0 40px rgba(247, 151, 30, 0.25), inset 0 0 30px rgba(247, 151, 30, 0.08); animation: badgePulse 3s ease-in-out infinite; position: relative; }
    .logo-badge::before { content: ''; position: absolute; inset: -4px; border-radius: 50px; background: linear-gradient(135deg, rgba(247, 151, 30, 0.4), rgba(255, 210, 0, 0.3)); opacity: 0; z-index: -1; animation: badgeGlow 3s ease-in-out infinite; filter: blur(12px); }
    @keyframes badgePulse { 0%, 100% { transform: scale(1); box-shadow: 0 0 40px rgba(247, 151, 30, 0.25), inset 0 0 30px rgba(247, 151, 30, 0.08); } 50% { transform: scale(1.04); box-shadow: 0 0 60px rgba(247, 151, 30, 0.4), inset 0 0 40px rgba(247, 151, 30, 0.15); } }
    @keyframes badgeGlow { 0%, 100% { opacity: 0; } 50% { opacity: 1; } }
    .logo-badge i { color: #ffd200; font-size: 20px; animation: starTwinkle 2s ease-in-out infinite; }
    .logo-badge i:last-child { animation-delay: 1s; }
    @keyframes starTwinkle { 0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; } 50% { transform: scale(1.3) rotate(20deg); opacity: 0.7; } }
    .guest-slider { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    #currentGuestCard { width: 100%; display: flex; justify-content: center; }
    #currentGuestCard .guest-card { width: 100%; max-width: 550px; padding: 35px 30px 30px; background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(30px); border: 1px solid rgba(247, 151, 30, 0.10); border-radius: 30px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); transition: all 0.4s ease; }
    #currentGuestCard .guest-card:hover { border-color: rgba(247, 151, 30, 0.2); background: rgba(255, 255, 255, 0.06); }
    #currentGuestCard .guest-photo, #currentGuestCard .guest-photo-default { width: 360px; height: 360px; margin-bottom: 16px; flex-shrink: 0; }
    #currentGuestCard .guest-photo { border-radius: 50%; object-fit: cover; border: 5px solid rgba(247, 151, 30, 0.5); box-shadow: 0 0 80px rgba(247, 151, 30, 0.2); transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.4s ease; }
    #currentGuestCard .guest-card:hover .guest-photo { border-color: #f7971e; box-shadow: 0 0 120px rgba(247, 151, 30, 0.35); transform: scale(1.02); }
    #currentGuestCard .guest-photo-default { border-radius: 50%; border: 5px solid rgba(247, 151, 30, 0.5); background: linear-gradient(135deg, #f7971e, #ffd200); display: flex; align-items: center; justify-content: center; font-size: 85px; font-weight: 700; color: white; box-shadow: 0 0 80px rgba(247, 151, 30, 0.2); transition: border-color 0.3s ease, transform 0.4s ease; }
    #currentGuestCard .guest-card:hover .guest-photo-default { border-color: #f7971e; transform: scale(1.02); }
    #currentGuestCard .guest-name { font-family: 'Playfair Display', serif; font-size: 44px; font-weight: 700; color: white; margin: 2px 0 4px; line-height: 1.15; }
    #currentGuestCard .guest-event { color: rgba(255, 255, 255, 0.35); font-size: 15px; font-weight: 300; margin-bottom: 4px; }
    #currentGuestCard .guest-event i { color: #ffd200; margin-right: 4px; }
    #currentGuestCard .guest-event .nb-pers { color: rgba(255, 255, 255, 0.2); font-size: 13px; margin-left: 4px; }
    #currentGuestCard .guest-status { color: #34d399; background: rgba(52, 211, 153, 0.08); border: 1px solid rgba(52, 211, 153, 0.12); border-radius: 50px; padding: 6px 24px; margin-top: 10px; font-size: 15px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    #currentGuestCard .guest-status i { font-size: 16px; }
    .slide-position { color: rgba(255, 255, 255, 0.15); font-size: 14px; font-weight: 300; margin-top: 12px; letter-spacing: 1px; }
    .slide-position .count { color: #ffd200; font-weight: 600; }
    .slide-position .total { color: rgba(255, 255, 255, 0.1); }
    .fade-in { animation: fadeInGuest 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
    .fade-out { animation: fadeOutGuest 0.7s cubic-bezier(0.55, 0.085, 0.68, 0.53) both; }
    @keyframes fadeInGuest { 0% { opacity: 0; transform: scale(0.94) translateY(16px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes fadeOutGuest { 0% { opacity: 1; transform: scale(1) translateY(0); } 100% { opacity: 0; transform: scale(0.96) translateY(-12px); } }
    .waiting-state { text-align: center; padding: 30px; }
    .waiting-state .icon { font-size: 60px; color: #f7971e; display: block; margin-bottom: 12px; animation: pulseIcon 2.5s ease-in-out infinite; }
    @keyframes pulseIcon { 0%, 100% { transform: scale(1); opacity: 0.7; } 50% { transform: scale(1.08); opacity: 1; } }
    .waiting-state h3 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: white; margin-bottom: 6px; }
    .waiting-state p { color: rgba(255, 255, 255, 0.3); font-size: 15px; font-weight: 300; }
    .waiting-state .spinner { display: inline-block; width: 36px; height: 36px; border: 3px solid rgba(255, 255, 255, 0.06); border-top: 3px solid #f7971e; border-radius: 50%; animation: spin 1s linear infinite; margin-top: 10px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .refresh-counter { position: fixed; bottom: 25px; right: 25px; font-size: 11px; color: rgba(255, 255, 255, 0.10); z-index: 50; background: rgba(0, 0, 0, 0.3); padding: 5px 14px; border-radius: 20px; backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.02); font-weight: 300; letter-spacing: 0.5px; }
    .refresh-counter .count { font-weight: 600; color: #f7971e; }
    .refresh-counter i { margin-right: 4px; }
    @media (max-width: 1024px) { .hero-content { padding: 20px 30px; gap: 25px; } .left-column { max-width: 40%; padding-right: 15px; } .right-column { max-width: 60%; padding-left: 15px; } .event-title-wrapper { padding: 15px 20px; } .event-title { font-size: 36px; } .floating-heart { font-size: 14px; } .floating-heart:nth-child(3n) { font-size: 16px; } .event-title::after { font-size: 16px; } .host-photo-slider { width: 150px; height: 150px; } .host-photo-wrapper .logo-placeholder { font-size: 48px; } .welcome-message { font-size: 17px; } .logo-badge { font-size: 24px; padding: 10px 30px; letter-spacing: 3px; margin-bottom: 18px; gap: 10px; } .logo-badge i { font-size: 16px; } #currentGuestCard .guest-photo, #currentGuestCard .guest-photo-default { width: 220px; height: 220px; } #currentGuestCard .guest-photo-default { font-size: 68px; } #currentGuestCard .guest-name { font-size: 36px; } #currentGuestCard .guest-card { max-width: 480px; padding: 28px 20px 24px; } }
    @media (max-width: 820px) { .hero-content { flex-direction: column; padding: 15px 25px; gap: 10px; height: 100vh; overflow-y: auto; } .left-column { max-width: 100%; width: 100%; padding-right: 0; border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.04); padding-bottom: 12px; flex-shrink: 0; } .right-column { max-width: 100%; width: 100%; padding-left: 0; flex: 1; min-height: 0; } .event-title-wrapper { padding: 10px 15px; } .event-title { font-size: 28px; } .floating-heart { font-size: 12px; } .floating-heart:nth-child(even) { font-size: 10px; } .floating-heart:nth-child(3n) { font-size: 14px; } .event-title::after { font-size: 14px; top: -15px; } .host-photo-slider { width: 100px; height: 100px; margin-bottom: 8px; } .host-photo-wrapper .logo-placeholder { font-size: 36px; } .host-photo-indicator { margin-top: 4px; } .host-photo-dot { width: 6px; height: 6px; } .welcome-message { font-size: 14px; max-width: 100%; margin: 2px auto 6px; } .welcome-message .heart-icon { font-size: 14px; } .event-date { font-size: 10px; padding: 3px 14px; margin: 2px auto 4px; } .divider { max-width: 150px; margin: 4px auto 6px; } .logo-badge { font-size: 20px; padding: 8px 24px; letter-spacing: 2px; margin-bottom: 14px; gap: 10px; } .logo-badge i { font-size: 14px; } .guest-slider { padding: 4px 0; } #currentGuestCard .guest-card { max-width: 400px; padding: 20px 16px 18px; border-radius: 22px; } #currentGuestCard .guest-photo, #currentGuestCard .guest-photo-default { width: 160px; height: 160px; margin-bottom: 10px; } #currentGuestCard .guest-photo-default { font-size: 50px; } #currentGuestCard .guest-name { font-size: 28px; } #currentGuestCard .guest-event { font-size: 13px; } #currentGuestCard .guest-status { font-size: 13px; padding: 4px 18px; margin-top: 6px; } .slide-position { font-size: 12px; margin-top: 6px; } .refresh-counter { bottom: 12px; right: 12px; font-size: 9px; padding: 3px 10px; } }
    @media (max-width: 480px) { .hero-content { padding: 10px 12px; gap: 6px; } .left-column { padding-bottom: 8px; } .event-title-wrapper { padding: 8px 10px; } .event-title { font-size: 22px; } .floating-heart { font-size: 9px; } .floating-heart:nth-child(even) { font-size: 8px; } .floating-heart:nth-child(3n) { font-size: 11px; } .event-title::after { font-size: 11px; top: -10px; } .host-photo-slider { width: 80px; height: 80px; margin-bottom: 4px; } .host-photo-wrapper { border-width: 3px; } .host-photo-wrapper .logo-placeholder { font-size: 28px; } .host-photo-indicator { margin-top: 2px; gap: 4px; } .host-photo-dot { width: 5px; height: 5px; } .welcome-message { font-size: 12px; margin: 1px auto 4px; line-height: 1.4; } .welcome-message .heart-icon { font-size: 12px; } .event-date { font-size: 8px; padding: 2px 10px; gap: 4px; } .event-date i { font-size: 10px; } .divider { max-width: 100px; gap: 8px; margin: 2px auto 4px; } .divider .diamond { font-size: 7px; } .logo-badge { font-size: 15px; padding: 6px 18px; letter-spacing: 1.5px; margin-bottom: 10px; gap: 8px; border-width: 1.5px; } .logo-badge i { font-size: 11px; } #currentGuestCard .guest-card { max-width: 320px; padding: 14px 10px 12px; border-radius: 16px; } #currentGuestCard .guest-photo, #currentGuestCard .guest-photo-default { width: 120px; height: 120px; margin-bottom: 6px; border-width: 3px; } #currentGuestCard .guest-photo-default { font-size: 38px; } #currentGuestCard .guest-name { font-size: 20px; } #currentGuestCard .guest-event { font-size: 11px; } #currentGuestCard .guest-event .nb-pers { font-size: 10px; } #currentGuestCard .guest-status { font-size: 11px; padding: 3px 12px; margin-top: 4px; gap: 4px; } #currentGuestCard .guest-status i { font-size: 12px; } .slide-position { font-size: 10px; margin-top: 4px; } .waiting-state .icon { font-size: 40px; } .waiting-state h3 { font-size: 18px; } .waiting-state p { font-size: 12px; } .waiting-state .spinner { width: 28px; height: 28px; } .refresh-counter { bottom: 8px; right: 8px; font-size: 8px; padding: 2px 8px; } }
    @media (max-width: 380px) { .event-title-wrapper { padding: 6px 8px; } .event-title { font-size: 18px; } .floating-heart { font-size: 8px; } .floating-heart:nth-child(3n) { font-size: 9px; } .event-title::after { font-size: 9px; top: -8px; } .logo-badge { font-size: 13px; padding: 5px 14px; letter-spacing: 1px; margin-bottom: 8px; gap: 6px; } .logo-badge i { font-size: 10px; } #currentGuestCard .guest-photo, #currentGuestCard .guest-photo-default { width: 100px; height: 100px; } #currentGuestCard .guest-photo-default { font-size: 32px; } #currentGuestCard .guest-name { font-size: 17px; } #currentGuestCard .guest-card { padding: 10px 8px 8px; } .welcome-message { font-size: 11px; } .host-photo-slider { width: 65px; height: 65px; } .host-photo-wrapper .logo-placeholder { font-size: 22px; } }
    @media (prefers-reduced-motion: reduce) { .particle-advanced { animation: none !important; display: none; } .wave { animation: none !important; } .host-photo-wrapper { animation: none !important; } .welcome-message .heart-icon { animation: none !important; } .waiting-state .icon { animation: none !important; } .fade-in, .fade-out { animation: none !important; } .host-photo-fade-in, .host-photo-fade-out { animation: none !important; } .logo-badge, .logo-badge::before, .logo-badge i { animation: none !important; } .event-title::before, .event-title::after, .event-title .highlight, .event-title .highlight::after, .floating-heart { animation: none !important; } }
</style>
</head>
<body>

<div class="hero-bg"></div>
<div class="waves-container"><div class="wave"></div><div class="wave"></div><div class="wave"></div><div class="wave"></div></div>
<div class="particles-container">
    <div class="particle-advanced heart">❤</div><div class="particle-advanced heart">💕</div><div class="particle-advanced heart">❤</div><div class="particle-advanced heart">💕</div><div class="particle-advanced heart">❤</div><div class="particle-advanced heart">💕</div><div class="particle-advanced heart">❤</div><div class="particle-advanced heart">💕</div><div class="particle-advanced heart">❤</div><div class="particle-advanced heart">💕</div>
    <div class="particle-advanced shooting-star"></div><div class="particle-advanced shooting-star"></div><div class="particle-advanced shooting-star"></div><div class="particle-advanced shooting-star"></div><div class="particle-advanced shooting-star"></div>
</div>

<div class="hero-content">
    <div class="left-column">
        <div class="event-title-wrapper">
            <div class="title-hearts">
                <span class="floating-heart" style="--delay: 0s; --x: 10%;">❤</span>
                <span class="floating-heart" style="--delay: 0.5s; --x: 25%;">💕</span>
                <span class="floating-heart" style="--delay: 1s; --x: 40%;">❤</span>
                <span class="floating-heart" style="--delay: 1.5s; --x: 55%;">💕</span>
                <span class="floating-heart" style="--delay: 2s; --x: 70%;">❤</span>
                <span class="floating-heart" style="--delay: 2.5s; --x: 85%;">💕</span>
            </div>
            <h1 class="event-title"><span class="highlight"><?php echo htmlspecialchars($eventDisplayName, ENT_QUOTES, 'UTF-8'); ?></span></h1>
        </div>

        <div class="host-photo-slider">
            <div class="host-photo-wrapper" id="hostPhotoWrapper">
                <?php if (!empty($hostPhotoUrls) && $hostPhotoUrls[0] !== 'default'): ?>
                    <img src="<?php echo htmlspecialchars($hostPhotoUrls[0], ENT_QUOTES, 'UTF-8'); ?>" alt="Photo des mariés" class="host-photo-fade-in" id="hostPhotoImg" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'logo-placeholder\'><i class=\'bi bi-calendar2-heart-fill\'></i></div>'">
                <?php else: ?>
                    <div class="logo-placeholder" id="hostPhotoPlaceholder"><i class="bi bi-calendar2-heart-fill"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="host-photo-indicator" id="hostPhotoIndicator">
            <?php for ($i = 0; $i < count($hostPhotoUrls); $i++): ?>
                <span class="host-photo-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>

        <div class="divider"><span class="line"></span><span class="diamond">◆</span><span class="line"></span></div>
        <p class="welcome-message"><span class="heart-icon">❤</span> Nous sommes ravis de vous accueillir<br>pour ce moment unique et inoubliable. <span class="heart-icon">❤</span></p>

        <?php if ($evenementInfo && !empty($evenementInfo['date_evenement'])): ?>
            <div class="event-date"><i class="bi bi-calendar-event"></i><span><strong><?php echo htmlspecialchars($evenementInfo['lieu'] ?? 'Mariage', ENT_QUOTES, 'UTF-8'); ?></strong> <span class="sep">•</span> <?php echo date('d F Y', strtotime($evenementInfo['date_evenement'])); ?></span></div>
        <?php endif; ?>
    </div>

    <div class="right-column">
        <div class="logo-badge"><i class="bi bi-star-fill"></i> Bienvenue <i class="bi bi-star-fill"></i></div>
        <div id="guestsGrid" class="guest-slider">
            <?php if (empty($invitesPresents)): ?>
                <div class="waiting-state"><span class="icon"><i class="bi bi-hourglass-split"></i></span><h3>En attente de présence</h3><p>Aucun invité n'est encore enregistré comme présent.</p><div class="spinner"></div></div>
            <?php else: ?>
                <div id="currentGuestCard"></div><div class="slide-position" id="slidePosition"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="refresh-counter" id="refreshCounter"><i class="bi bi-arrow-clockwise"></i> Mise à jour dans <span class="count" id="countdown">5</span>s</div>

<script>
// ============================================
// FIX: Utilise chemin relatif pour photos
// ============================================
const PHOTOS_BASE = <?php echo json_encode($photosBaseUrl, JSON_UNESCAPED_SLASHES); ?>;

let hostPhotos = <?php echo json_encode($hostPhotoUrls, JSON_UNESCAPED_SLASHES); ?>;
let currentHostPhotoIndex = 0;
let hostPhotoTimer = null;
const hostPhotoDuration = 4000;

function updateHostPhoto(index, withAnimation = true) {
    const wrapper = document.getElementById('hostPhotoWrapper');
    const img = document.getElementById('hostPhotoImg');
    const placeholder = document.getElementById('hostPhotoPlaceholder');
    if (!wrapper) return;
    const dots = document.querySelectorAll('.host-photo-dot');
    dots.forEach((dot, i) => { dot.classList.toggle('active', i === index); });
    if (!hostPhotos.length || hostPhotos[0] === 'default') {
        if (placeholder) placeholder.style.display = 'flex';
        if (img) img.style.display = 'none';
        return;
    }
    const photoUrl = hostPhotos[index];
    if (!photoUrl) return;
    if (img) {
        if (withAnimation) {
            img.classList.remove('host-photo-fade-in', 'host-photo-fade-out');
            void img.offsetWidth;
            img.classList.add('host-photo-fade-out');
            setTimeout(() => {
                img.src = photoUrl;
                img.classList.remove('host-photo-fade-out');
                img.classList.add('host-photo-fade-in');
                img.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            }, 600);
        } else {
            img.src = photoUrl;
            img.classList.add('host-photo-fade-in');
            img.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
    }
}
function nextHostPhoto() { if (hostPhotos.length <= 1 || hostPhotos[0] === 'default') return; currentHostPhotoIndex = (currentHostPhotoIndex + 1) % hostPhotos.length; updateHostPhoto(currentHostPhotoIndex, true); }
function startHostPhotoSlideshow() { clearInterval(hostPhotoTimer); if (hostPhotos.length > 1 && hostPhotos[0] !== 'default') { hostPhotoTimer = setInterval(nextHostPhoto, hostPhotoDuration); } }
if (hostPhotos.length > 0 && hostPhotos[0] !== 'default') { updateHostPhoto(0, false); startHostPhotoSlideshow(); }

document.addEventListener('DOMContentLoaded', function() {
    const hearts = document.querySelectorAll('.particle-advanced.heart');
    hearts.forEach((h) => { const delay = Math.random() * 3; const duration = 18 + Math.random() * 14; h.style.animationDelay = delay + 's'; h.style.animationDuration = duration + 's'; });
    document.addEventListener('mousemove', function(e) { const x = (e.clientX / window.innerWidth - 0.5) * 24; const y = (e.clientY / window.innerHeight - 0.5) * 24; const container = document.querySelector('.particles-container'); if (container) { container.style.transform = `translate(${x * 0.03}px, ${y * 0.03}px)`; } });
});

let countdown = 5;
const countdownEl = document.getElementById('countdown');
if (countdownEl) { setInterval(() => { countdown--; if (countdown < 0) countdown = 5; countdownEl.textContent = countdown; }, 1000); }

function createConfetti() {
    const container = document.createElement('div');
    container.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;overflow:hidden';
    document.body.appendChild(container);
    const colors = ['#f7971e', '#ffd200', '#ff6b6b', '#4facfe', '#38ef7d', '#f093fb', '#a18cd1', '#ff4757', '#00f2fe', '#ff6348', '#7bed9f', '#ffd93d'];
    for (let i = 0; i < 40; i++) {
        const c = document.createElement('div'); const color = colors[Math.floor(Math.random() * colors.length)]; const size = Math.random() * 12 + 4; const left = Math.random() * 100; const dur = Math.random() * 4 + 2.5; const delay = Math.random() * 2.5; const shapes = ['50%', '2px', '0px']; const shape = shapes[Math.floor(Math.random() * shapes.length)];
        c.style.cssText = `position:absolute; left:${left}%; top:-20px; width:${size}px; height:${size * (0.8 + Math.random() * 0.6)}px; background:${color}; border-radius:${shape}; animation:confettiFall ${dur}s ease-in ${delay}s forwards; transform:rotate(${Math.random() * 360}deg); box-shadow:0 2px 8px rgba(0,0,0,0.15);`;
        container.appendChild(c);
    }
    if (!document.getElementById('confettiStyle')) { const s = document.createElement('style'); s.id = 'confettiStyle'; s.textContent = `@keyframes confettiFall { 0% { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; } 100% { transform: translateY(110vh) rotate(${Math.random() > 0.5 ? '720' : '-540'}deg) scale(0.3); opacity: 0; } }`; document.head.appendChild(s); }
    setTimeout(() => container.remove(), 7000);
}
<?php if (!empty($invitesPresents)): ?> setTimeout(createConfetti, 400); setInterval(createConfetti, 9000); <?php endif; ?>

const evenementId = <?php echo (int)$evenementId; ?>;
let presentGuests = <?php echo json_encode($invitesPresents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let currentGuestIndex = 0;
let slideTimer = null;
let isTransitioning = false;
const slideDuration = 5000;
const transitionDuration = 700;
const guestsGrid = document.getElementById('guestsGrid');
const currentGuestCard = document.getElementById('currentGuestCard');
const slidePosition = document.getElementById('slidePosition');

function escapeHtml(v) { const d = document.createElement('div'); d.textContent = v ?? ''; return d.innerHTML; }
function getInitials(p, n) { return ((p || '').substring(0, 1) + (n || '').substring(0, 1)).toUpperCase(); }
function getGuestKey(inv) { return String(inv.invitation_id || inv.invite_id || `${inv.prenom}-${inv.nom}`); }
function getInvitePhotoUrl(photo) {
    if (!photo) return '';
    // FIX: chemin relatif, pas APP_URL absolu qui casse
    return PHOTOS_BASE + encodeURIComponent(photo);
}
function buildGuestCard(invite, anim = 'fade-in') {
    const nomComplet = `${invite.prenom || ''} ${invite.nom || ''}`.trim();
    const initiales = getInitials(invite.prenom, invite.nom);
    let photoHtml = `<div class="guest-photo-default">${escapeHtml(initiales || '??')}</div>`;
    if (invite.photo) {
        const url = getInvitePhotoUrl(invite.photo);
        photoHtml = `<img src="${url}" alt="Photo invité" class="guest-photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="guest-photo-default" style="display:none;">${escapeHtml(initiales || '??')}</div>`;
    }
    const nbPresents = invite.nb_presents ? ` <span class="nb-pers">• ${invite.nb_presents} pers.</span>` : '';
    return `<div class="guest-card ${anim}">${photoHtml}<div class="guest-name">${escapeHtml(nomComplet)}</div><div class="guest-event"><i class="bi bi-calendar-event"></i> ${escapeHtml(invite.evenement_nom || '')} ${nbPresents}</div><div class="guest-status"><i class="bi bi-check-circle-fill"></i> Présent(e)</div></div>`;
}
function renderEmptyState() { guestsGrid.innerHTML = `<div class="waiting-state"><span class="icon"><i class="bi bi-hourglass-split"></i></span><h3>En attente de présence</h3><p>Aucun invité n'est encore enregistré comme présent.</p><div class="spinner"></div></div>`; }
function updatePosition() { if (slidePosition && presentGuests.length > 0) { slidePosition.innerHTML = `<span class="count">${currentGuestIndex + 1}</span> <span class="total">/ ${presentGuests.length}</span>`; } else if (slidePosition) { slidePosition.innerHTML = ''; } }
function showGuest(index, withAnim = true) { if (!currentGuestCard || presentGuests.length === 0) return; currentGuestIndex = (index + presentGuests.length) % presentGuests.length; currentGuestCard.innerHTML = buildGuestCard(presentGuests[currentGuestIndex], withAnim ? 'fade-in' : ''); updatePosition(); }
function nextGuest() { if (presentGuests.length <= 1 || isTransitioning) return; isTransitioning = true; const oldCard = currentGuestCard.querySelector('.guest-card'); if (oldCard) { oldCard.classList.remove('fade-in'); oldCard.classList.add('fade-out'); } setTimeout(() => { showGuest(currentGuestIndex + 1, true); isTransitioning = false; }, transitionDuration); }
function startSlideshow() { clearInterval(slideTimer); if (presentGuests.length > 1) { slideTimer = setInterval(nextGuest, slideDuration); } }
function updateGuests(newGuests) {
    const oldKeys = new Set(presentGuests.map(g => getGuestKey(g)));
    const currentKey = presentGuests.length > 0 ? getGuestKey(presentGuests[currentGuestIndex]) : null;
    presentGuests = Array.isArray(newGuests) ? newGuests : [];
    if (presentGuests.length === 0) { currentGuestIndex = 0; clearInterval(slideTimer); renderEmptyState(); return; }
    const sameIdx = currentKey ? presentGuests.findIndex(g => getGuestKey(g) === currentKey) : -1;
    currentGuestIndex = sameIdx >= 0 ? sameIdx : 0;
    const hasNew = presentGuests.some(g => !oldKeys.has(getGuestKey(g)));
    if (hasNew) { currentGuestIndex = 0; showGuest(0, true); startSlideshow(); setTimeout(createConfetti, 300); } else { showGuest(currentGuestIndex, false); startSlideshow(); }
}
let isLoading = false;
function loadPresentGuests() {
    if (isLoading) return; isLoading = true;
    const url = new URL(window.location.href); url.searchParams.set('ajax', '1'); if (evenementId > 0) url.searchParams.set('evenement', evenementId);
    fetch(url.toString(), { cache: 'no-store', headers: { 'Accept': 'application/json' } })
        .then(r => { if (!r.ok) throw new Error('HTTP'); return r.json(); })
        .then(data => {
            if (data.success) {
                if (data.host_photos && Array.isArray(data.host_photos) && data.host_photos.length > 0) {
                    const newHostPhotos = data.host_photos;
                    if (JSON.stringify(hostPhotos) !== JSON.stringify(newHostPhotos)) {
                        const indicator = document.getElementById('hostPhotoIndicator');
                        if (indicator) {
                            let dotsHtml = ''; newHostPhotos.forEach((photo, i) => { dotsHtml += `<span class="host-photo-dot ${i === 0 ? 'active' : ''}" data-index="${i}"></span>`; }); indicator.innerHTML = dotsHtml;
                        }
                        if (newHostPhotos.length > 0 && newHostPhotos[0] !== 'default') {
                            const img = document.getElementById('hostPhotoImg');
                            if (img) { img.src = newHostPhotos[0]; img.style.display = 'block'; const placeholder = document.getElementById('hostPhotoPlaceholder'); if (placeholder) placeholder.style.display = 'none'; }
                            clearInterval(hostPhotoTimer); currentHostPhotoIndex = 0; hostPhotos = newHostPhotos;
                            if (newHostPhotos.length > 1) { hostPhotoTimer = setInterval(nextHostPhoto, hostPhotoDuration); }
                        }
                    }
                }
                updateGuests(data.invites);
            }
        })
        .catch(e => console.error('Erreur AJAX splash :', e))
        .finally(() => { isLoading = false; });
}
if (presentGuests.length > 0) { showGuest(0, false); startSlideshow(); }
setInterval(loadPresentGuests, 5000);
</script>
</body>
</html>
