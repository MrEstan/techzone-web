<?php
require_once __DIR__ . '/../config/supabase.php';
header('Content-Type: application/json; charset=utf-8');

$cfg = supabase_config();
if (!supabase_enabled()) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'Supabase no configurado. Crea .env con SUPABASE_URL y keys', 'config'=>$cfg]);
    exit;
}

try {
    // Test 1: listar productos
    $products = supabase_request('GET', '/rest/v1/products?select=*&limit=1');
    // Test 2: subir imagen 1x1
    $testImg = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';
    $url = null;
    try { $url = supabase_upload_image($testImg, 'test'); } catch(Exception $e){ $url = 'upload_error: '.$e->getMessage(); }

    echo json_encode([
        'ok'=>true,
        'supabase_url'=>$cfg['url'],
        'bucket'=>$cfg['bucket'],
        'products_count_sample'=>count($products),
        'storage_test_url'=>$url,
        'message'=>'Conexión OK - probá crear un producto desde admin.html'
    ], JSON_PRETTY_PRINT);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
