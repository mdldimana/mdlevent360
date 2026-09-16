<?php
/**
 * ============================================================
 * TEMPLATE : SPIDERMAN (Anniversaire)
 * ============================================================
 * 
 * Design comics / super-héros :
 * - Araignée qui descend du plafond
 * - Toiles d'araignée dans les coins
 * - Bulles BD (BAM! POW! ZAP!)
 * - Effet halftone (points BD)
 * - Skyline New York la nuit
 * - Palette rouge/bleu Spidey
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
    
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@300;400;500;600;700;800;900&family=Anton&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        /* ============================================
           VARIABLES SPIDERMAN
           ============================================ */
        :root {
            --spidey-red: #e62429;
            --spidey-red-dark: #a01619;
            --spidey-blue: #1a3a8f;
            --spidey-blue-dark: #0f2456;
            --spidey-black: #0a0a12;
            --spidey-dark: #14141f;
            --web-white: #f5f5f7;
            --comic-yellow: #ffd93d;
            --comic-orange: #ff8c42;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--spidey-black);
            background-image: 
                radial-gradient(circle at 20% 10%, rgba(230, 36, 41, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 90%, rgba(26, 58, 143, 0.15) 0%, transparent 40%);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           EFFET HALFTONE (points BD)
           ============================================ */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: 
                radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 8px 8px;
            pointer-events: none;
            z-index: 1;
        }
        
        /* ============================================
           INTRO : ARAIGNÉE QUI DESCEND
           ============================================ */
        .spider-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--spidey-black);
            pointer-events: none;
            animation: introFadeOut 3s ease-in-out 2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        /* Fil de toile */
        .spider-thread {
            position: absolute;
            top: 0;
            left: 50%;
            width: 3px;
            height: 0;
            background: linear-gradient(180deg, 
                rgba(255,255,255,0.9) 0%, 
                rgba(255,255,255,0.5) 100%);
            transform: translateX(-50%);
            box-shadow: 0 0 10px rgba(255,255,255,0.5);
            animation: threadDrop 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        @keyframes threadDrop {
            0% { height: 0; }
            100% { height: 40vh; }
        }
        
        /* Araignée */
        .spider-icon {
            position: absolute;
            top: 40vh;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 80px;
            color: var(--spidey-red);
            filter: drop-shadow(0 0 30px var(--spidey-red));
            animation: 
                spiderDrop 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards,
                spiderSwing 2s ease-in-out 1.2s infinite;
            opacity: 0;
        }
        @keyframes spiderDrop {
            0% { top: 0; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 40vh; opacity: 1; }
        }
        @keyframes spiderSwing {
            0%, 100% { transform: translate(-50%, -50%) rotate(-5deg); }
            50% { transform: translate(-50%, -50%) rotate(5deg); }
        }
        
        /* ============================================
           NAVBAR SPIDEY
           ============================================ */
        .spidey-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(10,10,18,0.95) 0%, rgba(10,10,18,0.7) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 3px solid var(--spidey-red);
            box-shadow: 0 4px 20px rgba(230, 36, 41, 0.3);
            opacity: 0;
            animation: fadeIn 1s ease-out 2.5s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .spidey-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Bangers', cursive;
            font-size: 28px;
            letter-spacing: 0.08em;
            color: var(--spidey-red);
            text-shadow: 
                3px 3px 0 var(--spidey-blue),
                6px 6px 0 rgba(0,0,0,0.3);
        }
        .spidey-brand .web-icon {
            font-size: 24px;
            color: white;
        }
        
        .spidey-status {
            font-family: 'Anton', sans-serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            color: var(--comic-yellow);
            text-transform: uppercase;
            text-shadow: 0 0 10px var(--comic-yellow);
        }
        
        /* ============================================
           DÉCORS : TOILES DANS LES COINS
           ============================================ */
        .web-corner {
            position: fixed;
            width: 180px;
            height: 180px;
            pointer-events: none;
            z-index: 5;
            opacity: 0.6;
        }
        .web-corner.tl { top: 60px; left: 0; }
        .web-corner.tr { top: 60px; right: 0; transform: scaleX(-1); }
        .web-corner.bl { bottom: 0; left: 0; transform: scaleY(-1); }
        .web-corner.br { bottom: 0; right: 0; transform: scale(-1, -1); }
        
        @media (max-width: 768px) {
            .web-corner { width: 100px; height: 100px; }
        }
        
        /* ============================================
           HERO SPIDEY
           ============================================ */
        .spidey-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            z-index: 10;
        }
        
        /* Skyline NY en bas */
        .ny-skyline {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: 
                linear-gradient(180deg, transparent 0%, rgba(26, 58, 143, 0.3) 100%),
                repeating-linear-gradient(90deg, 
                    transparent 0px, transparent 30px,
                    var(--spidey-blue-dark) 30px, var(--spidey-blue-dark) 60px,
                    transparent 60px, transparent 90px,
                    var(--spidey-blue-dark) 90px, var(--spidey-blue-dark) 110px,
                    transparent 110px, transparent 140px,
                    var(--spidey-blue-dark) 140px, var(--spidey-blue-dark) 160px,
                    transparent 160px, transparent 200px
                );
            clip-path: polygon(
                0 100%, 0 60%, 30px 60%, 30px 40%, 60px 40%, 60px 70%, 
                90px 70%, 90px 30%, 120px 30%, 120px 50%, 150px 50%, 
                150px 20%, 180px 20%, 180px 70%, 220px 70%, 220px 40%,
                260px 40%, 260px 60%, 300px 60%, 300px 30%, 340px 30%,
                340px 50%, 380px 50%, 380px 20%, 420px 20%, 420px 70%,
                460px 70%, 460px 45%, 500px 45%, 500px 60%, 540px 60%,
                540px 25%, 580px 25%, 580px 50%, 620px 50%, 620px 70%,
                660px 70%, 660px 40%, 700px 40%, 700px 60%, 740px 60%,
                740px 30%, 780px 30%, 780px 55%, 820px 55%, 820px 20%,
                860px 20%, 860px 65%, 900px 65%, 900px 45%, 940px 45%,
                940px 60%, 980px 60%, 980px 35%, 1020px 35%, 1020px 55%,
                1060px 55%, 1060px 25%, 1100px 25%, 1100px 60%, 1140px 60%,
                1140px 40%, 1180px 40%, 1180px 65%, 1220px 65%, 1220px 30%,
                1260px 30%, 1260px 50%, 1300px 50%, 1300px 70%, 1340px 70%,
                1340px 40%, 1380px 40%, 1380px 60%, 1420px 60%, 1420px 30%,
                1460px 30%, 1460px 55%, 1500px 55%, 1500px 100%
            );
            opacity: 0.5;
            z-index: 0;
        }
        
        /* Lune */
        .moon {
            position: absolute;
            top: 15%;
            right: 15%;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #fff8dc 0%, #f0e68c 70%, #daa520 100%);
            box-shadow: 
                0 0 60px rgba(255, 248, 220, 0.4),
                0 0 120px rgba(255, 248, 220, 0.2);
            z-index: 0;
            opacity: 0.8;
        }
        @media (max-width: 768px) {
            .moon { width: 70px; height: 70px; top: 12%; right: 10%; }
        }
        
        .spidey-blason {
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
        
        /* Badge super-héros */
        .spidey-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            background: var(--spidey-red);
            color: white;
            font-family: 'Anton', sans-serif;
            font-size: 13px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            margin-bottom: 30px;
            transform: rotate(-3deg);
            box-shadow: 
                6px 6px 0 var(--spidey-blue),
                0 0 30px rgba(230, 36, 41, 0.5);
            border: 3px solid var(--spidey-black);
        }
        
        /* Nom de l'invité style comics */
        .spidey-guest {
            font-family: 'Bangers', cursive;
            font-size: clamp(38px, 7vw, 68px);
            letter-spacing: 0.03em;
            color: var(--comic-yellow);
            text-shadow: 
                4px 4px 0 var(--spidey-red),
                8px 8px 0 var(--spidey-blue),
                0 0 40px rgba(255, 217, 61, 0.5);
            margin-bottom: 30px;
            transform: rotate(-1.5deg);
            line-height: 1;
        }
        
        /* Séparateur toile */
        .spidey-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .spidey-divider .line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--spidey-red), transparent);
            box-shadow: 0 0 15px var(--spidey-red);
        }
        .spidey-divider .web-icon {
            font-size: 24px;
            color: white;
            filter: drop-shadow(0 0 10px white);
        }
        
        .spidey-hosts-intro {
            font-family: 'Anton', sans-serif;
            font-size: 14px;
            letter-spacing: 0.3em;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        
        /* Nom de l'hôte */
        .spidey-host-name {
            font-family: 'Bangers', cursive;
            font-size: clamp(56px, 11vw, 110px);
            line-height: 0.95;
            background: linear-gradient(180deg, 
                var(--spidey-red) 0%, 
                var(--spidey-red-dark) 40%,
                var(--spidey-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.02em;
            margin-bottom: 16px;
            filter: drop-shadow(0 4px 20px rgba(230, 36, 41, 0.5));
            transform: rotate(-1deg);
        }
        
        .spidey-event-type {
            font-family: 'Anton', sans-serif;
            font-size: 16px;
            letter-spacing: 0.5em;
            color: var(--comic-yellow);
            text-transform: uppercase;
            text-shadow: 0 0 15px var(--comic-yellow);
        }
        
        /* ============================================
           BULLES BD FLOTTANTES
           ============================================ */
        .comic-bubble {
            position: absolute;
            font-family: 'Bangers', cursive;
            padding: 12px 24px;
            border-radius: 20px;
            border: 4px solid var(--spidey-black);
            box-shadow: 6px 6px 0 rgba(0,0,0,0.4);
            font-size: 28px;
            letter-spacing: 0.05em;
            z-index: 20;
            animation: bubbleFloat 4s ease-in-out infinite;
        }
        @keyframes bubbleFloat {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }
        
        .comic-bubble.bam {
            top: 12%;
            left: 8%;
            background: var(--comic-yellow);
            color: var(--spidey-red);
            animation-delay: 0s;
        }
        .comic-bubble.pow {
            top: 20%;
            right: 10%;
            background: var(--spidey-red);
            color: white;
            animation-delay: 1s;
        }
        .comic-bubble.zap {
            bottom: 25%;
            left: 12%;
            background: var(--spidey-blue);
            color: var(--comic-yellow);
            animation-delay: 2s;
        }
        .comic-bubble.wow {
            bottom: 18%;
            right: 14%;
            background: var(--comic-orange);
            color: white;
            animation-delay: 0.5s;
        }
        
        @media (max-width: 768px) {
            .comic-bubble {
                font-size: 18px;
                padding: 8px 16px;
                border-width: 3px;
                box-shadow: 4px 4px 0 rgba(0,0,0,0.4);
            }
            .comic-bubble.bam { top: 8%; left: 4%; }
            .comic-bubble.pow { top: 14%; right: 4%; }
            .comic-bubble.zap { bottom: 22%; left: 4%; }
            .comic-bubble.wow { bottom: 16%; right: 4%; }
        }
        
        /* ============================================
           CARTE COMICS (Détails)
           ============================================ */
        .comic-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--spidey-dark);
            background-image: 
                radial-gradient(circle, rgba(230, 36, 41, 0.08) 1px, transparent 1px);
            background-size: 12px 12px;
            border: 4px solid var(--spidey-black);
            box-shadow: 
                12px 12px 0 var(--spidey-red),
                24px 24px 0 var(--spidey-blue),
                0 30px 80px rgba(0, 0, 0, 0.6);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
            z-index: 10;
            transform: rotate(-0.5deg);
        }
        .comic-card.apparue {
            opacity: 1;
            transform: translateY(0) rotate(-0.5deg);
        }
        @media (max-width: 640px) {
            .comic-card { 
                padding: 40px 22px; 
                margin: 40px 15px;
                box-shadow: 
                    8px 8px 0 var(--spidey-red),
                    16px 16px 0 var(--spidey-blue);
            }
        }
        
        .comic-card-title {
            font-family: 'Bangers', cursive;
            font-size: 36px;
            letter-spacing: 0.05em;
            color: var(--comic-yellow);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 3px dashed var(--spidey-red);
            text-shadow: 
                3px 3px 0 var(--spidey-red),
                6px 6px 0 var(--spidey-blue);
            transform: rotate(-1deg);
        }
        
        .comic-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .comic-info-grid { grid-template-columns: 1fr; }
        }
        
        .comic-info-item {
            padding: 24px;
            background: rgba(0, 0, 0, 0.4);
            border: 3px solid var(--spidey-red);
            box-shadow: 
                4px 4px 0 var(--spidey-blue),
                inset 0 0 30px rgba(230, 36, 41, 0.05);
            transition: all 0.3s ease;
            text-align: center;
        }
        .comic-info-item:nth-child(even) {
            border-color: var(--spidey-blue);
            box-shadow: 
                4px 4px 0 var(--spidey-red),
                inset 0 0 30px rgba(26, 58, 143, 0.05);
        }
        .comic-info-item:hover {
            transform: translateY(-4px) rotate(1deg);
            box-shadow: 
                6px 6px 0 var(--comic-yellow),
                inset 0 0 40px rgba(255, 217, 61, 0.1);
        }
        
        .comic-info-item .icon {
            font-size: 36px;
            color: var(--comic-yellow);
            margin-bottom: 12px;
            display: block;
            filter: drop-shadow(0 0 10px var(--comic-yellow));
        }
        
        .comic-info-item .label {
            font-family: 'Anton', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .comic-info-item .value {
            font-family: 'Bangers', cursive;
            font-size: 26px;
            letter-spacing: 0.03em;
            color: white;
            line-height: 1.2;
            text-shadow: 
                2px 2px 0 var(--spidey-red),
                4px 4px 0 rgba(0,0,0,0.3);
        }
        .comic-info-item .value .sub {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            margin-top: 6px;
            font-weight: 500;
            text-shadow: none;
        }
        
        /* Bouton itinéraire comics */
        .comic-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 28px;
            background: var(--spidey-red);
            color: white;
            font-family: 'Bangers', cursive;
            font-size: 20px;
            letter-spacing: 0.08em;
            text-decoration: none;
            border: 3px solid var(--spidey-black);
            box-shadow: 
                4px 4px 0 var(--spidey-blue),
                0 0 30px rgba(230, 36, 41, 0.5);
            transition: all 0.3s ease;
            transform: rotate(-1deg);
        }
        .comic-btn-itinerary:hover {
            transform: translateY(-3px) rotate(1deg);
            box-shadow: 
                6px 6px 0 var(--comic-yellow),
                0 0 40px rgba(255, 217, 61, 0.6);
            color: white;
        }
        
        /* ============================================
           SECTIONS COMMUNES
           ============================================ */
        .comic-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: var(--spidey-dark);
            background-image: 
                radial-gradient(circle, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 10px 10px;
            border: 4px solid var(--spidey-black);
            box-shadow: 
                10px 10px 0 var(--spidey-blue),
                20px 20px 0 rgba(230, 36, 41, 0.7),
                0 20px 60px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
            z-index: 10;
        }
        .comic-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .comic-section { 
                padding: 35px 22px; 
                margin: 40px 15px;
                box-shadow: 
                    6px 6px 0 var(--spidey-blue),
                    12px 12px 0 rgba(230, 36, 41, 0.7);
            }
        }
        
        .comic-section-title {
            font-family: 'Bangers', cursive;
            font-size: 32px;
            letter-spacing: 0.05em;
            color: var(--comic-yellow);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px dashed var(--spidey-red);
            text-shadow: 
                3px 3px 0 var(--spidey-red),
                6px 6px 0 var(--spidey-blue);
            transform: rotate(-0.5deg);
        }
        
        /* Formulaires */
        .comic-form-group { margin-bottom: 24px; }
        .comic-form-group label {
            display: block;
            font-family: 'Anton', sans-serif;
            font-size: 12px;
            letter-spacing: 0.25em;
            color: var(--comic-yellow);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 8px rgba(255, 217, 61, 0.5);
        }
        .comic-form-group input,
        .comic-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 3px solid var(--spidey-red);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .comic-form-group input:focus,
        .comic-form-group textarea:focus {
            outline: none;
            border-color: var(--comic-yellow);
            box-shadow: 
                0 0 0 3px rgba(255, 217, 61, 0.2),
                0 0 30px rgba(255, 217, 61, 0.4);
            background: rgba(255, 217, 61, 0.05);
        }
        
        .comic-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .comic-options-grid { grid-template-columns: 1fr; }
        }
        
        .comic-option-radio { display: none; }
        .comic-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 3px solid var(--spidey-red);
            background: rgba(0, 0, 0, 0.5);
            font-family: 'Bangers', cursive;
            font-size: 22px;
            letter-spacing: 0.05em;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .comic-option-label:hover {
            border-color: var(--comic-yellow);
            color: var(--comic-yellow);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 217, 61, 0.3);
        }
        .comic-option-radio:checked + .comic-option-label {
            border-color: var(--comic-yellow);
            background: rgba(255, 217, 61, 0.15);
            color: var(--comic-yellow);
            box-shadow: 
                0 0 0 3px rgba(255, 217, 61, 0.2),
                0 0 30px rgba(255, 217, 61, 0.4);
            text-shadow: 0 0 10px var(--comic-yellow);
        }
        
        .comic-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 22px;
            background: linear-gradient(135deg, var(--spidey-red), var(--spidey-red-dark));
            color: white;
            border: 4px solid var(--spidey-black);
            font-family: 'Bangers', cursive;
            font-size: 26px;
            letter-spacing: 0.08em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                6px 6px 0 var(--spidey-blue),
                0 0 40px rgba(230, 36, 41, 0.5);
            margin-top: 10px;
            transform: rotate(-0.5deg);
        }
        .comic-btn-submit:hover {
            transform: translateY(-3px) rotate(0.5deg);
            box-shadow: 
                8px 8px 0 var(--comic-yellow),
                0 0 50px rgba(255, 217, 61, 0.5);
        }
        
        /* Photos style comic book */
        .comic-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }
        .comic-photo {
            background: white;
            padding: 16px 16px 60px;
            box-shadow: 
                8px 8px 0 var(--spidey-red),
                16px 16px 0 var(--spidey-blue);
            transform: rotate(-2deg);
            transition: all 0.4s ease;
            position: relative;
            border: 3px solid var(--spidey-black);
        }
        .comic-photo:nth-child(even) { 
            transform: rotate(2deg);
            box-shadow: 
                8px 8px 0 var(--spidey-blue),
                16px 16px 0 var(--spidey-red);
        }
        .comic-photo:hover {
            transform: rotate(0) scale(1.03);
            box-shadow: 
                12px 12px 0 var(--comic-yellow),
                24px 24px 0 var(--spidey-red);
            z-index: 5;
        }
        .comic-photo img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border: 2px solid var(--spidey-black);
        }
        .comic-photo .caption {
            position: absolute;
            bottom: 14px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Bangers', cursive;
            font-size: 22px;
            color: var(--spidey-red);
            letter-spacing: 0.05em;
        }
        
        /* Boissons */
        .comic-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .comic-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 3px solid var(--spidey-red);
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Bangers', cursive;
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.05em;
        }
        .comic-boisson-item.selected {
            border-color: var(--comic-yellow);
            background: rgba(255, 217, 61, 0.15);
            color: var(--comic-yellow);
            box-shadow: 0 0 25px rgba(255, 217, 61, 0.4);
            text-shadow: 0 0 10px var(--comic-yellow);
        }
        .comic-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .comic-boisson-item.selected .check { opacity: 1; }
        
        .comic-boisson-category { margin-bottom: 20px; }
        .comic-boisson-category-title {
            font-family: 'Bangers', cursive;
            font-size: 20px;
            letter-spacing: 0.1em;
            color: var(--spidey-red);
            text-transform: uppercase;
            margin-bottom: 12px;
            text-shadow: 2px 2px 0 var(--spidey-blue);
        }
        
        /* QR Code */
        .comic-qr-wrapper {
            text-align: center;
        }
        .comic-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 4px solid var(--spidey-black);
            box-shadow: 
                8px 8px 0 var(--spidey-red),
                16px 16px 0 var(--spidey-blue);
            position: relative;
            transform: rotate(-1deg);
        }
        .comic-qr-box::before {
            content: '🕷️';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 36px;
            animation: spiderFloat 2s ease-in-out infinite;
        }
        @keyframes spiderFloat {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-8px); }
        }
        
        /* ============================================
           FOOTER SPIDEY
           ============================================ */
        .comic-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .comic-footer-brand {
            font-family: 'Bangers', cursive;
            font-size: 42px;
            letter-spacing: 0.05em;
            color: var(--spidey-red);
            text-shadow: 
                4px 4px 0 var(--spidey-blue),
                8px 8px 0 rgba(0,0,0,0.3);
            margin-bottom: 8px;
            transform: rotate(-1deg);
        }
        .comic-footer-tagline {
            font-family: 'Anton', sans-serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            color: var(--comic-yellow);
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        
        .comic-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 40px;
            background: #25d366;
            color: white;
            font-family: 'Bangers', cursive;
            font-size: 22px;
            letter-spacing: 0.08em;
            text-decoration: none;
            border: 4px solid var(--spidey-black);
            box-shadow: 
                6px 6px 0 var(--spidey-blue),
                0 0 30px rgba(37, 211, 102, 0.5);
            transition: all 0.3s ease;
            transform: rotate(-0.5deg);
        }
        .comic-btn-whatsapp:hover {
            transform: translateY(-3px) rotate(0.5deg);
            box-shadow: 
                8px 8px 0 var(--comic-yellow),
                0 0 40px rgba(37, 211, 102, 0.7);
            color: white;
        }
        
        /* Alertes comics */
        .comic-alert {
            padding: 18px 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Bangers', cursive;
            font-size: 20px;
            letter-spacing: 0.05em;
            border: 3px solid;
            transform: rotate(-0.5deg);
        }
        .comic-alert-success { 
            border-color: var(--comic-yellow); 
            background: rgba(255, 217, 61, 0.15); 
            color: var(--comic-yellow); 
            box-shadow: 4px 4px 0 var(--spidey-blue);
        }
        .comic-alert-danger { 
            border-color: #ff3355; 
            background: rgba(255, 51, 85, 0.15); 
            color: #ff6688; 
            box-shadow: 4px 4px 0 var(--spidey-red);
        }
        .comic-alert-warning { 
            border-color: var(--comic-orange); 
            background: rgba(255, 140, 66, 0.15); 
            color: var(--comic-orange); 
            box-shadow: 4px 4px 0 var(--spidey-yellow);
        }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 18px 32px;
            background: var(--spidey-red);
            color: white;
            border: 4px solid var(--spidey-black);
            font-family: 'Bangers', cursive;
            font-size: 22px;
            letter-spacing: 0.08em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 
                6px 6px 0 var(--spidey-blue),
                0 0 40px rgba(230, 36, 41, 0.5);
            transform: rotate(-1deg);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px) rotate(0.5deg);
            box-shadow: 
                8px 8px 0 var(--comic-yellow),
                0 0 50px rgba(255, 217, 61, 0.6);
        }
        @media (max-width: 480px) {
            #downloadBtn { 
                bottom: 12px; 
                right: 12px; 
                padding: 12px 20px; 
                font-size: 16px;
                box-shadow: 
                    4px 4px 0 var(--spidey-blue),
                    0 0 30px rgba(230, 36, 41, 0.5);
            }
        }
    </style>
