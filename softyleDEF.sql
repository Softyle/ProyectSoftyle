-- =====================================================================
-- PICKFLOW / SOFTYLE - BASE DE DATOS COMPLETA (archivo único)
--
-- Crea la base "softyle" desde cero, ya con todo lo que usa la aplicación
-- web (segunda, tercera y entrega final). Reemplaza a los archivos
-- anteriores: softyle.sql, softyle_actualizacion_app.sql y
-- softyle_datos_ampliados.sql.
--
-- ATENCIÓN: borra la base "softyle" si ya existe (DROP DATABASE) y la
-- vuelve a crear con los datos de prueba. Si tenés datos propios que
-- quieras conservar, exportá la base antes desde phpMyAdmin.
--
-- Cómo importarlo:
--   - phpMyAdmin: pestaña Importar -> elegir este archivo -> Continuar.
--   - Terminal:   mysql -u root --default-character-set=utf8mb4 < softyleDEF.sql
--
-- Usuarios de prueba (contraseña 1234, salvo Guillermo: 0310):
--   admin (Administrador) - operario, Martina, Diego, Lucia, Santiago,
--   Camila (Operarios activos) - Federico (Operario inactivo).
--
-- Estructura del archivo:
--   PARTE 1 - Base de datos y tablas (InnoDB, con claves foráneas)
--   PARTE 2 - Triggers (alerta automática de stock bajo, RNE-6)
--   PARTE 3 - Datos de prueba
--   PARTE 4 - Vistas y procedimientos almacenados
--
-- Este archivo tiene solo lo que usa la aplicación. Las consultas de la
-- documentación de las entregas van aparte, fuera de la aplicación
-- (carpeta "documentos"):
--   1_consultas_de_la_aplicacion.sql  (una consulta por funcionalidad)
--   2_consultas_avanzadas.sql         (vistas, procedimientos, función,
--                                      trigger, transacción y reportes)
--   3_tercera_entrega_consultas.sql   (consultas de la tercera entrega)
-- Se importan después de este archivo y no modifican los datos.
-- =====================================================================

DROP DATABASE IF EXISTS softyle;
CREATE DATABASE softyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE softyle;
-- Misma collation que las tablas: evita errores de "Illegal mix of collations" en MySQL 8
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================================
-- PARTE 1 - TABLAS
-- Todas con ENGINE = InnoDB: es el motor que respeta las claves foráneas
-- y las transacciones (COMMIT / ROLLBACK). En WAMP el motor por defecto
-- es MyISAM, que las ignora sin avisar.
-- =====================================================================

CREATE TABLE Persona (
    CI VARCHAR(8) PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,          -- hash generado con password_hash()
    telefono VARCHAR(20),
    direccion VARCHAR(150),
    Rol VARCHAR(20) NOT NULL,                  -- 'Administrador', 'Operario' o 'Cliente'
    foto_perfil VARCHAR(255) NULL              -- archivo en uploads/perfiles/
) ENGINE = InnoDB;

CREATE TABLE Cliente (
    CI VARCHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Persona(CI)
) ENGINE = InnoDB;

CREATE TABLE Operario (
    CI VARCHAR(8) PRIMARY KEY,
    Estado VARCHAR(20) NOT NULL,               -- 'Activo' o 'Inactivo'
    hora_ingreso TIME NOT NULL,
    FOREIGN KEY (CI) REFERENCES Persona(CI)
) ENGINE = InnoDB;

CREATE TABLE Deposito (
    id_deposito INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    localidad VARCHAR(100) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE Zona (
    id_zona INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,       -- "Zona" + una letra; no se repite
    tipo VARCHAR(30) NOT NULL,                 -- 'Refrigerada', 'Seca' o 'Voluminosa'
    capacidad_total INT NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL,
    id_deposito INT NOT NULL,
    FOREIGN KEY (id_deposito) REFERENCES Deposito(id_deposito)
) ENGINE = InnoDB;

-- Código de ubicación: pasillo-estante-nivel, por ejemplo P02-E01-N01 (P = pasillo, E = estantería, N = nivel).
-- En las zonas voluminosas el estante E00 es el sector de piso.
CREATE TABLE Ubicacion (
    id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
    pasillo VARCHAR(20) NOT NULL,
    estante VARCHAR(20) NOT NULL,
    posicion VARCHAR(20) NOT NULL,
    disponible BOOLEAN NOT NULL DEFAULT TRUE,
    id_zona INT NOT NULL,
    UNIQUE KEY uq_ubicacion_codigo (pasillo, estante, posicion),   -- el código P02-E01-N01 no se repite
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona)
) ENGINE = InnoDB;

