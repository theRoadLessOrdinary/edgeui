<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EdgeUI — Apache Manager</title>
<link rel="stylesheet" href="/vendor/SimpleNotification/simpleNotification.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --dark:      #05080f;
  --dark-2:    #101a2c;
  --dark-3:    #1e2c48;
  --dark-4:    #2a3d5c;
  --blue:      #2979ff;
  --blue-dim:  #1a4fa0;
  --gold:      #ffc107;
  --gold-dim:  #b38600;
  --green:     #81c784;
  --red:       #ef5350;
  --text:      #f2f5fb;
  --text-dim:  #ccd7ec;
  --text-mute: #9fb2d4;
  --radius:    10px;
  --font:      -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
}
html { scroll-behavior: smooth; }
body {
  font-family: var(--font);
  background: var(--dark);
  color: var(--text);
  line-height: 1.6;
  min-height: 100vh;
}
a { color: inherit; text-decoration: none; }

/* ── Nav ── */
nav {
  position: sticky; top: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 5%;
  height: 64px;
  background: rgba(5,8,15,.95);
  border-bottom: 1px solid rgba(255,255,255,.13);
  backdrop-filter: blur(12px);
}
.nav-brand {
  display: flex; align-items: center; gap: 10px;
  font-size: 1.1rem; font-weight: 700; letter-spacing: -.3px;
}
.nav-brand span { color: var(--blue); }
.nav-right {
  display: flex; align-items: center; gap: 1.25rem;
}
.nav-status {
  display: flex; align-items: center; gap: 8px;
  font-size: .8rem; color: var(--text-mute);
}
.btn-undo {
  background: transparent;
  border: 1px solid rgba(255,255,255,.16);
  color: var(--text-mute);
  font-family: var(--font);
  font-size: .8rem; font-weight: 600;
  padding: 5px 13px; border-radius: 6px;
  cursor: pointer; transition: all .2s;
  opacity: .35; pointer-events: none;
}
.btn-undo.active {
  opacity: 1; pointer-events: all;
  border-color: var(--gold); color: var(--gold);
}
.btn-undo.active:hover { background: rgba(255,193,7,.1); }
.status-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--text-mute);
  transition: background .3s;
}
.status-dot.ok  { background: var(--green); }
.status-dot.err { background: var(--red); }

/* ── Layout ── */
.shell {
  display: grid;
  grid-template-columns: 220px 1fr;
  min-height: calc(100vh - 64px);
}

/* ── Sidebar ── */
aside {
  background: var(--dark-2);
  border-right: 1px solid rgba(255,255,255,.11);
  padding: 2rem 0;
  display: flex; flex-direction: column;
}
.sidebar-section {
  padding: 0 1.25rem;
}
.sidebar-label {
  font-size: .7rem; font-weight: 700; letter-spacing: .1em;
  color: var(--text-mute); text-transform: uppercase;
  margin-bottom: .75rem;
}
.sidebar-nav { display: flex; flex-direction: column; gap: 2px; }
.sidebar-nav button {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: .5rem .75rem;
  border-radius: 6px; border: none;
  background: none;
  font-family: var(--font); font-size: .9rem; color: var(--text-dim);
  cursor: pointer; transition: all .15s;
  text-align: left;
}
.sidebar-nav button:hover { background: var(--dark-3); color: var(--text); }
.sidebar-nav button.active {
  background: rgba(41,121,255,.15);
  color: var(--blue);
}
.sidebar-nav button svg { flex-shrink: 0; opacity: .7; }
.sidebar-nav button.active svg { opacity: 1; }

/* ── Main ── */
main {
  padding: 2.5rem 3rem;
  overflow-y: auto;
}
.page { display: none; }
.page.active { display: block; }

.page-header {
  margin-bottom: 2rem;
}
.page-header h1 {
  font-size: 1.5rem; font-weight: 700;
  margin-bottom: .35rem;
}
.page-header p { font-size: .9rem; color: var(--text-dim); }

/* ── Cards ── */
.card {
  background: var(--dark-2);
  border: 1px solid rgba(255,255,255,.11);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem;
}
.card-header h2 { font-size: 1rem; font-weight: 600; }

/* ── Vhost list ── */
.vhost-list { display: flex; flex-direction: column; gap: .75rem; }
.vhost-item {
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.10);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  transition: border-color .15s;
}
.vhost-item:hover { border-color: rgba(255,255,255,.16); }
.vhost-top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .6rem;
}
.vhost-info { display: flex; flex-direction: column; gap: 3px; }
.vhost-name { font-size: .95rem; font-weight: 600; cursor: pointer; }
.vhost-name:hover { color: var(--blue); }
.vhost-meta {
  font-size: .78rem; color: var(--text-mute);
  display: flex; gap: 1rem;
}
.vhost-actions { display: flex; align-items: center; gap: .75rem; }
.vhost-configs {
  display: flex; flex-wrap: wrap; gap: .5rem;
  padding-top: .6rem;
  border-top: 1px solid rgba(255,255,255,.10);
}
.config-pill {
  display: flex; align-items: center; gap: .5rem;
  background: var(--dark-4);
  border: 1px solid rgba(255,255,255,.13);
  border-radius: 20px;
  padding: 3px 10px 3px 8px;
  font-size: .78rem;
}
.port-tag {
  font-size: .72rem; font-weight: 700;
  color: var(--text-mute); letter-spacing: .03em;
}
.config-pill.enabled .port-tag { color: var(--blue); }
.config-pill.deleting      { outline: 1px solid var(--red); }
.config-pill.deleting-fade { opacity: 0; transition: opacity .3s ease; }
.pill-host { color: var(--text-mute); font-size: .72rem; }
.badge {
  font-size: .7rem; font-weight: 700; letter-spacing: .05em;
  padding: 2px 8px; border-radius: 20px; text-transform: uppercase;
}
.badge-enabled  { background: rgba(129,199,132,.15); color: var(--green); }
.badge-disabled { background: rgba(160,176,204,.1);  color: var(--text-mute); }

