<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// Lee credenciales desde data/admin.json (fuente real con fallback)
$dataFile = __DIR__ . '/../data/admin.json';
$admin = null;
if (file_exists($dataFile)) {
    $json = json_decode(file_get_contents($dataFile), true);
    if (isset($json['username'], $json['password'])) $admin = $json;
}
if (!$admin) $admin = ['username' => 'admin', 'password' => 'techzone2024'];

// Soporta password hasheado ($2y$) o texto plano
$valid = false;
if ($username === $admin['username']) {
    if (str_starts_with($admin['password'], '$2y$') || str_starts_with($admin['password'], '$argon')) {
        $valid = password_verify($password, $admin['password']);
    } else {
        $valid = hash_equals($admin['password'], $password);
    }
}

if ($valid) {
    echo json_encode(['success' => true, 'message' => 'Login correcto', 'username' => $admin['username']]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
}
?>