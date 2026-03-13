<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

$loggedIn  = !empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$adminName = $_SESSION['admin_name'] ?? '';

if ($loggedIn && isset($_SESSION['login_time'])) {
    if ((time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        session_destroy();
        $loggedIn  = false;
        $adminName = '';
    }
}

echo json_encode(['loggedIn' => $loggedIn, 'adminName' => $adminName]);
