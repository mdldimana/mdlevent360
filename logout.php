<?php
// Inclure l'authentification
require_once __DIR__ . '/includes/auth.php';

// Déconnecter l'utilisateur
logoutUser();

// Rediriger vers la page de connexion
header('Location: login.php?message=Déconnexion réussie');
exit;