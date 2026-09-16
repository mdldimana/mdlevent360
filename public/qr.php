<?php
// Inclure la configuration
require_once __DIR__ . '/../config/database.php';

// Récupérer le code d'invitation
$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('Code d\'invitation manquant');
}

// Vérifier l'invitation
$pdo = getDbConnection();
$invitation = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id, 
            i.code_unique, 
            i.statut,
            inv.nom, 
            inv.prenom, 
            inv.email,
            e.nom as evenement_nom,
            e.date_evenement,
            e.lieu,
            e.fond as evenement_image
        FROM invitations i
        JOIN invites inv ON i.id_invite = inv.id
        JOIN evenements e ON i.id_evenement = e.id
        WHERE i.code_unique = ?
    ");
    $stmt->execute([$code]);
    $invitation = $stmt->fetch();
} catch (PDOException $e) {}

if (!$invitation) {
    die('Invitation invalide');
}

// URL de l'invitation
$invitationUrl = APP_URL . '/public/invitation.php?code=' . $code;
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($invitationUrl) . '&color=1a1a2e&bgcolor=ffffff&qzone=1';

// ============================================
// GÉNÉRATION DU QR CODE EN BASE64
// ============================================
$qrCodeBase64 = '';
if (function_exists('curl_init')) {
    $ch = curl_init($qrCodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $imageData) {
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($imageData);
    }
}

// Si curl n'est pas disponible ou a échoué, utiliser l'URL
if (empty($qrCodeBase64)) {
    $qrCodeBase64 = $qrCodeUrl;
}

