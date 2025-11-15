-- ============================================================
CREATE DATABASE sistema_vencimiento
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sistema_vencimiento;

-- ============================================================
-- TABLA: rol
-- ============================================================
CREATE TABLE Rol (
  idRol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: usuario
-- ============================================================
CREATE TABLE Usuario (
  idUsuario INT AUTO_INCREMENT PRIMARY KEY,
  idRol INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL, -- guardar hash de password_hash()
  activo TINYINT(1) DEFAULT 1,
  fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idRol) REFERENCES Rol(idRol)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: proveedor
-- ============================================================
CREATE TABLE Proveedor (
  idProveedor INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  cuit VARCHAR(30),
  telefono VARCHAR(50),
  correo VARCHAR(150),
  direccion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: categoria
-- ============================================================
CREATE TABLE Categoria (
  idCategoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: producto (incluye codigo_producto)
-- ============================================================
CREATE TABLE Producto (
  idProducto INT AUTO_INCREMENT PRIMARY KEY,
  idCategoria INT NULL,
  codigoProducto VARCHAR(100) NOT NULL UNIQUE, -- SKU del producto
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT,
  fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: lote (incluye codigo_lote y referencia a producto)
-- ============================================================
CREATE TABLE Lote (
  idLote INT AUTO_INCREMENT PRIMARY KEY,
  idProducto INT NOT NULL,
  idProveedor INT NULL,
  codigoLote VARCHAR(120) NOT NULL,
  fechaIngreso DATE NOT NULL,
  fechaVencimiento DATE NOT NULL,
  cantidad INT NOT NULL DEFAULT 0,
  estado VARCHAR(30) NOT NULL DEFAULT 'activo',
  fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  -- restricción: un mismo producto no debe tener dos lotes con el mismo codigo
  UNIQUE KEY ux_producto_codigo_lote (idProducto, codigoLote),
  INDEX idx_fecha_vencimiento (fechaVencimiento),
  FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (idProveedor) REFERENCES Proveedor(idProveedor)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: umbral_alerta (umbral por rol o global)
-- ============================================================
CREATE TABLE Umbral_Alerta (
  idUmbral INT AUTO_INCREMENT PRIMARY KEY,
  idRol INT NULL,            -- NULL => umbral global
  diasAntes INT NOT NULL,    -- p.ej. 30, 15, 7
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idRol) REFERENCES Rol(idRol)
    ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY ux_rol_dias (idRol, diasAntes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: alerta (registra alertas generadas por lote+umbral)
-- ============================================================
CREATE TABLE Alerta (
  idAlerta INT AUTO_INCREMENT PRIMARY KEY,
  idLote INT NOT NULL,
  fechaGenerada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo VARCHAR(60) NOT NULL DEFAULT 'vencimiento',
  diasUmbral INT NULL,       -- 30,15,7 → qué umbral generó la alerta
  diasFaltantes INT NULL,    -- DATEDIFF en momento de generación
  mensaje TEXT,
  estado VARCHAR(30) NOT NULL DEFAULT 'nueva', -- nueva, revisada, descartada
  notificada TINYINT(1) DEFAULT 0,
  fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_alerta_lote (idLote),
  INDEX idx_alerta_estado (estado),
  FOREIGN KEY (idLote) REFERENCES Lote(idLote)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATOS DE EJEMPLO / SEED
-- ============================================================
-- Roles
INSERT INTO Rol (nombre, descripcion) VALUES
('Administrador','Acceso total al sistema y Recibe alertas de vencimiento'),
('Gerente','Acceso total al sistema y recibe alertas de vencimientos'),
('Encargado','Encargado de depósito');

-- Usuario demo (hash de 'admin123' generado con password_hash)
INSERT INTO Usuario (idRol, nombre, correo, contrasena) VALUES
(1, 'Administrador Demo', 'admin@demo.com', '$2y$10$wQb4lJrZyPwhWhSAXzDdBeKMwCfxzBPzib2l4f2F/jbZ9DhRBdO2i');

INSERT INTO Usuario (idRol, nombre, correo, contrasena) VALUES
(3, 'Encargado Demo', 'encargado@demo.com', '$2y$10$Ex2PpSEfuKDP3GDZO8BSj.FQRVcO14HISUtWEY/2SoZL07NowSaiW');

UPDATE Usuario
SET contrasena = '$2y$10$qmuZJwE13dd9fy8xRMzJTeZXQmEeER6IGem6KjEo6p7vTsIq/n14K'
WHERE correo = 'admin@demo.com';

UPDATE Usuario
SET contrasena = '$2y$10$qhY96Kv7MU5ndZKzKhT5Iui8TvS28jzMZx7v/wDBWuXQfery44jSq'
WHERE correo = 'encargado@demo.com';

-- Proveedores
INSERT INTO Proveedor (nombre, cuit, telefono, correo, direccion) VALUES
('Proveedor Central','30-00000000-0','388-111111','proveedor@empresa.com','Av. San Martín 123'),
('Lácteos del Norte','30-22222222-2','388-222222','contacto@lacteos.com','Ruta 9 KM 5');

INSERT INTO Proveedor (nombre, cuit, telefono, correo, direccion) VALUES
('Bebidas SRL', '20-12131415-0', '387-5222555', 'compras@bebidas.com', 'Av. Libertador 233');

-- Categorias
INSERT INTO Categoria (nombre, descripcion) VALUES
('Lácteos','Productos derivados de la leche'),
('Bebidas','Refrescos, aguas, jugos');

-- Productos (con codigo_producto)
INSERT INTO Producto (idCategoria, codigoProducto, nombre, descripcion) VALUES
(1,'CREM-001','Crema de leche 1L','Crema pasteurizada de 1 litro'),
(1,'QUES-500','Queso cheddar 500g','Queso cheddar en bloque'),
(2,'AGUA-500','Agua mineral 500ml','Agua mineral sin gas');

-- Lotes (cada lote tiene su codigo_lote)
INSERT INTO Lote (idProducto, idProveedor, codigoLote, fechaIngreso, fechaVencimiento, cantidad) VALUES
(1,1,'L-CREM-20251101','2025-10-01', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100),
(2,2,'L-QUES-20251015','2025-09-15', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 50),
(3,1,'L-AGUA-20251029','2025-09-29', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 200);

-- Umbrales por defecto (globales: id_rol NULL)
INSERT INTO Umbral_Alerta (idRol, diasAntes, activo) VALUES
(NULL, 30, 1),
(NULL, 15, 1),
(NULL, 7, 1);

-- Verificaciones rápidas (opcional)
SELECT 'OK Productos' AS Info, COUNT(*) AS total_productos FROM Producto;
SELECT 'OK Lotes' AS Info, COUNT(*) AS total_lotes FROM Lote;
SELECT 'OK Umbrales' AS Info, COUNT(*) AS total_umbral FROM Umbral_Alerta;

SELECT idUsuario, idRol, nombre, correo, contrasena, activo
FROM Usuario
WHERE correo = 'admin@demo.com' OR nombre = 'admin@demo.com' LIMIT 1;

-- ACTUALIZACION CONTRASEÑA DEMO
UPDATE Usuario
SET contrasena = '$2y$10$PEovNjJN.o2.MlxYLz8u.uLu/1PWE1hgQTM4mi2RUZNbb3UsYx6x.'
WHERE correo = 'admin@demo.com';

-- ELIMINAR COLUMNA
ALTER TABLE Alerta DROP COLUMN fechaCreacion;



SHOW TABLES;
SELECT COUNT(*) AS total_roles FROM Rol;
SELECT COUNT(*) AS total_usuarios FROM Usuario;
SELECT COUNT(*) AS total_lotes FROM Lote;
SELECT idLote, codigoLote, fechaVencimiento, DATEDIFF(fechaVencimiento, CURDATE()) AS dias_faltantes FROM Lote ORDER BY fechaVencimiento ASC;
SELECT COUNT(*) AS total_umbral FROM Umbral_Alerta;
SELECT COUNT(*) AS total_alertas FROM Alerta;

SELECT * FROM Lote;

SELECT * FROM Usuario;
