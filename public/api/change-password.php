<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta']);
    exit();
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo json_encode(['success' => true]);
?>