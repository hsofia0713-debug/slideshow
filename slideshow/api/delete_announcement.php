<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

$dataFile = '../data.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (string)$_POST['id'] : '';

    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少公告 ID']);
        exit;
    }

    if (file_exists($dataFile)) {
        $data    = json_decode(file_get_contents($dataFile), true);
        $deleted = null;

        foreach ($data as $item) {
            if ((string)$item['id'] === $id) { $deleted = $item; break; }
        }

        $newData = array_values(array_filter($data, fn($item) => (string)$item['id'] !== $id));

        if (file_put_contents($dataFile, json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
            writeAuditLog('刪除公告', ($deleted['status'] ?? '') . '｜' . ($deleted['title'] ?? $id));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '檔案寫入失敗']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '檔案不存在']);
    }
}
