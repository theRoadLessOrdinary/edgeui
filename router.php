<?php
require_once __DIR__ . '/lib/auth.php';
auth_ensure_configured();

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Reachable without auth: the login/logout flow itself
if ($uri === '/login')  { require __DIR__ . '/login.php';  exit; }
if ($uri === '/logout') { require __DIR__ . '/logout.php'; exit; }

if (str_starts_with($uri, '/api/')) {
    auth_require_api();

    $endpoint = trim(substr($uri, 5), '/');
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $endpoint)) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    $file = __DIR__ . '/api/' . $endpoint . '.php';
    if (file_exists($file)) { require $file; exit; }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Serve static vendor files — no auth needed, generic JS/CSS libraries
if (str_starts_with($uri, '/vendor/')) {
    $vendorRoot = realpath(__DIR__ . '/vendor');
    $file       = realpath(__DIR__ . $uri);
    if ($file && $vendorRoot && str_starts_with($file, $vendorRoot . DIRECTORY_SEPARATOR) && !is_dir($file)) {
        $ext  = pathinfo($file, PATHINFO_EXTENSION);
        $mime = match($ext) {
            'js'  => 'application/javascript',
            'css' => 'text/css',
            default => 'application/octet-stream',
        };
        header("Content-Type: $mime");
        readfile($file);
        exit;
    }
    http_response_code(404); exit;
}

auth_require_web();
require __DIR__ . '/index.php';
