-- =====================================================
-- PICKFLOW con productos reales (2026-11-13)
-- Depósito de supermercado: 7 zonas, 105 productos con su código de
-- barras de fábrica (fichas de Tienda Inglesa) y pedidos de ejemplo.
-- Reemplaza los productos genéricos, sus pedidos, movimientos y
-- notificaciones de prueba. Antes guarda una copia de cada tabla
-- (sufijo _respaldo_reales). Los usuarios y los logros no se tocan.
-- Se corre una sola vez (si las copias ya existen, se detiene).
-- =====================================================

-- 1) Copias de respaldo
CREATE TABLE Producto_respaldo_reales AS SELECT * FROM Producto;
CREATE TABLE Ubicacion_respaldo_reales AS SELECT * FROM Ubicacion;
CREATE TABLE Zona_respaldo_reales AS SELECT * FROM Zona;
CREATE TABLE Categoria_respaldo_reales AS SELECT * FROM Categoria;
CREATE TABLE Pedido_respaldo_reales AS SELECT * FROM Pedido;
CREATE TABLE Tiene_respaldo_reales AS SELECT * FROM Tiene;
CREATE TABLE Registra_respaldo_reales AS SELECT * FROM Registra;
CREATE TABLE Notificacion_respaldo_reales AS SELECT * FROM Notificacion;
CREATE TABLE API_Recepciones_respaldo_reales AS SELECT * FROM API_Recepciones;
CREATE TABLE Sensor_respaldo_reales AS SELECT * FROM Sensor;
CREATE TABLE Documento_Producto_respaldo_reales AS SELECT * FROM Documento_Producto;

-- 2) Se borran los datos de prueba (en orden, por las claves foráneas)
DELETE FROM Registra;
DELETE FROM Tiene;
DELETE FROM Notificacion;
DELETE FROM Documento_Producto;
DELETE FROM API_Recepciones;
DELETE FROM Pedido;
DELETE FROM Producto;
DELETE FROM Ubicacion;
DELETE FROM Categoria;

-- 3) Zonas del depósito de supermercado (se mantienen los ids: los sensores siguen apuntando bien)
UPDATE Zona SET tipo = 'Seca', capacidad_total = 1500, estado = 'Activa' WHERE nombre = 'Zona A';
SET @zA = (SELECT id_zona FROM Zona WHERE nombre = 'Zona A');
UPDATE Zona SET tipo = 'Seca', capacidad_total = 2500, estado = 'Activa' WHERE nombre = 'Zona B';
SET @zB = (SELECT id_zona FROM Zona WHERE nombre = 'Zona B');
UPDATE Zona SET tipo = 'Refrigerada', capacidad_total = 800, estado = 'Activa' WHERE nombre = 'Zona C';
SET @zC = (SELECT id_zona FROM Zona WHERE nombre = 'Zona C');
UPDATE Zona SET tipo = 'Refrigerada', capacidad_total = 600, estado = 'Activa' WHERE nombre = 'Zona D';
SET @zD = (SELECT id_zona FROM Zona WHERE nombre = 'Zona D');
UPDATE Zona SET tipo = 'Seca', capacidad_total = 1000, estado = 'Activa' WHERE nombre = 'Zona E';
SET @zE = (SELECT id_zona FROM Zona WHERE nombre = 'Zona E');
UPDATE Zona SET tipo = 'Seca', capacidad_total = 600, estado = 'Activa' WHERE nombre = 'Zona F';
SET @zF = (SELECT id_zona FROM Zona WHERE nombre = 'Zona F');
UPDATE Zona SET tipo = 'Seca', capacidad_total = 900, estado = 'Activa' WHERE nombre = 'Zona G';
SET @zG = (SELECT id_zona FROM Zona WHERE nombre = 'Zona G');

