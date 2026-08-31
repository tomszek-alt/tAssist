<?php
require_once __DIR__ . '/shared_lists_storage.php';

$secret = $_GET['secret'] ?? '';
$lists = shared_lists_load();
$list = &shared_list_find_by_secret($lists, $secret);
if (!$list) {
    http_response_code(403);
    exit('forbidden');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Packliste</title>
<style>
  :root{--bg:#1a1d24;--panel:#23272f;--panel2:#2b303a;--line:#363c47;--text:#e8e6e1;--muted:#8b8f98;--amber:#e8a33d;--teal:#4fb0a5;--red:#d9695f;}
  *{box-sizing:border-box;}
  body{margin:0;background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,sans-serif;}
  .wrap{max-width:680px;margin:0 auto;padding:24px 16px 70px;}
  h1{font-size:22px;margin:0 0 2px;}
  .sub{color:var(--muted);font-size:13px;margin-bottom:20px;}
  select,input{background:var(--panel2);border:1px solid var(--line);color:var(--text);border-radius:6px;padding:8px 10px;font-family:inherit;font-size:14px;width:100%;}
  input:focus{outline:1px solid var(--amber);}
  .btn{background:var(--amber);color:#1a1d24;border:none;border-radius:6px;padding:8px 14px;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap;}
  .btn.ghost{background:transparent;border:1px solid var(--line);color:var(--text);font-weight:500;}
  .btn.danger{background:var(--red);}
  .btn.small{padding:5px 9px;font-size:12px;}
  .cat{background:var(--panel);border:1px solid var(--line);border-radius:8px;margin-bottom:14px;overflow:hidden;}
  .cat-header{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:var(--panel2);}
  .cat-title{font-weight:700;font-size:15px;background:transparent;border:none;color:var(--text);width:auto;flex:1;}
  .progress{font-size:12px;color:var(--muted);white-space:nowrap;margin-right:8px;}
  .items{padding:6px 10px;}
  .item{display:flex;align-items:flex-start;gap:8px;padding:8px 4px;border-bottom:1px solid var(--line);}
  .item:last-child{border-bottom:none;}
  .item input[type=checkbox]{accent-color:var(--teal);width:18px;height:18px;flex:none;margin-top:2px;}
  .item.checked .item-title{color:var(--muted);text-decoration:line-through;}
  .item-main{flex:1;min-width:0;}
  .item-title{font-size:14px;background:transparent;border:none;color:var(--text);width:100%;padding:2px 0;}
  .item-row2{display:flex;gap:6px;margin-top:3px;}
  .item-qty{width:90px;font-size:12px;background:transparent;border:1px solid transparent;border-radius:4px;padding:2px 4px;color:var(--amber);}
  .item-note{flex:1;font-size:12px;background:transparent;border:1px solid transparent;border-radius:4px;padding:2px 4px;color:var(--muted);}
  .item-qty:focus,.item-note:focus,.item-title:focus{border-color:var(--line);background:var(--panel2);outline:none;}
  .item button{background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;flex:none;}
  .add-row{display:flex;gap:6px;padding:8px 10px;}
  .add-row input{font-size:13px;padding:6px 8px;}
  .add-cat-row{display:flex;gap:8px;margin-bottom:16px;}
</style>
</head>
<body>
<div class="wrap">
  <h1 id="titleView">📋 Packliste</h1>
  <div class="sub" id="subView">Lädt…</div>

  <div class="add-cat-row">
    <input id="newCatName" placeholder="Neue Kategorie…"/>
    <button class="btn" id="addCatBtn">+ Kategorie</button>
  </div>

  <div id="board"></div>
</div>

<script>
const SECRET = <?= json_encode($secret) ?>;
const API = 'shared_list_api.php';
let data = { title: '', subtitle: '', categories: [] };

function esc(s) { return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

async function call(action, payload = {}) {
  const res = await fetch(API, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ secret: SECRET, action, payload }),
  });
  const json = await res.json();
  if (!json.ok) { alert('Fehler: ' + json.error); throw new Error(json.error); }
  data = json.data;
  render();
  return json;
}

function render() {
  document.getElementById('titleView').textContent = '📋 ' + (data.title || 'Packliste');
  document.getElementById('subView').textContent = data.subtitle || '';

  const board = document.getElementById('board');
  board.innerHTML = '';
  data.categories.forEach(cat => {
    const done = cat.items.filter(i => i.checked).length;
    const el = document.createElement('div');
    el.className = 'cat';
    el.innerHTML = `
      <div class="cat-header">
        <input class="cat-title" data-catname="${cat.id}" value="${esc(cat.name)}"/>
        <span class="progress">${done}/${cat.items.length}</span>
        <button class="btn small danger" data-catdel="${cat.id}">🗑</button>
      </div>
      <div class="items">
        ${cat.items.map(it => `
          <div class="item ${it.checked ? 'checked' : ''}">
            <input type="checkbox" data-check="${cat.id}:${it.id}" ${it.checked ? 'checked' : ''}/>
            <div class="item-main">
              <input class="item-title" data-title="${cat.id}:${it.id}" value="${esc(it.title)}"/>
              <div class="item-row2">
                <input class="item-qty" data-qty="${cat.id}:${it.id}" value="${esc(it.qty)}" placeholder="Menge"/>
                <input class="item-note" data-note="${cat.id}:${it.id}" value="${esc(it.note)}" placeholder="Kommentar…"/>
              </div>
            </div>
            <button data-itemdel="${cat.id}:${it.id}">✕</button>
          </div>
        `).join('')}
      </div>
      <div class="add-row">
        <input placeholder="Neuer Punkt…" data-newitem="${cat.id}"/>
        <button class="btn small ghost" data-additem="${cat.id}">+</button>
      </div>
    `;
    board.appendChild(el);
  });

  board.querySelectorAll('[data-catname]').forEach(el => el.addEventListener('blur', () => call('category_rename', { id: el.dataset.catname, name: el.value })));
  board.querySelectorAll('[data-catdel]').forEach(el => el.onclick = () => { if (confirm('Kategorie löschen?')) call('category_delete', { id: el.dataset.catdel }); });
  board.querySelectorAll('[data-check]').forEach(el => el.onchange = () => { const [c,i]=el.dataset.check.split(':'); call('item_update', { categoryId:c, itemId:i, checked: el.checked }); });
  board.querySelectorAll('[data-title]').forEach(el => el.addEventListener('blur', () => { const [c,i]=el.dataset.title.split(':'); call('item_update', { categoryId:c, itemId:i, title: el.value }); }));
  board.querySelectorAll('[data-qty]').forEach(el => el.addEventListener('blur', () => { const [c,i]=el.dataset.qty.split(':'); call('item_update', { categoryId:c, itemId:i, qty: el.value }); }));
  board.querySelectorAll('[data-note]').forEach(el => el.addEventListener('blur', () => { const [c,i]=el.dataset.note.split(':'); call('item_update', { categoryId:c, itemId:i, note: el.value }); }));
  board.querySelectorAll('[data-itemdel]').forEach(el => el.onclick = () => { const [c,i]=el.dataset.itemdel.split(':'); call('item_delete', { categoryId:c, itemId:i }); });
  board.querySelectorAll('[data-additem]').forEach(el => el.onclick = () => {
    const input = board.querySelector(`[data-newitem="${el.dataset.additem}"]`);
    if (input.value.trim()) call('item_add', { categoryId: el.dataset.additem, title: input.value.trim() });
  });
}

document.getElementById('addCatBtn').onclick = () => {
  const input = document.getElementById('newCatName');
  if (input.value.trim()) { call('category_add', { name: input.value.trim() }); input.value = ''; }
};

call('get');
</script>
</body>
</html>
