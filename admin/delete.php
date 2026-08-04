<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile = __DIR__ . '/../data/posts.json';
$id = $_POST['id'] ?? '';

if ($id && file_exists($dataFile)) {
    $posts = json_decode(file_get_contents($dataFile), true) ?? [];
    $posts = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));
    file_put_contents($dataFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Retirer aussi l'épingle correspondante de la carte interactive
    $mapFile = __DIR__ . '/../data/photos-carte.json';
    if (file_exists($mapFile)) {
        $mapPhotos = json_decode(file_get_contents($mapFile), true) ?? [];
        $mapPhotos = array_values(array_filter($mapPhotos, fn($p) => ($p['post_id'] ?? '') !== $id));
        file_put_contents($mapFile, json_encode($mapPhotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}

header('Location: dashboard.php?success=' . urlencode('Article supprimé'));
exit;
