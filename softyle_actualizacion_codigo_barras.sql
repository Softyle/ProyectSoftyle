-- =====================================================================
-- PICKFLOW / SOFTYLE - ACTUALIZACIÓN: CÓDIGO DE BARRAS DE LOS PRODUCTOS
--
-- Para una base "softyle" que YA EXISTE (la de WAMP o la del servidor):
-- agrega la columna codigo_barras sin borrar ningún dato y les asigna a
-- todos los productos su código interno. Si la base se crea de cero con
-- softyleDEF.sql, este archivo no hace falta (ya trae la columna).
--
-- Cómo correrlo:
--   - phpMyAdmin: elegir la base softyle -> Importar -> este archivo.
--   - Terminal:   mysql -u root softyle < softyle_actualizacion_codigo_barras.sql
--                 (en el servidor Debian: sudo mariadb softyle < ...)
-- =====================================================================

ALTER TABLE Producto
    ADD COLUMN codigo_barras VARCHAR(13) NULL UNIQUE AFTER estado_producto;

-- Código interno EAN-13: "20" + id con ceros a la izquierda + dígito verificador
UPDATE Producto SET codigo_barras = CONCAT('20', LPAD(id_producto, 10, '0')) WHERE codigo_barras IS NULL;
UPDATE Producto SET codigo_barras = CONCAT(codigo_barras, MOD(10 - MOD(
        SUBSTRING(codigo_barras, 1, 1) + 3 * SUBSTRING(codigo_barras, 2, 1) + SUBSTRING(codigo_barras, 3, 1) + 3 * SUBSTRING(codigo_barras, 4, 1)
      + SUBSTRING(codigo_barras, 5, 1) + 3 * SUBSTRING(codigo_barras, 6, 1) + SUBSTRING(codigo_barras, 7, 1) + 3 * SUBSTRING(codigo_barras, 8, 1)
      + SUBSTRING(codigo_barras, 9, 1) + 3 * SUBSTRING(codigo_barras, 10, 1) + SUBSTRING(codigo_barras, 11, 1) + 3 * SUBSTRING(codigo_barras, 12, 1), 10), 10))
WHERE CHAR_LENGTH(codigo_barras) = 12;
