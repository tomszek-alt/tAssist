<?php
define('SHARED_LISTS_FILE', __DIR__ . '/../../../.configs/data/shared_lists.json');

function shared_lists_load() {
    if (!file_exists(SHARED_LISTS_FILE)) return [];
    $data = json_decode(file_get_contents(SHARED_LISTS_FILE), true);
    return is_array($data) ? $data : [];
}

function shared_lists_save($lists) {
    $dir = dirname(SHARED_LISTS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(SHARED_LISTS_FILE, json_encode($lists, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function shared_lists_new_secret() {
    return bin2hex(random_bytes(12));
}

function &shared_list_find_by_secret(&$lists, $secret) {
    foreach ($lists as &$l) { if ($l['secret'] === $secret) return $l; }
    $null = null; return $null;
}

function &shared_list_find_by_id(&$lists, $id) {
    foreach ($lists as &$l) { if ($l['id'] === $id) return $l; }
    $null = null; return $null;
}

function &shared_cat_find(&$list, $catId) {
    foreach ($list['categories'] as &$c) { if ($c['id'] === $catId) return $c; }
    $null = null; return $null;
}
