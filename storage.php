<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/telegram.php';

function empty_store() {
    return ['projects' => [], 'inbox' => [], 'news' => [], 'saved_links' => []];
}

function load_data() {
    if (!file_exists(DATA_FILE)) {
        return empty_store();
    }
    $raw = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return empty_store();
    }
    $data['projects'] = $data['projects'] ?? [];
    $data['inbox'] = $data['inbox'] ?? [];
    $data['news'] = $data['news'] ?? [];
    $data['saved_links'] = $data['saved_links'] ?? [];
    return $data;
}

function save_data($data) {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function add_inbox_item($text) {
    $data = load_data();
    $data['inbox'][] = [
        'id' => 'i' . time() . rand(100, 999),
        'text' => $text,
        'created' => date('c'),
    ];
    save_data($data);
    return $data;
}

function add_news_item($text) {
    $data = load_data();
    $data['news'][] = ['id' => 'n' . time() . rand(100, 999), 'text' => $text, 'created' => date('c')];
    save_data($data);
    return $data;
}

function add_saved_link($text) {
    $data = load_data();
    $data['saved_links'][] = ['id' => 'sl' . time() . rand(100, 999), 'text' => $text, 'created' => date('c')];
    save_data($data);
    return $data;
}

function projects_summary_text($data) {
    if (empty($data['projects'])) {
        return "Noch keine Projekte hinterlegt.";
    }
    $lines = [];
    foreach ($data['projects'] as $p) {
        $status = $p['status'] ?? 'idee';
        $label = [
            'idee' => 'Idee',
            'arbeit' => 'In Arbeit',
            'pausiert' => 'Pausiert',
            'fertig' => 'Abgeschlossen',
        ][$status] ?? $status;
        $next = !empty($p['next']) ? " – nächster Schritt: " . tg_esc($p['next']) : "";
        $chatLink = !empty($p['chat_url']) ? " (<a href=\"" . tg_esc($p['chat_url']) . "\">💬 Chat</a>)" : "";
        $lines[] = "• [{$label}] " . tg_esc($p['title']) . "{$next}{$chatLink}";
    }
    return implode("\n", $lines);
}
