<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$company = htmlspecialchars(trim($_POST['company'] ?? ''), ENT_QUOTES);
$name    = htmlspecialchars(trim($_POST['name']    ?? ''), ENT_QUOTES);
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = htmlspecialchars(trim($_POST['phone']   ?? ''), ENT_QUOTES);
$km      = intval($_POST['km'] ?? 0);
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES);
$tier    = htmlspecialchars(trim($_POST['tier']    ?? ''), ENT_QUOTES);

if (!$email) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email invalide']);
    exit;
}

$sujet  = "Demande de sponsoring — " . ($company ?: $name) . ($tier ? " ($tier)" : '');
$corps  = "Nouvelle demande de sponsoring\n\n";
$corps .= "Entreprise : $company\nContact    : $name\nEmail      : $email\n";
if ($phone)   $corps .= "Téléphone  : $phone\n";
if ($km)      $corps .= "Km voulus  : $km km\n";
if ($tier)    $corps .= "Formule    : $tier\n";
if ($message) $corps .= "\nMessage :\n$message\n";

$headers  = "From: Neuro-Odyssée <roland@neuro-odyssee.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$envoye = mail('roland@neuro-odyssee.com', $sujet, $corps, $headers);

if ($envoye) {
    $conf  = "Bonjour $name,\n\nMerci pour votre intérêt pour la Neuro-Odyssée !\nRoland vous répondra dans les 24h.\n\nÀ bientôt,\nRoland Crettaz\nwww.neuro-odyssee.com\n";
    mail($email, "Votre demande — Association Neuro-Odyssée", $conf,
        "From: Roland Crettaz <roland@neuro-odyssee.com>\r\nContent-Type: text/plain; charset=UTF-8\r\n");
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur envoi email']);
}
