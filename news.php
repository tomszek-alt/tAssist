<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/world_news.php';

// Schutz: nur mit korrektem Secret aufrufbar (verhindert, dass Bots/Crawler
// die Seite treffen und unnötig Brave/Claude-Aufrufe auslösen)
if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

$news = get_world_news_summary();
$newsHtml = nl2br(htmlspecialchars($news, ENT_QUOTES, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Top 5 Weltnachrichten</title>
<style>
  body{margin:0;background:#1a1d24;color:#e8e6e1;font-family:system-ui,-apple-system,sans-serif;}
  .wrap{max-width:640px;margin:0 auto;padding:32px 20px 60px;}
  h1{font-size:22px;margin:0 0 4px;}
  .sub{color:#8b8f98;font-size:13px;margin-bottom:24px;}
  .news{background:#23272f;border:1px solid #363c47;border-radius:10px;padding:20px 22px;line-height:1.7;font-size:15px;}
  .refresh{display:inline-block;margin-top:20px;background:#e8a33d;color:#1a1d24;text-decoration:none;font-weight:600;padding:9px 16px;border-radius:6px;font-size:14px;}
</style>
</head>
<body>
<div class="wrap">
  <h1>🌍 Top 5 Weltnachrichten</h1>
  <div class="sub">Stand: <?= date('d.m.Y, H:i') ?> Uhr</div>
  <div class="news"><?= $newsHtml ?></div>
  <a class="refresh" href="?secret=<?= urlencode($_GET['secret']) ?>">Neu abrufen</a>
</div>
</body>
</html>