// Statut
$statutConfig = [
    'EN_ATTENTE' => ['label' => 'En attente', 'dot' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#92400e'],
    'CONFIRMEE' => ['label' => '✅ Confirmée', 'dot' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#065f46'],
    'REFUSEE' => ['label' => '❌ Refusée', 'dot' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#991b1b'],
    'PRESENTE' => ['label' => '✅ Présente', 'dot' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.15)', 'color' => '#1e40af'],
    'ANNULEE' => ['label' => 'Annulée', 'dot' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.15)', 'color' => '#1f2937']
];
$statut = $statutConfig[$invitation['statut']] ?? $statutConfig['EN_ATTENTE'];

// Fonction pour obtenir la photo de fond
function getFondUrl($fondPath) {
    global $projectFolder;
    if (empty($fondPath)) return '';
    if (preg_match('/^https?:\/\//', $fondPath)) return $fondPath;
    $fileName = basename($fondPath);
    if (strpos($fondPath, 'fonds/') !== false || strpos($fondPath, 'fond') !== false) {
        return $projectFolder . '/uploads/fonds/' . $fileName;
    }
    return $projectFolder . '/uploads/photos/' . $fileName;
}

$pageBackground = !empty($invitation['evenement_image']) ? getFondUrl($invitation['evenement_image']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Georgia&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f8f5f2;
            <?php if ($pageBackground): ?>
            background-image: url('<?php echo htmlspecialchars($pageBackground); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
        }
        /* Pas d'overlay - l'image de fond reste visible normalement */

        .invitation-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
        }
        .ticket-card {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            position: relative;
            animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes cardIn{ from{opacity:0; transform: translateY(20px) scale(0.98);} to{opacity:1; transform: translateY(0) scale(1);} }

        .ticket-header {
            background: rgba(17, 17, 25, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 28px 32px 80px;
            position: relative;
            overflow: hidden;
        }
        .ticket-header::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(193,124,96,0.2) 0%, transparent 70%);
            top: -150px; right: -100px;
            pointer-events: none;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 700;
            letter-spacing: -0.02em;
            font-size: 14px;
        }
        .brand i {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: white;
            font-size: 18px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: <?php echo $statut['bg']; ?>;
            color: <?php echo $statut['color']; ?>;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .status-badge .dot {
            width: 6px;
            height: 6px;
            background: <?php echo $statut['dot']; ?>;
            border-radius: 50%;
            box-shadow: 0 0 0 3px <?php echo $statut['bg']; ?>;
        }

        .header-info { margin-top: 22px; position: relative; z-index: 1; }
        .header-info h2 {
            color: white;
            font-family: Georgia, serif;
            font-weight: 400;
            font-size: 26px;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .header-info p {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin: 6px 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .header-info p i {
            color: #c17c60;
        }
        
        .qr-section {
            padding: 0 32px;
            margin-top: -54px;
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .qr-frame {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 16px 40px rgba(17,17,25,0.08), 0 0 0 1px rgba(17,17,25,0.06);
            display: inline-block;
            position: relative;
        }
        .qr-frame::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 24px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(193,124,96,0.5), rgba(212,165,116,0.3));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
        .qr-frame img {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            display: block;
        }

        .ticket-body {
            padding: 22px 32px 28px;
            text-align: center;
        }
        .invite-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #1a1a1a;
            margin: 0;
        }
        .invite-email {
            font-size: 13px;
            color: #8a8a9a;
            margin-top: 2px;
        }

        .code-pill {
            margin: 18px auto 0;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(246, 245, 242, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(229, 226, 222, 0.5);
            padding: 8px 14px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: 0.08em;
        }
        .code-pill button {
            border: 0;
            background: rgba(255, 255, 255, 0.8);
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6b6b7a;
        }
        .code-pill button:hover {
            background: #c17c60;
            color: white;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 22px;
        }
        .btn-pro {
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary-gold {
            background: linear-gradient(135deg, #c17c60, #d4a574);
            color: white;
            box-shadow: 0 8px 20px rgba(193,124,96,0.3);
        }
        .btn-primary-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(193,124,96,0.35);
            color: white;
        }
        .btn-secondary-light {
            background: rgba(17, 17, 25, 0.85);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            color: white;
        }
        .btn-secondary-light:hover {
            background: rgba(17, 17, 25, 0.95);
            color: white;
            transform: translateY(-1px);
        }

        .btn-back-custom {
            grid-column: span 2;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(236, 233, 228, 0.5);
            color: #6b6b7a;
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-back-custom:hover {
            background: rgba(250, 250, 248, 0.8);
            color: #6b6b7a;
        }

        .footer-link {
            margin-top: 16px;
            font-size: 12px;
            color: rgba(168, 165, 160, 0.8);
            text-align: center;
        }
        .footer-link a {
            color: rgba(107, 107, 122, 0.9);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .footer-link a:hover {
            color: #c17c60;
        }

        /* Bouton Télécharger la carte en JPG */
        .btn-download-card {
            grid-column: span 2;
            background: linear-gradient(135deg, #1a1a1a, #2a2a3a);
            color: white;
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .btn-download-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
            color: white;
        }

        @media (max-width: 480px) {
            .ticket-header { padding: 24px 24px 70px; }
            .ticket-body, .qr-section { padding-left: 24px; padding-right: 24px; }
            .qr-frame img {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>

<div class="invitation-wrapper">
    <div class="ticket-card" id="ticketCard">
        <div class="ticket-header">
            <div class="header-top">
                <div class="brand">
                    <i class="fas fa-star"></i> 
                    <?php echo defined('APP_NAME') ? strtoupper(APP_NAME) : 'INVITATION'; ?>
                </div>
                <div class="status-badge">
                    <span class="dot"></span>
                    <?php echo $statut['label']; ?>
                </div>
            </div>
            <div class="header-info">
                <h2><?php echo htmlspecialchars($invitation['evenement_nom']); ?></h2>
                <p>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($invitation['lieu'] ?: 'Lieu à confirmer'); ?></span>
                    <span style="opacity:0.3;">•</span>
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($invitation['date_evenement'])); ?></span>
                </p>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-frame">
                <img 
                    src="<?php echo $qrCodeUrl; ?>" 
                    alt="QR Code" 
                    id="qrImage"
                    crossorigin="anonymous"
                    onerror="this.src='<?php echo $qrCodeBase64; ?>'"
                    onload="this.onerror=null;"
                >
            </div>
        </div>

        <div class="ticket-body">
            <h3 class="invite-name"><?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?></h3>
            <div class="invite-email"><?php echo htmlspecialchars($invitation['email']); ?></div>

            <div class="code-pill">
                <span id="codeText"><?php echo htmlspecialchars($invitation['code_unique']); ?></span>
                <button onclick="copyCode()" title="Copier"><i class="fas fa-copy"></i></button>
            </div>

            <div class="actions">
                <button class="btn-pro btn-primary-gold" onclick="downloadQR()">
                    <i class="fas fa-qrcode"></i> QR Code
                </button>
                <button class="btn-pro btn-secondary-light" onclick="downloadCard()">
                    <i class="fas fa-image"></i> Carte JPG
                </button>
                <a href="invitation.php?code=<?php echo urlencode($code); ?>" class="btn-back-custom">
                    <i class="fas fa-arrow-left"></i> Voir l'invitation
                </a>
            </div>
        </div>
    </div>

    <div class="footer-link">
        <i class="fas fa-link" style="opacity:0.5;"></i>
        <span id="linkText"><?php echo htmlspecialchars($invitationUrl); ?></span>
        <a href="#" onclick="copyLink(); return false;"><i class="fas fa-copy"></i> Copier</a>
    </div>
</div>

<script>
// Variable globale pour stocker l'image en base64
let qrImageBase64 = '<?php echo $qrCodeBase64; ?>';

function copyCode(){
    const code = document.getElementById('codeText').innerText;
    navigator.clipboard.writeText(code);
    toast('Code copié');
}

function copyLink(){
    const link = document.getElementById('linkText').innerText;
    navigator.clipboard.writeText(link);
    toast('Lien copié');
}

function downloadQR(){
    const img = document.getElementById('qrImage');
    
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    const size = 600;
    canvas.width = size;
    canvas.height = size;
    
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    
    const tempImg = new Image();
    tempImg.crossOrigin = 'anonymous';
    
    const imgSrc = (qrImageBase64 && qrImageBase64.startsWith('data:image')) 
        ? qrImageBase64 
        : img.src;
    
    tempImg.src = imgSrc;
    
    tempImg.onload = function() {
        const margin = 40;
        const qrSize = size - (margin * 2);
        ctx.drawImage(tempImg, margin, margin, qrSize, qrSize);
        
        ctx.fillStyle = '#111119';
        ctx.font = 'bold 16px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('<?php echo htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']); ?>', size/2, size - 10);
        
        const link = document.createElement('a');
        link.download = 'qr-invitation-<?php echo $invitation['code_unique']; ?>.png';
        link.href = canvas.toDataURL('image/png', 1.0);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        toast('QR Code téléchargé !');
    };
    
    tempImg.onerror = function() {
        toast('Erreur lors du téléchargement, veuillez réessayer');
    };
}

// ============================================================
// TÉLÉCHARGER LA CARTE ENTIÈRE EN JPG
// ============================================================
async function downloadCard() {
    const btn = document.querySelector('.btn-secondary-light');
    const card = document.getElementById('ticketCard');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
    
    try {
        await new Promise(resolve => setTimeout(resolve, 300));
        
        const canvas = await html2canvas(card, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            width: card.offsetWidth,
            height: card.offsetHeight,
            windowWidth: card.scrollWidth,
            windowHeight: card.scrollHeight,
            onclone: function(doc) {
                const imgs = doc.querySelectorAll('img');
                imgs.forEach(img => {
                    if (img.src && !img.src.startsWith('data:image')) {
                        img.crossOrigin = 'anonymous';
                    }
                });
            }
        });
        
        const link = document.createElement('a');
        const name = '<?php echo htmlspecialchars($invitation['prenom'] . '_' . $invitation['nom']); ?>';
        link.download = `carte_qr_${name}_${Date.now()}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        toast('Carte téléchargée en JPG !');
    } catch (error) {
        console.error('Erreur:', error);
        toast('Erreur lors du téléchargement');
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-image"></i> Carte JPG';
}

function toast(msg){
    let t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(17,17,25,0.9);color:white;padding:10px 18px;border-radius:100px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.2);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);';
    document.body.appendChild(t);
    setTimeout(()=>{ 
        t.style.opacity='0'; 
        t.style.transform='translateX(-50%) translateY(10px)'; 
        t.style.transition='all .3s'; 
        setTimeout(()=>t.remove(),300);
    }, 1800);
}

document.addEventListener('DOMContentLoaded', function() {
    const img = document.getElementById('qrImage');
    
    if (img.complete && img.naturalHeight > 0) {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth || 500;
            canvas.height = img.naturalHeight || 500;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            qrImageBase64 = canvas.toDataURL('image/png');
        } catch(e) {}
    } else {
        img.src = qrImageBase64;
    }
});
</script>

</body>
</html>