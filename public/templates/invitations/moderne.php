<?php
/**
 * ============================================================
 * TEMPLATE : MODERNE — Design contemporain élégant
 * ============================================================
 * 
 * Version avec transitions d'apparition au scroll :
 * - Chaque section apparaît en fondu + translation
 * - Animation en cascade pour les éléments internes
 * - Effet "reveal" fluide
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);

// Détecter le premier prénom du nom complet
$hostParts = preg_split('/\s+(?:et|&)\s+/i', $host1);
$hostName1 = trim($hostParts[0] ?? $host1);
$hostName2 = trim($hostParts[1] ?? '');

$hostFull1 = $hostName1;
$hostFull2 = $hostName2;
$lastName1 = '';
$lastName2 = '';
$firstOnly1 = $hostName1;
$firstOnly2 = $hostName2;

$parts1 = explode(' ', $hostName1);
if (count($parts1) > 1) {
    $firstOnly1 = $parts1[0];
    array_shift($parts1);
    $lastName1 = implode(' ', $parts1);
}
$parts2 = explode(' ', $hostName2);
if (count($parts2) > 1) {
    $firstOnly2 = $parts2[0];
    array_shift($parts2);
    $lastName2 = implode(' ', $parts2);
}

// RSVP
$rsvpLabel = 'En attente de confirmation';
$rsvpClass = 'pending';
if (($invitation['statut'] ?? '') === 'CONFIRMEE') {
    $rsvpLabel = 'Confirmé';
    $rsvpClass = 'confirmed';
} elseif (($invitation['statut'] ?? '') === 'REFUSEE') {
    $rsvpLabel = 'Refusé';
    $rsvpClass = 'refused';
}

// Photo principale
$mainPhoto = '';
if (!empty($photosHost)) {
    $mainPhoto = getPhotoUrl($photosHost[0]['photo']);
} elseif (!empty($pageBackground)) {
    $mainPhoto = $pageBackground;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Great+Vibes&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --gold: #c9a961;
            --gold-light: #e8d5a0;
            --gold-dark: #8b6f3f;
            --cream: #f5efe3;
            --dark-brown: #2a1f15;
            --dark-brown-2: #3d2d1e;
            --dark-bg: #1a120a;
            --white-soft: rgba(255, 255, 255, 0.95);
            --white-mid: rgba(255, 255, 255, 0.7);
            --white-dim: rgba(255, 255, 255, 0.4);
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: var(--dark-bg);
            color: white;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        
        /* ============================================
           TRANSITION D'OUVERTURE
           ============================================ */
        .moderne-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(135deg, #1a120a 0%, #2a1f15 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            animation: loaderFadeOut 1.2s ease-in-out 2s forwards;
        }
        
        @keyframes loaderFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .loader-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 10vw, 96px);
            color: white;
            margin-bottom: 20px;
            opacity: 0;
            animation: loaderNameIn 1.5s ease-out 0.3s forwards;
            text-shadow: 0 0 40px rgba(201, 169, 97, 0.4);
        }
        
        @keyframes loaderNameIn {
            0% { opacity: 0; letter-spacing: 0.5em; filter: blur(20px); }
            100% { opacity: 1; letter-spacing: 0; filter: blur(0); }
        }
        
        .loader-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            opacity: 0;
            animation: loaderDividerIn 0.8s ease-out 1.2s forwards;
        }
        
        @keyframes loaderDividerIn {
            to { opacity: 1; }
        }
        
        .loader-divider .line {
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        
        .loader-divider i {
            color: var(--gold);
            font-size: 18px;
        }
        
        /* ============================================
           FOND
           ============================================ */
        .moderne-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: linear-gradient(135deg, #1a120a 0%, #2a1f15 50%, #1a120a 100%);
        }
        
        <?php if ($hasFond): ?>
        .moderne-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            filter: blur(20px) saturate(0.8);
        }
        <?php endif; ?>
        
        .moderne-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 50% 30%, rgba(201, 169, 97, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 50% 80%, rgba(201, 169, 97, 0.05) 0%, transparent 60%);
        }
        
        /* ============================================
           WRAPPER
           ============================================ */
        .moderne-wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 40px 20px 60px;
            position: relative;
            z-index: 1;
            opacity: 0;
            animation: wrapperIn 1.2s ease-out 2.5s forwards;
        }
        
        @keyframes wrapperIn {
            to { opacity: 1; }
        }
        
        /* ============================================
           TRANSITIONS D'APPARITION AU SCROLL
           ============================================ */
        .reveal {
            opacity: 0;
            transform: translateY(60px);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity, transform;
        }
        
        .reveal.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Variantes de transition */
        .reveal-left {
            opacity: 0;
            transform: translateX(-80px);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .reveal-left.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-right {
            opacity: 0;
            transform: translateX(80px);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .reveal-right.apparue {
            opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-scale {
            opacity: 0;
            transform: scale(0.85);
            transition: opacity 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .reveal-scale.apparue {
            opacity: 1;
            transform: scale(1);
        }
        
        /* Délais en cascade */
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }
        .reveal-delay-5 { transition-delay: 0.5s; }
        .reveal-delay-6 { transition-delay: 0.6s; }
        
        /* ============================================
           CARTE PRINCIPALE
           ============================================ */
        .moderne-card {
            position: relative;
            background: rgba(20, 14, 8, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 4px;
            border: 1px solid rgba(201, 169, 97, 0.15);
            padding: 40px 30px 50px;
            overflow: hidden;
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.6),
                inset 0 0 60px rgba(201, 169, 97, 0.03);
        }
        
        @media (max-width: 480px) {
            .moderne-card { padding: 30px 20px 40px; }
        }
        
        .moderne-card::before {
            content: '';
            position: absolute;
            inset: 12px;
            border: 1px solid rgba(201, 169, 97, 0.15);
            pointer-events: none;
            border-radius: 2px;
        }
        
        @media (max-width: 480px) {
            .moderne-card::before { inset: 8px; }
        }
        
        /* ============================================
           EN-TÊTE
           ============================================ */
        .moderne-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .moderne-header-title {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 9vw, 72px);
            color: white;
            line-height: 1;
            margin-bottom: 16px;
            text-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.5),
                0 0 60px rgba(201, 169, 97, 0.2);
        }
        
        .moderne-header-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            opacity: 0.9;
        }
        
        .moderne-header-divider .line {
            width: 80px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        
        .moderne-header-divider .ornament {
            color: var(--gold);
            font-size: 16px;
            letter-spacing: 4px;
        }
        
        /* ============================================
           PHOTO PRINCIPALE
           ============================================ */
        .moderne-photo-block {
            position: relative;
            margin: 30px 0 40px;
            text-align: center;
        }
        
        .moderne-photo-frame {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            border-radius: 200px 200px 20px 20px;
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.6),
                inset 0 0 60px rgba(0, 0, 0, 0.3);
            aspect-ratio: 3/4;
        }
        
        .moderne-photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 8s ease-in-out;
        }
        
        .moderne-photo-frame:hover img {
            transform: scale(1.05);
        }
        
        .moderne-photo-frame::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 50%, rgba(10, 6, 2, 0.6) 100%);
            pointer-events: none;
        }
        
        .moderne-photo-frame::before {
            content: '';
            position: absolute;
            inset: 0;
            border: 1px solid rgba(201, 169, 97, 0.3);
            border-radius: 200px 200px 20px 20px;
            pointer-events: none;
            z-index: 2;
        }
        
        @media (max-width: 480px) {
            .moderne-photo-frame {
                border-radius: 150px 150px 16px 16px;
            }
            .moderne-photo-frame::before {
                border-radius: 150px 150px 16px 16px;
            }
        }
        
        /* ============================================
           NOMS
           ============================================ */
        .moderne-names {
            position: relative;
            margin-top: -60px;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 480px) {
            .moderne-names { margin-top: -40px; }
        }
        
        .moderne-name-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .moderne-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(36px, 7vw, 56px);
            color: white;
            line-height: 0.95;
            text-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.8),
                0 0 40px rgba(201, 169, 97, 0.3);
        }
        
        .moderne-name-amp {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(24px, 5vw, 36px);
            color: var(--gold-light);
            opacity: 0.9;
        }
        
        .moderne-lastname {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: clamp(14px, 2.5vw, 18px);
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 500;
            margin-top: 6px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
        }
        
        /* ============================================
           MESSAGE
           ============================================ */
        .moderne-message {
            text-align: center;
            margin: 40px 0 30px;
            padding: 0 10px;
        }
        
        .moderne-message-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.85);
            font-style: italic;
            font-weight: 300;
        }
        
        @media (max-width: 480px) {
            .moderne-message-text { font-size: 16px; }
        }
        
        .moderne-message-strong {
            color: white;
            font-weight: 500;
            font-style: normal;
        }
        
        /* ============================================
           BLOC DATE / LIEU
           ============================================ */
        .moderne-event-info {
            text-align: center;
            padding: 30px 20px;
            margin: 30px 0;
            background: rgba(201, 169, 97, 0.05);
            border-top: 1px solid rgba(201, 169, 97, 0.2);
            border-bottom: 1px solid rgba(201, 169, 97, 0.2);
            border-radius: 2px;
        }
        
        .moderne-event-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .moderne-event-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 400;
            color: white;
            letter-spacing: 0.05em;
        }
        
        @media (max-width: 480px) {
            .moderne-event-value { font-size: 18px; }
        }
        
        .moderne-event-address {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 6px;
            font-style: italic;
        }
        
        /* ============================================
           BOUTON ITINÉRAIRE
           ============================================ */
        .moderne-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 12px 28px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold-light);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .moderne-btn-itinerary:hover {
            background: var(--gold);
            color: #1a120a;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.4);
            transform: translateY(-2px);
        }
        
        /* ============================================
           TABLE
           ============================================ */
        .moderne-table {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: rgba(107, 30, 46, 0.1);
            border: 1px solid rgba(201, 169, 97, 0.25);
        }
        
        .moderne-table-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 8px;
        }
        
        .moderne-table-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 500;
            color: white;
        }
        
        /* ============================================
           SECTION TITRE
           ============================================ */
        .moderne-section-title {
            text-align: center;
            margin: 40px 0 24px;
            position: relative;
        }
        
        .moderne-section-title span {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 500;
        }
        
        .moderne-section-title::before,
        .moderne-section-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        
        .moderne-section-title::before { left: 0; }
        .moderne-section-title::after { right: 0; }
        
        /* ============================================
           DIAPORAMA
           ============================================ */
        .moderne-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(201, 169, 97, 0.3);
            margin-bottom: 20px;
        }
        
        .moderne-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .moderne-diaporama .slide.active {
            opacity: 1;
        }
        
        .moderne-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .moderne-diapo-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        
        .moderne-diapo-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: transparent;
            border: 1px solid rgba(201, 169, 97, 0.4);
            color: var(--gold);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .moderne-diapo-btn:hover {
            background: var(--gold);
            color: #1a120a;
            transform: scale(1.1);
        }
        
        .moderne-diapo-counter {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.2em;
            color: rgba(255, 255, 255, 0.7);
            min-width: 70px;
            text-align: center;
        }
        
        .moderne-diapo-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }
        
        .moderne-diapo-dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(201, 169, 97, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .moderne-diapo-dots span.active {
            background: var(--gold);
            transform: scale(1.4);
            box-shadow: 0 0 10px var(--gold);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .moderne-form-group {
            margin-bottom: 22px;
        }
        
        .moderne-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .moderne-form-group input,
        .moderne-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(201, 169, 97, 0.3);
            color: white;
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            transition: all 0.3s ease;
        }
        
        .moderne-form-group input:focus,
        .moderne-form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.15);
        }
        
        .moderne-form-group input::placeholder,
        .moderne-form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .moderne-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        @media (max-width: 480px) {
            .moderne-options-grid { grid-template-columns: 1fr; }
        }
        
        .moderne-option-radio { display: none; }
        
        .moderne-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(201, 169, 97, 0.3);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .moderne-option-label:hover {
            border-color: var(--gold);
            color: white;
        }
        
        .moderne-option-radio:checked + .moderne-option-label {
            border-color: var(--gold);
            background: rgba(201, 169, 97, 0.15);
            color: white;
        }
        
        .moderne-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #1a120a;
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.3);
        }
        
        .moderne-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(201, 169, 97, 0.5);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .moderne-boisson-category {
            margin-bottom: 24px;
        }
        
        .moderne-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .moderne-boisson-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .moderne-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(201, 169, 97, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.75);
            border-radius: 2px;
        }
        
        .moderne-boisson-item:hover {
            border-color: var(--gold);
            color: white;
            transform: translateY(-2px);
        }
        
        .moderne-boisson-item.selected {
            border-color: var(--gold);
            background: rgba(201, 169, 97, 0.15);
            color: white;
            font-weight: 600;
        }
        
        .moderne-boisson-item .check {
            opacity: 0;
            color: var(--gold);
            transition: opacity 0.3s ease;
        }
        
        .moderne-boisson-item.selected .check {
            opacity: 1;
        }
        
        /* ============================================
           QR CODE
           ============================================ */
        .moderne-qr-wrapper {
            text-align: center;
        }
        
        .moderne-qr-box {
            display: inline-block;
            padding: 16px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.3);
            position: relative;
        }
        
        .moderne-qr-box::before {
            content: '';
            position: absolute;
            inset: -8px;
            border: 1px solid rgba(201, 169, 97, 0.4);
            border-radius: 6px;
            pointer-events: none;
        }
        
        .moderne-qr-label {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 16px;
        }
        
        /* ============================================
           SIGNATURE
           ============================================ */
        .moderne-signature {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(201, 169, 97, 0.2);
        }
        
        .moderne-signature-intro {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-style: italic;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 12px;
        }
        
        .moderne-signature-name {
            font-family: 'Great Vibes', cursive;
            font-size: 42px;
            color: white;
            line-height: 1;
            text-shadow: 0 0 30px rgba(201, 169, 97, 0.3);
        }
        
        @media (max-width: 480px) {
            .moderne-signature-name { font-size: 32px; }
        }
        
        .moderne-rsvp {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(201, 169, 97, 0.1);
            border: 1px solid rgba(201, 169, 97, 0.3);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        
        .moderne-rsvp .label {
            color: var(--gold);
            display: block;
            font-size: 9px;
            margin-bottom: 4px;
        }
        
        .moderne-rsvp .value {
            color: white;
            font-weight: 600;
        }
        
        /* ============================================
           MESSAGES
           ============================================ */
        .moderne-alert {
            padding: 16px 24px;
            margin: 20px 0;
            font-size: 15px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-left: 3px solid;
            background: rgba(0, 0, 0, 0.3);
            font-family: 'Cormorant Garamond', serif;
        }
        
        .moderne-alert-success { border-color: #2d7a45; color: #a3e8b8; }
        .moderne-alert-danger  { border-color: #c17c60; color: #fca5a5; }
        .moderne-alert-warning { border-color: var(--gold); color: var(--gold-light); }
        
        /* ============================================
           FOOTER
           ============================================ */
        .moderne-footer {
            text-align: center;
            margin-top: 50px;
            padding: 30px 20px;
        }
        
        .moderne-footer-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .moderne-footer-divider .line {
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        
        .moderne-footer-divider i {
            color: var(--gold);
            font-size: 16px;
        }
        
        .moderne-footer-app {
            font-family: 'Great Vibes', cursive;
            font-size: 32px;
            color: var(--gold);
            margin-bottom: 8px;
            text-shadow: 0 0 20px rgba(201, 169, 97, 0.4);
        }
        
        .moderne-footer-tagline {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: rgba(201, 169, 97, 0.6);
            margin-bottom: 24px;
        }
        
        .moderne-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold-light);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .moderne-whatsapp:hover {
            background: var(--gold);
            color: #1a120a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.4);
        }
        
        /* ============================================
           BOUTON TÉLÉCHARGEMENT
           ============================================ */
        #moderneDownloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #1a120a;
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.4);
            opacity: 0;
            animation: wrapperIn 1s ease-out 3.5s forwards;
        }
        
        #moderneDownloadBtn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 32px rgba(201, 169, 97, 0.6);
        }
        
        #moderneDownloadBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        @media (max-width: 480px) {
            #moderneDownloadBtn {
                bottom: 16px;
                right: 16px;
                padding: 10px 16px;
                font-size: 9px;
            }
        }
    </style>
