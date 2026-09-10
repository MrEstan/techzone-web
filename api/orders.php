<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/supabase.php';

$useSupabase = supabase_enabled();
$dataFile = __DIR__ . '/../data/orders.json';
function read_orders_file(){ global $dataFile; if(!file_exists($dataFile)) return []; $j=json_decode(file_get_contents($dataFile),true); return is_array($j)?$j:[]; }
function write_orders_file($list){ global $dataFile; $dir=dirname($dataFile); if(!is_dir($dir)) mkdir($dir,0777,true); file_put_contents($dataFile, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX); }

$action = $_GET['action'] ?? '';
try {
if ($useSupabase) {
    switch($action){
        case 'list': $orders=supabase_request('GET','/rest/v1/orders?select=*&order=created_at.desc'); echo json_encode($orders); break;
        case 'create':
            if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();}
            $input=json_decode(file_get_contents('php://input'),true);
            $payload=['customer_name'=>$input['customer_name']??'','customer_phone'=>$input['customer_phone']??'','items'=>$input['items']??[],'total'=>intval($input['total']??0),'status'=>'pendiente'];
            $res=supabase_request('POST','/rest/v1/orders',$payload,true); echo json_encode(['success'=>true,'id'=>$res[0]['id']??null]); break;
        case 'update_status': $input=json_decode(file_get_contents('php://input'),true); supabase_request('PATCH',"/rest/v1/orders?id=eq.".intval($input['id']??0),['status'=>$input['status']??'pendiente'],true); echo json_encode(['success'=>true]); break;
        case 'delete': $input=json_decode(file_get_contents('php://input'),true); supabase_request('DELETE',"/rest/v1/orders?id=eq.".intval($input['id']??0),null,true); echo json_encode(['success'=>true]); break;
        case 'clear': $all=supabase_request('GET','/rest/v1/orders?select=id'); foreach($all as $o) supabase_request('DELETE',"/rest/v1/orders?id=eq.".$o['id'],null,true); echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
} else {
    switch($action){
        case 'list': $list=read_orders_file(); usort($list, fn($a,$b)=>strtotime($b['created_at']??'0')<=>strtotime($a['created_at']??'0')); echo json_encode($list); break;
        case 'create': if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();} $input=json_decode(file_get_contents('php://input'),true); $list=read_orders_file(); $maxId=0; foreach($list as $o) $maxId=max($maxId,intval($o['id']??0)); $newId=$maxId+1; $newOrder=['id'=>$newId,'customer_name'=>trim($input['customer_name']??''),'customer_phone'=>trim($input['customer_phone']??''),'items'=>$input['items']??[],'total'=>intval($input['total']??0),'status'=>'pendiente','created_at'=>date('Y-m-d H:i:s')]; $list[]=$newOrder; write_orders_file($list); echo json_encode(['success'=>true,'id'=>$newId]); break;
        case 'update_status': $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $status=$input['status']??'pendiente'; $list=read_orders_file(); $found=false; foreach($list as &$o) if(intval($o['id'])===$id){$o['status']=$status;$found=true;break;} unset($o); if(!$found){http_response_code(404);echo json_encode(['error'=>'Pedido no encontrado']);exit;} write_orders_file($list); echo json_encode(['success'=>true]); break;
        case 'delete': $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $list=read_orders_file(); $orig=count($list); $list=array_values(array_filter($list, fn($o)=>intval($o['id'])!==$id)); if(count($list)===$orig){http_response_code(404);echo json_encode(['error'=>'Pedido no encontrado']);exit;} write_orders_file($list); echo json_encode(['success'=>true]); break;
        case 'clear': write_orders_file([]); echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
}
} catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
