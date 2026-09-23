<?php
/**
 * ============================================================
 * TEMPLATE : FLORAL / SAVE THE DATE — v4
 * ============================================================
 * 
 * Nouveautés v4 :
 * - QR code INTÉGRÉ dans la carte principale
 * - Téléchargement = 1 seule carte (infos + QR)
 * 
 * ============================================================
 */

$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);

$hostParts = preg_split('/\s+(?:et|&)\s+/i', $host1);
$hostName1 = trim($hostParts[0] ?? $host1);
$hostName2 = trim($hostParts[1] ?? '');

$rsvpLabel = 'En attente';
$rsvpClass = 'pending';
if (($invitation['statut'] ?? '') === 'CONFIRMEE') {
    $rsvpLabel = '✓ Confirmé';
    $rsvpClass = 'confirmed';
} elseif (($invitation['statut'] ?? '') === 'REFUSEE') {
    $rsvpLabel = '✗ Refusé';
    $rsvpClass = 'refused';
}

$invitationMessage = trim($invitation['commentaire'] ?? $invitation['message'] ?? '');
$mainPhoto = $pageBackground ?: ($hasPhotos ? getPhotoUrl($photosHost[0]['photo']) : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Great+Vibes&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --rose: #e8b4b8;
            --rose-light: #f5d5d8;
            --rose-dark: #c98b8f;
            --rose-deep: #a86a6e;
            --rose-pale: #faf0ee;
            --cream: #faf6f1;
            --cream-2: #f5efe8;
            --text: #2a2420;
            --text-light: #6a5a4a;
            --text-muted: #9a8a7a;
            --gold: #c9a961;
            --gold-light: #e8d5a0;
            --leaf: #a8b89a;
            --leaf-dark: #7a8a6a;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: var(--cream);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            position: relative;
        }
        
        .bg-ornaments {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-flower {
            position: absolute;
            opacity: 0.06;
            color: var(--rose-dark);
        }
        
        .bg-flower.f1 { top: 5%; left: 3%; font-size: 180px; transform: rotate(-15deg); }
        .bg-flower.f2 { top: 30%; right: 5%; font-size: 220px; transform: rotate(25deg); }
        .bg-flower.f3 { top: 60%; left: 2%; font-size: 160px; transform: rotate(-30deg); }
        .bg-flower.f4 { bottom: 5%; right: 3%; font-size: 200px; transform: rotate(15deg); }
        
        .butterfly {
            position: fixed;
            z-index: 1;
            pointer-events: none;
            color: var(--rose-dark);
            opacity: 0.4;
            font-size: 24px;
        }
        
        .butterfly.b1 { top: 15%; left: 8%; animation: floatButterfly1 12s ease-in-out infinite; }
        .butterfly.b2 { top: 45%; right: 10%; animation: floatButterfly2 14s ease-in-out infinite; font-size: 20px; }
        .butterfly.b3 { top: 75%; left: 12%; animation: floatButterfly1 16s ease-in-out infinite; font-size: 28px; }
        
        @keyframes floatButterfly1 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(30px, -20px) rotate(10deg); }
            50% { transform: translate(0, -40px) rotate(-5deg); }
            75% { transform: translate(-30px, -20px) rotate(8deg); }
        }
        
        @keyframes floatButterfly2 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-40px, 20px) rotate(-12deg); }
            66% { transform: translate(20px, 40px) rotate(8deg); }
        }
        
        .gold-particle {
            position: fixed;
            width: 6px;
            height: 6px;
            background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.5;
        }
        
        .gold-particle.p1 { top: 20%; left: 20%; animation: floatParticle 8s ease-in-out infinite; }
        .gold-particle.p2 { top: 40%; right: 25%; animation: floatParticle 10s ease-in-out infinite 2s; width: 8px; height: 8px; }
        .gold-particle.p3 { top: 65%; left: 15%; animation: floatParticle 12s ease-in-out infinite 4s; }
        .gold-particle.p4 { top: 80%; right: 20%; animation: floatParticle 9s ease-in-out infinite 1s; width: 5px; height: 5px; }
        
        @keyframes floatParticle {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            50% { transform: translate(20px, -30px) scale(1.4); opacity: 0.7; }
        }
        
        .std-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            animation: loaderFadeOut 1.2s ease-in-out 2.2s forwards;
        }
        
        @keyframes loaderFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .loader-wreath {
            width: 160px;
            height: 160px;
            opacity: 0;
            animation: wreathIn 1.5s ease-out 0.3s forwards;
            color: var(--text);
        }
        
        @keyframes wreathIn {
            0% { opacity: 0; transform: scale(0.5) rotate(-45deg); }
            100% { opacity: 1; transform: scale(1) rotate(0); }
        }
        
        .loader-text {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            color: var(--text);
            margin-top: 20px;
            opacity: 0;
            animation: textIn 0.8s ease-out 1.2s forwards;
        }
        
        @keyframes textIn {
            to { opacity: 1; }
        }
        
        /* ============================================
           HERO
           ============================================ */
        .std-hero {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .std-hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            <?php if ($mainPhoto): ?>
            background-image: url('<?php echo htmlspecialchars($mainPhoto); ?>');
            <?php endif; ?>
            animation: kenBurns 25s ease-in-out infinite alternate;
            z-index: 0;
        }
        
        @keyframes kenBurns {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        
        .std-hero-overlay {
            position: absolute;
            inset: 0;
            background: 
                linear-gradient(180deg, rgba(0,0,0,0.15) 0%, transparent 30%, transparent 60%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
        }
        
        .hero-floral-garland {
            position: absolute;
            left: 0;
            right: 0;
            height: 120px;
            color: white;
            opacity: 0.8;
            z-index: 2;
            pointer-events: none;
        }
        .hero-floral-garland.top { top: 0; }
        .hero-floral-garland.bottom { bottom: 0; transform: scaleY(-1); }
        
        .std-hero-content {
            position: relative;
            z-index: 3;
            text-align: center;
            width: 100%;
            max-width: 600px;
            padding: 20px;
        }
        
        .std-hero-names {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 10vw, 88px);
            color: white;
            line-height: 1;
            margin-bottom: 40px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.6), 0 0 60px rgba(0, 0, 0, 0.4);
            opacity: 0;
            animation: heroNameIn 1.5s ease-out 2.5s forwards;
        }
        
        @keyframes heroNameIn {
            0% { opacity: 0; transform: translateY(-30px); filter: blur(10px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        
        .std-wreath {
            position: relative;
            width: 280px;
            height: 280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            animation: wreathFadeIn 1.8s ease-out 3s forwards;
        }
        
        @keyframes wreathFadeIn {
            0% { opacity: 0; transform: scale(0.7); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .std-wreath-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            color: white;
            filter: drop-shadow(0 4px 20px rgba(0, 0, 0, 0.5));
            animation: wreathRotate 60s linear infinite;
        }
        
        @keyframes wreathRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .std-wreath-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .std-save {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            letter-spacing: 0.2em;
            font-weight: 500;
            color: white;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
        }
        
        .std-the {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 12px 0;
            color: white;
        }
        
        .std-the-line {
            width: 30px;
            height: 1px;
            background: white;
        }
        
        .std-the-text {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-style: italic;
            letter-spacing: 0.1em;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
        }
        
        .std-date {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            letter-spacing: 0.15em;
            font-weight: 500;
            color: white;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
        }
        
        .std-hero-date-bottom {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-style: italic;
            letter-spacing: 0.1em;
            color: white;
            margin-top: 40px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
            opacity: 0;
            animation: heroNameIn 1.5s ease-out 3.5s forwards;
        }
        
        .std-scroll {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 24px;
            animation: scrollBounce 2s ease-in-out infinite;
            opacity: 0;
            animation: scrollBounce 2s ease-in-out infinite, textIn 1s ease-out 4s forwards;
            z-index: 3;
        }
        
        @keyframes scrollBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(10px); }
        }
        
        .std-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .std-reveal.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* ============================================
           WRAPPER TÉLÉCHARGEMENT (une seule carte)
           ============================================ */
        #stdDownloadCard {
            position: relative;
            padding: 40px 0;
        }
        
        .std-section {
            position: relative;
            padding: 80px 24px;
            background: 
                radial-gradient(circle at 15% 15%, rgba(232, 180, 184, 0.18) 0%, transparent 35%),
                radial-gradient(circle at 85% 85%, rgba(232, 180, 184, 0.15) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, rgba(168, 184, 154, 0.08) 0%, transparent 50%),
                var(--cream);
            overflow: hidden;
        }
        
        .std-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(ellipse 300px 200px at 0% 0%, rgba(232, 180, 184, 0.25) 0%, transparent 60%),
                radial-gradient(ellipse 250px 180px at 100% 100%, rgba(232, 180, 184, 0.2) 0%, transparent 60%),
                radial-gradient(ellipse 200px 150px at 100% 0%, rgba(168, 184, 154, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse 200px 150px at 0% 100%, rgba(168, 184, 154, 0.12) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }
        
        .std-section-content {
            max-width: 720px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* ============================================
           CARTE PRINCIPALE (avec QR intégré)
           ============================================ */
        .std-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(232, 180, 184, 0.5);
            border-radius: 4px;
            padding: 80px 55px 70px;
            box-shadow: 
                0 20px 60px rgba(201, 139, 143, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset,
                0 0 0 8px rgba(255, 255, 255, 0.3),
                0 0 0 9px rgba(232, 180, 184, 0.3),
                0 0 100px rgba(232, 180, 184, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        @media (max-width: 480px) {
            .std-card { padding: 55px 22px 45px; }
        }
        
        .card-garland-top {
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 600px;
            height: 100px;
            color: var(--rose-dark);
            opacity: 0.7;
            pointer-events: none;
            z-index: 2;
        }
        
        .card-garland-bottom {
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%) scaleY(-1);
            width: 100%;
            max-width: 600px;
            height: 100px;
            color: var(--rose-dark);
            opacity: 0.7;
            pointer-events: none;
            z-index: 2;
        }
        
        .std-corner-floral {
            position: absolute;
            width: 160px;
            height: 160px;
            pointer-events: none;
            color: var(--rose-dark);
            opacity: 0.65;
            z-index: 0;
        }
        
        .std-corner-floral.tl { top: 0; left: 0; }
        .std-corner-floral.tr { top: 0; right: 0; transform: scaleX(-1); }
        .std-corner-floral.bl { bottom: 0; left: 0; transform: scaleY(-1); }
        .std-corner-floral.br { bottom: 0; right: 0; transform: scale(-1); }
        
        @media (max-width: 480px) {
            .std-corner-floral { width: 90px; height: 90px; opacity: 0.5; }
        }
        
        .card-watermark-flower {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 400px;
            color: var(--rose-dark);
            opacity: 0.035;
            pointer-events: none;
            z-index: 0;
            line-height: 1;
        }
        
        @media (max-width: 480px) {
            .card-watermark-flower { font-size: 250px; }
        }
        
        .std-card > *:not(.std-corner-floral):not(.std-card-heart):not(.card-garland-top):not(.card-garland-bottom):not(.card-watermark-flower) {
            position: relative;
            z-index: 3;
        }
        
        .std-card-heart {
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--cream);
            padding: 0 24px;
            color: var(--rose-dark);
            font-size: 26px;
            z-index: 4;
            text-shadow: 0 2px 8px rgba(201, 139, 143, 0.3);
        }
        
        .std-recipient {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .std-recipient-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--rose-deep);
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .std-recipient-name::before,
        .std-recipient-name::after {
            content: '❦';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--rose-dark);
            font-size: 16px;
            opacity: 0.6;
        }
        .std-recipient-name::before { left: -30px; }
        .std-recipient-name::after { right: -30px; }
        
        .std-ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 25px 0 30px;
            color: var(--rose-dark);
            opacity: 0.85;
            position: relative;
        }
        
        .std-ornament svg {
            width: 100%;
            max-width: 500px;
            height: 50px;
        }
        
        .std-message {
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            line-height: 1.9;
            color: var(--text);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .std-message strong {
            font-weight: 700;
            color: var(--text);
        }
        
        .std-table {
            text-align: center;
            margin: 30px 0;
            padding: 18px 28px;
            background: linear-gradient(135deg, rgba(232, 180, 184, 0.15), rgba(232, 180, 184, 0.08));
            border-radius: 4px;
            display: inline-block;
            min-width: 200px;
            border: 1px solid rgba(232, 180, 184, 0.5);
            position: relative;
        }
        
        .std-table::before,
        .std-table::after {
            content: '❀';
            position: absolute;
            color: var(--rose-dark);
            font-size: 14px;
            opacity: 0.5;
            top: 50%;
            transform: translateY(-50%);
        }
        .std-table::before { left: 8px; }
        .std-table::after { right: 8px; }
        
        .std-table-label {
            font-family: 'Cormorant Garamond', serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        
        .std-table-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.05em;
        }
        
        /* ============================================
           SECTION QR INTÉGRÉE (dans la même carte)
           ============================================ */
        .std-qr-inline {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px dashed rgba(232, 180, 184, 0.5);
            text-align: center;
        }
        
        .std-qr-inline-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .std-qr-inline-title::before,
        .std-qr-inline-title::after {
            content: '❦';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--rose-dark);
            font-size: 16px;
            opacity: 0.5;
        }
        .std-qr-inline-title::before { left: -30px; }
        .std-qr-inline-title::after { right: -30px; }
        
        .std-qr-wrapper { text-align: center; }
        
        .std-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 1px solid rgba(232, 180, 184, 0.5);
            border-radius: 12px;
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.08),
                0 0 0 8px rgba(232, 180, 184, 0.15);
            position: relative;
        }
        
        .std-qr-box::before,
        .std-qr-box::after {
            content: '❀';
            position: absolute;
            color: var(--rose-dark);
            font-size: 24px;
            opacity: 0.6;
        }
        
        .std-qr-box::before { top: -16px; left: -12px; }
        .std-qr-box::after { bottom: -16px; right: -12px; }
        
        .std-qr-label {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            margin-top: 20px;
        }
        
        /* ============================================
           BOUTONS
           ============================================ */
        .std-btn-download {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 20px auto;
            padding: 14px 32px;
            background: linear-gradient(135deg, #e8a4a8 0%, #d88a8e 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(232, 164, 168, 0.4);
        }
        
        .std-btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(232, 164, 168, 0.6);
        }
        
        .std-btn-address {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #5a5a5a;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            font-weight: 500;
            letter-spacing: 0.03em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(90, 90, 90, 0.3);
        }
        
        .std-btn-address:hover {
            background: #4a4a4a;
            transform: translateY(-2px);
            color: white;
        }
        
        /* ============================================
           PRÉFÉRENCES BOISSONS
           ============================================ */
        .std-drinks { text-align: center; }
        
        .std-drinks-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .std-drinks-title::before,
        .std-drinks-title::after {
            content: '❦';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--rose-dark);
            font-size: 18px;
            opacity: 0.5;
        }
        .std-drinks-title::before { left: -36px; }
        .std-drinks-title::after { right: -36px; }
        
        .std-drinks-subtitle {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-style: italic;
            color: var(--text-light);
            margin-bottom: 12px;
        }
        
        .std-drinks-hint {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            color: var(--text-muted);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .std-drinks-category { margin-bottom: 24px; }
        
        .std-drinks-category-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 14px;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .std-drinks-category-title::before,
        .std-drinks-category-title::after {
            content: '';
            width: 30px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--rose-dark), transparent);
            opacity: 0.5;
        }
        
        .std-drinks-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }
        
        .std-drink {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            background: transparent;
            border: 1.5px solid rgba(232, 180, 184, 0.6);
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            color: var(--text);
        }
        
        .std-drink:hover {
            border-color: var(--rose-dark);
            background: rgba(232, 180, 184, 0.1);
            transform: translateY(-1px);
        }
        
        .std-drink.selected {
            background: var(--text);
            color: white;
            border-color: var(--text);
            font-weight: 600;
        }
        
        .std-drink .check {
            opacity: 0;
            width: 12px;
            font-size: 11px;
            transition: opacity 0.3s ease;
        }
        
        .std-drink.selected .check { opacity: 1; }
        
        .std-drinks-counter {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-style: italic;
            color: var(--text-muted);
            text-align: center;
            margin: 20px 0 10px;
        }
        
        .std-btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--rose) 0%, var(--rose-dark) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(232, 180, 184, 0.4);
        }
        
        .std-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(232, 180, 184, 0.6);
        }
        
        /* ============================================
           FORMULAIRE
           ============================================ */
        .std-form-group { margin-bottom: 20px; }
        
        .std-form-group label {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            font-size: 14px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .std-form-group input,
        .std-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.8);
            border: 1.5px solid rgba(232, 180, 184, 0.5);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            transition: all 0.3s ease;
            resize: vertical;
        }
        
        .std-form-group input:focus,
        .std-form-group textarea:focus {
            outline: none;
            border-color: var(--rose-dark);
            background: white;
            box-shadow: 0 0 0 4px rgba(232, 180, 184, 0.2);
        }
        
        .std-form-group textarea {
            min-height: 120px;
            font-style: italic;
            line-height: 1.7;
        }
        
        .std-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        @media (max-width: 480px) {
            .std-options-grid { grid-template-columns: 1fr; }
        }
        
        .std-option-radio { display: none; }
        
        .std-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.6);
            border: 1.5px solid rgba(232, 180, 184, 0.5);
            border-radius: 50px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .std-option-label:hover {
            border-color: var(--rose-dark);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .std-option-radio:checked + .std-option-label {
            border-color: var(--rose-dark);
            background: linear-gradient(135deg, rgba(232, 180, 184, 0.2), rgba(232, 180, 184, 0.1));
            color: var(--text);
            font-weight: 700;
        }
        
        .std-message-intro {
            text-align: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-style: italic;
            color: var(--text-light);
            line-height: 1.7;
            margin: 20px 0 16px;
        }
        
        .std-message-intro strong {
            color: var(--rose-deep);
            font-weight: 700;
        }
        
        .std-message-existing {
            background: linear-gradient(135deg, rgba(232, 180, 184, 0.12), rgba(232, 180, 184, 0.06));
            border-left: 3px solid var(--rose-dark);
            padding: 24px 28px;
            margin: 20px 0;
            text-align: left;
            font-style: italic;
            font-size: 17px;
            color: var(--text);
            line-height: 1.8;
            border-radius: 0 12px 12px 0;
            position: relative;
            box-shadow: 0 4px 16px rgba(232, 180, 184, 0.15);
        }
        
        .std-message-existing::before {
            content: '"';
            position: absolute;
            top: -16px;
            left: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 80px;
            color: var(--rose-dark);
            opacity: 0.3;
            line-height: 1;
        }
        
        .std-message-existing::after {
            content: '"';
            position: absolute;
            bottom: -36px;
            right: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 80px;
            color: var(--rose-dark);
            opacity: 0.3;
            line-height: 1;
        }
        
        /* ============================================
           DIAPORAMA PHOTOS
           ============================================ */
        .std-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: var(--cream-2);
            border-radius: 8px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.15),
                0 0 0 8px rgba(255, 255, 255, 0.6),
                0 0 0 9px rgba(232, 180, 184, 0.3);
            border: 1px solid rgba(232, 180, 184, 0.3);
        }
        
        .std-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .std-diaporama .slide.active { opacity: 1; }
        
        .std-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .std-diapo-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
        }
        
        .std-diapo-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: transparent;
            border: 1.5px solid var(--rose-dark);
            color: var(--rose-dark);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .std-diapo-btn:hover {
            background: var(--rose-dark);
            color: white;
            transform: scale(1.1);
        }
        
        .std-diapo-counter {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            letter-spacing: 0.2em;
            color: var(--text-light);
            min-width: 70px;
            text-align: center;
        }
        
        .std-diapo-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 16px;
        }
        
        .std-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(232, 180, 184, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .std-diapo-dots span.active {
            background: var(--rose-dark);
            transform: scale(1.3);
            box-shadow: 0 0 8px rgba(201, 139, 143, 0.5);
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        .std-footer {
            text-align: center;
            padding: 60px 24px 40px;
            background: var(--cream);
            border-top: 1px solid rgba(232, 180, 184, 0.3);
            position: relative;
        }
        
        .std-footer-ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .std-footer-ornament .line {
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--rose), transparent);
        }
        
        .std-footer-ornament i {
            color: var(--rose-dark);
            font-size: 16px;
        }
        
        .std-footer-app {
            font-family: 'Great Vibes', cursive;
            font-size: 40px;
            color: var(--text);
            margin-bottom: 8px;
        }
        
        .std-footer-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-size: 13px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .std-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            background: #25d366;
            color: white;
            border-radius: 9999px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
        }
        
        .std-whatsapp:hover {
            background: #128c7e;
            transform: translateY(-2px);
            color: white;
        }
        
        /* ============================================
           MESSAGES / ALERTES
           ============================================ */
        .std-alert {
            padding: 16px 24px;
            margin: 20px auto;
            max-width: 720px;
            font-size: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-left: 4px solid;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-family: 'Cormorant Garamond', serif;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .std-alert-success { border-color: #2d7a45; color: #2d7a45; }
        .std-alert-danger  { border-color: var(--rose-dark); color: var(--rose-dark); }
        .std-alert-warning { border-color: var(--gold); color: #8c6a3a; }
        
        /* ============================================
           BOUTON TÉLÉCHARGEMENT FLOTTANT
           ============================================ */
        #stdDownloadFloat {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #e8a4a8 0%, #d88a8e 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(232, 164, 168, 0.5);
            opacity: 0;
            visibility: hidden;
        }
        
        #stdDownloadFloat.visible {
            opacity: 1;
            visibility: visible;
        }
        
        #stdDownloadFloat:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 32px rgba(232, 164, 168, 0.7);
        }
        
        @media (max-width: 480px) {
            #stdDownloadFloat {
                bottom: 70px;
                right: 16px;
                padding: 12px 18px;
                font-size: 14px;
            }
        }
        
        #stdScrollTop {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--text);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        #stdScrollTop.visible {
            opacity: 1;
            visibility: visible;
        }
        
        #stdScrollTop:hover {
            background: var(--rose-dark);
            transform: translateY(-3px);
        }
        
        @media (max-width: 480px) {
            #stdScrollTop {
                bottom: 24px;
                right: 16px;
                width: 42px;
                height: 42px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-ornaments">
        <div class="bg-flower f1"><i class="fas fa-fan"></i></div>
        <div class="bg-flower f2"><i class="fas fa-spa"></i></div>
        <div class="bg-flower f3"><i class="fas fa-fan"></i></div>
        <div class="bg-flower f4"><i class="fas fa-spa"></i></div>
    </div>
    
    <div class="butterfly b1"><i class="fas fa-feather-alt"></i></div>
    <div class="butterfly b2"><i class="fas fa-leaf"></i></div>
    <div class="butterfly b3"><i class="fas fa-feather-alt"></i></div>
    
    <div class="gold-particle p1"></div>
    <div class="gold-particle p2"></div>
    <div class="gold-particle p3"></div>
    <div class="gold-particle p4"></div>

    <div class="std-loader">
        <svg class="loader-wreath" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5">
            <ellipse cx="50" cy="50" rx="35" ry="35" opacity="0.3"/>
            <ellipse cx="50" cy="50" rx="38" ry="38" opacity="0.2"/>
            <path d="M 50 12 Q 55 15 50 20 Q 45 15 50 12" fill="currentColor"/>
            <path d="M 88 50 Q 85 55 80 50 Q 85 45 88 50" fill="currentColor"/>
            <path d="M 50 88 Q 55 85 50 80 Q 45 85 50 88" fill="currentColor"/>
            <path d="M 12 50 Q 15 55 20 50 Q 15 45 12 50" fill="currentColor"/>
        </svg>
        <div class="loader-text">Save the date</div>
    </div>

    <!-- HERO -->
    <section class="std-hero" id="stdHero">
        <div class="std-hero-bg"></div>
        <div class="std-hero-overlay"></div>
        
        <svg class="hero-floral-garland top" viewBox="0 0 1200 120" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M 0 60 Q 100 20 200 60 T 400 60 T 600 60 T 800 60 T 1000 60 T 1200 60" opacity="0.6"/>
            <?php for ($i = 0; $i < 12; $i++): $x = 50 + ($i * 100); ?>
                <g transform="translate(<?php echo $x; ?>, 60)">
                    <ellipse cx="0" cy="-15" rx="8" ry="5" fill="currentColor" opacity="0.7"/>
                    <ellipse cx="0" cy="15" rx="8" ry="5" fill="currentColor" opacity="0.7"/>
                    <circle cx="0" cy="0" r="6" fill="currentColor" opacity="0.9"/>
                    <circle cx="0" cy="0" r="3" fill="white" opacity="0.5"/>
                </g>
            <?php endfor; ?>
        </svg>
        
        <svg class="hero-floral-garland bottom" viewBox="0 0 1200 120" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M 0 60 Q 100 20 200 60 T 400 60 T 600 60 T 800 60 T 1000 60 T 1200 60" opacity="0.6"/>
            <?php for ($i = 0; $i < 12; $i++): $x = 50 + ($i * 100); ?>
                <g transform="translate(<?php echo $x; ?>, 60)">
                    <ellipse cx="0" cy="-15" rx="8" ry="5" fill="currentColor" opacity="0.7"/>
                    <ellipse cx="0" cy="15" rx="8" ry="5" fill="currentColor" opacity="0.7"/>
                    <circle cx="0" cy="0" r="6" fill="currentColor" opacity="0.9"/>
                    <circle cx="0" cy="0" r="3" fill="white" opacity="0.5"/>
                </g>
            <?php endfor; ?>
        </svg>
        
        <div class="std-hero-content">
            <div class="std-hero-names"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></div>
            
            <div class="std-wreath">
                <svg class="std-wreath-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1">
                    <ellipse cx="100" cy="100" rx="85" ry="85" opacity="0.4"/>
                    <ellipse cx="100" cy="100" rx="80" ry="80" opacity="0.6"/>
                    <?php for ($i = 0; $i < 24; $i++): $angle = $i * 15; ?>
                        <g transform="rotate(<?php echo $angle; ?> 100 100)">
                            <path d="M 100 12 Q 106 20 100 30 Q 94 20 100 12 Z" fill="currentColor" opacity="0.85"/>
                            <path d="M 100 15 L 100 28" stroke="currentColor" stroke-width="0.5" opacity="0.6"/>
                        </g>
                    <?php endfor; ?>
                    <?php for ($i = 0; $i < 12; $i++): $angle = $i * 30 + 15; ?>
                        <circle cx="100" cy="20" r="2.5" fill="currentColor" opacity="0.7" transform="rotate(<?php echo $angle; ?> 100 100)"/>
                    <?php endfor; ?>
                    <ellipse cx="100" cy="100" rx="72" ry="72" opacity="0.3" stroke-dasharray="2 4"/>
                </svg>
                
                <div class="std-wreath-content">
                    <div class="std-save">SAVE</div>
                    <div class="std-the">
                        <div class="std-the-line"></div>
                        <span class="std-the-text">the</span>
                        <div class="std-the-line"></div>
                    </div>
                    <div class="std-date">DATE</div>
                </div>
            </div>
            
            <div class="std-hero-date-bottom"><?php echo htmlspecialchars($eventDate); ?></div>
        </div>
        
        <div class="std-scroll"><i class="fas fa-chevron-down"></i></div>
    </section>

    <?php if ($message): ?>
        <div class="std-alert std-alert-<?php echo htmlspecialchars($messageType); ?> std-reveal">
            <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- WRAPPER CAPTURÉ (UNE SEULE CARTE)          -->
    <!-- ============================================ -->
    <div id="stdDownloadCard">

        <section class="std-section std-reveal" id="stdDetails">
            <div class="std-section-content">
                
                <div class="std-card" id="stdCard">
                    
                    <div class="std-card-heart">♥</div>
                    
                    <div class="card-watermark-flower"><i class="fas fa-fan"></i></div>
                    
                    <svg class="card-garland-top" viewBox="0 0 600 100" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 50 50 Q 150 10 300 50 T 550 50" opacity="0.6"/>
                        <?php for ($i = 0; $i < 9; $i++): $x = 40 + ($i * 65); ?>
                            <g transform="translate(<?php echo $x; ?>, 50)">
                                <ellipse cx="-12" cy="-8" rx="6" ry="4" fill="currentColor" opacity="0.5" transform="rotate(-30)"/>
                                <ellipse cx="12" cy="-8" rx="6" ry="4" fill="currentColor" opacity="0.5" transform="rotate(30)"/>
                                <circle cx="0" cy="0" r="4" fill="currentColor" opacity="0.8"/>
                                <circle cx="0" cy="0" r="2" fill="white" opacity="0.6"/>
                            </g>
                        <?php endfor; ?>
                    </svg>
                    
                    <svg class="card-garland-bottom" viewBox="0 0 600 100" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 50 50 Q 150 10 300 50 T 550 50" opacity="0.6"/>
                        <?php for ($i = 0; $i < 9; $i++): $x = 40 + ($i * 65); ?>
                            <g transform="translate(<?php echo $x; ?>, 50)">
                                <ellipse cx="-12" cy="-8" rx="6" ry="4" fill="currentColor" opacity="0.5" transform="rotate(-30)"/>
                                <ellipse cx="12" cy="-8" rx="6" ry="4" fill="currentColor" opacity="0.5" transform="rotate(30)"/>
                                <circle cx="0" cy="0" r="4" fill="currentColor" opacity="0.8"/>
                                <circle cx="0" cy="0" r="2" fill="white" opacity="0.6"/>
                            </g>
                        <?php endfor; ?>
                    </svg>
                    
                    <svg class="std-corner-floral tl" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                        <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                        <ellipse cx="70" cy="45" rx="8" ry="14" transform="rotate(-20 70 45)" fill="currentColor" opacity="0.4"/>
                        <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                    </svg>
                    <svg class="std-corner-floral tr" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                        <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                        <ellipse cx="70" cy="45" rx="8" ry="14" transform="rotate(-20 70 45)" fill="currentColor" opacity="0.4"/>
                        <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                    </svg>
                    <svg class="std-corner-floral bl" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                        <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                        <ellipse cx="70" cy="45" rx="8" ry="14" transform="rotate(-20 70 45)" fill="currentColor" opacity="0.4"/>
                        <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                    </svg>
                    <svg class="std-corner-floral br" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                        <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                        <ellipse cx="70" cy="45" rx="8" ry="14" transform="rotate(-20 70 45)" fill="currentColor" opacity="0.4"/>
                        <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                    </svg>
                    
                    <div class="std-recipient">
                        <div class="std-recipient-name"><?php echo htmlspecialchars($guestName); ?></div>
                    </div>
                    
                    <div class="std-ornament">
                        <svg viewBox="0 0 500 50" fill="none" stroke="currentColor" stroke-width="1.2">
                            <path d="M 0 25 L 140 25"/>
                            <path d="M 360 25 L 500 25"/>
                            <path d="M 160 25 Q 170 10 180 25 Q 190 40 200 25 Q 210 10 220 25 Q 230 40 240 25 Q 250 10 260 25 Q 270 40 280 25 Q 290 10 300 25 Q 310 40 320 25 Q 330 10 340 25"/>
                            <ellipse cx="170" cy="17" rx="3" ry="6" transform="rotate(-30 170 17)" fill="currentColor" opacity="0.6"/>
                            <ellipse cx="220" cy="17" rx="3" ry="6" transform="rotate(-30 220 17)" fill="currentColor" opacity="0.6"/>
                            <ellipse cx="280" cy="17" rx="3" ry="6" transform="rotate(30 280 17)" fill="currentColor" opacity="0.6"/>
                            <ellipse cx="330" cy="17" rx="3" ry="6" transform="rotate(30 330 17)" fill="currentColor" opacity="0.6"/>
                            <circle cx="160" cy="25" r="3.5" fill="currentColor"/>
                            <circle cx="340" cy="25" r="3.5" fill="currentColor"/>
                            <circle cx="250" cy="25" r="5" fill="currentColor"/>
                        </svg>
                    </div>
                    
                    <div class="std-message">
                        C'est avec une immense joie que <strong><?php echo htmlspecialchars($host1); ?></strong> vous convient à célébrer leur <strong><?php echo htmlspecialchars(strtolower($eventType)); ?></strong> le <strong><?php echo htmlspecialchars($eventDate); ?></strong>
                    </div>
                    
                    <?php if (!empty($eventDescription)): ?>
                        <?php 
                        $paragraphs = explode("\n", $eventDescription);
                        foreach ($paragraphs as $p):
                            if (trim($p) === '') continue;
                        ?>
                            <div class="std-message"><?php echo nl2br(htmlspecialchars(trim($p))); ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="std-message">Cet événement si spécial marquera l'union de deux âmes qui se sont trouvées et qui souhaitent partager leur bonheur avec vous.</div>
                        <div class="std-message"><strong><?php echo htmlspecialchars($lieuDisplay); ?></strong><?php if ($adresseDisplay): ?>, <?php echo htmlspecialchars($adresseDisplay); ?><?php endif; ?></div>
                        <div class="std-message">Au plaisir de vous retrouver pour cette occasion spéciale.</div>
                    <?php endif; ?>
                    
                    <div class="std-message" style="font-style: italic; margin-top: 30px;">Avec tout notre amour,</div>
                    <div style="font-family: 'Great Vibes', cursive; font-size: 32px; text-align: center; color: var(--text); margin-bottom: 20px;">
                        <?php echo htmlspecialchars($invitation['evenement_nom']); ?> <i class="fas fa-heart" style="color: #e07a7e; font-size: 20px;"></i>
                    </div>
                    
                    <?php if ($hasTable): ?>
                    <div style="text-align: center;">
                        <div class="std-table">
                            <div class="std-table-label">Table</div>
                            <div class="std-table-value"><?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ============================================ -->
                    <!-- QR CODE INTÉGRÉ DANS LA MÊME CARTE          -->
                    <!-- ============================================ -->
                    <div class="std-qr-inline">
                        <div class="std-qr-inline-title">Code d'accès</div>
                        <div class="std-qr-wrapper">
                            <div class="std-qr-box">
                                <div id="stdQrcode"></div>
                            </div>
                            <div class="std-qr-label"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                        </div>
                    </div>
                    
                   
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                           target="_blank" rel="noopener" class="std-btn-address">
                            Afficher la carte d'adresse <i class="fas fa-map-marker-alt"></i>
                        </a>
                    </div>
                    
                </div>
                
            </div>
        </section>

    </div>
    <!-- FIN WRAPPER -->

    <!-- ============================================ -->
    <!-- SECTIONS HORS CAPTURE                       -->
    <!-- ============================================ -->

    <!-- DIAPORAMA PHOTOS -->
    <?php if ($hasPhotos && count($photosHost) > 1): ?>
    <section class="std-section std-reveal" id="stdPhotos">
        <div class="std-section-content">
            <div class="std-card">
                
                <svg class="std-corner-floral tl" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                <svg class="std-corner-floral br" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                
                <div class="std-drinks-title" style="text-align: center; margin-bottom: 30px; display: block;">Nos souvenirs</div>
                
                <div class="std-diaporama" id="stdDiaporama">
                    <?php 
                    $photoIndex = 0;
                    foreach ($photosHost as $index => $photo): 
                    ?>
                        <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                                 loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                                 crossorigin="anonymous">
                        </div>
                    <?php 
                        $photoIndex++;
                    endforeach; 
                    ?>
                </div>
                
                <div class="std-diapo-nav">
                    <button class="std-diapo-btn" onclick="stdDiapoChange(-1)"><i class="fas fa-chevron-left"></i></button>
                    <div class="std-diapo-counter" id="stdDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    <button class="std-diapo-btn" onclick="stdDiapoChange(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="std-diapo-dots" id="stdDiapoDots">
                    <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                        <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="stdDiapoGoTo(<?php echo $i; ?>)"></span>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE' || !empty($invitationMessage)): ?>
    <section class="std-section std-reveal" id="stdConfirm">
        <div class="std-section-content">
            <div class="std-card">
                
                <svg class="std-corner-floral tl" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                <svg class="std-corner-floral br" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                
                <div class="std-ornament" style="margin-top: 0; margin-bottom: 16px;">
                    <svg viewBox="0 0 500 50" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M 0 25 L 180 25"/>
                        <path d="M 320 25 L 500 25"/>
                        <path d="M 200 25 Q 210 10 220 25 Q 230 40 240 25 Q 250 10 260 25 Q 270 40 280 25 Q 290 10 300 25"/>
                        <circle cx="200" cy="25" r="3.5" fill="currentColor"/>
                        <circle cx="300" cy="25" r="3.5" fill="currentColor"/>
                        <circle cx="250" cy="25" r="5" fill="currentColor"/>
                    </svg>
                </div>
                
                <div class="std-drinks-title" style="text-align: center; margin-bottom: 8px; display: block;">
                    <?php echo $invitation['statut'] == 'EN_ATTENTE' ? 'Confirmez votre présence' : 'Votre réponse'; ?>
                </div>
                
                <div class="std-message-intro" style="margin-bottom: 30px;">
                    <strong>Un mot doux nous ferait chaud au cœur.</strong><br>
                    Votre message sera enregistré en même temps que votre réponse.
                </div>
                
                <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
                    <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                        <input type="hidden" name="action" value="confirmer">
                        
                        <div class="std-form-group">
                            <label>Nombre de personnes</label>
                            <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                        </div>
                        
                        <div class="std-form-group">
                            <label>Votre réponse</label>
                            <div class="std-options-grid">
                                <div>
                                    <input type="radio" name="reponse" id="stdOui" value="CONFIRMEE" checked class="std-option-radio">
                                    <label for="stdOui" class="std-option-label">
                                        <i class="fas fa-check"></i> Je confirme
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="reponse" id="stdNon" value="REFUSEE" class="std-option-radio">
                                    <label for="stdNon" class="std-option-label">
                                        <i class="fas fa-times"></i> Je ne peux pas
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="std-form-group">
                            <label><i class="fas fa-heart" style="color: var(--rose-dark);"></i> Votre message pour les mariés</label>
                            <textarea name="message_invite" rows="5" placeholder="Écrivez ici un vœu, un souvenir, un conseil... Chaque mot compte pour nous."></textarea>
                        </div>
                        
                        <div style="text-align: center;">
                            <button type="submit" class="std-btn-submit">
                                <i class="fas fa-heart"></i> Envoyer ma réponse
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <?php if (!empty($invitationMessage)): ?>
                        <div style="margin: 20px 0 30px;">
                            <div style="font-family: 'Cormorant Garamond', serif; font-size: 14px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; text-align: center;">
                                <i class="fas fa-heart" style="color: var(--rose-dark);"></i> Votre message
                            </div>
                            <div class="std-message-existing">
                                <?php echo nl2br(htmlspecialchars($invitationMessage)); ?>
                            </div>
                        </div>
                        
                        <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                            <input type="hidden" name="action" value="confirmer">
                            <input type="hidden" name="reponse" value="<?php echo htmlspecialchars($invitation['reponse'] ?? 'CONFIRMEE'); ?>">
                            <input type="hidden" name="nombre_personnes" value="<?php echo (int)($invitation['nb_confirme'] ?? $invitation['nb_places_max'] ?? 1); ?>">
                            
                            <div class="std-form-group">
                                <label>Souhaitez-vous modifier votre message ?</label>
                                <textarea name="message_invite" rows="4" placeholder="Écrivez ici un mot doux..."><?php echo htmlspecialchars($invitationMessage); ?></textarea>
                            </div>
                            
                            <div style="text-align: center;">
                                <button type="submit" class="std-btn-submit">
                                    <i class="fas fa-pen"></i> Mettre à jour mon message
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- PRÉFÉRENCES BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
    <section class="std-section std-reveal" id="stdDrinks">
        <div class="std-section-content">
            <div class="std-card">
                
                <svg class="std-corner-floral tl" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                <svg class="std-corner-floral br" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M 10 10 Q 60 15 80 50 Q 90 70 100 80" opacity="0.7"/>
                    <ellipse cx="40" cy="30" rx="10" ry="16" transform="rotate(-45 40 30)" fill="currentColor" opacity="0.5"/>
                    <circle cx="55" cy="55" r="6" fill="currentColor" opacity="0.6"/>
                </svg>
                
                <div class="std-drinks">
                    <div class="std-drinks-title">Vos préférences</div>
                    <div class="std-drinks-subtitle">Que désirez-vous boire 🍹 ?</div>
                    <div class="std-drinks-hint">
                        Aidez les mariés dans la planification de leur événement en leur suggérant vos goûts de boissons<br>
                        (Deux goûts au max)
                    </div>
                    
                    <?php if ($isLocked): ?>
                        <div style="color: var(--rose-dark); font-weight: 600; padding: 20px 0; font-size: 17px;">
                            <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                        </div>
                        <div class="std-drinks-grid" style="justify-content: center;">
                            <?php foreach ($boissons as $b):
                                if (!isset($preferencesBoissons[$b['id']])) continue;
                            ?>
                                <div class="std-drink selected" style="cursor: default;">
                                    <span><?php echo htmlspecialchars($b['nom']); ?></span>
                                    <i class="fas fa-check check"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                            <input type="hidden" name="action" value="preferences">
                            
                            <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                                <div class="std-drinks-category">
                                    <div class="std-drinks-category-title"><?php echo htmlspecialchars($type); ?></div>
                                    <div class="std-drinks-grid">
                                        <?php foreach ($boissonsByType as $b): 
                                            $selected = isset($preferencesBoissons[$b['id']]);
                                        ?>
                                            <div class="std-drink <?php echo $selected ? 'selected' : ''; ?>" 
                                                 data-id="<?php echo $b['id']; ?>"
                                                 onclick="stdToggleDrink(this, <?php echo $b['id']; ?>)">
                                                <span><?php echo htmlspecialchars($b['nom']); ?></span>
                                                <i class="fas fa-check check"></i>
                                                <input type="checkbox" name="boissons[]" value="<?php echo $b['id']; ?>" 
                                                       style="display:none;" <?php echo $selected ? 'checked' : ''; ?>>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="std-drinks-counter">Sélectionnez <span id="stdDrinkCount">0</span>/2 boissons</div>
                            
                            <div style="text-align: center;">
                                <button type="submit" class="std-btn-submit">
                                    <i class="fas fa-save"></i> Enregistrer mes préférences
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer class="std-footer">
        <div class="std-footer-ornament">
            <div class="line"></div>
            <i class="fas fa-fan"></i>
            <i class="fas fa-heart" style="margin: 0 8px;"></i>
            <i class="fas fa-fan"></i>
            <div class="line"></div>
        </div>
        <div class="std-footer-app"><?php echo htmlspecialchars($appName); ?></div>
        <div class="std-footer-tagline">Invitation réalisée par MdlEvent</div>
        
        <a href="https://wa.me/243963967028?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20mon%20invitation" 
           target="_blank" rel="noopener" class="std-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(232, 180, 184, 0.3); font-family: 'Cormorant Garamond', serif; font-size: 12px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-muted);">
            © <?php echo date('Y'); ?> · Tous droits réservés
        </div>
    </footer>

    <button id="stdDownloadFloat" onclick="stdDownload()">
        <i class="fas fa-download"></i>
        <span>Télécharger</span>
    </button>

    <button id="stdScrollTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('stdQrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180, height: 180,
                        colorDark: '#2a2420', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const reveals = document.querySelectorAll('.std-reveal');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('apparue');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });
            
            reveals.forEach(el => observer.observe(el));
            
            setTimeout(() => {
                reveals.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        el.classList.add('apparue');
                    }
                });
            }, 500);
        });

        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('stdScrollTop');
            const dlBtn = document.getElementById('stdDownloadFloat');
            
            if (window.scrollY > 600) {
                scrollBtn.classList.add('visible');
                dlBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
                dlBtn.classList.remove('visible');
            }
        });

        let stdDiapoIndex = 0;
        const stdSlides = document.querySelectorAll('#stdDiaporama .slide');
        const stdDots = document.querySelectorAll('#stdDiapoDots span');
        const stdCounter = document.getElementById('stdDiapoCounter');
        let stdDiapoInterval = null;

        function stdUpdateDiapo() {
            stdSlides.forEach((slide, i) => slide.classList.toggle('active', i === stdDiapoIndex));
            stdDots.forEach((dot, i) => dot.classList.toggle('active', i === stdDiapoIndex));
            if (stdCounter) stdCounter.textContent = (stdDiapoIndex + 1) + ' / ' + stdSlides.length;
        }

        function stdDiapoChange(direction) {
            stdDiapoIndex += direction;
            if (stdDiapoIndex < 0) stdDiapoIndex = stdSlides.length - 1;
            if (stdDiapoIndex >= stdSlides.length) stdDiapoIndex = 0;
            stdUpdateDiapo();
            resetStdDiapoAuto();
        }

        function stdDiapoGoTo(index) {
            stdDiapoIndex = index;
            stdUpdateDiapo();
            resetStdDiapoAuto();
        }

        function resetStdDiapoAuto() {
            if (stdDiapoInterval) clearInterval(stdDiapoInterval);
            if (stdSlides.length > 1) {
                stdDiapoInterval = setInterval(() => {
                    stdDiapoIndex = (stdDiapoIndex + 1) % stdSlides.length;
                    stdUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (stdSlides.length > 0) {
                stdUpdateDiapo();
                resetStdDiapoAuto();
            }
        });

        let stdSelectedDrinks = [];

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.std-drink.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!isNaN(id) && !stdSelectedDrinks.includes(id)) stdSelectedDrinks.push(id);
            });
            stdUpdateDrinkCount();
        });

        function stdToggleDrink(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const index = stdSelectedDrinks.indexOf(id);
                if (index > -1) stdSelectedDrinks.splice(index, 1);
                const checkbox = element.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
                stdUpdateDrinkCount();
                return;
            }
            
            if (stdSelectedDrinks.length >= 2) {
                alert('Vous ne pouvez sélectionner que 2 boissons maximum.');
                return;
            }
            
            element.classList.add('selected');
            stdSelectedDrinks.push(id);
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
            stdUpdateDrinkCount();
        }

        function stdUpdateDrinkCount() {
            const el = document.getElementById('stdDrinkCount');
            if (el) el.textContent = stdSelectedDrinks.length;
            
            document.querySelectorAll('.std-drink').forEach(item => {
                if (!item.classList.contains('selected') && stdSelectedDrinks.length >= 2) {
                    item.style.opacity = '0.5';
                    item.style.cursor = 'not-allowed';
                } else {
                    item.style.opacity = '1';
                    item.style.cursor = 'pointer';
                }
            });
        }

        // ================================================================
        // TÉLÉCHARGEMENT — CAPTURE LA CARTE UNIQUE (infos + QR)
        // ================================================================
        async function stdDownload() {
            const card = document.getElementById('stdCard');
            
            if (!card) {
                alert('Carte introuvable');
                return;
            }
            
            try {
                await new Promise(r => setTimeout(r, 1000));
                
                for (let i = 0; i < 10; i++) {
                    const qrCanvas = document.querySelector('#stdQrcode canvas');
                    const qrImg = document.querySelector('#stdQrcode img');
                    if (qrCanvas || qrImg) break;
                    await new Promise(r => setTimeout(r, 300));
                }
                
                const images = card.querySelectorAll('img');
                await Promise.all(Array.from(images).map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(resolve => {
                        img.onload = resolve;
                        img.onerror = resolve;
                        setTimeout(resolve, 2000);
                    });
                }));
                
                card.querySelectorAll('.std-reveal').forEach(el => {
                    el.classList.add('apparue');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                });
                
                await new Promise(r => setTimeout(r, 300));
                
                const canvas = await html2canvas(card, {
                    scale: 2.5,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#faf6f1',
                    logging: false,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.getElementById('stdCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                            clonedCard.style.transform = 'none';
                        }
                        
                        clonedDoc.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                        });
                        
                        const qrBox = clonedDoc.querySelector('.std-qr-box');
                        if (qrBox) {
                            qrBox.style.display = 'inline-block';
                            qrBox.style.visibility = 'visible';
                            qrBox.style.opacity = '1';
                        }
                    }
                });
                
                const link = document.createElement('a');
                const name = '<?php echo htmlspecialchars($invitation['evenement_nom']); ?>';
                link.download = `invitation_${name.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
            } catch(e) {
                console.error(e);
                alert('Erreur lors du téléchargement');
            }
        }
    </script>

</body>
</html>