<?php
session_start();
define('ADMIN_SESSION', 'ki_dashboard_admin');
if (!isset($_SESSION[ADMIN_SESSION]) || !$_SESSION[ADMIN_SESSION]) {
    // Not logged in – redirect to main with message
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – KI-Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .admin-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
    .admin-tab {
      padding:8px 18px; border-radius:8px; border:1px solid var(--border);
      background:var(--bg-card); color:var(--text-secondary); cursor:pointer;
      font-size:.875rem; font-weight:500; transition:all .2s;
    }
    .admin-tab.active, .admin-tab:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-glow); }
    .admin-section { display:none; }
    .admin-section.active { display:block; }
    .admin-header-bar {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:20px; gap:12px;
    }
    .table-wrap { overflow-x:auto; border:1px solid var(--border); border-radius:var(--radius); }
    .edit-area { min-height:120px; }
    .rss-feed-item { display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid var(--border); }
    .rss-feed-item:last-child { border-bottom:none; }
  </style>
</head>
<body>
<div id="app" style="min-height:100vh;background:var(--bg-primary)">

  <!-- Header -->
  <header id="header">
    <div class="header-logo">
      <div class="logo-icon">⚙️</div>
      <span class="logo-text"><span>KI</span>-Admin</span>
    </div>
    <div class="header-actions" style="margin-left:auto">
      <a href="../" class="btn btn-outline" style="font-size:.82rem">← Zum Dashboard</a>
      <button class="btn btn-danger" style="font-size:.82rem" onclick="adminLogout()">Logout</button>
    </div>
  </header>

  <main id="main" style="margin-left:0; padding:80px 24px 40px">
    <div class="page-header">
      <h1>⚙️ Admin-Bereich</h1>
      <p>Inhalte verwalten – Tools, Prompts, News, Kurse, Glossar und RSS-Feeds</p>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
      <button class="admin-tab active" onclick="showTab('tools')">🔧 Tools</button>
      <button class="admin-tab" onclick="showTab('prompts')">💬 Prompts</button>
      <button class="admin-tab" onclick="showTab('news')">📰 News</button>
      <button class="admin-tab" onclick="showTab('courses')">🎓 Kurse</button>
      <button class="admin-tab" onclick="showTab('glossary')">📖 Glossar</button>
      <button class="admin-tab" onclick="showTab('settings')">⚙️ Einstellungen</button>
    </div>

    <!-- ── TOOLS ──────────────────────────────────────────── -->
    <div id="tab-tools" class="admin-section active">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">KI-Tools verwalten</h2>
        <button class="btn btn-primary" onclick="openModal('tool')">+ Neues Tool</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Icon</th><th>Name</th><th>Kategorie</th><th>Preis</th><th>URL</th><th>Aktionen</th></tr></thead>
          <tbody id="tools-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── PROMPTS ────────────────────────────────────────── -->
    <div id="tab-prompts" class="admin-section">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">Prompt-Bibliothek verwalten</h2>
        <button class="btn btn-primary" onclick="openModal('prompt')">+ Neuer Prompt</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Titel</th><th>Kategorie</th><th>Beschreibung</th><th>Aktionen</th></tr></thead>
          <tbody id="prompts-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── NEWS ──────────────────────────────────────────── -->
    <div id="tab-news" class="admin-section">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">News-Beiträge verwalten</h2>
        <button class="btn btn-primary" onclick="openModal('news')">+ Neue Meldung</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Datum</th><th>Titel</th><th>Kategorie</th><th>Aktionen</th></tr></thead>
          <tbody id="news-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── COURSES ────────────────────────────────────────── -->
    <div id="tab-courses" class="admin-section">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">Kurse & Lernmodule verwalten</h2>
        <button class="btn btn-primary" onclick="openModal('course')">+ Neuer Kurs</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Icon</th><th>Titel</th><th>Lektionen</th><th>Aktionen</th></tr></thead>
          <tbody id="courses-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── GLOSSARY ───────────────────────────────────────── -->
    <div id="tab-glossary" class="admin-section">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">Glossar bearbeiten</h2>
        <button class="btn btn-primary" onclick="saveGlossary()">💾 Speichern</button>
      </div>
      <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:12px">
        Format: Jeder Eintrag beginnt mit <code style="color:var(--accent)">## Begriff</code>, darunter die Definition.
      </p>
      <textarea class="form-textarea edit-area" id="glossary-content" style="min-height:400px;font-family:var(--font-mono);font-size:.82rem;width:100%"></textarea>
    </div>

    <!-- ── SETTINGS ───────────────────────────────────────── -->
        <!-- RSS FEEDS -->
    <div id="tab-rss" class="admin-section">
      <div class="admin-header-bar">
        <h2 style="font-size:1rem;font-weight:600">RSS-Feeds verwalten</h2>
        <div style="display:flex;gap:10px">
          <button class="btn btn-primary" onclick="addRssFeed()">+ Feed hinzufügen</button>
          <button class="btn btn-outline" onclick="refreshRssCache()">🔄 Cache aktualisieren</button>
        </div>
      </div>
      <div class="card">
        <div class="card-title"><span class="dot"></span> Aktive RSS-Feeds</div>
        <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:16px">
          Diese Feeds werden automatisch abgerufen und im Bereich "Aktuelles" angezeigt.
        </p>
        <div id="rss-feeds-list" style="display:flex;flex-direction:column;gap:8px"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
          <button class="btn btn-primary" onclick="saveRssFeeds()">💾 Speichern</button>
        </div>
      </div>
    </div>

