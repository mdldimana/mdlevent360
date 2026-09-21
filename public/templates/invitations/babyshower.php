<?php
/**
 * ============================================================
 * TEMPLATE : OURSON / BABY SHOWER - v3
 * ============================================================
 * 
 * Nouveautés v3 :
 * - Suppression du header/navbar
 * - Affichage du nom de la table
 * - Animations d'affichage des sections
 * - Photo de fond comme background (non flou)
 * - Diaporama photos plein écran
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);
$mainPhoto = $pageBackground ?: ($hasPhotos ? getPhotoUrl($photosHost[0]['photo']) : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&family=Baloo+2:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --cream: #fff9f0;
            --beige: #f5e6d3;
            --beige-dark: #e8d4bc;
            --brown: #8b6f47;
            --brown-light: #c4a77d;
            --brown-dark: #5d4a2f;
            --blue-baby: #a8d5e2;
            --blue-soft: #d4eaf0;
            --pink-baby: #f5c6d6;
            --pink-soft: #fce4ec;
            --mint: #c5e8d5;
            --peach: #ffd6ba;
            --text: #5d4a2f;
            --text-muted: #9d8570;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        /* ============================================
           PHOTO DE FOND (non floue)
           ============================================ */
        body {
            font-family: 'Fredoka', system-ui, sans-serif;
            background: linear-gradient(180deg, 
                #fff9f0 0%, 
                #fce4ec 50%,
                #d4eaf0 100%);
            background-attachment: fixed;
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* Overlay photo de fond */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            opacity: 0.35;
        }
        
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            background: linear-gradient(180deg, 
                rgba(255, 249, 240, 0.7) 0%, 
                rgba(252, 228, 236, 0.65) 50%,
                rgba(212, 234, 240, 0.7) 100%);
            pointer-events: none;
        }
        
        /* ============================================
           INTRO : OURS EN PELUCHE
           ============================================ */
        .bear-intro {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(180deg, #fff9f0 0%, #fce4ec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introFadeOut 2.5s ease-in-out 2s forwards;
        }
        @keyframes introFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .bear-intro-icon {
            font-size: 140px;
            filter: drop-shadow(0 20px 40px rgba(139, 111, 71, 0.3));
            animation: bearAppear 1.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes bearAppear {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            60% { transform: scale(1.15) rotate(10deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        .balloon {
            position: absolute;
            font-size: 40px;
            opacity: 0;
            animation: balloonRise 3s ease-out forwards;
        }
        .balloon:nth-child(1) { left: 15%; animation-delay: 0.3s; }
        .balloon:nth-child(2) { left: 35%; animation-delay: 0.5s; }
        .balloon:nth-child(3) { left: 55%; animation-delay: 0.7s; }
        .balloon:nth-child(4) { left: 75%; animation-delay: 0.9s; }
        @keyframes balloonRise {
            0% { bottom: 0; opacity: 1; transform: translateY(0); }
            100% { bottom: 100%; opacity: 0; transform: translateY(-100px); }
        }
        
        /* ============================================
           DÉCORS FLOTTANTS
           ============================================ */
        .float-decor {
            position: fixed;
            font-size: 30px;
            opacity: 0.5;
            pointer-events: none;
            z-index: 1;
            animation: floatUpDown 4s ease-in-out infinite;
        }
        .float-decor.b1 { top: 10%; left: 8%; animation-delay: 0s; }
        .float-decor.b2 { top: 25%; right: 10%; animation-delay: 1s; }
        .float-decor.b3 { top: 55%; left: 5%; animation-delay: 2s; }
        .float-decor.b4 { bottom: 20%; right: 8%; animation-delay: 0.5s; }
        .float-decor.b5 { bottom: 10%; left: 40%; animation-delay: 1.5s; }
        
        @keyframes floatUpDown {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }
        
        /* ============================================
           ANIMATIONS D'AFFICHAGE DES SECTIONS
           ============================================ */
        .bear-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .bear-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .bear-anim.from-left {
            opacity: 0;
            transform: translateX(-80px);
        }
        .bear-anim.from-left.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .bear-anim.from-right {
            opacity: 0;
            transform: translateX(80px);
        }
        .bear-anim.from-right.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .bear-anim.zoom-in {
            opacity: 0;
            transform: scale(0.85);
        }
        .bear-anim.zoom-in.apparue {
            opacity: 1;
            transform: scale(1);
        }
        
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           HERO (SANS NAVBAR)
           ============================================ */
        .bear-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px 60px;
            z-index: 10;
        }
        
        .bear-blason {
            position: relative;
            z-index: 3;
            text-align: center;
            max-width: 800px;
            opacity: 0;
            transform: translateY(40px);
            animation: heroContentIn 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 2.7s forwards;
        }
        @keyframes heroContentIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .bear-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--beige), var(--beige-dark));
            border: 8px solid white;
            box-shadow: 
                0 20px 60px rgba(139, 111, 71, 0.2),
                0 0 0 8px var(--beige-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 90px;
            position: relative;
            animation: bearCircleBounce 3s ease-in-out infinite;
        }
        @keyframes bearCircleBounce {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }
        
        .bear-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--pink-soft), var(--blue-soft));
            border: 2px solid var(--pink-baby);
            border-radius: 999px;
            font-family: 'Fredoka', sans-serif;
            font-size: 13px;
            letter-spacing: 0.1em;
            color: var(--brown-dark);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 30px;
            box-shadow: 0 6px 20px rgba(245, 198, 214, 0.3);
        }
        
        .bear-guest {
            font-family: 'Baloo 2', cursive;
            font-size: clamp(36px, 7vw, 60px);
            font-weight: 800;
            background: linear-gradient(135deg, var(--brown) 0%, var(--brown-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            margin-bottom: 30px;
        }
        
        .bear-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        .bear-divider .line {
            flex: 1;
            height: 3px;
            border-radius: 2px;
            background: repeating-linear-gradient(90deg, var(--beige-dark) 0, var(--beige-dark) 8px, transparent 8px, transparent 16px);
        }
        .bear-divider .icon {
            font-size: 28px;
            animation: iconWiggle 2s ease-in-out infinite;
        }
        @keyframes iconWiggle {
            0%, 100% { transform: rotate(-8deg); }
            50% { transform: rotate(8deg); }
        }
        
        .bear-hosts-intro {
            font-family: 'Fredoka', sans-serif;
            font-size: 14px;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .bear-host-name {
            font-family: 'Baloo 2', cursive;
            font-size: clamp(52px, 11vw, 96px);
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, 
                var(--pink-baby) 0%, 
                var(--blue-baby) 50%,
                var(--mint) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: bearGradient 6s ease-in-out infinite;
            margin-bottom: 16px;
        }
        @keyframes bearGradient {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }
        
        .bear-event-type {
            font-family: 'Fredoka', sans-serif;
            font-size: 18px;
            letter-spacing: 0.15em;
            color: var(--brown);
            text-transform: uppercase;
            font-weight: 500;
        }
        
        /* ============================================
           CARTE ARRONDIE
           ============================================ */
        .bear-card {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            box-shadow: 
                0 20px 60px rgba(139, 111, 71, 0.15),
                0 0 0 8px rgba(255, 249, 240, 0.8);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .bear-card { padding: 40px 22px; margin: 40px 15px; }
        }
        
        .bear-card-title {
            font-family: 'Baloo 2', cursive;
            font-size: 32px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 24px;
            color: var(--brown);
            border-bottom: 3px dashed var(--beige-dark);
        }
        
        .bear-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 640px) {
            .bear-info-grid { grid-template-columns: 1fr; }
        }
        
        .bear-info-item {
            padding: 24px 20px;
            background: linear-gradient(135deg, var(--cream), white);
            border-radius: 24px;
            border: 3px solid var(--beige);
            transition: all 0.3s ease;
            text-align: center;
        }
        .bear-info-item:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 15px 40px rgba(139, 111, 71, 0.15);
            border-color: var(--pink-baby);
        }
        
        .bear-info-item .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pink-soft), var(--blue-soft));
            color: var(--brown);
            font-size: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 6px 20px rgba(245, 198, 214, 0.4);
        }
        .bear-info-item .label {
            font-family: 'Fredoka', sans-serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .bear-info-item .value {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .bear-info-item .value .sub {
            display: block;
            font-family: 'Fredoka', sans-serif;
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 500;
        }
        
        /* ⭐ CARTE TABLE */
        .bear-table-item {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--pink-soft), var(--blue-soft)) !important;
            border-color: var(--pink-baby) !important;
        }
        
        .bear-table-item .icon {
            animation: tableGlow 3s ease-in-out infinite;
        }
        
        @keyframes tableGlow {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        
        .bear-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--blue-baby), var(--pink-baby));
            color: white;
            font-family: 'Fredoka', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-decoration: none;
            border-radius: 999px;
            box-shadow: 0 8px 24px rgba(168, 213, 226, 0.4);
            transition: all 0.3s ease;
        }
        .bear-btn-itinerary:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 32px rgba(168, 213, 226, 0.6);
            color: white;
        }
        
        /* ============================================
           SECTIONS
           ============================================ */
        .bear-section {
            position: relative;
            max-width: 900px;
            margin: 60px auto;
            padding: 50px 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            box-shadow: 0 20px 60px rgba(139, 111, 71, 0.12);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .bear-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .bear-section-title {
            font-family: 'Baloo 2', cursive;
            font-size: 28px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            color: var(--brown);
            border-bottom: 3px dashed var(--beige-dark);
        }
        
        /* ============================================
           DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .bear-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: #000;
            border-radius: 24px;
            border: 4px solid var(--beige);
            box-shadow: 
                0 0 0 4px white,
                0 12px 40px rgba(139, 111, 71, 0.2);
        }
        
        .bear-diaporama .slide {
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
        
        .bear-diaporama .slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .bear-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
            padding: 8px;
        }
        
        .bear-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 249, 240, 0.9);
            border: 3px solid var(--pink-baby);
            color: var(--brown);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 111, 71, 0.2);
        }
        
        .bear-diapo-arrow:hover {
            background: var(--pink-baby);
            color: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        .bear-diapo-arrow.prev { left: 16px; }
        .bear-diapo-arrow.next { right: 16px; }
        
        @media (max-width: 480px) {
            .bear-diapo-arrow { width: 38px; height: 38px; font-size: 14px; }
            .bear-diapo-arrow.prev { left: 8px; }
            .bear-diapo-arrow.next { right: 8px; }
        }
        
        .bear-diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(255, 249, 240, 0.95);
            border: 2px solid var(--pink-baby);
            color: var(--brown);
            font-family: 'Baloo 2', cursive;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding: 6px 14px;
            border-radius: 999px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(139, 111, 71, 0.2);
        }
        
        .bear-diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(255, 249, 240, 0.9);
            padding: 8px 16px;
            border-radius: 999px;
            border: 2px solid var(--beige-dark);
        }
        
        .bear-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--beige-dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .bear-diapo-dots span.active {
            background: var(--pink-baby);
            transform: scale(1.4);
            box-shadow: 0 0 8px rgba(245, 198, 214, 0.8);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .bear-form-group { margin-bottom: 24px; }
        .bear-form-group label {
            display: block;
            font-family: 'Fredoka', sans-serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            color: var(--brown);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .bear-form-group input,
        .bear-form-group textarea {
            width: 100%;
            padding: 16px 22px;
            background: var(--cream);
            border: 3px solid var(--beige);
            border-radius: 20px;
            color: var(--text);
            font-family: 'Fredoka', sans-serif;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .bear-form-group input:focus,
        .bear-form-group textarea:focus {
            outline: none;
            border-color: var(--pink-baby);
            background: white;
            box-shadow: 0 0 0 4px rgba(245, 198, 214, 0.2);
        }
        
        .bear-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .bear-options-grid { grid-template-columns: 1fr; }
        }
        
        .bear-option-radio { display: none; }
        .bear-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 3px solid var(--beige);
            border-radius: 20px;
            background: var(--cream);
            font-family: 'Baloo 2', cursive;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bear-option-label:hover {
            border-color: var(--pink-baby);
            transform: translateY(-2px);
            color: var(--brown);
        }
        .bear-option-radio:checked + .bear-option-label {
            border-color: var(--pink-baby);
            background: linear-gradient(135deg, var(--pink-soft), var(--blue-soft));
            color: var(--brown-dark);
            box-shadow: 0 0 0 4px rgba(245, 198, 214, 0.2);
        }
        
        .bear-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--pink-baby), var(--blue-baby));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Baloo 2', cursive;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 12px 32px rgba(245, 198, 214, 0.5);
            margin-top: 10px;
        }
        .bear-btn-submit:hover {
            background-position: 100% center;
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(245, 198, 214, 0.6);
        }
        
        /* Boissons */
        .bear-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .bear-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 3px solid var(--beige);
            border-radius: 999px;
            background: var(--cream);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Fredoka', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .bear-boisson-item.selected {
            border-color: var(--pink-baby);
            background: linear-gradient(135deg, var(--pink-soft), var(--blue-soft));
            color: var(--brown-dark);
        }
        .bear-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .bear-boisson-item.selected .check { opacity: 1; }
        
        .bear-boisson-category { margin-bottom: 20px; }
        .bear-boisson-category-title {
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            color: var(--brown);
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        /* QR */
        .bear-qr-wrapper { text-align: center; }
        .bear-qr-box {
            display: inline-block;
            padding: 22px;
            background: white;
            border-radius: 24px;
            border: 4px solid var(--beige);
            box-shadow: 0 12px 40px rgba(139, 111, 71, 0.2);
            position: relative;
        }
        .bear-qr-box::before {
            content: '🐻';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 40px;
            animation: bearBounceSmall 2s ease-in-out infinite;
        }
        @keyframes bearBounceSmall {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-6px); }
        }
        
        /* Footer */
        .bear-footer {
            padding: 60px 40px 40px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .bear-footer-brand {
            font-family: 'Baloo 2', cursive;
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--pink-baby), var(--blue-baby));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% auto;
            animation: bearGradient 6s ease-in-out infinite;
            margin-bottom: 10px;
        }
        .bear-footer-tagline {
            font-family: 'Fredoka', sans-serif;
            font-size: 15px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .bear-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            font-family: 'Fredoka', sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-decoration: none;
            border-radius: 999px;
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.35);
            transition: all 0.3s ease;
        }
        .bear-btn-whatsapp:hover {
            transform: translateY(-3px) scale(1.03);
            color: white;
        }
        
        /* Alertes */
        .bear-alert {
            padding: 18px 26px;
            margin-bottom: 20px;
            display: flex;
            gap: 14px;
            align-items: center;
            font-family: 'Fredoka', sans-serif;
            font-size: 15px;
            font-weight: 500;
            border-radius: 20px;
        }
        .bear-alert-success { background: var(--mint); color: #2d7a45; }
        .bear-alert-danger { background: var(--pink-soft); color: #a01b3d; }
        .bear-alert-warning { background: #fff4c5; color: #806a00; }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--pink-baby), var(--blue-baby));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Fredoka', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 12px 32px rgba(245, 198, 214, 0.5);
            opacity: 0;
            animation: fadeIn 0.8s ease-out 3s forwards;
        }
        #downloadBtn:hover {
            background-position: 100% center;
            transform: translateY(-3px) scale(1.03);
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 20px; font-size: 11px; }
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- INTRO -->
    <div class="bear-intro">
        <div class="balloon">🎈</div>
        <div class="balloon">🎈</div>
        <div class="balloon">🎈</div>
        <div class="balloon">🎈</div>
        <div class="bear-intro-icon">🧸</div>
    </div>

    <!-- Décorations flottantes -->
    <div class="float-decor b1">🧸</div>
    <div class="float-decor b2">🍼</div>
    <div class="float-decor b3">🎈</div>
    <div class="float-decor b4">🧸</div>
    <div class="float-decor b5">☁️</div>

    <!-- HERO (SANS NAVBAR) -->
    <section class="bear-hero">
        <div class="bear-blason">
            
            <div class="bear-circle">🧸</div>
            
            <div class="bear-badge">
                <i class="fas fa-baby"></i>
                INVITATION SPÉCIALE
                <i class="fas fa-baby"></i>
            </div>
            
            <div class="bear-guest">
                <?php echo htmlspecialchars($guestName); ?>
            </div>
            
            <div class="bear-divider">
                <div class="line"></div>
                <span class="icon">🎀</span>
                <div class="line"></div>
            </div>
            
            <div class="bear-hosts-intro">
                🍼 Vous êtes invité(e) à célébrer 🍼
            </div>
            <div class="bear-host-name"><?php echo htmlspecialchars($host1); ?></div>
            <div class="bear-event-type">
                🧸 <?php echo htmlspecialchars($eventType); ?> 🧸
            </div>
            
        </div>
    </section>

    <!-- CARTE DÉTAILS -->
    <div class="bear-card bear-anim zoom-in">
        <div class="bear-card-title">Les détails tendres</div>
        <div class="bear-info-grid">
            
            <div class="bear-info-item bear-anim delay-1">
                <div class="icon"><i class="fas fa-calendar-heart"></i></div>
                <div class="label">DATE</div>
                <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
            </div>
            
            <div class="bear-info-item bear-anim delay-2">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">HEURE</div>
                <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
            </div>
            
            <div class="bear-info-item bear-anim delay-3" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="label">LIEU</div>
                <div class="value">
                    <?php echo htmlspecialchars($lieuDisplay); ?>
                    <?php if ($adresseDisplay): ?>
                        <span class="sub"><?php echo htmlspecialchars($adresseDisplay); ?></span>
                    <?php endif; ?>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" rel="noopener" class="bear-btn-itinerary">
                    <i class="fas fa-route"></i> Itinéraire
                </a>
            </div>
            
            <!-- ⭐ TABLE ASSIGNÉE -->
            <?php if ($hasTable): ?>
            <div class="bear-info-item bear-table-item bear-anim delay-4" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-chair"></i></div>
                <div class="label">VOTRE TABLE</div>
                <div class="value">
                    <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bear-info-item bear-anim delay-5" style="grid-column: 1 / -1;">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="label">PLACES</div>
                <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
            </div>
            
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bear-section bear-anim apparue">
            <div class="bear-alert bear-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ⭐ DIAPORAMA PHOTOS -->
    <?php if ($hasPhotos): ?>
        <div class="bear-section bear-anim from-left">
            <div class="bear-section-title">📸 Souvenirs</div>
            
            <div class="bear-diaporama" id="bearDiaporama">
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
                    <button class="bear-diapo-arrow prev" onclick="bearDiapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="bear-diapo-arrow next" onclick="bearDiapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="bear-diapo-counter" id="bearDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="bear-diapo-dots" id="bearDiapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="bearDiapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- QR CODE -->
    <div class="bear-section bear-anim from-right">
        <div class="bear-section-title">🎫 Code d'accès</div>
        <div class="bear-qr-wrapper">
            <div class="bear-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Baloo 2',cursive;font-size:16px;color:var(--brown);margin-top:24px;font-weight:700;letter-spacing:0.1em;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- CONFIRMATION -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="bear-section bear-anim from-left">
            <div class="bear-section-title">🧸 Confirmation</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="bear-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="bear-form-group">
                    <label>Votre réponse</label>
                    <div class="bear-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="bear-option-radio">
                            <label for="presenceOui" class="bear-option-label">✓ J'y serai</label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="bear-option-radio">
                            <label for="presenceNon" class="bear-option-label">✗ Pas dispo</label>
                        </div>
                    </div>
                </div>
                
                <div class="bear-form-group">
                    <label>Message</label>
                    <textarea name="message_invite" rows="3" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="bear-btn-submit">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- BOISSONS -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="bear-section bear-anim from-right">
            <div class="bear-section-title">🥤 Boissons</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--brown);font-family:'Baloo 2',cursive;font-size:18px;font-weight:700;padding:20px 0;">
                    <i class="fas fa-lock"></i> Préférences enregistrées
                </div>
                <div class="bear-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="bear-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Fredoka',sans-serif;font-size:15px;color:var(--text-muted);margin-bottom:24px;font-weight:500;">
                        Choisissez <strong style="color:var(--brown);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="bear-boisson-category">
                            <div class="bear-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="bear-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="bear-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="bear-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="bear-footer">
        <div class="bear-footer-brand">🧸 <?php echo htmlspecialchars($appName); ?></div>
        <div class="bear-footer-tagline">Des invitations pleines de tendresse</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="bear-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px dashed var(--beige-dark);font-family:'Fredoka',sans-serif;font-size:12px;color:var(--text-muted);letter-spacing:0.15em;">
            © <?php echo date('Y'); ?> • TOUS DROITS RÉSERVÉS
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.bear-anim');
            
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
            
            // Fallback
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
                        width: 180, height: 180,
                        colorDark: '#5d4a2f', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA PHOTOS
        // ================================================================
        let bearDiapoIndex = 0;
        const bearSlides = document.querySelectorAll('#bearDiaporama .slide');
        const bearDots = document.querySelectorAll('#bearDiapoDots span');
        const bearCounter = document.getElementById('bearDiapoCounter');
        let bearDiapoInterval = null;

        function bearUpdateDiapo() {
            bearSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === bearDiapoIndex);
            });
            bearDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === bearDiapoIndex);
            });
            if (bearCounter) {
                bearCounter.textContent = (bearDiapoIndex + 1) + ' / ' + bearSlides.length;
            }
        }

        function bearDiapoChange(direction) {
            bearDiapoIndex += direction;
            if (bearDiapoIndex < 0) bearDiapoIndex = bearSlides.length - 1;
            if (bearDiapoIndex >= bearSlides.length) bearDiapoIndex = 0;
            bearUpdateDiapo();
            resetBearDiapoAuto();
        }

        function bearDiapoGoTo(index) {
            bearDiapoIndex = index;
            bearUpdateDiapo();
            resetBearDiapoAuto();
        }

        function resetBearDiapoAuto() {
            if (bearDiapoInterval) clearInterval(bearDiapoInterval);
            if (bearSlides.length > 1) {
                bearDiapoInterval = setInterval(() => {
                    bearDiapoIndex = (bearDiapoIndex + 1) % bearSlides.length;
                    bearUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (bearSlides.length > 0) {
                bearUpdateDiapo();
                resetBearDiapoAuto();
                
                const container = document.getElementById('bearDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (bearDiapoInterval) clearInterval(bearDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetBearDiapoAuto);
                }
            }
        });

        // ================================================================
        // TÉLÉCHARGEMENT
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const hero = document.querySelector('.bear-hero');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(hero, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: '#fff9f0', logging: false
                });
                const link = document.createElement('a');
                link.download = `baby_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.bear-boisson-item.selected').forEach(item => {
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