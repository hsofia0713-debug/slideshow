<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

$dataFile = '../data.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id']       ?? '';
    $isPinned = filter_var($_POST['isPinned'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$id) { echo json_encode(['success' => false, 'message' => '缺少 ID']); exit; }

    $data    = json_decode(file_get_contents($dataFile), true);
    $success = false;

    foreach ($data as &$item) {
        if ((string)$item['id'] === (string)$id) {
            $item['isPinned'] = $isPinned;
            $action = $isPinned ? '跨月釘選' : '取消跨月釘選';
            writeAuditLog($action, $item['title'] ?? $id);
            $success = true;
            break;
        }
    }

    if ($success && file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗']);
    }
}
