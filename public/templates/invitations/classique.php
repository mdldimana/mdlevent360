<?php
/**
 * ============================================================
 * TEMPLATE : CLASSIQUE v3 — Design "Papeterie moderne"
 * ============================================================
 * 
 * Ce template reprend le design épuré et élégant de
 * invitation_demo.php, mais utilise les données réelles
 * de l'invitation ($invitation, $guestName, $photosHost...)
 * 
 * ============================================================
 */

// ============================================================
// PRÉPARATION DES VARIABLES SPÉCIFIQUES AU TEMPLATE
// ============================================================

// Monogramme (première lettre du nom de l'hôte)
$monogramLetter = mb_substr($host1, 0, 1);

// Nom des hôtes (sans le "& XXX" pour la signature)
$hostSignature = explode(' et ', $host1)[0];
if ($hostSignature === $host1) {
    $hostSignature = explode(' & ', $host1)[0];
}
$hostSignature = trim($hostSignature);

// URL de base pour les assets
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . $host;

// Image de fond
$hasFond = !empty($pageBackground);

// Photos (on vérifie que $photosHost est bien un tableau)
$hasPhotos = !empty($photosHost) && is_array($photosHost);

// Statut RSVP
$rsvpLabel = 'En attente de confirmation';
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
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Great+Vibes&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            -webkit-font-smoothing: antialiased;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
        }
        
        .app-wrapper {
            max-width: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 50px;
            position: relative;
            z-index: 1;
            padding: 0 20px;
        }
        .section-animee {
            opacity: 0;
            transform: translateY(80px);
            transition: all 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform, opacity;
        }
        .section-animee.apparue { opacity: 1; transform: translateY(0); }
        .delai-1 { transition-delay: 0.05s; }
        .delai-2 { transition-delay: 0.10s; }
        .delai-3 { transition-delay: 0.15s; }
        .delai-4 { transition-delay: 0.20s; }
        .delai-5 { transition-delay: 0.25s; }
        
        .section {
            width: 100%;
            max-width: 580px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 30px 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .section { padding: 40px 50px; border-radius: 28px; max-width: 680px; }
            .app-wrapper { padding: 0 40px; gap: 60px; }
        }
        @media (min-width: 1200px) {
            .section { padding: 50px 70px; border-radius: 32px; max-width: 800px; }
            .app-wrapper { padding: 0 60px; gap: 70px; }
        }
        .section-title {
            font-family: 'Inter', sans-serif; font-size: 11px; letter-spacing: 0.2em;
            text-transform: uppercase; color: #9a8a7f; font-weight: 500;
            margin-bottom: 16px; text-align: center;
        }
        
        #invitation-card {
            position: relative;
            width: 100%;
            max-width: 580px;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 32px 24px 80px 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        @media (min-width: 768px) {
            #invitation-card { padding: 56px 50px 90px 50px; border-radius: 28px; max-width: 680px; }
        }
        @media (min-width: 1200px) {
            #invitation-card { padding: 70px 70px 100px 70px; border-radius: 32px; max-width: 800px; }
        }
        #invitation-card .card-bg-image {
            position: absolute; inset: 0; background-size: cover; background-position: center;
            opacity: 0.08; pointer-events: none; z-index: 0;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            <?php endif; ?>
        }
        
        .card-bottom-image {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200px;
            height: 200px;
            background-size: cover;
            background-position: center;
            opacity: 0.04;
            pointer-events: none;
            z-index: 0;
            border-radius: 0 50% 0 0;
            <?php if ($hasFond): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            <?php endif; ?>
        }
        @media (min-width: 768px) { .card-bottom-image { width: 300px; height: 300px; } }
        @media (min-width: 1200px) { .card-bottom-image { width: 400px; height: 400px; } }
        
        .monogram-bg {
            position: absolute; top: 98px; left: 50%; transform: translateX(-50%);
            pointer-events: none; user-select: none; font-family: 'Great Vibes', cursive;
            font-size: 120px; line-height: 1; letter-spacing: -0.05em; color: rgba(200, 190, 180, 0.25);
            font-weight: 400; white-space: nowrap; z-index: 1;
        }
        @media (min-width: 768px) { .monogram-bg { font-size: 180px; } }
        @media (min-width: 1200px) { .monogram-bg { font-size: 220px; } }
        .arch {
            position: absolute; left: 50%; top: 176px; transform: translateX(-50%);
            width: 220px; height: 300px; pointer-events: none; max-width: 75%; z-index: 1;
        }
        @media (min-width: 768px) { .arch { top: 168px; width: 300px; height: 400px; max-width: none; } }
        .arch-inner {
            width: 100%; height: 100%; border-radius: 140px 140px 0 0;
            border: 1px solid rgba(234, 220, 209, 0.4); border-bottom: 0;
            background: linear-gradient(to bottom, rgba(251,248,245,0.6), transparent);
        }
        .arch-inner::after {
            content: ''; position: absolute; inset: 10px; border-radius: 130px 130px 0 0;
            border: 1px solid rgba(234,220,209,0.3); border-bottom: 0; top: 10px;
        }
        .deco-circles {
            position: absolute; top: 52px; right: 24px; display: flex; opacity: 0.7; pointer-events: none; z-index: 1;
        }
        @media (min-width: 768px) { .deco-circles { right: 56px; } }
        .deco-circles span { width: 28px; height: 28px; border-radius: 50%; border: 1px solid rgba(26,26,26,0.10); }
        .deco-circles span:last-child { border-color: rgba(193,124,96,0.2); margin-left: -12px; margin-top: 6px; }
        #invitation-card .card-content { position: relative; z-index: 2; text-align: center; }
        .badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;
            border-radius: 9999px; border: 1px solid rgba(234, 227, 220, 0.6); background: rgba(251, 248, 245, 0.7);
            font-family: 'Inter', sans-serif; font-size: 9.5px; letter-spacing: 0.18em;
            text-transform: uppercase; font-weight: 500; color: #7a6b60;
        }
        .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: #c17c60; }
        .badge-dark {
            display: inline-flex; align-items: center; gap: 10px; padding: 8px 16px;
            border-radius: 9999px; background: rgba(26, 26, 26, 0.85); color: white;
            font-family: 'Inter', sans-serif; font-size: 11px; letter-spacing: 0.14em;
            text-transform: uppercase; font-weight: 500;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .badge-dark-dot { width: 4px; height: 4px; border-radius: 50%; background: #c17c60; }
        .card-qr-wrapper { display: flex; justify-content: center; margin: 20px 0 10px; }
        .card-qr-wrapper #card-qrcode { 
            padding: 16px; background: rgba(255, 255, 255, 0.9); 
            border-radius: 12px; border: 2px solid rgba(234, 227, 220, 0.6);
            width: 160px; height: 160px;
            display: flex; align-items: center; justify-content: center;
        }
        .card-qr-wrapper #card-qrcode canvas,
        .card-qr-wrapper #card-qrcode img {
            width: 130px !important; height: 130px !important;
        }
        
        #downloadBtn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 12px; padding: 14px 32px; border-radius: 9999px;
            background: rgba(26, 26, 26, 0.9); color: white;
            font-family: 'Inter', sans-serif; font-size: 14px;
            letter-spacing: 0.04em; font-weight: 500;
            border: none; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            position: absolute; bottom: 20px; left: 50%;
            transform: translateX(-50%); z-index: 10;
            width: auto; min-width: 200px;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        #downloadBtn:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: translateX(-50%) scale(1.02);
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        }
        #downloadBtn:disabled { opacity: 0.7; pointer-events: none; }
        #downloadBtn .icon {
            width: 20px; height: 20px; border-radius: 50%;
            background: white; color: black;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; transition: transform 0.3s ease;
        }
        #downloadBtn:hover .icon { transform: rotate(12deg); }
        @media (max-width: 768px) {
            #downloadBtn { bottom: 15px; padding: 12px 20px; font-size: 12px; min-width: 160px; }
            #invitation-card { max-width: 100%; }
            .section { max-width: 100%; }
        }
        @media (max-width: 480px) {
            #downloadBtn { bottom: 10px; padding: 10px 16px; font-size: 11px; min-width: 140px; }
        }
        
        #section-photos {
            width: 100%; max-width: 580px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        @media (min-width: 768px) { #section-photos { max-width: 680px; border-radius: 28px; } }
        @media (min-width: 1200px) { #section-photos { max-width: 800px; border-radius: 32px; } }

        .diaporama-container {
            position: relative; width: 100%;
            overflow: hidden;
            background: rgba(26, 26, 26, 0.6);
            aspect-ratio: 16/9;
            margin: 0 auto; max-width: 100%;
        }
        .diaporama-container .slide {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            z-index: 0;
            background: rgba(26, 26, 26, 0.6);
        }
        .diaporama-container .slide.active { opacity: 1; z-index: 1; }
        .diaporama-container .slide img {
            width: 100%; height: 100%;
            object-fit: contain;
            background: rgba(26, 26, 26, 0.6);
            padding: 4px;
        }
        .diaporama-container .slide .placeholder {
            display: flex; align-items: center; justify-content: center;
            height: 100%; width: 100%;
            color: #d4c5b2; font-size: 60px;
            background: rgba(248, 245, 242, 0.5);
        }
        
        .diapo-nav-indicators {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 20px 4px 20px;
        }
        .diapo-nav-indicators .nav-hint {
            font-family: 'Inter', sans-serif; font-size: 12px;
            color: #b8a99c; letter-spacing: 0.1em;
            text-transform: uppercase; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; transition: all 0.3s ease;
            padding: 4px 12px; border-radius: 20px;
            border: 1px solid transparent;
        }
        .diapo-nav-indicators .nav-hint:hover {
            color: #c17c60; border-color: rgba(234, 227, 220, 0.6);
        }
        .diapo-nav-indicators .nav-hint i { font-size: 14px; }
        .diapo-nav-indicators .nav-hint.disabled {
            opacity: 0.3; cursor: not-allowed; pointer-events: none;
        }
        
        .diapo-indicators {
            display: flex; justify-content: center; gap: 10px; margin-top: 8px;
        }
        .diapo-indicators span {
            width: 10px; height: 10px; border-radius: 50%;
            background: rgba(212, 197, 178, 0.6);
            cursor: pointer; transition: all 0.3s ease;
        }
        .diapo-indicators span.active { background: #c17c60; transform: scale(1.5); }
        .diapo-indicators span:hover { background: #c17c60; }
        
        .boisson-category { margin-bottom: 20px; }
        .boisson-category:last-child { margin-bottom: 0; }
        .boisson-category-title {
            font-family: 'Inter', sans-serif; font-size: 11px;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: #9a8a7f; font-weight: 600;
            margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .boisson-category-title i { color: #c17c60; font-size: 13px; }
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .boisson-item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px 8px 12px;
            border: 2px solid rgba(234, 227, 220, 0.6);
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.7);
            cursor: pointer; transition: all 0.3s ease;
            position: relative; flex-shrink: 0;
        }
        .boisson-item:hover {
            border-color: #c17c60; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193,124,96,0.10);
        }
        .boisson-item.selected {
            border-color: #c17c60;
            background: linear-gradient(135deg, rgba(251, 248, 245, 0.8), rgba(245, 238, 232, 0.8));
            box-shadow: 0 4px 12px rgba(193,124,96,0.15);
        }
        .boisson-item.selected .boisson-check-icon { opacity: 1; }
        .boisson-item .boisson-icon {
            font-size: 16px; color: #c17c60;
            width: 24px; text-align: center; flex-shrink: 0;
        }
        .boisson-item .boisson-info .boisson-nom {
            font-family: 'Inter', sans-serif; font-size: 13px;
            font-weight: 500; color: #1a1a1a;
        }
        .boisson-item .boisson-check-icon {
            color: #c17c60; font-size: 16px;
            opacity: 0; transition: opacity 0.3s ease;
            flex-shrink: 0; margin-left: 2px;
        }
        .limit-indicator {
            font-family: 'Inter', sans-serif; font-size: 12px;
            color: #b8a99c; text-align: center; margin-top: 4px;
        }
        .limit-indicator span { font-weight: 600; color: #c17c60; }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .options-grid { grid-template-columns: 1fr; } }
        .option-radio { display: none; }
        .option-radio-label {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; padding: 14px 16px;
            border: 2px solid rgba(234, 227, 220, 0.6);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.7);
            font-family: 'Inter', sans-serif; font-size: 14px;
            font-weight: 500; color: #5a4a3a;
            cursor: pointer; transition: all 0.3s ease;
            text-align: center; min-height: 50px;
        }
        .option-radio-label:hover {
            border-color: #c17c60; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193,124,96,0.10);
        }
        .option-radio:checked + .option-radio-label {
            border-color: #c17c60;
            background: linear-gradient(135deg, rgba(251, 248, 245, 0.8), rgba(245, 238, 232, 0.8));
            box-shadow: 0 4px 12px rgba(193,124,96,0.15);
            font-weight: 600; color: #1a1a1a;
        }
        .option-radio:checked + .option-radio-label i { color: #c17c60; }
        
        .btn-confirm {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 12px; width: 100%; padding: 16px 32px;
            border-radius: 9999px;
            background: rgba(26, 26, 26, 0.9); color: white;
            font-family: 'Inter', sans-serif; font-size: 15px;
            letter-spacing: 0.04em; font-weight: 600;
            border: none; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .btn-confirm:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: scale(1.02);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }
        .btn-confirm.gold {
            background: linear-gradient(135deg, rgba(193, 124, 96, 0.9), rgba(212, 165, 116, 0.9));
            box-shadow: 0 8px 24px rgba(193,124,96,0.25);
        }
        .btn-confirm.gold:hover {
            background: linear-gradient(135deg, rgba(168, 106, 80, 0.95), rgba(196, 144, 106, 0.95));
            box-shadow: 0 12px 32px rgba(193,124,96,0.35);
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group:last-of-type { margin-bottom: 24px; }
        .form-group label {
            display: block; font-family: 'Inter', sans-serif;
            font-weight: 400; color: #6a5a4a; font-size: 12px;
            letter-spacing: 0.08em; text-transform: uppercase;
            margin-bottom: 8px;
        }
        .form-group label i { margin-right: 8px; color: #c17c60; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 12px 16px;
            border: 1.5px solid rgba(234, 227, 220, 0.6);
            border-radius: 10px;
            font-family: 'Inter', sans-serif; font-size: 15px;
            color: #1a1a1a; background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #c17c60; outline: none;
            box-shadow: 0 0 0 4px rgba(193,124,96,0.08);
        }
        .form-group textarea { height: 80px; resize: vertical; }
        
        .footer-text {
            font-family: 'Inter', sans-serif; font-size: 10px;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: rgba(184,169,156,0.7); text-align: center;
            padding-top: 20px;
        }
        
        .btn-whatsapp {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 12px; width: 100%; padding: 14px 32px;
            border-radius: 9999px;
            background: rgba(37, 211, 102, 0.9); color: white;
            font-family: 'Inter', sans-serif; font-size: 14px;
            letter-spacing: 0.04em; font-weight: 500;
            border: none; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(37,211,102,0.25);
            text-decoration: none;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .btn-whatsapp:hover {
            background: rgba(29, 168, 81, 0.95);
            transform: scale(1.01); color: white; text-decoration: none;
        }

        /* Alertes */
        .letter-alert {
            padding: 16px 24px; margin-bottom: 20px; font-size: 14px;
            display: flex; gap: 12px; align-items: center;
            border-radius: 12px; border-left: 3px solid;
        }
        .letter-alert-success { border-color: #2d7a45; background: rgba(45,122,69,0.08); color: #2d7a45; }
        .letter-alert-danger  { border-color: #c17c60; background: rgba(193,124,96,0.08); color: #c17c60; }
        .letter-alert-warning { border-color: #b8935a; background: rgba(184,147,90,0.1); color: #8c6a3a; }

        @media (max-width: 768px) {
            .app-wrapper { gap: 40px; padding: 0 10px; }
            #invitation-card { padding: 24px 16px 70px 16px; border-radius: 20px; }
            .section { padding: 20px 16px; border-radius: 20px; }
            #section-photos { padding: 10px 0; border-radius: 20px; }
            .monogram-bg { font-size: 80px; top: 70px; }
            .arch { width: 160px; height: 220px; top: 130px; }
            .deco-circles { top: 30px; right: 16px; }
            .deco-circles span { width: 20px; height: 20px; }
            .card-qr-wrapper #card-qrcode { width: 130px; height: 130px; }
            .card-qr-wrapper #card-qrcode canvas,
            .card-qr-wrapper #card-qrcode img { width: 100px !important; height: 100px !important; }
        }
        @media (max-width: 480px) {
            .app-wrapper { gap: 30px; }
            .diaporama-container { aspect-ratio: 16/10; }
            .card-qr-wrapper #card-qrcode { width: 110px; height: 110px; }
            .card-qr-wrapper #card-qrcode canvas,
            .card-qr-wrapper #card-qrcode img { width: 80px !important; height: 80px !important; }
            .boisson-grid { gap: 8px; }
            .boisson-item { padding: 5px 10px 5px 8px; font-size: 11px; }
            .boisson-item .boisson-icon { font-size: 12px; width: 18px; }
            .boisson-item .boisson-info .boisson-nom { font-size: 11px; }
        }
    </style>
</head>
<body>

<div class="app-wrapper">

    <!-- ========================================== -->
    <!-- CARTE D'INVITATION                         -->
    <!-- ========================================== -->
    <div id="invitation-card" class="section-animee zoom-in delai-1">
        <div class="card-bg-image"></div>
        <div class="card-bottom-image"></div>

        <div class="monogram-bg" id="monogramDisplay">
            <?php echo htmlspecialchars($monogramLetter); ?>
        </div>

        <div class="arch"><div class="arch-inner"></div></div>

        <div class="deco-circles"><span></span><span></span></div>

        <div class="card-content">

            <div class="badge" style="margin:0 auto;">
                <span class="badge-dot"></span>
                Invitation personnelle
            </div>

            <div style="margin-top:40px;">
                <div style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.28em;text-transform:uppercase;color:#a99a8d;font-weight:500;margin-bottom:8px;">
                    À l'attention de
                </div>
                <div style="font-family:'Inter',sans-serif;font-size:15px;letter-spacing:0.18em;text-transform:uppercase;font-weight:600;color:#1a1a1a;">
                    <?php echo htmlspecialchars($guestName); ?>
                </div>
                <div style="margin-top:12px;width:22px;height:1px;background:#c17c60;margin-left:auto;margin-right:auto;"></div>
            </div>

            <div style="margin-top:40px;">
                <h1 style="font-family:'Great Vibes', cursive;line-height:0.88;letter-spacing:-0.03em;color:#1a1a1a;font-weight:400;">
                    <span style="display:block;font-size:4.5rem;">
                        <?php echo htmlspecialchars($host1); ?>
                    </span>
                </h1>

                <div style="margin-top:24px;display:flex;align-items:center;justify-content:center;gap:12px;">
                    <span style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9a8a7f;">vous convient à</span>
                    <div style="height:1px;width:40px;background:rgba(234, 227, 220, 0.6);"></div>
                </div>
                <div style="margin-top:8px;font-family:'Inter',sans-serif;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500;color:#1a1a1a;">
                    célébrer leur <?php echo htmlspecialchars($eventType); ?>
                </div>
            </div>

            <div style="margin-top:40px;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:12px;">
                <div class="badge-dark">
                    <span class="badge-dark-dot"></span>
                    <?php echo htmlspecialchars($eventDate); ?>
                </div>
                <div style="font-family:'Inter',sans-serif;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#8c7e73;">
                    à <?php echo htmlspecialchars($eventTime ?: '--:--'); ?> • <?php echo htmlspecialchars($eventType); ?>
                </div>
            </div>

            <!-- Description personnalisée -->
            <div style="margin-top:32px;text-align:center;">
                <?php if (!empty($eventDescription)): ?>
                    <?php 
                    $paragraphs = explode("\n", $eventDescription);
                    $first = true;
                    foreach ($paragraphs as $paragraph):
                        if (trim($paragraph) === '') continue;
                    ?>
                        <p style="font-family:'Inter',sans-serif;font-size:15px;line-height:1.75;font-weight:300;color:#2e2e2e;margin-top:<?php echo $first ? '0' : '16px'; ?>;">
                            <?php echo nl2br(htmlspecialchars(trim($paragraph))); ?>
                        </p>
                    <?php 
                        $first = false;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <p style="font-family:'Inter',sans-serif;font-size:15px;line-height:1.75;font-weight:300;color:#2e2e2e;margin-top:0;">
                        C'est avec un immense plaisir que nous vous convions à partager avec nous un moment d'exception à l'occasion de notre <?php echo htmlspecialchars($eventType); ?>.
                    </p>
                    <p style="font-family:'Inter',sans-serif;font-size:15px;line-height:1.75;font-weight:300;color:#2e2e2e;margin-top:16px;">
                        Votre présence serait pour nous le plus précieux des présents. Nous espérons de tout cœur que vous pourrez vous joindre à nous pour célébrer ce moment unique.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Adresse -->
            <div style="margin-top:36px;display:flex;justify-content:center;">
                <div style="border-radius:12px;background:rgba(251, 248, 245, 0.7);border:1px solid rgba(239, 230, 221, 0.6);padding:14px 20px;display:inline-flex;align-items:center;flex-wrap:wrap;gap:12px;justify-content:center;max-width:100%;">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:center;">
                        <div style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.8);border:1px solid rgba(234, 227, 220, 0.6);display:flex;align-items:center;justify-content:center;">
                            <div style="width:12px;height:12px;border-radius:50%;border:1px solid #1a1a1a;display:flex;align-items:center;justify-content:center;">
                                <div style="width:3px;height:3px;border-radius:50%;background:#c17c60;"></div>
                            </div>
                        </div>
                        <div style="text-align:left;min-width:0;">
                            <div style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;font-weight:600;color:#9a8a7f;margin-bottom:2px;">
                                Adresse
                            </div>
                            <div style="font-family:'Inter',sans-serif;font-size:13px;line-height:1.5;color:#1a1a1a;word-wrap:break-word;">
                                <?php echo htmlspecialchars($lieuDisplay); ?>
                                <?php if ($adresseDisplay): ?>
                                    <span style="color:#9a8a7f;font-size:12px;"> — <?php echo htmlspecialchars($adresseDisplay); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lieuDisplay . ' ' . $adresseDisplay); ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border-radius:9999px;background:rgba(193,124,96,0.9);color:white;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;border:none;cursor:pointer;transition:all 0.3s ease;text-decoration:none;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);margin-left:0;">
                        <i class="fas fa-map-marked-alt"></i> Itinéraire
                    </a>
                </div>
            </div>

            <!-- Infos table (si assignée) -->
            <?php if ($hasTable): ?>
            <div style="margin-top:20px;display:flex;justify-content:center;">
                <div style="border-radius:12px;background:rgba(45,122,69,0.08);border:1px solid rgba(45,122,69,0.2);padding:12px 20px;display:inline-flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:center;">
                    <i class="fas fa-chair" style="color:#2d7a45;font-size:18px;"></i>
                    <div style="text-align:left;">
                        <div style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;font-weight:600;color:#2d7a45;margin-bottom:2px;">
                            Votre table
                        </div>
                        <div style="font-family:'Inter',sans-serif;font-size:14px;font-weight:600;color:#1a1a1a;">
                            <?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?>
                            <?php if (!empty($tableZone)): ?>
                                <span style="color:#6a5a4a;font-size:12px;font-weight:400;"> · Zone <?php echo htmlspecialchars($tableZone); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;justify-content:center;margin:28px 0 20px;">
                <div style="height:1px;width:40px;background:rgba(234, 227, 220, 0.6);"></div>
                <span style="margin:0 12px;width:6px;height:6px;border-radius:50%;background:#c17c60;"></span>
                <div style="height:1px;width:40px;background:rgba(234, 227, 220, 0.6);"></div>
            </div>

            <div class="card-qr-wrapper" id="card-qr-container">
                <div id="card-qrcode"></div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-top:16px;">
                <div style="text-align:left;">
                    <div style="font-family:'Great Vibes', cursive;font-size:16px;color:#6b5e54;margin-bottom:8px;">Avec tout notre amour,</div>
                    <div style="font-family:'Great Vibes', cursive;font-size:32px;line-height:1;color:#1a1a1a;">
                        <?php echo htmlspecialchars($host1); ?>
                    </div>
                    <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                        <div style="height:1px;width:32px;background:#c17c60;"></div>
                        <span style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:#a99a8d;">éternellement</span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-family:'Inter',sans-serif;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#a99a8d;">RSVP</div>
                    <div style="font-family:'Inter',sans-serif;font-size:11px;color:#1a1a1a;margin-top:4px;font-weight:500;">
                        <?php echo htmlspecialchars($rsvpLabel); ?>
                    </div>
                </div>
            </div>

        </div>
        
        <!-- Bouton Télécharger flottant -->
        <button class="btn-download" id="downloadBtn" onclick="telechargerJPEG()">
            <span class="icon">↓</span>
            <span id="btnText">Télécharger l'invitation</span>
            <span style="margin-left:4px;width:1px;height:16px;background:rgba(255,255,255,0.2);"></span>
            <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,0.6);letter-spacing:0.025em;">HD</span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- MESSAGES                                   -->
    <!-- ========================================== -->
    <?php if ($message): ?>
        <div class="section section-animee apparue">
            <div class="letter-alert letter-alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- DIAPORAMA PHOTOS                          -->
    <!-- ========================================== -->
    <?php if ($hasPhotos): ?>
    <div class="section-animee fade-right delai-2" id="section-photos">
        <div class="section-title" style="padding: 0 20px 16px 20px; margin-bottom: 0;">
            <i class="fas fa-images" style="margin-right:8px;color:#c17c60;"></i>
            Souvenirs partagés
        </div>
        
        <div class="diaporama-container" id="diaporama">
            <?php 
            $photoIndex = 0;
            foreach ($photosHost as $index => $photo): 
            ?>
                <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                    <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                         alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Photo ' . ($index + 1)); ?>"
                         loading="lazy"
                         crossorigin="anonymous"
                         onerror="this.parentElement.innerHTML='<div class=placeholder><i class=fas fa-image></i></div>'">
                </div>
            <?php 
                $photoIndex++;
            endforeach; 
            ?>
        </div>
        
        <?php if ($photoIndex > 0): ?>
            <div class="diapo-nav-indicators">
                <span class="nav-hint" id="navPrev" onclick="changerDiapo(-1)">
                    <i class="fas fa-chevron-left"></i> Précédent
                </span>
                <span class="diapo-counter" id="diapoCounter" style="font-family:'Inter',sans-serif;font-size:14px;color:#6a5a4a;font-weight:600;">1 / <?php echo $photoIndex; ?></span>
                <span class="nav-hint" id="navNext" onclick="changerDiapo(1)">
                    Suivant <i class="fas fa-chevron-right"></i>
                </span>
            </div>
            
            <div class="diapo-indicators" id="diapoIndicators">
                <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                    <span data-index="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToDiapo(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- CONFIRMATION DE PRÉSENCE                  -->
    <!-- ========================================== -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
    <div class="section section-animee flip delai-3" id="section-confirmation">
        <div class="section-title"><i class="fas fa-check-circle" style="margin-right:8px;color:#c17c60;"></i> Confirmation de présence</div>
        
        <div style="text-align:center; margin-bottom:24px;">
            <div style="font-family:'Inter',sans-serif;font-size:14px;color:#6a5a4a;">
                <i class="fas fa-user" style="color:#c17c60;margin-right:8px;"></i>
                <strong><?php echo htmlspecialchars($guestName); ?></strong>
            </div>
            <div style="font-family:'Inter',sans-serif;font-size:12px;color:#b8a99c;margin-top:4px;">
                <i class="fas fa-calendar-alt" style="margin-right:6px;"></i>
                <?php echo htmlspecialchars($eventDate); ?>
                <?php if ($eventTime): ?> • <?php echo htmlspecialchars($eventTime); ?><?php endif; ?>
            </div>
        </div>

        <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
            <input type="hidden" name="action" value="confirmer">
            
            <div class="form-group">
                <label><i class="fas fa-users"></i> Nombre de personnes</label>
                <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                <div style="font-family:'Inter',sans-serif;font-size:12px;color:#b8a99c;margin-top:4px;">
                    <i class="fas fa-info-circle"></i> Max: <?php echo $invitation['nb_places_max'] ?? 1; ?> personne(s)
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-check"></i> Votre réponse</label>
                <div class="options-grid">
                    <div>
                        <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="option-radio">
                        <label for="presenceOui" class="option-radio-label">
                            <i class="fas fa-check-circle" style="color:#28a745;font-size:18px;"></i> 
                            Je confirme
                        </label>
                    </div>
                    <div>
                        <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="option-radio">
                        <label for="presenceNon" class="option-radio-label">
                            <i class="fas fa-times-circle" style="color:#dc3545;font-size:18px;"></i> 
                            Je ne peux pas
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-pen"></i> Message (optionnel)</label>
                <textarea name="message_invite" placeholder="Un petit mot pour les organisateurs..." rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn-confirm gold">
                <i class="fas fa-check-circle"></i> Confirmer ma présence
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- PRÉFÉRENCES BOISSONS                      -->
    <!-- ========================================== -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
    <div class="section section-animee fade-left delai-4" id="section-boissons">
        <div class="section-title">
            <i class="fas fa-wine-glass-alt" style="margin-right:8px;color:#c17c60;"></i>
            Choisissez vos boissons (max 2)
            <?php if ($isLocked): ?>
                <span style="display:inline-block;background:rgba(45,122,69,0.15);color:#2d7a45;padding:4px 14px;border-radius:100px;font-size:12px;font-weight:600;margin-left:8px;">
                    <i class="fas fa-lock"></i> Enregistré
                </span>
            <?php endif; ?>
        </div>

        <?php if ($isLocked): ?>
            <div style="text-align:center;color:#2d7a45;font-weight:600;padding:10px 0 20px 0;font-size:14px;">
                <i class="fas fa-check-circle"></i> Vos préférences sont enregistrées
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">
                <?php foreach ($boissons as $b):
                    if (!isset($preferencesBoissons[$b['id']])) continue;
                ?>
                    <div class="boisson-item selected" style="cursor:default;">
                        <div class="boisson-icon"><i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i></div>
                        <div class="boisson-info"><div class="boisson-nom"><?php echo htmlspecialchars($b['nom']); ?></div></div>
                        <div class="boisson-check-icon" style="opacity:1;"><i class="fas fa-check-circle"></i></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center;margin-bottom:16px;color:#6a5a4a;font-size:13px;">
                <i class="fas fa-info-circle" style="color:#c17c60;"></i> 
                Sélectionnez jusqu'à 2 boissons (cliquez pour sélectionner)
            </div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences" id="preferencesForm">
                <input type="hidden" name="action" value="preferences">
                
                <div class="limit-indicator" style="margin-bottom:16px;">
                    <i class="fas fa-info-circle"></i> Sélectionnez <span id="selectedCount">0</span>/2 boissons
                </div>
                
                <?php foreach ($boissonsGrouped as $type => $boissonsByType): ?>
                    <div class="boisson-category">
                        <div class="boisson-category-title">
                            <i class="fas <?php echo getBoissonIcon($type); ?>"></i>
                            <?php echo htmlspecialchars($type ?: 'Autres boissons'); ?>
                        </div>
                        <div class="boisson-grid">
                            <?php foreach ($boissonsByType as $b): 
                                $selected = isset($preferencesBoissons[$b['id']]);
                            ?>
                                <div class="boisson-item <?php echo $selected ? 'selected' : ''; ?>" 
                                     data-id="<?php echo $b['id']; ?>"
                                     onclick="toggleBoisson(this, <?php echo $b['id']; ?>)">
                                    <div class="boisson-icon">
                                        <i class="fas <?php echo getBoissonIcon($b['type']); ?>"></i>
                                    </div>
                                    <div class="boisson-info">
                                        <div class="boisson-nom"><?php echo htmlspecialchars($b['nom']); ?></div>
                                    </div>
                                    <div class="boisson-check-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <input type="checkbox" name="boissons[]" value="<?php echo $b['id']; ?>" 
                                           style="display:none;" <?php echo $selected ? 'checked' : ''; ?>>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-confirm gold" style="margin-top:20px;" id="savePreferencesBtn">
                    <i class="fas fa-save"></i> Enregistrer mes préférences
                </button>
                <div style="font-family:'Inter',sans-serif;font-size:12px;color:#b8a99c;text-align:center;margin-top:8px;">
                    <i class="fas fa-info-circle"></i> Une fois enregistrées, vous ne pourrez plus les modifier.
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- QR CODE SECTION                           -->
    <!-- ========================================== -->
    <div class="section section-animee fade-right delai-3" id="section-qr">
        <div class="section-title"><i class="fas fa-qrcode" style="margin-right:8px;color:#c17c60;"></i> Code QR</div>
        <p style="font-family:'Inter',sans-serif;font-size:13px;color:#8c7e73;text-align:center;margin-bottom:12px;">Scannez pour accéder à l'invitation</p>
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:16px 0 8px;">
            <div id="qrcode" style="padding:16px;background:rgba(255,255,255,0.9);border-radius:12px;border:1px solid rgba(234, 227, 220, 0.6);"></div>
            <div style="font-family:'Inter',sans-serif;font-size:12px;color:#b8a99c;margin-top:4px;">Code: <?php echo htmlspecialchars($invitation['code_unique']); ?></div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FOOTER                                    -->
    <!-- ========================================== -->
    <div class="section section-animee flip delai-5">
        <div style="text-align:center;font-family:'Inter',sans-serif;font-size:13px;color:#6a5a4a;margin-bottom:16px;font-weight:600;">
            <i class="fas fa-star" style="color:#c17c60;"></i> Invitation réalisée par MdlEvent <i class="fas fa-star" style="color:#c17c60;"></i>
        </div>
        <a href="https://wa.me/243963967028?text=Bonjour%2C%20je%20souhaite%20avoir%20des%20informations%20sur%20votre%20plateforme%20d%27invitation" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp" style="font-size:20px;"></i> Nous contacter sur WhatsApp
            <span style="font-size:11px;font-weight:400;opacity:0.8;">+243 963 967 028</span>
        </a>
        <div class="footer-text" style="padding-top:16px;margin-top:12px;border-top:1px solid rgba(234, 227, 220, 0.6);">
            <i class="fas fa-heart" style="color:#c17c60;"></i> MdlEvent • Tous droits réservés
        </div>
    </div>

</div>

<script>
// ================================================================
// 1. ANIMATIONS AU SCROLL
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('.section-animee');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('apparue'); } });
    }, { threshold: 0.15, rootMargin: '0px 0px -30px 0px' });
    sections.forEach(section => { observer.observe(section); });
    setTimeout(() => {
        sections.forEach(section => {
            const rect = section.getBoundingClientRect();
            if (rect.top < window.innerHeight * 0.85) { section.classList.add('apparue'); }
        });
    }, 300);
});

