<?php
/**
 * Migration — Ajouter colonne 'club' à newsletter_subscribers
 * Exécuter une seule fois puis supprimer
 */
require_once __DIR__ . '/config.php';

try {
    $db = getDB();

    // Ajouter la colonne club (0 = newsletter seule, 1 = membre Club)
    $db->exec("ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS club TINYINT(1) NOT NULL DEFAULT 0 AFTER actif");

    // Marquer les existants inscrits via formulaire Club ou Stripe comme membres
    $db->exec("UPDATE newsletter_subscribers SET club = 1 WHERE source IN ('stripe', 'formulaire')");

    echo "Migration OK. Colonne 'club' ajoutée.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