CREATE TABLE Categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
) ENGINE = InnoDB;

CREATE TABLE Producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    CHECK (stock_actual >= 0 AND stock_minimo >= 0),               -- el stock nunca queda negativo
    estado_producto VARCHAR(30) NOT NULL,
    codigo_barras VARCHAR(13) NULL UNIQUE,     -- EAN-13/EAN-8/UPC-A del paquete, o el código interno "20..." del producto
    foto VARCHAR(255) NULL,                    -- enlace https a la foto que publica una tienda, o archivo subido a uploads/productos/
    foto_oculta BOOLEAN NOT NULL DEFAULT FALSE, -- la foto queda guardada pero no se muestra (lista, mapa y picking)
    id_categoria INT NOT NULL,
    id_ubicacion INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria),
    FOREIGN KEY (id_ubicacion) REFERENCES Ubicacion(id_ubicacion)
) ENGINE = InnoDB;

-- Estados: Pendiente -> En Picking -> Por validar -> Completado
CREATE TABLE Pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATETIME NOT NULL,
    fecha_inicio_picking DATETIME NULL,        -- cuando el operario abre la hoja de ruta
    fecha_entrega DATETIME,                    -- entrega programada (la usa el cronograma)
    estado VARCHAR(20) NOT NULL,
    recogido BOOLEAN NOT NULL DEFAULT FALSE,
    ci_cliente VARCHAR(8) NOT NULL,
    ci_operario VARCHAR(8),
    FOREIGN KEY (ci_cliente) REFERENCES Cliente(CI),
    FOREIGN KEY (ci_operario) REFERENCES Operario(CI)
) ENGINE = InnoDB;

CREATE TABLE Notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(30) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL,
    id_producto INT,
    id_zona INT,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona)
) ENGINE = InnoDB;

-- Detalle del pedido: qué productos lleva y si ya se recogieron en el picking
CREATE TABLE Tiene (
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    Cantidad INT NOT NULL,
    recogido BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id_pedido, id_producto),
    FOREIGN KEY (id_pedido) REFERENCES Pedido(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
) ENGINE = InnoDB;

-- Movimientos de stock. Tipo_Registro:
--   'Retiro' (el operario retiró), 'Devolución' (el admin lo devolvió al picking),
--   'Error de cantidad', 'Error de ubicación' y 'Error de producto' (código de barras
--   de otro producto): intentos fallidos del operario
CREATE TABLE Registra (
    ci_operario VARCHAR(8) NOT NULL,
    id_producto INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    Ubicacion_producto VARCHAR(90) NOT NULL,
    Tipo_Registro VARCHAR(30) NOT NULL,
    id_pedido INT NULL,
    cantidad INT NULL,
    PRIMARY KEY (ci_operario, id_producto, fecha_hora),
    FOREIGN KEY (ci_operario) REFERENCES Operario(CI),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_pedido) REFERENCES Pedido(id_pedido)
) ENGINE = InnoDB;

-- Documentación técnica de productos (hojas de seguridad, manuales). El archivo está en uploads/documentos/
CREATE TABLE Documento_Producto (
    id_documento    INT AUTO_INCREMENT PRIMARY KEY,
    id_producto     INT          NOT NULL,
    tipo_documento  VARCHAR(40)  NOT NULL,   -- 'Hoja de seguridad', 'Manual de manipulación' u 'Otro'
    nombre_original VARCHAR(255) NOT NULL,
    archivo         VARCHAR(100) NOT NULL,
    mime            VARCHAR(60)  NOT NULL,
    tamano_bytes    INT          NOT NULL,
    fecha_subida    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
) ENGINE = InnoDB;

