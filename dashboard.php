<?php
require_once __DIR__ . '/../../../.configs/config.php';

$secret = $_GET['secret'] ?? '';
if ($secret !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<style>
  :root{--bg:#1a1d24;--panel:#23272f;--panel2:#2b303a;--line:#363c47;--text:#e8e6e1;--muted:#8b8f98;--amber:#e8a33d;--teal:#4fb0a5;--red:#d9695f;--violet:#8b7fd6;}
  *{box-sizing:border-box;}
  body{margin:0;background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,sans-serif;}
  .wrap{max-width:820px;margin:0 auto;padding:24px 16px 70px;}
  h1{font-size:22px;margin:0 0 4px;}
  .sub{color:var(--muted);font-size:13px;margin-bottom:18px;}
  .tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;position:sticky;top:0;background:var(--bg);padding:8px 0;z-index:5;}
  .tab{background:var(--panel2);border:1px solid var(--line);color:var(--muted);border-radius:6px;padding:8px 14px;font-size:13px;cursor:pointer;font-weight:600;}
  .tab.active{background:var(--amber);color:#1a1d24;border-color:var(--amber);}
  .panel{display:none;}
  .panel.active{display:block;}
  select,input,textarea{background:var(--panel2);border:1px solid var(--line);color:var(--text);border-radius:6px;padding:8px 10px;font-family:inherit;font-size:14px;width:100%;}
  select:focus,input:focus,textarea:focus{outline:1px solid var(--amber);}
  .btn{background:var(--amber);color:#1a1d24;border:none;border-radius:6px;padding:8px 14px;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap;}
  .btn.ghost{background:transparent;border:1px solid var(--line);color:var(--text);font-weight:500;}
  .btn.danger{background:var(--red);}
  .btn.teal{background:var(--teal);}
  .btn.small{padding:5px 9px;font-size:12px;}
  .toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;}
  .toolbar select{width:auto;}
  .drag-handle{cursor:grab;color:var(--muted);padding:0 4px;flex:none;}
  .card.dragging{opacity:.4;}
  .settings-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:12px;color:var(--muted);margin-bottom:14px;}
  .settings-row select{width:auto;font-size:12px;padding:5px 8px;}
  .spacer{flex:1;}
  .card{background:var(--panel);border:1px solid var(--line);border-left:4px solid var(--muted);border-radius:8px;padding:0;margin-bottom:10px;overflow:hidden;}
  .card.idee{border-left-color:var(--violet);}
  .card.arbeit{border-left-color:var(--amber);}
  .card.fertig{border-left-color:var(--teal);}
  .card-header{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:14px 16px;cursor:pointer;}
  .card-header:hover{background:var(--panel2);}
  .card-header-left{display:flex;align-items:center;gap:10px;min-width:0;}
  .chevron{color:var(--muted);font-size:12px;transition:transform .15s;flex:none;}
  .card.open .chevron{transform:rotate(90deg);}
  .card-title-view{font-weight:600;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .card-preview{color:var(--muted);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .card-body{display:none;padding:0 16px 16px;}
  .card.open .card-body{display:block;}
  .card-title-edit{font-weight:600;font-size:15px;background:var(--panel2);border:1px solid var(--line);border-radius:6px;padding:6px 8px;margin-bottom:8px;width:100%;}
  .tag{font-size:11px;color:var(--muted);border:1px solid var(--line);border-radius:4px;padding:2px 6px;white-space:nowrap;}
  .row{display:flex;gap:6px;margin-top:8px;}
  .row input,.row select{font-size:13px;padding:6px 8px;}
  .steps,.links{margin-top:10px;display:flex;flex-direction:column;gap:5px;}
  .step,.link-row{display:flex;align-items:center;gap:8px;font-size:13px;}
  .step.done span{color:var(--muted);text-decoration:line-through;}
  .step input[type=checkbox]{accent-color:var(--teal);width:15px;height:15px;flex:none;}
  .step span,.link-row a{flex:1;}
  .link-row a{color:var(--teal);word-break:break-all;}
  .step button,.link-row button{background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;}
  .add-row{display:flex;gap:6px;margin-top:6px;}
  .add-row input{flex:1;font-size:12px;padding:6px 8px;}
  .notes{width:100%;background:var(--panel2);border:1px solid var(--line);border-radius:6px;padding:8px;font-size:13px;color:var(--muted);margin-top:8px;min-height:40px;}
  .chatlink{display:inline-flex;gap:5px;font-size:12px;color:var(--teal);text-decoration:none;border:1px solid var(--line);border-radius:6px;padding:4px 8px;margin-top:8px;}
  .item{background:var(--panel2);border:1px solid var(--line);border-radius:6px;padding:10px 12px;margin-bottom:6px;font-size:14px;}
  .item .actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;}
  .empty{color:var(--muted);font-size:13px;padding:10px 0;}
  .count{color:var(--muted);font-weight:400;font-size:13px;}
</style>
</head>
<body>
<div class="wrap">
  <h1>📋 Dashboard</h1>
  <div class="sub" id="statusLine">Lädt…</div>

  <div class="toolbar" style="margin-bottom:14px;">
    <button class="btn ghost" id="deployBtn">🚀 Deploy</button>
    <span id="deployStatus" style="font-size:12px;color:var(--muted);"></span>
  </div>

  <div class="settings-row">
    ⚙️ Dashboard startet mit:
    <select id="startTabSetting">
      <option value="projects">Projekte</option>
      <option value="inbox">Inbox</option>
      <option value="news">News</option>
      <option value="links">Links</option>
    </select>
  </div>

  <div class="tabs">
    <div class="tab" data-tab="projects">Projekte</div>
    <div class="tab" data-tab="inbox">Inbox</div>
    <div class="tab" data-tab="news">📰 News</div>
    <div class="tab" data-tab="links">🔗 Links</div>
  </div>

  <div class="panel" id="panel-projects">
    <div class="toolbar">
      <select id="filterStatus">
        <option value="alle">Alle Status</option>
        <option value="idee">Idee</option>
        <option value="arbeit">In Arbeit</option>
        <option value="pausiert">Pausiert</option>
        <option value="fertig">Abgeschlossen</option>
      </select>
      <select id="sortMode">
        <option value="manual">Sortierung: Manuell</option>
        <option value="alpha">Sortierung: Alphabetisch</option>
        <option value="chrono">Sortierung: Chronologisch (neueste zuerst)</option>
      </select>
      <span class="spacer"></span>
      <button class="btn" id="addProjectBtn">+ Neues Projekt</button>
    </div>
    <div id="projectsBoard"></div>
  </div>

  <div class="panel" id="panel-inbox">
    <div class="add-row" style="margin-bottom:14px;">
      <input id="newInboxInput" placeholder="Link oder Idee eingeben…"/>
      <button class="btn" id="newInboxBtn">+ Hinzufügen (KI ordnet ein)</button>
    </div>
    <div id="inboxBoard"></div>
  </div>

  <div class="panel" id="panel-news">
    <div class="add-row" style="margin-bottom:14px;">
      <input id="newNewsInput" placeholder="News-Eintrag…"/>
      <button class="btn" id="newNewsBtn">+ Hinzufügen</button>
    </div>
    <div id="newsBoard"></div>
  </div>

  <div class="panel" id="panel-links">
    <div class="add-row" style="margin-bottom:14px;">
      <input id="newLinkInput" placeholder="Link…"/>
      <button class="btn" id="newLinkBtn">+ Hinzufügen</button>
    </div>
    <div id="linksBoard"></div>
  </div>
</div>

<script>
const SECRET = <?= json_encode($secret) ?>;
const API = 'api.php';
let store = { projects: [], inbox: [], news: [], saved_links: [] };
const statusLabel = { idee: 'Idee', arbeit: 'In Arbeit', pausiert: 'Pausiert', fertig: 'Abgeschlossen' };

async function call(action, payload = {}) {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ secret: SECRET, action, payload }),
  });
  const json = await res.json();
  if (!json.ok) { alert('Fehler: ' + (json.error || '?')); throw new Error(json.error); }
  store = json.data;
  renderAll();
  return json;
}

