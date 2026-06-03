<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Validation
$data   = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? intval($data['amount']) : 0;
$pack   = isset($data['pack']) ? preg_replace('/[^a-z]/', '', $data['pack']) : '';

if ($amount < 1 || $amount > 10000) {
    http_response_code(400);
    echo json_encode(['error' => 'Montant invalide (1–10 000 €)']);
    exit;
}

$packNames = [
    'bronze'  => 'Pack Bronze — ½ km — La Neuro-Odyssée',
    'argent'  => 'Pack Argent — 1 km — La Neuro-Odyssée',
    'or'      => 'Pack Or — 5 km — La Neuro-Odyssée',
    'platine' => 'Pack Platine — 10 km — La Neuro-Odyssée',
];
$description = $pack && isset($packNames[$pack])
    ? $packNames[$pack]
    : 'Don — La Neuro-Odyssée';

// Mollie: amount.value doit être une string avec 2 décimales
$amountValue = number_format($amount, 2, '.', '');

$payload = json_encode([
    'amount'      => ['currency' => 'EUR', 'value' => $amountValue],
    'description' => $description,
    'redirectUrl' => SITE_URL . '/merci.html?type=don&montant=' . $amount . ($pack ? '&pack=' . $pack : ''),
    'cancelUrl'   => SITE_URL . '/soutenir.html?annule=1',
    'webhookUrl'  => SITE_URL . '/payment/webhook.php',
    'metadata'    => [
        'type'        => $pack ? 'pack' : 'don',
        'pack'        => $pack ?: 'libre',
        'montant_eur' => $amount,
    ],
]);

$ch = curl_init('https://api.mollie.com/v2/payments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . MOLLIE_API_KEY,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 201 && isset($result['_links']['checkout']['href'])) {
    echo json_encode(['url' => $result['_links']['checkout']['href']]);
} else {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Erreur Mollie',
        'detail' => $result['detail'] ?? $result['title'] ?? 'Erreur inconnue',
    ]);
}
