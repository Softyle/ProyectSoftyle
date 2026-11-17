-- =====================================================================
-- PICKFLOW - ACTUALIZACIÓN: ABASTECIMIENTO
--   1) Recepción de mercadería con lotes y vencimientos
--   2) Conteo de inventario
--   3) Proveedores, reposición y órdenes de compra
--
-- Se ejecuta UNA vez sobre la base que ya está funcionando (phpMyAdmin ->
-- SQL -> pegar -> Continuar). No borra ni cambia datos existentes: solo
-- agrega tablas, una columna en Producto y algunos proveedores de ejemplo.
-- =====================================================================

-- PROVEEDORES: a quién se le compra cada producto.
-- dias_entrega: cuántos días tarda en llegar un pedido (sirve para sugerir cuánto reponer).
CREATE TABLE IF NOT EXISTS Proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL UNIQUE,
    rut          VARCHAR(12)  NULL,
    contacto     VARCHAR(100) NULL,
    telefono     VARCHAR(20)  NULL,
    email        VARCHAR(100) NULL,
    dias_entrega INT          NOT NULL DEFAULT 2,
    activo       BOOLEAN      NOT NULL DEFAULT TRUE,
    CHECK (dias_entrega BETWEEN 0 AND 90)
) ENGINE = InnoDB;

-- Proveedor habitual de cada producto (opcional)
ALTER TABLE Producto ADD COLUMN IF NOT EXISTS id_proveedor INT NULL;
ALTER TABLE Producto ADD CONSTRAINT fk_producto_proveedor FOREIGN KEY IF NOT EXISTS (id_proveedor) REFERENCES Proveedor(id_proveedor);

-- ÓRDENES DE COMPRA: lo que se le pide a un proveedor.
-- Estados: Pendiente -> Parcial (llegó una parte) -> Recibida, o Cancelada.
CREATE TABLE IF NOT EXISTS Orden_Compra (
    id_orden       INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor   INT          NOT NULL,
    fecha_creacion DATETIME     NOT NULL,
    fecha_esperada DATE         NULL,
    estado         VARCHAR(20)  NOT NULL DEFAULT 'Pendiente',
    ci_creador     VARCHAR(8)   NOT NULL,
    observaciones  VARCHAR(255) NULL,
    FOREIGN KEY (id_proveedor) REFERENCES Proveedor(id_proveedor),
    FOREIGN KEY (ci_creador) REFERENCES Persona(CI)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS Orden_Compra_Detalle (
    id_orden          INT NOT NULL,
    id_producto       INT NOT NULL,
    cantidad_pedida   INT NOT NULL,
    cantidad_recibida INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_orden, id_producto),
    CHECK (cantidad_pedida > 0 AND cantidad_recibida >= 0),
    FOREIGN KEY (id_orden) REFERENCES Orden_Compra(id_orden),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
) ENGINE = InnoDB;

-- RECEPCIONES: cada vez que entra mercadería al depósito (con o sin orden de compra)
CREATE TABLE IF NOT EXISTS Recepcion_Mercaderia (
    id_recepcion  INT AUTO_INCREMENT PRIMARY KEY,
    fecha         DATETIME     NOT NULL,
    id_proveedor  INT          NULL,
    id_orden      INT          NULL,
    remito        VARCHAR(30)  NULL,          -- número de remito o factura del proveedor
    ci_persona    VARCHAR(8)   NOT NULL,      -- quién la recibió (administrador u operario)
    observaciones VARCHAR(255) NULL,
    FOREIGN KEY (id_proveedor) REFERENCES Proveedor(id_proveedor),
    FOREIGN KEY (id_orden) REFERENCES Orden_Compra(id_orden),
    FOREIGN KEY (ci_persona) REFERENCES Persona(CI)
) ENGINE = InnoDB;

-- LOTES: cada ingreso de un producto, con su número de lote y su vencimiento.
-- cantidad_actual baja a medida que se retira (primero lo que vence antes: FEFO).
-- El stock que ya estaba antes de esta actualización queda "sin lote".
CREATE TABLE IF NOT EXISTS Lote (
    id_lote           INT AUTO_INCREMENT PRIMARY KEY,
    id_producto       INT         NOT NULL,
    id_recepcion      INT         NULL,
    codigo_lote       VARCHAR(30) NULL,
    fecha_vencimiento DATE        NULL,
    cantidad_recibida INT         NOT NULL,
    cantidad_actual   INT         NOT NULL,
    fecha_ingreso     DATETIME    NOT NULL,
    CHECK (cantidad_recibida > 0 AND cantidad_actual >= 0 AND cantidad_actual <= cantidad_recibida),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_recepcion) REFERENCES Recepcion_Mercaderia(id_recepcion),
    INDEX idx_lote_vencimiento (id_producto, fecha_vencimiento)
) ENGINE = InnoDB;

