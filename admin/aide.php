<?php
// ============================================================
// GUIDE UTILISATEUR - MdlEvent
// ============================================================

$appName = 'MdlEvent';
$version = '1.0';
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
           BOUTONS FIXES (Retour + Impression)
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

        /* Bouton Retour */
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

        .back-btn i { font-size: 16px; }

        /* Bouton Impression */
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
        
        .cover-content {
            position: relative;
            z-index: 2;
        }
        
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
           WORKFLOW
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
<!-- BOUTONS FIXES (Retour + Impression)           -->
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
<!-- PAGE 2 : TABLE DES MATIÈRES                    -->
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
            <li>Créer un événement</li>
            <li>Créer les tables</li>
            <li>Assigner les boissons</li>
            <li>Créer les invités</li>
            <li>Créer les invitations</li>
            <li>Envoyer les invitations</li>
            <li>Suivre les confirmations</li>
            <li>Gérer les présences</li>
            <li>Consulter les rapports</li>
            <li>Questions fréquentes</li>
        </ol>
    </div>
    
    <h2><i class="fas fa-route"></i> Le workflow complet</h2>
    
    <p>Voici le parcours à suivre pour organiser votre événement :</p>
    
    <div class="workflow">
        <div class="workflow-step">
            <div class="number">1</div>
            <div class="content">
                <h4>📅 Créer l'événement</h4>
                <p>Informations générales (nom, date, lieu, type)</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">2</div>
            <div class="content">
                <h4>🪑 Créer les tables</h4>
                <p>Disposition de la salle + capacités</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">3</div>
            <div class="content">
                <h4>🍹 Assigner les boissons</h4>
                <p>Sélection des boissons disponibles</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">4</div>
            <div class="content">
                <h4>👥 Créer les invités</h4>
                <p>Nom, prénom, email, téléphone, nombre de places</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">5</div>
            <div class="content">
                <h4>✉️ Créer les invitations</h4>
                <p>Lien entre invité et événement + QR code</p>
            </div>
        </div>
        <div class="workflow-arrow"><i class="fas fa-arrow-down"></i></div>
        
        <div class="workflow-step">
            <div class="number">6</div>
            <div class="content">
                <h4>📤 Envoyer les invitations</h4>
                <p>Email, WhatsApp ou Telegram</p>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 2</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 3 : ÉTAPES 1 & 2                          -->
<!-- ============================================ -->
<div class="page">
    <h2><i class="fas fa-calendar-plus"></i> Étape 1 — Créer un événement</h2>
    
    <p>
        L'événement est la base de tout. C'est ici que vous définissez les informations principales 
        qui seront utilisées dans les invitations.
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
    <ol>
        <li>Allez dans le menu <code>Événements</code></li>
        <li>Cliquez sur <strong>➕ Ajouter un événement</strong></li>
        <li>Remplissez les informations demandées</li>
        <li>Cliquez sur <strong>💾 Enregistrer</strong></li>
    </ol>
    
    <h3><i class="fas fa-info-circle"></i> Informations à remplir</h3>
    <ul>
        <li><strong>Nom de l'événement</strong> — Ex: "Mariage de Clara & Junias"</li>
        <li><strong>Type</strong> — Mariage, anniversaire, baptême, etc.</li>
        <li><strong>Date et heure</strong></li>
        <li><strong>Lieu</strong> — Nom de la salle</li>
        <li><strong>Adresse</strong> — Adresse complète</li>
        <li><strong>Description</strong> — Message pour les invités</li>
        <li><strong>Image de fond</strong> — Photo de l'événement</li>
    </ul>
    
    <div class="info-box tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong>Astuce :</strong> Utilisez une belle image de fond — elle sera affichée dans les invitations.
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-chair"></i> Étape 2 — Créer les tables</h2>
    
    <p>
        Les tables représentent la disposition de votre salle. Vous pouvez définir leur capacité 
        et les organiser par zones.
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
    <ol>
        <li>Allez dans <code>Tables</code></li>
        <li>Cliquez sur <strong>➕ Ajouter une table</strong></li>
        <li>Sélectionnez l'événement concerné</li>
        <li>Indiquez le nom/n° de la table</li>
        <li>Définissez la capacité (nombre de places)</li>
        <li>Choisissez une zone (VIP, salle principale...)</li>
    </ol>
    
    <div class="info-box warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Important :</strong> Les tables doivent être créées <u>avant</u> d'assigner les invités.
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 3</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 4 : ÉTAPES 3 & 4                          -->
<!-- ============================================ -->
<div class="page">
    <h2><i class="fas fa-wine-glass"></i> Étape 3 — Assigner les boissons</h2>
    
    <p>
        Les invités pourront choisir leurs boissons préférées (max 2). Vous devez donc définir 
        la liste des boissons disponibles pour l'événement.
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
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
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-users"></i> Étape 4 — Créer les invités</h2>
    
    <p>
        Les invités sont les personnes à qui vous allez envoyer des invitations. 
        Chaque invité peut venir avec plusieurs personnes (défini par <em>nombre_personnes</em>).
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
    <ol>
        <li>Allez dans <code>Invités</code></li>
        <li>Cliquez sur <strong>➕ Ajouter un invité</strong></li>
        <li>Remplissez :</li>
    </ol>
    
    <ul>
        <li><strong>Nom + Prénom</strong></li>
        <li><strong>Email</strong> — pour l'envoi par email</li>
        <li><strong>Téléphone</strong> — pour WhatsApp (format international)</li>
        <li><strong>Nombre de personnes</strong> — places réservées</li>
        <li><strong>Événement</strong> — rattachez l'invité à l'événement</li>
    </ul>
    
    <div class="info-box info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Astuce :</strong> Pour créer plusieurs invités d'un coup, utilisez le bouton 
            <strong>📥 Importer (CSV)</strong>.
        </div>
    </div>
    
    <div class="info-box warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Format téléphone :</strong> Utilisez le format international, ex : <code>+243 812 345 678</code> pour la RDC.
        </div>
    </div>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 4</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 5 : ÉTAPES 5 & 6                          -->
