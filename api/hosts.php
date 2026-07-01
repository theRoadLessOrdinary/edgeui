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
    echo json_encode([
        'local'      => $parts['local'],
        'has_marker' => $parts['after'] !== null,
        'after'      => $parts['after'] ?? '',
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
