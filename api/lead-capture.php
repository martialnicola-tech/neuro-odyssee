<?php
// Capture silencieuse d'un lead (email + prénom)
// Appelé onblur depuis livre/index.html
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : '*'));

require_once __DIR__ . '/config.php';

$email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$prenom = mb_substr(trim($_POST['prenom'] ?? ''), 0, 100);
$source = mb_substr(trim($_POST['source'] ?? 'livre_prospect'), 0, 100);

if (!$email) { echo json_encode(['ok' => false]); exit; }

try {
    db()->prepare(
        'INSERT INTO no_leads (email, prenom, source, status, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
           prenom     = IF(prenom = \'\' AND VALUES(prenom) != \'\', VALUES(prenom), prenom),
           updated_at = NOW()'
    )->execute([$email, $prenom, $source, 'prospect']);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false]);
}
