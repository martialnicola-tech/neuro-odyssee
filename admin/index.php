<?php
session_start();

// Mot de passe admin (à changer après installation)
define('ADMIN_PASSWORD', 'Camino2026!');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = true;
    }
}

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Admin — La Neuro-Odyssée</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      background: linear-gradient(160deg, #1E6B5E 0%, #1a2332 60%, #0f1a2e 100%);
      display: flex; align-items: center; justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      padding: 1rem;
    }
    .card {
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 360px;
      text-align: center;
    }
    .logo {
      font-size: 2.5rem; margin-bottom: 0.5rem;
    }
    h1 {
      color: white;
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .subtitle {
      color: rgba(255,255,255,0.4);
      font-size: 0.8rem;
      margin-bottom: 2rem;
    }
    input[type="password"] {
      width: 100%;
      padding: 0.9rem 1rem;
      border-radius: 12px;
      border: 1.5px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.07);
      color: white;
      font-size: 1rem;
      outline: none;
      margin-bottom: 1rem;
      transition: border 0.2s;
    }
    input[type="password"]:focus {
      border-color: #F0A500;
    }
    input[type="password"]::placeholder { color: rgba(255,255,255,0.3); }
    button {
      width: 100%;
      padding: 0.9rem;
      background: #F0A500;
      color: #1a2332;
      font-weight: 700;
      font-size: 1rem;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    button:active { opacity: 0.85; }
    .error {
      background: rgba(220,50,50,0.15);
      border: 1px solid rgba(220,50,50,0.3);
      color: #ff8080;
      border-radius: 10px;
      padding: 0.7rem;
      font-size: 0.85rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">🧭</div>
    <h1>La Neuro-Odyssée</h1>
    <p class="subtitle">Espace Roland — Publication</p>

    <?php if (!empty($error)): ?>
      <div class="error">Mot de passe incorrect</div>
    <?php endif; ?>

    <form method="POST">
      <input type="password" name="password" placeholder="Mot de passe" autofocus autocomplete="current-password">
      <button type="submit">Accéder →</button>
    </form>
  </div>
</body>
</html>
