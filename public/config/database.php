<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dbPath = __DIR__ . '/../database.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA foreign_keys=ON");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        brand TEXT DEFAULT '',
        description TEXT DEFAULT '',
        price INTEGER DEFAULT 0,
        category TEXT DEFAULT '',
        image TEXT DEFAULT '',
        created_at TEXT DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT DEFAULT '',
        customer_phone TEXT DEFAULT '',
        items TEXT DEFAULT '[]',
        total INTEGER DEFAULT 0,
        status TEXT DEFAULT 'pendiente',
        created_at TEXT DEFAULT (datetime('now'))
    )");

    $adminCheck = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($adminCheck == 0) {
        $hash = password_hash('techzone2024', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admin_users (username, password) VALUES ('admin', '$hash')");
    }

    $productCheck = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($productCheck == 0) {
        $products = [
            ['AirPods Pro', 'Apple', 'Disfrutá de un sonido de alta calidad con cancelación activa de ruido.', 2000, 'airpods', 'img/airpods-pro.png'],
            ['AirPods 4 (ANC)', 'Apple', 'Los nuevos AirPods 4 con Cancelación Activa de Ruido.', 2000, 'airpods', 'img/airpods-4-anc.png'],
            ['AirPods Max', 'Apple', 'Sonido potente y nítido, con un diseño cómodo y elegante.', 2200, 'airpods', 'img/airpods-max.png'],
            ['JBL TUNE 760', 'JBL', 'Auriculares inalámbricos con JBL Pure Bass y cancelación activa.', 1800, 'auriculares', 'img/jbl-tune-760.png'],
            ['JBL WAVE 300tws', 'JBL', 'Auriculares inalámbricos con JBL Deep Bass.', 1000, 'auriculares', 'img/jbl-wave-300.png'],
            ['JBL TUNE 220', 'JBL', 'Auriculares inalámbricos con JBL Pure Bass.', 1400, 'auriculares', 'img/jbl-tune-220.png'],
            ['JBL FLIP 6', 'JBL', 'Parlante Bluetooth portátil con sonido potente.', 2000, 'parlantes', 'img/jbl-flip-6.png'],
            ['JBL Charge 5', 'JBL', 'Parlante Bluetooth con sonido potente y resistencia al agua.', 2200, 'parlantes', 'img/jbl-charge-5.png'],
            ['JBL GO 3', 'JBL', 'Parlante Bluetooth compacto y resistente al agua.', 800, 'parlantes', 'img/jbl-go-3.png'],
            ['Funda MagSafe Colores', '', 'Funda compatible MagSafe con imanes integrados.', 300, 'fundas', 'img/funda-magsafe-colores.png'],
            ['Templado iPhone', '', 'Templado autoaplicable para iPhone.', 400, 'fundas', 'img/templado-iphone.png'],
            ['Funda Silicona', '', 'Funda de silicona suave y resistente.', 300, 'fundas', 'img/funda-silicona.png'],
            ['Funda MagSafe Apple', 'Apple', 'Funda original Apple con MagSafe.', 300, 'fundas', 'img/funda-magsafe-apple.png'],
            ['Kit Ficha 20W + Cable', '', 'Kit de carga rápida con adaptador de 20W.', 400, 'cargadores', 'img/kit-ficha-20w.png'],
            ['Kit Ficha 20W + Cable C-C', '', 'Kit de carga rápida con cable USB-C.', 400, 'cargadores', 'img/kit-ficha-20w-cc.png'],
            ['Cargador MagSafe iPhone', 'Apple', 'Cargador inalámbrico magnético MagSafe.', 600, 'cargadores', 'img/cargador-magsafe.png'],
            ['Batería MagSafe Apple', 'Apple', 'Batería externa MagSafe.', 1100, 'cargadores', 'img/bateria-magsafe.png'],
        ];
        $stmt = $pdo->prepare("INSERT INTO products (name, brand, description, price, category, image) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute($p);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexion: ' . $e->getMessage()]);
    exit();
}
?>