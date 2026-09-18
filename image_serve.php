<?php
require_once __DIR__ . '/../../../.configs/config.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit;
}

$id = $_GET['id'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
    http_response_code(400);
    exit;
}

$dir = __DIR__ . '/../../../.configs/data/images/';
$candidates = glob($dir . $id . '.*');
if (empty($candidates)) {
    http_response_code(404);
    exit;
}
$file = $candidates[0];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=86400');
readfile($file);
