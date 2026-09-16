<?php
/**
 * ============================================================
 * TEMPLATE : POLAROID
 * ============================================================
 * 
 * Design scrapbooking avec photos polaroïd inclinées,
 * trombones, écriture manuscrite, fond papier craft.
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
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Crimson+Text:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Crimson Text', Georgia, serif;
            background: #d4c5b0;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(139, 105, 20, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(193, 124, 96, 0.08) 0%, transparent 50%),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><filter id="n"><feTurbulence baseFrequency="0.9" numOctaves="3"/></filter><rect width="100" height="100" filter="url(%23n)" opacity="0.04"/></svg>');
            color: #3a3028;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 16px;
            gap: 40px;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           POLAROID COMPOSITION
           ============================================ */
        .polaroid-board {
            position: relative;
            width: 100%;
            max-width: 600px;
            min-height: 800px;
            padding: 40px 20px;
            background: 
                linear-gradient(135deg, #f5efe2 0%, #ebe0cc 100%);
            border-radius: 4px;
            box-shadow: 
                0 40px 100px rgba(0,0,0,0.3),
                0 0 0 1px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        /* Texture papier déchiré en haut */
        .polaroid-board::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0; right: 0;
            height: 20px;
            background: 
                radial-gradient(ellipse at center, rgba(0,0,0,0.05) 0%, transparent 70%);
        }
        
        /* Washi tape décorative */
        .washi-tape {
            position: absolute;
            width: 100px;
            height: 30px;
            background: 
                repeating-linear-gradient(
                    45deg,
                    rgba(193, 124, 96, 0.6) 0px, rgba(193, 124, 96, 0.6) 8px,
                    rgba(193, 124, 96, 0.4) 8px, rgba(193, 124, 96, 0.4) 16px
                );
            opacity: 0.85;
            z-index: 10;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .washi-tape.tl { top: 20px; left: 30px; transform: rotate(-5deg); }
        .washi-tape.tr { top: 40px; right: 20px; transform: rotate(8deg); }
        
        /* Trombone décoratif */
        .paperclip {
            position: absolute;
            width: 20px;
            height: 50px;
            border: 3px solid #b89968;
            border-radius: 10px;
            border-left-color: transparent;
            z-index: 15;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .paperclip.top { top: 10px; left: 50%; transform: translateX(-50%); }
        
        /* ============================================
           POLAROIDS
           ============================================ */
        .polaroid {
            position: relative;
            background: white;
            padding: 14px 14px 60px;
            box-shadow: 
                0 10px 30px rgba(0,0,0,0.2),
                0 2px 6px rgba(0,0,0,0.1);
            width: 280px;
            margin: 0 auto 30px;
            transition: all 0.3s ease;
        }
        .polaroid img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            display: block;
            filter: sepia(0.15) contrast(1.05);
        }
        .polaroid .caption {
            position: absolute;
            bottom: 10px;
            left: 0; right: 0;
            text-align: center;
            font-family: 'Caveat', cursive;
            font-size: 22px;
            color: #3a3028;
            font-weight: 700;
            transform: rotate(-1deg);
        }
        .polaroid .placeholder-photo {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, #c17c60 0%, #8b6914 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.4);
            font-size: 60px;
        }
        
        /* Rotation individuelle pour effet naturel */
        .polaroid-1 { transform: rotate(-3deg); }
        .polaroid-2 { transform: rotate(2.5deg); margin-top: -30px; }
        .polaroid-3 { transform: rotate(-1.5deg); margin-top: -30px; }
        
        /* ============================================
           TEXTE PRINCIPAL
           ============================================ */
        .main-message {
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Caveat', cursive;
            font-size: 28px;
            color: #3a3028;
            line-height: 1.3;
            position: relative;
        }
        .main-message::before,
        .main-message::after {
            content: '"';
            font-family: 'Crimson Text', serif;
            font-size: 60px;
            color: #c17c60;
            opacity: 0.3;
            position: absolute;
            line-height: 1;
        }
        .main-message::before { top: -10px; left: 10px; }
        .main-message::after  { bottom: -40px; right: 10px; }
        
        .guest-name-polaroid {
            font-family: 'Caveat', cursive;
            font-size: 42px;
            color: #c17c60;
            font-weight: 700;
            text-align: center;
            margin: 10px 0 20px;
            text-decoration: underline;
            text-decoration-style: wavy;
            text-decoration-color: rgba(193, 124, 96, 0.4);
            text-decoration-thickness: 2px;
            text-underline-offset: 6px;
        }
        
        /* ============================================
           INFOS ÉVÉNEMENT
           ============================================ */
        .event-info-scrapbook {
            background: rgba(255,255,255,0.6);
            padding: 20px;
            margin: 20px 10px;
            border: 2px dashed rgba(139, 105, 20, 0.3);
            border-radius: 4px;
            transform: rotate(0.5deg);
            position: relative;
        }
        .event-info-scrapbook::before {
            content: '★';
            position: absolute;
            top: -12px;
            left: 20px;
            background: #ebe0cc;
            padding: 0 8px;
            color: #c17c60;
            font-size: 16px;
        }
        
        .event-info-scrapbook .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-family: 'Crimson Text', serif;
            font-size: 16px;
            color: #3a3028;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        .event-info-scrapbook .info-row:last-child { border-bottom: none; }
        .event-info-scrapbook .info-row i {
            width: 20px;
            color: #c17c60;
            font-size: 15px;
        }
        .event-info-scrapbook .info-row .label {
            font-weight: 700;
            min-width: 70px;
            font-size: 14px;
        }
        
        /* ============================================
           SECTIONS COMMUNES
           ============================================ */
        .section {
            width: 100%;
            max-width: 600px;
            background: #f5efe2;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            position: relative;
        }
        
        .section-title {
            font-family: 'Caveat', cursive;
            font-size: 30px;
            color: #c17c60;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        /* Formulaire */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-family: 'Caveat', cursive;
            font-size: 20px;
            color: #8b6914;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 4px;
            font-family: 'Crimson Text', serif;
            font-size: 15px;
            color: #3a3028;
            background: rgba(255,255,255,0.6);
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #c17c60;
            outline: none;
            background: white;
        }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option-radio { display: none; }
        .option-radio-label {
            padding: 14px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 4px;
            background: rgba(255,255,255,0.6);
            font-family: 'Caveat', cursive;
            font-size: 20px;
            font-weight: 700;
            color: #6a5a4a;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        .option-radio:checked + .option-radio-label {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.12);
            color: #c17c60;
        }
        
        .btn-confirm {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c17c60 0%, #8b6914 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-family: 'Caveat', cursive;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 105, 20, 0.3);
        }
        .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(139, 105, 20, 0.4); }
        
        .btn-whatsapp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: #25d366;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            margin-top: 10px;
        }
        
        /* QR */
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        #qrcode, #card-qrcode {
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 2px solid rgba(139, 105, 20, 0.2);
        }
        
        /* Boissons */
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 999px;
            background: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Caveat', cursive;
            font-size: 16px;
            font-weight: 700;
            color: #6a5a4a;
        }
        .boisson-item.selected {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.12);
            color: #c17c60;
        }
        .boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .boisson-item.selected .check { opacity: 1; }
        .boisson-category { margin-bottom: 16px; }
        .boisson-category-title {
            font-family: 'Caveat', cursive;
            font-size: 20px;
            font-weight: 700;
            color: #c17c60;
            margin-bottom: 8px;
        }
        
        /* Alert */
        .alert-custom {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .alert-success { background: rgba(45,122,69,0.1); color: #2d7a45; border: 1px solid rgba(45,122,69,0.2); }
        .alert-danger  { background: rgba(178,7,16,0.1);  color: #b20710; border: 1px solid rgba(178,7,16,0.2); }
        .alert-warning { background: rgba(139,105,20,0.1); color: #8b6914; border: 1px solid rgba(139,105,20,0.2); }
        
        /* Download */
        #downloadBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #c17c60 0%, #8b6914 100%);
            color: white;
            border: none;
            border-radius: 999px;
            font-family: 'Caveat', cursive;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(139, 105, 20, 0.5);
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.05); }
        
        @media (max-width: 480px) {
            body { padding: 20px 12px; gap: 20px; }
            .polaroid-board { padding: 30px 12px; }
            .polaroid { width: 240px; }
            .polaroid img, .polaroid .placeholder-photo { height: 200px; }
            .polaroid .caption { font-size: 18px; }
            .guest-name-polaroid { font-size: 34px; }
            .main-message { font-size: 22px; }
            .section { padding: 20px; }
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 16px; }
        }
    </style>
</head>
<body>

    <!-- ========================================== -->
    <!-- POLAROID BOARD                              -->
    <!-- ========================================== -->
    <div class="polaroid-board" id="invitation-card">
        
        <!-- Washi tapes -->
        <div class="washi-tape tl"></div>
        <div class="washi-tape tr"></div>
        
        <!-- Trombone -->
        <div class="paperclip top"></div>
        
        <!-- Polaroid #1 (photo principale) -->
        <div class="polaroid polaroid-1">
            <?php if (!empty($pageBackground)): ?>
                <img src="<?php echo htmlspecialchars($pageBackground); ?>" alt="">
            <?php elseif (!empty($photosHost)): ?>
                <img src="<?php echo htmlspecialchars(getPhotoUrl($photosHost[0]['photo'])); ?>" alt="">
            <?php else: ?>
                <div class="placeholder-photo"><i class="fas fa-camera-retro"></i></div>
            <?php endif; ?>
            <div class="caption"><?php echo htmlspecialchars($host1); ?></div>
        </div>
        
        <!-- Message principal -->
        <div class="guest-name-polaroid">
            <?php echo htmlspecialchars($guestName); ?>
        </div>
        
        <div class="main-message">
            Nous sommes heureux de vous convier à célébrer notre <?php echo htmlspecialchars($eventType); ?>
        </div>
        
        <!-- Polaroid #2 (seulement si photos dispo) -->
        <?php if (!empty($photosHost) && count($photosHost) > 1): ?>
            <div class="polaroid polaroid-2">
                <img src="<?php echo htmlspecialchars(getPhotoUrl($photosHost[1]['photo'])); ?>" alt="">
                <div class="caption">Souvenirs</div>
            </div>
        <?php endif; ?>
        
        <!-- Infos scrapbook -->
        <div class="event-info-scrapbook">
            <div class="info-row">
                <i class="fas fa-calendar-alt"></i>
                <span class="label">Date :</span>
                <span><?php echo htmlspecialchars($eventDate); ?></span>
            </div>
            <?php if ($eventTime): ?>
                <div class="info-row">
                    <i class="fas fa-clock"></i>
                    <span class="label">Heure :</span>
                    <span><?php echo htmlspecialchars($eventTime); ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <i class="fas fa-map-marker-alt"></i>
                <span class="label">Lieu :</span>
                <span><?php echo htmlspecialchars($lieuDisplay); ?></span>
            </div>
            <?php if ($adresseDisplay): ?>
                <div class="info-row">
                    <i class="fas fa-location-arrow"></i>
                    <span class="label">Adresse :</span>
                    <span><?php echo htmlspecialchars($adresseDisplay); ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <i class="fas fa-user-friends"></i>
                <span class="label">Places :</span>
                <span><?php echo (int)($invitation['nb_places_max'] ?? 1); ?> personne(s)</span>
            </div>
        </div>
        
        <!-- Statut -->
        <div style="text-align:center;font-family:'Caveat',cursive;font-size:22px;color:<?php 
            echo $invitation['statut'] == 'CONFIRMEE' ? '#2d7a45' : 
                ($invitation['statut'] == 'REFUSEE' ? '#b20710' : '#8b6914'); 
        ?>;font-weight:700;margin:20px 0;transform:rotate(-2deg);">
            <?php 
            if ($invitation['statut'] == 'CONFIRMEE') echo '✓ Confirmé(e)';
            elseif ($invitation['statut'] == 'REFUSEE') echo '✗ Refusé(e)';
            else echo '⏳ En attente de réponse';
            ?>
        </div>
        
        <!-- QR code en bas -->
        <div style="display:flex;justify-content:center;margin-top:20px;">
            <div style="padding:10px;background:white;border-radius:4px;border:2px solid rgba(139,105,20,0.2);transform:rotate(3deg);box-shadow:0 8px 20px rgba(0,0,0,0.15);">
                <div id="card-qrcode"></div>
            </div>
        </div>
        
    </div>

    <!-- ========================================== -->
    <!-- SECTIONS SOUS LE BOARD                      -->
    <!-- ========================================== -->
    
    <?php if ($message): ?>
        <div class="section">
            <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- QR code -->
    <div class="section">
        <div class="section-title">Code d'accès</div>
        <div class="qr-wrapper">
            <div id="qrcode"></div>
            <div style="font-family:'Caveat',cursive;font-size:20px;color:#8b6914;">
                <?php echo htmlspecialchars($invitation['code_unique']); ?>
            </div>
        </div>
    </div>
    
    <!-- Confirmation -->
    <?php if ($invitation['statut'] == 'EN_ATTENTE'): ?>
        <div class="section">
            <div class="section-title">Répondre à l'invitation</div>
            
            <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=confirmer">
                <input type="hidden" name="action" value="confirmer">
                
                <div class="form-group">
                    <label>Nombre de personnes :</label>
                    <input type="number" name="nombre_personnes" value="1" min="1" max="<?php echo $invitation['nb_places_max'] ?? 1; ?>">
                </div>
                
                <div class="form-group">
                    <label>Votre réponse :</label>
                    <div class="options-grid">
                        <div>
                            <input type="radio" name="reponse" id="presenceOui" value="CONFIRMEE" checked class="option-radio">
                            <label for="presenceOui" class="option-radio-label">✓ Je viendrai</label>
                        </div>
                        <div>
                            <input type="radio" name="reponse" id="presenceNon" value="REFUSEE" class="option-radio">
                            <label for="presenceNon" class="option-radio-label">✗ Je ne peux pas</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Un petit mot :</label>
                    <textarea name="message_invite" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn-confirm">
                    <i class="fas fa-paper-plane"></i> Envoyer ma réponse
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Boissons -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="section">
            <div class="section-title">🥂 Vos boissons préférées</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:#2d7a45;font-family:'Caveat',cursive;font-size:20px;font-weight:700;">
                    <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Caveat',cursive;font-size:20px;color:#8b6914;margin-bottom:16px;">
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
    
    <!-- Footer -->
    <div class="section" style="text-align:center;">
        <div style="font-family:'Caveat',cursive;font-size:32px;font-weight:700;color:#c17c60;margin-bottom:6px;">
            <?php echo htmlspecialchars($appName); ?>
        </div>
        <div style="font-family:'Crimson Text',serif;font-style:italic;font-size:16px;color:#6a5a4a;margin-bottom:16px;">
            Chaque événement mérite d'être immortalisé
        </div>
        
        <a href="https://wa.me/243829018462" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(139,105,20,0.15);font-family:'Caveat',cursive;font-size:16px;color:#8a7a6a;">
            © <?php echo date('Y'); ?>
        </div>
    </div>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Polaroïd</span>
    </button>

    <script>
        // QR
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(document.getElementById('card-qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 100, height: 100,
                        colorDark: '#3a3028', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    new QRCode(document.getElementById('qrcode'), {
                        text: '<?php echo addslashes($fullUrl); ?>',
                        width: 160, height: 160,
                        colorDark: '#3a3028', colorLight: '#ffffff',
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
                    backgroundColor: '#f5efe2', logging: false
                });
                const link = document.createElement('a');
                link.download = `polaroid_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                btnText.textContent = '✓ Téléchargé';
                setTimeout(() => btnText.textContent = 'Polaroïd', 3000);
            } catch(e) {
                btnText.textContent = 'Erreur';
                setTimeout(() => btnText.textContent = 'Polaroïd', 3000);
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