/* ============================================================
   KI-DASHBOARD – Frontend App Logic
   ============================================================ */

const App = {
  state: {
    theme: localStorage.getItem('ki_theme') || 'dark',
    sidebarCollapsed: localStorage.getItem('ki_sidebar') === '1',
    favorites: JSON.parse(localStorage.getItem('ki_favorites') || '[]'),
    progress: JSON.parse(localStorage.getItem('ki_progress') || '{}'),
    apiKey: localStorage.getItem('ki_api_key') || '',
    apiModel: localStorage.getItem('ki_api_model') || 'gpt-3.5-turbo',
    currentPage: 'dashboard',
    currentFilter: { cat: 'all', price: 'all', search: '' },
    promptFilter: 'all',
  },

  // ███ INIT ██████████████████████████████████████████████████████████████████████████████████████████████████████████
  init() {
    this.applyTheme();
    this.applySidebar();
    this.bindNav();
    this.bindHeader();
    this.bindMobile();
    this.loadPage('dashboard');
    this.initTicker();
    this.checkAuth();
  },

  checkAuth() {
    // Check if logged in (session cookie set by PHP)
    const loggedIn = document.body.dataset.loggedin === '1';
    const navAdmin = document.getElementById('nav-admin');
    if (navAdmin) navAdmin.style.display = loggedIn ? 'flex' : 'none';
  },

  // ███ THEME ███████████████████████████████████████████████████████████████████████████████████████████
  applyTheme() {
    document.documentElement.setAttribute('data-theme', this.state.theme);
    const icon = document.getElementById('theme-icon');
    if (icon) icon.textContent = this.state.theme === 'dark' ? '\u2600\ufe0f' : '\ud83c\udf19';
  },

  toggleTheme() {
    this.state.theme = this.state.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('ki_theme', this.state.theme);
    this.applyTheme();
  },

  // ███ SIDEBAR ██████████████████████████████████████████████████████████████████████████████████████
  applySidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    if (this.state.sidebarCollapsed) sidebar.classList.add('collapsed');
    else sidebar.classList.remove('collapsed');
  },

  toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle('mobile-open');
      // Close sidebar when clicking outside on mobile
      if (sidebar.classList.contains('mobile-open')) {
        this.bindSidebarBackdrop();
      }
    } else {
      this.state.sidebarCollapsed = !this.state.sidebarCollapsed;
      localStorage.setItem('ki_sidebar', this.state.sidebarCollapsed ? '1' : '0');
      this.applySidebar();
    }
  },

  bindSidebarBackdrop() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar || window.innerWidth > 768) return;
    
    // Close sidebar when clicking on main content
    const main = document.getElementById('main');
    if (main) {
      main.onclick = () => {
        if (sidebar.classList.contains('mobile-open')) {
          sidebar.classList.remove('mobile-open');
        }
      };
    }
  },

  // ███ MOBILE ENHANCEMENTS █████████████████████████████████████████████████████████████████
  bindMobile() {
    // Add touch-friendly behaviors
    this.bindTouchNavigation();
    this.bindSwipeGestures();
    this.handleWindowResize();
  },

  bindTouchNavigation() {
    // Make nav items more touch-friendly
    document.querySelectorAll('.nav-item[data-page]').forEach(el => {
      el.addEventListener('touchstart', () => {
        // Add active state for touch feedback
        el.classList.add('touch-active');
      });
      el.addEventListener('touchend', () => {
        el.classList.remove('touch-active');
      });
      el.addEventListener('touchcancel', () => {
        el.classList.remove('touch-active');
      });
    });

    // Make buttons more touch-friendly
    document.querySelectorAll('.btn, .btn-icon, .filter-btn, .tool-fav, .btn-copy, .btn-send').forEach(el => {
      el.addEventListener('touchstart', () => {
        el.classList.add('touch-active');
      });
      el.addEventListener('touchend', () => {
        el.classList.remove('touch-active');
      });
      el.addEventListener('touchcancel', () => {
        el.classList.remove('touch-active');
      });
    });
  },

  bindSwipeGestures() {
    let touchStartX = 0;
    let touchStartY = 0;
    const sidebar = document.getElementById('sidebar');
    
    if (!sidebar) return;

    document.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
      touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', (e) => {
      if (!touchStartX || !touchStartY) return;
      
      const touchEndX = e.changedTouches[0].screenX;
      const touchEndY = e.changedTouches[0].screenY;
      const diffX = touchStartX - touchEndX;
      const diffY = touchStartY - touchEndY;
      
      // Only handle horizontal swipes
      if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
        if (diffX > 0) {
          // Swipe left to right - open sidebar
          if (window.innerWidth <= 768 && touchStartX < 50) {
            sidebar.classList.add('mobile-open');
          }
        } else {
          // Swipe right to left - close sidebar
          if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
          }
        }
      }
      
      touchStartX = 0;
      touchStartY = 0;
    }, { passive: true });
  },

  handleWindowResize() {
    // Close sidebar on window resize to desktop
    window.addEventListener('resize', () => {
      const sidebar = document.getElementById('sidebar');
      if (sidebar && window.innerWidth > 768) {
        sidebar.classList.remove('mobile-open');
      }
    });
  },

  // ███ NAVIGATION ███████████████████████████████████████████████████████████████████████████████
  bindNav() {
    document.querySelectorAll('.nav-item[data-page]').forEach(el => {
      el.addEventListener('click', () => {
        const page = el.dataset.page;
        this.loadPage(page);
        // close mobile sidebar
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.remove('mobile-open');
      });
    });
  },

  loadPage(page) {
    this.state.currentPage = page;
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const pageEl = document.getElementById(`page-${page}`);
    const navEl = document.querySelector(`.nav-item[data-page="${page}"]`);
    if (pageEl) pageEl.classList.add('active');
    if (navEl) navEl.classList.add('active');

    // lazy load page data
    const loaders = {
      dashboard: () => this.loadDashboard(),
      tools: () => this.loadTools(),
      prompts: () => this.loadPrompts(),
      learn: () => this.loadLearn(),
      news: () => this.loadNews(),
      favorites: () => this.loadFavorites(),
    };
    if (loaders[page]) loaders[page]();
  },

  // ███ HEADER ██████████████████████████████████████████████████████████████████████████████
  bindHeader() {
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
      searchInput.addEventListener('input', e => {
        const q = e.target.value.trim();
        if (q.length > 1) this.globalSearch(q);
      });
      searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          const q = e.target.value.trim();
          if (q) {
            this.loadPage('tools');
            setTimeout(() => { this.state.currentFilter.search = q; this.renderTools(); }, 100);
          }
        }
      });
    }
  },

  globalSearch(q) {
    // Simple global search hint – navigate to tools with filter
  },

  // ███ TICKER ██████████████████████████████████████████████████████████████████████████████████████
  initTicker() {
    fetch('api.php?action=ticker')
      .then(r => r.json())
      .then(data => {
        const el = document.getElementById('ticker-inner');
        if (!el || !data.items) return;
        const html = data.items.map(t => `<span class="ticker-item">${t}</span>`).join('');
        el.innerHTML = html + html; // duplicate for seamless loop
      })
      .catch(() => {});
  },

  // ███ DASHBOARD ██████████████████████████████████████████████████████████████████████████████
  loadDashboard() {
    fetch('api.php?action=dashboard')
      .then(r => r.json())
      .then(data => {
        this.renderStats(data.stats);
        this.renderRecentNews(data.news);
        this.renderFavoriteWidgets();
      })
      .catch(() => {});
  },

  renderStats(stats) {
    if (!stats) return;
    const map = {
      'stat-tools': stats.tools,
      'stat-prompts': stats.prompts,
      'stat-courses': stats.courses,
      'stat-news': stats.news,
    };
    Object.entries(map).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val ?? '\u2014';
    });
  },

  renderRecentNews(items) {
    const el = document.getElementById('recent-news');
    if (!el || !items) return;
    if (!items.length) { el.innerHTML = '<p style="color:var(--text-muted);font-size:.82rem">Keine News vorhanden.</p>'; return; }
    el.innerHTML = items.slice(0, 5).map(n => `
      <div class="news-item" onclick="${n.url ? `window.open('${esc(n.url)}', '_blank')` : 'App.loadPage(\'news\')'}" style="cursor:pointer">
        <div class="news-dot ${n.color || ''}"></div>
        <div>
          <div class="news-title">${esc(n.title)}</div>
          <div class="news-meta">
            <span class="news-category">${esc(n.source || n.category || 'RSS')}</span>
            <span>${formatDate(n.date)}</span>
          </div>
        </div>
      </div>`).join('');
  },

  renderFavoriteWidgets() {
    const el = document.getElementById('dash-favorites');
    if (!el) return;
    const favs = this.state.favorites;
    if (!favs.length) {
      el.innerHTML = '<div class="empty-state"><div class="icon">\u2b50</div>Noch keine Favoriten.<br>Markiere Tools oder Prompts als Favorit.</div>';
      return;
    }
    el.innerHTML = favs.slice(0,4).map(f => `
      <span class="tag" style="cursor:pointer;padding:5px 10px" onclick="App.loadPage('${f.type === 'tool' ? 'tools' : 'prompts'}')">
        ${f.type === 'tool' ? '\ud83d\udd27' : '\ud83d\udcdd'} ${esc(f.name)}
      </span>`).join('');
  },

  // ███ TOOLS ████████████████████████████████████████████████████████████████████████████████████
  _toolsData: null,

  loadTools() {
    if (this._toolsData) { this.renderTools(); return; }
    fetch('api.php?action=tools')
      .then(r => r.json())
      .then(data => { this._toolsData = data.tools || []; this.renderTools(); })
      .catch(() => { document.getElementById('tools-grid').innerHTML = '<p style="color:var(--text-muted)">Fehler beim Laden.</p>'; });
  },

  renderTools() {
    const grid = document.getElementById('tools-grid');
    if (!grid) return;
    let tools = this._toolsData || [];
    const { cat, price, search } = this.state.currentFilter;
    if (cat !== 'all') tools = tools.filter(t => t.category === cat);
    if (price !== 'all') tools = tools.filter(t => t.price === price);
    if (search) {
      const q = search.toLowerCase();
      tools = tools.filter(t => t.name.toLowerCase().includes(q) || t.desc.toLowerCase().includes(q) || (t.tags||[]).some(tag => tag.toLowerCase().includes(q)));
    }
    if (!tools.length) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="icon">\ud83d\udd0d</div>Keine Tools gefunden.</div>';
      return;
    }
    const favIds = this.state.favorites.filter(f=>f.type==='tool').map(f=>f.id);
    grid.innerHTML = tools.map(t => `
      <div class="tool-card" id="tool-${t.id}">
        <div class="tool-card-header">
          <div class="tool-icon">${t.icon || '\ud83e\udd16'}</div>
          <button class="tool-fav ${favIds.includes(t.id)?'active':''}" onclick="App.toggleFav('tool','${t.id}','${esc(t.name)}',this)" title="Favorit">\u2605</button>
        </div>
        <div class="tool-name">${esc(t.name)}</div>
        <div class="tool-desc">${esc(t.desc)}</div>
        <div class="tool-tags">
          <span class="tag cat">${esc(t.category)}</span>
          <span class="tag ${t.price}">${esc(t.price)}</span>
          ${(t.tags||[]).map(tag=>`<span class="tag">${esc(tag)}</span>`).join('')}
        </div>
        ${t.url ? `<div><a href="${esc(t.url)}" target="_blank" rel="noopener" class="tool-visit">\u00d6ffnen \u2192</a></div>` : ''}
      </div>`).join('');
  },

  setToolFilter(type, val) {
    this.state.currentFilter[type] = val;
    document.querySelectorAll(`[data-filter="${type}"]`).forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-filter="${type}"][data-val="${val}"]`)?.classList.add('active');
    this.renderTools();
  },

  searchTools(q) {
    this.state.currentFilter.search = q;
    this.renderTools();
  },

  // ███ PROMPTS ████████████████████████████████████████████████████████████████████████████
  _promptsData: null,

  loadPrompts() {
    if (this._promptsData) { this.renderPrompts(); return; }
    fetch('api.php?action=prompts')
      .then(r => r.json())
      .then(data => { this._promptsData = data.prompts || []; this.renderPrompts(); })
      .catch(() => {});
  },

  renderPrompts() {
    const grid = document.getElementById('prompts-grid');
    if (!grid) return;
    let prompts = this._promptsData || [];
    if (this.state.promptFilter !== 'all') prompts = prompts.filter(p => p.category === this.state.promptFilter);
    const favIds = this.state.favorites.filter(f=>f.type==='prompt').map(f=>f.id);
    if (!prompts.length) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="icon">\ud83d\udcdd</div>Keine Prompts gefunden.</div>';
      return;
    }
    grid.innerHTML = prompts.map(p => `
      <div class="prompt-card">
        <div class="prompt-header">
          <div>
            <div class="prompt-title">${esc(p.title)}</div>
            <span class="tag cat" style="margin-top:4px;display:inline-block">${esc(p.category)}</span>
          </div>
          <button class="tool-fav ${favIds.includes(p.id)?'active':''}" onclick="App.toggleFav('prompt','${p.id}','${esc(p.title)}',this)" title="Favorit">\u2605</button>
        </div>
        <pre class="prompt-text">${esc(p.text)}</pre>
        <div style="display:flex;gap:8px;align-items:center">
          <button class="btn-copy" onclick="App.copyPrompt(this,'${p.id}')"><span>\ud83d\udccb</span> Kopieren</button>
          <span style="font-size:.75rem;color:var(--text-muted)">${esc(p.desc||'')}</span>
        </div>
      </div>`).join('');
  },

  copyPrompt(btn, id) {
    const prompt = (this._promptsData||[]).find(p=>p.id===id);
    if (!prompt) return;
    navigator.clipboard.writeText(prompt.text).then(() => {
      btn.classList.add('copied');
      btn.innerHTML = '<span>\u2705</span> Kopiert!';
      setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<span>\ud83d\udccb</span> Kopieren'; }, 2000);
    });
  },

  setPromptFilter(cat) {
    this.state.promptFilter = cat;
    document.querySelectorAll('.prompt-filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`.prompt-filter-btn[data-cat="${cat}"]`)?.classList.add('active');
    this.renderPrompts();
  },

  // ███ LEARN ████████████████████████████████████████████████████████████████████████████████
  _learnData: null,

  loadLearn() {
    if (this._learnData) return;
    fetch('api.php?action=courses')
      .then(r => r.json())
      .then(data => { this._learnData = data; this.renderCourses(data.courses||[]); this.renderGlossary(data.glossary||[]); })
      .catch(() => {});
  },

  renderCourses(courses) {
    const grid = document.getElementById('courses-grid');
    if (!grid) return;
    grid.innerHTML = courses.map(c => {
      const prog = this.state.progress[c.id] || 0;
      return `<div class="course-card" onclick="App.openCourse('${c.id}')">
        <div class="course-banner" style="background:${c.color||'linear-gradient(135deg,#1e3a5f,#0f2147)'}">
          <span>${c.icon||'\ud83d\udcda'}</span>
        </div>
        <div class="course-body">
          <div class="course-title">${esc(c.title)}</div>
          <div class="course-desc">${esc(c.desc)}</div>
          <div class="progress-bar-wrap"><div class="progress-bar" style="width:${prog}%"></div></div>
          <div class="progress-label">${prog}% abgeschlossen \u00b7 ${c.lessons||0} Lektionen</div>
        </div>
      </div>`;
    }).join('');
  },

  renderGlossary(items) {
    const el = document.getElementById('glossary-list');
    if (!el) return;
    if (!items.length) { el.innerHTML = '<p style="color:var(--text-muted);font-size:.82rem">Kein Glossar vorhanden.</p>'; return; }
    el.innerHTML = items.map(g => `
      <div class="glossary-item">
        <div class="glossary-term">${esc(g.term)}</div>
        <div class="glossary-def">${esc(g.def)}</div>
      </div>`).join('');
  },

  openCourse(id) {
    // Mark as started (25%) if not started
    if (!this.state.progress[id]) {
      this.state.progress[id] = 10;
      localStorage.setItem('ki_progress', JSON.stringify(this.state.progress));
    }
    App.showToast('Kurs ge\u00f6ffnet \u2013 Funktion wird ausgebaut!', 'info');
  },

  // ███ NEWS ███████████████████████████████████████████████████████████████████████████████████
  _newsData: null,

  loadNews() {
    if (this._newsData) { this.renderNewsFull(this._newsData); return; }
    fetch('api.php?action=rss_news')
      .then(r => r.json())
      .then(data => { this._newsData = data.items||[]; this.renderNewsFull(this._newsData); })
      .catch(() => {});
  },

  renderNewsFull(items) {
    const el = document.getElementById('news-list');
    if (!el) return;
    if (!items.length) { el.innerHTML = '<p style="color:var(--text-muted)">Keine News vorhanden.</p>'; return; }
    el.innerHTML = items.map(n => `
      <div class="card" style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px">
          <div>
            <span class="news-category">${esc(n.category)}</span>
            <h3 style="font-size:.95rem;font-weight:600;margin-top:6px">${esc(n.title)}</h3>
          </div>
          <span style="font-size:.75rem;color:var(--text-muted);white-space:nowrap">${esc(n.date)}</span>
        </div>
        <p style="font-size:.83rem;color:var(--text-secondary);line-height:1.6">${esc(n.content||n.excerpt||'')}</p>
        ${n.url ? `<a href="${esc(n.url)}" target="_blank" rel="noopener" class="tool-visit" style="margin-top:8px;display:inline-flex">Mehr lesen \u2192</a>` : ''}
      </div>`).join('');
  },

  // ███ FAVORITES ██████████████████████████████████████████████████████████████████████
  loadFavorites() {
    const el = document.getElementById('fav-list');
    if (!el) return;
    const favs = this.state.favorites;
    if (!favs.length) {
      el.innerHTML = '<div class="empty-state"><div class="icon">\u2b50</div>Noch keine Favoriten gespeichert.<br>Klicke bei Tools oder Prompts auf das \u2605-Symbol.</div>';
      return;
    }
    el.innerHTML = favs.map(f => `
      <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:1.2rem">${f.type==='tool'?'\ud83d\udd27':'\ud83d\udcdd'}</span>
          <div>
            <div style="font-weight:600;font-size:.9rem">${esc(f.name)}</div>
            <div style="font-size:.75rem;color:var(--text-muted)">${f.type==='tool'?'Tool':'Prompt'}</div>
          </div>
        </div>
        <button class="btn-icon" onclick="App.removeFav('${f.id}')" title="Entfernen">\u2715</button>
      </div>`).join('');
  },

  toggleFav(type, id, name, btn) {
    const idx = this.state.favorites.findIndex(f => f.id === id && f.type === type);
    if (idx >= 0) {
      this.state.favorites.splice(idx, 1);
      btn.classList.remove('active');
      App.showToast('Aus Favoriten entfernt', 'info');
    } else {
      this.state.favorites.push({ type, id, name });
      btn.classList.add('active');
      App.showToast(`"${name}" zu Favoriten hinzugef\u00fcgt`, 'success');
    }
    localStorage.setItem('ki_favorites', JSON.stringify(this.state.favorites));
    this.renderFavoriteWidgets();
  },

  removeFav(id) {
    this.state.favorites = this.state.favorites.filter(f => f.id !== id);
    localStorage.setItem('ki_favorites', JSON.stringify(this.state.favorites));
    this.loadFavorites();
    this.renderFavoriteWidgets();
  },

  // ███ AI WIDGET █████████████████████████████████████████████████████████████████████████
  saveApiKey() {
    const key = document.getElementById('ai-api-key')?.value.trim();
    const model = document.getElementById('ai-model')?.value;
    if (!key) { App.showToast('Bitte API-Key eingeben', 'error'); return; }
    this.state.apiKey = key;
    this.state.apiModel = model;
    localStorage.setItem('ki_api_key', key);
    localStorage.setItem('ki_api_model', model);
    App.showToast('API-Key gespeichert \u2713', 'success');
  },

  async sendAiMessage() {
    const input = document.getElementById('ai-input');
    const msgs = document.getElementById('ai-messages');
    if (!input || !msgs) return;
    const text = input.value.trim();
    if (!text) return;
    if (!this.state.apiKey) { App.showToast('Bitte zuerst API-Key speichern', 'error'); return; }

    input.value = '';
    msgs.innerHTML += `<div class="msg user">${esc(text)}</div>`;
    msgs.innerHTML += `<div class="msg bot" id="bot-typing"><div class="msg-label">KI</div><span style="opacity:.5">Denkt nach\u2026</span></div>`;
    msgs.scrollTop = msgs.scrollHeight;

    try {
      const res = await fetch('api.php?action=ai_query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, apiKey: this.state.apiKey, model: this.state.apiModel })
      });
      const data = await res.json();
      const typing = document.getElementById('bot-typing');
      if (typing) typing.outerHTML = `<div class="msg bot"><div class="msg-label">KI</div>${esc(data.reply || data.error || 'Keine Antwort')}</div>`;
    } catch (e) {
      const typing = document.getElementById('bot-typing');
      if (typing) typing.outerHTML = `<div class="msg bot"><div class="msg-label">KI</div>Fehler: ${esc(e.message)}</div>`;
    }
    msgs.scrollTop = msgs.scrollHeight;
  },

  // ███ TOAST █████████████████████████████████████████████████████████████████████████
  showToast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const icons = { success: '\u2705', error: '\u274c', info: '\u2139\ufe0f' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type]||'\u2139\ufe0f'}</span><span>${esc(msg)}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity='0'; toast.style.transform='translateX(100%)'; toast.style.transition='.3s'; setTimeout(()=>toast.remove(),300); }, 3500);
  },
};

// █████████████████████████████████████████████████████████████████████████████████████████████████
// HELPERS
// █████████████████████████████████████████████████████████████████████████████████████████████████████
function formatDate(dateStr) {
  if (!dateStr) return '';
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch (e) {
    return dateStr;
  }
}

function esc(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/\"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

// ██████████████████████████████████████████████████████████████████████████████████████████████████████
// BOOT
// █████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
document.addEventListener('DOMContentLoaded', () => App.init());
