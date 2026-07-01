<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function list_modules() {
    $available   = glob('/etc/apache2/mods-available/*.load') ?: [];
    $enabled_raw = glob('/etc/apache2/mods-enabled/*.load') ?: [];
    $enabled     = [];
    foreach ($enabled_raw as $e) $enabled[] = basename(realpath($e) ?: $e);

    $mods = [];
    foreach ($available as $path) {
        $mods[] = [
            'name'    => basename($path, '.load'),
            'enabled' => in_array(basename($path), $enabled),
        ];
    }
    usort($mods, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $mods;
}

if ($method === 'GET') {
    echo json_encode(list_modules());
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'toggle') {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $body['name'] ?? '');
        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'name required']);
            exit;
        }
        $cmd = !empty($body['enable']) ? "a2enmod $name" : "a2dismod $name";
        exec($cmd . ' 2>&1', $out, $rc);
        exec('apachectl graceful 2>&1', $out2, $rc2);
        echo json_encode(['ok' => $rc === 0 && $rc2 === 0, 'output' => implode("\n", array_merge($out, $out2))]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
