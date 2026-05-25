<?php
/**
 * api/check_auth.php
 * 確認目前 Session 是否已登入，並回傳帳號名稱與權限
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';

// 啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'path' => '/']);
    session_start();
}

$loggedIn  = !empty($_SESSION['admin_name']);
$adminName = $loggedIn ? ($_SESSION['admin_name'] ?? '') : '';
$perms     = $loggedIn ? getAdminPerms($adminName) : [];

echo json_encode([
    'loggedIn'  => $loggedIn,
    'adminName' => $adminName,
    'perms'     => $perms,          // ← 新增：權限陣列
]);
