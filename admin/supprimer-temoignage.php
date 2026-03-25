<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$id      = $_POST['id'] ?? '';
$fichier = __DIR__ . '/../data/temoignages.json';
$liste   = file_exists($fichier) ? json_decode(file_get_contents($fichier), true) : [];

$liste = array_values(array_filter($liste, fn($t) => $t['id'] !== $id));

file_put_contents($fichier, json_encode($liste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: dashboard.php?success=temoignage_supprime');