// ================================================================
// 2. QR CODE
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    if (typeof QRCode !== 'undefined') {
        try {
            new QRCode(document.getElementById('card-qrcode'), {
                text: '<?php echo addslashes($fullUrl); ?>',
                width: 150,
                height: 150,
                colorDark: '#1a1a1a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch(e) {
            console.error('Erreur QR code carte:', e);
        }
        
        try {
            new QRCode(document.getElementById('qrcode'), {
                text: '<?php echo addslashes($fullUrl); ?>',
                width: 140,
                height: 140,
                colorDark: '#1a1a1a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch(e) {
            console.error('Erreur QR code section:', e);
        }
    }
});

// ================================================================
// 3. DIAPORAMA PHOTOS
// ================================================================
let diapoIndex = 0;
const slides = document.querySelectorAll('.slide');
const indicators = document.querySelectorAll('#diapoIndicators span');
const counter = document.getElementById('diapoCounter');
const navPrev = document.getElementById('navPrev');
const navNext = document.getElementById('navNext');
let autoPlayInterval = null;

function updateDiapo() {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === diapoIndex);
    });
    indicators.forEach((ind, i) => {
        ind.classList.toggle('active', i === diapoIndex);
    });
    if (counter) {
        counter.textContent = (diapoIndex + 1) + ' / ' + slides.length;
    }
    if (navPrev) {
        if (diapoIndex === 0) {
            navPrev.classList.add('disabled');
        } else {
            navPrev.classList.remove('disabled');
        }
    }
    if (navNext) {
        if (diapoIndex === slides.length - 1) {
            navNext.classList.add('disabled');
        } else {
            navNext.classList.remove('disabled');
        }
    }
}

