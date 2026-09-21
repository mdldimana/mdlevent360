<?php
/**
 * ============================================================
 * TEMPLATE : DIPLÔME / GRADUATION - v3
 * ============================================================
 * 
 * Améliorations v3 :
 * - Photo de fond en background (non floue)
 * - Nom de la table
 * - Diaporama photos plein écran
 * - Animations de sections en cascade
 * - Palette de couleurs raffinée (navy/or/crème)
 * - Suppression du header
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
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
    
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            /* Palette raffinée */
            --navy: #0a1633;
            --navy-dark: #050b1f;
            --navy-mid: #132148;
            --navy-light: #1e2d5c;
            --gold: #d4af37;
            --gold-light: #f4e5a1;
            --gold-soft: #c9a961;
            --gold-dark: #8b6914;
            --cream: #f7f1e3;
            --paper: #fdfaf3;
            --paper-alt: #f5efe3;
            --text: #2a2420;
            --text-muted: #6a5a4a;
            --text-light: #9a8a7a;
            --border-gold: rgba(212, 175, 55, 0.35);
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        /* ============================================
           PHOTO DE FOND (BACKGROUND PRINCIPAL - NON FLOUE)
           ============================================ */
        html {
            background: var(--navy-dark);
        }
        
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: var(--navy-dark);
            <?php else: ?>
            background: var(--navy-dark);
            <?php endif; ?>
            color: var(--cream);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* Overlay dégradé subtil par-dessus la photo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background: 
                radial-gradient(ellipse at top, rgba(30, 45, 92, 0.55) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(5, 11, 31, 0.7) 0%, 
                    rgba(10, 22, 51, 0.6) 30%,
                    rgba(5, 11, 31, 0.75) 70%,
                    rgba(5, 11, 31, 0.9) 100%);
            pointer-events: none;
        }
        
        /* Contenu au-dessus de l'overlay */
        .graduation-hero,
        .certificate-card,
        .graduation-section,
        .graduation-footer {
            position: relative;
            z-index: 2;
        }
        
        /* ============================================
           INTRO : CHAPEAU QUI TOMBE
           ============================================ */
        .graduation-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(180deg, var(--navy) 0%, var(--navy-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introFadeOut 2.5s ease-in-out 2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .graduation-hat-intro {
            font-size: 140px;
            color: var(--gold);
            filter: drop-shadow(0 20px 40px rgba(212, 175, 55, 0.6));
            animation: hatDrop 1.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes hatDrop {
            0% { transform: translateY(-200px) rotate(-45deg); opacity: 0; }
            60% { transform: translateY(20px) rotate(5deg); opacity: 1; }
            100% { transform: translateY(0) rotate(0deg); opacity: 1; }
        }
        
        /* ============================================
           ANIMATIONS DE SECTIONS EN CASCADE
           ============================================ */
        .grad-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .grad-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        /* Variantes */
        .grad-anim.from-left {
            transform: translateX(-80px);
        }
        .grad-anim.from-left.apparue {
            transform: translateX(0);
        }
        
        .grad-anim.from-right {
            transform: translateX(80px);
        }
        .grad-anim.from-right.apparue {
            transform: translateX(0);
        }
        
        .grad-anim.zoom-in {
            transform: scale(0.85);
        }
        .grad-anim.zoom-in.apparue {
            transform: scale(1);
        }
        
        /* Délais en cascade */
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           HERO (SANS NAVBAR)
           ============================================ */
        .graduation-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px 80px;
            z-index: 10;
            overflow: hidden;
        }
        
        /* Rayons de lumière dorée */
        .light-rays {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 60%;
            background: conic-gradient(from 180deg at 50% 0%, 
                transparent 0deg,
                rgba(212, 175, 55, 0.04) 20deg,
                transparent 40deg,
                rgba(212, 175, 55, 0.07) 60deg,
                transparent 80deg,
                rgba(212, 175, 55, 0.04) 100deg,
                transparent 120deg,
                rgba(212, 175, 55, 0.07) 140deg,
                transparent 160deg,
                rgba(212, 175, 55, 0.04) 180deg,
                transparent 200deg,
                rgba(212, 175, 55, 0.07) 220deg,
                transparent 240deg,
                rgba(212, 175, 55, 0.04) 260deg,
                transparent 280deg,
                rgba(212, 175, 55, 0.07) 300deg,
                transparent 320deg,
                rgba(212, 175, 55, 0.04) 340deg,
                transparent 360deg);
            animation: raysRotate 25s linear infinite;
            z-index: 0;
            pointer-events: none;
        }
        @keyframes raysRotate {
            0% { transform: translateX(-50%) rotate(0deg); }
            100% { transform: translateX(-50%) rotate(360deg); }
        }
        
        .graduation-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 800px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.7s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Grand chapeau doré */
        .graduation-hat-big {
            font-size: 100px;
            color: var(--gold);
            filter: drop-shadow(0 10px 30px rgba(212, 175, 55, 0.5));
            margin-bottom: 20px;
            animation: hatFloat 3s ease-in-out infinite;
        }
        @keyframes hatFloat {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-10px) rotate(3deg); }
        }
        
        .graduation-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy-dark);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.25em;
            border-radius: 4px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            text-transform: uppercase;
        }
        
        .graduation-guest {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: clamp(28px, 5vw, 42px);
            color: var(--gold-light);
            margin-bottom: 30px;
            letter-spacing: 0.02em;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.5);
        }
        
        .graduation-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .graduation-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .graduation-divider .icon {
            font-size: 24px;
            color: var(--gold);
        }
        
        .graduation-hosts-intro {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 18px;
            letter-spacing: 0.1em;
            color: var(--gold-light);
            margin-bottom: 20px;
        }
        
        .graduation-host-name {
            font-family: 'Cinzel', serif;
            font-size: clamp(48px, 10vw, 96px);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, 
                var(--gold-light) 0%, 
                var(--gold) 30%,
                var(--gold-dark) 60%,
                var(--gold) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldShimmer 6s ease-in-out infinite;
            letter-spacing: 0.02em;
            margin-bottom: 16px;
            text-transform: uppercase;
            filter: drop-shadow(0 4px 20px rgba(212, 175, 55, 0.4));
        }
        @keyframes goldShimmer {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .graduation-event-type {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            letter-spacing: 0.5em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* ============================================
           CARTE CERTIFICAT (Détails)
           ============================================ */
        .certificate-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 60px 50px;
            background: var(--paper);
            background-image: 
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><filter id="n"><feTurbulence baseFrequency="0.9" numOctaves="3"/></filter><rect width="100" height="100" filter="url(%23n)" opacity="0.04"/></svg>'),
                radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.06) 0%, transparent 70%);
            border: 3px double var(--gold);
            box-shadow: 
                0 0 0 8px var(--navy-dark),
                0 0 0 10px var(--gold),
                0 20px 60px rgba(0, 0, 0, 0.6);
            z-index: 10;
            color: var(--text);
            text-align: center;
        }
        @media (max-width: 640px) {
            .certificate-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        /* Ornements aux coins */
        .certificate-card::before,
        .certificate-card::after {
            content: '❦';
            position: absolute;
            font-size: 40px;
            color: var(--gold);
        }
        .certificate-card::before { top: 8px; left: 12px; }
        .certificate-card::after { bottom: 8px; right: 12px; }
        
        .certificate-title {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            letter-spacing: 0.4em;
            color: var(--gold-dark);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .certificate-main-title {
            font-family: 'Cinzel', serif;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 0.05em;
            color: var(--navy);
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gold);
            text-transform: uppercase;
        }
        
        .certificate-text {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 20px;
            color: var(--text-muted);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .certificate-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 30px;
        }
        @media (max-width: 640px) {
            .certificate-info-grid { grid-template-columns: 1fr; }
        }
        
        .certificate-info-item {
            padding: 20px;
            border: 2px solid var(--gold);
            background: white;
            text-align: center;
            transition: all 0.3s ease;
        }
        .certificate-info-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.3);
            background: var(--cream);
        }
        
        .certificate-info-item .label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--gold-dark);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .certificate-info-item .value {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.3;
        }
        .certificate-info-item .value .sub {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            font-size: 14px;
            font-style: italic;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 400;
        }
        
        /* ⭐ CARTE TABLE */
        .certificate-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05)) !important;
            border: 2px solid var(--gold) !important;
            animation: tableCardPulse 3s ease-in-out infinite;
        }
        
        @keyframes tableCardPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
            50% { box-shadow: 0 0 30px 0 rgba(212, 175, 55, 0.6); }
        }
        
        .certificate-table-item .value {
            font-size: 26px !important;
            color: var(--gold-dark) !important;
            letter-spacing: 0.05em;
        }
        
        .certificate-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 12px 28px;
            background: var(--navy);
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-decoration: none;
            border: 2px solid var(--gold);
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .certificate-btn-itinerary:hover {
            background: var(--gold);
            color: var(--navy);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
        }
        
        /* ============================================
           SECTIONS
           ============================================ */
        .graduation-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(5, 11, 31, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 6px var(--navy),
                0 0 60px rgba(212, 175, 55, 0.25);
            z-index: 10;
            color: var(--cream);
        }
        @media (max-width: 640px) {
            .graduation-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .graduation-section-title {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            color: var(--gold);
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }
        
        /* ============================================
           DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .graduation-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: var(--navy-dark);
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 6px var(--navy),
                0 0 40px rgba(212, 175, 55, 0.3);
        }
        
        .graduation-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 0;
            background: var(--navy-dark);
        }
        
        .graduation-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .graduation-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--navy-dark);
            padding: 8px;
        }
        
        /* Flèches */
        .graduation-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.9);
            border: 2px solid var(--gold-light);
            color: var(--navy-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
        }
        
        .graduation-diapo-arrow:hover {
            background: var(--gold-light);
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 12px 32px rgba(212, 175, 55, 0.6);
        }
        
        .graduation-diapo-arrow.prev { left: 16px; }
        .graduation-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .graduation-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .graduation-diapo-arrow.prev { left: 8px; }
            .graduation-diapo-arrow.next { right: 8px; }
        }
        
        /* Compteur */
        .graduation-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(5, 11, 31, 0.9);
            border: 2px solid var(--gold);
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 8px 16px;
            z-index: 10;
        }
        
        /* Points */
        .graduation-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(5, 11, 31, 0.85);
            padding: 10px 20px;
            border: 2px solid var(--gold);
            backdrop-filter: blur(10px);
        }
        
        .graduation-diapo-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .graduation-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.9);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .graduation-form-group { margin-bottom: 24px; }
        .graduation-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .graduation-form-group input,
        .graduation-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: var(--navy);
            border: 2px solid rgba(212, 175, 55, 0.4);
            color: var(--cream);
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            transition: all 0.3s ease;
        }
        .graduation-form-group input:focus,
        .graduation-form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.2);
        }
        
        .graduation-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .graduation-options-grid { grid-template-columns: 1fr; }
        }
        
        .graduation-option-radio { display: none; }
        .graduation-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 2px solid rgba(212, 175, 55, 0.4);
            background: var(--navy);
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--gold-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .graduation-option-label:hover {
            border-color: var(--gold);
            color: var(--gold);
        }
        .graduation-option-radio:checked + .graduation-option-label {
            border-color: var(--gold);
            background: var(--gold);
            color: var(--navy-dark);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.5);
        }
        
        .graduation-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy-dark);
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 0 0 3px var(--navy-dark),
                0 0 0 5px var(--gold-dark),
                0 12px 30px rgba(212, 175, 55, 0.4);
            margin-top: 10px;
        }
        .graduation-btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 0 0 3px var(--navy-dark),
                0 0 0 5px var(--gold),
                0 16px 40px rgba(212, 175, 55, 0.6);
        }
        
        /* Boissons */
        .graduation-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .graduation-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid rgba(212, 175, 55, 0.4);
            background: var(--navy);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.1em;
            color: var(--gold-light);
            text-transform: uppercase;
        }
        .graduation-boisson-item.selected {
            border-color: var(--gold);
            background: var(--gold);
            color: var(--navy-dark);
        }
        .graduation-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .graduation-boisson-item.selected .check { opacity: 1; }
        
        .graduation-boisson-category { margin-bottom: 20px; }
        .graduation-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            color: var(--gold);
            margin-bottom: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        
        /* QR */
        .graduation-qr-wrapper { text-align: center; }
        .graduation-qr-box {
            display: inline-block;
            padding: 22px;
            background: var(--cream);
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 6px var(--navy-dark),
                0 0 0 8px var(--gold-dark),
                0 0 40px rgba(212, 175, 55, 0.4);
            position: relative;
        }
        .graduation-qr-box::before,
        .graduation-qr-box::after {
            content: '✦';
            position: absolute;
            font-size: 24px;
            color: var(--gold);
        }
        .graduation-qr-box::before { top: -12px; left: -12px; }
        .graduation-qr-box::after { bottom: -12px; right: -12px; }
        
        /* Footer */
        .graduation-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .graduation-footer-brand {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 900;
            color: var(--gold);
            letter-spacing: 0.2em;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
            text-transform: uppercase;
        }
        .graduation-footer-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 17px;
            color: var(--gold-light);
            margin-bottom: 30px;
        }
        
        .graduation-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-decoration: none;
            text-transform: uppercase;
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.35);
            transition: all 0.3s ease;
            border: 2px solid var(--gold);
        }
        .graduation-btn-whatsapp:hover {
            transform: translateY(-3px) scale(1.03);
            color: white;
            box-shadow: 0 16px 40px rgba(37, 211, 102, 0.5);
        }
        
        /* Alerts */
        .graduation-alert {
            padding: 18px 26px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            border-left: 4px solid;
        }
        .graduation-alert-success { border-color: #46d369; background: rgba(70, 211, 105, 0.15); color: #a3e8b8; }
        .graduation-alert-danger { border-color: var(--gold); background: rgba(212, 175, 55, 0.15); color: var(--gold-light); }
        .graduation-alert-warning { border-color: #ffa500; background: rgba(255, 165, 0, 0.15); color: #ffd58a; }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy-dark);
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 
                0 0 0 3px var(--navy-dark),
                0 0 0 5px var(--gold-dark),
                0 12px 32px rgba(212, 175, 55, 0.5);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 0 0 3px var(--navy-dark),
                0 0 0 5px var(--gold),
                0 16px 40px rgba(212, 175, 55, 0.7);
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 20px; font-size: 10px; }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- INTRO -->
    <div class="graduation-intro">
        <div class="graduation-hat-intro">🎓</div>
    </div>

    <!-- HERO (SANS NAVBAR) -->
    <section class="graduation-hero">
        <div class="light-rays"></div>
        
        <div class="graduation-blason">
            
            <div class="graduation-hat-big">🎓</div>
            
            <div class="graduation-badge">
                ✦ CÉRÉMONIE OFFICIELLE ✦
            </div>
            
            <div class="graduation-guest">
                À l'attention de <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="graduation-divider">
                <div class="line"></div>
                <span class="icon">✦</span>
                <div class="line"></div>
            </div>
            
            <div class="graduation-hosts-intro">
                ✦ Vous êtes convié(e) à la cérémonie de ✦
            </div>
            <div class="graduation-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="graduation-event-type">
                🎓 <?php echo htmlspecialchars(strtoupper($eventType)); ?> 🎓
            </div>
            
        </div>
    </section>

    <!-- CERTIFICAT (Animé) -->
    <div class="certificate-card grad-anim zoom-in">
        
        <div class="certificate-title">Université de la Réussite</div>
        <div class="certificate-main-title">Certificat de Célébration</div>
        
        <p class="certificate-text">
            Ceci certifie que la cérémonie de remise des diplômes aura lieu en l'honneur de 
            <strong style="color:var(--gold-dark);font-style:normal;"><?php echo htmlspecialchars($host1); ?></strong>
        </p>
        
        <div class="certificate-info-grid">
            
            <div class="certificate-info-item grad-anim delay-1">
                <div class="label">Date</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="certificate-info-item grad-anim delay-2">
                <div class="label">Heure</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="certificate-info-item grad-anim delay-3" style="grid-column: 1 / -1;">
                <div class="label">Lieu de la cérémonie</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" rel="noopener" class="certificate-btn-itinerary">
                    <i class="fas fa-route"></i> Itinéraire
                </a>
            </div>
            
            <!-- ⭐ TABLE ASSIGNÉE -->
            <?php if ($hasTable): ?>
            <div class="certificate-info-item certificate-table-item grad-anim delay-4">
                <div class="label">🎓 Votre table</div>
                <div class="value">
                    <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="certificate-info-item grad-anim delay-5" style="grid-column: 1 / -1;">
                <div class="label">Places réservées</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <?php if ($message): ?>
        <div class="graduation-section grad-anim apparue">
            <div class="graduation-alert graduation-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ⭐ DIAPORAMA PHOTOS -->
    <?php if ($hasPhotos): ?>
        <div class="graduation-section grad-anim from-left">
            <div class="graduation-section-title">✦ Souvenirs de Promotion ✦</div>
            
            <div class="graduation-diaporama" id="graduationDiaporama">
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
                    <button class="graduation-diapo-arrow prev" onclick="graduationDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="graduation-diapo-arrow next" onclick="graduationDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="graduation-diapo-counter" id="graduationDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="graduation-diapo-dots" id="graduationDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="graduationDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- QR CODE -->
    <div class="graduation-section grad-anim from-right">
        <div class="graduation-section-title">✦ Code d'Accès ✦</div>
        <div class="graduation-qr-wrapper">
            <div class="graduation-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Cinzel',serif;font-size:14px;color:var(--gold);margin-top:24px;font-weight:700;letter-spacing:0.2em;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="graduation-section grad-anim from-left">
            <div class="graduation-section-title">🎓 Confirmation 🎓</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="graduation-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="graduation-form-group">
                    <label>Votre réponse</label>
                    <div class="graduation-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="graduation-option-radio">
                            <label for="presenceOui" class="graduation-option-label">✦ Présent(e)</label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="graduation-option-radio">
                            <label for="presenceNon" class="graduation-option-label">✗ Absent(e)</label>
                        </div>
                    </div>
                </div>
                
                <div class="graduation-form-group">
                    <label>Message</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="graduation-btn-submit">
                    <i class="fas fa-graduation-cap"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="graduation-section grad-anim from-right">
            <div class="graduation-section-title">🥂 Réception 🥂</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--gold);font-family:'Cinzel',serif;font-size:13px;font-weight:700;letter-spacing:0.15em;padding:20px 0;text-transform:uppercase;">
                    <i class="fas fa-lock"></i> Préférences enregistrées
                </div>
                <div class="graduation-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="graduation-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Cormorant Garamond',serif;font-style:italic;font-size:16px;color:var(--gold-light);margin-bottom:24px;">
                        Sélectionnez <strong style="color:var(--gold);font-style:normal;">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="graduation-boisson-category">
                            <div class="graduation-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="graduation-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="graduation-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="graduation-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="graduation-footer grad-anim">
        <div class="graduation-footer-brand">🎓 <?php echo htmlspecialchars($appName); ?></div>
        <div class="graduation-footer-tagline">Célébrons ensemble vos réussites</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="graduation-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(212, 175, 55, 0.3);font-family:'Cinzel',serif;font-size:11px;color:var(--gold-light);letter-spacing:0.3em;text-transform:uppercase;">
            ✦ © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS ✦
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.grad-anim');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('apparue');
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: 0.15,
                rootMargin: '0px 0px -60px 0px'
            });
            
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

        // QR
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180, height: 180,
                        colorDark: '#0a1633', colorLight: '#fdfaf3',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA PHOTOS
        // ================================================================
        let graduationDiapoIndex = 0;
        const graduationSlides = document.querySelectorAll('#graduationDiaporama .slide');
        const graduationDots = document.querySelectorAll('#graduationDiapoDots span');
        const graduationCounter = document.getElementById('graduationDiapoCounter');
        let graduationDiapoInterval = null;

        function graduationUpdateDiapo() {
            graduationSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === graduationDiapoIndex);
            });
            graduationDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === graduationDiapoIndex);
            });
            if (graduationCounter) {
                graduationCounter.textContent = (graduationDiapoIndex + 1) + ' / ' + graduationSlides.length;
            }
        }

        function graduationDiapoChange(direction) {
            graduationDiapoIndex += direction;
            if (graduationDiapoIndex < 0) graduationDiapoIndex = graduationSlides.length - 1;
            if (graduationDiapoIndex >= graduationSlides.length) graduationDiapoIndex = 0;
            graduationUpdateDiapo();
            resetGraduationDiapoAuto();
        }

        function graduationDiapoGoTo(index) {
            graduationDiapoIndex = index;
            graduationUpdateDiapo();
            resetGraduationDiapoAuto();
        }

        function resetGraduationDiapoAuto() {
            if (graduationDiapoInterval) clearInterval(graduationDiapoInterval);
            if (graduationSlides.length > 1) {
                graduationDiapoInterval = setInterval(() => {
                    graduationDiapoIndex = (graduationDiapoIndex + 1) % graduationSlides.length;
                    graduationUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (graduationSlides.length > 0) {
                graduationUpdateDiapo();
                resetGraduationDiapoAuto();
                
                const container = document.getElementById('graduationDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (graduationDiapoInterval) clearInterval(graduationDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetGraduationDiapoAuto);
                }
            }
        });

        // Download
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.graduation-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#050b1f', logging: false
                });
                const link = document.createElement('a');
                link.download = `graduation_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                btnText.textContent = '✓ Téléchargé';
                setTimeout(() => btnText.textContent = 'Télécharger', 3000);
            } catch(e) {
                btnText.textContent = 'Erreur';
                setTimeout(() => btnText.textContent = 'Télécharger', 3000);
            }
            btn.disabled = false;
        }

        // Boissons
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.graduation-boisson-item.selected').forEach(item => {
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