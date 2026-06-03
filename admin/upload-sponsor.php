<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile  = __DIR__ . '/../data/sponsors.json';
$uploadDir = __DIR__ . '/../images/sponsors/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$name    = trim($_POST['name']    ?? '');
$tagline = trim($_POST['tagline'] ?? '');
$url     = trim($_POST['url']     ?? '');
$pages   = $_POST['pages'] ?? [];
$order   = max(1, intval($_POST['order'] ?? 99));

if (empty($name)) {
    header('Location: sponsors.php?error=Nom+du+sponsor+obligatoire');
    exit;
}

// Valider les pages
$pages = array_values(array_intersect((array)$pages, ['accueil', 'preparation']));
if (empty($pages)) $pages = ['accueil'];

// Validation URL
$urlClean = '';
if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
    $urlClean = $url;
}

// Upload logo
$logoPath = '';
if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
        if ($_FILES['logo']['size'] <= 5 * 1024 * 1024) { // 5 Mo max
            $newName = 'sponsor_' . uniqid('', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $newName)) {
                $logoPath = 'images/sponsors/' . $newName;
            }
        }
    }
}

$sponsors = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?? [])
    : [];

$sponsors[] = [
    'id'         => uniqid('sp_'),
    'name'       => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'tagline'    => htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8'),
    'logo'       => $logoPath,
    'url'        => $urlClean,
    'pages'      => $pages,
    'order'      => $order,
    'created_at' => time(),
];

file_put_contents($dataFile, json_encode($sponsors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: sponsors.php?success=' . urlencode('Sponsor ajouté avec succès !'));
exit;
