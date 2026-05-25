<?php
/**
 * api/delete_audit_log.php
 * 刪除指定行的操作紀錄（需登入）
 * POST JSON { "id": <原始行號 0-based> }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/auth_check.php';   // 未登入直接 403

// ── 解析請求 ──────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
$lineIdx = isset($body['id']) ? (int)$body['id'] : -1;

if ($lineIdx < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的紀錄 ID']);
    exit;
}

// ── 讀取原始檔案 ──────────────────────────────────────
if (!file_exists(AUDIT_LOG_FILE)) {
    echo json_encode(['success' => false, 'message' => '找不到 audit.log']);
    exit;
}

$lines = file(AUDIT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lineIdx >= count($lines)) {
    echo json_encode(['success' => false, 'message' => '行號超出範圍，紀錄可能已被刪除']);
    exit;
}

// ── 確認該行確實是「新增公告」或「覆蓋公告」，防止誤刪其他類型 ──
$parts  = explode("\t", $lines[$lineIdx], 5);
$action = $parts[3] ?? '';

if ($action !== '新增公告' && $action !== '覆蓋公告') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '此筆紀錄不允許刪除（僅限新增／覆蓋公告）']);
    exit;
}

// ── 移除該行並重新寫回 ────────────────────────────────
unset($lines[$lineIdx]);
$newContent = implode(PHP_EOL, array_values($lines));
if (!empty($newContent)) {
    $newContent .= PHP_EOL;
}

if (file_put_contents(AUDIT_LOG_FILE, $newContent, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '寫入失敗，請確認檔案權限']);
    exit;
}

echo json_encode(['success' => true]);
