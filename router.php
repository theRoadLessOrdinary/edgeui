<?php
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (str_starts_with($uri, '/api/')) {
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

// Serve static vendor files
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

require __DIR__ . '/index.php';
