<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

$absTargetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "Files" . DIRECTORY_SEPARATOR;
$relTargetDir = "Files/";
$dataFile = '../data.json';

if (!is_dir($absTargetDir)) mkdir($absTargetDir, 0777, true);

$response = ['success' => false, 'message' => '未知錯誤'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = $_POST['title']   ?? '未命名公告';
    $status = $_POST['status']  ?? '一般公告';
    $date   = date("Y-m-d");
    $imgPath = "";
    $pdfPath = "";

    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $safeFileName = time() . "_" . $key . "_" . basename($file['name']);
            $absPath = $absTargetDir . $safeFileName;
            if (move_uploaded_file($file['tmp_name'], $absPath)) {
                if ($key === 'img') $imgPath = $relTargetDir . $safeFileName;
                if ($key === 'pdf') $pdfPath = $relTargetDir . $safeFileName;
            }
        }
    }

    $currentData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

    $newEntry = [
        'id'        => uniqid(),
        'title'     => $title,
        'status'    => $status,
        'date'      => $date,
        'imgPath'   => $imgPath,
        'pdfPath'   => $pdfPath,
        'linkUrl'   => trim($_POST['linkUrl'] ?? ''),
        'isStarred' => false
    ];

    array_unshift($currentData, $newEntry);

    if (file_put_contents($dataFile, json_encode($currentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        writeAuditLog('新增公告', "{$status}｜{$title}");
        $response = ['success' => true, 'message' => '公告發佈成功', 'data' => $newEntry];
    } else {
        $response['message'] = "無法寫入 data.json，請檢查資料夾權限";
    }
}

echo json_encode($response);
