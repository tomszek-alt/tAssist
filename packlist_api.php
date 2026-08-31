<?php
require_once __DIR__ . '/packlist_storage.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$secret = $input['secret'] ?? $_GET['secret'] ?? '';
if ($secret !== PACKLIST_SECRET) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$action = $input['action'] ?? '';
$p = $input['payload'] ?? [];
$data = packlist_load();

function pl_find_cat(&$data, $id) {
    foreach ($data['categories'] as &$c) { if ($c['id'] === $id) return $c; }
    $null = null; return $null;
}

switch ($action) {
    case 'get':
        break;

    case 'category_add':
        if (trim($p['name'] ?? '') !== '') {
            $data['categories'][] = ['id' => 'cat' . uniqid(), 'name' => trim($p['name']), 'items' => []];
        }
        break;

    case 'category_delete':
        $data['categories'] = array_values(array_filter($data['categories'], fn($c) => $c['id'] !== ($p['id'] ?? '')));
        break;

    case 'category_rename':
        $cat = &pl_find_cat($data, $p['id'] ?? '');
        if ($cat && trim($p['name'] ?? '') !== '') $cat['name'] = trim($p['name']);
        unset($cat);
        break;

    case 'item_add':
        $cat = &pl_find_cat($data, $p['categoryId'] ?? '');
        if ($cat && trim($p['title'] ?? '') !== '') {
            $cat['items'][] = ['id' => 'it' . uniqid(), 'title' => trim($p['title']), 'qty' => trim($p['qty'] ?? ''), 'note' => trim($p['note'] ?? ''), 'checked' => false];
        }
        unset($cat);
        break;

    case 'item_update':
        $cat = &pl_find_cat($data, $p['categoryId'] ?? '');
        if ($cat) {
            foreach ($cat['items'] as &$it) {
                if ($it['id'] === ($p['itemId'] ?? '')) {
                    if (isset($p['title'])) $it['title'] = trim($p['title']);
                    if (isset($p['qty'])) $it['qty'] = trim($p['qty']);
                    if (isset($p['note'])) $it['note'] = trim($p['note']);
                    if (isset($p['checked'])) $it['checked'] = !!$p['checked'];
                }
            }
            unset($it);
        }
        unset($cat);
        break;

    case 'item_delete':
        $cat = &pl_find_cat($data, $p['categoryId'] ?? '');
        if ($cat) {
            $cat['items'] = array_values(array_filter($cat['items'], fn($it) => $it['id'] !== ($p['itemId'] ?? '')));
        }
        unset($cat);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown action']);
        exit;
}

packlist_save($data);
echo json_encode(['ok' => true, 'data' => $data]);
