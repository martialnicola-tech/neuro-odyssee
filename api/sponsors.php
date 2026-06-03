<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$page = $_GET['page'] ?? '';
$file = __DIR__ . '/../data/sponsors.json';

$sponsors = [];
if (file_exists($file)) {
    $sponsors = json_decode(file_get_contents($file), true) ?? [];
}

// Filtrer par page si demandé
if ($page) {
    $sponsors = array_values(array_filter($sponsors, function($s) use ($page) {
        return in_array($page, $s['pages'] ?? []);
    }));
}

// Trier par ordre
usort($sponsors, function($a, $b) {
    return ($a['order'] ?? 99) - ($b['order'] ?? 99);
});

echo json_encode($sponsors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
