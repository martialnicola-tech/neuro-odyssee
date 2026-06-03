<?php
/**
 * Désinscription newsletter
 * GET ?email=xxx&token=xxx
 */
require_once __DIR__ . '/config.php';

$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? '');

// Token = hash simple pour éviter les abus
$expected = substr(md5($email . 'neuro-odyssee-unsub'), 0, 16);

if (!$email || $token !== $expected) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:4rem;">
    <h2>Lien invalide</h2><p>Contactez roland@neuro-odyssee.com</p></body></html>';
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE newsletter_subscribers SET actif = 0 WHERE email = :email");
    $stmt->execute([':email' => $email]);

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
    <body style="font-family:sans-serif;text-align:center;padding:4rem;background:#f8f6f1;">
    <h2 style="color:#1E6B5E;">Désinscription confirmée</h2>
    <p>Vous ne recevrez plus les emails du Club Neuro-Odyssée.</p>
    <p style="margin-top:2rem;"><a href="https://www.neuro-odyssee.com" style="color:#1E6B5E;">Retour au site</a></p>
    </body></html>';

} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur. Contactez roland@neuro-odyssee.com';
}
