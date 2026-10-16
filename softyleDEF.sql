CREATE DATABASE IF NOT EXISTS softyle;
USE softyle;

CREATE TABLE Persona (
    CI VARCHAR(8) PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150),
    Rol VARCHAR(20) NOT NULL
);

CREATE TABLE Cliente (
    CI VARCHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Persona(CI)
);

CREATE TABLE Operario (
    CI VARCHAR(8) PRIMARY KEY,
    Estado VARCHAR(20) NOT NULL,
    hora_ingreso TIME NOT NULL,
    FOREIGN KEY (CI) REFERENCES Persona(CI)
);

CREATE TABLE Deposito (
    id_deposito INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    localidad VARCHAR(100) NOT NULL
);

CREATE TABLE Zona (
    id_zona INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    capacidad_total INT NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL,
    id_deposito INT NOT NULL,
    FOREIGN KEY (id_deposito) REFERENCES Deposito(id_deposito)
);

CREATE TABLE Ubicacion (
    id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
    pasillo VARCHAR(20) NOT NULL,
    estante VARCHAR(20) NOT NULL,
    posicion VARCHAR(20) NOT NULL,
    disponible BOOLEAN NOT NULL DEFAULT TRUE,
    id_zona INT NOT NULL,
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona)
);

CREATE TABLE Categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

CREATE TABLE Producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    estado_producto VARCHAR(30) NOT NULL,
    id_categoria INT NOT NULL,
    id_ubicacion INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria),
    FOREIGN KEY (id_ubicacion) REFERENCES Ubicacion(id_ubicacion)
);

CREATE TABLE Pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATETIME NOT NULL,
    fecha_entrega DATETIME,
    estado VARCHAR(20) NOT NULL,
    recogido BOOLEAN NOT NULL DEFAULT FALSE,
    ci_cliente VARCHAR(8) NOT NULL,
    ci_operario VARCHAR(8),
    FOREIGN KEY (ci_cliente) REFERENCES Cliente(CI),
    FOREIGN KEY (ci_operario) REFERENCES Operario(CI)
);

CREATE TABLE Notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(30) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL,
    id_producto INT,
    id_zona INT,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona)
);

