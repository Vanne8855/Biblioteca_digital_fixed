-- Base de Datos: Biblioteca Digital
-- Sistema de Compra y Envío con Geolocalización

-- Tabla de Clientes (Usuarios del sistema)
CREATE TABLE IF NOT EXISTS Clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT DEFAULT 1,
    INDEX idx_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Libros
CREATE TABLE IF NOT EXISTS Libros (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    autor VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    anio_publicacion INT DEFAULT NULL,
    descripcion TEXT,
    cantidad_disponible INT DEFAULT 0,
    precio DECIMAL(10,2) NOT NULL,
    imagen_url VARCHAR(255),
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_titulo (titulo),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Ventas (incluye datos de envío)
CREATE TABLE IF NOT EXISTS Ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_libro INT NOT NULL,
    cantidad_venta INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha_venta DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_venta ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    -- Datos de envío
    direccion_envio TEXT,
    ciudad VARCHAR(100),
    estado VARCHAR(100),
    codigo_postal VARCHAR(10),
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    referencia TEXT,
    instrucciones TEXT,
    CONSTRAINT fk_venta_cliente FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_venta_libro FOREIGN KEY (id_libro) REFERENCES Libros(id_libro) ON DELETE CASCADE,
    INDEX idx_fecha_venta (fecha_venta),
    INDEX idx_estado (estado_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Métodos de Pago (simplificada)
CREATE TABLE IF NOT EXISTS Pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    metodo_pago ENUM('tarjeta_credito', 'tarjeta_debito', 'paypal', 'transferencia') NOT NULL,
    numero_tarjeta_ultimos4 VARCHAR(4),
    nombre_titular VARCHAR(100),
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_pago ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'aprobado',
    CONSTRAINT fk_pago_venta FOREIGN KEY (id_venta) REFERENCES Ventas(id_venta) ON DELETE CASCADE,
    INDEX idx_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo para Clientes
INSERT INTO Clientes (nombre, correo, contrasena, telefono) VALUES
('Juan Pérez', 'juan@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '8781234567'), -- password: password
('María García', 'maria@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '8787654321'),
('Carlos López', 'carlos@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '8781112233'),
('Ana Martínez', 'ana@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '8784445566');

-- Insertar datos de ejemplo para Libros
INSERT INTO Libros (titulo, isbn, autor, categoria, anio_publicacion, descripcion, cantidad_disponible, precio) VALUES
('Cien años de soledad', '978-0307474728', 'Gabriel García Márquez', 'Ficción', 1967, 'Obra maestra del realismo mágico', 15, 299.00),
('Don Quijote de la Mancha', '978-8420412146', 'Miguel de Cervantes', 'Clásico', 1605, 'La novela más importante de la literatura española', 20, 450.00),
('El amor en los tiempos del cólera', '978-0307387387', 'Gabriel García Márquez', 'Romance', 1985, 'Historia de amor que trasciende el tiempo', 10, 320.00),
('1984', '978-0451524935', 'George Orwell', 'Distopía', 1949, 'Novela distópica sobre un futuro totalitario', 25, 280.00),
('Orgullo y prejuicio', '978-0141439518', 'Jane Austen', 'Romance', 1813, 'Clásico de la literatura romántica', 12, 250.00),
('El principito', '978-0156012195', 'Antoine de Saint-Exupéry', 'Infantil', 1943, 'Fábula poética sobre la amistad y el amor', 30, 180.00),
('Crónica de una muerte anunciada', '978-0307387400', 'Gabriel García Márquez', 'Ficción', 1981, 'Novela corta sobre el honor y la venganza', 18, 220.00),
('Harry Potter y la piedra filosofal', '978-8498383447', 'J.K. Rowling', 'Fantasía', 1997, 'Primera aventura del joven mago', 40, 350.00),
('El código Da Vinci', '978-0307474278', 'Dan Brown', 'Thriller', 2003, 'Thriller de misterio y conspiraciones', 22, 380.00),
('La sombra del viento', '978-0143126393', 'Carlos Ruiz Zafón', 'Misterio', 2001, 'Novela sobre libros, amor y secretos', 16, 420.00);