<!-- ============================================ -->
<div class="page">
    <h2><i class="fas fa-envelope"></i> Étape 5 — Créer les invitations</h2>
    
    <p>
        L'invitation fait le lien entre un <strong>invité</strong> et un <strong>événement</strong>. 
        Un code unique + QR code sont générés automatiquement.
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
    <ol>
        <li>Allez dans <code>Invitations</code></li>
        <li>Cliquez sur <strong>➕ Créer une invitation</strong></li>
        <li>Sélectionnez l'invité</li>
        <li>Sélectionnez l'événement</li>
        <li>Le <strong>code unique</strong> est généré automatiquement</li>
    </ol>
    
    <div class="info-box tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong>Astuce :</strong> Le code unique (ex: <code>MDL-DEMO-2024</code>) sert à identifier 
            l'invitation sur la page publique.
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-paper-plane"></i> Étape 6 — Envoyer les invitations</h2>
    
    <p>Vous pouvez envoyer les invitations par 3 canaux :</p>
    
    <div class="cards-grid">
        <div class="card-guide">
            <div class="card-icon"><i class="fas fa-envelope"></i></div>
            <h4>📧 Email</h4>
            <p>Envoi automatique par email avec le lien de l'invitation.</p>
        </div>
        <div class="card-guide">
            <div class="card-icon"><i class="fab fa-whatsapp"></i></div>
            <h4>💬 WhatsApp</h4>
            <p>Envoi via l'API WhatsApp Business avec message personnalisé.</p>
        </div>
        <div class="card-guide">
            <div class="card-icon"><i class="fab fa-telegram"></i></div>
            <h4>📨 Telegram</h4>
            <p>Envoi via un bot Telegram aux utilisateurs.</p>
        </div>
        <div class="card-guide">
            <div class="card-icon"><i class="fas fa-link"></i></div>
            <h4>🔗 Lien direct</h4>
            <p>Copiez le lien de l'invitation et partagez-le manuellement.</p>
        </div>
    </div>
    
    <h3><i class="fas fa-arrow-right"></i> Envoi en masse</h3>
    <ol>
        <li>Allez dans <code>Notifications</code></li>
        <li>Choisissez le canal (Email / WhatsApp / Telegram)</li>
        <li>Sélectionnez les invitations à envoyer</li>
        <li>Cliquez sur <strong>📤 Envoyer</strong></li>
    </ol>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 5</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 6 : SUIVI ET RAPPORTS                     -->
