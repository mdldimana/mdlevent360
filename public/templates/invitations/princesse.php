<?php
/**
 * ============================================================
 * TEMPLATE : PRINCESSE - v3 (Polices Defile)
 * ============================================================
 * 
 * Polices identiques à defile.php :
 * - Playfair Display (titres)
 * - Didact Gothic (textes)
 * - Inter (détails)
 * - Italiana (grands noms)
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
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Didact+Gothic&family=Inter:wght@300;400;500;600;700&family=Italiana&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --rose-pale: #fff5f8;
            --rose-light: #ffd6e7;
            --rose-medium: #ff9bc4;
            --rose-deep: #e91e63;
            --gold: #ffd700;
            --gold-dark: #d4af37;
            --blue-light: #d4e8fc;
            --blue-medium: #7cb8f0;
            --purple: #b19cd9;
            --text: #4a2c4a;
            --text-muted: #8b6a8b;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        /* ============================================
           PHOTO DE FOND EN BACKGROUND
           ============================================ */
        html {
            background: #1a0a2e;
        }
        
        body {
            font-family: 'Didact Gothic', sans-serif;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: #1a0a2e;
            <?php else: ?>
            background: linear-gradient(180deg, 
                #1a0a2e 0%,
                #2d1054 30%,
                #4a1a6e 60%,
                #7a3a9e 100%);
            background-attachment: fixed;
            <?php endif; ?>
            color: var(--text);
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
                radial-gradient(ellipse at top, rgba(26, 10, 46, 0.7) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(26, 10, 46, 0.75) 0%, 
                    rgba(45, 16, 84, 0.6) 30%,
                    rgba(26, 10, 46, 0.8) 70%,
                    rgba(26, 10, 46, 0.95) 100%);
            pointer-events: none;
        }
        
        .princess-hero,
        .princess-card,
        .princess-section,
        .princess-footer {
            position: relative;
            z-index: 2;
        }
        
        /* ============================================
           ANIMATIONS DE SECTIONS
           ============================================ */
        .princess-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .princess-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .princess-anim.from-left { transform: translateX(-80px); }
        .princess-anim.from-left.apparue { transform: translateX(0); }
        .princess-anim.from-right { transform: translateX(80px); }
        .princess-anim.from-right.apparue { transform: translateX(0); }
        .princess-anim.zoom-in { transform: scale(0.85); }
        .princess-anim.zoom-in.apparue { transform: scale(1); }
        
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           ÉTOILES
           ============================================ */
        .stars-container {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 5;
            overflow: hidden;
        }
        .star {
            position: absolute;
            color: var(--gold);
            animation: starTwinkle 3s ease-in-out infinite;
            filter: drop-shadow(0 0 10px currentColor);
        }
        @keyframes starTwinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.3); }
        }
        
        /* ============================================
           INTRO : CHÂTEAU
           ============================================ */
        .castle-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(180deg, #1a0a2e 0%, #4a1a6e 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            animation: introFadeOut 2.5s ease-in-out 2.5s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .castle-svg {
            width: 400px;
            max-width: 90vw;
            height: auto;
            opacity: 0;
            transform: translateY(100px);
            animation: castleRise 2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            filter: drop-shadow(0 0 60px rgba(255, 215, 0, 0.6));
        }
        @keyframes castleRise {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ============================================
           HERO
           ============================================ */
        .princess-hero {
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
        
        .princess-moon {
            position: absolute;
            top: 12%;
            right: 12%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, var(--gold), var(--gold-dark));
            box-shadow: 
                0 0 60px rgba(255, 215, 0, 0.6),
                0 0 120px rgba(255, 215, 0, 0.3);
            z-index: 0;
        }
        .princess-moon::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 25%;
            width: 70%;
            height: 70%;
            border-radius: 50%;
            background: radial-gradient(circle at 40% 40%, rgba(255,255,255,0.3) 0%, transparent 60%);
        }
        
        .princess-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 800px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.2s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .princess-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 26px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: #4a1a6e;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 0.4em;
            border-radius: 4px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.4);
            text-transform: uppercase;
        }
        
        .princess-guest {
            font-family: 'Playfair Display', serif;
            font-size: clamp(48px, 9vw, 88px);
            font-weight: 400;
            font-style: italic;
            line-height: 1;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 4px 30px rgba(0,0,0,0.6);
            background: linear-gradient(135deg, var(--gold) 0%, #fff8dc 50%, var(--gold) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldShimmer 4s ease-in-out infinite;
        }
        @keyframes goldShimmer {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .princess-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .princess-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .princess-divider .icon {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 24px;
            color: var(--gold);
            animation: sparkle 3s ease-in-out infinite;
        }
        @keyframes sparkle {
            0%, 100% { transform: rotate(0deg) scale(1); filter: drop-shadow(0 0 10px var(--gold)); }
            50% { transform: rotate(180deg) scale(1.2); filter: drop-shadow(0 0 25px var(--gold)); }
        }
        
        .princess-hosts-intro {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 18px;
            letter-spacing: 0.05em;
            color: var(--silver);
            margin-bottom: 20px;
        }
        
        .princess-host-name {
            font-family: 'Italiana', serif;
            font-size: clamp(56px, 12vw, 130px);
            line-height: 0.9;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            background: linear-gradient(180deg, 
                var(--white) 0%, 
                var(--silver) 50%,
                var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 30px rgba(201, 169, 97, 0.3));
        }
        
        .princess-event-type {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 13px;
            letter-spacing: 0.6em;
            color: var(--gold);
            text-transform: uppercase;
            padding-left: 0.6em;
        }
        
        /* ============================================
           CARTE ENCHANTÉE
           ============================================ */
        .princess-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: linear-gradient(180deg, 
                rgba(255, 245, 248, 0.97) 0%, 
                rgba(255, 214, 231, 0.97) 100%);
            border-radius: 20px;
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 8px rgba(74, 26, 110, 0.6),
                0 0 60px rgba(255, 215, 0, 0.4);
            z-index: 10;
        }
        .princess-card::before,
        .princess-card::after {
            content: '✦';
            position: absolute;
            font-size: 28px;
            color: var(--gold);
            filter: drop-shadow(0 0 10px var(--gold));
        }
        .princess-card::before { top: -20px; left: 50%; transform: translateX(-50%); }
        .princess-card::after { bottom: -20px; left: 50%; transform: translateX(-50%); }
        @media (max-width: 640px) {
            .princess-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        .princess-card-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            font-weight: 400;
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            color: var(--rose-deep);
            border-bottom: 2px dashed var(--rose-medium);
        }
        
        .princess-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .princess-info-grid { grid-template-columns: 1fr; }
        }
        
        .princess-info-item {
            padding: 24px 20px;
            background: white;
            border-radius: 16px;
            border: 2px solid var(--gold);
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(255, 215, 0, 0.15);
        }
        .princess-info-item:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 12px 40px rgba(255, 215, 0, 0.3);
        }
        
        .princess-info-item .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--rose-medium), var(--purple));
            color: white;
            font-size: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 6px 20px rgba(255, 155, 196, 0.4);
        }
        .princess-info-item .label {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--rose-deep);
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .princess-info-item .value {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 400;
            color: var(--text);
            line-height: 1.3;
        }
        .princess-info-item .value .sub {
            display: block;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 14px;
            font-style: italic;
            color: var(--text-muted);
            margin-top: 6px;
        }
        
        .princess-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, 
                rgba(255, 214, 231, 0.8) 0%, 
                rgba(255, 215, 0, 0.3) 100%) !important;
            border: 3px solid var(--gold) !important;
            animation: tableCardPulse 3s ease-in-out infinite;
        }
        
        @keyframes tableCardPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
            50% { box-shadow: 0 0 35px 0 rgba(255, 215, 0, 0.7); }
        }
        
        .princess-table-item .value {
            font-family: 'Italiana', serif !important;
            font-size: 32px !important;
            color: var(--rose-deep) !important;
            letter-spacing: 0.05em;
        }
        
        .princess-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: #4a1a6e;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            text-decoration: none;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(255, 215, 0, 0.4);
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .princess-btn-itinerary:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 32px rgba(255, 215, 0, 0.6);
            color: #4a1a6e;
        }
        
        /* Sections */
        .princess-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: linear-gradient(180deg, 
                rgba(255, 245, 248, 0.97) 0%, 
                rgba(255, 214, 231, 0.97) 100%);
            border-radius: 20px;
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 8px rgba(74, 26, 110, 0.6),
                0 0 60px rgba(255, 215, 0, 0.3);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .princess-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .princess-section-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            font-weight: 400;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            color: var(--rose-deep);
            border-bottom: 2px dashed var(--rose-medium);
        }
        
        /* ============================================
           DIAPORAMA PHOTOS
           ============================================ */
        .princess-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: #1a0a2e;
            border-radius: 16px;
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 6px rgba(74, 26, 110, 0.6),
                0 0 40px rgba(255, 215, 0, 0.4);
        }
        
        .princess-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a0a2e;
        }
        
        .princess-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .princess-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #1a0a2e;
            padding: 10px;
        }
        
        .princess-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border: 2px solid white;
            color: #4a1a6e;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(255, 215, 0, 0.5);
        }
        
        .princess-diapo-arrow:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 12px 32px rgba(255, 215, 0, 0.8);
        }
        
        .princess-diapo-arrow.prev { left: 16px; }
        .princess-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .princess-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .princess-diapo-arrow.prev { left: 8px; }
            .princess-diapo-arrow.next { right: 8px; }
        }
        
        .princess-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(26, 10, 46, 0.9);
            border: 2px solid var(--gold);
            color: var(--gold);
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            letter-spacing: 0.2em;
            padding: 8px 16px;
            border-radius: 999px;
            z-index: 10;
        }
        
        .princess-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(26, 10, 46, 0.85);
            padding: 10px 20px;
            border-radius: 999px;
            border: 1px solid rgba(255, 215, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .princess-diapo-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .princess-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
            box-shadow: 0 0 12px var(--gold);
        }
        
        /* Formulaires */
        .princess-form-group { margin-bottom: 24px; }
        .princess-form-group label {
            display: block;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--rose-deep);
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .princess-form-group input,
        .princess-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: white;
            border: 2px solid var(--gold);
            border-radius: 12px;
            color: var(--text);
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-style: italic;
            transition: all 0.3s ease;
        }
        .princess-form-group input:focus,
        .princess-form-group textarea:focus {
            outline: none;
            border-color: var(--rose-deep);
            box-shadow: 0 0 0 4px rgba(255, 155, 196, 0.2);
        }
        
        .princess-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .princess-options-grid { grid-template-columns: 1fr; }
        }
        
        .princess-option-radio { display: none; }
        .princess-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px solid var(--gold);
            border-radius: 12px;
            background: white;
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-style: italic;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .princess-option-label:hover {
            border-color: var(--rose-medium);
            color: var(--rose-deep);
            transform: translateY(-2px);
        }
        .princess-option-radio:checked + .princess-option-label {
            border-color: var(--rose-deep);
            background: linear-gradient(135deg, var(--rose-light), var(--gold));
            color: var(--text);
            box-shadow: 0 0 0 4px rgba(255, 155, 196, 0.2);
        }
        
        .princess-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: #4a1a6e;
            border: none;
            border-radius: 4px;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 12px 32px rgba(255, 215, 0, 0.5);
            margin-top: 10px;
        }
        .princess-btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(255, 215, 0, 0.7);
        }
        
        /* Boissons */
        .princess-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .princess-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid var(--gold);
            border-radius: 999px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-style: italic;
            color: var(--text-muted);
        }
        .princess-boisson-item.selected {
            border-color: var(--rose-deep);
            background: linear-gradient(135deg, var(--rose-light), var(--gold));
            color: var(--text);
        }
        .princess-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .princess-boisson-item.selected .check { opacity: 1; }
        
        .princess-boisson-category { margin-bottom: 20px; }
        .princess-boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 20px;
            color: var(--rose-deep);
            margin-bottom: 12px;
        }
        
        /* QR */
        .princess-qr-wrapper { text-align: center; }
        .princess-qr-box {
            display: inline-block;
            padding: 22px;
            background: white;
            border-radius: 16px;
            border: 3px solid var(--gold);
            box-shadow: 
                0 12px 40px rgba(255, 215, 0, 0.4),
                0 0 0 6px rgba(74, 26, 110, 0.6);
            position: relative;
        }
        .princess-qr-box::before {
            content: '👑';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 40px;
            animation: crownFloat 3s ease-in-out infinite;
            filter: drop-shadow(0 0 15px var(--gold));
        }
        @keyframes crownFloat {
            0%, 100% { transform: translateX(-50%) translateY(0) rotate(-5deg); }
            50% { transform: translateX(-50%) translateY(-8px) rotate(5deg); }
        }
        
        /* Footer */
        .princess-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .princess-footer-brand {
            font-family: 'Italiana', serif;
            font-size: 48px;
            letter-spacing: 0.2em;
            background: linear-gradient(135deg, var(--gold), #fff8dc, var(--gold));
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldShimmer 4s ease-in-out infinite;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .princess-footer-tagline {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 18px;
            color: var(--rose-light);
            margin-bottom: 30px;
        }
        
        .princess-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            text-decoration: none;
            border-radius: 4px;
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.35);
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .princess-btn-whatsapp:hover {
            transform: translateY(-3px) scale(1.03);
            color: white;
        }
        
        /* Alerts */
        .princess-alert {
            padding: 18px 26px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-style: italic;
            border-radius: 12px;
        }
        .princess-alert-success { background: #e8f5e9; color: #2d7a45; }
        .princess-alert-danger { background: var(--rose-light); color: #a01b3d; }
        .princess-alert-warning { background: #fff8e1; color: #806a00; }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: #4a1a6e;
            border: none;
            border-radius: 4px;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 12px 32px rgba(255, 215, 0, 0.5);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3.5s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 16px 40px rgba(255, 215, 0, 0.7);
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

    <!-- ÉTOILES -->
    <div class="stars-container" id="starsContainer"></div>

    <!-- INTRO -->
    <div class="castle-intro">
        <svg class="castle-svg" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="castleGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#fff8dc"/>
                    <stop offset="100%" stop-color="#ffd700"/>
                </linearGradient>
            </defs>
            <rect x="100" y="150" width="200" height="150" fill="url(#castleGrad)" opacity="0.9"/>
            <rect x="70" y="120" width="50" height="180" fill="url(#castleGrad)" opacity="0.9"/>
            <rect x="280" y="120" width="50" height="180" fill="url(#castleGrad)" opacity="0.9"/>
            <polygon points="100,150 150,80 200,150" fill="url(#castleGrad)" opacity="0.95"/>
            <polygon points="200,150 250,80 300,150" fill="url(#castleGrad)" opacity="0.95"/>
            <rect x="180" y="60" width="40" height="100" fill="url(#castleGrad)"/>
            <polygon points="180,60 200,20 220,60" fill="url(#castleGrad)"/>
            <line x1="200" y1="20" x2="200" y2="0" stroke="#ffd700" stroke-width="2"/>
            <polygon points="200,5 230,15 200,25" fill="#e91e63"/>
            <rect x="120" y="180" width="15" height="25" fill="#4a1a6e" rx="7"/>
            <rect x="160" y="180" width="15" height="25" fill="#4a1a6e" rx="7"/>
            <rect x="225" y="180" width="15" height="25" fill="#4a1a6e" rx="7"/>
            <rect x="265" y="180" width="15" height="25" fill="#4a1a6e" rx="7"/>
            <path d="M 185 300 L 185 240 Q 200 220 215 240 L 215 300 Z" fill="#4a1a6e"/>
            <text x="50" y="60" font-size="24" fill="#ffd700">✨</text>
            <text x="330" y="80" font-size="20" fill="#ffd700">✨</text>
            <text x="360" y="40" font-size="16" fill="#ffd700">✨</text>
            <text x="30" y="120" font-size="14" fill="#ffd700">✨</text>
        </svg>
    </div>

    <!-- HERO -->
    <section class="princess-hero">
        <div class="princess-moon"></div>
        
        <div class="princess-blason">
            
            <div class="princess-badge">
                👑 INVITATION ROYALE 👑
            </div>
            
            <div class="princess-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="princess-divider">
                <div class="line"></div>
                <span class="icon">✦</span>
                <div class="line"></div>
            </div>
            
            <div class="princess-hosts-intro">
                ✨ Vous êtes convié(e) au bal de ✨
            </div>
            <div class="princess-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="princess-event-type">
                👸 <?php echo htmlspecialchars(strtoupper($eventType)); ?> 👸
            </div>
            
        </div>
    </section>

    <!-- CARTE -->
    <div class="princess-card princess-anim zoom-in">
        <div class="princess-card-title">✦ DÉTAILS DU BAL ✦</div>
        <div class="princess-info-grid">
            
            <div class="princess-info-item princess-anim delay-1">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="princess-info-item princess-anim delay-2">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="princess-info-item princess-anim delay-3" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-crown"></i></div>
                <div class="label">CHÂTEAU</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" rel="noopener" class="princess-btn-itinerary">
                    <i class="fas fa-route"></i> ITINÉRAIRE
                </a>
            </div>
            
            <?php if ($hasTable): ?>
            <div class="princess-info-item princess-table-item princess-anim delay-4">
                <div class="icon"><i class="fas fa-chair"></i></div>
                <div class="label">👑 VOTRE TABLE ROYALE 👑</div>
                <div class="value">
                    <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="princess-info-item princess-anim delay-5" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="label">PLACES ROYALES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <?php if ($message): ?>
        <div class="princess-section princess-anim apparue">
            <div class="princess-alert princess-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($hasPhotos): ?>
        <div class="princess-section princess-anim from-left">
            <div class="princess-section-title">✦ SOUVENIRS MAGIQUES ✦</div>
            
            <div class="princess-diaporama" id="princessDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Souvenir ' . ($index + 1)); ?>"
                             loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                             crossorigin="anonymous">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
                
                <?php if ($photoIndex > 1): ?>
                    <button class="princess-diapo-arrow prev" onclick="princessDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="princess-diapo-arrow next" onclick="princessDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="princess-diapo-counter" id="princessDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="princess-diapo-dots" id="princessDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="princessDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="princess-section princess-anim from-right">
        <div class="princess-section-title">✦ CODE ROYAL ✦</div>
        <div class="princess-qr-wrapper">
            <div class="princess-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Didact Gothic',sans-serif;font-size:13px;color:var(--rose-deep);margin-top:24px;letter-spacing:0.3em;text-transform:uppercase;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="princess-section princess-anim from-left">
            <div class="princess-section-title">👑 CONFIRMATION 👑</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="princess-form-group">
                    <label>NOMBRE DE PERSONNES</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="princess-form-group">
                    <label>VOTRE RÉPONSE</label>
                    <div class="princess-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="princess-option-radio">
                            <label for="presenceOui" class="princess-option-label">✦ J'y serai</label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="princess-option-radio">
                            <label for="presenceNon" class="princess-option-label">✗ Je ne peux pas</label>
                        </div>
                    </div>
                </div>
                
                <div class="princess-form-group">
                    <label>MESSAGE</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="princess-btn-submit">
                    <i class="fas fa-paper-plane"></i> ENVOYER
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="princess-section princess-anim from-right">
            <div class="princess-section-title">✦ BOISSONS ROYALES ✦</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--rose-deep);font-family:'Didact Gothic',sans-serif;font-size:12px;letter-spacing:0.3em;padding:20px 0;text-transform:uppercase;">
                    <i class="fas fa-lock"></i> VOS CHOIX SONT VERROUILLÉS
                </div>
                <div class="princess-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="princess-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Playfair Display',serif;font-style:italic;font-size:16px;color:var(--text-muted);margin-bottom:24px;">
                        Choisissez <strong style="color:var(--rose-deep);font-style:normal;">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="princess-boisson-category">
                            <div class="princess-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="princess-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="princess-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="princess-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="princess-footer princess-anim">
        <div class="princess-footer-brand">👑 <?php echo htmlspecialchars($appName); ?></div>
        <div class="princess-footer-tagline">Des invitations dignes d'un conte de fées</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="princess-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(255, 215, 0, 0.3);font-family:'Didact Gothic',sans-serif;font-size:11px;color:var(--rose-light);letter-spacing:0.3em;text-transform:uppercase;">
            ✦ © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS ✦
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('starsContainer');
            for (let i = 0; i < 40; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.textContent = ['✦', '✧', '⭐', '✨'][Math.floor(Math.random() * 4)];
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.fontSize = (10 + Math.random() * 16) + 'px';
                star.style.animationDelay = (Math.random() * 3) + 's';
                container.appendChild(star);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.princess-anim');
            
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
                        width: 180, height: 180,
                        colorDark: '#4a1a6e', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        let princessDiapoIndex = 0;
        const princessSlides = document.querySelectorAll('#princessDiaporama .slide');
        const princessDots = document.querySelectorAll('#princessDiapoDots span');
        const princessCounter = document.getElementById('princessDiapoCounter');
        let princessDiapoInterval = null;

        function princessUpdateDiapo() {
            princessSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === princessDiapoIndex);
            });
            princessDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === princessDiapoIndex);
            });
            if (princessCounter) {
                princessCounter.textContent = (princessDiapoIndex + 1) + ' / ' + princessSlides.length;
            }
        }

        function princessDiapoChange(direction) {
            princessDiapoIndex += direction;
            if (princessDiapoIndex < 0) princessDiapoIndex = princessSlides.length - 1;
            if (princessDiapoIndex >= princessSlides.length) princessDiapoIndex = 0;
            princessUpdateDiapo();
            resetPrincessDiapoAuto();
        }

        function princessDiapoGoTo(index) {
            princessDiapoIndex = index;
            princessUpdateDiapo();
            resetPrincessDiapoAuto();
        }

        function resetPrincessDiapoAuto() {
            if (princessDiapoInterval) clearInterval(princessDiapoInterval);
            if (princessSlides.length > 1) {
                princessDiapoInterval = setInterval(() => {
                    princessDiapoIndex = (princessDiapoIndex + 1) % princessSlides.length;
                    princessUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (princessSlides.length > 0) {
                princessUpdateDiapo();
                resetPrincessDiapoAuto();
                
                const container = document.getElementById('princessDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (princessDiapoInterval) clearInterval(princessDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetPrincessDiapoAuto);
                }
            }
        });

        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.princess-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#1a0a2e', logging: false
                });
                const link = document.createElement('a');
                link.download = `princesse_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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

        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.princess-boisson-item.selected').forEach(item => {
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