<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (strlen($newPassword) < 4) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres']);
    exit();
}

$stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
    exit();
}

$hashed = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->execute([$hashed]);

echo json_encode(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
?>