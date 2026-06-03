<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../api/config.php';

$db            = getDB();
$templatesFile = __DIR__ . '/../data/email-templates.json';
$msg           = '';
$msgType       = '';
$previewHtml   = '';

// ── Charger les templates ──────────────────────────────────────────────────
function loadTemplates(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveTemplates(string $file, array $templates): bool
{
    return (bool) file_put_contents(
        $file,
        json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

// ── Stats envois par template ──────────────────────────────────────────────
function getStats(PDO $db): array
{
    try {
        $rows = $db->query(
            "SELECT template_key, COUNT(*) as total FROM drip_emails_sent GROUP BY template_key"
        )->fetchAll();
        $stats = [];
        foreach ($rows as $r) {
            $stats[$r['template_key']] = (int) $r['total'];
        }
        return $stats;
    } catch (PDOException $e) {
        return [];
    }
}

$templates = loadTemplates($templatesFile);
$stats     = getStats($db);

// ── Actions POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Sauvegarder les modifications d'un template
    if ($action === 'save_template') {
        $key     = trim($_POST['key'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');

        if ($key && $subject && $body) {
            foreach ($templates as &$tpl) {
                if ($tpl['key'] === $key) {
                    $tpl['subject'] = $subject;
                    $tpl['body']    = $body;
                    break;
                }
            }
            unset($tpl);

            if (saveTemplates($templatesFile, $templates)) {
                $msg     = "Template \"{$key}\" sauvegarde avec succes.";
                $msgType = 'success';
            } else {
                $msg     = "Erreur lors de la sauvegarde du fichier.";
                $msgType = 'error';
            }
        } else {
            $msg     = "Sujet et corps sont obligatoires.";
            $msgType = 'error';
        }
    }

    // Activer / desactiver un template
    if ($action === 'toggle_template') {
        $key = trim($_POST['key'] ?? '');
        if ($key) {
            foreach ($templates as &$tpl) {
                if ($tpl['key'] === $key) {
                    $tpl['actif'] = !($tpl['actif'] ?? true);
                    break;
                }
            }
            unset($tpl);

            if (saveTemplates($templatesFile, $templates)) {
                $msg     = "Template mis a jour.";
                $msgType = 'success';
            } else {
                $msg     = "Erreur lors de la sauvegarde.";
                $msgType = 'error';
            }
        }
    }

    // Previsualiser un template
    if ($action === 'preview') {
        $key = trim($_POST['key'] ?? '');
        foreach ($templates as $tpl) {
            if ($tpl['key'] === $key) {
                $prenom   = 'Roland';
                $unsubUrl = 'https://www.neuro-odyssee.com/api/unsubscribe.php?email=exemple@test.com&token=preview';
                $body     = str_replace(
                    ['{prenom}', '{unsub_url}'],
                    [$prenom, $unsubUrl],
                    $tpl['body']
                );
                $previewHtml = $tpl;
                $previewHtml['body_rendered'] = $body;
                break;
            }
        }
    }

    // Recharger les templates apres modification
    $templates = loadTemplates($templatesFile);
    $stats     = getStats($db);
}

// Stats globales
$totalSubs   = (int) $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 1")->fetchColumn();
$totalClub   = (int) $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 1 AND club = 1")->fetchColumn();
$totalNl     = $totalSubs - $totalClub;

$typeLabels  = ['club' => 'Club', 'newsletter' => 'Newsletter'];
$typeColors  = ['club' => '#6de0c8', 'newsletter' => '#F0A500'];
$typeBg      = ['club' => 'rgba(45,140,122,0.15)', 'newsletter' => 'rgba(240,165,0,0.1)'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emails automatiques — Admin Neuro-Odyssee</title>
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

    .container { max-width: 860px; margin: 0 auto; padding: 1.5rem; }

    .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.875rem; font-weight: 500; }
    .alert-success { background: rgba(45,140,122,0.15); border: 1px solid rgba(45,140,122,0.3); color: #6de0c8; }
    .alert-error   { background: rgba(220,50,50,0.15);  border: 1px solid rgba(220,50,50,0.3);  color: #ff8080; }

    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1.75rem; }
    .stat { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1rem; text-align: center; }
    .stat .number { font-size: 2rem; font-weight: 800; color: #F0A500; }
    .stat .label  { font-size: 0.72rem; color: rgba(255,255,255,0.45); margin-top: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em; }

    .section-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.3); margin: 1.75rem 0 0.85rem; font-weight: 600; }

    /* Template card */
    .tpl-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px;
      margin-bottom: 1rem;
      overflow: hidden;
      transition: border-color 0.2s;
    }
    .tpl-card.inactive { opacity: 0.55; }
    .tpl-card:hover { border-color: rgba(255,255,255,0.15); }

    .tpl-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.9rem 1.1rem;
      cursor: pointer;
      user-select: none;
      gap: 0.75rem;
    }
    .tpl-header-left { display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0; }
    .tpl-type-badge {
      flex-shrink: 0;
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 6px;
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    .tpl-day {
      flex-shrink: 0;
      font-size: 0.75rem;
      color: rgba(255,255,255,0.35);
      font-weight: 600;
      white-space: nowrap;
    }
    .tpl-subject-preview {
      font-size: 0.88rem;
      font-weight: 600;
      color: white;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .tpl-stats-badge {
      flex-shrink: 0;
      font-size: 0.72rem;
      color: rgba(255,255,255,0.4);
      white-space: nowrap;
    }
    .tpl-toggle-icon { flex-shrink: 0; color: rgba(255,255,255,0.3); font-size: 0.8rem; transition: transform 0.2s; }
    .tpl-card.open .tpl-toggle-icon { transform: rotate(180deg); }

    /* Inline edit form */
    .tpl-body {
      display: none;
      padding: 0 1.1rem 1.1rem;
      border-top: 1px solid rgba(255,255,255,0.07);
    }
    .tpl-card.open .tpl-body { display: block; }

    label.field-label {
      display: block;
      font-size: 0.72rem;
      color: rgba(255,255,255,0.4);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
      margin: 1rem 0 0.4rem;
    }
    input[type="text"], textarea {
      width: 100%;
      padding: 0.7rem 0.9rem;
      background: rgba(255,255,255,0.06);
      border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 10px;
      color: white;
      font-size: 0.875rem;
      outline: none;
      transition: border-color 0.2s;
      font-family: inherit;
    }
    input:focus, textarea:focus { border-color: #F0A500; }
    textarea { min-height: 220px; resize: vertical; line-height: 1.6; }

    .tpl-actions { display: flex; gap: 0.5rem; margin-top: 0.9rem; flex-wrap: wrap; }

    .btn {
      padding: 0.55rem 1rem;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: 700;
      font-size: 0.8rem;
      transition: opacity 0.2s;
    }
    .btn:hover { opacity: 0.85; }
    .btn-save    { background: #1E6B5E; color: white; }
    .btn-preview { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.15); }
    .btn-toggle-off { background: rgba(255,80,80,0.15); color: #ff8080; border: 1px solid rgba(255,80,80,0.25); }
    .btn-toggle-on  { background: rgba(45,140,122,0.15); color: #6de0c8; border: 1px solid rgba(45,140,122,0.3); }

    /* Preview modal */
    .preview-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.7);
      z-index: 100;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .preview-overlay.show { display: flex; }
    .preview-modal {
      background: #1a2332;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 16px;
      padding: 1.5rem;
      max-width: 600px;
      width: 100%;
      max-height: 85vh;
      overflow-y: auto;
    }
    .preview-modal h3 { color: #F0A500; font-size: 1rem; margin-bottom: 1rem; }
    .preview-subject { font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem; }
    .preview-subject strong { color: white; }
    .preview-body-wrap {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 10px;
      padding: 1rem;
      white-space: pre-wrap;
      font-size: 0.82rem;
      line-height: 1.7;
      color: #c8d0da;
      font-family: 'Courier New', monospace;
      margin-top: 0.75rem;
    }
    .preview-close {
      margin-top: 1rem;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      color: rgba(255,255,255,0.7);
      padding: 0.5rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.8rem;
    }

    /* Cron info box */
    .cron-box {
      background: rgba(30,107,94,0.08);
      border: 1px solid rgba(30,107,94,0.25);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-top: 2rem;
    }
    .cron-box h3 { color: #6de0c8; font-size: 0.85rem; margin-bottom: 0.5rem; }
    .cron-box code {
      display: block;
      background: rgba(0,0,0,0.3);
      padding: 0.6rem 0.9rem;
      border-radius: 8px;
      font-size: 0.78rem;
      color: #a0cfca;
      font-family: 'Courier New', monospace;
      margin-top: 0.5rem;
      word-break: break-all;
    }
    .cron-box p { font-size: 0.78rem; color: rgba(255,255,255,0.4); margin-top: 0.5rem; }
  </style>
</head>
<body>

<header>
  <div class="header-brand"><span>🧭</span><h1>Neuro-Odyssée Admin</h1></div>
  <nav class="header-nav">
    <a href="dashboard.php">📝 Publications</a>
    <a href="newsletter.php">📬 Newsletter</a>
    <a href="emails-auto.php" class="active">📧 Emails auto</a>
    <span class="nav-sep"></span>
    <a href="logout.php" class="nav-logout">Déconnexion</a>
  </nav>
</header>

<div class="container">

  <?php if ($msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Stats globales -->
  <div class="stats">
    <div class="stat">
      <div class="number"><?= count($templates) ?></div>
      <div class="label">Templates total</div>
    </div>
    <div class="stat">
      <div class="number" style="color:#6de0c8;"><?= $totalClub ?></div>
      <div class="label">Membres Club</div>
    </div>
    <div class="stat">
      <div class="number"><?= $totalNl ?></div>
      <div class="label">Abonnes Newsletter</div>
    </div>
  </div>

  <!-- Templates Club -->
  <div class="section-title">Sequence Club (<?= $totalClub ?> membres)</div>

  <?php foreach ($templates as $tpl):
    if ($tpl['type'] !== 'club') continue;
    $sentCount = $stats[$tpl['key']] ?? 0;
    $isActive  = $tpl['actif'] ?? true;
    $day       = (int) $tpl['delay_days'];
    $dayLabel  = $day === 0 ? 'Jour 0 — envoi immediat' : "Jour {$day}";
    $color     = $typeColors['club'];
    $bg        = $typeBg['club'];
  ?>
  <div class="tpl-card <?= $isActive ? '' : 'inactive' ?>" id="card-<?= htmlspecialchars($tpl['key']) ?>">
    <div class="tpl-header" onclick="toggleCard('<?= htmlspecialchars($tpl['key']) ?>')">
      <div class="tpl-header-left">
        <span class="tpl-type-badge" style="background:<?= $bg ?>; color:<?= $color ?>;">Club</span>
        <span class="tpl-day"><?= $dayLabel ?></span>
        <span class="tpl-subject-preview"><?= htmlspecialchars($tpl['subject']) ?></span>
      </div>
      <span class="tpl-stats-badge"><?= $sentCount ?> envois</span>
      <?php if (!$isActive): ?>
        <span style="font-size:0.7rem; color:#ff8080; font-weight:700; flex-shrink:0;">INACTIF</span>
      <?php endif; ?>
      <span class="tpl-toggle-icon">▼</span>
    </div>

    <div class="tpl-body">
      <form method="POST">
        <input type="hidden" name="action" value="save_template">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">

        <label class="field-label">Sujet</label>
        <input type="text" name="subject" value="<?= htmlspecialchars($tpl['subject']) ?>" required>

        <label class="field-label">Corps de l'email
          <span style="font-weight:400; color:rgba(255,255,255,0.25); text-transform:none; letter-spacing:0;">&nbsp;— variables : {prenom} {unsub_url}</span>
        </label>
        <textarea name="body" required><?= htmlspecialchars($tpl['body']) ?></textarea>

        <div class="tpl-actions">
          <button type="submit" class="btn btn-save">Sauvegarder</button>

          <button type="submit" form="preview-form-<?= htmlspecialchars($tpl['key']) ?>" class="btn btn-preview">Previsualiser</button>

          <button type="submit" form="toggle-form-<?= htmlspecialchars($tpl['key']) ?>"
            class="btn <?= $isActive ? 'btn-toggle-off' : 'btn-toggle-on' ?>">
            <?= $isActive ? 'Desactiver' : 'Activer' ?>
          </button>
        </div>
      </form>

      <form id="preview-form-<?= htmlspecialchars($tpl['key']) ?>" method="POST">
        <input type="hidden" name="action" value="preview">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">
      </form>

      <form id="toggle-form-<?= htmlspecialchars($tpl['key']) ?>" method="POST">
        <input type="hidden" name="action" value="toggle_template">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Templates Newsletter -->
  <div class="section-title">Sequence Newsletter (<?= $totalNl ?> abonnes)</div>

  <?php foreach ($templates as $tpl):
    if ($tpl['type'] !== 'newsletter') continue;
    $sentCount = $stats[$tpl['key']] ?? 0;
    $isActive  = $tpl['actif'] ?? true;
    $day       = (int) $tpl['delay_days'];
    $dayLabel  = $day === 0 ? 'Jour 0 — envoi immediat' : "Jour {$day}";
    $color     = $typeColors['newsletter'];
    $bg        = $typeBg['newsletter'];
  ?>
  <div class="tpl-card <?= $isActive ? '' : 'inactive' ?>" id="card-<?= htmlspecialchars($tpl['key']) ?>">
    <div class="tpl-header" onclick="toggleCard('<?= htmlspecialchars($tpl['key']) ?>')">
      <div class="tpl-header-left">
        <span class="tpl-type-badge" style="background:<?= $bg ?>; color:<?= $color ?>;">Newsletter</span>
        <span class="tpl-day"><?= $dayLabel ?></span>
        <span class="tpl-subject-preview"><?= htmlspecialchars($tpl['subject']) ?></span>
      </div>
      <span class="tpl-stats-badge"><?= $sentCount ?> envois</span>
      <?php if (!$isActive): ?>
        <span style="font-size:0.7rem; color:#ff8080; font-weight:700; flex-shrink:0;">INACTIF</span>
      <?php endif; ?>
      <span class="tpl-toggle-icon">▼</span>
    </div>

    <div class="tpl-body">
      <form method="POST">
        <input type="hidden" name="action" value="save_template">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">

        <label class="field-label">Sujet</label>
        <input type="text" name="subject" value="<?= htmlspecialchars($tpl['subject']) ?>" required>

        <label class="field-label">Corps de l'email
          <span style="font-weight:400; color:rgba(255,255,255,0.25); text-transform:none; letter-spacing:0;">&nbsp;— variables : {prenom} {unsub_url}</span>
        </label>
        <textarea name="body" required><?= htmlspecialchars($tpl['body']) ?></textarea>

        <div class="tpl-actions">
          <button type="submit" class="btn btn-save">Sauvegarder</button>

          <button type="submit" form="preview-form-<?= htmlspecialchars($tpl['key']) ?>" class="btn btn-preview">Previsualiser</button>

          <button type="submit" form="toggle-form-<?= htmlspecialchars($tpl['key']) ?>"
            class="btn <?= $isActive ? 'btn-toggle-off' : 'btn-toggle-on' ?>">
            <?= $isActive ? 'Desactiver' : 'Activer' ?>
          </button>
        </div>
      </form>

      <form id="preview-form-<?= htmlspecialchars($tpl['key']) ?>" method="POST">
        <input type="hidden" name="action" value="preview">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">
      </form>

      <form id="toggle-form-<?= htmlspecialchars($tpl['key']) ?>" method="POST">
        <input type="hidden" name="action" value="toggle_template">
        <input type="hidden" name="key" value="<?= htmlspecialchars($tpl['key']) ?>">
      </form>
    </div>
  </div>
  <?php endforeach; ?>


</div><!-- /container -->

<!-- Preview modal -->
<?php if ($previewHtml): ?>
<div class="preview-overlay show" id="previewModal" onclick="if(event.target===this) closePreview()">
  <div class="preview-modal">
    <h3>Apercu : <?= htmlspecialchars($previewHtml['key']) ?></h3>
    <div class="preview-subject">Sujet : <strong><?= htmlspecialchars($previewHtml['subject']) ?></strong></div>
    <div class="preview-subject" style="margin-top:0.25rem;">Type : <?= htmlspecialchars($previewHtml['type']) ?> — Jour <?= (int)$previewHtml['delay_days'] ?></div>
    <div class="preview-body-wrap">Bonjour Roland,

<?= htmlspecialchars($previewHtml['body_rendered']) ?></div>
    <button class="preview-close" onclick="closePreview()">Fermer</button>
  </div>
</div>
<?php endif; ?>

<script>
  function toggleCard(key) {
    const card = document.getElementById('card-' + key);
    card.classList.toggle('open');
  }

  function closePreview() {
    const modal = document.getElementById('previewModal');
    if (modal) modal.classList.remove('show');
  }

  // Ouvrir la carte du template previsualise
  <?php if ($previewHtml): ?>
  document.getElementById('card-<?= htmlspecialchars($previewHtml['key']) ?>')?.classList.add('open');
  <?php endif; ?>
</script>

</body>
</html>
