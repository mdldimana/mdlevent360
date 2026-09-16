<?php
// admin/notifications/whatsapp/verify_token.php
require_once __DIR__ . '/../../../includes/auth.php';

$token = 'EAAWKEAtjSZAoBSY81vo8bCvntcl7o8C5snryJdydp2KmReaceyyG6lGiZCZAG9ccqmHKTenhVZBdou7W7Bz5OjA3ZBqgwKIZCTjZB4kkLIM7Ue0UkKg028PwSCN19YZADjY4ns3T3p6YuevNk84VS64nP1HA4chMpfQVYYF3Hb1Oojssei4hbVNsjWOuDrlJ4XsFrYvnLxrZBstrXwA935t3RcEpjEH2BKLfUVkCkVEsbNZAqPQJwxh4itOEpt1e7wr2gVAt7QB24mMhF9v9WeFFUqlSvYrSZC06vCzywZDZD';
$wabaId = '4398227313769738';

echo "<h2>🔍 Vérification du token</h2>";

// Test 1: Vérifier le WABA
$url = "https://graph.facebook.com/v18.0/{$wabaId}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Test 1: Vérification du WABA</h3>";
echo "Code HTTP: $httpCode<br>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 10px;'>";
    echo "✅ Token valide !<br><br>";
    echo "📊 Informations du compte:<br>";
    echo "• Nom: " . ($data['name'] ?? 'Inconnu') . "<br>";
    echo "• ID: " . ($data['id'] ?? 'Inconnu') . "<br>";
    echo "• Statut: " . ($data['status'] ?? 'Inconnu') . "<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 10px;'>";
    echo "❌ Token invalide<br>";
    echo "Réponse: " . substr($response, 0, 500);
    echo "</div>";
    exit;
}

// Test 2: Récupérer les numéros de téléphone
echo "<h3>Test 2: Récupération des numéros</h3>";

$url = "https://graph.facebook.com/v18.0/{$wabaId}/phone_numbers";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode<br>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && !empty($data['data'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 10px;'>";
        echo "✅ Numéros trouvés :<br><br>";
        foreach ($data['data'] as $phone) {
            echo "📱 " . $phone['display_phone_number'] . "<br>";
            echo "🆔 ID: <code>" . $phone['id'] . "</code><br>";
            echo "⭐ Qualité: " . ($phone['quality_rating'] ?? 'N/A') . "<br>";
            echo "<hr>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 10px;'>";
        echo "⚠️ Aucun numéro trouvé. Ajoutez d'abord un numéro dans Meta Business.";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 10px;'>";
    echo "❌ Erreur: " . substr($response, 0, 500);
    echo "</div>";
}
?>