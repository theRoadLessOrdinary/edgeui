<?php http_response_code(503); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>EdgeUI — Setup needed</title>
<style>
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #05080f; color: #f2f5fb;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  }
  .card { width: 480px; background: #101a2c; border: 1px solid rgba(255,255,255,.11); border-radius: 10px; padding: 2rem; }
  h1 { font-size: 1.1rem; margin: 0 0 1rem; }
  code { background: #1e2c48; padding: 2px 6px; border-radius: 4px; }
  p { font-size: .9rem; color: #ccd7ec; line-height: 1.6; }
</style>
</head>
<body>
  <div class="card">
    <h1>No password set yet</h1>
    <p>EdgeUI requires a password before it will serve anything. From this
    machine, run:</p>
    <p><code>php <?= htmlspecialchars(__DIR__) ?>/set-password.php</code></p>
    <p>Then reload this page.</p>
  </div>
</body>
</html>
