<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Publier — La Neuro-Odyssée</title>
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
    .container { max-width: 600px; margin: 0 auto; padding: 1.25rem; }

    /* Type selector */
    .type-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 0.6rem;
      margin-bottom: 1.5rem;
    }
    .type-btn {
      background: rgba(255,255,255,0.05);
      border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 0.75rem 0.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      color: rgba(255,255,255,0.6);
      font-size: 0.75rem;
      font-weight: 600;
    }
    .type-btn .icon { font-size: 1.5rem; display: block; margin-bottom: 0.25rem; }
    .type-btn.active, .type-btn:hover {
      border-color: #F0A500;
      background: rgba(240,165,0,0.1);
      color: #F0A500;
    }

    /* Form */
    .field { margin-bottom: 1rem; }
    label { display: block; font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-bottom: 0.4rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    input[type="text"], textarea, select {
      width: 100%;
      padding: 0.8rem 1rem;
      background: rgba(255,255,255,0.06);
      border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      color: white;
      font-size: 0.95rem;
      outline: none;
      transition: border 0.2s;
      font-family: inherit;
    }
    input:focus, textarea:focus, select:focus { border-color: #F0A500; }
    textarea { min-height: 160px; resize: vertical; line-height: 1.6; }
    select option { background: #1a2332; }

    /* Photo upload */
    .upload-zone {
      border: 2px dashed rgba(255,255,255,0.15);
      border-radius: 14px;
      padding: 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover {
      border-color: #F0A500;
      background: rgba(240,165,0,0.05);
    }
    .upload-zone input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .upload-icon { font-size: 2rem; margin-bottom: 0.5rem; }
    .upload-text { color: rgba(255,255,255,0.4); font-size: 0.85rem; }
    .upload-hint { color: rgba(255,255,255,0.25); font-size: 0.75rem; margin-top: 0.25rem; }
    #preview-container { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
    #preview-container img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }

    /* Location */
    .location-row { display: flex; gap: 0.6rem; }
    .location-row input { flex: 1; }

    /* Crop cadrage photo — slider */
    .crop-slider-row { display: flex; align-items: center; gap: 0.6rem; }
    .crop-nudge {
      background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.12);
      border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center;
      justify-content: center; cursor: pointer; font-size: 1rem; color: rgba(255,255,255,0.6);
      flex-shrink: 0; transition: all 0.15s;
    }
    .crop-nudge:hover { border-color: #F0A500; color: #F0A500; background: rgba(240,165,0,0.1); }
    .crop-slider {
      -webkit-appearance: none; appearance: none;
      flex: 1; height: 4px; border-radius: 2px;
      background: rgba(255,255,255,0.15); outline: none; cursor: pointer;
    }
    .crop-slider::-webkit-slider-thumb {
      -webkit-appearance: none; appearance: none;
      width: 18px; height: 18px; border-radius: 50%;
      background: #F0A500; cursor: pointer; border: 2px solid #fff;
    }
    .crop-slider::-moz-range-thumb {
      width: 18px; height: 18px; border-radius: 50%;
      background: #F0A500; cursor: pointer; border: 2px solid #fff;
    }

    /* Submit */
    .btn-submit {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #1E6B5E, #2d9d8a);
      color: white;
      font-weight: 700;
      font-size: 1rem;
      border: none;
      border-radius: 14px;
      cursor: pointer;
      margin-top: 0.5rem;
      transition: opacity 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-submit:active { opacity: 0.85; }

    /* Alerts */
    .alert {
      border-radius: 12px;
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-success { background: rgba(30,107,94,0.25); border: 1px solid rgba(30,107,94,0.5); color: #7ecfc4; }
    .alert-error   { background: rgba(200,50,50,0.2);  border: 1px solid rgba(200,50,50,0.4);  color: #ff9090; }

    /* Recent posts */
    .section-title {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;
      color: rgba(255,255,255,0.3); margin: 1.5rem 0 0.75rem;
    }
    .post-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px;
      padding: 0.85rem 1rem;
      margin-bottom: 0.6rem;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .post-card .post-title { font-size: 0.9rem; font-weight: 600; color: white; margin-bottom: 0.2rem; }
    .post-card .post-meta  { font-size: 0.75rem; color: rgba(255,255,255,0.35); }
    .post-card .post-type  { font-size: 1.2rem; flex-shrink: 0; }
    .delete-btn {
      background: none; border: none; color: rgba(255,80,80,0.5); cursor: pointer;
      font-size: 1rem; padding: 0.2rem; flex-shrink: 0;
    }
    .delete-btn:hover { color: rgba(255,80,80,0.9); }
    .edit-btn {
      background: none; border: none; color: rgba(255,200,50,0.5); cursor: pointer;
      font-size: 1rem; padding: 0.2rem; flex-shrink: 0;
    }
    .edit-btn:hover { color: rgba(255,200,50,0.95); }
    .empty-state { color: rgba(255,255,255,0.25); font-size: 0.85rem; text-align: center; padding: 1.5rem 0; }

    /* Modal édition */
    #editModal {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
      overflow-y: auto; padding: 1.5rem 1rem;
    }
    #editModal .modal-box {
      max-width: 560px; margin: 2rem auto;
      background: #1a2332; border-radius: 16px;
      padding: 2rem 1.75rem; position: relative;
      border: 1px solid rgba(255,255,255,0.1);
    }
    #editModal h2 { font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 1.25rem; }
    #editModal .close-btn {
      position: absolute; top: 1rem; right: 1rem;
      background: rgba(255,255,255,0.07); border: none; border-radius: 50%;
      width: 2rem; height: 2rem; cursor: pointer; color: rgba(255,255,255,0.5);
      font-size: 1rem; display: flex; align-items: center; justify-content: center;
    }
    #editModal .close-btn:hover { color: white; }
  </style>
</head>
<body>
  <header>
    <div class="header-brand"><span>🧭</span><h1>Neuro-Odyssée Admin</h1></div>
    <nav class="header-nav">
      <a href="dashboard.php" class="active">📝 Publications</a>
      <a href="sponsors.php">⭐ Sponsors</a>
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

    <form method="POST" action="upload.php" enctype="multipart/form-data" id="postForm">

      <!-- Type de contenu -->
      <div class="type-grid">
        <label class="type-btn active" onclick="selectType('journal', this)">
          <span class="icon">📔</span>Journal
          <input type="radio" name="type" value="journal" style="display:none" checked>
        </label>
        <label class="type-btn" onclick="selectType('etape', this)">
          <span class="icon">📍</span>Étape
          <input type="radio" name="type" value="etape" style="display:none">
        </label>
        <label class="type-btn" onclick="selectType('entrainement', this)">
          <span class="icon">🏃</span>Entraîn.
          <input type="radio" name="type" value="entrainement" style="display:none">
        </label>
      </div>

      <!-- Titre -->
      <div class="field">
        <label>Titre</label>
        <input type="text" name="title" placeholder="Ex: Jour 12 — Les Pyrénées sous la pluie" required>
      </div>

      <!-- Contenu -->
      <div class="field">
        <label>Texte / Réflexions</label>
        <textarea name="content" placeholder="Ce que tu veux partager…"></textarea>
      </div>

      <!-- Localisation -->
      <div class="field" id="locationField">
        <label>Localisation</label>
        <div class="location-row">
          <input type="text" name="location" placeholder="Ex: Saint-Jean-Pied-de-Port">
          <input type="text" name="km" placeholder="km 850">
        </div>
      </div>

      <!-- Vidéo YouTube -->
      <div class="field">
        <label>Vidéo YouTube (optionnel)</label>
        <input type="text" name="youtube" placeholder="Ex: https://www.youtube.com/watch?v=abc123 ou https://youtu.be/abc123">
        <div style="font-size:0.7rem; color:rgba(255,255,255,0.3); margin-top:-0.6rem;">
          Colle le lien de ta vidéo YouTube — elle sera intégrée automatiquement
        </div>
      </div>

      <!-- Cadrage photo -->
      <div class="field">
        <label>Cadrage de la photo <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:0.75rem;">(ajusté après envoi)</span></label>
        <div class="crop-slider-row">
          <button type="button" class="crop-nudge" onclick="nudgeCropNew(-5)" title="Monter">↑</button>
          <input type="range" class="crop-slider" id="cropSlider" min="0" max="100" step="5" value="50" oninput="document.getElementById('cropValue').value=this.value">
          <button type="button" class="crop-nudge" onclick="nudgeCropNew(5)" title="Descendre">↓</button>
        </div>
        <input type="hidden" name="crop" id="cropValue" value="50">
      </div>

      <!-- Photos -->
      <div class="field">
        <label>Photos (optionnel)</label>
        <div class="upload-zone" id="dropZone">
          <input type="file" name="photos[]" multiple accept="image/*,video/mp4" id="fileInput">
          <div class="upload-icon">📷</div>
          <div class="upload-text">Appuyer pour ajouter photos ou vidéo</div>
          <div class="upload-hint">JPG, PNG, MP4 — max 20 Mo chacun</div>
        </div>
        <div id="preview-container"></div>
      </div>

      <button type="submit" class="btn-submit">
        <span>✨</span> Publier maintenant
      </button>
    </form>

    <!-- Toutes les publications -->
    <div class="section-title">Publications</div>
    <div id="recentPosts">
      <?php
      $dataFile = __DIR__ . '/../data/posts.json';
      $posts = [];
      if (file_exists($dataFile)) {
          $posts = json_decode(file_get_contents($dataFile), true) ?? [];
      }
      $allPosts = array_reverse($posts);

      // Carte de données pour le modal d'édition JS (décode les entités HTML stockées par upload.php)
      $postsDataJs = [];
      foreach ($allPosts as $p) {
          $postsDataJs[$p['id']] = [
              'type'     => $p['type'] ?? 'journal',
              'title'    => html_entity_decode($p['title']    ?? '', ENT_QUOTES, 'UTF-8'),
              'content'  => html_entity_decode($p['content']  ?? '', ENT_QUOTES, 'UTF-8'),
              'location' => html_entity_decode($p['location'] ?? '', ENT_QUOTES, 'UTF-8'),
              'km'       => $p['km']      ?? '',
              'youtube'  => $p['youtube'] ?? '',
              'url'      => $p['url']     ?? '',
              'crop'     => $p['crop']    ?? 'center',
              'image'    => $p['images'][0] ?? '',
          ];
      }

      $typeIcons = ['journal' => '📔', 'etape' => '📍', 'entrainement' => '🏃', 'neuro' => '🧠'];
      if (empty($allPosts)) {
          echo '<div class="empty-state">Aucune publication pour l\'instant</div>';
      } else {
          foreach ($allPosts as $p) {
              $icon = $typeIcons[$p['type'] ?? 'journal'] ?? '📝';
              if (!empty($p['youtube'])) $icon = '🎬';
              $date         = date('d/m/Y', $p['created_at'] ?? time());
              // Fix double-encodage : décode avant de réafficher
              $titleDisplay = htmlspecialchars(html_entity_decode($p['title'] ?? '', ENT_QUOTES, 'UTF-8'));
              $locDisplay   = htmlspecialchars(html_entity_decode($p['location'] ?? '', ENT_QUOTES, 'UTF-8'));
              $idEsc        = htmlspecialchars($p['id']);

              echo '<div class="post-card">';
              echo '  <span class="post-type">' . $icon . '</span>';
              echo '  <div style="flex:1">';
              echo '    <div class="post-title">' . $titleDisplay . '</div>';
              echo '    <div class="post-meta">' . $date;
              if (!empty($p['location'])) echo ' · ' . $locDisplay;
              if (!empty($p['youtube'])) echo ' · <span style="color:#FF0000;">▶ YouTube</span>';
              echo '</div>';
              echo '  </div>';
              // Passe uniquement l'ID — les données complètes sont dans POSTS_DATA (évite les problèmes
              // de retours à la ligne / guillemets dans les strings JS inline)
              echo '  <button type="button" class="edit-btn" onclick="openEdit(\'' . $p['id'] . '\')">✏️</button>';
              echo '  <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm(\'Supprimer définitivement ?\')">';
              echo '    <input type="hidden" name="id" value="' . $idEsc . '">';
              echo '    <button type="submit" class="delete-btn">🗑</button>';
              echo '  </form>';
              echo '</div>';
          }
      }
      ?>
    </div>

    <!-- Modal édition -->
    <div id="editModal">
      <div class="modal-box">
        <button class="close-btn" onclick="closeEdit()">✕</button>
        <h2>✏️ Modifier la publication</h2>
        <form method="POST" action="modifier-post.php" id="editForm" enctype="multipart/form-data">
          <input type="hidden" name="id" id="edit_id">

          <div class="type-grid" style="margin-bottom:1rem;">
            <label class="type-btn" id="eb_journal" onclick="setEditType('journal',this)">
              <span class="icon">📔</span>Journal
              <input type="radio" name="type" value="journal" style="display:none">
            </label>
            <label class="type-btn" id="eb_etape" onclick="setEditType('etape',this)">
              <span class="icon">📍</span>Étape
              <input type="radio" name="type" value="etape" style="display:none">
            </label>
            <label class="type-btn" id="eb_entrainement" onclick="setEditType('entrainement',this)">
              <span class="icon">🏃</span>Entraîn.
              <input type="radio" name="type" value="entrainement" style="display:none">
            </label>
          </div>

          <div class="field">
            <label>Titre</label>
            <input type="text" name="title" id="edit_title" required>
          </div>
          <div class="field">
            <label>Texte / Réflexions</label>
            <textarea name="content" id="edit_content" style="min-height:120px;"></textarea>
          </div>
          <div class="field">
            <label>Localisation</label>
            <div class="location-row">
              <input type="text" name="location" id="edit_location" placeholder="Ville">
              <input type="text" name="km" id="edit_km" placeholder="km 850">
            </div>
          </div>
          <div class="field">
            <label>Vidéo YouTube (optionnel)</label>
            <input type="text" name="youtube" id="edit_youtube" placeholder="URL ou ID YouTube">
          </div>
          <div class="field">
            <label>Lien site web (optionnel)</label>
            <input type="text" name="url" id="edit_url" placeholder="https://...">
          </div>
          <div class="field">
            <label>Ajouter des photos</label>
            <input type="file" name="photos[]" id="edit_fileInput" multiple accept="image/*,video/mp4,video/quicktime" style="color:rgba(255,255,255,0.6);font-size:0.85rem;">
          </div>
          <div class="field" id="edit_crop_field" style="display:none;">
            <label>Cadrage de la photo</label>
            <!-- Aperçu live -->
            <div id="edit_crop_preview" style="height:130px; border-radius:10px; overflow:hidden; background:#1a2332; margin-bottom:0.75rem; position:relative;">
              <img id="edit_crop_img" src="" alt="" style="width:100%; height:100%; object-fit:cover; object-position:center center; display:block; transition:object-position 0.3s ease;">
            </div>
            <div class="crop-slider-row">
              <button type="button" class="crop-nudge" onclick="nudgeEditCrop(-5)" title="Monter">↑</button>
              <input type="range" class="crop-slider" id="edit_crop_slider" min="0" max="100" step="5" value="50" oninput="updateEditCrop(this.value)">
              <button type="button" class="crop-nudge" onclick="nudgeEditCrop(5)" title="Descendre">↓</button>
            </div>
            <input type="hidden" name="crop" id="edit_crop" value="50">
          </div>

          <button type="submit" class="btn-submit" style="margin-top:1rem;">
            <span>💾</span> Enregistrer les modifications
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Données des publications générées côté PHP (gère retours à la ligne, apostrophes, etc.)
    var POSTS_DATA = <?= json_encode($postsDataJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    function selectType(type, el) {
      document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
      el.querySelector('input[type="radio"]').checked = true;
    }

    // Convertit une valeur crop (ancienne string ou nouveau nombre) en pourcentage
    function cropToPercent(val) {
      if (val === 'top')    return 0;
      if (val === 'bottom') return 100;
      if (val === 'center') return 50;
      var n = parseInt(val);
      return isNaN(n) ? 50 : Math.max(0, Math.min(100, n));
    }

    // Formulaire nouveau post
    function nudgeCropNew(delta) {
      var slider = document.getElementById('cropSlider');
      var val = Math.min(100, Math.max(0, parseInt(slider.value) + delta));
      slider.value = val;
      document.getElementById('cropValue').value = val;
    }

    // Modal édition — mise à jour aperçu
    function updateEditCrop(val) {
      val = Math.max(0, Math.min(100, parseInt(val)));
      document.getElementById('edit_crop').value = val;
      document.getElementById('edit_crop_slider').value = val;
      var img = document.getElementById('edit_crop_img');
      if (img) img.style.objectPosition = 'center ' + val + '%';
    }
    function nudgeEditCrop(delta) {
      var current = parseInt(document.getElementById('edit_crop_slider').value) || 50;
      updateEditCrop(current + delta);
    }

    function openEdit(id) {
      var p = POSTS_DATA[id];
      if (!p) return;

      document.getElementById('edit_id').value       = id;
      document.getElementById('edit_title').value    = p.title;
      document.getElementById('edit_content').value  = p.content;
      document.getElementById('edit_location').value = p.location;
      document.getElementById('edit_km').value       = p.km;
      document.getElementById('edit_youtube').value  = p.youtube;
      document.getElementById('edit_url').value      = p.url || '';

      // Cadrage + aperçu photo
      var cropPct = cropToPercent(p.crop);
      document.getElementById('edit_crop').value        = cropPct;
      document.getElementById('edit_crop_slider').value = cropPct;
      var cropField = document.getElementById('edit_crop_field');
      var cropImg   = document.getElementById('edit_crop_img');
      if (p.image) {
        cropImg.src = '../' + p.image;
        cropImg.style.objectPosition = 'center ' + cropPct + '%';
        cropField.style.display = 'block';
      } else {
        cropField.style.display = 'none';
      }

      // Sélectionner le type
      document.querySelectorAll('#editModal .type-btn').forEach(b => b.classList.remove('active'));
      var btn = document.getElementById('eb_' + p.type);
      if (btn) {
        btn.classList.add('active');
        btn.querySelector('input[type="radio"]').checked = true;
      }

      document.getElementById('editModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeEdit() {
      document.getElementById('editModal').style.display = 'none';
      document.body.style.overflow = '';
    }

    function setEditType(type, el) {
      document.querySelectorAll('#editModal .type-btn').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
      el.querySelector('input[type="radio"]').checked = true;
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeEdit();
    });

    // Photo preview
    document.getElementById('fileInput').addEventListener('change', function() {
      const container = document.getElementById('preview-container');
      container.innerHTML = '';
      Array.from(this.files).forEach(file => {
        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          container.appendChild(img);
        } else {
          const div = document.createElement('div');
          div.style.cssText = 'width:80px;height:80px;background:rgba(255,255,255,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;';
          div.textContent = '🎬';
          container.appendChild(div);
        }
      });
    });
  </script>

  <!-- ═══ SAISIE MANUELLE ════════════════════════════════════ -->
  <div class="section-title" style="margin-top:2rem;">💳 Saisie manuelle (Twint / virement)</div>
  <?php
    $statsFile = __DIR__ . '/../data/stats.json';
    $stats = json_decode(file_get_contents($statsFile), true);
  ?>
  <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1.25rem; margin-bottom:1rem;">
    <p style="font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:1rem;">
      Stats actuelles : <strong style="color:#F0A500;"><?= number_format($stats['collectes'] ?? 0, 2) ?> €</strong> collectés ·
      <strong style="color:#4ade80;"><?= $stats['dons'] ?? 0 ?></strong> dons ·
      <strong style="color:#60a5fa;"><?= $stats['km_paraines'] ?? 0 ?></strong> km parrainés
    </p>
    <form method="POST" action="ajouter-don.php" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
      <div>
        <label style="display:block; font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:0.3rem;">Montant reçu (€)</label>
        <input type="number" name="montant" min="1" step="0.01" placeholder="ex: 20"
          style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:0.55rem 0.75rem; color:white; font-size:0.9rem; width:130px;">
      </div>
      <div>
        <label style="display:block; font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:0.3rem;">Km à ajouter</label>
        <input type="number" name="km" min="0" step="1" placeholder="ex: 4"
          style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:0.55rem 0.75rem; color:white; font-size:0.9rem; width:110px;">
      </div>
      <button type="submit"
        style="background:rgba(74,222,128,0.15); border:1px solid rgba(74,222,128,0.3); color:#4ade80; border-radius:8px; padding:0.55rem 1.2rem; font-size:0.85rem; font-weight:600; cursor:pointer; white-space:nowrap;">
        ✚ Ajouter
      </button>
    </form>
    <?php if ($success === 'don_ajoute'): ?>
      <p style="color:#4ade80; font-size:0.8rem; margin-top:0.75rem;">✓ Stats mises à jour avec succès.</p>
    <?php endif; ?>
  </div>

  <!-- ═══ EBOOK — LEADS & VENTES ══════════════════════════ -->
  <div class="section-title" style="margin-top:2rem;">📖 Ebook — La Faim de Vivre</div>
  <?php
  try {
    require_once __DIR__ . '/../api/config.php';
    $db = db();

    // Stats globales
    $stats_leads   = $db->query("SELECT COUNT(*) FROM no_leads")->fetchColumn();
    $stats_paid    = $db->query("SELECT COUNT(*) FROM no_orders WHERE status='paid'")->fetchColumn();
    $stats_revenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM no_orders WHERE status='paid'")->fetchColumn();
    $stats_pending = $db->query("SELECT COUNT(*) FROM no_orders WHERE status='pending'")->fetchColumn();

    echo '<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:0.75rem; margin-bottom:1.25rem;">';
    foreach ([
      ['💰', number_format($stats_revenue, 2) . ' €', 'Revenus', '#4ade80'],
      ['🛒', $stats_paid,    'Ventes',   '#60a5fa'],
      ['📧', $stats_leads,   'Leads',    '#F0A500'],
      ['⏳', $stats_pending, 'En attente','rgba(255,255,255,0.4)'],
    ] as [$icon, $val, $label, $color]) {
      echo "<div style='background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:0.9rem; text-align:center;'>
        <div style='font-size:1.4rem;'>{$icon}</div>
        <div style='font-size:1.3rem; font-weight:700; color:{$color};'>{$val}</div>
        <div style='font-size:0.72rem; color:rgba(255,255,255,0.35); margin-top:2px;'>{$label}</div>
      </div>";
    }
    echo '</div>';

    // Dernières ventes
    $orders = $db->query(
      "SELECT o.id, o.email, o.prenom, o.amount, o.currency, o.created_at, o.downloaded_at, o.mollie_method
       FROM no_orders o WHERE o.status='paid'
       ORDER BY o.created_at DESC LIMIT 20"
    )->fetchAll();

    echo '<div style="font-size:0.78rem; font-weight:600; color:#4ade80; margin-bottom:0.5rem;">✅ Ventes payées (' . count($orders) . ')</div>';

    if (empty($orders)) {
      echo '<div style="padding:1rem; background:rgba(255,255,255,0.03); border-radius:8px; color:rgba(255,255,255,0.3); font-size:0.85rem; text-align:center;">Aucune vente pour l\'instant.</div>';
    } else {
      echo '<div style="overflow-x:auto; margin-bottom:1.25rem;"><table style="width:100%; border-collapse:collapse; font-size:0.8rem;">';
      echo '<thead><tr style="color:rgba(255,255,255,0.35); border-bottom:1px solid rgba(255,255,255,0.08);">';
      foreach (['Date','Email','Prénom','Montant','Méthode','Téléchargé'] as $h)
        echo "<th style='padding:0.4rem 0.6rem; text-align:left; font-weight:600;'>{$h}</th>";
      echo '</tr></thead><tbody>';
      foreach ($orders as $o) {
        $dl = $o['downloaded_at'] ? '<span style="color:#4ade80;">✓ ' . date('d/m', strtotime($o['downloaded_at'])) . '</span>' : '<span style="color:rgba(255,255,255,0.25);">—</span>';
        echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.05);'>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.5);'>" . date('d/m/y H:i', strtotime($o['created_at'])) . "</td>
          <td style='padding:0.45rem 0.6rem; color:white;'>" . htmlspecialchars($o['email']) . "</td>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.7);'>" . htmlspecialchars($o['prenom'] ?: '—') . "</td>
          <td style='padding:0.45rem 0.6rem; color:#4ade80; font-weight:700;'>" . number_format($o['amount'], 2) . " " . $o['currency'] . "</td>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.4);'>" . htmlspecialchars($o['mollie_method'] ?: '—') . "</td>
          <td style='padding:0.45rem 0.6rem;'>{$dl}</td>
        </tr>";
      }
      echo '</tbody></table></div>';
    }

    // Tous les leads
    $leads = $db->query(
      "SELECT l.email, l.prenom, l.source, l.status, l.created_at
       FROM no_leads l ORDER BY l.created_at DESC LIMIT 50"
    )->fetchAll();

    echo '<div style="font-size:0.78rem; font-weight:600; color:#F0A500; margin-bottom:0.5rem;">📧 Tous les leads (' . count($leads) . ')</div>';

    if (empty($leads)) {
      echo '<div style="padding:1rem; background:rgba(255,255,255,0.03); border-radius:8px; color:rgba(255,255,255,0.3); font-size:0.85rem; text-align:center;">Aucun lead pour l\'instant.</div>';
    } else {
      echo '<div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse; font-size:0.8rem;">';
      echo '<thead><tr style="color:rgba(255,255,255,0.35); border-bottom:1px solid rgba(255,255,255,0.08);">';
      foreach (['Date','Email','Prénom','Source','Statut'] as $h)
        echo "<th style='padding:0.4rem 0.6rem; text-align:left; font-weight:600;'>{$h}</th>";
      echo '</tr></thead><tbody>';
      $statusColors = [
        'customer'        => '#4ade80',
        'pending_payment' => '#F0A500',
        'prospect'        => '#60a5fa',
      ];
      foreach ($leads as $l) {
        $color = $statusColors[$l['status']] ?? 'rgba(255,255,255,0.4)';
        echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.05);'>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.5);'>" . date('d/m/y H:i', strtotime($l['created_at'])) . "</td>
          <td style='padding:0.45rem 0.6rem; color:white;'>" . htmlspecialchars($l['email']) . "</td>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.7);'>" . htmlspecialchars($l['prenom'] ?: '—') . "</td>
          <td style='padding:0.45rem 0.6rem; color:rgba(255,255,255,0.35); font-size:0.75rem;'>" . htmlspecialchars($l['source']) . "</td>
          <td style='padding:0.45rem 0.6rem;'><span style='color:{$color}; font-weight:600; font-size:0.75rem;'>" . htmlspecialchars($l['status']) . "</span></td>
        </tr>";
      }
      echo '</tbody></table></div>';
    }

  } catch (Exception $e) {
    echo '<div style="padding:1rem; background:rgba(220,50,50,0.1); border-radius:8px; color:#ff6b6b; font-size:0.82rem;">⚠️ Erreur DB : ' . htmlspecialchars($e->getMessage()) . '</div>';
  }
  ?>

  <!-- ═══ TÉMOIGNAGES ═══════════════════════════════════════ -->
  <div class="section-title" style="margin-top:2rem;">💬 Témoignages donateurs</div>
  <?php
  $tFile = __DIR__ . '/../data/temoignages.json';
  $tList = file_exists($tFile) ? (json_decode(file_get_contents($tFile), true) ?? []) : [];
  $pending = array_filter($tList, fn($t) => !$t['approuve'] && $t['publier']);
  $approved = array_filter($tList, fn($t) => $t['approuve']);

  if (empty($tList)): ?>
    <div style="padding:1.5rem; background:rgba(255,255,255,0.04); border-radius:0.75rem; color:rgba(255,255,255,0.4); text-align:center; font-size:0.9rem;">
      Aucun témoignage pour l'instant.
    </div>
  <?php else: ?>
    <?php if (!empty($pending)): ?>
      <div style="margin-bottom:0.5rem; font-size:0.8rem; color:#F0A500; font-weight:600;">
        ⏳ En attente d'approbation (<?= count($pending) ?>)
      </div>
      <?php foreach(array_reverse(array_values($pending)) as $t): ?>
        <div style="background:rgba(240,165,0,0.07); border:1px solid rgba(240,165,0,0.2); border-radius:0.75rem; padding:1rem; margin-bottom:0.6rem;">
          <div style="display:flex; justify-content:space-between; align-items:start; gap:1rem;">
            <div style="flex:1;">
              <div style="font-weight:700; color:white; font-size:0.9rem;"><?= $t['prenom'] ?></div>
              <div style="color:rgba(255,255,255,0.35); font-size:0.75rem; margin-bottom:0.4rem;">
                <?= $t['date'] ?> <?= $t['montant'] ? '· ' . $t['montant'] . ' €' : '' ?>
              </div>
              <div style="color:rgba(255,255,255,0.7); font-size:0.85rem; font-style:italic;">"<?= $t['message'] ?>"</div>
            </div>
            <div style="display:flex; flex-direction:column; gap:0.4rem;">
              <form method="POST" action="approuver-temoignage.php">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" style="background:#1E6B5E; color:white; border:none; padding:0.4rem 0.9rem; border-radius:1rem; font-size:0.78rem; font-weight:600; cursor:pointer; white-space:nowrap; width:100%;">
                  ✓ Approuver
                </button>
              </form>
              <form method="POST" action="supprimer-temoignage.php" onsubmit="return confirm('Supprimer ce témoignage ?')">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" style="background:rgba(220,50,50,0.15); color:#ff6b6b; border:1px solid rgba(220,50,50,0.3); padding:0.4rem 0.9rem; border-radius:1rem; font-size:0.78rem; font-weight:600; cursor:pointer; white-space:nowrap; width:100%;">
                  ✕ Supprimer
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($approved)): ?>
      <div style="margin-top:1rem; margin-bottom:0.5rem; font-size:0.8rem; color:#1E6B5E; font-weight:600;">
        ✅ Approuvés et publiés (<?= count($approved) ?>)
      </div>
      <?php foreach(array_reverse(array_values($approved)) as $t): ?>
        <div style="background:rgba(30,107,94,0.08); border:1px solid rgba(30,107,94,0.2); border-radius:0.75rem; padding:0.85rem 1rem; margin-bottom:0.5rem; display:flex; gap:0.75rem; align-items:center; justify-content:space-between;">
          <div style="display:flex; gap:0.75rem; align-items:start; flex:1;">
            <span style="font-size:1.1rem;">💚</span>
            <div>
              <div style="font-weight:700; color:white; font-size:0.85rem;"><?= $t['prenom'] ?></div>
              <div style="color:rgba(255,255,255,0.6); font-size:0.83rem; font-style:italic;">"<?= $t['message'] ?>"</div>
            </div>
          </div>
          <div style="display:flex; flex-direction:column; gap:0.3rem;">
            <form method="POST" action="depublier-temoignage.php">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <button type="submit" style="background:rgba(240,165,0,0.15); color:#F0A500; border:1px solid rgba(240,165,0,0.3); padding:0.3rem 0.75rem; border-radius:1rem; font-size:0.75rem; font-weight:600; cursor:pointer; white-space:nowrap; width:100%;">
                ⏸ Dépublier
              </button>
            </form>
            <form method="POST" action="supprimer-temoignage.php" onsubmit="return confirm('Supprimer définitivement ?')">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <button type="submit" style="background:rgba(220,50,50,0.12); color:#ff6b6b; border:1px solid rgba(220,50,50,0.25); padding:0.3rem 0.75rem; border-radius:1rem; font-size:0.75rem; font-weight:600; cursor:pointer; white-space:nowrap; width:100%;">
                ✕ Supprimer
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</body>
</html>
