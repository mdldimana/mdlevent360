<?php
/**
 * ============================================================
 * TEMPLATE : INAUGURATION - v3
 * ============================================================
 * 
 * Nouveautés v3 :
 * - Téléchargement = 1 seule carte (Hero + Détails + QR)
 * - QR code intégré dans la carte
 * - Correction de l'image noire
 * 
 * ============================================================
 */

$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);
$hasTable = !empty($tableNom) || !empty($tableNumero);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --black: #0a0a0a;
            --near-black: #121212;
            --off-black: #1a1a1a;
            --dark-gray: #2a2a2a;
            --gray: #4a4a4a;
            --silver: #d4d4d4;
            --white: #ffffff;
            --red: #c8102e;
            --red-dark: #8b0a1f;
            --red-light: #e84a5f;
            --gold: #d4af37;
            --gold-light: #f4e5a1;
            --gold-dark: #8b6914;
            --champagne: #f7e7ce;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        html { background: var(--black); }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: var(--black);
            <?php else: ?>
            background: 
                radial-gradient(ellipse at top, #1a0a0a 0%, transparent 60%),
                radial-gradient(ellipse at bottom, #2a1a0a 0%, transparent 60%),
                var(--black);
            <?php endif; ?>
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background: 
                radial-gradient(ellipse at top, rgba(26, 10, 10, 0.6) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(10, 10, 10, 0.75) 0%, 
                    rgba(26, 10, 10, 0.6) 30%,
                    rgba(10, 10, 10, 0.8) 70%,
                    rgba(10, 10, 10, 0.95) 100%);
            pointer-events: none;
        }
        
        /* ============================================
           INTRO
           ============================================ */
        .inauguration-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--black);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: introFadeOut 3s ease-in-out 2.2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .ribbon {
            position: relative;
            width: 100%;
            height: 40px;
            background: linear-gradient(180deg, var(--red) 0%, var(--red-dark) 50%, var(--red) 100%);
            box-shadow: 0 0 40px rgba(200, 16, 46, 0.6), inset 0 4px 8px rgba(255, 255, 255, 0.2), inset 0 -4px 8px rgba(0, 0, 0, 0.3);
            transform: scaleX(0);
            transform-origin: center;
            animation: ribbonExpand 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.3s forwards;
        }
        @keyframes ribbonExpand {
            0% { transform: scaleX(0); }
            100% { transform: scaleX(1); }
        }
        
        .ribbon::before {
            content: '';
            position: absolute;
            top: -10px;
            bottom: -10px;
            left: 50%;
            width: 4px;
            background: var(--black);
            transform: translateX(-50%) scaleY(0);
            animation: ribbonCut 0.4s ease-out 1.5s forwards;
            box-shadow: 0 0 20px var(--black), 0 0 40px var(--black);
        }
        @keyframes ribbonCut {
            0% { transform: translateX(-50%) scaleY(0); }
            100% { transform: translateX(-50%) scaleY(1.5); }
        }
        
        .scissors {
            position: absolute;
            top: 50%;
            left: 50%;
            font-size: 120px;
            color: var(--gold);
            filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.8));
            opacity: 0;
            animation: 
                scissorsAppear 0.5s ease-out 0.8s forwards,
                scissorsCut 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1.1s forwards;
            z-index: 3;
        }
        @keyframes scissorsAppear {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5) rotate(-20deg); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1) rotate(0deg); }
        }
        @keyframes scissorsCut {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(-15deg); }
            100% { transform: translate(-50%, -50%) rotate(0deg); }
        }
        
        .ribbon-flash {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 0;
            height: 0;
            background: radial-gradient(circle, white 0%, rgba(255, 255, 255, 0.5) 30%, transparent 70%);
            opacity: 0;
            animation: flashBang 1.5s ease-out 1.5s forwards;
            z-index: 2;
        }
        @keyframes flashBang {
            0% { width: 0; height: 0; opacity: 0; }
            30% { width: 500px; height: 500px; opacity: 0.8; }
            100% { width: 1500px; height: 1500px; opacity: 0; }
        }
        
        .inauguration-text {
            position: relative;
            z-index: 4;
            margin-top: 60px;
            text-align: center;
            opacity: 0;
            animation: textFadeIn 1s ease-out 2s forwards;
        }
        @keyframes textFadeIn {
            to { opacity: 1; }
        }
        
        .inauguration-text-main {
            font-family: 'Cinzel', serif;
            font-size: 40px;
            font-weight: 900;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.6), 0 4px 10px rgba(0, 0, 0, 0.5);
            text-transform: uppercase;
            padding-left: 0.3em;
        }
        
        .inauguration-text-sub {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 16px;
            letter-spacing: 0.3em;
            color: var(--silver);
            margin-top: 16px;
        }
        
        /* ============================================
           CONFETTIS
           ============================================ */
        .fireworks-container {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 5;
            overflow: hidden;
        }
        .confetti {
            position: absolute;
            top: -20px;
            width: 10px;
            height: 16px;
            opacity: 0;
            animation: confettiFall linear infinite;
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.8; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        .inaug-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .inaug-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .inaug-anim.from-left { transform: translateX(-80px); }
        .inaug-anim.from-left.apparue { transform: translateX(0); }
        
        .inaug-anim.from-right { transform: translateX(80px); }
        .inaug-anim.from-right.apparue { transform: translateX(0); }
        
        .inaug-anim.zoom-in { transform: scale(0.85); }
        .inaug-anim.zoom-in.apparue { transform: scale(1); }
        
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           WRAPPER DE TÉLÉCHARGEMENT
           ============================================ */
        #downloadCard {
            position: relative;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            <?php else: ?>
            background: var(--black);
            <?php endif; ?>
            padding-bottom: 20px;
        }
        
        #downloadCard::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at top, rgba(26, 10, 10, 0.6) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(10, 10, 10, 0.75) 0%, 
                    rgba(26, 10, 10, 0.6) 30%,
                    rgba(10, 10, 10, 0.8) 70%,
                    rgba(10, 10, 10, 0.95) 100%);
            pointer-events: none;
            z-index: 0;
        }
        
        #downloadCard > * {
            position: relative;
            z-index: 2;
        }
        
        /* ============================================
           HERO
           ============================================ */
        .inauguration-hero {
            position: relative;
            padding: 80px 20px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            overflow: hidden;
        }
        
        .halo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
            animation: haloPulse 6s ease-in-out infinite;
        }
        @keyframes haloPulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
            50% { transform: translate(-50%, -50%) scale(1.1); opacity: 1; }
        }
        
        .hero-ribbon {
            position: absolute;
            top: 50%;
            left: -100px;
            right: -100px;
            height: 30px;
            background: linear-gradient(180deg, var(--red) 0%, var(--red-dark) 50%, var(--red) 100%);
            transform: translateY(-50%) rotate(-3deg);
            box-shadow: 0 0 30px rgba(200, 16, 46, 0.4), inset 0 2px 4px rgba(255, 255, 255, 0.2);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }
        
        .inauguration-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 900px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.2s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .inauguration-star {
            font-size: 46px;
            color: var(--gold);
            margin-bottom: 20px;
            filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.7));
            animation: starRotate 4s ease-in-out infinite;
        }
        @keyframes starRotate {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.15) rotate(180deg); }
        }
        
        .inauguration-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 26px;
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            color: var(--white);
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.25em;
            border-radius: 4px;
            margin-bottom: 30px;
            box-shadow: 6px 6px 0 var(--gold-dark), 0 0 40px rgba(200, 16, 46, 0.5);
            text-transform: uppercase;
            transform: rotate(-2deg);
        }
        
        .inauguration-guest {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 6vw, 52px);
            font-weight: 400;
            font-style: italic;
            color: var(--white);
            letter-spacing: 0.01em;
            line-height: 1.15;
            margin-bottom: 30px;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.8);
        }
        
        .inauguration-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 500px;
            margin: 0 auto 40px;
        }
        .inauguration-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .inauguration-divider .icon {
            font-size: 24px;
            color: var(--gold);
            animation: iconPulse 2s ease-in-out infinite;
        }
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .inauguration-hosts-intro {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            letter-spacing: 0.35em;
            color: var(--silver);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .inauguration-host-name {
            font-family: 'Cinzel', serif;
            font-size: clamp(52px, 11vw, 110px);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            background: linear-gradient(180deg, var(--gold-light) 0%, var(--gold) 40%, var(--gold-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            filter: drop-shadow(0 6px 30px rgba(212, 175, 55, 0.4));
        }
        
        .inauguration-event-type {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Cinzel', serif;
            font-size: 14px;
            letter-spacing: 0.5em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 700;
            padding: 12px 28px;
            border: 2px solid var(--gold);
            background: rgba(212, 175, 55, 0.08);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
        }
        .inauguration-event-type::before,
        .inauguration-event-type::after {
            content: '✦';
            font-size: 14px;
        }
        
        /* ============================================
           CARTE INAUGURATION (avec QR intégré)
           ============================================ */
        .inauguration-card {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 60px 55px;
            background: linear-gradient(180deg, rgba(26, 26, 26, 0.95) 0%, rgba(18, 18, 18, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 2px solid var(--gold);
            box-shadow: 0 0 0 6px var(--black), 0 0 0 7px var(--red), 0 30px 80px rgba(0, 0, 0, 0.7);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .inauguration-card { padding: 40px 25px; margin: 60px 15px; }
        }
        
        .inauguration-card::before,
        .inauguration-card::after {
            content: '✦';
            position: absolute;
            color: var(--gold);
            font-size: 24px;
            background: var(--black);
            padding: 4px;
        }
        .inauguration-card::before { top: -16px; left: 20px; }
        .inauguration-card::after { bottom: -16px; right: 20px; }
        
        .inauguration-card-title {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            color: var(--white);
            margin-bottom: 40px;
            padding-bottom: 24px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border-bottom: 2px dashed rgba(212, 175, 55, 0.3);
            position: relative;
        }
        .inauguration-card-title::before {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            width: 12px;
            height: 12px;
            background: var(--red);
            transform: translateX(-50%) rotate(45deg);
            box-shadow: 0 0 20px var(--red);
        }
        
        .inauguration-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
        }
        @media (max-width: 640px) {
            .inauguration-info-grid { grid-template-columns: 1fr; }
        }
        
        .inauguration-info-item {
            padding: 26px 24px;
            background: rgba(255, 255, 255, 0.02);
            border-left: 3px solid var(--gold);
            transition: all 0.4s ease;
            text-align: left;
            position: relative;
        }
        
        .inauguration-info-item .icon {
            font-size: 26px;
            color: var(--gold);
            margin-bottom: 14px;
            display: block;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.4));
        }
        .inauguration-info-item .label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--gold);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .inauguration-info-item .value {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 400;
            color: var(--white);
            line-height: 1.4;
            letter-spacing: 0.01em;
        }
        .inauguration-info-item .value .sub {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-style: italic;
            color: var(--silver);
            margin-top: 6px;
            font-weight: 400;
        }
        
        .inauguration-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(200, 16, 46, 0.08)) !important;
            border-left: 4px solid var(--gold) !important;
        }
        
        .inauguration-table-item .value {
            font-size: 28px !important;
            color: var(--gold) !important;
            font-weight: 700 !important;
            letter-spacing: 0.05em;
        }
        
        .inauguration-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            padding: 14px 32px;
            background: var(--red);
            color: var(--white);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            text-decoration: none;
            border: 2px solid var(--gold);
            box-shadow: 4px 4px 0 var(--gold-dark), 0 0 30px rgba(200, 16, 46, 0.4);
            transition: all 0.3s ease;
        }
        
        /* ============================================
           SECTION QR INTÉGRÉE (dans la même carte)
           ============================================ */
        .inauguration-qr-inline {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px dashed rgba(212, 175, 55, 0.3);
            text-align: center;
        }
        
        .inauguration-qr-inline-title {
            font-family: 'Cinzel', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }
        
        .inauguration-qr-wrapper { text-align: center; }
        .inauguration-qr-box {
            display: inline-block;
            padding: 28px;
            background: var(--white);
            border: 3px solid var(--gold);
            box-shadow: 0 0 0 6px var(--black), 0 0 0 8px var(--red), 0 20px 60px rgba(212, 175, 55, 0.3);
            position: relative;
        }
        .inauguration-qr-box::before,
        .inauguration-qr-box::after {
            content: '✂';
            position: absolute;
            color: var(--gold);
            font-size: 24px;
            background: var(--black);
            padding: 4px;
            line-height: 1;
        }
        .inauguration-qr-box::before { top: -18px; left: -18px; }
        .inauguration-qr-box::after { bottom: -18px; right: -18px; }
        
        /* ============================================
           SECTIONS HORS CAPTURE
           ============================================ */
        .inauguration-section {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 60px 55px;
            background: linear-gradient(180deg, rgba(26, 26, 26, 0.9) 0%, rgba(18, 18, 18, 0.9) 100%);
            backdrop-filter: blur(10px);
            border: 2px solid var(--gold);
            box-shadow: 0 0 0 4px var(--black), 0 0 0 5px var(--red), 0 20px 60px rgba(0, 0, 0, 0.6);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .inauguration-section { padding: 40px 25px; margin: 60px 15px; }
        }
        
        .inauguration-section-title {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            border-bottom: 2px dashed rgba(212, 175, 55, 0.3);
        }
        
        /* ============================================
           DIAPORAMA PHOTOS
           ============================================ */
        .inauguration-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: var(--black);
            border: 3px solid var(--gold);
            box-shadow: 0 0 0 6px var(--black), 0 0 0 8px var(--red), 0 0 40px rgba(212, 175, 55, 0.3);
        }
        
        .inauguration-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 0;
            background: var(--black);
        }
        
        .inauguration-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .inauguration-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--black);
            padding: 8px;
        }
        
        .inauguration-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(200, 16, 46, 0.9);
            border: 2px solid var(--gold);
            color: var(--gold);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(200, 16, 46, 0.5);
        }
        
        .inauguration-diapo-arrow.prev { left: 16px; }
        .inauguration-diapo-arrow.next { right: 16px; }
        
        .inauguration-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(10, 10, 10, 0.9);
            border: 2px solid var(--gold);
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 8px 16px;
            z-index: 10;
        }
        
        .inauguration-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(10, 10, 10, 0.85);
            padding: 10px 20px;
            border: 2px solid var(--gold);
            backdrop-filter: blur(10px);
        }
        
        .inauguration-diapo-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .inauguration-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.9);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .inauguration-form-group { margin-bottom: 26px; }
        .inauguration-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .inauguration-form-group input,
        .inauguration-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .inauguration-form-group input:focus,
        .inauguration-form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
        }
        
        .inauguration-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .inauguration-options-grid { grid-template-columns: 1fr; }
        }
        
        .inauguration-option-radio { display: none; }
        .inauguration-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px;
            border: 2px solid rgba(212, 175, 55, 0.4);
            background: rgba(255, 255, 255, 0.02);
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--silver);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .inauguration-option-radio:checked + .inauguration-option-label {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-light);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
        }
        
        .inauguration-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            color: var(--white);
            border: 2px solid var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 6px 6px 0 var(--gold-dark), 0 0 40px rgba(200, 16, 46, 0.4);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .inauguration-boisson-category { margin-bottom: 28px; }
        .inauguration-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .inauguration-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .inauguration-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border: 2px solid rgba(212, 175, 55, 0.3);
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--silver);
        }
        .inauguration-boisson-item.selected {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.12);
            color: var(--gold-light);
        }
        .inauguration-boisson-item .check {
            opacity: 0;
            transition: opacity 0.3s ease;
            color: var(--gold);
        }
        .inauguration-boisson-item.selected .check { opacity: 1; }
        
        /* ============================================
           FOOTER
           ============================================ */
        .inauguration-footer {
            padding: 80px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            margin-top: 80px;
        }
        .inauguration-footer-star {
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 16px;
        }
        .inauguration-footer-brand {
            font-family: 'Cinzel', serif;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 0.2em;
            color: var(--white);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .inauguration-footer-tagline {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 16px;
            color: var(--gold);
            margin-bottom: 36px;
        }
        
        .inauguration-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 36px;
            background: transparent;
            border: 2px solid var(--gold);
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        /* Alerts */
        .inauguration-alert {
            padding: 20px 28px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: center;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 500;
            border-left: 3px solid;
        }
        .inauguration-alert-success { border-color: var(--gold); background: rgba(212, 175, 55, 0.08); color: var(--gold-light); }
        .inauguration-alert-danger { border-color: var(--red); background: rgba(200, 16, 46, 0.08); color: #ff8080; }
        .inauguration-alert-warning { border-color: #f59e0b; background: rgba(245, 158, 11, 0.08); color: #fcd34d; }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 18px 32px;
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            color: var(--white);
            border: 2px solid var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 6px 6px 0 var(--gold-dark), 0 0 40px rgba(200, 16, 46, 0.4);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3.5s forwards;
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 16px; right: 16px; padding: 14px 22px; font-size: 10px; }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="inauguration-intro">
        <div class="ribbon-flash"></div>
        <div class="ribbon"></div>
        <div class="scissors">✂</div>
        <div class="inauguration-text">
            <div class="inauguration-text-main">Inauguration</div>
            <div class="inauguration-text-sub">Cérémonie officielle</div>
        </div>
    </div>

    <div class="fireworks-container" id="confettiContainer"></div>

    <!-- ============================================ -->
    <!-- WRAPPER CAPTURÉ (Hero + Carte + QR)        -->
    <!-- ============================================ -->
    <div id="downloadCard">

        <!-- HERO -->
        <section class="inauguration-hero">
            <div class="halo"></div>
            <div class="hero-ribbon"></div>
            
            <div class="inauguration-blason">
                
                <div class="inauguration-star">✦</div>
                
                <div class="inauguration-badge">
                    GRAND OPENING
                </div>
                
                <div class="inauguration-guest">
                    <?php echo htmlspecialchars($guestName); ?>
                </div>
                
                <div class="inauguration-divider">
                    <div class="line"></div>
                    <span class="icon">✂</span>
                    <div class="line"></div>
                </div>
                
                <div class="inauguration-hosts-intro">
                    Vous êtes convié(e) à l'inauguration de
                </div>
                <div class="inauguration-host-name"><?php echo htmlspecialchars($host1); ?></div>
                <div class="inauguration-event-type">
                    <?php echo htmlspecialchars(strtoupper($eventType)); ?>
                </div>
                
            </div>
        </section>

        <!-- CARTE (Détails + QR intégré) -->
        <div class="inauguration-card">
            
            <div class="inauguration-card-title">Détails de la cérémonie</div>
            
            <div class="inauguration-info-grid">
                
                <div class="inauguration-info-item">
                    <i class="fas fa-calendar-alt icon"></i>
                    <div class="label">DATE</div>
                    <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                </div>
                
                <div class="inauguration-info-item">
                    <i class="fas fa-clock icon"></i>
                    <div class="label">HEURE</div>
                    <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                </div>
                
                <div class="inauguration-info-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-map-marker-alt icon"></i>
                    <div class="label">LIEU DE L'INAUGURATION</div>
                    <div class="value">
                        <?php echo htmlspecialchars($lieuDisplay); ?>
                        <?php if ($adresseDisplay): ?>
                            <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="inauguration-btn-itinerary">
                        <i class="fas fa-route"></i> VOIR L'ITINÉRAIRE
                    </a>
                </div>
                
                <?php if ($hasTable): ?>
                <div class="inauguration-info-item inauguration-table-item">
                    <i class="fas fa-chair icon"></i>
                    <div class="label">VOTRE TABLE</div>
                    <div class="value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="inauguration-info-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-users icon"></i>
                    <div class="label">PLACES RÉSERVÉES</div>
                    <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
                </div>
                
            </div>
            
            <!-- ✅ QR CODE INTÉGRÉ DANS LA MÊME CARTE -->
            <div class="inauguration-qr-inline">
                <div class="inauguration-qr-inline-title">Badge d'accès</div>
                <div class="inauguration-qr-wrapper">
                    <div class="inauguration-qr-box">
                        <div id="qrcode"></div>
                    </div>
                    <div style="font-family:'Cinzel',serif;font-size:13px;color:var(--gold);letter-spacing:0.2em;margin-top:24px;font-weight:700;text-transform:uppercase;">
                        <?php echo htmlspecialchars($invitation['code_unique']); ?>
                    </div>
                </div>
            </div>
            
        </div>

    </div>
    <!-- FIN WRAPPER -->

    <!-- ============================================ -->
    <!-- SECTIONS HORS CAPTURE                       -->
    <!-- ============================================ -->

    <?php if ($message): ?>
        <div class="inauguration-section inaug-anim apparue">
            <div class="inauguration-alert inauguration-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- DIAPORAMA PHOTOS -->
    <?php if ($hasPhotos): ?>
        <div class="inauguration-section inaug-anim from-left">
            <div class="inauguration-section-title">Galerie de l'événement</div>
            
            <div class="inauguration-diaporama" id="inaugurationDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                             loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                             crossorigin="anonymous">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
                
                <?php if ($photoIndex > 1): ?>
                    <button class="inauguration-diapo-arrow prev" onclick="inaugurationDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="inauguration-diapo-arrow next" onclick="inaugurationDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="inauguration-diapo-counter" id="inaugurationDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="inauguration-diapo-dots" id="inaugurationDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="inaugurationDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="inauguration-section inaug-anim from-left">
            <div class="inauguration-section-title">Confirmation de présence</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="inauguration-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>" placeholder="Combien serez-vous ?">
                </div>
                
                <div class="inauguration-form-group">
                    <label>Votre réponse</label>
                    <div class="inauguration-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="inauguration-option-radio">
                            <label for="presenceOui" class="inauguration-option-label">
                                <i class="fas fa-check"></i> Je serai présent(e)
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="inauguration-option-radio">
                            <label for="presenceNon" class="inauguration-option-label">
                                <i class="fas fa-times"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="inauguration-form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="inauguration-btn-submit">
                    <i class="fas fa-paper-plane"></i> CONFIRMER MA PRÉSENCE
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="inauguration-section inaug-anim from-right">
            <div class="inauguration-section-title">Cocktail de réception</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;font-family:'Inter',sans-serif;font-size:14px;color:var(--gold);font-weight:600;padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                </div>
                <div class="inauguration-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="inauguration-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Inter',sans-serif;font-size:14px;color:var(--silver);margin-bottom:30px;font-weight:500;">
                        Sélectionnez jusqu'à <strong style="color:var(--gold);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="inauguration-boisson-category">
                            <div class="inauguration-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="inauguration-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="inauguration-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $b['id']; ?>"
                                         onclick="toggleBoisson(this, <?php echo $b['id']; ?>)">
                                        <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                                        <span><?php echo htmlspecialchars($b['nom']); ?></span>
                                        <i class="fas fa-check check"></i>
                                        <input type="checkbox" name="boissons[]" value="<?php echo $b['id']; ?>" 
                                               style="display:none;" <?php echo $selected ? 'checked' : ''; ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="inauguration-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="inauguration-footer inaug-anim">
        <div class="inauguration-footer-star">✦</div>
        <div class="inauguration-footer-brand"><?php echo htmlspecialchars($appName); ?></div>
        <div class="inauguration-footer-tagline">Célébrons ensemble vos moments d'exception</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="inauguration-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:40px;padding-top:30px;border-top:1px solid rgba(212, 175, 55, 0.2);font-family:'Inter',sans-serif;font-size:11px;color:var(--gray);letter-spacing:0.4em;text-transform:uppercase;font-weight:600;">
            ✦ © <?php echo date('Y'); ?> · TOUS DROITS RÉSERVÉS ✦
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">TÉLÉCHARGER</span>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('confettiContainer');
            const colors = ['#c8102e', '#d4af37', '#ffffff', '#e84a5f', '#f4e5a1'];
            
            function createConfetti() {
                for (let i = 0; i < 50; i++) {
                    setTimeout(() => {
                        const confetti = document.createElement('div');
                        confetti.className = 'confetti';
                        confetti.style.left = Math.random() * 100 + '%';
                        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.animationDuration = (4 + Math.random() * 4) + 's';
                        confetti.style.animationDelay = (Math.random() * 2) + 's';
                        confetti.style.width = (6 + Math.random() * 10) + 'px';
                        confetti.style.height = (8 + Math.random() * 12) + 'px';
                        confetti.style.borderRadius = Math.random() > 0.5 ? '2px' : '50%';
                        container.appendChild(confetti);
                        
                        setTimeout(() => confetti.remove(), 10000);
                    }, i * 60);
                }
            }
            
            setTimeout(createConfetti, 2200);
            setInterval(createConfetti, 12000);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.inaug-anim');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('apparue');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
            
            animElements.forEach(el => observer.observe(el));
            
            setTimeout(() => {
                animElements.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        el.classList.add('apparue');
                    }
                });
            }, 500);
        });

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180,
                        height: 180,
                        colorDark: '#0a0a0a',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        let inaugurationDiapoIndex = 0;
        const inaugurationSlides = document.querySelectorAll('#inaugurationDiaporama .slide');
        const inaugurationDots = document.querySelectorAll('#inaugurationDiapoDots span');
        const inaugurationCounter = document.getElementById('inaugurationDiapoCounter');
        let inaugurationDiapoInterval = null;

        function inaugurationUpdateDiapo() {
            inaugurationSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === inaugurationDiapoIndex);
            });
            inaugurationDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === inaugurationDiapoIndex);
            });
            if (inaugurationCounter) {
                inaugurationCounter.textContent = (inaugurationDiapoIndex + 1) + ' / ' + inaugurationSlides.length;
            }
        }

        function inaugurationDiapoChange(direction) {
            inaugurationDiapoIndex += direction;
            if (inaugurationDiapoIndex < 0) inaugurationDiapoIndex = inaugurationSlides.length - 1;
            if (inaugurationDiapoIndex >= inaugurationSlides.length) inaugurationDiapoIndex = 0;
            inaugurationUpdateDiapo();
            resetInaugurationDiapoAuto();
        }

        function inaugurationDiapoGoTo(index) {
            inaugurationDiapoIndex = index;
            inaugurationUpdateDiapo();
            resetInaugurationDiapoAuto();
        }

        function resetInaugurationDiapoAuto() {
            if (inaugurationDiapoInterval) clearInterval(inaugurationDiapoInterval);
            if (inaugurationSlides.length > 1) {
                inaugurationDiapoInterval = setInterval(() => {
                    inaugurationDiapoIndex = (inaugurationDiapoIndex + 1) % inaugurationSlides.length;
                    inaugurationUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (inaugurationSlides.length > 0) {
                inaugurationUpdateDiapo();
                resetInaugurationDiapoAuto();
                
                const container = document.getElementById('inaugurationDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (inaugurationDiapoInterval) clearInterval(inaugurationDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetInaugurationDiapoAuto);
                }
            }
        });

        // ================================================================
        // TÉLÉCHARGEMENT — CAPTURE #downloadCard (Hero + Carte + QR)
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const card = document.getElementById('downloadCard');
            
            if (!card) {
                alert('Carte introuvable');
                return;
            }
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                await new Promise(r => setTimeout(r, 1500));
                
                for (let i = 0; i < 10; i++) {
                    const qrCanvas = document.querySelector('#qrcode canvas');
                    const qrImg = document.querySelector('#qrcode img');
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
                
                card.querySelectorAll('.inaug-anim').forEach(el => {
                    el.classList.add('apparue');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.visibility = 'visible';
                });
                
                card.querySelectorAll('.inauguration-blason, .inauguration-star, .inauguration-badge, .inauguration-guest, .inauguration-divider, .inauguration-hosts-intro, .inauguration-host-name, .inauguration-event-type').forEach(el => {
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.animation = 'none';
                    el.style.visibility = 'visible';
                });
                
                await new Promise(r => setTimeout(r, 300));
                
                const canvas = await html2canvas(card, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#0a0a0a',
                    logging: false,
                    width: card.scrollWidth,
                    height: card.scrollHeight,
                    windowWidth: card.scrollWidth,
                    windowHeight: card.scrollHeight,
                    scrollX: 0,
                    scrollY: 0,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.getElementById('downloadCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                            clonedCard.style.transform = 'none';
                        }
                        
                        clonedDoc.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                        });
                        
                        clonedDoc.querySelectorAll('.inaug-anim').forEach(el => {
                            el.classList.add('apparue');
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.visibility = 'visible';
                        });
                        
                        clonedDoc.querySelectorAll('.inauguration-blason, .inauguration-star, .inauguration-badge, .inauguration-guest, .inauguration-divider, .inauguration-hosts-intro, .inauguration-host-name, .inauguration-event-type').forEach(el => {
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.animation = 'none';
                            el.style.visibility = 'visible';
                            el.style.webkitTextFillColor = 'initial';
                        });
                        
                        const qrBox = clonedDoc.querySelector('.inauguration-qr-box');
                        if (qrBox) {
                            qrBox.style.display = 'inline-block';
                            qrBox.style.visibility = 'visible';
                            qrBox.style.opacity = '1';
                        }
                    }
                });
                
                const link = document.createElement('a');
                link.download = `inauguration_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                
                btnText.textContent = '✓ TÉLÉCHARGÉ';
                setTimeout(() => btnText.textContent = 'TÉLÉCHARGER', 3000);
            } catch(e) {
                console.error('Erreur téléchargement:', e);
                btnText.textContent = 'ERREUR';
                setTimeout(() => btnText.textContent = 'TÉLÉCHARGER', 3000);
            }
            
            btn.disabled = false;
        }

        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.inauguration-boisson-item.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!selectedBoissons.includes(id)) selectedBoissons.push(id);
            });
            updateCount();
        });
        function toggleBoisson(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const idx = selectedBoissons.indexOf(id);
                if (idx > -1) selectedBoissons.splice(idx, 1);
                element.querySelector('input[type="checkbox"]').checked = false;
                updateCount(); return;
            }
            if (selectedBoissons.length >= 2) { alert('Maximum 2 boissons.'); return; }
            element.classList.add('selected');
            selectedBoissons.push(id);
            element.querySelector('input[type="checkbox"]').checked = true;
            updateCount();
        }
        function updateCount() {
            const el = document.getElementById('selectedCount');
            if (el) el.textContent = selectedBoissons.length;
        }
        <?php endif; ?>
    </script>

</body>
</html>