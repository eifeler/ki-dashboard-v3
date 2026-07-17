<?php
session_start();
define('ADMIN_SESSION', 'ki_dashboard_admin');
$isAdmin = isset($_SESSION[ADMIN_SESSION]) && $_SESSION[ADMIN_SESSION] === true;
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KI-Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-loggedin="<?= $isAdmin ? '1' : '0' ?>">
<div id="app">

  <!-- ══ HEADER ══════════════════════════════════════════════ -->
  <header id="header">
    <div class="header-logo">
      <div class="logo-icon">KI</div>
      <span class="logo-text"><span>KI</span>-Dashboard</span>
    </div>

    <button class="btn-icon" onclick="App.toggleSidebar()" title="Sidebar">☰</button>

    <div class="header-search">
      <span class="search-icon">🔍</span>
      <input type="text" id="global-search" placeholder="Tools, Prompts, Kurse durchsuchen …" autocomplete="off">
    </div>

    <div class="header-actions">
      <button class="btn-icon" id="theme-icon" onclick="App.toggleTheme()" title="Theme wechseln">☀️</button>
      <?php if ($isAdmin): ?>
      <a href="admin/" class="btn-icon" title="Admin-Bereich">⚙️</a>
      <?php endif; ?>
      <div class="user-avatar" onclick="toggleUserMenu()" title="Benutzer">U</div>
    </div>
  </header>

  <!-- ══ SIDEBAR ═════════════════════════════════════════════ -->
  <nav id="sidebar">
    <div class="nav-section">
      <div class="nav-section-title">Navigation</div>
      <button class="nav-item active" data-page="dashboard">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Übersicht</span>
      </button>
      <button class="nav-item" data-page="news">
        <span class="nav-icon">📰</span>
        <span class="nav-label">Aktuelles</span>
        <span class="nav-badge" id="news-badge">!</span>
      </button>
      <button class="nav-item" data-page="tools">
        <span class="nav-icon">🔧</span>
        <span class="nav-label">KI-Werkzeuge</span>
      </button>
      <button class="nav-item" data-page="learn">
        <span class="nav-icon">🎓</span>
        <span class="nav-label">Lernbereich</span>
      </button>
      <button class="nav-item" data-page="prompts">
        <span class="nav-icon">💬</span>
        <span class="nav-label">Prompt-Bibliothek</span>
      </button>
      <button class="nav-item" data-page="ai">
        <span class="nav-icon">🤖</span>
        <span class="nav-label">KI-Widget</span>
      </button>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Konto</div>
      <button class="nav-item" data-page="favorites">
        <span class="nav-icon">⭐</span>
        <span class="nav-label">Meine Favoriten</span>
      </button>
      <button class="nav-item" id="nav-admin" style="display:<?= $isAdmin ? 'flex' : 'none' ?>" onclick="window.location='admin/'">
        <span class="nav-icon">⚙️</span>
        <span class="nav-label">Admin-Bereich</span>
      </button>
    </div>
    <div class="sidebar-footer">
      <button class="nav-item" onclick="toggleLoginModal()">
        <span class="nav-icon"><?= $isAdmin ? '🔓' : '🔒' ?></span>
        <span class="nav-label sidebar-footer-text"><?= $isAdmin ? 'Admin-Logout' : 'Admin-Login' ?></span>
      </button>
    </div>
  </nav>

  <!-- ══ MAIN ════════════════════════════════════════════════ -->
  <main id="main">

    <!-- News Ticker -->
    <div style="padding:16px 24px 0">
      <div class="ticker-wrap">
        <span id="ticker-inner" class="ticker-inner">
          <span class="ticker-item">KI-Dashboard wird geladen …</span>
        </span>
      </div>
    </div>

    <!-- ── PAGE: DASHBOARD ───────────────────────────────── -->
    <div id="page-dashboard" class="page-content active">
      <div class="page-header">
        <h1>Dashboard</h1>
        <p>Dein persönlicher KI-Überblick – Werkzeuge, News und Prompts auf einen Blick</p>
      </div>

      <!-- Stats -->
      <div class="overview-grid">
        <div class="stat-widget">
          <div class="stat-icon blue">🔧</div>
          <div class="stat-info">
            <div class="stat-label">KI-Tools</div>
            <div class="stat-value" id="stat-tools">—</div>
            <div class="stat-change">im Verzeichnis</div>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon purple">💬</div>
          <div class="stat-info">
            <div class="stat-label">Prompts</div>
            <div class="stat-value" id="stat-prompts">—</div>
            <div class="stat-change">einsatzbereit</div>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon green">🎓</div>
          <div class="stat-info">
            <div class="stat-label">Kurse</div>
            <div class="stat-value" id="stat-courses">—</div>
            <div class="stat-change">Lernmodule</div>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon orange">📰</div>
          <div class="stat-info">
            <div class="stat-label">News-Beiträge</div>
            <div class="stat-value" id="stat-news">—</div>
            <div class="stat-change">aktuell</div>
          </div>
        </div>
      </div>

      <!-- Bottom grid -->
      <div class="bottom-grid">
        <div class="card">
          <div class="card-title"><span class="dot"></span> Aktuelle Meldungen</div>
          <div id="recent-news"></div>
        </div>
        <div>
          <div class="card" style="margin-bottom:16px">
            <div class="card-title"><span class="dot" style="background:var(--accent-orange)"></span> Meine Favoriten</div>
            <div id="dash-favorites" style="display:flex;flex-wrap:wrap;gap:8px"></div>
          </div>
          <div class="card">
            <div class="card-title"><span class="dot" style="background:var(--accent-green)"></span> Schnellzugriff</div>
            <div style="display:flex;flex-direction:column;gap:8px">
              <button class="btn btn-outline" onclick="App.loadPage('tools')" style="justify-content:flex-start;width:100%">🔧 Alle KI-Tools</button>
              <button class="btn btn-outline" onclick="App.loadPage('prompts')" style="justify-content:flex-start;width:100%">💬 Prompt-Bibliothek</button>
              <button class="btn btn-outline" onclick="App.loadPage('learn')" style="justify-content:flex-start;width:100%">🎓 Lernbereich</button>
              <button class="btn btn-outline" onclick="App.loadPage('ai')" style="justify-content:flex-start;width:100%">🤖 KI-Widget testen</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── PAGE: NEWS ─────────────────────────────────────── -->
    <div id="page-news" class="page-content">
      <div class="page-header">
        <h1>📰 Aktuelles & Feed</h1>
        <p>Neuigkeiten zu KI-Modellen, rechtlichen Rahmenbedingungen und Updates</p>
      </div>
      <div id="news-list"><div style="color:var(--text-muted);padding:20px">Lade …</div></div>
    </div>

    <!-- ── PAGE: TOOLS ────────────────────────────────────── -->
    <div id="page-tools" class="page-content">
      <div class="page-header">
        <h1>🔧 KI-Werkzeugkasten</h1>
        <p>Durchsuchbares Verzeichnis der besten KI-Tools – gefiltert nach Kategorie, Preis und Anwendungsfall</p>
      </div>

      <div class="filter-bar">
        <div class="search-box">
          <span>🔍</span>
          <input type="text" placeholder="Tool suchen …" oninput="App.searchTools(this.value)">
        </div>

        <button class="filter-btn active" data-filter="cat" data-val="all" onclick="App.setToolFilter('cat','all');this.parentElement.querySelectorAll('[data-filter=cat]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Alle</button>
        <button class="filter-btn" data-filter="cat" data-val="Textgenerierung" onclick="App.setToolFilter('cat','Textgenerierung');document.querySelectorAll('[data-filter=cat]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Text</button>
        <button class="filter-btn" data-filter="cat" data-val="Bildbearbeitung" onclick="App.setToolFilter('cat','Bildbearbeitung');document.querySelectorAll('[data-filter=cat]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Bild</button>
        <button class="filter-btn" data-filter="cat" data-val="Code-Generierung" onclick="App.setToolFilter('cat','Code-Generierung');document.querySelectorAll('[data-filter=cat]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Code</button>
        <button class="filter-btn" data-filter="cat" data-val="Audio/Video" onclick="App.setToolFilter('cat','Audio/Video');document.querySelectorAll('[data-filter=cat]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Audio/Video</button>

        <span style="margin-left:8px;color:var(--text-muted);font-size:.8rem">Preis:</span>
        <button class="filter-btn active" data-filter="price" data-val="all" onclick="App.setToolFilter('price','all');document.querySelectorAll('[data-filter=price]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Alle</button>
        <button class="filter-btn" data-filter="price" data-val="free" onclick="App.setToolFilter('price','free');document.querySelectorAll('[data-filter=price]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Kostenlos</button>
        <button class="filter-btn" data-filter="price" data-val="freemium" onclick="App.setToolFilter('price','freemium');document.querySelectorAll('[data-filter=price]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Freemium</button>
        <button class="filter-btn" data-filter="price" data-val="paid" onclick="App.setToolFilter('price','paid');document.querySelectorAll('[data-filter=price]').forEach(b=>b.classList.remove('active'));this.classList.add('active')">Bezahlt</button>
      </div>

      <div id="tools-grid" class="grid-auto">
        <div style="color:var(--text-muted);padding:20px">Lade …</div>
      </div>
    </div>

    <!-- ── PAGE: LEARN ────────────────────────────────────── -->
    <div id="page-learn" class="page-content">
      <div class="page-header">
        <h1>🎓 Lernbereich & Ressourcen</h1>
        <p>Strukturiertes Wissen – von KI-Grundlagen bis zu Profi-Tipps</p>
      </div>

      <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start" class="learn-grid">
        <div>
          <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;color:var(--text-secondary)">Kurse & Module</h2>
          <div id="courses-grid" class="grid-2">
            <div style="color:var(--text-muted)">Lade …</div>
          </div>
        </div>
        <div>
          <div class="card">
            <div class="card-title"><span class="dot" style="background:var(--accent-purple)"></span> Glossar</div>
            <div id="glossary-list"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── PAGE: PROMPTS ──────────────────────────────────── -->
    <div id="page-prompts" class="page-content">
      <div class="page-header">
        <h1>💬 Prompt-Bibliothek</h1>
        <p>Erprobte Eingabebefehle für verschiedene Szenarien – direkt kopieren und anpassen</p>
      </div>

      <div class="filter-bar" id="prompt-filters">
        <button class="filter-btn prompt-filter-btn active" data-cat="all" onclick="App.setPromptFilter('all')">Alle</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Schreiben" onclick="App.setPromptFilter('Schreiben')">Schreiben</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Code" onclick="App.setPromptFilter('Code')">Code</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Analyse" onclick="App.setPromptFilter('Analyse')">Analyse</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Kreativ" onclick="App.setPromptFilter('Kreativ')">Kreativ</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Business" onclick="App.setPromptFilter('Business')">Business</button>
        <button class="filter-btn prompt-filter-btn" data-cat="Bildung" onclick="App.setPromptFilter('Bildung')">Bildung</button>
      </div>

      <div id="prompts-grid" class="grid-2">
        <div style="color:var(--text-muted)">Lade …</div>
      </div>
    </div>

    <!-- ── PAGE: AI WIDGET ────────────────────────────────── -->
    <div id="page-ai" class="page-content">
      <div class="page-header">
        <h1>🤖 KI-Widget</h1>
        <p>Direkte KI-Abfragen im Dashboard – ohne die Seite zu wechseln</p>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
        <div class="card">
          <div class="card-title"><span class="dot"></span> Chat-Interface</div>
          <div class="ai-widget">
            <div class="ai-messages" id="ai-messages">
              <div class="msg bot"><div class="msg-label">KI</div>Hallo! Gib deinen OpenAI API-Key ein und stelle mir eine Frage. 🚀</div>
            </div>
            <div class="ai-input-row">
              <input type="text" id="ai-input" placeholder="Stelle eine KI-Frage …" onkeydown="if(event.key==='Enter')App.sendAiMessage()">
              <button class="btn-send" onclick="App.sendAiMessage()">Senden ▶</button>
            </div>
          </div>
        </div>

        <div>
          <div class="card" style="margin-bottom:16px">
            <div class="card-title"><span class="dot" style="background:var(--accent-orange)"></span> API-Einstellungen</div>
            <div class="form-group">
              <label class="form-label">OpenAI API-Key</label>
              <div class="api-key-row">
                <input type="password" id="ai-api-key" placeholder="sk-…" value="">
              </div>
              <p class="api-label" style="margin-top:6px">Der Key wird nur lokal im Browser gespeichert.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Modell</label>
              <select class="form-select" id="ai-model">
                <option value="gpt-3.5-turbo">GPT-3.5 Turbo (günstig)</option>
                <option value="gpt-4o-mini">GPT-4o mini</option>
                <option value="gpt-4o">GPT-4o</option>
                <option value="gpt-4-turbo">GPT-4 Turbo</option>
              </select>
            </div>
            <button class="btn btn-primary" onclick="App.saveApiKey()">💾 Speichern</button>
          </div>

          <div class="card">
            <div class="card-title"><span class="dot" style="background:var(--accent-green)"></span> Hinweise</div>
            <ul style="font-size:.82rem;color:var(--text-secondary);line-height:1.8;padding-left:16px">
              <li>API-Key wird nur im Browser gespeichert</li>
              <li>Anfragen gehen über den Server-Proxy</li>
              <li>Kosten entstehen beim OpenAI-Konto</li>
              <li>Max. 600 Token pro Antwort</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- ── PAGE: FAVORITES ────────────────────────────────── -->
    <div id="page-favorites" class="page-content">
      <div class="page-header">
        <h1>⭐ Meine Favoriten</h1>
        <p>Gespeicherte Tools und Prompts für den schnellen Zugriff</p>
      </div>
      <div id="fav-list"></div>
    </div>

    <!-- ── FOOTER ─────────────────────────────────────────── -->
    <footer>
      <div>
        <span class="status-dot"></span>
        <span>System online · KI-Dashboard v1.0</span>
      </div>
      <div class="footer-links">
        <a href="#" onclick="App.showToast('Impressum folgt …','info');return false">Impressum</a>
        <a href="#" onclick="App.showToast('Datenschutz folgt …','info');return false">Datenschutz</a>
        <a href="#" onclick="App.showToast('Kontaktformular folgt …','info');return false">Kontakt</a>
      </div>
    </footer>

  </main><!-- /main -->
