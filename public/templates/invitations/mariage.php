<?php
/**
 * ============================================================
 * TEMPLATE : MARIAGE — Invitation verticale florale (SVG)
 * ============================================================
 * 
 * - Fleurs SVG style aquarelle romantique (violet + rose)
 * - Téléchargement : capture uniquement la carte (#mariageCard)
 * - Timeline avec heure de l'événement
 * - Save the Date
 * - QR Code vers l'URL de l'invitation
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);

// Détection des prénoms
$hostParts = preg_split('/\s+(?:et|&)\s+/i', $host1);

// Photo principale
$mainPhoto = '';
if (!empty($photosHost)) {
    $mainPhoto = getPhotoUrl($photosHost[0]['photo']);
} elseif (!empty($pageBackground)) {
    $mainPhoto = $pageBackground;
}

// Date formatée
$dateDay   = !empty($invitation['date_evenement']) ? date('d', strtotime($invitation['date_evenement'])) : '--';
$dateMonth = !empty($invitation['date_evenement']) ? date('m', strtotime($invitation['date_evenement'])) : '--';
$dateYear  = !empty($invitation['date_evenement']) ? date('Y', strtotime($invitation['date_evenement'])) : '----';

$moisFr = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$moisLabel = $moisFr[$dateMonth] ?? '';

// Heure
$eventHour = !empty($invitation['heure_evenement']) ? date('H:i', strtotime($invitation['heure_evenement'])) : '';

// RSVP
$rsvpLabel = 'En attente';
if (($invitation['statut'] ?? '') === 'CONFIRMEE') $rsvpLabel = 'Confirmé';
elseif (($invitation['statut'] ?? '') === 'REFUSEE') $rsvpLabel = 'Refusé';

// ============================================================
// SVG : FLEUR AQUARELLE ROMANTIQUE (violet + rose)
// ============================================================
function svgFlowerRomantic($size = 60, $rotate = 0) {
    return '
    <svg width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(' . $rotate . 'deg);">
        <defs>
            <radialGradient id="petalGrad' . $size . $rotate . '" cx="50%" cy="50%">
                <stop offset="0%" stop-color="#f5d5e6" stop-opacity="0.95"/>
                <stop offset="50%" stop-color="#e8a8c8" stop-opacity="0.85"/>
                <stop offset="100%" stop-color="#b794d4" stop-opacity="0.7"/>
            </radialGradient>
            <radialGradient id="centerGrad' . $size . $rotate . '" cx="50%" cy="50%">
                <stop offset="0%" stop-color="#c9a961"/>
                <stop offset="70%" stop-color="#8b6f3f"/>
                <stop offset="100%" stop-color="#5a4e2a"/>
            </radialGradient>
            <filter id="softBlur' . $size . $rotate . '">
                <feGaussianBlur stdDeviation="0.5"/>
            </filter>
        </defs>
        <g filter="url(#softBlur' . $size . $rotate . ')">
            <!-- Pétales arrière -->
            <ellipse cx="50" cy="22" rx="10" ry="18" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.85"/>
            <ellipse cx="50" cy="78" rx="10" ry="18" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.85"/>
            <ellipse cx="22" cy="50" rx="18" ry="10" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.85"/>
            <ellipse cx="78" cy="50" rx="18" ry="10" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.85"/>
            <!-- Pétales diagonaux -->
            <ellipse cx="30" cy="30" rx="9" ry="16" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.75" transform="rotate(45 30 30)"/>
            <ellipse cx="70" cy="30" rx="9" ry="16" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.75" transform="rotate(-45 70 30)"/>
            <ellipse cx="30" cy="70" rx="9" ry="16" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.75" transform="rotate(-45 30 70)"/>
            <ellipse cx="70" cy="70" rx="9" ry="16" fill="url(#petalGrad' . $size . $rotate . ')" opacity="0.75" transform="rotate(45 70 70)"/>
            <!-- Pétales avant -->
            <ellipse cx="50" cy="30" rx="8" ry="14" fill="#f5d5e6" opacity="0.9"/>
            <ellipse cx="50" cy="70" rx="8" ry="14" fill="#f5d5e6" opacity="0.9"/>
            <ellipse cx="30" cy="50" rx="14" ry="8" fill="#f5d5e6" opacity="0.9"/>
            <ellipse cx="70" cy="50" rx="14" ry="8" fill="#f5d5e6" opacity="0.9"/>
            <!-- Centre -->
            <circle cx="50" cy="50" r="8" fill="url(#centerGrad' . $size . $rotate . ')"/>
            <circle cx="50" cy="50" r="4" fill="#e8d5a0" opacity="0.8"/>
        </g>
    </svg>';
}

// Petite feuille / brindille
function svgLeaf($size = 40, $rotate = 0) {
    return '
    <svg width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(' . $rotate . 'deg);">
        <defs>
            <linearGradient id="leafGrad' . $size . $rotate . '" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#b794d4" stop-opacity="0.8"/>
                <stop offset="100%" stop-color="#7c4dbe" stop-opacity="0.6"/>
            </linearGradient>
        </defs>
        <path d="M50 10 Q 70 30 60 60 Q 55 75 50 90 Q 45 75 40 60 Q 30 30 50 10 Z" fill="url(#leafGrad' . $size . $rotate . ')"/>
        <path d="M50 15 L 50 85" stroke="#5a3288" stroke-width="0.5" opacity="0.5"/>
    </svg>';
}

// Branche florale horizontale (décor)
function svgFloralBranch() {
    return '
    <svg width="200" height="40" viewBox="0 0 200 40" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="branchGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#b794d4" stop-opacity="0"/>
                <stop offset="30%" stop-color="#b794d4" stop-opacity="0.7"/>
                <stop offset="50%" stop-color="#7c4dbe" stop-opacity="0.9"/>
                <stop offset="70%" stop-color="#b794d4" stop-opacity="0.7"/>
                <stop offset="100%" stop-color="#b794d4" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <path d="M 0 20 Q 50 10 100 20 Q 150 30 200 20" stroke="url(#branchGrad)" stroke-width="1.5" fill="none"/>
        <circle cx="50" cy="15" r="3" fill="#e8a8c8" opacity="0.7"/>
        <circle cx="100" cy="20" r="4" fill="#7c4dbe" opacity="0.8"/>
        <circle cx="150" cy="25" r="3" fill="#e8a8c8" opacity="0.7"/>
    </svg>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Mariage - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Great+Vibes&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --violet: #7c4dbe;
            --violet-dark: #5a3288;
            --violet-light: #b794d4;
            --pink: #e8a8c8;
            --pink-light: #f5d5e6;
            --cream: #fdf9f3;
            --cream-dark: #f5ede0;
            --brown: #4a3428;
            --gold: #c9a961;
            --gold-light: #e8d5a0;
            --text-dark: #3a2d24;
            --text-light: #7a6b60;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: var(--cream);
            color: var(--text-dark);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        
        /* ============================================
           TRANSITION D'OUVERTURE
           ============================================ */
        .mariage-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(135deg, #fdf9f3 0%, #f5ede0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            animation: loaderFadeOut 1.2s ease-in-out 2.2s forwards;
        }
        
        @keyframes loaderFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .loader-flower {
            margin-bottom: 20px;
            animation: flowerBloom 2s ease-out;
        }
        
        @keyframes flowerBloom {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(0deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        .loader-names {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 10vw, 80px);
            color: var(--brown);
            opacity: 0;
            animation: loaderNamesIn 1.5s ease-out 0.5s forwards;
        }
        
        @keyframes loaderNamesIn {
            0% { opacity: 0; letter-spacing: 0.5em; filter: blur(15px); }
            100% { opacity: 1; letter-spacing: 0; filter: blur(0); }
        }
        
        /* ============================================
           FOND GÉNÉRAL
           ============================================ */
        .mariage-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: var(--cream);
        }
        
        .mariage-bg::before {
            content: '';
            position: absolute;
            top: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(183, 148, 212, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .mariage-bg::after {
            content: '';
            position: absolute;
            bottom: -150px;
            right: -150px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232, 168, 200, 0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* ============================================
           WRAPPER
           ============================================ */
        .mariage-wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 20px 16px 60px;
            position: relative;
            z-index: 1;
            opacity: 0;
            animation: wrapperIn 1.2s ease-out 2.5s forwards;
        }
        
        @keyframes wrapperIn {
            to { opacity: 1; }
        }
        
        /* ============================================
           LA CARTE (SEUL ÉLÉMENT CAPTURÉ AU TÉLÉCHARGEMENT)
           ============================================ */
        #mariageCard {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 
                0 30px 80px rgba(74, 52, 40, 0.2),
                0 0 0 1px rgba(124, 77, 190, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        /* Fleurs SVG décoratives autour de la carte */
        .card-corner-flower {
            position: absolute;
            z-index: 3;
            pointer-events: none;
        }
        .card-corner-flower.tl { top: -10px; left: -10px; }
        .card-corner-flower.tr { top: -10px; right: -10px; transform: scaleX(-1); }
        .card-corner-flower.bl { bottom: -10px; left: -10px; transform: scaleY(-1); }
        .card-corner-flower.br { bottom: -10px; right: -10px; transform: scale(-1); }
        
        /* ============================================
           PHOTO DU COUPLE DANS LA CARTE
           ============================================ */
        .mariage-hero {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
        }
        
        .mariage-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .mariage-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 60%, rgba(253, 249, 243, 0.4) 100%);
            pointer-events: none;
        }
        
        /* ============================================
           CONTENU DE LA CARTE
           ============================================ */
        .card-content {
            padding: 30px 28px 40px;
            position: relative;
            z-index: 2;
        }
        
        @media (max-width: 480px) {
            .card-content { padding: 24px 18px 30px; }
        }
        
        /* Branche florale décorative */
        .floral-branch {
            display: flex;
            justify-content: center;
            margin: 8px 0 16px;
        }
        
        /* ============================================
           SAVE THE DATE
           ============================================ */
        .save-the-date {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .save-the-date-top {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .save-the-date-top .line {
            width: 50px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--violet-light), transparent);
        }
        
        .save-title {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(44px, 10vw, 68px);
            color: var(--violet);
            line-height: 1;
            margin-bottom: 10px;
        }
        
        .save-subtitle {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* ============================================
           NOMS DU COUPLE
           ============================================ */
        .couple-names {
            text-align: center;
            margin: 24px 0;
            padding: 20px 0;
            border-top: 1px solid rgba(124, 77, 190, 0.15);
            border-bottom: 1px solid rgba(124, 77, 190, 0.15);
        }
        
        .couple-name {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(32px, 7vw, 44px);
            color: var(--brown);
            line-height: 1;
            margin-bottom: 6px;
        }
        
        .couple-amp {
            font-family: 'Great Vibes', cursive;
            font-size: 32px;
            color: var(--pink);
            margin: 8px 0;
        }
        
        /* ============================================
           MESSAGE
           ============================================ */
        .mariage-message {
            text-align: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-dark);
            font-style: italic;
            margin: 24px 0;
            padding: 0 10px;
        }
        
        /* ============================================
           DATE / LIEU
           ============================================ */
        .mariage-date-block {
            text-align: center;
            margin: 24px 0;
            padding: 20px;
            background: linear-gradient(135deg, rgba(183, 148, 212, 0.08), rgba(232, 168, 200, 0.08));
            border-radius: 6px;
            border: 1px dashed rgba(124, 77, 190, 0.3);
        }
        
        .mariage-date-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--violet);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .mariage-date-value {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--brown);
            letter-spacing: 0.05em;
        }
        
        @media (max-width: 480px) {
            .mariage-date-value { font-size: 20px; }
        }
        
        .mariage-time-value {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            color: var(--violet);
            font-weight: 500;
            margin-top: 6px;
            letter-spacing: 0.15em;
        }
        
        .mariage-location-value {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-style: italic;
            color: var(--brown);
            margin-top: 8px;
        }
        
        .mariage-address-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            color: var(--text-light);
            margin-top: 4px;
        }
        
        .btn-mariage {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 26px;
            background: var(--violet);
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(124, 77, 190, 0.3);
        }
        
        .btn-mariage:hover {
            background: var(--violet-dark);
            transform: translateY(-2px);
            color: white;
        }
        
        /* ============================================
           QR CODE DANS LA CARTE
           ============================================ */
        .card-qr-wrapper {
            text-align: center;
            margin-top: 30px;
            padding-top: 24px;
            border-top: 1px solid rgba(124, 77, 190, 0.15);
        }
        
        .card-qr-box {
            display: inline-block;
            padding: 12px;
            background: white;
            border: 2px solid var(--violet);
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(124, 77, 190, 0.2);
            position: relative;
        }
        
        .card-qr-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-light);
            margin-top: 10px;
        }
        
        /* ============================================
           SIGNATURE DANS LA CARTE
           ============================================ */
        .card-signature {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(124, 77, 190, 0.15);
        }
        
        .card-signature-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-style: italic;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .card-signature-name {
            font-family: 'Great Vibes', cursive;
            font-size: 36px;
            color: var(--violet);
            line-height: 1;
        }
        
        @media (max-width: 480px) {
            .card-signature-name { font-size: 28px; }
        }
        
        /* ============================================
           SECTIONS EXTERNES (hors carte)
           ============================================ */
        .mariage-section {
            background: white;
            padding: 30px 24px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(74, 52, 40, 0.1);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .mariage-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        @media (max-width: 480px) {
            .mariage-section { padding: 24px 18px; }
        }
        
        .mariage-section-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .mariage-section-title-text {
            font-family: 'Great Vibes', cursive;
            font-size: 36px;
            color: var(--violet);
            line-height: 1;
        }
        
        @media (max-width: 480px) {
            .mariage-section-title-text { font-size: 28px; }
        }
        
        .mariage-section-title-sub {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--text-light);
            margin-top: 6px;
            font-weight: 500;
        }
        
        /* ============================================
           TIMELINE
           ============================================ */
        .timeline {
            position: relative;
            padding: 10px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 1px;
            background: linear-gradient(180deg, transparent, var(--violet-light), transparent);
            transform: translateX(-50%);
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            position: relative;
        }
        
        .timeline-item:nth-child(odd) { flex-direction: row-reverse; }
        
        .timeline-content {
            flex: 1;
            padding: 0 20px;
        }
        
        .timeline-item:nth-child(odd) .timeline-content { text-align: right; }
        .timeline-item:nth-child(even) .timeline-content { text-align: left; }
        
        .timeline-icon {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--violet);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--violet);
            font-size: 16px;
            z-index: 2;
            box-shadow: 0 4px 12px rgba(124, 77, 190, 0.2);
        }
        
        @media (max-width: 480px) {
            .timeline-icon { width: 34px; height: 34px; font-size: 13px; }
        }
        
        .timeline-time {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--violet);
            letter-spacing: 0.1em;
        }
        
        .timeline-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--brown);
            margin-top: 4px;
        }
        
        .timeline-desc {
            font-family: 'Cormorant Garamond', serif;
            font-size: 13px;
            color: var(--text-light);
            font-style: italic;
            margin-top: 2px;
        }
        
        /* ============================================
           CODE COULEUR
           ============================================ */
        .color-palette {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .color-swatch {
            width: 60px;
            height: 80px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        
        .color-swatch::after {
            content: '';
            position: absolute;
            inset: 4px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }
        
        .color-label {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 9px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-light);
            margin-top: 8px;
        }
        
        /* ============================================
           CADEAUX
           ============================================ */
        .gift-info {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(183, 148, 212, 0.08), rgba(232, 168, 200, 0.08));
            border-radius: 6px;
            border: 1px dashed rgba(124, 77, 190, 0.3);
        }
        
        .gift-icon {
            font-size: 36px;
            color: var(--violet);
            margin-bottom: 12px;
        }
        
        .gift-message {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            line-height: 1.7;
            font-style: italic;
            color: var(--text-dark);
        }
        
        /* ============================================
           DIAPORAMA
           ============================================ */
        .mariage-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            border-radius: 4px;
            background: var(--cream-dark);
            margin-bottom: 16px;
        }
        
        .mariage-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mariage-diaporama .slide.active { opacity: 1; }
        
        .mariage-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .mariage-diapo-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 12px;
        }
        
        .mariage-diapo-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: transparent;
            border: 1px solid var(--violet);
            color: var(--violet);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mariage-diapo-btn:hover {
            background: var(--violet);
            color: white;
        }
        
        .mariage-diapo-counter {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            color: var(--text-light);
            min-width: 70px;
            text-align: center;
        }
        
        .mariage-diapo-dots {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 10px;
        }
        
        .mariage-diapo-dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(124, 77, 190, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mariage-diapo-dots span.active {
            background: var(--violet);
            transform: scale(1.4);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .mariage-form-group { margin-bottom: 20px; }
        
        .mariage-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--violet);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .mariage-form-group input,
        .mariage-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: white;
            border: 1px solid rgba(124, 77, 190, 0.3);
            border-radius: 4px;
            color: var(--text-dark);
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .mariage-form-group input:focus,
        .mariage-form-group textarea:focus {
            outline: none;
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(124, 77, 190, 0.15);
        }
        
        .mariage-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        @media (max-width: 480px) {
            .mariage-options-grid { grid-template-columns: 1fr; }
        }
        
        .mariage-option-radio { display: none; }
        
        .mariage-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            background: white;
            border: 2px solid rgba(124, 77, 190, 0.2);
            border-radius: 4px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mariage-option-label:hover {
            border-color: var(--violet);
            color: var(--violet);
        }
        
        .mariage-option-radio:checked + .mariage-option-label {
            border-color: var(--violet);
            background: rgba(124, 77, 190, 0.08);
            color: var(--violet);
            font-weight: 700;
        }
        
        .mariage-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: var(--violet);
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(124, 77, 190, 0.3);
        }
        
        .mariage-btn-submit:hover {
            background: var(--violet-dark);
            transform: translateY(-2px);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .mariage-boisson-category { margin-bottom: 20px; }
        
        .mariage-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--violet);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .mariage-boisson-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .mariage-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: white;
            border: 1px solid rgba(124, 77, 190, 0.3);
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            color: var(--text-dark);
        }
        
        .mariage-boisson-item.selected {
            border-color: var(--violet);
            background: rgba(124, 77, 190, 0.08);
            color: var(--violet);
            font-weight: 600;
        }
        
        .mariage-boisson-item .check {
            opacity: 0;
            color: var(--violet);
            transition: opacity 0.3s ease;
        }
        
        .mariage-boisson-item.selected .check { opacity: 1; }
        
        /* ============================================
           MESSAGE FINAL
           ============================================ */
        .final-message {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, rgba(183, 148, 212, 0.1), rgba(232, 168, 200, 0.1));
            border-radius: 12px;
            margin-top: 24px;
        }
        
        .final-message-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            line-height: 1.7;
            font-style: italic;
            color: var(--text-dark);
        }
        
        .final-message-signature {
            font-family: 'Great Vibes', cursive;
            font-size: 36px;
            color: var(--violet);
            margin-top: 16px;
        }
        
        /* ============================================
           ALERTES
           ============================================ */
        .mariage-alert {
            padding: 14px 20px;
            margin: 16px 0;
            font-size: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
            border-radius: 4px;
            border-left: 3px solid;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .mariage-alert-success { border-color: #2d7a45; color: #2d7a45; }
        .mariage-alert-danger  { border-color: #c17c60; color: #c17c60; }
        .mariage-alert-warning { border-color: var(--gold); color: var(--gold); }
        
        /* ============================================
           FOOTER
           ============================================ */
        .mariage-footer {
            text-align: center;
            margin-top: 40px;
            padding: 30px 20px;
        }
        
        .mariage-footer-flower {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .mariage-footer-app {
            font-family: 'Great Vibes', cursive;
            font-size: 30px;
            color: var(--violet);
            margin-bottom: 6px;
        }
        
        .mariage-footer-tagline {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .mariage-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 26px;
            background: #25d366;
            color: white;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
        }
        
        .mariage-whatsapp:hover {
            background: #128c7e;
            transform: translateY(-2px);
            color: white;
        }
        
        /* ============================================
           BOUTON TÉLÉCHARGEMENT
           ============================================ */
        #mariageDownloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: var(--violet);
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(124, 77, 190, 0.4);
            opacity: 0;
            animation: wrapperIn 1s ease-out 3.5s forwards;
        }
        
        #mariageDownloadBtn:hover {
            transform: translateY(-3px) scale(1.05);
            background: var(--violet-dark);
        }
        
        #mariageDownloadBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        @media (max-width: 480px) {
            #mariageDownloadBtn {
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
    <div class="mariage-loader">
        <div class="loader-flower">
            <?php echo svgFlowerRomantic(100, 0); ?>
        </div>
        <div class="loader-names"><?php echo htmlspecialchars($host1); ?></div>
    </div>

    <!-- FOND -->
    <div class="mariage-bg"></div>

    <!-- WRAPPER -->
    <div class="mariage-wrapper">

        <!-- ========================================== -->
        <!-- CARTE PRINCIPALE (SEUL ÉLÉMENT CAPTURÉ)   -->
        <!-- ========================================== -->
        <div id="mariageCard">
            
            <!-- Fleurs SVG dans les coins de la carte -->
            <div class="card-corner-flower tl"><?php echo svgFlowerRomantic(70, 0); ?></div>
            <div class="card-corner-flower tr"><?php echo svgFlowerRomantic(70, 0); ?></div>
            <div class="card-corner-flower bl"><?php echo svgFlowerRomantic(70, 0); ?></div>
            <div class="card-corner-flower br"><?php echo svgFlowerRomantic(70, 0); ?></div>

            <!-- PHOTO DU COUPLE -->
            <?php if ($mainPhoto): ?>
            <div class="mariage-hero">
                <img src="<?php echo htmlspecialchars($mainPhoto); ?>" 
                     alt="<?php echo htmlspecialchars($invitation['evenement_nom']); ?>"
                     crossorigin="anonymous"
                     onerror="this.parentElement.style.display='none';">
            </div>
            <?php endif; ?>

            <!-- CONTENU -->
            <div class="card-content">
                
                <!-- Branche florale décorative -->
                <div class="floral-branch">
                    <?php echo svgFloralBranch(); ?>
                </div>

                <!-- SAVE THE DATE -->
                <div class="save-the-date">
                    <div class="save-the-date-top">
                        <div class="line"></div>
                        <?php echo svgFlowerRomantic(30, 0); ?>
                        <div class="line"></div>
                    </div>
                    <div class="save-title">Save the Date</div>
                    <div class="save-subtitle"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
                </div>

                <!-- NOMS -->
                <div class="couple-names">
                    <?php if (count($hostParts) >= 2): ?>
                        <div class="couple-name"><?php echo htmlspecialchars(trim($hostParts[0])); ?></div>
                        <div class="couple-amp">&</div>
                        <div class="couple-name"><?php echo htmlspecialchars(trim($hostParts[1])); ?></div>
                    <?php else: ?>
                        <div class="couple-name"><?php echo htmlspecialchars($host1); ?></div>
                    <?php endif; ?>
                </div>

                <!-- MESSAGE -->
                <div class="mariage-message">
                    C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <strong><?php echo htmlspecialchars($eventType); ?></strong>.
                    <br><br>
                    Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.
                </div>

                <!-- DATE / HEURE / LIEU -->
                <div class="mariage-date-block">
                    <div class="mariage-date-label">Date du mariage</div>
                    <div class="mariage-date-value">
                        <?php echo htmlspecialchars($dateDay); ?> <?php echo htmlspecialchars($moisLabel); ?> <?php echo htmlspecialchars($dateYear); ?>
                    </div>
                    <?php if ($eventTime): ?>
                        <div class="mariage-time-value">
                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($eventTime); ?>
                        </div>
                    <?php endif; ?>
                    <div class="mariage-location-value"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                    <?php if ($adresseDisplay): ?>
                        <div class="mariage-address-value"><?php echo htmlspecialchars($adresseDisplay); ?></div>
                    <?php endif; ?>
                    
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="btn-mariage">
                        <i class="fas fa-map-marked-alt"></i>
                        Voir l'itinéraire
                    </a>
                </div>

                <!-- QR CODE DANS LA CARTE -->
                <div class="card-qr-wrapper">
                    <div class="card-qr-box">
                        <div id="mariageQrcode"></div>
                    </div>
                    <div class="card-qr-label"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                </div>

                <!-- SIGNATURE -->
                <div class="card-signature">
                    <div class="card-signature-text">Avec toute notre affection,</div>
                    <div class="card-signature-name"><?php echo htmlspecialchars($invitation['evenement_nom']); ?></div>
                </div>

            </div>
        </div>
        <!-- FIN CARTE PRINCIPALE -->

        <!-- ========================================== -->
        <!-- MESSAGES                                   -->
        <!-- ========================================== -->
        <?php if ($message): ?>
            <div class="mariage-alert mariage-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- PROGRAMME (timeline avec heure événement)  -->
        <!-- ========================================== -->
        <div class="mariage-section" id="programme">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Programme</div>
                <div class="mariage-section-title-sub">De la journée</div>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-time"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                        <div class="timeline-title">Cérémonie</div>
                        <div class="timeline-desc">Mariage & Bénédiction</div>
                    </div>
                    <div class="timeline-icon"><i class="fas fa-ring"></i></div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-time">+2h</div>
                        <div class="timeline-title">Réception</div>
                        <div class="timeline-desc">Cocktail & Festivités</div>
                    </div>
                    <div class="timeline-icon"><i class="fas fa-glass-cheers"></i></div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-time">+4h</div>
                        <div class="timeline-title">Dîner</div>
                        <div class="timeline-desc">Repas de fête</div>
                    </div>
                    <div class="timeline-icon"><i class="fas fa-utensils"></i></div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-time">+6h</div>
                        <div class="timeline-title">Soirée</div>
                        <div class="timeline-desc">Danse & Célébration</div>
                    </div>
                    <div class="timeline-icon"><i class="fas fa-music"></i></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CODE COULEUR                               -->
        <!-- ========================================== -->
        <div class="mariage-section" id="couleur">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Code Couleur</div>
                <div class="mariage-section-title-sub">Palette du jour</div>
            </div>
            
            <div class="color-palette">
                <div>
                    <div class="color-swatch" style="background: #7c4dbe;"></div>
                    <div class="color-label">Violet</div>
                </div>
                <div>
                    <div class="color-swatch" style="background: #e8a8c8;"></div>
                    <div class="color-label">Rose</div>
                </div>
                <div>
                    <div class="color-swatch" style="background: #b794d4;"></div>
                    <div class="color-label">Lilas</div>
                </div>
                <div>
                    <div class="color-swatch" style="background: #f5ede0;"></div>
                    <div class="color-label">Crème</div>
                </div>
            </div>
            
            <p style="text-align: center; font-family: 'Cormorant Garamond', serif; font-size: 15px; font-style: italic; color: var(--text-light); margin-top: 16px;">
                Nous vous invitons à porter une touche de ces couleurs pour célébrer avec nous.
            </p>
        </div>

        <!-- ========================================== -->
        <!-- DIAPORAMA PHOTOS                           -->
        <!-- ========================================== -->
        <?php if ($hasPhotos && count($photosHost) > 1): ?>
        <div class="mariage-section" id="photos">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Nos Souvenirs</div>
                <div class="mariage-section-title-sub">Galerie photos</div>
            </div>
            
            <div class="mariage-diaporama" id="mariageDiaporama">
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
            
            <div class="mariage-diapo-nav">
                <button class="mariage-diapo-btn" onclick="mariageDiapoChange(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="mariage-diapo-counter" id="mariageDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                <button class="mariage-diapo-btn" onclick="mariageDiapoChange(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="mariage-diapo-dots" id="mariageDiapoDots">
                <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                    <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="mariageDiapoGoTo(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- CADEAUX                                    -->
        <!-- ========================================== -->
        <div class="mariage-section" id="cadeaux">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Cadeaux</div>
                <div class="mariage-section-title-sub">Liste de mariage</div>
            </div>
            
            <div class="gift-info">
                <div class="gift-icon"><i class="fas fa-gift"></i></div>
                <p class="gift-message">
                    Votre présence à nos côtés est le plus beau cadeau que vous puissiez nous offrir.
                    <br><br>
                    Si toutefois vous souhaitez nous gâter, une urne sera à votre disposition le jour de la célébration.
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CONFIRMATION                               -->
        <!-- ========================================== -->
        <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="mariage-section" id="confirmation">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Confirmation</div>
                <div class="mariage-section-title-sub">Votre présence</div>
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="mariage-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="mariage-form-group">
                    <label>Votre réponse</label>
                    <div class="mariage-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="mariageOui" value="CONFIRMEE" checked class="mariage-option-radio">
                            <label for="mariageOui" class="mariage-option-label">
                                <i class="fas fa-check"></i> Je serai là
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="mariageNon" value="REFUSEE" class="mariage-option-radio">
                            <label for="mariageNon" class="mariage-option-label">
                                <i class="fas fa-times"></i> Je ne peux pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mariage-form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message_invite" rows="3" placeholder="Un petit mot..."></textarea>
                </div>
                
                <button type="submit" class="mariage-btn-submit">
                    <i class="fas fa-check"></i>
                    Envoyer ma réponse
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- BOISSONS                                   -->
        <!-- ========================================== -->
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="mariage-section" id="boissons">
            <div class="mariage-section-title">
                <div class="mariage-section-title-text">Boissons</div>
                <div class="mariage-section-title-sub">Vos préférences</div>
            </div>
            
            <?php if ($isLocked): ?>
                <div style="text-align: center; color: var(--violet); font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; padding: 20px 0;">
                    <i class="fas fa-lock"></i> Préférences enregistrées
                </div>
                <div class="mariage-boisson-grid" style="justify-content: center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="mariage-boisson-item selected" style="cursor: default;">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; font-family: 'Cormorant Garamond', serif; font-size: 15px; color: var(--text-light); margin-bottom: 20px; font-style: italic;">
                    Choisissez jusqu'à <strong style="color: var(--violet);">2 boissons</strong> : <span id="mariageSelectedCount">0</span>/2
                </p>
                
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                    <input type="hidden" name="action" value="preferences">
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="mariage-boisson-category">
                            <div class="mariage-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="mariage-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="mariage-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $b['id']; ?>"
                                         onclick="mariageToggleBoisson(this, <?php echo $b['id']; ?>)">
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
                    
                    <button type="submit" class="mariage-btn-submit" style="margin-top: 20px;">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MESSAGE FINAL                              -->
        <!-- ========================================== -->
        <div class="final-message">
            <p class="final-message-text">
                Pour ceux qui célèbrent avec nous de loin, sachez que votre pensée nous touche profondément.
                <br><br>
                Que vous soyez présents ou non, vous faites partie de notre histoire.
            </p>
            <div class="final-message-signature">
                <?php echo htmlspecialchars($invitation['evenement_nom']); ?>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- FOOTER                                     -->
        <!-- ========================================== -->
        <footer class="mariage-footer">
            <div class="mariage-footer-flower">
                <?php echo svgFlowerRomantic(30, 0); ?>
                <?php echo svgFlowerRomantic(30, 0); ?>
                <?php echo svgFlowerRomantic(30, 0); ?>
            </div>
            <div class="mariage-footer-app"><?php echo htmlspecialchars($appName); ?></div>
            <div class="mariage-footer-tagline">Invitation d'exception</div>
            
            <a href="https://wa.me/243963967028?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20mon%20invitation" 
               target="_blank" 
               rel="noopener"
               class="mariage-whatsapp">
                <i class="fab fa-whatsapp"></i>
                Nous contacter
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(124, 77, 190, 0.15); font-family: 'Cinzel', serif; font-size: 9px; letter-spacing: 0.3em; text-transform: uppercase; color: var(--text-light);">
                © <?php echo date('Y'); ?> · Tous droits réservés
            </div>
        </footer>

    </div>

    <!-- BOUTON TÉLÉCHARGEMENT -->
    <button id="mariageDownloadBtn" onclick="mariageDownload()">
        <i class="fas fa-download"></i>
        <span id="mariageBtnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // QR CODE
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('mariageQrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 160,
                        height: 160,
                        colorDark: '#4a3428',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) {
                    console.error('Erreur QR code:', e);
                }
            }
        });

        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.mariage-section');
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
        // DIAPORAMA
        // ================================================================
        let mariageDiapoIndex = 0;
        const mariageSlides = document.querySelectorAll('#mariageDiaporama .slide');
        const mariageDots = document.querySelectorAll('#mariageDiapoDots span');
        const mariageCounter = document.getElementById('mariageDiapoCounter');
        let mariageDiapoInterval = null;

        function mariageUpdateDiapo() {
            mariageSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === mariageDiapoIndex);
            });
            mariageDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === mariageDiapoIndex);
            });
            if (mariageCounter) {
                mariageCounter.textContent = (mariageDiapoIndex + 1) + ' / ' + mariageSlides.length;
            }
        }

        function mariageDiapoChange(direction) {
            mariageDiapoIndex += direction;
            if (mariageDiapoIndex < 0) mariageDiapoIndex = mariageSlides.length - 1;
            if (mariageDiapoIndex >= mariageSlides.length) mariageDiapoIndex = 0;
            mariageUpdateDiapo();
            resetMariageDiapoAuto();
        }

        function mariageDiapoGoTo(index) {
            mariageDiapoIndex = index;
            mariageUpdateDiapo();
            resetMariageDiapoAuto();
        }

        function resetMariageDiapoAuto() {
            if (mariageDiapoInterval) clearInterval(mariageDiapoInterval);
            if (mariageSlides.length > 1) {
                mariageDiapoInterval = setInterval(() => {
                    mariageDiapoIndex = (mariageDiapoIndex + 1) % mariageSlides.length;
                    mariageUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (mariageSlides.length > 0) {
                mariageUpdateDiapo();
                resetMariageDiapoAuto();
                
                const container = document.getElementById('mariageDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (mariageDiapoInterval) clearInterval(mariageDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetMariageDiapoAuto);
                }
            }
        });

        // ================================================================
        // BOISSONS
        // ================================================================
        let mariageSelectedBoissons = [];

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.mariage-boisson-item.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!isNaN(id) && !mariageSelectedBoissons.includes(id)) {
                    mariageSelectedBoissons.push(id);
                }
            });
            mariageUpdateBoissonCount();
        });

        function mariageToggleBoisson(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const index = mariageSelectedBoissons.indexOf(id);
                if (index > -1) mariageSelectedBoissons.splice(index, 1);
                const checkbox = element.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
                mariageUpdateBoissonCount();
                return;
            }
            
            if (mariageSelectedBoissons.length >= 2) {
                alert('Vous ne pouvez sélectionner que 2 boissons maximum.');
                return;
            }
            
            element.classList.add('selected');
            mariageSelectedBoissons.push(id);
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
            mariageUpdateBoissonCount();
        }

        function mariageUpdateBoissonCount() {
            const el = document.getElementById('mariageSelectedCount');
            if (el) el.textContent = mariageSelectedBoissons.length;
            
            document.querySelectorAll('.mariage-boisson-item').forEach(item => {
                if (!item.classList.contains('selected') && mariageSelectedBoissons.length >= 2) {
                    item.style.opacity = '0.4';
                    item.style.cursor = 'not-allowed';
                } else {
                    item.style.opacity = '1';
                    item.style.cursor = 'pointer';
                }
            });
        }

        // ================================================================
        // TÉLÉCHARGEMENT : CAPTURE UNIQUEMENT LA CARTE (#mariageCard)
        // ================================================================
        async function mariageDownload() {
            const btn = document.getElementById('mariageDownloadBtn');
            const btnText = document.getElementById('mariageBtnText');
            const card = document.getElementById('mariageCard');
            
            if (!card) {
                alert('Carte introuvable');
                return;
            }
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                // Attendre que le QR code soit bien rendu
                await new Promise(r => setTimeout(r, 600));
                
                // S'assurer que les images sont chargées
                const images = card.querySelectorAll('img');
                await Promise.all(Array.from(images).map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(resolve => {
                        img.onload = resolve;
                        img.onerror = resolve;
                    });
                }));
                
                // Capture UNIQUEMENT la carte
                const canvas = await html2canvas(card, {
                    scale: 2.5,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    width: card.offsetWidth,
                    height: card.offsetHeight,
                    windowWidth: card.scrollWidth,
                    windowHeight: card.scrollHeight,
                    scrollX: 0,
                    scrollY: 0,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.getElementById('mariageCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                            clonedCard.style.transform = 'none';
                        }
                        // S'assurer que les fleurs SVG sont visibles
                        clonedDoc.querySelectorAll('.card-corner-flower').forEach(el => {
                            el.style.opacity = '1';
                        });
                    }
                });
                
                const link = document.createElement('a');
                const name = '<?php echo htmlspecialchars($invitation['evenement_nom']); ?>';
                link.download = `invitation_mariage_${name.replace(/\s/g, '_')}.jpg`;
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