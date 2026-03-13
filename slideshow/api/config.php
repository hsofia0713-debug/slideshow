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
    '管理員A' => 'admin1234',   // ← 請改成實際密碼
    '管理員B' => 'pass5678',    // ← 第二組帳號，可繼續新增
]);

// Hash 快取目錄（每個帳號各自一個隱藏檔）
define('HASH_DIR',        __DIR__ . '/.hashes/');

// Session 設定
define('SESSION_NAME',    'cmmc_admin');
define('SESSION_LIFETIME', 3600);   // 有效秒數，預設 1 小時

// 操作紀錄檔路徑
define('AUDIT_LOG_FILE',  __DIR__ . '/../audit.log');

/**
 * 驗證帳號密碼，成功回傳帳號名稱，失敗回傳 false
 */
function verifyAdmin(string $password): string|false {
    if (!is_dir(HASH_DIR)) mkdir(HASH_DIR, 0700, true);

    foreach (ADMIN_ACCOUNTS as $name => $plain) {
        $hashFile = HASH_DIR . md5($name) . '.hash';

        // 若 hash 檔不存在，自動產生
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
 */
function writeAuditLog(string $action, string $detail = ''): void {
    $operator = $_SESSION['admin_name'] ?? '未知';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '-';
    $time     = date('Y-m-d H:i:s');
    $line     = "{$time}\t{$operator}\t{$ip}\t{$action}\t{$detail}" . PHP_EOL;
    file_put_contents(AUDIT_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
