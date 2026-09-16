<?php
/**
 * Fonctions utilitaires PARTAGÉES par tous les templates d'invitation.
 * Ce fichier est inclus automatiquement par invitation.php
 */

if (!function_exists('getPhotoUrl')) {
    /**
     * URL d'une photo d'hôte
     */
    function getPhotoUrl(string $photoPath): string {
        global $projectFolder;
        if (empty($photoPath)) return '';
        if (preg_match('/^https?:\/\//', $photoPath)) return $photoPath;
        return ($projectFolder ?: '') . '/uploads/photos_host/' . basename($photoPath);
    }
}

if (!function_exists('getFondUrl')) {
    /**
     * URL d'une image de fond
     */
    function getFondUrl(string $fondPath): string {
        global $projectFolder;
        if (empty($fondPath)) return '';
        if (preg_match('/^https?:\/\//', $fondPath)) return $fondPath;

        $fileName = basename($fondPath);

        // Détecte automatiquement le dossier
        if (strpos($fondPath, 'fonds/') !== false || strpos($fondPath, 'fond') !== false) {
            return ($projectFolder ?: '') . '/uploads/fonds/' . $fileName;
        }
        return ($projectFolder ?: '') . '/uploads/photos/' . $fileName;
    }
}

if (!function_exists('getFullUrl')) {
    /**
     * URL complète pour le QR Code
     */
    function getFullUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri      = $_SERVER['REQUEST_URI'] ?? '';
        return $protocol . $host . $uri;
    }
}

if (!function_exists('getBoissonIcon')) {
    /**
     * Icône FontAwesome selon le type de boisson
     */
    function getBoissonIcon(string $type): string {
        $type = strtolower($type);
        if (strpos($type, 'alcool') !== false || strpos($type, 'vin') !== false || strpos($type, 'champagne') !== false) {
            return 'fa-wine-glass';
        } elseif (strpos($type, 'bière') !== false || strpos($type, 'biere') !== false) {
            return 'fa-beer';
        } elseif (strpos($type, 'soft') !== false || strpos($type, 'soda') !== false || strpos($type, 'jus') !== false) {
            return 'fa-glass-whiskey';
        } elseif (strpos($type, 'eau') !== false) {
            return 'fa-tint';
        } elseif (strpos($type, 'cocktail') !== false) {
            return 'fa-cocktail';
        } elseif (strpos($type, 'café') !== false || strpos($type, 'cafe') !== false || strpos($type, 'thé') !== false) {
            return 'fa-mug-hot';
        }
        return 'fa-glass-martini-alt';
    }
}