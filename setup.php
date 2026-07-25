<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS techzoneuy");
    $pdo->exec("USE techzoneuy");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        brand VARCHAR(100) DEFAULT '',
        description TEXT,
        price INT NOT NULL DEFAULT 0,
        category VARCHAR(50) NOT NULL,
        image MEDIUMBLOB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(200) DEFAULT '',
        customer_phone VARCHAR(30) DEFAULT '',
        items JSON,
        total INT NOT NULL DEFAULT 0,
        status VARCHAR(20) DEFAULT 'pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $adminPassword = password_hash('techzone2024', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES ('admin', ?) ON DUPLICATE KEY UPDATE password = ?");
    $stmt->execute([$adminPassword, $adminPassword]);

    echo "<h2>TECHZONEUY - Setup completado</h2>";
    echo "<p style='color:green; font-size:18px;'>✅ Base de datos creada correctamente</p>";
    echo "<p>Usuario: <b>admin</b></p>";
    echo "<p>Contraseña: <b>techzone2024</b></p>";
    echo "<p>Ahora elimina este archivo (setup.php) por seguridad.</p>";
    echo "<p><a href='admin.html'>Ir al Panel de Admin</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>