function changerDiapo(direction) {
    const newIndex = diapoIndex + direction;
    if (newIndex < 0 || newIndex >= slides.length) return;
    diapoIndex = newIndex;
    updateDiapo();
    resetAutoPlay();
}

function goToDiapo(index) {
    diapoIndex = index;
    updateDiapo();
    resetAutoPlay();
}

function resetAutoPlay() {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
    }
    if (slides.length > 1) {
        autoPlayInterval = setInterval(() => {
            if (diapoIndex < slides.length - 1) {
                changerDiapo(1);
            } else {
                diapoIndex = 0;
                updateDiapo();
            }
        }, 4000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (slides.length > 0) {
        updateDiapo();
        resetAutoPlay();
        
        const container = document.getElementById('diaporama');
        if (container) {
            container.addEventListener('mouseenter', function() {
                if (autoPlayInterval) {
                    clearInterval(autoPlayInterval);
                    autoPlayInterval = null;
                }
            });
            container.addEventListener('mouseleave', function() {
                resetAutoPlay();
            });
        }
    }
});

// ================================================================
// 4. BOISSONS
// ================================================================
let selectedBoissons = [];

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.boisson-item.selected').forEach(item => {
        const id = parseInt(item.dataset.id);
        if (!isNaN(id) && !selectedBoissons.includes(id)) {
            selectedBoissons.push(id);
        }
    });
    updateCount();
});

