<?php
// Page d'accueil du site MdlEvent
require_once __DIR__ . '/../config/database.php';

$appName = defined('APP_NAME') ? APP_NAME : 'MdlEvent';

// ============================================================
// DÉTECTION DU CHEMIN DU LOGO
// ============================================================
$logoPath = '';
$logoFullPath = '';

$possiblePaths = [
    __DIR__ . '/../assets/images/logo.png',
    __DIR__ . '/assets/images/logo.png',
    $_SERVER['DOCUMENT_ROOT'] . '/gestion_invitations/assets/images/logo.png',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/images/logo.png',
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $logoFullPath = $path;
        break;
    }
}

if (!empty($logoFullPath)) {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $relativePath = str_replace($docRoot, '', $logoFullPath);
    $relativePath = str_replace('\\', '/', $relativePath);

    if (strpos($relativePath, '/gestion_invitations/') !== false) {
        $logoPath = '/gestion_invitations/assets/images/logo.png';
    } else {
        $logoPath = '/assets/images/logo.png';
    }
}

if (empty($logoPath)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/images/logo.png')) {
        $logoPath = '/assets/images/logo.png';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/gestion_invitations/assets/images/logo.png')) {
        $logoPath = '/gestion_invitations/assets/images/logo.png';
    } else {
        if (file_exists(__DIR__ . '/../assets/images/logo.png')) {
            $logoPath = '../assets/images/logo.png';
        } elseif (file_exists(__DIR__ . '/assets/images/logo.png')) {
            $logoPath = 'assets/images/logo.png';
        }
    }
}

// ============================================================
// DÉTECTION DU CHEMIN DES IMAGES
// ============================================================
$baseImagePath = '';
if (!empty($logoPath)) {
    if (strpos($logoPath, '/assets/') !== false) {
        $baseImagePath = str_replace('/logo.png', '', $logoPath);
    } else {
        $baseImagePath = 'assets/images';
    }
} else {
    $baseImagePath = 'assets/images';
}

function imgUrl($path, $baseImagePath) {
    $fullPath = __DIR__ . '/../' . $baseImagePath . '/' . $path;
    $rootPath = $_SERVER['DOCUMENT_ROOT'] . $baseImagePath . '/' . $path;

    if (file_exists($fullPath)) {
        return $baseImagePath . '/' . $path;
    } elseif (file_exists($rootPath)) {
        return $baseImagePath . '/' . $path;
    }
    return '';
}

// ============================================================
// TYPES D'ÉVÉNEMENTS AVEC IMAGES
// ============================================================
$eventTypes = [
    ['icon' => 'fa-ring',              'label' => 'Mariage',                   'desc' => 'Cérémonies intimes ou grandes réceptions', 'image' => 'galerie/mariage.jpg'],
    ['icon' => 'fa-birthday-cake',     'label' => 'Anniversaire',              'desc' => 'Fêtes et célébrations personnelles',       'image' => 'galerie/anniversaire.jpg'],
    ['icon' => 'fa-chalkboard-teacher','label' => 'Conférence',                'desc' => 'Conférences et keynote speakers',          'image' => 'galerie/conference.jpg'],
    ['icon' => 'fa-users',             'label' => 'Séminaire',                 'desc' => 'Formations et séminaires professionnels',   'image' => 'galerie/seminaire.jpg'],
    ['icon' => 'fa-handshake',         'label' => 'Réunion',                   'desc' => 'Rencontres et assemblées',                  'image' => 'galerie/reunion.jpg'],
    ['icon' => 'fa-tshirt',            'label' => 'Défilé de mode',            'desc' => 'Shows et lancements de collections',        'image' => 'galerie/defile.jpg'],
    ['icon' => 'fa-award',             'label' => 'Cérémonie',                 'desc' => 'Remises de prix et distinctions',           'image' => 'galerie/ceremonies.jpg'],
    ['icon' => 'fa-glass-cheers',      'label' => 'Soirée privée',             'desc' => 'Événements intimes et VIP',                 'image' => 'galerie/soiree.jpg'],
    ['icon' => 'fa-rocket',            'label' => 'Lancement de produit',      'desc' => 'Inaugurations et présentations',            'image' => 'galerie/lancement.jpg'],
    ['icon' => 'fa-briefcase',         'label' => 'Événement professionnel',   'desc' => 'Corporate, gala et networking',             'image' => 'galerie/professionnel.jpg'],
];

// ============================================================
// INVITÉS DE DÉMONSTRATION POUR L'ÉCRAN SPLASH
// (Seront remplacés par de vraies données scannées en BDD)
// ============================================================
$demoGuests = [
      [
        'name'       => 'MERLIN DIMANA',
        'hosts'      => 'Ziana et Bénédicte',
        'persons'    => '1 pers.',
        'status'     => 'Présent(e)',
        'hostsImg'   => 'splash/hosts-ziana-benedicte.jpeg',
        'guestImg'   => 'splash/guest-merlin.jpeg',
        'eventInfo'  => 'Salle Prestige · 15 Février 2027',
        'position'   => 3,
    ],
    [
        'name'       => 'DENIS NDEZO',
        'hosts'      => 'Gradie et Divine',
        'persons'    => '1 pers.',
        'status'     => 'Présent(e)',
        'hostsImg'   => 'splash/hosts-gradie-divine.jpg',
        'guestImg'   => 'splash/denis.jpeg',
        'eventInfo'  => 'Salle de fete Exodus · 21 Decembre 2026',
        'position'   => 2,
    ],
  
    [
        'name'       => 'PASTOR GRADI',
        'hosts'      => 'Trésor & Olivia',
        'persons'    => '2 pers.',
        'status'     => 'Présent(e)',
        'hostsImg'   => 'splash/hosts.jpg',
        'guestImg'   => 'splash/pastor.jpeg',
        'eventInfo'  => 'Salle de fete Exodus · 21 Decembre 2026',
        'position'   => 4,
    ],
];

