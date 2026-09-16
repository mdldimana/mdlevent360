<?php
/**
 * ============================================================
 * TEMPLATE : MODERNE v2 — Hologramme cyberpunk
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400;500;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --cyan: #00f0ff;
            --cyan-dark: #00a8b8;
            --magenta: #ff00e5;
            --magenta-dark: #b8009d;
            --black: #05050a;
            --dark: #0a0a12;
            --dark-2: #101018;
            --grid: rgba(0, 240, 255, 0.08);
            --text: #e8e8f0;
            --text-muted: #6a6a80;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: var(--black);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           GRILLE NÉON DE FOND
           ============================================ */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
            animation: gridScroll 20s linear infinite;
        }
        @keyframes gridScroll {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* ============================================
           HOLOGRAMME D'INTRO
           ============================================ */
        .holo-intro {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: holoFadeOut 2.5s ease-in-out 2s forwards;
        }
        @keyframes holoFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .holo-lines {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .holo-lines span {
            position: absolute;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0;
            animation: holoScan 1s ease-in-out infinite;
        }
        .holo-lines span:nth-child(1) { top: 20%; animation-delay: 0s; }
        .holo-lines span:nth-child(2) { top: 40%; animation-delay: 0.15s; }
        .holo-lines span:nth-child(3) { top: 60%; animation-delay: 0.3s; }
        .holo-lines span:nth-child(4) { top: 80%; animation-delay: 0.45s; }
        @keyframes holoScan {
            0%, 100% { opacity: 0; transform: scaleX(0); }
            50% { opacity: 1; transform: scaleX(1); }
        }
        
        .holo-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            color: var(--cyan);
            letter-spacing: 0.3em;
            opacity: 0;
            animation: holoTextIn 1.5s ease-out 0.3s forwards;
            text-shadow: 0 0 20px var(--cyan);
        }
        @keyframes holoTextIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        /* ============================================
           NAVBAR CYBER
           ============================================ */
        .cyber-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(5, 5, 10, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--cyan);
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.15);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }
        
        .cyber-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.2em;
            color: var(--cyan);
            text-shadow: 0 0 10px var(--cyan);
        }
        .cyber-brand .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }
        
        .cyber-status {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.15em;
        }
        .cyber-status .live {
            color: var(--cyan);
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        /* ============================================
           HERO CYBER
           ============================================ */
        .cyber-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            z-index: 1;
        }
        
        /* Cercles néon */
        .cyber-rings {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }
        .cyber-rings .ring {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 1px solid var(--cyan);
            opacity: 0.3;
            animation: ringExpand 4s ease-out infinite;
        }
        .cyber-rings .ring:nth-child(1) { width: 300px; height: 300px; animation-delay: 0s; }
        .cyber-rings .ring:nth-child(2) { width: 500px; height: 500px; animation-delay: 1.3s; }
        .cyber-rings .ring:nth-child(3) { width: 700px; height: 700px; animation-delay: 2.6s; }
        @keyframes ringExpand {
            0% { opacity: 0.6; transform: translate(-50%, -50%) scale(0.5); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(1.5); }
        }
        
        .cyber-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 700px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.3s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .cyber-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border: 1px solid var(--cyan);
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--cyan);
            margin-bottom: 30px;
            text-transform: uppercase;
            box-shadow: 
                0 0 20px rgba(0, 240, 255, 0.2),
                inset 0 0 20px rgba(0, 240, 255, 0.05);
        }
        .cyber-tag::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 10px var(--cyan);
        }
        
        .cyber-guest {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(28px, 5vw, 44px);
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.05em;
            margin-bottom: 40px;
            text-shadow: 
                0 0 20px rgba(0, 240, 255, 0.3),
                0 0 40px rgba(255, 0, 229, 0.1);
            position: relative;
        }
        .cyber-guest .glitch {
            position: relative;
        }
        .cyber-guest .glitch::before,
        .cyber-guest .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0; left: 0;
            width: 100%;
        }
        .cyber-guest .glitch::before {
            color: var(--magenta);
            animation: glitch1 3s infinite linear alternate-reverse;
            clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
        }
        .cyber-guest .glitch::after {
            color: var(--cyan);
            animation: glitch2 2s infinite linear alternate-reverse;
            clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
        }
        @keyframes glitch1 {
            0%, 100% { transform: translate(0, 0); }
            20% { transform: translate(-2px, 1px); }
            40% { transform: translate(-1px, -1px); }
            60% { transform: translate(2px, 1px); }
            80% { transform: translate(1px, -1px); }
        }
        @keyframes glitch2 {
            0%, 100% { transform: translate(0, 0); }
            20% { transform: translate(2px, -1px); }
            40% { transform: translate(1px, 1px); }
            60% { transform: translate(-2px, -1px); }
            80% { transform: translate(-1px, 1px); }
        }
        
        .cyber-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        .cyber-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            box-shadow: 0 0 10px var(--cyan);
        }
        .cyber-divider .diamond {
            width: 8px;
            height: 8px;
            background: var(--cyan);
            transform: rotate(45deg);
            box-shadow: 0 0 15px var(--cyan);
        }
        
        .cyber-hosts-intro {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .cyber-host-name {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(48px, 10vw, 96px);
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--cyan) 0%, var(--magenta) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 0.95;
            margin-bottom: 16px;
            filter: drop-shadow(0 0 30px rgba(0, 240, 255, 0.3));
            animation: cyberGradient 6s ease-in-out infinite;
            background-size: 200% auto;
        }
        @keyframes cyberGradient {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .cyber-event-type {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            letter-spacing: 0.4em;
            color: var(--cyan);
            text-transform: uppercase;
            text-shadow: 0 0 10px var(--cyan);
        }
        
        /* ============================================
           CARTE HOLOGRAMME (Détails)
           ============================================ */
        .cyber-card {
            position: relative;
            max-width: 800px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(10, 10, 18, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--cyan);
            box-shadow: 
                0 0 0 1px rgba(0, 240, 255, 0.1),
                0 0 60px rgba(0, 240, 255, 0.15),
                inset 0 0 60px rgba(0, 240, 255, 0.03);
            clip-path: polygon(30px 0, 100% 0, 100% calc(100% - 30px), calc(100% - 30px) 100%, 0 100%, 0 30px);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.9s ease;
            z-index: 1;
        }
        .cyber-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .cyber-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        /* Coins décoratifs */
        .cyber-card::before, .cyber-card::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border-color: var(--magenta);
            border-style: solid;
        }
        .cyber-card::before {
            top: 8px; left: 8px;
            border-width: 2px 0 0 2px;
            box-shadow: 0 0 15px var(--magenta);
        }
        .cyber-card::after {
            bottom: 8px; right: 8px;
            border-width: 0 2px 2px 0;
            box-shadow: 0 0 15px var(--magenta);
        }
        
        .cyber-card-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            letter-spacing: 0.4em;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 240, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cyber-card-title::before {
            content: '>';
            color: var(--magenta);
            animation: blink 1s steps(1) infinite;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
        
        .cyber-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .cyber-info-grid { grid-template-columns: 1fr; }
        }
        
        .cyber-info-item {
            padding: 20px;
            background: rgba(0, 0, 0, 0.4);
            border-left: 2px solid var(--cyan);
            transition: all 0.3s ease;
        }
        .cyber-info-item:hover {
            background: rgba(0, 240, 255, 0.05);
            border-left-color: var(--magenta);
            transform: translateX(4px);
        }
        
        .cyber-info-item .label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .cyber-info-item .value {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.4;
        }
        .cyber-info-item .value .sub {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 400;
        }
        
        /* Bouton itinéraire cyber */
        .cyber-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 24px;
            background: transparent;
            border: 1px solid var(--cyan);
            color: var(--cyan);
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .cyber-btn-itinerary::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: var(--cyan);
            transition: left 0.4s ease;
            z-index: -1;
        }
        .cyber-btn-itinerary:hover {
            color: var(--black);
            box-shadow: 0 0 30px var(--cyan);
        }
        .cyber-btn-itinerary:hover::before {
            left: 0;
        }
        
        /* ============================================
           SECTIONS CYBER
           ============================================ */
        .cyber-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(10, 10, 18, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 240, 255, 0.2);
            clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
            z-index: 1;
        }
        .cyber-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .cyber-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .cyber-section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--text);
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .cyber-section-title .accent {
            color: var(--cyan);
            text-shadow: 0 0 20px var(--cyan);
        }
        
        /* Formulaires */
        .cyber-form-group { margin-bottom: 24px; }
        .cyber-form-group label {
            display: block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .cyber-form-group label::before {
            content: '$ ';
            color: var(--magenta);
        }
        .cyber-form-group input,
        .cyber-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 240, 255, 0.3);
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .cyber-form-group input:focus,
        .cyber-form-group textarea:focus {
            outline: none;
            border-color: var(--cyan);
            background: rgba(0, 240, 255, 0.05);
            box-shadow: 
                0 0 0 1px var(--cyan),
                0 0 20px rgba(0, 240, 255, 0.3);
        }
        
        .cyber-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 480px) {
            .cyber-options-grid { grid-template-columns: 1fr; }
        }
        
        .cyber-option-radio { display: none; }
        .cyber-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 1px solid rgba(0, 240, 255, 0.3);
            background: rgba(0, 0, 0, 0.4);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .cyber-option-label:hover {
            border-color: var(--cyan);
            color: var(--cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
        }
        .cyber-option-radio:checked + .cyber-option-label {
            border-color: var(--cyan);
            background: rgba(0, 240, 255, 0.1);
            color: var(--cyan);
            box-shadow: 
                0 0 0 1px var(--cyan),
                0 0 30px rgba(0, 240, 255, 0.3);
            text-shadow: 0 0 10px var(--cyan);
        }
        
        .cyber-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--cyan), var(--magenta));
            color: var(--black);
            border: none;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.4);
            margin-top: 10px;
        }
        .cyber-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 0 40px rgba(0, 240, 255, 0.6),
                0 0 80px rgba(255, 0, 229, 0.3);
        }
        
        /* Photos */
        .cyber-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .cyber-photo {
            position: relative;
            aspect-ratio: 1/1;
            overflow: hidden;
            border: 1px solid var(--cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
            transition: all 0.3s ease;
            clip-path: polygon(15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%, 0 15px);
        }
        .cyber-photo:hover {
            transform: scale(1.02);
            box-shadow: 0 0 40px rgba(0, 240, 255, 0.4);
        }
        .cyber-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(1.2) contrast(1.1);
        }
        
        /* Boissons */
        .cyber-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .cyber-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 1px solid rgba(0, 240, 255, 0.3);
            background: rgba(0, 0, 0, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
        }
        .cyber-boisson-item.selected {
            border-color: var(--cyan);
            background: rgba(0, 240, 255, 0.1);
            color: var(--cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.3);
        }
        .cyber-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .cyber-boisson-item.selected .check { opacity: 1; }
        
        .cyber-boisson-category { margin-bottom: 20px; }
        .cyber-boisson-category-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            letter-spacing: 0.2em;
            color: var(--magenta);
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        
        /* QR */
        .cyber-qr-wrapper {
            text-align: center;
        }
        .cyber-qr-box {
            display: inline-block;
            padding: 20px;
            background: var(--text);
            border: 2px solid var(--cyan);
            box-shadow: 
                0 0 0 4px rgba(0, 240, 255, 0.2),
                0 0 40px rgba(0, 240, 255, 0.4);
            position: relative;
        }
        .cyber-qr-box::before, .cyber-qr-box::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--magenta);
            box-shadow: 0 0 15px var(--magenta);
        }
        .cyber-qr-box::before { top: -2px; left: -2px; clip-path: polygon(0 0, 100% 0, 0 100%); }
        .cyber-qr-box::after { bottom: -2px; right: -2px; clip-path: polygon(100% 100%, 100% 0, 0 100%); }
        
        /* Footer */
        .cyber-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .cyber-footer-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.2em;
            color: var(--cyan);
            text-shadow: 0 0 20px var(--cyan);
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .cyber-footer-tagline {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        
        .cyber-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: transparent;
            border: 1px solid #25d366;
            color: #25d366;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .cyber-btn-whatsapp:hover {
            background: #25d366;
            color: var(--black);
            box-shadow: 0 0 30px rgba(37, 211, 102, 0.5);
        }
        
        /* Alerts */
        .cyber-alert {
            padding: 16px 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            border-left: 3px solid;
        }
        .cyber-alert-success { border-color: #10b981; background: rgba(16,185,129,0.1); color: #6ee7b7; }
        .cyber-alert-danger  { border-color: #ef4444; background: rgba(239,68,68,0.1); color: #fca5a5; }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--cyan), var(--magenta));
            color: var(--black);
            border: none;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.4);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3.8s forwards;
        }
        #downloadBtn:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 50px rgba(0, 240, 255, 0.6);
        }
        @media (max-width: 480px) { #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 11px; } }
    </style>
</head>
<body>

    <!-- ============================================
         INTRO HOLOGRAMME
         ============================================ -->
    <div class="holo-intro">
        <div class="holo-lines">
            <span></span><span></span><span></span><span></span>
        </div>
        <div class="holo-text">INITIALIZING...</div>
    </div>

    <!-- ============================================
         NAVBAR CYBER
         ============================================ -->
    <nav class="cyber-navbar">
        <div class="cyber-brand">
            <span class="dot"></span>
            <?php echo htmlspecialchars(strtoupper($appName)); ?>
        </div>
        <div class="cyber-status">
            <span class="live">● LIVE</span> / INVITATION_v2.0
        </div>
    </nav>

    <!-- ============================================
         HERO CYBER
         ============================================ -->
    <section class="cyber-hero">
        
        <div class="cyber-rings">
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="ring"></div>
        </div>
        
        <div class="cyber-blason">
            
            <div class="cyber-tag">INVITATION PERSONNELLE</div>
            
            <div class="cyber-guest">
                <span class="glitch" data-text="<?php echo htmlspecialchars($guestName); ?>">
                    <?php echo htmlspecialchars($guestName); ?>
                </span>
            </div>
            
            <div class="cyber-divider">
                <div class="line"></div>
                <div class="diamond"></div>
                <div class="line"></div>
            </div>
            
            <div class="cyber-hosts-intro">// Vous êtes convié(e) à célébrer</div>
            <div class="cyber-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="cyber-event-type">[ <?php echo htmlspecialchars(strtoupper($eventType)); ?> ]</div>
            
        </div>
    </section>

    <!-- ============================================
         CARTE HOLOGRAMME (Détails)
         ============================================ -->
    <div class="cyber-card">
        
        <div class="cyber-card-title">DÉTAILS_ÉVÉNEMENT.config</div>
        
        <div class="cyber-info-grid">
            
            <div class="cyber-info-item">
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="cyber-info-item">
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="cyber-info-item" style="grid-column: 1 / -1;">
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
                   class="cyber-btn-itinerary">
                    <i class="fas fa-route"></i> Ouvrir dans Maps
                </a>
            </div>
            
            <div class="cyber-info-item" style="grid-column: 1 / -1;">
                <div class="label">PLACES_RÉSERVÉES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="cyber-section apparue">
            <div class="cyber-alert cyber-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="cyber-section">
            <div class="cyber-section-title">// <span class="accent">GALERIE</span>_SOUVENIRS</div>
            <div class="cyber-photos-grid">
                <?php foreach ($photosHost as $photo): ?>
                    <div class="cyber-photo">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="cyber-section">
        <div class="cyber-section-title">// <span class="accent">CODE</span>_D'ACCÈS</div>
        <div class="cyber-qr-wrapper">
            <div class="cyber-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--cyan);letter-spacing:0.3em;margin-top:20px;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="cyber-section">
            <div class="cyber-section-title">// <span class="accent">CONFIRM</span>_PRÉSENCE</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="cyber-form-group">
                    <label>nombre_personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="cyber-form-group">
                    <label>réponse</label>
                    <div class="cyber-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="cyber-option-radio">
                            <label for="presenceOui" class="cyber-option-label">
                                <i class="fas fa-play"></i> Je serai là
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="cyber-option-radio">
                            <label for="presenceNon" class="cyber-option-label">
                                <i class="fas fa-times"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="cyber-form-group">
                    <label>message_</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="cyber-btn-submit">
                    <i class="fas fa-paper-plane"></i> TRANSMETTRE
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="cyber-section">
            <div class="cyber-section-title">// <span class="accent">PRÉFÉRENCES</span>_BOISSONS</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--cyan);font-family:'JetBrains Mono',monospace;font-size:12px;padding:20px 0;">
                    <i class="fas fa-lock"></i> PRÉFÉRENCES_ENREGISTRÉES
                </div>
                <div class="cyber-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="cyber-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-muted);margin-bottom:20px;letter-spacing:0.1em;">
                        SELECT_2_MAX : <span id="selectedCount" style="color:var(--cyan);">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="cyber-boisson-category">
                            <div class="cyber-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'AUTRES'); ?>
                            </div>
                            <div class="cyber-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="cyber-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="cyber-btn-submit">
                        <i class="fas fa-save"></i> SAUVEGARDER
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER CYBER
         ============================================ -->
    <footer class="cyber-footer">
        <div class="cyber-footer-brand"><?php echo htmlspecialchars($appName); ?></div>
        <div class="cyber-footer-tagline">// EXPÉRIENCE_ÉVÉNEMENTIELLE_v2.0</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="cyber-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> CONTACT
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(0, 240, 255, 0.2);font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:0.3em;color:var(--text-muted);">
            © <?php echo date('Y'); ?> // SYSTÈME_OPÉRATIONNEL
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.cyber-card, .cyber-section');
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
                        width: 180, height: 180,
                        colorDark: '#05050a', colorLight: '#e8e8f0',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // Download
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.cyber-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#05050a', logging: false
                });
                const link = document.createElement('a');
                link.download = `cyber_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.cyber-boisson-item.selected').forEach(item => {
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