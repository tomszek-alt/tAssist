<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/deploy_logic.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
echo run_deploy();
