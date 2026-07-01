<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function get_vhosts() {
    $available = glob('/etc/apache2/sites-available/*.conf') ?: [];
    $enabled_raw = glob('/etc/apache2/sites-enabled/*.conf') ?: [];
    $enabled = [];
    foreach ($enabled_raw as $e) $enabled[] = basename(realpath($e) ?: $e);

    $flat = [];
    foreach ($available as $path) {
        $name = basename($path, '.conf');
        $content = file_get_contents($path);

        $server_name = '';
        $doc_root = '';
        $port = '80';
        if (preg_match('/ServerName\s+(\S+)/i', $content, $m)) $server_name = $m[1];
        if (preg_match('/DocumentRoot\s+(\S+)/i', $content, $m)) $doc_root = $m[1];
        if (preg_match('/<VirtualHost\s+[^:]+:(\d+)/i', $content, $m)) $port = $m[1];

        $flat[] = [
            'name'        => $name,
            'server_name' => $server_name,
            'doc_root'    => $doc_root,
            'port'        => $port,
            'enabled'     => in_array(basename($path), $enabled),
            'path'        => $path,
        ];
    }

    // Extract SLD from a hostname (e.g. store.mysite.com → mysite)
    function sld($hostname) {
        if (!$hostname) return '';
        $parts = explode('.', $hostname);
        return count($parts) >= 2 ? $parts[count($parts) - 2] : $hostname;
    }

    // Group by SLD; entries with no server_name stay solo
    $groups = [];
    foreach ($flat as $v) {
        $key = $v['server_name'] ? sld($v['server_name']) : ('__solo__' . $v['name']);
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'sld'         => $key,
                'doc_root'    => $v['doc_root'],
                'configs'     => [],
            ];
        }
        $groups[$key]['configs'][] = [
            'name'        => $v['name'],
            'server_name' => $v['server_name'],
            'port'        => $v['port'],
            'enabled'     => $v['enabled'],
            'path'        => $v['path'],
        ];
    }

    // Sort configs within each group by port ascending
    foreach ($groups as &$g) {
        usort($g['configs'], fn($a, $b) => intval($a['port']) - intval($b['port']));
    }

    return array_values($groups);
}

if ($method === 'GET') {
    echo json_encode(get_vhosts());
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'toggle') {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['name'] ?? '');
        $cmd = $body['enable'] ? "a2ensite $name" : "a2dissite $name";
        exec($cmd . ' 2>&1', $out, $rc);
        exec('apachectl graceful 2>&1', $out2, $rc2);
        echo json_encode(['ok' => $rc === 0 && $rc2 === 0, 'output' => implode("\n", array_merge($out, $out2))]);
        exit;
    }

    if ($action === 'create') {
        $name      = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['name'] ?? '');
        $srv_name  = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['server_name'] ?? ''));
        $doc_root  = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $body['doc_root'] ?? ''));
        $port      = intval($body['port'] ?? 80);

        if (!$name || !$srv_name || !$doc_root) {
            http_response_code(400);
            echo json_encode(['error' => 'name, server_name and doc_root are required']);
            exit;
        }

        $conf = <<<CONF
<VirtualHost *:{$port}>
    ServerName {$srv_name}
    DocumentRoot {$doc_root}

    <Directory {$doc_root}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/{$name}-error.log
    CustomLog \${APACHE_LOG_DIR}/{$name}-access.log combined
</VirtualHost>
CONF;

        $path = "/etc/apache2/sites-available/{$name}.conf";
        if (file_exists($path)) {
            http_response_code(409);
            echo json_encode(['error' => 'Virtual host already exists']);
            exit;
        }

        file_put_contents($path, $conf);
        echo json_encode(['ok' => true, 'path' => $path]);
        exit;
    }

    if ($action === 'delete') {
        $name    = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['name'] ?? '');
        $path    = "/etc/apache2/sites-available/{$name}.conf";
        $backdir = '/etc/apache2/sites-backup';

        if (!is_dir($backdir)) mkdir($backdir, 0700, true);

        $token = null;
        if (file_exists($path)) {
            $token = time() . '_' . $name;
            copy($path, "$backdir/{$token}.conf");
        }

        exec("a2dissite $name 2>&1");
        exec('apachectl graceful 2>&1');
        if (file_exists($path)) unlink($path);

        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }

    if ($action === 'restore') {
        $token   = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');
        $backdir = '/etc/apache2/sites-backup';
        $backup  = "$backdir/{$token}.conf";

        if (!file_exists($backup)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            exit;
        }

        // token format: timestamp_name
        $name = preg_replace('/^\d+_/', '', $token);
        $dest = "/etc/apache2/sites-available/{$name}.conf";

        copy($backup, $dest);
        unlink($backup);
        exec("a2ensite $name 2>&1");
        exec('apachectl graceful 2>&1');
        echo json_encode(['ok' => true, 'name' => $name]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