/* ── delete-in-place sizing (custom element has no default font-size) ── */
delete-in-place { font-size: .78rem; line-height: 1; flex-shrink: 0; }
delete-in-place .dip-confirm { font-weight: 600; }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; border-radius: 6px;
  font-size: .82rem; font-weight: 600;
  border: none; cursor: pointer; transition: all .2s;
  font-family: var(--font);
}
.btn-blue  { background: var(--blue); color: #fff; }
.btn-blue:hover { background: #3d8bff; transform: translateY(-1px); }
.btn-gold  { background: var(--gold); color: #1a1000; }
.btn-gold:hover { background: #ffd54f; transform: translateY(-1px); }
.btn-ghost {
  background: transparent; color: var(--text-dim);
  border: 1px solid rgba(255,255,255,.16);
}
.btn-ghost:hover { border-color: rgba(255,255,255,.25); color: var(--text); }
.btn-sm { padding: 5px 11px; font-size: .78rem; }

/* ── Form ── */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.form-group { display: flex; flex-direction: column; gap: .4rem; }
.form-group.full { grid-column: 1 / -1; }
label { font-size: .82rem; font-weight: 600; color: var(--text-dim); }
input[type=text], input[type=number] {
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 6px;
  color: var(--text);
  font-family: var(--font);
  font-size: .88rem;
  padding: .45rem .65rem;
  transition: border-color .15s;
  width: 100%;
}
input:focus {
  outline: none;
  border-color: var(--blue);
}
input::placeholder { color: var(--text-mute); }
.code-textarea {
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 6px;
  color: var(--text);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: .82rem;
  line-height: 1.5;
  padding: .75rem .9rem;
  width: 100%;
  min-height: 320px;
  resize: vertical;
  tab-size: 4;
  transition: border-color .15s;
}
.code-textarea:focus { outline: none; border-color: var(--blue); }
.code-textarea::placeholder { color: var(--text-mute); }
select {
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 6px;
  color: var(--text);
  font-family: var(--font);
  font-size: .8rem;
  padding: .55rem .75rem;
  width: 100%;
  transition: border-color .15s;
  cursor: pointer;
}
select:focus { outline: none; border-color: var(--blue); }

/* ── Contextual help ── */
.help-intro {
  font-size: .82rem; color: var(--text-dim); line-height: 1.5;
  background: rgba(41,121,255,.08);
  border: 1px solid rgba(41,121,255,.15);
  border-radius: 8px;
  padding: .75rem 1rem;
  margin: 1rem 0 1.25rem;
}
.help-tip {
  display: inline-flex; align-items: center; justify-content: center;
  width: 15px; height: 15px; border-radius: 50%;
  background: rgba(160,176,204,.15); color: var(--text-mute);
  font-size: .68rem; font-weight: 700; line-height: 1;
  cursor: help; flex-shrink: 0; margin-left: 5px;
  position: relative; vertical-align: middle;
}
.help-tip:hover, .help-tip:focus {
  background: var(--blue); color: #fff; outline: none;
}
.help-tip::after {
  content: attr(data-tip);
  position: absolute; bottom: calc(100% + 7px);
  left: 50%; transform: translateX(calc(-50% + var(--tip-shift, 0px)));
  background: var(--dark-4); color: var(--text);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 6px; padding: .55rem .7rem;
  font-size: .78rem; font-weight: 400; line-height: 1.4;
  white-space: normal; width: max-content; max-width: 230px;
  text-align: left;
  opacity: 0; pointer-events: none;
  transition: opacity .12s ease;
  z-index: 30;
}
.help-tip:hover::after, .help-tip:focus::after { opacity: 1; }

/* ── Checkbox dropdown (multi-select) ── */
.ms-dropdown { position: relative; }
.ms-dropdown-btn {
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 6px;
  color: var(--text);
  font-family: var(--font);
  font-size: .8rem;
  padding: .55rem .75rem;
  width: 100%;
  text-align: left;
  cursor: pointer;
  display: flex; align-items: center; justify-content: space-between; gap: .5rem;
  transition: border-color .15s;
}
.ms-dropdown-btn:hover, .ms-dropdown.open .ms-dropdown-btn { border-color: var(--blue); }
.ms-dropdown-btn .ms-caret { color: var(--text-mute); flex-shrink: 0; font-size: .7rem; }
.ms-dropdown-btn .ms-btn-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-mute); }
.ms-dropdown-btn .ms-btn-label.has-selection { color: var(--text); }
.ms-dropdown-panel {
  display: none;
  position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 20;
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 8px;
  padding: .4rem;
  box-shadow: 0 8px 24px rgba(0,0,0,.4);
  max-height: 260px;
  overflow-y: auto;
}
.ms-dropdown.open .ms-dropdown-panel { display: block; }
.ms-option {
  display: flex; align-items: center; gap: .5rem;
  padding: .4rem .5rem;
  border-radius: 5px;
  font-size: .78rem;
  font-weight: 400;
  color: var(--text);
  cursor: pointer;
}
.ms-option:hover { background: var(--dark-4); }
.ms-option input[type=checkbox] { flex-shrink: 0; accent-color: var(--blue); }
.ms-hint { color: var(--text-mute); font-size: .72rem; margin-left: auto; padding-left: .5rem; }
.ms-option-redirect { display: flex; align-items: center; gap: .5rem; padding: .2rem .5rem; }
.ms-option-redirect label { display: flex; align-items: center; gap: .5rem; font-size: .78rem; font-weight: 400; color: var(--text); cursor: pointer; flex: 1; }
.ms-option-redirect select { width: auto; padding: .3rem .5rem; font-size: .74rem; }
.ms-option-redirect select:disabled { opacity: .4; cursor: not-allowed; }

/* ── Redirect rows ── */
.redirect-item {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
  flex-wrap: wrap;
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.10);
  border-radius: 8px;
  padding: .9rem 1.25rem;
  transition: border-color .15s;
}
.redirect-item:hover { border-color: rgba(255,255,255,.16); }
.redirect-item.deleting      { outline: 1px solid var(--red); }
.redirect-item.deleting-fade { opacity: 0; transition: opacity .3s ease; }
.redirect-info { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 0; }
.redirect-label { font-size: .74rem; color: var(--text-mute); }
.redirect-rule {
  display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
  font-size: .8rem;
}
.redirect-rule code {
  background: var(--dark-4); border-radius: 4px;
  padding: 1px 7px; font-size: .76rem; color: var(--text-dim);
  word-break: break-all;
}
.redirect-arrow { color: var(--text-mute); flex-shrink: 0; }
.tag {
  font-size: .68rem; font-weight: 700; letter-spacing: .05em;
  padding: 2px 7px; border-radius: 4px; text-transform: uppercase; flex-shrink: 0;
}
.tag-301 { background: rgba(41,121,255,.15); color: var(--blue); }
.tag-302 { background: rgba(255,193,7,.12);  color: var(--gold); }
.tag-307 { background: rgba(255,193,7,.12);  color: var(--gold); }
.tag-410 { background: rgba(239,83,80,.12);  color: var(--red);  }
.tag-match { background: rgba(160,176,204,.1); color: var(--text-mute); }

/* ── Toggle switch ── */
.toggle {
  position: relative; width: 36px; height: 20px; cursor: pointer;
}
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-track {
  position: absolute; inset: 0;
  background: var(--dark-4); border-radius: 20px;
  transition: background .2s;
}
.toggle input:checked + .toggle-track { background: var(--blue); }
.toggle-thumb {
  position: absolute;
  top: 3px; left: 3px;
  width: 14px; height: 14px;
  background: #fff; border-radius: 50%;
  transition: transform .2s;
  pointer-events: none;
}
.toggle input:checked ~ .toggle-thumb { transform: translateX(16px); }

/* ── Notice ── */
.notice {
  padding: .75rem 1rem; border-radius: 6px;
  font-size: .85rem; margin-bottom: 1rem;
  display: none;
}
.notice.show { display: block; }
.notice-ok  { background: rgba(129,199,132,.1); color: var(--green); border: 1px solid rgba(129,199,132,.2); }
.notice-err { background: rgba(239,83,80,.1);   color: var(--red);   border: 1px solid rgba(239,83,80,.2); }

/* ── Toggle rows (error handling page) ── */
.toggle-row {
  display: flex; align-items: center; justify-content: space-between; gap: 2rem;
  padding: .25rem 0;
}
.toggle-row-info { display: flex; flex-direction: column; gap: 3px; }
.toggle-row-label { font-size: .92rem; font-weight: 600; }
.toggle-row-sub { font-size: .8rem; color: var(--text-mute); }

/* ── Inline error ── */
.inline-error {
  width: 100%;
  font-size: .78rem; color: var(--red);
  padding-top: .5rem;
  max-height: 0; overflow: hidden;
  transition: max-height .2s ease;
}
.inline-error.show { max-height: 4rem; }

/* ── Delete animation ── */
.vhost-item.deleting      { outline: 1px solid var(--red); }
.vhost-item.deleting-fade { opacity: 0; transition: opacity .3s ease; }


/* ── Empty state ── */
.empty {
  text-align: center; padding: 3rem 1rem;
  color: var(--text-mute); font-size: .9rem;
}

/* ── Divider ── */
.divider {
  border: none; border-top: 1px solid rgba(255,255,255,.11);
  margin: 1.5rem 0;
}

/* ── Drawer ── */
me-drawer {
  position: fixed; top: 0; right: 0; bottom: 0; width: 680px; max-width: 92vw;
  background: var(--dark-2);
  box-shadow: -4px 0 32px rgba(0,0,0,.5);
  z-index: 500; display: flex; flex-direction: column;
  transform: translateX(100%); transition: transform .3s ease;
}
me-drawer.open { transform: translateX(0); }
drawer-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255,255,255,.13);
  background: var(--dark-3); flex-shrink: 0;
}
drawer-head h2 { font-size: 1rem; font-weight: 700; }
.drawer-close {
  background: none; border: none; font-size: 1.5rem;
  cursor: pointer; color: var(--text-mute); line-height: 1; padding: 0 .25rem;
}
.drawer-close:hover { color: var(--text); }
drawer-content { flex: 1; overflow-y: auto; padding: 1.25rem; display: block; }
drawer-foot {
  display: flex; align-items: center; justify-content: flex-end; gap: .65rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255,255,255,.13);
  background: var(--dark-3); flex-shrink: 0;
}
#drawer-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.45); z-index: 499;
}
#drawer-overlay.show { display: block; }

