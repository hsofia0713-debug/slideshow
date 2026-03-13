<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

$dataFile  = '../data.json';
$targetDir = "../Files/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = $_POST['id']    ?? '';
    $title = $_POST['title'] ?? '';
    $data  = json_decode(file_get_contents($dataFile), true);
    $success = false;
    $updated = null;

    foreach ($data as &$item) {
        if ($item['id'] === $id) {
            $oldTitle    = $item['title'];
            $item['title']   = $title;
            $item['linkUrl'] = trim($_POST['linkUrl'] ?? '');

            if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
                if (!empty($item['imgPath']) && file_exists($item['imgPath'])) unlink($item['imgPath']);
                $newName = time() . "_img_" . basename($_FILES['img']['name']);
                move_uploaded_file($_FILES['img']['tmp_name'], $targetDir . $newName);
                $item['imgPath'] = $targetDir . $newName;
            }

            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                if (!empty($item['pdfPath']) && file_exists($item['pdfPath'])) unlink($item['pdfPath']);
                $newName = time() . "_pdf_" . basename($_FILES['pdf']['name']);
                move_uploaded_file($_FILES['pdf']['tmp_name'], $targetDir . $newName);
                $item['pdfPath'] = $targetDir . $newName;
            }

            $success = true;
            $updated = $item;
            $logDetail = $oldTitle !== $title ? "標題：{$oldTitle} → {$title}" : "標題：{$title}";
            break;
        }
    }

    if ($success && file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        writeAuditLog('編輯公告', $logDetail ?? '');
        echo json_encode([
            'success' => true,
            'imgPath' => $updated['imgPath'] ?? '',
            'pdfPath' => $updated['pdfPath'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗或找不到該 ID']);
    }
}
