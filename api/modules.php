<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Apache doesn't expose module descriptions anywhere machine-readable — mods-available/*.load
// files only contain the LoadModule directive — so these are maintained by hand.
$MODULE_DESCRIPTIONS = [
    'access_compat'        => 'Old-style Order/Allow/Deny access control (kept for compatibility with older configs)',
    'actions'               => 'Run a CGI script when a file of a given type or method is requested',
    'alias'                 => 'Map URLs to a different filesystem path (Alias, ScriptAlias, Redirect)',
    'allowmethods'          => 'Restrict which HTTP methods (GET, POST, etc.) are allowed',
    'asis'                  => 'Serve files whose content already includes raw HTTP headers',
    'auth_basic'            => 'HTTP Basic authentication (username/password popup)',
    'auth_digest'           => 'HTTP Digest authentication (like Basic, but the password isn\'t sent in the clear)',
    'auth_form'             => 'Authenticate visitors using an HTML login form instead of a browser popup',
    'authn_anon'            => 'Allow anonymous logins as a valid "user" for auth-protected areas',
    'authn_core'            => 'Core authentication framework other authn_* modules build on',
    'authn_dbd'             => 'Look up login credentials in an SQL database',
    'authn_dbm'             => 'Look up login credentials in a DBM key/value file',
    'authn_file'            => 'Look up login credentials in a plain htpasswd file',
    'authn_socache'         => 'Cache authentication lookups in shared memory for speed',
    'authnz_fcgi'           => 'Authenticate/authorize using an external FastCGI application',
    'authnz_ldap'           => 'Authenticate/authorize against an LDAP directory',
    'authz_core'            => 'Core authorization framework (Require directives) other authz_* modules build on',
    'authz_dbd'             => 'Authorize access based on a database lookup',
    'authz_dbm'             => 'Authorize access based on a DBM file lookup',
    'authz_groupfile'       => 'Authorize access based on group membership in a text file',
    'authz_host'            => 'Authorize access by client IP address or hostname',
    'authz_owner'           => 'Authorize access based on file ownership matching the authenticated user',
    'authz_user'            => 'Authorize access to specific logged-in usernames',
    'autoindex'             => 'Generate a directory listing page when no index file is present',
    'brotli'                => 'Compress responses with Brotli before sending (like gzip, usually smaller)',
    'buffer'                => 'Buffer input/output to smooth out slow clients or backends',
    'cache'                 => 'Core HTTP caching framework other cache_* modules build on',
    'cache_disk'            => 'Cache responses to disk',
    'cache_socache'         => 'Cache responses in shared memory instead of disk',
    'cern_meta'             => 'Attach extra HTTP headers to files via separate CERN-style meta files',
    'cgi'                   => 'Run CGI scripts (via mod_prefork/mod_worker MPMs)',
    'cgid'                  => 'Run CGI scripts via a persistent daemon (used with the event/worker MPMs)',
    'charset_lite'          => 'Convert file content between character encodings on the fly',
    'data'                  => 'Turn arbitrary files into a text/plain "data:" style response',
    'dav'                   => 'WebDAV support — lets clients edit/upload files over HTTP',
    'dav_fs'                => 'Filesystem storage backend for WebDAV',
    'dav_lock'              => 'Generic locking backend used by WebDAV',
    'dbd'                   => 'Shared SQL database connection pooling for other modules',
    'deflate'               => 'Compress responses with gzip before sending, to save bandwidth',
    'dialup'                => 'Slow down responses to simulate a dial-up connection (testing only)',
    'dir'                   => 'Serve index.html/index.php automatically for directory requests',
    'dump_io'               => 'Log all request/response bytes for debugging (very verbose)',
    'echo'                  => 'Simple TCP echo service for testing',
    'env'                   => 'Set environment variables based on request headers for scripts to read',
    'expires'               => 'Add Expires/Cache-Control headers so browsers cache files longer',
    'ext_filter'            => 'Pipe response content through an external program before sending',
    'file_cache'            => 'Keep frequently-served files open/cached to speed up repeated requests',
    'filter'                => 'Framework for chaining content filters (compression, includes, etc.)',
    'headers'               => 'Add, remove, or edit arbitrary HTTP request/response headers',
    'heartbeat'             => 'Send periodic heartbeat data used by lbmethod_heartbeat load balancing',
    'heartmonitor'          => 'Collect heartbeat data from backends for lbmethod_heartbeat load balancing',
    'http2'                 => 'Serve sites over HTTP/2 instead of HTTP/1.1',
    'ident'                 => 'Look up the RFC 1413 identity of the connecting client (rarely used today)',
    'imagemap'              => 'Server-side clickable image maps',
    'include'               => 'Server-side includes — embed dynamic content in static HTML (.shtml)',
    'info'                  => 'Expose a page showing the full Apache config as currently loaded',
    'lbmethod_bybusyness'   => 'Load-balancing strategy: send traffic to the least-busy backend',
    'lbmethod_byrequests'   => 'Load-balancing strategy: round-robin by request count',
    'lbmethod_bytraffic'    => 'Load-balancing strategy: balance by bytes transferred',
    'lbmethod_heartbeat'    => 'Load-balancing strategy: weight backends by their heartbeat health',
    'ldap'                  => 'Shared LDAP connection handling used by mod_authnz_ldap',
    'log_debug'             => 'Log fine-grained debug-level trace information',
    'log_forensic'          => 'Log every request in full detail before it\'s processed (for forensic replay)',
    'lua'                   => 'Write request-handling logic in embedded Lua scripts',
    'macro'                 => 'Define reusable blocks of config to reduce duplication in .conf files',
    'md'                    => 'Automatic TLS certificate management (e.g. via Let\'s Encrypt)',
    'mime'                  => 'Determine a file\'s Content-Type from its extension',
    'mime_magic'            => 'Guess a file\'s Content-Type by inspecting its contents, not just its extension',
    'mpm_event'             => 'Multi-Processing Module: event-driven, handles many idle keep-alive connections efficiently',
    'mpm_prefork'           => 'Multi-Processing Module: one process per connection (needed for non-thread-safe code like classic mod_php)',
    'mpm_worker'            => 'Multi-Processing Module: threaded, a middle ground between prefork and event',
    'negotiation'           => 'Content negotiation — serve different file variants by language/type automatically',
    'proxy'                 => 'Core reverse/forward proxy framework other proxy_* modules build on',
    'proxy_ajp'             => 'Proxy requests to a backend over the AJP protocol (e.g. Tomcat)',
    'proxy_balancer'        => 'Load-balance proxied requests across multiple backend servers',
    'proxy_connect'         => 'Support the CONNECT method for proxying (e.g. HTTPS tunneling)',
    'proxy_express'         => 'Simple dynamic reverse-proxy mappings without editing config files',
    'proxy_fcgi'            => 'Proxy requests to a FastCGI backend (e.g. PHP-FPM)',
    'proxy_fdpass'          => 'Pass the raw connection file descriptor to a backend process',
    'proxy_ftp'             => 'Proxy FTP requests',
    'proxy_hcheck'          => 'Active health-checking of proxy backends',
    'proxy_html'            => 'Rewrite links inside proxied HTML/XML responses',
    'proxy_http'            => 'Proxy requests to a backend over plain HTTP',
    'proxy_http2'           => 'Proxy requests to a backend over HTTP/2',
    'proxy_scgi'            => 'Proxy requests to a backend over the SCGI protocol',
    'proxy_uwsgi'           => 'Proxy requests to a backend over the uWSGI protocol',
    'proxy_wstunnel'        => 'Proxy WebSocket connections through to a backend',
    'ratelimit'             => 'Throttle response bandwidth per request',
    'reflector'             => 'Echo request details back in the response (debugging)',
    'remoteip'              => 'Trust an X-Forwarded-For style header to determine the real client IP behind a proxy',
    'reqtimeout'            => 'Drop slow/stalled clients that take too long to send a full request',
    'request'               => 'Inspect and manipulate the request body (e.g. size limits)',
    'rewrite'               => 'Rewrite incoming URLs internally based on pattern-matching rules',
    'sed'                   => 'Run response content through sed-style find/replace before sending',
    'session'               => 'Track visitor session state across requests',
    'session_cookie'        => 'Store session data in a cookie',
    'session_crypto'        => 'Encrypt session data before storing it',
    'session_dbd'           => 'Store session data in an SQL database',
    'setenvif'              => 'Set environment variables based on matching request headers',
    'slotmem_plain'         => 'Shared-memory allocator backend used by other modules',
    'slotmem_shm'           => 'Shared-memory allocator backend (System V shared memory) used by other modules',
    'socache_dbm'           => 'Shared object cache backend using a DBM file',
    'socache_memcache'      => 'Shared object cache backend using Memcached',
    'socache_redis'         => 'Shared object cache backend using Redis',
    'socache_shmcb'         => 'Shared object cache backend using shared memory (default for SSL session caching)',
    'speling'               => 'Automatically correct minor typos/case mistakes in requested URLs',
    'ssl'                   => 'HTTPS support — TLS/SSL encryption for connections',
    'status'                => 'Expose a live server-status page showing current activity/load',
    'substitute'            => 'Find-and-replace text in response bodies (e.g. rewriting URLs in output)',
    'suexec'                => 'Run CGI scripts as a specific user/group instead of the web server\'s own user',
    'unique_id'             => 'Generate a unique identifier for every request, for correlating logs',
    'userdir'               => 'Serve personal sites from each user\'s home directory (e.g. /~username/)',
    'usertrack'             => 'Track visitors across requests with a cookie (mod_usertrack "clickstream" logging)',
    'vhost_alias'           => 'Massively/dynamically map many virtual hosts from one config using patterns',
    'xml2enc'               => 'Handle character-encoding conversion for XML content (used by proxy_html)',
];

function list_modules($descriptions) {
    $available   = glob('/etc/apache2/mods-available/*.load') ?: [];
    $enabled_raw = glob('/etc/apache2/mods-enabled/*.load') ?: [];
    $enabled     = [];
    foreach ($enabled_raw as $e) $enabled[] = basename(realpath($e) ?: $e);

    $mods = [];
    foreach ($available as $path) {
        $name = basename($path, '.load');
        // Distributions bundle version-specific PHP modules (php8.3, php8.4, ...) — match by prefix.
        $desc = $descriptions[$name] ?? (str_starts_with($name, 'php') ? 'Run PHP scripts via mod_php (embedded interpreter)' : null);
        $mods[] = [
            'name'        => $name,
            'enabled'     => in_array(basename($path), $enabled),
            'description' => $desc ?? 'No description available',
        ];
    }
    usort($mods, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $mods;
}

if ($method === 'GET') {
    echo json_encode(list_modules($MODULE_DESCRIPTIONS));
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
