<?php
// admin/notifications/whatsapp/test_send.php
$token = 'EAAWKEAtjSZAoBSUqFG4bYCNBUCZBwOBf9uZA7YQy1C1LV5ZCxSLrZBNO1ufqTRiK5g19jlSyzm1pMZB6AmhW1yz3aebnAAr7VoIZB0e3kO5COZAUkuyTNhZAfPPLUKtW3hkvaTgOqE7PsUoZBDsmCimF6eSRp31nIaZCW8TQgYicE03j20tsO7i0Mle4BeYZC1E8BVJm5RPcyMgtvTdvWns9qkwYIAIfZCCfltBgUoZB3BNwE8tOZBIZBUhd4ZBkWXFiWC2ZAxhALwIKjnBQ4vlrRr6ssrS6IokMR6jgc7a7kwqAZDZD';
$phoneNumberId = '1322376334284502';
$to = $_GET['phone'] ?? '';

echo "<h2>📱 Envoi d'un message de test WhatsApp</h2>";

if (empty($to)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; margin-bottom: 15px;'>";
    echo "⚠️ Ajoutez un numéro de test dans l'URL : <code>?phone=+243XXXXXXXXX</code>";
    echo "</div>";
    echo "<form method='GET' class='mt-3'>";
    echo "<div class='input-group' style='max-width: 400px;'>";
    echo "<span class='input-group-text'>📞</span>";
    echo "<input type='text' class='form-control' name='phone' placeholder='+243XXXXXXXXX' required>";
    echo "<button type='submit' class='btn btn-success'>Envoyer le test</button>";
    echo "</div>";
    echo "</form>";
    exit;
}

$message = "🧪 Test de connexion WhatsApp\n";
$message .= "✅ Votre numéro est déjà vérifié !\n";
$message .= "📅 Test effectué le " . date('d/m/Y H:i:s') . "\n";
$message .= "📱 Ce message a été envoyé depuis votre application.";

$url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";

$data = [
    'messaging_product' => 'whatsapp',
    'to' => $to,
    'type' => 'text',
    'text' => ['body' => $message]
];

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 15px;'>";
echo "<strong>📤 Envoi en cours...</strong><br>";
echo "Destinataire: " . htmlspecialchars($to) . "<br>";
echo "Phone Number ID: " . $phoneNumberId . "<br>";
echo "</div>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h3>📊 Résultat</h3>";
echo "Code HTTP: <strong>$httpCode</strong><br>";

if ($curlError) {
    echo "Erreur CURL: $curlError<br>";
}

if ($httpCode >= 200 && $httpCode < 300) {
    $result = json_decode($response, true);
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin-top: 10px;'>";
    echo "✅ <strong>Message envoyé avec succès !</strong><br><br>";
    echo "📨 ID du message: " . ($result['messages'][0]['id'] ?? 'N/A') . "<br>";
    echo "📱 Destinataire: " . htmlspecialchars($to) . "<br>";
    echo "⏰ Date: " . date('d/m/Y H:i:s') . "<br>";
    echo "</div>";
} else {
    $error = json_decode($response, true);
    $errorMessage = $error['error']['message'] ?? 'Erreur inconnue';
    $errorCode = $error['error']['code'] ?? 'N/A';
    
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin-top: 10px;'>";
    echo "❌ <strong>Erreur d'envoi</strong><br><br>";
    echo "Code: $errorCode<br>";
    echo "Message: " . htmlspecialchars($errorMessage) . "<br>";
    echo "</div>";
    
    // Afficher plus de détails pour le débogage
    echo "<details style='margin-top: 15px;'>";
    echo "<summary style='cursor: pointer; color: #0066cc;'>🔍 Voir la réponse complète</summary>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;'>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";
    echo "</details>";
}
?>