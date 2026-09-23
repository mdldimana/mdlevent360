<?php
/**
 * ============================================================
 * TEMPLATE : DÉFILÉ DE MODE (Fashion Week) - v3
 * ============================================================
 * 
 * Nouveautés v3 :
 * - Téléchargement capture #downloadCard (Hero + Lookbook + QR)
 * - Le QR code est inclus dans l'image téléchargée
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
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Didact+Gothic&family=Inter:wght@300;400;500;600;700&family=Italiana&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --black: #000000;
            --near-black: #0a0a0a;
            --off-black: #1a1a1a;
            --dark-gray: #2a2a2a;
            --gray: #404040;
            --light-gray: #888888;
            --silver: #d4d4d4;
            --white: #ffffff;
            --off-white: #f5f5f5;
            --cream: #ede7d9;
            --gold: #c9a961;
            --gold-light: #e8d4a2;
            --red: #d40000;
            --red-dark: #a00000;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--black);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           INTRO FASHION SHOW
           ============================================ */
        .fashion-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            overflow: hidden;
            animation: introFadeOut 2.5s ease-in-out 2.5s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .spotlight {
            position: absolute;
            top: -50%;
            left: 50%;
            width: 400px;
            height: 200%;
            background: linear-gradient(180deg, 
                rgba(255, 255, 255, 0.15) 0%,
                rgba(255, 255, 255, 0.08) 30%,
                transparent 100%);
            transform-origin: center top;
            pointer-events: none;
            filter: blur(20px);
        }
        .spotlight.s1 { animation: sweep1 4s ease-in-out infinite; }
        .spotlight.s2 { animation: sweep2 5s ease-in-out infinite; animation-delay: 0.5s; opacity: 0.6; }
        .spotlight.s3 { animation: sweep3 3.5s ease-in-out infinite; animation-delay: 1s; opacity: 0.4; }
        
        @keyframes sweep1 {
            0%, 100% { transform: translateX(-50%) rotate(-30deg); }
            50% { transform: translateX(-50%) rotate(30deg); }
        }
        @keyframes sweep2 {
            0%, 100% { transform: translateX(-50%) rotate(40deg); }
            50% { transform: translateX(-50%) rotate(-40deg); }
        }
        @keyframes sweep3 {
            0%, 100% { transform: translateX(-50%) rotate(10deg); }
            50% { transform: translateX(-50%) rotate(-20deg); }
        }
        
        .fashion-intro-text {
            font-family: 'Playfair Display', serif;
            font-size: clamp(30px, 6vw, 60px);
            font-weight: 400;
            letter-spacing: 0.5em;
            color: var(--white);
            text-transform: uppercase;
            opacity: 0;
            animation: textReveal 1.5s ease-out 0.3s forwards;
            position: relative;
            z-index: 2;
            text-align: center;
            padding-left: 0.5em;
        }
        .fashion-intro-sub {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            letter-spacing: 0.6em;
            color: var(--gold);
            margin-top: 20px;
            text-transform: uppercase;
            opacity: 0;
            animation: textReveal 1.5s ease-out 0.8s forwards;
            position: relative;
            z-index: 2;
            padding-left: 0.6em;
        }
        @keyframes textReveal {
            0% { opacity: 0; letter-spacing: 1em; filter: blur(10px); }
            100% { opacity: 1; letter-spacing: 0.5em; filter: blur(0); }
        }
        
        .fashion-intro-line {
            width: 0;
            height: 1px;
            background: var(--gold);
            margin-top: 30px;
            animation: lineExpand 1.2s ease-out 1.2s forwards;
            position: relative;
            z-index: 2;
        }
        @keyframes lineExpand {
            0% { width: 0; }
            100% { width: 200px; }
        }
        
        /* ============================================
           FLASHES PAPARAZZI
           ============================================ */
        .camera-flash {
            position: fixed;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            box-shadow: 
                0 0 20px 10px rgba(255, 255, 255, 0.9),
                0 0 40px 20px rgba(255, 255, 255, 0.5);
            opacity: 0;
            pointer-events: none;
            z-index: 900;
            animation: flashBang 6s ease-in-out infinite;
        }
        .camera-flash.f1 { top: 15%; left: 10%; animation-delay: 0s; }
        .camera-flash.f2 { top: 25%; right: 15%; animation-delay: 1.5s; }
        .camera-flash.f3 { top: 60%; left: 20%; animation-delay: 3s; }
        .camera-flash.f4 { top: 70%; right: 25%; animation-delay: 4.5s; }
        .camera-flash.f5 { top: 40%; left: 8%; animation-delay: 2s; }
        .camera-flash.f6 { top: 50%; right: 10%; animation-delay: 5s; }
        
        @keyframes flashBang {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            5% { opacity: 1; transform: scale(1.5); }
            10% { opacity: 0; transform: scale(1); }
        }
        
        /* ============================================
           ANIMATIONS DE SECTIONS
           ============================================ */
        .fashion-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .fashion-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .fashion-anim.from-left {
            transform: translateX(-80px);
        }
        .fashion-anim.from-left.apparue {
            transform: translateX(0);
        }
        
        .fashion-anim.from-right {
            transform: translateX(80px);
        }
        .fashion-anim.from-right.apparue {
            transform: translateX(0);
        }
        
        .fashion-anim.zoom-in {
            transform: scale(0.85);
        }
        .fashion-anim.zoom-in.apparue {
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
           HERO FASHION
           ============================================ */
        .fashion-hero {
            position: relative;
            padding: 100px 20px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            overflow: hidden;
            background: 
                radial-gradient(ellipse 800px 500px at 50% 40%, rgba(201, 169, 97, 0.06) 0%, transparent 100%),
                var(--black);
        }
        
        .runway-floor {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(180deg, 
                transparent 0%,
                rgba(201, 169, 97, 0.05) 40%,
                rgba(201, 169, 97, 0.15) 100%);
            pointer-events: none;
            z-index: 0;
        }
        .runway-floor::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%) perspective(500px) rotateX(60deg);
            width: 80%;
            height: 100%;
            background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.1) 100%);
            border-top: 1px solid var(--gold);
            box-shadow: 0 -20px 60px rgba(201, 169, 97, 0.2);
        }
        
        .gold-stripes {
            position: absolute;
            top: 0; bottom: 0;
            width: 1px;
            background: linear-gradient(180deg, 
                transparent 0%,
                var(--gold) 30%,
                var(--gold) 70%,
                transparent 100%);
            opacity: 0.3;
            pointer-events: none;
            z-index: 1;
        }
        .gold-stripes.left { left: 8%; }
        .gold-stripes.right { right: 8%; }
        @media (max-width: 768px) {
            .gold-stripes { display: none; }
        }
        
        .fashion-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 900px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fashion-issue {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 3.5s forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fashion-issue .line {
            width: 60px;
            height: 1px;
            background: var(--gold);
        }
        .fashion-issue .text {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.5em;
            color: var(--gold);
            text-transform: uppercase;
        }
        
        .fashion-guest {
            font-family: 'Playfair Display', serif;
            font-size: clamp(48px, 10vw, 120px);
            font-weight: 400;
            line-height: 0.9;
            letter-spacing: -0.02em;
            color: var(--white);
            margin-bottom: 30px;
            font-style: italic;
            text-shadow: 0 4px 40px rgba(0, 0, 0, 0.8);
            opacity: 0;
            animation: fadeInUp 1.2s ease-out 3.7s forwards;
        }
        
        .fashion-subtitle {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 13px;
            letter-spacing: 0.5em;
            color: var(--silver);
            text-transform: uppercase;
            margin-bottom: 50px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 4s forwards;
        }
        
        .fashion-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 500px;
            margin: 0 auto 40px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 4.2s forwards;
        }
        .fashion-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .fashion-divider .icon {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 24px;
            color: var(--gold);
        }
        
        .fashion-hosts-intro {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 18px;
            color: var(--silver);
            margin-bottom: 20px;
            letter-spacing: 0.05em;
            opacity: 0;
            animation: fadeInUp 1s ease-out 4.4s forwards;
        }
        
        .fashion-host-name {
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
            opacity: 0;
            animation: fadeInUp 1.2s ease-out 4.6s forwards;
        }
        
        .fashion-event-type {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 13px;
            letter-spacing: 0.6em;
            color: var(--gold);
            text-transform: uppercase;
            padding-left: 0.6em;
            opacity: 0;
            animation: fadeInUp 1s ease-out 4.8s forwards;
        }
        
        .fashion-marquee {
            position: absolute;
            top: 90px;
            left: 0; right: 0;
            overflow: hidden;
            height: 30px;
            z-index: 5;
            opacity: 0;
            animation: fadeIn 1s ease-out 3.2s forwards;
        }
        .fashion-marquee-content {
            display: flex;
            gap: 40px;
            white-space: nowrap;
            animation: marqueeScroll 30s linear infinite;
        }
        .fashion-marquee-content span {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 14px;
            color: var(--gold);
            letter-spacing: 0.3em;
            text-transform: uppercase;
        }
        .fashion-marquee-content span::before {
            content: '✦';
            margin-right: 40px;
            font-style: normal;
            color: var(--red);
        }
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        /* ============================================
           LOOKBOOK CARD
           ============================================ */
        .lookbook-card {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 70px 60px;
            background: var(--near-black);
            border: 1px solid rgba(201, 169, 97, 0.3);
            box-shadow: 
                0 0 80px rgba(201, 169, 97, 0.1),
                inset 0 0 80px rgba(201, 169, 97, 0.02);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .lookbook-card { padding: 50px 25px; margin: 60px 15px; }
        }
        
        .lookbook-card::before,
        .lookbook-card::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border: 1px solid var(--gold);
        }
        .lookbook-card::before {
            top: -1px; left: -1px;
            border-right: none; border-bottom: none;
        }
        .lookbook-card::after {
            bottom: -1px; right: -1px;
            border-left: none; border-top: none;
        }
        
        .lookbook-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 30px;
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(201, 169, 97, 0.2);
        }
        .lookbook-header .label {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-transform: uppercase;
        }
        .lookbook-header .number {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            color: var(--white);
        }
        @media (max-width: 480px) {
            .lookbook-header { flex-direction: column; gap: 10px; text-align: center; }
        }
        
        .lookbook-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 36px;
            font-weight: 400;
            color: var(--white);
            text-align: center;
            margin-bottom: 50px;
            letter-spacing: 0.02em;
        }
        @media (max-width: 480px) {
            .lookbook-title { font-size: 26px; }
        }
        
        .lookbook-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 640px) {
            .lookbook-grid { grid-template-columns: 1fr; }
        }
        
        .lookbook-item {
            padding: 30px 24px;
            border-left: 2px solid var(--gold);
            background: linear-gradient(90deg, 
                rgba(201, 169, 97, 0.05) 0%, 
                transparent 100%);
            transition: all 0.4s ease;
            position: relative;
        }
        
        .lookbook-item .label {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .lookbook-item .value {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 400;
            color: var(--white);
            line-height: 1.3;
            letter-spacing: 0.01em;
        }
        .lookbook-item .value .sub {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-style: italic;
            color: var(--light-gray);
            margin-top: 8px;
            font-weight: 300;
            letter-spacing: 0.02em;
        }
        
        .lookbook-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(90deg, 
                rgba(201, 169, 97, 0.15) 0%, 
                rgba(212, 0, 0, 0.05) 100%) !important;
            border-left: 4px solid var(--gold) !important;
        }
        
        .lookbook-table-item .value {
            font-size: 28px !important;
            color: var(--gold) !important;
            font-weight: 700 !important;
            letter-spacing: 0.05em;
            text-shadow: 0 0 20px rgba(201, 169, 97, 0.4);
        }
        
        .fashion-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin-top: 24px;
            padding: 16px 36px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.4s ease;
        }
        .fashion-btn-itinerary:hover {
            background: var(--gold);
            color: var(--black);
        }
        
        /* ============================================
           QR CARD INTÉGRÉE
           ============================================ */
        .fashion-qr-card {
            position: relative;
            max-width: 900px;
            margin: 0 auto 40px;
            padding: 60px 40px;
            background: var(--near-black);
            border: 1px solid rgba(201, 169, 97, 0.3);
            box-shadow: 
                0 0 80px rgba(201, 169, 97, 0.1),
                inset 0 0 80px rgba(201, 169, 97, 0.02);
            z-index: 10;
            text-align: center;
        }
        @media (max-width: 640px) {
            .fashion-qr-card { padding: 45px 25px; margin: 0 15px 40px; }
        }
        
        .fashion-qr-card::before,
        .fashion-qr-card::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border: 1px solid var(--gold);
        }
        .fashion-qr-card::before {
            top: -1px; left: -1px;
            border-right: none; border-bottom: none;
        }
        .fashion-qr-card::after {
            bottom: -1px; right: -1px;
            border-left: none; border-top: none;
        }
        
        .fashion-qr-card-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            font-weight: 400;
            color: var(--white);
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: 0.02em;
            position: relative;
            padding-bottom: 24px;
        }
        .fashion-qr-card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 1px;
            background: var(--gold);
        }
        
        .fashion-qr-wrapper {
            text-align: center;
        }
        .fashion-qr-box {
            display: inline-block;
            padding: 30px;
            background: var(--white);
            position: relative;
            box-shadow: 0 0 60px rgba(201, 169, 97, 0.3);
        }
        .fashion-qr-box::before {
            content: '';
            position: absolute;
            inset: -10px;
            border: 1px solid var(--gold);
        }
        
        /* ============================================
           SECTIONS HORS CAPTURE
           ============================================ */
        .editorial-section {
            position: relative;
            max-width: 900px;
            margin: 80px auto;
            padding: 70px 60px;
            background: var(--near-black);
            border: 1px solid rgba(201, 169, 97, 0.2);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .editorial-section { padding: 45px 25px; margin: 50px 15px; }
        }
        
        .editorial-section-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            font-weight: 400;
            color: var(--white);
            text-align: center;
            margin-bottom: 50px;
            letter-spacing: 0.02em;
            position: relative;
            padding-bottom: 24px;
        }
        .editorial-section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 1px;
            background: var(--gold);
        }
        
        /* ============================================
           DIAPORAMA PHOTOS
           ============================================ */
        .fashion-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: var(--black);
            border: 2px solid var(--gold);
            box-shadow: 0 0 40px rgba(201, 169, 97, 0.3);
        }
        
        .fashion-diaporama .slide {
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
        
        .fashion-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .fashion-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--black);
            padding: 10px;
        }
        
        .fashion-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: rgba(201, 169, 97, 0.9);
            border: 1px solid var(--gold);
            color: var(--black);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(201, 169, 97, 0.4);
        }
        
        .fashion-diapo-arrow:hover {
            background: var(--gold-light);
            transform: translateY(-50%) scale(1.1);
        }
        
        .fashion-diapo-arrow.prev { left: 16px; }
        .fashion-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .fashion-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .fashion-diapo-arrow.prev { left: 8px; }
            .fashion-diapo-arrow.next { right: 8px; }
        }
        
        .fashion-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid var(--gold);
            color: var(--gold);
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            padding: 8px 16px;
            z-index: 10;
        }
        
        .fashion-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.85);
            padding: 10px 20px;
            border: 1px solid var(--gold);
            backdrop-filter: blur(10px);
        }
        
        .fashion-diapo-dots span {
            width: 10px;
            height: 10px;
            background: rgba(201, 169, 97, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fashion-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.3);
            box-shadow: 0 0 12px rgba(201, 169, 97, 0.9);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .fashion-form-group { margin-bottom: 30px; }
        .fashion-form-group label {
            display: block;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .fashion-form-group input,
        .fashion-form-group textarea {
            width: 100%;
            padding: 18px 4px;
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(201, 169, 97, 0.4);
            color: var(--white);
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-style: italic;
            transition: all 0.4s ease;
        }
        .fashion-form-group input:focus,
        .fashion-form-group textarea:focus {
            outline: none;
            border-bottom-color: var(--gold);
            padding-left: 10px;
            background: linear-gradient(90deg, 
                rgba(201, 169, 97, 0.05) 0%, 
                transparent 100%);
        }
        .fashion-form-group input::placeholder,
        .fashion-form-group textarea::placeholder {
            color: var(--gray);
        }
        
        .fashion-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 480px) {
            .fashion-options-grid { grid-template-columns: 1fr; }
        }
        
        .fashion-option-radio { display: none; }
        .fashion-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 24px 20px;
            border: 1px solid rgba(201, 169, 97, 0.4);
            background: transparent;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.3em;
            color: var(--silver);
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
        }
        .fashion-option-radio:checked + .fashion-option-label {
            border-color: var(--gold);
            background: var(--gold);
            color: var(--black);
            box-shadow: 0 0 40px rgba(201, 169, 97, 0.4);
        }
        
        .fashion-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            padding: 22px;
            background: linear-gradient(135deg, var(--gold), #a8884a);
            color: var(--black);
            border: none;
            font-family: 'Didact Gothic', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 20px;
        }
        .fashion-btn-submit:hover {
            box-shadow: 0 12px 40px rgba(201, 169, 97, 0.5);
            transform: translateY(-2px);
        }
        
        /* BOISSONS */
        .fashion-boisson-category { margin-bottom: 30px; }
        .fashion-boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 22px;
            color: var(--gold);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(201, 169, 97, 0.2);
            letter-spacing: 0.02em;
        }
        
        .fashion-boisson-grid { display: flex; flex-wrap: wrap; gap: 12px; }
        .fashion-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border: 1px solid rgba(201, 169, 97, 0.3);
            background: transparent;
            cursor: pointer;
            transition: all 0.4s ease;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 300;
            letter-spacing: 0.05em;
            color: var(--silver);
        }
        .fashion-boisson-item.selected {
            border-color: var(--gold);
            background: rgba(201, 169, 97, 0.15);
            color: var(--gold-light);
        }
        .fashion-boisson-item .check {
            opacity: 0;
            transition: opacity 0.3s ease;
            color: var(--gold);
        }
        .fashion-boisson-item.selected .check { opacity: 1; }
        
        /* FOOTER */
        .fashion-footer {
            padding: 80px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
            border-top: 1px solid rgba(201, 169, 97, 0.15);
            margin-top: 80px;
        }
        .fashion-footer-brand {
            font-family: 'Italiana', serif;
            font-size: 52px;
            letter-spacing: 0.2em;
            color: var(--white);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .fashion-footer-brand .accent {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
            font-style: italic;
        }
        .fashion-footer-tagline {
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.6em;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 40px;
            padding-left: 0.6em;
        }
        
        .fashion-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 40px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.4s ease;
        }
        .fashion-btn-whatsapp:hover {
            background: var(--gold);
            color: var(--black);
        }
        
        .fashion-alert {
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
        .fashion-alert-success { 
            border-color: var(--gold); 
            background: rgba(201, 169, 97, 0.08); 
            color: var(--gold-light); 
        }
        .fashion-alert-danger { 
            border-color: var(--red); 
            background: rgba(212, 0, 0, 0.08); 
            color: #ff8080; 
        }
        .fashion-alert-warning { 
            border-color: #ffa500; 
            background: rgba(255, 165, 0, 0.08); 
            color: #ffcc80; 
        }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 18px 32px;
            background: var(--black);
            color: var(--gold);
            border: 1px solid var(--gold);
            font-family: 'Didact Gothic', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            animation: fadeIn 1s ease-out 5s forwards;
            backdrop-filter: blur(10px);
        }
        #downloadBtn:hover {
            background: var(--gold);
            color: var(--black);
            box-shadow: 0 12px 40px rgba(201, 169, 97, 0.5);
            transform: translateY(-3px);
        }
        @media (max-width: 480px) {
            #downloadBtn { 
                bottom: 16px; 
                right: 16px; 
                padding: 14px 20px; 
                font-size: 10px;
                letter-spacing: 0.2em;
            }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- INTRO -->
    <div class="fashion-intro">
        <div class="spotlight s1"></div>
        <div class="spotlight s2"></div>
        <div class="spotlight s3"></div>
        <div class="fashion-intro-text">DÉFILÉ</div>
        <div class="fashion-intro-line"></div>
        <div class="fashion-intro-sub">ÉDITION SPÉCIALE · 2025</div>
    </div>

    <!-- Flashs paparazzi -->
    <div class="camera-flash f1"></div>
    <div class="camera-flash f2"></div>
    <div class="camera-flash f3"></div>
    <div class="camera-flash f4"></div>
    <div class="camera-flash f5"></div>
    <div class="camera-flash f6"></div>

    <!-- ============================================ -->
    <!-- WRAPPER CAPTURÉ (Hero + Lookbook + QR)      -->
    <!-- ============================================ -->
    <div id="downloadCard">

        <!-- HERO -->
        <section class="fashion-hero">
            <div class="runway-floor"></div>
            <div class="gold-stripes left"></div>
            <div class="gold-stripes right"></div>
            
            <div class="fashion-marquee">
                <div class="fashion-marquee-content">
                    <span>Haute Couture</span>
                    <span>Édition Limitée</span>
                    <span>Invitation Privée</span>
                    <span>Podium Exclusif</span>
                    <span>Haute Couture</span>
                    <span>Édition Limitée</span>
                    <span>Invitation Privée</span>
                    <span>Podium Exclusif</span>
                </div>
            </div>
            
            <div class="fashion-blason">
                <div class="fashion-issue">
                    <div class="line"></div>
                    <div class="text">N° 01 · ÉDITION SPÉCIALE</div>
                    <div class="line"></div>
                </div>
                
                <div class="fashion-guest">
                    <?php echo htmlspecialchars($guestName); ?>
                </div>
                
                <div class="fashion-subtitle">
                    INVITATION PERSONNELLE
                </div>
                
                <div class="fashion-divider">
                    <div class="line"></div>
                    <div class="icon">✦</div>
                    <div class="line"></div>
                </div>
                
                <div class="fashion-hosts-intro">
                    Présenté par
                </div>
                <div class="fashion-host-name"><?php echo htmlspecialchars($host1); ?></div>
                <div class="fashion-event-type">
                    ✦ <?php echo htmlspecialchars(strtoupper($eventType)); ?> ✦
                </div>
            </div>
        </section>

        <!-- LOOKBOOK CARD -->
        <div class="lookbook-card">
            <div class="lookbook-header">
                <div class="label">FICHE TECHNIQUE</div>
                <div class="number">N° 01</div>
            </div>
            
            <div class="lookbook-title">Informations sur le défilé</div>
            
            <div class="lookbook-grid">
                <div class="lookbook-item">
                    <div class="label">DATE DU SHOW</div>
                    <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                </div>
                
                <div class="lookbook-item">
                    <div class="label">HEURE DE DÉBUT</div>
                    <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                </div>
                
                <div class="lookbook-item" style="grid-column: 1 / -1;">
                    <div class="label">LIEU DU PODIUM</div>
                    <div class="value">
                        <?php echo htmlspecialchars($lieuDisplay); ?>
                        <?php if ($adresseDisplay): ?>
                            <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="fashion-btn-itinerary">
                        <i class="fas fa-map-marked-alt"></i> VOIR L'ITINÉRAIRE
                    </a>
                </div>
                
                <?php if ($hasTable): ?>
                <div class="lookbook-item lookbook-table-item">
                    <div class="label">✦ VOTRE PLACE PRIVILÉGIÉE ✦</div>
                    <div class="value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="lookbook-item" style="grid-column: 1 / -1;">
                    <div class="label">PLACES SUR LE PODIUM</div>
                    <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
                </div>
            </div>
        </div>

        <!-- ✅ QR CARD INTÉGRÉE DANS LE WRAPPER -->
        <div class="fashion-qr-card">
            <div class="fashion-qr-card-title">Accès privé</div>
            <div class="fashion-qr-wrapper">
                <div class="fashion-qr-box">
                    <div id="qrcode"></div>
                </div>
                <div style="font-family:'Playfair Display',serif;font-style:italic;font-size:18px;color:var(--gold);margin-top:30px;letter-spacing:0.1em;">
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
        <div class="editorial-section apparue">
            <div class="fashion-alert fashion-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- DIAPORAMA PHOTOS -->
    <?php if ($hasPhotos): ?>
        <div class="editorial-section fashion-anim from-left">
            <div class="editorial-section-title">Galerie · Collection</div>
            
            <div class="fashion-diaporama" id="fashionDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Look ' . ($index + 1)); ?>"
                             loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                             crossorigin="anonymous">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
                
                <?php if ($photoIndex > 1): ?>
                    <button class="fashion-diapo-arrow prev" onclick="fashionDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="fashion-diapo-arrow next" onclick="fashionDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="fashion-diapo-counter" id="fashionDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="fashion-diapo-dots" id="fashionDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="fashionDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="editorial-section fashion-anim from-left">
            <div class="editorial-section-title">RSVP</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="fashion-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>" placeholder="Combien serez-vous ?">
                </div>
                
                <div class="fashion-form-group">
                    <label>Votre réponse</label>
                    <div class="fashion-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="fashion-option-radio">
                            <label for="presenceOui" class="fashion-option-label">
                                <i class="fas fa-check"></i> Je serai présent(e)
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="fashion-option-radio">
                            <label for="presenceNon" class="fashion-option-label">
                                <i class="fas fa-times"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="fashion-form-group">
                    <label>Message personnel</label>
                    <textarea name="message_invite" rows="3" placeholder="Un mot à la maison..."></textarea>
                </div>
                
                <button type="submit" class="fashion-btn-submit">
                    <i class="fas fa-paper-plane"></i> ENVOYER MA RÉPONSE
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="editorial-section fashion-anim from-right">
            <div class="editorial-section-title">Bar à cocktails</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;font-family:'Playfair Display',serif;font-style:italic;font-size:18px;color:var(--gold);padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos choix sont verrouillés
                </div>
                <div class="fashion-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="fashion-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Playfair Display',serif;font-style:italic;font-size:17px;color:var(--silver);margin-bottom:36px;">
                        Sélectionnez jusqu'à <strong style="color:var(--gold);font-style:normal;">2 créations</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="fashion-boisson-category">
                            <div class="fashion-boisson-category-title">
                                <?php echo htmlspecialchars($type ?: 'Autres créations'); ?>
                            </div>
                            <div class="fashion-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="fashion-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="fashion-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="fashion-footer fashion-anim">
        <div class="fashion-footer-brand">
            <?php echo htmlspecialchars(strtoupper($appName)); ?>
            <span class="accent">✦</span>
        </div>
        <div class="fashion-footer-tagline">HAUTE COUTURE · INVITATION</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="fashion-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:50px;padding-top:30px;border-top:1px solid rgba(201, 169, 97, 0.15);font-family:'Didact Gothic',sans-serif;font-size:10px;color:var(--gray);letter-spacing:0.5em;text-transform:uppercase;">
            © <?php echo date('Y'); ?> · TOUS DROITS RÉSERVÉS
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">TÉLÉCHARGER</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.fashion-anim');
            
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
                        width: 180,
                        height: 180,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA
        // ================================================================
        let fashionDiapoIndex = 0;
        const fashionSlides = document.querySelectorAll('#fashionDiaporama .slide');
        const fashionDots = document.querySelectorAll('#fashionDiapoDots span');
        const fashionCounter = document.getElementById('fashionDiapoCounter');
        let fashionDiapoInterval = null;

        function fashionUpdateDiapo() {
            fashionSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === fashionDiapoIndex);
            });
            fashionDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === fashionDiapoIndex);
            });
            if (fashionCounter) {
                fashionCounter.textContent = (fashionDiapoIndex + 1) + ' / ' + fashionSlides.length;
            }
        }

        function fashionDiapoChange(direction) {
            fashionDiapoIndex += direction;
            if (fashionDiapoIndex < 0) fashionDiapoIndex = fashionSlides.length - 1;
            if (fashionDiapoIndex >= fashionSlides.length) fashionDiapoIndex = 0;
            fashionUpdateDiapo();
            resetFashionDiapoAuto();
        }

        function fashionDiapoGoTo(index) {
            fashionDiapoIndex = index;
            fashionUpdateDiapo();
            resetFashionDiapoAuto();
        }

        function resetFashionDiapoAuto() {
            if (fashionDiapoInterval) clearInterval(fashionDiapoInterval);
            if (fashionSlides.length > 1) {
                fashionDiapoInterval = setInterval(() => {
                    fashionDiapoIndex = (fashionDiapoIndex + 1) % fashionSlides.length;
                    fashionUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (fashionSlides.length > 0) {
                fashionUpdateDiapo();
                resetFashionDiapoAuto();
                
                const container = document.getElementById('fashionDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (fashionDiapoInterval) clearInterval(fashionDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetFashionDiapoAuto);
                }
            }
        });

        // ================================================================
        // TÉLÉCHARGEMENT — CAPTURE #downloadCard (Hero + Lookbook + QR)
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
                
                // 2. Vérifier que le QR est généré
                for (let i = 0; i < 10; i++) {
                    const qrCanvas = document.querySelector('#qrcode canvas');
                    const qrImg = document.querySelector('#qrcode img');
                    if (qrCanvas || qrImg) break;
                    await new Promise(r => setTimeout(r, 300));
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
                
                // 4. Forcer l'affichage de tous les éléments
                card.querySelectorAll('.fashion-anim').forEach(el => {
                    el.classList.add('apparue');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.visibility = 'visible';
                });
                
                // 5. Forcer les éléments du hero
                card.querySelectorAll('.fashion-blason, .fashion-issue, .fashion-guest, .fashion-subtitle, .fashion-divider, .fashion-hosts-intro, .fashion-host-name, .fashion-event-type, .fashion-marquee').forEach(el => {
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                    el.style.animation = 'none';
                    el.style.visibility = 'visible';
                });
                
                await new Promise(r => setTimeout(r, 300));
                
                // 6. Capturer
                const canvas = await html2canvas(card, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#000000',
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
                            clonedCard.style.background = '#000000';
                        }
                        
                        // Désactiver les animations
                        clonedDoc.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                        });
                        
                        // Forcer les animations
                        clonedDoc.querySelectorAll('.fashion-anim').forEach(el => {
                            el.classList.add('apparue');
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.visibility = 'visible';
                        });
                        
                        // Forcer les éléments du hero
                        clonedDoc.querySelectorAll('.fashion-blason, .fashion-issue, .fashion-guest, .fashion-subtitle, .fashion-divider, .fashion-hosts-intro, .fashion-host-name, .fashion-event-type, .fashion-marquee').forEach(el => {
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                            el.style.animation = 'none';
                            el.style.visibility = 'visible';
                            // Fixer les gradients
                            el.style.webkitTextFillColor = 'initial';
                        });
                        
                        // S'assurer que le QR est visible
                        const qrBox = clonedDoc.querySelector('.fashion-qr-box');
                        if (qrBox) {
                            qrBox.style.display = 'inline-block';
                            qrBox.style.visibility = 'visible';
                            qrBox.style.opacity = '1';
                        }
                    }
                });
                
                // 7. Télécharger
                const link = document.createElement('a');
                link.download = `fashion_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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

        // Boissons
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fashion-boisson-item.selected').forEach(item => {
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