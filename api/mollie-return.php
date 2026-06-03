<?php
require_once __DIR__ . '/mollie.php';

$orderId = (int) ($_GET['order'] ?? 0);
$token   = trim($_GET['t'] ?? '');

try {
    $db = db();
    $s  = $db->prepare('SELECT * FROM no_orders WHERE id = ? AND session_token = ?');
    $s->execute([$orderId, $token]);
    $order = $s->fetch();

    if (!$order) { http_response_code(404); exit('Commande introuvable.'); }

    // Sync au cas où le webhook n'est pas encore arrivé
    $order = mollie_sync_order($orderId);

    if ($order['status'] !== 'paid') {
        // Paiement échoué / annulé
        header('Location: ' . SITE_URL . '/livre/?payment=failed', true, 302);
        exit;
    }

    // Paiement OK → page merci avec token
    header('Location: ' . SITE_URL . '/livre/merci.html?oid=' . $orderId . '&t=' . $token, true, 302);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erreur : ' . htmlspecialchars($e->getMessage());
}
