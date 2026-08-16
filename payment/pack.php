<?php
/**
 * Sponsoring, Paiement Mollie
 * Packs fixes : ?pack=bronze|argent|or|platine
 * Montant libre : ?km=50  (calculateur)
 */
require_once __DIR__ . '/config.php';

const PRIX_KM = 25.79;   // € par km parrainé (490€ / 19 km = base Bronze)

$PACKS = [
    'bronze'  => ['prix' => 590,  'km' => 19,  'label' => 'Pack Bronze, 19 km'],
    'argent'  => ['prix' => 1490, 'km' => 57,  'label' => 'Pack Argent, 57 km'],
    'or'      => ['prix' => 2990, 'km' => 114, 'label' => 'Pack Or, 114 km'],
    'platine' => ['prix' => 4990, 'km' => 190, 'label' => 'Pack Platine, 190 km'],
];

$pack = preg_replace('/[^a-z]/', '', strtolower($_GET['pack'] ?? ''));
$km   = isset($_GET['km']) ? max(1, min(2200, intval($_GET['km']))) : 0;

if ($pack && isset($PACKS[$pack])) {
    // Pack fixe
    $amount      = $PACKS[$pack]['prix'];
    $km_label    = $PACKS[$pack]['km'];
    $description = $PACKS[$pack]['label'] . ', Association Neuro-Odyssée';
    $meta_type   = 'sponsoring_pack';
} elseif ($km > 0) {
    // Montant calculé depuis le nombre de km
    $amount      = max(27, (int) round($km * PRIX_KM));
    $km_label    = $km;
    $description = 'Parrainage ' . $km . ' km, Association Neuro-Odyssée';
    $meta_type   = 'sponsoring_libre';
    $pack        = 'libre';
} else {
    // Paramètres manquants → retour sponsoring
    header('Location: ' . SITE_URL . '/sponsoring.html');
    exit;
}

$amountValue = number_format($amount, 2, '.', '');

$payload = json_encode([
    'amount'      => ['currency' => 'EUR', 'value' => $amountValue],
    'description' => $description,
    'redirectUrl' => SITE_URL . '/merci.html?type=sponsoring&pack=' . urlencode($pack) . '&montant=' . $amount . '&km=' . $km_label,
    'cancelUrl'   => SITE_URL . '/sponsoring.html?annule=1',
    'webhookUrl'  => SITE_URL . '/payment/webhook.php',
    'metadata'    => [
        'type'        => $meta_type,
        'pack'        => $pack,
        'km'          => $km_label,
        'montant_eur' => $amount,
    ],
]);

$ch = curl_init('https://api.mollie.com/v2/payments');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MOLLIE_API_KEY,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 201 && isset($result['_links']['checkout']['href'])) {
    $checkoutUrl  = $result['_links']['checkout']['href'];
    $checkoutHost = parse_url($checkoutUrl, PHP_URL_HOST);
    // Vérification que l'URL de checkout appartient bien à Mollie
    if (!$checkoutHost || !str_ends_with($checkoutHost, 'mollie.com')) {
        header('Location: ' . SITE_URL . '/sponsoring.html?erreur=' . urlencode('URL de paiement invalide'));
        exit;
    }
    header('Location: ' . $checkoutUrl);
} else {
    // Erreur → retour avec message
    $erreur = urlencode($result['detail'] ?? $result['title'] ?? 'Erreur de paiement');
    header('Location: ' . SITE_URL . '/sponsoring.html?erreur=' . $erreur);
}
exit;
