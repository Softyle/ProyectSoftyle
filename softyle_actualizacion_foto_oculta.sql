-- =====================================================
-- PICKFLOW: ocultar la foto sin borrarla (2026-11-16)
-- Agrega Producto.foto_oculta: la foto queda guardada pero no se
-- muestra en la lista, el mapa ni el picking hasta destildarla.
-- Además devuelve la foto de la tienda a los productos del catálogo
-- que la perdieron con la casilla anterior ("Quitar la foto").
-- =====================================================

ALTER TABLE Producto ADD COLUMN IF NOT EXISTS foto_oculta BOOLEAN NOT NULL DEFAULT FALSE AFTER foto;

SELECT Nombre AS sin_foto_antes FROM Producto WHERE foto IS NULL OR foto = '';

UPDATE Producto SET foto = 'https://i.aw.uy/frog/0006492_10923-1_510.jpeg' WHERE codigo_barras = '7730115040105' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0008689_18110-1_510.png' WHERE codigo_barras = '7730430000143' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0005522_11134-1_510.png' WHERE codigo_barras = '7730132001165' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007899_55163-1_510.png' WHERE codigo_barras = '7730975890346' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0008506_11805-1_510.jpeg' WHERE codigo_barras = '7730376000276' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002761_11563-1_510.jpeg' WHERE codigo_barras = '7730241003654' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004753_10616-1_510.jpeg' WHERE codigo_barras = '7730105005121' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007824_90835-1_510.jpeg' WHERE codigo_barras = '7794000958999' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0010791_19848-1_510.jpeg' WHERE codigo_barras = '7730306000604' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004881_10936-1_510.jpeg' WHERE codigo_barras = '7730116101188' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004172_11392-1_510.jpeg' WHERE codigo_barras = '7730205041678' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0006595_13197-1_510.jpeg' WHERE codigo_barras = '7790580660000' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0010775_15031-1_510.jpeg' WHERE codigo_barras = '77300607' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002995_coca-cola-zero-600-ml_510.jpeg' WHERE codigo_barras = '7730197301958' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002982_11369-1_510.jpeg' WHERE codigo_barras = '7730197232962' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002817_94178-1_510.jpeg' WHERE codigo_barras = '7730197000417' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002947_48787-1_510.jpeg' WHERE codigo_barras = '7730197010911' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002958_14893-1_510.jpeg' WHERE codigo_barras = '7730197002190' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002944_15771-1_510.jpeg' WHERE codigo_barras = '7730197002206' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002959_15038-1_510.jpeg' WHERE codigo_barras = '7730197002213' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002918_51494-1_510.jpeg' WHERE codigo_barras = '7730197272968' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002936_51489-1_510.jpeg' WHERE codigo_barras = '7730197006624' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0002415_51495-1_510.jpeg' WHERE codigo_barras = '7730197252960' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://prod-resize.tiendainglesa.com.uy/images/medium/P455106-2.jpg' WHERE codigo_barras = '7730289001124' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0006268_14789-1_510.jpeg' WHERE codigo_barras = '7734284244081' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003905_15970-1_510.jpeg' WHERE codigo_barras = '7730400000173' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0014414_59975-1_510.jpeg' WHERE codigo_barras = '7730400004249' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004818_16890-1_510.jpeg' WHERE codigo_barras = '7730105001321' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004817_leche-fresca-conaprole-descremada-1-l_510.jpeg' WHERE codigo_barras = '7730105001284' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004850_10595-1_510.jpeg' WHERE codigo_barras = '7730105001413' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004669_54565-1_510.jpeg' WHERE codigo_barras = '7730105023446' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004785_12043-1_510.jpeg' WHERE codigo_barras = '77306197' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004844_10636-1_510.jpeg' WHERE codigo_barras = '7730105011283' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004856_10625-1_510.jpeg' WHERE codigo_barras = '7730105008832' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003749_91633-1_510.png' WHERE codigo_barras = '7730939200419' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004315_53741-1_510.jpeg' WHERE codigo_barras = '7730939200976' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0008368_49460-1_510.png' WHERE codigo_barras = '7730653000104' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003372_hamburguesas-schneck-12-un-1-kg_510.png' WHERE codigo_barras = '7730138000056' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003369_hamburguesas-schneck-8-un-668-g_510.png' WHERE codigo_barras = '7730138000049' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007170_papas-mc-cain-700g-horneables-tradicional_510.jpeg' WHERE codigo_barras = '7797906054864' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004748_17375-1_510.jpeg' WHERE codigo_barras = '7730105912207' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004727_20516-1_510.jpeg' WHERE codigo_barras = '7730105925047' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0008758_58278-1_510.jpeg' WHERE codigo_barras = '7730961050259' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007357_22122-1_510.png' WHERE codigo_barras = '5412532270021' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0008723_58156-1_510.jpeg' WHERE codigo_barras = '7730105208010' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007538_53795-1_510.jpeg' WHERE codigo_barras = '7798098027872' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007369_16425-1_510.png' WHERE codigo_barras = '7730990540011' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007795_13388-1_510.png' WHERE codigo_barras = '7791290003392' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007720_11280-1_510.jpeg' WHERE codigo_barras = '7730165317424' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0005745_55656-1_510.png' WHERE codigo_barras = '7793253005528' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007783_90998-1_510.jpeg' WHERE codigo_barras = '7791290008076' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003740_54151-1_510.jpeg' WHERE codigo_barras = '7730219011865' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004160_51128-1_510.jpeg' WHERE codigo_barras = '7730219013067' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0005966_12937-2_510.jpeg' WHERE codigo_barras = '7790520984524' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007157_52385-1_510.jpeg' WHERE codigo_barras = '4897047540141' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0005216_93629-1_510.jpeg' WHERE codigo_barras = '7896001045231' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0005828_91549-2_510.jpeg' WHERE codigo_barras = '7790520004321' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007695_54523-1_510.jpeg' WHERE codigo_barras = '7791293042022' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007696_54525-1_510.jpeg' WHERE codigo_barras = '7791293042053' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0000285_14619-1_510.jpeg' WHERE codigo_barras = '7898422746827' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0011533_59180-1_510.jpeg' WHERE codigo_barras = '7509546702605' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0000366_cepillo-dental-colgate-classic-adulto-medio_510.jpeg' WHERE codigo_barras = '7891024184608' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007716_13621-1_510.jpeg' WHERE codigo_barras = '7791293784465' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0010934_58345-1_510.jpeg' WHERE codigo_barras = '7702425632189' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0013615_58804-1_510.jpeg' WHERE codigo_barras = '7794626011887' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0007581_11406-1_510.png' WHERE codigo_barras = '7730207004107' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0001180_93341-1_510.png' WHERE codigo_barras = '7804955003634' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004506_50166-1_510.png' WHERE codigo_barras = '7797453001519' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://prod-resize.tiendainglesa.com.uy/images/medium/P126645-1.jpg' WHERE codigo_barras = '7613034416865' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004585_17768-1_510.png' WHERE codigo_barras = '7797453000475' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0004491_18981-1_510.png' WHERE codigo_barras = '7797453971188' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003731_whiskas-pescado-05-kg_510.png' WHERE codigo_barras = '7797453970990' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://prod-resize.tiendainglesa.com.uy/images/medium/P017220-1.jpg' WHERE codigo_barras = '7730204022586' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0006881_10006-1_510.png' WHERE codigo_barras = '041333001029' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0011118_91000-1_510.jpeg' WHERE codigo_barras = '7896279200011' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0011067_54433-1_510.jpeg' WHERE codigo_barras = '8718699608408' AND (foto IS NULL OR foto = '');
UPDATE Producto SET foto = 'https://i.aw.uy/frog/0003864_56957-1_510.png' WHERE codigo_barras = '7898553454820' AND (foto IS NULL OR foto = '');

SELECT COUNT(*) AS productos, SUM(foto IS NOT NULL) AS con_foto, SUM(foto_oculta) AS ocultas FROM Producto;
