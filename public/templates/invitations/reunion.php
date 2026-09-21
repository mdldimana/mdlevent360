<?php
/**
 * ============================================================
 * TEMPLATE : CONFÉRENCE / RÉUNION / SÉMINAIRE - v2
 * ============================================================
 * 
 * Nouveautés v2 :
 * - Suppression du header/navbar
 * - Diaporama photos plein écran (image entière)
 * - Affichage du nom de la table
 * - Définition des variables $hasFond et $hasPhotos
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            /* Palette corporate */
            --navy: #0a2540;
            --navy-dark: #061626;
            --navy-light: #1a3a5c;
            --blue: #2563eb;
            --blue-light: #3b82f6;
            --blue-soft: #eff6ff;
            --gold: #c9a961;
            --gold-light: #e8d4a2;
            --bg: #f8fafc;
            --bg-alt: #f1f5f9;
            --white: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            background-image: 
                linear-gradient(rgba(10, 37, 64, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(10, 37, 64, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* ============================================
           INTRO : LIGNES DE CODE / TITRES
           ============================================ */
        .pro-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: introFadeOut 2.2s ease-in-out 1.8s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        /* Lignes horizontales animées */
        .pro-lines {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .pro-lines span {
            position: absolute;
            left: -100%;
            height: 1px;
            width: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(201, 169, 97, 0.4),
                rgba(37, 99, 235, 0.6),
                rgba(201, 169, 97, 0.4),
                transparent);
            animation: lineSweep 2s ease-out forwards;
        }
        .pro-lines span:nth-child(1) { top: 20%; animation-delay: 0s; }
        .pro-lines span:nth-child(2) { top: 35%; animation-delay: 0.15s; }
        .pro-lines span:nth-child(3) { top: 50%; animation-delay: 0.3s; }
        .pro-lines span:nth-child(4) { top: 65%; animation-delay: 0.45s; }
        .pro-lines span:nth-child(5) { top: 80%; animation-delay: 0.6s; }
        
        @keyframes lineSweep {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .pro-intro-content {
            text-align: center;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: contentFadeIn 1s ease-out 0.8s forwards;
        }
        @keyframes contentFadeIn {
            to { opacity: 1; }
        }
        
        .pro-intro-icon {
            width: 80px;
            height: 80px;
            border: 2px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
            color: var(--gold);
            animation: iconPulse 2s ease-in-out infinite;
        }
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(201, 169, 97, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(201, 169, 97, 0); }
        }
        
        .pro-intro-text {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 400;
            color: var(--white);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        
        .pro-intro-sub {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.5em;
            color: var(--gold);
            text-transform: uppercase;
        }
        
        /* ============================================
           HERO CORPORATE (SANS NAVBAR)
           ============================================ */
        .corporate-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px 80px;
            z-index: 10;
            overflow: hidden;
        }
        
        /* Formes géométriques décoratives */
        .geo-shape {
            position: absolute;
            pointer-events: none;
            z-index: 0;
        }
        .geo-shape.circle {
            width: 400px;
            height: 400px;
            border: 1px solid rgba(37, 99, 235, 0.08);
            border-radius: 50%;
            top: 10%;
            right: -100px;
        }
        .geo-shape.circle.small {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: -50px;
            border-color: rgba(201, 169, 97, 0.15);
        }
        .geo-shape.square {
            width: 150px;
            height: 150px;
            border: 1px solid rgba(201, 169, 97, 0.12);
            top: 25%;
            left: 8%;
            transform: rotate(45deg);
        }
        
        /* Bande dorée verticale */
        .gold-bar {
            position: absolute;
            top: 15%;
            bottom: 15%;
            left: 60px;
            width: 3px;
            background: linear-gradient(180deg, 
                transparent,
                var(--gold) 30%,
                var(--gold) 70%,
                transparent);
            opacity: 0.6;
            z-index: 1;
        }
        .gold-bar.right { left: auto; right: 60px; }
        @media (max-width: 992px) {
            .gold-bar { display: none; }
        }
        
        .corporate-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 900px;
            opacity: 0;
            transform: translateY(30px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.5s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Badge professionnel */
        .corporate-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: 100px;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            color: var(--blue);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .corporate-badge i {
            font-size: 12px;
        }
        
        /* Numéro d'événement */
        .event-number {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-style: italic;
            color: var(--gold);
            letter-spacing: 0.3em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        .event-number::before,
        .event-number::after {
            content: '';
            width: 40px;
            height: 1px;
            background: var(--gold);
        }
        
        /* Nom de l'invité */
        .corporate-guest {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5.5vw, 52px);
            font-weight: 400;
            font-style: italic;
            color: var(--navy);
            letter-spacing: -0.01em;
            line-height: 1.15;
            margin-bottom: 30px;
        }
        
        /* Séparateur corporate */
        .corporate-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        .corporate-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .corporate-divider .diamond {
            width: 8px;
            height: 8px;
            background: var(--gold);
            transform: rotate(45deg);
        }
        
        /* Hôte / événement */
        .corporate-hosts-intro {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 0.4em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .corporate-host-name {
            font-family: 'Playfair Display', serif;
            font-size: clamp(52px, 11vw, 110px);
            font-weight: 400;
            line-height: 0.95;
            letter-spacing: -0.02em;
            color: var(--navy);
            margin-bottom: 20px;
            background: linear-gradient(180deg, 
                var(--navy) 0%, 
                var(--navy-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .corporate-event-type {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            letter-spacing: 0.4em;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 700;
            padding: 12px 28px;
            border: 1px solid rgba(201, 169, 97, 0.4);
            border-radius: 100px;
            background: rgba(201, 169, 97, 0.05);
        }
        .corporate-event-type::before,
        .corporate-event-type::after {
            content: '◆';
            font-size: 10px;
        }
        
        /* ============================================
           CARTE CORPORATE (Détails)
           ============================================ */
        .corporate-card {
            position: relative;
            max-width: 1000px;
            margin: 80px auto;
            padding: 60px 50px;
            background: var(--white);
            border-radius: 24px;
            box-shadow: 
                0 1px 3px rgba(15, 23, 42, 0.04),
                0 20px 60px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--border);
            opacity: 0;
            transform: translateY(40px);
            transition: all 1s ease;
            z-index: 10;
            overflow: hidden;
        }
        .corporate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--navy) 0%,
                var(--blue) 50%,
                var(--gold) 100%);
        }
        .corporate-card.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .corporate-card { padding: 40px 25px; margin: 60px 15px; }
        }
        
        /* Header avec numéro */
        .corporate-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 30px;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border);
            gap: 20px;
            flex-wrap: wrap;
        }
        .corporate-card-header .label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--blue);
            text-transform: uppercase;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .corporate-card-header .label i {
            font-size: 14px;
        }
        .corporate-card-header .ref {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-style: italic;
            color: var(--text-muted);
        }
        
        .corporate-card-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 400;
            color: var(--navy);
            text-align: center;
            margin-bottom: 50px;
            letter-spacing: -0.01em;
        }
        .corporate-card-title strong {
            font-weight: 700;
            color: var(--blue);
        }
        
        /* Grille d'infos */
        .corporate-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .corporate-info-grid { grid-template-columns: 1fr; }
        }
        
        .corporate-info-item {
            display: flex;
            gap: 20px;
            padding: 24px;
            background: var(--bg-alt);
            border-radius: 16px;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }
        .corporate-info-item:hover {
            background: var(--blue-soft);
            border-color: rgba(37, 99, 235, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }
        .corporate-info-item.full {
            grid-column: 1 / -1;
        }
        
        .corporate-info-item .icon {
            width: 48px;
            height: 48px;
            background: var(--white);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--blue);
            font-size: 20px;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }
        .corporate-info-item:hover .icon {
            background: var(--blue);
            color: var(--white);
            border-color: var(--blue);
        }
        
        .corporate-info-item .content {
            flex: 1;
            min-width: 0;
        }
        .corporate-info-item .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .corporate-info-item .value {
            font-family: 'Inter', sans-serif;
            font-size: 17px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.4;
        }
        .corporate-info-item .value .sub {
            display: block;
            font-size: 14px;
            font-weight: 400;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        /* ⭐ CARTE TABLE */
        .corporate-table-item {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(201, 169, 97, 0.08)) !important;
            border: 1.5px solid rgba(37, 99, 235, 0.3) !important;
        }
        .corporate-table-item .icon {
            background: linear-gradient(135deg, var(--blue), var(--blue-light)) !important;
            color: var(--white) !important;
            border-color: var(--blue) !important;
            animation: tableIconPulse 3s ease-in-out infinite;
        }
        @keyframes tableIconPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
        }
        
        /* Bouton itinéraire corporate */
        .corporate-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 10px 22px;
            background: var(--navy);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-decoration: none;
            border-radius: 100px;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .corporate-btn-itinerary:hover {
            background: var(--blue);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        /* ============================================
           SECTIONS CORPORATE
           ============================================ */
        .corporate-section {
            position: relative;
            max-width: 1000px;
            margin: 60px auto;
            padding: 60px 50px;
            background: var(--white);
            border-radius: 24px;
            box-shadow: 
                0 1px 3px rgba(15, 23, 42, 0.04),
                0 20px 60px rgba(15, 23, 42, 0.06);
            border: 1px solid var(--border);
            opacity: 0;
            transform: translateY(40px);
            transition: all 1s ease;
            z-index: 10;
            overflow: hidden;
        }
        .corporate-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, 
                var(--navy) 0%,
                var(--blue) 50%,
                var(--gold) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 1s ease 0.3s;
        }
        .corporate-section.apparue::before {
            transform: scaleX(1);
        }
        .corporate-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .corporate-section { padding: 40px 25px; margin: 45px 15px; }
        }
        
        .corporate-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 400;
            color: var(--navy);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            letter-spacing: -0.01em;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        .corporate-section-title::before,
        .corporate-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
        }
        .corporate-section-title .icon {
            color: var(--gold);
            font-size: 22px;
            background: var(--white);
            padding: 0 8px;
        }
        
        /* ============================================
           DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .corporate-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 16/10;
            overflow: hidden;
            border-radius: 20px;
            background: var(--navy-dark);
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
        }
        
        .corporate-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 0;
            background: var(--navy-dark);
        }
        
        .corporate-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .corporate-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--navy-dark);
            padding: 10px;
        }
        
        /* Flèches navigation */
        .corporate-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border);
            color: var(--navy);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
        }
        
        .corporate-diapo-arrow:hover {
            background: var(--blue);
            color: white;
            border-color: var(--blue);
            transform: translateY(-50%) scale(1.1);
        }
        
        .corporate-diapo-arrow.prev { left: 16px; }
        .corporate-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .corporate-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .corporate-diapo-arrow.prev { left: 8px; }
            .corporate-diapo-arrow.next { right: 8px; }
        }
        
        /* Compteur */
        .corporate-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(10, 37, 64, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 8px 16px;
            border-radius: 100px;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        
        /* Points de pagination */
        .corporate-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(10, 37, 64, 0.85);
            padding: 10px 20px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .corporate-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .corporate-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
            box-shadow: 0 0 12px rgba(201, 169, 97, 0.8);
        }
        
        /* ============================================
           FORMULAIRES CORPORATE
           ============================================ */
        .corporate-form-group { margin-bottom: 26px; }
        .corporate-form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            color: var(--navy);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .corporate-form-group label i {
            color: var(--blue);
            margin-right: 8px;
        }
        .corporate-form-group input,
        .corporate-form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: var(--bg-alt);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .corporate-form-group input:focus,
        .corporate-form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .corporate-form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .corporate-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 480px) {
            .corporate-options-grid { grid-template-columns: 1fr; }
        }
        
        .corporate-option-radio { display: none; }
        .corporate-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .corporate-option-label:hover {
            border-color: var(--blue);
            color: var(--blue);
            background: var(--blue-soft);
        }
        .corporate-option-radio:checked + .corporate-option-label {
            border-color: var(--blue);
            background: var(--blue-soft);
            color: var(--blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .corporate-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 8px 24px rgba(10, 37, 64, 0.25);
        }
        .corporate-btn-submit:hover {
            background: linear-gradient(135deg, var(--blue), var(--blue-light));
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.4);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .corporate-boisson-category { margin-bottom: 28px; }
        .corporate-boisson-category-title {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--navy);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .corporate-boisson-category-title i {
            color: var(--blue);
        }
        
        .corporate-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .corporate-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border: 1.5px solid var(--border);
            border-radius: 100px;
            background: var(--white);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .corporate-boisson-item.selected {
            border-color: var(--blue);
            background: var(--blue-soft);
            color: var(--blue);
        }
        .corporate-boisson-item .check {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .corporate-boisson-item.selected .check { opacity: 1; }
        
        /* ============================================
           QR CODE
           ============================================ */
        .corporate-qr-wrapper { text-align: center; }
        .corporate-qr-box {
            display: inline-block;
            padding: 24px;
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.1);
            position: relative;
        }
        .corporate-qr-box::before {
            content: '';
            position: absolute;
            top: -1.5px;
            left: 30px;
            right: 30px;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent,
                var(--blue),
                var(--gold),
                var(--blue),
                transparent);
            border-radius: 3px;
        }
        .corporate-qr-label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-top: 20px;
        }
        
        /* ============================================
           FOOTER CORPORATE
           ============================================ */
        .corporate-footer {
            padding: 80px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
            border-top: 1px solid var(--border);
            margin-top: 80px;
            background: var(--white);
        }
        .corporate-footer-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .corporate-footer-brand .brand-mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: var(--white);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
        }
        .corporate-footer-tagline {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 0.3em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 36px;
            font-weight: 500;
        }
        
        .corporate-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-decoration: none;
            border-radius: 100px;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        .corporate-btn-whatsapp:hover {
            background: linear-gradient(135deg, var(--blue), var(--blue-light));
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.4);
        }
        
        /* ============================================
           ALERTES
           ============================================ */
        .corporate-alert {
            padding: 18px 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            border-radius: 12px;
            border-left: 4px solid;
        }
        .corporate-alert-success { 
            border-color: var(--success); 
            background: rgba(16, 185, 129, 0.08); 
            color: #065f46; 
        }
        .corporate-alert-danger { 
            border-color: #ef4444; 
            background: rgba(239, 68, 68, 0.08); 
            color: #991b1b; 
        }
        .corporate-alert-warning { 
            border-color: #f59e0b; 
            background: rgba(245, 158, 11, 0.08); 
            color: #78350f; 
        }
        
        /* ============================================
           DOWNLOAD
           ============================================ */
        #downloadBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 16px 30px;
            background: var(--navy);
            color: var(--white);
            border: none;
            border-radius: 100px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 12px 32px rgba(10, 37, 64, 0.3);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            background: var(--blue);
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(37, 99, 235, 0.4);
        }
        @media (max-width: 480px) {
            #downloadBtn { 
                bottom: 16px; 
                right: 16px; 
                padding: 12px 22px; 
                font-size: 11px;
            }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- ============================================
         INTRO CORPORATE
         ============================================ -->
    <div class="pro-intro">
        <div class="pro-lines">
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="pro-intro-content">
            <div class="pro-intro-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="pro-intro-text">Conférence</div>
            <div class="pro-intro-sub">INVITATION PROFESSIONNELLE</div>
        </div>
    </div>

    <!-- ============================================
         HERO CORPORATE (SANS NAVBAR)
         ============================================ -->
    <section class="corporate-hero">
        <div class="geo-shape circle"></div>
        <div class="geo-shape circle small"></div>
        <div class="geo-shape square"></div>
        <div class="gold-bar"></div>
        <div class="gold-bar right"></div>
        
        <div class="corporate-blason">
            
            <div class="corporate-badge">
                <i class="fas fa-certificate"></i>
                ÉVÉNEMENT PROFESSIONNEL
            </div>
            
            <div class="event-number">Invitation personnelle</div>
            
            <div class="corporate-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="corporate-divider">
                <div class="line"></div>
                <div class="diamond"></div>
                <div class="line"></div>
            </div>
            
            <div class="corporate-hosts-intro">
                Vous êtes cordialement invité(e) à
            </div>
            <div class="corporate-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="corporate-event-type">
                <?php echo htmlspecialchars(strtoupper($eventType)); ?>
            </div>
            
        </div>
    </section>

    <!-- ============================================
         CARTE CORPORATE (Détails)
         ============================================ -->
    <div class="corporate-card">
        
        <div class="corporate-card-header">
            <div class="label">
                <i class="fas fa-clipboard-list"></i>
                Informations pratiques
            </div>
            <div class="ref">Réf. <?php echo htmlspecialchars($invitation['code_unique']); ?></div>
        </div>
        
        <div class="corporate-card-title">
            Détails de <strong>l'événement</strong>
        </div>
        
        <div class="corporate-info-grid">
            
            <div class="corporate-info-item">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="content">
                    <div class="label">Date</div>
                    <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                </div>
            </div>
            
            <div class="corporate-info-item">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="content">
                    <div class="label">Heure</div>
                    <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                </div>
            </div>
            
            <div class="corporate-info-item full">
                <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="content">
                    <div class="label">Lieu</div>
                    <div class="value">
                        <?php echo htmlspecialchars($lieuDisplay); ?>
                        <?php if ($adresseDisplay): ?>
                            <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="corporate-btn-itinerary">
                        <i class="fas fa-route"></i> Voir l'itinéraire
                    </a>
                </div>
            </div>
            
            <!-- ⭐ TABLE ASSIGNÉE -->
            <?php if ($hasTable): ?>
            <div class="corporate-info-item full corporate-table-item">
                <div class="icon"><i class="fas fa-chair"></i></div>
                <div class="content">
                    <div class="label">Votre table</div>
                    <div class="value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="corporate-info-item full">
                <div class="icon"><i class="fas fa-user-tie"></i></div>
                <div class="content">
                    <div class="label">Accréditations</div>
                    <div class="value">
                        <?php echo (int)($invitation['nb_places_max'] ?? 1); ?> place<?php echo ($invitation['nb_places_max'] ?? 1) > 1 ? 's' : ''; ?> réservée<?php echo ($invitation['nb_places_max'] ?? 1) > 1 ? 's' : ''; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="corporate-section apparue">
            <div class="corporate-alert corporate-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         ⭐ DIAPORAMA PHOTOS PLEIN ÉCRAN
         ============================================ -->
    <?php if ($hasPhotos): ?>
        <div class="corporate-section">
            <div class="corporate-section-title">
                <span class="icon"><i class="fas fa-images"></i></span>
                Galerie
                <span class="icon"><i class="fas fa-images"></i></span>
            </div>
            
            <div class="corporate-diaporama" id="corporateDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                             loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                             crossorigin="anonymous">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
                
                <?php if ($photoIndex > 1): ?>
                    <button class="corporate-diapo-arrow prev" onclick="corporateDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="corporate-diapo-arrow next" onclick="corporateDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="corporate-diapo-counter" id="corporateDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="corporate-diapo-dots" id="corporateDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="corporateDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="corporate-section">
        <div class="corporate-section-title">
            <span class="icon"><i class="fas fa-qrcode"></i></span>
            Votre badge d'accès
            <span class="icon"><i class="fas fa-qrcode"></i></span>
        </div>
        <div class="corporate-qr-wrapper">
            <div class="corporate-qr-box">
                <div id="qrcode"></div>
            </div>
            <div class="corporate-qr-label">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="corporate-section">
            <div class="corporate-section-title">
                <span class="icon"><i class="fas fa-check-circle"></i></span>
                Confirmer votre présence
                <span class="icon"><i class="fas fa-check-circle"></i></span>
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="corporate-form-group">
                    <label><i class="fas fa-users"></i> Nombre de participants</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>" placeholder="Nombre de personnes">
                </div>
                
                <div class="corporate-form-group">
                    <label><i class="fas fa-check-square"></i> Votre réponse</label>
                    <div class="corporate-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="corporate-option-radio">
                            <label for="presenceOui" class="corporate-option-label">
                                <i class="fas fa-check"></i> Je confirme
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="corporate-option-radio">
                            <label for="presenceNon" class="corporate-option-label">
                                <i class="fas fa-times"></i> Absent(e)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="corporate-form-group">
                    <label><i class="fas fa-comment"></i> Message (optionnel)</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message pour les organisateurs..."></textarea>
                </div>
                
                <button type="submit" class="corporate-btn-submit">
                    <i class="fas fa-paper-plane"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="corporate-section">
            <div class="corporate-section-title">
                <span class="icon"><i class="fas fa-coffee"></i></span>
                Pause café & rafraîchissements
                <span class="icon"><i class="fas fa-coffee"></i></span>
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;font-family:'Inter',sans-serif;font-size:14px;color:var(--success);font-weight:600;padding:20px 0;">
                    <i class="fas fa-check-circle"></i> Vos préférences sont enregistrées
                </div>
                <div class="corporate-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="corporate-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Inter',sans-serif;font-size:13px;color:var(--text-muted);margin-bottom:30px;font-weight:500;">
                        Sélectionnez jusqu'à <strong style="color:var(--blue);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="corporate-boisson-category">
                            <div class="corporate-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="corporate-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="corporate-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="corporate-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer mes préférences
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER CORPORATE
         ============================================ -->
    <footer class="corporate-footer">
        <div class="corporate-footer-brand">
            <div class="brand-mark">M</div>
            <?php echo htmlspecialchars($appName); ?>
        </div>
        <div class="corporate-footer-tagline">
            Solutions événementielles professionnelles
        </div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="corporate-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:40px;padding-top:30px;border-top:1px solid var(--border);font-family:'Inter',sans-serif;font-size:11px;color:var(--text-light);letter-spacing:0.2em;text-transform:uppercase;font-weight:600;">
            © <?php echo date('Y'); ?> · Tous droits réservés
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
            const sections = document.querySelectorAll('.corporate-card, .corporate-section');
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
                        colorDark: '#0a2540',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA PHOTOS
        // ================================================================
        let corporateDiapoIndex = 0;
        const corporateSlides = document.querySelectorAll('#corporateDiaporama .slide');
        const corporateDots = document.querySelectorAll('#corporateDiapoDots span');
        const corporateCounter = document.getElementById('corporateDiapoCounter');
        let corporateDiapoInterval = null;

        function corporateUpdateDiapo() {
            corporateSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === corporateDiapoIndex);
            });
            corporateDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === corporateDiapoIndex);
            });
            if (corporateCounter) {
                corporateCounter.textContent = (corporateDiapoIndex + 1) + ' / ' + corporateSlides.length;
            }
        }

        function corporateDiapoChange(direction) {
            corporateDiapoIndex += direction;
            if (corporateDiapoIndex < 0) corporateDiapoIndex = corporateSlides.length - 1;
            if (corporateDiapoIndex >= corporateSlides.length) corporateDiapoIndex = 0;
            corporateUpdateDiapo();
            resetCorporateDiapoAuto();
        }

        function corporateDiapoGoTo(index) {
            corporateDiapoIndex = index;
            corporateUpdateDiapo();
            resetCorporateDiapoAuto();
        }

        function resetCorporateDiapoAuto() {
            if (corporateDiapoInterval) clearInterval(corporateDiapoInterval);
            if (corporateSlides.length > 1) {
                corporateDiapoInterval = setInterval(() => {
                    corporateDiapoIndex = (corporateDiapoIndex + 1) % corporateSlides.length;
                    corporateUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (corporateSlides.length > 0) {
                corporateUpdateDiapo();
                resetCorporateDiapoAuto();
                
                const container = document.getElementById('corporateDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (corporateDiapoInterval) clearInterval(corporateDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetCorporateDiapoAuto);
                }
            }
        });

        // Download
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.corporate-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5,
                    useCORS: true,
                    backgroundColor: '#f8fafc',
                    logging: false
                });
                const link = document.createElement('a');
                link.download = `conference_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.corporate-boisson-item.selected').forEach(item => {
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