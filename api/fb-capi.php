<?php
require_once __DIR__ . '/config.php';

function fb_capi_hash(string $v): string { return hash('sha256', strtolower(trim($v))); }

function fb_capi_send(string $eventName, array $opts = []): bool {
    if (!defined('FB_PIXEL_ID') || FB_PIXEL_ID === '' || FB_CAPI_TOKEN === '') return false;

    $user_data = [];
    if (!empty($opts['email']))      $user_data['em'] = fb_capi_hash($opts['email']);
    if (!empty($opts['first_name'])) $user_data['fn'] = fb_capi_hash($opts['first_name']);

    $autoEnrich = $opts['auto_enrich'] ?? true;
    if ($autoEnrich) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (str_contains($ip, ',')) $ip = trim(explode(',', $ip)[0]);
        if ($ip)                                     $user_data['client_ip_address'] = $ip;
        if (!empty($_SERVER['HTTP_USER_AGENT']))      $user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        if (!empty($_COOKIE['_fbp']))                 $user_data['fbp'] = $_COOKIE['_fbp'];
        if (!empty($_COOKIE['_fbc']))                 $user_data['fbc'] = $_COOKIE['_fbc'];
    }

    if (empty($user_data)) return false;

    $event = [
        'event_name'       => $eventName,
        'event_time'       => $opts['event_time'] ?? time(),
        'action_source'    => 'website',
        'event_source_url' => $opts['event_source_url'] ?? SITE_URL . '/livre/',
        'user_data'        => $user_data,
    ];
    if (!empty($opts['event_id']))   $event['event_id']   = $opts['event_id'];
    if (!empty($opts['custom_data'])) $event['custom_data'] = $opts['custom_data'];

    $payload = ['data' => [$event]];
    if (FB_TEST_EVENT_CODE !== '') $payload['test_event_code'] = FB_TEST_EVENT_CODE;

    $ch = curl_init('https://graph.facebook.com/v19.0/' . FB_PIXEL_ID . '/events?access_token=' . urlencode(FB_CAPI_TOKEN));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) error_log("fb-capi [{$eventName}]: HTTP {$code} · {$res}");
    return $code >= 200 && $code < 300;
}
