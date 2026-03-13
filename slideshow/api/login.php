<?php
/**
 * api/login.php — 多帳號登入驗證
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不支援此請求方式']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => '請輸入密碼']);
    exit;
}

// 防暴力破解
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$failKey   = 'login_fail_' . md5($ip);
$failCount = $_SESSION[$failKey] ?? 0;
$failTime  = $_SESSION[$failKey . '_time'] ?? 0;

if ($failCount >= 5 && (time() - $failTime) < 60) {
    $remaining = 60 - (time() - $failTime);
    echo json_encode(['success' => false, 'message' => "登入嘗試過多，請 {$remaining} 秒後再試"]);
    exit;
}

// 驗證（支援多帳號）
$adminName = verifyAdmin($password);

if ($adminName !== false) {
    unset($_SESSION[$failKey], $_SESSION[$failKey . '_time']);
    session_regenerate_id(true);
    $_SESSION['is_admin']    = true;
    $_SESSION['admin_name']  = $adminName;   // ← 記錄是誰登入
    $_SESSION['login_time']  = time();
    $_SESSION['login_ip']    = $ip;

    writeAuditLog('登入');
    echo json_encode(['success' => true, 'message' => '登入成功', 'adminName' => $adminName]);
} else {
    $_SESSION[$failKey]           = $failCount + 1;
    $_SESSION[$failKey . '_time'] = time();
    echo json_encode(['success' => false, 'message' => '密碼錯誤，請重新輸入']);
}
