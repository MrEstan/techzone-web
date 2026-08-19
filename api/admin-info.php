<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$admin = tz_read_json('admin.json');
echo json_encode(['username' => $admin['username'] ?? 'admin']);
?>
