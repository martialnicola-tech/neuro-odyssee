<?php
/**
 * Installation BDD, exécuter une seule fois puis supprimer
 */
require_once __DIR__ . '/config.php';

try {
    $db = getDB();
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $db->exec($sql);
    echo "✅ Tables créées avec succès !<br>";

    // Vérifier
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables : " . implode(', ', $tables);
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
