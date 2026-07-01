<?php
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$backdir = '/etc/apache2/sites-backup/redirects';

function vhost_path($name) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
    return "/etc/apache2/sites-available/{$name}.conf";
}

function parse_redirects($path) {
    if (!file_exists($path)) return [];
    $lines   = file($path, FILE_IGNORE_NEW_LINES);
    $results = [];
    foreach ($lines as $i => $line) {
        $trimmed = trim($line);
        // RedirectMatch
        if (preg_match('/^RedirectMatch\s+(\S+)\s+"?([^"\s]+)"?\s*"?([^"\s]*)"?/i', $trimmed, $m)) {
            $status = is_numeric($m[1]) ? intval($m[1]) : status_word($m[1]);
            $results[] = [
                'line'   => $i,
                'type'   => 'match',
                'status' => $status,
                'from'   => $m[2],
                'to'     => $m[3] ?? '',
            ];
            continue;
        }
        // Redirect
        if (preg_match('/^Redirect\s+(\S+)\s+(\S+)\s*(\S*)/i', $trimmed, $m)) {
            $status = is_numeric($m[1]) ? intval($m[1]) : status_word($m[1]);
            $results[] = [
                'line'   => $i,
                'type'   => 'exact',
                'status' => $status,
                'from'   => $m[2],
                'to'     => $m[3] ?? '',
            ];
        }
    }
    return $results;
}

function status_word($word) {
    return match(strtolower($word)) {
        'permanent' => 301,
        'temp'      => 302,
        'seeother'  => 303,
        'gone'      => 410,
        default     => 302,
    };
}

function backup_vhost($name, $path, $backdir) {
    if (!is_dir($backdir)) mkdir($backdir, 0700, true);
    $token = time() . '_' . $name;
    copy($path, "$backdir/$token.conf");
    return $token;
}

if ($method === 'GET') {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['vhost'] ?? '');
    if (!$name) { echo json_encode([]); exit; }
    echo json_encode(parse_redirects(vhost_path($name)));
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $vhost  = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
        $type   = $body['type'] === 'match' ? 'match' : 'exact';
        $status = in_array(intval($body['status'] ?? 0), [301, 302, 307, 410]) ? intval($body['status']) : 301;
        $from   = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['from'] ?? ''));
        $to     = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['to'] ?? ''));

        if (!$vhost || !$from || ($status !== 410 && !$to)) {
            http_response_code(400);
            echo json_encode(['error' => 'vhost, from, and to are required']);
            exit;
        }

        $path = vhost_path($vhost);
        if (!file_exists($path)) {
            http_response_code(404);
            echo json_encode(['error' => 'Virtual host config not found']);
            exit;
        }

        $token = backup_vhost($vhost, $path, $backdir);

        if ($type === 'match') {
            $directive = $status === 410
                ? "    RedirectMatch gone \"$from\""
                : "    RedirectMatch $status \"$from\" \"$to\"";
        } else {
            $directive = $status === 410
                ? "    Redirect gone $from"
                : "    Redirect $status $from $to";
        }

        // Insert before closing </VirtualHost>
        $content = file_get_contents($path);
        $content = preg_replace('/(<\/VirtualHost>)/i', "$directive\n$1", $content, 1);
        file_put_contents($path, $content);
        exec('apachectl graceful 2>&1');

        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'delete') {
        $vhost = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
        $line  = intval($body['line'] ?? -1);

        $path = vhost_path($vhost);
        if (!file_exists($path) || $line < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $token = backup_vhost($vhost, $path, $backdir);

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Re-read without skipping empty so line numbers stay accurate
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        unset($lines[$line]);
        file_put_contents($path, implode("\n", $lines) . "\n");
        exec('apachectl graceful 2>&1');

        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'restore') {
        $token = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');
        $backup = "$backdir/$token.conf";

        if (!file_exists($backup)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            exit;
        }

        // token format: timestamp_name
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
