<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/**
 * Écran Splash FULLSCREEN - Affichage TV / Projecteur
 * Reprend la logique de splash.php mais optimisé plein écran
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== FIX INFINITYFREE ==========
$documentRoot  = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot   = realpath(__DIR__ . '/../');
$projectFolder = '';
if ($documentRoot && $projectRoot) {
    $relative = str_replace($documentRoot, '', $projectRoot);
    $relative = str_replace('\\', '/', $relative);
    $projectFolder = rtrim($relative, '/');
}
if (!defined('BASE_PATH')) define('BASE_PATH', $projectFolder);
if (!defined('APP_NAME'))  define('APP_NAME', 'Gestion Invitations');
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol . '://' . $host . $projectFolder);
}

$evenementId = isset($_GET['evenement']) ? (int)$_GET['evenement'] : 0;
$pdo = getDbConnection();

// ============================================
// FONCTION : PHOTOS HÔTES
// ============================================
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

$hostPhotos    = getHostPhotos($pdo, $evenementId);
$hostPhotoUrls = [];
foreach ($hostPhotos as $photo) {
    if ($photo) {
        $hostPhotoUrls[] = APP_URL . '/uploads/photos_host/' . rawurlencode(basename($photo));
    }
}
if (empty($hostPhotoUrls)) {
    $hostPhotoUrls = ['default'];
}

// ============================================
// ENDPOINT AJAX (identique à splash.php)
// ============================================
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
                $updatedHostPhotoUrls[] = APP_URL . '/uploads/photos_host/' . rawurlencode(basename($photo));
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

// ============================================
// CHARGEMENT INITIAL
// ============================================
$invitesPresents = [];
$evenementInfo   = null;

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Écran d'accueil — <?php echo htmlspecialchars(APP_NAME); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    html, body {
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #0f0e17;
        font-family: 'Poppins', sans-serif;
        color: white;
        cursor: none; /* Cache le curseur pour un vrai mode kiosque */
        user-select: none;
    }

    /* ============================================
       FOND ANIMÉ
       ============================================ */
    .hero-bg {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        z-index: 0;
        background: linear-gradient(145deg, #0f0e17 0%, #1a1424 30%, #2d1b3d 60%, #0f0e17 100%);
        background-size: 300% 300%;
        animation: bgShift 20s ease-in-out infinite;
    }
    @keyframes bgShift {
        0%, 100% { background-position: 0% 50%; }
        50%      { background-position: 100% 50%; }
    }
    .hero-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(145deg,
            rgba(15,14,23,0.85) 0%,
            rgba(247,151,30,0.06) 45%,
            rgba(15,14,23,0.80) 100%);
        z-index: 1;
    }

    /* ============================================
       VAGUES
       ============================================ */
    .waves-container {
        position: fixed;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        overflow: hidden;
    }
    .wave {
        position: absolute;
        bottom: -20%; left: -50%;
        width: 200%; height: 200%;
        border-radius: 45%;
        opacity: 0.08;
        animation: waveMove 15s infinite linear;
    }
    .wave:nth-child(1) { background: radial-gradient(ellipse at center, rgba(247,151,30,0.3), transparent 70%); animation-duration: 12s; }
    .wave:nth-child(2) { background: radial-gradient(ellipse at center, rgba(255,210,0,0.2), transparent 70%); animation-duration: 18s; animation-delay: -3s; width: 250%; height: 250%; }
    .wave:nth-child(3) { background: radial-gradient(ellipse at center, rgba(247,151,30,0.15), transparent 70%); animation-duration: 22s; animation-delay: -6s; width: 180%; height: 180%; }
    .wave:nth-child(4) { background: radial-gradient(ellipse at center, rgba(255,107,107,0.08), transparent 70%); animation-duration: 14s; animation-delay: -8s; width: 220%; height: 220%; }
    @keyframes waveMove {
        0%   { transform: translateX(0) translateY(0) rotate(0deg) scale(1); }
        25%  { transform: translateX(5%) translateY(-3%) rotate(5deg) scale(1.02); }
        50%  { transform: translateX(-5%) translateY(-5%) rotate(-3deg) scale(0.98); }
        75%  { transform: translateX(8%) translateY(-2%) rotate(4deg) scale(1.01); }
        100% { transform: translateX(0) translateY(0) rotate(0deg) scale(1); }
    }

    /* ============================================
       PARTICULES (CŒURS FLOTTANTS)
       ============================================ */
    .particles-container {
        position: fixed;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        overflow: hidden;
    }
    .particle-heart {
        position: absolute;
        bottom: -10vh;
        font-size: 20px;
        color: #ff6b6b;
        opacity: 0;
        animation: floatHeartUp linear infinite;
        text-shadow: 0 0 15px rgba(255,107,107,0.5);
    }
    .particle-heart:nth-child(even) { color: #ffd200; text-shadow: 0 0 15px rgba(255,210,0,0.5); }
    .particle-heart:nth-child(3n)  { color: #ff4757; }
    @keyframes floatHeartUp {
        0%   { transform: translateY(0) rotate(0deg) scale(0.3); opacity: 0; }
        10%  { opacity: 0.9; }
        50%  { transform: translateY(-55vh) rotate(15deg) scale(1); opacity: 1; }
        100% { transform: translateY(-115vh) rotate(-15deg) scale(0.5); opacity: 0; }
    }

    /* ============================================
       LAYOUT PRINCIPAL
       ============================================ */
    .hero-content {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 42% 1px 58%;
        width: 100vw;
        height: 100vh;
        padding: 2vh 3vw;
        gap: 2.5vw;
        align-items: center;
    }

    /* Séparateur vertical doré */
    .center-divider {
        width: 1px;
        align-self: stretch;
        margin: 12vh 0;
        background: linear-gradient(to bottom,
            transparent 0%,
            rgba(247,151,30,0.15) 20%,
            rgba(255,210,0,0.6) 50%,
            rgba(247,151,30,0.15) 80%,
            transparent 100%);
        position: relative;
    }
    .center-divider::before,
    .center-divider::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #ffd200;
        box-shadow: 0 0 15px rgba(255,210,0,0.9);
    }
    .center-divider::before { top: -3px; }
    .center-divider::after  { bottom: -3px; }

    /* ============================================
       COLONNE GAUCHE — HÔTES
       ============================================ */
    .left-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        height: 100%;
        gap: 1.5vh;
    }

    /* === NOM DE L'ÉVÉNEMENT === */
    .event-title-wrapper {
        position: relative;
        flex-shrink: 0;
    }
    .event-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 3.2vw, 64px);
        font-weight: 900;
        color: white;
        line-height: 1.1;
        text-shadow: 0 4px 30px rgba(0,0,0,0.4);
        letter-spacing: -0.02em;
    }
    .event-title .highlight {
        background: linear-gradient(135deg, #f7971e 0%, #ffd200 25%, #ff6b6b 50%, #ffd200 75%, #f7971e 100%);
        background-size: 300% 300%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-style: italic;
        animation: titleGradient 6s ease-in-out infinite;
        filter: drop-shadow(0 0 20px rgba(255,210,0,0.5));
    }
    @keyframes titleGradient {
        0%, 100% { background-position: 0% 50%; }
        50%      { background-position: 100% 50%; }
    }

    /* Cœurs flottants autour du titre */
    .title-hearts { position: absolute; inset: -30px; pointer-events: none; }
    .floating-heart {
        position: absolute;
        bottom: 0;
        left: var(--x, 50%);
        font-size: clamp(12px, 1.2vw, 22px);
        color: #ff6b6b;
        text-shadow: 0 0 15px rgba(255,107,107,0.7);
        opacity: 0;
        animation: floatHeartTitle 4s ease-out infinite;
        animation-delay: var(--delay, 0s);
    }
    .floating-heart:nth-child(even) { color: #ffd200; font-size: clamp(10px, 1vw, 18px); text-shadow: 0 0 15px rgba(255,210,0,0.7); }
    @keyframes floatHeartTitle {
        0%   { transform: translateY(0) scale(0.5); opacity: 0; }
        15%  { opacity: 0.9; }
        100% { transform: translateY(-120px) translateX(10px) scale(0.4) rotate(20deg); opacity: 0; }
    }

    /* === PHOTO DES HÔTES === */
    .host-photo-slider {
        position: relative;
        width: clamp(180px, 22vw, 380px);
        height: clamp(180px, 22vw, 380px);
        flex-shrink: 0;
    }
    .host-photo-wrapper {
        width: 100%; height: 100%;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid rgba(247,151,30,0.5);
        box-shadow: 0 0 80px rgba(247,151,30,0.25);
        animation: logoPulse 4s ease-in-out infinite;
        background: linear-gradient(135deg, #2d2b3f, #1a1a2e);
        position: relative;
    }
    @keyframes logoPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 60px rgba(247,151,30,0.15); }
        50%      { transform: scale(1.04); box-shadow: 0 0 100px rgba(247,151,30,0.35); }
    }
    .host-photo-wrapper img {
        width: 100%; height: 100%;
        object-fit: cover;
        position: absolute; top: 0; left: 0;
        transition: transform 0.6s ease;
    }
    .host-photo-wrapper .logo-placeholder {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #f7971e, #ffd200);
        color: white;
        font-size: clamp(60px, 8vw, 140px);
    }
    .host-photo-fade-in  { animation: hostFadeIn  1.2s ease-in-out both; }
    .host-photo-fade-out { animation: hostFadeOut 1.2s ease-in-out both; }
    @keyframes hostFadeIn  { 0% { opacity: 0; transform: scale(0.92); } 100% { opacity: 1; transform: scale(1); } }
    @keyframes hostFadeOut { 0% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(1.08); } }

    .host-photo-indicator {
        display: flex; gap: 6px;
        justify-content: center;
        margin-top: 8px;
        flex-shrink: 0;
    }
    .host-photo-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        transition: all 0.4s ease;
    }
    .host-photo-dot.active {
        background: #f7971e;
        box-shadow: 0 0 15px rgba(247,151,30,0.5);
        transform: scale(1.2);
    }

    /* === DIVIDER DÉCORATIF === */
    .divider {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0.5vh auto;
        max-width: 200px;
        width: 100%;
        flex-shrink: 0;
    }
    .divider .line {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(247,151,30,0.4), transparent);
    }
    .divider .diamond {
        color: #ffd200;
        font-size: 10px;
        opacity: 0.6;
    }

    /* === MESSAGE DE BIENVENUE === */
    .welcome-message {
        font-family: 'Playfair Display', serif;
        font-size: clamp(13px, 1.15vw, 22px);
        font-style: italic;
        color: rgba(255,255,255,0.7);
        max-width: 90%;
        line-height: 1.6;
        flex-shrink: 0;
    }
    .welcome-message .heart-icon {
        color: #ff6b6b;
        display: inline-block;
        animation: heartBeat 1.8s ease-in-out infinite;
        font-size: 1.2em;
    }
    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        14%      { transform: scale(1.35); }
        28%      { transform: scale(1); }
        42%      { transform: scale(1.25); }
        56%      { transform: scale(1); }
    }

    /* === DATE / LIEU === */
    .event-date {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0.6vh 1.5vw;
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(16px);
        border-radius: 50px;
        border: 1px solid rgba(255,255,255,0.06);
        color: rgba(255,255,255,0.5);
        font-size: clamp(11px, 0.9vw, 15px);
        font-weight: 300;
        letter-spacing: 0.05em;
        flex-shrink: 0;
    }
    .event-date strong { color: white; font-weight: 500; }
    .event-date .sep { color: rgba(255,255,255,0.1); }
    .event-date .icon { color: #ffd200; }

    /* ============================================
       COLONNE DROITE — INVITÉ
       ============================================ */
    .right-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 2vh;
    }

    /* === BADGE BIENVENUE === */
    .logo-badge {
        display: inline-flex;
        align-items: center;
        gap: 1vw;
        padding: 1.2vh 3vw;
        background: linear-gradient(135deg, rgba(247,151,30,0.25), rgba(255,210,0,0.15));
        border: 2px solid rgba(255,210,0,0.5);
        border-radius: 50px;
        font-family: 'Playfair Display', serif;
        font-size: clamp(18px, 1.8vw, 38px);
        font-weight: 700;
        letter-spacing: 0.2em;
        color: #ffd200;
        text-transform: uppercase;
        flex-shrink: 0;
        text-shadow: 0 0 30px rgba(255,210,0,0.6), 0 4px 20px rgba(0,0,0,0.5);
        box-shadow: 0 0 40px rgba(247,151,30,0.3), inset 0 0 30px rgba(247,151,30,0.1);
        animation: badgePulse 3s ease-in-out infinite;
    }
    .logo-badge .star {
        color: #ffd200;
        font-size: 0.85em;
        animation: starTwinkle 2s ease-in-out infinite;
    }
    .logo-badge .star:last-child { animation-delay: 1s; }
    @keyframes badgePulse {
        0%, 100% { transform: scale(1);     box-shadow: 0 0 40px rgba(247,151,30,0.3), inset 0 0 30px rgba(247,151,30,0.1); }
        50%      { transform: scale(1.04);  box-shadow: 0 0 70px rgba(247,151,30,0.5), inset 0 0 40px rgba(247,151,30,0.2); }
    }
    @keyframes starTwinkle {
        0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
        50%      { transform: scale(1.3) rotate(20deg); opacity: 0.7; }
    }

    /* === CARTE INVITÉ === */
    .guest-slider {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        min-height: 0;
    }
    #currentGuestCard {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    #currentGuestCard .guest-card {
        width: 100%;
        max-width: min(90%, 550px);
        padding: clamp(20px, 3vh, 40px) clamp(20px, 2.5vw, 40px);
        background: rgba(255,255,255,0.04);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(247,151,30,0.12);
        border-radius: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    }
    #currentGuestCard .guest-photo,
    #currentGuestCard .guest-photo-default {
        width: clamp(180px, 18vw, 340px);
        height: clamp(180px, 18vw, 340px);
        margin-bottom: 1.5vh;
        flex-shrink: 0;
        border-radius: 50%;
        border: 5px solid rgba(247,151,30,0.5);
        box-shadow: 0 0 80px rgba(247,151,30,0.25);
        transition: all 0.4s ease;
    }
    #currentGuestCard .guest-photo { object-fit: cover; }
    #currentGuestCard .guest-photo-default {
        background: linear-gradient(135deg, #f7971e, #ffd200);
        display: flex; align-items: center; justify-content: center;
        color: white;
        font-size: clamp(60px, 6vw, 120px);
        font-weight: 700;
    }
    #currentGuestCard .guest-name {
        font-family: 'Playfair Display', serif;
        font-size: clamp(26px, 2.6vw, 52px);
        font-weight: 700;
        color: white;
        line-height: 1.15;
        margin-bottom: 0.8vh;
    }
    #currentGuestCard .guest-event {
        color: rgba(255,255,255,0.45);
        font-size: clamp(12px, 1vw, 18px);
        font-weight: 300;
        margin-bottom: 0.6vh;
    }
    #currentGuestCard .guest-event .icon { color: #ffd200; margin-right: 6px; }
    #currentGuestCard .guest-event .nb-pers { color: rgba(255,255,255,0.25); font-size: 0.85em; margin-left: 6px; }
    #currentGuestCard .guest-status {
        color: #34d399;
        background: rgba(52,211,153,0.1);
        border: 1px solid rgba(52,211,153,0.15);
        border-radius: 50px;
        padding: 0.7vh 2vw;
        margin-top: 1vh;
        font-size: clamp(12px, 1vw, 17px);
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    #currentGuestCard .guest-status .icon { font-size: 1.2em; }

    .slide-position {
        color: rgba(255,255,255,0.2);
        font-size: clamp(12px, 1vw, 16px);
        font-weight: 300;
        margin-top: 1.5vh;
        letter-spacing: 0.1em;
    }
    .slide-position .count { color: #ffd200; font-weight: 700; font-size: 1.3em; }
    .slide-position .total { color: rgba(255,255,255,0.15); }

    /* === TRANSITIONS === */
    .fade-in  { animation: fadeInGuest  0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
    .fade-out { animation: fadeOutGuest 0.7s cubic-bezier(0.55, 0.085, 0.68, 0.53) both; }
    @keyframes fadeInGuest  { 0% { opacity: 0; transform: scale(0.94) translateY(16px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes fadeOutGuest { 0% { opacity: 1; transform: scale(1) translateY(0); } 100% { opacity: 0; transform: scale(0.96) translateY(-12px); } }

    /* === ÉTAT VIDE === */
    .waiting-state {
        text-align: center;
        padding: 4vh;
    }
    .waiting-state .icon {
        font-size: clamp(50px, 5vw, 90px);
        color: #f7971e;
        display: block;
        margin-bottom: 2vh;
        animation: pulseIcon 2.5s ease-in-out infinite;
    }
    @keyframes pulseIcon {
        0%, 100% { transform: scale(1); opacity: 0.7; }
        50%      { transform: scale(1.08); opacity: 1; }
    }
    .waiting-state h3 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(22px, 2vw, 36px);
        font-weight: 700;
        color: white;
        margin-bottom: 1vh;
    }
    .waiting-state p {
        color: rgba(255,255,255,0.4);
        font-size: clamp(13px, 1vw, 18px);
        font-weight: 300;
    }
    .waiting-state .spinner {
        display: inline-block;
        width: 40px; height: 40px;
        border: 3px solid rgba(255,255,255,0.06);
        border-top-color: #f7971e;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-top: 2vh;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* ============================================
       INDICATEUR CONNEXION
       ============================================ */
    .connection-status {
        position: fixed;
        bottom: 2vh;
        left: 2vw;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        color: rgba(255,255,255,0.3);
        background: rgba(0,0,0,0.35);
        padding: 6px 14px;
        border-radius: 100px;
        backdrop-filter: blur(8px);
        z-index: 50;
        letter-spacing: 0.05em;
    }
    .connection-status .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #f59e0b;
        animation: pulseSoft 1.5s ease-in-out infinite;
    }
    .connection-status.connected .dot { background: #10b981; }
    @keyframes pulseSoft {
        0%, 100% { opacity: 0.85; transform: scale(1); }
        50%      { opacity: 1; transform: scale(1.2); }
    }

    /* ============================================
       COMPTEUR BAS DROITE
       ============================================ */
    .refresh-counter {
        position: fixed;
        bottom: 2vh;
        right: 2vw;
        font-size: 11px;
        color: rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.35);
        padding: 6px 14px;
        border-radius: 100px;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.03);
        font-weight: 300;
        letter-spacing: 0.05em;
        z-index: 50;
    }
    .refresh-counter .count { color: #f7971e; font-weight: 700; font-size: 1.15em; margin: 0 4px; }

    /* ============================================
       RESPONSIVE (écrans secondaires)
       ============================================ */
    @media (max-width: 1024px) {
        .hero-content {
            grid-template-columns: 1fr;
            grid-template-rows: auto auto auto;
            padding: 2vh 3vw;
            gap: 1.5vh;
            overflow-y: auto;
        }
        .center-divider {
            width: 70%;
            height: 1px;
            justify-self: center;
            align-self: center;
            margin: 1vh 0;
            background: linear-gradient(to right, transparent, rgba(255,210,0,0.5), transparent);
        }
        .center-divider::before,
        .center-divider::after { display: none; }

        .left-column,
        .right-column { height: auto; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        .wave, .particle-heart, .floating-heart, .host-photo-wrapper { display: none; }
    }
</style>
</head>
<body>

<div class="hero-bg"></div>

<div class="waves-container">
    <div class="wave"></div>
    <div class="wave"></div>
    <div class="wave"></div>
    <div class="wave"></div>
</div>

<div class="particles-container" id="particlesContainer"></div>

<div class="hero-content">

    <!-- ============ COLONNE GAUCHE : HÔTES ============ -->
    <div class="left-column">

        <!-- Titre événement -->
        <div class="event-title-wrapper">
            <div class="title-hearts">
                <span class="floating-heart" style="--delay: 0s;   --x: 10%;">❤</span>
                <span class="floating-heart" style="--delay: 0.5s; --x: 25%;">💕</span>
                <span class="floating-heart" style="--delay: 1s;   --x: 40%;">❤</span>
                <span class="floating-heart" style="--delay: 1.5s; --x: 55%;">💕</span>
                <span class="floating-heart" style="--delay: 2s;   --x: 70%;">❤</span>
                <span class="floating-heart" style="--delay: 2.5s; --x: 85%;">💕</span>
            </div>
            <h1 class="event-title">
                <span class="highlight"><?php echo htmlspecialchars($eventDisplayName, ENT_QUOTES, 'UTF-8'); ?></span>
            </h1>
        </div>

        <!-- Photo des hôtes -->
        <div class="host-photo-slider">
            <div class="host-photo-wrapper" id="hostPhotoWrapper">
                <?php if (!empty($hostPhotoUrls) && $hostPhotoUrls[0] !== 'default'): ?>
                    <img src="<?php echo htmlspecialchars($hostPhotoUrls[0], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Hôtes"
                         class="host-photo-fade-in"
                         id="hostPhotoImg"
                         onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'logo-placeholder\'>❤</div>'">
                <?php else: ?>
                    <div class="logo-placeholder" id="hostPhotoPlaceholder">❤</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="host-photo-indicator" id="hostPhotoIndicator">
            <?php for ($i = 0; $i < count($hostPhotoUrls); $i++): ?>
                <span class="host-photo-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>

        <div class="divider">
            <span class="line"></span>
            <span class="diamond">◆</span>
            <span class="line"></span>
        </div>

        <p class="welcome-message">
            <span class="heart-icon">❤</span>
            Nous sommes ravis de vous accueillir<br>
            pour ce moment unique et inoubliable.
            <span class="heart-icon">❤</span>
        </p>

        <?php if ($evenementInfo && !empty($evenementInfo['date_evenement'])): ?>
            <div class="event-date">
                <span class="icon">📅</span>
                <span>
                    <strong><?php echo htmlspecialchars($evenementInfo['lieu'] ?? 'Événement', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="sep">•</span>
                    <?php echo date('d F Y', strtotime($evenementInfo['date_evenement'])); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============ SÉPARATEUR ============ -->
    <div class="center-divider"></div>

    <!-- ============ COLONNE DROITE : INVITÉ ============ -->
    <div class="right-column">

        <div class="logo-badge">
            <span class="star">★</span>
            Bienvenue
            <span class="star">★</span>
        </div>

        <div id="guestsGrid" class="guest-slider">
            <?php if (empty($invitesPresents)): ?>
                <div class="waiting-state">
                    <span class="icon">⏳</span>
                    <h3>En attente de présence</h3>
                    <p>Aucun invité n'est encore enregistré comme présent.</p>
                    <div class="spinner"></div>
                </div>
            <?php else: ?>
                <div id="currentGuestCard"></div>
                <div class="slide-position" id="slidePosition"></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="connection-status" id="connectionStatus">
    <span class="dot"></span>
    <span id="connectionText">Connexion…</span>
</div>

<div class="refresh-counter">
    🔄 Mise à jour dans <span class="count" id="countdown">5</span> s
</div>

<script>
// ============================================================
// CŒURS FLOTTANTS DYNAMIQUES
// ============================================================
(function generateHearts() {
    const container = document.getElementById('particlesContainer');
    if (!container) return;
    for (let i = 0; i < 15; i++) {
        const h = document.createElement('div');
        h.className = 'particle-heart';
        h.textContent = ['❤', '💕', '💖', '💗'][Math.floor(Math.random() * 4)];
        h.style.left = Math.random() * 100 + '%';
        h.style.animationDuration = (15 + Math.random() * 12) + 's';
        h.style.animationDelay = (Math.random() * 10) + 's';
        h.style.fontSize = (14 + Math.random() * 14) + 'px';
        container.appendChild(h);
    }
})();

// ============================================================
// DIAPORAMA PHOTO HÔTES
// ============================================================
let hostPhotos = <?php echo json_encode($hostPhotoUrls, JSON_UNESCAPED_SLASHES); ?>;
let currentHostPhotoIndex = 0;
let hostPhotoTimer = null;
const hostPhotoDuration = 4000;

function updateHostPhoto(index, withAnimation = true) {
    const img         = document.getElementById('hostPhotoImg');
    const placeholder = document.getElementById('hostPhotoPlaceholder');
    const dots        = document.querySelectorAll('.host-photo-dot');

    dots.forEach((dot, i) => dot.classList.toggle('active', i === index));

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

function nextHostPhoto() {
    if (hostPhotos.length <= 1 || hostPhotos[0] === 'default') return;
    currentHostPhotoIndex = (currentHostPhotoIndex + 1) % hostPhotos.length;
    updateHostPhoto(currentHostPhotoIndex, true);
}

function startHostPhotoSlideshow() {
    clearInterval(hostPhotoTimer);
    if (hostPhotos.length > 1 && hostPhotos[0] !== 'default') {
        hostPhotoTimer = setInterval(nextHostPhoto, hostPhotoDuration);
    }
}

if (hostPhotos.length > 0 && hostPhotos[0] !== 'default') {
    updateHostPhoto(0, false);
    startHostPhotoSlideshow();
}

// ============================================================
// COMPTEUR D'ACTUALISATION
// ============================================================
let countdown = 5;
const countdownEl = document.getElementById('countdown');
if (countdownEl) {
    setInterval(() => {
        countdown--;
        if (countdown < 0) countdown = 5;
        countdownEl.textContent = countdown;
    }, 1000);
}

// ============================================================
// CONFETTIS
// ============================================================
function createConfetti() {
    const container = document.createElement('div');
    container.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;overflow:hidden';
    document.body.appendChild(container);

    const colors = ['#f7971e', '#ffd200', '#ff6b6b', '#4facfe', '#38ef7d', '#f093fb', '#a18cd1', '#ff4757',
        '#00f2fe', '#ff6348', '#7bed9f', '#ffd93d'];

    for (let i = 0; i < 45; i++) {
        const c       = document.createElement('div');
        const color   = colors[Math.floor(Math.random() * colors.length)];
        const size    = Math.random() * 12 + 4;
        const left    = Math.random() * 100;
        const dur     = Math.random() * 4 + 2.5;
        const delay   = Math.random() * 2.5;
        const shapes  = ['50%', '2px', '0px'];
        const shape   = shapes[Math.floor(Math.random() * shapes.length)];

        c.style.cssText = `
            position:absolute;
            left:${left}%;
            top:-20px;
            width:${size}px;
            height:${size * (0.8 + Math.random() * 0.6)}px;
            background:${color};
            border-radius:${shape};
            animation:confettiFall ${dur}s ease-in ${delay}s forwards;
            transform:rotate(${Math.random() * 360}deg);
            box-shadow:0 2px 8px rgba(0,0,0,0.15);
        `;
        container.appendChild(c);
    }

    if (!document.getElementById('confettiStyle')) {
        const s = document.createElement('style');
        s.id = 'confettiStyle';
        s.textContent = `
            @keyframes confettiFall {
                0%   { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
                100% { transform: translateY(110vh) rotate(${Math.random() > 0.5 ? '720' : '-540'}deg) scale(0.3); opacity: 0; }
            }
        `;
        document.head.appendChild(s);
    }

    setTimeout(() => container.remove(), 7000);
}

<?php if (!empty($invitesPresents)): ?>
    setTimeout(createConfetti, 400);
    setInterval(createConfetti, 9000);
<?php endif; ?>

// ============================================================
// DIAPORAMA INVITÉS
// ============================================================
const evenementId = <?php echo (int)$evenementId; ?>;
let presentGuests = <?php echo json_encode($invitesPresents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let currentGuestIndex = 0;
let slideTimer = null;
let isTransitioning = false;

const slideDuration      = 5000;
const transitionDuration = 700;

const guestsGrid        = document.getElementById('guestsGrid');
const currentGuestCard  = document.getElementById('currentGuestCard');
const slidePosition     = document.getElementById('slidePosition');

function escapeHtml(v) {
    const d = document.createElement('div');
    d.textContent = v ?? '';
    return d.innerHTML;
}

function getInitials(p, n) {
    return ((p || '').substring(0, 1) + (n || '').substring(0, 1)).toUpperCase();
}

function getGuestKey(inv) {
    return String(inv.invitation_id || inv.invite_id || `${inv.prenom}-${inv.nom}`);
}

function getInvitePhotoUrl(photo) {
    if (!photo) return '';
    return `<?php echo htmlspecialchars(APP_URL . '/uploads/photos/', ENT_QUOTES, 'UTF-8'); ?>${encodeURIComponent(photo)}`;
}

function buildGuestCard(invite, anim = 'fade-in') {
    const nomComplet = `${invite.prenom || ''} ${invite.nom || ''}`.trim();
    const initiales  = getInitials(invite.prenom, invite.nom);

    let photoHtml = `<div class="guest-photo-default">${escapeHtml(initiales || '??')}</div>`;

    if (invite.photo) {
        const url = getInvitePhotoUrl(invite.photo);
        photoHtml = `
            <img src="${url}" alt="Photo invité" class="guest-photo"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="guest-photo-default" style="display:none;">${escapeHtml(initiales || '??')}</div>
        `;
    }

    const nbPresents = invite.nb_presents ? ` <span class="nb-pers">• ${invite.nb_presents} pers.</span>` : '';

    return `
        <div class="guest-card ${anim}">
            ${photoHtml}
            <div class="guest-name">${escapeHtml(nomComplet)}</div>
            <div class="guest-event">
                <span class="icon">📅</span>
                ${escapeHtml(invite.evenement_nom || '')}
                ${nbPresents}
            </div>
            <div class="guest-status">
                <span class="icon">✓</span>
                Présent(e)
            </div>
        </div>
    `;
}

function renderEmptyState() {
    guestsGrid.innerHTML = `
        <div class="waiting-state">
            <span class="icon">⏳</span>
            <h3>En attente de présence</h3>
            <p>Aucun invité n'est encore enregistré comme présent.</p>
            <div class="spinner"></div>
        </div>
    `;
}

function updatePosition() {
    if (slidePosition && presentGuests.length > 0) {
        slidePosition.innerHTML =
            `<span class="count">${currentGuestIndex + 1}</span> <span class="total">/ ${presentGuests.length}</span>`;
    } else if (slidePosition) {
        slidePosition.innerHTML = '';
    }
}

function showGuest(index, withAnim = true) {
    if (!currentGuestCard || presentGuests.length === 0) return;
    currentGuestIndex = (index + presentGuests.length) % presentGuests.length;
    currentGuestCard.innerHTML = buildGuestCard(presentGuests[currentGuestIndex], withAnim ? 'fade-in' : '');
    updatePosition();
}

function nextGuest() {
    if (presentGuests.length <= 1 || isTransitioning) return;
    isTransitioning = true;
    const oldCard = currentGuestCard.querySelector('.guest-card');
    if (oldCard) {
        oldCard.classList.remove('fade-in');
        oldCard.classList.add('fade-out');
    }
    setTimeout(() => {
        showGuest(currentGuestIndex + 1, true);
        isTransitioning = false;
    }, transitionDuration);
}

function startSlideshow() {
    clearInterval(slideTimer);
    if (presentGuests.length > 1) {
        slideTimer = setInterval(nextGuest, slideDuration);
    }
}

function updateGuests(newGuests) {
    const oldKeys    = new Set(presentGuests.map(g => getGuestKey(g)));
    const currentKey = presentGuests.length > 0 ? getGuestKey(presentGuests[currentGuestIndex]) : null;

    presentGuests = Array.isArray(newGuests) ? newGuests : [];

    if (presentGuests.length === 0) {
        currentGuestIndex = 0;
        clearInterval(slideTimer);
        renderEmptyState();
        return;
    }

    const sameIdx = currentKey ? presentGuests.findIndex(g => getGuestKey(g) === currentKey) : -1;
    currentGuestIndex = sameIdx >= 0 ? sameIdx : 0;

    const hasNew = presentGuests.some(g => !oldKeys.has(getGuestKey(g)));
    if (hasNew) {
        currentGuestIndex = 0;
        showGuest(0, true);
        startSlideshow();
        setTimeout(createConfetti, 300);
    } else {
        showGuest(currentGuestIndex, false);
        startSlideshow();
    }
}

// ============================================================
// CHARGEMENT AJAX (avec statut connexion)
// ============================================================
let isLoading = false;

function updateConnectionStatus(connected) {
    const el   = document.getElementById('connectionStatus');
    const text = document.getElementById('connectionText');
    if (connected) {
        el.classList.add('connected');
        text.textContent = 'En ligne';
    } else {
        el.classList.remove('connected');
        text.textContent = 'Hors ligne';
    }
}

function loadPresentGuests() {
    if (isLoading) return;
    isLoading = true;

    const url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    if (evenementId > 0) url.searchParams.set('evenement', evenementId);

    fetch(url.toString(), { cache: 'no-store', headers: { 'Accept': 'application/json' } })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(data => {
            updateConnectionStatus(true);
            if (data.success) {
                if (data.host_photos && Array.isArray(data.host_photos) && data.host_photos.length > 0) {
                    const newHostPhotos = data.host_photos;
                    if (JSON.stringify(hostPhotos) !== JSON.stringify(newHostPhotos)) {
                        const indicator = document.getElementById('hostPhotoIndicator');
                        if (indicator) {
                            let dotsHtml = '';
                            newHostPhotos.forEach((photo, i) => {
                                dotsHtml += `<span class="host-photo-dot ${i === 0 ? 'active' : ''}" data-index="${i}"></span>`;
                            });
                            indicator.innerHTML = dotsHtml;
                        }
                        if (newHostPhotos.length > 0 && newHostPhotos[0] !== 'default') {
                            const img = document.getElementById('hostPhotoImg');
                            if (img) {
                                img.src = newHostPhotos[0];
                                img.style.display = 'block';
                                const placeholder = document.getElementById('hostPhotoPlaceholder');
                                if (placeholder) placeholder.style.display = 'none';
                            }
                            clearInterval(hostPhotoTimer);
                            currentHostPhotoIndex = 0;
                            hostPhotos = newHostPhotos;
                            if (newHostPhotos.length > 1) {
                                hostPhotoTimer = setInterval(nextHostPhoto, hostPhotoDuration);
                            }
                        }
                    }
                }
                updateGuests(data.invites);
            }
        })
        .catch(e => {
            console.error('Erreur AJAX splash :', e);
            updateConnectionStatus(false);
        })
        .finally(() => { isLoading = false; });
}

// Initialisation
if (presentGuests.length > 0) {
    showGuest(0, false);
    startSlideshow();
}

// Polling toutes les 5s
setInterval(loadPresentGuests, 5000);

// ============================================================
// PLEIN ÉCRAN AUTO (au premier clic, car les navigateurs bloquent)
// ============================================================
document.addEventListener('click', function requestFs() {
    if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    }
    document.removeEventListener('click', requestFs);
});

// ============================================================
// MASQUER LE CURSEUR (kiosque)
// ============================================================
let cursorTimer;
document.addEventListener('mousemove', function() {
    document.body.style.cursor = 'default';
    clearTimeout(cursorTimer);
    cursorTimer = setTimeout(() => {
        document.body.style.cursor = 'none';
    }, 3000);
});

// ============================================================
// RECHARGE AUTO EN CAS D'ERREUR DE FOND
// ============================================================
window.addEventListener('error', function(e) {
    console.error('Erreur détectée :', e.message);
}, true);
</script>

</body>
</html>