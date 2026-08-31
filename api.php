<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$secret = $input['secret'] ?? $_GET['secret'] ?? '';
if ($secret !== WEBHOOK_SECRET) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$action = $input['action'] ?? '';
$p = $input['payload'] ?? [];
$store = load_data();

function &find_project(&$store, $id) {
    foreach ($store['projects'] as &$proj) {
        if ($proj['id'] === $id) return $proj;
    }
    $null = null;
    return $null;
}

switch ($action) {
    case 'get':
        break;

    case 'sharedlist_create':
        require_once __DIR__ . '/shared_lists_storage.php';
        $lists = shared_lists_load();
        $lists[] = [
            'id' => 'sl' . uniqid(),
            'secret' => shared_lists_new_secret(),
            'title' => trim($p['title'] ?? 'Neue Liste'),
            'subtitle' => trim($p['subtitle'] ?? ''),
            'categories' => [],
        ];
        shared_lists_save($lists);
        break;

    case 'sharedlist_delete':
        require_once __DIR__ . '/shared_lists_storage.php';
        $lists = array_values(array_filter(shared_lists_load(), fn($l) => $l['id'] !== ($p['id'] ?? '')));
        shared_lists_save($lists);
        break;

    case 'project_add':
        $store['projects'][] = [
            'id' => 'p' . uniqid(),
            'title' => trim($p['title'] ?? 'Neues Projekt'),
            'status' => $p['status'] ?? 'idee',
            'next' => trim($p['next'] ?? ''),
            'notes' => trim($p['notes'] ?? ''),
            'chat_url' => trim($p['chat_url'] ?? ''),
            'created' => date('c'),
            'steps' => [],
            'links' => [],
        ];
        break;

    case 'projects_reorder':
        $order = $p['order'] ?? [];
        $byId = [];
        foreach ($store['projects'] as $proj) { $byId[$proj['id']] = $proj; }
        $reordered = [];
        foreach ($order as $id) {
            if (isset($byId[$id])) { $reordered[] = $byId[$id]; unset($byId[$id]); }
        }
        // Etwaige nicht in order enthaltene Projekte hinten anhängen (Sicherheitsnetz)
        foreach ($byId as $rest) { $reordered[] = $rest; }
        $store['projects'] = $reordered;
        break;

    case 'project_update':
        $proj = &find_project($store, $p['id'] ?? '');
        if ($proj) {
            $proj['title'] = trim($p['title'] ?? $proj['title']);
            $proj['status'] = $p['status'] ?? $proj['status'];
            $proj['next'] = trim($p['next'] ?? $proj['next']);
            $proj['notes'] = trim($p['notes'] ?? $proj['notes']);
            $proj['chat_url'] = trim($p['chat_url'] ?? ($proj['chat_url'] ?? ''));
        }
        unset($proj);
        break;

    case 'project_delete':
        $store['projects'] = array_values(array_filter($store['projects'], fn($x) => $x['id'] !== ($p['id'] ?? '')));
        break;

    case 'step_add':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj && trim($p['text'] ?? '') !== '') {
            $proj['steps'][] = ['id' => 's' . uniqid(), 'text' => trim($p['text']), 'done' => false];
        }
        unset($proj);
        break;

    case 'step_toggle':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj) {
            foreach ($proj['steps'] as &$s) {
                if ($s['id'] === ($p['stepId'] ?? '')) $s['done'] = !empty($p['done']);
            }
            unset($s);
        }
        unset($proj);
        break;

    case 'step_delete':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj) {
            $proj['steps'] = array_values(array_filter($proj['steps'], fn($s) => $s['id'] !== ($p['stepId'] ?? '')));
        }
        unset($proj);
        break;

    case 'link_add':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj && trim($p['url'] ?? '') !== '') {
            $proj['links'][] = ['id' => 'l' . uniqid(), 'url' => trim($p['url']), 'note' => trim($p['note'] ?? '')];
        }
        unset($proj);
        break;

    case 'link_delete':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj) {
            $proj['links'] = array_values(array_filter($proj['links'], fn($l) => $l['id'] !== ($p['linkId'] ?? '')));
        }
        unset($proj);
        break;

    case 'inbox_add':
        require_once __DIR__ . '/ai_sort.php';
        $text = trim($p['text'] ?? '');
        if ($text === '') break;
        $newItem = ['id' => 'i' . uniqid(), 'text' => $text, 'created' => date('c')];
        $suggestion = claude_classify_inbox($text, $store['projects']);
        if (!empty($suggestion['project_id'])) {
            $newItem['suggested_project_id'] = $suggestion['project_id'];
            $newItem['suggested_reason'] = $suggestion['reason'] ?? '';
        } elseif (!empty($suggestion['new_title'])) {
            $newItem['suggested_new_title'] = $suggestion['new_title'];
        }
        $store['inbox'][] = $newItem;
        break;

    case 'inbox_assign':
        $itemId = $p['itemId'] ?? '';
        $item = null;
        foreach ($store['inbox'] as $i) { if ($i['id'] === $itemId) { $item = $i; break; } }
        if ($item) {
            $proj = &find_project($store, $p['projectId'] ?? '');
            if ($proj) {
                if (preg_match('#^https?://(www\.)?claude\.ai/chat/#i', $item['text'])) {
                    $proj['chat_url'] = $item['text'];
                } elseif (preg_match('/^https?:\/\//i', $item['text'])) {
                    $proj['links'][] = ['id' => 'l' . uniqid(), 'url' => $item['text'], 'note' => ''];
                } else {
                    $proj['steps'][] = ['id' => 's' . uniqid(), 'text' => $item['text'], 'done' => false];
                }
            }
            unset($proj);
            $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== $itemId));
        }
        break;

    case 'inbox_newproj':
        $itemId = $p['itemId'] ?? '';
        $item = null;
        foreach ($store['inbox'] as $i) { if ($i['id'] === $itemId) { $item = $i; break; } }
        if ($item) {
            $isUrl = preg_match('/^https?:\/\//i', $item['text']);
            $store['projects'][] = [
                'id' => 'p' . uniqid(),
                'title' => mb_substr($item['text'], 0, 60),
                'status' => 'idee', 'next' => '', 'notes' => '', 'chat_url' => '',
                'steps' => [], 'links' => $isUrl ? [['id' => 'l' . uniqid(), 'url' => $item['text'], 'note' => '']] : [],
            ];
            $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== $itemId));
        }
        break;

    case 'inbox_to_news':
        $itemId = $p['itemId'] ?? '';
        foreach ($store['inbox'] as $i) {
            if ($i['id'] === $itemId) $store['news'][] = ['id' => 'n' . uniqid(), 'text' => $i['text'], 'created' => date('c')];
        }
        $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== $itemId));
        break;

    case 'inbox_to_link':
        $itemId = $p['itemId'] ?? '';
        foreach ($store['inbox'] as $i) {
            if ($i['id'] === $itemId) $store['saved_links'][] = ['id' => 'sl' . uniqid(), 'text' => $i['text'], 'created' => date('c')];
        }
        $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== $itemId));
        break;

    case 'inbox_delete':
        $store['inbox'] = array_values(array_filter($store['inbox'], fn($i) => $i['id'] !== ($p['itemId'] ?? '')));
        break;

    case 'news_add':
        if (trim($p['text'] ?? '') !== '') {
            $store['news'][] = ['id' => 'n' . uniqid(), 'text' => trim($p['text']), 'created' => date('c')];
        }
        break;

    case 'saved_link_add':
        if (trim($p['text'] ?? '') !== '') {
            $store['saved_links'][] = ['id' => 'sl' . uniqid(), 'text' => trim($p['text']), 'created' => date('c')];
        }
        break;

    case 'news_delete':
        $store['news'] = array_values(array_filter($store['news'], fn($n) => $n['id'] !== ($p['id'] ?? '')));
        break;

    case 'saved_link_delete':
        $store['saved_links'] = array_values(array_filter($store['saved_links'], fn($l) => $l['id'] !== ($p['id'] ?? '')));
        break;

    case 'project_customfield_add':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj && trim($p['key'] ?? '') !== '') {
            $proj['custom_fields'] = $proj['custom_fields'] ?? [];
            $proj['custom_fields'][] = ['id' => 'cf' . uniqid(), 'key' => trim($p['key']), 'value' => trim($p['value'] ?? '')];
        }
        unset($proj);
        break;

    case 'project_customfield_delete':
        $proj = &find_project($store, $p['projectId'] ?? '');
        if ($proj) {
            $proj['custom_fields'] = array_values(array_filter($proj['custom_fields'] ?? [], fn($f) => $f['id'] !== ($p['fieldId'] ?? '')));
        }
        unset($proj);
        break;

    case 'collection_add':
        if (trim($p['name'] ?? '') !== '') {
            $store['collections'][] = ['id' => 'col' . uniqid(), 'name' => trim($p['name']), 'items' => []];
        }
        break;

    case 'collection_delete':
        $store['collections'] = array_values(array_filter($store['collections'], fn($c) => $c['id'] !== ($p['id'] ?? '')));
        break;

    case 'collection_item_add':
        foreach ($store['collections'] as &$col) {
            if ($col['id'] === ($p['collectionId'] ?? '') && trim($p['title'] ?? '') !== '') {
                $col['items'][] = [
                    'id' => 'ci' . uniqid(),
                    'title' => trim($p['title']),
                    'note' => trim($p['note'] ?? ''),
                    'url' => trim($p['url'] ?? ''),
                    'created' => date('c'),
                ];
            }
        }
        unset($col);
        break;

    case 'collection_item_delete':
        foreach ($store['collections'] as &$col) {
            if ($col['id'] === ($p['collectionId'] ?? '')) {
                $col['items'] = array_values(array_filter($col['items'], fn($i) => $i['id'] !== ($p['itemId'] ?? '')));
            }
        }
        unset($col);
        break;

    case 'youtube_summary':
        require_once __DIR__ . '/video_summary.php';
        $summary = summarize_youtube_url($p['url'] ?? '');
        echo json_encode(['ok' => true, 'data' => $store, 'message' => $summary]);
        exit;

    case 'deploy':
        require_once __DIR__ . '/deploy_logic.php';
        $message = run_deploy();
        echo json_encode(['ok' => true, 'data' => $store, 'message' => $message]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown action']);
        exit;
}

save_data($store);
require_once __DIR__ . '/shared_lists_storage.php';
$store['shared_lists'] = shared_lists_load();
echo json_encode(['ok' => true, 'data' => $store]);
