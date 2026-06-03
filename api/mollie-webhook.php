<?php
require_once __DIR__ . '/mollie.php';

$mollieId = $_POST['id'] ?? '';
if (!$mollieId) { http_response_code(400); exit('missing id'); }

try {
    $db = db();
    $s  = $db->prepare('SELECT id FROM no_orders WHERE mollie_id = ?');
    $s->execute([$mollieId]);
    $orderId = (int) $s->fetchColumn();

    if ($orderId) mollie_sync_order($orderId);

    http_response_code(200);
    echo 'ok';
} catch (Exception $e) {
    error_log('webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo 'error';
}
