<?php
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$path    = '/etc/hosts';
$marker  = '### end local ###';
$backdir = '/etc/apache2/sites-backup/hosts';

function split_hosts($content, $marker) {
    $pos = strpos($content, $marker);
    if ($pos === false) {
        return ['local' => rtrim($content, "\n"), 'after' => null];
    }
    $local = rtrim(substr($content, 0, $pos), "\n");
    $after = ltrim(substr($content, $pos + strlen($marker)), "\n");
    return ['local' => $local, 'after' => $after];
}

if ($method === 'GET') {
    $content = file_exists($path) ? file_get_contents($path) : '';
    $parts   = split_hosts($content, $marker);
    // The "after" section (e.g. a large ad-block list) is left untouched on save and can be huge —
    // never ship its full contents to the browser, just enough to describe it.
    $afterLines = $parts['after'] !== null && $parts['after'] !== ''
        ? substr_count(rtrim($parts['after'], "\n"), "\n") + 1
        : 0;
    echo json_encode([
        'local'       => $parts['local'],
        'has_marker'  => $parts['after'] !== null,
        'after_lines' => $afterLines,
        'after_bytes' => $parts['after'] !== null ? strlen($parts['after']) : 0,
    ]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'save') {
        // Multi-line by nature — only strip null bytes and normalize line endings, keep newlines.
        $local = str_replace("\0", '', $body['local'] ?? '');
        $local = rtrim(str_replace("\r\n", "\n", $local), "\n");

        // Re-read the current on-disk "after" section so we never clobber it with a stale copy.
        $current = file_exists($path) ? file_get_contents($path) : '';
        $parts   = split_hosts($current, $marker);
        $after   = $parts['after'] ?? '';

        $newContent = $local . "\n" . $marker . "\n" . $after;

        if (!is_dir($backdir)) mkdir($backdir, 0700, true);
        $token = (string) time();
        if (file_exists($path)) copy($path, "$backdir/$token.hosts");

        file_put_contents($path, $newContent);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'append') {
        // Add a single "IP  host" mapping to the local section — used when creating
        // a vhost with "add server name to hosts" checked. Skips if already present
        // anywhere in the local section rather than creating a duplicate/conflicting line.
        $host = trim(preg_replace('/[\x00-\x1F\x7F\s]/', '', $body['host'] ?? ''));
        $ip   = trim(preg_replace('/[^0-9a-fA-F.:]/', '', $body['ip'] ?? '')) ?: '127.0.0.1';

        if (!$host) {
            http_response_code(400);
            echo json_encode(['error' => 'host is required']);
            exit;
        }

        $current = file_exists($path) ? file_get_contents($path) : '';
        $parts   = split_hosts($current, $marker);
        $local   = $parts['local'];
        $after   = $parts['after'] ?? '';

        foreach (explode("\n", $local) as $line) {
            $trimmed = trim(preg_replace('/#.*/', '', $line));
            if ($trimmed === '') continue;
            $fields = preg_split('/\s+/', $trimmed);
            if (in_array($host, array_slice($fields, 1), true)) {
                echo json_encode(['ok' => true, 'skipped' => true]);
                exit;
            }
        }

        $newLocal = rtrim($local, "\n") . "\n{$ip}\t{$host}";

        if (!is_dir($backdir)) mkdir($backdir, 0700, true);
        $token = (string) time();
        if (file_exists($path)) copy($path, "$backdir/$token.hosts");

        file_put_contents($path, $newLocal . "\n" . $marker . "\n" . $after);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'disable' || $action === 'enable') {
        $current = file_exists($path) ? file_get_contents($path) : '';
        $parts   = split_hosts($current, $marker);
        $local   = $parts['local'];
        $after   = $parts['after'] ?? '';

        $prefix = '#DISABLED# ';
        $lines  = explode("\n", $local);
        foreach ($lines as &$line) {
            if ($action === 'disable') {
                if (trim($line) === '' || str_starts_with(ltrim($line), '#')) continue;
                $line = $prefix . $line;
            } else {
                if (str_starts_with(ltrim($line), $prefix)) {
                    $line = preg_replace('/^(\s*)' . preg_quote($prefix, '/') . '/', '$1', $line);
                }
            }
        }
        unset($line);
        $newLocal = implode("\n", $lines);

        if (!is_dir($backdir)) mkdir($backdir, 0700, true);
        $token = (string) time();
        if (file_exists($path)) copy($path, "$backdir/$token.hosts");

        file_put_contents($path, $newLocal . "\n" . $marker . "\n" . $after);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'restore') {
        $token  = preg_replace('/[^0-9]/', '', $body['token'] ?? '');
        $backup = "$backdir/$token.hosts";

        if (!$token || !file_exists($backup)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            exit;
        }

        copy($backup, $path);
        unlink($backup);
        echo json_encode(['ok' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
