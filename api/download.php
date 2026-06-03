<?php
require_once __DIR__ . '/config.php';

$file    = trim($_GET['file'] ?? '');   // ex: livre
$orderId = (int) ($_GET['oid']  ?? 0);
$token   = trim($_GET['t']     ?? '');

if (!$file || !$orderId || !$token) {
    http_response_code(400); exit('Paramètres manquants.');
}

try {
    $db = db();
    $s  = $db->prepare('SELECT * FROM no_orders WHERE id = ? AND session_token = ? AND status = ?');
    $s->execute([$orderId, $token, 'paid']);
    $order = $s->fetch();

    if (!$order) {
        http_response_code(403);
        exit('Accès refusé. Vérifiez que votre paiement a bien été confirmé.');
    }

    // Vérifie que le fichier demandé est dans les items achetés
    $items    = json_decode($order['items'] ?? '[]', true) ?: [];
    $sku      = 'livre_faim_de_vivre'; // mapping file → sku
    if ($file === 'livre') $sku = 'livre_faim_de_vivre';

    if (!in_array($sku, $items, true)) {
        http_response_code(403); exit('Produit non acheté.');
    }

    $product = PRODUCTS[$sku] ?? null;
    if (!$product) { http_response_code(404); exit('Produit inconnu.'); }

    $pdfPath = __DIR__ . '/../downloads/' . $product['file'];
    if (!file_exists($pdfPath)) {
        http_response_code(404); exit('Fichier introuvable. Contactez support@neuro-odyssee.com');
    }

    // Log le téléchargement
    $db->prepare('UPDATE no_orders SET downloaded_at = NOW() WHERE id = ?')->execute([$orderId]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="La-Faim-de-Vivre-Roland-Crettaz.pdf"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: no-store');
    header('Pragma: no-cache');
    readfile($pdfPath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit('Erreur serveur. Contactez support@neuro-odyssee.com');
}
