<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$dataFile   = __DIR__ . '/../data/posts.json';
$uploadDir  = __DIR__ . '/../images/posts/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Load existing posts
$posts = [];
if (file_exists($dataFile)) {
    $posts = json_decode(file_get_contents($dataFile), true) ?? [];
}

$title    = trim($_POST['title']    ?? '');
$content  = trim($_POST['content']  ?? '');
$type     = trim($_POST['type']     ?? 'journal');
$location = trim($_POST['location'] ?? '');
$km       = trim($_POST['km']       ?? '');
$youtube  = trim($_POST['youtube']  ?? '');
$cropRaw  = trim($_POST['crop'] ?? '50');
$crop     = is_numeric($cropRaw) ? max(0, min(100, (int)$cropRaw)) : 50;

// Extraire l'ID YouTube depuis différents formats d'URL
$youtubeId = '';
if ($youtube) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/|youtube\.com\/live\/)([a-zA-Z0-9_-]{11})/', $youtube, $m)) {
        $youtubeId = $m[1];
    } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube)) {
        // ID brut collé directement
        $youtubeId = $youtube;
    }
}

if (empty($title)) {
    header('Location: dashboard.php?error=Titre+obligatoire');
    exit;
}

// Handle file uploads
$uploadedFiles = [];
if (!empty($_FILES['photos']['name'][0])) {
    $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov'];
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
        if (!$tmpName) continue;
        $origName = $_FILES['photos']['name'][$i];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        if ($_FILES['photos']['size'][$i] > 25 * 1024 * 1024) continue; // 25MB max

        $newName = uniqid('img_', true) . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
            $uploadedFiles[] = 'images/posts/' . $newName;
        }
    }
}

// Create post
$post = [
    'id'         => uniqid(),
    'type'       => in_array($type, ['journal','etape','entrainement','neuro']) ? $type : 'journal',
    'title'      => htmlspecialchars($title),
    'content'    => htmlspecialchars($content),
    'location'   => htmlspecialchars($location),
    'km'         => htmlspecialchars($km),
    'images'     => $uploadedFiles,
    'youtube'    => $youtubeId,
    'crop'       => $crop,
    'created_at' => time(),
    'published'  => true,
];

$posts[] = $post;
file_put_contents($dataFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: dashboard.php?success=' . urlencode('Article publié avec succès !'));
exit;