-- 4) Sensores: cadena de frío en Lácteos (2 a 8 °C) y en Congelados (-22 a -15 °C)
UPDATE Sensor SET codigo = 'SNS-C01', id_zona = @zC WHERE codigo = 'SNS-F01';
INSERT INTO Sensor (codigo, id_zona, temp_min, temp_max, hum_min, hum_max) VALUES ('SNS-D01', @zD, -22.00, -15.00, 40.00, 80.00);
SET @sD = LAST_INSERT_ID();
-- 48 lecturas de las últimas 24 horas para el sensor nuevo (alguna fuera de rango)
INSERT INTO Lectura_Sensor (id_sensor, temperatura, humedad, fecha_hora)
SELECT @sD, ROUND(IF(RAND() < 0.08, -15 + 0.5 + RAND() * 2, -22 + 0.5 + RAND() * 6), 2), ROUND(40 + RAND() * 40, 2), NOW() - INTERVAL (n.i * 30) MINUTE
FROM (SELECT 0 AS i UNION ALL SELECT 1 AS i UNION ALL SELECT 2 AS i UNION ALL SELECT 3 AS i UNION ALL SELECT 4 AS i UNION ALL SELECT 5 AS i UNION ALL SELECT 6 AS i UNION ALL SELECT 7 AS i UNION ALL SELECT 8 AS i UNION ALL SELECT 9 AS i UNION ALL SELECT 10 AS i UNION ALL SELECT 11 AS i UNION ALL SELECT 12 AS i UNION ALL SELECT 13 AS i UNION ALL SELECT 14 AS i UNION ALL SELECT 15 AS i UNION ALL SELECT 16 AS i UNION ALL SELECT 17 AS i UNION ALL SELECT 18 AS i UNION ALL SELECT 19 AS i UNION ALL SELECT 20 AS i UNION ALL SELECT 21 AS i UNION ALL SELECT 22 AS i UNION ALL SELECT 23 AS i UNION ALL SELECT 24 AS i UNION ALL SELECT 25 AS i UNION ALL SELECT 26 AS i UNION ALL SELECT 27 AS i UNION ALL SELECT 28 AS i UNION ALL SELECT 29 AS i UNION ALL SELECT 30 AS i UNION ALL SELECT 31 AS i UNION ALL SELECT 32 AS i UNION ALL SELECT 33 AS i UNION ALL SELECT 34 AS i UNION ALL SELECT 35 AS i UNION ALL SELECT 36 AS i UNION ALL SELECT 37 AS i UNION ALL SELECT 38 AS i UNION ALL SELECT 39 AS i UNION ALL SELECT 40 AS i UNION ALL SELECT 41 AS i UNION ALL SELECT 42 AS i UNION ALL SELECT 43 AS i UNION ALL SELECT 44 AS i UNION ALL SELECT 45 AS i UNION ALL SELECT 46 AS i UNION ALL SELECT 47 AS i) n;

-- 5) Categorías
INSERT INTO Categoria (nombre, descripcion) VALUES ('Almacén', 'Alimentos secos y envasados de góndola'); SET @cAlm = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bebidas', 'Refrescos, aguas saborizadas y limonadas'); SET @cBeb = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Lácteos', 'Leche, yogures, manteca y postres (cadena de frío 2 a 8 °C)'); SET @cLac = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Congelados', 'Productos congelados (-18 °C o menos)'); SET @cCon = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Limpieza', 'Artículos de limpieza del hogar'); SET @cLim = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Perfumería e higiene', 'Cuidado personal e higiene'); SET @cPer = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Mascotas', 'Alimento y accesorios para mascotas'); SET @cMas = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bazar', 'Pilas, iluminación y cocina'); SET @cBaz = LAST_INSERT_ID();

