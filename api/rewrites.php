<?php
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$backdir = '/etc/apache2/sites-backup/rewrites';

function vhost_path($name) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
    return "/etc/apache2/sites-available/{$name}.conf";
}

function backup_vhost($name, $path, $backdir) {
    if (!is_dir($backdir)) mkdir($backdir, 0700, true);
    $token = time() . '_' . $name;
    copy($path, "$backdir/$token.conf");
    return $token;
}

function parse_rewrites($path) {
    if (!file_exists($path)) return ['engine_on' => false, 'rules' => []];
    $lines      = file($path, FILE_IGNORE_NEW_LINES);
    $engine_on  = false;
    $rules      = [];
    $pendingConds = [];

    foreach ($lines as $i => $line) {
        $trimmed = trim($line);

        if (preg_match('/^RewriteEngine\s+On/i', $trimmed)) {
            $engine_on = true;
            continue;
        }

        if (preg_match('/^RewriteCond\s+(\S+)\s+(\S+)(?:\s+\[([^\]]*)\])?/i', $trimmed, $m)) {
            $pendingConds[] = ['line' => $i, 'test' => $m[1], 'pattern' => $m[2], 'flags' => $m[3] ?? ''];
            continue;
        }

        if (preg_match('/^RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[([^\]]*)\])?/i', $trimmed, $m)) {
            $condLines = array_map(fn($c) => $c['line'], $pendingConds);
            $rules[] = [
                'lines'        => array_merge($condLines, [$i]),
                'pattern'      => $m[1],
                'substitution' => $m[2],
                'flags'        => $m[3] ?? '',
                'conditions'   => $pendingConds,
            ];
            $pendingConds = [];
            continue;
        }

        // Any other directive breaks a pending cond block (conds must immediately precede the rule)
        if ($trimmed !== '') $pendingConds = [];
    }

    return ['engine_on' => $engine_on, 'rules' => $rules];
}

if ($method === 'GET') {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['vhost'] ?? '');
    if (!$name) { echo json_encode(['engine_on' => false, 'rules' => []]); exit; }
    echo json_encode(parse_rewrites(vhost_path($name)));
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $vhost        = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
        $pattern      = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['pattern'] ?? ''));
        $substitution = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['substitution'] ?? ''));
        $flags        = preg_replace('/[^a-zA-Z0-9=,_.\/-]/', '', $body['flags'] ?? '');
        $conditions   = is_array($body['conditions'] ?? null) ? $body['conditions'] : [];

        if (!$vhost || !$pattern || !$substitution) {
            http_response_code(400);
            echo json_encode(['error' => 'vhost, pattern, and substitution are required']);
            exit;
        }

        $path = vhost_path($vhost);
        if (!file_exists($path)) {
            http_response_code(404);
            echo json_encode(['error' => 'Virtual host config not found']);
            exit;
        }

        $token = backup_vhost($vhost, $path, $backdir);

        $block = [];
        foreach ($conditions as $c) {
            $test    = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $c['test'] ?? ''));
            $cpat    = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $c['pattern'] ?? ''));
            $cflags  = preg_replace('/[^a-zA-Z0-9,]/', '', $c['flags'] ?? '');
            if ($test === '' || $cpat === '') continue;
            $block[] = "    RewriteCond $test $cpat" . ($cflags ? " [$cflags]" : '');
        }
        $block[] = "    RewriteRule $pattern $substitution" . ($flags ? " [$flags]" : '');
        $directive = implode("\n", $block);

        $content = file_get_contents($path);
        if (!preg_match('/^\s*RewriteEngine\s+On/mi', $content)) {
            $directive = "    RewriteEngine On\n" . $directive;
        }
        $content = preg_replace('/(<\/VirtualHost>)/i', "$directive\n$1", $content, 1);
        file_put_contents($path, $content);
        exec('apachectl graceful 2>&1');

        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'delete') {
        $vhost      = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
        $ruleLines  = is_array($body['lines'] ?? null) ? array_map('intval', $body['lines']) : [];

        $path = vhost_path($vhost);
        if (!file_exists($path) || !$ruleLines) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $token = backup_vhost($vhost, $path, $backdir);

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($ruleLines as $line) unset($lines[$line]);
        file_put_contents($path, implode("\n", $lines) . "\n");
        exec('apachectl graceful 2>&1');

        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'restore') {
        $token  = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');
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
