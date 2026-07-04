<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function safe_path(string $path): ?string {
    $path = $path !== '' ? $path : '/';
    $real = realpath($path);
    return ($real && is_dir($real)) ? $real : null;
}

if ($method === 'GET') {
    $path = safe_path($_GET['path'] ?? '/');
    if (!$path) {
        http_response_code(404);
        echo json_encode(['error' => 'Path does not exist or is not a directory']);
        exit;
    }

    $entries = [];
    foreach (scandir($path) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        if ($entry[0] === '.') continue; // skip hidden dirs — noise for a docroot picker
        $full = $path . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($full)) continue;
        $entries[] = [
            'name'      => $entry,
            'writable'  => is_writable($full),
        ];
    }
    usort($entries, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    $parent = dirname($path);
    echo json_encode([
        'path'     => $path,
        'parent'   => $parent !== $path ? $parent : null,
        'writable' => is_writable($path),
        'entries'  => $entries,
    ]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'mkdir') {
        $path = safe_path($body['path'] ?? '/');
        $name = trim(preg_replace('/[\x00-\x1F\x7F\/]/', '', $body['name'] ?? ''));

        if (!$path) {
            http_response_code(404);
            echo json_encode(['error' => 'Path does not exist or is not a directory']);
            exit;
        }
        if ($name === '' || $name === '.' || $name === '..') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid folder name']);
            exit;
        }

        $new = $path . DIRECTORY_SEPARATOR . $name;
        if (file_exists($new)) {
            http_response_code(409);
            echo json_encode(['error' => 'A file or folder with that name already exists']);
            exit;
        }
        if (!is_writable($path)) {
            http_response_code(403);
            echo json_encode(['error' => 'Directory is not writable']);
            exit;
        }

        if (!mkdir($new, 0755)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create folder']);
            exit;
        }

        echo json_encode(['ok' => true, 'path' => $new]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
