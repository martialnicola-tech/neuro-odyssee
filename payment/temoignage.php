<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$data    = json_decode(file_get_contents('php://input'), true);
$prenom  = isset($data['prenom'])     ? htmlspecialchars(trim($data['prenom']),     ENT_QUOTES) : '';
$message = isset($data['message'])    ? htmlspecialchars(trim($data['message']),    ENT_QUOTES) : '';
$publier = isset($data['publier'])    ? (bool)$data['publier'] : false;
$montant = isset($data['montant'])    ? intval($data['montant']) : 0;

if (empty($prenom) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Prénom et message requis']);
    exit;
}

$fichier = __DIR__ . '/../data/temoignages.json';
$liste   = file_exists($fichier) ? json_decode(file_get_contents($fichier), true) : [];

$liste[] = [
    'id'       => uniqid(),
    'prenom'   => $prenom,
    'message'  => $message,
    'publier'  => $publier,
    'montant'  => $montant,
    'date'     => date('Y-m-d H:i:s'),
    'approuve' => false,
];

file_put_contents($fichier, json_encode($liste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