/* ── Drawer tabs ── */
.drawer-tabs {
  display: flex; gap: 0;
  border-bottom: 1px solid rgba(255,255,255,.13);
  margin: 0 -1.25rem 0;
  padding: 0 1.25rem;
}
.drawer-tab {
  background: none; border: none; border-bottom: 2px solid transparent;
  color: var(--text-mute); font-family: var(--font); font-size: .85rem; font-weight: 600;
  padding: .65rem 1rem; cursor: pointer; transition: all .15s; margin-bottom: -1px;
}
.drawer-tab:hover { color: var(--text-dim); }
.drawer-tab.active { color: var(--blue); border-bottom-color: var(--blue); }
.drawer-panel { display: none; }
.drawer-panel.active { display: block; }

/* ── Site picker button ── */
.site-picker-btn {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%;
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 8px;
  color: var(--text-dim);
  font-family: var(--font); font-size: .9rem;
  padding: .7rem 1rem;
  cursor: pointer; transition: border-color .15s;
  margin-bottom: 1.5rem;
}
.site-picker-btn:hover { border-color: rgba(255,255,255,.25); }
.site-picker-btn.selected { color: var(--text); border-color: var(--blue); }
.site-picker-btn svg { opacity: .5; flex-shrink: 0; }

/* ── Site list in drawer ── */
.site-list { display: flex; flex-direction: column; gap: .4rem; }
.site-list-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: .65rem .9rem;
  border-radius: 6px;
  cursor: pointer; transition: background .15s;
  border: 1px solid transparent;
}
.site-list-item:hover { background: var(--dark-3); }
.site-list-item.active {
  background: rgba(41,121,255,.12);
  border-color: rgba(41,121,255,.25);
}
.site-list-name { font-size: .9rem; font-weight: 500; }
.site-list-meta { font-size: .75rem; color: var(--text-mute); }
.site-list-port {
  font-size: .72rem; font-weight: 700; color: var(--text-mute);
  background: var(--dark-4); border-radius: 4px; padding: 2px 6px;
}
</style>
</head>
<body>

<nav>
  <div class="nav-brand">Edge<span>UI</span></div>
  <div class="nav-right">
    <button class="btn-undo" id="btn-undo" onclick="undoDelete()">Undo Last Action</button>
    <div class="nav-status">
      <div class="status-dot" id="status-dot"></div>
      <span id="status-text">Checking…</span>
    </div>
  </div>
</nav>

<div class="shell">

  <aside>
    <div class="sidebar-section">
      <div class="sidebar-label">Apache</div>
      <div class="sidebar-nav">
        <button class="active" data-page="vhosts">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
          Sites
        </button>
        <button data-page="modules">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          Modules
        </button>
      </div>
    </div>
    <div class="sidebar-section" style="margin-top:1.5rem">
      <div class="sidebar-label">System</div>
      <div class="sidebar-nav">
        <button data-page="hosts">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>
          Hosts File
        </button>
      </div>
    </div>
  </aside>

  <main>

    <!-- Virtual Hosts -->
    <div class="page active" id="page-vhosts">
      <div class="page-header">
        <h1>Virtual Hosts</h1>
        <p>Manage Apache virtual host configurations</p>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Add Virtual Host</h2>
        </div>
        <div class="help-intro">
          A <strong>virtual host</strong> tells Apache which website to serve for a given domain and port —
          it's just a config file pointing a domain name at a folder of files on disk.
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Config name<span class="help-tip" tabindex="0" data-tip="Just the filename Apache uses internally (becomes name.conf). Visitors never see this — it doesn't need to match your domain.">?</span></label>
            <input type="text" id="vh-name" placeholder="mysite">
          </div>
          <div class="form-group">
            <label>Port<span class="help-tip" tabindex="0" data-tip="The network port Apache listens on for this site. 80 is standard HTTP, 443 is HTTPS. Multiple sites can share a port as long as their Server names differ.">?</span></label>
            <input type="number" id="vh-port" value="80">
          </div>
          <div class="form-group">
            <label>Server name<span class="help-tip" tabindex="0" data-tip="The domain visitors type in their browser, e.g. mysite.com. Apache uses this to decide which site to serve when several share the same port.">?</span></label>
            <input type="text" id="vh-servername" placeholder="mysite.local">
          </div>
          <div class="form-group">
            <label>Document root<span class="help-tip" tabindex="0" data-tip="The folder on disk holding this site's files — where its index.html or index.php lives.">?</span></label>
            <input type="text" id="vh-docroot" placeholder="/var/www/mysite">
          </div>
        </div>
        <button class="btn btn-blue" onclick="createVhost()">Create Virtual Host</button>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Configured Sites</h2>
          <button class="btn btn-ghost btn-sm" onclick="loadVhosts()">Refresh</button>
        </div>
        <div id="vhost-list" class="vhost-list">
          <div class="empty">Loading…</div>
        </div>
      </div>
    </div>


    <!-- Modules -->
    <div class="page" id="page-modules">
      <div class="page-header">
        <h1>Modules</h1>
        <p>Enable or disable Apache modules</p>
      </div>

      <div class="card">
        <div class="help-intro">
          Modules add optional features to Apache — rewriting, compression, SSL, and so on. Most sites
          only need a handful of these enabled. Disabling one that another module or site config
          depends on will fail safely: Apache rejects the change and an error shows on that row instead
          of anything breaking.
        </div>
        <div class="form-group" style="margin-bottom:1.25rem">
          <label>Filter</label>
          <input type="text" id="mod-filter" placeholder="Search modules…" oninput="renderModules()">
        </div>
        <div id="module-list" class="vhost-list">
          <div class="empty">Loading…</div>
        </div>
      </div>
    </div>

    <!-- Hosts File -->
    <div class="page" id="page-hosts">
      <div class="page-header">
        <h1>Hosts File</h1>
        <p>Edit the local entries in <code>/etc/hosts</code></p>
      </div>

      <div class="card">
        <div class="help-intro">
          <code>/etc/hosts</code> maps hostnames to IP addresses before DNS is even checked — it's how
          <code>edgecart.local</code>-style names resolve on this machine. Only the section above the
          <code>### end local ###</code> marker is editable here; anything below it is left untouched
          (e.g. entries managed by other tools), so it won't get overwritten when you save.
        </div>
        <label>Local entries</label>
        <textarea id="hosts-local" class="code-textarea" spellcheck="false" style="min-height:220px"></textarea>
        <div id="hosts-after-wrap" class="redirect-label" style="display:none;margin-top:.75rem"></div>
        <button class="btn btn-blue btn-sm" onclick="saveHostsFile()" style="margin-top:1.25rem">Save Hosts File</button>
      </div>
    </div>

  </main>
</div>

<div id="drawer-overlay"></div>

