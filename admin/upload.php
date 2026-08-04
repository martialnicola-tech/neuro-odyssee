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

// --- Épingler automatiquement la 1re photo du post sur la carte interactive ---
$pinMsg = '';
$firstImg = null;
foreach ($uploadedFiles as $f) {
    $e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($e, ['jpg', 'jpeg', 'png', 'webp'])) { $firstImg = $f; break; }
}
if ($firstImg) {
    $lat = null; $lng = null; $src = '';
    // 1) EXIF GPS de la photo
    $imgPath = __DIR__ . '/../' . $firstImg;
    if (function_exists('exif_read_data') && in_array(strtolower(pathinfo($firstImg, PATHINFO_EXTENSION)), ['jpg', 'jpeg'])) {
        $exif = @exif_read_data($imgPath);
        if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
            $toDec = function ($coord, $ref) {
                if (!is_array($coord) || count($coord) < 3) return null;
                $p = [];
                foreach ($coord as $c) {
                    if (strpos($c, '/') !== false) { [$n, $d] = explode('/', $c); $p[] = $d ? $n / $d : 0; }
                    else { $p[] = (float)$c; }
                }
                $dec = $p[0] + $p[1] / 60 + $p[2] / 3600;
                return in_array($ref, ['S', 'W']) ? -$dec : $dec;
            };
            $lat = $toDec($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
            $lng = $toDec($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');
            $src = 'exif';
        }
    }
    // 2) Sinon : dernière position GPS du suivi
    if ($lat === null || $lng === null) {
        $trk = json_decode(@file_get_contents(__DIR__ . '/../data/tracking.json'), true);
        if (!empty($trk['current']['lat'])) {
            $lat = $trk['current']['lat']; $lng = $trk['current']['lng']; $src = 'gps';
        }
    }
    if ($lat !== null && $lng !== null) {
        $mapFile = __DIR__ . '/../data/photos-carte.json';
        $mapPhotos = file_exists($mapFile) ? (json_decode(file_get_contents($mapFile), true) ?? []) : [];
        $mapPhotos[] = [
            'id'      => 'ph_' . $post['id'],
            'post_id' => $post['id'],
            'img'     => $firstImg,
            'caption' => $post['title'],
            'lat'     => round((float)$lat, 5),
            'lng'     => round((float)$lng, 5),
            'time'    => time(),
            'pos_src' => $src,
        ];
        file_put_contents($mapFile, json_encode($mapPhotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $pinMsg = ' Photo épinglée sur la carte 📍';
    } else {
        $pinMsg = ' (pas de position GPS, photo non épinglée sur la carte)';
    }
}

header('Location: dashboard.php?success=' . urlencode('Article publié avec succès !' . $pinMsg));
exit;