$firstGuest = $demoGuests[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName); ?> - L'expert événementiel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&family=Great+Vibes&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8f5f2;
            color: #1a1a1a;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ================================================================
           ANIMATIONS GLOBALES
           ================================================================ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes floatUp {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        @keyframes pulseSoft {
            0%, 100% { opacity: 0.85; transform: scale(1); }
            50%      { opacity: 1; transform: scale(1.05); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes floatHeart {
            0%   { opacity: 0; transform: translateY(0) scale(0.5) rotate(0deg); }
            20%  { opacity: 1; }
            100% { opacity: 0; transform: translateY(-400px) scale(1) rotate(20deg); }
        }

        /* Animation d'apparition de l'invité (scan) */
        @keyframes guestAppear {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.85);
                filter: blur(8px);
            }
            60% {
                opacity: 1;
                transform: translateY(-6px) scale(1.03);
                filter: blur(0);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }
        @keyframes hostAppear {
            0% {
                opacity: 0;
                transform: scale(0.85) rotate(-6deg);
                filter: blur(6px);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg);
                filter: blur(0);
            }
        }
        @keyframes scanSweep {
            0%   { transform: translateY(-100%); opacity: 0; }
            20%  { opacity: 1; }
            80%  { opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* ===== HERO SECTION ===== */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 70px;
            position: relative;
            background: linear-gradient(135deg, rgba(17,17,25,0.92) 0%, rgba(30,30,48,0.88) 100%);
            overflow: hidden;
        }
        .hero-bg-image {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.35;
            z-index: 0;
            animation: zoomIn 1.4s ease-out both;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(193,124,96,0.25) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212,165,116,0.15) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 850px;
        }

        .hero-logo { margin-bottom: 24px; animation: zoomIn 0.9s ease-out both; }
        .hero-logo img {
            max-width: 130px;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            transition: transform 0.4s ease;
        }
        .hero-logo img:hover { transform: scale(1.05); }
        .hero-logo .logo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 110px;
            height: 110px;
            background: linear-gradient(135deg, rgba(193,124,96,0.2), rgba(212,165,116,0.1));
            border-radius: 20px;
            border: 2px solid rgba(193,124,96,0.2);
            font-size: 52px;
            color: #c17c60;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(193,124,96,0.15);
            border: 1px solid rgba(193,124,96,0.2);
            padding: 8px 18px;
            border-radius: 100px;
            color: #c17c60;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
            animation: fadeInUp 0.9s ease-out 0.2s both;
        }
        .hero-badge i { font-size: 14px; animation: pulseSoft 2.5s ease-in-out infinite; }

        .hero h1 {
            font-family: Georgia, serif;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 400;
            color: white;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: fadeInUp 0.9s ease-out 0.35s both;
        }
        .hero h1 span { color: #c17c60; }

        .hero p {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: rgba(255,255,255,0.75);
            max-width: 650px;
            margin: 0 auto 32px;
            line-height: 1.7;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: fadeInUp 0.9s ease-out 0.5s both;
        }
        .hero p strong { color: #d4a574; font-weight: 700; }

        .social-links {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 28px;
            animation: fadeInUp 0.9s ease-out 0.65s both;
        }
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.6);
            font-size: 18px;
            transition: all 0.3s ease;
            text-decoration: none;
            backdrop-filter: blur(8px);
        }
        .social-links a:hover {
            background: rgba(193,124,96,0.2);
            border-color: rgba(193,124,96,0.3);
            color: #c17c60;
            transform: translateY(-4px) scale(1.08);
        }
        .social-links a.whatsapp:hover {
            background: rgba(37, 211, 102, 0.2);
            border-color: rgba(37, 211, 102, 0.3);
            color: #25D366;
        }
        .social-links a.facebook:hover {
            background: rgba(24, 119, 242, 0.2);
            border-color: rgba(24, 119, 242, 0.3);
            color: #1877F2;
        }

        /* ===== SECTION COMMUNE ===== */
        .section {
            padding: 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-glass {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 60px 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .section-glass.visible { opacity: 1; transform: translateY(0); }
        @media (max-width: 768px) {
            .section-glass { padding: 40px 24px; border-radius: 20px; }
        }
        .section-title {
            text-align: center;
            margin-bottom: 48px;
        }
        .section-title .subtitle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #c17c60;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .section-title h2 {
            font-family: Georgia, serif;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 400;
            color: #1a1a1a;
            letter-spacing: -0.02em;
        }
        .section-title p {
            color: #8a8a9a;
            font-size: 1.05rem;
            max-width: 600px;
            margin: 12px auto 0;
        }

        /* ================================================================
           TYPES D'ÉVÉNEMENTS
           ================================================================ */
        .event-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }
        .event-type-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            opacity: 0;
            transform: translateY(30px) scale(0.96);
            transition: opacity 0.6s ease, transform 0.6s ease,
                        box-shadow 0.35s ease, border-color 0.35s ease;
        }
        .event-type-card.visible { opacity: 1; transform: translateY(0) scale(1); }
        .event-type-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 16px 40px rgba(193,124,96,0.18);
            border-color: rgba(193,124,96,0.35);
        }
        .event-type-image {
            width: 100%;
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
            background-color: #f0ebe5;
            overflow: hidden;
            transition: transform 0.6s ease;
        }
        .event-type-card:hover .event-type-image { transform: scale(1.04); }
        .event-type-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.4) 100%);
            transition: opacity 0.35s ease;
        }
        .event-type-card:hover .event-type-image::after { opacity: 0.7; }
        .event-type-image .image-icon-overlay {
            position: absolute;
            bottom: 12px; left: 12px;
            width: 40px; height: 40px;
            border-radius: 12px;
            background: rgba(193,124,96,0.9);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 18px;
            z-index: 1;
            transition: all 0.35s ease;
        }
        .event-type-card:hover .event-type-image .image-icon-overlay {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            transform: scale(1.15) rotate(-8deg);
            box-shadow: 0 8px 20px rgba(193,124,96,0.4);
        }
        .event-type-body { padding: 20px 20px 24px; }
        .event-type-body h4 {
            font-size: 17px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }
        .event-type-body p {
            font-size: 13px;
            color: #8a8a9a;
            line-height: 1.5;
        }

        /* ================================================================
           PHOTOBOOTH
           ================================================================ */
        .photobooth-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            background: linear-gradient(135deg, rgba(193,124,96,0.05), rgba(212,165,116,0.08));
            border: 2px solid rgba(193,124,96,0.15);
            border-radius: 24px;
            padding: 40px;
            margin-top: 8px;
        }
        .photobooth-image {
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 4/5;
            background: #f0ebe5;
            position: relative;
            box-shadow: 0 12px 40px rgba(193,124,96,0.15);
            opacity: 0;
            transform: translateX(-30px) scale(0.96);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .photobooth-image.visible { opacity: 1; transform: translateX(0) scale(1); }
        .photobooth-image img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .photobooth-image:hover img { transform: scale(1.05); }
        .photobooth-image .image-badge {
            position: absolute;
            top: 16px; left: 16px;
            background: rgba(193,124,96,0.95);
            color: white;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
            animation: pulseSoft 3s ease-in-out infinite;
        }
        .photobooth-content {
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .photobooth-content.visible { opacity: 1; transform: translateX(0); }
        .photobooth-content .photobooth-icon {
            font-size: 48px;
            color: #c17c60;
            margin-bottom: 12px;
            display: block;
            animation: floatUp 3.5s ease-in-out infinite;
        }
        .photobooth-content h3 {
            font-family: Georgia, serif;
            font-size: 32px;
            font-weight: 400;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        .photobooth-content > p {
            color: #6a6a7a;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.7;
        }
        .photobooth-features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .photobooth-features span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.7);
            border-radius: 100px;
            font-size: 12px;
            color: #3a3a4a;
            border: 1px solid rgba(234,227,220,0.4);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .photobooth-features span:hover {
            background: white;
            border-color: #c17c60;
            transform: translateY(-2px);
        }
        .photobooth-features span i { color: #c17c60; font-size: 12px; }
        .btn-photobooth {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(193,124,96,0.25);
        }
        .btn-photobooth:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 32px rgba(193,124,96,0.4);
            color: white;
        }

        /* ================================================================
           FLOOR PLAN
           ================================================================ */
        .floorplan-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        .floorplan-image {
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 4/3;
            background: #f0ebe5;
            position: relative;
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
            opacity: 0;
            transform: translateX(-30px) scale(0.96);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .floorplan-image.visible { opacity: 1; transform: translateX(0) scale(1); }
        .floorplan-image img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .floorplan-image:hover img { transform: scale(1.05); }
        .floorplan-image .image-label {
            position: absolute;
            bottom: 16px; left: 16px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            color: white;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .floorplan-image .image-label i { color: #c17c60; }
        .floor-plan-container {
            position: relative;
            width: 100%;
            background: rgba(255,255,255,0.4);
            border-radius: 20px;
            padding: 30px;
            border: 2px dashed rgba(193,124,96,0.2);
            min-height: 400px;
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .floor-plan-container.visible { opacity: 1; transform: translateX(0); }
        .floor-plan {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }
        .floor-plan .stage {
            width: 60%;
            padding: 16px;
            background: rgba(193,124,96,0.15);
            border-radius: 12px;
            text-align: center;
            color: #c17c60;
            font-weight: 600;
            font-size: 13px;
            border: 2px solid rgba(193,124,96,0.2);
        }
        .floor-plan .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 16px;
            width: 100%;
        }
        .floor-plan .table-item {
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            padding: 12px 10px;
            text-align: center;
            border: 2px solid rgba(234,227,220,0.5);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        .floor-plan .table-item.visible { opacity: 1; transform: translateY(0); }
        .floor-plan .table-item:hover {
            border-color: #c17c60;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .floor-plan .table-item .table-icon {
            font-size: 24px;
            color: #c17c60;
            margin-bottom: 4px;
        }
        .floor-plan .table-item .table-name {
            font-weight: 600;
            font-size: 12px;
            color: #1a1a1a;
        }
        .floor-plan .table-item .table-guests {
            font-size: 10px;
            color: #8a8a9a;
        }
        .floor-plan .table-item.occupied {
            border-color: #f59e0b;
            background: rgba(245,158,11,0.08);
        }
        .floor-plan .table-item.full {
            border-color: #ef4444;
            background: rgba(239,68,68,0.08);
        }
        .status-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .status-dot.available { background: #10b981; }
        .status-dot.occupied { background: #f59e0b; }
        .status-dot.full { background: #ef4444; }

        /* ================================================================
           SPLASH EN TEMPS RÉEL — FIDÈLE À LA CAPTURE
           ================================================================ */
        .splash-preview {
            position: relative;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 40px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 50px 40px;
            overflow: hidden;
            min-height: 650px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 32px;
        }
        .splash-preview::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(2px 2px at 10% 20%, rgba(255,255,255,0.4), transparent),
                radial-gradient(2px 2px at 30% 70%, rgba(255,215,0,0.3), transparent),
                radial-gradient(2px 2px at 70% 30%, rgba(255,192,203,0.4), transparent),
                radial-gradient(2px 2px at 85% 80%, rgba(255,255,255,0.3), transparent),
                radial-gradient(1px 1px at 50% 50%, rgba(255,255,255,0.2), transparent),
                radial-gradient(2px 2px at 15% 85%, rgba(255,215,0,0.4), transparent),
                radial-gradient(1px 1px at 90% 15%, rgba(255,192,203,0.3), transparent);
            pointer-events: none;
        }
        .splash-preview-left,
        .splash-preview-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 10px;
        }
        .splash-preview-right { justify-content: flex-start; padding-top: 0; }

        .splash-hosts {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .splash-hosts .heart-icon {
            color: #ff4d6d;
            font-size: 22px;
            animation: pulseSoft 2s ease-in-out infinite;
        }
        .hosts-name {
            font-family: 'Great Vibes', cursive;
            font-size: 42px;
            background: linear-gradient(135deg, #f5d76e, #f0a500, #ffdd57);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 400;
            letter-spacing: 1px;
            text-shadow: 0 0 30px rgba(245, 215, 110, 0.3);
        }

        .splash-hosts-circle {
            position: relative;
            width: 320px;
            height: 320px;
            margin-bottom: 30px;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .splash-hosts-circle.animate {
            animation: hostAppear 0.9s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .splash-hosts-circle .circle-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            padding: 8px;
            background: linear-gradient(135deg, #d4a017, #f5d76e, #d4a017);
            box-shadow:
                0 0 40px rgba(212, 160, 23, 0.5),
                0 0 80px rgba(212, 160, 23, 0.2),
                inset 0 0 30px rgba(0,0,0,0.3);
            animation: floatUp 6s ease-in-out infinite;
        }
        .splash-hosts-circle .circle-inner img,
        .splash-hosts-circle .circle-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .splash-hosts-circle .circle-placeholder {
            background: linear-gradient(135deg, #2d1b3d, #1a1a2e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4a017;
            font-size: 80px;
        }
        .splash-hosts-circle .circle-dots {
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }
        .splash-hosts-circle .circle-dots .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        .splash-hosts-circle .circle-dots .dot.active {
            background: #d4a017;
            width: 24px;
            border-radius: 4px;
        }

        .splash-welcome-text {
            font-style: italic;
            font-size: 16px;
            line-height: 1.7;
            color: #e0e0e0;
            margin-bottom: 20px;
            max-width: 380px;
            font-family: Georgia, serif;
        }
        .splash-welcome-text .text-heart {
            color: #ff4d6d;
            font-size: 14px;
            margin: 0 4px;
            animation: pulseSoft 2s ease-in-out infinite;
        }
        .splash-event-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 8px 18px;
            border-radius: 100px;
            color: #d4a017;
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }
        .splash-event-info i { color: #f5d76e; }
        .splash-divider {
            width: 60%;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(212, 160, 23, 0.6), transparent);
            margin-top: 24px;
        }
        .splash-position {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 12px;
            color: #d4a017;
            font-weight: 700;
            font-size: 15px;
        }
        .splash-position .position-active { font-size: 18px; color: #f5d76e; }
        .splash-separator {
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.2), transparent);
            align-self: stretch;
        }

        .splash-welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(212, 160, 23, 0.15), rgba(245, 215, 110, 0.1));
            border: 2px solid #d4a017;
            padding: 10px 32px;
            border-radius: 100px;
            color: #f5d76e;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 4px;
            margin-bottom: 30px;
            box-shadow:
                0 0 20px rgba(212, 160, 23, 0.4),
                inset 0 0 20px rgba(212, 160, 23, 0.1);
            animation: pulseSoft 3s ease-in-out infinite;
        }
        .splash-welcome-badge .star { color: #f5d76e; font-size: 20px; }
        .splash-welcome-badge .badge-text {
            background: linear-gradient(135deg, #f5d76e, #ffffff, #f5d76e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .guest-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 32px 28px;
            width: 100%;
            max-width: 380px;
            backdrop-filter: blur(12px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .guest-card.animate {
            animation: guestAppear 1s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .guest-photo-circle {
            width: 200px;
            height: 200px;
            margin: 0 auto 24px;
            border-radius: 50%;
            padding: 6px;
            background: linear-gradient(135deg, #d4a017, #f5d76e, #d4a017);
            box-shadow: 0 0 30px rgba(212, 160, 23, 0.5);
            position: relative;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .guest-photo-circle::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 1px solid rgba(212, 160, 23, 0.3);
            animation: pulseSoft 2.5s ease-in-out infinite;
        }
        .guest-photo-circle img,
        .guest-photo-circle .guest-placeholder {
            width: 100%; height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .guest-photo-circle .guest-placeholder {
            background: linear-gradient(135deg, #2d1b3d, #1a1a2e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4a017;
            font-size: 60px;
        }
        .guest-fullname {
            font-family: Georgia, serif;
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 2px;
            margin-bottom: 12px;
            text-transform: uppercase;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .guest-event-detail {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            color: #a0a0a0;
            margin-bottom: 18px;
        }
        .guest-event-detail i { color: #d4a017; }
        .guest-event-detail .dot-sep { color: #666; }
        .guest-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 8px 24px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
        }
        .guest-status-badge i { font-size: 16px; }

        .splash-position-right {
            margin-top: 24px;
            color: #d4a017;
            font-weight: 700;
            font-size: 14px;
        }
        .splash-position-right .position-active { font-size: 18px; color: #f5d76e; }
        .splash-update-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            color: #a0a0a0;
            font-size: 12px;
        }
        .splash-update-btn i { color: #d4a017; animation: spin 3s linear infinite; }

        .floating-hearts {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .floating-hearts .heart-particle {
            position: absolute;
            color: #ff4d6d;
            font-size: 14px;
            opacity: 0;
            animation: floatHeart 6s ease-in infinite;
        }

        /* Overlay scan */
        .scan-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 10;
            border-radius: 24px;
        }
        .scan-overlay.active { display: flex; }
        .scan-overlay .scan-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4a017, #f5d76e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            box-shadow: 0 0 40px rgba(212, 160, 23, 0.6);
            animation: pulseSoft 1.5s ease-in-out infinite;
        }
        .scan-overlay .scan-text {
            color: #f5d76e;
            font-size: 18px;
            font-weight: 600;
            margin-top: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .scan-overlay .scan-progress {
            width: 240px;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            margin-top: 16px;
            overflow: hidden;
        }
        .scan-overlay .scan-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #d4a017, #f5d76e);
            width: 0%;
            border-radius: 4px;
            transition: width 1.2s ease;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 12px 24px;
            background: rgba(17, 17, 25, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: fadeInUp 0.6s ease-out both;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 700;
            font-size: 18px;
            text-decoration: none;
        }
        .navbar-brand img {
            height: 36px; width: auto;
            border-radius: 8px;
            object-fit: contain;
        }
        .navbar-brand .brand-icon {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            width: 36px; height: 36px;
            display: grid; place-items: center;
            border-radius: 10px;
            color: white;
            font-size: 18px;
        }
        .navbar-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }
        .navbar-links a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }
        .navbar-links a:hover,
        .navbar-links a.active { color: white; }
        .navbar-links a::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0;
            width: 0; height: 2px;
            background: #c17c60;
            transition: width 0.3s ease;
        }
        .navbar-links a:hover::after,
        .navbar-links a.active::after { width: 100%; }
        .navbar-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .navbar-toggle { display: block; }
            .navbar-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%; left: 0; right: 0;
                background: rgba(17,17,25,0.98);
                backdrop-filter: blur(16px);
                padding: 20px 24px;
                gap: 16px;
                border-bottom: 1px solid rgba(255,255,255,0.06);
            }
            .navbar-links.open { display: flex; }
        }

        /* ================================================================
           FEATURES
           ================================================================ */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 32px 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            opacity: 0;
            transform: translateY(30px) scale(0.96);
            transition: opacity 0.6s ease, transform 0.6s ease,
                        box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .feature-card.visible { opacity: 1; transform: translateY(0) scale(1); }
        .feature-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border-color: rgba(193,124,96,0.3);
        }
        .feature-card .icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, rgba(193,124,96,0.12), rgba(212,165,116,0.08));
            border-radius: 16px;
            display: grid; place-items: center;
            margin: 0 auto 16px;
            font-size: 24px;
            color: #c17c60;
            transition: all 0.4s ease;
        }
        .feature-card:hover .icon {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            transform: scale(1.1) rotate(-6deg);
            box-shadow: 0 8px 20px rgba(193,124,96,0.3);
        }
        .feature-card h4 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .feature-card p {
            color: #6a6a7a;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ================================================================
           PRICING
           ================================================================ */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        .pricing-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 32px 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            opacity: 0;
            transform: translateY(30px) scale(0.96);
            transition: opacity 0.6s ease, transform 0.6s ease,
                        box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .pricing-card.visible { opacity: 1; transform: translateY(0) scale(1); }
        .pricing-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
        }
        .pricing-card.featured {
            border-color: #c17c60;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 8px 32px rgba(193,124,96,0.1);
        }
        .pricing-card .price {
            font-family: Georgia, serif;
            font-size: 3rem;
            font-weight: 400;
            color: #1a1a1a;
        }
        .pricing-card .price span {
            font-size: 1.2rem;
            color: #8a8a9a;
        }
        .pricing-card .plan-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .pricing-card ul {
            list-style: none;
            padding: 0;
            margin: 16px 0 24px;
        }
        .pricing-card ul li {
            padding: 6px 0;
            color: #5a5a6a;
            font-size: 14px;
        }
        .pricing-card ul li i {
            color: #10b981;
            margin-right: 8px;
            font-size: 14px;
        }
        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(193,124,96,0.25);
        }
        .btn-gold:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 28px rgba(193,124,96,0.4);
            color: white;
        }

        /* ================================================================
           CONTACT
           ================================================================ */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
        }
        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; gap: 32px; }
        }
        .contact-info-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .contact-info-item.visible { opacity: 1; transform: translateX(0); }
        .contact-info-item .icon {
            width: 44px; height: 44px;
            background: rgba(193,124,96,0.08);
            border-radius: 12px;
            display: grid; place-items: center;
            color: #c17c60;
            font-size: 18px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .contact-info-item:hover .icon {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            transform: scale(1.1) rotate(-6deg);
        }
        .contact-info-item .text {
            font-size: 14px;
            color: #3a3a4a;
        }
        .contact-info-item .text strong {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
        }
        .contact-info-item .text a {
            color: #3a3a4a;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .contact-info-item .text a:hover { color: #c17c60; }
        .contact-form {
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .contact-form.visible { opacity: 1; transform: translateX(0); }
        .contact-form input,
        .contact-form textarea,
        .contact-form select {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid rgba(234, 227, 220, 0.5);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            background: rgba(255,255,255,0.6);
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }
        .contact-form input:focus,
        .contact-form textarea:focus,
        .contact-form select:focus {
            outline: none;
            border-color: #c17c60;
            box-shadow: 0 0 0 4px rgba(193,124,96,0.08);
            transform: translateY(-1px);
        }
        .contact-form textarea {
            height: 120px;
            resize: vertical;
        }
        .contact-form .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .contact-form .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(193,124,96,0.4);
        }

        /* ================================================================
           FOOTER
           ================================================================ */
        .footer {
            text-align: center;
            padding: 40px 20px;
            color: #8a8a9a;
            font-size: 13px;
            border-top: 1px solid rgba(234, 227, 220, 0.3);
            margin-top: 40px;
            animation: fadeInUp 0.8s ease-out both;
        }
        .footer strong { color: #1a1a1a; }
        .footer .heart { color: #c17c60; animation: pulseSoft 2s ease-in-out infinite; }
        .footer .social-links-footer {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
        }
        .footer .social-links-footer a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            border-radius: 50%;
            background: rgba(193,124,96,0.08);
            color: #8a8a9a;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .footer .social-links-footer a:hover {
            background: rgba(193,124,96,0.15);
            color: #c17c60;
            transform: translateY(-4px) scale(1.1);
        }

        /* ================================================================
           SÉLECTEUR DÉMO
           ================================================================ */
        .splash-demo-selector {
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 32px 24px;
            border: 2px dashed rgba(193,124,96,0.2);
            text-align: center;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .splash-demo-selector.visible { opacity: 1; transform: translateY(0); }
        .splash-demo-selector .demo-guest {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            margin: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(234,227,220,0.3);
        }
        .splash-demo-selector .demo-guest:hover {
            border-color: #c17c60;
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 8px 20px rgba(193,124,96,0.15);
        }
        .splash-demo-selector .demo-guest .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c17c60, #d4a574);
            display: grid;
            place-items: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.3s ease;
        }
        .splash-demo-selector .demo-guest:hover .avatar { transform: rotate(-8deg) scale(1.1); }
        .splash-demo-selector .demo-guest .name {
            font-weight: 500;
            font-size: 14px;
            color: #1a1a1a;
        }

        /* ================================================================
           RESPONSIVE
           ================================================================ */
        @media (max-width: 992px) {
            .photobooth-layout,
            .floorplan-layout {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .photobooth-image { aspect-ratio: 16/9; }
            .splash-preview {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 30px 20px;
            }
            .splash-separator { display: none; }
            .hosts-name { font-size: 32px; }
            .splash-hosts-circle { width: 240px; height: 240px; }
            .guest-fullname { font-size: 24px; }
            .splash-welcome-badge { font-size: 14px; padding: 8px 20px; letter-spacing: 3px; }
        }
        @media (max-width: 768px) {
            .hero { padding: 100px 20px 60px; }
            .section { padding: 40px 16px; }
            .section-glass { padding: 32px 20px; }
            .features-grid,
            .pricing-grid { grid-template-columns: 1fr; }
            .event-types-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
            .floor-plan-container { padding: 16px; }
            .hero-logo img { max-width: 90px; }
            .photobooth-layout { padding: 24px; }
            .photobooth-content h3 { font-size: 24px; }
        }
        @media (max-width: 480px) {
            .event-types-grid { grid-template-columns: 1fr; }
            .hosts-name { font-size: 26px; }
            .splash-hosts-circle { width: 200px; height: 200px; }
            .guest-photo-circle { width: 150px; height: 150px; }
            .guest-fullname { font-size: 20px; }
            .splash-welcome-badge { font-size: 12px; letter-spacing: 2px; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar">
    <a href="#" class="navbar-brand">
        <?php if (!empty($logoPath) && (file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath) || file_exists(__DIR__ . '/..' . $logoPath))): ?>
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?>">
        <?php else: ?>
            <span class="brand-icon"><i class="fas fa-star"></i></span>
        <?php endif; ?>
        <?php echo htmlspecialchars($appName); ?>
    </a>
    <button class="navbar-toggle" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </button>
    <ul class="navbar-links" id="navLinks">
        <li><a href="#eventtypes" class="active">Événements</a></li>
        <li><a href="#features">Fonctionnalités</a></li>
        <li><a href="#photobooth">Photobooth</a></li>
        <li><a href="#floorplan">Plan de salle</a></li>
        <li><a href="#splashdemo">Accueil</a></li>
        <li><a href="#pricing">Tarifs</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="invitation_demo.php" class="btn-primary" style="padding:8px 20px;font-size:13px;">Invitation</a></li>
    </ul>
</nav>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-bg-image" style="background-image: url('<?php echo htmlspecialchars($baseImagePath . '/galerie/hero-bg.jpg'); ?>');"></div>
    <div class="hero-content">
        <div class="hero-logo">
            <?php if (!empty($logoPath) && (file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath) || file_exists(__DIR__ . '/..' . $logoPath))): ?>
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?>">
            <?php else: ?>
                <div class="logo-placeholder"><i class="fas fa-star"></i></div>
            <?php endif; ?>
        </div>

        <div class="hero-badge">
            <i class="fas fa-sparkles"></i> 
            L’excellence au service de vos événements
        </div>
        <h1>Vos événements méritent <span>l'excellence</span></h1>
        <p>Avec <strong>MdlEvent</strong>, donnez une nouvelle dimension à vos événements grâce à une expérience événementielle moderne, élégante et digitale.</p>

        <div class="social-links">
            <a href="https://wa.me/243963967028" target="_blank" class="whatsapp" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="https://facebook.com/MdlEvent" target="_blank" class="facebook" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="mailto:mdlevent@gmail.com" class="email" title="Email">
                <i class="fas fa-envelope"></i>
            </a>
            <a href="tel:+243963967028" class="phone" title="Appel">
                <i class="fas fa-phone"></i>
            </a>
        </div>
    </div>
</section>

<!-- ===== TYPES D'ÉVÉNEMENTS ===== -->
<section class="section" id="eventtypes">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-calendar-star"></i> Nos domaines d'expertise</div>
            <h2>Les événements que nous couvrons</h2>
            <p>MdlEvent accompagne tous types d'événements, qu'ils soient personnels, professionnels ou culturels.</p>
        </div>
        <div class="event-types-grid">
            <?php foreach ($eventTypes as $type):
                $imageUrl = imgUrl($type['image'], $baseImagePath);
            ?>
                <div class="event-type-card">
                    <div class="event-type-image"
                         style="<?php echo $imageUrl ? "background-image: url('" . htmlspecialchars($imageUrl) . "');" : ""; ?>">
                        <div class="image-icon-overlay">
                            <i class="fas <?php echo htmlspecialchars($type['icon']); ?>"></i>
                        </div>
                    </div>
                    <div class="event-type-body">
                        <h4><?php echo htmlspecialchars($type['label']); ?></h4>
                        <p><?php echo htmlspecialchars($type['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="section" id="features">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-cubes"></i> Fonctionnalités</div>
            <h2>Des fonctionnalités conçues pour vous</h2>
            <p>Des outils puissants pour répondre à toutes vos attentes</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h4>Gestion des invités</h4>
                <p>Gérez très simplement la liste des invités de votre événement avec des outils puissants.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fas fa-qrcode"></i></div>
                <h4>Invitations numériques avec Code QR</h4>
                <p>Intégrez vos propres conceptions d'invitations et obtenez des invitations avec Code QR.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fas fa-share-alt"></i></div>
                <h4>Distribution automatique</h4>
                <p>Distribution des invitations via WhatsApp, email ou directement dans l'application.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fas fa-map"></i></div>
                <h4>Plan de salle cartographié</h4>
                <p>Visualisez et gérez la disposition des tables et des sièges pour vos invités.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fas fa-user-check"></i></div>
                <h4>Accueil personnalisé des invités</h4>
                <p>Écran splash d'accueil avec diaporama, album, mot de bienvenue et QR invite.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <h4>Check-in digital</h4>
                <p>Contrôle d'accès rapide et sécurisé par scan du Code QR le jour de l'événement.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== PHOTOBOOTH ===== -->
<section class="section" id="photobooth">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-camera-retro"></i> Location</div>
            <h2>Photobooth 360</h2>
            <p>Immortalisez vos moments précieux avec une expérience photo inédite</p>
        </div>

        <div class="photobooth-layout">
            <div class="photobooth-image">
                <?php $photoImg = imgUrl('galerie/photobooth-1.jpeg', $baseImagePath); ?>
                <?php if ($photoImg): ?>
                    <img src="<?php echo htmlspecialchars($photoImg); ?>" alt="Photobooth 360">
                <?php endif; ?>
                <div class="image-badge"><i class="fas fa-camera"></i> Live 360°</div>
            </div>
            <div class="photobooth-content">
                <span class="photobooth-icon"><i class="fas fa-camera"></i></span>
                <h3>Photobooth 360°</h3>
                <p>Offrez à vos invités une expérience photo unique avec notre cabine Photobooth 360°. Des souvenirs inoubliables capturés sous tous les angles !</p>
                <div class="photobooth-features">
                    <span><i class="fas fa-video"></i> Vidéo 360°</span>
                    <span><i class="fas fa-images"></i> Photos HD</span>
                    <span><i class="fas fa-palette"></i> Personnalisation</span>
                    <span><i class="fas fa-share-alt"></i> Partage</span>
                    <span><i class="fas fa-print"></i> Impression</span>
                    <span><i class="fas fa-smile"></i> Accessoires</span>
                </div>
                <a href="#contact" class="btn-photobooth">
                    <i class="fas fa-calendar-check"></i> Réserver maintenant
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===== FLOOR PLAN ===== -->
<section class="section" id="floorplan">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-map"></i> Plan de salle</div>
            <h2>Cartographiez votre événement</h2>
            <p>Visualisez la disposition des tables et gérez les places disponibles</p>
        </div>

        <div class="floorplan-layout">
            <div class="floorplan-image">
                <?php $salleImg = imgUrl('galerie/salle-plan.png', $baseImagePath); ?>
                <?php if ($salleImg): ?>
                    <img src="<?php echo htmlspecialchars($salleImg); ?>" alt="Salle d'événement">
                <?php endif; ?>
                <div class="image-label">
                    <i class="fas fa-map-marker-alt"></i> Vue réelle de la salle
                </div>
            </div>
            <div class="floor-plan-container">
                <div class="floor-plan">
                    <div class="stage"><i class="fas fa-microphone-alt"></i> Scène / Estrade</div>
                    <div class="tables-grid">
                        <div class="table-item"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T1</div><div class="table-guests"><span class="status-dot available"></span> 4/8</div></div>
                        <div class="table-item occupied"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T2</div><div class="table-guests"><span class="status-dot occupied"></span> 6/8</div></div>
                        <div class="table-item full"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T3</div><div class="table-guests"><span class="status-dot full"></span> 8/8</div></div>
                        <div class="table-item"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T4</div><div class="table-guests"><span class="status-dot available"></span> 2/8</div></div>
                        <div class="table-item occupied"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T5</div><div class="table-guests"><span class="status-dot occupied"></span> 5/8</div></div>
                        <div class="table-item"><div class="table-icon"><i class="fas fa-circle"></i></div><div class="table-name">T6</div><div class="table-guests"><span class="status-dot available"></span> 3/8</div></div>
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:center;gap:16px;flex-wrap:wrap;font-size:12px;color:#6a6a7a;">
                    <span><span class="status-dot available"></span> Disponible</span>
                    <span><span class="status-dot occupied"></span> Partiel</span>
                    <span><span class="status-dot full"></span> Complète</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== SPLASH EN TEMPS RÉEL ===== -->
<section class="section" id="splashdemo">
    <div class="section-glass" style="padding: 40px 20px;">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-user-check"></i> Accueil des invités</div>
            <h2>Écran splash en temps réel</h2>
            <p>Quand un invité arrive, son QR code est scanné et ses informations s'affichent instantanément</p>
        </div>

        <!-- ==== APERÇU DE L'ÉCRAN SPLASH ==== -->
        <div class="splash-preview" id="splashPreview">

            <!-- GAUCHE : Hôtes / Couple -->
            <div class="splash-preview-left">
                <div class="floating-hearts" id="floatingHeartsLeft"></div>

                <div class="splash-hosts">
                    <i class="fas fa-heart heart-icon"></i>
                    <span class="hosts-name" id="splashHostsName"><?php echo htmlspecialchars($firstGuest['hosts']); ?></span>
                </div>

                <div class="splash-hosts-circle" id="hostsCircle">
                    <div class="circle-inner">
                        <img src="<?php echo htmlspecialchars($baseImagePath . '/' . $firstGuest['hostsImg']); ?>"
                             alt="Hôtes"
                             id="hostsImage"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="circle-placeholder" style="display:none;"><i class="fas fa-heart"></i></div>
                    </div>
                    <div class="circle-dots">
                        <span class="dot"></span>
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>

                <p class="splash-welcome-text">
                    <i class="fas fa-heart text-heart"></i>
                    Nous sommes ravis de vous accueillir<br>
                    pour ce moment unique et inoubliable.
                    <i class="fas fa-heart text-heart"></i>
                </p>

                <div class="splash-event-info">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="splashEventInfo"><?php echo htmlspecialchars($firstGuest['eventInfo']); ?></span>
                </div>

                <div class="splash-divider"></div>

                <div class="splash-position">
                    <span class="position-active" id="splashPositionCurrent"><?php echo (int)$firstGuest['position']; ?></span>
                    <span class="position-total">3</span>
                </div>
            </div>

            <!-- SÉPARATEUR -->
            <div class="splash-separator"></div>

            <!-- DROITE : Invité qui vient d'arriver -->
            <div class="splash-preview-right">
                <div class="floating-hearts" id="floatingHeartsRight"></div>

                <div class="splash-welcome-badge">
                    <span class="star">★</span>
                    <span class="badge-text">BIENVENUE</span>
                    <span class="star">★</span>
                </div>

                <div class="guest-card" id="guestCard">
                    <div class="guest-photo-circle" id="guestPhotoCircle">
                        <img src="<?php echo htmlspecialchars($baseImagePath . '/' . $firstGuest['guestImg']); ?>"
                             alt="Invité"
                             id="guestImage"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="guest-placeholder" style="display:none;"><i class="fas fa-user"></i></div>
                    </div>

                    <h3 class="guest-fullname" id="guestFullname"><?php echo htmlspecialchars($firstGuest['name']); ?></h3>

                    <div class="guest-event-detail" id="guestEventDetail">
                        <i class="fas fa-calendar-check"></i>
                        <span id="guestHostsName"><?php echo htmlspecialchars($firstGuest['hosts']); ?></span>
                        <span class="dot-sep">•</span>
                        <span id="guestPersons"><?php echo htmlspecialchars($firstGuest['persons']); ?></span>
                    </div>

                    <div class="guest-status-badge" id="guestStatusBadge">
                        <i class="fas fa-check-circle"></i>
                        <span id="guestStatusText"><?php echo htmlspecialchars($firstGuest['status']); ?></span>
                    </div>
                </div>

                <div class="splash-position-right">
                    <span class="position-active" id="splashPositionCurrentRight"><?php echo (int)$firstGuest['position']; ?></span>
                    <span class="position-total">/ 3</span>
                </div>

                <div class="splash-update-btn">
                    <i class="fas fa-sync-alt"></i>
                    <span>Mise à jour dans <span id="splashCountdown">0</span>s</span>
                </div>
            </div>

            <!-- OVERLAY SCAN -->
            <div class="scan-overlay" id="scanOverlay">
                <div class="scan-circle">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="scan-text">Scan du QR code...</div>
                <div class="scan-progress"><div class="scan-progress-bar" id="scanProgressBar"></div></div>
            </div>
        </div>

        <!-- ==== SIMULATEUR DE SCAN ==== -->
        <div class="splash-demo-selector">
            <p style="color:#6a6a7a;margin-bottom:16px;font-size:14px;">
                <i class="fas fa-info-circle" style="color:#c17c60;"></i> 
                Simulez le scan du QR code d'un invité qui vient d'arriver
            </p>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;">
                <?php foreach ($demoGuests as $guest):
                    $initials = '';
                    $parts = explode(' ', $guest['name']);
                    foreach ($parts as $p) { $initials .= mb_substr($p, 0, 1); }
                    $initials = mb_substr($initials, 0, 2);
                ?>
                    <div class="demo-guest"
                         data-guest='<?php echo htmlspecialchars(json_encode([
                            "name"       => $guest["name"],
                            "hosts"      => $guest["hosts"],
                            "persons"    => $guest["persons"],
                            "status"     => $guest["status"],
                            "hostsImg"   => $baseImagePath . "/" . $guest["hostsImg"],
                            "guestImg"   => $baseImagePath . "/" . $guest["guestImg"],
                            "eventInfo"  => $guest["eventInfo"],
                            "position"   => $guest["position"],
                         ]), ENT_QUOTES, 'UTF-8'); ?>'
                         onclick="simulateScanFromButton(this)">
                        <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <div>
                            <div class="name"><?php echo htmlspecialchars(ucwords(strtolower($guest['name']))); ?></div>
                            <div style="font-size:11px;color:#8a8a9a;"><?php echo htmlspecialchars($guest['hosts']); ?> • <?php echo htmlspecialchars($guest['persons']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== PRICING ===== -->
<section class="section" id="pricing">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-tag"></i> Tarifs</div>
            <h2>Un seul prix, une seule excellence</h2>
            <p>0,6$ par invitation — modulable selon le nombre d'invités</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card featured">
                <div style="display:inline-block;background:rgba(193,124,96,0.12);color:#c17c60;padding:4px 14px;border-radius:100px;font-size:12px;font-weight:600;margin-bottom:12px;">Offre Unique</div>
                <div class="plan-name">MdlEvent Standard</div>
                <div class="price">$0.60 <span>/ invitation</span></div>
                <ul>
                    <li><i class="fas fa-check"></i> Invités illimités</li>
                    <li><i class="fas fa-check"></i> Invitations avec QR Code</li>
                    <li><i class="fas fa-check"></i> Plan de salle avancé</li>
                    <li><i class="fas fa-check"></i> Écran splash avec diaporama</li>
                    <li><i class="fas fa-check"></i> Check-in digital</li>
                    <li><i class="fas fa-check"></i> Distribution WhatsApp & Email</li>
                    <li><i class="fas fa-check"></i> Modulable selon le nombre d'invités</li>
                    <li><i class="fas fa-check"></i> Location Photobooth 360 disponible</li>
                </ul>
                <a href="#contact" class="btn-gold">Commencer maintenant</a>
            </div>
        </div>
        <div style="text-align:center;margin-top:24px;font-size:13px;color:#8a8a9a;">
            <a href="https://wa.me/243963967028" target="_blank" style="color:#25D366;text-decoration:none;font-weight:600;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <span style="margin:0 12px;">•</span>
            <a href="tel:+243963967028" style="color:#c17c60;text-decoration:none;font-weight:600;">
                <i class="fas fa-phone"></i> +243 963 967 028
            </a>
            <span style="margin:0 12px;">•</span>
            <a href="mailto:mdlevent@gmail.com" style="color:#c17c60;text-decoration:none;font-weight:600;">
                <i class="fas fa-envelope"></i> mdlevent@gmail.com
            </a>
        </div>
    </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="section" id="contact">
    <div class="section-glass">
        <div class="section-title">
            <div class="subtitle"><i class="fas fa-envelope"></i> Contact</div>
            <h2>Vous préparez un événement ?</h2>
            <p>Nous sommes là pour le rendre inoubliable.</p>
        </div>
        <div class="contact-grid">
            <div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-phone"></i></div>
                    <div class="text">
                        <strong>Appel Téléphonique</strong>
                        <a href="tel:+243963967028">+243 963 967 028</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fab fa-whatsapp"></i></div>
                    <div class="text">
                        <strong>WhatsApp</strong>
                        <a href="https://wa.me/243963967028" target="_blank">+243 963 967 028</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fab fa-facebook"></i></div>
                    <div class="text">
                        <strong>Facebook</strong>
                        <a href="https://facebook.com/MdlEvent" target="_blank">MdlEvent</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div class="text">
                        <strong>Email</strong>
                        <a href="mailto:mdlevent@gmail.com">mdlevent@gmail.com</a>
                    </div>
                </div>
                <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(234,227,220,0.3);">
                    <p style="font-size:13px;color:#6a6a7a;">
                        <strong style="color:#1a1a1a;">Votre événement. Votre vision. Notre expertise.</strong>
                    </p>
                    <p style="font-size:13px;color:#6a6a7a;margin-top:4px;">
                        <i class="fas fa-camera" style="color:#c17c60;"></i> 
                        Location Photobooth 360 disponible sur demande
                    </p>
                </div>
            </div>
            <div>
                <form class="contact-form" onsubmit="event.preventDefault(); alert('Message envoyé ! Nous vous contacterons rapidement.');">
                    <input type="text" placeholder="Votre nom" required>
                    <input type="email" placeholder="Votre email" required>
                    <input type="text" placeholder="Type d'événement (Mariage, Anniversaire, etc.)">
                    <select>
                        <option value="">Service souhaité...</option>
                        <option value="invitations">Invitations digitales</option>
                        <option value="photobooth">Photobooth 360</option>
                        <option value="both">Invitations + Photobooth</option>
                        <option value="other">Autre</option>
                    </select>
                    <textarea placeholder="Décrivez votre projet..." required></textarea>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <p>
        <i class="fas fa-heart heart"></i>
        © Copyright <?php echo date('Y'); ?> <strong><?php echo htmlspecialchars($appName); ?></strong> Tous Droits Réservés
    </p>
    <p style="margin-top:4px;font-size:12px;color:#b8a99c;">
        Votre événement. Votre vision. Notre expertise.
    </p>
    <p style="margin-top:4px;font-size:12px;color:#b8a99c;">
        <i class="fas fa-camera" style="color:#c17c60;"></i> Location Photobooth 360 disponible
    </p>
    <div class="social-links-footer">
        <a href="https://wa.me/243963967028" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        <a href="https://facebook.com/MdlEvent" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="mailto:mdlevent@gmail.com" title="Email"><i class="fas fa-envelope"></i></a>
        <a href="tel:+243963967028" title="Appel"><i class="fas fa-phone"></i></a>
    </div>
</footer>

<script>
// ================================================================
// MENU MOBILE
// ================================================================
function toggleMenu() {
    document.getElementById('navLinks').classList.toggle('open');
}

// ================================================================
// ACTIVE NAV LINK
// ================================================================
document.querySelectorAll('.navbar-links a').forEach(link => {
    link.addEventListener('click', function() {
        document.querySelectorAll('.navbar-links a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('navLinks').classList.remove('open');
    });
});

// ================================================================
// CŒURS FLOTTANTS
// ================================================================
function generateFloatingHearts(containerId, count) {
    const container = document.getElementById(containerId);
    if (!container) return;
    for (let i = 0; i < count; i++) {
        const heart = document.createElement('i');
        heart.className = 'fas fa-heart heart-particle';
        heart.style.left = Math.random() * 90 + '%';
        heart.style.top = (60 + Math.random() * 40) + '%';
        heart.style.animationDelay = (Math.random() * 6) + 's';
        heart.style.fontSize = (8 + Math.random() * 10) + 'px';
        heart.style.color = ['#ff4d6d', '#ff6b9d', '#ffd700'][Math.floor(Math.random() * 3)];
        container.appendChild(heart);
    }
}

// ================================================================
// SIMULATION DE SCAN (le cœur de la logique)
// ================================================================
function simulateScanFromButton(btn) {
    try {
        const data = JSON.parse(btn.getAttribute('data-guest'));
        simulateScan(data);
    } catch (e) {
        console.error('Erreur de parsing :', e);
    }
}

function simulateScan(guest) {
    const overlay      = document.getElementById('scanOverlay');
    const progressBar  = document.getElementById('scanProgressBar');
    const guestCard    = document.getElementById('guestCard');
    const hostsCircle  = document.getElementById('hostsCircle');
    const hostsImage   = document.getElementById('hostsImage');
    const guestImage   = document.getElementById('guestImage');
    const hostsName    = document.getElementById('splashHostsName');
    const eventInfo    = document.getElementById('splashEventInfo');

    // 1. Afficher l'overlay scan
    overlay.classList.add('active');
    progressBar.style.width = '0%';

    // Petite animation de la barre de progression
    setTimeout(() => { progressBar.style.width = '40%'; }, 100);
    setTimeout(() => { progressBar.style.width = '75%'; }, 500);
    setTimeout(() => { progressBar.style.width = '100%'; }, 1000);

    // 2. Après ~1.4s, cacher l'overlay et mettre à jour les infos
    setTimeout(() => {
        overlay.classList.remove('active');

        // Mise à jour du nom des hôtes
        if (hostsName) hostsName.textContent = guest.hosts;

        // Mise à jour de l'événement
        if (eventInfo) eventInfo.textContent = guest.eventInfo;

        // Mise à jour de la photo des hôtes
        if (hostsImage) {
            hostsImage.style.display = 'block';
            hostsImage.src = guest.hostsImg;
        }

        // Mise à jour de la photo de l'invité
        if (guestImage) {
            guestImage.style.display = 'block';
            guestImage.src = guest.guestImg;
        }

        // Mise à jour du nom de l'invité
        const guestFullname = document.getElementById('guestFullname');
        if (guestFullname) guestFullname.textContent = guest.name;

        // Mise à jour du détail événement
        const guestHostsName = document.getElementById('guestHostsName');
        const guestPersons   = document.getElementById('guestPersons');
        if (guestHostsName) guestHostsName.textContent = guest.hosts;
        if (guestPersons)   guestPersons.textContent   = guest.persons;

        // Mise à jour du statut
        const guestStatusText = document.getElementById('guestStatusText');
        if (guestStatusText) guestStatusText.textContent = guest.status;

        // Mise à jour de la position
        const posCurrent = document.getElementById('splashPositionCurrent');
        const posRight   = document.getElementById('splashPositionCurrentRight');
        if (posCurrent) posCurrent.textContent = guest.position;
        if (posRight)   posRight.textContent   = guest.position;

        // 3. Rejouer les animations d'apparition
        if (hostsCircle) {
            hostsCircle.classList.remove('animate');
            void hostsCircle.offsetWidth; // Force reflow
            hostsCircle.classList.add('animate');
        }
        if (guestCard) {
            guestCard.classList.remove('animate');
            void guestCard.offsetWidth;
            guestCard.classList.add('animate');
        }
    }, 1400);
}

// ================================================================
// COMPTE À REBOURS
// ================================================================
let countdownValue = 5;
setInterval(() => {
    countdownValue = countdownValue <= 0 ? 5 : countdownValue - 1;
    const el = document.getElementById('splashCountdown');
    if (el) el.textContent = countdownValue;
}, 1000);

// ================================================================
// INITIALISATION
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    generateFloatingHearts('floatingHeartsLeft', 8);
    generateFloatingHearts('floatingHeartsRight', 8);
});

// ================================================================
// SCROLL ANIMATIONS
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll(
        '.section-glass, .event-type-card, .feature-card, ' +
        '.photobooth-image, .photobooth-content, .floorplan-image, ' +
        '.floor-plan-container, .floor-plan .table-item, ' +
        '.pricing-card, .contact-info-item, .contact-form, .splash-demo-selector'
    );

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const index = Array.from(entry.target.parentElement.children).indexOf(entry.target);
                const delay = Math.min(index * 60, 400);
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, delay);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -50px 0px'
    });

    animatedElements.forEach(el => observer.observe(el));
});
</script>

</body>
</html>