<me-drawer id="site-drawer">
  <drawer-head>
    <h2 id="site-drawer-title">Site</h2>
    <button class="drawer-close" onclick="closeSiteDrawer()">&times;</button>
  </drawer-head>
  <drawer-content>
    <div class="drawer-tabs">
      <button class="drawer-tab active" data-tab="redirects">Redirects</button>
      <button class="drawer-tab" data-tab="rewrites">Rewrites</button>
      <button class="drawer-tab" data-tab="errors">Error Handling</button>
      <button class="drawer-tab" data-tab="htaccess">.htaccess</button>
    </div>

    <!-- Redirects tab -->
    <div class="drawer-panel active" id="dp-redirects">
      <div class="help-intro">
        A <strong>redirect</strong> sends visitors from one URL to another — the browser's address bar changes.
        Use this when a page has moved, been renamed, or removed.
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Type<span class="help-tip" tabindex="0" data-tip="Exact path matches one specific URL only. Regex match uses a pattern to match a whole group of URLs at once.">?</span></label>
          <select id="dp-rd-type">
            <option value="exact">Exact path</option>
            <option value="match">Regex match</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status<span class="help-tip" tabindex="0" data-tip="301 = permanently moved (search engines update their records). 302/307 = temporary. 410 = gone for good, with no destination to send visitors to.">?</span></label>
          <select id="dp-rd-status">
            <option value="301">301 — Permanent</option>
            <option value="302">302 — Temporary</option>
            <option value="307">307 — Temporary (method-safe)</option>
            <option value="410">410 — Gone</option>
          </select>
        </div>
        <div class="form-group">
          <label>From<span class="help-tip" tabindex="0" data-tip="The old path visitors are currently hitting, e.g. /old-page.">?</span></label>
          <input type="text" id="dp-rd-from" placeholder="/old-path">
        </div>
        <div class="form-group" id="dp-rd-to-group">
          <label>To<span class="help-tip" tabindex="0" data-tip="Where to send visitors instead — a full URL, or another path on the same site.">?</span></label>
          <input type="text" id="dp-rd-to" placeholder="https://example.com/new">
        </div>
      </div>
      <button class="btn btn-blue btn-sm" onclick="dpCreateRedirect()" style="margin-bottom:1.25rem">Add Redirect</button>
      <div id="dp-redirect-list" class="vhost-list"></div>
    </div>

    <!-- Rewrites tab -->
    <div class="drawer-panel" id="dp-rewrites">
      <div class="help-intro">
        A <strong>rewrite</strong> changes what Apache serves internally without changing the URL visitors see
        in their browser — used for clean URLs, routing, or blocking access. More advanced than a redirect;
        skip this tab unless you know you need it.
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Pattern<span class="help-tip" tabindex="0" data-tip="A regular expression matched against the incoming URL path (not the full domain).">?</span></label>
          <input type="text" id="dp-rw-pattern" placeholder="^/old/(.*)$">
        </div>
        <div class="form-group">
          <label>Substitution<span class="help-tip" tabindex="0" data-tip="What Apache actually serves instead. $1, $2… refer to the parenthesized groups captured in Pattern.">?</span></label>
          <input type="text" id="dp-rw-sub" placeholder="/new/$1">
        </div>
        <div class="form-group">
          <label>Flags<span class="help-tip" tabindex="0" data-tip="Extra behavior for this rule. Hover any option in the list below to see what it does.">?</span></label>
          <div class="ms-dropdown" id="dp-rw-flags-dd">
            <button type="button" class="ms-dropdown-btn" id="dp-rw-flags-btn">
              <span class="ms-btn-label" id="dp-rw-flags-label">None selected</span>
              <span class="ms-caret">▾</span>
            </button>
            <div class="ms-dropdown-panel" id="dp-rw-flags-panel">
              <label class="ms-option"><input type="checkbox" value="L"> L <span class="ms-hint">Last</span></label>
              <label class="ms-option"><input type="checkbox" value="NC"> NC <span class="ms-hint">No case</span></label>
              <label class="ms-option"><input type="checkbox" value="QSA"> QSA <span class="ms-hint">Append query string</span></label>
              <label class="ms-option"><input type="checkbox" value="NE"> NE <span class="ms-hint">No URL escape</span></label>
              <label class="ms-option"><input type="checkbox" value="END"> END <span class="ms-hint">Stop rewriting</span></label>
              <label class="ms-option"><input type="checkbox" value="PT"> PT <span class="ms-hint">Pass through</span></label>
              <label class="ms-option"><input type="checkbox" value="NS"> NS <span class="ms-hint">No subrequests</span></label>
              <label class="ms-option"><input type="checkbox" value="F"> F <span class="ms-hint">Forbidden (403)</span></label>
              <label class="ms-option"><input type="checkbox" value="G"> G <span class="ms-hint">Gone (410)</span></label>
              <div class="ms-option-redirect">
                <label><input type="checkbox" id="dp-rw-flag-r"> R <span class="ms-hint">Redirect</span></label>
                <select id="dp-rw-flag-r-code" disabled>
                  <option value="">302</option>
                  <option value="301">301</option>
                  <option value="302">302</option>
                  <option value="303">303</option>
                  <option value="307">307</option>
                  <option value="308">308</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="form-grid" style="margin-top:.75rem">
        <div class="form-group">
          <label>Condition test (optional)<span class="help-tip" tabindex="0" data-tip="Only apply this rule when a server value — like the requested hostname — matches the Condition pattern. Leave as '— none —' to always apply the rule.">?</span></label>
          <select id="dp-rw-cond-test">
            <option value="">— none —</option>
            <option value="%{HTTP_HOST}">%{HTTP_HOST}</option>
            <option value="%{HTTPS}">%{HTTPS}</option>
            <option value="%{REQUEST_URI}">%{REQUEST_URI}</option>
            <option value="%{QUERY_STRING}">%{QUERY_STRING}</option>
            <option value="%{REQUEST_METHOD}">%{REQUEST_METHOD}</option>
            <option value="%{REQUEST_FILENAME}">%{REQUEST_FILENAME}</option>
            <option value="%{DOCUMENT_ROOT}">%{DOCUMENT_ROOT}</option>
            <option value="%{HTTP_USER_AGENT}">%{HTTP_USER_AGENT}</option>
            <option value="%{HTTP_REFERER}">%{HTTP_REFERER}</option>
            <option value="%{REMOTE_ADDR}">%{REMOTE_ADDR}</option>
            <option value="%{SERVER_PORT}">%{SERVER_PORT}</option>
            <option value="%{TIME}">%{TIME}</option>
            <option value="__custom__">Custom…</option>
          </select>
          <input type="text" id="dp-rw-cond-test-custom" placeholder="%{ENV:SOMEVAR}" style="display:none;margin-top:.4rem">
        </div>
        <div class="form-group">
          <label>Condition pattern<span class="help-tip" tabindex="0" data-tip="A regular expression the Condition test value must match for this rule to apply.">?</span></label>
          <input type="text" id="dp-rw-cond-pattern" placeholder="^old\.example\.com$">
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem;margin:1rem 0 1.25rem">
        <button class="btn btn-blue btn-sm" id="dp-rw-submit-btn" onclick="dpCreateRewrite()">Add Rewrite</button>
        <button class="btn btn-ghost btn-sm" id="dp-rw-cancel-edit" onclick="dpCancelRewriteEdit()" style="display:none">Cancel</button>
      </div>
      <div id="dp-rewrite-list" class="vhost-list"></div>
    </div>

    <!-- Error Handling tab -->
    <div class="drawer-panel" id="dp-errors">
      <div class="help-intro">
        Controls what visitors and log files see when something goes wrong on this site.
      </div>
      <div>
        <div class="toggle-row" style="margin-bottom:1rem">
          <div class="toggle-row-info">
            <div class="toggle-row-label">PHP error display<span class="help-tip" tabindex="0" data-tip="Turn this off in production. Showing raw PHP errors to visitors can leak file paths and source code — keep it on only while actively debugging.">?</span></div>
            <div class="toggle-row-sub">Show PHP errors in browser</div>
          </div>
          <label class="toggle">
            <input type="checkbox" id="dp-php-errors" onchange="dpSetPhpErrors(this.checked, this)">
            <div class="toggle-track"></div>
            <div class="toggle-thumb"></div>
          </label>
        </div>
        <div class="toggle-row" style="margin-bottom:1.5rem">
          <div class="toggle-row-info">
            <div class="toggle-row-label">Error logging<span class="help-tip" tabindex="0" data-tip="Keep this on. Without a log file, you can't diagnose crashes after the fact — this only writes to a server log, visitors never see it.">?</span></div>
            <div class="toggle-row-sub">Write errors to log file</div>
          </div>
          <label class="toggle">
            <input type="checkbox" id="dp-error-log" onchange="dpSetErrorLog(this.checked, this)">
            <div class="toggle-track"></div>
            <div class="toggle-thumb"></div>
          </label>
        </div>
        <hr class="divider">
        <div class="form-grid" style="margin-top:1rem">
          <div class="form-group">
            <label>Status code<span class="help-tip" tabindex="0" data-tip="Which kind of error this custom page applies to — e.g. 404 means 'page not found', 500 means an internal server error.">?</span></label>
            <select id="dp-err-code">
              <option value="400">400 — Bad Request</option>
              <option value="401">401 — Unauthorized</option>
              <option value="403">403 — Forbidden</option>
              <option value="404" selected>404 — Not Found</option>
              <option value="410">410 — Gone</option>
              <option value="500">500 — Server Error</option>
              <option value="502">502 — Bad Gateway</option>
              <option value="503">503 — Unavailable</option>
            </select>
          </div>
          <div class="form-group">
            <label>Path or URL<span class="help-tip" tabindex="0" data-tip="The page Apache shows instead of its default error page for this status code, e.g. /errors/404.html.">?</span></label>
            <input type="text" id="dp-err-target" placeholder="/errors/404.html">
          </div>
        </div>
        <button class="btn btn-blue btn-sm" onclick="dpSetErrorDoc()" style="margin-bottom:1.25rem">Set Error Page</button>
        <div id="dp-err-doc-list" class="vhost-list"></div>
      </div>
    </div>

    <!-- .htaccess tab -->
    <div class="drawer-panel" id="dp-htaccess">
      <div class="help-intro">
        The site's own <code>.htaccess</code> file, in its document root. Apache reads this on every
        request — no reload needed after saving, but a typo here can break the whole site immediately.
      </div>
      <div id="dp-ht-path" class="redirect-label" style="margin-bottom:.5rem"></div>
      <textarea id="dp-ht-content" class="code-textarea" spellcheck="false" placeholder="# No .htaccess file yet — anything you save here will create one"></textarea>
      <button class="btn btn-blue btn-sm" onclick="dpSaveHtaccess()" style="margin-top:1rem">Save .htaccess</button>
    </div>
  </drawer-content>
