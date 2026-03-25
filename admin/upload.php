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
    'created_at' => time(),
    'published'  => true,
];

$posts[] = $post;
file_put_contents($dataFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: dashboard.php?success=' . urlencode('Article publié avec succès !'));
exit;
