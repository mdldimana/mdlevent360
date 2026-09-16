<?php
// admin/notifications/whatsapp/test.php
require_once __DIR__ . '/../../../includes/auth.php';
requirePermission('notifications.whatsapp');

$pdo = getDbConnection();

// Récupérer la configuration active
$stmt = $pdo->query("SELECT * FROM whatsapp_config WHERE status = 'active' ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();

if (!$config) {
    die('❌ Aucune configuration WhatsApp active trouvée.');
}

echo "<h1>Test de configuration WhatsApp</h1>";

// Afficher la configuration (masquer le token)
echo "<pre>";
echo "Service: " . ($config['service'] ?? 'meta') . "\n";
echo "Phone ID: " . $config['phone_number_id'] . "\n";
echo "API URL: " . ($config['api_url'] ?? 'https://graph.facebook.com/v18.0/') . "\n";
echo "Business ID: " . ($config['business_account_id'] ?? 'Non défini') . "\n";
echo "Token: " . substr($config['access_token'], 0, 10) . "...\n";
echo "</pre>";

// Test 1: Vérifier que le token est valide
echo "<h2>Test 1: Vérification du token</h2>";
$phoneId = $config['phone_number_id'];
$token = $config['access_token'];

$url = 'https://graph.facebook.com/v18.0/' . $phoneId;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Token valide !\n";
    $data = json_decode($response, true);
    echo "Nom du compte: " . ($data['name'] ?? 'Inconnu') . "\n";
} else {
    echo "❌ Token invalide ou erreur de connexion (Code: $httpCode)\n";
    echo "Réponse: " . substr($response, 0, 500) . "\n";
}

// Test 2: Envoyer un message de test
echo "<h2>Test 2: Envoi d'un message de test</h2>";

$testNumber = $_GET['phone'] ?? ''; // Passer ?phone=+243XXXXXXXXX

if (empty($testNumber)) {
    echo "⚠️ Pour tester l'envoi, ajoutez ?phone=+243XXXXXXXXX à l'URL\n";
} else {
    $message = "🧪 Test de connexion WhatsApp - " . date('d/m/Y H:i:s');
    
    $url = 'https://graph.facebook.com/v18.0/' . $phoneId . '/messages';
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $testNumber,
        'type' => 'text',
        'text' => ['body' => $message]
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
    
    echo "Code HTTP: $httpCode\n";
    echo "Réponse: " . $response . "\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Message envoyé avec succès !\n";
    } else {
        $error = json_decode($response, true);
        echo "❌ Erreur d'envoi: " . ($error['error']['message'] ?? 'Erreur inconnue') . "\n";
    }
}
?>