<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// rename() fails across filesystem boundaries (EXDEV) — real in this setup:
// site docroots live on a separate mounted drive from /etc/apache2/sites-backup.
// Try the cheap same-filesystem rename first, fall back to copy+remove.
function move_directory(string $src, string $dest): bool {
    if (@rename($src, $dest)) {
        return true;
    }
    $srcEsc  = escapeshellarg($src);
    $destEsc = escapeshellarg($dest);
    exec("cp -a {$srcEsc} {$destEsc} 2>&1", $cpOut, $cpRc);
    if ($cpRc !== 0) {
        return false;
    }
    exec("rm -rf {$srcEsc} 2>&1", $rmOut, $rmRc);
    return $rmRc === 0;
}

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

        // Seed a placeholder so a fresh vhost shows something immediately —
        // but never touch a docroot that already has any content in it.
        if (!is_dir($doc_root)) {
            @mkdir($doc_root, 0755, true);
        }
        if (is_dir($doc_root)) {
            $existing = array_diff(scandir($doc_root), ['.', '..']);
            if (empty($existing)) {
                file_put_contents(rtrim($doc_root, '/') . '/index.php', "<?php\nphpinfo();\n");
            }
        }

        // Enable immediately — creating a vhost should mean it's live, not
        // leave it sitting inert in sites-available until someone notices
        // and flips the toggle by hand.
        exec("a2ensite {$name} 2>&1", $enOut, $enRc);
        exec('apachectl graceful 2>&1', $enOut2, $enRc2);

        echo json_encode([
            'ok'      => true,
            'path'    => $path,
            'enabled' => $enRc === 0 && $enRc2 === 0,
        ]);
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

    if ($action === 'delete_docroot') {
        // Opt-in, separate from config deletion — moves (never rm -rf's) a
        // vhost's document root into quarantine alongside the same token
        // used for its conf backup, so one Undo click restores both.
        $doc_root = rtrim($body['doc_root'] ?? '', '/');
        $token    = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['token'] ?? '');

        if (!$doc_root || !$token) {
            http_response_code(400);
            echo json_encode(['error' => 'doc_root and token are required']);
            exit;
        }

        $real = realpath($doc_root);
        if (!$real || !is_dir($real)) {
            echo json_encode(['ok' => true, 'skipped' => 'not_found']);
            exit;
        }

        // Refuse anything that isn't a real, specific site folder — a typo
        // or a docroot pointed at a shared parent should never be deletable.
        $denylist = ['/', '/var', '/var/www', '/home', '/etc', '/usr', '/root',
                     '/bin', '/sbin', '/lib', '/lib64', '/opt', '/srv', '/tmp',
                     '/media', '/mnt', '/proc', '/sys', '/boot', '/dev'];
        if (in_array($real, $denylist, true) || strlen($real) < 5) {
            http_response_code(403);
            echo json_encode(['error' => 'Refusing to delete a shared/system directory']);
            exit;
        }

        // Refuse if any OTHER surviving vhost still points at this same docroot
        foreach ((glob('/etc/apache2/sites-available/*.conf') ?: []) as $confPath) {
            $c = file_get_contents($confPath);
            if (preg_match('/DocumentRoot\s+(\S+)/i', $c, $m) && rtrim($m[1], '/') === $real) {
                http_response_code(409);
                echo json_encode(['error' => 'Another virtual host still uses this document root']);
                exit;
            }
        }

        $quarantineDir = '/etc/apache2/sites-backup/docroots';
        if (!is_dir($quarantineDir)) mkdir($quarantineDir, 0700, true);

        $dest = "$quarantineDir/$token";
        if (!move_directory($real, $dest)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move document root']);
            exit;
        }
        file_put_contents("$quarantineDir/{$token}.path", $real);

        echo json_encode(['ok' => true, 'moved_to' => $dest]);
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

        // Bring a quarantined docroot back too, if this token has one
        $docQuarantineDir = '/etc/apache2/sites-backup/docroots';
        $docBackup        = "$docQuarantineDir/$token";
        $pathFile         = "$docQuarantineDir/{$token}.path";
        if (is_dir($docBackup) && file_exists($pathFile)) {
            $originalPath = trim(file_get_contents($pathFile));
            if ($originalPath && !file_exists($originalPath) && move_directory($docBackup, $originalPath)) {
                unlink($pathFile);
            }
        }

        echo json_encode(['ok' => true, 'name' => $name]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