-- HISTORIAL DE STOCK: todo lo que cambia el stock fuera del picking
-- (el picking ya queda en la tabla Registra).
-- tipo: 'Recepción', 'Ajuste de conteo', 'Baja por vencimiento' o 'Ajuste manual'.
-- cantidad: positiva si entra, negativa si sale.
CREATE TABLE IF NOT EXISTS Movimiento_Stock (
    id_movimiento    INT AUTO_INCREMENT PRIMARY KEY,
    id_producto      INT          NOT NULL,
    fecha            DATETIME     NOT NULL,
    tipo             VARCHAR(30)  NOT NULL,
    cantidad         INT          NOT NULL,
    stock_resultante INT          NOT NULL,
    ci_persona       VARCHAR(8)   NOT NULL,
    detalle          VARCHAR(255) NULL,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (ci_persona) REFERENCES Persona(CI),
    INDEX idx_movimiento_producto (id_producto, fecha)
) ENGINE = InnoDB;

-- CONTEOS DE INVENTARIO: se cuenta una zona y se ajustan las diferencias.
-- Estados: En curso -> Cerrado (se aplicaron los ajustes) o Cancelado.
-- ciego: el que cuenta no ve el stock del sistema (así no se deja llevar por el número).
CREATE TABLE IF NOT EXISTS Conteo (
    id_conteo     INT AUTO_INCREMENT PRIMARY KEY,
    id_zona       INT          NOT NULL,
    fecha_inicio  DATETIME     NOT NULL,
    fecha_cierre  DATETIME     NULL,
    estado        VARCHAR(20)  NOT NULL DEFAULT 'En curso',
    ciego         BOOLEAN      NOT NULL DEFAULT TRUE,
    ci_creador    VARCHAR(8)   NOT NULL,
    ci_asignado   VARCHAR(8)   NULL,          -- operario que cuenta (vacío: lo cuenta el administrador)
    observaciones VARCHAR(255) NULL,
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona),
    FOREIGN KEY (ci_creador) REFERENCES Persona(CI),
    FOREIGN KEY (ci_asignado) REFERENCES Persona(CI)
) ENGINE = InnoDB;

-- Lo que había en el sistema al empezar y lo que se contó
CREATE TABLE IF NOT EXISTS Conteo_Detalle (
    id_conteo        INT      NOT NULL,
    id_producto      INT      NOT NULL,
    stock_sistema    INT      NOT NULL,
    cantidad_contada INT      NULL,           -- vacío: todavía no se contó
    fecha_conteo     DATETIME NULL,
    PRIMARY KEY (id_conteo, id_producto),
    CHECK (cantidad_contada IS NULL OR cantidad_contada >= 0),
    FOREIGN KEY (id_conteo) REFERENCES Conteo(id_conteo),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
) ENGINE = InnoDB;

-- PROVEEDORES DE EJEMPLO: empresas uruguayas que fabrican productos del catálogo.
-- Los días de entrega son estimados y los datos de contacto quedan vacíos,
-- para ajustarlos desde la aplicación. Los productos de marcas extranjeras
-- quedan sin proveedor: se elige en Reposición o en Editar producto.
INSERT IGNORE INTO Proveedor (nombre, dias_entrega) VALUES
('Conaprole', 1),
('Montevideo Refrescos', 2),
('Salus', 2),
('Schneck', 2),
('Las Acacias', 2),
('Saman', 3),
('COUSA', 3),
('Canarias', 3),
('IPUSA', 3),
('ALUR', 4);

UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Conaprole'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND (p.Nombre LIKE '%Conaprole%' OR p.Nombre LIKE '%Vital%');
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Montevideo Refrescos'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND (p.Nombre LIKE 'Coca-Cola%' OR p.Nombre LIKE 'Fanta%' OR p.Nombre LIKE 'Sprite%');
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Salus'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Salus%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Schneck'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Schneck%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Las Acacias'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Las Acacias%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Saman'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Saman%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'COUSA'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Optimo%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'Canarias'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Canarias%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'IPUSA'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Higienol%';
UPDATE Producto p JOIN Proveedor v ON v.nombre = 'ALUR'
SET p.id_proveedor = v.id_proveedor WHERE p.id_proveedor IS NULL AND p.Nombre LIKE '%Bella Uni%';
