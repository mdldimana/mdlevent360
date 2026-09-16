<?php
/**
 * ============================================================
 * TEMPLATE : ANNÉES 80
 * ============================================================
 * 
 * Design synthwave / VHS / arcade / néon des années 80 :
 * - Grille 3D rétro-futuriste qui défile
 * - Effet VHS (scanlines, tracking, glitch)
 * - Néons qui pulsent (rose, cyan, violet)
 * - Typo chunky rétro
 * - Soleil synthwave derrière les montagnes
 * - Cassette audio animée
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
    
    <link href="https://fonts.googleapis.com/css2?family=Monoton&family=Audiowide&family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --neon-pink: #ff2d95;
            --neon-cyan: #00f0ff;
            --neon-purple: #9d4edd;
            --neon-yellow: #ffe600;
            --neon-orange: #ff6b00;
            --bg-deep: #0a0420;
            --bg-dark: #140b2e;
            --bg-card: rgba(20, 11, 46, 0.75);
            --text: #ffffff;
            --text-muted: #b8b0d4;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Orbitron', system-ui, sans-serif;
            background: var(--bg-deep);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           FOND SYNTHWAVE (grid + soleil)
           ============================================ */
        .retro-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(180deg, 
                #0a0420 0%, 
                #1a0a3a 30%,
                #2a1050 55%,
                #4a0e2a 75%,
                #6b1050 100%);
        }
        
        /* Étoiles */
        .retro-stars {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(1px 1px at 10% 15%, white, transparent),
                radial-gradient(1px 1px at 25% 8%, white, transparent),
                radial-gradient(2px 2px at 40% 20%, white, transparent),
                radial-gradient(1px 1px at 60% 12%, white, transparent),
                radial-gradient(1.5px 1.5px at 75% 25%, white, transparent),
                radial-gradient(1px 1px at 88% 18%, white, transparent),
                radial-gradient(1px 1px at 15% 35%, white, transparent),
                radial-gradient(2px 2px at 92% 40%, white, transparent),
                radial-gradient(1px 1px at 5% 50%, white, transparent),
                radial-gradient(1.5px 1.5px at 50% 30%, white, transparent);
            animation: twinkle 4s ease-in-out infinite alternate;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        /* Soleil synthwave */
        .retro-sun {
            position: absolute;
            bottom: 45%;
            left: 50%;
            transform: translateX(-50%);
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: linear-gradient(180deg, 
                var(--neon-yellow) 0%, 
                var(--neon-orange) 30%,
                var(--neon-pink) 60%,
                var(--neon-purple) 100%);
            box-shadow: 
                0 0 80px rgba(255, 45, 149, 0.6),
                0 0 160px rgba(255, 107, 0, 0.4);
            opacity: 0.55;
            filter: blur(2px);
        }
        /* Rayures horizontales du soleil */
        .retro-sun::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: repeating-linear-gradient(
                0deg,
                transparent 0px,
                transparent 8px,
                var(--bg-deep) 8px,
                var(--bg-deep) 14px
            );
            mask-image: linear-gradient(180deg, transparent 0%, black 40%, black 100%);
            -webkit-mask-image: linear-gradient(180deg, transparent 0%, black 40%, black 100%);
        }
        
        /* Grille 3D au sol */
        .retro-grid {
            position: absolute;
            bottom: 0;
            left: -50%;
            right: -50%;
            height: 50%;
            background-image: 
                linear-gradient(90deg, var(--neon-cyan) 1px, transparent 1px),
                linear-gradient(0deg, var(--neon-pink) 1px, transparent 1px);
            background-size: 60px 60px;
            transform: perspective(400px) rotateX(75deg);
            transform-origin: bottom;
            animation: gridMove 3s linear infinite;
            opacity: 0.6;
            box-shadow: 0 0 80px rgba(0, 240, 255, 0.3) inset;
        }
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 0 60px; }
        }
        
        /* Bande de lumière horizontale (horizon) */
        .retro-horizon {
            position: absolute;
            bottom: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                var(--neon-cyan) 30%,
                white 50%,
                var(--neon-cyan) 70%,
                transparent 100%);
            box-shadow: 
                0 0 30px var(--neon-cyan),
                0 0 60px var(--neon-cyan);
        }
        
        /* ============================================
           EFFET VHS (scanlines + tracking)
           ============================================ */
        .vhs-overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 100;
        }
        .vhs-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                rgba(0, 0, 0, 0.15) 0px,
                rgba(0, 0, 0, 0.15) 1px,
                transparent 1px,
                transparent 3px
            );
            animation: scanlineMove 8s linear infinite;
        }
        @keyframes scanlineMove {
            0% { background-position: 0 0; }
            100% { background-position: 0 100px; }
        }
        
        /* Bande VHS qui descend */
        .vhs-overlay::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(180deg, 
                transparent 0%,
                rgba(255, 255, 255, 0.05) 40%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0.05) 60%,
                transparent 100%);
            animation: vhsTracking 6s linear infinite;
        }
        @keyframes vhsTracking {
            0% { top: -100px; }
            100% { top: 100%; }
        }
        
        /* Barres de couleur VHS en haut */
        .vhs-bars {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            display: flex;
            z-index: 101;
            pointer-events: none;
        }
        .vhs-bars span {
            flex: 1;
        }
        .vhs-bars span:nth-child(1) { background: #ffe600; }
        .vhs-bars span:nth-child(2) { background: #00f0ff; }
        .vhs-bars span:nth-child(3) { background: #00ff00; }
        .vhs-bars span:nth-child(4) { background: #ff00ff; }
        .vhs-bars span:nth-child(5) { background: #ff0000; }
        .vhs-bars span:nth-child(6) { background: #0000ff; }
        .vhs-bars span:nth-child(7) { background: #000000; }
        
        /* ============================================
           NAVBAR RÉTRO
           ============================================ */
        .retro-navbar {
            position: fixed;
            top: 8px;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10, 4, 32, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 240, 255, 0.3);
            opacity: 0;
            animation: fadeIn 1s ease-out 2.5s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .retro-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Monoton', cursive;
            font-size: 22px;
            letter-spacing: 0.15em;
            color: var(--neon-pink);
            text-shadow: 
                0 0 10px var(--neon-pink),
                0 0 20px var(--neon-pink),
                0 0 40px var(--neon-pink);
        }
        
        .retro-status {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--neon-cyan);
            text-shadow: 0 0 10px var(--neon-cyan);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .retro-status .rec-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff0000;
            box-shadow: 0 0 10px #ff0000;
            animation: recBlink 1s ease-in-out infinite;
        }
        @keyframes recBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        /* ============================================
           HERO RÉTRO
           ============================================ */
        .retro-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            z-index: 1;
        }
        
        .retro-blason {
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
        
        /* Bandeau année 80 */
        .retro-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            border: 2px solid var(--neon-cyan);
            background: rgba(0, 240, 255, 0.08);
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            margin-bottom: 30px;
            box-shadow: 
                0 0 20px rgba(0, 240, 255, 0.4),
                inset 0 0 20px rgba(0, 240, 255, 0.1);
            animation: tagPulse 2s ease-in-out infinite;
        }
        @keyframes tagPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 240, 255, 0.4), inset 0 0 20px rgba(0, 240, 255, 0.1); }
            50% { box-shadow: 0 0 40px rgba(0, 240, 255, 0.8), inset 0 0 30px rgba(0, 240, 255, 0.2); }
        }
        
        /* Invité avec effet néon */
        .retro-guest {
            font-family: 'Audiowide', sans-serif;
            font-size: clamp(28px, 5vw, 46px);
            font-weight: 400;
            letter-spacing: 0.08em;
            margin-bottom: 30px;
            color: var(--text);
            text-shadow: 
                0 0 10px var(--neon-pink),
                0 0 20px var(--neon-pink),
                0 0 40px var(--neon-pink),
                0 0 60px var(--neon-pink);
            animation: neonFlicker 3s infinite;
        }
        @keyframes neonFlicker {
            0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% {
                text-shadow: 
                    0 0 10px var(--neon-pink),
                    0 0 20px var(--neon-pink),
                    0 0 40px var(--neon-pink),
                    0 0 60px var(--neon-pink);
            }
            20%, 24%, 55% {
                text-shadow: none;
            }
        }
        
        /* Séparateur néon */
        .retro-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        .retro-divider .line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-cyan), transparent);
            box-shadow: 0 0 15px var(--neon-cyan);
        }
        .retro-divider .star {
            font-size: 24px;
            color: var(--neon-yellow);
            text-shadow: 0 0 20px var(--neon-yellow);
        }
        
        .retro-hosts-intro {
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        /* Nom de l'hôte avec dégradé rétro */
        .retro-host-name {
            font-family: 'Monoton', cursive;
            font-size: clamp(48px, 10vw, 100px);
            line-height: 1;
            background: linear-gradient(90deg, 
                var(--neon-pink) 0%, 
                var(--neon-yellow) 25%,
                var(--neon-cyan) 50%,
                var(--neon-purple) 75%,
                var(--neon-pink) 100%);
            background-size: 300% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: retroFlow 6s linear infinite;
            letter-spacing: 0.03em;
            margin-bottom: 16px;
            filter: drop-shadow(0 0 25px rgba(255, 45, 149, 0.5));
        }
        @keyframes retroFlow {
            0% { background-position: 0% center; }
            100% { background-position: 300% center; }
        }
        
        .retro-event-type {
            font-family: 'Audiowide', sans-serif;
            font-size: 14px;
            letter-spacing: 0.5em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            text-shadow: 0 0 15px var(--neon-cyan);
        }
        
        /* ============================================
           CARTE RÉTRO (Détails)
           ============================================ */
        .retro-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 2px solid var(--neon-cyan);
            box-shadow: 
                0 0 30px rgba(0, 240, 255, 0.4),
                inset 0 0 40px rgba(0, 240, 255, 0.05),
                0 20px 60px rgba(0, 0, 0, 0.6);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
            z-index: 1;
        }
        .retro-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .retro-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        /* Coins néon */
        .retro-card::before,
        .retro-card::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border: 3px solid var(--neon-pink);
            box-shadow: 0 0 20px var(--neon-pink);
        }
        .retro-card::before {
            top: -3px; left: -3px;
            border-right: none; border-bottom: none;
        }
        .retro-card::after {
            bottom: -3px; right: -3px;
            border-left: none; border-top: none;
        }
        
        .retro-card-title {
            font-family: 'Audiowide', sans-serif;
            font-size: 22px;
            letter-spacing: 0.15em;
            color: var(--neon-pink);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 2px dashed rgba(255, 45, 149, 0.3);
            text-shadow: 0 0 15px var(--neon-pink);
            text-transform: uppercase;
        }
        
        .retro-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .retro-info-grid { grid-template-columns: 1fr; }
        }
        
        .retro-info-item {
            padding: 24px;
            background: rgba(10, 4, 32, 0.6);
            border: 1px solid rgba(0, 240, 255, 0.3);
            border-left: 3px solid var(--neon-cyan);
            box-shadow: 
                0 0 15px rgba(0, 240, 255, 0.15),
                inset 0 0 20px rgba(0, 240, 255, 0.03);
            transition: all 0.3s ease;
            text-align: center;
        }
        .retro-info-item:hover {
            background: rgba(0, 240, 255, 0.08);
            border-left-color: var(--neon-pink);
            transform: translateY(-4px);
            box-shadow: 
                0 0 30px rgba(255, 45, 149, 0.3),
                inset 0 0 20px rgba(255, 45, 149, 0.05);
        }
        
        .retro-info-item .icon {
            font-size: 32px;
            color: var(--neon-yellow);
            text-shadow: 0 0 20px var(--neon-yellow);
            margin-bottom: 12px;
            display: block;
        }
        
        .retro-info-item .label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 0.35em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 0 8px var(--neon-cyan);
        }
        .retro-info-item .value {
            font-family: 'Audiowide', sans-serif;
            font-size: 20px;
            font-weight: 400;
            color: var(--text);
            line-height: 1.4;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        .retro-info-item .value .sub {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        
        /* Bouton itinéraire rétro */
        .retro-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 28px;
            background: transparent;
            border: 2px solid var(--neon-yellow);
            color: var(--neon-yellow);
            font-family: 'Audiowide', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 
                0 0 20px rgba(255, 230, 0, 0.3),
                inset 0 0 20px rgba(255, 230, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        .retro-btn-itinerary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--neon-yellow);
            transform: translateX(-100%);
            transition: transform 0.4s ease;
            z-index: -1;
        }
        .retro-btn-itinerary:hover {
            color: var(--bg-deep);
            box-shadow: 
                0 0 40px var(--neon-yellow),
                inset 0 0 30px rgba(255, 230, 0, 0.3);
        }
        .retro-btn-itinerary:hover::before {
            transform: translateX(0);
        }
        
        /* ============================================
           SECTIONS RÉTRO
           ============================================ */
        .retro-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 2px solid var(--neon-purple);
            box-shadow: 
                0 0 30px rgba(157, 78, 221, 0.4),
                inset 0 0 40px rgba(157, 78, 221, 0.05);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
            z-index: 1;
        }
        .retro-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .retro-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .retro-section-title {
            font-family: 'Audiowide', sans-serif;
            font-size: 24px;
            letter-spacing: 0.15em;
            color: var(--neon-purple);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(157, 78, 221, 0.3);
            text-shadow: 0 0 20px var(--neon-purple);
            text-transform: uppercase;
        }
        
        /* Formulaires */
        .retro-form-group { margin-bottom: 24px; }
        .retro-form-group label {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 8px var(--neon-cyan);
        }
        .retro-form-group input,
        .retro-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(10, 4, 32, 0.8);
            border: 2px solid rgba(0, 240, 255, 0.4);
            color: var(--text);
            font-family: 'Orbitron', sans-serif;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .retro-form-group input:focus,
        .retro-form-group textarea:focus {
            outline: none;
            border-color: var(--neon-pink);
            box-shadow: 
                0 0 0 2px rgba(255, 45, 149, 0.3),
                0 0 30px rgba(255, 45, 149, 0.4);
            background: rgba(255, 45, 149, 0.05);
        }
        
        .retro-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .retro-options-grid { grid-template-columns: 1fr; }
        }
        
        .retro-option-radio { display: none; }
        .retro-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px solid rgba(0, 240, 255, 0.4);
            background: rgba(10, 4, 32, 0.6);
            font-family: 'Audiowide', sans-serif;
            font-size: 14px;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .retro-option-label:hover {
            border-color: var(--neon-cyan);
            color: var(--neon-cyan);
            box-shadow: 0 0 25px rgba(0, 240, 255, 0.3);
        }
        .retro-option-radio:checked + .retro-option-label {
            border-color: var(--neon-pink);
            background: rgba(255, 45, 149, 0.1);
            color: var(--neon-pink);
            box-shadow: 
                0 0 0 2px rgba(255, 45, 149, 0.3),
                0 0 40px rgba(255, 45, 149, 0.4);
            text-shadow: 0 0 10px var(--neon-pink);
        }
        
        .retro-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            border: none;
            font-family: 'Audiowide', sans-serif;
            font-size: 15px;
            font-weight: 400;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 0 30px rgba(255, 45, 149, 0.5),
                0 0 60px rgba(157, 78, 221, 0.3);
            margin-top: 10px;
        }
        .retro-btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 0 50px rgba(255, 45, 149, 0.7),
                0 0 100px rgba(157, 78, 221, 0.5);
            text-shadow: 0 0 15px white;
        }
        
        /* Photos style Polaroid rétro */
        .retro-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }
        .retro-photo {
            background: white;
            padding: 12px 12px 50px;
            box-shadow: 
                0 0 20px rgba(0, 240, 255, 0.3),
                0 8px 24px rgba(0, 0, 0, 0.5);
            transform: rotate(-2deg);
            transition: all 0.4s ease;
            position: relative;
        }
        .retro-photo:nth-child(even) { transform: rotate(2.5deg); }
        .retro-photo:nth-child(3n) { transform: rotate(-1deg); }
        .retro-photo:hover {
            transform: rotate(0) scale(1.05);
            box-shadow: 
                0 0 40px var(--neon-pink),
                0 12px 32px rgba(0, 0, 0, 0.6);
            z-index: 5;
        }
        .retro-photo img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            filter: saturate(1.3) contrast(1.1) sepia(0.1) hue-rotate(-10deg);
        }
        .retro-photo .caption {
            position: absolute;
            bottom: 12px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Monoton', cursive;
            font-size: 18px;
            color: var(--bg-deep);
            letter-spacing: 0.05em;
        }
        
        /* Boissons */
        .retro-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .retro-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid rgba(0, 240, 255, 0.4);
            background: rgba(10, 4, 32, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
        .retro-boisson-item.selected {
            border-color: var(--neon-pink);
            background: rgba(255, 45, 149, 0.1);
            color: var(--neon-pink);
            box-shadow: 0 0 25px rgba(255, 45, 149, 0.4);
            text-shadow: 0 0 10px var(--neon-pink);
        }
        .retro-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .retro-boisson-item.selected .check { opacity: 1; }
        
        .retro-boisson-category { margin-bottom: 20px; }
        .retro-boisson-category-title {
            font-family: 'Audiowide', sans-serif;
            font-size: 14px;
            letter-spacing: 0.2em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            margin-bottom: 12px;
            text-shadow: 0 0 10px var(--neon-cyan);
        }
        
        /* QR Code */
        .retro-qr-wrapper {
            text-align: center;
        }
        .retro-qr-box {
            display: inline-block;
            padding: 20px;
            background: var(--text);
            border: 3px solid var(--neon-pink);
            box-shadow: 
                0 0 0 8px rgba(10, 4, 32, 0.5),
                0 0 0 10px var(--neon-cyan),
                0 0 50px var(--neon-pink);
            position: relative;
        }
        .retro-qr-box::before,
        .retro-qr-box::after {
            content: '★';
            position: absolute;
            color: var(--neon-yellow);
            font-size: 28px;
            text-shadow: 0 0 15px var(--neon-yellow);
            animation: starTwinkle 2s ease-in-out infinite;
        }
        @keyframes starTwinkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.2) rotate(20deg); opacity: 0.6; }
        }
        .retro-qr-box::before { top: -14px; left: -14px; }
        .retro-qr-box::after { bottom: -14px; right: -14px; }
        
        /* ============================================
           CASSETTE AUDIO ANIMÉE
           ============================================ */
        .cassette-decor {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }
        .cassette {
            position: relative;
            width: 200px;
            height: 130px;
            background: linear-gradient(180deg, #2a1550 0%, #1a0a3a 100%);
            border-radius: 8px;
            border: 3px solid var(--neon-cyan);
            box-shadow: 
                0 0 30px rgba(0, 240, 255, 0.5),
                inset 0 0 30px rgba(0, 240, 255, 0.1);
            padding: 15px;
        }
        .cassette::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 20px;
            right: 20px;
            height: 40px;
            background: linear-gradient(180deg, var(--neon-pink) 0%, #5a0040 100%);
            border-radius: 4px;
            border: 2px solid var(--neon-pink);
            box-shadow: 0 0 15px var(--neon-pink);
        }
        .cassette-reels {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 60px;
        }
        .cassette-reel {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: radial-gradient(circle, #0a0420 0%, #1a0a3a 40%, var(--neon-cyan) 45%, var(--neon-cyan) 50%, #0a0420 55%);
            border: 2px solid var(--neon-cyan);
            box-shadow: 0 0 15px var(--neon-cyan);
            animation: reelSpin 3s linear infinite;
        }
        @keyframes reelSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============================================
           FOOTER RÉTRO
           ============================================ */
        .retro-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .retro-footer-brand {
            font-family: 'Monoton', cursive;
            font-size: 42px;
            letter-spacing: 0.05em;
            color: var(--neon-pink);
            text-shadow: 
                0 0 10px var(--neon-pink),
                0 0 30px var(--neon-pink),
                0 0 60px var(--neon-pink);
            margin-bottom: 8px;
        }
        .retro-footer-tagline {
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            color: var(--neon-cyan);
            text-transform: uppercase;
            margin-bottom: 30px;
            text-shadow: 0 0 10px var(--neon-cyan);
        }
        
        .retro-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            background: transparent;
            border: 2px solid #25d366;
            color: #25d366;
            font-family: 'Audiowide', sans-serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(37, 211, 102, 0.3);
        }
        .retro-btn-whatsapp:hover {
            background: #25d366;
            color: var(--bg-deep);
            box-shadow: 
                0 0 40px rgba(37, 211, 102, 0.6),
                inset 0 0 20px rgba(37, 211, 102, 0.2);
        }
        
        /* Alerts */
        .retro-alert {
            padding: 18px 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            border: 2px solid;
            letter-spacing: 0.05em;
        }
        .retro-alert-success { border-color: var(--neon-cyan); background: rgba(0, 240, 255, 0.1); color: var(--neon-cyan); }
        .retro-alert-danger  { border-color: #ff3355; background: rgba(255, 51, 85, 0.1); color: #ff6688; }
        .retro-alert-warning { border-color: var(--neon-yellow); background: rgba(255, 230, 0, 0.1); color: var(--neon-yellow); }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 16px 28px;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            border: 2px solid var(--neon-cyan);
            font-family: 'Audiowide', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 
                0 0 30px rgba(255, 45, 149, 0.5),
                0 0 60px rgba(0, 240, 255, 0.3);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 0 50px rgba(255, 45, 149, 0.8),
                0 0 100px rgba(0, 240, 255, 0.5);
        }
        @media (max-width: 480px) {
            #downloadBtn {
                bottom: 12px;
                right: 12px;
                padding: 12px 20px;
                font-size: 10px;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .retro-sun { width: 260px; height: 260px; }
            .retro-grid { background-size: 40px 40px; }
            .retro-navbar { padding: 12px 20px; }
            .retro-brand { font-size: 16px; }
        }
        
        @media (max-width: 480px) {
            .retro-sun { width: 200px; height: 200px; }
            .retro-footer-brand { font-size: 32px; }
        }
    </style>
</head>
<body>

    <!-- ============================================
         FOND SYNTHWAVE
         ============================================ -->
    <div class="retro-bg">
        <div class="retro-stars"></div>
        <div class="retro-sun"></div>
        <div class="retro-horizon"></div>
        <div class="retro-grid"></div>
    </div>
    
    <!-- ============================================
         EFFET VHS (scanlines + tracking + barres)
         ============================================ -->
    <div class="vhs-overlay"></div>
    <div class="vhs-bars">
        <span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span>
    </div>

    <!-- ============================================
         NAVBAR RÉTRO
         ============================================ -->
    <nav class="retro-navbar">
        <div class="retro-brand">
            <?php echo htmlspecialchars(strtoupper($appName)); ?>
        </div>
        <div class="retro-status">
            <span class="rec-dot"></span>
            REC • INVITATION_80s.mp4
        </div>
    </nav>

    <!-- ============================================
         HERO RÉTRO
         ============================================ -->
    <section class="retro-hero">
        
        <div class="retro-blason">
            
            <div class="retro-tag">
                <i class="fas fa-cassette-tape"></i>
                INVITATION SPÉCIALE • 1980s EDITION
            </div>
            
            <div class="retro-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="retro-divider">
                <div class="line"></div>
                <span class="star">★</span>
                <div class="line"></div>
            </div>
            
            <div class="retro-hosts-intro">
                // Vous êtes convié(e) à célébrer
            </div>
            <div class="retro-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="retro-event-type">
                ⚡ <?php echo htmlspecialchars(strtoupper($eventType)); ?> ⚡
            </div>
            
        </div>
    </section>

    <!-- ============================================
         CASSETTE AUDIO DÉCORATIVE
         ============================================ -->
    <div class="cassette-decor">
        <div class="cassette">
            <div class="cassette-reels">
                <div class="cassette-reel"></div>
                <div class="cassette-reel"></div>
            </div>
        </div>
    </div>

    <!-- ============================================
         CARTE RÉTRO (Détails)
         ============================================ -->
    <div class="retro-card">
        
        <div class="retro-card-title">
            ▶ DÉTAILS DE L'ÉVÉNEMENT ◀
        </div>
        
        <div class="retro-info-grid">
            
            <div class="retro-info-item">
                <i class="fas fa-calendar-alt icon"></i>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="retro-info-item">
                <i class="fas fa-clock icon"></i>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="retro-info-item" style="grid-column: 1 / -1;">
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
                   class="retro-btn-itinerary">
                    <i class="fas fa-route"></i> Itinéraire
                </a>
            </div>
            
            <div class="retro-info-item" style="grid-column: 1 / -1;">
                <i class="fas fa-users icon"></i>
                <div class="label">PLACES RÉSERVÉES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="retro-section apparue">
            <div class="retro-alert retro-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="retro-section">
            <div class="retro-section-title">
                📼 GALERIE SOUVENIRS 📼
            </div>
            <div class="retro-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="retro-photo">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                        <div class="caption">Photo n°<?php echo $index + 1; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="retro-section">
        <div class="retro-section-title">
            ★ CODE D'ACCÈS ★
        </div>
        <div class="retro-qr-wrapper">
            <div class="retro-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Orbitron',sans-serif;font-size:13px;color:var(--neon-cyan);letter-spacing:0.3em;margin-top:24px;text-shadow:0 0 10px var(--neon-cyan);font-weight:700;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="retro-section">
            <div class="retro-section-title">
                ⚡ CONFIRMATION ⚡
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="retro-form-group">
                    <label>NOMBRE DE PERSONNES</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="retro-form-group">
                    <label>VOTRE RÉPONSE</label>
                    <div class="retro-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="retro-option-radio">
                            <label for="presenceOui" class="retro-option-label">
                                <i class="fas fa-thumbs-up"></i> JE VIE NS
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="retro-option-radio">
                            <label for="presenceNon" class="retro-option-label">
                                <i class="fas fa-times"></i> PAS DISPO
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="retro-form-group">
                    <label>MESSAGE (OPTIONNEL)</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message rétro..."></textarea>
                </div>
                
                <button type="submit" class="retro-btn-submit">
                    <i class="fas fa-play"></i> ENVOYER MA RÉPONSE
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="retro-section">
            <div class="retro-section-title">
                🥤 BOISSONS PRÉFÉRÉES 🥤
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--neon-cyan);font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:0.2em;padding:20px 0;text-shadow:0 0 10px var(--neon-cyan);">
                    <i class="fas fa-lock"></i> PRÉFÉRENCES ENREGISTRÉES
                </div>
                <div class="retro-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="retro-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Orbitron',sans-serif;font-size:13px;color:var(--text-muted);margin-bottom:24px;letter-spacing:0.1em;">
                        SÉLECTIONNEZ <strong style="color:var(--neon-pink);text-shadow:0 0 10px var(--neon-pink);">2 BOISSONS</strong> : <span id="selectedCount" style="color:var(--neon-cyan);">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="retro-boisson-category">
                            <div class="retro-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'AUTRES'); ?>
                            </div>
                            <div class="retro-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="retro-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="retro-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER RÉTRO
         ============================================ -->
    <footer class="retro-footer">
        <div class="retro-footer-brand"><?php echo htmlspecialchars($appName); ?></div>
        <div class="retro-footer-tagline">// INVITATIONS • SINCE 1980 //</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="retro-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(0, 240, 255, 0.2);font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:0.3em;color:var(--text-muted);">
            © <?php echo date('Y'); ?> • ALL RIGHTS RESERVED
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.retro-card, .retro-section');
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
                        colorDark: '#0a0420',
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
            const hero = document.querySelector('.retro-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5,
                    useCORS: true,
                    backgroundColor: '#0a0420',
                    logging: false
                });
                const link = document.createElement('a');
                link.download = `retro80_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.retro-boisson-item.selected').forEach(item => {
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