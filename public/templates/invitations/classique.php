<?php
/**
 * ============================================================
 * TEMPLATE : CLASSIQUE v2 — Papeterie haut de gamme
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Caveat:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
            --paper: #f5efe3;
            --paper-dark: #e8dfce;
            --ink: #2a2420;
            --ink-light: #6a5a4a;
            --ink-muted: #9a8a7a;
            --wax: #8b1a1a;
            --wax-dark: #5a0f0f;
            --gold: #b8935a;
            --gold-light: #d4b280;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cormorant Garamond', Georgia, serif;
            background: #2a2420;
            background-image: 
                radial-gradient(circle at 30% 20%, rgba(184, 147, 90, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(139, 26, 26, 0.05) 0%, transparent 50%);
            color: var(--ink);
            min-height: 100vh;
            padding: 40px 20px;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           ENVELOPPE QUI S'OUVRE
           ============================================ */
        .envelope-intro {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #2a2420;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1000px;
            animation: envelopeFadeOut 3s ease-in-out 2.5s forwards;
        }
        @keyframes envelopeFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; pointer-events: none; }
        }
        
        .envelope {
            position: relative;
            width: 320px;
            height: 220px;
            background: linear-gradient(135deg, var(--paper) 0%, var(--paper-dark) 100%);
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 
                0 20px 60px rgba(0,0,0,0.6),
                inset 0 0 60px rgba(0,0,0,0.05);
        }
        
        .envelope::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(180deg, var(--paper-dark) 0%, var(--paper) 50%);
            clip-path: polygon(0 0, 50% 50%, 100% 0, 100% 100%, 0 100%);
            animation: envelopeOpen 1.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.5s forwards;
            transform-origin: top;
            z-index: 2;
        }
        @keyframes envelopeOpen {
            0% { transform: rotateX(0deg); }
            100% { transform: rotateX(-180deg); }
        }
        
        .envelope-wax {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, var(--wax) 0%, var(--wax-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-light);
            font-size: 24px;
            box-shadow: 
                0 4px 12px rgba(0,0,0,0.4),
                inset -4px -4px 8px rgba(0,0,0,0.3);
            z-index: 3;
            animation: waxBreak 0.8s ease-in 1.8s forwards;
        }
        @keyframes waxBreak {
            0% { transform: translate(-50%, -50%) scale(1) rotate(0); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(0) rotate(180deg); opacity: 0; }
        }
        
        /* ============================================
           PAPIER LETTRE
           ============================================ */
        .letter-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .letter {
            position: relative;
            background: var(--paper);
            background-image: 
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><filter id="n"><feTurbulence baseFrequency="0.9" numOctaves="4"/></filter><rect width="200" height="200" filter="url(%23n)" opacity="0.04"/></svg>'),
                radial-gradient(circle at 20% 20%, rgba(139, 26, 26, 0.03) 0%, transparent 50%);
            padding: 80px 60px 60px;
            box-shadow: 
                0 30px 80px rgba(0,0,0,0.4),
                0 0 0 1px rgba(0,0,0,0.08);
            border-radius: 2px;
            opacity: 0;
            transform: translateY(30px);
            animation: letterRise 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) 3s forwards;
        }
        @keyframes letterRise {
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 640px) {
            .letter { padding: 50px 25px 40px; }
        }
        
        /* Coin corné (effet papier) */
        .letter::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(225deg, transparent 50%, var(--paper-dark) 50%, var(--paper-dark) 100%);
            box-shadow: -2px 2px 4px rgba(0,0,0,0.1);
        }
        .letter::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 30px;
            height: 30px;
            background: linear-gradient(225deg, transparent 50%, rgba(0,0,0,0.05) 50%);
            transform: translate(0, 0);
        }
        
        /* Ornement filigrane en arrière-plan */
        .letter-ornament {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 400px;
            color: rgba(139, 26, 26, 0.03);
            font-family: 'Playfair Display', serif;
            font-style: italic;
            z-index: 0;
            pointer-events: none;
            user-select: none;
            line-height: 1;
        }
        
        .letter-content {
            position: relative;
            z-index: 1;
        }
        
        /* En-tête expéditeur */
        .letter-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 60px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        
        .letter-expediteur {
            font-family: 'Caveat', cursive;
            font-size: 22px;
            color: var(--ink-light);
            line-height: 1.4;
        }
        .letter-expediteur .signature {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            color: var(--wax);
            display: block;
            margin-top: 4px;
        }
        
        .letter-date {
            text-align: right;
            font-family: 'Caveat', cursive;
            font-size: 18px;
            color: var(--ink-muted);
        }
        
        /* Destinataire */
        .letter-recipient {
            margin-bottom: 50px;
        }
        .letter-recipient .label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 0.3em;
            color: var(--ink-muted);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .letter-recipient .name {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .letter-recipient .name::after {
            content: '';
            display: block;
            width: 60px;
            height: 2px;
            background: var(--wax);
            margin-top: 12px;
        }
        
        /* Corps de la lettre */
        .letter-body {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            line-height: 1.85;
            color: var(--ink);
            margin-bottom: 40px;
        }
        .letter-body p {
            margin-bottom: 20px;
            text-align: justify;
            text-justify: inter-word;
        }
        .letter-body p:first-child::first-letter {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 700;
            float: left;
            line-height: 0.85;
            margin: 4px 12px 0 0;
            color: var(--wax);
        }
        .letter-body strong {
            font-weight: 700;
            color: var(--ink);
        }
        .letter-body em {
            font-style: italic;
            color: var(--wax);
        }
        
        /* Invitation à célébrer */
        .letter-invitation {
            text-align: center;
            margin: 50px 0;
            padding: 40px 20px;
            border-top: 1px solid rgba(0,0,0,0.08);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            position: relative;
        }
        .letter-invitation::before,
        .letter-invitation::after {
            content: '❦';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: var(--paper);
            padding: 0 12px;
            color: var(--wax);
            font-size: 20px;
        }
        .letter-invitation::before { top: -14px; }
        .letter-invitation::after { bottom: -14px; }
        
        .letter-invitation .intro {
            font-family: 'Caveat', cursive;
            font-size: 24px;
            color: var(--ink-light);
            margin-bottom: 16px;
        }
        .letter-invitation .host-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: clamp(36px, 8vw, 64px);
            color: var(--wax);
            line-height: 1;
            margin-bottom: 16px;
        }
        .letter-invitation .event-type {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 0.5em;
            color: var(--ink-muted);
            text-transform: uppercase;
        }
        
        /* Détails dans la lettre */
        .letter-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 50px 0;
        }
        @media (max-width: 640px) {
            .letter-details { grid-template-columns: 1fr; gap: 20px; }
        }
        
        .letter-detail {
            text-align: center;
            padding: 24px 16px;
            border: 1px dashed rgba(0,0,0,0.15);
            position: relative;
        }
        .letter-detail::before {
            content: attr(data-label);
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--paper);
            padding: 0 12px;
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            color: var(--ink-muted);
            text-transform: uppercase;
        }
        
        .letter-detail .value {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }
        
        /* Bouton itinéraire */
        .letter-btn-itinerary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 12px 28px;
            border: 1px solid var(--wax);
            background: transparent;
            color: var(--wax);
            font-family: 'Caveat', cursive;
            font-size: 20px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }
        .letter-btn-itinerary::before,
        .letter-btn-itinerary::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            border: 1px solid var(--wax);
        }
        .letter-btn-itinerary::before { top: -4px; left: -4px; border-right: none; border-bottom: none; }
        .letter-btn-itinerary::after { bottom: -4px; right: -4px; border-left: none; border-top: none; }
        .letter-btn-itinerary:hover {
            background: var(--wax);
            color: var(--paper);
        }
        
        /* Signature bas de lettre */
        .letter-signature {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .letter-signature-text {
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: var(--ink-light);
        }
        .letter-signature-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 42px;
            color: var(--wax);
            line-height: 1;
            margin-top: 4px;
        }
        
        .letter-rsvp {
            text-align: right;
        }
        .letter-rsvp .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.25em;
            color: var(--ink-muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .letter-rsvp .value {
            font-family: 'Caveat', cursive;
            font-size: 22px;
            font-weight: 700;
        }
        .letter-rsvp .value.confirmed { color: #2d7a45; }
        .letter-rsvp .value.refused { color: var(--wax); }
        .letter-rsvp .value.pending { color: var(--ink-muted); }
        
        /* ============================================
           SCEAU DE CIRE FINAL
           ============================================ */
        .final-seal {
            position: absolute;
            bottom: -40px;
            right: 40px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, var(--wax) 0%, var(--wax-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-light);
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 
                0 8px 20px rgba(0,0,0,0.4),
                inset -6px -6px 12px rgba(0,0,0,0.4),
                inset 6px 6px 12px rgba(255,255,255,0.15);
            transform: rotate(-15deg);
            z-index: 5;
        }
        .final-seal::before {
            content: '';
            position: absolute;
            inset: 14px;
            border: 2px solid rgba(212, 178, 128, 0.5);
            border-radius: 50%;
        }
        
        /* ============================================
           SECTIONS SUIVANTES (même style papeterie)
           ============================================ */
        .letter-section {
            max-width: 800px;
            margin: 60px auto;
            padding: 50px 60px;
            background: var(--paper);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
        }
        .letter-section.apparue {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .letter-section { padding: 35px 22px; margin: 40px 15px; }
        }
        
        .letter-section-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 28px;
            color: var(--wax);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            position: relative;
        }
        .letter-section-title::after {
            content: '❦';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--paper);
            padding: 0 12px;
            color: var(--wax);
            font-size: 18px;
        }
        
        /* Formulaires */
        .letter-form-group { margin-bottom: 24px; }
        .letter-form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            letter-spacing: 0.3em;
            color: var(--ink-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .letter-form-group input,
        .letter-form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(0,0,0,0.15);
            color: var(--ink);
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            transition: all 0.3s ease;
        }
        .letter-form-group textarea { min-height: 100px; resize: vertical; }
        .letter-form-group input:focus,
        .letter-form-group textarea:focus {
            outline: none;
            border-color: var(--wax);
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 26, 26, 0.1);
        }
        
        .letter-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 480px) {
            .letter-options-grid { grid-template-columns: 1fr; }
        }
        
        .letter-option-radio { display: none; }
        .letter-option-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            border: 1px solid rgba(0,0,0,0.15);
            background: rgba(255,255,255,0.4);
            font-family: 'Caveat', cursive;
            font-size: 22px;
            color: var(--ink-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .letter-option-label:hover {
            border-color: var(--wax);
            background: white;
        }
        .letter-option-radio:checked + .letter-option-label {
            border-color: var(--wax);
            background: rgba(139, 26, 26, 0.08);
            color: var(--wax);
        }
        
        .letter-btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px;
            background: var(--wax);
            color: var(--paper);
            border: none;
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .letter-btn-submit:hover {
            background: var(--wax-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(139, 26, 26, 0.4);
        }
        
        /* Photos polaroid */
        .letter-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }
        .letter-photo {
            background: white;
            padding: 10px 10px 40px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transform: rotate(-1deg);
            transition: all 0.3s ease;
        }
        .letter-photo:nth-child(even) { transform: rotate(1.5deg); }
        .letter-photo:hover {
            transform: rotate(0) scale(1.03);
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
            z-index: 5;
        }
        .letter-photo img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
        }
        .letter-photo .caption {
            text-align: center;
            font-family: 'Caveat', cursive;
            font-size: 18px;
            color: var(--ink-light);
            margin-top: 8px;
        }
        
        /* Boissons */
        .letter-boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .letter-boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 1px solid rgba(0,0,0,0.15);
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: var(--ink-light);
        }
        .letter-boisson-item.selected {
            border-color: var(--wax);
            background: rgba(139, 26, 26, 0.08);
            color: var(--wax);
        }
        .letter-boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .letter-boisson-item.selected .check { opacity: 1; }
        
        .letter-boisson-category { margin-bottom: 20px; }
        .letter-boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 20px;
            color: var(--wax);
            margin-bottom: 10px;
        }
        
        /* QR */
        .letter-qr-wrapper {
            text-align: center;
        }
        .letter-qr-box {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 2px solid var(--wax);
            box-shadow: 0 8px 24px rgba(139, 26, 26, 0.2);
            position: relative;
        }
        .letter-qr-box::before,
        .letter-qr-box::after {
            content: '❦';
            position: absolute;
            color: var(--wax);
            font-size: 20px;
        }
        .letter-qr-box::before { top: -10px; left: -10px; }
        .letter-qr-box::after { bottom: -10px; right: -10px; }
        
        /* Footer */
        .letter-footer {
            text-align: center;
            padding: 40px 20px;
            color: var(--paper);
        }
        .letter-footer-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 8px;
        }
        .letter-footer-tagline {
            font-family: 'Caveat', cursive;
            font-size: 22px;
            color: var(--paper);
            opacity: 0.7;
            margin-bottom: 24px;
        }
        
        .letter-btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: #25d366;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .letter-btn-whatsapp:hover {
            background: #128c7e;
            transform: translateY(-2px);
            color: white;
        }
        
        /* Alerts */
        .letter-alert {
            padding: 16px 24px;
            margin-bottom: 20px;
            font-size: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-left: 3px solid;
        }
        .letter-alert-success { border-color: #2d7a45; background: rgba(45,122,69,0.08); color: #2d7a45; }
        .letter-alert-danger  { border-color: var(--wax); background: rgba(139,26,26,0.08); color: var(--wax); }
        .letter-alert-warning { border-color: var(--gold); background: rgba(184,147,90,0.1); color: var(--gold); }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            padding: 14px 24px;
            background: var(--wax);
            color: var(--paper);
            border: none;
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(139, 26, 26, 0.4);
        }
        #downloadBtn:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(139, 26, 26, 0.6); }
        @media (max-width: 480px) { #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 12px; } }
    </style>
</head>
<body>

    <!-- ============================================
         ENVELOPPE D'INTRO
         ============================================ -->
    <div class="envelope-intro">
        <div class="envelope">
            <div class="envelope-wax">
                <i class="fas fa-feather-alt"></i>
            </div>
        </div>
    </div>

    <!-- ============================================
         LETTRE PRINCIPALE
         ============================================ -->
    <div class="letter-wrapper">
        <div class="letter" id="invitation-card">
            
            <!-- Ornement filigrane -->
            <div class="letter-ornament"><?php echo htmlspecialchars(mb_substr($host1, 0, 1)); ?></div>
            
            <div class="letter-content">
                
                <!-- En-tête -->
                <div class="letter-header">
                    <div class="letter-expediteur">
                        De la part de
                        <span class="signature"><?php echo htmlspecialchars($host1); ?></span>
                    </div>
                    <div class="letter-date">
                        <?php echo htmlspecialchars(date('d/m/Y')); ?><br>
                        <?php echo htmlspecialchars($lieuDisplay); ?>
                    </div>
                </div>
                
                <!-- Destinataire -->
                <div class="letter-recipient">
                    <div class="label">À l'attention de</div>
                    <div class="name"><?php echo htmlspecialchars($guestName); ?></div>
                </div>
                
                <!-- Corps de la lettre -->
                <div class="letter-body">
                    <?php if (!empty($eventDescription)): ?>
                        <?php 
                        $paragraphs = explode("\n", $eventDescription);
                        foreach ($paragraphs as $p):
                            if (trim($p) === '') continue;
                        ?>
                            <p><?php echo nl2br(htmlspecialchars(trim($p))); ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <em><?php echo htmlspecialchars($eventType); ?></em>.</p>
                        <p>Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Invitation à célébrer -->
                <div class="letter-invitation">
                    <div class="intro">Vous êtes invité(e) à célébrer</div>
                    <div class="host-name"><?php echo htmlspecialchars($host1); ?></div>
                    <div class="event-type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
                </div>
                
                <!-- Détails -->
                <div class="letter-details">
                    
                    <div class="letter-detail" data-label="Date">
                        <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                    </div>
                    
                    <div class="letter-detail" data-label="Heure">
                        <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                    </div>
                    
                    <div class="letter-detail" data-label="Lieu" style="grid-column: 1 / -1;">
                        <div class="value"><?php echo htmlspecialchars($lieuDisplay); ?></div>
                        <?php if ($adresseDisplay): ?>
                            <div style="font-family:'Caveat',cursive;font-size:18px;color:var(--ink-light);margin-top:8px;">
                                <?php echo htmlspecialchars($adresseDisplay); ?>
                            </div>
                        <?php endif; ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="letter-btn-itinerary">
                            <i class="fas fa-route"></i> Itinéraire
                        </a>
                    </div>
                    
                    <div class="letter-detail" data-label="Places" style="grid-column: 1 / -1;">
                        <div class="value"><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</div>
                    </div>
                    
                </div>
                
                <!-- Signature -->
                <div class="letter-signature">
                    <div>
                        <div class="letter-signature-text">Avec toute notre affection,</div>
                        <div class="letter-signature-name"><?php echo htmlspecialchars($host1); ?></div>
                    </div>
                    <div class="letter-rsvp">
                        <div class="label">RSVP</div>
                        <div class="value <?php 
                            echo $invitation['statut'] == 'CONFIRMEE' ? 'confirmed' : 
                                ($invitation['statut'] == 'REFUSEE' ? 'refused' : 'pending'); 
                        ?>">
                            <?php 
                            if ($invitation['statut'] == 'CONFIRMEE') echo '✓ Confirmé';
                            elseif ($invitation['statut'] == 'REFUSEE') echo '✗ Refusé';
                            else echo '⏳ En attente';
                            ?>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Sceau de cire final -->
            <div class="final-seal">
                <i class="fas fa-feather-alt"></i>
            </div>
            
        </div>
    </div>

    <!-- ============================================
         MESSAGES
         ============================================ -->
    <?php if ($message): ?>
        <div class="letter-section apparue">
            <div class="letter-alert letter-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         PHOTOS
         ============================================ -->
    <?php if (!empty($photosHost)): ?>
        <div class="letter-section">
            <div class="letter-section-title">Souvenirs partagés</div>
            <div class="letter-photos-grid">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="letter-photo">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                        <div class="caption">Souvenir n°<?php echo $index + 1; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================
         QR CODE
         ============================================ -->
    <div class="letter-section">
        <div class="letter-section-title">Code d'accès</div>
        <div class="letter-qr-wrapper">
            <div class="letter-qr-box">
                <div id="qrcode"></div>
            </div>
            <div style="font-family:'Caveat',cursive;font-size:20px;color:var(--ink-light);margin-top:20px;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         CONFIRMATION
         ============================================ -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="letter-section">
            <div class="letter-section-title">Répondre à l'invitation</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="letter-form-group">
                    <label>Nombre de personnes</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="letter-form-group">
                    <label>Votre réponse</label>
                    <div class="letter-options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="letter-option-radio">
                            <label for="presenceOui" class="letter-option-label">
                                <i class="fas fa-check"></i> Je viendrai
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="letter-option-radio">
                            <label for="presenceNon" class="letter-option-label">
                                <i class="fas fa-times"></i> Je ne pourrai pas
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="letter-form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message_invite" placeholder="Votre message..."></textarea>
                </div>
                
                <button type="submit" class="letter-btn-submit">
                    <i class="fas fa-feather-alt"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================
         BOISSONS
         ============================================ -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="letter-section">
            <div class="letter-section-title">Vos préférences</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:#2d7a45;font-family:'Caveat',cursive;font-size:22px;padding:20px 0;">
                    <i class="fas fa-lock"></i> Vos choix sont enregistrés
                </div>
                <div class="letter-boisson-grid" style="justify-content:center;">
                    <?php foreach ($boissons as $b):
                        if (!isset($preferencesBoissons[$b['id']])) continue;
                    ?>
                        <div class="letter-boisson-item selected">
                            <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                            <span><?php echo htmlspecialchars($b['nom']); ?></span>
                            <i class="fas fa-check-circle check"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Caveat',cursive;font-size:22px;color:var(--ink-light);margin-bottom:20px;">
                        Choisissez jusqu'à <strong style="color:var(--wax);">2 boissons</strong> : <span id="selectedCount">0</span>/2
                    </p>
                    
                    <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                        <div class="letter-boisson-category">
                            <div class="letter-boisson-category-title">
                                <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                                <?php echo htmlspecialchars($type ?: 'Autres'); ?>
                            </div>
                            <div class="letter-boisson-grid">
                                <?php foreach ($boissonsByType as $b): 
                                    $selected = isset($preferencesBoissons[$b['id']]);
                                ?>
                                    <div class="letter-boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
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
                    
                    <button type="submit" class="letter-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="letter-footer">
        <div class="letter-footer-name"><?php echo htmlspecialchars($appName); ?></div>
        <div class="letter-footer-tagline">Papeterie d'exception</div>
        
        <a href="https://wa.me/243829018462" target="_blank" rel="noopener" class="letter-btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(212, 178, 128, 0.2);font-size:11px;letter-spacing:0.2em;color:rgba(245, 239, 227, 0.5);">
            © <?php echo date('Y'); ?> · TOUS DROITS RÉSERVÉS
        </div>
    </footer>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Télécharger</span>
    </button>

    <script>
        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.letter-section');
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
                        colorDark: '#2a2420', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
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
                    backgroundColor: '#f5efe3', logging: false
                });
                const link = document.createElement('a');
                link.download = `lettre_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
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
            document.querySelectorAll('.letter-boisson-item.selected').forEach(item => {
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