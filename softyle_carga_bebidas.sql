-- =====================================================
-- CARGA DE BEBIDAS (2026-11-13)
-- Zona G (seca) con un pasillo propio, categoría "Bebidas"
-- y los refrescos con las presentaciones que se venden en
-- Uruguay (catálogo de Tienda Inglesa y de Tata).
-- Cada producto: 48 unidades, mínimo 12. La Coca-Cola Zero
-- 250 ml queda en el mínimo a propósito para ver la alerta.
-- Antes de correrlo, cambiar @pasillo si ya existe.
-- Para deshacerlo: borrar los productos de la Zona G, sus
-- ubicaciones y la zona (o volver a la copia de respaldo).
-- =====================================================

SET @pasillo = 'P09';

-- Copia de respaldo de las tablas que se tocan
CREATE TABLE Producto_respaldo_bebidas AS SELECT * FROM Producto;
CREATE TABLE Ubicacion_respaldo_bebidas AS SELECT * FROM Ubicacion;

INSERT INTO Categoria (nombre, descripcion)
SELECT 'Bebidas', 'Refrescos, gaseosas y limonadas' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM Categoria WHERE nombre = 'Bebidas');
SET @categoria = (SELECT id_categoria FROM Categoria WHERE nombre = 'Bebidas');

INSERT INTO Zona (nombre, tipo, capacidad_total, estado, id_deposito)
VALUES ('Zona G', 'Seca', 2500, 'Activa', 1);
SET @zona = LAST_INSERT_ID();

-- Una estantería por marca, un nivel por presentación
CREATE TEMPORARY TABLE carga_bebidas (
    estante VARCHAR(20), nivel VARCHAR(20), nombre VARCHAR(100), descripcion VARCHAR(255), stock INT
);
INSERT INTO carga_bebidas VALUES
('E01', 'N01', 'Coca-Cola Original 250 ml',            'Refresco cola, botella 250 ml',                      48),
('E01', 'N02', 'Coca-Cola Original 600 ml',            'Refresco cola, botella 600 ml',                      48),
('E01', 'N03', 'Coca-Cola Original 1 L vidrio',        'Refresco cola, botella de vidrio retornable 1 L',    48),
('E01', 'N04', 'Coca-Cola Original 1,5 L',             'Refresco cola, botella 1,5 L',                       48),
('E01', 'N05', 'Coca-Cola Original 2 L retornable',    'Refresco cola, botella retornable 2 L',              48),
('E01', 'N06', 'Coca-Cola Original 2,5 L',             'Refresco cola, botella 2,5 L',                       48),
('E01', 'N07', 'Coca-Cola Original 3 L',               'Refresco cola, botella 3 L',                         48),
('E02', 'N01', 'Coca-Cola Zero 250 ml',                'Refresco cola sin azúcar, botella 250 ml',           12),
('E02', 'N02', 'Coca-Cola Zero 600 ml',                'Refresco cola sin azúcar, botella 600 ml',           48),
('E02', 'N03', 'Coca-Cola Zero 1 L vidrio',            'Refresco cola sin azúcar, botella de vidrio retornable 1 L', 48),
('E02', 'N04', 'Coca-Cola Zero 1,5 L',                 'Refresco cola sin azúcar, botella 1,5 L',            48),
('E02', 'N05', 'Coca-Cola Zero 2 L retornable',        'Refresco cola sin azúcar, botella retornable 2 L',   48),
('E02', 'N06', 'Coca-Cola Zero 2,25 L',                'Refresco cola sin azúcar, botella 2,25 L',           48),
('E02', 'N07', 'Coca-Cola Zero 3 L',                   'Refresco cola sin azúcar, botella 3 L',              48),
('E03', 'N01', 'Fanta Naranja 250 ml',                 'Refresco de naranja, botella 250 ml',                48),
('E03', 'N02', 'Fanta Naranja 500 ml',                 'Refresco de naranja, botella 500 ml',                48),
('E03', 'N03', 'Fanta Naranja 1,5 L',                  'Refresco de naranja, botella 1,5 L',                 48),
('E03', 'N04', 'Fanta Naranja 2,25 L',                 'Refresco de naranja, botella 2,25 L',                48),
('E03', 'N05', 'Fanta Naranja 3 L',                    'Refresco de naranja, botella 3 L',                   48),
('E03', 'N06', 'Fanta Zero 1,5 L',                     'Refresco de naranja sin azúcar, botella 1,5 L',      48),
('E04', 'N01', 'Sprite sin azúcar 1,5 L',              'Refresco lima limón sin azúcar, botella 1,5 L',      48),
('E04', 'N02', 'Sprite sin azúcar 2,25 L',             'Refresco lima limón sin azúcar, botella 2,25 L',     48),
('E05', 'N01', 'Nix Cola 2 L',                         'Refresco cola, botella 2 L',                         48),
('E05', 'N02', 'Nix Cola 2,5 L',                       'Refresco cola, botella 2,5 L',                       48),
('E05', 'N03', 'Nix Cola Cero 2 L',                    'Refresco cola sin azúcar, botella 2 L',              48),
('E06', 'N01', 'Salus Limonada Clásica 600 ml',        'Limonada sin azúcar, botella 600 ml',                48),
('E06', 'N02', 'Salus Limonada Clásica 1,5 L',         'Limonada sin azúcar, botella 1,5 L',                 48),
('E06', 'N03', 'Salus Limonada Menta 600 ml',          'Limonada con menta sin azúcar, botella 600 ml',      48),
('E06', 'N04', 'Salus Limonada Menta 1,5 L',           'Limonada con menta sin azúcar, botella 1,5 L',       48),
('E06', 'N05', 'Salus Pomelo sin azúcar 1,5 L',        'Refresco de pomelo sin azúcar, botella 1,5 L',       48),
('E06', 'N06', 'Salus Tónica sin azúcar 500 ml',       'Agua tónica sin azúcar, botella 500 ml',             48),
('E06', 'N07', 'Salus Tónica sin azúcar 1,5 L',        'Agua tónica sin azúcar, botella 1,5 L',              48),
('E07', 'N01', 'Paso de los Toros Pomelo 500 ml',      'Refresco de pomelo, botella 500 ml',                 48),
('E07', 'N02', 'Paso de los Toros Pomelo 1 L',         'Refresco de pomelo, botella 1 L',                    48),
('E07', 'N03', 'Paso de los Toros Pomelo 1,5 L',       'Refresco de pomelo, botella 1,5 L',                  48),
('E07', 'N04', 'Paso de los Toros Pomelo 2,5 L',       'Refresco de pomelo, botella 2,5 L',                  48),
('E07', 'N05', 'Paso de los Toros Pomelo sin azúcar 500 ml', 'Refresco de pomelo sin azúcar, botella 500 ml', 48),
('E07', 'N06', 'Paso de los Toros Pomelo sin azúcar 1,5 L',  'Refresco de pomelo sin azúcar, botella 1,5 L',  48),
('E07', 'N07', 'Paso de los Toros Pomelo sin azúcar 2,5 L',  'Refresco de pomelo sin azúcar, botella 2,5 L',  48),
('E07', 'N08', 'Paso de los Toros Tónica 500 ml',      'Agua tónica, botella 500 ml',                        48),
('E07', 'N09', 'Paso de los Toros Tónica 1,5 L',       'Agua tónica, botella 1,5 L',                         48),
('E07', 'N10', 'Paso de los Toros Tónica sin azúcar 1,5 L', 'Agua tónica sin azúcar, botella 1,5 L',         48);

