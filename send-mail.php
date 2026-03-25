<?php
/**
 * La Neuro-Odyssée — Formulaire de contact
 * Reçoit les demandes de sponsoring et les envoie à roland@neuro-odyssee.com
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://www.neuro-odyssee.com');
header('Access-Control-Allow-Methods: POST');

// Sécurité : POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// ─── Configuration ───────────────────────────────────────────
$TO      = 'roland@neuro-odyssee.com';
$SUBJECT = '[Neuro-Odyssée] Nouvelle demande de sponsoring';
// ─────────────────────────────────────────────────────────────

// Récupération + nettoyage des champs
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')));
}

$company = clean($_POST['company'] ?? '');
$name    = clean($_POST['name']    ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = clean($_POST['phone']   ?? '');
$km      = intval($_POST['km']     ?? 0);
$tier    = clean($_POST['tier']    ?? '');
$message = clean($_POST['message'] ?? '');

// Validation minimale
if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Nom et email requis']);
    exit;
}

// ─── Construction de l'email ──────────────────────────────────
$km_text = $km > 0 ? "{$km} km" : 'Non précisé';

$body = "Nouvelle demande de sponsoring — La Neuro-Odyssée\n";
$body .= str_repeat('─', 50) . "\n\n";
$body .= "Entreprise   : {$company}\n";
$body .= "Contact      : {$name}\n";
$body .= "Email        : {$email}\n";
$body .= "Téléphone    : " . ($phone ?: 'Non renseigné') . "\n";
$body .= "Formule      : " . ($tier  ?: 'Non précisée') . "\n";
$body .= "Km souhaités : {$km_text}\n";

if (!empty($message)) {
    $body .= "\nMessage :\n{$message}\n";
}

$body .= "\n" . str_repeat('─', 50) . "\n";
$body .= "Envoyé depuis www.neuro-odyssee.com\n";

// Headers email
$headers  = "From: noreply@neuro-odyssee.com\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Envoi
$sent = mail($TO, $SUBJECT, $body, $headers);

if ($sent) {
    // Email de confirmation à l'expéditeur
    $confirm_subject = 'Votre demande — La Neuro-Odyssée';
    $confirm_body  = "Bonjour {$name},\n\n";
    $confirm_body .= "Merci pour votre intérêt pour La Neuro-Odyssée !\n\n";
    $confirm_body .= "Roland Crettaz vous répondra dans les 24 heures à cette adresse.\n\n";
    $confirm_body .= "Votre demande résumée :\n";
    $confirm_body .= "• Entreprise   : {$company}\n";
    $confirm_body .= "• Formule      : " . ($tier ?: 'À définir') . "\n";
    $confirm_body .= "• Km souhaités : {$km_text}\n\n";
    $confirm_body .= "À très bientôt,\nRoland Crettaz\nwww.neuro-odyssee.com\n";

    $confirm_headers  = "From: roland@neuro-odyssee.com\r\n";
    $confirm_headers .= "Reply-To: roland@neuro-odyssee.com\r\n";
    $confirm_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $confirm_subject, $confirm_body, $confirm_headers);

    echo json_encode(['ok' => true, 'message' => 'Message envoyé avec succès']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur — réessayez']);
}
