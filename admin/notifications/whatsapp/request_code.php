<?php
// admin/notifications/whatsapp/request_code.php
$token = 'EAAWKEAtjSZAoBSY81vo8bCvntcl7o8C5snryJdydp2KmReaceyyG6lGiZCZAG9ccqmHKTenhVZBdou7W7Bz5OjA3ZBqgwKIZCTjZB4kkLIM7Ue0UkKg028PwSCN19YZADjY4ns3T3p6YuevNk84VS64nP1HA4chMpfQVYYF3Hb1Oojssei4hbVNsjWOuDrlJ4XsFrYvnLxrZBstrXwA935t3RcEpjEH2BKLfUVkCkVEsbNZAqPQJwxh4itOEpt1e7wr2gVAt7QB24mMhF9v9WeFFUqlSvYrSZC06vCzywZDZD';
$phoneNumberId = '1322376334284502';

$url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/request_code";

$data = [
    'code_method' => 'SMS',      // ou 'VOICE' pour un appel
    'language' => 'fr'           // Langue du message (fr, en, etc.)
];

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
curl_close($ch);

echo "<h2>📞 Demande de code de vérification</h2>";
echo "Code HTTP: $httpCode<br>";
echo "Réponse: <pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 10px;'>";
    echo "✅ Code de vérification envoyé avec succès !<br>";
    echo "📱 Vérifiez vos SMS sur le numéro +243 963 967 028<br>";
    echo "🕐 Le code est valable 5 minutes.";
    echo "</div>";
} else {
    $error = json_decode($response, true);
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 10px;'>";
    echo "❌ Erreur: " . ($error['error']['message'] ?? 'Erreur inconnue');
    echo "</div>";
}
?>