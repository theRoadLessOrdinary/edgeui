<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

exec('apachectl -t 2>&1', $conf_out, $conf_rc);
if ($conf_rc !== 0) {
    echo json_encode(['ok' => false, 'output' => implode("\n", $conf_out)]);
    exit;
}

exec('apachectl restart 2>&1', $out, $rc);
echo json_encode(['ok' => $rc === 0, 'output' => implode("\n", $out)]);