</me-drawer>

<script src="/vendor/SimpleNotification/simpleNotification.min.js"></script>
<script src="/vendor/delete-in-place.js"></script>
<script>
// ── Notifications ─────────────────────────────────────────────────────────────
function notifyOk(msg)  { SimpleNotification.success({ text: msg }); }
function notifyErr(msg) { SimpleNotification.error({ text: msg }); }

// ── Delete-in-place row removal (ported timing from EdgeCart admin/js/products.js) ──
function dipRemoveRow(item, onGone) {
  item.classList.add('deleting');
  setTimeout(() => {
    item.classList.add('deleting-fade');
    setTimeout(() => { item.remove(); if (onGone) onGone(); }, 300);
  }, 200);
}

// ── Site properties drawer ────────────────────────────────────────────────────
let _activeSite = null; // { name, server_name, port, doc_root }

function openSiteProps(sld) {
  const group = window._vhostGroups[sld];
  if (!group) return;
  const c    = group.configs[0];
  _activeSite = { name: c.name, server_name: c.server_name, port: c.port, doc_root: group.doc_root };

  document.getElementById('site-drawer-title').textContent = c.server_name || c.name;
  document.getElementById('site-drawer').classList.add('open');
  document.getElementById('drawer-overlay').classList.add('show');

  // Open to redirects tab by default
  switchDrawerTab('redirects');
}

function closeSiteDrawer() {
  document.getElementById('site-drawer').classList.remove('open');
  document.getElementById('drawer-overlay').classList.remove('show');
  _activeSite = null;
}

document.getElementById('drawer-overlay').addEventListener('click', closeSiteDrawer);

// Tab switching
document.querySelectorAll('.drawer-tab').forEach(btn => {
  btn.addEventListener('click', () => switchDrawerTab(btn.dataset.tab));
});

function switchDrawerTab(tab) {
  document.querySelectorAll('.drawer-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  document.querySelectorAll('.drawer-panel').forEach(p => p.classList.toggle('active', p.id === 'dp-' + tab));
  if (tab === 'redirects') dpLoadRedirects();
  if (tab === 'rewrites')  dpLoadRewrites();
  if (tab === 'errors')    dpLoadErrors();
  if (tab === 'htaccess')  dpLoadHtaccess();
}

// ── Drawer: Redirects ─────────────────────────────────────────────────────────
document.getElementById('dp-rd-status').addEventListener('change', function() {
  document.getElementById('dp-rd-to-group').style.display = this.value === '410' ? 'none' : '';
});

async function dpLoadRedirects() {
  if (!_activeSite) return;
  const list = document.getElementById('dp-redirect-list');
  list.innerHTML = '<div class="empty">Loading…</div>';
  const r = await fetch('/api/redirects?vhost=' + encodeURIComponent(_activeSite.name));
  const redirects = await r.json();
  if (!redirects.length) { list.innerHTML = '<div class="empty">No redirects</div>'; return; }
  list.innerHTML = redirects.map(rd => {
    const toHtml   = rd.status !== 410 ? `<span class="redirect-arrow">→</span><code>${rd.to}</code>` : '';
    const matchTag = rd.type === 'match' ? `<span class="tag tag-match">regex</span>` : '';
    return `<div class="redirect-item" data-line="${rd.line}">
      <div class="redirect-info"><div class="redirect-rule">
        <span class="tag tag-${rd.status}">${rd.status}</span>${matchTag}<code>${rd.from}</code>${toHtml}
      </div></div>
      <delete-in-place caption="&#128465;" confirm="ok?" data-line="${rd.line}"></delete-in-place>
    </div>`;
  }).join('');
}

document.getElementById('dp-redirect-list').addEventListener('dip-confirm', function (e) {
  dpDeleteRedirect(parseInt(e.detail['data-line'], 10), e.target.closest('.redirect-item'));
});

async function dpCreateRedirect() {
  if (!_activeSite) return;
  const type   = document.getElementById('dp-rd-type').value;
  const status = document.getElementById('dp-rd-status').value;
  const from   = document.getElementById('dp-rd-from').value.trim();
  const to     = document.getElementById('dp-rd-to').value.trim();
  if (!from || (status !== '410' && !to)) { SimpleNotification.error({ text: 'From and To are required.' }); return; }
  const r = await fetch('/api/redirects', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'create', vhost: _activeSite.name, type, status, from, to })
  });
  const d = await r.json();
  if (d.ok) {
    _undoTokens = [d.token]; _undoSection = 'redirects'; setUndoActive(true);
    document.getElementById('dp-rd-from').value = '';
    document.getElementById('dp-rd-to').value   = '';
    dpLoadRedirects();
  } else { SimpleNotification.error({ text: d.error || 'Unknown error' }); }
}

async function dpDeleteRedirect(line, item) {
  if (!_activeSite) return;
  const r = await fetch('/api/redirects', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'delete', vhost: _activeSite.name, line })
  });
  const d = await r.json();
  if (!d.ok) { notifyErr(d.error || 'Unknown error'); return; }
  _undoTokens = d.token ? [d.token] : []; _undoSection = 'redirects'; setUndoActive(_undoTokens.length > 0);
  dipRemoveRow(item);
}

// ── Drawer: Rewrites ───────────────────────────────────────────────────────────

// Flags checkbox-dropdown
const _rwFlagsDd    = document.getElementById('dp-rw-flags-dd');
const _rwFlagsBtn   = document.getElementById('dp-rw-flags-btn');
const _rwFlagsLabel = document.getElementById('dp-rw-flags-label');
const _rwFlagRCheck = document.getElementById('dp-rw-flag-r');
const _rwFlagRCode  = document.getElementById('dp-rw-flag-r-code');

_rwFlagsBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  _rwFlagsDd.classList.toggle('open');
});
document.addEventListener('click', (e) => {
  if (!_rwFlagsDd.contains(e.target)) _rwFlagsDd.classList.remove('open');
});
_rwFlagRCheck.addEventListener('change', () => { _rwFlagRCode.disabled = !_rwFlagRCheck.checked; updateFlagsLabel(); });
_rwFlagsDd.querySelectorAll('.ms-option input[type=checkbox]').forEach(cb => cb.addEventListener('change', updateFlagsLabel));
_rwFlagRCode.addEventListener('change', updateFlagsLabel);

function getSelectedFlags() {
  const flags = [];
  _rwFlagsDd.querySelectorAll('.ms-option input[type=checkbox]:checked').forEach(cb => flags.push(cb.value));
  if (_rwFlagRCheck.checked) flags.push(_rwFlagRCode.value ? `R=${_rwFlagRCode.value}` : 'R');
  return flags;
}