<div id="tab-settings" class="admin-section">
      <div class="card" style="max-width:480px">
        <div class="card-title"><span class="dot"></span> Admin-Passwort ändern</div>
        <div class="form-group">
          <label class="form-label">Neues Passwort</label>
          <input type="password" class="form-input" id="new-pw" placeholder="Neues Passwort …">
        </div>
        <div class="form-group">
          <label class="form-label">Wiederholen</label>
          <input type="password" class="form-input" id="new-pw2" placeholder="Wiederholen …">
        </div>
        <button class="btn btn-primary" onclick="changePassword()">Passwort ändern</button>
      </div>
    </div>

  </main>
</div><!-- /app -->

<div id="toast-container"></div>

<!-- ══ MODALS ════════════════════════════════════════════════ -->

<!-- Tool Modal -->
<div class="modal-overlay" id="modal-tool">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title" id="modal-tool-title">Neues Tool</span>
      <button class="modal-close" onclick="closeModal('tool')">✕</button>
    </div>
    <input type="hidden" id="tool-file">
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">ID (slug)</label>
        <input type="text" class="form-input" id="tool-id" placeholder="chatgpt">
      </div>
      <div class="form-group">
        <label class="form-label">Icon (Emoji)</label>
        <input type="text" class="form-input" id="tool-icon" placeholder="🤖" maxlength="4">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Name *</label>
      <input type="text" class="form-input" id="tool-name" placeholder="ChatGPT">
    </div>
    <div class="form-group">
      <label class="form-label">Kurzbeschreibung *</label>
      <input type="text" class="form-input" id="tool-desc" placeholder="KI-Chatbot von OpenAI">
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Kategorie</label>
        <select class="form-select" id="tool-category">
          <option>Textgenerierung</option>
          <option>Bildbearbeitung</option>
          <option>Code-Generierung</option>
          <option>Audio/Video</option>
          <option>Sonstiges</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Preismodell</label>
        <select class="form-select" id="tool-price">
          <option value="free">Kostenlos</option>
          <option value="freemium">Freemium</option>
          <option value="paid">Bezahlt</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">URL</label>
      <input type="url" class="form-input" id="tool-url" placeholder="https://chat.openai.com">
    </div>
    <div class="form-group">
      <label class="form-label">Tags (kommagetrennt)</label>
      <input type="text" class="form-input" id="tool-tags" placeholder="Chatbot, Schreiben, Analyse">
    </div>
    <div class="form-group">
      <label class="form-label">Ausführliche Beschreibung</label>
      <textarea class="form-textarea" id="tool-content" rows="4"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-outline" onclick="closeModal('tool')">Abbrechen</button>
      <button class="btn btn-primary" onclick="saveTool()">💾 Speichern</button>
    </div>
  </div>
</div>

