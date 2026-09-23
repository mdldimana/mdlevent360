<?php
/**
 * ============================================================
 * TEMPLATE : STORY INSTAGRAM - v7 FIX QR BASE64
 * ============================================================
 * Fix: QR en base64 côté PHP pour que html2canvas le capture
 * ============================================================
 */

// FIX QR - Génération en base64 pour capture garantie
if (!empty($fullUrl)) {
    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=330x330&data=" . urlencode($fullUrl) . "&color=1a1a1a&bgcolor=ffffff&margin=1&qzone=1";
    $qrDataUri = $qrApiUrl;
    $qrContent = @file_get_contents($qrApiUrl);
    if ($qrContent !== false && strlen($qrContent) > 100) {
        $qrDataUri = "data:image/png;base64," . base64_encode($qrContent);
    }
} else {
    $qrDataUri = "";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - <?php echo htmlspecialchars($invitation['evenement_nom']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            color: white;
            min-height: 100vh;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        
        @media (min-width: 768px) {
            body {
                background: #111;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                padding: 40px 20px;
            }
        }
        
        .story-container {
            position: relative;
            width: 100%;
            max-width: 420px;
            aspect-ratio: 9/16;
            background: #000;
            overflow: hidden;
            border-radius: 0;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
        }
        
        @media (min-width: 768px) {
            .story-container {
                border-radius: 40px;
                box-shadow: 
                    0 0 0 12px #000,
                    0 0 0 14px #333,
                    0 40px 100px rgba(0,0,0,0.9);
            }
        }
        
        .story-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            z-index: 0;
        }
        .story-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, 
                rgba(0,0,0,0.55) 0%, 
                rgba(0,0,0,0.15) 30%,
                rgba(0,0,0,0.4) 70%,
                rgba(0,0,0,0.95) 100%);
        }
        
        .story-progress {
            position: relative;
            z-index: 10;
            display: flex;
            gap: 4px;
            padding: 12px 12px 0;
            flex-shrink: 0;
        }
        .story-progress .bar {
            flex: 1;
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
            overflow: hidden;
        }
        .story-progress .bar.active::after {
            content: '';
            display: block;
            height: 100%;
            width: 100%;
            background: white;
            border-radius: 3px;
            animation: progressFill 5s linear infinite;
        }
        @keyframes progressFill {
            from { width: 0%; }
            to   { width: 100%; }
        }
        
        .story-header {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            flex-shrink: 0;
        }
        .story-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            padding: 2px;
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
            flex-shrink: 0;
        }
        .story-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #1a1a1a;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 14px;
            color: white;
        }
        .story-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .story-username {
            font-size: 13px;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .story-time {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            flex-shrink: 0;
        }
        .story-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }
        
        .story-content {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 10px 20px 20px;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .story-content::-webkit-scrollbar { display: none; }
        
        .story-sticker {
            position: absolute;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) rotate(-3deg);
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            padding: 6px 16px;
            border-radius: 24px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            box-shadow: 0 8px 20px rgba(120, 115, 245, 0.4);
            z-index: 20;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .story-card {
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 20px 18px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            text-align: center;
            width: 100%;
        }
        
        .story-label {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .story-guest-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            font-style: italic;
            line-height: 1.1;
            margin-bottom: 12px;
            color: white;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }
        
        .story-divider {
            width: 40px;
            height: 1px;
            background: rgba(255,255,255,0.4);
            margin: 0 auto 12px;
        }
        
        .story-host-name {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .story-event-type {
            font-size: 9px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
        }
        
        .story-table {
            margin-top: 12px;
            padding: 8px 14px;
            background: linear-gradient(135deg, rgba(255,110,199,0.25), rgba(120,115,245,0.25));
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 14px;
            display: inline-block;
        }
        
        .story-table .label {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.7);
            font-weight: 700;
            margin-bottom: 2px;
        }
        
        .story-table .value {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
        }
        
        .story-info-bar {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        .story-info-bar .item {
            text-align: center;
            flex: 1;
        }
        .story-info-bar .label {
            font-size: 7px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            margin-bottom: 3px;
        }
        .story-info-bar .value {
            font-size: 10px;
            font-weight: 700;
            color: white;
        }
        
        /* QR CODE */
        .story-qr-section {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed rgba(255,255,255,0.2);
        }
        
        .story-qr-label {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .story-qr-box {
            display: inline-block;
            padding: 6px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            min-width: 122px;
            min-height: 122px;
            line-height: 0;
        }
        
        .story-qr-box img {
            display: block !important;
            width: 110px !important;
            height: 110px !important;
            margin: 0 auto;
        }
        
        .story-qr-code {
            font-size: 8px;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.6);
            margin-top: 8px;
            font-weight: 600;
        }
        
        .story-swipe {
            position: relative;
            z-index: 10;
            text-align: center;
            padding-bottom: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.1em;
            animation: swipeUp 2s ease-in-out infinite;
            flex-shrink: 0;
        }
        .story-swipe i {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
        }
        @keyframes swipeUp {
            0%, 100% { transform: translateY(0); opacity: 0.7; }
            50% { transform: translateY(-6px); opacity: 1; }
        }
        
        .story-cta-below {
            max-width: 420px;
            margin: 20px auto 0;
            text-align: center;
            padding: 0 20px;
        }
        
        .story-cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            box-shadow: 0 12px 32px rgba(120, 115, 245, 0.5);
            border: 2px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        .story-cta-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 16px 40px rgba(120, 115, 245, 0.7);
            color: white;
        }
        
        .content-below {
            max-width: 420px;
            margin: 0 auto;
            padding: 30px 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }
        .info-card h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-card h3 i {
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 20px;
        }
        
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 11px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 700;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #7873f5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(120, 115, 245, 0.2);
        }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option-radio { display: none; }
        .option-radio-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            background: rgba(0,0,0,0.4);
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-radio:checked + .option-radio-label {
            background: linear-gradient(135deg, rgba(255,110,199,0.2), rgba(120,115,245,0.2));
            border-color: #7873f5;
            color: white;
        }
        
        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 12px 28px rgba(120, 115, 245, 0.4);
        }
        
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(0,0,0,0.4);
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 700;
            color: rgba(255,255,255,0.7);
        }
        .boisson-item.selected {
            background: linear-gradient(135deg, rgba(255,110,199,0.25), rgba(120,115,245,0.25));
            border-color: #7873f5;
            color: white;
        }
        .boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .boisson-item.selected .check { opacity: 1; }
        .boisson-category { margin-bottom: 16px; }
        .boisson-category-title {
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #ff6ec7;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .alert-custom {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
        .alert-danger  { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }
        .alert-warning { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #fcd34d; }
        
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 22px;
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            border: none;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(120, 115, 245, 0.5);
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.05); }
        
        .diaporama {
            width: 100%;
            aspect-ratio: 4/5;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            background: #000;
            border: 1px solid rgba(255,255,255,0.15);
        }
        
        .diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .diaporama .slide.active { opacity: 1; }
        
        .diaporama .slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
        
        .diapo-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .diapo-arrow.prev { left: 12px; }
        .diapo-arrow.next { right: 12px; }
        
        .diapo-counter {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 6px 12px;
            border-radius: 999px;
            z-index: 10;
        }
    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- STORY AVEC QR INTÉGRÉ EN BASE64              -->
    <!-- ============================================ -->
    <div class="story-container" id="invitation-card">
        
        <div class="story-bg" style="<?php if (!empty($pageBackground)): ?>background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');<?php else: ?>background: linear-gradient(135deg, #1a0033 0%, #000 50%, #33001a 100%);<?php endif; ?>"></div>
        
        <div class="story-progress">
            <div class="bar active"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        
        <div class="story-header">
            <div class="story-avatar">
                <div class="story-avatar-inner">
                    <?php echo htmlspecialchars(mb_substr($host1, 0, 1)); ?>
                </div>
            </div>
            <div class="story-info">
                <div class="story-username"><?php echo htmlspecialchars($host1); ?></div>
                <div class="story-time">Il y a 5 min</div>
            </div>
            <button class="story-close">×</button>
        </div>
        
        <div class="story-sticker">
            ✨ Invitation exclusive
        </div>
        
        <div class="story-content">
            
            <div class="story-card">
                
                <div class="story-label">À l'attention de</div>
                <div class="story-guest-name"><?php echo htmlspecialchars($guestName); ?></div>
                
                <div class="story-divider"></div>
                
                <div style="font-size:10px;color:rgba(255,255,255,0.7);letter-spacing:0.15em;text-transform:uppercase;margin-bottom:8px;">
                    Vous êtes invité par
                </div>
                
                <div class="story-host-name"><?php echo htmlspecialchars($host1); ?></div>
                <div class="story-event-type"><?php echo htmlspecialchars(strtoupper($eventType)); ?></div>
                
                <?php if ($hasTable): ?>
                <div class="story-table">
                    <div class="label">Votre table</div>
                    <div class="value"><?php echo htmlspecialchars($tableNom ?: 'Table ' . $tableNumero); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="story-info-bar">
                    <div class="item">
                        <div class="label">Date</div>
                        <div class="value"><?php echo htmlspecialchars($eventDate); ?></div>
                    </div>
                    <div class="item">
                        <div class="label">Heure</div>
                        <div class="value"><?php echo htmlspecialchars($eventTime ?: '--:--'); ?></div>
                    </div>
                    <div class="item">
                        <div class="label">Lieu</div>
                        <div class="value" style="font-size:9px;"><?php echo htmlspecialchars(mb_substr($lieuDisplay, 0, 10)); ?></div>
                    </div>
                </div>
                
                <!-- ✅ QR CODE EN BASE64 - CAPTURE GARANTIE -->
                <div class="story-qr-section">
                    <div class="story-qr-label">Scannez pour confirmer</div>
                    <div class="story-qr-box">
                        <img 
                            id="qrImage"
                            src="<?php echo $qrDataUri; ?>"
                            alt="QR Code"
                            width="110"
                            height="110"
                        >
                    </div>
                    <div class="story-qr-code"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                </div>
                
            </div>
            
        </div>
        
        <div class="story-swipe">
            <i class="fas fa-chevron-up"></i>
            Glisser vers le haut
        </div>
        
    </div>

    <!-- BOUTON RÉPONDRE SOUS LA STORY -->
    <div class="story-cta-below">
        <a href="#section-confirmation" class="story-cta-btn">
            <i class="fas fa-check-circle"></i>
            Répondre à l'invitation
        </a>
    </div>

    <!-- CONTENU SOUS LA STORY -->
    <div class="content-below">
        
        <?php if ($message): ?>
            <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- DIAPORAMA PHOTOS -->
        <?php if (!empty($photosHost)): ?>
            <div class="info-card">
                <h3><i class="fas fa-images"></i> Souvenirs</h3>
                
                <div class="diaporama" id="diaporama">
                    <?php 
                    $photoIndex = 0;
                    foreach ($photosHost as $index => $photo): 
                    ?>
                        <div class="slide <?php echo $photoIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $photoIndex; ?>">
                            <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['titre'] ?? 'Souvenir ' . ($index + 1)); ?>"
                                 loading="<?php echo $photoIndex === 0 ? 'eager' : 'lazy'; ?>">
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
                    <?php endif; ?>
                </div>
                
                <div style="display:flex;justify-content:center;gap:8px;margin-top:12px;" id="diapoIndicators">
                    <?php for ($i = 0; $i < $photoIndex; $i++): ?>
                        <span data-index="<?php echo $i; ?>" 
                              class="<?php echo $i === 0 ? 'active' : ''; ?>" 
                              onclick="goToDiapo(<?php echo $i; ?>)"
                              style="width:8px;height:8px;border-radius:50%;background:<?php echo $i === 0 ? '#7873f5' : 'rgba(255,255,255,0.2)'; ?>;cursor:pointer;"></span>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Confirmation -->
        <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
            <div class="info-card" id="section-confirmation">
                <h3><i class="fas fa-check-circle"></i> Confirmer</h3>
                
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
                                    <i class="fas fa-check"></i> Oui
                                </label>
                            </div>
                            <div>
                                <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="option-radio">
                                <label for="presenceNon" class="option-radio-label">
                                    <i class="fas fa-times"></i> Non
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Message (optionnel)</label>
                        <textarea name="message_invite" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Boissons -->
        <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
            <div class="info-card">
                <h3><i class="fas fa-glass-cheers"></i> Boissons</h3>
                
                <?php if ($isLocked): ?>
                    <div style="text-align:center;color:#6ee7b7;font-size:12px;font-weight:700;">
                        <i class="fas fa-lock"></i> Préférences enregistrées
                    </div>
                <?php else: ?>
                    <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                        <input type="hidden" name="action" value="preferences">
                        
                        <p style="font-size:12px;color:rgba(255,255,255,0.6);text-align:center;margin-bottom:16px;">
                            Choisissez <strong style="color:#ff6ec7;">2 boissons</strong> : <span id="selectedCount">0</span>/2
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
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="info-card" style="text-align:center;">
            <div style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:white;margin-bottom:6px;">
                <?php echo htmlspecialchars($appName); ?>
            </div>
            <div style="font-size:10px;letter-spacing:0.25em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:16px;">
                Créateur d'émotions
            </div>
            
            <a href="https://wa.me/243829018462" target="_blank" 
               style="display:inline-flex;align-items:center;gap:8px;color:#25d366;text-decoration:none;font-size:13px;font-weight:700;">
                <i class="fab fa-whatsapp" style="font-size:16px;"></i>
                Nous contacter
            </a>
        </div>
        
    </div>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Story</span>
    </button>

    <script>
        // ================================================================
        // DIAPORAMA
        // ================================================================
        let diapoIndex = 0;
        const slides = document.querySelectorAll('#diaporama .slide');
        const indicators = document.querySelectorAll('#diapoIndicators span');
        const diapoCounter = document.getElementById('diapoCounter');
        let diapoInterval = null;

        function updateDiapo() {
            slides.forEach((s, i) => s.classList.toggle('active', i === diapoIndex));
            indicators.forEach((ind, i) => {
                ind.classList.toggle('active', i === diapoIndex);
                ind.style.background = i === diapoIndex ? '#7873f5' : 'rgba(255,255,255,0.2)';
            });
            if (diapoCounter) {
                diapoCounter.textContent = (diapoIndex + 1) + ' / ' + slides.length;
            }
        }

        function diapoChange(direction) {
            diapoIndex += direction;
            if (diapoIndex < 0) diapoIndex = slides.length - 1;
            if (diapoIndex >= slides.length) diapoIndex = 0;
            updateDiapo();
            resetDiapoAuto();
        }

        function goToDiapo(index) { 
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
            }
        });

        // ================================================================
        // TÉLÉCHARGEMENT FIX QR BASE64
        // ================================================================
        async function telechargerJPEG() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const card = document.getElementById('invitation-card');
            
            if (!card) return;
            
            btn.disabled = true;
            btnText.textContent = 'Génération...';
            
            try {
                await new Promise(r => setTimeout(r, 600));
                
                const canvas = await html2canvas(card, {
                    scale: 3,
                    useCORS: false,
                    allowTaint: false,
                    backgroundColor: '#000000',
                    logging: false,
                    imageTimeout: 0,
                    foreignObjectRendering: false,
                    onclone: function(clonedDoc) {
                        clonedDoc.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                            el.style.transition = 'none';
                        });
                        // S'assurer que le QR reste visible dans le clone
                        const qrBox = clonedDoc.querySelector('.story-qr-box');
                        if (qrBox) {
                            qrBox.style.display = 'inline-block';
                            qrBox.style.background = 'white';
                            qrBox.style.padding = '6px';
                            qrBox.style.visibility = 'visible';
                            qrBox.style.opacity = '1';
                        }
                        const qrImg = clonedDoc.getElementById('qrImage');
                        if (qrImg) {
                            qrImg.style.display = 'block';
                            qrImg.style.width = '110px';
                            qrImg.style.height = '110px';
                            qrImg.style.visibility = 'visible';
                            qrImg.style.opacity = '1';
                        }
                    }
                });
                
                const link = document.createElement('a');
                link.download = `story_<?php echo htmlspecialchars($host1); ?>.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                
                btnText.textContent = '✓ Téléchargé';
                setTimeout(() => btnText.textContent = 'Story', 3000);
            } catch(e) {
                console.error(e);
                alert('Erreur capture: ' + e.message);
                btnText.textContent = 'Erreur';
                setTimeout(() => btnText.textContent = 'Story', 3000);
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
            const el = document.getElementById('selectedCount');
            if(el) el.textContent = selectedBoissons.length;
        }
        <?php endif; ?>
    </script>

</body>
</html>
