<?php
header('Content-Type: application/json');

exec('systemctl is-active apache2 2>&1', $out, $rc);
$running = trim($out[0] ?? '') === 'active';

exec('apachectl -t 2>&1', $conf_out, $conf_rc);
$config_ok = $conf_rc === 0;
$config_msg = implode("\n", $conf_out);

echo json_encode([
    'running'    => $running,
    'config_ok'  => $config_ok,
    'config_msg' => $config_msg,
]);
