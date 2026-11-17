-- =====================================================
-- PICKFLOW: productos comunes con foto (2026-11-16)
-- 77 productos de supermercado (Frog y Tienda Inglesa) con su
-- código de barras y el enlace a la foto que publica la tienda.
-- Agrega la columna Producto.foto (enlace https o archivo subido a
-- uploads/productos/). Reemplaza los productos, pedidos, movimientos y
-- avisos de prueba; antes guarda copias con el sufijo _respaldo_fotos.
-- Las zonas, los sensores, los usuarios y los logros no se tocan.
-- =====================================================

-- 1) Columna de la foto
ALTER TABLE Producto ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL AFTER codigo_barras;

-- 2) Copias de respaldo
CREATE TABLE Producto_respaldo_fotos AS SELECT * FROM Producto;
CREATE TABLE Ubicacion_respaldo_fotos AS SELECT * FROM Ubicacion;
CREATE TABLE Categoria_respaldo_fotos AS SELECT * FROM Categoria;
CREATE TABLE Pedido_respaldo_fotos AS SELECT * FROM Pedido;
CREATE TABLE Tiene_respaldo_fotos AS SELECT * FROM Tiene;
CREATE TABLE Registra_respaldo_fotos AS SELECT * FROM Registra;
CREATE TABLE Notificacion_respaldo_fotos AS SELECT * FROM Notificacion;
CREATE TABLE API_Recepciones_respaldo_fotos AS SELECT * FROM API_Recepciones;

-- 3) Se borran los datos de prueba (en orden, por las claves foráneas)
DELETE FROM Registra;
DELETE FROM Tiene;
DELETE FROM Notificacion;
DELETE FROM Documento_Producto;
DELETE FROM API_Recepciones;
DELETE FROM Pedido;
DELETE FROM Producto;
DELETE FROM Ubicacion;
DELETE FROM Categoria;

-- 4) Capacidad de cada zona según lo que guarda
UPDATE Zona SET capacidad_total = 1500 WHERE nombre = 'Zona A';
SET @zA = (SELECT id_zona FROM Zona WHERE nombre = 'Zona A');
UPDATE Zona SET capacidad_total = 1200 WHERE nombre = 'Zona B';
SET @zB = (SELECT id_zona FROM Zona WHERE nombre = 'Zona B');
UPDATE Zona SET capacidad_total = 800 WHERE nombre = 'Zona C';
SET @zC = (SELECT id_zona FROM Zona WHERE nombre = 'Zona C');
UPDATE Zona SET capacidad_total = 600 WHERE nombre = 'Zona D';
SET @zD = (SELECT id_zona FROM Zona WHERE nombre = 'Zona D');
UPDATE Zona SET capacidad_total = 1000 WHERE nombre = 'Zona E';
SET @zE = (SELECT id_zona FROM Zona WHERE nombre = 'Zona E');
UPDATE Zona SET capacidad_total = 600 WHERE nombre = 'Zona F';
SET @zF = (SELECT id_zona FROM Zona WHERE nombre = 'Zona F');
UPDATE Zona SET capacidad_total = 800 WHERE nombre = 'Zona G';
SET @zG = (SELECT id_zona FROM Zona WHERE nombre = 'Zona G');

-- 5) Categorías
INSERT INTO Categoria (nombre, descripcion) VALUES ('Almacén', 'Alimentos secos y envasados de góndola'); SET @cAlm = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bebidas', 'Refrescos, aguas y limonadas'); SET @cBeb = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Lácteos', 'Leche, yogur, manteca, quesos y huevos (cadena de frío 2 a 8 °C)'); SET @cLac = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Congelados', 'Productos congelados (-18 °C o menos)'); SET @cCon = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Limpieza', 'Artículos de limpieza del hogar'); SET @cLim = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Perfumería e higiene', 'Cuidado personal e higiene'); SET @cPer = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Mascotas', 'Alimento y piedras sanitarias para mascotas'); SET @cMas = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bazar', 'Pilas, fósforos, iluminación y cocina'); SET @cBaz = LAST_INSERT_ID();

