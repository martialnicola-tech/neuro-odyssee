<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fb-capi.php';

// ─── API Mollie ────────────────────────────────────────────
function mollie_api(string $method, string $endpoint, array $body = null): array {
    $ch = curl_init('https://api.mollie.com/v2' . $endpoint);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . MOLLIE_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ];
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    curl_setopt_array($ch, $opts);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);
    if ($code < 200 || $code >= 300) {
        throw new Exception('Mollie API ' . $code . ' · ' . ($data['detail'] ?? $res));
    }
    return $data;
}

// ─── Créer une commande + paiement Mollie ─────────────────
function mollie_create_order(array $items, string $email, string $prenom, string $funnelStep): array {
    $total = 0;
    $desc  = [];
    foreach ($items as $sku) {
        if (!isset(PRODUCTS[$sku])) throw new Exception("Produit inconnu: {$sku}");
        $total += PRODUCTS[$sku]['price'];
        $desc[]  = PRODUCTS[$sku]['name'];
    }
    $currency     = PRODUCTS[$items[0]]['currency'] ?? 'EUR';
    $sessionToken = bin2hex(random_bytes(16));

    $db = db();

    // Upsert lead
    $db->prepare(
        'INSERT INTO no_leads (email, prenom, source, status, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE prenom = VALUES(prenom), updated_at = NOW()'
    )->execute([$email, $prenom, 'checkout_' . $funnelStep, 'pending_payment']);

    $s = $db->prepare('SELECT id FROM no_leads WHERE email = ?');
    $s->execute([$email]);
    $leadId = (int) $s->fetchColumn();

    // Insert order
    $db->prepare(
        'INSERT INTO no_orders (email, prenom, lead_id, amount, currency, status, items, funnel_step, session_token, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    )->execute([$email, $prenom, $leadId ?: null, $total, $currency, 'pending', json_encode($items), $funnelStep, $sessionToken]);
    $orderId = (int) $db->lastInsertId();

    $redirectUrl = SITE_URL . "/api/mollie-return.php?order={$orderId}&t={$sessionToken}";
    $webhookUrl  = SITE_URL . '/api/mollie-webhook.php';

    $payment = mollie_api('POST', '/payments', [
        'amount'      => ['currency' => $currency, 'value' => number_format($total, 2, '.', '')],
        'description' => implode(' + ', $desc),
        'redirectUrl' => $redirectUrl,
        'webhookUrl'  => $webhookUrl,
        'metadata'    => ['order_id' => $orderId, 'funnel_step' => $funnelStep],
    ]);

    $db->prepare('UPDATE no_orders SET mollie_id = ? WHERE id = ?')
       ->execute([$payment['id'], $orderId]);

    return [
        'order_id'      => $orderId,
        'session_token' => $sessionToken,
        'checkout_url'  => $payment['_links']['checkout']['href'] ?? null,
        'mollie_id'     => $payment['id'],
    ];
}

// ─── Synchroniser le statut depuis Mollie ─────────────────
function mollie_sync_order(int $orderId): array {
    $db = db();
    $order = $db->prepare('SELECT * FROM no_orders WHERE id = ?');
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order)             throw new Exception('Commande introuvable');
    if (!$order['mollie_id']) return $order;

    $p         = mollie_api('GET', '/payments/' . $order['mollie_id']);
    $newStatus = $p['status'];
    $method    = $p['method'] ?? '';

    if ($newStatus !== $order['status']) {
        $db->prepare('UPDATE no_orders SET status = ?, mollie_method = ?, updated_at = NOW() WHERE id = ?')
           ->execute([$newStatus, $method, $orderId]);

        if ($newStatus === 'paid') {
            // Lead → customer
            if ($order['email']) {
                $db->prepare(
                    'INSERT INTO no_leads (email, prenom, source, status, created_at)
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE status = ?, updated_at = NOW()'
                )->execute([$order['email'], $order['prenom'] ?? '', 'purchase_' . $order['funnel_step'], 'customer', 'customer']);
            }

            // FB CAPI Purchase
            $items = json_decode($order['items'] ?? '[]', true) ?: [];
            fb_capi_send('Purchase', [
                'event_id'         => 'mollie_' . $order['mollie_id'],
                'email'            => $order['email'] ?? '',
                'first_name'       => $order['prenom'] ?? '',
                'event_source_url' => SITE_URL . '/livre/',
                'event_time'       => time(),
                'auto_enrich'      => false,
                'custom_data'      => [
                    'currency'     => $order['currency'] ?? 'EUR',
                    'value'        => (float) $order['amount'],
                    'content_ids'  => $items,
                    'content_type' => 'product',
                ],
            ]);
        }

        $order['status'] = $newStatus;
    }

    return $order;
}
