<?php
/**
 * api/auth_check.php
 * 
 * 所有需要管理員權限的 API 開頭引入此檔：
 *   require_once __DIR__ . '/auth_check.php';
 * 
 * 未登入時直接回傳 403 並終止執行。
 */

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

$loggedIn = !empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// 逾時檢查
if ($loggedIn && isset($_SESSION['login_time'])) {
    if ((time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        session_destroy();
        $loggedIn = false;
    }
}

if (!$loggedIn) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '未授權：請先登入管理員帳號']);
    exit;
}
