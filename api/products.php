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

$dataFile = __DIR__ . '/../data/products.json';

function read_products() {
    global $dataFile;
    if (!file_exists($dataFile)) return [];
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}
function write_products($list) {
    global $dataFile;
    $dir = dirname($dataFile);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($dataFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function sort_products(&$list) {
    $orderCat = ['airpods'=>1,'auriculares'=>2,'parlantes'=>3,'fundas'=>4,'cargadores'=>5,'accesorios'=>6];
    $orderBrand = ['Apple'=>1,'JBL'=>2];
    usort($list, function($a,$b) use ($orderCat,$orderBrand){
        $ca = $orderCat[$a['category'] ?? ''] ?? 99;
        $cb = $orderCat[$b['category'] ?? ''] ?? 99;
        if ($ca !== $cb) return $ca <=> $cb;
        $ba = $orderBrand[$a['brand'] ?? ''] ?? 99;
        $bb = $orderBrand[$b['brand'] ?? ''] ?? 99;
        if ($ba !== $bb) return $ba <=> $bb;
        return strcmp($a['name'] ?? '', $b['name'] ?? '');
    });
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $list = read_products();
        sort_products($list);
        echo json_encode($list);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $list = read_products();
        foreach ($list as $p) if (intval($p['id']) === $id) { echo json_encode($p); exit; }
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado']);
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $list = read_products();
        $maxId = 0; foreach ($list as $p) $maxId = max($maxId, intval($p['id'] ?? 0));
        $newId = $maxId + 1;
        $newProduct = [
            'id' => $newId,
            'name' => trim($input['name'] ?? ''),
            'brand' => trim($input['brand'] ?? ''),
            'description' => trim($input['description'] ?? ''),
            'price' => intval($input['price'] ?? 0),
            'category' => trim($input['category'] ?? ''),
            'image' => $input['image'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $list[] = $newProduct;
        write_products($list);
        echo json_encode(['success' => true, 'id' => $newId]);
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $list = read_products();
        $found = false;
        foreach ($list as &$p) {
            if (intval($p['id']) === $id) {
                $p['name'] = trim($input['name'] ?? $p['name']);
                $p['brand'] = trim($input['brand'] ?? $p['brand']);
                $p['description'] = trim($input['description'] ?? $p['description']);
                $p['price'] = intval($input['price'] ?? $p['price']);
                $p['category'] = trim($input['category'] ?? $p['category']);
                if (isset($input['image']) && $input['image'] !== '') $p['image'] = $input['image'];
                $found = true; break;
            }
        }
        unset($p);
        if (!$found) { http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); exit; }
        write_products($list);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $list = read_products();
        $orig = count($list);
        $list = array_values(array_filter($list, fn($p)=> intval($p['id']) !== $id));
        if (count($list) === $orig) { http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); exit; }
        write_products($list);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