</head>
<body>

    <!-- ============================================
         INTRO : ARAIGNÉE QUI DESCEND
         ============================================ -->
    <div class="spider-intro">
        <div class="spider-thread"></div>
        <div class="spider-icon">🕷️</div>
    </div>

    <!-- ============================================
         DÉCORS : TOILES DANS LES COINS
         ============================================ -->
    <svg class="web-corner tl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <g stroke="white" stroke-width="1.5" fill="none" opacity="0.6">
            <path d="M 0 0 L 200 200 M 0 0 L 180 60 M 0 0 L 140 140 M 0 0 L 60 180 M 0 0 L 120 40 M 0 0 L 40 120"/>
            <path d="M 20 40 Q 30 30 40 20 M 40 80 Q 60 60 80 40 M 60 120 Q 90 90 120 60 M 80 160 Q 120 120 160 80"/>
        </g>
    </svg>
    <svg class="web-corner tr" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <g stroke="white" stroke-width="1.5" fill="none" opacity="0.6">
            <path d="M 0 0 L 200 200 M 0 0 L 180 60 M 0 0 L 140 140 M 0 0 L 60 180 M 0 0 L 120 40 M 0 0 L 40 120"/>
            <path d="M 20 40 Q 30 30 40 20 M 40 80 Q 60 60 80 40 M 60 120 Q 90 90 120 60 M 80 160 Q 120 120 160 80"/>
        </g>
    </svg>
    <svg class="web-corner bl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <g stroke="white" stroke-width="1.5" fill="none" opacity="0.6">
            <path d="M 0 0 L 200 200 M 0 0 L 180 60 M 0 0 L 140 140 M 0 0 L 60 180"/>
            <path d="M 20 40 Q 30 30 40 20 M 40 80 Q 60 60 80 40 M 60 120 Q 90 90 120 60"/>
        </g>
    </svg>
    <svg class="web-corner br" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <g stroke="white" stroke-width="1.5" fill="none" opacity="0.6">
            <path d="M 0 0 L 200 200 M 0 0 L 180 60 M 0 0 L 140 140 M 0 0 L 60 180"/>
            <path d="M 20 40 Q 30 30 40 20 M 40 80 Q 60 60 80 40 M 60 120 Q 90 90 120 60"/>
        </g>
    </svg>

    <!-- ============================================
         NAVBAR SPIDEY
         ============================================ -->
    <nav class="spidey-navbar">
        <div class="spidey-brand">
            <span class="web-icon">🕷️</span>
            <?php echo htmlspecialchars(strtoupper($appName)); ?>
        </div>
        <div class="spidey-status">
            ⚡ ANNIVERSAIRE SPÉCIAL ⚡
        </div>
    </nav>

    <!-- ============================================
         HERO SPIDEY
         ============================================ -->
    <section class="spidey-hero">
        
        <!-- Lune -->
        <div class="moon"></div>
        
        <!-- Skyline NY -->
        <div class="ny-skyline"></div>
        
        <!-- Bulles BD -->
        <div class="comic-bubble bam">BAM!</div>
        <div class="comic-bubble pow">POW!</div>
        <div class="comic-bubble zap">ZAP!</div>
        <div class="comic-bubble wow">WOW!</div>
        
        <div class="spidey-blason">
            
            <div class="spidey-badge">
                🕸️ INVITATION SPÉCIALE 🕸️
            </div>
            
            <div class="spidey-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="spidey-divider">
                <div class="line"></div>
                <span class="web-icon">🕷️</span>
                <div class="line"></div>
            </div>
            
            <div class="spidey-hosts-intro">
                ⚡ Vous êtes invité à l'anniversaire de ⚡
            </div>
            <div class="spidey-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="spidey-event-type">
                🎂 <?php echo htmlspecialchars(strtoupper($eventType)); ?> 🎂
            </div>
            
        </div>
    </section>

    <!-- ============================================
         CARTE COMICS (Détails)
         ============================================ -->
    <div class="comic-card">
        
        <div class="comic-card-title">
            ⚡ MISSION : DÉTAILS ⚡
        </div>
        
        <div class="comic-info-grid">
            
            <div class="comic-info-item">
                <i class="fas fa-calendar-alt icon"></i>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="comic-info-item">
                <i class="fas fa-clock icon"></i>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="comic-info-item" style="grid-column: 1 / -1;">
                <i class="fas fa-map-marker-alt icon"></i>
                <div class="label">QG DE LA FÊTE</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="comic-btn-itinerary">
                    <i class="fas fa-route"></i> ITINÉRAIRE
                </a>
            </div>
            
            <div class="comic-info-item" style="grid-column: 1 / -1;">
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
        <div class="comic-section apparue">
            <div class="comic-alert comic-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="comic-section">
            <div class="comic-section-title">
                📸 GALERIE HÉROÏQUE 📸
            </div>
            <div class="comic-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="comic-photo">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                        <div class="caption">ISSUE #<?php echo $index + 1; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="comic-section">
        <div class="comic-section-title">
            🎫 CODE D'ACCÈS 🎫
        </div>
        <div class="comic-qr-wrapper">
            <div class="comic-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Bangers',cursive;font-size:22px;color:var(--comic-yellow);letter-spacing:0.1em;margin-top:24px;text-shadow:2px 2px 0 var(--spidey-red);">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="comic-section">
            <div class="comic-section-title">
                ⚡ CONFIRMATION ⚡
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="comic-form-group">
                    <label>NOMBRE DE PERSONNES</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="comic-form-group">
                    <label>TA RÉPONSE, HÉROS !</label>
                    <div class="comic-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="comic-option-radio">
                            <label for="presenceOui" class="comic-option-label">
                                <i class="fas fa-thumbs-up"></i> OUI !
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="comic-option-radio">
                            <label for="presenceNon" class="comic-option-label">
                                <i class="fas fa-times"></i> PAS LÀ
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="comic-form-group">
                    <label>MESSAGE (OPTIONNEL)</label>
                    <textarea name="message_invite" rows="3" placeholder="Ton message..."></textarea>
                </div>
                
                <button type="submit" class="comic-btn-submit">
                    <i class="fas fa-bolt"></i> ENVOYER !
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="comic-section">
            <div class="comic-section-title">
                🥤 BOISSONS 🥤
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--comic-yellow);font-family:'Bangers',cursive;font-size:22px;letter-spacing:0.05em;padding:20px 0;text-shadow:2px 2px 0 var(--spidey-red);">
                    <i class="fas fa-lock"></i> PRÉFÉRENCES VERROUILLÉES
                </div>
                <div class="comic-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="comic-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Anton',sans-serif;font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:24px;letter-spacing:0.15em;">
                        SÉLECTIONNE <strong style="color:var(--comic-yellow);">2 BOISSONS</strong> : <span id="selectedCount" style="color:var(--spidey-red);font-weight:700;">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="comic-boisson-category">
                            <div class="comic-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'AUTRES'); ?>
                            </div>
                            <div class="comic-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="comic-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="comic-btn-submit">
                        <i class="fas fa-save"></i> ENREGISTRER !
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER SPIDEY
         ============================================ -->
    <footer class="comic-footer">
        <div class="comic-footer-brand">🕷️ <?php echo htmlspecialchars($appName); ?></div>
        <div class="comic-footer-tagline">// INVITATIONS HÉROÏQUES //</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="comic-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> NOUS CONTACTER
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px solid rgba(230, 36, 41, 0.3);font-family:'Anton',sans-serif;font-size:11px;letter-spacing:0.3em;color:rgba(255,255,255,0.5);">
            © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS
        </div>
    </footer>

    <!-- Download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">TÉLÉCHARGER</span>
    </button>

    <script>
        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.comic-card, .comic-section');
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
                        colorDark: '#0a0a12',
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
            const hero = document.querySelector('.spidey-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5,
                    useCORS: true,
                    backgroundColor: '#0a0a12',
                    logging: false
                });
                const link = document.createElement('a');
                link.download = `spiderman_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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

        // Boissons
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.comic-boisson-item.selected').forEach(item => {
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