-- 6) Ubicaciones y productos (un pasillo por zona, una estantería por grupo, un nivel por producto)
CREATE TEMPORARY TABLE carga (
    id_zona INT, pasillo VARCHAR(20), estante VARCHAR(20), nivel VARCHAR(20), id_categoria INT,
    nombre VARCHAR(100), descripcion VARCHAR(255), codigo VARCHAR(13), stock INT, minimo INT, foto VARCHAR(255)
);
INSERT INTO carga VALUES
(@zA, 'P01', 'E01', 'N01', @cAlm, 'Arroz Blanco Saman 1 kg', 'Arroz blanco, paquete de 1 kg', '7730115040105', 120, 30, 'https://i.aw.uy/frog/0006492_10923-1_510.jpeg'),
(@zA, 'P01', 'E01', 'N02', @cAlm, 'Fideos Spaghetti Las Acacias 500 g', 'Fideos al huevo, paquete de 500 g', '7730430000143', 110, 30, 'https://i.aw.uy/frog/0008689_18110-1_510.png'),
(@zA, 'P01', 'E01', 'N03', @cAlm, 'Aceite Optimo Girasol y Maíz 900 ml', 'Aceite comestible, botella de 900 ml', '7730132001165', 80, 20, 'https://i.aw.uy/frog/0005522_11134-1_510.png'),
(@zA, 'P01', 'E01', 'N04', @cAlm, 'Azúcar Bella Unión 1 kg', 'Azúcar blanco, paquete de 1 kg', '7730975890346', 100, 25, 'https://i.aw.uy/frog/0007899_55163-1_510.png'),
(@zA, 'P01', 'E01', 'N05', @cAlm, 'Harina Leudante Cañuelas 1 kg', 'Harina con polvo de hornear, paquete de 1 kg', '7730376000276', 60, 15, 'https://i.aw.uy/frog/0008506_11805-1_510.jpeg'),
(@zA, 'P01', 'E02', 'N01', @cAlm, 'Yerba Canarias 1 kg', 'Yerba mate, paquete de 1 kg', '7730241003654', 90, 24, 'https://i.aw.uy/frog/0002761_11563-1_510.jpeg'),
(@zA, 'P01', 'E02', 'N02', @cAlm, 'Dulce de Leche Conaprole 250 g', 'Dulce de leche, pote de 250 g', '7730105005121', 50, 12, 'https://i.aw.uy/frog/0004753_10616-1_510.jpeg'),
(@zA, 'P01', 'E02', 'N03', @cAlm, 'Mayonesa Hellmann''s 237 g', 'Mayonesa clásica, doypack de 237 g', '7794000958999', 18, 18, 'https://i.aw.uy/frog/0007824_90835-1_510.jpeg'),
(@zA, 'P01', 'E02', 'N04', @cAlm, 'Pulpa de Tomate De Ley 1 kg', 'Pulpa de tomate, envase de 1,02 kg', '7730306000604', 70, 20, 'https://i.aw.uy/frog/0010791_19848-1_510.jpeg'),
(@zA, 'P01', 'E02', 'N05', @cAlm, 'Galletitas María Rika El Trigal 300 g', 'Galletitas dulces, paquete de 300 g', '7730116101188', 75, 20, 'https://i.aw.uy/frog/0004881_10936-1_510.jpeg'),
(@zA, 'P01', 'E03', 'N01', @cAlm, 'Atún al Natural Río de la Plata 170 g', 'Atún en trozos, lata de 170 g', '7730205041678', 60, 15, 'https://i.aw.uy/frog/0004172_11392-1_510.jpeg'),
(@zA, 'P01', 'E03', 'N02', @cAlm, 'Polenta Presto Pronta Arcor 500 g', 'Polenta instantánea, paquete de 500 g', '7790580660000', 40, 10, 'https://i.aw.uy/frog/0006595_13197-1_510.jpeg'),
(@zA, 'P01', 'E03', 'N03', @cAlm, 'Sal Fina Sek 500 g', 'Sal fina yodada, paquete de 500 g', '77300607', 50, 12, 'https://i.aw.uy/frog/0010775_15031-1_510.jpeg'),
(@zB, 'P02', 'E01', 'N01', @cBeb, 'Coca-Cola Original 600 ml', 'Refresco cola, botella de 600 ml', '7730197301958', 48, 12, 'https://i.aw.uy/frog/0002995_coca-cola-zero-600-ml_510.jpeg'),
(@zB, 'P02', 'E01', 'N02', @cBeb, 'Coca-Cola Original 1,5 L', 'Refresco cola, botella de 1,5 L', '7730197232962', 42, 12, 'https://i.aw.uy/frog/0002982_11369-1_510.jpeg'),
(@zB, 'P02', 'E01', 'N03', @cBeb, 'Coca-Cola Original 2,5 L', 'Refresco cola, botella de 2,5 L', '7730197000417', 48, 12, 'https://i.aw.uy/frog/0002817_94178-1_510.jpeg'),
(@zB, 'P02', 'E02', 'N01', @cBeb, 'Coca-Cola Zero 250 ml', 'Refresco cola sin azúcar, botella de 250 ml', '7730197010911', 12, 12, 'https://i.aw.uy/frog/0002947_48787-1_510.jpeg'),
(@zB, 'P02', 'E02', 'N02', @cBeb, 'Coca-Cola Zero 600 ml', 'Refresco cola sin azúcar, botella de 600 ml', '7730197002190', 48, 12, 'https://i.aw.uy/frog/0002958_14893-1_510.jpeg'),
(@zB, 'P02', 'E02', 'N03', @cBeb, 'Coca-Cola Zero 1,5 L', 'Refresco cola sin azúcar, botella de 1,5 L', '7730197002206', 48, 12, 'https://i.aw.uy/frog/0002944_15771-1_510.jpeg'),
(@zB, 'P02', 'E02', 'N04', @cBeb, 'Coca-Cola Zero 2,25 L', 'Refresco cola sin azúcar, botella de 2,25 L', '7730197002213', 48, 12, 'https://i.aw.uy/frog/0002959_15038-1_510.jpeg'),
(@zB, 'P02', 'E03', 'N01', @cBeb, 'Fanta Naranja 1,5 L', 'Refresco de naranja, botella de 1,5 L', '7730197272968', 48, 12, 'https://i.aw.uy/frog/0002918_51494-1_510.jpeg'),
(@zB, 'P02', 'E03', 'N02', @cBeb, 'Fanta Naranja 2,25 L', 'Refresco de naranja, botella de 2,25 L', '7730197006624', 48, 12, 'https://i.aw.uy/frog/0002936_51489-1_510.jpeg'),
(@zB, 'P02', 'E03', 'N03', @cBeb, 'Sprite 1,5 L', 'Refresco lima limón, botella de 1,5 L', '7730197252960', 44, 12, 'https://i.aw.uy/frog/0002415_51495-1_510.jpeg'),
(@zB, 'P02', 'E04', 'N01', @cBeb, 'Nix Cola 2,5 L', 'Refresco cola, botella de 2,5 L', '7730289001124', 48, 12, 'https://prod-resize.tiendainglesa.com.uy/images/medium/P455106-2.jpg'),
(@zB, 'P02', 'E04', 'N02', @cBeb, 'Paso de los Toros Pomelo 1,5 L', 'Refresco de pomelo, botella de 1,5 L', '7734284244081', 48, 12, 'https://i.aw.uy/frog/0006268_14789-1_510.jpeg'),
(@zB, 'P02', 'E04', 'N03', @cBeb, 'Agua Salus sin Gas 1,5 L', 'Agua mineral, botella de 1,5 L', '7730400000173', 60, 15, 'https://i.aw.uy/frog/0003905_15970-1_510.jpeg'),
(@zB, 'P02', 'E04', 'N04', @cBeb, 'Salus Limonada Clásica 1,5 L', 'Limonada sin azúcar, botella de 1,5 L', '7730400004249', 48, 12, 'https://i.aw.uy/frog/0014414_59975-1_510.jpeg'),
(@zC, 'P03', 'E01', 'N01', @cLac, 'Leche Conaprole Entera Ultra 1 L', 'Leche entera ultrapasteurizada, 1 L', '7730105001321', 90, 30, 'https://i.aw.uy/frog/0004818_16890-1_510.jpeg'),
(@zC, 'P03', 'E01', 'N02', @cLac, 'Leche Conaprole Fresca Descremada 1 L', 'Leche descremada pasteurizada, 1 L', '7730105001284', 96, 40, 'https://i.aw.uy/frog/0004817_leche-fresca-conaprole-descremada-1-l_510.jpeg'),
(@zC, 'P03', 'E01', 'N03', @cLac, 'Leche Conaprole Entera Larga Vida 1 L', 'Leche entera larga vida, 1 L', '7730105001413', 60, 20, 'https://i.aw.uy/frog/0004850_10595-1_510.jpeg'),
(@zC, 'P03', 'E01', 'N04', @cLac, 'Yogur Vital Frutilla Conaprole 1 L', 'Yogur bebible de frutilla, 1 L', '7730105023446', 15, 20, 'https://i.aw.uy/frog/0004669_54565-1_510.jpeg'),
(@zC, 'P03', 'E01', 'N05', @cLac, 'Manteca Conaprole 200 g', 'Manteca, pan de 200 g', '77306197', 50, 15, 'https://i.aw.uy/frog/0004785_12043-1_510.jpeg'),
(@zC, 'P03', 'E02', 'N01', @cLac, 'Crema de Leche Conaprole 500 ml', 'Crema de leche, 500 ml', '7730105011283', 40, 12, 'https://i.aw.uy/frog/0004844_10636-1_510.jpeg'),
(@zC, 'P03', 'E02', 'N02', @cLac, 'Queso Rallado Conaprole 80 g', 'Queso rallado, sobre de 80 g', '7730105008832', 45, 12, 'https://i.aw.uy/frog/0004856_10625-1_510.jpeg'),
(@zC, 'P03', 'E02', 'N03', @cLac, 'Queso Danbo en Fetas Farming 200 g', 'Queso danbo feteado, 200 g', '7730939200419', 30, 10, 'https://i.aw.uy/frog/0003749_91633-1_510.png'),
(@zC, 'P03', 'E02', 'N04', @cLac, 'Muzzarella Rallada Farming 200 g', 'Queso muzzarella rallado, 200 g', '7730939200976', 30, 10, 'https://i.aw.uy/frog/0004315_53741-1_510.jpeg'),
(@zC, 'P03', 'E02', 'N05', @cLac, 'Huevos Del Solar 6 un.', 'Huevos frescos, maple de 6 unidades', '7730653000104', 40, 12, 'https://i.aw.uy/frog/0008368_49460-1_510.png'),
(@zD, 'P04', 'E01', 'N01', @cCon, 'Hamburguesas Schneck 12 un.', 'Hamburguesas de carne vacuna, caja de 1 kg', '7730138000056', 37, 10, 'https://i.aw.uy/frog/0003372_hamburguesas-schneck-12-un-1-kg_510.png'),
(@zD, 'P04', 'E01', 'N02', @cCon, 'Hamburguesas Schneck 8 un.', 'Hamburguesas de carne vacuna, caja de 668 g', '7730138000049', 35, 10, 'https://i.aw.uy/frog/0003369_hamburguesas-schneck-8-un-668-g_510.png'),
(@zD, 'P04', 'E01', 'N03', @cCon, 'Papas Bastón McCain 700 g', 'Papas prefritas congeladas, 700 g', '7797906054864', 55, 15, 'https://i.aw.uy/frog/0007170_papas-mc-cain-700g-horneables-tradicional_510.jpeg'),
(@zD, 'P04', 'E01', 'N04', @cCon, 'Helado Conaprole Vainilla 1 L', 'Helado de crema vainilla, 1 L', '7730105912207', 30, 8, 'https://i.aw.uy/frog/0004748_17375-1_510.jpeg'),
(@zD, 'P04', 'E01', 'N05', @cCon, 'Helado Conaprole Dulce de Leche 2 L', 'Helado de dulce de leche marmolado, 2 L', '7730105925047', 21, 8, 'https://i.aw.uy/frog/0004727_20516-1_510.jpeg'),
(@zD, 'P04', 'E02', 'N01', @cCon, 'Pizza Muzzarella Congelada Rodelu', 'Pizzas de muzzarella congeladas, Sarubbi Rodelu', '7730961050259', 20, 6, 'https://i.aw.uy/frog/0008758_58278-1_510.jpeg'),
(@zD, 'P04', 'E02', 'N02', @cCon, 'Arvejas Congeladas 450 g', 'Arvejas congeladas Flanders Best, 450 g', '5412532270021', 6, 12, 'https://i.aw.uy/frog/0007357_22122-1_510.png'),
(@zD, 'P04', 'E02', 'N03', @cCon, 'Nuggets de Pollo Polar 500 g', 'Nuggets de pollo congelados, 500 g', '7730105208010', 30, 8, 'https://i.aw.uy/frog/0008723_58156-1_510.jpeg'),
(@zD, 'P04', 'E02', 'N04', @cCon, 'Medallones de Merluza Patagonia Mare 400 g', 'Medallones de merluza congelados, 400 g', '7798098027872', 25, 8, 'https://i.aw.uy/frog/0007538_53795-1_510.jpeg'),
(@zD, 'P04', 'E02', 'N05', @cCon, 'Milanesas de Soja Sojamil 400 g', 'Milanesas de soja congeladas, 400 g', '7730990540011', 30, 8, 'https://i.aw.uy/frog/0007369_16425-1_510.png'),
(@zE, 'P05', 'E01', 'N01', @cLim, 'Jabón en Polvo Nevex Matic 800 g', 'Jabón en polvo para lavarropas, 800 g', '7791290003392', 36, 10, 'https://i.aw.uy/frog/0007795_13388-1_510.png'),
(@zE, 'P05', 'E01', 'N02', @cLim, 'Detergente Hurra Nevex Limón 1,25 L', 'Detergente lavavajillas, 1,25 L', '7730165317424', 64, 20, 'https://i.aw.uy/frog/0007720_11280-1_510.jpeg'),
(@zE, 'P05', 'E01', 'N03', @cLim, 'Agua Jane Lavanda 2 L', 'Agua lavandina, 2 L', '7793253005528', 60, 15, 'https://i.aw.uy/frog/0005745_55656-1_510.png'),
(@zE, 'P05', 'E01', 'N04', @cLim, 'Suavizante Vivere 900 ml', 'Suavizante para ropa, doypack de 900 ml', '7791290008076', 45, 12, 'https://i.aw.uy/frog/0007783_90998-1_510.jpeg'),
(@zE, 'P05', 'E01', 'N05', @cLim, 'Papel Higiénico Higienol Max 4 rollos', 'Papel higiénico, 4 rollos de 90 m', '7730219011865', 72, 20, 'https://i.aw.uy/frog/0003740_54151-1_510.jpeg'),
(@zE, 'P05', 'E02', 'N01', @cLim, 'Rollo de Cocina Elite 2 un.', 'Rollo de cocina, 2 rollos de 120 paños', '7730219013067', 55, 15, 'https://i.aw.uy/frog/0004160_51128-1_510.jpeg'),
(@zE, 'P05', 'E02', 'N02', @cLim, 'Limpiador Antigrasa Mr. Músculo 450 ml', 'Limpiador de cocina, doypack de 450 ml', '7790520984524', 35, 10, 'https://i.aw.uy/frog/0005966_12937-2_510.jpeg'),
(@zE, 'P05', 'E02', 'N03', @cLim, 'Bolsas de Residuos Júpiter 10 un.', 'Bolsas negras para residuos de 50 x 55 cm', '4897047540141', 65, 15, 'https://i.aw.uy/frog/0007157_52385-1_510.jpeg'),
(@zE, 'P05', 'E02', 'N04', @cLim, 'Esponja Bettanin 3 un.', 'Esponjas de cocina, pack de 3', '7896001045231', 30, 10, 'https://i.aw.uy/frog/0005216_93629-1_510.jpeg'),
(@zE, 'P05', 'E02', 'N05', @cLim, 'Limpiador de Pisos Blem 800 ml', 'Cera limpiadora para pisos plastificados, 800 ml', '7790520004321', 40, 10, 'https://i.aw.uy/frog/0005828_91549-2_510.jpeg'),
(@zF, 'P06', 'E01', 'N01', @cPer, 'Shampoo Sedal Colágeno 650 ml', 'Shampoo, botella de 650 ml', '7791293042022', 40, 10, 'https://i.aw.uy/frog/0007695_54523-1_510.jpeg'),
(@zF, 'P06', 'E01', 'N02', @cPer, 'Acondicionador Sedal Hialurónico 650 ml', 'Acondicionador, botella de 650 ml', '7791293042053', 35, 10, 'https://i.aw.uy/frog/0007696_54525-1_510.jpeg'),
(@zF, 'P06', 'E01', 'N03', @cPer, 'Jabón de Tocador Dove 90 g', 'Jabón de tocador, 90 g', '7898422746827', 41, 12, 'https://i.aw.uy/frog/0000285_14619-1_510.jpeg'),
(@zF, 'P06', 'E01', 'N04', @cPer, 'Crema Dental Colgate Total 90 g', 'Crema dental, 90 g', '7509546702605', 56, 15, 'https://i.aw.uy/frog/0011533_59180-1_510.jpeg'),
(@zF, 'P06', 'E01', 'N05', @cPer, 'Cepillo Dental Colgate Classic', 'Cepillo de dientes adulto, cerdas medias', '7891024184608', 50, 12, 'https://i.aw.uy/frog/0000366_cepillo-dental-colgate-classic-adulto-medio_510.jpeg'),
(@zF, 'P06', 'E02', 'N01', @cPer, 'Desodorante Rexona Cotton 105 ml', 'Desodorante antitranspirante en aerosol, 105 ml', '7791293784465', 8, 10, 'https://i.aw.uy/frog/0007716_13621-1_510.jpeg'),
(@zF, 'P06', 'E02', 'N02', @cPer, 'Toallitas Húmedas Huggies 112 un.', 'Toallitas húmedas para bebé, 112 unidades', '7702425632189', 25, 8, 'https://i.aw.uy/frog/0010934_58345-1_510.jpeg'),
(@zF, 'P06', 'E02', 'N03', @cPer, 'Pañales Huggies Plus Talle G 40 un.', 'Pañales talle G, 40 unidades', '7794626011887', 20, 6, 'https://i.aw.uy/frog/0013615_58804-1_510.jpeg'),
(@zF, 'P06', 'E02', 'N04', @cPer, 'Algodón Zig Zag 100 g', 'Algodón hidrófilo, 100 g', '7730207004107', 40, 10, 'https://i.aw.uy/frog/0007581_11406-1_510.png'),
(@zF, 'P06', 'E02', 'N05', @cPer, 'Crema Corporal Nivea 150 ml', 'Crema humectante, 150 ml', '7804955003634', 28, 8, 'https://i.aw.uy/frog/0001180_93341-1_510.png'),
(@zG, 'P07', 'E01', 'N01', @cMas, 'Alimento para Perros Pedigree Cachorro 3 kg', 'Alimento seco para cachorros, bolsa de 3 kg', '7797453001519', 20, 6, 'https://i.aw.uy/frog/0004506_50166-1_510.png'),
(@zG, 'P07', 'E01', 'N02', @cMas, 'Alimento para Perros Dog Chow Adultos 3 kg', 'Alimento seco razas pequeñas, bolsa de 3 kg', '7613034416865', 23, 6, 'https://prod-resize.tiendainglesa.com.uy/images/medium/P126645-1.jpg'),
(@zG, 'P07', 'E01', 'N03', @cMas, 'Alimento Húmedo Pedigree Carne 100 g', 'Alimento húmedo para perros, sobre de 100 g', '7797453000475', 80, 20, 'https://i.aw.uy/frog/0004585_17768-1_510.png'),
(@zG, 'P07', 'E01', 'N04', @cMas, 'Alimento para Gatos Whiskas Pollo 1 kg', 'Alimento seco para gatos adultos, 1 kg', '7797453971188', 30, 8, 'https://i.aw.uy/frog/0004491_18981-1_510.png'),
(@zG, 'P07', 'E01', 'N05', @cMas, 'Alimento para Gatos Whiskas Pescado 500 g', 'Alimento seco para gatos adultos, 500 g', '7797453970990', 40, 10, 'https://i.aw.uy/frog/0003731_whiskas-pescado-05-kg_510.png'),
(@zG, 'P07', 'E02', 'N01', @cMas, 'Piedras Sanitarias Denucio 2 kg', 'Piedras sanitarias para gatos, 2 kg', '7730204022586', 27, 8, 'https://prod-resize.tiendainglesa.com.uy/images/medium/P017220-1.jpg'),
(@zG, 'P07', 'E03', 'N01', @cBaz, 'Pilas AA Duracell 4 un.', 'Pilas alcalinas AA, 4 unidades', '041333001029', 38, 10, 'https://i.aw.uy/frog/0006881_10006-1_510.png'),
(@zG, 'P07', 'E03', 'N02', @cBaz, 'Fósforos Gaboardi Cocina 200 un.', 'Fósforos de cocina, caja de 200', '7896279200011', 50, 12, 'https://i.aw.uy/frog/0011118_91000-1_510.jpeg'),
(@zG, 'P07', 'E03', 'N03', @cBaz, 'Lámpara LED Philips 10 W E27', 'Lámpara LED de luz fría, rosca E27', '8718699608408', 22, 8, 'https://i.aw.uy/frog/0011067_54433-1_510.jpeg'),
(@zG, 'P07', 'E03', 'N04', @cBaz, 'Papel de Aluminio Darnel 7,5 m', 'Papel de aluminio de 30 cm x 7,5 m', '7898553454820', 30, 8, 'https://i.aw.uy/frog/0003864_56957-1_510.png');

