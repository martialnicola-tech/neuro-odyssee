<?php
/**
 * Mollie Webhook, Association Neuro-Odyssée
 * 1) Vérifie le statut du paiement via l'API Mollie
 * 2) Met à jour stats.json
 * 3) Envoie les PDF par email selon le pack acheté
 */
require_once 'config.php';

// Répondre 200 immédiatement, Mollie n'attend pas
http_response_code(200);
echo json_encode(['ok' => true]);

// Fermer la connexion pour que Mollie ne timeout pas
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ob_end_flush();
    flush();
}

// Mollie peut envoyer soit un JSON body (nouveau format Aperçu) soit POST id (ancien format)
$rawBody = file_get_contents('php://input');
$bodyJson = json_decode($rawBody, true);

// Nouveau format : payload JSON complet
if ($bodyJson && isset($bodyJson['resource'])) {
    $resource = $bodyJson['resource']; // 'payment-link' ou 'payment'

    // Payment Link API : chercher le paiement dans les _embedded
    if ($resource === 'payment-link') {
        $payments = $bodyJson['_embedded']['payments'] ?? [];
        $payment  = null;
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'paid') {
                $payment = $p;
                break;
            }
        }
        if (!$payment) {
            http_response_code(200);
            exit('no paid payment found');
        }
    } elseif ($resource === 'payment') {
        $payment = $bodyJson;
        if (($payment['status'] ?? '') !== 'paid') {
            http_response_code(200);
            exit('ignored');
        }
    } else {
        http_response_code(200);
        exit('ignored');
    }
} else {
    // Ancien format : POST avec id
    $paymentId = $_POST['id'] ?? $bodyJson['id'] ?? '';
    if (!$paymentId) {
        http_response_code(400);
        exit('Missing payment id');
    }

    $ch = curl_init('https://api.mollie.com/v2/payments/' . $paymentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . MOLLIE_API_KEY]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) { http_response_code(500); exit('Mollie API error'); }

    $payment = json_decode($response, true);
    if (($payment['status'] ?? '') !== 'paid') {
        http_response_code(200);
        exit('ignored');
    }
}

$montant     = floatval($payment['amount']['value'] ?? 0);
$description = strtolower($payment['description'] ?? '');

// Identifier le pack depuis la description du lien Mollie
if (str_contains($description, 'platine') || $montant >= 99) {
    $pack = 'platine';
} elseif (str_contains($description, 'or') || $montant >= 59) {
    $pack = 'or';
} elseif (str_contains($description, 'argent') || $montant >= 29) {
    $pack = 'argent';
} elseif (str_contains($description, 'bronze') || $montant >= 19) {
    $pack = 'bronze';
} else {
    $pack = 'libre';
}

// Récupérer l'email du client (disponible selon la méthode de paiement)
$email = $payment['details']['consumerEmail']
      ?? $payment['billingAddress']['email']
      ?? $payment['shippingAddress']['email']
      ?? '';

// ─── 1) Mise à jour stats.json ──────────────────────────────
$fichier = __DIR__ . '/../data/stats.json';
$stats   = file_exists($fichier)
    ? (json_decode(file_get_contents($fichier), true) ?? [])
    : [];

$stats['collectes']   = ($stats['collectes']   ?? 0) + $montant;
$stats['dons']        = ($stats['dons']        ?? 0) + 1;
$stats['km_paraines'] = $stats['km_paraines'] ?? 0;
$stats['sponsors']    = $stats['sponsors']    ?? 0;
$stats['km_marches']  = $stats['km_marches']  ?? 0;

file_put_contents($fichier, json_encode($stats, JSON_PRETTY_PRINT));

// ─── 2) Log ─────────────────────────────────────────────────
$logLine = date('Y-m-d H:i:s') . " | $paymentId | {$montant}EUR | $pack | paid\n";
file_put_contents(__DIR__ . '/../data/webhook-log.txt', $logLine, FILE_APPEND | LOCK_EX);

// ─── 3) Envoi PDF si pack ───────────────────────────────────
if ($pack && $pack !== 'libre' && $email) {
    $name = $payment['billingAddress']['givenName'] ?? 'Soutien';
    envoyerPdfPack($pack, $email, $name, $montant);
}

// ─── 4) Auto-inscription Club ───────────────────────────────
if ($email) {
    $name = $payment['billingAddress']['givenName'] ?? 'Soutien';
    inscrireClub($email, $name, $pack, $montant);
}

http_response_code(200);
echo json_encode(['ok' => true]);

