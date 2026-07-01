# EdgeUI

A small PHP dashboard for managing Apache virtual hosts on a single server — enable/disable sites,
add and remove them, and edit redirects, mod_rewrite rules, and error handling per site, all from
a browser instead of hand-editing `.conf` files.

Built for personal/internal use on one machine. It's intentionally minimal: no build step, no
framework, no database — a couple of PHP files and a bit of vanilla JS.

## Requirements

- PHP 8+ (uses `str_starts_with`, `match`, etc.)
- Apache with `a2ensite`/`a2dissite`/`apachectl` available
- Passwordless (or otherwise permitted) access to run those commands, since the service manages
  `/etc/apache2/sites-available` directly

## Running it

`router.php` is meant to be served with PHP's built-in server:

```
php -S 127.0.0.1:8765 router.php
```

`edgeui.service` is a systemd unit that runs this as a persistent service:

```
sudo cp edgeui.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now edgeui
```

Then open `http://127.0.0.1:8765` in a browser.

## What it does

- **Virtual Hosts** — list all configs in `/etc/apache2/sites-available`, grouped by domain across
  ports. Create new vhosts, toggle individual configs on/off, delete a single config or a whole
  group.
- **Redirects** — per-site `Redirect`/`RedirectMatch` directives (exact path or regex, with the
  usual 301/302/307/410 status codes).
- **Rewrites** — per-site `RewriteCond`/`RewriteRule` blocks, with a checkbox picker for common
  flags (`L`, `NC`, `QSA`, etc.) and a dropdown of common condition variables
  (`%{HTTP_HOST}`, `%{REQUEST_URI}`, ...).
- **Error Handling** — custom `ErrorDocument` pages per status code, plus toggles for PHP error
  display and error logging.
- **.htaccess** — view and edit a site's own `.htaccess` file (in its document root) directly.
- **Hosts File** — edit the local entries in `/etc/hosts`. Only the section above a
  `### end local ###` marker is touched; anything below that marker (e.g. a large ad-block list
  managed by another tool) is never even sent to the browser — just its line/byte count.
- **Modules** — list and toggle Apache modules (`a2enmod`/`a2dismod`), filterable by name.

Every destructive action (delete a redirect, remove a vhost, etc.) uses an inline
click-to-confirm control rather than a browser `confirm()` dialog, and every change is backed up
before it's applied — a global **Undo** button restores the last change and reloads Apache.

## Security notes

- No authentication. This only makes sense because the service is bound to `127.0.0.1` and used
  by a single person at the machine console — don't put this behind a reverse proxy or bind it to
  a non-localhost interface without adding auth first.
- The service runs as root (needed for `a2ensite`/`a2dissite`/`apachectl` and writing to
  `/etc/apache2/sites-available`), so treat it with the same care as shell access to the box.
- Path traversal and Apache-directive-injection protections are in place (`router.php`'s
  `/vendor/` and `/api/` handlers, and control-character stripping on any field written into a
  `.conf` file) — if you're extending this with new user-supplied fields that get written into
  Apache config, strip control characters (`[\x00-\x1F\x7F]`) from them first.

## Layout

```
index.php              single-file frontend (HTML/CSS/JS)
router.php             routes /api/* and /vendor/* requests, otherwise serves index.php
api/vhosts.php          list/create/delete/toggle/restore virtual hosts
api/redirects.php       Redirect / RedirectMatch directives
api/rewrites.php        RewriteCond / RewriteRule blocks
api/errors.php          ErrorDocument, PHP error display, error logging
api/htaccess.php        read/write a site's .htaccess (path resolved from its DocumentRoot)
api/hosts.php           read/write /etc/hosts, preserving everything below "### end local ###"
api/modules.php         list/toggle Apache modules (a2enmod/a2dismod)
api/status.php          Apache running/config-check status for the top nav indicator
vendor/                 small vanilla-JS dependencies (no build step)
edgeui.service          systemd unit
```
