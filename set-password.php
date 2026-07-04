<?php
/**
 * Run from the command line to set (or change) EdgeUI's login password:
 *   php set-password.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

require_once __DIR__ . '/lib/auth.php';

echo "Set EdgeUI password.\n";
echo "Password: ";
shell_exec('stty -echo');
$password = trim(fgets(STDIN));
shell_exec('stty echo');
echo "\n";

if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.\n");
}

echo "Confirm password: ";
shell_exec('stty -echo');
$confirm = trim(fgets(STDIN));
shell_exec('stty echo');
echo "\n";

if ($password !== $confirm) {
    exit("Passwords did not match. Nothing was changed.\n");
}

auth_set_password($password);
echo "Password set. (" . AUTH_CONFIG_PATH . ")\n";
