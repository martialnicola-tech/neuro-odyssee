<?php
// Stripe Webhook — met à jour stats.json automatiquement
require_once 'config.php';

$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Vérification signature Stripe (sécurité)
if (defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET) {
    $parts     = [];
    foreach (explode(',', $sig) as $part) {
        [$k, $v] = explode('=', $part, 2);
        $parts[$k][] = $v;
    }
    $timestamp = $parts['t'][0] ?? 0;
    $expected  = hash_hmac('sha256', $timestamp . '.' . $payload, STRIPE_WEBHOOK_SECRET);
    if (!hash_equals($expected, $parts['v1'][0] ?? '')) {
        http_response_code(400);
        exit('Signature invalide');
    }
}

$event = json_decode($payload, true);

// On ne traite que les paiements réussis
if (($event['type'] ?? '') !== 'checkout.session.completed') {
    http_response_code(200);
    exit('ignored');
}

$session = $event['data']['object'];
$montant = intval($session['amount_total'] ?? 0) / 100; // centimes → €

// Mise à jour stats.json
$fichier = __DIR__ . '/../data/stats.json';
$stats   = file_exists($fichier)
    ? (json_decode(file_get_contents($fichier), true) ?? [])
    : [];

$stats['collectes'] = ($stats['collectes'] ?? 0) + $montant;
$stats['dons']      = ($stats['dons']      ?? 0) + 1;

// Initialiser les champs manquants
$stats['km_paraines'] = $stats['km_paraines'] ?? 0;
$stats['sponsors']    = $stats['sponsors']    ?? 0;
$stats['km_marches']  = $stats['km_marches']  ?? 0;

file_put_contents($fichier, json_encode($stats, JSON_PRETTY_PRINT));

http_response_code(200);
echo json_encode(['ok' => true, 'collectes' => $stats['collectes']]);
