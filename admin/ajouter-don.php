<?php
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$montant = (float) ($_POST['montant'] ?? 0);
$km      = (int)   ($_POST['km']      ?? 0);

if ($montant <= 0 && $km <= 0) {
    header('Location: dashboard.php?error=montant_invalide');
    exit;
}

$statsFile = __DIR__ . '/../data/stats.json';
$stats = json_decode(file_get_contents($statsFile), true);

if ($montant > 0) {
    $stats['collectes'] = ($stats['collectes'] ?? 0) + $montant;
    $stats['dons']      = ($stats['dons']      ?? 0) + 1;
}
if ($km > 0) {
    $stats['km_paraines'] = ($stats['km_paraines'] ?? 0) + $km;
}

file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

header('Location: dashboard.php?success=don_ajoute');
exit;