CREATE TABLE Tiene (
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    Cantidad INT NOT NULL,
    PRIMARY KEY (id_pedido, id_producto),
    FOREIGN KEY (id_pedido) REFERENCES Pedido(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

CREATE TABLE Registra (
    ci_operario VARCHAR(8) NOT NULL,
    id_producto INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    Ubicacion_producto VARCHAR(90) NOT NULL,
    Tipo_Registro VARCHAR(30) NOT NULL,
    PRIMARY KEY (ci_operario, id_producto, fecha_hora),
    FOREIGN KEY (ci_operario) REFERENCES Operario(CI),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

-- Datos de prueba 
USE softyle;

-- PERSONAS
INSERT INTO Persona
(CI, usuario, email, contraseña, telefono, direccion, Rol)
VALUES
('40123456', 'admin', 'admin@gmail.com', '1234', '099111111', 'Av. Principal 100', 'Administrador'),
('45678901', 'operario', 'operario@gmail.com', '1234', '099222222', 'Calle Central 200', 'Operario'),
('57602994', 'Guillermo', 'Guillermo@gmail.com', '0310', '091312099', 'Maldonado 2125', 'Operario'),
('42345678', 'Lucas Fernandez', 'lucas@email.com', '1234', '099333333', '18 de Julio 300', 'Cliente'),
('43456789', 'Valentina Lopez', 'valentina@email.com', '1234', '099444444', 'Rivera 400', 'Cliente'),
('44567890', 'Nicolas Gonzalez', 'nicolas@email.com', '1234', '099555555', 'Artigas 500', 'Cliente');


-- CLIENTES

INSERT INTO Cliente (CI)
VALUES
('42345678'),
('43456789'),
('44567890');


-- OPERARIO
INSERT INTO Operario
(CI, Estado, hora_ingreso)
VALUES
('45678901', 'Activo', '07:30:00'),
('57602994', 'Activo', '08:00:00');


-- DEPOSITOS

INSERT INTO Deposito
(nombre, direccion, localidad)
VALUES
('Deposito Central', 'Ruta 5 Km 25', 'Montevideo'),
('Deposito Norte', 'Av. Artigas 1500', 'Las Piedras');


-- ZONAS

INSERT INTO Zona
(nombre, tipo, capacidad_total, estado, id_deposito)
VALUES
('Zona A', 'Almacenamiento', 500, 'Activa', 1),
('Zona B', 'Picking', 300, 'Activa', 1),
('Zona C', 'Despacho', 200, 'Activa', 1),
('Zona D', 'Almacenamiento', 400, 'Activa', 2),
('Zona E', 'Picking', 250, 'Activa', 2);


-- UBICACIONES

INSERT INTO Ubicacion
(pasillo, estante, posicion, disponible, id_zona)
VALUES
('A01', 'E01', 'P01', TRUE, 1),
('A01', 'E01', 'P02', TRUE, 1),
('A01', 'E02', 'P01', TRUE, 1),
('A02', 'E01', 'P01', TRUE, 2),
('A02', 'E01', 'P02', TRUE, 2),
('A02', 'E02', 'P01', TRUE, 2),
('B01', 'E01', 'P01', TRUE, 3),
('B01', 'E02', 'P01', TRUE, 4),
('B02', 'E01', 'P01', TRUE, 4),
('B02', 'E01', 'P02', TRUE, 5);


-- CATEGORIAS

INSERT INTO Categoria
(nombre, descripcion)
VALUES
('Informatica', 'Equipos y accesorios informaticos'),
('Electronica', 'Productos electronicos'),
('Oficina', 'Articulos de oficina'),
('Herramientas', 'Herramientas de trabajo'),
('Seguridad', 'Elementos de seguridad');


-- PRODUCTOS

INSERT INTO Producto
(Nombre, descripcion, stock_actual, stock_minimo, estado_producto, id_categoria, id_ubicacion)
VALUES
('Teclado USB', 'Teclado USB estandar', 50, 10, 'Disponible', 1, 1),
('Mouse USB', 'Mouse optico USB', 75, 15, 'Disponible', 1, 2),
('Monitor 24 pulgadas', 'Monitor LED de 24 pulgadas', 20, 5, 'Disponible', 1, 3),
('Cable HDMI', 'Cable HDMI de 2 metros', 100, 20, 'Disponible', 2, 4),
('Cable de red', 'Cable de red UTP categoria 6', 150, 30, 'Disponible', 2, 5),
('Auriculares', 'Auriculares con microfono', 35, 10, 'Disponible', 1, 6),
('Resma A4', 'Resma de papel de 500 hojas', 80, 20, 'Disponible', 3, 7),
('Lapicera azul', 'Lapicera de tinta azul', 200, 50, 'Disponible', 3, 8),
('Destornillador', 'Destornillador Phillips', 25, 5, 'Disponible', 4, 9),
('Guantes de seguridad', 'Guantes de proteccion', 60, 15, 'Disponible', 5, 10),
('Auriculares Inalambricos', 'Auriculares con Bluetooth', 9, 10, 'Disponible', 1, 6);


-- PEDIDOS

INSERT INTO Pedido
(fecha_creacion, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES
('2026-09-25 08:30:00', '2026-09-26 12:00:00', 'Completado', TRUE, '42345678', '45678901'),
('2026-09-26 10:15:00', '2026-09-27 15:00:00', 'En Picking', FALSE, '43456789', '45678901'),
('2026-09-27 09:00:00', '2026-09-28 11:00:00', 'Pendiente', FALSE, '44567890', NULL),
('2026-09-28 09:00:00', '2026-09-29 11:00:00', 'Pendiente', FALSE, '44567890', NULL);

-- TIENE

INSERT INTO Tiene
(id_pedido, id_producto, Cantidad)
VALUES
(1, 1, 5),
(1, 2, 2),
(1, 4, 10),

(2, 3, 1),
(2, 5, 4),
(2, 6, 2),

(3, 7, 3),
(3, 8, 10),

(4, 9, 2),
(4, 10, 5);


-- REGISTROS DEL OPERARIO
INSERT INTO Registra
(ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro)
VALUES
('45678901', 1, '2026-09-25 09:00:00', 'A01-E01-P01', 'Retiro'),
('45678901', 2, '2026-09-25 09:05:00', 'A01-E01-P02', 'Retiro'),
('45678901', 4, '2026-09-25 09:10:00', 'A02-E01-P01', 'Retiro'),
('45678901', 3, '2026-09-26 10:00:00', 'A01-E02-P01', 'Retiro'),
('45678901', 5, '2026-09-26 10:05:00', 'A02-E01-P02', 'Retiro');


-- NOTIFICACIONES
INSERT INTO Notificacion
(tipo, mensaje, fecha, id_producto, id_zona)
VALUES
('Stock bajo', 'El stock del producto se encuentra cerca del minimo', '2026-09-29 08:00:00', 3, 1),

('Picking', 'El pedido 2 esta listo para realizar picking', '2026-09-29 09:00:00', NULL, 2),

('Stock', 'Producto disponible para picking', '2026-09-29 09:30:00', 5, 2);




-- Inserciones avanzadas en la base de datos

USE softyle;

-- 1. Reporte de inventario por categoría y ubicación

SELECT
    p.id_producto,
    p.Nombre AS producto,
    c.nombre AS categoria,
    p.stock_actual, -- Producto
    p.stock_minimo, -- Producto
    p.estado_producto, -- Producto
    CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) AS ubicacion,
    z.nombre AS zona, -- Zona
    d.nombre AS deposito -- Deposito
FROM Producto p
INNER JOIN Categoria c 
    ON p.id_categoria = c.id_categoria
INNER JOIN Ubicacion u 
    ON p.id_ubicacion = u.id_ubicacion -- Producto y Ubicación 
INNER JOIN Zona z 
    ON u.id_zona = z.id_zona -- Ubicacion y zona
INNER JOIN Deposito d 
    ON z.id_deposito = d.id_deposito
ORDER BY d.nombre, z.nombre, p.Nombre; -- Deposito nombre, zona nombre y producto nombre



-- 2. Productos que necesitan reposición
SELECT
    p.id_producto,
    p.Nombre AS producto,
    c.nombre AS categoria,
    p.stock_actual,
    p.stock_minimo
FROM Producto p
INNER JOIN Categoria c
    ON p.id_categoria = c.id_categoria
WHERE p.stock_actual <= p.stock_minimo
ORDER BY p.stock_actual;


-- 3. Productos más solicitados
SELECT
    p.id_producto,
    p.Nombre AS producto,
    c.nombre AS categoria,
    SUM(t.cantidad) AS unidades_solicitadas,
    COUNT(t.id_pedido) AS cantidad_pedidos
FROM Tiene t
INNER JOIN Producto p
    ON t.id_producto = p.id_producto
INNER JOIN Categoria c
    ON p.id_categoria = c.id_categoria
GROUP BY
    p.id_producto,
    p.Nombre,
    c.nombre
ORDER BY unidades_solicitadas DESC;

-- 4. Resumen del pedido
SELECT
    pe.id_pedido,
    pc.usuario AS cliente,
    po.usuario AS operario,
    pe.fecha_creacion,
    pe.fecha_entrega,
    pe.estado,
    COUNT(t.id_producto) AS productos_diferentes,
    SUM(t.cantidad) AS unidades_totales
FROM Pedido pe
INNER JOIN Cliente c
    ON pe.ci_cliente = c.CI
INNER JOIN Persona pc
    ON c.CI = pc.CI
LEFT JOIN Operario o
    ON pe.ci_operario = o.CI
LEFT JOIN Persona po
    ON o.CI = po.CI
INNER JOIN Tiene t
    ON pe.id_pedido = t.id_pedido
GROUP BY
    pe.id_pedido,
    pc.usuario,
    po.usuario,
    pe.fecha_creacion,
    pe.fecha_entrega,
    pe.estado
ORDER BY pe.fecha_creacion DESC;


-- 5. Carga de trabajo de los operarios
SELECT
    o.CI,
    p.usuario AS operario,
    o.Estado,
    COUNT(DISTINCT pe.id_pedido) AS pedidos_asignados,
    SUM(t.cantidad) AS unidades_a_preparar
FROM Operario o
INNER JOIN Persona p
    ON o.CI = p.CI
LEFT JOIN Pedido pe
    ON o.CI = pe.ci_operario
LEFT JOIN Tiene t
    ON pe.id_pedido = t.id_pedido
GROUP BY
    o.CI,
    p.usuario,
    o.Estado
ORDER BY unidades_a_preparar DESC;

-- 6. Productos pedidos que superan el stock disponible 
SELECT
    t.id_pedido,
    p.id_producto,
    p.Nombre AS producto,
    t.cantidad AS cantidad_solicitada,
    p.stock_actual
FROM Tiene t
INNER JOIN Producto p
    ON t.id_producto = p.id_producto
WHERE t.cantidad > p.stock_actual
ORDER BY t.id_pedido;





-- 7. Productos y stock por depósito y zona
SELECT
    d.nombre AS deposito,
    z.nombre AS zona,
    z.tipo,
    COUNT(p.id_producto) AS cantidad_productos,
    SUM(p.stock_actual) AS stock_total
FROM Deposito d
INNER JOIN Zona z
    ON d.id_deposito = z.id_deposito
INNER JOIN Ubicacion u
    ON z.id_zona = u.id_zona
INNER JOIN Producto p
    ON u.id_ubicacion = p.id_ubicacion
GROUP BY
    d.nombre,
    z.nombre,
    z.tipo
ORDER BY d.nombre, z.nombre;


-- 8 Categorías con mayor cantidad de productos

SELECT
    c.id_categoria,
    c.nombre AS categoria,
    COUNT(p.id_producto) AS cantidad_productos,
    SUM(p.stock_actual) AS stock_total
FROM Categoria c
INNER JOIN Producto p
    ON c.id_categoria = p.id_categoria
GROUP BY
    c.id_categoria,
    c.nombre
HAVING COUNT(p.id_producto) >= 2
ORDER BY cantidad_productos DESC;

-- 9. Vista
CREATE VIEW Vista_Inventario AS
SELECT
    p.id_producto,
    p.Nombre AS producto,
    c.nombre AS categoria,
    p.stock_actual,
    p.stock_minimo,
    p.estado_producto,
    u.pasillo,
    u.estante,
    u.posicion,
    z.nombre AS zona,
    d.nombre AS deposito
FROM Producto p
INNER JOIN Categoria c
    ON p.id_categoria = c.id_categoria
INNER JOIN Ubicacion u
    ON p.id_ubicacion = u.id_ubicacion
INNER JOIN Zona z
    ON u.id_zona = z.id_zona
INNER JOIN Deposito d
    ON z.id_deposito = d.id_deposito;
    
SELECT *
FROM Vista_Inventario;
    
-- 10. Procedimiento Almacenado
DELIMITER //

CREATE PROCEDURE ConsultarStock(IN idProducto INT)
BEGIN

    SELECT
        p.id_producto,
        p.Nombre AS producto,
        c.nombre AS categoria,
        p.stock_actual,
        p.stock_minimo,
        p.estado_producto
    FROM Producto p
    INNER JOIN Categoria c
        ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = idProducto;

END //

DELIMITER ;
USE softyle;
CALL ConsultarStock(1);
-- Otro Producto 
CALL ConsultarStock(5);