<!-- Prompt Modal -->
<div class="modal-overlay" id="modal-prompt">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title">Prompt bearbeiten</span>
      <button class="modal-close" onclick="closeModal('prompt')">✕</button>
    </div>
    <input type="hidden" id="prompt-file">
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">ID (slug)</label>
        <input type="text" class="form-input" id="prompt-id" placeholder="email-schreiben">
      </div>
      <div class="form-group">
        <label class="form-label">Kategorie</label>
        <select class="form-select" id="prompt-category">
          <option>Schreiben</option><option>Code</option><option>Analyse</option>
          <option>Kreativ</option><option>Business</option><option>Bildung</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Titel *</label>
      <input type="text" class="form-input" id="prompt-title" placeholder="Professionelle E-Mail verfassen">
    </div>
    <div class="form-group">
      <label class="form-label">Beschreibung</label>
      <input type="text" class="form-input" id="prompt-desc" placeholder="Kurze Erklärung des Prompts">
    </div>
    <div class="form-group">
      <label class="form-label">Prompt-Text *</label>
      <textarea class="form-textarea" id="prompt-text" rows="6" style="font-family:var(--font-mono);font-size:.82rem" placeholder="Du bist ein professioneller Texter …"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-outline" onclick="closeModal('prompt')">Abbrechen</button>
      <button class="btn btn-primary" onclick="savePrompt()">💾 Speichern</button>
    </div>
  </div>
</div>

<!-- News Modal -->
<div class="modal-overlay" id="modal-news">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title">News-Beitrag</span>
      <button class="modal-close" onclick="closeModal('news')">✕</button>
    </div>
    <input type="hidden" id="news-file">
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">ID (slug)</label>
        <input type="text" class="form-input" id="news-id" placeholder="wird-automatisch-generiert">
      </div>
      <div class="form-group">
        <label class="form-label">Datum</label>
        <input type="date" class="form-input" id="news-date">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Titel *</label>
      <input type="text" class="form-input" id="news-title" placeholder="GPT-5 veröffentlicht">
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Kategorie</label>
        <select class="form-select" id="news-category">
          <option>Modell-Update</option><option>Recht &amp; Politik</option>
          <option>Produkt-Launch</option><option>Forschung</option><option>Sonstiges</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Farb-Marker</label>
        <select class="form-select" id="news-color">
          <option value="">Blau (Standard)</option>
          <option value="orange">Orange</option>
          <option value="green">Grün</option>
          <option value="purple">Lila</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Volltext / Zusammenfassung</label>
      <textarea class="form-textarea" id="news-content" rows="5"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Externer Link (optional)</label>
      <input type="url" class="form-input" id="news-url" placeholder="https://…">
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-outline" onclick="closeModal('news')">Abbrechen</button>
      <button class="btn btn-primary" onclick="saveNews()">💾 Speichern</button>
    </div>
  </div>
</div>

<!-- Course Modal -->
<div class="modal-overlay" id="modal-course">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Kurs bearbeiten</span>
      <button class="modal-close" onclick="closeModal('course')">✕</button>
    </div>
    <input type="hidden" id="course-file">
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">ID (slug)</label>
        <input type="text" class="form-input" id="course-id" placeholder="ki-grundlagen">
      </div>
      <div class="form-group">
        <label class="form-label">Icon (Emoji)</label>
        <input type="text" class="form-input" id="course-icon" placeholder="🤖" maxlength="4">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Titel *</label>
      <input type="text" class="form-input" id="course-title" placeholder="KI-Grundlagen">
    </div>
    <div class="form-group">
      <label class="form-label">Beschreibung</label>
      <input type="text" class="form-input" id="course-desc">
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Anzahl Lektionen</label>
        <input type="number" class="form-input" id="course-lessons" value="5">
      </div>
      <div class="form-group">
        <label class="form-label">Banner-Farbe (CSS)</label>
        <input type="text" class="form-input" id="course-color" placeholder="linear-gradient(135deg,#1e3a5f,#0f2147)">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Inhalte / Lektionen (Markdown)</label>
      <textarea class="form-textarea" id="course-content" rows="6" style="font-family:var(--font-mono);font-size:.82rem"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-outline" onclick="closeModal('course')">Abbrechen</button>
      <button class="btn btn-primary" onclick="saveCourse()">💾 Speichern</button>
    </div>
  </div>
</div>

<script>
// ── TAB NAVIGATION ──────────────────────────────────────
function showTab(name) {
  document.querySelectorAll('.admin-section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.admin-tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+name)?.classList.add('active');
  event.target.classList.add('active');
  loaders[name]?.();
}

const loaders = {
  tools: loadTools,
  prompts: loadPrompts,
  news: loadNews,
  courses: loadCourses,
  glossary: loadGlossary,
  rss: loadRssFeeds,
};

// ── INITIAL LOAD ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadTools();
  // Set today's date in news form
  document.getElementById('news-date').value = new Date().toISOString().split('T')[0];
});

