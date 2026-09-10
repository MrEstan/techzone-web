<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/supabase.php';

$useSupabase = supabase_enabled();
$dataFile = __DIR__ . '/../data/products.json';

function read_products_file() {
    global $dataFile;
    if (!file_exists($dataFile)) return [];
    $j = json_decode(file_get_contents($dataFile), true);
    return is_array($j) ? $j : [];
}
function write_products_file($list) {
    global $dataFile;
    $dir = dirname($dataFile);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($dataFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function sort_products_local(&$list) {
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

try {
if ($useSupabase) {
    switch ($action) {
        case 'list':
            $products = supabase_request('GET', '/rest/v1/products?select=*');
            sort_products_local($products);
            echo json_encode($products); break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $res = supabase_request('GET', "/rest/v1/products?id=eq.$id&select=*");
            if (!empty($res[0])) echo json_encode($res[0]);
            else { http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); }
            break;
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input = json_decode(file_get_contents('php://input'), true);
            $imageUrl = $input['image'] ?? '';
            if (str_starts_with($imageUrl, 'data:image/')) $imageUrl = supabase_upload_image($imageUrl, 'products');
            $payload = ['name'=>trim($input['name']??''),'brand'=>trim($input['brand']??''),'description'=>trim($input['description']??''),'price'=>intval($input['price']??0),'category'=>trim($input['category']??''),'image'=>$imageUrl];
            $res = supabase_request('POST', '/rest/v1/products', $payload, true);
            echo json_encode(['success'=>true,'id'=>$res[0]['id'] ?? null]); break;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input = json_decode(file_get_contents('php://input'), true); $id=intval($input['id']??0);
            $imageUrl = $input['image'] ?? null;
            if ($imageUrl !== null && str_starts_with($imageUrl, 'data:image/')) $imageUrl = supabase_upload_image($imageUrl, 'products');
            $payload = ['name'=>trim($input['name']??''),'brand'=>trim($input['brand']??''),'description'=>trim($input['description']??''),'price'=>intval($input['price']??0),'category'=>trim($input['category']??'')];
            if ($imageUrl !== null && $imageUrl !== '') $payload['image']=$imageUrl;
            supabase_request('PATCH', "/rest/v1/products?id=eq.$id", $payload, true);
            echo json_encode(['success'=>true]); break;
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0);
            supabase_request('DELETE', "/rest/v1/products?id=eq.$id", null, true);
            echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
} else {
    // Fallback archivo local
    switch ($action) {
        case 'list': $list=read_products_file(); sort_products_local($list); echo json_encode($list); break;
        case 'get': $id=intval($_GET['id']??0); $list=read_products_file(); foreach($list as $p) if(intval($p['id'])===$id){echo json_encode($p); exit;} http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); break;
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input=json_decode(file_get_contents('php://input'),true); $list=read_products_file(); $maxId=0; foreach($list as $p) $maxId=max($maxId,intval($p['id']??0));
            $newId=$maxId+1; $newProduct=['id'=>$newId,'name'=>trim($input['name']??''),'brand'=>trim($input['brand']??''),'description'=>trim($input['description']??''),'price'=>intval($input['price']??0),'category'=>trim($input['category']??''),'image'=>$input['image']??'','created_at'=>date('Y-m-d H:i:s')];
            $list[]=$newProduct; write_products_file($list); echo json_encode(['success'=>true,'id'=>$newId]); break;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $list=read_products_file(); $found=false; foreach($list as &$p) if(intval($p['id'])===$id){$p['name']=trim($input['name']??$p['name']);$p['brand']=trim($input['brand']??$p['brand']);$p['description']=trim($input['description']??$p['description']);$p['price']=intval($input['price']??$p['price']);$p['category']=trim($input['category']??$p['category']); if(isset($input['image'])&&$input['image']!=='')$p['image']=$input['image']; $found=true; break;} unset($p); if(!$found){http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); exit;} write_products_file($list); echo json_encode(['success'=>true]); break;
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }
            $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $list=read_products_file(); $orig=count($list); $list=array_values(array_filter($list, fn($p)=>intval($p['id'])!==$id)); if(count($list)===$orig){http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); exit;} write_products_file($list); echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
}
} catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
