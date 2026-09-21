<?php
/**
 * ============================================================
 * TEMPLATE : CARTE POSTALE - v2 (Modernisée)
 * ============================================================
 * 
 * Nouveautés v2 :
 * - Photo de fond en background
 * - Nom de la table
 * - Diaporama photos plein écran (grande taille)
 * - Animations de sections en cascade
 * - Design modernisé
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Caveat:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --cream: #fdf6ec;
            --cream-2: #f5e9d8;
            --paper: #fefaf4;
            --brown: #3a3028;
            --brown-2: #5a4a3a;
            --brown-light: #8a7a6a;
            --gold: #8b6914;
            --gold-light: #c9a961;
            --terracotta: #c17c60;
            --terracotta-light: #e8a888;
            --green: #2d7a45;
            --red: #b20710;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-md: 0 12px 32px rgba(0,0,0,0.12);
            --shadow-lg: 0 30px 80px rgba(0,0,0,0.3);
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        /* ============================================
           PHOTO DE FOND EN BACKGROUND
           ============================================ */
        html {
            background: #2a2420;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: #2a2420;
            <?php else: ?>
            background: #2a2420;
            background-image: 
                radial-gradient(circle at 30% 20%, rgba(212, 165, 116, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(193, 124, 96, 0.12) 0%, transparent 50%);
            <?php endif; ?>
            color: var(--brown);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 60px 16px 40px;
            gap: 50px;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* Overlay dégradé sur la photo de fond */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background: 
                radial-gradient(ellipse at top, rgba(42, 36, 32, 0.6) 0%, transparent 70%),
                linear-gradient(180deg, 
                    rgba(42, 36, 32, 0.7) 0%, 
                    rgba(42, 36, 32, 0.55) 30%,
                    rgba(42, 36, 32, 0.75) 70%,
                    rgba(42, 36, 32, 0.9) 100%);
            pointer-events: none;
        }
        
        /* Contenu au-dessus de l'overlay */
        .postcard,
        .section {
            position: relative;
            z-index: 2;
        }
        
        /* ============================================
           ANIMATIONS DE SECTIONS EN CASCADE
           ============================================ */
        .cp-anim {
            opacity: 0;
            transform: translateY(60px) scale(0.96);
            transition: 
                opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            will-change: opacity, transform;
        }
        
        .cp-anim.apparue {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .cp-anim.from-left {
            transform: translateX(-80px);
        }
        .cp-anim.from-left.apparue {
            transform: translateX(0);
        }
        
        .cp-anim.from-right {
            transform: translateX(80px);
        }
        .cp-anim.from-right.apparue {
            transform: translateX(0);
        }
        
        .cp-anim.zoom-in {
            transform: scale(0.85);
        }
        .cp-anim.zoom-in.apparue {
            transform: scale(1);
        }
        
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        
        /* ============================================
           FORMAT CARTE POSTALE MODERNE
           ============================================ */
        .postcard {
            position: relative;
            width: 100%;
            max-width: 900px;
            background: var(--paper);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 
                0 40px 100px rgba(0,0,0,0.6),
                0 0 0 1px rgba(255,255,255,0.1),
                0 0 0 8px rgba(253, 246, 236, 0.15);
            display: grid;
            grid-template-columns: 1fr 1fr;
            aspect-ratio: 3/2;
        }
        
        /* Colonne gauche - visuel */
        .postcard-visual {
            position: relative;
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }
        .postcard-visual::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.6) 100%);
        }
        
        .postcard-visual-content {
            position: relative;
            z-index: 2;
            height: 100%;
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }
        
        .postcard-stamp {
            width: 70px;
            height: 88px;
            background: linear-gradient(135deg, var(--terracotta-light), var(--terracotta));
            border: 2px dashed rgba(255,255,255,0.7);
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            color: white;
            align-self: flex-end;
            transform: rotate(6deg);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        
        .postcard-visual-title {
            text-align: left;
        }
        .postcard-visual-title .greeting {
            font-family: 'Caveat', cursive;
            font-size: 38px;
            color: rgba(255,255,255,0.95);
            margin-bottom: 6px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }
        .postcard-visual-title .host {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 900;
            line-height: 1.05;
            text-shadow: 0 3px 15px rgba(0,0,0,0.6);
        }
        .postcard-visual-title .type {
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            opacity: 0.9;
            margin-top: 10px;
            font-weight: 700;
            text-shadow: 0 2px 6px rgba(0,0,0,0.5);
        }
        
        /* Colonne droite - adresse */
        .postcard-address {
            position: relative;
            padding: 32px;
            background: var(--paper);
            background-image: 
                radial-gradient(circle at 100% 0%, rgba(201, 169, 97, 0.08) 0%, transparent 60%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Lignes de courrier subtiles */
        .postcard-address::before {
            content: '';
            position: absolute;
            top: 32px; left: 32px; right: 32px;
            height: 55%;
            background-image: repeating-linear-gradient(
                transparent 0px, transparent 24px,
                rgba(139, 105, 20, 0.06) 24px, rgba(139, 105, 20, 0.06) 25px
            );
            pointer-events: none;
        }
        
        /* Tampon postal */
        .postcard-postmark {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 90px;
            height: 90px;
            border: 2px solid rgba(193, 124, 96, 0.5);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transform: rotate(-12deg);
            font-family: 'Playfair Display', serif;
            font-size: 10px;
            color: rgba(193, 124, 96, 0.75);
            text-align: center;
            line-height: 1.2;
            letter-spacing: 0.05em;
            z-index: 3;
            background: rgba(253, 246, 236, 0.5);
        }
        .postcard-postmark .city {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.1em;
        }
        .postcard-postmark .date {
            font-size: 9px;
            margin-top: 3px;
        }
        
        .postcard-address-content {
            position: relative;
            z-index: 2;
            padding-top: 20px;
        }
        
        .postcard-address-content .to-label {
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: var(--gold);
            margin-bottom: 10px;
        }
        
        .postcard-address-content .guest-name {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 900;
            font-style: italic;
            color: var(--brown);
            line-height: 1.1;
            margin-bottom: 20px;
        }
        
        .postcard-address-content .message {
            font-family: 'Caveat', cursive;
            font-size: 17px;
            line-height: 1.65;
            color: var(--brown-2);
            margin-bottom: 20px;
            min-height: 70px;
        }
        
        .postcard-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 13px;
            color: var(--brown-2);
            margin-top: auto;
        }
        .postcard-meta .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .postcard-meta .meta-row i {
            width: 18px;
            color: var(--terracotta);
            font-size: 14px;
        }
        .postcard-meta .meta-row strong {
            font-weight: 700;
            color: var(--brown);
        }
        
        /* QR mini */
        .postcard-qr {
            position: absolute;
            bottom: 24px;
            right: 24px;
            padding: 8px;
            background: white;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 6px;
            z-index: 3;
            box-shadow: var(--shadow-sm);
        }
        
        /* Statut RSVP */
        .postcard-status {
            position: absolute;
            bottom: 24px;
            left: 24px;
            padding: 8px 16px;
            border: 2px solid;
            border-radius: 6px;
            font-family: 'Playfair Display', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transform: rotate(-4deg);
            z-index: 3;
        }
        .postcard-status.confirmed { border-color: var(--green); color: var(--green); background: rgba(45,122,69,0.1); }
        .postcard-status.refused   { border-color: var(--red); color: var(--red); background: rgba(178,7,16,0.1); }
        .postcard-status.pending   { border-color: var(--gold); color: var(--gold); background: rgba(139,105,20,0.1); }
        
        /* ============================================
           SECTIONS MODERNES
           ============================================ */
        .section {
            width: 100%;
            max-width: 900px;
            background: var(--paper);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 
                0 20px 60px rgba(0,0,0,0.4),
                0 0 0 1px rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--terracotta) 0%, 
                var(--gold-light) 50%, 
                var(--terracotta) 100%);
        }
        
        @media (max-width: 640px) {
            .section { padding: 28px 22px; border-radius: 12px; }
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 900;
            color: var(--brown);
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: 0.02em;
            position: relative;
            padding-bottom: 20px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--terracotta), transparent);
        }
        
        /* ⭐ CARTE TABLE */
        .table-card {
            background: linear-gradient(135deg, 
                rgba(201, 169, 97, 0.15) 0%, 
                rgba(193, 124, 96, 0.08) 100%);
            border: 2px solid var(--gold-light);
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            animation: tableCardPulse 3s ease-in-out infinite;
            margin-bottom: 30px;
        }
        
        @keyframes tableCardPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(201, 169, 97, 0.3); }
            50% { box-shadow: 0 0 30px 0 rgba(201, 169, 97, 0.5); }
        }
        
        .table-card .icon {
            font-size: 32px;
            color: var(--terracotta);
            margin-bottom: 12px;
        }
        
        .table-card .label {
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: var(--gold);
            margin-bottom: 8px;
        }
        
        .table-card .value {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 900;
            color: var(--brown);
            letter-spacing: 0.05em;
        }
        
        /* ============================================
           DIAPORAMA PHOTOS PLEIN ÉCRAN
           ============================================ */
        .diaporama {
            width: 100%;
            aspect-ratio: 16/10;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: #2a2420;
            border: 2px solid rgba(193, 124, 96, 0.3);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2a2420;
        }
        .diaporama .slide.active { opacity: 1; z-index: 1; }
        .diaporama .slide img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain;
            background: #2a2420;
            padding: 8px;
        }
        
        /* Flèches */
        .diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(253, 246, 236, 0.95);
            border: 2px solid var(--terracotta);
            color: var(--terracotta);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .diapo-arrow:hover {
            background: var(--terracotta);
            color: white;
            transform: translateY(-50%) scale(1.1);
        }
        .diapo-arrow.prev { left: 16px; }
        .diapo-arrow.next { right: 16px; }
        
        /* Compteur */
        .diapo-counter {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(42, 36, 32, 0.9);
            border: 2px solid var(--gold-light);
            color: var(--gold-light);
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 8px 16px;
            border-radius: 999px;
            z-index: 10;
        }
        
        /* Points */
        .diapo-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
            background: rgba(42, 36, 32, 0.85);
            padding: 10px 20px;
            border-radius: 999px;
            border: 1px solid rgba(193, 124, 96, 0.3);
        }
        .diapo-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(253, 246, 236, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .diapo-dots span.active {
            background: var(--terracotta);
            transform: scale(1.4);
            box-shadow: 0 0 12px rgba(193, 124, 96, 0.8);
        }
        
        /* Formulaires */
        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: var(--gold);
            margin-bottom: 10px;
            font-weight: 700;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            color: var(--brown);
            background: rgba(255,255,255,0.7);
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--terracotta);
            outline: none;
            background: white;
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.15);
        }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .options-grid { grid-template-columns: 1fr; } }
        
        .option-radio { display: none; }
        .option-radio-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 10px;
            background: rgba(255,255,255,0.7);
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--brown-2);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-radio-label:hover { border-color: var(--terracotta); }
        .option-radio:checked + .option-radio-label {
            border-color: var(--terracotta);
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(201, 169, 97, 0.1));
            color: var(--terracotta);
            box-shadow: 0 0 0 4px rgba(193, 124, 96, 0.1);
        }
        
        .btn-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px 24px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--gold) 100%);
            color: white;
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(139, 105, 20, 0.3);
        }
        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(139, 105, 20, 0.5);
        }
        
        /* QR */
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        #qrcode {
            padding: 16px;
            background: white;
            border-radius: 12px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            box-shadow: var(--shadow-md);
        }
        
        /* Boissons */
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 999px;
            background: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            color: var(--brown-2);
        }
        .boisson-item:hover { border-color: var(--terracotta); }
        .boisson-item.selected {
            border-color: var(--terracotta);
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.15), rgba(201, 169, 97, 0.1));
            color: var(--terracotta);
        }
        .boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .boisson-item.selected .check { opacity: 1; }
        .boisson-category { margin-bottom: 20px; }
        .boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 12px;
        }
        
        /* Alert */
        .alert-custom {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 14px;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .alert-success { background: rgba(45,122,69,0.1); color: var(--green); border: 1px solid rgba(45,122,69,0.2); }
        .alert-danger  { background: rgba(178,7,16,0.1);  color: var(--red); border: 1px solid rgba(178,7,16,0.2); }
        .alert-warning { background: rgba(139,105,20,0.1); color: var(--gold); border: 1px solid rgba(139,105,20,0.2); }
        
        /* Footer */
        .footer-section {
            text-align: center;
        }
        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 900;
            color: var(--gold);
            margin-bottom: 8px;
        }
        .footer-tagline {
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: var(--brown-2);
            margin-bottom: 20px;
        }
        
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            border-radius: 10px;
            background: #25d366;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.3);
        }
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(37, 211, 102, 0.5);
            color: white;
        }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 16px 28px;
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--gold) 100%);
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 12px 36px rgba(139, 105, 20, 0.5);
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.05); }
        
        /* Responsive */
        @media (max-width: 700px) {
            .postcard {
                grid-template-columns: 1fr;
                aspect-ratio: auto;
            }
            .postcard-visual { min-height: 280px; }
            .postcard-address { padding: 24px; }
            .postcard-address::before { display: none; }
            body { padding: 40px 12px 30px; gap: 30px; }
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 20px; font-size: 11px; }
            .diaporama { aspect-ratio: 4/3; }
        }
    </style>