function updateFlagsLabel() {
  const flags = getSelectedFlags();
  _rwFlagsLabel.textContent = flags.length ? flags.join(', ') : 'None selected';
  _rwFlagsLabel.classList.toggle('has-selection', flags.length > 0);
}

function resetFlagsDropdown() {
  _rwFlagsDd.querySelectorAll('.ms-option input[type=checkbox]').forEach(cb => cb.checked = false);
  _rwFlagRCheck.checked = false;
  _rwFlagRCode.disabled = true;
  _rwFlagRCode.value = '';
  updateFlagsLabel();
}

// Condition test dropdown — reveal free text input for "Custom…"
document.getElementById('dp-rw-cond-test').addEventListener('change', function() {
  document.getElementById('dp-rw-cond-test-custom').style.display = this.value === '__custom__' ? '' : 'none';
});

function getConditionTest() {
  const sel = document.getElementById('dp-rw-cond-test');
  if (sel.value === '__custom__') return document.getElementById('dp-rw-cond-test-custom').value.trim();
  return sel.value;
}

let _rwRules     = [];
let _rwEditLines = null; // lines array of the rule currently being edited, or null

async function dpLoadRewrites() {
  if (!_activeSite) return;
  const list = document.getElementById('dp-rewrite-list');
  list.innerHTML = '<div class="empty">Loading…</div>';
  const r = await fetch('/api/rewrites?vhost=' + encodeURIComponent(_activeSite.name));
  const d = await r.json();
  _rwRules = d.rules;
  if (!d.rules.length) { list.innerHTML = '<div class="empty">No rewrite rules</div>'; return; }
  list.innerHTML = d.rules.map((rule, i) => {
    const condHtml = rule.conditions.map(c =>
      `<div class="redirect-label">Cond: <code>${c.test}</code> <code>${c.pattern}</code></div>`
    ).join('');
    const flagsTag = rule.flags ? `<span class="tag tag-match">${rule.flags}</span>` : '';
    return `<div class="redirect-item" data-lines="${rule.lines.join(',')}">
      <div class="redirect-info">
        ${condHtml}
        <div class="redirect-rule">${flagsTag}<code>${rule.pattern}</code><span class="redirect-arrow">→</span><code>${rule.substitution}</code></div>
      </div>
      <div style="display:flex;align-items:center;gap:.5rem">
        <button type="button" class="btn btn-ghost btn-sm" onclick="dpEditRewrite(${i})">Edit</button>
        <delete-in-place caption="&#128465;" confirm="ok?" data-lines="${rule.lines.join(',')}"></delete-in-place>
      </div>
    </div>`;
  }).join('');
}

document.getElementById('dp-rewrite-list').addEventListener('dip-confirm', function (e) {
  dpDeleteRewrite(e.detail['data-lines'], e.target.closest('.redirect-item'));
});

function dpEditRewrite(i) {
  const rule = _rwRules[i];
  if (!rule) return;

  document.getElementById('dp-rw-pattern').value = rule.pattern;
  document.getElementById('dp-rw-sub').value      = rule.substitution;

  resetFlagsDropdown();
  (rule.flags ? rule.flags.split(',') : []).forEach(f => {
    if (!f) return;
    if (f[0] === 'R') {
      _rwFlagRCheck.checked = true;
      _rwFlagRCode.disabled = false;
      const eq = f.indexOf('=');
      _rwFlagRCode.value = eq >= 0 ? f.slice(eq + 1) : '';
    } else {
      const cb = _rwFlagsDd.querySelector(`.ms-option input[value="${f}"]`);
      if (cb) cb.checked = true;
    }
  });
  updateFlagsLabel();

  const condSel    = document.getElementById('dp-rw-cond-test');
  const condCustom = document.getElementById('dp-rw-cond-test-custom');
  const cond       = rule.conditions[0];
  if (cond) {
    const known = Array.from(condSel.options).some(o => o.value === cond.test);
    if (known) { condSel.value = cond.test; condCustom.style.display = 'none'; condCustom.value = ''; }
    else       { condSel.value = '__custom__'; condCustom.style.display = ''; condCustom.value = cond.test; }
    document.getElementById('dp-rw-cond-pattern').value = cond.pattern;
  } else {
    condSel.value = '';
    condCustom.style.display = 'none';
    condCustom.value = '';
    document.getElementById('dp-rw-cond-pattern').value = '';
  }

  _rwEditLines = rule.lines;
  document.getElementById('dp-rw-submit-btn').textContent = 'Save Changes';
  document.getElementById('dp-rw-cancel-edit').style.display = '';
  document.getElementById('dp-rw-pattern').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function dpCancelRewriteEdit() {
  _rwEditLines = null;
  document.getElementById('dp-rw-pattern').value = '';
  document.getElementById('dp-rw-sub').value = '';
  resetFlagsDropdown();
  document.getElementById('dp-rw-cond-test').value = '';
  document.getElementById('dp-rw-cond-test-custom').style.display = 'none';
  document.getElementById('dp-rw-cond-test-custom').value = '';
  document.getElementById('dp-rw-cond-pattern').value = '';
  document.getElementById('dp-rw-submit-btn').textContent = 'Add Rewrite';
  document.getElementById('dp-rw-cancel-edit').style.display = 'none';
}

async function dpCreateRewrite() {
  if (!_activeSite) return;
  const pattern      = document.getElementById('dp-rw-pattern').value.trim();
  const substitution = document.getElementById('dp-rw-sub').value.trim();
  const flags        = getSelectedFlags().join(',');
  const condTest      = getConditionTest();
  const condPattern   = document.getElementById('dp-rw-cond-pattern').value.trim();
  if (!pattern || !substitution) { SimpleNotification.error({ text: 'Pattern and substitution are required.' }); return; }
  const conditions = (condTest && condPattern) ? [{ test: condTest, pattern: condPattern }] : [];

  // Editing an existing rule: remove its old lines first, then add the new version.
  // Only the pre-edit backup token is kept for undo, so one Undo click restores the original rule exactly.
  let preEditToken = null;
  if (_rwEditLines) {
    const delRes  = await fetch('/api/rewrites', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'delete', vhost: _activeSite.name, lines: _rwEditLines })
    });
    const delData = await delRes.json();
    if (!delData.ok) { SimpleNotification.error({ text: delData.error || 'Failed to update rule' }); return; }
    preEditToken = delData.token;
  }

  const r = await fetch('/api/rewrites', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'create', vhost: _activeSite.name, pattern, substitution, flags, conditions })
  });
  const d = await r.json();
  if (d.ok) {
    _undoTokens = [preEditToken || d.token]; _undoSection = 'rewrites'; setUndoActive(true);
    dpCancelRewriteEdit();
    dpLoadRewrites();
  } else { SimpleNotification.error({ text: d.error || 'Unknown error' }); }
}

async function dpDeleteRewrite(lineCsv, item) {
  if (!_activeSite) return;
  const lines = lineCsv.split(',').map(Number);
  const r = await fetch('/api/rewrites', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'delete', vhost: _activeSite.name, lines })
  });
  const d = await r.json();
  if (!d.ok) { notifyErr(d.error || 'Unknown error'); return; }
  _undoTokens = d.token ? [d.token] : []; _undoSection = 'rewrites'; setUndoActive(_undoTokens.length > 0);
  if (_rwEditLines && lineCsv === _rwEditLines.join(',')) dpCancelRewriteEdit();
  dipRemoveRow(item);
}

// ── Drawer: Error Handling ────────────────────────────────────────────────────
async function dpLoadErrors() {
  if (!_activeSite) return;
  const r = await fetch('/api/errors?vhost=' + encodeURIComponent(_activeSite.name));
  const d = await r.json();
  if (!d) return;
  document.getElementById('dp-php-errors').checked = d.php_errors ? d.php_errors.on : false;
  document.getElementById('dp-error-log').checked  = d.error_log !== null && !d.log_disabled;
  const list = document.getElementById('dp-err-doc-list');
  if (!d.error_docs.length) { list.innerHTML = '<div class="empty">No custom error pages</div>'; return; }
  list.innerHTML = d.error_docs.map(doc => `
    <div class="redirect-item" data-code="${doc.code}">
      <div class="redirect-info"><div class="redirect-rule">
        <span class="tag" style="background:rgba(160,176,204,.1);color:var(--text-dim)">${doc.code}</span>
        <code>${doc.target}</code>
      </div></div>
      <delete-in-place caption="&#128465;" confirm="ok?" data-code="${doc.code}"></delete-in-place>
    </div>`).join('');
}

