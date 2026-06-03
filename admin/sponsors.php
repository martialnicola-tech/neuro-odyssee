<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

$dataFile = __DIR__ . '/../data/sponsors.json';
$sponsors = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?? [])
    : [];
usort($sponsors, fn($a, $b) => ($a['order'] ?? 99) - ($b['order'] ?? 99));

// Données JS pour le modal d'édition
$sponsorsDataJs = [];
foreach ($sponsors as $s) {
    $sponsorsDataJs[$s['id']] = [
        'name'    => html_entity_decode($s['name']    ?? '', ENT_QUOTES, 'UTF-8'),
        'tagline' => html_entity_decode($s['tagline'] ?? '', ENT_QUOTES, 'UTF-8'),
        'url'     => $s['url']   ?? '',
        'pages'   => $s['pages'] ?? [],
        'order'   => $s['order'] ?? 99,
        'logo'    => $s['logo']  ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Sponsors — La Neuro-Odyssée</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0f1520;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: #e0e0e0;
      min-height: 100vh;
    }
    header {
      background: linear-gradient(135deg, #0f1520 0%, #1a2332 100%);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      padding: 0 1.5rem;
      display: flex; align-items: center; justify-content: center;
      height: 56px; gap: 2rem;
    }
    .header-brand { display: flex; align-items: center; gap: 0.5rem; }
    .header-brand span { font-size: 1.2rem; }
    .header-brand h1 { color: white; font-size: 0.95rem; font-weight: 700; }
    .header-nav { display: flex; align-items: center; gap: 0.25rem; }
    .header-nav a {
      display: flex; align-items: center; gap: 0.4rem;
      padding: 0.45rem 0.85rem; border-radius: 8px;
      font-size: 0.8rem; font-weight: 500;
      color: rgba(255,255,255,0.55); text-decoration: none; transition: all 0.2s;
    }
    .header-nav a:hover { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.85); }
    .header-nav a.active { background: rgba(240,165,0,0.12); color: #F0A500; font-weight: 600; }
    .header-nav .nav-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.1); margin: 0 0.25rem; }
    .header-nav a.nav-logout { color: rgba(255,255,255,0.3); font-size: 0.75rem; }
    .header-nav a.nav-logout:hover { color: #ff8080; background: rgba(255,80,80,0.08); }

    .container { max-width: 700px; margin: 0 auto; padding: 1.5rem; }

    .section-title {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;
      color: rgba(255,255,255,0.3); margin: 1.75rem 0 0.85rem;
    }

    /* Alertes */
    .alert { border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 500; }
    .alert-success { background: rgba(30,107,94,0.25); border: 1px solid rgba(30,107,94,0.5); color: #7ecfc4; }
    .alert-error   { background: rgba(200,50,50,0.2);  border: 1px solid rgba(200,50,50,0.4);  color: #ff9090; }

    /* Formulaire */
    .form-box {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px; padding: 1.5rem;
    }
    .field { margin-bottom: 1rem; }
    label { display: block; font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-bottom: 0.4rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    input[type="text"], input[type="url"], input[type="number"] {
      width: 100%; padding: 0.75rem 1rem;
      background: rgba(255,255,255,0.06); border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 12px; color: white; font-size: 0.9rem; outline: none;
      transition: border 0.2s; font-family: inherit;
    }
    input:focus { border-color: #F0A500; }

    /* Upload zone */
    .upload-zone {
      border: 2px dashed rgba(255,255,255,0.15); border-radius: 14px;
      padding: 1.25rem; text-align: center; cursor: pointer; transition: all 0.2s; position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover { border-color: #F0A500; background: rgba(240,165,0,0.05); }
    .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .upload-text { color: rgba(255,255,255,0.4); font-size: 0.85rem; }
    .upload-hint { color: rgba(255,255,255,0.25); font-size: 0.72rem; margin-top: 0.25rem; }
    #logoPreview { display:none; max-height:60px; margin: 0.75rem auto 0; }

    /* Checkboxes pages */
    .pages-grid { display: flex; gap: 0.75rem; }
    .page-check {
      flex: 1; border: 1.5px solid rgba(255,255,255,0.1); border-radius: 12px;
      padding: 0.85rem; cursor: pointer; transition: all 0.2s; text-align: center;
      color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 600;
    }
    .page-check input { display: none; }
    .page-check .icon { font-size: 1.5rem; display: block; margin-bottom: 0.25rem; }
    .page-check:has(input:checked),
    .page-check.checked { border-color: #F0A500; background: rgba(240,165,0,0.1); color: #F0A500; }

    /* Bouton */
    .btn-submit {
      width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #1E6B5E, #2d9d8a);
      color: white; font-weight: 700; font-size: 0.95rem; border: none;
      border-radius: 14px; cursor: pointer; margin-top: 0.5rem; transition: opacity 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-submit:active { opacity: 0.85; }

    /* Sponsor cards */
    .sponsor-card {
      background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 0.75rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .sponsor-logo-wrap {
      width: 80px; height: 48px; flex-shrink: 0;
      background: white; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; padding: 0.4rem;
    }
    .sponsor-logo-wrap img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .sponsor-logo-wrap .no-logo { font-size: 1.75rem; }
    .sponsor-info { flex: 1; min-width: 0; }
    .sponsor-name { font-size: 0.95rem; font-weight: 700; color: white; margin-bottom: 0.2rem; }
    .sponsor-meta { font-size: 0.75rem; color: rgba(255,255,255,0.35); }
    .sponsor-pages { display: flex; gap: 0.35rem; margin-top: 0.4rem; }
    .page-badge {
      font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
      padding: 0.15rem 0.55rem; border-radius: 1rem;
      background: rgba(240,165,0,0.15); color: #F0A500; border: 1px solid rgba(240,165,0,0.3);
    }
    .sponsor-order { font-size: 0.75rem; color: rgba(255,255,255,0.2); margin-left: 0.25rem; }
    .delete-btn {
      background: none; border: none; color: rgba(255,80,80,0.4); cursor: pointer;
      font-size: 1.1rem; padding: 0.3rem; flex-shrink: 0; transition: color 0.2s;
    }
    .delete-btn:hover { color: rgba(255,80,80,0.9); }
    .edit-sp-btn {
      background: none; border: none; color: rgba(255,200,50,0.45); cursor: pointer;
      font-size: 1.05rem; padding: 0.3rem; flex-shrink: 0; transition: color 0.2s;
    }
    .edit-sp-btn:hover { color: rgba(255,200,50,0.95); }

    /* Modal édition sponsor */
    #editSponsorModal {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.78); backdrop-filter: blur(4px);
      overflow-y: auto; padding: 1.5rem 1rem;
    }
    #editSponsorModal .modal-box {
      max-width: 560px; margin: 2rem auto;
      background: #1a2332; border-radius: 16px;
      padding: 2rem 1.75rem; position: relative;
      border: 1px solid rgba(255,255,255,0.1);
    }
    #editSponsorModal h2 { font-size: 1.05rem; font-weight: 700; color: white; margin-bottom: 1.25rem; }
    #editSponsorModal .close-btn {
      position: absolute; top: 1rem; right: 1rem;
      background: rgba(255,255,255,0.07); border: none; border-radius: 50%;
      width: 2rem; height: 2rem; cursor: pointer; color: rgba(255,255,255,0.5);
      font-size: 1rem; display: flex; align-items: center; justify-content: center;
    }
    #editSponsorModal .close-btn:hover { color: white; }
    #editCurrentLogo { max-height: 48px; border-radius: 6px; background: white; padding: 4px; margin-bottom: 0.5rem; }
    .empty-state { color: rgba(255,255,255,0.25); font-size: 0.85rem; text-align: center; padding: 2rem 0; }

    /* Preview bandeau */
    .ticker-preview {
      margin: 0.75rem 0 0;
      background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
      border-radius: 10px; padding: 0.75rem; overflow: hidden;
      font-size: 0.75rem; color: rgba(255,255,255,0.3); text-align: center;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-brand"><span>🧭</span><h1>Neuro-Odyssée Admin</h1></div>
    <nav class="header-nav">
      <a href="dashboard.php">📝 Publications</a>
      <a href="sponsors.php" class="active">⭐ Sponsors</a>
      <a href="newsletter.php">📬 Newsletter</a>
      <a href="emails-auto.php">📧 Emails auto</a>
      <span class="nav-sep"></span>
      <a href="logout.php" class="nav-logout">Déconnexion</a>
    </nav>
  </header>

  <div class="container">

    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire ajout -->
    <div class="section-title">➕ Ajouter un sponsor</div>
    <div class="form-box">
      <form method="POST" action="upload-sponsor.php" enctype="multipart/form-data">

        <div class="field">
          <label>Nom du sponsor *</label>
          <input type="text" name="name" placeholder="Ex: Vivomixx Neo" required>
        </div>

        <div class="field">
          <label>Slogan / description courte</label>
          <input type="text" name="tagline" placeholder="Ex: Probiotiques et bien-être digestif">
        </div>

        <div class="field">
          <label>Site web (optionnel)</label>
          <input type="url" name="url" placeholder="https://www.example.com">
        </div>

        <div class="field">
          <label>Logo (JPG, PNG, WebP, SVG — max 5 Mo)</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="logo" accept="image/*" id="logoInput">
            <div class="upload-text">📷 Appuyer pour choisir un logo</div>
            <div class="upload-hint">Format recommandé : fond transparent (PNG) ou blanc</div>
          </div>
          <img id="logoPreview" alt="Aperçu">
        </div>

        <div class="field">
          <label>Afficher sur</label>
          <div class="pages-grid">
            <label class="page-check" id="lbl_accueil" onclick="togglePage(this)">
              <input type="checkbox" name="pages[]" value="accueil" checked>
              <span class="icon">🏠</span>
              Accueil
            </label>
            <label class="page-check" id="lbl_preparation" onclick="togglePage(this)">
              <input type="checkbox" name="pages[]" value="preparation">
              <span class="icon">🏃</span>
              Préparation
            </label>
            <label class="page-check" id="lbl_both" onclick="toggleBoth(this)">
              <input type="checkbox" style="display:none">
              <span class="icon">✨</span>
              Les deux
            </label>
          </div>
        </div>

        <div class="field">
          <label>Ordre d'affichage</label>
          <input type="number" name="order" value="<?= count($sponsors) + 1 ?>" min="1" max="99" style="width:100px;">
        </div>

        <button type="submit" class="btn-submit">
          <span>⭐</span> Ajouter le sponsor
        </button>
      </form>
    </div>

    <!-- Liste des sponsors -->
    <div class="section-title">Sponsors actifs (<?= count($sponsors) ?>)</div>

    <?php if (empty($sponsors)): ?>
      <div class="empty-state">Aucun sponsor pour l'instant.</div>
    <?php else: ?>

      <div class="ticker-preview">
        👁 Aperçu du bandeau défilant →&nbsp;
        <?php foreach($sponsors as $s): ?>
          <?php if (!empty($s['logo'])): ?>
            <img src="../<?= htmlspecialchars($s['logo']) ?>" style="height:20px; vertical-align:middle; margin: 0 0.5rem; opacity:0.7;">
          <?php else: ?>
            <strong style="color:rgba(255,255,255,0.5); margin: 0 0.5rem;"><?= htmlspecialchars(html_entity_decode($s['name'])) ?></strong>
          <?php endif; ?>
          <span style="opacity:0.2;">·</span>
        <?php endforeach; ?>
      </div>

      <?php foreach ($sponsors as $s): ?>
        <?php
          $name    = htmlspecialchars(html_entity_decode($s['name']    ?? '', ENT_QUOTES, 'UTF-8'));
          $tagline = htmlspecialchars(html_entity_decode($s['tagline'] ?? '', ENT_QUOTES, 'UTF-8'));
          $pages   = $s['pages'] ?? [];
          $pageLabels = ['accueil' => '🏠 Accueil', 'preparation' => '🏃 Préparation'];
        ?>
        <div class="sponsor-card">
          <div class="sponsor-logo-wrap">
            <?php if (!empty($s['logo'])): ?>
              <img src="../<?= htmlspecialchars($s['logo']) ?>" alt="<?= $name ?>">
            <?php else: ?>
              <span class="no-logo">⭐</span>
            <?php endif; ?>
          </div>
          <div class="sponsor-info">
            <div class="sponsor-name"><?= $name ?> <span class="sponsor-order">#<?= $s['order'] ?? '?' ?></span></div>
            <?php if (!empty($s['url'])): ?>
              <div class="sponsor-meta"><a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" style="color:rgba(59,155,176,0.7); text-decoration:none;"><?= htmlspecialchars($s['url']) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($tagline)): ?>
              <div class="sponsor-meta"><?= $tagline ?></div>
            <?php endif; ?>
            <div class="sponsor-pages">
              <?php foreach ($pages as $pg): ?>
                <span class="page-badge"><?= $pageLabels[$pg] ?? $pg ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="button" class="edit-sp-btn" onclick="openEditSponsor(<?= json_encode($s['id']) ?>)">✏️</button>
          <form method="POST" action="delete-sponsor.php" onsubmit="return confirm('Supprimer <?= addslashes($name) ?> ?')">
            <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
            <button type="submit" class="delete-btn">🗑</button>
          </form>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>

    <div style="margin-top:2rem; padding:1rem; background:rgba(255,255,255,0.03); border-radius:10px; font-size:0.78rem; color:rgba(255,255,255,0.3); line-height:1.7;">
      💡 <strong style="color:rgba(255,255,255,0.45);">Comment ça marche ?</strong><br>
      Les logos des sponsors s'affichent automatiquement dans un <strong style="color:rgba(240,165,0,0.6);">bandeau défilant</strong> en bas du héro sur les pages choisies.<br>
      Pour modifier un sponsor existant : supprime-le et re-ajoute-le avec les nouvelles infos.<br>
      Formats de logo recommandés : PNG fond transparent ou fond blanc, ratio 3:1 environ.
    </div>

  </div>

  <!-- ═══ MODAL ÉDITION SPONSOR ═══════════════════════════ -->
  <div id="editSponsorModal">
    <div class="modal-box">
      <button class="close-btn" onclick="closeEditSponsor()">✕</button>
      <h2>✏️ Modifier le sponsor</h2>
      <form method="POST" action="modifier-sponsor.php" enctype="multipart/form-data" id="editSponsorForm">
        <input type="hidden" name="id" id="es_id">

        <div class="field">
          <label>Nom du sponsor *</label>
          <input type="text" name="name" id="es_name" required>
        </div>
        <div class="field">
          <label>Slogan / description</label>
          <input type="text" name="tagline" id="es_tagline">
        </div>
        <div class="field">
          <label>Site web</label>
          <input type="url" name="url" id="es_url" placeholder="https://">
        </div>

        <div class="field">
          <label>Logo actuel</label>
          <div id="editCurrentLogoWrap" style="margin-bottom:0.5rem;"></div>
          <div class="upload-zone" style="padding:1rem;">
            <input type="file" name="logo" accept="image/*" id="esLogoInput">
            <div class="upload-text" style="font-size:0.8rem;">📷 Remplacer le logo (optionnel)</div>
            <div class="upload-hint">Laisser vide pour conserver l'actuel</div>
          </div>
          <img id="esLogoPreview" style="display:none; max-height:48px; margin-top:0.5rem; border-radius:6px; background:white; padding:4px;" alt="Nouveau logo">
        </div>

        <div class="field">
          <label>Afficher sur</label>
          <div class="pages-grid">
            <label class="page-check" id="es_lbl_accueil" onclick="toggleEditPage(this)">
              <input type="checkbox" name="pages[]" value="accueil" id="es_chk_accueil">
              <span class="icon">🏠</span>Accueil
            </label>
            <label class="page-check" id="es_lbl_preparation" onclick="toggleEditPage(this)">
              <input type="checkbox" name="pages[]" value="preparation" id="es_chk_preparation">
              <span class="icon">🏃</span>Préparation
            </label>
            <label class="page-check" id="es_lbl_both" onclick="toggleEditBoth(this)">
              <input type="checkbox" style="display:none">
              <span class="icon">✨</span>Les deux
            </label>
          </div>
        </div>

        <div class="field">
          <label>Ordre d'affichage</label>
          <input type="number" name="order" id="es_order" min="1" max="99" style="width:100px;">
        </div>

        <button type="submit" class="btn-submit" style="margin-top:0.5rem;">
          <span>💾</span> Enregistrer
        </button>
      </form>
    </div>
  </div>

  <script>
    var SPONSORS_DATA = <?= json_encode($sponsorsDataJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    function openEditSponsor(id) {
      var s = SPONSORS_DATA[id];
      if (!s) return;
      document.getElementById('es_id').value      = id;
      document.getElementById('es_name').value    = s.name;
      document.getElementById('es_tagline').value = s.tagline;
      document.getElementById('es_url').value     = s.url;
      document.getElementById('es_order').value   = s.order;

      // Logo actuel
      var wrap = document.getElementById('editCurrentLogoWrap');
      wrap.innerHTML = s.logo
        ? '<img src="../' + s.logo + '" id="editCurrentLogo" alt="Logo actuel" style="max-height:48px;border-radius:6px;background:white;padding:4px;">'
        : '<span style="color:rgba(255,255,255,0.3);font-size:0.8rem;">Aucun logo</span>';

      // Pages checkboxes
      ['accueil','preparation'].forEach(function(pg) {
        var chk = document.getElementById('es_chk_' + pg);
        var lbl = document.getElementById('es_lbl_' + pg);
        chk.checked = s.pages && s.pages.indexOf(pg) !== -1;
        lbl.classList.toggle('checked', chk.checked);
      });
      var bothActive = s.pages && s.pages.indexOf('accueil') !== -1 && s.pages.indexOf('preparation') !== -1;
      document.getElementById('es_lbl_both').classList.toggle('checked', bothActive);

      // Reset preview
      document.getElementById('esLogoPreview').style.display = 'none';
      document.getElementById('esLogoInput').value = '';

      document.getElementById('editSponsorModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeEditSponsor() {
      document.getElementById('editSponsorModal').style.display = 'none';
      document.body.style.overflow = '';
    }

    document.getElementById('editSponsorModal').addEventListener('click', function(e) {
      if (e.target === this) closeEditSponsor();
    });

    function toggleEditPage(lbl) {
      setTimeout(function() {
        lbl.classList.toggle('checked', lbl.querySelector('input').checked);
        // Synchro bouton "Les deux"
        var a = document.getElementById('es_chk_accueil').checked;
        var p = document.getElementById('es_chk_preparation').checked;
        document.getElementById('es_lbl_both').classList.toggle('checked', a && p);
      }, 0);
    }

    function toggleEditBoth(lbl) {
      event.preventDefault();
      var a = document.getElementById('es_chk_accueil');
      var p = document.getElementById('es_chk_preparation');
      var active = lbl.classList.contains('checked');
      a.checked = p.checked = !active;
      ['accueil','preparation'].forEach(function(pg) {
        document.getElementById('es_lbl_' + pg).classList.toggle('checked', !active);
      });
      lbl.classList.toggle('checked', !active);
    }

    // Aperçu nouveau logo dans le modal
    document.getElementById('esLogoInput').addEventListener('change', function() {
      var preview = document.getElementById('esLogoPreview');
      if (this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.style.display = 'block';
      }
    });

    // Aperçu logo (formulaire ajout)
    document.getElementById('logoInput').addEventListener('change', function() {
      var preview = document.getElementById('logoPreview');
      if (this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.style.display = 'block';
      }
    });

    // Gestion des checkboxes pages
    function togglePage(lbl) {
      // Petit délai pour laisser le checkbox se cocher
      setTimeout(function() {
        lbl.classList.toggle('checked', lbl.querySelector('input').checked);
        document.getElementById('lbl_both').classList.remove('checked');
      }, 0);
    }

    function toggleBoth(lbl) {
      event.preventDefault();
      var accueil = document.querySelector('input[value="accueil"]');
      var prep    = document.querySelector('input[value="preparation"]');
      var isActive = lbl.classList.contains('checked');
      if (isActive) {
        // Désactiver tout
        accueil.checked = false;
        prep.checked    = false;
        lbl.classList.remove('checked');
        document.getElementById('lbl_accueil').classList.remove('checked');
        document.getElementById('lbl_preparation').classList.remove('checked');
      } else {
        // Activer les deux
        accueil.checked = true;
        prep.checked    = true;
        lbl.classList.add('checked');
        document.getElementById('lbl_accueil').classList.add('checked');
        document.getElementById('lbl_preparation').classList.add('checked');
      }
    }

    // Init état des checkboxes
    document.addEventListener('DOMContentLoaded', function() {
      var accueil = document.querySelector('input[value="accueil"]');
      var prep    = document.querySelector('input[value="preparation"]');
      if (accueil && accueil.checked) document.getElementById('lbl_accueil').classList.add('checked');
      if (prep    && prep.checked)    document.getElementById('lbl_preparation').classList.add('checked');
    });
  </script>
</body>
</html>
