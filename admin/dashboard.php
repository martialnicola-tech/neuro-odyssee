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
      background: linear-gradient(90deg, #1E6B5E, #1a2332);
      padding: 1rem 1.25rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    header h1 { color: white; font-size: 1.1rem; font-weight: 700; }
    header a { color: rgba(255,255,255,0.5); font-size: 0.8rem; text-decoration: none; }
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
    .empty-state { color: rgba(255,255,255,0.25); font-size: 0.85rem; text-align: center; padding: 1.5rem 0; }
  </style>
</head>
<body>
  <header>
    <h1>🧭 Publier un contenu</h1>
    <a href="logout.php">Déconnexion</a>
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

    <!-- Articles récents -->
    <div class="section-title">Dernières publications</div>
    <div id="recentPosts">
      <?php
      $dataFile = __DIR__ . '/../data/posts.json';
      $posts = [];
      if (file_exists($dataFile)) {
          $posts = json_decode(file_get_contents($dataFile), true) ?? [];
      }
      $recent = array_slice(array_reverse($posts), 0, 5);
      $typeIcons = ['journal' => '📔', 'etape' => '📍', 'entrainement' => '🏃', 'neuro' => '🧠'];
      if (empty($recent)) {
          echo '<div class="empty-state">Aucune publication pour l\'instant</div>';
      } else {
          foreach ($recent as $p) {
              $icon = $typeIcons[$p['type'] ?? 'journal'] ?? '📝';
              $date = date('d/m/Y', $p['created_at'] ?? time());
              echo '<div class="post-card">';
              echo '  <span class="post-type">' . $icon . '</span>';
              echo '  <div style="flex:1">';
              echo '    <div class="post-title">' . htmlspecialchars($p['title']) . '</div>';
              echo '    <div class="post-meta">' . $date;
              if (!empty($p['location'])) echo ' · ' . htmlspecialchars($p['location']);
              echo '</div>';
              echo '  </div>';
              echo '  <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm(\'Supprimer ?\')">';
              echo '    <input type="hidden" name="id" value="' . htmlspecialchars($p['id']) . '">';
              echo '    <button type="submit" class="delete-btn">🗑</button>';
              echo '  </form>';
              echo '</div>';
          }
      }
      ?>
    </div>
  </div>

  <script>
    function selectType(type, el) {
      document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
      el.querySelector('input[type="radio"]').checked = true;
    }

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
          <form method="POST" action="supprimer-temoignage.php" onsubmit="return confirm('Supprimer ce témoignage ?')">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" style="background:rgba(220,50,50,0.15); color:#ff6b6b; border:1px solid rgba(220,50,50,0.3); padding:0.3rem 0.75rem; border-radius:1rem; font-size:0.75rem; font-weight:600; cursor:pointer; white-space:nowrap;">
              ✕
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</body>
</html>