-- Recepciones de la API simulada de pedidos
CREATE TABLE API_Recepciones (
    id_recepcion    INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente  VARCHAR(100) NOT NULL,
    sku             VARCHAR(40)  NOT NULL,
    cantidad        INT          NOT NULL,
    zona_destino    VARCHAR(100) NOT NULL,
    estado          VARCHAR(24)  NOT NULL,   -- 'aceptado', 'rechazado' o 'pendiente_ingreso'
    mensaje         VARCHAR(255) NOT NULL,
    id_picking      INT NULL,
    fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Sensores de temperatura y humedad (control ambiental) y sus lecturas
CREATE TABLE Sensor (
    id_sensor   INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL UNIQUE,
    id_zona     INT          NOT NULL,
    temp_min    DECIMAL(5,2) NOT NULL,   -- rango permitido de temperatura (°C)
    temp_max    DECIMAL(5,2) NOT NULL,
    hum_min     DECIMAL(5,2) NOT NULL,   -- rango permitido de humedad (%)
    hum_max     DECIMAL(5,2) NOT NULL,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_zona) REFERENCES Zona(id_zona)
) ENGINE = InnoDB;

CREATE TABLE Lectura_Sensor (
    id_lectura  INT AUTO_INCREMENT PRIMARY KEY,
    id_sensor   INT          NOT NULL,
    temperatura DECIMAL(5,2) NOT NULL,
    humedad     DECIMAL(5,2) NOT NULL,
    fecha_hora  DATETIME     NOT NULL,
    FOREIGN KEY (id_sensor) REFERENCES Sensor(id_sensor),
    INDEX idx_lectura_sensor_fecha (id_sensor, fecha_hora)
) ENGINE = InnoDB;

-- Gamificación: catálogo de logros (insignias) y logros obtenidos por cada operario
CREATE TABLE Logro (
    id_logro     INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(60)  NOT NULL UNIQUE,
    descripcion  VARCHAR(255) NOT NULL,
    categoria    VARCHAR(20)  NOT NULL,   -- 'Precisión', 'Velocidad' o 'Volumen'
    nivel        VARCHAR(10)  NOT NULL,   -- 'Oro', 'Plata' o 'Bronce'
    criterio     VARCHAR(30)  NOT NULL,   -- métrica que se evalúa (columna de Vista_Estadisticas_Operario)
    umbral       DECIMAL(8,2) NOT NULL,   -- valor a alcanzar
    icono        VARCHAR(40)  NOT NULL DEFAULT 'bi-award'
) ENGINE = InnoDB;

CREATE TABLE Operario_Logro (
    ci_operario    VARCHAR(8) NOT NULL,
    id_logro       INT        NOT NULL,
    fecha_obtenido DATETIME   NOT NULL,
    PRIMARY KEY (ci_operario, id_logro),
    FOREIGN KEY (ci_operario) REFERENCES Operario(CI),
    FOREIGN KEY (id_logro) REFERENCES Logro(id_logro)
) ENGINE = InnoDB;

-- Intentos fallidos de inicio de sesión: frenan a quien prueba contraseñas una tras otra
-- (5 del mismo usuario e IP, o 20 de la misma IP, en 15 minutos). Ver model/IntentoLogin.php
CREATE TABLE Intento_Login (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    ip      VARCHAR(45)  NOT NULL,
    fecha   DATETIME     NOT NULL,
    INDEX idx_intento_ip_fecha (ip, fecha)
) ENGINE = InnoDB;

-- =====================================================================
-- PARTE 2 - TRIGGERS
-- RNE-6: cuando el stock de un producto llega al mínimo, se genera
-- automáticamente una notificación para el administrador. Solo se
-- dispara si el stock bajó o si se subió el mínimo (editar el nombre o
-- reponer stock no repite la alerta).
-- =====================================================================

DELIMITER //
CREATE TRIGGER NotificarStock
AFTER UPDATE ON Producto
FOR EACH ROW
BEGIN
    IF NEW.stock_actual <= NEW.stock_minimo
       AND (NEW.stock_actual < OLD.stock_actual OR NEW.stock_minimo > OLD.stock_minimo) THEN
        INSERT INTO Notificacion (tipo, mensaje, fecha, id_producto, id_zona)
        SELECT 'Stock bajo',
               CONCAT(NEW.Nombre, ' quedó con ', NEW.stock_actual, ' unidades (mínimo ', NEW.stock_minimo, ').'),
               NOW(), NEW.id_producto, u.id_zona
        FROM Ubicacion u
        WHERE u.id_ubicacion = NEW.id_ubicacion;
    END IF;
