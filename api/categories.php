<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/supabase.php';

$useSupabase = supabase_enabled();
$dataFile = __DIR__ . '/../data/categories.json';

function read_cats_file(){ global $dataFile; if(!file_exists($dataFile)) return []; $j=json_decode(file_get_contents($dataFile),true); return is_array($j)?$j:[]; }
function write_cats_file($list){ global $dataFile; $dir=dirname($dataFile); if(!is_dir($dir)) mkdir($dir,0777,true); file_put_contents($dataFile, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX); }
function slugify($str){ $s=strtolower(trim($str)); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-'); }

$action = $_GET['action'] ?? 'list';

try {
if ($useSupabase) {
    try {
    switch($action){
        case 'list':
            $cats = supabase_request('GET','/rest/v1/categories?select=*&order=name.asc');
            echo json_encode($cats); break;
        case 'create':
            if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();}
            $input=json_decode(file_get_contents('php://input'),true);
            $name=trim($input['name']??''); if($name===''){http_response_code(400);echo json_encode(['error'=>'Nombre requerido']);exit;}
            $slug = slugify($input['slug'] ?? $name);
            $payload=['slug'=>$slug,'name'=>$name];
            $res=supabase_request('POST','/rest/v1/categories',$payload,true);
            echo json_encode(['success'=>true,'data'=>$res[0]??$payload]); break;
        case 'delete':
            if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();}
            $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $slug=trim($input['slug']??'');
            if($id) supabase_request('DELETE',"/rest/v1/categories?id=eq.$id",null,true);
            elseif($slug) supabase_request('DELETE',"/rest/v1/categories?slug=eq.$slug",null,true);
            else {http_response_code(400);echo json_encode(['error'=>'id o slug requerido']);exit;}
            echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
    } catch(Exception $e){
        // Si la tabla no existe en Supabase, fallback a archivo local
        if (str_contains($e->getMessage(),'PGRST205') || str_contains($e->getMessage(),'Could not find the table')) {
            // fallback file
            switch($action){
                case 'list': echo json_encode(read_cats_file()); break;
                case 'create':
                    $input=json_decode(file_get_contents('php://input'),true); $name=trim($input['name']??''); if($name===''){http_response_code(400);echo json_encode(['error'=>'Nombre requerido']);exit;}
                    $slug=slugify($input['slug']??$name); $list=read_cats_file(); foreach($list as $c) if($c['slug']===$slug){http_response_code(409);echo json_encode(['error'=>'Categoría ya existe']);exit;}
                    $maxId=0; foreach($list as $c) $maxId=max($maxId,intval($c['id']??0)); $new=['id'=>$maxId+1,'slug'=>$slug,'name'=>$name]; $list[]=$new; write_cats_file($list); echo json_encode(['success'=>true,'data'=>$new]); break;
                case 'delete':
                    $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $slug=trim($input['slug']??''); $list=read_cats_file(); $orig=count($list); $list=array_values(array_filter($list, fn($c)=> ($id? intval($c['id'])!==$id : $c['slug']!==$slug))); if(count($list)===$orig){http_response_code(404);echo json_encode(['error'=>'No encontrada']);exit;} write_cats_file($list); echo json_encode(['success'=>true]); break;
                default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
            }
        } else throw $e;
    }
} else {
    switch($action){
        case 'list': echo json_encode(read_cats_file()); break;
        case 'create':
            if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();}
            $input=json_decode(file_get_contents('php://input'),true); $name=trim($input['name']??''); if($name===''){http_response_code(400);echo json_encode(['error'=>'Nombre requerido']);exit;}
            $slug=slugify($input['slug']??$name); $list=read_cats_file();
            foreach($list as $c) if($c['slug']===$slug){http_response_code(409);echo json_encode(['error'=>'Categoría ya existe']);exit;}
            $maxId=0; foreach($list as $c) $maxId=max($maxId,intval($c['id']??0));
            $new=['id'=>$maxId+1,'slug'=>$slug,'name'=>$name]; $list[]=$new; write_cats_file($list);
            echo json_encode(['success'=>true,'data'=>$new]); break;
        case 'delete':
            if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit();}
            $input=json_decode(file_get_contents('php://input'),true); $id=intval($input['id']??0); $slug=trim($input['slug']??'');
            $list=read_cats_file(); $orig=count($list);
            $list=array_values(array_filter($list, fn($c)=> ($id? intval($c['id'])!==$id : $c['slug']!==$slug)));
            if(count($list)===$orig){http_response_code(404);echo json_encode(['error'=>'No encontrada']);exit;}
            write_cats_file($list); echo json_encode(['success'=>true]); break;
        default: http_response_code(400); echo json_encode(['error'=>'Acción no válida']); break;
    }
}
} catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
