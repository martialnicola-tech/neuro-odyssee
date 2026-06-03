<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : '*'));

require_once __DIR__ . '/mollie.php';

try {
    $body   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $items  = $body['items'] ?? ['livre_faim_de_vivre'];
    $email  = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $prenom = mb_substr(trim($body['prenom'] ?? ''), 0, 100);
    $step   = $body['funnel_step'] ?? 'livre';

    if (!$email)          throw new Exception('Email invalide');
    if (empty($items))    throw new Exception('Aucun produit');

    $result = mollie_create_order($items, $email, $prenom, $step);

    echo json_encode(['success' => true] + $result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
