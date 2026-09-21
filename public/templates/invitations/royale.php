<?php
/**
 * ============================================================
 * TEMPLATE : ROYAL — Invitation majestueuse
 * ============================================================
 */

$monogramLetter = mb_substr($host1, 0, 1);
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);

$rsvpLabel = 'En attente';
$rsvpClass = 'pending';
if (($invitation['statut'] ?? '') === 'CONFIRMEE') {
    $rsvpLabel = 'Confirmé';
    $rsvpClass = 'confirmed';
} elseif (($invitation['statut'] ?? '') === 'REFUSEE') {
    $rsvpLabel = 'Refusé';
    $rsvpClass = 'refused';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Royale - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Great+Vibes&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --royal-gold: #c9a961;
            --royal-gold-dark: #8b6f3f;
            --royal-gold-light: #e8d5a0;
            --royal-cream: #faf6ee;
            --royal-paper: #fdfbf6;
            --royal-ink: #2a2420;
            --royal-ink-light: #5a4e42;
            --royal-burgundy: #6b1e2e;
            --royal-navy: #1a1f2e;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: #1a1f2e;
            color: var(--royal-ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        
        /* ============================================
           TRANSITION D'OUVERTURE
           ============================================ */
        .royal-curtain {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: curtainLift 3.5s cubic-bezier(0.65, 0, 0.35, 1) 2.5s forwards;
        }
        
        @keyframes curtainLift {
            0% { clip-path: inset(0 0 0 0); }
            100% { clip-path: inset(0 0 100% 0); }
        }
        
        .royal-curtain::before,
        .royal-curtain::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 50%;
            background: 
                repeating-linear-gradient(90deg,
                    transparent 0px,
                    rgba(201, 169, 97, 0.15) 2px,
                    transparent 4px,
                    rgba(201, 169, 97, 0.05) 8px,
                    transparent 12px
                ),
                linear-gradient(180deg, #1a0f05 0%, #2a1810 50%, #1a0f05 100%);
            box-shadow: inset 0 0 100px rgba(0,0,0,0.8);
            animation: curtainOpen 2.5s cubic-bezier(0.65, 0, 0.35, 1) 2.5s forwards;
        }
        .royal-curtain::before {
            left: 0;
            transform-origin: left center;
            border-right: 2px solid var(--royal-gold);
        }
        .royal-curtain::after {
            right: 0;
            transform-origin: right center;
            border-left: 2px solid var(--royal-gold);
        }
        
        @keyframes curtainOpen {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        @keyframes curtainOpenRight {
            0% { transform: translateX(0); }
            100% { transform: translateX(100%); }
        }
        .royal-curtain::after {
            animation: curtainOpenRight 2.5s cubic-bezier(0.65, 0, 0.35, 1) 2.5s forwards;
        }
        
        .curtain-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--royal-gold);
            animation: curtainContentFade 2.5s ease-in-out 2.5s forwards;
        }
        
        @keyframes curtainContentFade {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.3); }
        }
        
        .curtain-crown {
            font-size: 80px;
            color: var(--royal-gold);
            margin-bottom: 20px;
            animation: crownGlow 2s ease-in-out infinite alternate;
            text-shadow: 0 0 40px rgba(201, 169, 97, 0.8);
        }
        
        @keyframes crownGlow {
            0% { text-shadow: 0 0 20px rgba(201, 169, 97, 0.5); }
            100% { text-shadow: 0 0 60px rgba(201, 169, 97, 1), 0 0 100px rgba(201, 169, 97, 0.6); }
        }
        
        .curtain-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(28px, 6vw, 56px);
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--royal-gold);
            margin-bottom: 16px;
            text-shadow: 0 0 30px rgba(201, 169, 97, 0.6);
            animation: titleReveal 1.5s ease-out 0.3s both;
        }
        
        @keyframes titleReveal {
            from { opacity: 0; letter-spacing: 0.8em; }
            to { opacity: 1; letter-spacing: 0.3em; }
        }
        
        .curtain-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
            opacity: 0;
            animation: fadeIn 1s ease-out 1.2s forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        .curtain-divider .line {
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        }
        
        .curtain-divider .diamond {
            width: 8px;
            height: 8px;
            background: var(--royal-gold);
            transform: rotate(45deg);
            box-shadow: 0 0 12px var(--royal-gold);
        }
        
        /* Particules dorées */
        .gold-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 1;
        }
        .gold-particles span {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--royal-gold);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--royal-gold);
            animation: particleFloat 4s ease-in-out infinite;
        }
        .gold-particles span:nth-child(1) { top: 15%; left: 10%; animation-delay: 0s; }
        .gold-particles span:nth-child(2) { top: 25%; right: 15%; animation-delay: 0.5s; width: 6px; height: 6px; }
        .gold-particles span:nth-child(3) { bottom: 30%; left: 20%; animation-delay: 1s; }
        .gold-particles span:nth-child(4) { bottom: 20%; right: 25%; animation-delay: 1.5s; width: 5px; height: 5px; }
        .gold-particles span:nth-child(5) { top: 50%; left: 5%; animation-delay: 2s; }
        .gold-particles span:nth-child(6) { top: 60%; right: 8%; animation-delay: 2.5s; width: 7px; height: 7px; }
        
        @keyframes particleFloat {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
            50% { transform: translateY(-50px) scale(1.5); opacity: 1; }
        }
        
        /* ============================================
           FOND GÉNÉRAL (MOINS SOMBRE)
           ============================================ */
        .royal-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: #1a1f2e;
        }
        .royal-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            /* MOINS SOMBRE : 0.6 au lieu de 0.35 */
            filter: brightness(0.6) saturate(0.9);
        }
        .royal-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            /* OVERLAY MOINS OPAQUE */
            background: 
                radial-gradient(circle at 50% 50%, transparent 0%, rgba(10,10,15,0.4) 100%),
                linear-gradient(180deg, rgba(10,10,15,0.25) 0%, rgba(10,10,15,0.5) 100%);
        }
        
        /* ============================================
           WRAPPER
           ============================================ */
        .royal-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 20px 80px;
            position: relative;
            z-index: 1;
            opacity: 0;
            animation: wrapperFadeIn 1.5s ease-out 4s forwards;
        }
        
        @keyframes wrapperFadeIn {
            to { opacity: 1; }
        }
        
        /* ============================================
           ORNEMENTS DE COIN
           ============================================ */
        .corner-ornament {
            position: fixed;
            width: 120px;
            height: 120px;
            border: 2px solid var(--royal-gold);
            z-index: 5;
            pointer-events: none;
            opacity: 0;
            animation: ornamentFadeIn 1.5s ease-out 4.5s forwards;
        }
        .corner-ornament::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--royal-gold);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--royal-gold);
        }
        
        .corner-tl { top: 30px; left: 30px; border-right: none; border-bottom: none; }
        .corner-tl::before { top: 4px; right: 4px; }
        .corner-tr { top: 30px; right: 30px; border-left: none; border-bottom: none; }
        .corner-tr::before { top: 4px; left: 4px; }
        .corner-bl { bottom: 30px; left: 30px; border-right: none; border-top: none; }
        .corner-bl::before { bottom: 4px; right: 4px; }
        .corner-br { bottom: 30px; right: 30px; border-left: none; border-top: none; }
        .corner-br::before { bottom: 4px; left: 4px; }
        
        @keyframes ornamentFadeIn {
            to { opacity: 0.6; }
        }
        
        @media (max-width: 768px) {
            .corner-ornament { width: 60px; height: 60px; }
            .corner-tl, .corner-tr { top: 15px; }
            .corner-bl, .corner-br { bottom: 15px; }
            .corner-tl, .corner-bl { left: 15px; }
            .corner-tr, .corner-br { right: 15px; }
        }
        
        /* ============================================
           PARCHEMIN PRINCIPAL
           ============================================ */
        .royal-scroll {
            position: relative;
            background: var(--royal-paper);
            background-image: 
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><filter id="n"><feTurbulence baseFrequency="0.9" numOctaves="4"/></filter><rect width="200" height="200" filter="url(%23n)" opacity="0.03"/></svg>'),
                radial-gradient(circle at 50% 0%, rgba(201, 169, 97, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 50% 100%, rgba(107, 30, 46, 0.05) 0%, transparent 60%);
            padding: 70px 50px 60px;
            box-shadow: 
                0 30px 80px rgba(0,0,0,0.6),
                0 0 0 1px rgba(201, 169, 97, 0.3),
                0 0 0 8px rgba(10, 10, 15, 0.4),
                0 0 0 9px rgba(201, 169, 97, 0.5),
                0 0 80px rgba(201, 169, 97, 0.1);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        
        /* ============================================
           RESPONSIVE MOBILE : SECTIONS MOINS LARGES
           ============================================ */
        @media (max-width: 768px) {
            .royal-wrapper {
                padding: 40px 16px 60px;
                max-width: 100%;
            }
            .royal-scroll {
                padding: 45px 22px 40px;
                margin: 0 auto;
            }
            .royal-section {
                padding: 30px 20px;
                margin-top: 30px;
            }
        }
        
        @media (max-width: 480px) {
            .royal-wrapper {
                padding: 30px 14px 50px;
            }
            .royal-scroll {
                padding: 35px 18px 30px;
            }
            .royal-section {
                padding: 25px 16px;
                margin-top: 25px;
            }
        }
        
        /* Coins du parchemin */
        .royal-scroll::before,
        .royal-scroll::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 60px;
            border: 2px solid var(--royal-gold);
            pointer-events: none;
        }
        .royal-scroll::before { top: 12px; left: 12px; border-right: none; border-bottom: none; }
        .royal-scroll::after { bottom: 12px; right: 12px; border-left: none; border-top: none; }
        
        @media (max-width: 480px) {
            .royal-scroll::before,
            .royal-scroll::after { width: 40px; height: 40px; }
        }
        
        /* Filigrane couronne */
        .scroll-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 500px;
            color: rgba(201, 169, 97, 0.04);
            pointer-events: none;
            z-index: 0;
            line-height: 1;
        }
        
        @media (max-width: 768px) {
            .scroll-watermark { font-size: 300px; }
        }
        
        .scroll-content {
            position: relative;
            z-index: 1;
        }
        
        /* ============================================
           BLASON
           ============================================ */
        .coat-of-arms {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0;
            transform: scale(0.5);
            animation: coatAppear 1s cubic-bezier(0.34, 1.56, 0.64, 1) 4.2s forwards;
        }
        
        @keyframes coatAppear {
            to { opacity: 1; transform: scale(1); }
        }
        
        .crown-icon {
            font-size: 56px;
            color: var(--royal-gold);
            margin-bottom: 8px;
            filter: drop-shadow(0 4px 8px rgba(201, 169, 97, 0.4));
        }
        
        @media (max-width: 480px) {
            .crown-icon { font-size: 44px; }
        }
        
        .coat-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
        }
        
        .coat-divider .line {
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        }
        
        .coat-divider .diamond {
            width: 6px;
            height: 6px;
            background: var(--royal-gold);
            transform: rotate(45deg);
            box-shadow: 0 0 8px var(--royal-gold);
        }
        
        /* ============================================
           BADGE
           ============================================ */
        .royal-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            background: linear-gradient(135deg, rgba(201, 169, 97, 0.15), rgba(139, 111, 63, 0.15));
            border: 1px solid var(--royal-gold);
            border-radius: 9999px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--royal-gold-dark);
            margin-bottom: 30px;
        }
        
        @media (max-width: 480px) {
            .royal-badge { font-size: 9px; padding: 6px 14px; letter-spacing: 0.2em; }
        }
        
        .royal-badge i {
            color: var(--royal-gold);
            font-size: 12px;
        }
        
        /* ============================================
           DESTINATAIRE
           ============================================ */
        .recipient-block {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(201, 169, 97, 0.3);
            position: relative;
        }
        
        .recipient-block::after {
            content: '❦';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--royal-paper);
            padding: 0 14px;
            color: var(--royal-gold);
            font-size: 20px;
        }
        
        .recipient-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--royal-ink-light);
            margin-bottom: 10px;
        }
        
        .recipient-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            font-style: italic;
            color: var(--royal-ink);
            letter-spacing: 0.02em;
        }
        
        @media (max-width: 480px) {
            .recipient-name { font-size: 22px; }
        }
        
        /* ============================================
           TITRE ROYAL
           ============================================ */
        .royal-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .royal-subtitle {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .royal-subtitle { font-size: 10px; letter-spacing: 0.25em; }
        }
        
        .royal-host {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(48px, 9vw, 90px);
            line-height: 1;
            color: var(--royal-ink);
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(201, 169, 97, 0.2);
        }
        
        .royal-event-type {
            font-family: 'Cinzel', serif;
            font-size: 14px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            .royal-event-type { font-size: 11px; letter-spacing: 0.3em; }
        }
        
        /* ============================================
           DATE / HEURE
           ============================================ */
        .royal-date-block {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            margin: 40px 0;
            flex-wrap: wrap;
        }
        
        .date-item {
            text-align: center;
            padding: 0 16px;
        }
        
        .date-value {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--royal-ink);
            letter-spacing: 0.05em;
        }
        
        @media (max-width: 480px) {
            .date-value { font-size: 20px; }
        }
        
        .date-label {
            font-family: 'Cinzel', serif;
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            margin-top: 4px;
        }
        
        .date-separator {
            width: 1px;
            height: 40px;
            background: linear-gradient(180deg, transparent, var(--royal-gold), transparent);
        }
        
        @media (max-width: 480px) {
            .date-separator { display: none; }
            .date-item { padding: 8px 0; }
        }
        
        /* ============================================
           DESCRIPTION
           ============================================ */
        .royal-description {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            line-height: 1.85;
            color: var(--royal-ink);
            text-align: center;
            margin: 40px 0;
            padding: 0 20px;
            font-weight: 400;
        }
        
        @media (max-width: 480px) {
            .royal-description { font-size: 17px; padding: 0 5px; }
        }
        
        .royal-description p {
            margin-bottom: 16px;
        }
        
        .royal-description p:first-child::first-letter {
            font-family: 'Great Vibes', cursive;
            font-size: 60px;
            float: left;
            line-height: 0.8;
            margin: 4px 12px 0 0;
            color: var(--royal-burgundy);
        }
        
        /* ============================================
           LIEU
           ============================================ */
        .royal-location {
            margin: 40px 0;
            padding: 24px;
            background: linear-gradient(135deg, rgba(201, 169, 97, 0.08), rgba(201, 169, 97, 0.03));
            border: 1px solid rgba(201, 169, 97, 0.4);
            border-radius: 4px;
            text-align: center;
            position: relative;
        }
        
        @media (max-width: 480px) {
            .royal-location { padding: 18px; margin: 30px 0; }
        }
        
        .royal-location::before,
        .royal-location::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid var(--royal-gold);
        }
        .royal-location::before { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .royal-location::after { bottom: -2px; right: -2px; border-left: none; border-top: none; }
        
        .location-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            margin-bottom: 12px;
        }
        
        .location-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            font-style: italic;
            color: var(--royal-ink);
            margin-bottom: 6px;
        }
        
        @media (max-width: 480px) {
            .location-name { font-size: 18px; }
        }
        
        .location-address {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: var(--royal-ink-light);
            margin-bottom: 16px;
        }
        
        @media (max-width: 480px) {
            .location-address { font-size: 14px; }
        }
        
        .btn-royal-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            background: transparent;
            border: 1px solid var(--royal-gold);
            color: var(--royal-gold-dark);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-royal-itinerary:hover {
            background: var(--royal-gold);
            color: white;
            box-shadow: 0 4px 16px rgba(201, 169, 97, 0.4);
        }
        
        @media (max-width: 480px) {
            .btn-royal-itinerary { font-size: 10px; padding: 9px 18px; }
        }
        
        /* ============================================
           TABLE
           ============================================ */
        .royal-table-info {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, rgba(107, 30, 46, 0.05), rgba(107, 30, 46, 0.02));
            border: 1px solid rgba(107, 30, 46, 0.3);
            text-align: center;
        }
        
        .table-label {
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--royal-burgundy);
            margin-bottom: 8px;
        }
        
        .table-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--royal-ink);
        }
        
        @media (max-width: 480px) {
            .table-value { font-size: 18px; }
        }
        
        /* ============================================
           SCEAU ROYAL
           ============================================ */
        .royal-seal {
            position: absolute;
            bottom: -30px;
            right: 30px;
            width: 120px;
            height: 120px;
            z-index: 10;
            animation: sealDrop 1s cubic-bezier(0.34, 1.56, 0.64, 1) 5s forwards;
            opacity: 0;
            transform: translateY(-100px) rotate(180deg);
        }
        
        @media (max-width: 480px) {
            .royal-seal { width: 80px; height: 80px; right: 15px; bottom: -20px; }
        }
        
        @keyframes sealDrop {
            0% { opacity: 0; transform: translateY(-100px) rotate(180deg); }
            60% { opacity: 1; transform: translateY(10px) rotate(-10deg); }
            100% { opacity: 1; transform: translateY(0) rotate(0deg); }
        }
        
        .seal-circle {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #8b1a1a 0%, #5a0f0f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--royal-gold-light);
            box-shadow: 
                0 10px 30px rgba(0,0,0,0.5),
                inset -6px -6px 12px rgba(0,0,0,0.4),
                inset 6px 6px 12px rgba(255,255,255,0.15);
        }
        
        .seal-circle::before {
            content: '';
            position: absolute;
            inset: 14px;
            border: 2px solid rgba(201, 169, 97, 0.5);
            border-radius: 50%;
        }
        
        .seal-circle::after {
            content: '';
            position: absolute;
            inset: 20px;
            border: 1px solid rgba(201, 169, 97, 0.3);
            border-radius: 50%;
        }
        
        .seal-inner {
            font-size: 40px;
            z-index: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        
        @media (max-width: 480px) {
            .seal-inner { font-size: 28px; }
        }
        
        /* ============================================
           TITRES DE SECTION
           ============================================ */
        .royal-section-title {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-align: center;
            color: var(--royal-ink);
            margin-bottom: 24px;
            position: relative;
            padding-bottom: 16px;
        }
        
        @media (max-width: 480px) {
            .royal-section-title { font-size: 14px; letter-spacing: 0.2em; }
        }
        
        .royal-section-title::after {
            content: '❦';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--royal-paper);
            padding: 0 14px;
            color: var(--royal-gold);
            font-size: 18px;
        }
        
        .royal-section-title::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 15%;
            right: 15%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        }
        
        /* ============================================
           SECTIONS SUIVANTES
           ============================================ */
        .royal-section {
            background: var(--royal-paper);
            background-image: 
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><filter id="n"><feTurbulence baseFrequency="0.9" numOctaves="4"/></filter><rect width="200" height="200" filter="url(%23n)" opacity="0.03"/></svg>');
            padding: 40px 40px 40px;
            margin-top: 40px;
            box-shadow: 
                0 20px 60px rgba(0,0,0,0.5),
                0 0 0 1px rgba(201, 169, 97, 0.3),
                0 0 0 6px rgba(10, 10, 15, 0.4),
                0 0 0 7px rgba(201, 169, 97, 0.5);
            border-radius: 4px;
            position: relative;
            opacity: 0;
            transform: translateY(50px);
            transition: all 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .royal-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        
        .royal-section::before,
        .royal-section::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border: 2px solid var(--royal-gold);
            pointer-events: none;
        }
        .royal-section::before { top: 8px; left: 8px; border-right: none; border-bottom: none; }
        .royal-section::after { bottom: 8px; right: 8px; border-left: none; border-top: none; }
        
        @media (max-width: 480px) {
            .royal-section::before,
            .royal-section::after { width: 25px; height: 25px; }
        }
        
        /* ============================================
           DIAPORAMA PHOTOS
           ============================================ */
        .royal-diaporama {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: #0a0a0a;
            border: 2px solid var(--royal-gold);
            box-shadow: inset 0 0 60px rgba(0,0,0,0.8);
        }
        
        .royal-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .royal-diaporama .slide.active {
            opacity: 1;
        }
        
        .royal-diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        
        .royal-diapo-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
        }
        
        .royal-diapo-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: transparent;
            border: 1px solid var(--royal-gold);
            color: var(--royal-gold-dark);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .royal-diapo-btn:hover {
            background: var(--royal-gold);
            color: white;
            transform: scale(1.1);
        }
        
        .royal-diapo-counter {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            letter-spacing: 0.15em;
            color: var(--royal-ink-light);
            min-width: 80px;
            text-align: center;
        }
        
        .royal-diapo-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }
        
        .royal-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(201, 169, 97, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .royal-diapo-dots span.active {
            background: var(--royal-gold);
            transform: scale(1.3);
            box-shadow: 0 0 12px var(--royal-gold);
        }
        
        /* ============================================
           FORMULAIRES
           ============================================ */
        .royal-form-group {
            margin-bottom: 24px;
        }
        
        .royal-form-group label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .royal-form-group input,
        .royal-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(201, 169, 97, 0.4);
            color: var(--royal-ink);
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            transition: all 0.3s ease;
        }
        
        .royal-form-group input:focus,
        .royal-form-group textarea:focus {
            outline: none;
            border-color: var(--royal-gold);
            background: white;
            box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.15);
        }
        
        .royal-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        @media (max-width: 480px) {
            .royal-options-grid { grid-template-columns: 1fr; }
        }
        
        .royal-option-radio { display: none; }
        
        .royal-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(201, 169, 97, 0.4);
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--royal-ink-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .royal-option-label:hover {
            border-color: var(--royal-gold);
            background: white;
        }
        
        .royal-option-radio:checked + .royal-option-label {
            border-color: var(--royal-gold);
            background: linear-gradient(135deg, rgba(201, 169, 97, 0.15), rgba(201, 169, 97, 0.05));
            color: var(--royal-ink);
            font-weight: 700;
        }
        
        .btn-royal-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--royal-gold) 0%, var(--royal-gold-dark) 100%);
            color: white;
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 13px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.4);
        }
        
        .btn-royal-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(201, 169, 97, 0.6);
        }
        
        /* ============================================
           BOISSONS
           ============================================ */
        .royal-boisson-category {
            margin-bottom: 24px;
        }
        
        .royal-boisson-category-title {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--royal-gold-dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .royal-boisson-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .royal-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(201, 169, 97, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: var(--royal-ink-light);
        }
        
        .royal-boisson-item:hover {
            border-color: var(--royal-gold);
            transform: translateY(-2px);
        }
        
        .royal-boisson-item.selected {
            border-color: var(--royal-gold);
            background: linear-gradient(135deg, rgba(201, 169, 97, 0.15), rgba(201, 169, 97, 0.05));
            color: var(--royal-ink);
            font-weight: 600;
        }
        
        .royal-boisson-item .check {
            opacity: 0;
            color: var(--royal-gold-dark);
            transition: opacity 0.3s ease;
        }
        
        .royal-boisson-item.selected .check {
            opacity: 1;
        }
        
        /* ============================================
           QR CODE
           ============================================ */
        .royal-qr-wrapper {
            text-align: center;
            padding: 20px 0;
        }
        
        .royal-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 2px solid var(--royal-gold);
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.3);
            position: relative;
        }
        
        .royal-qr-box::before,
        .royal-qr-box::after {
            content: '❦';
            position: absolute;
            color: var(--royal-gold);
            font-size: 20px;
        }
        
        .royal-qr-box::before { top: -12px; left: -12px; }
        .royal-qr-box::after { bottom: -12px; right: -12px; }
        
        /* ============================================
           MESSAGES
           ============================================ */
        .royal-alert {
            padding: 16px 24px;
            margin-bottom: 20px;
            font-size: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-left: 4px solid;
            background: rgba(255,255,255,0.7);
        }
        
        .royal-alert-success { border-color: #2d7a45; color: #2d7a45; }
        .royal-alert-danger  { border-color: var(--royal-burgundy); color: var(--royal-burgundy); }
        .royal-alert-warning { border-color: var(--royal-gold-dark); color: var(--royal-gold-dark); }
        
        /* ============================================
           FOOTER
           ============================================ */
        .royal-footer {
            text-align: center;
            margin-top: 40px;
            padding: 30px;
            color: var(--royal-cream);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            opacity: 0;
            animation: wrapperFadeIn 1s ease-out 5.5s forwards;
        }
        
        .royal-footer .divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .royal-footer .divider .line {
            width: 80px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        }
        
        .royal-footer .crown {
            color: var(--royal-gold);
            font-size: 24px;
        }
        
        .royal-footer .app-name {
            font-family: 'Great Vibes', cursive;
            font-size: 28px;
            letter-spacing: 0.02em;
            text-transform: none;
            color: var(--royal-gold);
            margin-bottom: 8px;
        }
        
        .royal-footer .whatsapp-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 12px 28px;
            background: transparent;
            border: 1px solid var(--royal-gold);
            color: var(--royal-gold);
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .royal-footer .whatsapp-link:hover {
            background: var(--royal-gold);
            color: #1a1f2e;
        }
        
        /* ============================================
           BOUTON TÉLÉCHARGEMENT
           ============================================ */
        #royalDownloadBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 28px;
            background: linear-gradient(135deg, var(--royal-gold) 0%, var(--royal-gold-dark) 100%);
            color: white;
            border: none;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(201, 169, 97, 0.5);
            opacity: 0;
            animation: wrapperFadeIn 1s ease-out 5.5s forwards;
        }
        
        #royalDownloadBtn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 32px rgba(201, 169, 97, 0.8);
        }
        
        #royalDownloadBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        @media (max-width: 480px) {
            #royalDownloadBtn {
                bottom: 16px;
                right: 16px;
                padding: 12px 18px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- TRANSITION D'OUVERTURE -->
    <div class="royal-curtain">
        <div class="gold-particles">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>
        <div class="curtain-content">
            <div class="curtain-crown">♛</div>
            <div class="curtain-title">Invitation</div>
            <div class="curtain-divider">
                <div class="line"></div>
                <div class="diamond"></div>
                <div class="line"></div>
            </div>
        </div>
    </div>

    <!-- FOND -->
    <div class="royal-bg"></div>

    <!-- ORNEMENTS DE COIN -->
    <div class="corner-ornament corner-tl"></div>
    <div class="corner-ornament corner-tr"></div>
    <div class="corner-ornament corner-bl"></div>
    <div class="corner-ornament corner-br"></div>

    <!-- CONTENU PRINCIPAL -->
    <div class="royal-wrapper">

        <!-- PARCHEMIN PRINCIPAL -->
        <div class="royal-scroll" id="royalCard">
            <div class="scroll-watermark">♛</div>
            
            <div class="scroll-content">
                
                <!-- Blason -->
                <div class="coat-of-arms">
                    <div class="crown-icon">♛</div>
                    <div class="coat-divider">
                        <div class="line"></div>
                        <div class="diamond"></div>
                        <div class="line"></div>
                    </div>
                </div>

                <!-- Badge -->
                <div style="text-align:center;">
                    <div class="royal-badge">
                        <i class="fas fa-crown"></i>
                        Invitation Royale
                    </div>
                </div>

                <!-- Destinataire -->
                <div class="recipient-block">
                    <div class="recipient-label">À l'attention de</div>
                    <div class="recipient-name"><?php echo htmlspecialchars($guestName); ?></div>
                </div>

                <!-- Titre -->
                <div class="royal-title">
                    <div class="royal-subtitle">Vous êtes convié(e) à célébrer</div>
                    <div class="royal-host"><?php echo htmlspecialchars($host1); ?></div>
                    <div class="royal-event-type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
                </div>

                <!-- Date / Heure -->
                <div class="royal-date-block">
                    <div class="date-item">
                        <div class="date-value"><?php echo htmlspecialchars($eventDate); ?></div>
                        <div class="date-label">Date</div>
                    </div>
                    <?php if ($eventTime): ?>
                    <div class="date-separator"></div>
                    <div class="date-item">
                        <div class="date-value"><?php echo htmlspecialchars($eventTime); ?></div>
                        <div class="date-label">Heure</div>
                    </div>
                    <?php endif; ?>
                    <div class="date-separator"></div>
                    <div class="date-item">
                        <div class="date-value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?></div>
                        <div class="date-label">Place(s)</div>
                    </div>
                </div>

                <!-- Description -->
                <div class="royal-description">
                    <?php if (!empty($eventDescription)): ?>
                        <?php 
                        $paragraphs = explode("\n", $eventDescription);
                        foreach ($paragraphs as $p):
                            if (trim($p) === '') continue;
                        ?>
                            <p><?php echo nl2br(htmlspecialchars(trim($p))); ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <?php echo htmlspecialchars($eventType); ?>.</p>
                        <p>Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.</p>
                    <?php endif; ?>
                </div>

                <!-- Lieu -->
                <div class="royal-location">
                    <div class="location-label">Lieu de la célébration</div>
                    <div class="location-name"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                    <?php if ($adresseDisplay): ?>
                        <div class="location-address"><?php echo htmlspecialchars($adresseDisplay); ?></div>
                    <?php endif; ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="btn-royal-itinerary">
                        <i class="fas fa-map-marked-alt"></i>
                        Itinéraire
                    </a>
                </div>

                <!-- Table -->
                <?php if ($hasTable): ?>
                <div class="royal-table-info">
                    <div class="table-label">Votre table</div>
                    <div class="table-value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                        <?php if (!empty($tableZone)): ?>
                            <span style="font-size: 16px; color: var(--royal-gold-dark);"> · Zone <?php echo htmlspecialchars($tableZone); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Séparateur -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin: 40px 0 30px;">
                    <div style="width: 60px; height: 1px; background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);"></div>
                    <div style="color: var(--royal-gold); font-size: 20px;">❦</div>
                    <div style="width: 60px; height: 1px; background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);"></div>
                </div>

                <!-- QR Code -->
                <div style="text-align: center;">
                    <div style="font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--royal-gold-dark); margin-bottom: 16px;">
                        Code d'accès
                    </div>
                    <div class="royal-qr-box">
                        <div id="royalQrcode"></div>
                    </div>
                    <div style="font-family: 'Cinzel', serif; font-size: 12px; letter-spacing: 0.15em; color: var(--royal-ink-light); margin-top: 16px;">
                        <?php echo htmlspecialchars($invitation['code_unique']); ?>
                    </div>
                </div>

                <!-- Signature : NOM COMPLET DE L'ÉVÉNEMENT -->
                <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(201, 169, 97, 0.3);">
                    <div style="text-align: left;">
                        <div style="font-family: 'Cormorant Garamond', serif; font-size: 16px; font-style: italic; color: var(--royal-ink-light); margin-bottom: 8px;">Avec toute notre affection,</div>
                        <div style="font-family: 'Great Vibes', cursive; font-size: 36px; line-height: 1; color: var(--royal-ink);">
                            <?php echo htmlspecialchars($invitation['evenement_nom']); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--royal-gold-dark); margin-bottom: 6px;">RSVP</div>
                        <div style="font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 600; color: <?php echo $rsvpClass === 'confirmed' ? '#2d7a45' : ($rsvpClass === 'refused' ? 'var(--royal-burgundy)' : 'var(--royal-ink-light)'); ?>;">
                            <?php echo htmlspecialchars($rsvpLabel); ?>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Sceau Royal -->
            <div class="royal-seal">
                <div class="seal-circle">
                    <div class="seal-inner">♛</div>
                </div>
            </div>
            
        </div>

        <!-- MESSAGES -->
        <?php if ($message): ?>
            <div class="royal-section apparue">
                <div class="royal-alert royal-alert-<?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- DIAPORAMA PHOTOS -->
        <?php if ($hasPhotos): ?>
        <div class="royal-section" id="royalPhotos">
            <div class="royal-section-title">Souvenirs</div>
            
            <div class="royal-diaporama" id="royalDiaporama">
                <?php 
                $photoIndex = 0;
                foreach ($photosHost as $index => $photo): 
                ?>
                    <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                             alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                             loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>"
                             crossorigin="anonymous"
                             onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#c9a961;font-size:80px;\'><i class=\'fas fa-image\'></i></div>'">
                    </div>
                <?php 
                    $photoIndex++;
                endforeach; 
                ?>
            </div>
            
            <?php if ($photoIndex > 0): ?>
            <div class="royal-diapo-nav">
                <button class="royal-diapo-btn" onclick="royalDiapoChange(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="royal-diapo-counter" id="royalDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                <button class="royal-diapo-btn" onclick="royalDiapoChange(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="royal-diapo-dots" id="royalDiapoDots">
                <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                    <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="royalDiapoGoTo(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CONFIRMATION -->
        <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="royal-section" id="royalConfirm">
            <div class="royal-section-title">Confirmation de présence</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="royal-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="royal-form-group">
                    <label>Votre réponse</label>
                    <div class="royal-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="royalOui" value="CONFIRMEE" checked class="royal-option-radio">
                            <label for="royalOui" class="royal-option-label">
                                <i class="fas fa-check"></i> Je confirme
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="royalNon" value="REFUSEE" class="royal-option-radio">
                            <label for="royalNon" class="royal-option-label">
                                <i class="fas fa-times"></i> Je ne peux pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="royal-form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message_invite" rows="3" placeholder="Un petit mot..."></textarea>
                </div>
                
                <button type="submit" class="btn-royal-submit">
                    <i class="fas fa-crown"></i>
                    Envoyer ma réponse
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- BOISSONS -->
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="royal-section" id="royalBoissons">
            <div class="royal-section-title">Préférences</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align: center; color: var(--royal-gold-dark); font-family: 'Cinzel', serif; font-size: 12px; letter-spacing: 0.2em; text-transform: uppercase; padding: 20px 0;">
                    <i class="fas fa-lock"></i> Préférences enregistrées
                </div>
                <div class="royal-boisson-grid" style="justify-content: center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="royal-boisson-item selected" style="cursor: default;">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; font-family: 'Cormorant Garamond', serif; font-size: 16px; color: var(--royal-ink-light); margin-bottom: 24px;">
                    Choisissez jusqu'à <strong style="color: var(--royal-gold-dark);">2 boissons</strong> : <span id="royalSelectedCount">0</span>/2
                </p>
                
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="royalPreferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="royal-boisson-category">
                            <div class="royal-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="royal-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="royal-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $b['id']; ?>"
                                         onclick="royalToggleBoisson(this, <?php echo $b['id']; ?>)">
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
                    
                    <button type="submit" class="btn-royal-submit" style="margin-top: 20px;">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <footer class="royal-footer">
            <div class="divider">
                <div class="line"></div>
                <div class="crown">♛</div>
                <div class="line"></div>
            </div>
            <div class="app-name"><?php echo htmlspecialchars($appName); ?></div>
            <div style="font-size: 10px; letter-spacing: 0.4em; color: rgba(201, 169, 97, 0.6); margin-top: 8px;">
                Invitation d'exception
            </div>
            
            <a href="https://wa.me/243963967028?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20mon%20invitation" 
               target="_blank" 
               rel="noopener"
               class="whatsapp-link">
                <i class="fab fa-whatsapp"></i>
                Nous contacter
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(201, 169, 97, 0.2); font-size: 10px; color: rgba(201, 169, 97, 0.5);">
                © <?php echo date('Y'); ?> · Tous droits réservés
            </div>
        </footer>

    </div>

    <!-- BOUTON TÉLÉCHARGEMENT -->
    <button id="royalDownloadBtn" onclick="royalDownload()">
        <i class="fas fa-download"></i>
        <span id="royalBtnText">Télécharger</span>
    </button>

    <script>
        // ANIMATIONS AU SCROLL
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.royal-section');
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

        // QR CODE
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('royalQrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180,
                        height: 180,
                        colorDark: '#2a2420',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) {
                    console.error('Erreur QR code:', e);
                }
            }
        });

        // DIAPORAMA
        let royalDiapoIndex = 0;
        const royalSlides = document.querySelectorAll('#royalDiaporama .slide');
        const royalDots = document.querySelectorAll('#royalDiapoDots span');
        const royalCounter = document.getElementById('royalDiapoCounter');
        let royalDiapoInterval = null;

        function royalUpdateDiapo() {
            royalSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === royalDiapoIndex);
            });
            royalDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === royalDiapoIndex);
            });
            if (royalCounter) {
                royalCounter.textContent = (royalDiapoIndex + 1) + ' / ' + royalSlides.length;
            }
        }

        function royalDiapoChange(direction) {
            royalDiapoIndex += direction;
            if (royalDiapoIndex < 0) royalDiapoIndex = royalSlides.length - 1;
            if (royalDiapoIndex >= royalSlides.length) royalDiapoIndex = 0;
            royalUpdateDiapo();
            resetRoyalDiapoAuto();
        }

        function royalDiapoGoTo(index) {
            royalDiapoIndex = index;
            royalUpdateDiapo();
            resetRoyalDiapoAuto();
        }

        function resetRoyalDiapoAuto() {
            if (royalDiapoInterval) clearInterval(royalDiapoInterval);
            if (royalSlides.length > 1) {
                royalDiapoInterval = setInterval(() => {
                    royalDiapoIndex = (royalDiapoIndex + 1) % royalSlides.length;
                    royalUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (royalSlides.length > 0) {
                royalUpdateDiapo();
                resetRoyalDiapoAuto();
                
                const container = document.getElementById('royalDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (royalDiapoInterval) clearInterval(royalDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetRoyalDiapoAuto);
                }
            }
        });

        // BOISSONS
        let royalSelectedBoissons = [];

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.royal-boisson-item.selected').forEach(item => {
                const id = parseInt(item.dataset.id);
                if (!isNaN(id) && !royalSelectedBoissons.includes(id)) {
                    royalSelectedBoissons.push(id);
                }
            });
            royalUpdateBoissonCount();
        });

        function royalToggleBoisson(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                const index = royalSelectedBoissons.indexOf(id);
                if (index > -1) royalSelectedBoissons.splice(index, 1);
                const checkbox = element.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
                royalUpdateBoissonCount();
                return;
            }
            
            if (royalSelectedBoissons.length >= 2) {
                alert('Vous ne pouvez sélectionner que 2 boissons maximum.');
                return;
            }
            
            element.classList.add('selected');
            royalSelectedBoissons.push(id);
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
            royalUpdateBoissonCount();
        }

        function royalUpdateBoissonCount() {
            const el = document.getElementById('royalSelectedCount');
            if (el) el.textContent = royalSelectedBoissons.length;
            
            document.querySelectorAll('.royal-boisson-item').forEach(item => {
                if (!item.classList.contains('selected') && royalSelectedBoissons.length >= 2) {
                    item.style.opacity = '0.4';
                    item.style.cursor = 'not-allowed';
                } else {
                    item.style.opacity = '1';
                    item.style.cursor = 'pointer';
                }
            });
        }

        // TÉLÉCHARGEMENT
        async function royalDownload() {
            const btn = document.getElementById('royalDownloadBtn');
            const btnText = document.getElementById('royalBtnText');
            const card = document.getElementById('royalCard');
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                await new Promise(r => setTimeout(r, 400));
                
                const canvas = await html2canvas(card, {
                    scale: 2.5,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#fdfbf6',
                    logging: false,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.getElementById('royalCard');
                        if (clonedCard) {
                            clonedCard.style.animation = 'none';
                            clonedCard.style.opacity = '1';
                        }
                    }
                });
                
                const link = document.createElement('a');
                const name = '<?php echo htmlspecialchars($invitation['evenement_nom']); ?>';
                link.download = `invitation_royale_${name.replace(/\s/g, '_')}.jpg`;
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