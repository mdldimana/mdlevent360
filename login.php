<?php
// Inclure l'authentification
require_once __DIR__ . '/includes/auth.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

// Variables
$error = '';
$username = '';
$remember = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            // Si "Se souvenir de moi" est coché
            if ($remember) {
                // Créer un cookie de session plus long (7 jours)
                ini_set('session.cookie_lifetime', 604800);
            }
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ============================================
           STYLES COMPLETS - PAGE DE CONNEXION
           ============================================ */
        
        /* RESET & BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        /* ANIMATION DE FOND - CERCLES FLOTTANTS */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.1), rgba(42, 82, 152, 0.1));
            animation: float 20s infinite linear;
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            animation-delay: -5s;
        }
        
        .circle-3 {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 15%;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-20px) rotate(120deg);
            }
            66% {
                transform: translateY(20px) rotate(240deg);
            }
        }
        
        /* CONTENEUR PRINCIPAL */
        .login-container {
            display: flex;
            max-width: 1000px;
            width: 90%;
            min-height: 600px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }
        
        .login-container.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* PARTIE GAUCHE - FORMULAIRE */
        .login-form-container {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            padding: 15px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.2);
        }
        
        .logo i {
            color: white;
            font-size: 60px;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 500;
        }
        
        /* FORMULAIRE */
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #1e3c72;
            width: 20px;
            text-align: center;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e1e5ee;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            color: #333;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }
        
        .form-input::placeholder {
            color: #aaa;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #1e3c72;
        }
        
        /* MESSAGE D'ERREUR */
        .error-message {
            background: linear-gradient(135deg, #ff4757, #ff3838);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        .error-message i {
            font-size: 20px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* BOUTON DE CONNEXION */
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        /* OPTIONS DU FORMULAIRE */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            cursor: pointer;
        }
        
        .checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .remember-me input {
            display: none;
        }
        
        .remember-me input:checked + .checkbox-custom {
            background: #1e3c72;
            border-color: #1e3c72;
        }
        
        .remember-me input:checked + .checkbox-custom::after {
            content: '✓';
            color: white;
            font-size: 12px;
        }
        
        .forgot-password {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: #ff6b6b;
            text-decoration: underline;
        }
        
        /* PIED DE PAGE */
        .login-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 14px;
        }
        
        .developer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            color: #666;
        }
        
        .developer i {
            color: #1e3c72;
        }
        
        /* PARTIE DROITE - PANNEAU D'INFORMATION */
        .login-info-container {
            flex: 1;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .info-bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none" opacity="0.05"><path d="M0,0 L100,0 L100,100 Z" fill="white"/></svg>');
            background-size: cover;
        }
        
        .info-content {
            position: relative;
            z-index: 2;
            max-width: 400px;
            width: 100%;
        }
        
        .info-logo {
            width: 180px;
            height: 180px;
            margin: 0 auto 30px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .info-logo i {
            color: white;
            font-size: 80px;
        }
        
        .info-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }
        
        .info-subtitle {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 25px;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .info-features {
            text-align: left;
            margin: 40px 0;
            width: 100%;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .feature:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .feature i {
            font-size: 24px;
            color: #4facfe;
            min-width: 40px;
            text-align: center;
        }
        
        .feature-text {
            flex: 1;
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-desc {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .info-security-badge {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
        }
        
        .info-security-badge p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .info-security-badge i {
            margin-right: 10px;
        }
        
        /* ÉCRAN DE CHARGEMENT */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 20px;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1e3c72;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .loading-text {
            color: #1e3c72;
            font-weight: 600;
            font-size: 16px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                min-height: auto;
                max-width: 100%;
            }
            
            .login-form-container,
            .login-info-container {
                padding: 40px 30px;
            }
            
            .login-info-container {
                border-radius: 0 0 24px 24px;
            }
            
            .info-content {
                max-width: 100%;
            }
            
            .info-logo {
                width: 130px;
                height: 130px;
            }
            
            .info-logo i {
                font-size: 60px;
            }
            
            .login-container.show {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px;
            }
            
            .login-container {
                width: 100%;
                border-radius: 20px;
            }
            
            .login-form-container,
            .login-info-container {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 24px;
            }
            
            .info-title {
                font-size: 26px;
            }
            
            .logo {
                width: 90px;
                height: 90px;
            }
            
            .logo i {
                font-size: 45px;
            }
            
            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .form-input {
                padding: 14px 16px 14px 45px;
                font-size: 14px;
            }
            
            .submit-btn {
                padding: 15px;
                font-size: 14px;
            }
            
            .feature {
                padding: 12px;
            }
        }
        
        /* PETITE ANIMATION SUPPLÉMENTAIRE POUR LE BOUTON */
        .submit-btn .fa-spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>

<!-- ÉCRAN DE CHARGEMENT -->
<div class="loading" id="loadingScreen">
    <div class="loader"></div>
    <p class="loading-text">Connexion en cours...</p>
</div>

<!-- ANIMATION DE FOND -->
<div class="bg-animation">
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    <div class="bg-circle circle-3"></div>
</div>

<!-- CONTENEUR PRINCIPAL -->
<div class="login-container" id="loginContainer">

    <!-- PARTIE GAUCHE - FORMULAIRE -->
    <div class="login-form-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h1 class="login-title">Bienvenue</h1>
            <p class="login-subtitle">Connectez-vous à votre espace</p>
        </div>

        <!-- MESSAGE D'ERREUR -->
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- FORMULAIRE -->
        <form class="login-form" method="POST" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user"></i> Identifiant
                </label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           class="form-input" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" 
                           placeholder="Nom d'utilisateur ou email" 
                           required 
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Mot de passe
                </label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           class="form-input" 
                           id="password" 
                           name="password" 
                           placeholder="Entrez votre mot de passe" 
                           required>
                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Afficher/Masquer le mot de passe">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkbox-custom"></span>
                    Se souvenir de moi
                </label>
                <a href="#" class="forgot-password" id="forgotPassword">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="submit-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

        <div class="login-footer">
            <p>Application sécurisée - Tous droits réservés</p>
            <div class="developer">
                <i class="fas fa-shield-alt"></i>
                <span>Protection des données</span>
                <i class="fas fa-circle" style="font-size: 4px; color: #ddd;"></i>
                <span>Version 2.0</span>
            </div>
        </div>
    </div>

    <!-- PARTIE DROITE - PANNEAU D'INFORMATION -->
    <div class="login-info-container">
        <div class="info-bg-pattern"></div>
        <div class="info-content">
            <div class="info-logo">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h2 class="info-title">Gestion des Invitations</h2>
            <p class="info-subtitle">Solution complète pour vos événements</p>

            <div class="info-features">
                <div class="feature">
                    <i class="fas fa-calendar-check"></i>
                    <div class="feature-text">
                        <div class="feature-title">Événements</div>
                        <div class="feature-desc">Créez et gérez vos événements facilement</div>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <div class="feature-text">
                        <div class="feature-title">Invités</div>
                        <div class="feature-desc">Liste complète et catégorisation</div>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-qrcode"></i>
                    <div class="feature-text">
                        <div class="feature-title">QR Codes</div>
                        <div class="feature-desc">Contrôle d'accès simplifié</div>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-pie"></i>
                    <div class="feature-text">
                        <div class="feature-title">Statistiques</div>
                        <div class="feature-desc">Suivez les tendances en temps réel</div>
                    </div>
                </div>
            </div>

            <div class="info-security-badge">
                <p>
                    <i class="fas fa-shield-alt"></i>
                    Accès sécurisé - Authentification renforcée
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================================
    // JAVASCRIPT - PAGE DE CONNEXION
    // ============================================

    /**
     * Afficher/Masquer le mot de passe
     */
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }

    /**
     * Animation de chargement lors de la soumission du formulaire
     */
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const loadingScreen = document.getElementById('loadingScreen');
        const loginBtn = document.getElementById('loginBtn');
        
        // Afficher l'écran de chargement
        loadingScreen.style.display = 'flex';
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner"></i> Connexion...';
    });

    /**
     * Animation d'entrée de la page
     */
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('loginContainer');
        
        // Ajouter un léger délai pour l'animation
        setTimeout(() => {
            container.classList.add('show');
        }, 100);
    });

    /**
     * Soumission avec la touche Entrée
     */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const activeElement = document.activeElement;
            if (activeElement && (activeElement.id === 'username' || activeElement.id === 'password')) {
                document.getElementById('loginForm').submit();
            }
        }
    });

    /**
     * Lien "Mot de passe oublié" - Demo
     */
    document.getElementById('forgotPassword').addEventListener('click', function(e) {
        e.preventDefault();
        alert('🔐 Fonctionnalité de récupération de mot de passe\n\nVeuillez contacter l\'administrateur pour réinitialiser votre mot de passe.');
    });

    /**
     * Focus automatique sur le champ username si page chargée
     */
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        }
    });
</script>

</body>
</html>