INSERT INTO Ubicacion (pasillo, estante, posicion, disponible, id_zona)
SELECT pasillo, estante, nivel, TRUE, id_zona FROM carga;

-- El trigger NotificarStockNuevo avisa de los que entran en el mínimo
INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, codigo_barras, foto, id_categoria, id_ubicacion)
SELECT c.nombre, c.descripcion, c.stock, c.minimo, 'Disponible', c.codigo, c.foto, c.id_categoria, u.id_ubicacion
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
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001284'), 24, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), 10, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005121'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001284'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105001284'), 'Retiro', @p2, 24);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '77306197'), 'Retiro', @p2, 10);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005121'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105005121'), 'Retiro', @p2, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -2 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46123456', '57441750');
SET @p3 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7791290003392'), 4, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), 8, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165317424'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 3 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011865'), 'Error de producto', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7791290003392'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 9 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7791290003392'), 'Retiro', @p3, 4);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 15 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011865'), 'Retiro', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57441750', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165317424'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 21 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730165317424'), 'Retiro', @p3, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -1 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46234567', '57798668');
SET @p4 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7797453000475'), 12, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), 3, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '041333001029'), 2, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '7797453000475'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7797453000475'), 'Retiro', @p4, 12);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730204022586'), 'Retiro', @p4, 3);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57798668', (SELECT id_producto FROM Producto WHERE codigo_barras = '041333001029'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '041333001029'), 'Retiro', @p4, 2);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL 0 DAY + INTERVAL 15 HOUR, 'Por validar', FALSE, '46345678', '57602994');
SET @p5 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105912207'), 4, TRUE),
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730138000056'), 3, TRUE),
(@p5, (SELECT id_producto FROM Producto WHERE codigo_barras = '7797906054864'), 5, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105912207'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105912207'), 'Retiro', @p5, 4);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730138000056'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730138000056'), 'Retiro', @p5, 3);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('57602994', (SELECT id_producto FROM Producto WHERE codigo_barras = '7797906054864'), DATE(NOW()) - INTERVAL 1 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7797906054864'), 'Retiro', @p5, 5);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 40 MINUTE, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'En Picking', FALSE, '46456789', '56709313');
SET @p6 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), 6, TRUE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197006624'), 4, FALSE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197252960'), 4, FALSE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('56709313', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), NOW() - INTERVAL 40 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730197002190'), 'Retiro', @p6, 6);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '46567890', '57798668');
SET @p7 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7791293042022'), 3, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7898422746827'), 2, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7509546702605'), 4, FALSE),
(@p7, (SELECT id_producto FROM Producto WHERE codigo_barras = '7804955003634'), 2, FALSE);

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
(@p9, (SELECT id_producto FROM Producto WHERE codigo_barras = '5412532270021'), 4, FALSE),
(@p9, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730961050259'), 2, FALSE);

INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 3 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '44567890', NULL);
SET @p10 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p10, (SELECT id_producto FROM Producto WHERE codigo_barras = '7613034416865'), 2, FALSE),
(@p10, (SELECT id_producto FROM Producto WHERE codigo_barras = '8718699608408'), 3, FALSE);

-- 8) Logros (solo agrega los que correspondan)
CALL AsignarLogros('57602994');
CALL AsignarLogros('56709313');
CALL AsignarLogros('57441750');
CALL AsignarLogros('57798668');

-- 9) Resumen
SELECT z.nombre AS zona, COUNT(p.id_producto) AS productos, SUM(p.foto IS NOT NULL) AS con_foto,
       SUM(p.stock_actual) AS unidades, SUM(p.stock_actual <= p.stock_minimo) AS en_minimo
FROM Zona z LEFT JOIN Ubicacion u ON u.id_zona = z.id_zona LEFT JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
GROUP BY z.id_zona, z.nombre ORDER BY z.nombre;
