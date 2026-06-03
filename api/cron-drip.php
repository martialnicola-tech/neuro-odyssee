<?php
/**
 * Cron drip emails — La Neuro-Odyssee
 * Execution quotidienne via Hostinger cron :
 *   php /path/to/api/cron-drip.php?token=neuro-drip-2026
 * Ou en HTTP (protege par token) :
 *   https://www.neuro-odyssee.com/api/cron-drip.php?token=neuro-drip-2026
 */
declare(strict_types=1);

// Protection par token
$token = $_GET['token'] ?? $_SERVER['argv'][1] ?? '';
if ($token !== 'neuro-drip-2026') {
    http_response_code(403);
    echo "Acces refuse — token manquant ou invalide.\n";
    exit;
}

require_once __DIR__ . '/config.php';

$templatesFile = __DIR__ . '/../data/email-templates.json';
if (!file_exists($templatesFile)) {
    echo "ERREUR : fichier email-templates.json introuvable.\n";
    exit(1);
}

$templates = json_decode(file_get_contents($templatesFile), true);
if (!is_array($templates)) {
    echo "ERREUR : email-templates.json invalide.\n";
    exit(1);
}

// Exclure les templates jour 0 (welcome) — envoyes par subscribe.php
// et les templates inactifs
$dripTemplates = array_filter($templates, function (array $tpl): bool {
    return ($tpl['delay_days'] > 0) && ($tpl['actif'] === true);
});

if (empty($dripTemplates)) {
    echo "Aucun template drip actif (delay_days > 0). Rien a faire.\n";
    exit(0);
}

try {
    $db = getDB();

    // Recuperer tous les abonnes actifs
    $subscribers = $db->query(
        "SELECT id, email, nom, club, created_at FROM newsletter_subscribers WHERE actif = 1"
    )->fetchAll();

    $sent  = 0;
    $skip  = 0;
    $error = 0;
    $log   = [];

    foreach ($subscribers as $sub) {
        $subId     = (int) $sub['id'];
        $subType   = ((int) $sub['club'] === 1) ? 'club' : 'newsletter';
        $createdAt = new DateTimeImmutable($sub['created_at']);
        $now       = new DateTimeImmutable();
        $daysSince = (int) $now->diff($createdAt)->days;

        foreach ($dripTemplates as $tpl) {
            // Ce template est-il pour ce type d'abonne ?
            if ($tpl['type'] !== $subType) {
                continue;
            }

            // Est-ce que le delai est atteint ?
            if ($daysSince < $tpl['delay_days']) {
                continue;
            }

            // Deja envoye ?
            $check = $db->prepare(
                "SELECT id FROM drip_emails_sent WHERE subscriber_id = ? AND template_key = ?"
            );
            $check->execute([$subId, $tpl['key']]);
            if ($check->fetch()) {
                $skip++;
                continue;
            }

            // Preparer et envoyer l'email
            $prenom   = trim($sub['nom'] ?: '') ?: 'Ami(e) de la Neuro-Odyssee';
            $token    = substr(md5($sub['email'] . 'neuro-odyssee-unsub'), 0, 16);
            $unsubUrl = 'https://www.neuro-odyssee.com/api/unsubscribe.php'
                . '?email=' . urlencode($sub['email'])
                . '&token=' . $token;

            $body = "Bonjour {$prenom},\n\n";
            $body .= str_replace(
                ['{prenom}', '{unsub_url}'],
                [$prenom, $unsubUrl],
                $tpl['body']
            );

            $subject = $tpl['subject'];

            $headers  = "From: Roland Crettaz <roland@neuro-odyssee.com>\r\n";
            $headers .= "Reply-To: roland@neuro-odyssee.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $ok = mail($sub['email'], $subject, $body, $headers);

            if ($ok) {
                // Logger l'envoi
                $insert = $db->prepare(
                    "INSERT IGNORE INTO drip_emails_sent (subscriber_id, template_key, sent_at) VALUES (?, ?, NOW())"
                );
                $insert->execute([$subId, $tpl['key']]);

                $sent++;
                $log[] = "[OK] {$sub['email']} — {$tpl['key']}";
            } else {
                $error++;
                $log[] = "[FAIL] {$sub['email']} — {$tpl['key']}";
                error_log("cron-drip: echec envoi {$sub['email']} template {$tpl['key']}");
            }

            // Anti-spam : 100ms entre chaque email
            usleep(100000);
        }
    }

    // Rapport
    $date = date('Y-m-d H:i:s');
    echo "=== Cron drip — {$date} ===\n";
    echo "Abonnes traites  : " . count($subscribers) . "\n";
    echo "Emails envoyes   : {$sent}\n";
    echo "Deja envoyes     : {$skip}\n";
    echo "Erreurs          : {$error}\n";

    if (!empty($log)) {
        echo "\nDetail :\n";
        foreach ($log as $line) {
            echo "  {$line}\n";
        }
    }

} catch (PDOException $e) {
    echo "ERREUR PDO : " . $e->getMessage() . "\n";
    error_log('cron-drip PDO error: ' . $e->getMessage());
    exit(1);
}
