<?php
// Migra productos existentes (SQLite o data/products.json) a Supabase
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/database.php';

if (!supabase_enabled()) die("Supabase no configurado - crea .env\n");

// Leer productos locales: prioriza SQLite, fallback data/products.json
$local = [];
try {
    $stmt = $pdo->query("SELECT * FROM products");
    $local = $stmt->fetchAll();
    echo "Leídos ".count($local)." productos de SQLite\n";
} catch(Exception $e){
    $file = __DIR__ . '/../data/products.json';
    if (file_exists($file)) $local = json_decode(file_get_contents($file), true) ?: [];
    echo "Leídos ".count($local)." productos de data/products.json\n";
}
if (empty($local)) die("Nada que migrar\n");

$count=0;
foreach($local as $p){
    $image = $p['image'] ?? '';
    // Si es base64, subir a Storage
    if (str_starts_with($image, 'data:image/')) {
        try { $image = supabase_upload_image($image, 'products'); echo "  Imagen subida: $image\n"; } catch(Exception $e){ echo "  Error imagen {$p['name']}: ".$e->getMessage()."\n"; }
    }
    $payload = [
        'name'=> $p['name'] ?? '',
        'brand'=> $p['brand'] ?? '',
        'description'=> $p['description'] ?? '',
        'price'=> intval($p['price'] ?? 0),
        'category'=> $p['category'] ?? 'accesorios',
        'image'=> $image
    ];
    try {
        supabase_request('POST', '/rest/v1/products', $payload, true);
        $count++; echo "  Migrado: {$payload['name']}\n";
    } catch(Exception $e){ echo "  Error {$payload['name']}: ".$e->getMessage()."\n"; }
}
echo "Migración completa: $count productos\n";