document.getElementById('dp-err-doc-list').addEventListener('dip-confirm', function (e) {
  dpRemoveErrorDoc(e.detail['data-code'], e.target.closest('.redirect-item'));
});

async function dpSetPhpErrors(on, checkbox) {
  if (!_activeSite) return;
  checkbox.disabled = true;
  const r = await fetch('/api/errors', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'set_php_errors', vhost: _activeSite.name, on })
  });
  const d = await r.json();
  checkbox.disabled = false;
  if (d.ok) { _undoTokens = [d.token]; _undoSection = 'errors'; setUndoActive(true); }
  else { checkbox.checked = !on; SimpleNotification.error({ text: 'Failed to update' }); }
}

async function dpSetErrorLog(on, checkbox) {
  if (!_activeSite) return;
  checkbox.disabled = true;
  const r = await fetch('/api/errors', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'set_error_log', vhost: _activeSite.name, disable: !on })
  });
  const d = await r.json();
  checkbox.disabled = false;
  if (d.ok) { _undoTokens = [d.token]; _undoSection = 'errors'; setUndoActive(true); }
  else { checkbox.checked = !on; SimpleNotification.error({ text: 'Failed to update' }); }
}

async function dpSetErrorDoc() {
  if (!_activeSite) return;
  const code   = document.getElementById('dp-err-code').value;
  const target = document.getElementById('dp-err-target').value.trim();
  if (!target) { SimpleNotification.error({ text: 'Path or URL is required.' }); return; }
  const r = await fetch('/api/errors', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'set_error_doc', vhost: _activeSite.name, code, target })
  });
  const d = await r.json();
  if (d.ok) {
    _undoTokens = [d.token]; _undoSection = 'errors'; setUndoActive(true);
    document.getElementById('dp-err-target').value = '';
    dpLoadErrors();
  } else { SimpleNotification.error({ text: d.error || 'Unknown error' }); }
}

async function dpRemoveErrorDoc(code, item) {
  if (!_activeSite) return;
  const r = await fetch('/api/errors', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'set_error_doc', vhost: _activeSite.name, code, target: '' })
  });
  const d = await r.json();
  if (d.ok) {
    _undoTokens = [d.token]; _undoSection = 'errors'; setUndoActive(true);
    dipRemoveRow(item);
  } else { notifyErr(d.error || 'Failed to remove'); }
}

// ── Drawer: .htaccess ────────────────────────────────────────────────────────
async function dpLoadHtaccess() {
  if (!_activeSite) return;
  const pathEl = document.getElementById('dp-ht-path');
  const textEl = document.getElementById('dp-ht-content');
  pathEl.textContent = 'Loading…';
  const r = await fetch('/api/htaccess?vhost=' + encodeURIComponent(_activeSite.name));
  const d = await r.json();
  textEl.value = d.content || '';
  pathEl.textContent = d.path ? d.path + (d.exists ? '' : ' (does not exist yet)') : 'Could not resolve document root';
}

async function dpSaveHtaccess() {
  if (!_activeSite) return;
  const content = document.getElementById('dp-ht-content').value;
  const r = await fetch('/api/htaccess', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'save', vhost: _activeSite.name, content })
  });
  const d = await r.json();
  if (d.ok) {
    if (d.token) { _undoTokens = [d.token]; _undoSection = 'htaccess'; setUndoActive(true); }
    notifyOk('.htaccess saved.');
    dpLoadHtaccess();
  } else { notifyErr(d.error || 'Failed to save'); }
}

// ── Nav ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.sidebar-nav button[data-page]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.sidebar-nav button').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('page-' + btn.dataset.page).classList.add('active');
    if (btn.dataset.page === 'hosts') loadHostsFile();
    if (btn.dataset.page === 'modules') loadModules();
  });
});

// ── Status ───────────────────────────────────────────────────────────────────
async function checkStatus() {
  try {
    const r = await fetch('/api/status');
    const d = await r.json();
    const dot  = document.getElementById('status-dot');
    const text = document.getElementById('status-text');
    dot.className  = 'status-dot ' + (d.running && d.config_ok ? 'ok' : 'err');
    text.textContent = d.running
      ? (d.config_ok ? 'Apache running' : 'Config error')
      : 'Apache stopped';
  } catch(e) {
    document.getElementById('status-text').textContent = 'Unreachable';
  }
}
checkStatus();
setInterval(checkStatus, 15000);

// ── Virtual Hosts ─────────────────────────────────────────────────────────────
async function loadVhosts() {
  const list = document.getElementById('vhost-list');
  list.innerHTML = '<div class="empty">Loading…</div>';
  const r = await fetch('/api/vhosts');
  const vhosts = await r.json();

  if (!vhosts.length) {
    list.innerHTML = '<div class="empty">No virtual hosts found</div>';
    return;
  }

  // Store groups by SLD for Manage button lookup
  window._vhostGroups = {};
  vhosts.forEach(g => { window._vhostGroups[g.sld || g.configs[0].name] = g; });

  list.innerHTML = vhosts.map(g => {
    const allEnabled  = g.configs.every(c => c.enabled);
    const anyEnabled  = g.configs.some(c => c.enabled);
    const statusLabel = allEnabled ? 'Enabled' : anyEnabled ? 'Partial' : 'Disabled';
    const statusClass = allEnabled ? 'badge-enabled' : 'badge-disabled';
    const sldKey      = g.sld || g.configs[0].name;
    const allNames    = g.configs.map(c => c.name).join(',');

    const pills = g.configs.map(c => `
      <div class="config-pill ${c.enabled ? 'enabled' : ''}" data-name="${c.name}">
        <span class="port-tag">:${c.port}</span>
        <span class="pill-host">${c.server_name || c.name}</span>
        <label class="toggle" title="${c.enabled ? 'Disable' : 'Enable'} :${c.port}">
          <input type="checkbox" ${c.enabled ? 'checked' : ''} onchange="toggleVhost('${c.name}', this.checked, this)">
          <div class="toggle-track"></div>
          <div class="toggle-thumb"></div>
        </label>
        <delete-in-place caption="&times;" confirm="ok?" data-name="${c.name}" title="Delete ${c.server_name || c.name}"></delete-in-place>
      </div>
    `).join('');

    return `
      <div class="vhost-item" data-sld="${g.sld || g.configs[0].name}">
        <div class="vhost-top">
          <div class="vhost-info">
            <div class="vhost-name" onclick="openSiteProps('${sldKey}')">${g.sld || g.configs[0].name}</div>
            <div class="vhost-meta"><span>${g.doc_root || '—'}</span></div>
          </div>
          <div class="vhost-actions">
            <span class="badge ${statusClass}">${statusLabel}</span>
            <button class="btn btn-ghost btn-sm" onclick="openSiteProps('${sldKey}')">Manage</button>
            ${g.configs.length > 1 ? `<delete-in-place caption="Delete All" confirm="ok?" data-names="${allNames}"></delete-in-place>` : ''}
          </div>
        </div>
        <div class="vhost-configs">${pills}</div>
      </div>
    `;
  }).join('');
}

document.getElementById('vhost-list').addEventListener('dip-confirm', function (e) {
  if (e.detail['data-names']) {
    deleteVhostGroup(e.detail['data-names'], e.target.closest('.vhost-item'));
  } else if (e.detail['data-name']) {
    deleteVhostConfig(e.detail['data-name'], e.target.closest('.config-pill'));
  }
});

