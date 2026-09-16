<?php
// admin/notifications/whatsapp/get_phone_id.php
require_once __DIR__ . '/../../../includes/auth.php';

$token = 'EAAWKEAtjSZAoBScJVZBSDE1uhpKWa33zCy2IZBoE1Ft1G9icAvt7R6PrMOb55KqNjZBsC';
$wabaId = '4398227313769738';

$url = "https://graph.facebook.com/v18.0/{$wabaId}/phone_numbers";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h2>Numéros de téléphone WhatsApp Business</h2>";
echo "<pre>";
print_r($data);
echo "</pre>";

if (isset($data['data']) && !empty($data['data'])) {
    foreach ($data['data'] as $phone) {
        echo "Phone ID: " . $phone['id'] . "\n";
        echo "Numéro: " . $phone['display_phone_number'] . "\n";
        echo "Statut: " . $phone['quality_rating'] . "\n";
        echo "---\n";
    }
} else {
    echo "Aucun numéro trouvé. Ajoutez d'abord un numéro dans Meta Business.\n";
}
?>