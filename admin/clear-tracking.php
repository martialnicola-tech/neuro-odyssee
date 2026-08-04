<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// Effacer la position GPS affichée (remet le suivi à zéro)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = __DIR__ . '/../data/tracking.json';
    file_put_contents($file, json_encode(['current' => null, 'trail' => []], JSON_UNESCAPED_SLASHES), LOCK_EX);
    header('Location: dashboard.php?success=' . urlencode('Position GPS effacée (le point a disparu de la carte).'));
} else {
    header('Location: dashboard.php');
}
exit;
