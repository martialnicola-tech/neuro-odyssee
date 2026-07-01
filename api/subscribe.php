<?php
/**
 * API inscription newsletter — Club Neuro-Odyssée
 * POST { email, nom? }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$nom   = trim($data['nom'] ?? '');

// Validation email
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

try {
    $db = getDB();

    // Vérifier si déjà inscrit
    $check = $db->prepare("SELECT id, actif FROM newsletter_subscribers WHERE email = ?");
    $check->execute([$email]);
    $existing = $check->fetch();

    if ($existing) {
        // Réactiver et mettre à jour le nom si fourni
        $sql = "UPDATE newsletter_subscribers SET actif = 1, club = 1, updated_at = NOW()";
        $params = [];
        if ($nom) {
            $sql .= ", nom = ?";
            $params[] = $nom;
        }
        $sql .= " WHERE email = ?";
        $params[] = $email;
        $db->prepare($sql)->execute($params);
    } else {
        // Nouvelle inscription Club
        $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, nom, source, actif, club) VALUES (?, ?, 'formulaire', 1, 1)");
        $stmt->execute([$email, $nom]);
    }

    // Email de bienvenue Club
    envoyerBienvenueClub($email, $nom);

    echo json_encode(['ok' => true, 'message' => 'Bienvenue dans le Club !']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'detail' => $e->getMessage()]);
    error_log('Newsletter subscribe error: ' . $e->getMessage());
}

function envoyerBienvenueClub($email, $nom) {
    $prenom = $nom ?: 'Ami(e) de la Neuro-Odyssee';
    $token  = substr(md5($email . 'neuro-odyssee-unsub'), 0, 16);
    $unsub  = 'https://www.neuro-odyssee.com/api/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;

    $body  = "Bonjour $prenom,\n\n";
    $body .= "Bienvenue dans le Club Neuro-Odyssee !\n\n";
    $body .= "Tu fais maintenant partie d'une communaute de personnes engagees dans le changement.\n\n";
    $body .= "Ce que tu vas recevoir en tant que membre du Club :\n\n";
    $body .= "  - Les coulisses du periple de 2 200 km de Roland\n";
    $body .= "  - Du contenu exclusif sur les neurosciences et le TDAH\n";
    $body .= "  - Des conseils pratiques pour reprendre le controle de ton cerveau\n";
    $body .= "  - Un acces prioritaire aux futurs evenements et au livre\n\n";
    $body .= "Tu veux aller plus loin ? Decouvre les packs de soutien et recois des ebooks exclusifs :\n";
    $body .= "https://www.neuro-odyssee.com/soutenir.html\n\n";
    $body .= "A tres vite sur le chemin,\n";
    $body .= "Roland Crettaz\n";
    $body .= "La Neuro-Odyssee\n";
    $body .= "www.neuro-odyssee.com\n\n";
    $body .= "---\n";
    $body .= "Se desinscrire : $unsub\n";

    $headers  = "From: Roland Crettaz <roland@neuro-odyssee.com>\r\n";
    $headers .= "Reply-To: roland@neuro-odyssee.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, 'Bienvenue dans le Club Neuro-Odyssee !', $body, $headers);
}