async function toggleVhost(name, enable, checkbox) {
  checkbox.disabled = true;

  const r = await fetch('/api/vhosts', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'toggle', name, enable })
  });
  const d = await r.json();
  checkbox.disabled = false;

  if (d.ok) {
    // Update pill and group badge in place
    const pill = checkbox.closest('.config-pill');
    pill.classList.toggle('enabled', enable);
    pill.querySelector('.port-tag').style.color = enable ? 'var(--blue)' : '';

    const item   = checkbox.closest('.vhost-item');
    const pills  = item.querySelectorAll('.config-pill');
    const total  = pills.length;
    const active = item.querySelectorAll('.config-pill.enabled').length;
    const badge  = item.querySelector('.badge');
    badge.textContent  = active === total ? 'Enabled' : active === 0 ? 'Disabled' : 'Partial';
    badge.className    = 'badge ' + (active === total ? 'badge-enabled' : 'badge-disabled');
  } else {
    checkbox.checked = !enable; // revert
    showInlineError(checkbox.closest('.vhost-item'), d.output || 'Apache rejected the change');
  }
}

function showInlineError(item, message) {
  let err = item.querySelector('.inline-error');
  if (!err) {
    err = document.createElement('div');
    err.className = 'inline-error';
    item.appendChild(err);
  }
  err.textContent = message;
  err.classList.add('show');
  setTimeout(() => err.classList.remove('show'), 5000);
}

async function createVhost() {
  const name       = document.getElementById('vh-name').value.trim();
  const port       = document.getElementById('vh-port').value.trim();
  const serverName = document.getElementById('vh-servername').value.trim();
  const docRoot    = document.getElementById('vh-docroot').value.trim();

  if (!name || !serverName || !docRoot) {
    SimpleNotification.error({ text: 'Config name, server name, and document root are required.' });
    return;
  }

  const r = await fetch('/api/vhosts', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'create', name, port, server_name: serverName, doc_root: docRoot })
  });
  const d = await r.json();

  if (d.ok) {
    SimpleNotification.success({ text: 'Virtual host created.' });
    document.getElementById('vh-name').value = '';
    document.getElementById('vh-servername').value = '';
    document.getElementById('vh-docroot').value = '';
    document.getElementById('vh-port').value = '80';
    loadVhosts();
  } else {
    SimpleNotification.error({ text: d.error || 'Unknown error' });
  }
}

let _undoTokens  = [];
let _undoSection = 'vhosts';

function setUndoActive(active) {
  document.getElementById('btn-undo').classList.toggle('active', active);
}

async function deleteVhost(name) {
  const r = await fetch('/api/vhosts', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'delete', name })
  });
  const d = await r.json();
  return d.token || null;
}

async function deleteVhostConfig(name, pill) {
  const item = pill.closest('.vhost-item');

  const token = await deleteVhost(name);
  _undoTokens  = token ? [token] : [];
  _undoSection = 'vhosts';
  setUndoActive(_undoTokens.length > 0);

  dipRemoveRow(pill, () => {
    const remaining = item.querySelectorAll('.config-pill');
    if (!remaining.length) { dipRemoveRow(item); return; }
    const active = item.querySelectorAll('.config-pill.enabled').length;
    const badge  = item.querySelector('.badge');
    badge.textContent = active === remaining.length ? 'Enabled' : active === 0 ? 'Disabled' : 'Partial';
    badge.className   = 'badge ' + (active === remaining.length ? 'badge-enabled' : 'badge-disabled');
  });
}

async function deleteVhostGroup(names, item) {
  const list = names.split(',');
  const tokens = await Promise.all(list.map(n => deleteVhost(n)));
  _undoTokens  = tokens.filter(Boolean);
  _undoSection = 'vhosts';
  setUndoActive(_undoTokens.length > 0);

  dipRemoveRow(item);
}

async function undoDelete() {
  if (!_undoTokens.length) return;
  setUndoActive(false);

  const apis = { redirects: '/api/redirects', rewrites: '/api/rewrites', errors: '/api/errors', htaccess: '/api/htaccess', hosts: '/api/hosts' };
  const api  = apis[_undoSection] || '/api/vhosts';
  await Promise.all(_undoTokens.map(token =>
    fetch(api, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'restore', token })
    })
  ));
  _undoTokens = [];
  if (_undoSection === 'redirects') dpLoadRedirects();
  else if (_undoSection === 'rewrites') dpLoadRewrites();
  else if (_undoSection === 'errors') dpLoadErrors();
  else if (_undoSection === 'htaccess') dpLoadHtaccess();
  else if (_undoSection === 'hosts') loadHostsFile();
  else loadVhosts();
}

loadVhosts();

// ── Modules ──────────────────────────────────────────────────────────────────
let _modules = [];

async function loadModules() {
  const list = document.getElementById('module-list');
  list.innerHTML = '<div class="empty">Loading…</div>';
  const r = await fetch('/api/modules');
  _modules = await r.json();
  renderModules();
}

function renderModules() {
  const list   = document.getElementById('module-list');
  const filter = (document.getElementById('mod-filter').value || '').toLowerCase().trim();
  const filtered = filter
    ? _modules.filter(m => m.name.toLowerCase().includes(filter) || (m.description || '').toLowerCase().includes(filter))
    : _modules;

  if (!filtered.length) { list.innerHTML = '<div class="empty">No modules match.</div>'; return; }

  list.innerHTML = filtered.map(m => `
    <div class="redirect-item" data-name="${m.name}">
      <div class="redirect-info">
        <div class="redirect-rule"><code>${m.name}</code></div>
        <div class="redirect-label">${m.description || ''}</div>
      </div>
      <label class="toggle" title="${m.enabled ? 'Disable' : 'Enable'} ${m.name}">
        <input type="checkbox" ${m.enabled ? 'checked' : ''} onchange="toggleModule('${m.name}', this.checked, this)">
        <div class="toggle-track"></div>
        <div class="toggle-thumb"></div>
      </label>
    </div>
  `).join('');
}

async function toggleModule(name, enable, checkbox) {
  checkbox.disabled = true;
  const r = await fetch('/api/modules', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'toggle', name, enable })
  });
  const d = await r.json();
  checkbox.disabled = false;

  const mod = _modules.find(m => m.name === name);
  if (d.ok) {
    if (mod) mod.enabled = enable;
    checkbox.closest('.redirect-item').querySelector('label.toggle').title = (enable ? 'Disable' : 'Enable') + ' ' + name;
  } else {
    checkbox.checked = !enable;
    showInlineError(checkbox.closest('.redirect-item'), d.output || 'Apache rejected the change');
  }
}

// ── Hosts File ───────────────────────────────────────────────────────────────
async function loadHostsFile() {
  const localEl   = document.getElementById('hosts-local');
  const afterWrap = document.getElementById('hosts-after-wrap');
  const r = await fetch('/api/hosts');
  const d = await r.json();
  localEl.value = d.local || '';
  if (d.has_marker) {
    afterWrap.style.display = '';
    const kb = (d.after_bytes / 1024).toFixed(0);
    afterWrap.textContent = `${d.after_lines.toLocaleString()} lines (${kb} KB) below the marker — left untouched, not shown here.`;
  } else {
    afterWrap.style.display = 'none';
  }
}

async function saveHostsFile() {
  const local = document.getElementById('hosts-local').value;
  const r = await fetch('/api/hosts', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'save', local })
  });
  const d = await r.json();
  if (d.ok) {
    _undoTokens = [d.token]; _undoSection = 'hosts'; setUndoActive(true);
    notifyOk('Hosts file saved.');
    loadHostsFile();
  } else { notifyErr(d.error || 'Failed to save'); }
}

// ── Help tip collision avoidance ────────────────────────────────────────────────
function positionHelpTip(el) {
  const rect     = el.getBoundingClientRect();
  const scroller = el.closest('drawer-content, main') || document.body;
  const cRect    = scroller.getBoundingClientRect();
  const bubbleWidth = 230;
  const margin      = 12;
  const iconCenter  = rect.left + rect.width / 2;
  const idealLeft   = iconCenter - bubbleWidth / 2;
  const idealRight  = iconCenter + bubbleWidth / 2;
  let shift = 0;
  if (idealRight > cRect.right - margin)    shift = (cRect.right - margin) - idealRight;
  else if (idealLeft < cRect.left + margin) shift = (cRect.left + margin) - idealLeft;
  el.style.setProperty('--tip-shift', shift + 'px');
}
document.querySelectorAll('.help-tip').forEach(el => {
  el.addEventListener('mouseenter', () => positionHelpTip(el));
  el.addEventListener('focus', () => positionHelpTip(el));
});


</script>
</body>
</html>