// ═══════════════════════════════════════════════════════════════
// FONCTIONS
// ═══════════════════════════════════════════════════════════════
function envoyerPdfPack($pack, $email, $name, $montant) {
    $pdfDir = __DIR__ . '/../pdf';

    $packPdfs = [
        'bronze'  => ['pourquoi-tu-narrive-pas-a-changer.pdf'],
        'argent'  => ['pourquoi-tu-narrive-pas-a-changer.pdf', 'comment-casser-un-automatisme-en-24h.pdf'],
        'or'      => ['pourquoi-tu-narrive-pas-a-changer.pdf', 'comment-casser-un-automatisme-en-24h.pdf', 'protocole-reset-mental-7-jours.pdf'],
        'platine' => ['pourquoi-tu-narrive-pas-a-changer.pdf', 'comment-casser-un-automatisme-en-24h.pdf', 'protocole-reset-mental-7-jours.pdf', 'reprendre-le-controle-de-son-cerveau.pdf'],
    ];

    $pdfs = $packPdfs[$pack] ?? [];
    if (empty($pdfs)) return;

    foreach ($pdfs as $pdf) {
        if (!file_exists($pdfDir . '/' . $pdf)) {
            mail('roland@neuro-odyssee.com',
                '[URGENT] PDF manquant, ' . $pdf,
                "Client $email a payé le pack $pack ($montant EUR) mais $pdf introuvable.\nEnvoyer manuellement à : $email",
                "From: noreply@neuro-odyssee.com\r\nContent-Type: text/plain; charset=UTF-8\r\n"
            );
            return;
        }
    }

    $packLabels = ['bronze' => 'Bronze', 'argent' => 'Argent', 'or' => 'Or', 'platine' => 'Platine'];
    $packLabel  = $packLabels[$pack] ?? $pack;
    $nbPdfs     = count($pdfs);
    $pdfTitles  = [
        'pourquoi-tu-narrive-pas-a-changer.pdf'   => "Pourquoi tu n'arrives pas à changer (et quoi faire)",
        'comment-casser-un-automatisme-en-24h.pdf' => 'Comment casser un automatisme en 24h',
        'protocole-reset-mental-7-jours.pdf'       => 'Le protocole reset mental, 7 jours',
        'reprendre-le-controle-de-son-cerveau.pdf' => 'Reprendre le contrôle de son cerveau',
    ];

    $textBody  = "Bonjour $name,\n\nMerci pour ton soutien à la Neuro-Odyssée !\n\n";
    $textBody .= "Ton Pack $packLabel ($montant EUR) finance des kilomètres réels sur le chemin de Compostelle.\n\n";
    $textBody .= "Tu trouveras en pièce jointe " . ($nbPdfs === 1 ? "ton guide exclusif" : "tes $nbPdfs guides exclusifs") . " :\n\n";
    foreach ($pdfs as $i => $pdf) {
        $textBody .= "  " . ($i + 1) . ". " . ($pdfTitles[$pdf] ?? $pdf) . "\n";
    }
    $textBody .= "\nBonne lecture !\n\nRoland Crettaz\nwww.neuro-odyssee.com\n";

    $boundary = md5(uniqid(time()));
    $headers  = "From: Roland Crettaz <roland@neuro-odyssee.com>\r\nReply-To: roland@neuro-odyssee.com\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $message  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $textBody . "\r\n";
    foreach ($pdfs as $pdf) {
        $fileBase64 = chunk_split(base64_encode(file_get_contents($pdfDir . '/' . $pdf)));
        $message .= "--$boundary\r\nContent-Type: application/pdf; name=\"$pdf\"\r\nContent-Disposition: attachment; filename=\"$pdf\"\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $fileBase64 . "\r\n";
    }
    $message .= "--$boundary--";

    mail($email, "Tes guides Neuro-Odyssée, Pack $packLabel", $message, $headers);
    mail('roland@neuro-odyssee.com',
        "[Neuro-Odyssée] Pack $packLabel, $name ($montant EUR)",
        "Nouveau soutien Pack $packLabel !\nClient : $name\nEmail : $email\nMontant : $montant EUR",
        "From: noreply@neuro-odyssee.com\r\nContent-Type: text/plain; charset=UTF-8\r\n"
    );
}

function inscrireClub($email, $nom, $pack, $montant) {
    try {
        require_once __DIR__ . '/../api/config.php';
        $db = getDB();
        $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $sql = "UPDATE newsletter_subscribers SET actif = 1, club = 1, source = 'mollie', updated_at = NOW()";
            $params = [];
            if ($nom) { $sql .= ", nom = ?"; $params[] = $nom; }
            if ($pack && $pack !== 'libre') { $sql .= ", pack = ?"; $params[] = $pack; }
            $sql .= ", montant = GREATEST(montant, ?) WHERE email = ?";
            $params[] = $montant; $params[] = $email;
            $db->prepare($sql)->execute($params);
        } else {
            $db->prepare("INSERT INTO newsletter_subscribers (email, nom, pack, montant, source, actif, club) VALUES (?, ?, ?, ?, 'mollie', 1, 1)")
               ->execute([$email, $nom, $pack, $montant]);
        }
    } catch (Exception $e) {
        error_log('Club inscription error: ' . $e->getMessage());
    }
}