// ── MODALS ───────────────────────────────────────────────
function openModal(type, data = null) {
  clearForm(type);
  if (data) fillForm(type, data);
  document.getElementById('modal-'+type).classList.add('open');
}
function closeModal(type) {
  document.getElementById('modal-'+type).classList.remove('open');
}
// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function clearForm(type) {
  document.querySelectorAll(`#modal-${type} input, #modal-${type} textarea, #modal-${type} select`).forEach(el => {
    if (el.type !== 'hidden') el.value = '';
  });
  if (type === 'news') document.getElementById('news-date').value = new Date().toISOString().split('T')[0];
}

function fillForm(type, data) {
  const fields = Object.keys(data);
  fields.forEach(k => {
    const el = document.getElementById(type + '-' + k);
    if (el) el.value = Array.isArray(data[k]) ? data[k].join(', ') : (data[k] || '');
  });
}

// ── TOOLS ────────────────────────────────────────────────
async function loadTools() {
  const r = await fetch('../api.php?action=tools');
  const d = await r.json();
  const tbody = document.getElementById('tools-tbody');
  if (!tbody) return;
  tbody.innerHTML = (d.tools||[]).map(t => `
    <tr>
      <td>${t.icon||'🤖'}</td>
      <td><strong>${esc(t.name)}</strong></td>
      <td><span class="tag cat">${esc(t.category)}</span></td>
      <td><span class="tag ${t.price}">${esc(t.price)}</span></td>
      <td>${t.url ? `<a href="${esc(t.url)}" target="_blank" style="font-size:.78rem">Link</a>` : '—'}</td>
      <td>
        <button class="btn-icon" onclick='openModal("tool",${JSON.stringify(t)})' title="Bearbeiten">✏️</button>
        <button class="btn-icon" style="margin-left:4px" onclick='deleteTool("${esc(t._file)}")' title="Löschen">🗑️</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="6" style="color:var(--text-muted);text-align:center;padding:20px">Noch keine Tools eingetragen.</td></tr>';
}

async function saveTool() {
  const data = {
    id: document.getElementById('tool-id').value || '',
    name: document.getElementById('tool-name').value,
    icon: document.getElementById('tool-icon').value,
    desc: document.getElementById('tool-desc').value,
    category: document.getElementById('tool-category').value,
    price: document.getElementById('tool-price').value,
    url: document.getElementById('tool-url').value,
    tags: document.getElementById('tool-tags').value.split(',').map(s=>s.trim()).filter(Boolean),
    content: document.getElementById('tool-content').value,
    _file: document.getElementById('tool-file').value,
  };
  if (!data.name) { toast('Name erforderlich','error'); return; }
  const r = await fetch('../api.php?action=admin_save_tool',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const d = await r.json();
  if (d.success) { toast('Tool gespeichert ✓','success'); closeModal('tool'); loadTools(); }
  else toast('Fehler: '+(d.error||''),'error');
}

async function deleteTool(file) {
  if (!confirm('Tool wirklich löschen?')) return;
  const r = await fetch('../api.php?action=admin_delete_tool',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({file})});
  const d = await r.json();
  if (d.success) { toast('Gelöscht','info'); loadTools(); }
  else toast('Fehler','error');
}

// ── PROMPTS ──────────────────────────────────────────────
async function loadPrompts() {
  const r = await fetch('../api.php?action=prompts');
  const d = await r.json();
  const tbody = document.getElementById('prompts-tbody');
  if (!tbody) return;
  tbody.innerHTML = (d.prompts||[]).map(p => `
    <tr>
      <td><strong>${esc(p.title)}</strong></td>
      <td><span class="tag cat">${esc(p.category)}</span></td>
      <td style="color:var(--text-muted);font-size:.8rem">${esc((p.desc||'').substring(0,60))}</td>
      <td>
        <button class="btn-icon" onclick='openModal("prompt",${JSON.stringify(p)})'>✏️</button>
        <button class="btn-icon" style="margin-left:4px" onclick='deletePrompt("${esc(p._file)}")'>🗑️</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="4" style="color:var(--text-muted);text-align:center;padding:20px">Noch keine Prompts.</td></tr>';
}

async function savePrompt() {
  const data = {
    id: document.getElementById('prompt-id').value,
    title: document.getElementById('prompt-title').value,
    category: document.getElementById('prompt-category').value,
    desc: document.getElementById('prompt-desc').value,
    text: document.getElementById('prompt-text').value,
    _file: document.getElementById('prompt-file').value,
  };
  if (!data.title || !data.text) { toast('Titel und Prompt-Text erforderlich','error'); return; }
  const r = await fetch('../api.php?action=admin_save_prompt',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const d = await r.json();
  if (d.success) { toast('Prompt gespeichert ✓','success'); closeModal('prompt'); loadPrompts(); }
  else toast('Fehler','error');
}

async function deletePrompt(file) {
  if (!confirm('Prompt löschen?')) return;
  await fetch('../api.php?action=admin_delete_prompt',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({file})});
  toast('Gelöscht','info'); loadPrompts();
}

// ── NEWS ─────────────────────────────────────────────────
async function loadNews() {
  const r = await fetch('../api.php?action=news');
  const d = await r.json();
  const tbody = document.getElementById('news-tbody');
  if (!tbody) return;
  tbody.innerHTML = (d.news||[]).map(n => `
    <tr>
      <td style="white-space:nowrap">${esc(n.date)}</td>
      <td><strong>${esc(n.title)}</strong></td>
      <td><span class="news-category">${esc(n.category)}</span></td>
      <td>
        <button class="btn-icon" onclick='openModal("news",${JSON.stringify(n)})'>✏️</button>
        <button class="btn-icon" style="margin-left:4px" onclick='deleteNews("${esc(n._file)}")'>🗑️</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="4" style="color:var(--text-muted);text-align:center;padding:20px">Keine News.</td></tr>';
}

async function saveNews() {
  const data = {
    id: document.getElementById('news-id').value,
    title: document.getElementById('news-title').value,
    date: document.getElementById('news-date').value,
    category: document.getElementById('news-category').value,
    color: document.getElementById('news-color').value,
    url: document.getElementById('news-url').value,
    content: document.getElementById('news-content').value,
    _file: document.getElementById('news-file').value,
  };
  if (!data.title) { toast('Titel erforderlich','error'); return; }
  const r = await fetch('../api.php?action=admin_save_news',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const d = await r.json();
  if (d.success) { toast('News gespeichert ✓','success'); closeModal('news'); loadNews(); }
  else toast('Fehler','error');
}

async function deleteNews(file) {
  if (!confirm('Beitrag löschen?')) return;
  await fetch('../api.php?action=admin_delete_news',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({file})});
  toast('Gelöscht','info'); loadNews();
}

// ── COURSES ──────────────────────────────────────────────
async function loadCourses() {
  const r = await fetch('../api.php?action=courses');
  const d = await r.json();
  const tbody = document.getElementById('courses-tbody');
  if (!tbody) return;
  tbody.innerHTML = (d.courses||[]).map(c => `
    <tr>
      <td>${c.icon||'📚'}</td>
      <td><strong>${esc(c.title)}</strong></td>
      <td>${esc(c.lessons||0)}</td>
      <td><button class="btn-icon" onclick='openModal("course",${JSON.stringify(c)})'>✏️</button></td>
    </tr>`).join('') || '<tr><td colspan="4" style="color:var(--text-muted);text-align:center;padding:20px">Keine Kurse.</td></tr>';
}

async function saveCourse() {
  const data = {
    id: document.getElementById('course-id').value,
    title: document.getElementById('course-title').value,
    icon: document.getElementById('course-icon').value,
    desc: document.getElementById('course-desc').value,
    lessons: document.getElementById('course-lessons').value,
    color: document.getElementById('course-color').value,
    content: document.getElementById('course-content').value,
    _file: document.getElementById('course-file').value,
  };
  if (!data.title) { toast('Titel erforderlich','error'); return; }
  const r = await fetch('../api.php?action=admin_save_course',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const d = await r.json();
  if (d.success) { toast('Kurs gespeichert ✓','success'); closeModal('course'); loadCourses(); }
  else toast('Fehler','error');
}

// ── GLOSSARY ──────────────────────────────────────────────
async function loadGlossary() {
  const r = await fetch('../api.php?action=courses');
  const d = await r.json();
  // Load raw file via settings endpoint
  const r2 = await fetch('../api.php?action=admin_glossary_raw');
  // Fallback: load via courses endpoint which returns parsed; we'll reconstruct
  const items = d.glossary || [];
  const content = items.map(g => `## ${g.term}\n${g.def}`).join('\n\n');
  document.getElementById('glossary-content').value = content;
}

async function saveGlossary() {
  const content = document.getElementById('glossary-content').value;
  const r = await fetch('../api.php?action=admin_save_glossary',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({content})
  });
  const d = await r.json();
  if (d.success) toast('Glossar gespeichert ✓','success');
  else toast('Fehler','error');
}

// ── PASSWORD CHANGE ────────────────────────────────────────
async function changePassword() {
  const pw1 = document.getElementById('new-pw').value;
  const pw2 = document.getElementById('new-pw2').value;
  if (!pw1) { toast('Passwort eingeben','error'); return; }
  if (pw1 !== pw2) { toast('Passwörter stimmen nicht überein','error'); return; }
  const r = await fetch('../api.php?action=admin_change_pw',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({password: pw1})
  });
  const d = await r.json();
  if (d.success) toast('Passwort geändert ✓','success');
  else toast('Fehler: '+(d.error||''),'error');
}

