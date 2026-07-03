<?php
/**
 * Précommande du livre, Paiement Mollie
 * Montant fixe côté serveur : CHF 29.90 (prix de précommande)
 * Aucun paramètre client → pas de manipulation de montant possible.
 */
require_once __DIR__ . '/config.php';

const LIVRE_DEVISE  = 'CHF';
const LIVRE_MONTANT = 29.90;

$amountValue = number_format(LIVRE_MONTANT, 2, '.', '');

$payload = json_encode([
    'amount'      => ['currency' => LIVRE_DEVISE, 'value' => $amountValue],
    'description' => 'Précommande, Le Livre du périple, Association Neuro-Odyssée',
    'redirectUrl' => SITE_URL . '/merci.html?type=precommande-livre',
    'cancelUrl'   => SITE_URL . '/index.html?annule=1#documentaire',
    'webhookUrl'  => SITE_URL . '/payment/webhook.php',
    'metadata'    => [
        'type'        => 'precommande_livre',
        'produit'     => 'livre_du_periple',
        'montant_chf' => $amountValue,
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
        header('Location: ' . SITE_URL . '/index.html?erreur=' . urlencode('URL de paiement invalide') . '#documentaire');
        exit;
    }
    header('Location: ' . $checkoutUrl);
} else {
    $erreur = urlencode($result['detail'] ?? $result['title'] ?? 'Erreur de paiement');
    header('Location: ' . SITE_URL . '/index.html?erreur=' . $erreur . '#documentaire');
}
exit;
