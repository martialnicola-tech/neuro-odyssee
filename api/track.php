<?php
/**
 * Réception GPS temps réel de Roland (protocole OsmAnd / Traccar Client).
 * L'appli du téléphone envoie : ?key=SECRET&lat=..&lon=..&timestamp=..&speed=..&batt=..
 * Stocke la dernière position + un tracé downsamplé dans data/tracking.json.
 */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

// --- Authentification par token secret ---
// Accepté via ?key= (URL) OU via le champ "id" de Traccar Client (identifiant appareil = token)
$key = $_REQUEST['key'] ?? ($_REQUEST['id'] ?? '');
$authOk = defined('TRACK_KEY') && TRACK_KEY !== '' && hash_equals(TRACK_KEY, (string)$key);

// --- Journal de diagnostic (temporaire, le token est MASQUÉ dans l'URL) ---
$uri = $_SERVER['REQUEST_URI'] ?? '';
$body = @file_get_contents('php://input');
if (defined('TRACK_KEY') && TRACK_KEY !== '') {
    $uri  = str_replace(TRACK_KEY, '{TOKEN}', $uri);
    $body = str_replace(TRACK_KEY, '{TOKEN}', (string)$body);
}
$dbg = date('Y-m-d H:i:s')
    . ' | ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?')
    . ' | ' . ($_SERVER['REQUEST_METHOD'] ?? '?')
    . ' | auth=' . ($authOk ? 'OK' : 'FAIL')
    . ' | uri=' . substr($uri, 0, 200)
    . ' | body=' . substr(trim((string)$body), 0, 120);
$dbgFile = __DIR__ . '/../data/track-debug.log';
$prev = is_file($dbgFile) ? array_slice(file($dbgFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -40) : [];
$prev[] = $dbg;
@file_put_contents($dbgFile, implode("\n", $prev) . "\n", LOCK_EX);

if (!$authOk) { http_response_code(403); exit('forbidden'); }

// --- Coordonnées (GET ou POST, OsmAnd utilise lat/lon) ---
$lat = $_REQUEST['lat'] ?? null;
$lon = $_REQUEST['lon'] ?? ($_REQUEST['lng'] ?? null);
if (!is_numeric($lat) || !is_numeric($lon)) { http_response_code(400); exit('bad coords'); }
$lat = (float)$lat; $lon = (float)$lon;
if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) { http_response_code(400); exit('out of range'); }

// --- Métadonnées optionnelles ---
$speed = isset($_REQUEST['speed']) && is_numeric($_REQUEST['speed']) ? (float)$_REQUEST['speed'] : null;
$batt  = isset($_REQUEST['batt'])  && is_numeric($_REQUEST['batt'])  ? (float)$_REQUEST['batt']  : null;
$ts    = $_REQUEST['timestamp'] ?? time();
if (!is_numeric($ts)) { $ts = strtotime((string)$ts) ?: time(); }
$ts = (int)$ts;

function trk_haversine($la1, $lo1, $la2, $lo2) {
    $R = 6371.0; // km
    $dLa = deg2rad($la2 - $la1);
    $dLo = deg2rad($lo2 - $lo1);
    $a = sin($dLa/2)**2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dLo/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// --- Écriture atomique (verrou) ---
$file = __DIR__ . '/../data/tracking.json';
$fp = @fopen($file, 'c+');
if (!$fp) { http_response_code(500); exit('io'); }
flock($fp, LOCK_EX);
$raw  = stream_get_contents($fp);
$data = json_decode($raw, true);
if (!is_array($data)) { $data = ['current' => null, 'trail' => []]; }
$trail = isset($data['trail']) && is_array($data['trail']) ? $data['trail'] : [];

// Downsampling : n'ajoute au tracé que si déplacement > 150 m depuis le dernier point
$add = true;
if ($trail) {
    $last = end($trail);
    if (is_array($last) && count($last) >= 2) {
        $add = trk_haversine($last[0], $last[1], $lat, $lon) > 0.15;
    }
}
if ($add) { $trail[] = [round($lat, 5), round($lon, 5)]; }
if (count($trail) > 3000) { $trail = array_slice($trail, -3000); }

$data['current'] = [
    'lat'   => round($lat, 5),
    'lng'   => round($lon, 5),
    'time'  => $ts,
    'speed' => $speed !== null ? round($speed, 1) : null,
    'batt'  => $batt,
];
$data['trail'] = $trail;

rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo 'OK';
