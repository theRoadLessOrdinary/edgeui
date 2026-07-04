<?php
/**
 * lib/auth.php
 * Minimal session-password auth. No database — the password hash lives in
 * .auth-config.php (gitignored, .php so a misconfigured static server would
 * execute it rather than leak its contents). Auto-provisions a default
 * password ("admin1234") on first request if none exists yet, rather than
 * blocking until `set-password.php` is run by hand — change it via the
 * in-app "Change Password" dialog right away, since the default is public
 * (this repo is public on GitHub).
 */

define('AUTH_CONFIG_PATH', __DIR__ . '/../.auth-config.php');
define('AUTH_THROTTLE_PATH', __DIR__ . '/../.auth-throttle.php');
define('AUTH_DEFAULT_PASSWORD', 'admin1234');

function auth_is_configured(): bool {
    return file_exists(AUTH_CONFIG_PATH);
}

function auth_ensure_configured(): void {
    if (!auth_is_configured()) {
        auth_set_password(AUTH_DEFAULT_PASSWORD);
    }
}

function auth_get_hash(): ?string {
    if (!auth_is_configured()) return null;
    $cfg = require AUTH_CONFIG_PATH;
    return $cfg['password_hash'] ?? null;
}

function auth_set_password(string $password): void {
    $hash    = password_hash($password, PASSWORD_DEFAULT);
    $content = "<?php\nreturn ['password_hash' => " . var_export($hash, true) . "];\n";
    file_put_contents(AUTH_CONFIG_PATH, $content);
    chmod(AUTH_CONFIG_PATH, 0600);
}

function auth_start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_name('edgeui_sess');
        session_start();
    }
}

function auth_is_logged_in(): bool {
    auth_start_session();
    return !empty($_SESSION['edgeui_authed']);
}

// Basic throttling — a fixed, growing delay after repeated failures, since
// there's no rate-limiting proxy in front of a `php -S` dev server and this
// guards a root-privileged tool. Not a full lockout: a single-user tool
// shouldn't risk locking its only user out entirely.
function auth_throttle_check(): void {
    $state = file_exists(AUTH_THROTTLE_PATH) ? (require AUTH_THROTTLE_PATH) : ['fails' => 0, 'last' => 0];
    $fails = $state['fails'] ?? 0;
    if ($fails >= 3) {
        $wait = min(30, pow(2, $fails - 3)); // 1s,2s,4s,8s,16s,capped at 30s
        $elapsed = time() - ($state['last'] ?? 0);
        if ($elapsed < $wait) {
            sleep(min($wait - $elapsed, 5)); // don't hang the request forever
        }
    }
}

function auth_throttle_record(bool $success): void {
    if ($success) {
        if (file_exists(AUTH_THROTTLE_PATH)) unlink(AUTH_THROTTLE_PATH);
        return;
    }
    $state = file_exists(AUTH_THROTTLE_PATH) ? (require AUTH_THROTTLE_PATH) : ['fails' => 0, 'last' => 0];
    $fails   = ($state['fails'] ?? 0) + 1;
    $content = "<?php\nreturn ['fails' => {$fails}, 'last' => " . time() . "];\n";
    file_put_contents(AUTH_THROTTLE_PATH, $content);
    chmod(AUTH_THROTTLE_PATH, 0600);
}

function auth_login(string $password): bool {
    auth_throttle_check();
    $hash = auth_get_hash();
    $ok   = $hash && password_verify($password, $hash);
    auth_throttle_record($ok);
    if ($ok) {
        auth_start_session();
        session_regenerate_id(true);
        $_SESSION['edgeui_authed'] = true;
    }
    return $ok;
}

function auth_logout(): void {
    auth_start_session();
    $_SESSION = [];
    session_destroy();
}

function auth_require_web(): void {
    if (!auth_is_configured()) {
        require __DIR__ . '/../setup-needed.php';
        exit;
    }
    if (!auth_is_logged_in()) {
        header('Location: /login');
        exit;
    }
}

function auth_require_api(): void {
    if (!auth_is_configured() || !auth_is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}
