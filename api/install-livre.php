<?php
require_once __DIR__ . '/config.php';
if (($_GET['secret'] ?? '') !== INSTALL_SECRET) { http_response_code(403); exit('Accès refusé.'); }

$sql = [
    "CREATE TABLE IF NOT EXISTS no_leads (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        email      VARCHAR(255) NOT NULL,
        prenom     VARCHAR(100) DEFAULT '',
        source     VARCHAR(100) DEFAULT '',
        status     VARCHAR(30)  DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_email (email),
        INDEX idx_source (source),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS no_orders (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        email          VARCHAR(255),
        prenom         VARCHAR(100) DEFAULT '',
        lead_id        INT DEFAULT NULL,
        amount         DECIMAL(10,2) NOT NULL,
        currency       CHAR(3) DEFAULT 'EUR',
        status         VARCHAR(30)  DEFAULT 'pending',
        mollie_id      VARCHAR(100),
        mollie_method  VARCHAR(50)  DEFAULT '',
        items          TEXT,
        funnel_step    VARCHAR(50)  DEFAULT 'livre',
        session_token  CHAR(32),
        downloaded_at  DATETIME DEFAULT NULL,
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_mollie (mollie_id),
        INDEX idx_session (session_token),
        INDEX idx_status (status),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

header('Content-Type: text/html; charset=utf-8');
echo "<body style='font-family:monospace;background:#0d1117;color:#58a6ff;padding:40px'>";
echo "<h2>Installation, Neuro-Odyssée Livre</h2>";
try {
    $db = db();
    foreach ($sql as $s) {
        $db->exec($s);
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $s, $m);
        echo "<p>✅ Table <strong>{$m[1]}</strong> OK</p>";
    }
    echo "<p style='color:#3fb950;margin-top:20px'><strong>✅ Installation terminée. Supprime ce fichier.</strong></p>";
} catch (Exception $e) {
    echo "<p style='color:#f85149'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</body>";
