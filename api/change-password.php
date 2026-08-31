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

if (strlen($newPassword) < 4) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres']);
    exit();
}

$dataFile = __DIR__ . '/../data/admin.json';
$admin = ['username'=>'admin','password'=>'techzone2024'];
if (file_exists($dataFile)) {
    $j = json_decode(file_get_contents($dataFile), true);
    if (isset($j['username'], $j['password'])) $admin = $j;
}

$valid = false;
if (str_starts_with($admin['password'], '$2y$') || str_starts_with($admin['password'], '$argon')) {
    $valid = password_verify($currentPassword, $admin['password']);
} else {
    $valid = hash_equals($admin['password'], $currentPassword);
}

if (!$valid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
    exit();
}

$admin['password'] = $newPassword;
file_put_contents($dataFile, json_encode($admin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo json_encode(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
