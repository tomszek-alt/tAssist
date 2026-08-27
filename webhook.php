<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/ai_sort.php';

function send_assign_prompt($item, $chatId, $projects, $suggestion = null) {
    $buttons = [];
    if ($suggestion && !empty($suggestion['project_id'])) {
        $projTitle = '';
        foreach ($projects as $p) { if ($p['id'] === $suggestion['project_id']) $projTitle = $p['title']; }
        if ($projTitle) {
            $buttons[] = [['text' => "→ {$projTitle}", 'callback_data' => "assign:{$item['id']}:{$suggestion['project_id']}"]];
        }
    }
    $buttons[] = [['text' => "+ Neues Projekt", 'callback_data' => "newproj:{$item['id']}"]];
    $buttons[] = [['text' => "📰 News", 'callback_data' => "news:{$item['id']}"], ['text' => "🔗 Link", 'callback_data' => "link:{$item['id']}"]];
    $buttons[] = [['text' => "Später (in Inbox lassen)", 'callback_data' => "skip:{$item['id']}"]];
    $buttons[] = [['text' => "🗑 Löschen", 'callback_data' => "del:{$item['id']}"]];

    $hint = ($suggestion && !empty($suggestion['reason'])) ? "\n<i>Vorschlag: " . tg_esc($suggestion['reason']) . "</i>" : "";
    $preview = mb_substr($item['text'], 0, 200);

    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => tg_esc($preview) . "\nWohin damit?{$hint}",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_exec($ch);
    curl_close($ch);
}

// Schutz: nur mit korrektem Secret aufrufbar
if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

$update = json_decode(file_get_contents('php://input'), true);
if (!$update) { exit; }

// ── Button-Klick (Callback Query) ───────────────────────────────
if (isset($update['callback_query'])) {
    $cq = $update['callback_query'];
    $data = $cq['data']; // Format: "assign:<inboxId>:<projectId>" oder "newproj:<inboxId>" oder "skip:<inboxId>"
    $chatId = $cq['message']['chat']['id'];
    $parts = explode(':', $data);
    $action = $parts[0];
    $inboxId = $parts[1] ?? null;

    $store = load_data();
    $item = null;
    foreach ($store['inbox'] as $i) { if ($i['id'] === $inboxId) { $item = $i; break; } }

    if ($item) {
        if ($action === 'assign') {
            $projectId = $parts[2];
            foreach ($store['projects'] as &$p) {
                if ($p['id'] === $projectId) {
                    if (preg_match('#^https?://(www\.)?claude\.ai/chat/#i', $item['text'])) {
                        $p['chat_url'] = $item['text'];
                        telegram_send("🔗 Claude-Chat-Link für „" . tg_esc($p['title']) . "“ gesetzt.", $chatId);
                    } elseif (preg_match('/^https?:\/\//i', $item['text'])) {
                        $p['links'][] = ['id' => 'l' . time(), 'url' => $item['text'], 'note' => ''];
                        telegram_send("✅ Zu „" . tg_esc($p['title']) . "“ hinzugefügt.", $chatId);
                    } else {
                        $p['steps'][] = ['id' => 's' . time(), 'text' => $item['text'], 'done' => false];
                        telegram_send("✅ Zu „" . tg_esc($p['title']) . "“ hinzugefügt.", $chatId);
                    }
                    break;
                }
            }
            unset($p);
        } elseif ($action === 'newproj') {
            $store['projects'][] = [
                'id' => 'p' . time(),
                'title' => mb_substr($item['text'], 0, 60),
                'status' => 'idee',
                'next' => '', 'notes' => '', 'steps' => [], 'links' => [],
            ];
            telegram_send("✅ Neues Projekt angelegt: „" . tg_esc($item['text']) . "\"", $chatId);
        } elseif ($action === 'news') {
            $store['news'][] = ['id' => 'n' . time(), 'text' => $item['text'], 'created' => date('c')];
            telegram_send("📰 Als News gespeichert.", $chatId);
        } elseif ($action === 'link') {
            $store['saved_links'][] = ['id' => 'sl' . time(), 'text' => $item['text'], 'created' => date('c')];
            telegram_send("🔗 Als Link gespeichert.", $chatId);
        } elseif ($action === 'skip') {
            telegram_send("Übersprungen, bleibt in der Inbox.", $chatId);
            $item = null; // nicht aus Inbox entfernen
        } elseif ($action === 'del') {
            telegram_send("🗑 Gelöscht.", $chatId);
            // $item bleibt gesetzt, damit unten aus der Inbox entfernt wird
        }
        if ($item && $action !== 'skip') {
            $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== $inboxId));
        }
        save_data($store);
    }
    exit;
}

// ── Normale Nachricht ────────────────────────────────────────────
$msg = $update['message'] ?? null;
if (!$msg || !isset($msg['text'])) { exit; }
$chatId = $msg['chat']['id'];
$text = trim($msg['text']);

if ($text === '/liste' || $text === '/status') {
    $store = load_data();
    telegram_send("<b>Aktueller Stand:</b>\n" . projects_summary_text($store), $chatId);
    exit;
}

if ($text === '/hilfe' || $text === '/start') {
    telegram_send("Schick mir einfach einen Link oder eine Idee — ich frage dann per Buttons nach, wohin sie soll.\n\nBefehle:\n/liste – aktueller Stand aller Projekte\n/inbox – offene, noch nicht zugeordnete Einträge\n/news – gespeicherte News\n/links – gespeicherte Links\n/deploy – neueste Code-Version vom Repo holen", $chatId);
    exit;
}

if ($text === '/deploy') {
    require_once __DIR__ . '/deploy_logic.php';
    telegram_send("🚀 Deploye…", $chatId);
    $result = run_deploy();
    telegram_send($result, $chatId);
    exit;
}

if ($text === '/news') {
    $store = load_data();
    if (empty($store['news'])) { telegram_send("Noch keine News gespeichert.", $chatId); exit; }
    $lines = array_map(fn($n) => "📰 " . tg_esc(mb_substr($n['text'], 0, 150)), array_slice($store['news'], -20));
    telegram_send(implode("\n\n", $lines), $chatId);
    exit;
}

if ($text === '/links') {
    $store = load_data();
    if (empty($store['saved_links'])) { telegram_send("Noch keine Links gespeichert.", $chatId); exit; }
    $lines = array_map(function($l) {
        $isUrl = preg_match('/^https?:\/\//i', $l['text']);
        return $isUrl ? "🔗 <a href=\"" . tg_esc($l['text']) . "\">" . tg_esc($l['text']) . "</a>" : "🔗 " . tg_esc($l['text']);
    }, array_slice($store['saved_links'], -20));
    telegram_send(implode("\n\n", $lines), $chatId);
    exit;
}

if ($text === '/inbox') {
    $store = load_data();
    if (empty($store['inbox'])) {
        telegram_send("Inbox ist leer — alles zugeordnet.", $chatId);
        exit;
    }
    telegram_send(count($store['inbox']) . " offene Einträge:", $chatId);
    foreach (array_slice($store['inbox'], 0, 10) as $item) {
        send_assign_prompt($item, $chatId, $store['projects']);
    }
    exit;
}

// Alles andere: als Inbox-Eintrag speichern und per KI einordnen lassen
$store = add_inbox_item($text);
$newItem = end($store['inbox']);

$suggestion = claude_classify_inbox($text, $store['projects']);
send_assign_prompt($newItem, $chatId, $store['projects'], $suggestion);
