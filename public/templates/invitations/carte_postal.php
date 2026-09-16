<?php
/**
 * ============================================================
 * TEMPLATE : CARTE POSTALE
 * ============================================================
 * 
 * Format horizontal, layout 2 colonnes, style carte postale
 * de voyage avec timbre, tampon postal, ligne de séparation.
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Caveat:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #2a2420;
            background-image: 
                radial-gradient(circle at 30% 20%, rgba(212, 165, 116, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(193, 124, 96, 0.08) 0%, transparent 50%);
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
           FORMAT CARTE POSTALE HORIZONTALE
           ============================================ */
        .postcard {
            position: relative;
            width: 100%;
            max-width: 800px;
            background: #fdf6ec;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 
                0 30px 80px rgba(0,0,0,0.5),
                0 0 0 1px rgba(0,0,0,0.1);
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
            background: linear-gradient(135deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.5) 100%);
        }
        
        .postcard-visual-content {
            position: relative;
            z-index: 2;
            height: 100%;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }
        
        .postcard-stamp {
            width: 60px;
            height: 75px;
            background: linear-gradient(135deg, #e8c5a0, #c17c60);
            border: 2px dashed rgba(255,255,255,0.6);
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: white;
            align-self: flex-end;
            transform: rotate(5deg);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .postcard-visual-title {
            text-align: left;
        }
        .postcard-visual-title .greeting {
            font-family: 'Caveat', cursive;
            font-size: 32px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 4px;
        }
        .postcard-visual-title .host {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        .postcard-visual-title .type {
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            opacity: 0.8;
            margin-top: 8px;
            font-weight: 600;
        }
        
        /* Colonne droite - adresse/style courrier */
        .postcard-address {
            position: relative;
            padding: 24px;
            background: #fdf6ec;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Lignes de courrier (comme sur une vraie carte postale) */
        .postcard-address::before {
            content: '';
            position: absolute;
            top: 24px; left: 24px; right: 24px;
            height: 60%;
            background-image: repeating-linear-gradient(
                transparent 0px, transparent 22px,
                rgba(0,0,0,0.08) 22px, rgba(0,0,0,0.08) 23px
            );
            pointer-events: none;
        }
        
        /* Tampon postal */
        .postcard-postmark {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            border: 2px solid rgba(139, 69, 19, 0.4);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transform: rotate(-15deg);
            font-family: 'Playfair Display', serif;
            font-size: 9px;
            color: rgba(139, 69, 19, 0.6);
            text-align: center;
            line-height: 1.2;
            letter-spacing: 0.05em;
            z-index: 3;
        }
        .postcard-postmark .city {
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 0.1em;
        }
        .postcard-postmark .date {
            font-size: 8px;
            margin-top: 2px;
        }
        
        .postcard-address-content {
            position: relative;
            z-index: 2;
            padding-top: 20px;
        }
        
        .postcard-address-content .to-label {
            font-family: 'Caveat', cursive;
            font-size: 18px;
            color: #8b6914;
            margin-bottom: 8px;
        }
        
        .postcard-address-content .guest-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 900;
            font-style: italic;
            color: #3a3028;
            line-height: 1.1;
            margin-bottom: 16px;
        }
        
        .postcard-address-content .message {
            font-family: 'Caveat', cursive;
            font-size: 16px;
            line-height: 1.6;
            color: #5a4a3a;
            margin-bottom: 16px;
            min-height: 60px;
        }
        
        .postcard-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12px;
            color: #6a5a4a;
            margin-top: auto;
        }
        .postcard-meta .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .postcard-meta .meta-row i {
            width: 16px;
            color: #c17c60;
            font-size: 13px;
        }
        .postcard-meta .meta-row strong {
            font-weight: 700;
            color: #3a3028;
        }
        
        /* QR mini dans un coin */
        .postcard-qr {
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 6px;
            background: white;
            border: 2px solid rgba(139, 69, 19, 0.2);
            border-radius: 4px;
            z-index: 3;
        }
        
        /* Statut RSVP - tampon diagonal */
        .postcard-status {
            position: absolute;
            bottom: 20px;
            left: 20px;
            padding: 6px 14px;
            border: 2px solid;
            border-radius: 4px;
            font-family: 'Playfair Display', serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transform: rotate(-5deg);
            z-index: 3;
        }
        .postcard-status.confirmed { border-color: #2d7a45; color: #2d7a45; background: rgba(45,122,69,0.08); }
        .postcard-status.refused   { border-color: #b20710; color: #b20710; background: rgba(178,7,16,0.08); }
        .postcard-status.pending   { border-color: #8b6914; color: #8b6914; background: rgba(139,105,20,0.08); }
        
        /* ============================================
           SECTIONS SOUS LA CARTE POSTALE
           ============================================ */
        .section {
            width: 100%;
            max-width: 800px;
            background: #fdf6ec;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #3a3028;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: 0.02em;
        }
        
        /* Formulaires */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-family: 'Caveat', cursive;
            font-size: 18px;
            color: #8b6914;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #3a3028;
            background: rgba(255,255,255,0.6);
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #c17c60;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(193, 124, 96, 0.1);
        }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .option-radio { display: none; }
        .option-radio-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 6px;
            background: rgba(255,255,255,0.6);
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 700;
            color: #6a5a4a;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-radio:checked + .option-radio-label {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.1);
            color: #c17c60;
        }
        
        .btn-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            border-radius: 6px;
            background: linear-gradient(135deg, #c17c60 0%, #8b6914 100%);
            color: white;
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(139, 105, 20, 0.3);
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(139, 105, 20, 0.4);
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
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            margin-top: 10px;
        }
        
        /* Photos */
        .diaporama {
            width: 100%;
            aspect-ratio: 16/9;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #3a3028;
        }
        .diaporama .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .diaporama .slide.active { opacity: 1; }
        .diaporama .slide img { width: 100%; height: 100%; object-fit: cover; }
        
        /* QR */
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        #qrcode {
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 2px solid rgba(139, 105, 20, 0.2);
        }
        
        /* Boissons */
        .boisson-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .boisson-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 2px solid rgba(139, 105, 20, 0.2);
            border-radius: 999px;
            background: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: 600;
            color: #6a5a4a;
        }
        .boisson-item.selected {
            border-color: #c17c60;
            background: rgba(193, 124, 96, 0.1);
            color: #c17c60;
        }
        .boisson-item .check { opacity: 0; transition: opacity 0.3s ease; }
        .boisson-item.selected .check { opacity: 1; }
        .boisson-category { margin-bottom: 18px; }
        .boisson-category-title {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            color: #8b6914;
            margin-bottom: 10px;
        }
        
        /* Alert */
        .alert-custom {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 13px;
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
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(139, 105, 20, 0.5);
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        #downloadBtn:hover { transform: translateY(-3px) scale(1.05); }
        
        /* Responsive */
        @media (max-width: 700px) {
            .postcard {
                grid-template-columns: 1fr;
                aspect-ratio: auto;
            }
            .postcard-visual {
                min-height: 250px;
            }
            .postcard-address {
                padding: 20px;
            }
            .postcard-address::before { display: none; }
            body { padding: 20px 12px; }
            #downloadBtn { bottom: 12px; right: 12px; padding: 12px 18px; font-size: 10px; }
        }
    </style>
</head>
<body>

    <!-- ========================================== -->
    <!-- CARTE POSTALE HORIZONTALE                   -->
    <!-- ========================================== -->
    <div class="postcard" id="invitation-card">
        
        <!-- Colonne gauche : visuel -->
        <div class="postcard-visual" 
             style="background-image: url('<?php echo !empty($pageBackground) ? htmlspecialchars($pageBackground) : ''; ?>'), linear-gradient(135deg, #c17c60 0%, #8b6914 100%);">
            
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
            
            <!-- QR code mini en bas à droite -->
            <div class="postcard-qr">
                <div id="card-qrcode"></div>
            </div>
            
        </div>
        
    </div>

    <!-- ========================================== -->
    <!-- SECTIONS SOUS LA CARTE POSTALE              -->
    <!-- ========================================== -->
    
    <?php if ($message): ?>
        <div class="section">
            <div class="alert-custom alert-<?php echo htmlspecialchars($messageType); ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Photos -->
    <?php if (!empty($photosHost)): ?>
        <div class="section">
            <div class="section-title">📷 Souvenirs partagés</div>
            <div class="diaporama" id="diaporama">
                <?php foreach ($photosHost as $index => $photo): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars(getPhotoUrl($photo['photo'])); ?>" alt="" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- QR Code -->
    <div class="section">
        <div class="section-title">Code d'accès</div>
        <div class="qr-wrapper">
            <div id="qrcode"></div>
            <div style="font-size:12px;color:#6a5a4a;letter-spacing:0.15em;font-family:'Playfair Display',serif;">
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
    
    <!-- Boissons -->
    <?php if ($invitation['reponse'] == 'CONFIRMEE' && !empty($boissons)): ?>
        <div class="section">
            <div class="section-title">🥂 Vos boissons préférées</div>
            
            <?php if ($isLocked): ?>
                <div style="text-align:center;color:#2d7a45;font-weight:700;">
                    <i class="fas fa-lock"></i> Vos préférences sont enregistrées
                </div>
            <?php else: ?>
                <form method="POST" action="?code=<?php echo urlencode($code); ?>&action=preferences">
                    <input type="hidden" name="action" value="preferences">
                    
                    <p style="text-align:center;font-family:'Caveat',cursive;font-size:18px;color:#8b6914;margin-bottom:16px;">
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
        <div style="font-family:'Playfair Display',serif;font-size:24px;font-weight:900;color:#8b6914;margin-bottom:6px;">
            <?php echo htmlspecialchars($appName); ?>
        </div>
        <div style="font-family:'Caveat',cursive;font-size:18px;color:#6a5a4a;margin-bottom:16px;">
            Des invitations qui voyagent
        </div>
        
        <a href="https://wa.me/243829018462" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Nous contacter
        </a>
        
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(139,105,20,0.15);font-size:11px;color:#8a7a6a;letter-spacing:0.15em;text-transform:uppercase;">
            © <?php echo date('Y'); ?>
        </div>
    </div>

    <button id="downloadBtn" onclick="telechargerJPEG()">
        <i class="fas fa-download"></i>
        <span id="btnText">Carte</span>
    </button>

    <script>
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
                        width: 160, height: 160,
                        colorDark: '#3a3028', colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch(e) { console.error(e); }
            }
        });

        // Diaporama
        let diapoIndex = 0;
        const slides = document.querySelectorAll('.slide');
        function updateDiapo() {
            slides.forEach((s, i) => s.classList.toggle('active', i === diapoIndex));
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
                    backgroundColor: '#fdf6ec', logging: false
                });
                const link = document.createElement('a');
                link.download = `carte_postale_${'<?php echo htmlspecialchars($host1); ?>'.replace(/\s/g, '_')}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                btnText.textContent = '✓ Téléchargé';
                setTimeout(() => btnText.textContent = 'Carte', 3000);
            } catch(e) {
                btnText.textContent = 'Erreur';
                setTimeout(() => btnText.textContent = 'Carte', 3000);
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