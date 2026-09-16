<?php
/**
 * ============================================================
 * TEMPLATE : FLORAL v2 — Jardin botanique
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --sage: #a8bfa0;
            --sage-dark: #7d9a75;
            --rose: #e8b4a8;
            --rose-deep: #c98a7d;
            --rose-pale: #fdf6f3;
            --cream: #fdfbf7;
            --paper: #f5efe6;
            --text: #3d3229;
            --text-muted: #8a7a6a;
            --gold: #d4a574;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: var(--paper);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(232, 180, 168, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 60%, rgba(168, 191, 160, 0.12) 0%, transparent 40%),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><filter id="n"><feTurbulence baseFrequency="0.8" numOctaves="4"/></filter><rect width="200" height="200" filter="url(%23n)" opacity="0.03"/></svg>');
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           FLEURS QUI POUSSENT À L'OUVERTURE
           ============================================ */
        .growing-flowers {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: linear-gradient(180deg, var(--cream) 0%, var(--paper) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: gardenFadeOut 2.5s ease-in-out 2s forwards;
        }
        @keyframes gardenFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .flower-growing {
            position: absolute;
            font-size: 120px;
            color: var(--rose);
            opacity: 0;
            transform: scale(0) translateY(200px);
            animation: flowerGrow 2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .flower-growing:nth-child(1) { top: 10%; left: 15%; animation-delay: 0.2s; color: var(--rose-deep); }
        .flower-growing:nth-child(2) { top: 20%; right: 20%; animation-delay: 0.4s; color: var(--sage-dark); font-size: 100px; }
        .flower-growing:nth-child(3) { bottom: 25%; left: 25%; animation-delay: 0.6s; color: var(--rose); font-size: 140px; }
        .flower-growing:nth-child(4) { bottom: 15%; right: 15%; animation-delay: 0.8s; color: var(--sage); font-size: 90px; }
        .flower-growing:nth-child(5) { top: 50%; left: 50%; transform-origin: center; animation-delay: 0.3s; font-size: 200px; }
        
        @keyframes flowerGrow {
            0% { transform: scale(0) translateY(200px); opacity: 0; }
            60% { transform: scale(1.15) translateY(-10px); opacity: 1; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        /* ============================================
           PÉTALES QUI VOLENT
           ============================================ */
        .flying-petals {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 5;
            overflow: hidden;
        }
        .petal {
            position: absolute;
            font-size: 24px;
            color: var(--rose);
            opacity: 0;
            animation: petalFly 12s linear infinite;
        }
        @keyframes petalFly {
            0% { transform: translate(0, 100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.8; }
            90% { opacity: 0.6; }
            100% { transform: translate(100px, -100px) rotate(720deg); opacity: 0; }
        }
        
        /* ============================================
           NAVBAR FLORALE
           ============================================ */
        .floral-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(253, 251, 247, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(232, 180, 168, 0.3);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .floral-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Great Vibes', cursive;
            font-size: 28px;
            color: var(--rose-deep);
        }
        
        /* ============================================
           SECTION HERO FLORAL
           ============================================ */
        .floral-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            overflow: hidden;
        }
        
        /* Couronne de fleurs SVG au-dessus */
        .floral-crown-svg {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            opacity: 0;
            animation: fadeIn 1s ease-out 3.2s forwards;
        }
        
        .floral-blason {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.4s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .floral-ornament {
            font-size: 28px;
            color: var(--rose);
            letter-spacing: 0.5em;
            margin-bottom: 24px;
        }
        
        .floral-label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .floral-guest {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 10vw, 88px);
            color: var(--rose-deep);
            line-height: 1;
            margin-bottom: 30px;
            filter: drop-shadow(0 4px 12px rgba(201, 138, 125, 0.2));
        }
        
        .floral-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .floral-separator .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--rose), transparent);
        }
        .floral-separator i {
            color: var(--sage-dark);
            font-size: 20px;
        }
        
        .floral-hosts-intro {
            font-style: italic;
            font-size: 22px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        
        .floral-host-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(56px, 12vw, 120px);
            background: linear-gradient(135deg, var(--rose-deep) 0%, var(--rose) 50%, var(--gold) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 0.95;
            animation: floralFlow 8s ease-in-out infinite;
            margin-bottom: 16px;
        }
        @keyframes floralFlow {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .floral-event-type {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 0.5em;
            color: var(--sage-dark);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* ============================================
           CARTE AQUARELLE (Détails)
           ============================================ */
        .floral-watercolor-card {
            max-width: 800px;
            margin: 60px auto;
            padding: 60px 50px;
            background: 
                radial-gradient(circle at 10% 10%, rgba(232, 180, 168, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(168, 191, 160, 0.15) 0%, transparent 40%),
                rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(232, 180, 168, 0.3);
            border-radius: 8px 40px 8px 40px;
            position: relative;
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
        }
        .floral-watercolor-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .floral-watercolor-card { padding: 40px 25px; margin: 40px 15px; }
        }
        
        /* Fleur coin supérieur */
        .floral-watercolor-card::before {
            content: '🌸';
            position: absolute;
            top: -20px;
            left: -20px;
            font-size: 60px;
            transform: rotate(-15deg);
        }
        .floral-watercolor-card::after {
            content: '🌿';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 60px;
            transform: rotate(15deg);
        }
        
        .floral-card-title {
            font-family: 'Great Vibes', cursive;
            font-size: 40px;
            color: var(--rose-deep);
            text-align: center;
            margin-bottom: 40px;
        }
        
        .floral-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 640px) {
            .floral-info-grid { grid-template-columns: 1fr; }
        }
        
        .floral-info-item {
            text-align: center;
            padding: 24px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .floral-info-item:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(201, 138, 125, 0.15);
        }
        
        .floral-info-item .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--rose), var(--sage));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(201, 138, 125, 0.25);
        }
        .floral-info-item .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .floral-info-item .value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.4;
        }
        
        /* Bouton itinéraire floral */
        .floral-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--rose), var(--rose-deep));
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(201, 138, 125, 0.3);
        }
        .floral-btn-itinerary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(201, 138, 125, 0.5);
            color: white;
        }
        
        /* ============================================
           SECTION PHOTOS DÉCHIRÉES
           ============================================ */
        .floral-photos-section {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        .floral-photos-title {
            font-family: 'Great Vibes', cursive;
            font-size: 48px;
            color: var(--rose-deep);
            text-align: center;
            margin-bottom: 40px;
        }
        
        .floral-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
        }
        
        .floral-photo-card {
            background: white;
            padding: 12px 12px 40px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            transform: rotate(-2deg);
            transition: all 0.4s ease;
            position: relative;
        }
        .floral-photo-card:nth-child(even) { transform: rotate(2deg); }
        .floral-photo-card:hover {
            transform: rotate(0) scale(1.05);
            box-shadow: 0 16px 40px rgba(201, 138, 125, 0.25);
            z-index: 5;
        }
        
        .floral-photo-card img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
        }
        .floral-photo-card .caption {
            position: absolute;
            bottom: 10px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Great Vibes', cursive;
            font-size: 22px;
            color: var(--rose-deep);
        }
        
        /* ============================================
           FORMULAIRES FLORAUX
           ============================================ */
        .floral-form-section {
            max-width: 700px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 40px 8px 40px 8px;
            box-shadow: 0 12px 40px rgba(201, 138, 125, 0.15);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
        }
        .floral-form-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .floral-form-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .floral-form-title {
            font-family: 'Great Vibes', cursive;
            font-size: 36px;
            color: var(--rose-deep);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .floral-form-group { margin-bottom: 22px; }
        .floral-form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .floral-form-group input,
        .floral-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(232, 180, 168, 0.3);
            border-radius: 999px;
            color: var(--text);
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            transition: all 0.3s ease;
        }
        .floral-form-group textarea { border-radius: 20px; }
        .floral-form-group input:focus,
        .floral-form-group textarea:focus {
            outline: none;
            border-color: var(--rose);
            background: white;
            box-shadow: 0 0 0 4px rgba(232, 180, 168, 0.2);
        }
        
        .floral-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 480px) {
            .floral-options-grid { grid-template-columns: 1fr; }
        }
        
        .floral-option-radio { display: none; }
        .floral-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 2px solid rgba(232, 180, 168, 0.4);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.6);
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .floral-option-label:hover {
            border-color: var(--rose);
            transform: translateY(-2px);
        }
        .floral-option-radio:checked + .floral-option-label {
            border-color: var(--rose-deep);
            background: rgba(232, 180, 168, 0.25);
            color: var(--rose-deep);
            box-shadow: 0 6px 20px rgba(201, 138, 125, 0.25);
        }
        
        .floral-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px 32px;
            background: linear-gradient(135deg, var(--rose-deep), var(--rose));
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(201, 138, 125, 0.35);
            margin-top: 10px;
        }
        .floral-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(201, 138, 125, 0.5);
        }
        
        /* Boissons */
        .floral-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .floral-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 2px solid rgba(232, 180, 168, 0.4);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .floral-boisson-item.selected {
            border-color: var(--rose-deep);
            background: rgba(232, 180, 168, 0.25);
            color: var(--rose-deep);
        }
        .floral-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .floral-boisson-item.selected .check { opacity: 1; }
        
        .floral-boisson-category { margin-bottom: 20px; }
        .floral-boisson-category-title {
            font-family: 'Great Vibes', cursive;
            font-size: 26px;
            color: var(--rose-deep);
            margin-bottom: 10px;
        }
        
        /* ============================================
           QR FLORAL
           ============================================ */
        .floral-qr-wrapper {
            text-align: center;
            padding: 20px;
        }
        .floral-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 40px 8px 40px 8px;
            box-shadow: 0 12px 40px rgba(201, 138, 125, 0.2);
            border: 2px solid rgba(232, 180, 168, 0.3);
        }
        
        /* ============================================
           FOOTER FLORAL
           ============================================ */
        .floral-footer {
            padding: 60px 40px 40px;
            text-align: center;
            background: linear-gradient(180deg, transparent 0%, rgba(232, 180, 168, 0.15) 100%);
            border-top: 1px solid rgba(232, 180, 168, 0.3);
        }
        .floral-footer-name {
            font-family: 'Great Vibes', cursive;
            font-size: 42px;
            color: var(--rose-deep);
            margin-bottom: 8px;
        }
        .floral-footer-tagline {
            font-style: italic;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        
        .floral-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 999px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
        }
        .floral-btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.5);
            color: white;
        }
        
        /* Alerts */
        .floral-alert {
            padding: 16px 24px;
            border-radius: 999px;
            margin-bottom: 20px;
            font-size: 15px;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .floral-alert-success { background: rgba(168, 191, 160, 0.2); color: var(--sage-dark); }
        .floral-alert-danger  { background: rgba(232, 180, 168, 0.3); color: var(--rose-deep); }
        .floral-alert-warning { background: rgba(212, 165, 116, 0.2); color: var(--gold); }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 14px 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--rose-deep), var(--rose));
            color: white;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(201, 138, 125, 0.4);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3.8s forwards;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.03); }
        @media (max-width: 480px) { #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 10px; } }
    </style>
</head>
<body>

    <!-- ============================================
         INTRO FLEURS QUI POUSSENT
         ============================================ -->
    <div class="growing-flowers">
        <div class="flower-growing">🌸</div>
        <div class="flower-growing">🌷</div>
        <div class="flower-growing">🌿</div>
        <div class="flower-growing">🌺</div>
        <div class="flower-growing">🌹</div>
    </div>
    
    <!-- Pétales qui volent -->
    <div class="flying-petals" id="petals"></div>

    <!-- ============================================
         NAVBAR
         ============================================ -->
    <nav class="floral-navbar">
        <div class="floral-brand">
            <i class="fas fa-seedling"></i>
            <?php echo htmlspecialchars($appName); ?>
        </div>
    </nav>

    <!-- ============================================
         HERO FLORAL
         ============================================ -->
    <section class="floral-hero">
        
        <!-- Couronne fleurs SVG -->
        <svg class="floral-crown-svg" viewBox="0 0 300 60" xmlns="http://www.w3.org/2000/svg">
            <path d="M 20 40 Q 60 20 100 30 Q 130 35 150 45" stroke="#a8bfa0" stroke-width="1.5" fill="none" opacity="0.6"/>
            <circle cx="40" cy="32" r="8" fill="#e8b4a8" opacity="0.85"/>
            <circle cx="40" cy="32" r="4" fill="#fdf6f3"/>
            <circle cx="70" cy="25" r="10" fill="#c98a7d" opacity="0.8"/>
            <circle cx="70" cy="25" r="5" fill="#fdf6f3"/>
            <circle cx="100" cy="30" r="7" fill="#e8b4a8" opacity="0.85"/>
            <circle cx="100" cy="30" r="3.5" fill="#fdf6f3"/>
            <ellipse cx="55" cy="38" rx="6" ry="3" fill="#a8bfa0" opacity="0.7" transform="rotate(-20 55 38)"/>
            <path d="M 280 40 Q 240 20 200 30 Q 170 35 150 45" stroke="#a8bfa0" stroke-width="1.5" fill="none" opacity="0.6"/>
            <circle cx="260" cy="32" r="8" fill="#e8b4a8" opacity="0.85"/>
            <circle cx="260" cy="32" r="4" fill="#fdf6f3"/>
            <circle cx="230" cy="25" r="10" fill="#c98a7d" opacity="0.8"/>
            <circle cx="230" cy="25" r="5" fill="#fdf6f3"/>
            <circle cx="200" cy="30" r="7" fill="#e8b4a8" opacity="0.85"/>
            <circle cx="200" cy="30" r="3.5" fill="#fdf6f3"/>
            <ellipse cx="245" cy="38" rx="6" ry="3" fill="#a8bfa0" opacity="0.7" transform="rotate(20 245 38)"/>
            <circle cx="150" cy="35" r="12" fill="#c98a7d" opacity="0.9"/>
            <circle cx="150" cy="35" r="7" fill="#e8b4a8"/>
            <circle cx="150" cy="35" r="3" fill="#fdf6f3"/>
        </svg>
        
        <div class="floral-blason">
            
            <div class="floral-ornament">✿ ❀ ✿</div>
            
            <div class="floral-label">Invitation personnelle</div>
            
            <div class="floral-guest"><?php echo htmlspecialchars($guestName); ?></div>
            
            <div class="floral-separator">
                <div class="line"></div>
                <i class="fas fa-leaf"></i>
                <i class="fas fa-spa"></i>
                <i class="fas fa-leaf"></i>
                <div class="line"></div>
            </div>
            
            <div class="floral-hosts-intro">Vous êtes invité(e) à célébrer</div>
            <div class="floral-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="floral-event-type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
            
        </div>
    </section>

    <!-- ============================================
         CARTE AQUARELLE (Détails)
         ============================================ -->
    <div class="floral-watercolor-card">
        
        <div class="floral-card-title">Les détails</div>
        
        <div class="floral-info-grid">
            
            <div class="floral-info-item">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="label">Date</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="floral-info-item">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">Heure</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="floral-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="label">Lieu</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <div style="font-size:14px;color:var(--text-muted);margin-top:6px;font-style:italic;">
                            <?php echo htmlspecialchars($adresseDisplay); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="floral-btn-itinerary">
                    <i class="fas fa-route"></i> Voir l'itinéraire
                </a>
            </div>
            
            <div class="floral-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-user-friends"></i></div>
                <div class="label">Places réservées</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="floral-form-section apparue" style="max-width:700px;padding:30px;">
            <div class="floral-alert floral-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="floral-photos-section">
            <div class="floral-photos-title">Souvenirs fleuris</div>
            <div class="floral-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="floral-photo-card">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                        <div class="caption">Souvenir n°<?php echo $index + 1; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="floral-form-section">
        <div class="floral-form-title">Votre code d'accès</div>
        <div class="floral-qr-wrapper">
            <div class="floral-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Inter',sans-serif;font-size:11px;letter-spacing:0.3em;color:var(--text-muted);margin-top:16px;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="floral-form-section">
            <div class="floral-form-title">Confirmez votre présence</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="floral-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="floral-form-group">
                    <label>Votre réponse</label>
                    <div class="floral-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="floral-option-radio">
                            <label for="presenceOui" class="floral-option-label">
                                <i class="fas fa-heart" style="color:var(--rose-deep);"></i> Je serai là
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="floral-option-radio">
                            <label for="presenceNon" class="floral-option-label">
                                <i class="fas fa-times" style="color:var(--text-muted);"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="floral-form-group">
                    <label>Un mot doux</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="floral-btn-submit">
                    <i class="fas fa-envelope"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="floral-form-section">
            <div class="floral-form-title">Vos boissons préférées</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--sage-dark);font-family:'Cormorant Garamond',serif;font-size:18px;padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos choix sont enregistrés
                </div>
                <div class="floral-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="floral-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check-circle check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-style:italic;font-size:17px;color:var(--text-muted);margin-bottom:20px;">
                        Choisissez jusqu'à <strong style="color:var(--rose-deep);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="floral-boisson-category">
                            <div class="floral-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>" style="color:var(--rose-deep);font-size:20px;margin-right:8px;"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="floral-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="floral-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="floral-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="floral-footer">
        <div class="floral-footer-name"><?php echo htmlspecialchars($appName); ?></div>
        <div class="floral-footer-tagline">Chaque événement mérite sa touche florale</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="floral-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(232, 180, 168, 0.3);font-size:11px;color:var(--text-muted);letter-spacing:0.15em;">
            🌸 © <?php echo date('Y'); ?> · Tous droits réservés
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // PÉTALES QUI VOLENT
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const petalsContainer = document.getElementById('petals');
            const petalEmojis = ['🌸', '🌺', '🌷', '❀', '✿'];
            
            for (let i = 0; i < 15; i++) {
                const petal = document.createElement('div');
                petal.className = 'petal';
                petal.textContent = petalEmojis[Math.floor(Math.random() * petalEmojis.length)];
                petal.style.left = Math.random() * 100 + '%';
                petal.style.animationDelay = Math.random() * 12 + 's';
                petal.style.animationDuration = (10 + Math.random() * 8) + 's';
                petal.style.fontSize = (16 + Math.random() * 20) + 'px';
                petalsContainer.appendChild(petal);
            }
        });

        // ================================================================
        // SCROLL ANIMATIONS
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.floral-watercolor-card, .floral-form-section');
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
                        width: 180, height: 180,
                        colorDark: '#3d3229', colorLight: '#fdf6f3',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DOWNLOAD
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.floral-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#f5efe6', logging: false
                });
                const link = document.createElement('a');
                link.download = `floral_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.floral-boisson-item.selected').forEach(item => {
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