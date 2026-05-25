<?php
/**
 * api/config.php — 管理員帳號設定
 *
 * 【新增 / 修改帳號】
 *  在 ADMIN_ACCOUNTS 陣列中加入一筆：
 *    '帳號名稱' => '明文密碼'
 *  儲存後刪除 api/.hashes/ 目錄，系統重啟時自動重新 hash。
 *
 * 【停用帳號】
 *  將該帳號行刪除或前面加 // 即可。
 */

define('ADMIN_ACCOUNTS', [
    '視聽中心' => 'avc@4220',
    '教學部'   => 'pass4200',
    '主任'     => '940702',
]);

/**
 * 各帳號的功能權限
 * 可用權限：'upload'（新增）、'edit'（編輯）、'delete'（刪除）、'star'（輪播）、'pin'（釘選）
 * 未在此設定的帳號，預設擁有全部權限
 */
define('ADMIN_PERMS', [
    '教學部' => ['edit'],   // 僅能編輯，不可刪除、新增、輪播、釘選
    // '主任' 未設定 → 自動擁有全部權限（與視聽中心相同）
]);

// Hash 快取目錄（每個帳號各自一個隱藏檔）
define('HASH_DIR',        __DIR__ . '/.hashes/');

// Session 設定
define('SESSION_NAME',    'cmmc_admin');
define('SESSION_LIFETIME', 3600);   // 有效秒數，預設 1 小時

// 操作紀錄檔路徑
define('AUDIT_LOG_FILE',  __DIR__ . '/../audit.log');

/**
 * 取得指定帳號的權限陣列
 * 若未設定則視為擁有全部權限
 */
function getAdminPerms(string $name): array {
    $permsMap = defined('ADMIN_PERMS') ? ADMIN_PERMS : [];
    if (array_key_exists($name, $permsMap)) {
        return $permsMap[$name];
    }
    return ['upload', 'edit', 'delete', 'star', 'pin'];
}

/**
 * 驗證帳號密碼，成功回傳帳號名稱，失敗回傳 false
 */
function verifyAdmin(string $password): string|false {
    if (!is_dir(HASH_DIR)) mkdir(HASH_DIR, 0700, true);

    foreach (ADMIN_ACCOUNTS as $name => $plain) {
        $hashFile = HASH_DIR . md5($name) . '.hash';

        if (!file_exists($hashFile)) {
            file_put_contents($hashFile, password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]));
        }

        $hash = trim(file_get_contents($hashFile));
        if (password_verify($password, $hash)) {
            return $name;
        }
    }
    return false;
}

/**
 * 寫入操作紀錄
 *
 * ★ 修正：只在 session 已啟動且 admin_name 確實存在時才寫入；
 *   避免 logout.php 等在 session destroy 後呼叫而產生「未知」紀錄。
 */
function writeAuditLog(string $action, string $detail = ''): void {
    // session 必須已啟動且登入身份明確，否則略過
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $operator = $_SESSION['admin_name'] ?? '';
    if ($operator === '') return;   // ← 身份不明就不寫，杜絕「未知」出現

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
    $time = date('Y-m-d H:i:s');
    $line = "{$time}\t{$operator}\t{$ip}\t{$action}\t{$detail}" . PHP_EOL;
    file_put_contents(AUDIT_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
