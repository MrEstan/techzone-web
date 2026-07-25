CREATE DATABASE IF NOT EXISTS techzoneuy;
USE techzoneuy;

CREATE TABLE IF NOT EXISTS config (
    id INT PRIMARY KEY DEFAULT 1,
    admin_password VARCHAR(255) NOT NULL,
    store_name VARCHAR(100) DEFAULT 'TECHZONEUY',
    whatsapp VARCHAR(20) DEFAULT '59892489156',
    instagram VARCHAR(100) DEFAULT '@tzone040710',
    location VARCHAR(200) DEFAULT 'Uruguay - Santa Lucía'
);

INSERT INTO config (id, admin_password) VALUES (1, '$2y$10$YourHashedPasswordHere') ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100) DEFAULT '',
    description TEXT,
    price INT NOT NULL DEFAULT 0,
    category VARCHAR(50) NOT NULL,
    image LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(200) DEFAULT '',
    customer_phone VARCHAR(30) DEFAULT '',
    items JSON NOT NULL,
    total INT NOT NULL DEFAULT 0,
    status ENUM('pendiente', 'completado', 'cancelado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_users (username, password) VALUES ('admin', '$2y$10$YourHashedPasswordHere') ON DUPLICATE KEY UPDATE id=id;
