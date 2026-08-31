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

$dataFile = __DIR__ . '/../data/orders.json';

function read_orders() {
    global $dataFile;
    if (!file_exists($dataFile)) return [];
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}
function write_orders($list) {
    global $dataFile;
    $dir = dirname($dataFile);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($dataFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $list = read_orders();
        usort($list, fn($a,$b)=> strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0'));
        // items ya viene como array en JSON file, no necesita json_decode
        echo json_encode($list);
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $list = read_orders();
        $maxId = 0; foreach ($list as $o) $maxId = max($maxId, intval($o['id'] ?? 0));
        $newId = $maxId + 1;
        $newOrder = [
            'id' => $newId,
            'customer_name' => trim($input['customer_name'] ?? ''),
            'customer_phone' => trim($input['customer_phone'] ?? ''),
            'items' => $input['items'] ?? [],
            'total' => intval($input['total'] ?? 0),
            'status' => 'pendiente',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $list[] = $newOrder;
        write_orders($list);
        echo json_encode(['success' => true, 'id' => $newId]);
        break;

    case 'update_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $status = $input['status'] ?? 'pendiente';
        $list = read_orders();
        $found=false;
        foreach ($list as &$o) if (intval($o['id']) === $id) { $o['status']=$status; $found=true; break; }
        unset($o);
        if (!$found){ http_response_code(404); echo json_encode(['error'=>'Pedido no encontrado']); exit; }
        write_orders($list);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $list = read_orders();
        $orig=count($list);
        $list = array_values(array_filter($list, fn($o)=> intval($o['id']) !== $id));
        if (count($list)===$orig){ http_response_code(404); echo json_encode(['error'=>'Pedido no encontrado']); exit; }
        write_orders($list);
        echo json_encode(['success'=>true]);
        break;

    case 'clear':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }
        write_orders([]);
        echo json_encode(['success'=>true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error'=>'Acción no válida']);
        break;
}
