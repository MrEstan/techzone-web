<?php
// config/supabase.php - lee credenciales desde .env o variables de entorno
// No commitear keys reales, usar .env

function env($key, $default = null) {
    // 1. getenv
    $v = getenv($key);
    if ($v !== false) return $v;
    // 2. $_ENV
    if (isset($_ENV[$key])) return $_ENV[$key];
    // 3. .env file en raíz
    static $dotenv = null;
    if ($dotenv === null) {
        $dotenv = [];
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k); $v = trim($v);
                $v = trim($v, '"\'');
                $dotenv[$k] = $v;
                if (!getenv($k)) putenv("$k=$v");
            }
        }
    }
    return $dotenv[$key] ?? $default;
}

function supabase_config() {
    return [
        'url' => env('SUPABASE_URL', env('NEXT_PUBLIC_SUPABASE_URL', '')),
        'anon_key' => env('SUPABASE_ANON_KEY', env('NEXT_PUBLIC_SUPABASE_ANON_KEY', env('NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY', ''))),
        'service_key' => env('SUPABASE_SERVICE_KEY', env('SUPABASE_SECRET_KEY', env('NEXT_PUBLIC_SUPABASE_SECRET_KEY',''))),
        'bucket' => env('SUPABASE_BUCKET', 'product-images'),
    ];
}

function supabase_enabled() {
    $c = supabase_config();
    return !empty($c['url']) && (!empty($c['anon_key']) || !empty($c['service_key']));
}

// Helper REST API
function supabase_request($method, $path, $body = null, $useServiceKey = false) {
    $cfg = supabase_config();
    $url = rtrim($cfg['url'], '/') . $path;
    $key = $useServiceKey && !empty($cfg['service_key']) ? $cfg['service_key'] : $cfg['anon_key'];

    $ch = curl_init($url);
    $headers = [
        "apikey: $key",
        "Authorization: Bearer $key",
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    // Supabase requiere SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new Exception("cURL error: $err");
    $data = json_decode($res, true);
    // Si es array asociativo con error
    if ($code >= 400) {
        $msg = is_array($data) ? json_encode($data) : $res;
        throw new Exception("Supabase $method $path -> $code: $msg");
    }
    return $data;
}

// Subir imagen base64 a Storage y retornar URL pública
function supabase_upload_image($base64DataUri, $prefix = 'products') {
    $cfg = supabase_config();
    if (!supabase_enabled()) return $base64DataUri; // fallback sin supabase

    // Validar data URI
    if (!str_starts_with($base64DataUri, 'data:image/')) {
        // Ya es URL (img/... o https://) -> no subir
        return $base64DataUri;
    }
    // data:image/png;base64,xxxxx
    if (!preg_match('#^data:(image/[^;]+);base64,(.+)$#', $base64DataUri, $m)) return $base64DataUri;
    $mime = $m[1];
    $b64 = $m[2];
    $binary = base64_decode($b64);
    if ($binary === false) throw new Exception("Base64 inválido");

    $ext = match($mime) {
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        default => 'png'
    };
    $filename = $prefix . '/' . uniqid() . '.' . $ext;
    $bucket = $cfg['bucket'];
    $url = rtrim($cfg['url'], '/') . "/storage/v1/object/$bucket/$filename";
    $key = !empty($cfg['service_key']) ? $cfg['service_key'] : $cfg['anon_key'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $key",
        "Authorization: Bearer $key",
        "Content-Type: $mime",
        "x-upsert: true"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $binary);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new Exception("Upload cURL: $err");
    if ($code >= 400) throw new Exception("Upload $code: $res");

    // URL pública
    return rtrim($cfg['url'], '/') . "/storage/v1/object/public/$bucket/$filename";
}
