<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$id     = $_POST['id'] ?? '';
$fichier = __DIR__ . '/../data/temoignages.json';
$liste  = file_exists($fichier) ? json_decode(file_get_contents($fichier), true) : [];

foreach ($liste as &$t) {
    if ($t['id'] === $id) { $t['approuve'] = true; break; }
}

file_put_contents($fichier, json_encode($liste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: dashboard.php?success=temoignage_approuve');
