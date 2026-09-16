<?php
/**
 * ============================================================
 * TEMPLATE : ROYAL v2 — Palais doré en 3D
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Great+Vibes&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f4e5a1;
            --gold-dark: #8b6914;
            --deep-red: #6b1414;
            --velvet: #4a0e0e;
            --black-royal: #0a0505;
            --cream: #f5e6c8;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: var(--black-royal);
            color: var(--cream);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           RIDEAUX DE THÉÂTRE
           ============================================ */
        .royal-curtains {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            overflow: hidden;
        }
        .curtain-left, .curtain-right {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 52%;
            background: 
                repeating-linear-gradient(90deg, 
                    var(--velvet) 0px, var(--velvet) 20px,
                    #5a1818 20px, #5a1818 40px,
                    var(--velvet) 40px, var(--velvet) 60px
                );
            box-shadow: inset 0 0 100px rgba(0,0,0,0.8);
            animation: curtainOpen 2.5s cubic-bezier(0.87, 0, 0.13, 1) 1s forwards;
        }
        .curtain-left { left: 0; transform-origin: left; }
        .curtain-right { right: 0; transform-origin: right; }
        
        @keyframes curtainOpen {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); opacity: 0; visibility: hidden; }
        }
        .curtain-right { animation-name: curtainOpenRight; }
        @keyframes curtainOpenRight {
            0% { transform: translateX(0); }
            100% { transform: translateX(100%); opacity: 0; visibility: hidden; }
        }
        
        /* Franges dorées en bas des rideaux */
        .curtain-left::after, .curtain-right::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0; right: 0;
            height: 30px;
            background: 
                repeating-linear-gradient(90deg,
                    var(--gold) 0px, var(--gold) 3px,
                    transparent 3px, transparent 8px
                );
        }
        
        /* ============================================
           COURONNE QUI DESCEND
           ============================================ */
        .royal-crown-intro {
            position: fixed;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9998;
            color: var(--gold);
            font-size: 120px;
            text-shadow: 
                0 0 40px var(--gold),
                0 0 80px rgba(212, 175, 55, 0.6);
            animation: crownDescend 1.8s cubic-bezier(0.34, 1.56, 0.64, 1) 1.5s forwards;
        }
        @keyframes crownDescend {
            0% { top: -200px; transform: translateX(-50%) rotate(-20deg); }
            70% { top: 40vh; transform: translateX(-50%) rotate(5deg) scale(1.2); }
            100% { top: 30vh; transform: translateX(-50%) rotate(0deg) scale(1); opacity: 0; }
        }
        
        /* ============================================
           NAVBAR ROYALE
           ============================================ */
        .royal-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(10,5,5,0.9) 0%, transparent 100%);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3.5s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .royal-crest {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--gold);
        }
        .royal-crest .emblem {
            width: 40px; height: 40px;
            border: 2px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-size: 18px;
            font-weight: 900;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
        }
        .royal-crest .name {
            font-family: 'Cinzel', serif;
            font-size: 16px;
            letter-spacing: 0.3em;
            font-weight: 700;
        }
        
        /* ============================================
           SECTION HERO ROYALE
           ============================================ */
        .royal-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            background: 
                radial-gradient(ellipse at center, rgba(107, 20, 20, 0.4) 0%, transparent 60%),
                linear-gradient(180deg, var(--black-royal) 0%, #1a0a0a 50%, var(--black-royal) 100%);
            overflow: hidden;
        }
        
        /* Colonnes dorées latérales */
        .royal-column {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 80px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(212, 175, 55, 0.1) 30%,
                rgba(212, 175, 55, 0.2) 50%,
                rgba(212, 175, 55, 0.1) 70%,
                transparent 100%);
            pointer-events: none;
        }
        .royal-column::before, .royal-column::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            background: var(--gold);
            border-radius: 50%;
            opacity: 0.3;
        }
        .royal-column::before { top: 20px; }
        .royal-column::after { bottom: 20px; }
        .royal-column.left { left: 40px; }
        .royal-column.right { right: 40px; }
        
        @media (max-width: 768px) {
            .royal-column { width: 40px; }
            .royal-column.left { left: 10px; }
            .royal-column.right { right: 10px; }
        }
        
        /* Blason central */
        .royal-blason {
            position: relative;
            z-index: 2;
            text-align: center;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.2s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Ornement du haut */
        .royal-ornament {
            margin-bottom: 30px;
            color: var(--gold);
            font-size: 32px;
            letter-spacing: 0.5em;
        }
        
        /* Petite couronne */
        .royal-crown-small {
            font-size: 60px;
            color: var(--gold);
            margin-bottom: 20px;
            filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.6));
            animation: crownPulse 3s ease-in-out infinite;
        }
        @keyframes crownPulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.6)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 50px rgba(212, 175, 55, 0.9)); }
        }
        
        /* Label */
        .royal-label {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.5em;
            color: var(--gold-light);
            margin-bottom: 20px;
            opacity: 0.8;
        }
        
        /* Nom invité */
        .royal-guest {
            font-family: 'Cinzel', serif;
            font-size: clamp(28px, 6vw, 52px);
            font-weight: 900;
            color: white;
            letter-spacing: 0.08em;
            margin-bottom: 40px;
            text-shadow: 
                0 2px 20px rgba(0,0,0,0.8),
                0 0 60px rgba(212, 175, 55, 0.3);
        }
        
        /* Séparateur */
        .royal-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        .royal-separator .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .royal-separator .diamond {
            color: var(--gold);
            font-size: 14px;
        }
        
        /* Hôte */
        .royal-hosts-intro {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 20px;
            color: var(--cream);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .royal-host-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(64px, 12vw, 120px);
            line-height: 1;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 30%, var(--gold-dark) 60%, var(--gold) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldFlow 8s ease-in-out infinite;
            filter: drop-shadow(0 4px 20px rgba(212, 175, 55, 0.5));
            margin-bottom: 20px;
        }
        @keyframes goldFlow {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .royal-event-type {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            letter-spacing: 0.6em;
            color: var(--gold);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        /* ============================================
           PARCHEMIN CENTRAL (Infos)
           ============================================ */
        .royal-scroll {
            position: relative;
            max-width: 800px;
            margin: 60px auto;
            padding: 60px 50px;
            background: 
                radial-gradient(ellipse at top left, rgba(212, 175, 55, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, #1a0f0f 0%, #2a1515 100%);
            border: 2px solid var(--gold);
            box-shadow: 
                0 0 0 8px var(--black-royal),
                0 0 0 9px var(--gold-dark),
                0 30px 80px rgba(0,0,0,0.8);
            opacity: 0;
            transform: translateY(60px);
            transition: all 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .royal-scroll.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Coins ornés */
        .royal-scroll::before, .royal-scroll::after {
            content: '❦';
            position: absolute;
            font-size: 40px;
            color: var(--gold);
            opacity: 0.6;
        }
        .royal-scroll::before { top: -12px; left: -12px; }
        .royal-scroll::after { bottom: -12px; right: -12px; }
        
        /* Titre parchemin */
        .royal-scroll-title {
            font-family: 'Cinzel', serif;
            font-size: 20px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        /* Infos en grille */
        .royal-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 640px) {
            .royal-info-grid { grid-template-columns: 1fr; }
            .royal-scroll { padding: 40px 25px; margin: 40px 15px; }
        }
        
        .royal-info-item {
            text-align: center;
            padding: 20px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            background: rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        .royal-info-item:hover {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.08);
            transform: translateY(-4px);
        }
        
        .royal-info-item .icon {
            font-size: 32px;
            color: var(--gold);
            margin-bottom: 12px;
        }
        .royal-info-item .label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--gold-light);
            margin-bottom: 8px;
            opacity: 0.7;
        }
        .royal-info-item .value {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
            line-height: 1.4;
        }
        
        /* Bouton itinéraire royal */
        .royal-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            padding: 12px 24px;
            border: 2px solid var(--gold);
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(212, 175, 55, 0.05));
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .royal-btn-itinerary:hover {
            background: var(--gold);
            color: var(--black-royal);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.6);
            transform: translateY(-2px);
        }
        
        /* ============================================
           SCEAU DE CIRE ANIMÉ
           ============================================ */
        .royal-seal {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 40px auto;
            opacity: 0;
            animation: sealAppear 1s cubic-bezier(0.34, 1.56, 0.64, 1) 4s forwards;
        }
        @keyframes sealAppear {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        .royal-seal-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #c41e1e 0%, var(--deep-red) 50%, var(--velvet) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 8px 24px rgba(0,0,0,0.6),
                inset -8px -8px 20px rgba(0,0,0,0.5),
                inset 8px 8px 20px rgba(255,255,255,0.1);
            position: relative;
        }
        .royal-seal-inner::before {
            content: '';
            position: absolute;
            inset: 12px;
            border: 2px solid rgba(212, 175, 55, 0.5);
            border-radius: 50%;
        }
        .royal-seal-inner i {
            color: var(--gold-light);
            font-size: 40px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        
        /* ============================================
           DIAPORAMA PHOTOS ROYAL
           ============================================ */
        .royal-photos-section {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        .royal-section-title {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        .royal-section-title::before, .royal-section-title::after {
            content: '';
            width: 50px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        
        .royal-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 8px var(--black-royal),
                0 0 0 9px var(--gold-dark),
                0 30px 60px rgba(0,0,0,0.8);
            overflow: hidden;
        }
        
        .royal-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease;
        }
        .royal-diaporama .slide.active { opacity: 1; }
        .royal-diaporama .slide img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        
        /* ============================================
           SECTIONS FORMULAIRE
           ============================================ */
        .royal-form-section {
            max-width: 800px;
            margin: 60px auto;
            padding: 50px 40px;
            background: linear-gradient(135deg, #1a0f0f, #2a1515);
            border: 1px solid var(--gold);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
        }
        .royal-form-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .royal-form-section { padding: 35px 20px; margin: 40px 15px; }
        }
        
        .royal-form-group {
            margin-bottom: 24px;
        }
        .royal-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--gold-light);
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .royal-form-group input,
        .royal-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: white;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .royal-form-group input:focus,
        .royal-form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }
        
        .royal-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .royal-options-grid { grid-template-columns: 1fr; }
        }
        
        .royal-option-radio { display: none; }
        .royal-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 2px solid rgba(212, 175, 55, 0.3);
            background: rgba(0,0,0,0.4);
            color: var(--gold-light);
            font-family: 'Cinzel', serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.15em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .royal-option-label:hover {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.1);
            transform: translateY(-2px);
        }
        .royal-option-radio:checked + .royal-option-label {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.2);
            color: white;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
        }
        
        .royal-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 20px 32px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--black-royal);
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 0 0 3px var(--black-royal),
                0 0 0 5px var(--gold-dark),
                0 12px 30px rgba(212, 175, 55, 0.3);
            margin-top: 20px;
        }
        .royal-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 0 0 3px var(--black-royal),
                0 0 0 5px var(--gold),
                0 18px 40px rgba(212, 175, 55, 0.5);
        }
        
        /* ============================================
           BOISSONS ROYAL
           ============================================ */
        .royal-boisson-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .royal-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border: 2px solid rgba(212, 175, 55, 0.3);
            background: rgba(0,0,0,0.4);
            color: var(--gold-light);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .royal-boisson-item.selected {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.2);
            color: white;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
        }
        .royal-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; color: var(--gold); }
        .royal-boisson-item.selected .check { opacity: 1; }
        
        .royal-boisson-category {
            margin-bottom: 24px;
        }
        .royal-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* ============================================
           QR CODE ROYAL
           ============================================ */
        .royal-qr-wrapper {
            text-align: center;
            padding: 20px;
        }
        .royal-qr-box {
            display: inline-block;
            padding: 20px;
            background: var(--cream);
            border: 3px solid var(--gold);
            box-shadow: 
                0 0 0 6px var(--black-royal),
                0 0 0 8px var(--gold-dark),
                0 0 40px rgba(212, 175, 55, 0.4);
            position: relative;
        }
        .royal-qr-box::before, .royal-qr-box::after {
            content: '❦';
            position: absolute;
            color: var(--gold);
            font-size: 24px;
        }
        .royal-qr-box::before { top: -8px; left: -8px; }
        .royal-qr-box::after { bottom: -8px; right: -8px; }
        
        /* ============================================
           FOOTER ROYAL
           ============================================ */
        .royal-footer {
            padding: 60px 40px 40px;
            background: linear-gradient(180deg, transparent 0%, var(--black-royal) 100%);
            text-align: center;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .royal-footer-crest {
            font-size: 48px;
            color: var(--gold);
            margin-bottom: 16px;
            filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.5));
        }
        .royal-footer-name {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            letter-spacing: 0.4em;
            color: var(--gold);
            margin-bottom: 8px;
        }
        .royal-footer-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            color: var(--gold-light);
            margin-bottom: 30px;
            opacity: 0.7;
        }
        
        .royal-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            border: 2px solid #25d366;
            background: transparent;
            color: #25d366;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .royal-btn-whatsapp:hover {
            background: #25d366;
            color: white;
            box-shadow: 0 0 30px rgba(37, 211, 102, 0.5);
        }
        
        /* ============================================
           ALERTES
           ============================================ */
        .royal-alert {
            padding: 16px 24px;
            margin-bottom: 20px;
            border-left: 4px solid var(--gold);
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-light);
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .royal-alert-success { border-left-color: #46d369; background: rgba(70, 211, 105, 0.1); color: #a3e8b8; }
        .royal-alert-danger  { border-left-color: var(--nf-red); background: rgba(229,9,20,0.1); color: #fca5a5; }
        
        /* Download btn */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--black-royal);
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 4s forwards;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 12px 32px rgba(212, 175, 55, 0.6); }
        @media (max-width: 480px) { #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 10px; } }
    </style>
</head>
<body>

    <!-- ============================================
         RIDEAUX D'INTRO
         ============================================ -->
    <div class="royal-curtains">
        <div class="curtain-left"></div>
        <div class="curtain-right"></div>
    </div>
    
    <!-- Couronne qui descend -->
    <div class="royal-crown-intro">♛</div>

    <!-- ============================================
         NAVBAR ROYALE
         ============================================ -->
    <nav class="royal-navbar">
        <div class="royal-crest">
            <div class="emblem">M</div>
            <div class="name">MAISON ROYALE</div>
        </div>
        <div style="font-family:'Cinzel',serif;font-size:12px;letter-spacing:0.3em;color:var(--gold);">
            INVITATION OFFICIELLE
        </div>
    </nav>

    <!-- ============================================
         HERO ROYAL
         ============================================ -->
    <section class="royal-hero">
        <div class="royal-column left"></div>
        <div class="royal-column right"></div>
        
        <div class="royal-blason">
            
            <div class="royal-ornament">✦ ❦ ✦</div>
            
            <div class="royal-crown-small">♛</div>
            
            <div class="royal-label">INVITATION PERSONNELLE</div>
            
            <div class="royal-guest"><?php echo htmlspecialchars(strtoupper($guestName)); ?></div>
            
            <div class="royal-separator">
                <div class="line"></div>
                <div class="diamond">◆</div>
                <div class="line"></div>
            </div>
            
            <div class="royal-hosts-intro">Vous êtes convié(e) à célébrer</div>
            <div class="royal-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="royal-event-type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
            
        </div>
    </section>

    <!-- ============================================
         PARCHEMIN CENTRAL (Détails)
         ============================================ -->
    <div class="royal-scroll" id="scrollDetails">
        
        <div class="royal-scroll-title">✦ DÉTAILS DE LA CÉRÉMONIE ✦</div>
        
        <div class="royal-info-grid">
            
            <div class="royal-info-item">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="royal-info-item">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="royal-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="label">LIEU DE LA CÉRÉMONIE</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <div style="font-size:14px;color:var(--gold-light);margin-top:8px;font-weight:400;font-style:italic;">
                            <?php echo htmlspecialchars($adresseDisplay); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="royal-btn-itinerary">
                    <i class="fas fa-route"></i> Ouvrir dans Google Maps
                </a>
            </div>
            
            <div class="royal-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-user-friends"></i></div>
                <div class="label">NOMBRE DE PLACES RÉSERVÉES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
        
        <!-- Sceau de cire -->
        <div class="royal-seal">
            <div class="royal-seal-inner">
                <i class="fas fa-crown"></i>
            </div>
        </div>
        
    </div>

    <!-- ============================================
         SECTION MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="royal-form-section apparue" style="max-width:800px;margin:40px auto;padding:30px;">
            <div class="royal-alert royal-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         DIAPORAMA PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="royal-photos-section">
            <div class="royal-section-title">✦ SOUVENIRS ROYAUX ✦</div>
            <div class="royal-diaporama" id="diaporama">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="royal-form-section">
        <div class="royal-scroll-title">✦ CODE D'ACCÈS ROYAL ✦</div>
        <div class="royal-qr-wrapper">
            <div class="royal-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Cinzel',serif;font-size:12px;letter-spacing:0.4em;color:var(--gold);margin-top:20px;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="royal-form-section">
            <div class="royal-scroll-title">✦ CONFIRMATION DE PRÉSENCE ✦</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="royal-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="royal-form-group">
                    <label>Votre réponse royale</label>
                    <div class="royal-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="royal-option-radio">
                            <label for="presenceOui" class="royal-option-label">
                                <i class="fas fa-crown"></i> J'honore
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="royal-option-radio">
                            <label for="presenceNon" class="royal-option-label">
                                <i class="fas fa-times"></i> Je décline
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="royal-form-group">
                    <label>Message à la cour</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre mot..."></textarea>
                </div>
                
                <button type="submit" class="royal-btn-submit">
                    <i class="fas fa-feather-alt"></i> Sceller ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="royal-form-section">
            <div class="royal-scroll-title">✦ CARTE DES BOISSONS ✦</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--gold);padding:20px 0;font-family:'Cinzel',serif;letter-spacing:0.2em;">
                    <i class="fas fa-lock"></i> VOS CHOIX SONT SCELLÉS
                </div>
                <div class="royal-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="royal-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check-circle check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Cormorant Garamond',serif;font-style:italic;font-size:18px;color:var(--gold-light);margin-bottom:24px;">
                        Sélectionnez jusqu'à <strong style="color:var(--gold);font-style:normal;">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="royal-boisson-category">
                            <div class="royal-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="royal-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="royal-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="royal-btn-submit">
                        <i class="fas fa-save"></i> Sceller mes choix
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER ROYAL
         ============================================ -->
    <footer class="royal-footer">
        <div class="royal-footer-crest">♛</div>
        <div class="royal-footer-name"><?php echo htmlspecialchars($appName); ?></div>
        <div class="royal-footer-tagline">L'excellence au service des grandes occasions</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="royal-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(212, 175, 55, 0.2);font-size:11px;color:rgba(212, 175, 55, 0.5);letter-spacing:0.3em;font-family:'Cinzel',serif;">
            © <?php echo date('Y'); ?> · MAISON ROYALE
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.royal-scroll, .royal-form-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { 
                    if (entry.isIntersecting) { 
                        entry.target.classList.add('apparue'); 
                        observer.unobserve(entry.target);
                    } 
                });
            }, { threshold: 0.15 });
            sections.forEach(s => observer.observe(s));
        });

        // ================================================================
        // QR CODE
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180,
                        height: 180,
                        colorDark: '#1a0f0f',
                        colorLight: '#f5e6c8',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA
        // ================================================================
        let diapoIndex = 0;
        const slides = document.querySelectorAll('.royal-diaporama .slide');
        function updateDiapo() {
            slides.forEach((s, i) => s.classList.toggle('active', i === diapoIndex));
        }
        if (slides.length > 1) {
            setInterval(() => { 
                diapoIndex = (diapoIndex + 1) % slides.length; 
                updateDiapo(); 
            }, 5000);
        }

        // ================================================================
        // DOWNLOAD
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.royal-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#0a0505', logging: false
                });
                const link = document.createElement('a');
                link.download = `royal_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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

        // ================================================================
        // BOISSONS
        // ================================================================
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.royal-boisson-item.selected').forEach(item => {
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