<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile = __DIR__ . '/../data/sponsors.json';
$id = trim($_POST['id'] ?? '');

if (!$id) {
    header('Location: sponsors.php');
    exit;
}

$sponsors = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?? [])
    : [];

// Supprimer le fichier logo si ce n'est pas un logo pré-existant
foreach ($sponsors as $s) {
    if ($s['id'] === $id && !empty($s['logo'])) {
        $logoFile = __DIR__ . '/../' . $s['logo'];
        // Ne supprimer que les logos uploadés via l'admin (préfixe sponsor_)
        if (file_exists($logoFile) && strpos(basename($logoFile), 'sponsor_') === 0) {
            unlink($logoFile);
        }
    }
}

$sponsors = array_values(array_filter($sponsors, fn($s) => $s['id'] !== $id));
file_put_contents($dataFile, json_encode($sponsors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: sponsors.php?success=' . urlencode('Sponsor supprimé.'));
exit;
