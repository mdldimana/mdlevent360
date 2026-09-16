<?php
/**
 * ============================================================
 * TEMPLATE : CARS / RACING
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Racing+Sans+One&family=Bebas+Neue&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --racing-red: #e63946;
            --racing-red-dark: #a4161a;
            --racing-black: #0a0a0a;
            --racing-dark: #1a1a1a;
            --racing-gray: #2a2a2a;
            --racing-yellow: #ffd60a;
            --racing-white: #ffffff;
            --racing-orange: #ff8500;
            --racing-blue: #1e90ff;
            --text: #ffffff;
            --text-muted: #888;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--racing-black);
            background-image: 
                repeating-linear-gradient(0deg, 
                    transparent 0, transparent 40px,
                    rgba(230, 57, 70, 0.03) 40px, rgba(230, 57, 70, 0.03) 41px),
                repeating-linear-gradient(90deg, 
                    transparent 0, transparent 40px,
                    rgba(230, 57, 70, 0.03) 40px, rgba(230, 57, 70, 0.03) 41px);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           INTRO : FEUX DE DÉPART
           ============================================ */
        .racing-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--racing-black);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introFadeOut 3.5s ease-in-out 1s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .lights-container {
            display: flex;
            gap: 20px;
        }
        .light {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #333;
            border: 4px solid #555;
            position: relative;
            box-shadow: inset 0 4px 8px rgba(0,0,0,0.8);
        }
        .light::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            opacity: 0;
            animation: lightOn 0.6s forwards;
        }
        .light:nth-child(1)::after { background: var(--racing-red); box-shadow: 0 0 40px var(--racing-red); animation-delay: 0.3s; }
        .light:nth-child(2)::after { background: var(--racing-red); box-shadow: 0 0 40px var(--racing-red); animation-delay: 0.7s; }
        .light:nth-child(3)::after { background: var(--racing-red); box-shadow: 0 0 40px var(--racing-red); animation-delay: 1.1s; }
        .light:nth-child(4)::after { background: var(--racing-yellow); box-shadow: 0 0 40px var(--racing-yellow); animation-delay: 1.5s; }
        .light:nth-child(5)::after { background: #00e676; box-shadow: 0 0 60px #00e676; animation-delay: 2s; }
        
        @keyframes lightOn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        /* ============================================
           NAVBAR
           ============================================ */
        .racing-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 4px solid var(--racing-red);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 4s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .racing-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Racing Sans One', cursive;
            font-size: 24px;
            letter-spacing: 0.05em;
            color: var(--racing-red);
            text-shadow: 
                0 0 10px var(--racing-red),
                2px 2px 0 var(--racing-black);
        }
        
        .racing-status {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.25em;
            color: var(--racing-yellow);
        }
        
        /* ============================================
           DRAPEAU À DAMIER (bandes décoratives)
           ============================================ */
        .checkered-flag {
            position: fixed;
            left: 0; right: 0;
            height: 20px;
            z-index: 100;
            background-image: 
                linear-gradient(45deg, #000 25%, transparent 25%, transparent 75%, #000 75%, #000),
                linear-gradient(45deg, #000 25%, #fff 25%, #fff 75%, #000 75%, #000);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            pointer-events: none;
        }
        .checkered-flag.top { top: 70px; }
        .checkered-flag.bottom { bottom: 0; }
        
        /* ============================================
           HERO
           ============================================ */
        .racing-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            z-index: 10;
            overflow: hidden;
        }
        
        /* Piste de course avec lignes jaunes */
        .track-lines {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 200px;
            background: linear-gradient(180deg, transparent 0%, var(--racing-dark) 100%);
            overflow: hidden;
        }
        .track-lines::before {
            content: '';
            position: absolute;
            bottom: 30px; left: 0; right: 0;
            height: 6px;
            background: repeating-linear-gradient(90deg,
                var(--racing-yellow) 0, var(--racing-yellow) 60px,
                transparent 60px, transparent 120px);
            animation: trackMove 1s linear infinite;
        }
        @keyframes trackMove {
            0% { transform: translateX(0); }
            100% { transform: translateX(-120px); }
        }
        
        .racing-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 850px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 4.2s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .racing-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 22px;
            background: var(--racing-red);
            color: white;
            font-family: 'Racing Sans One', cursive;
            font-size: 14px;
            letter-spacing: 0.15em;
            border-radius: 4px;
            margin-bottom: 30px;
            box-shadow: 
                4px 4px 0 var(--racing-black),
                0 0 30px rgba(230, 57, 70, 0.5);
            transform: rotate(-2deg);
        }
        
        .racing-guest {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(36px, 7vw, 64px);
            letter-spacing: 0.08em;
            background: linear-gradient(180deg, var(--racing-white) 0%, var(--racing-yellow) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            text-shadow: 0 4px 20px rgba(255, 214, 10, 0.3);
        }
        
        .racing-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .racing-divider .line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--racing-yellow), transparent);
        }
        .racing-divider .icon {
            font-size: 28px;
            color: var(--racing-yellow);
            text-shadow: 0 0 20px var(--racing-yellow);
        }
        
        .racing-hosts-intro {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            letter-spacing: 0.4em;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .racing-host-name {
            font-family: 'Racing Sans One', cursive;
            font-size: clamp(56px, 12vw, 110px);
            line-height: 0.9;
            background: linear-gradient(135deg, 
                var(--racing-red) 0%, 
                var(--racing-orange) 50%,
                var(--racing-yellow) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.02em;
            filter: drop-shadow(0 6px 30px rgba(230, 57, 70, 0.5));
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        
        .racing-event-type {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 0.5em;
            color: var(--racing-yellow);
            text-shadow: 0 0 15px var(--racing-yellow);
        }
        
        /* ============================================
           CARTE RACING
           ============================================ */
        .racing-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--racing-dark);
            border: 4px solid var(--racing-red);
            box-shadow: 
                12px 12px 0 var(--racing-yellow),
                24px 24px 0 rgba(230, 57, 70, 0.3),
                0 0 60px rgba(230, 57, 70, 0.2);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
            z-index: 10;
        }
        .racing-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .racing-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        /* Coin damier */
        .racing-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 80px; height: 80px;
            background-image: 
                linear-gradient(45deg, #000 25%, transparent 25%, transparent 75%, #000 75%, #000),
                linear-gradient(45deg, #000 25%, #fff 25%, #fff 75%, #000 75%, #000);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            z-index: 1;
        }
        
        .racing-card-title {
            font-family: 'Racing Sans One', cursive;
            font-size: 32px;
            letter-spacing: 0.05em;
            color: var(--racing-yellow);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 3px dashed var(--racing-red);
            text-transform: uppercase;
            text-shadow: 3px 3px 0 var(--racing-red);
        }
        
        .racing-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .racing-info-grid { grid-template-columns: 1fr; }
        }
        
        .racing-info-item {
            padding: 24px;
            background: var(--racing-black);
            border: 3px solid var(--racing-red);
            transition: all 0.3s ease;
            text-align: center;
        }
        .racing-info-item:nth-child(even) {
            border-color: var(--racing-yellow);
        }
        .racing-info-item:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 12px 40px rgba(230, 57, 70, 0.4);
            border-color: var(--racing-yellow);
        }
        
        .racing-info-item .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--racing-red), var(--racing-orange));
            color: white;
            font-size: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }
        .racing-info-item .label {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 13px;
            letter-spacing: 0.3em;
            color: var(--racing-yellow);
            margin-bottom: 10px;
        }
        .racing-info-item .value {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 26px;
            letter-spacing: 0.05em;
            color: var(--racing-white);
            line-height: 1.3;
        }
        .racing-info-item .value .sub {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 400;
            letter-spacing: 0;
        }
        
        .racing-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 16px 32px;
            background: var(--racing-yellow);
            color: var(--racing-black);
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.15em;
            text-decoration: none;
            border: 3px solid var(--racing-black);
            box-shadow: 6px 6px 0 var(--racing-red);
            transition: all 0.3s ease;
        }
        .racing-btn-itinerary:hover {
            transform: translateY(-3px);
            box-shadow: 8px 8px 0 var(--racing-red);
            color: var(--racing-black);
        }
        
        /* Sections */
        .racing-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--racing-dark);
            border: 4px solid var(--racing-red);
            box-shadow: 
                10px 10px 0 var(--racing-yellow),
                0 0 60px rgba(230, 57, 70, 0.15);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
            z-index: 10;
        }
        .racing-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .racing-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .racing-section-title {
            font-family: 'Racing Sans One', cursive;
            font-size: 28px;
            letter-spacing: 0.05em;
            color: var(--racing-yellow);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px dashed var(--racing-red);
            text-transform: uppercase;
            text-shadow: 3px 3px 0 var(--racing-red);
        }
        
        /* Formulaires */
        .racing-form-group { margin-bottom: 24px; }
        .racing-form-group label {
            display: block;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.25em;
            color: var(--racing-yellow);
            margin-bottom: 10px;
        }
        .racing-form-group input,
        .racing-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: var(--racing-black);
            border: 3px solid var(--racing-red);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .racing-form-group input:focus,
        .racing-form-group textarea:focus {
            outline: none;
            border-color: var(--racing-yellow);
            box-shadow: 0 0 0 4px rgba(255, 214, 10, 0.2);
        }
        
        .racing-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .racing-options-grid { grid-template-columns: 1fr; }
        }
        
        .racing-option-radio { display: none; }
        .racing-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 3px solid var(--racing-red);
            background: var(--racing-black);
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .racing-option-label:hover {
            border-color: var(--racing-yellow);
            color: var(--racing-yellow);
        }
        .racing-option-radio:checked + .racing-option-label {
            border-color: var(--racing-yellow);
            background: var(--racing-yellow);
            color: var(--racing-black);
            box-shadow: 0 0 30px rgba(255, 214, 10, 0.4);
        }
        
        .racing-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 22px;
            background: linear-gradient(135deg, var(--racing-red), var(--racing-orange));
            color: white;
            border: 4px solid var(--racing-black);
            font-family: 'Racing Sans One', cursive;
            font-size: 24px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                6px 6px 0 var(--racing-yellow),
                0 0 40px rgba(230, 57, 70, 0.4);
            margin-top: 10px;
        }
        .racing-btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 
                8px 8px 0 var(--racing-yellow),
                0 0 60px rgba(255, 214, 10, 0.6);
        }
        
        /* Photos */
        .racing-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }
        .racing-photo {
            background: var(--racing-black);
            padding: 14px 14px 55px;
            border: 4px solid var(--racing-red);
            box-shadow: 
                6px 6px 0 var(--racing-yellow),
                12px 12px 0 rgba(230, 57, 70, 0.3);
            transform: rotate(-1deg);
            transition: all 0.4s ease;
            position: relative;
        }
        .racing-photo:nth-child(even) { 
            transform: rotate(1deg);
            border-color: var(--racing-yellow);
        }
        .racing-photo:hover {
            transform: rotate(0) scale(1.05);
            z-index: 5;
        }
        .racing-photo img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
        }
        .racing-photo .caption {
            position: absolute;
            bottom: 14px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            color: var(--racing-yellow);
            letter-spacing: 0.1em;
        }
        
        /* Boissons */
        .racing-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .racing-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 3px solid var(--racing-red);
            background: var(--racing-black);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.1em;
            color: var(--text-muted);
        }
        .racing-boisson-item.selected {
            border-color: var(--racing-yellow);
            background: var(--racing-yellow);
            color: var(--racing-black);
        }
        .racing-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .racing-boisson-item.selected .check { opacity: 1; }
        
        .racing-boisson-category { margin-bottom: 20px; }
        .racing-boisson-category-title {
            font-family: 'Racing Sans One', cursive;
            font-size: 20px;
            color: var(--racing-red);
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        
        /* QR */
        .racing-qr-wrapper { text-align: center; }
        .racing-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 4px solid var(--racing-black);
            box-shadow: 
                8px 8px 0 var(--racing-yellow),
                16px 16px 0 var(--racing-red);
        }
        
        /* Footer */
        .racing-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .racing-footer-brand {
            font-family: 'Racing Sans One', cursive;
            font-size: 42px;
            color: var(--racing-red);
            text-shadow: 
                4px 4px 0 var(--racing-yellow),
                0 0 30px rgba(230, 57, 70, 0.5);
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .racing-footer-tagline {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.4em;
            color: var(--racing-yellow);
            margin-bottom: 30px;
        }
        
        .racing-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 40px;
            background: #25d366;
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.15em;
            text-decoration: none;
            border: 3px solid var(--racing-black);
            box-shadow: 6px 6px 0 var(--racing-yellow);
            transition: all 0.3s ease;
        }
        .racing-btn-whatsapp:hover {
            transform: translateY(-3px);
            box-shadow: 8px 8px 0 var(--racing-yellow);
            color: white;
        }
        
        /* Alerts */
        .racing-alert {
            padding: 18px 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.1em;
            border: 3px solid;
        }
        .racing-alert-success { border-color: #00e676; background: rgba(0, 230, 118, 0.1); color: #00e676; }
        .racing-alert-danger { border-color: var(--racing-red); background: rgba(230, 57, 70, 0.1); color: #ff6b6b; }
        .racing-alert-warning { border-color: var(--racing-yellow); background: rgba(255, 214, 10, 0.1); color: var(--racing-yellow); }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 18px 32px;
            background: var(--racing-red);
            color: white;
            border: 4px solid var(--racing-black);
            font-family: 'Racing Sans One', cursive;
            font-size: 18px;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 6px 6px 0 var(--racing-yellow);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 4s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px);
            box-shadow: 8px 8px 0 var(--racing-yellow);
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 20px; font-size: 14px; }
        }
    </style>
