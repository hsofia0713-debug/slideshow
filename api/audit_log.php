<?php
/**
 * api/audit_log.php
 * 回傳操作紀錄（需登入）
 * GET ?limit=100  → 最新 N 筆
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/auth_check.php';   // 未登入直接 403

$limit = min((int)($_GET['limit'] ?? 100), 500);

if (!file_exists(AUDIT_LOG_FILE)) {
    echo json_encode(['success' => true, 'logs' => []]);
    exit;
}

$lines = file(AUDIT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$totalLines = count($lines);
$lines = array_reverse($lines);         // 最新在最上
$lines = array_slice($lines, 0, $limit);

$logs = [];
foreach ($lines as $reversedIdx => $line) {
    $parts = explode("\t", $line, 5);
    if (count($parts) < 4) continue;
    // 計算此行在原始檔案中的行號（0-based），供刪除時使用
    $originalLineIdx = $totalLines - 1 - $reversedIdx;
    $logs[] = [
        'id'       => $originalLineIdx,
        'time'     => $parts[0],
        'operator' => $parts[1],
        'ip'       => $parts[2],
        'action'   => $parts[3],
        'detail'   => $parts[4] ?? '',
    ];
}

echo json_encode(['success' => true, 'logs' => $logs]);
