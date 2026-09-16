<?php
// migration_notifications.php
require_once __DIR__ . '/includes/auth.php';
requirePermission('admin.acces');

$pdo = getDbConnection();

echo "<h1>🔄 Migration des notifications</h1>";

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'email_sent'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "<p>✅ Ajout de la colonne email_sent...</p>";
        $pdo->exec("ALTER TABLE invitations ADD COLUMN email_sent BOOLEAN DEFAULT FALSE AFTER email_sent_at");
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'whatsapp_sent'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "<p>✅ Ajout de la colonne whatsapp_sent...</p>";
        $pdo->exec("ALTER TABLE invitations ADD COLUMN whatsapp_sent BOOLEAN DEFAULT FALSE AFTER whatsapp_sent_at");
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'telegram_sent'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "<p>✅ Ajout de la colonne telegram_sent...</p>";
        $pdo->exec("ALTER TABLE invitations ADD COLUMN telegram_sent BOOLEAN DEFAULT FALSE AFTER telegram_sent_at");
    }
    
    // Mettre à jour les valeurs existantes
    echo "<p>🔄 Mise à jour des valeurs existantes...</p>";
    $pdo->exec("UPDATE invitations SET email_sent = TRUE WHERE email_sent_at IS NOT NULL");
    $pdo->exec("UPDATE invitations SET whatsapp_sent = TRUE WHERE whatsapp_sent_at IS NOT NULL");
    $pdo->exec("UPDATE invitations SET telegram_sent = TRUE WHERE telegram_sent_at IS NOT NULL");
    
    // Ajouter des index
    echo "<p>✅ Ajout des index...</p>";
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_sent ON invitations(email_sent)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_whatsapp_sent ON invitations(whatsapp_sent)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_telegram_sent ON invitations(telegram_sent)");
    
    // Compter les notifications
    $emailCount = $pdo->query("SELECT COUNT(*) as count FROM invitations WHERE email_sent = TRUE")->fetch()['count'];
    $whatsappCount = $pdo->query("SELECT COUNT(*) as count FROM invitations WHERE whatsapp_sent = TRUE")->fetch()['count'];
    $telegramCount = $pdo->query("SELECT COUNT(*) as count FROM invitations WHERE telegram_sent = TRUE")->fetch()['count'];
    
    echo "<h3>📊 Résultats :</h3>";
    echo "<ul>";
    echo "<li>📧 Emails envoyés : <strong>$emailCount</strong></li>";
    echo "<li>💬 WhatsApp envoyés : <strong>$whatsappCount</strong></li>";
    echo "<li>📨 Telegram envoyés : <strong>$telegramCount</strong></li>";
    echo "</ul>";
    
    echo "<p style='color:green; font-weight:bold;'>✅ Migration terminée avec succès !</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>