async function adminLogout() {
  await fetch('../api.php?action=admin_logout',{method:'POST'});
  location.href='../';
}

// ── TOAST ──────────────────────────────────────────────────
function toast(msg, type='info') {
  const c = document.getElementById('toast-container');
  const icons = {success:'✅',error:'❌',info:'ℹ️'};
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transform='translateX(100%)';t.style.transition='.3s';setTimeout(()=>t.remove(),300);},3000);
}

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// RSS FEEDS
let rssFeeds = [];

async function loadRssFeeds() {
  const r = await fetch('../api.php?action=rss_feeds');
  const d = await r.json();
  rssFeeds = d.feeds || [];
  renderRssFeeds();
}

function renderRssFeeds() {
  const el = document.getElementById('rss-feeds-list');
  if (!el) return;
  el.innerHTML = rssFeeds.map((feed, idx) => `
    <div class="rss-feed-item">
      <div style="flex:1">
        <div style="font-weight:600">${esc(feed.title || 'Unbenannter Feed')}</div>
        <div style="font-size:.8rem;color:var(--text-muted);word-break:break-all">${esc(feed.url)}</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="checkbox" ${feed.enabled ? 'checked' : ''} 
                 onchange="rssFeeds[${idx}].enabled = this.checked; renderRssFeeds()">
          <span style="font-size:.8rem">Aktiv</span>
        </label>
        <button class="btn-icon" onclick="editRssFeed(${idx})" title="Bearbeiten">✏️</button>
        <button class="btn-icon" onclick="removeRssFeed(${idx})" title="Entfernen">🗑️</button>
      </div>
    </div>`).join('');
}

