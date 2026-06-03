<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile  = __DIR__ . '/../data/sponsors.json';
$uploadDir = __DIR__ . '/../images/sponsors/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$id      = trim($_POST['id']      ?? '');
$name    = trim($_POST['name']    ?? '');
$tagline = trim($_POST['tagline'] ?? '');
$url     = trim($_POST['url']     ?? '');
$pages   = $_POST['pages'] ?? [];
$order   = max(1, intval($_POST['order'] ?? 99));

if (!$id || empty($name)) {
    header('Location: sponsors.php?error=Données+invalides');
    exit;
}

$pages = array_values(array_intersect((array)$pages, ['accueil', 'preparation']));
if (empty($pages)) $pages = ['accueil'];

$urlClean = '';
if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
    $urlClean = $url;
}

$sponsors = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?? [])
    : [];

$found = false;
foreach ($sponsors as &$s) {
    if ($s['id'] === $id) {
        $s['name']       = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
        $s['tagline']    = htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8');
        $s['url']        = $urlClean;
        $s['pages']      = $pages;
        $s['order']      = $order;
        $s['updated_at'] = time();

        // Nouveau logo si fourni
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) && $_FILES['logo']['size'] <= 5 * 1024 * 1024) {
                $newName = 'sponsor_' . uniqid('', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $newName)) {
                    // Supprimer l'ancien logo uploadé via admin (préfixe sponsor_)
                    if (!empty($s['logo'])) {
                        $old = __DIR__ . '/../' . $s['logo'];
                        if (file_exists($old) && strpos(basename($old), 'sponsor_') === 0) {
                            unlink($old);
                        }
                    }
                    $s['logo'] = 'images/sponsors/' . $newName;
                }
            }
        }

        $found = true;
        break;
    }
}
unset($s);

if (!$found) {
    header('Location: sponsors.php?error=Sponsor+introuvable');
    exit;
}

file_put_contents($dataFile, json_encode($sponsors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: sponsors.php?success=' . urlencode('Sponsor modifié avec succès !'));
exit;
