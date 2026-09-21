<?php
/**
 * ============================================================
 * TEMPLATE : TICKET - v2
 * ============================================================
 * 
 * Nouveautés v2 :
 * - Diaporama photos plein écran
 * - Animations de sections en cascade
 * - Nom de la table
 * - Photo de fond en background
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES
// ============================================================
$hasFond = !empty($pageBackground);
$hasPhotos = !empty($photosHost) && is_array($photosHost);
$hasTable = !empty($tableNom) || !empty($tableNumero);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        /* ============================================
           PHOTO DE FOND EN BACKGROUND
           ============================================ */
        html {
            background: #1a1a1a;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: #1a1a1a;
            <?php else: ?>
            background: #1a1a1a;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(255, 107, 53, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(255, 193, 7, 0.05) 0%, transparent 40%);
            <?php endif; ?>
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 16px;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* Overlay dégradé sur la photo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background: 
                radial-gradient(ellipse at top, rgba(26, 26, 26, 0.7) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(26, 26, 26, 0.75) 0%, 
                    rgba(26, 26, 26, 0.6) 30%,
                    rgba(26, 26, 26, 0.8) 70%,
                    rgba(26, 26, 26, 0.95) 100%);
            pointer-events: none;
        }
        
        .app-wrapper {
            max-width: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 60px;
            position: relative;
            z-index: 2;
        }
        
        /* ============================================
           ANIMATIONS DE SECTIONS EN CASCADE
           ============================================ */
        .ticket-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .ticket-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .ticket-anim.from-left {
            transform: translateX(-80px);
        }
        .ticket-anim.from-left.apparue {
            transform: translateX(0);
        }
        
        .ticket-anim.from-right {
            transform: translateX(80px);
        }
        .ticket-anim.from-right.apparue {
            transform: translateX(0);
        }
        
        .ticket-anim.zoom-in {
            transform: scale(0.85);
        }
        .ticket-anim.zoom-in.apparue {
            transform: scale(1);
        }
        
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           TICKET FORMAT
           ============================================ */
        .ticket {
            position: relative;
            width: 100%;
            max-width: 420px;
            background: #fefefe;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 
                0 20px 60px rgba(0,0,0,0.5),
                0 0 0 1px rgba(0,0,0,0.05);
            filter: drop-shadow(0 0 20px rgba(255, 107, 53, 0.15));
        }
        
        .ticket::before,
        .ticket::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: #1a1a1a;
            border-radius: 50%;
            z-index: 10;
        }
        .ticket::before {
            top: 55%;
            left: -12px;
            transform: translateY(-50%);
        }
        .ticket::after {
            top: 55%;
            right: -12px;
            transform: translateY(-50%);
        }
        
        .ticket-cut {
            position: absolute;
            top: 55%;
            left: 20px;
            right: 20px;
            height: 1px;
            border-top: 2px dashed rgba(0,0,0,0.15);
            z-index: 5;
            transform: translateY(-50%);
        }
        
        /* ============================================
           EN-TÊTE DU TICKET
           ============================================ */
        .ticket-header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            padding: 30px 24px;
            color: white;
            position: relative;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }
        .ticket-header::before {
            content: 'ADMIT ONE';
            position: absolute;
            top: 50%;
            right: -20px;
            transform: translateY(-50%) rotate(-90deg);
            font-family: 'Bebas Neue', sans-serif;
            font-size: 60px;
            opacity: 0.08;
            letter-spacing: 0.1em;
            color: white;
            pointer-events: none;
        }
        
        .ticket-brand {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .ticket-brand .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 0.15em;
            color: white;
        }
        .ticket-brand .num {
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255,255,255,0.7);
            text-align: right;
        }
        .ticket-brand .num strong {
            display: block;
            color: white;
            font-size: 14px;
            letter-spacing: 0.05em;
        }
        
        .ticket-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 56px;
            line-height: 0.9;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .ticket-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
        }
        
        /* ============================================
           CORPS DU TICKET
           ============================================ */
        .ticket-body {
            padding: 24px 24px 20px;
        }
        
        .ticket-guest {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .ticket-guest .label {
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 4px;
        }
        .ticket-guest .name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 32px;
            color: #1a1a1a;
            letter-spacing: 0.02em;
            line-height: 1;
        }
        
        .ticket-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .ticket-info-item .label {
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 4px;
        }
        .ticket-info-item .value {
            font-family: 'Space Mono', monospace;
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 0.02em;
        }
        
        /* ⭐ CARTE TABLE DANS LE TICKET */
        .ticket-table-info {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08), rgba(247, 147, 30, 0.05));
            border: 2px solid #ff6b35;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 16px 0;
            animation: tablePulse 3s ease-in-out infinite;
        }
        
        @keyframes tablePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.3); }
            50% { box-shadow: 0 0 25px 0 rgba(255, 107, 53, 0.5); }
        }
        
        .ticket-table-info .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #ff6b35;
            margin-bottom: 6px;
        }
        
        .ticket-table-info .value {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 26px;
            color: #1a1a1a;
            letter-spacing: 0.05em;
            line-height: 1;
        }
        
        /* ============================================
           SECTION DÉTACHABLE
           ============================================ */
        .ticket-stub {
            padding: 20px 24px 24px;
            background: #fafafa;
            border-radius: 0 0 8px 8px;
            position: relative;
        }
        
        .ticket-barcode {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .ticket-barcode .bars {
            flex: 1;
            height: 50px;
            background-image: repeating-linear-gradient(
                90deg,
                #1a1a1a 0px, #1a1a1a 2px,
                transparent 2px, transparent 4px,
                #1a1a1a 4px, #1a1a1a 5px,
                transparent 5px, transparent 8px,
                #1a1a1a 8px, #1a1a1a 11px,
                transparent 11px, transparent 13px,
                #1a1a1a 13px, #1a1a1a 14px,
                transparent 14px, transparent 17px,
                #1a1a1a 17px, #1a1a1a 20px,
                transparent 20px, transparent 22px
            );
        }
        .ticket-barcode .code {
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            color: #666;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            letter-spacing: 0.2em;
        }
        
        .ticket-stub-content {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .ticket-qr {
            flex-shrink: 0;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .ticket-stub-text {
            flex: 1;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #666;
        }
        .ticket-stub-text strong {
            display: block;
            font-size: 13px;
            color: #1a1a1a;
            margin-bottom: 4px;
            font-weight: 700;
        }
        
        .ticket-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 14px;
            border-radius: 4px;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            z-index: 5;
        }
        .ticket-status.confirmed { background: #d4f4dd; color: #0a6e2c; }
        .ticket-status.refused   { background: #ffd4d4; color: #b20710; }
        .ticket-status.pending   { background: #fff4d4; color: #8b6914; }
        
        /* ============================================
           SECTIONS COMMUNES
           ============================================ */
        .section {
            width: 100%;
            max-width: 420px;
            background: #fefefe;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 0.05em;
            color: #1a1a1a;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        /* ============================================
           ⭐ DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .ticket-diaporama {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #1a1a1a;
            border: 3px solid #ff6b35;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }
        
        .ticket-diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a1a;
        }
        .ticket-diaporama .slide.active { opacity: 1; z-index: 1; }
        .ticket-diaporama .slide img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain;
            background: #1a1a1a;
            padding: 8px;
        }
        
        /* Flèches */
        .ticket-diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 107, 53, 0.9);
            border: 2px solid white;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        .ticket-diapo-arrow:hover {
            background: #f7931e;
            transform: translateY(-50%) scale(1.1);
        }
        .ticket-diapo-arrow.prev { left: 12px; }
        .ticket-diapo-arrow.next { right: 12px; }
        
        @media (max-width: 480px) {
            .ticket-diapo-arrow { width: 36px; height: 36px; font-size: 13px; }
            .ticket-diapo-arrow.prev { left: 8px; }
            .ticket-diapo-arrow.next { right: 8px; }
        }
        
        /* Compteur */
        .ticket-diapo-counter {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid #ff6b35;
            color: #ff6b35;
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 6px 12px;
            border-radius: 999px;
            z-index: 10;
        }
        
        /* Points */
        .ticket-diapo-dots {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 10;
            background: rgba(26, 26, 26, 0.85);
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255, 107, 53, 0.5);
        }
        .ticket-diapo-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .ticket-diapo-dots span.active {
            background: #ff6b35;
            transform: scale(1.4);
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.8);
        }
        
        /* Formulaires */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e5e5;
            border-radius: 6px;
            font-family: 'Space Mono', monospace;
            font-size: 14px;
            color: #1a1a1a;
            background: #fafafa;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #ff6b35;
            outline: none;
            background: white;
        }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option-radio { display: none; }
        .option-radio-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 16px;
            border: 2px solid #e5e5e5;
            border-radius: 6px;
            background: #fafafa;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            letter-spacing: 0.05em;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-radio:checked + .option-radio-label {
            border-color: #ff6b35;
            background: #fff5f0;
            color: #ff6b35;
        }
        
        .btn-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            border-radius: 6px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255, 107, 53, 0.4);
        }
        
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            border-radius: 6px;
            background: #25d366;
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        /* QR */
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .qr-wrapper #qrcode {
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 2px solid #e5e5e5;
        }
        
        /* Boissons */
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 2px solid #e5e5e5;
            border-radius: 6px;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Space Mono', monospace;
            font-size: 12px;
            font-weight: 700;
            color: #666;
        }
        .boisson-item:hover { border-color: #ff6b35; }
        .boisson-item.selected {
            border-color: #ff6b35;
            background: #fff5f0;
            color: #ff6b35;
        }
        .boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .boisson-item.selected .check { opacity: 1; }
        .boisson-category { margin-bottom: 18px; }
        .boisson-category-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.1em;
            color: #ff6b35;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        /* Alert */
        .alert-custom {
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .alert-success { background: #d4f4dd; color: #0a6e2c; }
        .alert-danger  { background: #ffd4d4; color: #b20710; }
        .alert-warning { background: #fff4d4; color: #8b6914; }
        
        /* Bouton download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 14px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.5);
            z-index: 100;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        #downloadBtn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 107, 53, 0.6);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 20px 12px; }
            .ticket-title { font-size: 42px; }
            .ticket-guest .name { font-size: 26px; }
            .ticket-info-grid { gap: 12px; }
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 12px; }
        }
    </style>
</head>
<body>

    <div class="app-wrapper">

        <!-- ========================================== -->
        <!-- TICKET PRINCIPAL                           -->
        <!-- ========================================== -->
        <div class="ticket ticket-anim zoom-in" id="invitation-card">
            
            <!-- En-tête coloré -->
            <div class="ticket-header">
                
                <div class="ticket-status <?php 
                    echo $invitation['statut'] == 'CONFIRMEE' ? 'confirmed' : 
                        ($invitation['statut'] == 'REFUSEE' ? 'refused' : 'pending'); 
                ?>">
                    <?php 
                    if ($invitation['statut'] == 'CONFIRMEE') echo '✓ VALIDÉ';
                    elseif ($invitation['statut'] == 'REFUSEE') echo '✗ REFUSÉ';
                    else echo '⏳ EN ATTENTE';
                    ?>
                </div>
                
                <div class="ticket-brand">
                    <div class="logo"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 10))); ?></div>
                    <div class="num">
                        BILLET N°
                        <strong><?php echo htmlspecialchars(substr($invitation['code_unique'], -6)); ?></strong>
                    </div>
                </div>
                
                <div class="ticket-title">
                    <?php echo htmlspecialchars($host1); ?>
                </div>
                <div class="ticket-subtitle">
                    <?php echo htmlspecialchars(strtoupper($eventType)); ?>
                </div>
            </div>
            
            <!-- Corps du ticket -->
            <div class="ticket-body">
                
                <div class="ticket-guest">
                    <div class="label">Titulaire</div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($guestName)); ?></div>
                </div>
                
                <div class="ticket-info-grid">
                    <div class="ticket-info-item">
                        <div class="label">Date</div>
                        <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                    </div>
                    <div class="ticket-info-item">
                        <div class="label">Heure</div>
                        <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                    </div>
                    <div class="ticket-info-item">
                        <div class="label">Lieu</div>
                        <div class="value" style="font-size:12px;line-height:1.4;"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                    </div>
                    <div class="ticket-info-item">
                        <div class="label">Place</div>
                        <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> pers.</div>
                    </div>
                </div>
                
                <!-- ⭐ TABLE ASSIGNÉE -->
                <?php if ($hasTable): ?>
                <div class="ticket-table-info">
                    <div class="label">✦ Votre table ✦</div>
                    <div class="value">
                        <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($eventDescription)): ?>
                    <div style="font-family:'Inter',sans-serif;font-size:13px;line-height:1.6;color:#666;padding:12px 0;border-top:1px solid #eee;">
                        <?php echo nl2br(htmlspecialchars(mb_substr($eventDescription, 0, 200))); ?>
                        <?php if (mb_strlen($eventDescription) > 200) echo '...'; ?>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="ticket-cut"></div>
            
            <!-- Souche détachable -->
            <div class="ticket-stub">
                
                <div class="ticket-barcode">
                    <div class="bars"></div>
                    <div class="code"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                </div>
                
                <div class="ticket-stub-content">
                    <div class="ticket-qr">
                        <div id="card-qrcode"></div>
                    </div>
                    <div class="ticket-stub-text">
                        <strong>Présentez ce billet à l'entrée</strong>
                        Scannez le QR code pour accéder à votre invitation numérique complète.
                    </div>
                </div>
                
            </div>
            
        </div>

        <!-- ========================================== -->
        <!-- ⭐ DIAPORAMA PHOTOS PLEIN ÉCRAN            -->
        <!-- ========================================== -->
        <?php if ($hasPhotos): ?>
            <div class="section ticket-anim from-left">
                <div class="section-title">📸 Souvenirs</div>
                
                <div class="ticket-diaporama" id="ticketDiaporama">
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
                        <button class="ticket-diapo-arrow prev" onclick="ticketDiapoChange(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="ticket-diapo-arrow next" onclick="ticketDiapoChange(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <div class="ticket-diapo-counter" id="ticketDiapoCounter">1 / <?php echo $photoIndex; ?></div>
                        
                        <div class="ticket-diapo-dots" id="ticketDiapoDots">
                            <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                                <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="ticketDiapoGoTo(<?php echo $i; ?>)"></span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MESSAGES                                   -->
        <!-- ========================================== -->
        <?php if ($message): ?>
            <div class="section ticket-anim apparue">
                <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- QR CODE                                    -->
        <!-- ========================================== -->
        <div class="section ticket-anim from-right">
            <div class="section-title">Code QR</div>
            <div class="qr-wrapper">
                <div id="qrcode"></div>
                <div style="font-family:'Space Mono',monospace;font-size:11px;color:#666;letter-spacing:0.1em;">
                    <?php echo htmlspecialchars($invitation['code_unique']); ?>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CONFIRMATION                               -->
        <!-- ========================================== -->
        <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
            <div class="section ticket-anim from-left">
                <div class="section-title">Confirmer</div>
                
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                    <input type="hidden" name="action" value="confirmer">
                    
                    <div class="form-group">
                        <label>Nombre de personnes</label>
                        <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Votre réponse</label>
                        <div class="options-grid">
                            <div>
                                <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="option-radio">
                                <label for="presenceOui" class="option-radio-label">
                                    <i class="fas fa-check"></i> Présent
                                </label>
                            </div>
                            <div>
                                <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="option-radio">
                                <label for="presenceNon" class="option-radio-label">
                                    <i class="fas fa-times"></i> Absent
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message_invite" placeholder="..." rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-confirm">
                        <i class="fas fa-ticket-alt"></i> Valider
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- BOISSONS                                   -->
        <!-- ========================================== -->
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
            <div class="section ticket-anim from-right">
                <div class="section-title">🍹 Boissons</div>
                
                <?php if ($isLocked): ?>
                    <div style="text-align:center;color:#0a6e2c;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;">
                        <i class="fas fa-lock"></i> Préférences enregistrées
                    </div>
                <?php else: ?>
                    <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                        <input type="hidden" name="action" value="preferences">
                        
                        <p style="font-family:'Inter',sans-serif;font-size:13px;color:#666;text-align:center;margin-bottom:16px;">
                            Choisissez <strong>2 boissons</strong> : <span id="selectedCount">0</span>/2
                        </p>
                        
                        <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                            <div class="boisson-category">
                                <div class="boisson-category-title"><?php echo htmlspecialchars($type ?: 'Autres'); ?></div>
                                <div class="boisson-grid">
                                    <?php foreach ($boissonsByType as $b): 
                                        $selected = isset($preferencesBoissons[$b['id']]);
                                    ?>
                                        <div class="boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                        
                        <button type="submit" class="btn-confirm">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- FOOTER                                     -->
        <!-- ========================================== -->
        <div class="section ticket-anim" style="text-align:center;">
            <div style="font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:0.1em;color:#ff6b35;margin-bottom:6px;">
                <?php echo htmlspecialchars(strtoupper($appName)); ?>
            </div>
            <div style="font-family:'Inter',sans-serif;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:#999;margin-bottom:16px;">
                Billets d'exception
            </div>
            
            <a href="https://wa.me/243829018462?text=Bonjour" 
               target="_blank" 
               class="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Contact
            </a>
            
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #eee;font-family:'Space Mono',monospace;font-size:10px;color:#999;letter-spacing:0.1em;">
                © <?php echo date('Y'); ?>
            </div>
        </div>

    </div>

    <!-- Bouton download -->
    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.ticket-anim');
            
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
            
            setTimeout(() => {
                animElements.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        el.classList.add('apparue');
                    }
                });
            }, 500);
        });

        // QR
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('card-qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 90, height: 90,
                        colorDark: '#1a1a1a', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 160, height: 160,
                        colorDark: '#1a1a1a', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA PHOTOS
        // ================================================================
        let ticketDiapoIndex = 0;
        const ticketSlides = document.querySelectorAll('#ticketDiaporama .slide');
        const ticketDots = document.querySelectorAll('#ticketDiapoDots span');
        const ticketCounter = document.getElementById('ticketDiapoCounter');
        let ticketDiapoInterval = null;

        function ticketUpdateDiapo() {
            ticketSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === ticketDiapoIndex);
            });
            ticketDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === ticketDiapoIndex);
            });
            if (ticketCounter) {
                ticketCounter.textContent = (ticketDiapoIndex + 1) + ' / ' + ticketSlides.length;
            }
        }

        function ticketDiapoChange(direction) {
            ticketDiapoIndex += direction;
            if (ticketDiapoIndex < 0) ticketDiapoIndex = ticketSlides.length - 1;
            if (ticketDiapoIndex >= ticketSlides.length) ticketDiapoIndex = 0;
            ticketUpdateDiapo();
            resetTicketDiapoAuto();
        }

        function ticketDiapoGoTo(index) {
            ticketDiapoIndex = index;
            ticketUpdateDiapo();
            resetTicketDiapoAuto();
        }

        function resetTicketDiapoAuto() {
            if (ticketDiapoInterval) clearInterval(ticketDiapoInterval);
            if (ticketSlides.length > 1) {
                ticketDiapoInterval = setInterval(() => {
                    ticketDiapoIndex = (ticketDiapoIndex + 1) % ticketSlides.length;
                    ticketUpdateDiapo();
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (ticketSlides.length > 0) {
                ticketUpdateDiapo();
                resetTicketDiapoAuto();
                
                const container = document.getElementById('ticketDiaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (ticketDiapoInterval) clearInterval(ticketDiapoInterval);
                    });
                    container.addEventListener('mouseleave', resetTicketDiapoAuto);
                }
            }
        });

        // Download
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const card = document.getElementById('invitation-card');
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            try {
                await new Promise(r => setTimeout(r, 300));
                const canvas = await html2canvas(card, {
                    scale: 2.5, useCORS: true,
                    backgroundColor: null, logging: false
                });
                const link = document.createElement('a');
                link.download = `ticket_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.boisson-item.selected').forEach(item => {
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
            if (selectedBoissons.length >= 2) {
                alert('Maximum 2 boissons'); return;
            }
            element.classList.add('selected');
            selectedBoissons.push(id);
            element.querySelector('input[type="checkbox"]').checked = true;
            updateCount();
        }
        function updateCount() {
            document.getElementById('selectedCount').textContent = selectedBoissons.length;
        }
        <?php endif; ?>
    </script>

</body>
</html>