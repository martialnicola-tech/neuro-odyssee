<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../api/config.php';

$db = getDB();
$msg = '';
$msgType = '';

// ── Action : envoyer une newsletter ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'send') {
        $sujet   = trim($_POST['sujet'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $cible   = $_POST['cible'] ?? 'tous';

        if (!$sujet || !$contenu) {
            $msg = 'Sujet et contenu requis.';
            $msgType = 'error';
        } else {
            // Récupérer les abonnés selon la cible
            $sql = "SELECT email, nom, club FROM newsletter_subscribers WHERE actif = 1";
            if ($cible === 'club') {
                $sql .= " AND club = 1";
            } elseif ($cible === 'newsletter') {
                $sql .= " AND club = 0";
            }
            $subs = $db->query($sql)->fetchAll();

            $sent = 0;
            $fail = 0;

            foreach ($subs as $sub) {
                $prenom = $sub['nom'] ?: 'Ami(e) de la Neuro-Odyssée';
                $token  = substr(md5($sub['email'] . 'neuro-odyssee-unsub'), 0, 16);
                $unsubUrl = 'https://www.neuro-odyssee.com/api/unsubscribe.php?email=' . urlencode($sub['email']) . '&token=' . $token;

                $body  = "Bonjour $prenom,\n\n";
                $body .= $contenu;
                $body .= "\n\n---\n";
                $body .= "Club Neuro-Odyssée — www.neuro-odyssee.com\n";
                $body .= "Se désinscrire : $unsubUrl\n";

                $headers  = "From: Roland Crettaz <roland@neuro-odyssee.com>\r\n";
                $headers .= "Reply-To: roland@neuro-odyssee.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (mail($sub['email'], $sujet, $body, $headers)) {
                    $sent++;
                } else {
                    $fail++;
                }

                // Anti-spam : petite pause
                usleep(100000); // 100ms entre chaque email
            }

            // Sauvegarder dans l'historique
            $stmt = $db->prepare("INSERT INTO newsletters (sujet, contenu, nb_envois, envoye_le) VALUES (:s, :c, :n, NOW())");
            $stmt->execute([':s' => $sujet, ':c' => $contenu, ':n' => $sent]);

            $msg = "Newsletter envoyée ! $sent emails OK" . ($fail ? ", $fail échoués" : "");
            $msgType = 'success';
        }
    }

    if ($_POST['action'] === 'delete_sub') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM newsletter_subscribers WHERE id = :id")->execute([':id' => $id]);
            $msg = 'Abonné supprimé.';
            $msgType = 'success';
        }
    }

    if ($_POST['action'] === 'toggle_sub') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE newsletter_subscribers SET actif = NOT actif WHERE id = :id")->execute([':id' => $id]);
            $msg = 'Statut modifié.';
            $msgType = 'success';
        }
    }

    if ($_POST['action'] === 'add_sub') {
        $addEmail = trim($_POST['email'] ?? '');
        $addNom   = trim($_POST['nom'] ?? '');
        $addType  = $_POST['type'] ?? 'newsletter';
        $isClub   = ($addType === 'club' || $addType === 'both') ? 1 : 0;
        if ($addEmail && filter_var($addEmail, FILTER_VALIDATE_EMAIL)) {
            $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $check->execute([$addEmail]);
            if ($check->fetch()) {
                $sql = "UPDATE newsletter_subscribers SET actif = 1, club = ?";
                $params = [$isClub];
                if ($addNom) { $sql .= ", nom = ?"; $params[] = $addNom; }
                $sql .= " WHERE email = ?";
                $params[] = $addEmail;
                $db->prepare($sql)->execute($params);
            } else {
                $db->prepare("INSERT INTO newsletter_subscribers (email, nom, source, actif, club) VALUES (?, ?, 'admin', 1, ?)")->execute([$addEmail, $addNom, $isClub]);
            }
            $typeLabel = $addType === 'both' ? 'Newsletter + Club' : ($addType === 'club' ? 'Club' : 'Newsletter');
            $msg = "$addEmail ajouté ($typeLabel) !";
            $msgType = 'success';
        }
    }
}

