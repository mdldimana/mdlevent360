<?php
// ============================================================
// GUIDE UTILISATEUR - MdlEvent v2
// ============================================================

$appName = 'MdlEvent';
$version = '2.0';
$date = date('d/m/Y');

// Détecter BASE_PATH
if (!defined('BASE_PATH')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*?)/admin/#', $scriptPath, $matches)) {
        define('BASE_PATH', $matches[1]);
    } else {
        define('BASE_PATH', '');
    }
}

if (!function_exists('adminUrl')) {
    function adminUrl(string $path = ''): string {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/admin/' . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide utilisateur - <?php echo $appName; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --orange: #f7971e;
            --orange-light: #ffd200;
            --orange-dark: #d4880f;
            --dark: #1a1a2e;
            --dark-2: #2a2a3e;
            --gray: #666;
            --gray-light: #999;
            --gray-lighter: #e1e5ee;
            --bg: #fdfcfb;
            --white: #ffffff;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #e8e8e8;
            color: var(--dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ============================================
           BOUTONS FIXES
           ============================================ */
        .fixed-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 12px;
            align-items: center;
            z-index: 1000;
        }

        .back-btn {
            padding: 16px 28px;
            background: white;
            color: var(--dark);
            border: 2px solid var(--gray-lighter);
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            border-color: var(--orange);
            color: var(--orange);
            box-shadow: 0 15px 40px rgba(247, 151, 30, 0.25);
        }

        .print-btn {
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(247, 151, 30, 0.5);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .print-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(247, 151, 30, 0.7);
        }
        
        /* ============================================
           PAGE A4
           ============================================ */
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            background: var(--white);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            page-break-after: always;
        }
        
        @media print {
            body { background: white; }
            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
            .no-print { display: none !important; }
        }
        
        /* ============================================
           COUVERTURE
           ============================================ */
        .cover {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #2a2a3e 100%);
            color: white;
            padding: 40mm 20mm;
            position: relative;
            overflow: hidden;
        }
        
        .cover::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(247, 151, 30, 0.3) 0%, transparent 70%);
        }
        
        .cover::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 210, 0, 0.2) 0%, transparent 70%);
        }
        
        .cover-content { position: relative; z-index: 2; }
        
        .cover-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 20px 60px rgba(247, 151, 30, 0.4);
        }
        
        .cover-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 0%, var(--orange-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .cover-subtitle {
            font-size: 20px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        .cover-divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--orange), var(--orange-light));
            margin: 0 auto 40px;
            border-radius: 3px;
        }
        
        .cover-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }
        
        .cover-info span {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .cover-info i { color: var(--orange); }
        
        /* ============================================
           TYPOGRAPHIE
           ============================================ */
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--orange);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        h2 i { color: var(--orange); }
        
        h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-2);
            margin: 20px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h3 i { color: var(--orange); font-size: 16px; }
        
        h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin: 15px 0 8px;
        }
        
        p {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 12px;
            line-height: 1.7;
        }
        
        ul, ol {
            margin: 10px 0 15px 20px;
            font-size: 14px;
            color: var(--gray);
        }
        
        li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        strong { color: var(--dark); font-weight: 600; }
        
        code {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--orange-dark);
        }
        
        /* ============================================
           TABLE DES MATIÈRES
           ============================================ */
        .toc {
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
            border: 2px solid var(--orange);
            border-radius: 16px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .toc h2 { border: none; margin-top: 0; color: var(--orange-dark); }
        
        .toc ol {
            list-style: none;
            margin-left: 0;
            counter-reset: toc;
        }
        
        .toc li {
            counter-increment: toc;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(247, 151, 30, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .toc li:last-child { border-bottom: none; }
        
        .toc li::before {
            content: counter(toc);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            color: white;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        
        /* ============================================
           INFO BOXES
           ============================================ */
        .info-box {
            padding: 18px 22px;
            border-radius: 12px;
            margin: 15px 0;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            font-size: 14px;
        }
        
        .info-box i { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
        
        .info-box.tip {
            background: rgba(40, 167, 69, 0.08);
            border-left: 4px solid var(--success);
            color: #155724;
        }
        .info-box.tip i { color: var(--success); }
        
        .info-box.warning {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid var(--warning);
            color: #856404;
        }
        .info-box.warning i { color: var(--warning); }
        
        .info-box.danger {
            background: rgba(220, 53, 69, 0.08);
            border-left: 4px solid var(--danger);
            color: #721c24;
        }
        .info-box.danger i { color: var(--danger); }
        
        .info-box.info {
            background: rgba(23, 162, 184, 0.08);
            border-left: 4px solid var(--info);
            color: #0c5460;
        }
        .info-box.info i { color: var(--info); }
        
        /* ============================================
           WORKFLOW (timeline verticale)
           ============================================ */
        .workflow {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 25px 0;
        }
        
        .workflow-step {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 18px 22px;
            background: linear-gradient(135deg, #fdfcfb 0%, #fff5e6 100%);
            border-radius: 12px;
            border: 2px solid rgba(247, 151, 30, 0.2);
        }
        
        .workflow-step .number {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(247, 151, 30, 0.3);
        }
        
        .workflow-step .content h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .workflow-step .content p {
            font-size: 13px;
            color: var(--gray);
            margin: 0;
        }
        
        .workflow-arrow {
            text-align: center;
            color: var(--orange);
            font-size: 24px;
            margin: -8px 0;
        }
        
        /* ============================================
           ÉTAPES DÉTAILLÉES
           ============================================ */
        .step-block {
            background: white;
            border: 2px solid var(--gray-lighter);
            border-left: 5px solid var(--orange);
            border-radius: 12px;
            padding: 25px 28px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
            padding-bottom: 15px;
            border-bottom: 2px dashed var(--gray-lighter);
        }
        
        .step-badge {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(247, 151, 30, 0.3);
        }
        
        .step-header h3 {
            margin: 0;
            font-size: 20px;
            color: var(--dark);
        }
        
        .step-header h3 i { color: var(--orange); }
        
        /* Grille des champs */
        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 15px 0;
        }
        
        .field-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 15px;
            background: #fafafa;
            border-radius: 10px;
            border: 1px solid var(--gray-lighter);
            font-size: 13px;
        }
        
        .field-item i {
            color: var(--orange);
            margin-top: 3px;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .field-item .field-content {
            flex: 1;
        }
        
        .field-item .field-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 2px;
        }
        
        .field-item .field-desc {
            font-size: 12px;
            color: var(--gray);
        }
        
        .field-item.required {
            border-color: rgba(220, 53, 69, 0.3);
            background: rgba(220, 53, 69, 0.03);
        }
        
        .field-item.optional {
            border-color: rgba(23, 162, 184, 0.3);
            background: rgba(23, 162, 184, 0.03);
        }
        
        /* ============================================
           CARDS
           ============================================ */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .card-guide {
            background: white;
            border: 2px solid var(--gray-lighter);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .card-guide:hover {
            border-color: var(--orange);
            box-shadow: 0 8px 25px rgba(247, 151, 30, 0.15);
        }
        
        .card-guide .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--orange), var(--orange-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .card-guide h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .card-guide p {
            font-size: 13px;
            color: var(--gray);
            margin: 0;
        }
        
        /* ============================================
           FAQ
           ============================================ */
        .faq-item {
            background: #fafafa;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 12px;
            border-left: 4px solid var(--orange);
        }
        
        .faq-item .question {
            font-weight: 700;
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .faq-item .question i {
            color: var(--orange);
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .faq-item .answer {
            font-size: 13px;
            color: var(--gray);
            padding-left: 25px;
            line-height: 1.6;
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        .page-footer {
            position: absolute;
            bottom: 15mm;
            left: 20mm;
            right: 20mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--gray-light);
            padding-top: 10px;
            border-top: 1px solid var(--gray-lighter);
        }
        
        .page-footer .brand {
            font-weight: 600;
            color: var(--orange);
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .page {
                width: 100%;
                padding: 15mm 10mm;
                margin: 5mm auto;
            }
            .cover-title { font-size: 36px; }
            .cover-logo { width: 70px; height: 70px; font-size: 32px; }
            h1 { font-size: 26px; }
            h2 { font-size: 20px; }
            .cards-grid { grid-template-columns: 1fr; }
            .fields-grid { grid-template-columns: 1fr; }
            
            .fixed-buttons {
                bottom: 15px;
                right: 15px;
                flex-direction: column-reverse;
                gap: 8px;
                align-items: stretch;
            }
            .back-btn, .print-btn {
                padding: 12px 18px;
                font-size: 11px;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- BOUTONS FIXES                                 -->
<!-- ============================================ -->
<div class="fixed-buttons no-print">
    <a href="<?php echo adminUrl('dashboard.php'); ?>" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Retour au tableau de bord
    </a>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-file-pdf"></i>
        Enregistrer en PDF
    </button>
</div>

<!-- ============================================ -->
<!-- PAGE 1 : COUVERTURE                            -->
<!-- ============================================ -->
<div class="page cover">
    <div class="cover-content">
        <div class="cover-logo">
            <i class="fas fa-stars"></i>
        </div>
        
        <div class="cover-title">Guide<br>Utilisateur</div>
        <div class="cover-subtitle"><?php echo $appName; ?> • Gestion d'événements</div>
        
        <div class="cover-divider"></div>
        
        <div class="cover-info">
            <span><i class="fas fa-book"></i> Version <?php echo $version; ?></span>
            <span><i class="fas fa-calendar"></i> Édition <?php echo $date; ?></span>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Guide utilisateur v<?php echo $version; ?></span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 2 : TABLE DES MATIÈRES + WORKFLOW          -->
<!-- ============================================ -->
<div class="page">
    <h1>Bienvenue 👋</h1>
    
    <p>
        Ce guide vous accompagne pas à pas dans l'utilisation de <strong><?php echo $appName; ?></strong>, 
        votre plateforme de gestion d'événements. Suivez les étapes dans l'ordre pour une utilisation optimale.
    </p>
    
    <div class="toc">
        <h2><i class="fas fa-list"></i> Table des matières</h2>
        <ol>
            <li>Créer l'événement</li>
            <li>Assigner les boissons</li>
            <li>Créer les tables</li>
            <li>Créer l'invité (invitation automatique)</li>
            <li>Envoyer les invitations en un clic</li>
            <li>Recevoir les réponses des invités</li>
            <li>Gérer les présences le jour J</li>
        </ol>
    </div>
    
    <h2><i class="fas fa-route"></i> Le workflow complet</h2>
    
    <p>Voici le parcours à suivre pour organiser votre événement :</p>
    
    <div class="workflow">
        <div class="workflow-step">
            <div class="number">1</div>
            <div class="content">
                <h4>📅 Créer l'événement</h4>
                <p>Nom, type, description, date/heure, lieu, modèle, photo de fond...</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">2</div>
            <div class="content">
                <h4>🍹 Assigner les boissons</h4>
                <p>Sélection des boissons disponibles pour les invités</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">3</div>
            <div class="content">
                <h4>🪑 Créer les tables</h4>
                <p>Nom, capacité, type, zone, position X/Y</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">4</div>
            <div class="content">
                <h4>👥 Créer l'invité</h4>
                <p>Infos personnelles + invitation générée automatiquement</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">5</div>
            <div class="content">
                <h4>📤 Envoyer les invitations en 1 clic</h4>
                <p>WhatsApp et/ou Email en masse</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">6</div>
            <div class="content">
                <h4>💬 Recevoir les réponses</h4>
                <p>Confirmation, message, choix des boissons</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">7</div>
            <div class="content">
                <h4>📷 Gérer les présences le jour J</h4>
                <p>Scan QR code + affichage photo sur écran géant</p>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 2</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 3 : ÉTAPE 1 — CRÉER L'ÉVÉNEMENT          -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">1</div>
            <h3><i class="fas fa-calendar-plus"></i> Créer l'événement</h3>
        </div>
        
        <p>
            L'événement est la base de tout. C'est ici que vous définissez les informations principales 
            qui seront utilisées dans les invitations.
        </p>
        
        <h4><i class="fas fa-arrow-right" style="color:var(--orange);"></i> Comment faire</h4>
        <ol>
            <li>Allez dans le menu <code>Événements</code></li>
            <li>Cliquez sur <strong>➕ Ajouter un événement</strong></li>
            <li>Remplissez tous les champs</li>
            <li>Cliquez sur <strong>💾 Enregistrer</strong></li>
        </ol>
        
        <h4><i class="fas fa-list-check" style="color:var(--orange);"></i> Champs à remplir</h4>
        
        <div class="fields-grid">
            <div class="field-item required">
                <i class="fas fa-tag"></i>
                <div class="field-content">
                    <div class="field-name">Nom de l'événement *</div>
                    <div class="field-desc">Ex: "Mariage de Clara & Junias"</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-theater-masks"></i>
                <div class="field-content">
                    <div class="field-name">Type d'événement *</div>
                    <div class="field-desc">Mariage, anniversaire, baptême...</div>
                </div>
            </div>
            
            <div class="field-item required" style="grid-column: 1 / -1;">
                <i class="fas fa-comment-dots"></i>
                <div class="field-content">
                    <div class="field-name">Description *</div>
                    <div class="field-desc">Message qui s'affichera sur l'invitation</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-calendar-day"></i>
                <div class="field-content">
                    <div class="field-name">Date & Heure *</div>
                    <div class="field-desc">Date et heure de l'événement</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-map-marker-alt"></i>
                <div class="field-content">
                    <div class="field-name">Lieu *</div>
                    <div class="field-desc">Nom de la salle + adresse</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-palette"></i>
                <div class="field-content">
                    <div class="field-name">Modèle d'invitation *</div>
                    <div class="field-desc">Classique, Netflix, Floral...</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-image"></i>
                <div class="field-content">
                    <div class="field-name">Photo de fond *</div>
                    <div class="field-desc">Image principale de l'événement</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-share-alt"></i>
                <div class="field-content">
                    <div class="field-name">Moyens de communication</div>
                    <div class="field-desc">Email, WhatsApp, Telegram</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-user-friends"></i>
                <div class="field-content">
                    <div class="field-name">Utilisateurs associés</div>
                    <div class="field-desc">Qui peut gérer cet événement</div>
                </div>
            </div>
            
            <div class="field-item optional" style="grid-column: 1 / -1;">
                <i class="fas fa-camera"></i>
                <div class="field-content">
                    <div class="field-name">Photos souvenirs</div>
                    <div class="field-desc">Photos qui apparaîtront dans l'invitation</div>
                </div>
            </div>
        </div>
        
        <div class="info-box tip">
            <i class="fas fa-lightbulb"></i>
            <div>
                <strong>Astuce :</strong> Utilisez une belle image de fond de haute qualité — elle sera affichée en plein écran dans les invitations.
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 3</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 4 : ÉTAPE 2 — ASSIGNER LES BOISSONS      -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">2</div>
            <h3><i class="fas fa-wine-glass"></i> Assigner les boissons</h3>
        </div>
        
        <p>
            Les invités pourront choisir leurs boissons préférées (2 maximum). 
            Vous devez donc définir la liste des boissons disponibles pour l'événement.
        </p>
        
        <h4><i class="fas fa-arrow-right" style="color:var(--orange);"></i> Comment faire</h4>
        <ol>
            <li>Allez dans <code>Boissons</code></li>
            <li>Créez d'abord vos boissons (si ce n'est pas fait)</li>
            <li>Cliquez sur <strong>🍹 Assigner à un événement</strong></li>
            <li>Sélectionnez les boissons à rendre disponibles</li>
            <li>Validez</li>
        </ol>
        
        <div class="info-box tip">
            <i class="fas fa-lightbulb"></i>
            <div>
                <strong>Conseil :</strong> Proposez un mix d'alcools, softs et eaux pour tous les goûts.
                <br>Exemple : Champagne, Vin rouge, Coca-Cola, Fanta, Eau plate, Eau gazeuse...
            </div>
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">3</div>
            <h3><i class="fas fa-chair"></i> Créer les tables</h3>
        </div>
        
        <p>
            Les tables représentent la disposition de votre salle. Vous pouvez définir leur capacité 
            et les organiser par zones.
        </p>
        
        <h4><i class="fas fa-arrow-right" style="color:var(--orange);"></i> Comment faire</h4>
        <ol>
            <li>Allez dans <code>Tables</code></li>
            <li>Cliquez sur <strong>➕ Ajouter une table</strong></li>
            <li>Sélectionnez l'événement concerné</li>
            <li>Remplissez les informations</li>
        </ol>
        
        <h4><i class="fas fa-list-check" style="color:var(--orange);"></i> Champs à remplir</h4>
        
        <div class="fields-grid">
            <div class="field-item required">
                <i class="fas fa-tag"></i>
                <div class="field-content">
                    <div class="field-name">Nom de la table *</div>
                    <div class="field-desc">Ex: Table d'honneur</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-users"></i>
                <div class="field-content">
                    <div class="field-name">Capacité min / max *</div>
                    <div class="field-desc">Ex: 8 à 10 personnes</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-shapes"></i>
                <div class="field-content">
                    <div class="field-name">Type de table</div>
                    <div class="field-desc">Ronde, carrée, rectangulaire...</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-map"></i>
                <div class="field-content">
                    <div class="field-name">Zone</div>
                    <div class="field-desc">VIP, Salle principale, Terrasse...</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-arrows-alt"></i>
                <div class="field-content">
                    <div class="field-name">Position X / Y</div>
                    <div class="field-desc">Coordonnées sur le plan de salle</div>
                </div>
            </div>
        </div>
        
        <div class="info-box warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Important :</strong> Les tables doivent être créées <u>avant</u> d'assigner les invités.
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 4</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 5 : ÉTAPE 4 — CRÉER L'INVITÉ             -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">4</div>
            <h3><i class="fas fa-user-plus"></i> Créer l'invité</h3>
        </div>
        
        <p>
            Les invités sont les personnes à qui vous allez envoyer des invitations. 
            Chaque invité peut venir avec plusieurs personnes (défini par <em>nombre_personnes</em>).
        </p>
        
        <h4><i class="fas fa-arrow-right" style="color:var(--orange);"></i> Comment faire</h4>
        <ol>
            <li>Allez dans <code>Invités</code></li>
            <li>Cliquez sur <strong>➕ Ajouter un invité</strong></li>
            <li>Remplissez les informations</li>
            <li>Validez</li>
        </ol>
        
        <h4><i class="fas fa-list-check" style="color:var(--orange);"></i> Champs à remplir</h4>
        
        <div class="fields-grid">
            <div class="field-item required">
                <i class="fas fa-user"></i>
                <div class="field-content">
                    <div class="field-name">Prénom & Nom *</div>
                    <div class="field-desc">Identité de l'invité</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-address-book"></i>
                <div class="field-content">
                    <div class="field-name">Email ou Téléphone *</div>
                    <div class="field-desc">Au moins un moyen de contact</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-tags"></i>
                <div class="field-content">
                    <div class="field-name">Catégorie</div>
                    <div class="field-desc">Famille, Amis, Collègues...</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-map-marker-alt"></i>
                <div class="field-content">
                    <div class="field-name">Adresse</div>
                    <div class="field-desc">Optionnel</div>
                </div>
            </div>
            
            <div class="field-item required">
                <i class="fas fa-users"></i>
                <div class="field-content">
                    <div class="field-name">Nombre de personnes *</div>
                    <div class="field-desc">Places réservées (1 à 100)</div>
                </div>
            </div>
            
            <div class="field-item optional">
                <i class="fas fa-comment"></i>
                <div class="field-content">
                    <div class="field-name">Préférences de contact</div>
                    <div class="field-desc">Email, WhatsApp, Telegram, SMS</div>
                </div>
            </div>
            
            <div class="field-item optional" style="grid-column: 1 / -1;">
                <i class="fas fa-camera"></i>
                <div class="field-content">
                    <div class="field-name">Photo de l'invité</div>
                    <div class="field-desc">Optionnel — utilisée lors du check-in le jour J</div>
                </div>
            </div>
        </div>
        
        <div class="info-box info">
            <i class="fas fa-magic"></i>
            <div>
                <strong>🎉 Important :</strong> Une fois l'invité créé, une <strong>invitation en son nom est générée automatiquement</strong>.
                <br>
                Vous pouvez la voir immédiatement dans l'onglet <code>Invitations</code>.
                <br>
                Un <strong>code unique + QR code</strong> sont créés automatiquement avec le statut <strong>En attente</strong>.
            </div>
        </div>
        
        <div class="info-box warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Format téléphone :</strong> Utilisez le format international, ex : <code>+243 812 345 678</code> pour la RDC.
            </div>
        </div>
        
        <div class="info-box tip">
            <i class="fas fa-lightbulb"></i>
            <div>
                <strong>Astuce :</strong> Pour créer plusieurs invités d'un coup, utilisez le bouton 
                <strong>📥 Importer (CSV)</strong> dans la liste des invités.
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 5</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 6 : ÉTAPE 5 — ENVOYER LES INVITATIONS    -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">5</div>
            <h3><i class="fas fa-paper-plane"></i> Envoyer les invitations en 1 clic</h3>
        </div>
        
        <p>
            C'est l'étape la plus rapide ! Vous sélectionnez les invités et vous cliquez sur envoyer — 
            <strong>tout le monde reçoit son invitation instantanément</strong>.
        </p>
        
        <h3><i class="fas fa-paper-plane" style="color:var(--orange);"></i> Par WhatsApp</h3>
        <ol>
            <li>Allez dans <code>Notifications → WhatsApp</code></li>
            <li>Sélectionnez les invités à qui envoyer</li>
            <li>Cliquez sur <strong>📤 Envoyer</strong></li>
            <li>Chaque invité reçoit son invitation par WhatsApp</li>
        </ol>
        
        <h3><i class="fas fa-envelope" style="color:var(--orange);"></i> Par Email</h3>
        <ol>
            <li>Allez dans <code>Notifications → Emails</code></li>
            <li>Sélectionnez les invitations à envoyer</li>
            <li>Cliquez sur <strong>📤 Envoyer</strong></li>
            <li>Chaque invité reçoit un email avec le lien de son invitation</li>
        </ol>
        
        <h3><i class="fas fa-link" style="color:var(--orange);"></i> Ou par lien direct</h3>
        <p>
            Vous pouvez aussi copier le lien de l'invitation depuis <code>Invitations</code> 
            et le partager manuellement.
        </p>
        
        <div class="info-box tip">
            <i class="fas fa-rocket"></i>
            <div>
                <strong>Envoi en masse :</strong> Un seul clic suffit pour envoyer à tous vos invités 
                en même temps. Plus besoin de le faire un par un !
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 6</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 7 : ÉTAPE 6 — LES INVITÉS RÉPONDENT      -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">6</div>
            <h3><i class="fas fa-comments"></i> Les invités reçoivent et répondent</h3>
        </div>
        
        <p>
            Une fois l'invitation reçue, l'invité peut interagir avec elle depuis son téléphone. 
            Voici ce qu'il peut faire :
        </p>
        
        <h3><i class="fas fa-check-circle" style="color:var(--orange);"></i> 1. Confirmer sa présence</h3>
        <p>
            L'invité clique sur le bouton <strong>"Confirmer ma présence"</strong> et indique 
            le nombre de personnes qui viendront.
        </p>
        
        <h3><i class="fas fa-comment-dots" style="color:var(--orange);"></i> 2. Laisser un message aux hôtes</h3>
        <p>
            Il peut écrire un message personnel (vœux, souvenirs, encouragement...) 
            qui sera enregistré et visible par les organisateurs.
        </p>
        
        <h3><i class="fas fa-wine-glass" style="color:var(--orange);"></i> 3. Choisir ses préférences de boissons</h3>
        <p>
            Il sélectionne jusqu'à <strong>2 boissons</strong> parmi celles que vous avez définies.
            Cela vous permet d'anticiper les quantités.
        </p>
        
        <div class="info-box info">
            <i class="fas fa-chart-line"></i>
            <div>
                <strong>Tout est visible dans l'onglet <code>Rapports</code></strong> :
                <ul style="margin-top: 8px; margin-bottom: 0;">
                    <li>✅ Confirmations reçues</li>
                    <li>💬 Messages des invités</li>
                    <li>🍹 Préférences de boissons</li>
                    <li>📊 Statistiques globales</li>
                </ul>
            </div>
        </div>
        
        <div class="info-box tip">
            <i class="fas fa-bell"></i>
            <div>
                <strong>Astuce :</strong> Suivez les réponses en temps réel depuis le <strong>Tableau de bord</strong> 
                pour connaître à tout moment le nombre de confirmations.
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 7</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 8 : ÉTAPE 7 — GÉRER LES PRÉSENCES        -->
<!-- ============================================ -->
<div class="page">
    <div class="step-block">
        <div class="step-header">
            <div class="step-badge">7</div>
            <h3><i class="fas fa-qrcode"></i> Gérer les présences le jour J</h3>
        </div>
        
        <p>
            C'est le moment magique ! Le jour de l'événement, l'invité se présente à l'entrée 
            avec son invitation (papier ou téléphone). Le contrôleur scanne son QR code.
        </p>
        
        <h3><i class="fas fa-mobile-alt" style="color:var(--orange);"></i> Procédure de check-in</h3>
        <ol>
            <li>L'invité arrive à l'entrée</li>
            <li>Le contrôleur ouvre <code>Présences</code> sur son téléphone ou tablette</li>
            <li>Il scanne le QR code de l'invitation</li>
            <li>La présence est enregistrée automatiquement</li>
        </ol>
        
        <h3><i class="fas fa-tv" style="color:var(--orange);"></i> Affichage sur écran géant</h3>
        <p>
            Une fois la présence détectée, voici ce qui se passe :
        </p>
        <ul>
            <li>📸 <strong>La photo de l'invité s'affiche</strong> en grand sur l'écran géant</li>
            <li>👤 <strong>Son nom apparaît</strong> en lettres majuscules</li>
            <li>🎉 <strong>Un message de bienvenue personnalisé</strong> s'affiche</li>
            <li>✨ Une animation d'accueil s'enclenche</li>
        </ul>
        
        <div class="info-box tip">
            <i class="fas fa-lightbulb"></i>
            <div>
                <strong>Astuce :</strong> Utilisez une grande TV ou un vidéoprojecteur connecté à un ordinateur 
                pour afficher l'écran de bienvenue. Effet garanti auprès de vos invités !
            </div>
        </div>
        
        <div class="info-box warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Prévoyez :</strong> Un smartphone ou une tablette pour le contrôleur, 
                avec une bonne connexion internet pour synchroniser les présences en temps réel.
            </div>
        </div>
        
        <h3><i class="fas fa-chart-bar" style="color:var(--orange);"></i> Suivi en temps réel</h3>
        <p>
            Depuis votre téléphone, vous pouvez voir à tout moment :
        </p>
        <ul>
            <li>Le nombre d'invités déjà arrivés</li>
            <li>Le nombre d'invités attendus</li>
            <li>La liste des présences enregistrées</li>
        </ul>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 8</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 9 : FAQ                                  -->
<!-- ============================================ -->
<div class="page">
    <h1>Questions fréquentes ❓</h1>
    
    <h2><i class="fas fa-question-circle"></i> FAQ</h2>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Une invitation est-elle créée automatiquement quand je crée un invité ?
        </div>
        <div class="answer">
            <strong>Oui !</strong> Dès que vous créez un invité, une invitation en son nom est 
            générée automatiquement avec un code unique et un QR code. 
            Vous pouvez la voir immédiatement dans <code>Invitations</code>.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Comment envoyer les invitations à tout le monde en même temps ?
        </div>
        <div class="answer">
            Allez dans <code>Notifications → WhatsApp</code> ou <code>Notifications → Emails</code>, 
            sélectionnez tous les invités, puis cliquez sur <strong>📤 Envoyer</strong>. 
            Un seul clic suffit !
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Comment modifier une invitation déjà envoyée ?
        </div>
        <div class="answer">
            Vous pouvez à tout moment modifier une invitation via <code>Invitations → ✏️ Modifier</code>. 
            Les changements seront visibles immédiatement sur la page publique de l'invité.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Un invité peut-il venir avec plus de personnes que prévu ?
        </div>
        <div class="answer">
            Non, le nombre de personnes maximum est défini à la création de l'invité. 
            Vous pouvez augmenter cette limite depuis <code>Invités → ✏️ Modifier</code>.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Où voir les messages laissés par les invités ?
        </div>
        <div class="answer">
            Tous les messages sont visibles dans <code>Rapports → Messages</code> ou 
            directement dans <code>Invitations</code>.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Comment fonctionne l'affichage sur écran géant ?
        </div>
        <div class="answer">
            Quand un invité arrive et que son QR code est scanné, sa photo et son nom s'affichent 
            automatiquement sur l'écran géant avec un message de bienvenue. 
            Ouvrez simplement la page d'affichage sur l'écran avant l'événement.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Comment exporter la liste des présents ?
        </div>
        <div class="answer">
            Allez dans <code>Rapports → Présences</code> puis cliquez sur <strong>📄 Exporter PDF</strong>.
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-life-ring"></i> Besoin d'aide ?</h2>
    
    <p>Si vous rencontrez un problème ou avez une question :</p>
    
    <div class="info-box info">
        <i class="fab fa-whatsapp"></i>
        <div>
            <strong>WhatsApp :</strong> +243 963 967 028<br>
            <span style="font-size: 12px; color: var(--gray-light);">Réponse en moins de 24h</span>
        </div>
    </div>
    
    <div class="info-box info">
        <i class="fas fa-envelope"></i>
        <div>
            <strong>Email :</strong> support@mdlevent.com
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 9</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 10 : RÉCAPITULATIF                        -->
<!-- ============================================ -->
<div class="page">
    <h1>Récapitulatif 🎯</h1>
    
    <h2><i class="fas fa-check-double"></i> Checklist avant l'événement</h2>
    
    <div class="workflow">
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Événement créé</h4>
                <p>Nom, type, description, date, lieu, modèle, photo de fond</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Boissons assignées</h4>
                <p>Liste des boissons disponibles pour les invités</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Tables configurées</h4>
                <p>Nom, capacité, type, zone, position</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Invités créés</h4>
                <p>Invitations générées automatiquement</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Invitations envoyées</h4>
                <p>WhatsApp / Email en 1 clic</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Réponses reçues</h4>
                <p>Confirmations, messages, boissons</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Présences le jour J</h4>
                <p>Scan QR code + affichage écran géant</p>
            </div>
        </div>
    </div>
    
    <div class="info-box tip" style="margin-top: 30px;">
        <i class="fas fa-trophy"></i>
        <div>
            <strong>Félicitations !</strong> Vous êtes prêt pour votre événement. 
            Bonne organisation et profitez bien de ce moment inoubliable ! 🎉
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 60px;">
        <div style="font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 900; color: var(--orange); margin-bottom: 10px;">
            <?php echo $appName; ?>
        </div>
        <div style="font-size: 14px; color: var(--gray);">
            L'expert événementiel
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 10 — Fin du guide</span>
    </div>
</div>

<script>
    document.querySelector('.print-btn').addEventListener('click', function() {
        setTimeout(() => window.print(), 100);
    });
    
    if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
        document.querySelector('.print-btn').innerHTML = 
            '<i class="fas fa-file-pdf"></i> Partager en PDF';
    }
</script>

</body>
</html>