</head>
<body>

    <!-- TRANSITION D'OUVERTURE -->
    <div class="moderne-loader">
        <div class="loader-name"><?php echo htmlspecialchars($host1); ?></div>
        <div class="loader-divider">
            <div class="line"></div>
            <i class="fas fa-heart"></i>
            <div class="line"></div>
        </div>
    </div>

    <!-- FOND -->
    <div class="moderne-bg"></div>

    <!-- CONTENU PRINCIPAL -->
    <div class="moderne-wrapper">

        <div class="moderne-card reveal-scale" id="moderneCard">
            
            <!-- EN-TÊTE -->
            <div class="moderne-header">
                <div class="moderne-header-title reveal" data-delay="1">Invitation</div>
                <div class="moderne-header-divider reveal" data-delay="2">
                    <div class="line"></div>
                    <div class="ornament">❦ ❦ ❦</div>
                    <div class="line"></div>
                </div>
            </div>

            <!-- PHOTO PRINCIPALE -->
            <?php if ($mainPhoto): ?>
            <div class="moderne-photo-block reveal" data-delay="3">
                <div class="moderne-photo-frame">
                    <img src="<?php echo htmlspecialchars($mainPhoto); ?>" 
                         alt="<?php echo htmlspecialchars($invitation['evenement_nom']); ?>"
                         loading="eager"
                         crossorigin="anonymous"
                         onerror="this.parentElement.style.display='none';">
                </div>
            </div>
            <?php endif; ?>

            <!-- NOMS DES HÔTES -->
            <div class="moderne-names reveal" data-delay="4">
                <div class="moderne-name-row">
                    <div style="text-align:center;">
                        <div class="moderne-name"><?php echo htmlspecialchars($firstOnly1); ?></div>
                        <?php if ($lastName1): ?>
                            <span class="moderne-lastname"><?php echo htmlspecialchars(strtoupper($lastName1)); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($hostName2): ?>
                    <div class="moderne-name-amp">&</div>
                    <div style="text-align:center;">
                        <div class="moderne-name"><?php echo htmlspecialchars($firstOnly2); ?></div>
                        <?php if ($lastName2): ?>
                            <span class="moderne-lastname"><?php echo htmlspecialchars(strtoupper($lastName2)); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MESSAGE D'INVITATION -->
            <div class="moderne-message reveal" data-delay="5">
                <p class="moderne-message-text">
                    C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <span class="moderne-message-strong"><?php echo htmlspecialchars($eventType); ?></span>.
                    <br><br>
                    Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.
                </p>
            </div>

            <!-- DESTINATAIRE -->
            <div class="reveal" data-delay="6" style="text-align: center; margin: 30px 0;">
                <div class="moderne-event-label" style="margin-bottom: 10px;">À l'attention de</div>
                <div style="font-family: 'Cormorant Garamond', serif; font-size: 26px; font-weight: 500; color: white; letter-spacing: 0.02em;">
                    <?php echo htmlspecialchars($guestName); ?>
                </div>
            </div>

            <!-- DATE / HEURE -->
            <div class="moderne-event-info reveal-left" data-delay="1">
                <div class="moderne-event-label">Date & Heure</div>
                <div class="moderne-event-value">
                    <?php echo htmlspecialchars($eventDate); ?>
                    <?php if ($eventTime): ?>
                        · <?php echo htmlspecialchars($eventTime); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LIEU -->
            <div class="moderne-event-info reveal-right" data-delay="2">
                <div class="moderne-event-label">Lieu de la célébration</div>
                <div class="moderne-event-value"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                <?php if ($adresseDisplay): ?>
                    <div class="moderne-event-address"><?php echo htmlspecialchars($adresseDisplay); ?></div>
                <?php endif; ?>
                
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="moderne-btn-itinerary">
                    <i class="fas fa-map-marked-alt"></i>
                    Itinéraire
                </a>
            </div>

            <!-- TABLE ASSIGNÉE -->
            <?php if ($hasTable): ?>
            <div class="moderne-table reveal-scale" data-delay="3">
                <div class="moderne-table-label">Votre table</div>
                <div class="moderne-table-value">
                    <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    <?php if (!empty($tableZone)): ?>
                        <span style="font-size: 16px; color: var(--gold);"> · <?php echo htmlspecialchars($tableZone); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- SIGNATURE -->
            <div class="moderne-signature reveal" data-delay="4">
                <div class="moderne-signature-intro">Avec toute notre affection,</div>
                <div class="moderne-signature-name">
                    <?php echo htmlspecialchars($invitation['evenement_nom']); ?>
                </div>
                
                <div class="moderne-rsvp">
                    <span class="label">RSVP</span>
                    <span class="value"><?php echo htmlspecialchars($rsvpLabel); ?></span>
                </div>
            </div>

            <!-- QR CODE -->
            <div style="margin-top: 40px;" class="reveal-scale" data-delay="5">
                <div class="moderne-section-title">
                    <span>Code d'accès</span>
                </div>
                <div class="moderne-qr-wrapper">
                    <div class="moderne-qr-box">
                        <div id="moderneQrcode"></div>
                    </div>
                    <div class="moderne-qr-label"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                </div>
            </div>

        </div>

        <!-- MESSAGES -->
        <?php if ($message): ?>
            <div class="moderne-alert moderne-alert-<?php echo htmlspecialchars($messageType); ?> reveal">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- DIAPORAMA PHOTOS -->
        <?php if ($hasPhotos && count($photosHost) > 1): ?>
        <div class="moderne-card reveal" style="margin-top: 30px;">
            <div class="moderne-section-title">
                <span>Souvenirs</span>
            </div>
            
            <div class="moderne-diaporama" id="moderneDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                             loading="lazy"
                             crossorigin="anonymous">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
            </div>
            
            <div class="moderne-diapo-nav">
                <button class="moderne-diapo-btn" onclick="moderneDiapoChange(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="moderne-diapo-counter" id="moderneDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                <button class="moderne-diapo-btn" onclick="moderneDiapoChange(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="moderne-diapo-dots" id="moderneDiapoDots">
                <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                    <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="moderneDiapoGoTo(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CONFIRMATION -->
        <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="moderne-card reveal" style="margin-top: 30px;">
            <div class="moderne-section-title">
                <span>Confirmation</span>
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="moderne-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="moderne-form-group">
                    <label>Votre réponse</label>
                    <div class="moderne-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="moderneOui" value="CONFIRMEE" checked class="moderne-option-radio">
                            <label for="moderneOui" class="moderne-option-label">
                                <i class="fas fa-check"></i> Je confirme
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="moderneNon" value="REFUSEE" class="moderne-option-radio">
                            <label for="moderneNon" class="moderne-option-label">
                                <i class="fas fa-times"></i> Je ne peux pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="moderne-form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message_invite" rows="3" placeholder="Un petit mot..."></textarea>
                </div>
                
                <button type="submit" class="moderne-btn-submit">
                    <i class="fas fa-check"></i>
                    Confirmer ma présence
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- BOISSONS -->
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="moderne-card reveal" style="margin-top: 30px;">
            <div class="moderne-section-title">
                <span>Vos préférences</span>
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align: center; color: var(--gold); font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; padding: 20px 0;">
                    <i class="fas fa-lock"></i> Préférences enregistrées
                </div>
                <div class="moderne-boisson-grid" style="justify-content: center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="moderne-boisson-item selected" style="cursor: default;">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; font-family: 'Cormorant Garamond', serif; font-size: 15px; color: rgba(255,255,255,0.7); margin-bottom: 24px;">
                    Choisissez jusqu'à <strong style="color: var(--gold);">2 boissons</strong> : <span id="moderneSelectedCount">0</span>/2
                </p>
                
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                    <input type="hidden" name="action" value="preferences">
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="moderne-boisson-category">
                            <div class="moderne-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="moderne-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="moderne-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $b['id']; ?>"
                                         onclick="moderneToggleBoisson(this, <?php echo $b['id']; ?>)">
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
                    
                    <button type="submit" class="moderne-btn-submit" style="margin-top: 20px;">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <footer class="moderne-footer reveal">
            <div class="moderne-footer-divider">
                <div class="line"></div>
                <i class="fas fa-heart"></i>
                <div class="line"></div>
            </div>
            <div class="moderne-footer-app"><?php echo htmlspecialchars($appName); ?></div>
            <div class="moderne-footer-tagline">Invitation d'exception</div>
            
            <a href="https://wa.me/243963967028?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20mon%20invitation" 
               target="_blank" 
               rel="noopener"
               class="moderne-whatsapp">
                <i class="fab fa-whatsapp"></i>
                Nous contacter
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(201, 169, 97, 0.15); font-family: 'Cinzel', serif; font-size: 9px; letter-spacing: 0.3em; text-transform: uppercase; color: rgba(201, 169, 97, 0.4);">
                © <?php echo date('Y'); ?> · Tous droits réservés
            </div>
        </footer>

    </div>

    <!-- BOUTON TÉLÉCHARGEMENT -->
    <button id="moderneDownloadBtn" onclick="moderneDownload()">
        <i class="fas fa-download"></i>
        <span id="moderneBtnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // 1. ANIMATIONS AU SCROLL (REVEAL)
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Appliquer le délai si spécifié
                        const delay = entry.target.getAttribute('data-delay');
                        if (delay) {
                            setTimeout(() => {
                                entry.target.classList.add('apparue');
                            }, delay * 100);
                        } else {
                            entry.target.classList.add('apparue');
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: 0.1, 
                rootMargin: '0px 0px -80px 0px' 
            });
            
            reveals.forEach(el => observer.observe(el));
            
            // Fallback : rendre visible tout ce qui est déjà dans le viewport
            setTimeout(() => {
                reveals.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        el.classList.add('apparue');
                    }
                });
            }, 500);
        });

        // ================================================================
        // 2. QR CODE
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('moderneQrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180,
                        height: 180,
                        colorDark: '#1a120a',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) {
                    console.error('Erreur QR code:', e);
                }
            }
        });

        // ================================================================
        // 3. DIAPORAMA PHOTOS
        // ================================================================
        let moderneDiapoIndex = 0;
        const moderneSlides = document.querySelectorAll('#moderneDiaporama .slide');
        const moderneDots = document.querySelectorAll('#moderneDiapoDots span');
        const moderneCounter = document.getElementById('moderneDiapoCounter');
        let moderneDiapoInterval = null;

        function moderneUpdateDiapo() {
            moderneSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === moderneDiapoIndex);
            });
            moderneDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === moderneDiapoIndex);
            });
            if (moderneCounter) {
                moderneCounter.textContent = (moderneDiapoIndex + 1) + ' / ' + moderneSlides.length;
            }
        }

        function moderneDiapoChange(direction) {
            moderneDiapoIndex += direction;
            if (moderneDiapoIndex < 0) moderneDiapoIndex = moderneSlides.length - 1;
            if (moderneDiapoIndex >= moderneSlides.length) moderneDiapoIndex = 0;
            moderneUpdateDiapo();
            resetModerneDiapoAuto();
        }

        function moderneDiapoGoTo(index) {
            moderneDiapoIndex = index;
            moderneUpdateDiapo();
            resetModerneDiapoAuto();
        }

        function resetModerneDiapoAuto() {
            if (moderneDiapoInterval) clearInterval(moderneDiapoInterval);
            if (moderneSlides.length > 1) {
                moderneDiapoInterval = setInterval(() => {
                    moderneDiapoIndex = (moderneDiapoIndex + 1) % moderneSlides.length;
                    moderneUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (moderneSlides.length > 0) {
                moderneUpdateDiapo();
                resetModerneDiapoAuto();
                
                const container = document.getElementById('moderneDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (moderneDiapoInterval) clearInterval(moderneDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetModerneDiapoAuto);
                }
            }
        });

        // ================================================================
        // 4. BOISSONS
        // ================================================================
        let moderneSelectedBoissons = [];

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.moderne-boisson-item.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!isNaN(id) && !moderneSelectedBoissons.includes(id)) {
                    moderneSelectedBoissons.push(id);
                }
            });
            moderneUpdateBoissonCount();
        });

        function moderneToggleBoisson(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const index = moderneSelectedBoissons.indexOf(id);
                if (index > -1) moderneSelectedBoissons.splice(index, 1);
                const checkbox = element.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
                moderneUpdateBoissonCount();
                return;
            }
            
            if (moderneSelectedBoissons.length >= 2) {
                alert('Vous ne pouvez sélectionner que 2 boissons maximum.');
                return;
            }
            
            element.classList.add('selected');
            moderneSelectedBoissons.push(id);
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
            moderneUpdateBoissonCount();
        }

        function moderneUpdateBoissonCount() {
            const el = document.getElementById('moderneSelectedCount');
            if (el) el.textContent = moderneSelectedBoissons.length;
            
            document.querySelectorAll('.moderne-boisson-item').forEach(item => {
                if (!item.classList.contains('selected') && moderneSelectedBoissons.length >= 2) {
                    item.style.opacity = '0.4';
                    item.style.cursor = 'not-allowed';
                } else {
                    item.style.opacity = '1';
                    item.style.cursor = 'pointer';
                }
            });
        }

        // ================================================================
        // 5. TÉLÉCHARGEMENT
        // ================================================================
        async function moderneDownload() {
            const btn = document.getElementById('moderneDownloadBtn');
            const btnText = document.getElementById('moderneBtnText');
            const card = document.getElementById('moderneCard');
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                await new Promise(r => setTimeout(r, 400));
                
                const canvas = await html2canvas(card, {
                    scale: 2.5,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#1a120a',
                    logging: false,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.getElementById('moderneCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                        }
                    }
                });
                
                const link = document.createElement('a');
                const name = '<?php echo htmlspecialchars($invitation['evenement_nom']); ?>';
                link.download = `invitation_${name.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                
                btnText.textContent = 'Téléchargé ✓';
                setTimeout(() => btnText.textContent = 'Télécharger', 3000);
            } catch(e) {
                console.error(e);
                btnText.textContent = 'Erreur';
                setTimeout(() => btnText.textContent = 'Télécharger', 3000);
            }
            
            btn.disabled = false;
        }
    </script>

</body>
</html>