function esc(s) {
  return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function isUrl(s) { return /^https?:\/\//i.test((s||'').trim()); }

// ── Tabs ──────────────────────────────────────────────
function activateTab(name) {
  document.querySelectorAll('.tab').forEach(x => x.classList.toggle('active', x.dataset.tab === name));
  document.querySelectorAll('.panel').forEach(x => x.classList.toggle('active', x.id === 'panel-' + name));
}
document.querySelectorAll('.tab').forEach(t => {
  t.onclick = () => activateTab(t.dataset.tab);
});

// Start-Kategorie: Einstellung merken (im Browser, pro Gerät)
const startTabSel = document.getElementById('startTabSetting');
const savedStartTab = localStorage.getItem('dashboard_start_tab') || 'projects';
startTabSel.value = savedStartTab;
activateTab(savedStartTab);
startTabSel.onchange = () => {
  localStorage.setItem('dashboard_start_tab', startTabSel.value);
  activateTab(startTabSel.value);
};

// ── Projekte ──────────────────────────────────────────
const expandedIds = new Set();

function renderProjects() {
  const filter = document.getElementById('filterStatus').value;
  const sortMode = document.getElementById('sortMode').value;
  const board = document.getElementById('projectsBoard');
  let list = store.projects.filter(p => filter === 'alle' || p.status === filter);

  if (sortMode === 'alpha') {
    list = [...list].sort((a, b) => a.title.localeCompare(b.title, 'de'));
  } else if (sortMode === 'chrono') {
    list = [...list].sort((a, b) => new Date(b.created || 0) - new Date(a.created || 0));
  }
  // 'manual' → Reihenfolge wie in store.projects (per Drag & Drop steuerbar)

  board.innerHTML = list.length ? '' : '<div class="empty">Keine Projekte.</div>';

  list.forEach(p => {
    const isOpen = expandedIds.has(p.id);
    const card = document.createElement('div');
    card.className = 'card ' + p.status + (isOpen ? ' open' : '');
    card.dataset.id = p.id;
    if (sortMode === 'manual') card.draggable = true;

    const openSteps = (p.steps||[]).filter(s => !s.done).length;
    const previewParts = [];
    if (p.next) previewParts.push(p.next);
    else if (openSteps) previewParts.push(openSteps + ' offene Schritte');
    const preview = previewParts.join(' · ');

    const dragHandle = sortMode === 'manual' ? '<span class="drag-handle">⋮⋮</span>' : '';

    const stepsHtml = (p.steps||[]).map(s => `
      <div class="step ${s.done ? 'done' : ''}">
        <input type="checkbox" data-step-toggle="${p.id}:${s.id}" ${s.done ? 'checked' : ''}/>
        <span>${esc(s.text)}</span>
        <button data-step-del="${p.id}:${s.id}">✕</button>
      </div>`).join('');
    const linksHtml = (p.links||[]).map(l => `
      <div class="link-row">
        <a href="${esc(l.url)}" target="_blank" rel="noopener">${esc(l.note || l.url)}</a>
        <button data-link-del="${p.id}:${l.id}">✕</button>
      </div>`).join('');

    card.innerHTML = `
      <div class="card-header" data-toggle="${p.id}">
        <div class="card-header-left">
          ${dragHandle}
          <span class="chevron">▶</span>
          <div>
            <div class="card-title-view">${esc(p.title)}</div>
            ${preview ? `<div class="card-preview">${esc(preview)}</div>` : ''}
          </div>
        </div>
        <span class="tag">${statusLabel[p.status] || p.status}</span>
      </div>
      <div class="card-body">
        <input class="card-title-edit" data-title="${p.id}" value="${esc(p.title)}"/>
        <div class="row">
          <select data-status="${p.id}">
            ${Object.entries(statusLabel).map(([k,v]) => `<option value="${k}" ${p.status===k?'selected':''}>${v}</option>`).join('')}
          </select>
        </div>
        <div class="row"><input placeholder="Nächster Schritt" data-next="${p.id}" value="${esc(p.next||'')}"/></div>
        <div class="row"><input placeholder="Claude-Chat-Link" data-chaturl="${p.id}" value="${esc(p.chat_url||'')}"/></div>
        ${p.chat_url ? `<a class="chatlink" href="${esc(p.chat_url)}" target="_blank">💬 Chat öffnen</a>` : ''}
        <textarea class="notes" placeholder="Notizen" data-notes="${p.id}">${esc(p.notes||'')}</textarea>

        <div class="steps">${stepsHtml}</div>
        <div class="add-row">
          <input placeholder="Schritt hinzufügen…" data-newstep="${p.id}"/>
          <button class="btn small ghost" data-addstep="${p.id}">+</button>
        </div>

        <div class="links">${linksHtml}</div>
        <div class="add-row">
          <input placeholder="Link hinzufügen…" data-newlink="${p.id}"/>
          <button class="btn small ghost" data-addlink="${p.id}">+</button>
        </div>

        <div class="row" style="margin-top:12px;">
          <button class="btn danger small" data-delproj="${p.id}">Projekt löschen</button>
        </div>
      </div>
    `;
    board.appendChild(card);
  });

  // Auf-/Zuklappen
  board.querySelectorAll('[data-toggle]').forEach(el => {
    el.onclick = () => {
      const id = el.dataset.toggle;
      expandedIds.has(id) ? expandedIds.delete(id) : expandedIds.add(id);
      renderProjects();
    };
  });

  // Events (innerhalb geöffneter Karten)
  board.querySelectorAll('[data-title]').forEach(el => {
    el.addEventListener('blur', () => call('project_update', { id: el.dataset.title, title: el.value.trim() }));
  });
  board.querySelectorAll('[data-status]').forEach(el => {
    el.onclick = e => e.stopPropagation();
    el.onchange = () => call('project_update', { id: el.dataset.status, status: el.value });
  });
  board.querySelectorAll('[data-next]').forEach(el => {
    el.addEventListener('blur', () => call('project_update', { id: el.dataset.next, next: el.value }));
  });
  board.querySelectorAll('[data-chaturl]').forEach(el => {
    el.addEventListener('blur', () => call('project_update', { id: el.dataset.chaturl, chat_url: el.value }));
  });
  board.querySelectorAll('[data-notes]').forEach(el => {
    el.addEventListener('blur', () => call('project_update', { id: el.dataset.notes, notes: el.value }));
  });
  board.querySelectorAll('[data-delproj]').forEach(el => {
    el.onclick = () => { if (confirm('Projekt wirklich löschen?')) call('project_delete', { id: el.dataset.delproj }); };
  });
  board.querySelectorAll('[data-step-toggle]').forEach(el => {
    el.onchange = () => { const [pid,sid] = el.dataset.stepToggle.split(':'); call('step_toggle', { projectId: pid, stepId: sid, done: el.checked }); };
  });
  board.querySelectorAll('[data-step-del]').forEach(el => {
    el.onclick = () => { const [pid,sid] = el.dataset.stepDel.split(':'); call('step_delete', { projectId: pid, stepId: sid }); };
  });
  board.querySelectorAll('[data-addstep]').forEach(el => {
    el.onclick = () => {
      const input = board.querySelector(`[data-newstep="${el.dataset.addstep}"]`);
      if (input.value.trim()) call('step_add', { projectId: el.dataset.addstep, text: input.value.trim() });
    };
  });
  board.querySelectorAll('[data-link-del]').forEach(el => {
    el.onclick = () => { const [pid,lid] = el.dataset.linkDel.split(':'); call('link_delete', { projectId: pid, linkId: lid }); };
  });
  board.querySelectorAll('[data-addlink]').forEach(el => {
    el.onclick = () => {
      const input = board.querySelector(`[data-newlink="${el.dataset.addlink}"]`);
      if (input.value.trim()) call('link_add', { projectId: el.dataset.addlink, url: input.value.trim() });
    };
  });

  // Drag & Drop (nur im Modus "Manuell")
  if (sortMode === 'manual') {
    let dragEl = null;
    board.querySelectorAll('.card').forEach(cardEl => {
      cardEl.addEventListener('dragstart', () => { dragEl = cardEl; cardEl.classList.add('dragging'); });
      cardEl.addEventListener('dragend', () => { cardEl.classList.remove('dragging'); dragEl = null; });
      cardEl.addEventListener('dragover', e => {
        e.preventDefault();
        if (!dragEl || dragEl === cardEl) return;
        const rect = cardEl.getBoundingClientRect();
        const after = (e.clientY - rect.top) > rect.height / 2;
        cardEl.parentNode.insertBefore(dragEl, after ? cardEl.nextSibling : cardEl);
      });
      cardEl.addEventListener('drop', () => {
        const newOrder = [...board.querySelectorAll('.card')].map(c => c.dataset.id);
        // Reihenfolge lokal übernehmen + auf Server speichern
        store.projects.sort((a, b) => newOrder.indexOf(a.id) - newOrder.indexOf(b.id));
        call('projects_reorder', { order: newOrder });
      });
    });
  }
}
document.getElementById('filterStatus').onchange = renderProjects;
document.getElementById('sortMode').onchange = () => {
  localStorage.setItem('dashboard_sort_mode', document.getElementById('sortMode').value);
  renderProjects();
};
document.getElementById('sortMode').value = localStorage.getItem('dashboard_sort_mode') || 'manual';
document.getElementById('addProjectBtn').onclick = async () => {
  await call('project_add', { title: 'Neues Projekt' });
  const last = store.projects[store.projects.length - 1];
  if (last) { expandedIds.add(last.id); renderProjects(); }
};

// ── Inbox ─────────────────────────────────────────────
function renderInbox() {
  const board = document.getElementById('inboxBoard');
  board.innerHTML = store.inbox.length ? '' : '<div class="empty">Inbox leer.</div>';
  store.inbox.forEach(item => {
    const el = document.createElement('div');
    el.className = 'item';
    const projOptions = store.projects.map(p =>
      `<option value="${p.id}" ${p.id === item.suggested_project_id ? 'selected' : ''}>${esc(p.title)}</option>`
    ).join('');
    const suggestedProj = store.projects.find(p => p.id === item.suggested_project_id);
    let hint = '';
    if (suggestedProj) {
      hint = `<div style="font-size:12px;color:var(--teal);margin-top:4px;">🤖 Vorschlag: ${esc(suggestedProj.title)}${item.suggested_reason ? ' — ' + esc(item.suggested_reason) : ''}</div>`;
    } else if (item.suggested_new_title) {
      hint = `<div style="font-size:12px;color:var(--teal);margin-top:4px;">🤖 Vorschlag: neues Projekt „${esc(item.suggested_new_title)}"</div>`;
    }
    el.innerHTML = `
      <div>${isUrl(item.text) ? `<a href="${esc(item.text)}" target="_blank" style="color:var(--teal)">${esc(item.text)}</a>` : esc(item.text)}</div>
      ${hint}
      <div class="actions">
        <select data-assign-sel="${item.id}"><option value="">→ Projekt wählen…</option>${projOptions}</select>
        <button class="btn small" data-assign-go="${item.id}">Zuordnen</button>
        <button class="btn small ghost" data-newproj="${item.id}">+ Neues Projekt</button>
        <button class="btn small teal" data-tonews="${item.id}">📰 News</button>
        <button class="btn small teal" data-tolink="${item.id}">🔗 Link</button>
        <button class="btn small danger" data-inboxdel="${item.id}">🗑</button>
      </div>
    `;
    board.appendChild(el);
  });
  board.querySelectorAll('[data-assign-go]').forEach(b => {
    b.onclick = () => {
      const sel = board.querySelector(`[data-assign-sel="${b.dataset.assignGo}"]`);
      if (sel.value) call('inbox_assign', { itemId: b.dataset.assignGo, projectId: sel.value });
    };
  });
  board.querySelectorAll('[data-newproj]').forEach(b => b.onclick = () => call('inbox_newproj', { itemId: b.dataset.newproj }));
  board.querySelectorAll('[data-tonews]').forEach(b => b.onclick = () => call('inbox_to_news', { itemId: b.dataset.tonews }));
  board.querySelectorAll('[data-tolink]').forEach(b => b.onclick = () => call('inbox_to_link', { itemId: b.dataset.tolink }));
  board.querySelectorAll('[data-inboxdel]').forEach(b => b.onclick = () => call('inbox_delete', { itemId: b.dataset.inboxdel }));
}

// ── News ──────────────────────────────────────────────
function renderNews() {
  const board = document.getElementById('newsBoard');
  board.innerHTML = store.news.length ? '' : '<div class="empty">Noch keine News gespeichert.</div>';
  [...store.news].reverse().forEach(n => {
    const el = document.createElement('div');
    el.className = 'item';
    el.innerHTML = `<div>${esc(n.text)}</div><div class="actions"><button class="btn small danger" data-newsdel="${n.id}">🗑</button></div>`;
    board.appendChild(el);
  });
  board.querySelectorAll('[data-newsdel]').forEach(b => b.onclick = () => call('news_delete', { id: b.dataset.newsdel }));
}

// ── Links ─────────────────────────────────────────────
function renderLinks() {
  const board = document.getElementById('linksBoard');
  board.innerHTML = store.saved_links.length ? '' : '<div class="empty">Noch keine Links gespeichert.</div>';
  [...store.saved_links].reverse().forEach(l => {
    const el = document.createElement('div');
    el.className = 'item';
    el.innerHTML = `<div>${isUrl(l.text) ? `<a href="${esc(l.text)}" target="_blank" style="color:var(--teal)">${esc(l.text)}</a>` : esc(l.text)}</div><div class="actions"><button class="btn small danger" data-linkdel="${l.id}">🗑</button></div>`;
    board.appendChild(el);
  });
  board.querySelectorAll('[data-linkdel]').forEach(b => b.onclick = () => call('saved_link_delete', { id: b.dataset.linkdel }));
}

function renderAll() {
  renderProjects(); renderInbox(); renderNews(); renderLinks();
  document.getElementById('statusLine').textContent =
    `${store.projects.length} Projekte · ${store.inbox.length} in Inbox · ${store.news.length} News · ${store.saved_links.length} Links`;
}

document.getElementById('newInboxBtn').onclick = async () => {
  const input = document.getElementById('newInboxInput');
  const text = input.value.trim();
  if (!text) return;
  const btn = document.getElementById('newInboxBtn');
  btn.disabled = true;
  btn.textContent = 'KI ordnet ein…';
  try {
    await call('inbox_add', { text });
    input.value = '';
    activateTab('inbox');
  } finally {
    btn.disabled = false;
    btn.textContent = '+ Hinzufügen (KI ordnet ein)';
  }
};
document.getElementById('newInboxInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('newInboxBtn').click();
});

document.getElementById('newNewsBtn').onclick = () => {
  const input = document.getElementById('newNewsInput');
  if (input.value.trim()) { call('news_add', { text: input.value.trim() }); input.value = ''; }
};
document.getElementById('newNewsInput').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('newNewsBtn').click(); });

document.getElementById('newLinkBtn').onclick = () => {
  const input = document.getElementById('newLinkInput');
  if (input.value.trim()) { call('saved_link_add', { text: input.value.trim() }); input.value = ''; }
};
document.getElementById('newLinkInput').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('newLinkBtn').click(); });

call('get');

document.getElementById('deployBtn').onclick = async () => {
  const statusEl = document.getElementById('deployStatus');
  statusEl.textContent = 'läuft…';
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ secret: SECRET, action: 'deploy', payload: {} }),
    });
    const json = await res.json();
    statusEl.textContent = json.ok ? '✅ ' + (json.message || 'fertig').split('\n')[0] : '❌ Fehler';
    if (json.ok) alert(json.message);
  } catch (e) {
    statusEl.textContent = '❌ Fehler';
  }
};
</script>
</body>
</html>
