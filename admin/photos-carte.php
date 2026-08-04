<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$dataFile  = __DIR__ . '/../data/photos-carte.json';
$uploadDir = __DIR__ . '/../images/carte/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$photos = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?? []) : [];
$success = ''; $error = '';

// --- Conversion EXIF GPS -> décimal ---
function exifGpsToDec($coord, $ref) {
    if (!is_array($coord) || count($coord) < 3) return null;
    $parts = [];
    foreach ($coord as $c) {
        if (strpos($c, '/') !== false) { [$n, $d] = explode('/', $c); $parts[] = $d ? $n / $d : 0; }
        else { $parts[] = (float)$c; }
    }
    $dec = $parts[0] + $parts[1] / 60 + $parts[2] / 3600;
    return in_array($ref, ['S', 'W']) ? -$dec : $dec;
}

// --- Suppression ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    foreach ($photos as $p) {
        if ($p['id'] === $id && !empty($p['img'])) {
            $f = __DIR__ . '/../' . $p['img'];
            if (is_file($f) && strpos(basename($f), 'carte_') === 0) unlink($f);
        }
    }
    $photos = array_values(array_filter($photos, fn($p) => $p['id'] !== $id));
    file_put_contents($dataFile, json_encode($photos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $success = 'Photo supprimée.';
}

// --- Ajout ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $caption = trim($_POST['caption'] ?? '');
    $latIn   = trim($_POST['lat'] ?? '');
    $lngIn   = trim($_POST['lng'] ?? '');

    if (empty($_FILES['photo']['tmp_name'])) {
        $error = 'Choisissez une photo.';
    } else {
        $tmp = $_FILES['photo']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'])) {
            $error = 'Format non supporté (jpg, png, webp).';
        } elseif ($_FILES['photo']['size'] > 25 * 1024 * 1024) {
            $error = 'Photo trop lourde (25 Mo max).';
        } else {
            // 1) Position : EXIF de la photo → sinon champs manuels → sinon dernière position GPS
            $lat = null; $lng = null; $src = '';
            if (function_exists('exif_read_data') && in_array($ext, ['jpg', 'jpeg'])) {
                $exif = @exif_read_data($tmp);
                if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
                    $lat = exifGpsToDec($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
                    $lng = exifGpsToDec($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');
                    $src = 'exif';
                }
            }
            if (($lat === null || $lng === null) && is_numeric($latIn) && is_numeric($lngIn)) {
                $lat = (float)$latIn; $lng = (float)$lngIn; $src = 'manuel';
            }
            if ($lat === null || $lng === null) {
                $trk = json_decode(@file_get_contents(__DIR__ . '/../data/tracking.json'), true);
                if (!empty($trk['current']['lat'])) {
                    $lat = $trk['current']['lat']; $lng = $trk['current']['lng']; $src = 'gps';
                }
            }

            if ($lat === null || $lng === null) {
                $error = 'Aucune position : activez le suivi GPS, ou renseignez lat/lng.';
            } else {
                // 2) Redimensionner (max 1400px) + recompresser en JPEG
                $name = uniqid('carte_', true) . '.jpg';
                $dest = $uploadDir . $name;
                $saved = false;
                if (function_exists('imagecreatetruecolor')) {
                    $img = null;
                    if (in_array($ext, ['jpg', 'jpeg'])) $img = @imagecreatefromjpeg($tmp);
                    elseif ($ext === 'png')  $img = @imagecreatefrompng($tmp);
                    elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($tmp);
                    if ($img) {
                        // Orientation EXIF
                        if (in_array($ext, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                            $o = @exif_read_data($tmp)['Orientation'] ?? 1;
                            if ($o == 3) $img = imagerotate($img, 180, 0);
                            elseif ($o == 6) $img = imagerotate($img, -90, 0);
                            elseif ($o == 8) $img = imagerotate($img, 90, 0);
                        }
                        $w = imagesx($img); $h = imagesy($img);
                        $max = 1400;
                        if (max($w, $h) > $max) {
                            $r = $max / max($w, $h);
                            $nw = (int)($w * $r); $nh = (int)($h * $r);
                            $res = imagecreatetruecolor($nw, $nh);
                            imagecopyresampled($res, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                            imagedestroy($img); $img = $res;
                        }
                        $saved = imagejpeg($img, $dest, 80);
                        imagedestroy($img);
                    }
                }
                if (!$saved) { $saved = move_uploaded_file($tmp, $dest); }

                if ($saved) {
                    $photos[] = [
                        'id'      => uniqid('ph_'),
                        'img'     => 'images/carte/' . $name,
                        'caption' => mb_substr($caption, 0, 200),
                        'lat'     => round((float)$lat, 5),
                        'lng'     => round((float)$lng, 5),
                        'time'    => time(),
                        'pos_src' => $src,
                    ];
                    file_put_contents($dataFile, json_encode($photos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
                    $success = 'Photo publiée sur la carte (' . ($src === 'exif' ? 'position de la photo' : ($src === 'gps' ? 'position GPS actuelle' : 'position saisie')) . ').';
                } else {
                    $error = 'Échec de l\'enregistrement de l\'image.';
                }
            }
        }
    }
}

// Dernière position connue (pour info)
$trk = json_decode(@file_get_contents(__DIR__ . '/../data/tracking.json'), true);
$lastPos = $trk['current'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Photos sur la carte — Admin</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0f1520; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #e0e0e0; min-height: 100vh; }
    .container { max-width: 600px; margin: 0 auto; padding: 1.25rem; }
    h1 { font-size: 1.15rem; color: #F0A500; padding: 1rem 1.25rem 0; max-width: 600px; margin: 0 auto; }
    a.back { color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; }
    .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
    label { display: block; font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 0.8rem 0 0.3rem; }
    input[type=text], input[type=file] { width: 100%; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 0.6rem 0.75rem; color: white; font-size: 0.9rem; }
    .btn { background: #F0A500; color: #1a2332; border: none; border-radius: 8px; padding: 0.7rem 1.3rem; font-weight: 700; font-size: 0.9rem; cursor: pointer; margin-top: 1rem; }
    .btn-del { background: rgba(200,50,50,0.2); border: 1px solid rgba(200,50,50,0.4); color: #ff9090; border-radius: 7px; padding: 0.35rem 0.7rem; font-size: 0.75rem; cursor: pointer; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; }
    .ok  { background: rgba(30,107,94,0.25); border: 1px solid rgba(30,107,94,0.5); color: #7ecfc4; }
    .err { background: rgba(200,50,50,0.2); border: 1px solid rgba(200,50,50,0.4); color: #ff9090; }
    .ph { display: flex; gap: 0.9rem; align-items: center; padding: 0.7rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .ph img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; }
    .ph .meta { flex: 1; font-size: 0.8rem; color: rgba(255,255,255,0.6); line-height: 1.5; }
    .hint { font-size: 0.75rem; color: rgba(255,255,255,0.4); line-height: 1.5; margin-top: 0.5rem; }
  </style>
</head>
<body>
  <h1>📸 Photos sur la carte &nbsp;<a class="back" href="dashboard.php">← retour</a></h1>
  <div class="container">
    <?php if ($success): ?><div class="alert ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
      <form method="POST" enctype="multipart/form-data">
        <label>Photo</label>
        <input type="file" name="photo" accept="image/*" required>
        <label>Légende (optionnel)</label>
        <input type="text" name="caption" maxlength="200" placeholder="ex : Col du Grand-St-Bernard, quel panorama !">
        <label>Position manuelle (optionnel, sinon auto)</label>
        <div style="display:flex; gap:0.5rem;">
          <input type="text" name="lat" placeholder="lat (ex 46.219)" style="flex:1;">
          <input type="text" name="lng" placeholder="lng (ex 7.003)" style="flex:1;">
        </div>
        <p class="hint">
          📍 La position est prise automatiquement : d'abord depuis la photo (si elle contient le lieu),
          sinon depuis la dernière position GPS du suivi<?= $lastPos ? ' (actuellement : ' . $lastPos['lat'] . ', ' . $lastPos['lng'] . ')' : ' (aucune pour l\'instant)' ?>.
        </p>
        <button class="btn" type="submit">Publier sur la carte</button>
      </form>
    </div>

    <div class="card">
      <p style="font-size:0.85rem; color:#F0A500; font-weight:600; margin-bottom:0.4rem;"><?= count($photos) ?> photo<?= count($photos) > 1 ? 's' : '' ?> sur la carte</p>
      <?php foreach (array_reverse($photos) as $p): ?>
        <div class="ph">
          <img src="../<?= htmlspecialchars($p['img']) ?>" alt="">
          <div class="meta">
            <?= htmlspecialchars($p['caption'] ?: '(sans légende)') ?><br>
            <?= date('d/m/Y H:i', $p['time']) ?> · <?= $p['lat'] ?>, <?= $p['lng'] ?>
          </div>
          <form method="POST" onsubmit="return confirm('Retirer cette photo de la carte ?')">
            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($p['id']) ?>">
            <button class="btn-del" type="submit">Suppr.</button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (!$photos): ?><p class="hint">Aucune photo pour l'instant.</p><?php endif; ?>
    </div>
  </div>
</body>
</html>