function toggleBoisson(element, id) {
    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        const index = selectedBoissons.indexOf(id);
        if (index > -1) {
            selectedBoissons.splice(index, 1);
        }
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
    const el = document.getElementById('selectedCount');
    if (el) el.textContent = selectedBoissons.length;
    const items = document.querySelectorAll('.boisson-item');
    items.forEach(item => {
        if (!item.classList.contains('selected') && selectedBoissons.length >= 2) {
            item.style.opacity = '0.5';
            item.style.cursor = 'not-allowed';
        } else {
            item.style.opacity = '1';
            item.style.cursor = 'pointer';
        }
    });
}

// ================================================================
// 5. TÉLÉCHARGEMENT JPEG (capture toute la carte)
// ================================================================
async function telechargerJPEG() {
    const btn = document.getElementById('downloadBtn');
    const btnText = document.getElementById('btnText');
    const card = document.getElementById('invitation-card');
    btn.disabled = true;
    btnText.textContent = 'Génération...';
    try {
        await new Promise(resolve => setTimeout(resolve, 300));
        const canvas = await html2canvas(card, {
            scale: 2.5,
            useCORS: true,
            allowTaint: true,
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            logging: false,
            width: card.offsetWidth,
            height: card.offsetHeight,
            windowWidth: card.scrollWidth,
            windowHeight: card.scrollHeight,
            onclone: function(clonedDoc) {
                const clonedCard = clonedDoc.getElementById('invitation-card');
                if (clonedCard) {
                    clonedCard.style.transform = 'none';
                    clonedCard.style.opacity = '1';
                }
            }
        });
        const link = document.createElement('a');
        const name = '<?php echo htmlspecialchars($host1); ?>';
        link.download = `invitation_${name.replace(/\s/g, '_')}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();
        btnText.textContent = 'JPEG téléchargé ✓';
        setTimeout(() => { btnText.textContent = 'Télécharger l\'invitation'; }, 3000);
    } catch (error) {
        console.error('Erreur:', error);
        btnText.textContent = 'Erreur — réessayez';
        setTimeout(() => { btnText.textContent = 'Télécharger l\'invitation'; }, 3000);
    }
    btn.disabled = false;
}
</script>

</body>
</html>