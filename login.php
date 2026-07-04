<?php
require_once __DIR__ . '/lib/auth.php';

if (auth_is_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (auth_login($password)) {
        header('Location: /');
        exit;
    }
    $error = 'Incorrect password.';
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>EdgeUI — Sign in</title>
<style>
  :root {
    --dark: #05080f; --dark-2: #101a2c; --dark-3: #1e2c48;
    --blue: #2979ff; --red: #ef5350;
    --text: #f2f5fb; --text-mute: #9fb2d4;
    --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--dark); color: var(--text); font-family: var(--font);
  }
  .login-card {
    width: 320px; background: var(--dark-2); border: 1px solid rgba(255,255,255,.11);
    border-radius: 10px; padding: 2rem;
  }
  .login-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; text-align: center; }
  .login-title span { color: var(--blue); }
  input[type=password] {
    width: 100%; background: var(--dark-3); border: 1px solid rgba(255,255,255,.16);
    border-radius: 6px; color: var(--text); font-family: var(--font); font-size: .9rem;
    padding: .6rem .7rem; margin-bottom: 1rem;
  }
  input:focus { outline: none; border-color: var(--blue); }
  button {
    width: 100%; background: var(--blue); color: #fff; border: none; border-radius: 6px;
    padding: .65rem; font-size: .9rem; font-weight: 600; cursor: pointer; font-family: var(--font);
  }
  button:hover { background: #3d8bff; }
  .error {
    color: var(--red); font-size: .82rem; margin-bottom: 1rem; text-align: center;
  }
</style>
</head>
<body>
  <form class="login-card" method="POST" autocomplete="off">
    <div class="login-title">Edge<span>UI</span></div>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <input type="password" name="password" placeholder="Password" autofocus required>
    <button type="submit">Sign in</button>
  </form>
</body>
</html>
