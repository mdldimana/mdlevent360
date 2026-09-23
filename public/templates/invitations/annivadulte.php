<?php
/**
 * ============================================================
 * TEMPLATE : ANNIVERSAIRE ADULTE (Chic & Lounge) - v5
 * ============================================================
 * 
 * Correction v5 :
 * - Téléchargement fonctionnel (image non noire)
 * - QR code visible dans l'image téléchargée
 * - Suppression du min-height: 100vh qui cassait html2canvas
 * - Forçage des animations dans le clone
 * 
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Italiana&family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet" />
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
            --light-gray: #999999;
            --silver: #d4d4d4;
            --white: #ffffff;
            --cream: #f5efe3;
            --gold: #d4af37;
            --gold-light: #f4e5a1;
            --gold-dark: #8b6914;
            --champagne: #f7e7ce;
            --wine: #6b1414;
            --rose-gold: #b76e79;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: 
                radial-gradient(ellipse at top, #1a1a1a 0%, transparent 60%),
                radial-gradient(ellipse at bottom, #2a1a0a 0%, transparent 60%),
                var(--black);
            background-attachment: fixed;
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           INTRO
           ============================================ */
        .champagne-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: introFadeOut 2.5s ease-in-out 2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .champagne-glass {
            width: 120px;
            height: auto;
            opacity: 0;
            animation: glassAppear 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.3s forwards;
            filter: drop-shadow(0 10px 30px rgba(212, 175, 55, 0.5));
        }
        @keyframes glassAppear {
            0% { opacity: 0; transform: scale(0.5) translateY(50px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .intro-bubbles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .intro-bubbles span {
            position: absolute;
            bottom: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.9), rgba(212, 175, 55, 0.5));
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.6);
            opacity: 0;
            animation: bubbleRise 3s ease-out infinite;
        }
        .intro-bubbles span:nth-child(1) { left: 20%; animation-delay: 0.2s; width: 6px; height: 6px; }
        .intro-bubbles span:nth-child(2) { left: 30%; animation-delay: 0.5s; width: 10px; height: 10px; }
        .intro-bubbles span:nth-child(3) { left: 42%; animation-delay: 0.8s; width: 8px; height: 8px; }
        .intro-bubbles span:nth-child(4) { left: 55%; animation-delay: 1.1s; width: 12px; height: 12px; }
        .intro-bubbles span:nth-child(5) { left: 65%; animation-delay: 0.3s; width: 7px; height: 7px; }
        .intro-bubbles span:nth-child(6) { left: 75%; animation-delay: 0.9s; width: 9px; height: 9px; }
        .intro-bubbles span:nth-child(7) { left: 25%; animation-delay: 1.4s; width: 5px; height: 5px; }
        .intro-bubbles span:nth-child(8) { left: 60%; animation-delay: 1.7s; width: 11px; height: 11px; }
        
        @keyframes bubbleRise {
            0% { bottom: 0; opacity: 0; transform: translateX(0); }
            15% { opacity: 1; }
            100% { bottom: 100%; opacity: 0; transform: translateX(30px); }
        }
        
        /* ============================================
           BULLES FLOTTANTES
           ============================================ */
        .champagne-bubbles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 5;
            overflow: hidden;
        }
        .champagne-bubbles .bubble {
            position: absolute;
            bottom: -20px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.7), rgba(212, 175, 55, 0.3));
            box-shadow: 0 0 8px rgba(212, 175, 55, 0.4);
            animation: bubbleFloat linear infinite;
        }
        @keyframes bubbleFloat {
            0% { bottom: -20px; opacity: 0; transform: translateX(0); }
            10% { opacity: 1; }
            90% { opacity: 0.6; }
            100% { bottom: 100vh; opacity: 0; transform: translateX(50px); }
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        .lounge-anim {
            opacity: 0;
            transform: translateY(80px) scale(0.95);
            transition: 
                opacity 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .lounge-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .lounge-anim.from-left {
            opacity: 0;
            transform: translateX(-100px);
        }
        .lounge-anim.from-left.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .lounge-anim.from-right {
            opacity: 0;
            transform: translateX(100px);
        }
        .lounge-anim.from-right.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .lounge-anim.zoom-in {
            opacity: 0;
            transform: scale(0.85);
        }
        .lounge-anim.zoom-in.apparue {
            opacity: 1;
            transform: scale(1);
        }
        
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
            background: var(--black);
            padding-bottom: 20px;
        }
        
        /* ============================================
           HERO
           ============================================ */
        .lounge-hero {
            position: relative;
            padding: 80px 20px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            overflow: hidden;
            background: 
                radial-gradient(ellipse 600px 400px at 50% 30%, rgba(212, 175, 55, 0.05) 0%, transparent 100%),
                radial-gradient(ellipse at top, #1a1a1a 0%, transparent 60%),
                radial-gradient(ellipse at bottom, #2a1a0a 0%, transparent 60%),
                var(--black);
        }
        
        .ambient-light {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: 
                radial-gradient(ellipse 400px 300px at 20% 30%, rgba(212, 175, 55, 0.08) 0%, transparent 100%),
                radial-gradient(ellipse 400px 300px at 80% 70%, rgba(212, 175, 55, 0.06) 0%, transparent 100%);
        }
        
        .gold-ornament {
            position: absolute;
            width: 100px;
            height: 100px;
            z-index: 2;
            pointer-events: none;
            opacity: 0.4;
        }
        .gold-ornament.tl { top: 30px; left: 30px; }
        .gold-ornament.tr { top: 30px; right: 30px; transform: scaleX(-1); }
        .gold-ornament.bl { bottom: 30px; left: 30px; transform: scaleY(-1); }
        .gold-ornament.br { bottom: 30px; right: 30px; transform: scale(-1); }
        
        @media (max-width: 768px) {
            .gold-ornament { width: 60px; height: 60px; }
            .gold-ornament.tl, .gold-ornament.tr { top: 20px; }
        }
        
        .lounge-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 850px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.7s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .lounge-star {
            font-size: 42px;
            color: var(--gold);
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.5));
            animation: starGlow 3s ease-in-out infinite;
        }
        @keyframes starGlow {
            0%, 100% { transform: scale(1) rotate(0deg); filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.5)); }
            50% { transform: scale(1.1) rotate(180deg); filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.8)); }
        }
        
        .lounge-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 24px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.08);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.35em;
            color: var(--gold);
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 30px;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.15);
        }
        .lounge-badge::before,
        .lounge-badge::after {
            content: '✦';
            font-size: 12px;
        }
        
        .lounge-guest {
            font-family: 'Playfair Display', serif;
            font-size: clamp(38px, 7vw, 64px);
            font-weight: 400;
            font-style: italic;
            color: var(--white);
            letter-spacing: 0.02em;
            line-height: 1.1;
            margin-bottom: 30px;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.8);
        }
        
        .lounge-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 500px;
            margin: 0 auto 40px;
        }
        .lounge-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .lounge-divider .icon {
            font-size: 20px;
            color: var(--gold);
            animation: pulse 2.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0.7; }
        }
        
        .lounge-hosts-intro {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 18px;
            letter-spacing: 0.1em;
            color: var(--silver);
            margin-bottom: 20px;
            opacity: 0.85;
        }
        
        .lounge-host-name {
            font-family: 'Italiana', serif;
            font-size: clamp(56px, 12vw, 130px);
            line-height: 0.9;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: linear-gradient(180deg, 
                var(--gold-light) 0%, 
                var(--gold) 40%,
                var(--gold-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            filter: drop-shadow(0 6px 30px rgba(212, 175, 55, 0.4));
        }
        
        .lounge-event-type {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            letter-spacing: 0.5em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 500;
        }
        
        /* ============================================
           CARTE
           ============================================ */
        .lounge-card {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 60px 55px;
            background: linear-gradient(180deg, 
                rgba(26, 26, 26, 0.95) 0%, 
                rgba(18, 18, 18, 0.95) 100%);
            border: 1px solid rgba(212, 175, 55, 0.25);
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.7),
                inset 0 0 60px rgba(212, 175, 55, 0.02);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .lounge-card { padding: 40px 25px; margin: 60px 15px; }
        }
        
        .lounge-card::before,
        .lounge-card::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border: 1px solid var(--gold);
        }
        .lounge-card::before {
            top: -1px; left: -1px;
            border-right: none; border-bottom: none;
        }
        .lounge-card::after {
            bottom: -1px; right: -1px;
            border-left: none; border-top: none;
        }
        
        .lounge-card-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 30px;
            font-weight: 400;
            text-align: center;
            color: var(--white);
            margin-bottom: 50px;
            letter-spacing: 0.02em;
            position: relative;
            padding-bottom: 24px;
        }
        .lounge-card-title::after {
            content: '✦';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--near-black);
            padding: 0 16px;
            color: var(--gold);
            font-size: 14px;
        }
        
        .lounge-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 640px) {
            .lounge-info-grid { grid-template-columns: 1fr; }
        }
        
        .lounge-info-item {
            padding: 30px 26px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(212, 175, 55, 0.15);
            transition: all 0.4s ease;
            text-align: center;
            position: relative;
        }
        .lounge-info-item::before {
            content: '';
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 1px;
            background: var(--gold);
            transition: width 0.4s ease;
        }
        
        .lounge-info-item .icon {
            font-size: 30px;
            color: var(--gold);
            margin-bottom: 16px;
            display: block;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.4));
        }
        .lounge-info-item .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--gold);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .lounge-info-item .value {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 400;
            color: var(--white);
            line-height: 1.3;
            letter-spacing: 0.01em;
        }
        .lounge-info-item .value .sub {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 14px;
            color: var(--light-gray);
            margin-top: 8px;
            font-weight: 400;
        }
        
        .lounge-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, 
                rgba(212, 175, 55, 0.08) 0%, 
                rgba(212, 175, 55, 0.03) 100%);
            border: 1px solid rgba(212, 175, 55, 0.4);
        }
        
        .lounge-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 22px;
            padding: 14px 32px;
            border: 1px solid var(--gold);
            background: transparent;
            color: var(--gold);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        .lounge-btn-itinerary:hover {
            background: var(--gold);
            color: var(--black);
        }
        
        /* ============================================
           CARTE QR INTÉGRÉE
           ============================================ */
        .lounge-qr-card {
            position: relative;
            max-width: 900px;
            margin: 0 auto 40px;
            padding: 50px 40px;
            background: linear-gradient(180deg, 
                rgba(26, 26, 26, 0.95) 0%, 
                rgba(18, 18, 18, 0.95) 100%);
            border: 1px solid rgba(212, 175, 55, 0.25);
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.7),
                inset 0 0 60px rgba(212, 175, 55, 0.02);
            z-index: 10;
            text-align: center;
        }
        @media (max-width: 640px) {
            .lounge-qr-card { padding: 40px 25px; margin: 0 15px 40px; }
        }
        
        .lounge-qr-card::before,
        .lounge-qr-card::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border: 1px solid var(--gold);
        }
        .lounge-qr-card::before {
            top: -1px; left: -1px;
            border-right: none; border-bottom: none;
        }
        .lounge-qr-card::after {
            bottom: -1px; right: -1px;
            border-left: none; border-top: none;
        }
        
        .lounge-qr-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 26px;
            font-weight: 400;
            color: var(--white);
            margin-bottom: 30px;
            letter-spacing: 0.02em;
            position: relative;
            padding-bottom: 20px;
        }
        .lounge-qr-title::after {
            content: '✦';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--near-black);
            padding: 0 16px;
            color: var(--gold);
            font-size: 14px;
        }
        
        .lounge-qr-wrapper { text-align: center; }
        .lounge-qr-box {
            display: inline-block;
            padding: 28px;
            background: var(--cream);
            border: 2px solid var(--gold);
            box-shadow: 
                0 0 0 6px var(--black),
                0 0 0 7px var(--gold),
                0 20px 60px rgba(212, 175, 55, 0.3);
            position: relative;
        }
        .lounge-qr-box::before,
        .lounge-qr-box::after {
            content: '✦';
            position: absolute;
            color: var(--gold);
            font-size: 20px;
            background: var(--black);
            padding: 4px;
        }
        .lounge-qr-box::before { top: -18px; left: -18px; }
        .lounge-qr-box::after { bottom: -18px; right: -18px; }
        
        /* ============================================
           SECTIONS
           ============================================ */
        .lounge-section {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 60px 55px;
            background: linear-gradient(180deg, 
                rgba(26, 26, 26, 0.95) 0%, 
                rgba(18, 18, 18, 0.95) 100%);
            border: 1px solid rgba(212, 175, 55, 0.2);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .lounge-section { padding: 40px 25px; margin: 60px 15px; }
        }
        
        .lounge-section-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            font-weight: 400;
            color: var(--white);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            letter-spacing: 0.02em;
            position: relative;
        }
        .lounge-section-title::after {
            content: '✦';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--near-black);
            padding: 0 16px;
            color: var(--gold);
            font-size: 14px;
        }
        
        /* ============================================
           DIAPORAMA
           ============================================ */
        .lounge-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 16/10;
            overflow: hidden;
            background: #000;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        .lounge-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 0;
            background: #000;
        }
        
        .lounge-diaporama .slide.active { opacity: 1; z-index: 1; }
        
        .lounge-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
            padding: 12px;
        }
        
        .lounge-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(212, 175, 55, 0.5);
            color: var(--gold);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .lounge-diapo-arrow:hover {
            background: var(--gold);
            color: var(--black);
            transform: translateY(-50%) scale(1.1);
        }
        
        .lounge-diapo-arrow.prev { left: 16px; }
        .lounge-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .lounge-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .lounge-diapo-arrow.prev { left: 8px; }
            .lounge-diapo-arrow.next { right: 8px; }
        }
        
        .lounge-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(212, 175, 55, 0.5);
            color: var(--gold);
            font-family: 'Playfair Display', serif;
            font-size: 13px;
            letter-spacing: 0.15em;
            padding: 8px 16px;
            z-index: 10;
        }
        
        .lounge-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.5);
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        .lounge-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .lounge-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .lounge-form-group { margin-bottom: 30px; }
        .lounge-form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .lounge-form-group input,
        .lounge-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(212, 175, 55, 0.25);
            color: var(--white);
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            transition: all 0.4s ease;
        }
        .lounge-form-group input:focus,
        .lounge-form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
        }
        .lounge-form-group input::placeholder,
        .lounge-form-group textarea::placeholder {
            color: var(--gray);
            font-style: italic;
        }
        
        .lounge-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 480px) {
            .lounge-options-grid { grid-template-columns: 1fr; }
        }
        
        .lounge-option-radio { display: none; }
        .lounge-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 22px 20px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            background: rgba(255, 255, 255, 0.02);
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-style: italic;
            color: var(--silver);
            cursor: pointer;
            transition: all 0.4s ease;
        }
        .lounge-option-label:hover {
            border-color: var(--gold);
            color: var(--gold);
        }
        .lounge-option-radio:checked + .lounge-option-label {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.12);
            color: var(--gold-light);
        }
        
        .lounge-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--black);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 20px;
            box-shadow: 0 12px 40px rgba(212, 175, 55, 0.25);
        }
        .lounge-btn-submit:hover {
            transform: translateY(-3px);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .lounge-boisson-category { margin-bottom: 30px; }
        .lounge-boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 20px;
            color: var(--gold);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        }
        
        .lounge-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .lounge-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: all 0.4s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: var(--silver);
        }
        .lounge-boisson-item.selected {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.12);
            color: var(--gold-light);
        }
        .lounge-boisson-item .check {
            opacity: 0;
            transition: opacity 0.3s ease;
            color: var(--gold);
        }
        .lounge-boisson-item.selected .check { opacity: 1; }
        
        /* ============================================
           FOOTER
           ============================================ */
        .lounge-footer {
            padding: 80px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
            border-top: 1px solid rgba(212, 175, 55, 0.15);
            margin-top: 80px;
        }
        .lounge-footer-star {
            font-size: 32px;
            color: var(--gold);
            margin-bottom: 16px;
        }
        .lounge-footer-brand {
            font-family: 'Italiana', serif;
            font-size: 40px;
            letter-spacing: 0.2em;
            color: var(--white);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .lounge-footer-tagline {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 17px;
            color: var(--gold);
            margin-bottom: 36px;
        }
        
        .lounge-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 36px;
            border: 1px solid var(--gold);
            background: transparent;
            color: var(--gold);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.4s ease;
        }
        .lounge-btn-whatsapp:hover {
            background: var(--gold);
            color: var(--black);
        }
        
        /* ============================================
           ALERTES
           ============================================ */
        .lounge-alert {
            padding: 20px 28px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: center;
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 17px;
            border-left: 2px solid;
        }
        .lounge-alert-success { 
            border-color: var(--gold); 
            background: rgba(212, 175, 55, 0.08); 
            color: var(--gold-light); 
        }
        .lounge-alert-danger { 
            border-color: #d40000; 
            background: rgba(212, 0, 0, 0.08); 
            color: #ff8080; 
        }
        .lounge-alert-warning { 
            border-color: #ffa500; 
            background: rgba(255, 165, 0, 0.08); 
            color: #ffcc80; 
        }
        
        /* ============================================
           BOUTON DOWNLOAD
           ============================================ */
        #downloadBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 18px 32px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--black);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 
                0 0 0 3px var(--black),
                0 0 0 4px var(--gold),
                0 12px 40px rgba(212, 175, 55, 0.4);
            opacity: 0;
            animation: fadeIn 1s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px);
        }
        @media (max-width: 480px) {
            #downloadBtn { 
                bottom: 16px; 
                right: 16px; 
                padding: 14px 20px; 
                font-size: 10px;
                letter-spacing: 0.15em;
            }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- INTRO -->
    <div class="champagne-intro">
        <div class="intro-bubbles">
            <span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span>
        </div>
        <svg class="champagne-glass" viewBox="0 0 120 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="goldGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#f4e5a1"/>
                    <stop offset="50%" stop-color="#d4af37"/>
                    <stop offset="100%" stop-color="#8b6914"/>
                </linearGradient>
                <linearGradient id="champGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#f7e7ce"/>
                    <stop offset="100%" stop-color="#d4af37"/>
                </linearGradient>
            </defs>
            <path d="M 20 20 L 100 20 Q 100 80 60 100 Q 20 80 20 20 Z" 
                  fill="url(#champGrad)" opacity="0.85" stroke="url(#goldGrad)" stroke-width="2"/>
            <path d="M 30 30 L 55 30 Q 55 65 40 75 Q 30 60 30 30 Z" 
                  fill="white" opacity="0.3"/>
            <rect x="58" y="100" width="4" height="70" fill="url(#goldGrad)"/>
            <ellipse cx="60" cy="175" rx="35" ry="6" fill="url(#goldGrad)"/>
            <circle cx="40" cy="50" r="3" fill="white" opacity="0.7"/>
            <circle cx="75" cy="45" r="2.5" fill="white" opacity="0.6"/>
            <circle cx="55" cy="70" r="2" fill="white" opacity="0.8"/>
            <circle cx="80" cy="65" r="3" fill="white" opacity="0.5"/>
            <circle cx="45" cy="80" r="2.5" fill="white" opacity="0.6"/>
            <circle cx="65" cy="40" r="2" fill="white" opacity="0.7"/>
        </svg>
    </div>

    <!-- Bulles persistantes -->
    <div class="champagne-bubbles" id="bubblesContainer"></div>

    <!-- ============================================ -->
    <!-- WRAPPER CAPTURÉ                              -->
    <!-- ============================================ -->
    <div id="downloadCard">

        <!-- HERO -->
        <section class="lounge-hero">
            <div class="ambient-light"></div>
            
            <svg class="gold-ornament tl" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <g stroke="#d4af37" stroke-width="1.5" fill="none">
                    <path d="M 0 0 L 100 100 M 0 0 L 80 30 M 0 0 L 30 80"/>
                    <circle cx="15" cy="15" r="3" fill="#d4af37"/>
                    <circle cx="40" cy="40" r="2" fill="#d4af37"/>
                </g>
            </svg>
            <svg class="gold-ornament tr" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <g stroke="#d4af37" stroke-width="1.5" fill="none">
                    <path d="M 0 0 L 100 100 M 0 0 L 80 30 M 0 0 L 30 80"/>
                    <circle cx="15" cy="15" r="3" fill="#d4af37"/>
                    <circle cx="40" cy="40" r="2" fill="#d4af37"/>
                </g>
            </svg>
            <svg class="gold-ornament bl" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <g stroke="#d4af37" stroke-width="1.5" fill="none">
                    <path d="M 0 0 L 100 100 M 0 0 L 80 30 M 0 0 L 30 80"/>
                    <circle cx="15" cy="15" r="3" fill="#d4af37"/>
                </g>
            </svg>
            <svg class="gold-ornament br" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <g stroke="#d4af37" stroke-width="1.5" fill="none">
                    <path d="M 0 0 L 100 100 M 0 0 L 80 30 M 0 0 L 30 80"/>
                    <circle cx="15" cy="15" r="3" fill="#d4af37"/>
                </g>
            </svg>
            
            <div class="lounge-blason">
                <div class="lounge-star">✦</div>
                
                <div class="lounge-badge">
                    ANNIVERSAIRE EXCLUSIF
                </div>
                
                <div class="lounge-guest">
                    <?php echo htmlspecialchars($guestName); ?>
                </div>
                
                <div class="lounge-divider">
                    <div class="line"></div>
                    <span class="icon">🍾</span>
                    <div class="line"></div>
                </div>
                
                <div class="lounge-hosts-intro">
                    Vous êtes convié(e) à célébrer l'anniversaire de
                </div>
                <div class="lounge-host-name"><?php echo htmlspecialchars($host1); ?></div>
                <div class="lounge-event-type">
                    ✦ <?php echo htmlspecialchars(strtoupper($eventType)); ?> ✦
                </div>
            </div>
        </section>

        <!-- CARTE DÉTAILS -->
        <div class="lounge-card">
            <div class="lounge-card-title">Détails de la soirée</div>
            
            <div class="lounge-info-grid">
                
                <div class="lounge-info-item">
                    <i class="fas fa-calendar-alt icon"></i>
                    <div class="label">DATE</div>
                    <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                </div>
                
                <div class="lounge-info-item">
                    <i class="fas fa-clock icon"></i>
                    <div class="label">HEURE</div>
                    <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                </div>
                
                <div class="lounge-info-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-map-marker-alt icon"></i>
                    <div class="label">LIEU</div>
                    <div class="value">
                        <?php echo htmlspecialchars($lieuDisplay); ?>
                        <?php if ($adresseDisplay): ?>
                            <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="lounge-btn-itinerary">
                        <i class="fas fa-map-marked-alt"></i> VOIR L'ITINÉRAIRE
                    </a>
                </div>
                
                <?php if ($hasTable): ?>
                <div class="lounge-info-item lounge-table-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-chair icon"></i>
                    <div class="label">VOTRE TABLE</div>
                    <div class="value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="lounge-info-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-users icon"></i>
                    <div class="label">PLACES RÉSERVÉES</div>
                    <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
                </div>
                
            </div>
        </div>

        <!-- QR CODE -->
        <div class="lounge-qr-card">
            <div class="lounge-qr-title">Votre accès privé</div>
            <div class="lounge-qr-wrapper">
                <div class="lounge-qr-box">
                    <div id="qrcode"></div>
                </div>
                <div style="font-family:'Playfair Display',serif;font-style:italic;font-size:16px;color:var(--gold);letter-spacing:0.1em;margin-top:24px;">
                    <?php echo htmlspecialchars($invitation['code_unique']); ?>
                </div>
            </div>
        </div>

    </div>
    <!-- FIN WRAPPER -->

    <!-- ============================================ -->
    <!-- SECTIONS HORS CAPTURE                       -->
    <!-- ============================================ -->

    <?php if ($message): ?>
        <div class="lounge-section lounge-anim apparue">
            <div class="lounge-alert lounge-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- DIAPORAMA PHOTOS -->
    <?php if (!empty($photosHost)): ?>
        <div class="lounge-section lounge-anim from-left">
            <div class="lounge-section-title">Souvenirs</div>
            
            <div class="lounge-diaporama" id="loungeDiaporama">
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
                    <button class="lounge-diapo-arrow prev" onclick="loungeDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="lounge-diapo-arrow next" onclick="loungeDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="lounge-diapo-counter" id="loungeDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="lounge-diapo-dots" id="loungeDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="loungeDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="lounge-section lounge-anim from-left">
            <div class="lounge-section-title">Confirmez votre présence</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="lounge-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>" placeholder="Combien serez-vous ?">
                </div>
                
                <div class="lounge-form-group">
                    <label>Votre réponse</label>
                    <div class="lounge-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="lounge-option-radio">
                            <label for="presenceOui" class="lounge-option-label">
                                <i class="fas fa-check"></i> Je serai là
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="lounge-option-radio">
                            <label for="presenceNon" class="lounge-option-label">
                                <i class="fas fa-times"></i> Je ne peux pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="lounge-form-group">
                    <label>Message personnel</label>
                    <textarea name="message_invite" rows="3" placeholder="Un mot à partager..."></textarea>
                </div>
                
                <button type="submit" class="lounge-btn-submit">
                    <i class="fas fa-paper-plane"></i> ENVOYER MA RÉPONSE
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="lounge-section lounge-anim from-right">
            <div class="lounge-section-title">Sélection des boissons</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;font-family:'Playfair Display',serif;font-style:italic;font-size:17px;color:var(--gold);padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos choix sont enregistrés
                </div>
                <div class="lounge-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="lounge-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Playfair Display',serif;font-style:italic;font-size:16px;color:var(--silver);margin-bottom:30px;">
                        Sélectionnez jusqu'à <strong style="color:var(--gold);font-style:normal;">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="lounge-boisson-category">
                            <div class="lounge-boisson-category-title">
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="lounge-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="lounge-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="lounge-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="lounge-footer">
        <div class="lounge-footer-star">✦</div>
        <div class="lounge-footer-brand"><?php echo htmlspecialchars($appName); ?></div>
        <div class="lounge-footer-tagline">L'élégance au service de vos plus belles soirées</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="lounge-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:40px;padding-top:30px;border-top:1px solid rgba(212, 175, 55, 0.15);font-family:'Inter',sans-serif;font-size:10px;color:var(--gray);letter-spacing:0.4em;text-transform:uppercase;">
            ✦ © <?php echo date('Y'); ?> · TOUS DROITS RÉSERVÉS ✦
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">TÉLÉCHARGER</span>
    </button>

    <script>
        // ================================================================
        // BULLES FLOTTANTES
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('bubblesContainer');
            for (let i = 0; i < 20; i++) {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                const size = 6 + Math.random() * 14;
                bubble.style.width = size + 'px';
                bubble.style.height = size + 'px';
                bubble.style.left = Math.random() * 100 + '%';
                bubble.style.animationDuration = (8 + Math.random() * 8) + 's';
                bubble.style.animationDelay = (Math.random() * 10) + 's';
                container.appendChild(bubble);
            }
        });

        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.lounge-anim');
            
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
                        colorDark: '#0a0a0a',
                        colorLight: '#f5efe3',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA
        // ================================================================
        let loungeDiapoIndex = 0;
        const loungeSlides = document.querySelectorAll('#loungeDiaporama .slide');
        const loungeDots = document.querySelectorAll('#loungeDiapoDots span');
        const loungeCounter = document.getElementById('loungeDiapoCounter');
        let loungeDiapoInterval = null;

        function loungeUpdateDiapo() {
            loungeSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === loungeDiapoIndex);
            });
            loungeDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === loungeDiapoIndex);
            });
            if (loungeCounter) {
                loungeCounter.textContent = (loungeDiapoIndex + 1) + ' / ' + loungeSlides.length;
            }
        }

        function loungeDiapoChange(direction) {
            loungeDiapoIndex += direction;
            if (loungeDiapoIndex < 0) loungeDiapoIndex = loungeSlides.length - 1;
            if (loungeDiapoIndex >= loungeSlides.length) loungeDiapoIndex = 0;
            loungeUpdateDiapo();
            resetLoungeDiapoAuto();
        }

        function loungeDiapoGoTo(index) {
            loungeDiapoIndex = index;
            loungeUpdateDiapo();
            resetLoungeDiapoAuto();
        }

        function resetLoungeDiapoAuto() {
            if (loungeDiapoInterval) clearInterval(loungeDiapoInterval);
            if (loungeSlides.length > 1) {
                loungeDiapoInterval = setInterval(() => {
                    loungeDiapoIndex = (loungeDiapoIndex + 1) % loungeSlides.length;
                    loungeUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (loungeSlides.length > 0) {
                loungeUpdateDiapo();
                resetLoungeDiapoAuto();
                
                const container = document.getElementById('loungeDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (loungeDiapoInterval) clearInterval(loungeDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetLoungeDiapoAuto);
                }
            }
        });

        // ================================================================
        // TÉLÉCHARGEMENT — CORRIGÉ
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
                // 1. Attendre que tout soit rendu
                await new Promise(r => setTimeout(r, 1500));
                
                // 2. Vérifier que le QR code est généré
                let qrReady = false;
                for (let i = 0; i < 10; i++) {
                    const qrCanvas = document.querySelector('#qrcode canvas');
                    const qrImg = document.querySelector('#qrcode img');
                    if (qrCanvas || qrImg) {
                        qrReady = true;
                        break;
                    }
                    await new Promise(r => setTimeout(r, 300));
                }
                
                if (!qrReady) {
                    console.warn('QR code non prêt, on continue quand même...');
                }
                
                // 3. Attendre le chargement des images
                const images = card.querySelectorAll('img');
                await Promise.all(Array.from(images).map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(resolve => {
                        img.onload = resolve;
                        img.onerror = resolve;
                        setTimeout(resolve, 2000);
                    });
                }));
                
                // 4. Forcer l'affichage de TOUS les éléments visibles
                card.querySelectorAll('.lounge-anim').forEach(el => {
                    el.classList.add('apparue');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.visibility = 'visible';
                });
                
                // 5. Forcer le blason hero
                card.querySelectorAll('.lounge-blason, .lounge-star, .lounge-badge, .lounge-guest, .lounge-divider, .lounge-hosts-intro, .lounge-host-name, .lounge-event-type').forEach(el => {
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.animation = 'none';
                    el.style.visibility = 'visible';
                });
                
                // 6. Attendre un peu
                await new Promise(r => setTimeout(r, 300));
                
                // 7. Capturer
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
                        // Forcer le wrapper
                        const clonedCard = clonedDoc.getElementById('downloadCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                            clonedCard.style.transform = 'none';
                            clonedCard.style.background = '#0a0a0a';
                        }
                        
                        // Désactiver toutes les animations
                        clonedDoc.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                        });
                        
                        // Forcer l'affichage de tous les éléments cachés
                        clonedDoc.querySelectorAll('.lounge-anim').forEach(el => {
                            el.classList.add('apparue');
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.visibility = 'visible';
                        });
                        
                        // Forcer le blason hero
                        clonedDoc.querySelectorAll('.lounge-blason, .lounge-star, .lounge-badge, .lounge-guest, .lounge-divider, .lounge-hosts-intro, .lounge-host-name, .lounge-event-type').forEach(el => {
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.animation = 'none';
                            el.style.visibility = 'visible';
                        });
                        
                        // S'assurer que le QR code est visible
                        const qrBox = clonedDoc.querySelector('.lounge-qr-box');
                        if (qrBox) {
                            qrBox.style.display = 'inline-block';
                            qrBox.style.visibility = 'visible';
                            qrBox.style.opacity = '1';
                        }
                        
                        // S'assurer que le hero a un fond
                        const clonedHero = clonedDoc.querySelector('.lounge-hero');
                        if (clonedHero) {
                            clonedHero.style.background = 'radial-gradient(ellipse 600px 400px at 50% 30%, rgba(212, 175, 55, 0.05) 0%, transparent 100%), radial-gradient(ellipse at top, #1a1a1a 0%, transparent 60%), radial-gradient(ellipse at bottom, #2a1a0a 0%, transparent 60%), #0a0a0a';
                        }
                    }
                });
                
                // 8. Télécharger
                const link = document.createElement('a');
                link.download = `anniversaire_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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

        // ================================================================
        // BOISSONS
        // ================================================================
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.lounge-boisson-item.selected').forEach(item => {
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