<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Validation
$data   = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? intval($data['amount']) : 0;

if ($amount < 1 || $amount > 10000) {
    http_response_code(400);
    echo json_encode(['error' => 'Montant invalide (1–10 000 €)']);
    exit;
}

// Appel API Stripe via cURL (pas de SDK nécessaire)
$payload = [
    'payment_method_types[0]'                        => 'card',
    'line_items[0][price_data][currency]'            => 'eur',
    'line_items[0][price_data][product_data][name]'  => 'Don — La Neuro-Odyssée',
    'line_items[0][price_data][product_data][description]' => 'Soutien au périple de 1 600 km de Roland Crettaz',
    'line_items[0][price_data][unit_amount]'         => $amount * 100, // en centimes
    'line_items[0][quantity]'                        => '1',
    'mode'                       => 'payment',
    'success_url'                => SITE_URL . '/merci.html?type=don&montant=' . $amount,
    'cancel_url'                 => SITE_URL . '/soutenir.html?annule=1',
    'metadata[type]'             => 'don',
    'metadata[montant_eur]'      => $amount,
];

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['url'])) {
    echo json_encode(['url' => $result['url']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur Stripe', 'detail' => $result['error']['message'] ?? '']);
}
