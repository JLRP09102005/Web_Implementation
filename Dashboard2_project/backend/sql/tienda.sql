-- sql/schema.sql
CREATE DATABASE IF NOT EXISTS tienda;
USE tienda;

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'en_camino', 'entregado') NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla intermedia pedido_productos (detalle)
CREATE TABLE pedido_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos de ejemplo
INSERT INTO clientes (nombre, email) VALUES
('Ana García', 'ana@email.com'),
('Carlos López', 'carlos@email.com'),
('Marta Sánchez', 'marta@email.com');

INSERT INTO productos (nombre, precio, stock) VALUES
('iPhone 14', 999.99, 10),
('AirPods Pro', 249.99, 25),
('MacBook Air', 1199.99, 5);

INSERT INTO pedidos (cliente_id, fecha, estado) VALUES
(1, '2025-03-01 10:30:00', 'entregado'),
(2, '2025-03-05 15:45:00', 'en_camino'),
(3, '2025-03-07 09:20:00', 'pendiente');

INSERT INTO pedido_productos (pedido_id, producto_id, cantidad) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 1),
(3, 1, 1);