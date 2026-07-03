<?php
/**
 * API inscription Newsletter seule (pas Club)
 * POST { email }
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

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

try {
    $db = getDB();

    $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        // Déjà inscrit, réactiver et mettre à jour le nom si fourni
        if ($nom) {
            $db->prepare("UPDATE newsletter_subscribers SET actif = 1, nom = ?, updated_at = NOW() WHERE email = ?")->execute([$nom, $email]);
        } else {
            $db->prepare("UPDATE newsletter_subscribers SET actif = 1, updated_at = NOW() WHERE email = ?")->execute([$email]);
        }
    } else {
        // Nouvelle inscription newsletter seule (club = 0)
        $db->prepare("INSERT INTO newsletter_subscribers (email, nom, source, actif, club) VALUES (?, ?, 'newsletter', 1, 0)")->execute([$email, $nom]);
    }

    // Email de bienvenue Newsletter
    envoyerBienvenueNewsletter($email, $nom);

    echo json_encode(['ok' => true, 'message' => 'Inscription confirmée !']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
    error_log('Newsletter subscribe error: ' . $e->getMessage());
}

function envoyerBienvenueNewsletter($email, $nom = '') {
    $prenom = $nom ?: 'Ami(e) de la Neuro-Odyssée';
    $token = substr(md5($email . 'neuro-odyssee-unsub'), 0, 16);
    $unsub = 'https://www.neuro-odyssee.com/api/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;

    $body  = "Bonjour $prenom,\n\n";
    $body .= "Merci pour ton inscription a la newsletter de La Neuro-Odyssee !\n\n";
    $body .= "Tu recevras regulierement :\n\n";
    $body .= "  - Les actualites du periple de Roland (2 200 km vers Santiago)\n";
    $body .= "  - Des articles sur les neurosciences, le TDAH et la reconstruction\n";
    $body .= "  - Les nouvelles videos et publications du journal de bord\n\n";
    $body .= "Tu veux aller plus loin ? Rejoins le Club Neuro-Odyssee pour des avantages exclusifs :\n";
    $body .= "https://www.neuro-odyssee.com/club.html\n\n";
    $body .= "A bientot,\n";
    $body .= "Roland Crettaz\n";
    $body .= "La Neuro-Odyssee\n";
    $body .= "www.neuro-odyssee.com\n\n";
    $body .= "---\n";
    $body .= "Se desinscrire : $unsub\n";

    $headers  = "From: Roland Crettaz <roland@neuro-odyssee.com>\r\n";
    $headers .= "Reply-To: roland@neuro-odyssee.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, 'Bienvenue sur La Neuro-Odyssee !', $body, $headers);
}