INSERT INTO Ubicacion (pasillo, estante, posicion, disponible, id_zona)
SELECT @pasillo, estante, nivel, TRUE, @zona FROM carga_bebidas;

INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, id_categoria, id_ubicacion)
SELECT b.nombre, b.descripcion, b.stock, 12, 'Disponible', @categoria, u.id_ubicacion
FROM carga_bebidas b
JOIN Ubicacion u ON BINARY u.pasillo = BINARY @pasillo AND BINARY u.estante = BINARY b.estante AND BINARY u.posicion = BINARY b.nivel
ORDER BY b.estante, b.nivel;

-- Código de barras interno: "20" + id con ceros + dígito verificador (igual que la app)
UPDATE Producto SET codigo_barras = CONCAT('20', LPAD(id_producto, 10, '0')) WHERE codigo_barras IS NULL;
UPDATE Producto SET codigo_barras = CONCAT(codigo_barras, MOD(10 - MOD(
        SUBSTRING(codigo_barras, 1, 1) + 3 * SUBSTRING(codigo_barras, 2, 1) + SUBSTRING(codigo_barras, 3, 1) + 3 * SUBSTRING(codigo_barras, 4, 1)
      + SUBSTRING(codigo_barras, 5, 1) + 3 * SUBSTRING(codigo_barras, 6, 1) + SUBSTRING(codigo_barras, 7, 1) + 3 * SUBSTRING(codigo_barras, 8, 1)
      + SUBSTRING(codigo_barras, 9, 1) + 3 * SUBSTRING(codigo_barras, 10, 1) + SUBSTRING(codigo_barras, 11, 1) + 3 * SUBSTRING(codigo_barras, 12, 1), 10), 10))
WHERE CHAR_LENGTH(codigo_barras) = 12;

-- Códigos reales (EAN-13 de fábrica, leídos del envase)
UPDATE Producto SET codigo_barras = '7730197010911' WHERE Nombre = 'Coca-Cola Zero 250 ml';

DROP TEMPORARY TABLE carga_bebidas;

SELECT z.nombre AS zona, COUNT(p.id_producto) AS productos, SUM(p.stock_actual <= p.stock_minimo) AS en_minimo
FROM Zona z JOIN Ubicacion u ON u.id_zona = z.id_zona JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
WHERE z.id_zona = @zona GROUP BY z.nombre;