-- 6) Ubicaciones y productos (un pasillo por zona, una estantería por grupo, un nivel por producto)
CREATE TEMPORARY TABLE carga (
    id_zona INT, pasillo VARCHAR(20), estante VARCHAR(20), nivel VARCHAR(20), id_categoria INT,
    nombre VARCHAR(100), descripcion VARCHAR(255), codigo VARCHAR(13), stock INT, minimo INT
);
INSERT INTO carga VALUES
(@zA, 'P01', 'E01', 'N01', @cAlm, 'Arroz Blanco Saman 1 kg', 'Arroz blanco, paquete de 1 kg', '7730115040105', 120, 30),
(@zA, 'P01', 'E01', 'N02', @cAlm, 'Yerba Canarias 1 kg', 'Yerba mate, paquete de 1 kg', '7730241003654', 90, 24),
(@zA, 'P01', 'E01', 'N03', @cAlm, 'Aceite de Girasol y Maíz Optimo 900 ml', 'Aceite comestible, botella de 900 ml', '7730132001165', 80, 20),
(@zA, 'P01', 'E01', 'N04', @cAlm, 'Azúcar Bella Unión 1 kg', 'Azúcar blanco, paquete de 1 kg', '7730106005113', 100, 25),
(@zA, 'P01', 'E01', 'N05', @cAlm, 'Harina con Polvo de Hornear Cañuelas 1 kg', 'Harina leudante, paquete de 1 kg', '7730376000276', 60, 15),
(@zA, 'P01', 'E02', 'N01', @cAlm, 'Fideos Tirabuzones Adria 500 g', 'Pasta seca, paquete de 500 g', '7730103300488', 110, 30),
(@zA, 'P01', 'E02', 'N02', @cAlm, 'Pulpa de Tomate De Ley 1 kg', 'Pulpa de tomate tamizada, envase de 1,02 kg', '7730306000604', 70, 20),
(@zA, 'P01', 'E02', 'N03', @cAlm, 'Mayonesa Hellmann''s Doypack 475 g', 'Mayonesa clásica, doypack de 475 g', '7730343001220', 18, 18),
(@zA, 'P01', 'E02', 'N04', @cAlm, 'Dulce de Leche Conaprole Clásico 500 g', 'Dulce de leche, pote de 500 g', '7730105005114', 50, 12),
(@zA, 'P01', 'E02', 'N05', @cAlm, 'Polenta Instantánea Río de la Plata 450 g', 'Harina de maíz precocida, 450 g', '7730205065018', 40, 10),
(@zA, 'P01', 'E03', 'N01', @cAlm, 'Galletas María Rika El Trigal 300 g', 'Galletas dulces, tripack de 300 g', '7730116101249', 75, 20),
(@zB, 'P02', 'E01', 'N01', @cBeb, 'Coca-Cola Original 250 ml', 'Refresco cola, botella de 250 ml', '7730197003227', 48, 12),
(@zB, 'P02', 'E01', 'N02', @cBeb, 'Coca-Cola Original 600 ml', 'Refresco cola, botella de 600 ml', '7730197002923', 48, 12),
(@zB, 'P02', 'E01', 'N03', @cBeb, 'Coca-Cola Original 1 L vidrio', 'Refresco cola, botella de vidrio retornable de 1 L', '7730197000806', 48, 12),
(@zB, 'P02', 'E01', 'N04', @cBeb, 'Coca-Cola Original 1,5 L', 'Refresco cola, botella de 1,5 L', '7730197232962', 42, 12),
(@zB, 'P02', 'E01', 'N05', @cBeb, 'Coca-Cola Original 2 L retornable', 'Refresco cola, botella retornable de 2 L', '7730197001377', 48, 12),
(@zB, 'P02', 'E01', 'N06', @cBeb, 'Coca-Cola Original 2,5 L', 'Refresco cola, botella de 2,5 L', '7730197000073', 48, 12),
(@zB, 'P02', 'E01', 'N07', @cBeb, 'Coca-Cola Original 3 L', 'Refresco cola, botella de 3 L', '7730197000639', 48, 12),
(@zB, 'P02', 'E02', 'N01', @cBeb, 'Coca-Cola Zero 250 ml', 'Refresco cola sin azúcar, botella de 250 ml', '7730197010911', 12, 12),
(@zB, 'P02', 'E02', 'N02', @cBeb, 'Coca-Cola Zero 600 ml', 'Refresco cola sin azúcar, botella de 600 ml', '7730197002190', 48, 12),
(@zB, 'P02', 'E02', 'N03', @cBeb, 'Coca-Cola Zero 1 L vidrio', 'Refresco cola sin azúcar, botella de vidrio retornable de 1 L', '7730197002220', 48, 12),
(@zB, 'P02', 'E02', 'N04', @cBeb, 'Coca-Cola Zero 1,5 L', 'Refresco cola sin azúcar, botella de 1,5 L', '7730197002206', 48, 12),
(@zB, 'P02', 'E02', 'N05', @cBeb, 'Coca-Cola Zero 2 L retornable', 'Refresco cola sin azúcar, botella retornable de 2 L', '7730197002428', 48, 12),
(@zB, 'P02', 'E02', 'N06', @cBeb, 'Coca-Cola Zero 2,25 L', 'Refresco cola sin azúcar, botella de 2,25 L', '7730197002213', 48, 12),
(@zB, 'P02', 'E02', 'N07', @cBeb, 'Coca-Cola Zero 3 L', 'Refresco cola sin azúcar, botella de 3 L', '7730197007713', 48, 12),
(@zB, 'P02', 'E03', 'N01', @cBeb, 'Fanta Naranja 250 ml', 'Refresco de naranja, botella de 250 ml', '7730197003784', 48, 12),
(@zB, 'P02', 'E03', 'N02', @cBeb, 'Fanta Naranja 500 ml', 'Refresco de naranja, botella de 500 ml', '7730197000479', 48, 12),
(@zB, 'P02', 'E03', 'N03', @cBeb, 'Fanta Naranja 1,5 L', 'Refresco de naranja, botella de 1,5 L', '7730197010478', 48, 12),
(@zB, 'P02', 'E03', 'N04', @cBeb, 'Fanta Naranja 2,25 L', 'Refresco de naranja, botella de 2,25 L', '7730197006624', 48, 12),
(@zB, 'P02', 'E03', 'N05', @cBeb, 'Fanta Naranja 3 L', 'Refresco de naranja, botella de 3 L', '7730197004804', 48, 12),
(@zB, 'P02', 'E03', 'N06', @cBeb, 'Fanta Zero 1,5 L', 'Refresco de naranja sin azúcar, botella de 1,5 L', '7730197001520', 48, 12),
(@zB, 'P02', 'E04', 'N01', @cBeb, 'Sprite sin azúcar 1,5 L', 'Refresco lima limón sin azúcar, botella de 1,5 L', '7730197009793', 44, 12),
(@zB, 'P02', 'E04', 'N02', @cBeb, 'Sprite sin azúcar 2,25 L', 'Refresco lima limón sin azúcar, botella de 2,25 L', '7730197009809', 48, 12),
(@zB, 'P02', 'E05', 'N01', @cBeb, 'Nix Cola 2 L', 'Refresco cola, botella de 2 L', '7730289001117', 48, 12),
(@zB, 'P02', 'E05', 'N02', @cBeb, 'Nix Cola 2,5 L', 'Refresco cola, botella de 2,5 L', '7730289001124', 48, 12),
(@zB, 'P02', 'E05', 'N03', @cBeb, 'Nix Cola Cero 2 L', 'Refresco cola sin azúcar, botella de 2 L', '7730130002430', 48, 12),
(@zB, 'P02', 'E06', 'N01', @cBeb, 'Salus Limonada Clásica 600 ml', 'Limonada sin azúcar, botella de 600 ml', '7730400004232', 48, 12),
(@zB, 'P02', 'E06', 'N02', @cBeb, 'Salus Limonada Clásica 1,5 L', 'Limonada sin azúcar, botella de 1,5 L', '7730400004249', 48, 12),
(@zB, 'P02', 'E06', 'N03', @cBeb, 'Salus Limonada Menta 600 ml', 'Limonada con menta sin azúcar, botella de 600 ml', '7730400004256', 48, 12),
(@zB, 'P02', 'E06', 'N04', @cBeb, 'Salus Limonada Menta 1,5 L', 'Limonada con menta sin azúcar, botella de 1,5 L', '7730400004263', 48, 12),
(@zB, 'P02', 'E06', 'N05', @cBeb, 'Salus Pomelo sin azúcar 1,5 L', 'Refresco de pomelo sin azúcar, botella de 1,5 L', '7730400003969', 48, 12),
(@zB, 'P02', 'E06', 'N06', @cBeb, 'Salus Tónica sin azúcar 500 ml', 'Agua tónica sin azúcar, botella de 500 ml', '7730400003938', 48, 12),
(@zB, 'P02', 'E06', 'N07', @cBeb, 'Salus Tónica sin azúcar 1,5 L', 'Agua tónica sin azúcar, botella de 1,5 L', '7730400003945', 48, 12),
(@zB, 'P02', 'E07', 'N01', @cBeb, 'Paso de los Toros Pomelo 500 ml', 'Refresco de pomelo, botella de 500 ml', '77303929', 48, 12),
(@zB, 'P02', 'E07', 'N02', @cBeb, 'Paso de los Toros Pomelo 1 L', 'Refresco de pomelo, botella de 1 L', '7734284002292', 48, 12),
(@zB, 'P02', 'E07', 'N03', @cBeb, 'Paso de los Toros Pomelo 1,5 L', 'Refresco de pomelo, botella de 1,5 L', '7734284244081', 48, 12),
(@zB, 'P02', 'E07', 'N04', @cBeb, 'Paso de los Toros Pomelo 2,5 L', 'Refresco de pomelo, botella de 2,5 L', '7734284001448', 48, 12),
(@zB, 'P02', 'E07', 'N05', @cBeb, 'Paso de los Toros Pomelo sin azúcar 500 ml', 'Refresco de pomelo sin azúcar, botella de 500 ml', '77305435', 48, 12),
(@zB, 'P02', 'E07', 'N06', @cBeb, 'Paso de los Toros Pomelo sin azúcar 1,5 L', 'Refresco de pomelo sin azúcar, botella de 1,5 L', '7734284224083', 48, 12),
(@zB, 'P02', 'E07', 'N07', @cBeb, 'Paso de los Toros Pomelo sin azúcar 2,5 L', 'Refresco de pomelo sin azúcar, botella de 2,5 L', '7734284001608', 48, 12),
(@zB, 'P02', 'E07', 'N08', @cBeb, 'Paso de los Toros Tónica 500 ml', 'Agua tónica, botella de 500 ml', '77304971', 48, 12),
(@zB, 'P02', 'E07', 'N09', @cBeb, 'Paso de los Toros Tónica 1,5 L', 'Agua tónica, botella de 1,5 L', '7734284254080', 48, 12),
(@zB, 'P02', 'E07', 'N10', @cBeb, 'Paso de los Toros Tónica sin azúcar 1,5 L', 'Agua tónica sin azúcar, botella de 1,5 L', '7734284002438', 48, 12),
(@zC, 'P03', 'E01', 'N01', @cLac, 'Leche Conaprole Fresca Entera 1 L', 'Leche pasteurizada entera, sachet de 1 L', '7730105001109', 96, 40),
(@zC, 'P03', 'E01', 'N02', @cLac, 'Leche Conaprole Entera Ultra 1 L', 'Leche ultrapasteurizada entera, sachet de 1 L', '7730105001321', 90, 30),
(@zC, 'P03', 'E01', 'N03', @cLac, 'Yogur Bebible Frutilla Conamigos 185 g', 'Yogur bebible Conaprole, 185 g', '7730105088162', 60, 20),
(@zC, 'P03', 'E01', 'N04', @cLac, 'Yogur Bebible Durazno Conamigos 185 g', 'Yogur bebible Conaprole, 185 g', '7730105088315', 15, 20),
(@zC, 'P03', 'E01', 'N05', @cLac, 'Manteca sin Sal Conaprole 200 g', 'Manteca, pan de 200 g', '77306197', 50, 15),
(@zC, 'P03', 'E02', 'N01', @cLac, 'Manteca sin Sal Conaprole 500 g', 'Manteca, pan de 500 g', '7730105006128', 30, 10),
(@zC, 'P03', 'E02', 'N02', @cLac, 'Crema de Leche Conaprole Ultra 500 ml', 'Crema de leche, sachet de 500 ml', '7730105011283', 40, 12),
(@zC, 'P03', 'E02', 'N03', @cLac, 'Crema de Leche Conaprole Light 250 ml', 'Crema de leche UHT liviana, 250 ml', '7730105011382', 35, 10),
(@zC, 'P03', 'E02', 'N04', @cLac, 'Postre Viva Vainilla Conaprole 2 un.', 'Postre sin azúcar de vainilla, 2 unidades de 110 g', '7730105026553', 45, 12),
(@zD, 'P04', 'E01', 'N01', @cCon, 'Hamburguesas Clásicas Schneck 12 un.', 'Hamburguesas de carne vacuna, caja de 1 kg', '7730138000056', 37, 10),
(@zD, 'P04', 'E01', 'N02', @cCon, 'Hamburguesas Clásicas Schneck 8 un.', 'Hamburguesas de carne vacuna, caja de 8 unidades', '7730138000049', 35, 10),
(@zD, 'P04', 'E01', 'N03', @cCon, 'Helado Conaprole Vainilla 2 L', 'Helado de vainilla, pote de 2 L', '7730105923050', 21, 8),
(@zD, 'P04', 'E01', 'N04', @cCon, 'Helado Conaprole Dulce de Leche Granizado 1 L', 'Helado de dulce de leche con granizado, 1 L', '7730105913518', 30, 8),
(@zD, 'P04', 'E01', 'N05', @cCon, 'Pizza Muzzarella La Especialista 2 un.', 'Pizza a la piedra congelada, 2 unidades', '7730152001985', 20, 6),
(@zD, 'P04', 'E02', 'N01', @cCon, 'Papas Bastón McCain 700 g', 'Papas prefritas congeladas, 700 g', '7797906000380', 55, 15),
(@zD, 'P04', 'E02', 'N02', @cCon, 'Arvejas McCain 500 g', 'Arvejas congeladas, 500 g', '5411916120891', 45, 12),
(@zD, 'P04', 'E02', 'N03', @cCon, 'Chauchas McCain 500 g', 'Chauchas congeladas, 500 g', '7730298002112', 6, 12),
(@zD, 'P04', 'E02', 'N04', @cCon, 'Nuggets de Pollo Sadia 900 g', 'Nuggets de pollo crocantes, 900 g', '7798183044470', 30, 8),
(@zD, 'P04', 'E02', 'N05', @cCon, 'Espinaca en Hojas Huerta del Este 1 kg', 'Espinaca congelada, 1 kg', '7730916584020', 25, 8),
(@zD, 'P04', 'E03', 'N01', @cCon, 'Postre Deleite Dulce de Leche 100 g', 'Postre helado de dulce de leche, 100 g', '7730105205033', 50, 15),
(@zE, 'P05', 'E01', 'N01', @cLim, 'Jabón en Polvo Nevex Matic 3 kg', 'Jabón en polvo para lavarropas, 3 kg', '7730165312023', 36, 10),
(@zE, 'P05', 'E01', 'N02', @cLim, 'Detergente Nevex Hurra Aloe Vera 750 ml', 'Detergente lavavajillas, 750 ml', '7730165009398', 64, 20),
(@zE, 'P05', 'E01', 'N03', @cLim, 'Detergente Cif BioActive Lima 750 ml', 'Detergente lavavajillas, 750 ml', '7791290791183', 50, 15),
(@zE, 'P05', 'E01', 'N04', @cLim, 'Suavizante Vivere Clásico 900 ml', 'Suavizante para ropa, 900 ml', '7791290793750', 45, 12),
(@zE, 'P05', 'E01', 'N05', @cLim, 'Lavandina Sello Rojo Lavanda 2 L', 'Agua lavandina perfumada, 2 L', '7793253005702', 60, 15),
(@zE, 'P05', 'E02', 'N01', @cLim, 'Papel Higiénico Higienol Premium 4 rollos', 'Papel higiénico doble hoja, 4 rollos de 50 m', '7730219011995', 72, 20),
(@zE, 'P05', 'E02', 'N02', @cLim, 'Toalla de Papel Elite Ultra 3 rollos', 'Rollo de cocina, 3 rollos de 210 paños', '7730219013029', 55, 15),
(@zE, 'P05', 'E02', 'N03', @cLim, 'Limpiador Antigrasa Mr. Músculo 900 ml', 'Limpiador de cocina, repuesto de 900 ml', '7790520012456', 35, 10),
(@zE, 'P05', 'E02', 'N04', @cLim, 'Limpiador de Pisos Blem Original 800 ml', 'Limpiador para pisos plastificados, 800 ml', '7790520611000', 40, 10),
(@zE, 'P05', 'E02', 'N05', @cLim, 'Esponja de Bronce Bronsol', 'Esponja de bronce para ollas', '7730341000041', 30, 10),
(@zE, 'P05', 'E03', 'N01', @cLim, 'Bolsas de Residuos Unisold 50 x 62 cm 30 un.', 'Bolsas para residuos en rollo, 30 unidades', '7730969080326', 65, 15),
(@zF, 'P06', 'E01', 'N01', @cPer, 'Shampoo Sedal Ceramidas 650 ml', 'Shampoo, botella de 650 ml', '7791293031033', 40, 10),
(@zF, 'P06', 'E01', 'N02', @cPer, 'Acondicionador Pantene Restauración 400 ml', 'Acondicionador, botella de 400 ml', '7501006721317', 35, 10),
(@zF, 'P06', 'E01', 'N03', @cPer, 'Jabón de Tocador Dove Original 6 un.', 'Jabón de tocador, 6 unidades de 90 g', '7730165346943', 41, 12),
(@zF, 'P06', 'E01', 'N04', @cPer, 'Pasta Dental Colgate Max White', 'Crema dental blanqueadora, 219 g', '7509546069265', 56, 15),
(@zF, 'P06', 'E01', 'N05', @cPer, 'Cepillo de Dientes Colgate Triple Acción 2 un.', 'Cepillo dental, pack de 2 unidades', '7509546012544', 50, 12),
(@zF, 'P06', 'E02', 'N01', @cPer, 'Desodorante Rexona Xtracool Dry 250 ml', 'Desodorante en aerosol, 250 ml', '7791293049649', 8, 10),
(@zF, 'P06', 'E02', 'N02', @cPer, 'Toallitas Húmedas Huggies 288 un.', 'Toallitas húmedas para bebé, 288 unidades', '7736550274761', 25, 8),
(@zF, 'P06', 'E02', 'N03', @cPer, 'Pañales Huggies Natural Care XXG 50 un.', 'Pañales talle XXG, 50 unidades', '7794626010125', 20, 6),
(@zF, 'P06', 'E02', 'N04', @cPer, 'Crema Nivea Lata 150 ml', 'Crema humectante, lata de 150 ml', '4005800137556', 28, 8),
(@zF, 'P06', 'E02', 'N05', @cPer, 'Algodón Zigzag 100 g', 'Algodón hidrófilo, 100 g', '7730207004107', 40, 10),
(@zG, 'P07', 'E01', 'N01', @cMas, 'Alimento para Perros Dog Chow Adultos 3 kg', 'Alimento seco razas pequeñas, bolsa de 3 kg', '7613034416865', 23, 6),
(@zG, 'P07', 'E01', 'N02', @cMas, 'Alimento para Perros Pedigree Carne 100 g', 'Alimento húmedo para perros adultos, 100 g', '7797453000796', 80, 20),
(@zG, 'P07', 'E01', 'N03', @cMas, 'Alimento para Gatos Cat Chow Carne 85 g', 'Alimento húmedo para gatos adultos, 85 g', '7891000113066', 78, 24),
(@zG, 'P07', 'E01', 'N04', @cMas, 'Alimento para Gatos Cat Chow Pollo 85 g', 'Alimento húmedo para gatos adultos, 85 g', '7891000244074', 85, 24),
(@zG, 'P07', 'E01', 'N05', @cMas, 'Alimento para Gatos Whiskas Salmón 85 g', 'Alimento húmedo para gatos adultos, 85 g', '7797453000413', 70, 20),
(@zG, 'P07', 'E02', 'N01', @cMas, 'Piedras Sanitarias Denucio 2 kg', 'Piedras sanitarias para gatos, 2 kg', '7730204022586', 27, 8),
(@zG, 'P07', 'E03', 'N01', @cBaz, 'Pilas AA Duracell Alcalinas 6 un.', 'Pilas alcalinas AA, 6 unidades', '041333900483', 38, 10),
(@zG, 'P07', 'E03', 'N02', @cBaz, 'Pilas AAA Duracell Alcalinas 6 un.', 'Pilas alcalinas AAA, 6 unidades', '041333904481', 35, 10),
(@zG, 'P07', 'E03', 'N03', @cBaz, 'Lámpara LED 12 W E27 Luz Fría', 'Lámpara LED Tienda Inglesa, rosca E27', '7730127012152', 22, 8),
(@zG, 'P07', 'E03', 'N04', @cBaz, 'Papel de Aluminio Fiesta 30 m', 'Rollo de papel de aluminio de 30 m', '7730955510073', 30, 8),
(@zG, 'P07', 'E03', 'N05', @cBaz, 'Film Adherente Júpiter 28 cm x 100 m', 'Film para alimentos, rollo de 100 m', '4897052123452', 20, 6);

