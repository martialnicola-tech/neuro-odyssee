<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile  = __DIR__ . '/../data/posts.json';
$uploadDir = __DIR__ . '/../images/posts/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$id       = trim($_POST['id']       ?? '');
$type     = trim($_POST['type']     ?? 'journal');
$title    = trim($_POST['title']    ?? '');
$content  = trim($_POST['content']  ?? '');
$location = trim($_POST['location'] ?? '');
$km       = trim($_POST['km']       ?? '');
$youtube  = trim($_POST['youtube']  ?? '');
$url      = trim($_POST['url']      ?? '');
$cropRaw  = trim($_POST['crop'] ?? '50');
$crop     = is_numeric($cropRaw) ? max(0, min(100, (int)$cropRaw)) : 50;

if (!$id || empty($title)) {
    header('Location: dashboard.php?error=Données+invalides');
    exit;
}

// Extraire l'ID YouTube
$youtubeId = '';
if ($youtube) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/|youtube\.com\/live\/)([a-zA-Z0-9_-]{11})/', $youtube, $m)) {
        $youtubeId = $m[1];
    } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube)) {
        $youtubeId = $youtube;
    }
}

// Upload de nouvelles photos
$newFiles = [];
if (!empty($_FILES['photos']['name'][0])) {
    $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov'];
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
        if (!$tmpName) continue;
        $origName = $_FILES['photos']['name'][$i];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        if ($_FILES['photos']['size'][$i] > 25 * 1024 * 1024) continue;
        $newName = uniqid('img_', true) . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
            $newFiles[] = 'images/posts/' . $newName;
        }
    }
}

$posts = [];
if (file_exists($dataFile)) {
    $posts = json_decode(file_get_contents($dataFile), true) ?? [];
}

$found = false;
foreach ($posts as &$p) {
    if ($p['id'] === $id) {
        $p['type']     = in_array($type, ['journal','etape','entrainement','neuro']) ? $type : 'journal';
        $p['title']    = htmlspecialchars($title);
        $p['content']  = htmlspecialchars($content);
        $p['location'] = htmlspecialchars($location);
        $p['km']       = htmlspecialchars($km);
        $p['youtube']  = $youtubeId;
        $p['url']      = htmlspecialchars($url);
        $p['crop']     = $crop;
        // Ajouter les nouvelles photos aux images existantes
        if (!empty($newFiles)) {
            $existing = $p['images'] ?? [];
            $p['images'] = array_merge($existing, $newFiles);
        }
        $p['updated_at'] = time();
        $found = true;
        break;
    }
}

if (!$found) {
    header('Location: dashboard.php?error=Publication+introuvable');
    exit;
}

file_put_contents($dataFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: dashboard.php?success=' . urlencode('Publication modifiée avec succès !'));
exit;
