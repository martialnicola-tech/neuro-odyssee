<?php
// ============================================
// API — Enregistrement acheteur livre
// POST { email } → sauvegarde en DB + retourne token de téléchargement
// ============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

try {
    $db = getDB();

    // Crée la table si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS acheteurs_livre (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        downloaded_at DATETIME NULL,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Génère un token unique
    $token = bin2hex(random_bytes(32));

    // Vérifie si l'email existe déjà → met à jour le token (re-téléchargement)
    $stmt = $db->prepare("SELECT id FROM acheteurs_livre WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("UPDATE acheteurs_livre SET token = ?, downloaded_at = NULL WHERE email = ?");
        $stmt->execute([$token, $email]);
    } else {
        $stmt = $db->prepare("INSERT INTO acheteurs_livre (email, token) VALUES (?, ?)");
        $stmt->execute([$email, $token]);
    }

    echo json_encode([
        'success' => true,
        'token'   => $token
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
