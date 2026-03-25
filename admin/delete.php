<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile = __DIR__ . '/../data/posts.json';
$id = $_POST['id'] ?? '';

if ($id && file_exists($dataFile)) {
    $posts = json_decode(file_get_contents($dataFile), true) ?? [];
    $posts = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));
    file_put_contents($dataFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

header('Location: dashboard.php?success=' . urlencode('Article supprimé'));
exit;