INSERT INTO Ubicacion (pasillo, estante, posicion, disponible, id_zona)
SELECT pasillo, estante, nivel, TRUE, id_zona FROM carga;

-- El trigger NotificarStockNuevo avisa de los que entran en el mínimo
INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, codigo_barras, id_categoria, id_ubicacion)
SELECT c.nombre, c.descripcion, c.stock, c.minimo, 'Disponible', c.codigo, c.id_categoria, u.id_ubicacion
FROM carga c
JOIN Ubicacion u ON BINARY u.pasillo = BINARY c.pasillo AND BINARY u.estante = BINARY c.estante AND BINARY u.posicion = BINARY c.nivel
ORDER BY c.pasillo, c.estante, c.nivel;
DROP TEMPORARY TABLE carga;

-- 7) Pedidos de ejemplo
INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 4 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 4 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -3 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '42345678', '57602994');
SET @p1 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p1, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197232962'), 6, TRUE),
(@p1, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730115040105'), 10, TRUE),
(@p1, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730241003654'), 5, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197232962'), DATE(NOW()) - INTERVAL 4 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730197232962'), 'Retiro', @p1, 6);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730115040105'), DATE(NOW()) - INTERVAL 4 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730115040105'), 'Retiro', @p1, 10);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730241003654'), DATE(NOW()) - INTERVAL 4 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730241003654'), 'Retiro', @p1, 5);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -2 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '43456789', '56709313');
SET @p2 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001109'), 24, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), 10, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005114'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001109'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105001109'), 'Retiro', @p2, 24);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '77306197'), 'Retiro', @p2, 10);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005114'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105005114'), 'Retiro', @p2, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -2 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46123456', '57441750');
SET @p3 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165312023'), 4, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011995'), 8, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165009398'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011995'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 3 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011995'), 'Error de producto', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165312023'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 9 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730165312023'), 'Retiro', @p3, 4);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011995'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 15 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011995'), 'Retiro', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165009398'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 21 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730165009398'), 'Retiro', @p3, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -1 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46234567', '57798668');
SET @p4 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7891000113066'), 12, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), 3, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '041333900483'), 2, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '7891000113066'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7891000113066'), 'Retiro', @p4, 12);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730204022586'), 'Retiro', @p4, 3);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '041333900483'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '041333900483'), 'Retiro', @p4, 2);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL 0 DAY + INTERVAL 15 HOUR, 'Por validar', FALSE, '46345678', '57602994');
SET @p5 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105923050'), 4, TRUE),
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730138000056'), 3, TRUE),
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7797906000380'), 5, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105923050'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105923050'), 'Retiro', @p5, 4);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730138000056'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730138000056'), 'Retiro', @p5, 3);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7797906000380'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7797906000380'), 'Retiro', @p5, 5);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 40 MINUTE, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'En Picking', FALSE, '46456789', '56709313');
SET @p6 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), 6, TRUE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197006624'), 4, FALSE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197009793'), 4, FALSE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), NOW() - INTERVAL 40 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730197002190'), 'Retiro', @p6, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '46567890', '57798668');
SET @p7 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7791293031033'), 3, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165346943'), 2, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7509546069265'), 4, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '4005800137556'), 2, FALSE);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 2 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '46678901', NULL);
SET @p8 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p8, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730289001124'), 6, FALSE),
(@p8, (SELECT id_producto FROM Producto WHERE codigo_barras = '7734284244081'), 6, FALSE),
(@p8, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730400004249'), 6, FALSE);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 2 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '46789012', NULL);
SET @p9 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p9, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197010911'), 6, FALSE),
(@p9, (SELECT id_producto FROM Producto WHERE codigo_barras = '5411916120891'), 4, FALSE),
(@p9, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730152001985'), 2, FALSE);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 3 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '44567890', NULL);
SET @p10 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p10, (SELECT id_producto FROM Producto WHERE codigo_barras = '7613034416865'), 2, FALSE),
(@p10, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730127012152'), 3, FALSE);

-- 8) Logros que correspondan con los pedidos nuevos (solo agrega, no quita)
CALL AsignarLogros('57602994');
CALL AsignarLogros('56709313');
CALL AsignarLogros('57441750');
CALL AsignarLogros('57798668');

-- 9) Resumen
SELECT z.nombre AS zona, z.tipo, COUNT(p.id_producto) AS productos, SUM(p.stock_actual) AS unidades,
       SUM(p.stock_actual <= p.stock_minimo) AS en_minimo
FROM Zona z LEFT JOIN Ubicacion u ON u.id_zona = z.id_zona LEFT JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
GROUP BY z.id_zona, z.nombre, z.tipo ORDER BY z.nombre;
