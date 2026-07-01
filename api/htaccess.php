<?php
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$backdir = '/etc/apache2/sites-backup/htaccess';

function vhost_conf_path($name) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
    return "/etc/apache2/sites-available/{$name}.conf";
}

function doc_root_for($name) {
    $path = vhost_conf_path($name);
    if (!file_exists($path)) return null;
    $content = file_get_contents($path);
    if (!preg_match('/DocumentRoot\s+(\S+)/i', $content, $m)) return null;
    return rtrim($m[1], '/');
}

function htaccess_path($name) {
    $docRoot = doc_root_for($name);
    return $docRoot ? "$docRoot/.htaccess" : null;
}

if ($method === 'GET') {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['vhost'] ?? '');
    $path = $name ? htaccess_path($name) : null;

    if (!$path) {
        echo json_encode(['exists' => false, 'content' => '', 'path' => null]);
        exit;
    }

    echo json_encode([
        'exists'  => file_exists($path),
        'content' => file_exists($path) ? file_get_contents($path) : '',
        'path'    => $path,
    ]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    $name   = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['vhost'] ?? '');
    $path   = $name ? htaccess_path($name) : null;

    if (!$path) {
        http_response_code(404);
        echo json_encode(['error' => 'Could not resolve document root for this vhost']);
        exit;
    }

    if ($action === 'save') {
        $content = $body['content'] ?? '';

        $token = null;
        if (file_exists($path)) {
            if (!is_dir($backdir)) mkdir($backdir, 0700, true);
            $token = time() . '_' . $name;
            copy($path, "$backdir/$token.htaccess");
        }

        file_put_contents($path, $content);
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'restore') {
        $token  = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');
        $rname  = preg_replace('/^\d+_/', '', $token);
        $rpath  = htaccess_path($rname);
        $backup = "$backdir/$token.htaccess";

        if (!$rpath || !file_exists($backup)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            exit;
        }

        copy($backup, $rpath);
        unlink($backup);
        echo json_encode(['ok' => true, 'vhost' => $rname]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
