<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$current = (string)($body['current_password'] ?? '');
$new     = (string)($body['new_password'] ?? '');

$hash = auth_get_hash();
if (!$hash || !password_verify($current, $hash)) {
    http_response_code(403);
    echo json_encode(['error' => 'Current password is incorrect.']);
    exit;
}

if (strlen($new) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 8 characters.']);
    exit;
}

auth_set_password($new);
echo json_encode(['ok' => true]);