function addRssFeed() {
  rssFeeds.push({ url: '', title: 'Neuer Feed', enabled: true });
  renderRssFeeds();
}

function editRssFeed(idx) {
  const feed = rssFeeds[idx];
  const url = prompt('Feed-URL:', feed.url);
  if (url !== null) {
    const title = prompt('Feed-Titel:', feed.title);
    if (title !== null) {
      rssFeeds[idx] = { url, title, enabled: feed.enabled };
      renderRssFeeds();
    }
  }
}

function removeRssFeed(idx) {
  if (confirm('Feed wirklich entfernen?')) {
    rssFeeds.splice(idx, 1);
    renderRssFeeds();
  }
}

async function saveRssFeeds() {
  const r = await fetch('../api.php?action=admin_save_rss_feeds', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ feeds: rssFeeds })
  });
  const d = await r.json();
  if (d.success) {
    toast('RSS-Feeds gespeichert ✅', 'success');
  } else {
    toast('Fehler beim Speichern', 'error');
  }
}

async function refreshRssCache() {
  const r = await fetch('../api.php?action=admin_refresh_rss', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  });
  const d = await r.json();
  if (d.success) {
    toast(`Cache aktualisiert - ${d.items || 0} Nachrichten geladen`, 'success');
  } else {
    toast('Fehler beim Aktualisieren', 'error');
  }
}


// RSS FEEDS
let rssFeeds = [];

async function saveRssFeeds() {
  const r = await fetch('../api.php?action=admin_save_rss_feeds', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ feeds: rssFeeds })
  });
  const d = await r.json();
  if (d.success) {
    toast('RSS-Feeds gespeichert ✅', 'success');
  } else {
    toast('Fehler beim Speichern', 'error');
  }
}

async function refreshRssCache() {
  const r = await fetch('../api.php?action=admin_refresh_rss', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  });
  const d = await r.json();
  if (d.success) {
    toast(`Cache aktualisiert - ${d.items || 0} Nachrichten geladen`, 'success');
  } else {
    toast('Fehler beim Aktualisieren', 'error');
  }
}

</script>
</body>
</html>
