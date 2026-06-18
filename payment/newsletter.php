<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://neuro-odyssee.com');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''), ENT_QUOTES);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email invalide']);
    exit;
}

// Sauvegarde abonné
$file = __DIR__ . '/../data/abonnes.json';
$abonnes = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Vérifier doublon
foreach ($abonnes as $a) {
    if ($a['email'] === $email) {
        // Déjà inscrit — renvoyer l'ebook quand même
        break;
    }
}

$abonnes[] = [
    'prenom' => $prenom,
    'email'  => $email,
    'date'   => date('Y-m-d H:i:s'),
];

file_put_contents($file, json_encode($abonnes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Envoi email avec lien ebook
$lien = 'https://neuro-odyssee.com/ebooks/ebook_neuro_odyssee_newsletter.pdf';
$salutation = $prenom ? "Bonjour $prenom," : "Bonjour,";

$sujet = "Votre ebook Association Neuro-Odyssée";

$corps = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f3ee;font-family:Georgia,serif;">
  <div style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1E6B5E 0%,#2d8c7a 100%);padding:32px 40px;text-align:center;">
      <div style="font-family:Georgia,serif;font-size:1.5rem;font-weight:700;color:white;letter-spacing:-0.02em;">Association Neuro-Odyssée</div>
      <div style="color:rgba(255,255,255,0.6);font-size:0.8rem;margin-top:4px;letter-spacing:0.1em;text-transform:uppercase;">Roland Crettaz · St-Maurice → Santiago</div>
    </div>

    <!-- Body -->
    <div style="padding:36px 40px;">
      <p style="font-size:1rem;color:#1a2332;margin:0 0 1rem;">' . $salutation . '</p>
      <p style="font-size:0.95rem;color:#444;line-height:1.8;margin:0 0 1.5rem;">
        Merci pour votre intérêt pour la Neuro-Odyssée. Votre ebook est prêt — cliquez sur le bouton ci-dessous pour le télécharger.
      </p>

      <div style="text-align:center;margin:2rem 0;">
        <a href="' . $lien . '"
           style="display:inline-block;background:#1E6B5E;color:white;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:0.95rem;font-weight:700;letter-spacing:0.03em;">
          📥 Télécharger l\'ebook
        </a>
      </div>

      <p style="font-size:0.85rem;color:#888;line-height:1.7;margin:1.5rem 0 0;">
        Vous recevrez des nouvelles de Roland directement depuis le chemin dès le départ le 21 août 2026. Chaque étape documentée, chaque avancée partagée.
      </p>
    </div>

    <!-- Footer -->
    <div style="background:#f8f6f1;padding:20px 40px;text-align:center;border-top:1px solid #e8e4db;">
      <p style="font-size:0.75rem;color:#aaa;margin:0;">
        Association Neuro-Odyssée · neuro-odyssee.com<br>
        Vous recevez cet email car vous avez demandé l\'ebook sur notre site.
      </p>
    </div>
  </div>
</body>
</html>';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Association Neuro-Odyssée <roland@neuro-odyssee.com>\r\n";
$headers .= "Reply-To: roland@neuro-odyssee.com\r\n";

$envoye = mail($email, $sujet, $corps, $headers);

// Notifier Roland
$notif = "Nouvel abonné newsletter : $prenom <$email> le " . date('d/m/Y à H:i');
mail('roland@neuro-odyssee.com', "Nouvel abonné — Neuro-Odyssée", $notif,
    "From: Association Neuro-Odyssée <roland@neuro-odyssee.com>\r\n");

echo json_encode(['success' => true, 'envoye' => $envoye]);
