<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$prenom     = htmlspecialchars(trim($input['prenom'] ?? ''));
$entreprise = htmlspecialchars(trim($input['entreprise'] ?? ''));
$email      = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telephone  = htmlspecialchars(trim($input['telephone'] ?? ''));
$sujet      = htmlspecialchars(trim($input['sujet'] ?? ''));
$message    = htmlspecialchars(trim($input['message'] ?? ''));

if (!$prenom || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
    exit;
}

$sujets = [
    'sponsoring'  => 'Partenariat / Sponsoring',
    'media'       => 'Presse / Médias',
    'coaching'    => 'Accompagnement personnel',
    'conference'  => 'Conférence / Intervention',
    'autre'       => 'Autre'
];
$sujetLabel = $sujets[$sujet] ?? $sujet;

$to      = 'roland@neuro-odyssee.com';
$subject = "Neuro-Odyssée — {$sujetLabel} de {$prenom}";

$body  = "Nouveau message via le site neuro-odyssee.com\n";
$body .= "========================================\n\n";
$body .= "Prénom    : {$prenom}\n";
if ($entreprise) $body .= "Entreprise: {$entreprise}\n";
$body .= "Email     : {$email}\n";
if ($telephone)  $body .= "Téléphone : {$telephone}\n";
$body .= "Sujet     : {$sujetLabel}\n";
$body .= "\nMessage :\n----------\n{$message}\n";
$body .= "\n========================================\n";
$body .= "Envoyé le : " . date('d/m/Y à H:i') . "\n";

$headers  = "From: noreply@neuro-odyssee.com\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    // Confirmation email to sender
    $confirmSubject = "Votre message à Roland Crettaz — Association Neuro-Odyssée";
    $confirmBody  = "Bonjour {$prenom},\n\n";
    $confirmBody .= "Merci pour votre message. Roland vous répondra dans les 24h.\n\n";
    $confirmBody .= "Votre message :\n--------------\n{$message}\n\n";
    $confirmBody .= "---\nAssociation Neuro-Odyssée — neuro-odyssee.com\n";
    $confirmHeaders  = "From: roland@neuro-odyssee.com\r\n";
    $confirmHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    mail($email, $confirmSubject, $confirmBody, $confirmHeaders);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Mail server error']);
}
