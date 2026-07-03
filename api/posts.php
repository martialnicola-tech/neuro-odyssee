<?php
/**
 * API publique, Retourne les posts publiés (JSON)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Essayer plusieurs chemins possibles
$paths = [
    __DIR__ . '/../data/posts.json',
    $_SERVER['DOCUMENT_ROOT'] . '/data/posts.json',
    dirname(__DIR__) . '/data/posts.json',
];

$dataFile = null;
foreach ($paths as $p) {
    if (file_exists($p)) {
        $dataFile = $p;
        break;
    }
}

if (!$dataFile) {
    echo json_encode([]);
    exit;
}

$posts = json_decode(file_get_contents($dataFile), true) ?? [];

// Ne garder que les publiés, trier par date desc
$posts = array_filter($posts, fn($p) => !empty($p['published']));
usort($posts, fn($a, $b) => ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0));

// Nettoyer les données sensibles
$clean = [];
foreach ($posts as $p) {
    $clean[] = [
        'id'         => $p['id'] ?? '',
        'type'       => $p['type'] ?? 'journal',
        'title'      => html_entity_decode($p['title'] ?? '', ENT_QUOTES, 'UTF-8'),
        'content'    => html_entity_decode($p['content'] ?? '', ENT_QUOTES, 'UTF-8'),
        'location'   => html_entity_decode($p['location'] ?? '', ENT_QUOTES, 'UTF-8'),
        'km'         => $p['km'] ?? '',
        'images'     => $p['images'] ?? [],
        'youtube'    => $p['youtube'] ?? '',
        'url'        => $p['url'] ?? '',
        'crop'       => $p['crop'] ?? 'center',
        'created_at' => $p['created_at'] ?? 0,
    ];
}

echo json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
