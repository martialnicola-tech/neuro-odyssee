<?php
/**
 * Migration, Table drip_emails_sent
 * Script one-time : creer la table de suivi des emails drip
 * Appeler une seule fois via navigateur ou CLI
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS drip_emails_sent (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            subscriber_id  INT NOT NULL,
            template_key   VARCHAR(50) NOT NULL,
            sent_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sub_template (subscriber_id, template_key),
            INDEX idx_subscriber_id (subscriber_id),
            INDEX idx_template_key (template_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "OK, Table drip_emails_sent creee (ou deja existante).\n";

} catch (PDOException $e) {
    http_response_code(500);
    echo "ERREUR : " . $e->getMessage() . "\n";
    error_log('migrate-drip error: ' . $e->getMessage());
}