<!-- ============================================ -->
<div class="page">
    <h2><i class="fas fa-chart-line"></i> Étape 7 — Suivre les confirmations</h2>
    
    <p>
        Une fois les invitations envoyées, les invités vont confirmer ou refuser leur présence. 
        Vous pouvez suivre tout ça en temps réel.
    </p>
    
    <h3><i class="fas fa-arrow-right"></i> Tableau de bord</h3>
    <p>Le <strong>Tableau de bord</strong> affiche en direct :</p>
    <ul>
        <li>Nombre d'invitations envoyées</li>
        <li>Nombre de confirmations</li>
        <li>Nombre de refus</li>
        <li>Nombre de présences enregistrées</li>
    </ul>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-qrcode"></i> Étape 8 — Gérer les présences</h2>
    
    <p>Le jour J, scannez le QR code de chaque invité à l'entrée pour enregistrer sa présence.</p>
    
    <h3><i class="fas fa-arrow-right"></i> Comment faire</h3>
    <ol>
        <li>Allez dans <code>Présences</code></li>
        <li>Utilisez la caméra pour scanner le QR code</li>
        <li>La présence est enregistrée automatiquement</li>
        <li>Ou saisissez manuellement le code unique</li>
    </ol>
    
    <div class="info-box tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong>Astuce :</strong> Utilisez un smartphone ou une tablette à l'entrée pour un check-in rapide.
        </div>
    </div>
    
    <hr style="margin: 40px 0; border: none; border-top: 2px dashed var(--gray-lighter);">
    
    <h2><i class="fas fa-file-alt"></i> Étape 9 — Consulter les rapports</h2>
    
    <p>Tous les rapports sont disponibles dans <code>Rapports</code> :</p>
    
    <ul>
        <li>📊 <strong>Statistiques globales</strong></li>
        <li>👥 <strong>Liste des invités</strong></li>
        <li>✅ <strong>Confirmations</strong></li>
        <li>🎯 <strong>Présences</strong></li>
        <li>🍹 <strong>Préférences boissons</strong></li>
        <li>📤 <strong>Export PDF</strong></li>
    </ul>
    
    <div class="page-footer">
        <span class="brand"><?php echo $appName; ?></span>
        <span>Page 6</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 7 : FAQ                                  -->
<!-- ============================================ -->
<div class="page">
    <h1>Questions fréquentes ❓</h1>
    
    <h2><i class="fas fa-question-circle"></i> FAQ</h2>
    
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
            Comment créer plusieurs invitations en une seule fois ?
        </div>
        <div class="answer">
            Utilisez la fonction <strong>📥 Importer CSV</strong> dans <code>Invités</code>. 
            Un modèle de fichier est disponible au téléchargement.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Que se passe-t-il si un invité ne reçoit pas son invitation ?
        </div>
        <div class="answer">
            Vérifiez que l'email/téléphone est correct, puis renvoyez manuellement depuis 
            <code>Invitations → 📤 Envoyer</code>.
        </div>
    </div>
    
    <div class="faq-item">
        <div class="question">
            <i class="fas fa-comments"></i>
            Puis-je annuler une invitation ?
        </div>
        <div class="answer">
            Oui. Dans <code>Invitations</code>, cliquez sur <strong>🚫 Annuler</strong>. 
            L'invitation ne sera plus accessible via son code.
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
        <span>Page 7</span>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGE 8 : RÉCAPITULATIF                         -->
<!-- ============================================ -->
<div class="page">
    <h1>Récapitulatif 🎯</h1>
    
    <h2><i class="fas fa-check-double"></i> Checklist avant l'événement</h2>
    
    <div class="workflow">
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Événement créé</h4>
                <p>Nom, date, lieu et description renseignés</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Tables configurées</h4>
                <p>Capacité et zones définies</p>
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
                <h4>Invités créés</h4>
                <p>Nom, email, téléphone et nombre de places</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Invitations générées</h4>
                <p>Codes uniques et QR codes créés</p>
            </div>
        </div>
        <div class="workflow-step">
            <div class="number">✓</div>
            <div class="content">
                <h4>Invitations envoyées</h4>
                <p>Email, WhatsApp ou Telegram</p>
            </div>
        </div>
    </div>
    
    <div class="info-box tip" style="margin-top: 30px;">
        <i class="fas fa-trophy"></i>
        <div>
            <strong>Félicitations !</strong> Vous êtes prêt pour votre événement. 
            Bonne organisation et profitez bien de ce moment ! 🎉
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
        <span>Page 8 — Fin du guide</span>
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