END //
DELIMITER ;

-- Mismo control al dar de alta un producto que ya entra en el mínimo
DELIMITER //
CREATE TRIGGER NotificarStockNuevo
AFTER INSERT ON Producto
FOR EACH ROW
BEGIN
    IF NEW.stock_actual <= NEW.stock_minimo THEN
        INSERT INTO Notificacion (tipo, mensaje, fecha, id_producto, id_zona)
        SELECT 'Stock bajo',
               CONCAT(NEW.Nombre, ' se registró con ', NEW.stock_actual, ' unidades (mínimo ', NEW.stock_minimo, ').'),
               NOW(), NEW.id_producto, u.id_zona
        FROM Ubicacion u
        WHERE u.id_ubicacion = NEW.id_ubicacion;
    END IF;
END //
DELIMITER ;

-- =====================================================================
-- PARTE 3 - DATOS DE PRUEBA
-- =====================================================================

-- PERSONAS (contraseñas guardadas con password_hash: 1234, salvo Guillermo 0310)
INSERT INTO Persona (CI, usuario, email, contraseña, telefono, direccion, Rol) VALUES
('40123456', 'admin',            'admin@gmail.com',             '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099111111', 'Av. Principal 100',       'Administrador'),
('45678901', 'operario',         'operario@gmail.com',          '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099222222', 'Calle Central 200',       'Operario'),
('57602994', 'Guillermo',        'Guillermo@gmail.com',         '$2y$10$VZ5RcSzgdvC7SM8jQfu8he5KZsAe.yfs2Q0ccPRFy5TIipfJ0ASL2', '091312099', 'Maldonado 2125',          'Operario'),
('51234567', 'Martina',          'martina.silva@pickflow.uy',   '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '098111222', 'Av. Italia 3200',         'Operario'),
('52345678', 'Diego',            'diego.pereira@pickflow.uy',   '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099222333', 'Bvar. Artigas 1450',      'Operario'),
('53456789', 'Lucia',            'lucia.rodriguez@pickflow.uy', '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '091333444', 'Colonia 1820',            'Operario'),
('54567890', 'Santiago',         'santiago.mendez@pickflow.uy', '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '092444555', 'Garibaldi 2210',          'Operario'),
('55678901', 'Camila',           'camila.suarez@pickflow.uy',   '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '093555666', '8 de Octubre 3901',       'Operario'),
('56789012', 'Federico',         'federico.nunez@pickflow.uy',  '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '094666777', 'Millán 4400',             'Operario'),
('42345678', 'Lucas Fernandez',  'lucas@email.com',             '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099333333', '18 de Julio 300',         'Cliente'),
('43456789', 'Valentina Lopez',  'valentina@email.com',         '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099444444', 'Rivera 400',              'Cliente'),
('44567890', 'Nicolas Gonzalez', 'nicolas@email.com',           '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '099555555', 'Artigas 500',             'Cliente'),
('46123456', 'Sofia Martinez',   'sofia.martinez@email.com',    '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095101010', 'Rambla Gandhi 650',       'Cliente'),
('46234567', 'Joaquin Castro',   'joaquin.castro@email.com',    '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095202020', 'Av. Brasil 2890',         'Cliente'),
('46345678', 'Florencia Gomez',  'florencia.gomez@email.com',   '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095303030', 'Canelones 1100',          'Cliente'),
('46456789', 'Matias Rodriguez', 'matias.rodriguez@email.com',  '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095404040', 'Av. Rivera 4521',         'Cliente'),
('46567890', 'Agustina Sosa',    'agustina.sosa@email.com',     '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095505050', 'Luis A. de Herrera 1290', 'Cliente'),
('46678901', 'Bruno Acosta',     'bruno.acosta@email.com',      '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095606060', 'Camino Carrasco 6100',    'Cliente'),
('46789012', 'Carolina Diaz',    'carolina.diaz@email.com',     '$2y$10$pcgjzK7.7Oo0l9sGsat68uZ4HovS1bvLdj1Nej2hGfKoKD3nY4eNG', '095707070', 'Av. Giannattasio km 21',  'Cliente');

-- CLIENTES
INSERT INTO Cliente (CI) VALUES
('42345678'), ('43456789'), ('44567890'), ('46123456'), ('46234567'),
('46345678'), ('46456789'), ('46567890'), ('46678901'), ('46789012');

-- OPERARIOS (Federico queda Inactivo: no puede iniciar sesión)
INSERT INTO Operario (CI, Estado, hora_ingreso) VALUES
('45678901', 'Activo',   '07:30:00'),
('57602994', 'Activo',   '08:00:00'),
('51234567', 'Activo',   '06:00:00'),
('52345678', 'Activo',   '06:00:00'),
('53456789', 'Activo',   '08:00:00'),
('54567890', 'Activo',   '14:00:00'),
('55678901', 'Activo',   '14:00:00'),
('56789012', 'Inactivo', '22:00:00');

-- DEPÓSITOS
INSERT INTO Deposito (nombre, direccion, localidad) VALUES
('Deposito Central', 'Ruta 5 Km 25', 'Montevideo'),
('Deposito Norte', 'Av. Artigas 1500', 'Las Piedras');

-- ZONAS: depósito de supermercado (una por rubro). Las refrigeradas tienen sensores de frío.
INSERT INTO Zona (nombre, tipo, capacidad_total, estado, id_deposito) VALUES
('Zona A', 'Seca', 1500, 'Activa', 1),   -- almacén
('Zona B', 'Seca', 1200, 'Activa', 1),   -- bebidas
('Zona C', 'Refrigerada', 800, 'Activa', 1),   -- lácteos (cadena de frío 2 a 8 °C)
('Zona D', 'Refrigerada', 600, 'Activa', 2),   -- congelados (-18 °C)
('Zona E', 'Seca', 1000, 'Activa', 2),   -- limpieza
('Zona F', 'Seca', 600, 'Activa', 1),   -- perfumería e higiene
('Zona G', 'Seca', 800, 'Activa', 1);   -- mascotas y bazar
SET @zA = (SELECT id_zona FROM Zona WHERE nombre = 'Zona A');
SET @zB = (SELECT id_zona FROM Zona WHERE nombre = 'Zona B');
SET @zC = (SELECT id_zona FROM Zona WHERE nombre = 'Zona C');
SET @zD = (SELECT id_zona FROM Zona WHERE nombre = 'Zona D');
SET @zE = (SELECT id_zona FROM Zona WHERE nombre = 'Zona E');
SET @zF = (SELECT id_zona FROM Zona WHERE nombre = 'Zona F');
SET @zG = (SELECT id_zona FROM Zona WHERE nombre = 'Zona G');

-- CATEGORÍAS
INSERT INTO Categoria (nombre, descripcion) VALUES ('Almacén', 'Alimentos secos y envasados de góndola'); SET @cAlm = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bebidas', 'Refrescos, aguas y limonadas'); SET @cBeb = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Lácteos', 'Leche, yogur, manteca, quesos y huevos (cadena de frío 2 a 8 °C)'); SET @cLac = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Congelados', 'Productos congelados (-18 °C o menos)'); SET @cCon = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Limpieza', 'Artículos de limpieza del hogar'); SET @cLim = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Perfumería e higiene', 'Cuidado personal e higiene'); SET @cPer = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Mascotas', 'Alimento y piedras sanitarias para mascotas'); SET @cMas = LAST_INSERT_ID();
INSERT INTO Categoria (nombre, descripcion) VALUES ('Bazar', 'Pilas, fósforos, iluminación y cocina'); SET @cBaz = LAST_INSERT_ID();

-- UBICACIONES Y PRODUCTOS: productos reales de supermercados uruguayos (Frog y Tienda Inglesa),
-- con su código de barras de fábrica y el enlace a la foto que publica la tienda.
-- Un pasillo por zona (P01 = Zona A ... P07 = Zona G), una estantería por grupo y un nivel por producto.
-- Algunos quedan en el stock mínimo a propósito para ver las alertas.
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
INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, codigo_barras, foto, id_categoria, id_ubicacion)
SELECT c.nombre, c.descripcion, c.stock, c.minimo, 'Disponible', c.codigo, c.foto, c.id_categoria, u.id_ubicacion
FROM carga c
JOIN Ubicacion u ON BINARY u.pasillo = BINARY c.pasillo AND BINARY u.estante = BINARY c.estante AND BINARY u.posicion = BINARY c.nivel
ORDER BY c.pasillo, c.estante, c.nivel;
DROP TEMPORARY TABLE carga;

-- PEDIDOS DE EJEMPLO (fechas relativas a hoy: completados, por validar, en picking y pendientes)
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
VALUES (DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -2 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '43456789', '45678901');
SET @p2 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001284'), 24, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), 10, TRUE),
(@p2, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005121'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('45678901', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105001284'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105001284'), 'Retiro', @p2, 24);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('45678901', (SELECT id_producto FROM Producto WHERE codigo_barras = '77306197'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '77306197'), 'Retiro', @p2, 10);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('45678901', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730105005121'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730105005121'), 'Retiro', @p2, 6);
INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -2 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46123456', '51234567');
SET @p3 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7791290003392'), 4, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), 8, TRUE),
(@p3, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165317424'), 6, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('51234567', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 3 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011865'), 'Error de producto', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('51234567', (SELECT id_producto FROM Producto WHERE codigo_barras = '7791290003392'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 9 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7791290003392'), 'Retiro', @p3, 4);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('51234567', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730219011865'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 15 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730219011865'), 'Retiro', @p3, 8);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('51234567', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730165317424'), DATE(NOW()) - INTERVAL 3 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 21 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730165317424'), 'Retiro', @p3, 6);
INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR, DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, DATE(NOW()) + INTERVAL -1 DAY + INTERVAL 15 HOUR, 'Completado', TRUE, '46234567', '52345678');
SET @p4 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7797453000475'), 12, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), 3, TRUE),
(@p4, (SELECT id_producto FROM Producto WHERE codigo_barras = '041333001029'), 2, TRUE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('52345678', (SELECT id_producto FROM Producto WHERE codigo_barras = '7797453000475'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7797453000475'), 'Retiro', @p4, 12);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('52345678', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730204022586'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 12 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730204022586'), 'Retiro', @p4, 3);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('52345678', (SELECT id_producto FROM Producto WHERE codigo_barras = '041333001029'), DATE(NOW()) - INTERVAL 2 DAY + INTERVAL 9 HOUR + INTERVAL 30 MINUTE + INTERVAL 18 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '041333001029'), 'Retiro', @p4, 2);
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
VALUES (NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 40 MINUTE, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'En Picking', FALSE, '46456789', '45678901');
SET @p6 = LAST_INSERT_ID();
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), 6, TRUE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197006624'), 4, FALSE),
(@p6, (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197252960'), 4, FALSE);
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES ('45678901', (SELECT id_producto FROM Producto WHERE codigo_barras = '7730197002190'), NOW() - INTERVAL 40 MINUTE + INTERVAL 6 MINUTE, (SELECT CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) FROM Producto p JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion WHERE p.codigo_barras = '7730197002190'), 'Retiro', @p6, 6);
INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
VALUES (NOW() - INTERVAL 2 HOUR, NULL, DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 15 HOUR, 'Pendiente', FALSE, '46567890', '52345678');
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

-- NOTIFICACIONES: las de stock bajo las genera sola la base (trigger NotificarStockNuevo) al cargar los productos
INSERT INTO Notificacion (tipo, mensaje, fecha, id_producto, id_zona) VALUES
('Picking', 'Hay pedidos pendientes listos para asignar', NOW(), NULL, @zB);

-- SENSORES: ambiente en el almacén y cadena de frío en lácteos (2 a 8 °C) y congelados (-22 a -15 °C)
INSERT INTO Sensor (codigo, id_zona, temp_min, temp_max, hum_min, hum_max) VALUES
('SNS-A01', @zA,  15.00,  25.00, 30.00, 60.00),
('SNS-C01', @zC,   2.00,   8.00, 40.00, 70.00),
('SNS-D01', @zD, -22.00, -15.00, 40.00, 80.00);

-- LOGROS (insignias por precisión, velocidad y volumen de picking)
INSERT INTO Logro (nombre, descripcion, categoria, nivel, criterio, umbral, icono) VALUES
('Primer pedido',    'Completó su primer pedido.',                        'Volumen',   'Bronce', 'pedidos_completados', 1,   'bi-flag'),
('Constante',        'Completó 5 pedidos.',                               'Volumen',   'Plata',  'pedidos_completados', 5,   'bi-boxes'),
('Centenario',       'Recogió 100 unidades o más.',                       'Volumen',   'Oro',    'unidades_recogidas',  100, 'bi-trophy'),
('Ojo de halcón',    'Precisión del 95% o más en sus retiros.',           'Precisión', 'Plata',  'precision_pct',       95,  'bi-bullseye'),
('Picking perfecto', 'Precisión del 100% con al menos 10 retiros.',       'Precisión', 'Oro',    'precision_perfecta',  10,  'bi-patch-check'),
('Rápido',           'Tiempo promedio de picking de 30 minutos o menos.', 'Velocidad', 'Bronce', 'minutos_promedio',    30,  'bi-lightning'),
('Relámpago',        'Tiempo promedio de picking de 15 minutos o menos.', 'Velocidad', 'Oro',    'minutos_promedio',    15,  'bi-lightning-charge'),
('En marcha',            'Completó 3 pedidos.',                                'Volumen',   'Bronce', 'pedidos_completados', 3,    'bi-play-circle'),
('Imparable',            'Completó 10 pedidos.',                               'Volumen',   'Plata',  'pedidos_completados', 10,   'bi-fire'),
('Veterano',             'Completó 25 pedidos.',                               'Volumen',   'Oro',    'pedidos_completados', 25,   'bi-award'),
('Leyenda del depósito', 'Completó 50 pedidos.',                               'Volumen',   'Oro',    'pedidos_completados', 50,   'bi-gem'),
('Manos a la obra',      'Recogió 10 unidades.',                               'Volumen',   'Bronce', 'unidades_recogidas',  10,   'bi-box-seam'),
('Medio centenar',       'Recogió 50 unidades.',                               'Volumen',   'Plata',  'unidades_recogidas',  50,   'bi-stack'),
('Montacargas',          'Recogió 250 unidades.',                              'Volumen',   'Oro',    'unidades_recogidas',  250,  'bi-truck'),
('Titán del depósito',   'Recogió 500 unidades.',                              'Volumen',   'Oro',    'unidades_recogidas',  500,  'bi-rocket-takeoff'),
('Primeros pasos',       'Confirmó su primer retiro en la hoja de ruta.',      'Volumen',   'Bronce', 'retiros',             1,    'bi-signpost'),
('Buen ritmo',           'Confirmó 25 retiros.',                               'Volumen',   'Plata',  'retiros',             25,   'bi-activity'),
('Recolector experto',   'Confirmó 100 retiros.',                              'Volumen',   'Oro',    'retiros',             100,  'bi-basket'),
('Buena puntería',       'Precisión del 80% o más en sus retiros.',            'Precisión', 'Bronce', 'precision_pct',       80,   'bi-crosshair'),
('Certero',              'Precisión del 90% o más en sus retiros.',            'Precisión', 'Plata',  'precision_pct',       90,   'bi-check2-circle'),
('Pulso firme',          'Al menos 5 retiros sin ningún error.',                'Precisión', 'Bronce', 'precision_perfecta',  5,    'bi-hand-index-thumb'),
('Infalible',            'Precisión del 100% con al menos 50 retiros.',        'Precisión', 'Oro',    'precision_perfecta',  50,   'bi-shield-check'),
('Puntual',              'Tiempo promedio de picking de 45 minutos o menos.',  'Velocidad', 'Bronce', 'minutos_promedio',    45,   'bi-clock'),
('Ágil',                 'Tiempo promedio de picking de 20 minutos o menos.',  'Velocidad', 'Plata',  'minutos_promedio',    20,   'bi-speedometer'),
('Supersónico',          'Tiempo promedio de picking de 10 minutos o menos.',  'Velocidad', 'Oro',    'minutos_promedio',    10,   'bi-rocket');

-- =====================================================================
-- PARTE 4 - VISTAS Y PROCEDIMIENTOS ALMACENADOS
-- =====================================================================

-- Estadísticas de picking de cada operario (las usan los logros y el ranking).
--   precision_pct    = retiros correctos / (retiros + errores de cantidad o de ubicación).
--                      Las devoluciones que hace el admin no cuentan como error.
--   minutos_promedio = desde que abre la hoja de ruta hasta su último retiro.
CREATE VIEW Vista_Estadisticas_Operario AS
SELECT
    o.CI AS ci_operario,
    p.usuario AS operario,
    (SELECT COUNT(*) FROM Pedido pe
      WHERE pe.ci_operario = o.CI AND pe.estado = 'Completado') AS pedidos_completados,
    (SELECT COALESCE(SUM(r.cantidad), 0) FROM Registra r
      WHERE r.ci_operario = o.CI AND r.Tipo_Registro = 'Retiro') AS unidades_recogidas,
    (SELECT COUNT(*) FROM Registra r
      WHERE r.ci_operario = o.CI AND r.Tipo_Registro = 'Retiro') AS retiros,
    (SELECT COUNT(*) FROM Registra r
      WHERE r.ci_operario = o.CI AND r.Tipo_Registro LIKE 'Error%') AS errores,
    (SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, pe.fecha_inicio_picking,
        (SELECT MAX(r.fecha_hora) FROM Registra r WHERE r.id_pedido = pe.id_pedido AND r.Tipo_Registro = 'Retiro'))), 1)
       FROM Pedido pe
      WHERE pe.ci_operario = o.CI AND pe.estado = 'Completado'
        AND pe.fecha_inicio_picking IS NOT NULL) AS minutos_promedio,
    (SELECT ROUND(100 * SUM(r.Tipo_Registro = 'Retiro') / NULLIF(COUNT(*), 0), 1)
       FROM Registra r WHERE r.ci_operario = o.CI
        AND (r.Tipo_Registro = 'Retiro' OR r.Tipo_Registro LIKE 'Error%')) AS precision_pct
FROM Operario o
JOIN Persona p ON p.CI = o.CI;

-- Otorga los logros alcanzados por un operario. La aplicación lo llama cuando el
-- admin valida un pedido y cuando el operario abre sus logros.
-- INSERT IGNORE evita repetir un logro ya obtenido.
DELIMITER //
CREATE PROCEDURE AsignarLogros(IN p_ci VARCHAR(8))
BEGIN
    INSERT IGNORE INTO Operario_Logro (ci_operario, id_logro, fecha_obtenido)
    SELECT e.ci_operario, l.id_logro, NOW()
    FROM Vista_Estadisticas_Operario e
    JOIN Logro l ON
           (l.criterio = 'pedidos_completados' AND e.pedidos_completados >= l.umbral)
        OR (l.criterio = 'unidades_recogidas'  AND e.unidades_recogidas  >= l.umbral)
        OR (l.criterio = 'retiros'             AND e.retiros             >= l.umbral)
        OR (l.criterio = 'precision_pct'       AND e.precision_pct       >= l.umbral)
        OR (l.criterio = 'precision_perfecta'  AND e.errores = 0 AND e.retiros >= l.umbral)
        OR (l.criterio = 'minutos_promedio'    AND e.minutos_promedio IS NOT NULL
                                               AND e.minutos_promedio <= l.umbral)
    WHERE e.ci_operario = p_ci;
END //
DELIMITER ;

-- Genera lecturas simuladas de los sensores hacia atrás en el tiempo
-- (alrededor del 10 % sale fuera de rango, para poder ver alertas).
DELIMITER //
CREATE PROCEDURE SimularLecturas(IN p_cantidad INT, IN p_intervalo INT)
BEGIN
    DECLARE i INT DEFAULT 0;
    WHILE i < p_cantidad DO
        INSERT INTO Lectura_Sensor (id_sensor, temperatura, humedad, fecha_hora)
        SELECT s.id_sensor,
               ROUND(IF(RAND() < 0.10, s.temp_max + 0.5 + RAND() * 3,
                        s.temp_min + 0.5 + RAND() * (s.temp_max - s.temp_min - 1)), 2),
               ROUND(s.hum_min + RAND() * (s.hum_max - s.hum_min), 2),
               NOW() - INTERVAL (i * p_intervalo) MINUTE
        FROM Sensor s
        WHERE s.activo = TRUE;
        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

-- Historial inicial: 48 lecturas por sensor, una cada 30 minutos (últimas 24 horas).
-- Después, cada vez que el monitor consulta la API de sensores se genera una lectura nueva.
CALL SimularLecturas(48, 30);

-- Logros que ya corresponden a los datos de prueba
CALL AsignarLogros('45678901');
