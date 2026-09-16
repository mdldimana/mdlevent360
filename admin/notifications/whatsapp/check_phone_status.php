<?php
// admin/notifications/whatsapp/check_phone_status.php
$token = 'EAAWKEAtjSZAoBSUqFG4bYCNBUCZBwOBf9uZA7YQy1C1LV5ZCxSLrZBNO1ufqTRiK5g19jlSyzm1pMZB6AmhW1yz3aebnAAr7VoIZB0e3kO5COZAUkuyTNhZAfPPLUKtW3hkvaTgOqE7PsUoZBDsmCimF6eSRp31nIaZCW8TQgYicE03j20tsO7i0Mle4BeYZC1E8BVJm5RPcyMgtvTdvWns9qkwYIAIfZCCfltBgUoZB3BNwE8tOZBIZBUhd4ZBkWXFiWC2ZAxhALwIKjnBQ4vlrRr6ssrS6IokMR6jgc7a7kwqAZDZD';
$phoneNumberId = '1322376334284502';

echo "<h2>📊 Vérification détaillée du numéro</h2>";

// Récupérer les détails du numéro
$url = "https://graph.facebook.com/v18.0/{$phoneNumberId}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    echo "<h3>🔍 Analyse</h3>";
    echo "<ul>";
    echo "<li>📱 Numéro: " . ($data['display_phone_number'] ?? 'Inconnu') . "</li>";
    echo "<li>🆔 Phone ID: " . ($data['id'] ?? 'Inconnu') . "</li>";
    echo "<li>📌 Statut: " . ($data['status'] ?? 'Inconnu') . "</li>";
    echo "<li>⭐ Qualité: " . ($data['quality_rating'] ?? 'N/A') . "</li>";
    echo "<li>✅ Vérifié: " . ($data['verified_name'] ? 'Oui' : 'Non') . "</li>";
    
    // Vérifier si le numéro est enregistré sur WhatsApp
    if (isset($data['status']) && $data['status'] === 'ACTIVE') {
        echo "<li style='color: green;'>✅ Le numéro est actif sur WhatsApp</li>";
    } elseif (isset($data['status']) && $data['status'] === 'PENDING') {
        echo "<li style='color: orange;'>⏳ Le numéro est en attente d'activation</li>";
    } elseif (isset($data['status']) && $data['status'] === 'UNVERIFIED') {
        echo "<li style='color: red;'>❌ Le numéro n'est pas vérifié</li>";
    } else {
        echo "<li style='color: red;'>❌ Statut inconnu: " . ($data['status'] ?? 'N/A') . "</li>";
    }
    echo "</ul>";
    
    // Recommandations
    if (!isset($data['status']) || $data['status'] !== 'ACTIVE') {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin-top: 15px;'>";
        echo "<h4>⚠️ Problème détecté</h4>";
        echo "Le numéro n'est pas encore actif sur WhatsApp.<br>";
        echo "Voici les étapes à suivre :";
        echo "<ol>";
        echo "<li>Connectez-vous à <a href='https://business.facebook.com/' target='_blank'>Meta Business Suite</a></li>";
        echo "<li>Paramètres → Comptes → Comptes WhatsApp</li>";
        echo "<li>Cliquez sur votre compte <strong>MdlEvent</strong></li>";
        echo "<li>Cliquez sur le numéro <strong>+243 963 967 028</strong></li>";
        echo "<li>Vérifiez le statut et suivez les instructions</li>";
        echo "</ol>";
        echo "</div>";
    }
} else {
    $error = json_decode($response, true);
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 10px;'>";
    echo "❌ Erreur: " . ($error['error']['message'] ?? 'Erreur inconnue');
    echo "</div>";
}
?>