// Stats
$totalSubs   = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 1")->fetchColumn();
$totalClub   = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 1 AND club = 1")->fetchColumn();
$totalInactif = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 0")->fetchColumn();
$subscribers = $db->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC")->fetchAll();
$pastNewsletters = $db->query("SELECT * FROM newsletters ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Newsletter — Admin Neuro-Odyssée</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0f1520; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #e0e0e0; min-height: 100vh; }
    header {
      background: linear-gradient(135deg, #0f1520 0%, #1a2332 100%);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      padding: 0 1.5rem;
      display: flex; align-items: center; justify-content: center;
      height: 56px;
      gap: 2rem;
    }
    .header-brand { display: flex; align-items: center; gap: 0.5rem; }
    .header-brand span { font-size: 1.2rem; }
    .header-brand h1 { color: white; font-size: 0.95rem; font-weight: 700; letter-spacing: -0.01em; }
    .header-nav { display: flex; align-items: center; gap: 0.25rem; }
    .header-nav a {
      display: flex; align-items: center; gap: 0.4rem;
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      font-size: 0.8rem; font-weight: 500;
      color: rgba(255,255,255,0.55);
      text-decoration: none;
      transition: all 0.2s;
    }
    .header-nav a:hover { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.85); }
    .header-nav a.active { background: rgba(240,165,0,0.12); color: #F0A500; font-weight: 600; }
    .header-nav .nav-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.1); margin: 0 0.25rem; }
    .header-nav a.nav-logout { color: rgba(255,255,255,0.3); font-size: 0.75rem; }
    .header-nav a.nav-logout:hover { color: #ff8080; background: rgba(255,80,80,0.08); }
    .container { max-width: 800px; margin: 0 auto; padding: 1.5rem; }
    .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .card h2 { color: #F0A500; font-size: 1.1rem; margin-bottom: 1rem; }
    .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem; }
    .stat { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1rem; text-align: center; }
    .stat .number { font-size: 2rem; font-weight: 800; color: #F0A500; }
    .stat .label { font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 0.25rem; }
    input, textarea { width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.07); color: white; font-size: 0.9rem; outline: none; margin-bottom: 0.75rem; font-family: inherit; }
    input:focus, textarea:focus { border-color: #F0A500; }
    textarea { min-height: 150px; resize: vertical; }
    .btn { padding: 0.75rem 1.5rem; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; font-size: 0.9rem; transition: opacity 0.2s; }
    .btn:hover { opacity: 0.85; }
    .btn-gold { background: #F0A500; color: #1a2332; }
    .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.75rem; border-radius: 8px; }
    .btn-danger { background: rgba(220,50,50,0.2); color: #ff8080; border: 1px solid rgba(220,50,50,0.3); }
    .btn-toggle { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.15); }
    table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    th { text-align: left; padding: 0.5rem; color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.08); }
    td { padding: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.65rem; font-weight: 700; }
    .badge-ok { background: rgba(45,140,122,0.2); color: #6de0c8; }
    .badge-off { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.3); }
    .badge-stripe { background: rgba(99,91,255,0.15); color: #a29fff; }
    .badge-form { background: rgba(240,165,0,0.15); color: #F0A500; }
    .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.85rem; }
    .alert-success { background: rgba(45,140,122,0.15); border: 1px solid rgba(45,140,122,0.3); color: #6de0c8; }
    .alert-error { background: rgba(220,50,50,0.15); border: 1px solid rgba(220,50,50,0.3); color: #ff8080; }
  </style>
</head>
<body>
  <header>
    <div class="header-brand"><span>🧭</span><h1>Neuro-Odyssée Admin</h1></div>
    <nav class="header-nav">
      <a href="dashboard.php">📝 Publications</a>
      <a href="newsletter.php" class="active">📬 Newsletter</a>
      <a href="emails-auto.php">📧 Emails auto</a>
      <span class="nav-sep"></span>
      <a href="logout.php" class="nav-logout">Déconnexion</a>
    </nav>
  </header>

  <div class="container">

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats" style="grid-template-columns: 1fr 1fr 1fr;">
      <div class="stat">
        <div class="number"><?= $totalSubs ?></div>
        <div class="label">Abonnés actifs</div>
      </div>
      <div class="stat">
        <div class="number" style="color:#6de0c8;"><?= $totalClub ?></div>
        <div class="label">Membres Club</div>
      </div>
      <div class="stat">
        <div class="number"><?= count($pastNewsletters) ?></div>
        <div class="label">Newsletters envoyées</div>
      </div>
    </div>

    <!-- Composer -->
    <div class="card">
      <h2>✍️ Composer une newsletter</h2>
      <form method="POST" id="sendForm">
        <input type="hidden" name="action" value="send">

        <!-- Sélecteur de cible -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
          <label style="flex:1; cursor:pointer;">
            <input type="radio" name="cible" value="tous" checked style="display:none;" onchange="updateSendBtn()">
            <div class="cible-btn active" id="cible-tous" onclick="selectCible(this, 'tous')">
              👥 Tous (<?= $totalSubs ?>)
            </div>
          </label>
          <label style="flex:1; cursor:pointer;">
            <input type="radio" name="cible" value="club" style="display:none;" onchange="updateSendBtn()">
            <div class="cible-btn" id="cible-club" onclick="selectCible(this, 'club')">
              ⭐ Club (<?= $totalClub ?>)
            </div>
          </label>
          <label style="flex:1; cursor:pointer;">
            <input type="radio" name="cible" value="newsletter" style="display:none;" onchange="updateSendBtn()">
            <div class="cible-btn" id="cible-newsletter" onclick="selectCible(this, 'newsletter')">
              📬 Newsletter (<?= $totalSubs - $totalClub ?>)
            </div>
          </label>
        </div>

        <input type="text" name="sujet" placeholder="Sujet de la newsletter" required>
        <textarea name="contenu" placeholder="Contenu de la newsletter (texte brut, le prénom sera ajouté automatiquement)..." required></textarea>
        <p style="font-size:0.75rem; color:rgba(255,255,255,0.35); margin-bottom:1rem;">
          Le message sera précédé de "Bonjour [Prénom]," et suivi du lien de désinscription automatiquement.
        </p>
        <button type="submit" class="btn btn-gold" id="sendBtn" onclick="return confirm('Envoyer à ' + document.querySelector('.cible-btn.active').textContent.trim() + ' ?')">
          📤 Envoyer
        </button>
      </form>
    </div>

    <style>
      .cible-btn {
        text-align:center; padding:0.6rem; border-radius:10px; font-size:0.8rem; font-weight:600;
        background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.1);
        color:rgba(255,255,255,0.5); transition:all 0.2s;
      }
      .cible-btn.active, .cible-btn:hover {
        border-color:#F0A500; background:rgba(240,165,0,0.1); color:#F0A500;
      }
    </style>
    <script>
      function selectCible(el, value) {
        document.querySelectorAll('.cible-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        el.closest('label').querySelector('input[type=radio]').checked = true;
      }
    </script>

    <!-- Ajouter manuellement -->
    <div class="card">
      <h2>➕ Ajouter un abonné</h2>
      <form method="POST">
        <input type="hidden" name="action" value="add_sub">
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
          <div class="type-btn active" onclick="selectType(this, 'newsletter')" id="type-newsletter">📬 Newsletter</div>
          <div class="type-btn" onclick="selectType(this, 'club')" id="type-club">⭐ Club</div>
          <div class="type-btn" onclick="selectType(this, 'both')" id="type-both">📬+⭐ Les deux</div>
        </div>
        <input type="hidden" name="type" id="add-type" value="newsletter">
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <input type="email" name="email" placeholder="Email" required style="flex:1; min-width:200px;">
          <input type="text" name="nom" placeholder="Nom (optionnel)" style="flex:1; min-width:150px;">
          <button type="submit" class="btn btn-gold btn-sm">Ajouter</button>
        </div>
      </form>
    </div>
    <style>
      .type-btn {
        flex:1; text-align:center; padding:0.5rem; border-radius:8px; font-size:0.75rem; font-weight:600;
        background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.1);
        color:rgba(255,255,255,0.5); cursor:pointer; transition:all 0.2s;
      }
      .type-btn.active { border-color:#F0A500; background:rgba(240,165,0,0.1); color:#F0A500; }
      .type-btn:hover { border-color:#F0A500; color:#F0A500; }
    </style>
    <script>
      function selectType(el, value) {
        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('add-type').value = value;
      }
    </script>

    <!-- Liste abonnés -->
    <div class="card">
      <h2>👥 Abonnés (<?= count($subscribers) ?>)</h2>
      <?php if (empty($subscribers)): ?>
        <p style="color:rgba(255,255,255,0.4); font-size:0.85rem;">Aucun abonné pour le moment.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr><th>Email</th><th>Nom</th><th>Type</th><th>Pack</th><th>Source</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($subscribers as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['nom'] ?: '—') ?></td>
                <td>
                  <?php if (!empty($s['club'])): ?>
                    <span class="badge" style="background:rgba(45,140,122,0.2); color:#6de0c8;">Club</span>
                  <?php else: ?>
                    <span class="badge badge-off">Newsletter</span>
                  <?php endif; ?>
                </td>
                <td><?= $s['pack'] ? htmlspecialchars($s['pack']) . ($s['montant'] ? ' (' . $s['montant'] . '€)' : '') : '—' ?></td>
                <td>
                  <?php if ($s['source'] === 'stripe'): ?>
                    <span class="badge badge-stripe">Stripe</span>
                  <?php elseif ($s['source'] === 'formulaire'): ?>
                    <span class="badge badge-form">Formulaire</span>
                  <?php else: ?>
                    <span class="badge badge-off">Admin</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $s['actif'] ? 'badge-ok' : 'badge-off' ?>"><?= $s['actif'] ? 'Actif' : 'Inactif' ?></span>
                </td>
                <td style="white-space:nowrap;">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_sub">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-toggle"><?= $s['actif'] ? 'Désactiver' : 'Réactiver' ?></button>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                    <input type="hidden" name="action" value="delete_sub">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">✕</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Historique -->
    <?php if (!empty($pastNewsletters)): ?>
    <div class="card">
      <h2>📋 Historique</h2>
      <table>
        <thead><tr><th>Date</th><th>Sujet</th><th>Envois</th></tr></thead>
        <tbody>
          <?php foreach ($pastNewsletters as $nl): ?>
          <tr>
            <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($nl['envoye_le'] ?? $nl['created_at'])) ?></td>
            <td><?= htmlspecialchars($nl['sujet']) ?></td>
            <td><?= $nl['nb_envois'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
</body>
</html>
