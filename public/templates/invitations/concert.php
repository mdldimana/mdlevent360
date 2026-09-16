<?php
/**
 * ============================================================
 * TEMPLATE : TICKET
 * ============================================================
 * 
 * Format ticket de concert/cinéma avec perforations,
 * code-barres, style déchirable. Structure radicalement différente.
 * 
 * ============================================================
 */
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
        
        body {
            font-family: 'Inter', sans-serif;
            background: #1a1a1a;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(255, 107, 53, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(255, 193, 7, 0.05) 0%, transparent 40%);
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 16px;
            -webkit-font-smoothing: antialiased;
        }
        
        .app-wrapper {
            max-width: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 60px;
        }
        
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
        
        /* Perforations décoratives sur les côtés */
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
        
        /* Découpe pointillée horizontale au milieu */
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
           EN-TÊTE DU TICKET (fond coloré)
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
           CORPS DU TICKET (fond blanc)
           ============================================ */
        .ticket-body {
            padding: 24px 24px 20px;
        }
        
        /* Invité - style "nom sur billet" */
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
        
        /* Grille infos en 2 colonnes */
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
        
        /* ============================================
           SECTION DÉTACHABLE (basse du ticket)
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
        
        /* QR code dans le stub */
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
        
        /* ============================================
           STATUT RSVP (badge en coin)
           ============================================ */
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
        
        /* Photos */
        .diaporama {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            background: #1a1a1a;
        }
        .diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .diaporama .slide.active { opacity: 1; }
        .diaporama .slide img { width: 100%; height: 100%; object-fit: cover; }
        
        .diapo-nav {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
        }
        .diapo-nav span {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .diapo-nav span.active { background: #ff6b35; transform: scale(1.3); }
        
        /* QR Section */
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
        <div class="ticket section-animee delai-1" id="invitation-card">
            
            <!-- En-tête coloré -->
            <div class="ticket-header">
                
                <!-- Statut RSVP -->
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
                
                <!-- Invité -->
                <div class="ticket-guest">
                    <div class="label">Titulaire</div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($guestName)); ?></div>
                </div>
                
                <!-- Infos -->
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
                
                <!-- Description -->
                <?php if (!empty($eventDescription)): ?>
                    <div style="font-family:'Inter',sans-serif;font-size:13px;line-height:1.6;color:#666;padding:12px 0;border-top:1px solid #eee;">
                        <?php echo nl2br(htmlspecialchars(mb_substr($eventDescription, 0, 200))); ?>
                        <?php if (mb_strlen($eventDescription) > 200) echo '...'; ?>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Découpe pointillée -->
            <div class="ticket-cut"></div>
            
            <!-- Souche détachable -->
            <div class="ticket-stub">
                
                <!-- Code-barres -->
                <div class="ticket-barcode">
                    <div class="bars"></div>
                    <div class="code"><?php echo htmlspecialchars($invitation['code_unique']); ?></div>
                </div>
                
                <!-- QR + info -->
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
        <!-- DIAPORAMA PHOTOS                           -->
        <!-- ========================================== -->
        <?php if (!empty($photosHost)): ?>
            <div class="section section-animee delai-2">
                <div class="section-title">📸 Souvenirs</div>
                <div class="diaporama" id="diaporama">
                    <?php foreach ($photosHost as $index => $photo): ?>
                        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" 
                                 alt="" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="diapo-nav" id="diapoIndicators">
                    <?php foreach ($photosHost as $index => $photo): ?>
                        <span data-index="<?php echo $index; ?>" 
                              class="<?php echo $index === 0 ? 'active' : ''; ?>" 
                              onclick="goToDiapo(<?php echo $index; ?>)"></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MESSAGES                                   -->
        <!-- ========================================== -->
        <?php if ($message): ?>
            <div class="section section-animee delai-2">
                <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- QR CODE                                    -->
        <!-- ========================================== -->
        <div class="section section-animee delai-3">
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
            <div class="section section-animee delai-4">
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
            <div class="section section-animee delai-4">
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
        <div class="section section-animee delai-5" style="text-align:center;">
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
        // Animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section-animee');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { 
                    if (entry.isIntersecting) entry.target.classList.add('apparue'); 
                });
            }, { threshold: 0.15 });
            sections.forEach(s => observer.observe(s));
            setTimeout(() => sections.forEach(s => {
                if (s.getBoundingClientRect().top < window.innerHeight * 0.85) s.classList.add('apparue');
            }), 300);
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

        // Diaporama
        let diapoIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('#diapoIndicators span');

        function updateDiapo() {
            slides.forEach((s, i) => s.classList.toggle('active', i === diapoIndex));
            indicators.forEach((ind, i) => ind.classList.toggle('active', i === diapoIndex));
        }
        function goToDiapo(index) { diapoIndex = index; updateDiapo(); }
        function changerDiapo(dir) {
            const n = diapoIndex + dir;
            if (n < 0 || n >= slides.length) return;
            diapoIndex = n; updateDiapo();
        }
        if (slides.length > 1) {
            setInterval(() => { diapoIndex = (diapoIndex + 1) % slides.length; updateDiapo(); }, 5000);
        }

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