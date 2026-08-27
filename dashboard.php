<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

$store = load_data();
$statusLabel = ['idee' => 'Idee', 'arbeit' => 'In Arbeit', 'pausiert' => 'Pausiert', 'fertig' => 'Abgeschlossen'];
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function isUrl($s) { return preg_match('/^https?:\/\//i', trim($s)); }
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<style>
  :root{--bg:#1a1d24;--panel:#23272f;--panel2:#2b303a;--line:#363c47;--text:#e8e6e1;--muted:#8b8f98;--amber:#e8a33d;--teal:#4fb0a5;--violet:#8b7fd6;}
  *{box-sizing:border-box;}
  body{margin:0;background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,sans-serif;}
  .wrap{max-width:760px;margin:0 auto;padding:28px 18px 60px;}
  h1{font-size:22px;margin:0 0 4px;}
  .sub{color:var(--muted);font-size:13px;margin-bottom:26px;}
  h2{font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin:30px 0 10px;}
  .card{background:var(--panel);border:1px solid var(--line);border-left:4px solid var(--muted);border-radius:8px;padding:12px 16px;margin-bottom:8px;}
  .card.idee{border-left-color:var(--violet);}
  .card.arbeit{border-left-color:var(--amber);}
  .card.fertig{border-left-color:var(--teal);}
  .card-top{display:flex;justify-content:space-between;gap:10px;font-size:15px;font-weight:600;}
  .tag{font-size:11px;color:var(--muted);border:1px solid var(--line);border-radius:4px;padding:2px 6px;white-space:nowrap;}
  .meta{font-size:13px;color:var(--muted);margin-top:4px;}
  .item{background:var(--panel2);border:1px solid var(--line);border-radius:6px;padding:10px 12px;margin-bottom:6px;font-size:14px;}
  .item a{color:var(--teal);word-break:break-all;}
  .item time{display:block;color:var(--muted);font-size:11px;margin-top:4px;}
  .empty{color:var(--muted);font-size:13px;}
  .count{color:var(--muted);font-weight:400;font-size:13px;}
</style>
</head>
<body>
<div class="wrap">
  <h1>📋 Dashboard</h1>
  <div class="sub">Stand: <?= date('d.m.Y, H:i') ?> Uhr</div>

  <h2>Projekte <span class="count">(<?= count($store['projects']) ?>)</span></h2>
  <?php if (empty($store['projects'])): ?>
    <div class="empty">Keine Projekte.</div>
  <?php else: foreach ($store['projects'] as $p):
    $open = array_filter($p['steps'] ?? [], fn($s) => empty($s['done']));
  ?>
    <div class="card <?= h($p['status'] ?? '') ?>">
      <div class="card-top">
        <span><?= h($p['title']) ?></span>
        <span class="tag"><?= h($statusLabel[$p['status'] ?? ''] ?? $p['status'] ?? '') ?></span>
      </div>
      <?php if (!empty($p['next'])): ?><div class="meta">→ <?= h($p['next']) ?></div><?php endif; ?>
      <?php if (!empty($open)): ?><div class="meta"><?= count($open) ?> offene Schritte</div><?php endif; ?>
      <?php if (!empty($p['chat_url'])): ?><div class="meta"><a href="<?= h($p['chat_url']) ?>" target="_blank">💬 Claude-Chat</a></div><?php endif; ?>
    </div>
  <?php endforeach; endif; ?>

  <h2>📰 News <span class="count">(<?= count($store['news']) ?>)</span></h2>
  <?php if (empty($store['news'])): ?>
    <div class="empty">Noch keine News gespeichert.</div>
  <?php else: foreach (array_reverse($store['news']) as $n): ?>
    <div class="item"><?= h($n['text']) ?><time><?= h($n['created'] ?? '') ?></time></div>
  <?php endforeach; endif; ?>

  <h2>🔗 Links <span class="count">(<?= count($store['saved_links']) ?>)</span></h2>
  <?php if (empty($store['saved_links'])): ?>
    <div class="empty">Noch keine Links gespeichert.</div>
  <?php else: foreach (array_reverse($store['saved_links']) as $l): ?>
    <div class="item">
      <?= isUrl($l['text']) ? '<a href="' . h($l['text']) . '" target="_blank">' . h($l['text']) . '</a>' : h($l['text']) ?>
      <time><?= h($l['created'] ?? '') ?></time>
    </div>
  <?php endforeach; endif; ?>

  <h2>📥 Offene Inbox <span class="count">(<?= count($store['inbox']) ?>)</span></h2>
  <?php if (empty($store['inbox'])): ?>
    <div class="empty">Inbox leer.</div>
  <?php else: foreach (array_reverse($store['inbox']) as $i): ?>
    <div class="item"><?= h($i['text']) ?></div>
  <?php endforeach; endif; ?>
</div>
</body>
</html>