</head>
<body>

    <!-- ========================================== -->
    <!-- CARTE POSTALE MODERNE                      -->
    <!-- ========================================== -->
    <div class="postcard cp-anim zoom-in" id="invitation-card">
        
        <!-- Colonne gauche : visuel -->
        <div class="postcard-visual" 
             style="background-image: url('<?php echo !empty($pageBackground) ? htmlspecialchars($pageBackground) : ''; ?>'), linear-gradient(135deg, var(--terracotta) 0%, var(--gold) 100%);">
            
            <div class="postcard-visual-content">
                
                <div class="postcard-stamp">✉</div>
                
                <div class="postcard-visual-title">
                    <div class="greeting">Cher(e)</div>
                    <div class="host"><?php echo htmlspecialchars($host1); ?></div>
                    <div class="type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Colonne droite : adresse -->
        <div class="postcard-address">
            
            <!-- Tampon postal -->
            <div class="postcard-postmark">
                <div class="city"><?php echo htmlspecialchars(mb_substr($lieuDisplay, 0, 12)); ?></div>
                <div class="date"><?php echo htmlspecialchars(date('d.m.Y', strtotime($invitation['date_evenement']))); ?></div>
            </div>
            
            <!-- Statut -->
            <div class="postcard-status <?php 
                echo $invitation['statut'] == 'CONFIRMEE' ? 'confirmed' : 
                    ($invitation['statut'] == 'REFUSEE' ? 'refused' : 'pending'); 
            ?>">
                <?php 
                if ($invitation['statut'] == 'CONFIRMEE') echo '✓ Confirmé';
                elseif ($invitation['statut'] == 'REFUSEE') echo '✗ Refusé';
                else echo '⏳ En attente';
                ?>
            </div>
            
            <div class="postcard-address-content">
                
                <div class="to-label">À l'attention de :</div>
                <div class="guest-name"><?php echo htmlspecialchars($guestName); ?></div>
                
                <div class="message">
                    <?php if (!empty($eventDescription)): ?>
                        <?php echo htmlspecialchars(mb_substr($eventDescription, 0, 180)); ?>
                        <?php if (mb_strlen($eventDescription) > 180) echo '...'; ?>
                    <?php else: ?>
                        Nous serions honorés de votre présence pour célébrer avec nous ce moment unique et inoubliable.
                    <?php endif; ?>
                </div>
                
                <div class="postcard-meta">
                    <div class="meta-row">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Le <strong><?php echo htmlspecialchars($eventDate); ?></strong></span>
                    </div>
                    <?php if ($eventTime): ?>
                        <div class="meta-row">
                            <i class="fas fa-clock"></i>
                            <span>À <strong><?php echo htmlspecialchars($eventTime); ?></strong></span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong><?php echo htmlspecialchars($lieuDisplay); ?></strong></span>
                    </div>
                    <div class="meta-row">
                        <i class="fas fa-user-friends"></i>
                        <span><strong><?php echo (int)($invitation['nb_places_max'] ?? 1); ?></strong> personne(s)</span>
                    </div>
                </div>
                
            </div>
            
            <!-- QR code mini -->
            <div class="postcard-qr">
                <div id="card-qrcode"></div>
            </div>
            
        </div>
        
    </div>

    <!-- ========================================== -->
    <!-- TABLE ASSIGNÉE                             -->
    <!-- ========================================== -->
    <?php if ($hasTable): ?>
    <div class="section cp-anim from-left">
        <div class="section-title">✦ Votre table ✦</div>
        
        <div class="table-card">
            <div class="icon"><i class="fas fa-chair"></i></div>
            <div class="label">Vous êtes placé(e) à</div>
            <div class="value">
                <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- MESSAGES                                    -->
    <!-- ========================================== -->
    <?php if ($message): ?>
        <div class="section cp-anim apparue">
            <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- ========================================== -->
    <!-- ⭐ DIAPORAMA PHOTOS PLEIN ÉCRAN            -->
    <!-- ========================================== -->
    <?php if ($hasPhotos): ?>
        <div class="section cp-anim from-right">
            <div class="section-title">📷 Souvenirs partagés</div>
            
            <div class="diaporama" id="diaporama">
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
                    <button class="diapo-arrow prev" onclick="diapoChange(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="diapo-arrow next" onclick="diapoChange(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="diapo-counter" id="diapoCounter">1 / <?php echo $photoIndex; ?></div>
                    
                    <div class="diapo-dots" id="diapoDots">
                        <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                            <span class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="diapoGoTo(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- ========================================== -->
    <!-- QR CODE                                     -->
    <!-- ========================================== -->
    <div class="section cp-anim from-left">
        <div class="section-title">Code d'accès</div>
        <div class="qr-wrapper">
            <div id="qrcode"></div>
            <div style="font-size:14px;color:var(--brown-2);letter-spacing:0.15em;font-family:'Playfair Display',serif;font-weight:700;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>
    
    <!-- ========================================== -->
    <!-- CONFIRMATION                                -->
    <!-- ========================================== -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="section cp-anim from-right">
            <div class="section-title">Répondre à l'invitation</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="form-group">
                    <label>Combien serez-vous ?</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="form-group">
                    <label>Votre réponse</label>
                    <div class="options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="option-radio">
                            <label for="presenceOui" class="option-radio-label">
                                <i class="fas fa-check"></i> Je viendrai
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="option-radio">
                            <label for="presenceNon" class="option-radio-label">
                                <i class="fas fa-times"></i> Je ne peux pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Un mot pour les hôtes...</label>
                    <textarea name="message_invite" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn-confirm">
                    <i class="fas fa-paper-plane"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- ========================================== -->
    <!-- BOISSONS                                    -->
    <!-- ========================================== -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="section cp-anim from-left">
            <div class="section-title">🥂 Vos boissons préférées</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:var(--green);font-weight:700;padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Caveat',cursive;font-size:20px;color:var(--gold);margin-bottom:20px;">
                        Choisissez vos 2 boissons favorites : <strong id="selectedCount">0</strong>/2
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
    <!-- FOOTER                                      -->
    <!-- ========================================== -->
    <div class="section footer-section cp-anim">
        <div class="footer-brand">
            <?php echo htmlspecialchars($appName); ?>
        </div>
        <div class="footer-tagline">
            Des invitations qui voyagent
        </div>
        
        <a href="https://wa.me/243829018462" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(139,105,20,0.15);font-size:11px;color:var(--brown-light);letter-spacing:0.15em;text-transform:uppercase;">
            © <?php echo date('Y'); ?>
        </div>
    </div>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // ================================================================
        // ANIMATIONS AU SCROLL
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            const animElements = document.querySelectorAll('.cp-anim');
            
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
                        width: 80, height: 80,
                        colorDark: '#3a3028', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 180, height: 180,
                        colorDark: '#3a3028', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // ================================================================
        // DIAPORAMA
        // ================================================================
        let diapoIndex = 0;
        const slides = document.querySelectorAll('#diaporama .slide');
        const dots = document.querySelectorAll('#diapoDots span');
        const counter = document.getElementById('diapoCounter');
        let diapoInterval = null;

        function updateDiapo() {
            slides.forEach((s, i) => s.classList.toggle('active', i === diapoIndex));
            dots.forEach((d, i) => d.classList.toggle('active', i === diapoIndex));
            if (counter) counter.textContent = (diapoIndex + 1) + ' / ' + slides.length;
        }
        
        function diapoChange(direction) {
            diapoIndex += direction;
            if (diapoIndex < 0) diapoIndex = slides.length - 1;
            if (diapoIndex >= slides.length) diapoIndex = 0;
            updateDiapo();
            resetDiapoAuto();
        }
        
        function diapoGoTo(index) {
            diapoIndex = index;
            updateDiapo();
            resetDiapoAuto();
        }
        
        function resetDiapoAuto() {
            if (diapoInterval) clearInterval(diapoInterval);
            if (slides.length > 1) {
                diapoInterval = setInterval(() => {
                    diapoIndex = (diapoIndex + 1) % slides.length;
                    updateDiapo();
                }, 5000);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            if (slides.length > 0) {
                updateDiapo();
                resetDiapoAuto();
                
                const container = document.getElementById('diaporama');
                if (container) {
                    container.addEventListener('mouseenter', () => {
                        if (diapoInterval) clearInterval(diapoInterval);
                    });
                    container.addEventListener('mouseleave', resetDiapoAuto);
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
                    backgroundColor: '#fdf6ec', logging: false
                });
                const link = document.createElement('a');
                link.download = `carte_postale_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            if (selectedBoissons.length >= 2) { alert('Maximum 2 boissons'); return; }
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