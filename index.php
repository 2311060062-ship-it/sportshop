<?php
if (trim((string) ($_GET['url'] ?? ''), '/') === 'up') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
/**
 * ============================================================
 *  SPORT SHOP – Entry Point duy nhất
 *  File: index.php
 * ============================================================
 */

// Load cấu hình trước tiên
require_once __DIR__ . '/config/config.php';

// Load core classes
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/App.php';

// Khởi động ứng dụng – Router sẽ tự động xử lý URL
new App();