</div><!-- /app -->

<!-- ══ TOAST CONTAINER ══════════════════════════════════════ -->
<div id="toast-container"></div>

<!-- ══ LOGIN MODAL ══════════════════════════════════════════ -->
<div class="modal-overlay" id="login-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><?= $isAdmin ? '🔓 Admin-Logout' : '🔒 Admin-Login' ?></span>
      <button class="modal-close" onclick="toggleLoginModal()">✕</button>
    </div>
    <?php if ($isAdmin): ?>
    <p style="color:var(--text-secondary);font-size:.875rem;margin-bottom:20px">Du bist als Admin eingeloggt.</p>
    <button class="btn btn-danger" style="width:100%" onclick="adminLogout()">Ausloggen</button>
    <?php else: ?>
    <div class="form-group">
      <label class="form-label">Admin-Passwort</label>
      <input type="password" class="form-input" id="admin-pw" placeholder="Passwort eingeben …" onkeydown="if(event.key==='Enter')adminLogin()">
    </div>
    <button class="btn btn-primary" style="width:100%" onclick="adminLogin()">Einloggen</button>
    <?php endif; ?>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
// Admin login/logout (simple, sessionbasiert)
function toggleLoginModal() {
  document.getElementById('login-modal').classList.toggle('open');
}
function adminLogin() {
  const pw = document.getElementById('admin-pw')?.value;
  if (!pw) return;
  fetch('api.php?action=admin_login', {
    method: 'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({password: pw})
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      App.showToast('Erfolgreich eingeloggt ✓','success');
      setTimeout(()=>location.reload(),800);
    } else {
      App.showToast('Falsches Passwort','error');
    }
  });
}
function adminLogout() {
  fetch('api.php?action=admin_logout',{method:'POST'}).then(()=>{
    App.showToast('Ausgeloggt','info');
    setTimeout(()=>location.reload(),600);
  });
}

// Prefill saved API key
document.addEventListener('DOMContentLoaded', () => {
  const k = localStorage.getItem('ki_api_key');
  const m = localStorage.getItem('ki_api_model');
  if (k && document.getElementById('ai-api-key')) document.getElementById('ai-api-key').value = k;
  if (m && document.getElementById('ai-model')) document.getElementById('ai-model').value = m;
});
</script>
</body>
</html>
