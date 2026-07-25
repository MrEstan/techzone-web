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

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $stmt = $pdo->query("SELECT * FROM products ORDER BY FIELD(category, 'airpods', 'auriculares', 'parlantes', 'fundas', 'cargadores'), FIELD(brand, 'Apple', 'JBL', ''), name");
        $products = $stmt->fetchAll();
        echo json_encode($products);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
        }
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO products (name, brand, description, price, category, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$input['name'] ?? '', $input['brand'] ?? '', $input['description'] ?? '', intval($input['price'] ?? 0), $input['category'] ?? '', $input['image'] ?? '']);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE products SET name=?, brand=?, description=?, price=?, category=?, image=? WHERE id=?");
        $stmt->execute([$input['name'] ?? '', $input['brand'] ?? '', $input['description'] ?? '', intval($input['price'] ?? 0), $input['category'] ?? '', $input['image'] ?? '', $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>