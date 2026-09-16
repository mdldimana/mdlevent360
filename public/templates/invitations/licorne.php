<?php
/**
 * ============================================================
 * TEMPLATE : LICORNE / PRINCESSE
 * ============================================================
 * 
 * Design magique et féérique :
 * - Licorne au centre avec crinière arc-en-ciel
 * - Paillettes et étoiles qui tombent en continu
 * - Arc-en-ciel pastel qui s'anime
 * - Nuages roses flottants
 * - Typo cursive magique
 * - Couronne dorée
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
    
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Pacifico&family=Quicksand:wght@300;400;500;600;700&family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        /* ============================================
           VARIABLES LICORNE
           ============================================ */
        :root {
            --rose-pale: #ffe8f0;
            --rose-light: #ffd1e0;
            --rose-medium: #ff9ec1;
            --rose-deep: #ff6b9d;
            --rose-dark: #e84a85;
            --violet: #c89bff;
            --violet-light: #e0c3ff;
            --mint: #b8f0e4;
            --sky: #c5e5ff;
            --lemon: #fff4c5;
            --peach: #ffd5b8;
            --gold: #ffd966;
            --gold-dark: #e6b800;
            --text: #5a3a4a;
            --text-muted: #a87d95;
            --white: #fffdfd;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Nunito', system-ui, sans-serif;
            background: linear-gradient(180deg, 
                #ffe8f0 0%,
                #ffd1e0 30%,
                #e0c3ff 60%,
                #c5e5ff 100%);
            background-attachment: fixed;
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           PAILLETTES QUI TOMBENT
           ============================================ */
        .sparkles-container {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 5;
            overflow: hidden;
        }
        .sparkle {
            position: absolute;
            top: -20px;
            font-size: 16px;
            opacity: 0;
            animation: sparkleFall 8s linear infinite;
        }
        @keyframes sparkleFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.8; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        /* Nuages flottants */
        .cloud {
            position: fixed;
            background: white;
            border-radius: 100px;
            opacity: 0.6;
            pointer-events: none;
            z-index: 1;
            filter: blur(1px);
        }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: white;
            border-radius: 100px;
        }
        .cloud.c1 {
            top: 15%;
            left: -100px;
            width: 200px;
            height: 50px;
            animation: cloudFloat 30s linear infinite;
        }
        .cloud.c1::before { width: 90px; height: 90px; top: -40px; left: 25px; }
        .cloud.c1::after { width: 70px; height: 70px; top: -30px; right: 30px; }
        
        .cloud.c2 {
            top: 30%;
            right: -100px;
            width: 180px;
            height: 45px;
            animation: cloudFloatReverse 35s linear infinite;
        }
        .cloud.c2::before { width: 80px; height: 80px; top: -35px; left: 20px; }
        .cloud.c2::after { width: 60px; height: 60px; top: -25px; right: 25px; }
        
        .cloud.c3 {
            top: 65%;
            left: -100px;
            width: 220px;
            height: 55px;
            animation: cloudFloat 40s linear infinite;
            animation-delay: -10s;
        }
        .cloud.c3::before { width: 100px; height: 100px; top: -45px; left: 30px; }
        .cloud.c3::after { width: 75px; height: 75px; top: -35px; right: 35px; }
        
        @keyframes cloudFloat {
            0% { transform: translateX(-200px); }
            100% { transform: translateX(calc(100vw + 200px)); }
        }
        @keyframes cloudFloatReverse {
            0% { transform: translateX(200px); }
            100% { transform: translateX(calc(-100vw - 200px)); }
        }
        
        /* ============================================
           INTRO : ARC-EN-CIEL ANIMÉ
           ============================================ */
        .rainbow-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(180deg, #ffe8f0 0%, #ffd1e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introFadeOut 2.5s ease-in-out 2.2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .rainbow-arc {
            position: relative;
            width: 300px;
            height: 150px;
            overflow: hidden;
        }
        .rainbow-arc::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 20px solid;
            border-color: 
                #ff6b9d 0deg 30deg,
                #ffd966 30deg 60deg,
                #b8f0e4 60deg 90deg,
                #c5e5ff 90deg 120deg,
                #c89bff 120deg 150deg,
                #ff9ec1 150deg 180deg,
                #ff6b9d 180deg 210deg,
                #ffd966 210deg 240deg,
                #b8f0e4 240deg 270deg,
                #c5e5ff 270deg 300deg,
                #c89bff 300deg 330deg,
                #ff9ec1 330deg 360deg;
            border-top-color: transparent;
            border-right-color: transparent;
            border-left-color: transparent;
            border-bottom-width: 20px;
            clip-path: polygon(0 50%, 100% 50%, 100% 100%, 0 100%);
            animation: rainbowGrow 1.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            transform: translateX(-50%) scale(0);
            transform-origin: center bottom;
        }
        @keyframes rainbowGrow {
            0% { transform: translateX(-50%) scale(0); }
            100% { transform: translateX(-50%) scale(1); }
        }
        
        .unicorn-intro-icon {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 70px;
            animation: unicornBounce 1.5s ease-in-out infinite;
            filter: drop-shadow(0 8px 20px rgba(255, 107, 157, 0.5));
        }
        @keyframes unicornBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-15px); }
        }
        
        /* ============================================
           NAVBAR LICORNE
           ============================================ */
        .unicorn-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 253, 253, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 3px solid var(--rose-light);
            box-shadow: 0 4px 20px rgba(255, 107, 157, 0.15);
            opacity: 0;
            animation: fadeIn 1s ease-out 2.5s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .unicorn-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Pacifico', cursive;
            font-size: 24px;
            color: var(--rose-deep);
            text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.8);
        }
        
        .unicorn-status {
            font-family: 'Quicksand', sans-serif;
            font-size: 12px;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* ============================================
           HERO LICORNE
           ============================================ */
        .unicorn-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            z-index: 10;
        }
        
        /* Arc-en-ciel décoratif */
        .rainbow-decor {
            position: absolute;
            top: 8%;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 300px;
            opacity: 0.35;
            pointer-events: none;
            z-index: 0;
        }
        .rainbow-decor::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            border-radius: 50%;
            border: 30px solid;
            border-color: 
                #ff6b9d transparent transparent transparent,
                #ffd966 transparent transparent transparent,
                #b8f0e4 transparent transparent transparent,
                #c5e5ff transparent transparent transparent,
                #c89bff transparent transparent transparent;
            border-top-color: transparent;
            border-left-color: transparent;
            border-right-color: transparent;
            clip-path: polygon(0 50%, 100% 50%, 100% 100%, 0 100%);
        }
        @media (max-width: 768px) {
            .rainbow-decor { width: 350px; height: 175px; }
            .rainbow-decor::before { width: 350px; height: 350px; border-width: 20px; }
        }
        
        .unicorn-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 800px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.8s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Badge magique */
        .unicorn-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 26px;
            background: linear-gradient(135deg, var(--rose-light), var(--violet-light));
            border: 2px solid var(--rose-medium);
            border-radius: 999px;
            font-family: 'Quicksand', sans-serif;
            font-size: 13px;
            letter-spacing: 0.15em;
            color: var(--rose-dark);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 30px;
            box-shadow: 
                0 8px 24px rgba(255, 107, 157, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.6);
            animation: badgeFloat 3s ease-in-out infinite;
        }
        @keyframes badgeFloat {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-6px) rotate(2deg); }
        }
        .unicorn-badge i { color: var(--gold-dark); }
        
        /* Nom de l'invité */
        .unicorn-guest {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(52px, 10vw, 96px);
            line-height: 1;
            background: linear-gradient(135deg, 
                var(--rose-deep) 0%, 
                var(--violet) 50%,
                var(--sky) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            filter: drop-shadow(0 4px 20px rgba(255, 107, 157, 0.3));
            animation: gradientFlow 6s ease-in-out infinite;
            background-size: 200% auto;
        }
        @keyframes gradientFlow {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        /* Séparateur licorne */
        .unicorn-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .unicorn-divider .line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--rose-medium), transparent);
            border-radius: 2px;
        }
        .unicorn-divider .icon {
            font-size: 24px;
            animation: iconSpin 4s ease-in-out infinite;
        }
        @keyframes iconSpin {
            0%, 100% { transform: rotate(-10deg) scale(1); }
            50% { transform: rotate(10deg) scale(1.15); }
        }
        
        .unicorn-hosts-intro {
            font-family: 'Quicksand', sans-serif;
            font-size: 15px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        /* Nom de l'hôte */
        .unicorn-host-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(64px, 12vw, 120px);
            line-height: 0.95;
            background: linear-gradient(135deg, 
                var(--rose-deep) 0%, 
                var(--gold) 25%,
                var(--mint) 50%,
                var(--violet) 75%,
                var(--rose-deep) 100%);
            background-size: 300% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: rainbowFlow 8s linear infinite;
            filter: drop-shadow(0 6px 24px rgba(200, 155, 255, 0.3));
            margin-bottom: 20px;
        }
        @keyframes rainbowFlow {
            0% { background-position: 0% center; }
            100% { background-position: 300% center; }
        }
        
        .unicorn-event-type {
            font-family: 'Pacifico', cursive;
            font-size: 22px;
            letter-spacing: 0.05em;
            color: var(--rose-dark);
            text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.8);
        }
        
        /* ============================================
           CARTE MAGIQUE (Détails)
           ============================================ */
        .magic-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(255, 253, 253, 0.95);
            border-radius: 30px;
            border: 3px solid white;
            box-shadow: 
                0 20px 60px rgba(255, 107, 157, 0.2),
                0 0 0 8px rgba(255, 253, 253, 0.4),
                0 0 80px rgba(200, 155, 255, 0.2);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
            z-index: 10;
            overflow: hidden;
        }
        .magic-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: 30px;
            background: linear-gradient(45deg, 
                #ff6b9d, #ffd966, #b8f0e4, #c5e5ff, #c89bff, #ff9ec1, #ff6b9d);
            background-size: 400% 400%;
            z-index: -1;
            animation: cardBorderFlow 8s ease infinite;
        }
        @keyframes cardBorderFlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .magic-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .magic-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        .magic-card-title {
            font-family: 'Great Vibes', cursive;
            font-size: 44px;
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            color: var(--rose-deep);
            border-bottom: 2px dashed var(--rose-light);
            position: relative;
        }
        .magic-card-title::before,
        .magic-card-title::after {
            content: '✨';
            position: absolute;
            bottom: -16px;
            font-size: 20px;
        }
        .magic-card-title::before { left: 30%; }
        .magic-card-title::after { right: 30%; }
        
        .magic-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .magic-info-grid { grid-template-columns: 1fr; }
        }
        
        .magic-info-item {
            padding: 26px 20px;
            background: linear-gradient(135deg, var(--rose-pale), #fff);
            border-radius: 20px;
            border: 2px solid var(--rose-light);
            box-shadow: 
                0 6px 20px rgba(255, 107, 157, 0.1),
                inset 0 -3px 0 rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .magic-info-item::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .magic-info-item:hover::before {
            opacity: 1;
            animation: shimmer 1s ease;
        }
        @keyframes shimmer {
            0% { transform: translate(-50%, -50%) scale(0); }
            100% { transform: translate(-50%, -50%) scale(1); }
        }
        .magic-info-item:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 
                0 12px 32px rgba(255, 107, 157, 0.25),
                inset 0 -3px 0 rgba(255, 255, 255, 0.8);
            border-color: var(--rose-medium);
        }
        
        .magic-info-item .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--rose-medium), var(--violet));
            color: white;
            font-size: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.3);
            position: relative;
            z-index: 1;
        }
        .magic-info-item .label {
            font-family: 'Quicksand', sans-serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .magic-info-item .value {
            font-family: 'Quicksand', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }
        .magic-info-item .value .sub {
            display: block;
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 500;
        }
        
        /* Bouton itinéraire magique */
        .magic-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--rose-medium), var(--violet));
            color: white;
            font-family: 'Quicksand', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 999px;
            border: none;
            box-shadow: 
                0 8px 24px rgba(255, 107, 157, 0.4),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .magic-btn-itinerary:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 12px 32px rgba(255, 107, 157, 0.5),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        /* ============================================
           SECTIONS MAGIQUES
           ============================================ */
        .magic-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(255, 253, 253, 0.95);
            border-radius: 30px;
            border: 3px solid white;
            box-shadow: 
                0 20px 60px rgba(255, 107, 157, 0.15),
                0 0 0 8px rgba(255, 253, 253, 0.4);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
            z-index: 10;
            overflow: hidden;
        }
        .magic-section::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: 30px;
            background: linear-gradient(45deg, 
                #ff6b9d, #ffd966, #b8f0e4, #c5e5ff, #c89bff, #ff9ec1, #ff6b9d);
            background-size: 400% 400%;
            z-index: -1;
            animation: cardBorderFlow 8s ease infinite;
        }
        .magic-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .magic-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .magic-section-title {
            font-family: 'Pacifico', cursive;
            font-size: 32px;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            color: var(--rose-deep);
            border-bottom: 2px dashed var(--rose-light);
        }
        
        /* Formulaires */
        .magic-form-group { margin-bottom: 24px; }
        .magic-form-group label {
            display: block;
            font-family: 'Quicksand', sans-serif;
            font-size: 12px;
            letter-spacing: 0.2em;
            color: var(--rose-dark);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .magic-form-group input,
        .magic-form-group textarea {
            width: 100%;
            padding: 16px 22px;
            background: var(--rose-pale);
            border: 2px solid var(--rose-light);
            border-radius: 16px;
            color: var(--text);
            font-family: 'Quicksand', sans-serif;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .magic-form-group input:focus,
        .magic-form-group textarea:focus {
            outline: none;
            border-color: var(--rose-deep);
            background: white;
            box-shadow: 
                0 0 0 4px rgba(255, 158, 193, 0.2),
                0 8px 24px rgba(255, 107, 157, 0.15);
        }
        
        .magic-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .magic-options-grid { grid-template-columns: 1fr; }
        }
        
        .magic-option-radio { display: none; }
        .magic-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px solid var(--rose-light);
            border-radius: 16px;
            background: var(--rose-pale);
            font-family: 'Quicksand', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .magic-option-label:hover {
            border-color: var(--rose-medium);
            transform: translateY(-2px);
            color: var(--rose-dark);
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.15);
        }
        .magic-option-radio:checked + .magic-option-label {
            border-color: var(--rose-deep);
            background: linear-gradient(135deg, var(--rose-light), var(--violet-light));
            color: var(--rose-dark);
            box-shadow: 
                0 0 0 3px rgba(255, 158, 193, 0.3),
                0 8px 24px rgba(255, 107, 157, 0.2);
        }
        
        .magic-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, 
                var(--rose-deep) 0%, 
                var(--rose-medium) 50%,
                var(--violet) 100%);
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Quicksand', sans-serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 
                0 12px 32px rgba(255, 107, 157, 0.35),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            margin-top: 10px;
        }
        .magic-btn-submit:hover {
            background-position: 100% center;
            transform: translateY(-3px);
            box-shadow: 
                0 16px 40px rgba(255, 107, 157, 0.5),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }
        
        /* Photos avec cadre magique */
        .magic-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }
        .magic-photo {
            background: white;
            padding: 14px 14px 55px;
            border-radius: 16px;
            box-shadow: 
                0 10px 32px rgba(255, 107, 157, 0.2),
                0 0 0 6px var(--rose-pale),
                0 0 0 8px var(--rose-light);
            transform: rotate(-2deg);
            transition: all 0.4s ease;
            position: relative;
        }
        .magic-photo:nth-child(even) { transform: rotate(2.5deg); }
        .magic-photo:nth-child(3n) { transform: rotate(-1deg); }
        .magic-photo:hover {
            transform: rotate(0) scale(1.05);
            box-shadow: 
                0 16px 48px rgba(255, 107, 157, 0.35),
                0 0 0 6px var(--rose-pale),
                0 0 0 8px var(--rose-deep);
            z-index: 5;
        }
        .magic-photo img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 10px;
        }
        .magic-photo .caption {
            position: absolute;
            bottom: 14px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Great Vibes', cursive;
            font-size: 24px;
            color: var(--rose-deep);
        }
        
        /* Boissons */
        .magic-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .magic-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid var(--rose-light);
            border-radius: 999px;
            background: var(--rose-pale);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Quicksand', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .magic-boisson-item.selected {
            border-color: var(--rose-deep);
            background: linear-gradient(135deg, var(--rose-light), var(--violet-light));
            color: var(--rose-dark);
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.25);
        }
        .magic-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .magic-boisson-item.selected .check { opacity: 1; }
        
        .magic-boisson-category { margin-bottom: 20px; }
        .magic-boisson-category-title {
            font-family: 'Pacifico', cursive;
            font-size: 20px;
            color: var(--rose-deep);
            margin-bottom: 12px;
        }
        
        /* QR Code */
        .magic-qr-wrapper {
            text-align: center;
        }
        .magic-qr-box {
            display: inline-block;
            padding: 22px;
            background: white;
            border-radius: 24px;
            box-shadow: 
                0 12px 40px rgba(255, 107, 157, 0.25),
                0 0 0 8px var(--rose-pale),
                0 0 0 10px var(--rose-light);
            position: relative;
        }
        .magic-qr-box::before,
        .magic-qr-box::after {
            content: '✨';
            position: absolute;
            font-size: 28px;
            animation: sparkleSpin 3s ease-in-out infinite;
        }
        .magic-qr-box::before { top: -18px; left: -18px; }
        .magic-qr-box::after { bottom: -18px; right: -18px; animation-delay: 1.5s; }
        @keyframes sparkleSpin {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.3) rotate(180deg); opacity: 0.7; }
        }
        
        /* ============================================
           LICORNE SVG CENTRALE
           ============================================ */
        .unicorn-svg-decor {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }
        .unicorn-svg-decor svg {
            width: 140px;
            height: 140px;
            filter: drop-shadow(0 10px 30px rgba(255, 107, 157, 0.4));
            animation: unicornFloat 4s ease-in-out infinite;
        }
        @keyframes unicornFloat {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }
        
        /* ============================================
           FOOTER LICORNE
           ============================================ */
        .magic-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .magic-footer-brand {
            font-family: 'Pacifico', cursive;
            font-size: 36px;
            background: linear-gradient(135deg, 
                var(--rose-deep) 0%, 
                var(--violet) 50%,
                var(--sky) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% auto;
            animation: gradientFlow 6s ease-in-out infinite;
            margin-bottom: 10px;
        }
        .magic-footer-tagline {
            font-family: 'Great Vibes', cursive;
            font-size: 22px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .magic-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            font-family: 'Quicksand', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 999px;
            box-shadow: 
                0 12px 32px rgba(37, 211, 102, 0.35),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .magic-btn-whatsapp:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 16px 40px rgba(37, 211, 102, 0.5),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        /* Alertes */
        .magic-alert {
            padding: 18px 26px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Quicksand', sans-serif;
            font-size: 15px;
            font-weight: 600;
            border-radius: 16px;
        }
        .magic-alert-success { 
            background: linear-gradient(135deg, var(--mint), #d7f5eb); 
            color: #2d7a45; 
            border: 2px solid var(--mint);
        }
        .magic-alert-danger { 
            background: linear-gradient(135deg, #ffd1dc, #ffe8f0); 
            color: #a01b3d; 
            border: 2px solid var(--rose-medium);
        }
        .magic-alert-warning { 
            background: linear-gradient(135deg, var(--lemon), #fffae6); 
            color: #806a00; 
            border: 2px solid var(--lemon);
        }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--rose-deep), var(--violet));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Quicksand', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 
                0 12px 32px rgba(255, 107, 157, 0.4),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            background-position: 100% center;
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 16px 40px rgba(255, 107, 157, 0.6),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 20px; font-size: 11px; }
        }
    </style>
</head>
<body>

    <!-- ============================================
         INTRO : ARC-EN-CIEL QUI POUSSE
         ============================================ -->
    <div class="rainbow-intro">
        <div class="rainbow-arc">
            <div class="unicorn-intro-icon">🦄</div>
        </div>
    </div>

    <!-- ============================================
         PAILLETTES QUI TOMBENT
         ============================================ -->
    <div class="sparkles-container" id="sparklesContainer"></div>

    <!-- ============================================
         NUAGES FLOTTANTS
         ============================================ -->
    <div class="cloud c1"></div>
    <div class="cloud c2"></div>
    <div class="cloud c3"></div>

    <!-- ============================================
         NAVBAR LICORNE
         ============================================ -->
    <nav class="unicorn-navbar">
        <div class="unicorn-brand">
            🦄 <?php echo htmlspecialchars($appName); ?>
        </div>
        <div class="unicorn-status">
            ✨ INVITATION MAGIQUE ✨
        </div>
    </nav>

    <!-- ============================================
         HERO LICORNE
         ============================================ -->
    <section class="unicorn-hero">
        
        <!-- Arc-en-ciel déco -->
        <div class="rainbow-decor"></div>
        
        <div class="unicorn-blason">
            
            <div class="unicorn-badge">
                <i class="fas fa-crown"></i>
                INVITATION ROYALE
                <i class="fas fa-crown"></i>
            </div>
            
            <div class="unicorn-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="unicorn-divider">
                <div class="line"></div>
                <span class="icon">🦄</span>
                <div class="line"></div>
            </div>
            
            <div class="unicorn-hosts-intro">
                ✨ Vous êtes invité(e) à célébrer ✨
            </div>
            <div class="unicorn-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="unicorn-event-type">
                🎀 <?php echo htmlspecialchars($eventType); ?> 🎀
            </div>
            
        </div>
    </section>

    <!-- ============================================
         LICORNE SVG DÉCORATIVE
         ============================================ -->
    <div class="unicorn-svg-decor">
        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="maneGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#ff6b9d"/>
                    <stop offset="25%" style="stop-color:#ffd966"/>
                    <stop offset="50%" style="stop-color:#b8f0e4"/>
                    <stop offset="75%" style="stop-color:#c5e5ff"/>
                    <stop offset="100%" style="stop-color:#c89bff"/>
                </linearGradient>
            </defs>
            
            <!-- Corps -->
            <ellipse cx="100" cy="130" rx="50" ry="35" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            
            <!-- Pattes -->
            <rect x="65" y="150" width="10" height="35" rx="4" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            <rect x="85" y="150" width="10" height="35" rx="4" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            <rect x="105" y="150" width="10" height="35" rx="4" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            <rect x="125" y="150" width="10" height="35" rx="4" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            
            <!-- Tête -->
            <ellipse cx="150" cy="100" rx="25" ry="22" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            
            <!-- Oreille -->
            <path d="M 140 82 L 135 65 L 148 78 Z" fill="#fff5fa" stroke="#ff9ec1" stroke-width="2"/>
            
            <!-- Corne arc-en-ciel -->
            <path d="M 155 78 L 165 50 L 175 78 Z" fill="url(#maneGrad)"/>
            <circle cx="165" cy="50" r="3" fill="#ffd966"/>
            
            <!-- Œil -->
            <circle cx="160" cy="98" r="4" fill="#5a3a4a"/>
            <circle cx="161" cy="97" r="1.5" fill="white"/>
            
            <!-- Joue rose -->
            <circle cx="170" cy="108" r="5" fill="#ffb8d4" opacity="0.7"/>
            
            <!-- Crinière arc-en-ciel -->
            <path d="M 135 85 Q 110 75 100 90 Q 115 95 125 100 Q 105 105 100 120 Q 120 120 130 115 Q 115 130 120 145 Q 135 135 140 125" 
                  fill="url(#maneGrad)" opacity="0.9"/>
            
            <!-- Queue arc-en-ciel -->
            <path d="M 55 125 Q 30 120 25 145 Q 45 140 50 155 Q 40 160 55 165 Q 65 150 60 140 Z" 
                  fill="url(#maneGrad)" opacity="0.9"/>
            
            <!-- Étoiles décoratives -->
            <text x="30" y="60" font-size="20" fill="#ffd966">✨</text>
            <text x="170" y="40" font-size="16" fill="#ff9ec1">✨</text>
            <text x="40" y="170" font-size="18" fill="#c89bff">✨</text>
        </svg>
    </div>

    <!-- ============================================
         CARTE MAGIQUE (Détails)
         ============================================ -->
    <div class="magic-card">
        
        <div class="magic-card-title">Les détails magiques</div>
        
        <div class="magic-info-grid">
            
            <div class="magic-info-item">
                <div class="icon"><i class="fas fa-calendar-heart"></i></div>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="magic-info-item">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="magic-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="label">LIEU ENCHANTÉ</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="magic-btn-itinerary">
                    <i class="fas fa-route"></i> Voir l'itinéraire
                </a>
            </div>
            
            <div class="magic-info-item" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="label">PLACES RÉSERVÉES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="magic-section apparue">
            <div class="magic-alert magic-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="magic-section">
            <div class="magic-section-title">Souvenirs féériques</div>
            <div class="magic-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="magic-photo">
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
    <div class="magic-section">
        <div class="magic-section-title">Ton code magique</div>
        <div class="magic-qr-wrapper">
            <div class="magic-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Quicksand',sans-serif;font-size:14px;color:var(--text-muted);letter-spacing:0.2em;margin-top:24px;font-weight:700;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="magic-section">
            <div class="magic-section-title">Dis oui ! 🦄</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="magic-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="magic-form-group">
                    <label>Ta réponse magique</label>
                    <div class="magic-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="magic-option-radio">
                            <label for="presenceOui" class="magic-option-label">
                                <i class="fas fa-heart"></i> J'y serai !
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="magic-option-radio">
                            <label for="presenceNon" class="magic-option-label">
                                <i class="fas fa-times"></i> Pas dispo
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="magic-form-group">
                    <label>Un petit mot magique...</label>
                    <textarea name="message_invite" rows="3" placeholder="Ton message..."></textarea>
                </div>
                
                <button type="submit" class="magic-btn-submit">
                    <i class="fas fa-paper-plane"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="magic-section">
            <div class="magic-section-title">Tes boissons préférées</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--rose-dark);font-family:'Quicksand',sans-serif;font-size:15px;font-weight:700;padding:20px 0;">
                    <i class="fas fa-lock"></i> Tes choix sont enregistrés ✨
                </div>
                <div class="magic-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="magic-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Quicksand',sans-serif;font-size:14px;color:var(--text-muted);margin-bottom:24px;font-weight:600;">
                        Choisis <strong style="color:var(--rose-deep);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="magic-boisson-category">
                            <div class="magic-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="magic-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="magic-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="magic-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER LICORNE
         ============================================ -->
    <footer class="magic-footer">
        <div class="magic-footer-brand">🦄 <?php echo htmlspecialchars($appName); ?></div>
        <div class="magic-footer-tagline">Chaque événement mérite sa magie</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="magic-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px dashed var(--rose-light);font-family:'Quicksand',sans-serif;font-size:12px;color:var(--text-muted);letter-spacing:0.2em;font-weight:600;">
            ✨ © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS ✨
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // PAILLETTES QUI TOMBENT
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('sparklesContainer');
            const sparkles = ['✨', '⭐', '💫', '🌟', '💖', '🦄'];
            
            for (let i = 0; i < 25; i++) {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle';
                sparkle.textContent = sparkles[Math.floor(Math.random() * sparkles.length)];
                sparkle.style.left = Math.random() * 100 + '%';
                sparkle.style.animationDelay = (Math.random() * 8) + 's';
                sparkle.style.animationDuration = (6 + Math.random() * 6) + 's';
                sparkle.style.fontSize = (12 + Math.random() * 20) + 'px';
                container.appendChild(sparkle);
            }
        });

        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.magic-card, .magic-section');
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

        // QR
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180,
                        height: 180,
                        colorDark: '#5a3a4a',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // Download
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.unicorn-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5,
                    useCORS: true,
                    backgroundColor: '#ffe8f0',
                    logging: false
                });
                const link = document.createElement('a');
                link.download = `licorne_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.magic-boisson-item.selected').forEach(item => {
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