<?php
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$backdir = '/etc/apache2/sites-backup/errors';

function vhost_path($name) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
    return "/etc/apache2/sites-available/{$name}.conf";
}

function backup($name, $path, $backdir) {
    if (!is_dir($backdir)) mkdir($backdir, 0700, true);
    $token = time() . '_' . $name;
    copy($path, "$backdir/$token.conf");
    return $token;
}

function restore_path($token, $backdir) {
    return "$backdir/$token.conf";
}

function parse_config($path) {
    if (!file_exists($path)) return null;
    $content = file_get_contents($path);
    $lines   = explode("\n", $content);

    $error_docs   = [];
    $php_errors   = null;
    $error_log    = null;
    $error_log_line = null;
    $log_disabled = false;

    foreach ($lines as $i => $line) {
        $t = trim($line);

        if (preg_match('/^ErrorDocument\s+(\d+)\s+(\S+)/i', $t, $m)) {
            $error_docs[] = ['line' => $i, 'code' => $m[1], 'target' => $m[2]];
            continue;
        }
        if (preg_match('/^php_(?:admin_)?flag\s+display_errors\s+(\S+)/i', $t, $m)) {
            $php_errors = ['line' => $i, 'on' => strtolower($m[1]) === 'on'];
            continue;
        }
        if (preg_match('/^ErrorLog\s+(\S+)/i', $t, $m)) {
            $error_log      = $m[1];
            $error_log_line = $i;
            $log_disabled   = ($m[1] === '/dev/null');
        }
    }

    return [
        'error_docs'      => $error_docs,
        'php_errors'      => $php_errors,
        'error_log'       => $error_log,
        'error_log_line'  => $error_log_line,
        'log_disabled'    => $log_disabled,
    ];
}

function write_file($path, $lines) {
    file_put_contents($path, implode("\n", $lines));
    exec('apachectl graceful 2>&1');
}

if ($method === 'GET') {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['vhost'] ?? '');
    if (!$name) { echo json_encode(null); exit; }
    echo json_encode(parse_config(vhost_path($name)));
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    $vhost  = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
    $path   = vhost_path($vhost);

    if (!$vhost || !file_exists($path)) {
        http_response_code(404);
        echo json_encode(['error' => 'Virtual host not found']);
        exit;
    }

    $token = backup($vhost, $path, $backdir);
    $lines = explode("\n", file_get_contents($path));

    // ── Custom error page ──────────────────────────────────────────────────────
    if ($action === 'set_error_doc') {
        $code   = preg_replace('/[^0-9]/', '', $body['code'] ?? '');
        $target = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['target'] ?? ''));

        if (!$code) { http_response_code(400); echo json_encode(['error' => 'code required']); exit; }

        // Remove existing ErrorDocument for this code
        $lines = array_filter($lines, function($l) use ($code) {
            return !preg_match('/^\s*ErrorDocument\s+' . $code . '\s+/i', $l);
        });
        $lines = array_values($lines);

        if ($target) {
            // Insert before </VirtualHost>
            foreach ($lines as $i => $l) {
                if (preg_match('/<\/VirtualHost>/i', $l)) {
                    array_splice($lines, $i, 0, ["    ErrorDocument $code $target"]);
                    break;
                }
            }
        }

        write_file($path, $lines);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    // ── PHP display_errors ─────────────────────────────────────────────────────
    if ($action === 'set_php_errors') {
        $on  = !empty($body['on']);
        $val = $on ? 'On' : 'Off';

        // Replace existing or insert before </VirtualHost>
        $found = false;
        foreach ($lines as $i => $l) {
            if (preg_match('/^\s*php_(?:admin_)?flag\s+display_errors/i', $l)) {
                $lines[$i] = "    php_flag display_errors $val";
                $found = true;
                break;
            }
        }
        if (!$found) {
            foreach ($lines as $i => $l) {
                if (preg_match('/<\/VirtualHost>/i', $l)) {
                    array_splice($lines, $i, 0, ["    php_flag display_errors $val"]);
                    break;
                }
            }
        }

        write_file($path, $lines);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    // ── Error logging ──────────────────────────────────────────────────────────
    if ($action === 'set_error_log') {
        $disable = !empty($body['disable']);

        foreach ($lines as $i => $l) {
            if (preg_match('/^\s*ErrorLog\s+(\S+)/i', $l, $m)) {
                $current = $m[1];
                if ($disable) {
                    // Store real path in a comment, switch to /dev/null
                    if ($current !== '/dev/null') {
                        $lines[$i] = "    ErrorLog /dev/null # was: $current";
                    }
                } else {
                    // Restore real path from comment if present
                    if (preg_match('/# was:\s*(\S+)/', $l, $cm)) {
                        $lines[$i] = "    ErrorLog {$cm[1]}";
                    } else {
                        $lines[$i] = "    ErrorLog {$current}";
                    }
                }
                break;
            }
        }

        write_file($path, $lines);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    // ── Restore ────────────────────────────────────────────────────────────────
    if ($action === 'restore') {
        $token  = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');
        $backup = restore_path($token, $backdir);
        if (!file_exists($backup)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            exit;
        }
        $name = preg_replace('/^\d+_/', '', $token);
        $dest = "/etc/apache2/sites-available/{$name}.conf";
        copy($backup, $dest);
        unlink($backup);
        exec('apachectl graceful 2>&1');
        echo json_encode(['ok' => true, 'vhost' => $name]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
