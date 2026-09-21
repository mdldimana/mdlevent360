<?php
/**
 * ============================================================
 * TEMPLATE : NETFLIX v3 - Refonte complète
 * ============================================================
 * 
 * Nouveautés v3 :
 * - Affichage du nom de la table (si assignée)
 * - Photos en diaporama plein écran (image entière visible)
 * - Téléchargement corrigé (capture tout le contenu)
 * - Texte d'invitation personnalisé
 * - Suppression de la section "Informations complémentaires"
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
    
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        /* ============================================
           VARIABLES NETFLIX
           ============================================ */
        :root {
            --nf-red: #E50914;
            --nf-red-hover: #f40612;
            --nf-red-dark: #b20710;
            --nf-black: #000000;
            --nf-dark: #141414;
            --nf-dark-2: #181818;
            --nf-dark-3: #232323;
            --nf-gray: #2f2f2f;
            --nf-gray-light: #404040;
            --nf-text: #ffffff;
            --nf-text-muted: #808080;
            --nf-text-light: #b3b3b3;
            --nf-green: #46d369;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', 'Roboto', system-ui, sans-serif;
            background: var(--nf-black);
            color: var(--nf-text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           ANIMATION INTRO NETFLIX (Ta-dum)
           ============================================ */
        .netflix-intro {
            position: fixed;
            inset: 0;
            background: #000;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introFadeOut 2.8s ease-in-out 1.2s forwards;
        }
        
        .netflix-intro .letter {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(120px, 30vw, 320px);
            color: var(--nf-red);
            line-height: 1;
            opacity: 0;
            transform: scale(0.8);
            animation: 
                letterAppear 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s forwards,
                letterZoom 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1s forwards;
            text-shadow: 
                0 0 80px rgba(229, 9, 20, 0.8),
                0 0 160px rgba(229, 9, 20, 0.4);
        }
        
        @keyframes letterAppear {
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes letterZoom {
            0% { transform: scale(1); opacity: 1; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }
        
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        /* Particules d'ambiance autour du N */
        .intro-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .intro-particles span {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--nf-red);
            box-shadow: 0 0 20px var(--nf-red);
            animation: particleFloat 2.5s ease-in-out infinite;
        }
        .intro-particles span:nth-child(1) { top: 20%; left: 15%; animation-delay: 0s; }
        .intro-particles span:nth-child(2) { top: 70%; left: 25%; animation-delay: 0.3s; width: 6px; height: 6px; }
        .intro-particles span:nth-child(3) { top: 35%; right: 20%; animation-delay: 0.6s; }
        .intro-particles span:nth-child(4) { bottom: 25%; right: 30%; animation-delay: 0.9s; width: 8px; height: 8px; }
        .intro-particles span:nth-child(5) { top: 15%; right: 40%; animation-delay: 1.2s; }
        .intro-particles span:nth-child(6) { bottom: 40%; left: 10%; animation-delay: 1.5s; width: 5px; height: 5px; }
        
        @keyframes particleFloat {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.4; }
            50% { transform: translateY(-30px) scale(1.5); opacity: 1; }
        }
        
        /* ============================================
           NAVBAR NETFLIX (fixe)
           ============================================ */
        .netflix-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 16px 40px;
            background: linear-gradient(180deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.4s ease;
            opacity: 0;
            animation: navbarFadeIn 0.6s ease-out 2.5s forwards;
        }
        .netflix-navbar.scrolled {
            background: rgba(20, 20, 20, 0.98);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        @keyframes navbarFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .netflix-logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 32px;
            color: var(--nf-red);
            letter-spacing: 0.03em;
            text-decoration: none;
            line-height: 1;
            text-shadow: 0 0 20px rgba(229, 9, 20, 0.4);
            transition: all 0.3s ease;
        }
        .netflix-logo:hover {
            text-shadow: 0 0 30px rgba(229, 9, 20, 0.8);
            transform: scale(1.02);
        }
        
        .netflix-nav-links {
            display: none;
            gap: 24px;
            list-style: none;
        }
        @media (min-width: 768px) {
            .netflix-nav-links { display: flex; }
        }
        .netflix-nav-links a {
            color: var(--nf-text-light);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .netflix-nav-links a:hover { color: white; }
        
        /* ============================================
           HERO NETFLIX PLEIN ÉCRAN (Ken Burns)
           ============================================ */
        .netflix-hero {
            position: relative;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            overflow: hidden;
            padding-bottom: 80px;
        }
        
        .hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            animation: kenBurns 20s ease-in-out infinite alternate;
            z-index: 0;
        }
        
        @keyframes kenBurns {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(-2%, -2%); }
        }
        
        .hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                linear-gradient(180deg, rgba(0,0,0,0.7) 0%, transparent 30%, transparent 50%, rgba(0,0,0,0.95) 100%),
                linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 0 40px;
            max-width: 700px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.7s forwards;
        }
        
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .hero-content { padding: 0 20px; }
            .netflix-hero { min-height: 90vh; padding-bottom: 60px; }
        }
        
        /* Badge "N SERIES" */
        .netflix-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.3em;
            color: var(--nf-text-light);
            margin-bottom: 16px;
            opacity: 0;
            animation: slideInLeft 0.7s ease-out 3s forwards;
        }
        .netflix-badge .n-icon {
            color: var(--nf-red);
            font-size: 24px;
            font-weight: 900;
            font-family: 'Bebas Neue', sans-serif;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Titre géant Netflix */
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(60px, 11vw, 140px);
            font-weight: 400;
            line-height: 0.88;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            color: white;
            margin-bottom: 20px;
            text-shadow: 
                0 4px 20px rgba(0,0,0,0.9),
                0 0 60px rgba(0,0,0,0.6);
            opacity: 0;
            animation: 
                titleReveal 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3.1s forwards,
                titleGlow 4s ease-in-out 4s infinite;
        }
        
        @keyframes titleReveal {
            from { opacity: 0; transform: translateY(30px); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        
        @keyframes titleGlow {
            0%, 100% { text-shadow: 0 4px 20px rgba(0,0,0,0.9), 0 0 60px rgba(0,0,0,0.6); }
            50% { text-shadow: 0 4px 20px rgba(0,0,0,0.9), 0 0 60px rgba(0,0,0,0.6), 0 0 100px rgba(229, 9, 20, 0.15); }
        }
        
        .hero-title .red-accent {
            color: var(--nf-red);
        }
        
        /* Métadonnées Netflix */
        .hero-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--nf-text-light);
            opacity: 0;
            animation: fadeInUp 0.7s ease-out 3.4s forwards;
        }
        .hero-meta .match {
            color: var(--nf-green);
            font-weight: 700;
        }
        .hero-meta .year {
            color: white;
            font-weight: 500;
        }
        .hero-meta .badge-hd {
            border: 1px solid var(--nf-text-muted);
            padding: 1px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .hero-meta .badge-age {
            background: var(--nf-red);
            color: white;
            padding: 1px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 800;
        }
        .hero-meta .badge-table {
            background: var(--nf-green);
            color: black;
            padding: 2px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        
        /* Synopsis */
        .hero-synopsis {
            font-size: 16px;
            line-height: 1.6;
            color: var(--nf-text-light);
            max-width: 620px;
            margin-bottom: 28px;
            font-weight: 400;
            opacity: 0;
            animation: fadeInUp 0.7s ease-out 3.6s forwards;
        }
        .hero-synopsis strong { color: white; }
        
        /* Boutons style Netflix */
        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeInUp 0.7s ease-out 3.8s forwards;
        }
        
        .nf-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: none;
        }
        .nf-btn-play {
            background: white;
            color: black;
        }
        .nf-btn-play:hover {
            background: rgba(255,255,255,0.8);
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(255,255,255,0.3);
        }
        .nf-btn-info {
            background: rgba(109, 109, 110, 0.7);
            color: white;
        }
        .nf-btn-info:hover {
            background: rgba(109, 109, 110, 0.5);
            transform: scale(1.05);
        }
        .nf-btn i { font-size: 18px; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ============================================
           CARROUSEL NETFLIX (Row style)
           ============================================ */
        .nf-row {
            padding: 40px 0 20px;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        .nf-row.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        .nf-row-title {
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: white;
            padding: 0 40px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nf-row-title .explore {
            font-size: 12px;
            color: var(--nf-text-muted);
            font-weight: 500;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .nf-row:hover .nf-row-title .explore { opacity: 1; color: var(--nf-text-light); }
        .nf-row-title .explore i { font-size: 10px; margin-left: 4px; }
        
        @media (max-width: 768px) {
            .nf-row-title { padding: 0 20px; font-size: 17px; }
        }
        
        /* Scroll horizontal */
        .nf-row-scroller {
            display: flex;
            gap: 8px;
            padding: 20px 40px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            scroll-snap-type: x mandatory;
        }
        .nf-row-scroller::-webkit-scrollbar { display: none; }
        @media (max-width: 768px) {
            .nf-row-scroller { padding: 20px; }
        }
        
        /* Carte Netflix (avec hover scale) */
        .nf-card {
            flex-shrink: 0;
            width: 260px;
            aspect-ratio: 16/9;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            scroll-snap-align: start;
            background: var(--nf-dark-2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        .nf-card:hover {
            transform: scale(1.08);
            z-index: 10;
            box-shadow: 0 12px 40px rgba(0,0,0,0.8);
        }
        @media (max-width: 480px) {
            .nf-card { width: 200px; }
        }
        
        .nf-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .nf-card:hover img { transform: scale(1.05); }
        
        .nf-card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.9) 100%);
            opacity: 0;
            transition: opacity 0.35s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 16px;
        }
        .nf-card:hover .nf-card-overlay { opacity: 1; }
        
        .nf-card-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 0.03em;
            color: white;
            line-height: 1;
            margin-bottom: 8px;
        }
        .nf-card-sub {
            font-size: 11px;
            color: var(--nf-text-light);
            margin-bottom: 10px;
        }
        
        .nf-card-buttons {
            display: flex;
            gap: 6px;
        }
        .nf-card-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(42, 42, 42, 0.9);
            border: 1px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        .nf-card-btn:hover {
            background: white;
            color: black;
            transform: scale(1.15);
        }
        .nf-card-btn.play {
            background: white;
            color: black;
        }
        .nf-card-btn.play:hover {
            background: rgba(255,255,255,0.8);
        }
        
        /* ============================================
           SECTIONS TYPE NETFLIX DETAILS
           ============================================ */
        .nf-section {
            padding: 60px 40px;
            background: var(--nf-dark);
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        .nf-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 768px) {
            .nf-section { padding: 40px 20px; }
        }
        
        .nf-section-title {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--nf-text-muted);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nf-section-title .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--nf-gray) 0%, transparent 100%);
        }
        
        /* ============================================
           FORMULAIRES NETFLIX
           ============================================ */
        .nf-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .nf-form-group {
            margin-bottom: 20px;
        }
        .nf-form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--nf-text-light);
            margin-bottom: 8px;
        }
        .nf-form-group input,
        .nf-form-group textarea {
            width: 100%;
            padding: 16px 18px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--nf-gray);
            border-radius: 4px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .nf-form-group input:focus,
        .nf-form-group textarea:focus {
            outline: none;
            border-color: var(--nf-red);
            background: rgba(0, 0, 0, 0.7);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.2);
        }
        .nf-form-group input::placeholder,
        .nf-form-group textarea::placeholder {
            color: var(--nf-text-muted);
        }
        
        .nf-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 480px) {
            .nf-options-grid { grid-template-columns: 1fr; }
        }
        
        .nf-option-radio { display: none; }
        .nf-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px 20px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid var(--nf-gray);
            border-radius: 4px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.1em;
            color: var(--nf-text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            min-height: 64px;
        }
        .nf-option-label:hover {
            border-color: var(--nf-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(229, 9, 20, 0.2);
        }
        .nf-option-radio:checked + .nf-option-label {
            border-color: var(--nf-red);
            background: rgba(229, 9, 20, 0.15);
            color: white;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.3);
        }
        
        .nf-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px 32px;
            background: var(--nf-red);
            color: white;
            border: none;
            border-radius: 4px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(229, 9, 20, 0.4);
        }
        .nf-btn-submit:hover {
            background: var(--nf-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(229, 9, 20, 0.6);
        }
        
        .nf-btn-whatsapp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 32px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            margin-top: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
        }
        .nf-btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.5);
            color: white;
        }
        
        /* ============================================
           BOISSONS PILLS NETFLIX
           ============================================ */
        .nf-boisson-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .nf-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid var(--nf-gray);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: 600;
            color: var(--nf-text-light);
        }
        .nf-boisson-item:hover {
            border-color: var(--nf-red);
            transform: translateY(-2px);
        }
        .nf-boisson-item.selected {
            border-color: var(--nf-red);
            background: rgba(229, 9, 20, 0.15);
            color: white;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.2);
        }
        .nf-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; color: var(--nf-red); }
        .nf-boisson-item.selected .check { opacity: 1; }
        
        .nf-boisson-category {
            margin-bottom: 20px;
        }
        .nf-boisson-category-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            letter-spacing: 0.15em;
            color: var(--nf-red);
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* ============================================
           QR CODE
           ============================================ */
        .nf-qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .nf-qr-box {
            padding: 16px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 0 40px rgba(229, 9, 20, 0.4);
            position: relative;
        }
        .nf-qr-box::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 8px;
            border: 2px dashed rgba(229, 9, 20, 0.4);
            animation: qrPulse 3s ease-in-out infinite;
        }
        @keyframes qrPulse {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }
        .nf-qr-label {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.3em;
            color: var(--nf-text-muted);
            text-transform: uppercase;
        }
        
        /* ============================================
           MESSAGES / ALERTES
           ============================================ */
        .nf-alert {
            padding: 16px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid;
            animation: alertSlideIn 0.4s ease-out;
        }
        @keyframes alertSlideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .nf-alert-success { background: rgba(70, 211, 105, 0.1); border-color: var(--nf-green); color: #a3e8b8; }
        .nf-alert-danger  { background: rgba(229, 9, 20, 0.1); border-color: var(--nf-red); color: #fca5a5; }
        .nf-alert-warning { background: rgba(255, 165, 0, 0.1); border-color: #ffa500; color: #ffd58a; }
        
        /* ============================================
           FOOTER NETFLIX
           ============================================ */
        .nf-footer {
            padding: 60px 40px 40px;
            background: var(--nf-black);
            border-top: 1px solid var(--nf-gray);
            text-align: center;
        }
        .nf-footer-logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 36px;
            color: var(--nf-red);
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            text-shadow: 0 0 20px rgba(229, 9, 20, 0.4);
        }
        .nf-footer-tagline {
            font-size: 12px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--nf-text-muted);
            margin-bottom: 30px;
        }
        .nf-footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 24px;
            margin-bottom: 30px;
        }
        .nf-footer-links a {
            color: var(--nf-text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }
        .nf-footer-links a:hover { color: white; }
        .nf-footer-bottom {
            padding-top: 20px;
            border-top: 1px solid var(--nf-gray);
            font-size: 12px;
            color: var(--nf-text-muted);
        }
        @media (max-width: 768px) {
            .nf-footer { padding: 40px 20px 30px; }
        }
        
        /* ============================================
           BOUTON DOWNLOAD FLOTTANT
           ============================================ */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: var(--nf-red);
            color: white;
            border: none;
            border-radius: 4px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(229, 9, 20, 0.5);
            transition: all 0.3s ease;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out 4s forwards;
        }
        #downloadBtn:hover {
            background: var(--nf-red-hover);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 32px rgba(229, 9, 20, 0.7);
        }
        #downloadBtn:disabled { opacity: 0.6; cursor: not-allowed; }
        @media (max-width: 480px) {
            #downloadBtn {
                bottom: 16px;
                right: 16px;
                padding: 12px 18px;
                font-size: 13px;
            }
        }

        /* ============================================
           DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .photo-diaporama {
            position: relative;
            width: 100%;
            height: 70vh;
            min-height: 400px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
        }

        .photo-diaporama .diapo-slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 1.2s ease-in-out;
            z-index: 1;
        }

        .photo-diaporama .diapo-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .photo-diaporama .diapo-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* ← Permet de voir l'image entière */
            background: #000;
            border-radius: 8px;
            transition: transform 8s ease-in-out;
        }

        .photo-diaporama .diapo-slide.active img {
            transform: scale(1.05);
        }

        .diapo-nav {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
            background: rgba(0,0,0,0.6);
            padding: 8px 16px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
        }

        .diapo-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .diapo-dot.active {
            background: var(--nf-red);
            width: 28px;
            border-radius: 5px;
            box-shadow: 0 0 12px rgba(229, 9, 20, 0.8);
        }

        .diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .diapo-arrow:hover {
            background: var(--nf-red);
            transform: translateY(-50%) scale(1.1);
        }

        .diapo-arrow.prev { left: 20px; }
        .diapo-arrow.next { right: 20px; }

        @media (max-width: 480px) {
            .photo-diaporama { height: 50vh; min-height: 300px; }
            .diapo-arrow { width: 36px; height: 36px; font-size: 14px; }
            .diapo-arrow.prev { left: 10px; }
            .diapo-arrow.next { right: 10px; }
        }
    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- INTRO NETFLIX (Ta-dum)                        -->
    <!-- ============================================ -->
    <div class="netflix-intro">
        <div class="intro-particles">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>
        <div class="letter">N</div>
    </div>

    <!-- ============================================ -->
    <!-- NAVBAR NETFLIX                                -->
    <!-- ============================================ -->
    <nav class="netflix-navbar" id="netflixNav">
        <a href="#" class="netflix-logo">N</a>
        <ul class="netflix-nav-links">
            <li><a href="#hero">Accueil</a></li>
            <li><a href="#details">Détails</a></li>
            <li><a href="#photos">Photos</a></li>
            <li><a href="#confirm">Confirmer</a></li>
        </ul>
    </nav>

    <!-- ============================================ -->
    <!-- HERO NETFLIX (plein écran)                    -->
    <!-- ============================================ -->
    <section class="netflix-hero" id="hero">
        
        <!-- Fond image avec effet Ken Burns -->
        <div class="hero-bg" style="<?php if (!empty($pageBackground)): ?>background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');<?php else: ?>background: linear-gradient(135deg, #1a0000 0%, #000 50%, #1a0000 100%);<?php endif; ?>"></div>
        
        <!-- Contenu -->
        <div class="hero-content">
            
            <!-- Badge N SERIES -->
            <div class="netflix-badge">
                <span class="n-icon">N</span>
                INVITATION ORIGINALE
            </div>
            
            <!-- Titre -->
            <h1 class="hero-title">
                <?php echo htmlspecialchars(strtoupper($host1)); ?><br>
                <span class="red-accent"><?php echo htmlspecialchars(strtoupper($eventType)); ?></span>
            </h1>
            
            <!-- Métadonnées Netflix -->
            <div class="hero-meta">
                <span class="match"><?php echo rand(94, 99); ?>% pour vous</span>
                <span class="year"><?php echo date('Y', strtotime($invitation['date_evenement'])); ?></span>
                <span class="badge-age">+<?php echo $invitation['nb_places_max'] ?? 1; ?></span>
                <span class="badge-hd">HD</span>
                <span><?php echo htmlspecialchars(strtoupper($eventType)); ?></span>
                
                <!-- Badge Table -->
                <?php if (!empty($hasTable)): ?>
                    <span class="badge-table">
                        <i class="fas fa-chair"></i>
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                        <?php if (!empty($tableZone)): ?>
                            · <?php echo htmlspecialchars($tableZone); ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Synopsis personnalisé -->
            <p class="hero-synopsis">
                C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <strong><?php echo htmlspecialchars($eventType); ?></strong>.
                <br><br>
                Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.
            </p>
            
            <!-- Boutons d'action -->
            <div class="hero-actions">
                <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
                    <a href="#confirm" class="nf-btn nf-btn-play">
                        <i class="fas fa-play"></i>
                        Confirmer ma présence
                    </a>
                <?php endif; ?>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="nf-btn nf-btn-info">
                    <i class="fas fa-map-marked-alt"></i>
                    Itinéraire
                </a>
            </div>
            
        </div>
    </section>

    <!-- ============================================ -->
    <!-- ROW : DÉTAILS DE L'ÉVÉNEMENT (style Netflix)  -->
    <!-- ============================================ -->
    <section class="nf-row apparue" id="details">
        <div class="nf-row-title">
            <i class="fas fa-film" style="color: var(--nf-red);"></i>
            Détails de la séance
            <span class="explore">Tout explorer <i class="fas fa-chevron-right"></i></span>
        </div>
        
        <div class="nf-row-scroller">
            
            <!-- Carte Date -->
            <div class="nf-card">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#2a0a0a 0%,#000 100%);display:flex;align-items:center;justify-content:center;color:var(--nf-red);font-size:60px;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="nf-card-overlay" style="opacity:1;">
                    <div class="nf-card-title">DATE</div>
                    <div class="nf-card-sub"><?php echo htmlspecialchars($eventDate); ?></div>
                    <?php if ($eventTime): ?>
                        <div class="nf-card-sub">à <?php echo htmlspecialchars($eventTime); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Carte Lieu -->
            <div class="nf-card">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#0a0a2a 0%,#000 100%);display:flex;align-items:center;justify-content:center;color:var(--nf-red);font-size:60px;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="nf-card-overlay" style="opacity:1;">
                    <div class="nf-card-title">LIEU</div>
                    <div class="nf-card-sub"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                    <?php if ($adresseDisplay): ?>
                        <div class="nf-card-sub" style="font-size:10px;"><?php echo htmlspecialchars($adresseDisplay); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Carte Invité -->
            <div class="nf-card">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#2a0a1a 0%,#000 100%);display:flex;align-items:center;justify-content:center;color:var(--nf-red);font-size:60px;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="nf-card-overlay" style="opacity:1;">
                    <div class="nf-card-title">INVITÉ</div>
                    <div class="nf-card-sub"><?php echo htmlspecialchars($guestName); ?></div>
                </div>
            </div>
            
            <!-- Carte Places -->
            <div class="nf-card">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a2a0a 0%,#000 100%);display:flex;align-items:center;justify-content:center;color:var(--nf-red);font-size:60px;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="nf-card-overlay" style="opacity:1;">
                    <div class="nf-card-title">PLACES</div>
                    <div class="nf-card-sub"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
                </div>
            </div>

            <!-- Carte Table (si assignée) -->
            <?php if (!empty($hasTable)): ?>
            <div class="nf-card">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#0a2a1a 0%,#000 100%);display:flex;align-items:center;justify-content:center;color:var(--nf-green);font-size:60px;">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="nf-card-overlay" style="opacity:1;">
                    <div class="nf-card-title">VOTRE TABLE</div>
                    <div class="nf-card-sub"><?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?></div>
                    <?php if (!empty($tableZone)): ?>
                        <div class="nf-card-sub" style="font-size:10px;">Zone <?php echo htmlspecialchars($tableZone); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>

    <!-- ============================================ -->
    <!-- SECTION PHOTOS (diaporama plein écran)        -->
    <!-- ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <section class="nf-section" id="photos">
            <div class="nf-section-title">
                <i class="fas fa-images"></i>
                Souvenirs
                <div class="line"></div>
            </div>
            
            <div class="photo-diaporama" id="photoDiaporama">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="diapo-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                             loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#E50914;font-size:80px;\'><i class=\'fas fa-film\'></i></div>';">
                    </div>
                <?php endforeach; ?>
                
                <!-- Flèches de navigation -->
                <button class="diapo-arrow prev" onclick="changeSlide(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="diapo-arrow next" onclick="changeSlide(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Points de navigation -->
                <div class="diapo-nav">
                    <?php foreach ($photosHost as $index => $photo): ?>
                        <button class="diapo-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                onclick="goToSlide(<?php echo $index; ?>)"
                                aria-label="Photo <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- MESSAGES                                       -->
    <!-- ============================================ -->
    <?php if ($message): ?>
        <section class="nf-section">
            <div class="nf-alert nf-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- QR CODE                                        -->
    <!-- ============================================ -->
    <section class="nf-section">
        <div class="nf-section-title">
            <i class="fas fa-qrcode"></i>
            Code d'accès
            <div class="line"></div>
        </div>
        
        <div class="nf-qr-wrapper">
            <div class="nf-qr-box">
                <div id="qrcode"></div>
            </div>
            <div class="nf-qr-label"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- CONFIRMATION                                   -->
    <!-- ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <section class="nf-section" id="confirm">
            <div class="nf-section-title">
                <i class="fas fa-check-circle"></i>
                Confirmer votre présence
                <div class="line"></div>
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer" class="nf-form">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="nf-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="nf-form-group">
                    <label>Votre réponse</label>
                    <div class="nf-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="nf-option-radio">
                            <label for="presenceOui" class="nf-option-label">
                                <i class="fas fa-play"></i> Je serai là
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="nf-option-radio">
                            <label for="presenceNon" class="nf-option-label">
                                <i class="fas fa-times"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="nf-form-group">
                    <label>Laissez-nous quelques mots. Un vœu, un souvenir, un conseil... Chaque ligne écrite ici restera à jamais dans notre cœur.</label>
                    <textarea name="message_invite" rows="3" placeholder="Un petit mot..."></textarea>
                </div>
                
                <button type="submit" class="nf-btn-submit">
                    <i class="fas fa-check-circle"></i>
                    Confirmer
                </button>
            </form>
        </section>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- BOISSONS                                       -->
    <!-- ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <section class="nf-section">
            <div class="nf-section-title">
                <i class="fas fa-glass-cheers"></i>
                Vos préférences de boissons
                <div class="line"></div>
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--nf-green);font-weight:700;padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                </div>
                <div class="nf-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="nf-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check-circle check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm" class="nf-form">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;color:var(--nf-text-light);margin-bottom:20px;font-size:14px;">
                        Sélectionnez jusqu'à <strong style="color:var(--nf-red);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="nf-boisson-category">
                            <div class="nf-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="nf-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="nf-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="nf-btn-submit" style="margin-top:20px;">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- FOOTER NETFLIX                                 -->
    <!-- ============================================ -->
    <footer class="nf-footer">
        <div class="nf-footer-logo">N</div>
        <div class="nf-footer-tagline">Un événement original</div>
        
        <div class="nf-footer-links">
            <a href="#hero">Accueil</a>
            <a href="#details">Détails</a>
            <a href="#photos">Photos</a>
            <a href="#confirm">Confirmer</a>
        </div>
        
        <a href="https://wa.me/243829018462?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20mon%20invitation" 
           target="_blank" 
           rel="noopener"
           class="nf-btn-whatsapp">
            <i class="fab fa-whatsapp"></i>
            Nous contacter · 0829018462
        </a>
        
        <div class="nf-footer-bottom">
            © <?php echo date('Y'); ?> · Tous droits réservés
        </div>
    </footer>

    <!-- ============================================ -->
    <!-- BOUTON DOWNLOAD                                -->
    <!-- ============================================ -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // 1. NAVBAR SCROLL EFFECT
        // ================================================================
        const nav = document.getElementById('netflixNav');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // ================================================================
        // 2. ANIMATIONS AU SCROLL (comme Netflix rows)
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.nf-row, .nf-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { 
                    if (entry.isIntersecting) { 
                        entry.target.classList.add('apparue'); 
                        observer.unobserve(entry.target);
                    } 
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
            
            sections.forEach(section => observer.observe(section));
        });

        // ================================================================
        // 3. SCROLL HORIZONTAL AVEC MOLETTE (comme Netflix)
        // ================================================================
        document.querySelectorAll('.nf-row-scroller, .photo-diaporama').forEach(scroller => {
            scroller.addEventListener('wheel', function(e) {
                if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                    e.preventDefault();
                    scroller.scrollLeft += e.deltaY;
                }
            }, { passive: false });
        });

        // ================================================================
        // 4. QR CODE
        // ================================================================
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
                } catch(e) {
                    console.error('Erreur QR code:', e);
                }
            }
        });

        // ================================================================
        // 5. DIAPORAMA PHOTOS
        // ================================================================
        let currentSlide = 0;
        let diapoInterval = null;
        const slides = document.querySelectorAll('.diapo-slide');
        const dots = document.querySelectorAll('.diapo-dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            if (totalSlides === 0) return;
            
            // Normaliser l'index
            if (index >= totalSlides) index = 0;
            if (index < 0) index = totalSlides - 1;
            
            // Masquer tous les slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Afficher le slide actif
            if (slides[index]) slides[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
            
            currentSlide = index;
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function changeSlide(direction) {
            if (direction === 1) nextSlide();
            else prevSlide();
            resetDiapoInterval();
        }

        function goToSlide(index) {
            showSlide(index);
            resetDiapoInterval();
        }

        function resetDiapoInterval() {
            if (diapoInterval) clearInterval(diapoInterval);
            if (totalSlides > 1) {
                diapoInterval = setInterval(nextSlide, 5000); // Change toutes les 5 secondes
            }
        }

        // Démarrer le diaporama automatique
        document.addEventListener('DOMContentLoaded', function() {
            if (totalSlides > 1) {
                diapoInterval = setInterval(nextSlide, 5000);
            }
        });

        // Pause au survol
        const diaporama = document.getElementById('photoDiaporama');
        if (diaporama) {
            diaporama.addEventListener('mouseenter', () => {
                if (diapoInterval) clearInterval(diapoInterval);
            });
            diaporama.addEventListener('mouseleave', () => {
                resetDiapoInterval();
            });
        }

        // ================================================================
        // 6. TÉLÉCHARGEMENT JPEG (CORRIGÉ)
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.netflix-hero');
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                // Attendre un peu pour être sûr que tout est chargé
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // S'assurer que les animations sont terminées
                const heroContent = hero.querySelector('.hero-content');
                if (heroContent) {
                    heroContent.style.opacity = '1';
                    heroContent.style.transform = 'translateY(0)';
                }
                
                // Forcer l'affichage des éléments cachés par les animations
                hero.querySelectorAll('.netflix-badge, .hero-title, .hero-meta, .hero-synopsis, .hero-actions').forEach(el => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                    el.style.filter = 'blur(0)';
                    el.style.animation = 'none';
                });
                
                // Capture avec html2canvas
                const canvas = await html2canvas(hero, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#000000',
                    logging: false,
                    windowWidth: hero.scrollWidth,
                    windowHeight: hero.scrollHeight,
                    scrollX: 0,
                    scrollY: -window.scrollY,
                    onclone: function(clonedDoc) {
                        // S'assurer que le background est visible
                        const clonedHero = clonedDoc.querySelector('.netflix-hero');
                        if (clonedHero) {
                            const clonedBg = clonedHero.querySelector('.hero-bg');
                            if (clonedBg) {
                                clonedBg.style.animation = 'none';
                            }
                            const clonedContent = clonedHero.querySelector('.hero-content');
                            if (clonedContent) {
                                clonedContent.style.opacity = '1';
                                clonedContent.style.transform = 'translateY(0)';
                            }
                            // Forcer l'affichage de tous les éléments
                            clonedHero.querySelectorAll('.netflix-badge, .hero-title, .hero-meta, .hero-synopsis, .hero-actions').forEach(el => {
                                el.style.opacity = '1';
                                el.style.transform = 'translateY(0)';
                                el.style.filter = 'blur(0)';
                                el.style.animation = 'none';
                            });
                        }
                    }
                });
                
                const link = document.createElement('a');
                const host = '<?php echo htmlspecialchars($host1); ?>' || 'invitation';
                link.download = `invitation_${host.replace(/\s/g, '_')}_${Date.now()}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                
                btnText.textContent = '✓ Téléchargé';
                setTimeout(() => { btnText.textContent = 'Télécharger'; }, 3000);
            } catch (error) {
                console.error('Erreur:', error);
                btnText.textContent = 'Erreur';
                setTimeout(() => { btnText.textContent = 'Télécharger'; }, 3000);
            }
            
            btn.disabled = false;
        }

        // ================================================================
        // 7. GESTION DES BOISSONS
        // ================================================================
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons) && !$isLocked): ?>
        let selectedBoissons = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nf-boisson-item.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!selectedBoissons.includes(id)) selectedBoissons.push(id);
            });
            updateCount();
        });
        
        function toggleBoisson(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const index = selectedBoissons.indexOf(id);
                if (index > -1) selectedBoissons.splice(index, 1);
                const checkbox = element.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
                updateCount();
                return;
            }
            
            if (selectedBoissons.length >= 2) {
                alert('Vous ne pouvez sélectionner que 2 boissons maximum.');
                return;
            }
            
            element.classList.add('selected');
            selectedBoissons.push(id);
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
            updateCount();
        }
        
        function updateCount() {
            const countEl = document.getElementById('selectedCount');
            if (countEl) countEl.textContent = selectedBoissons.length;
            
            document.querySelectorAll('.nf-boisson-item').forEach(item => {
                if (!item.classList.contains('selected') && selectedBoissons.length >= 2) {
                    item.style.opacity = '0.4';
                    item.style.cursor = 'not-allowed';
                } else {
                    item.style.opacity = '1';
                    item.style.cursor = 'pointer';
                }
            });
        }
        <?php endif; ?>
    </script>

</body>
</html>