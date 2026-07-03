<?php
// ============================================
// API, Téléchargement sécurisé du livre PDF
// GET ?t=TOKEN → sert le PDF + marque le téléchargement en DB
// ============================================
require_once __DIR__ . '/config.php';

$token = trim($_GET['t'] ?? '');

if (!$token || strlen($token) !== 64) {
    http_response_code(403);
    echo 'Lien invalide. Retournez sur la page de téléchargement.';
    exit;
}

$pdfPath = __DIR__ . '/../downloads/la-faim-de-vivre.pdf';

if (!file_exists($pdfPath)) {
    http_response_code(404);
    echo 'Fichier introuvable. Contactez roland@neuro-odyssee.com';
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM acheteurs_livre WHERE token = ?");
    $stmt->execute([$token]);
    $acheteur = $stmt->fetch();

    if (!$acheteur) {
        http_response_code(403);
        echo 'Lien expiré ou invalide. Retournez sur la page de téléchargement.';
        exit;
    }

    // Marque la date de téléchargement
    $db->prepare("UPDATE acheteurs_livre SET downloaded_at = NOW() WHERE token = ?")->execute([$token]);

    // Sert le PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="La-Faim-de-Vivre-Roland-Crettaz.pdf"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($pdfPath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erreur serveur. Contactez roland@neuro-odyssee.com';
}