</head>
<body>

    <!-- INTRO -->
    <div class="racing-intro">
        <div class="lights-container">
            <div class="light"></div>
            <div class="light"></div>
            <div class="light"></div>
            <div class="light"></div>
            <div class="light"></div>
        </div>
    </div>

    <!-- Drapeau damier -->
    <div class="checkered-flag top"></div>
    <div class="checkered-flag bottom"></div>

    <!-- NAVBAR -->
    <nav class="racing-navbar">
        <div class="racing-brand">🏁 <?php echo htmlspecialchars($appName); ?></div>
        <div class="racing-status">🏎️ GRAND PRIX INVITATION</div>
    </nav>

    <!-- HERO -->
    <section class="racing-hero">
        <div class="track-lines"></div>
        
        <div class="racing-blason">
            
            <div class="racing-badge">
                🏁 INVITATION OFFICIELLE
            </div>
            
            <div class="racing-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="racing-divider">
                <div class="line"></div>
                <span class="icon">🏎️</span>
                <div class="line"></div>
            </div>
            
            <div class="racing-hosts-intro">
                🏁 VOUS ÊTES INVITÉ À LA COURSE DE 🏁
            </div>
            <div class="racing-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="racing-event-type">
                🏎️ <?php echo htmlspecialchars(strtoupper($eventType)); ?> 🏎️
            </div>
            
        </div>
    </section>

    <!-- CARTE -->
    <div class="racing-card">
        <div class="racing-card-title">⚡ DÉTAILS DE LA COURSE ⚡</div>
        <div class="racing-info-grid">
            
            <div class="racing-info-item">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="racing-info-item">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="racing-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-flag-checkered"></i></div>
                <div class="label">CIRCUIT</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" rel="noopener" class="racing-btn-itinerary">
                    <i class="fas fa-route"></i> ITINÉRAIRE
                </a>
            </div>
            
            <div class="racing-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="label">PLACES AU STAND</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <?php if ($message): ?>
        <div class="racing-section apparue">
            <div class="racing-alert racing-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($photosHost)): ?>
        <div class="racing-section">
            <div class="racing-section-title">📸 PHOTOS DE COURSE 📸</div>
            <div class="racing-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="racing-photo">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                        <div class="caption">PHOTO #<?php echo $index + 1; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="racing-section">
        <div class="racing-section-title">🏁 CODE D'ACCÈS 🏁</div>
        <div class="racing-qr-wrapper">
            <div class="racing-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Bebas Neue',sans-serif;font-size:20px;color:var(--racing-yellow);letter-spacing:0.2em;margin-top:24px;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="racing-section">
            <div class="racing-section-title">🏎️ CONFIRMATION 🏎️</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="racing-form-group">
                    <label>NOMBRE DE PILOTES</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="racing-form-group">
                    <label>VOTRE RÉPONSE</label>
                    <div class="racing-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="racing-option-radio">
                            <label for="presenceOui" class="racing-option-label">🏁 AU DÉPART</label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="racing-option-radio">
                            <label for="presenceNon" class="racing-option-label">✗ FORFAIT</label>
                        </div>
                    </div>
                </div>
                
                <div class="racing-form-group">
                    <label>MESSAGE</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="racing-btn-submit">
                    <i class="fas fa-flag-checkered"></i> ENVOYER !
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="racing-section">
            <div class="racing-section-title">🥤 PIT STOP 🥤</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--racing-yellow);font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:0.1em;padding:20px 0;">
                    <i class="fas fa-lock"></i> PRÉFÉRENCES VERROUILLÉES
                </div>
                <div class="racing-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="racing-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Bebas Neue',sans-serif;font-size:18px;color:var(--text-muted);margin-bottom:24px;letter-spacing:0.15em;">
                        SÉLECTIONNEZ <strong style="color:var(--racing-yellow);">2 BOISSONS</strong> : <span id="selectedCount" style="color:var(--racing-yellow);">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="racing-boisson-category">
                            <div class="racing-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'AUTRES'); ?>
                            </div>
                            <div class="racing-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="racing-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="racing-btn-submit">
                        <i class="fas fa-save"></i> SAUVEGARDER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="racing-footer">
        <div class="racing-footer-brand">🏁 <?php echo htmlspecialchars($appName); ?></div>
        <div class="racing-footer-tagline">DES INVITATIONS À PLEINE VITESSE</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="racing-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px dashed var(--racing-red);font-family:'Bebas Neue',sans-serif;font-size:12px;color:var(--text-muted);letter-spacing:0.3em;">
            © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">TÉLÉCHARGER</span>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.racing-card, .racing-section');
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

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180, height: 180,
                        colorDark: '#0a0a0a', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.racing-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#0a0a0a', logging: false
                });
                const link = document.createElement('a');
                link.download = `racing_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                btnText.textContent = '✓ TÉLÉCHARGÉ';
                setTimeout(() => btnText.textContent = 'TÉLÉCHARGER', 3000);
            } catch(e) {
                btnText.textContent = 'ERREUR';
                setTimeout(() => btnText.textContent = 'TÉLÉCHARGER', 3000);
            }
            btn.disabled = false;
        }

        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.racing-boisson-item.selected').forEach(item => {
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