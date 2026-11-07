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
    nombre VARCHAR(100) NOT NULL,
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
    estado_producto VARCHAR(30) NOT NULL,
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
--   'Error de cantidad' y 'Error de ubicación' (intentos fallidos del operario)
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

-- ZONAS (según la naturaleza de los productos que guardan)
INSERT INTO Zona (nombre, tipo, capacidad_total, estado, id_deposito) VALUES
('Zona A', 'Seca',        500, 'Activa', 1),   -- informática y oficina
('Zona B', 'Seca',        300, 'Activa', 1),   -- electrónica y cables
('Zona C', 'Voluminosa',  200, 'Activa', 1),   -- mobiliario y equipos grandes
('Zona D', 'Voluminosa',  400, 'Activa', 2),   -- maquinaria
('Zona E', 'Seca',        250, 'Activa', 2),   -- herramientas manuales y seguridad
('Zona F', 'Refrigerada', 150, 'Activa', 1);   -- lácteos (cadena de frío)

-- UBICACIONES (una por producto)
INSERT INTO Ubicacion (pasillo, estante, posicion, disponible, id_zona) VALUES
('P01', 'E01', 'N01', TRUE, 1),
('P01', 'E01', 'N02', TRUE, 1),
('P01', 'E02', 'N01', TRUE, 1),
('P02', 'E01', 'N01', TRUE, 2),
('P02', 'E01', 'N02', TRUE, 2),
('P02', 'E02', 'N01', TRUE, 2),
('P03', 'E01', 'N01', TRUE, 3),
('P03', 'E02', 'N01', TRUE, 4),
('P04', 'E01', 'N01', TRUE, 4),
('P04', 'E01', 'N02', TRUE, 5),
('P01', 'E02', 'N02', TRUE, 1),
('P01', 'E03', 'N01', TRUE, 1),
('P02', 'E02', 'N02', TRUE, 2),
('P02', 'E03', 'N01', TRUE, 2),
('P05', 'E00', 'N01', TRUE, 3),   -- E00 = sector de piso
('P05', 'E00', 'N02', TRUE, 3),
('P05', 'E00', 'N03', TRUE, 3),
('P05', 'E00', 'N04', TRUE, 3),
('P06', 'E00', 'N01', TRUE, 4),
('P06', 'E00', 'N02', TRUE, 4),
('P06', 'E00', 'N03', TRUE, 4),
('P08', 'E01', 'N01', TRUE, 5),
('P08', 'E01', 'N02', TRUE, 5),
('P08', 'E02', 'N01', TRUE, 5),
('P08', 'E02', 'N02', TRUE, 5),
('P07', 'E01', 'N01', TRUE, 6),
('P07', 'E01', 'N02', TRUE, 6),
('P07', 'E02', 'N01', TRUE, 6),
('P07', 'E02', 'N02', TRUE, 6),
('P07', 'E03', 'N01', TRUE, 6);

-- CATEGORÍAS
INSERT INTO Categoria (nombre, descripcion) VALUES
('Informatica',  'Equipos y accesorios informaticos'),
('Electronica',  'Productos electronicos'),
('Oficina',      'Articulos de oficina'),
('Herramientas', 'Herramientas de trabajo'),
('Seguridad',    'Elementos de seguridad'),
('Mobiliario',   'Muebles y equipamiento de oficina de gran tamaño'),
('Maquinaria',   'Máquinas y equipos de carga voluminosos'),
('Lácteos',      'Productos refrigerados que requieren cadena de frío (2 a 8 °C)');

-- PRODUCTOS (5 por zona). Los números de ubicación corresponden al orden de arriba.
-- Algunos quedan en el stock mínimo a propósito para ver las alertas.
INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, id_categoria, id_ubicacion) VALUES
-- 1 a 11: productos originales (los usan los pedidos de prueba)
('Teclado USB',              'Teclado USB estandar',                           50,  10, 'Disponible', 1, 1),
('Mouse USB',                'Mouse optico USB',                               75,  15, 'Disponible', 1, 2),
('Monitor 24 pulgadas',      'Monitor LED de 24 pulgadas',                     20,   5, 'Disponible', 1, 3),
('Cable HDMI',               'Cable HDMI de 2 metros',                        100,  20, 'Disponible', 2, 4),
('Cable de red',             'Cable de red UTP categoria 6',                  150,  30, 'Disponible', 2, 5),
('Auriculares',              'Auriculares con microfono',                      35,  10, 'Disponible', 1, 6),
('Resma A4',                 'Resma de papel de 500 hojas',                    80,  20, 'Disponible', 3, 12),
('Lapicera azul',            'Lapicera de tinta azul',                        200,  50, 'Disponible', 3, 11),
('Destornillador',           'Destornillador Phillips',                        25,   5, 'Disponible', 4, 22),
('Guantes de seguridad',     'Guantes de proteccion',                          60,  15, 'Disponible', 5, 10),
('Auriculares Inalambricos', 'Auriculares con Bluetooth',                       9,  10, 'Disponible', 1, 13),
-- Zona B (Seca): electrónica y cables
('Cargador USB-C 65W',       'Cargador rápido USB-C para notebook',            40,  10, 'Disponible', 2, 14),
-- Zona C (Voluminosa): mobiliario y equipos grandes
('Escritorio de oficina',    'Escritorio de 140 x 70 cm con cajonera',          8,   3, 'Disponible', 6, 15),
('Silla ergonómica',         'Silla de oficina con apoyo lumbar',              15,   5, 'Disponible', 6, 16),
('Impresora multifunción',   'Impresora láser con escáner, 40 kg',             10,   4, 'Disponible', 1, 7),
('Rack de servidor 42U',     'Gabinete de piso para servidores',                3,   2, 'Disponible', 1, 17),
('UPS 3000VA',               'Respaldo de energía para servidores, 35 kg',      6,   2, 'Disponible', 2, 18),
-- Zona D (Voluminosa): maquinaria
('Escalera de aluminio 3 m', 'Escalera tijera de 8 escalones',                 12,   4, 'Disponible', 4, 8),
('Carretilla de carga',      'Carretilla de dos ruedas, hasta 250 kg',          9,   3, 'Disponible', 7, 9),
('Compresor de aire 50 L',   'Compresor eléctrico de 2 HP',                     5,   2, 'Disponible', 7, 19),
('Hidrolavadora industrial', 'Hidrolavadora de 2000 W',                         4,   2, 'Disponible', 7, 20),
('Taladro de banco',         'Taladro de columna de 16 mm',                     2,   2, 'Disponible', 7, 21),
-- Zona E (Seca): herramientas manuales y seguridad
('Martillo de carpintero',   'Martillo de 500 g con mango de fibra',           30,   8, 'Disponible', 4, 23),
('Casco de seguridad',       'Casco con arnés regulable',                       8,  10, 'Disponible', 5, 24),
('Lentes de protección',     'Lentes antiempañantes',                          45,  12, 'Disponible', 5, 25),
-- Zona F (Refrigerada): lácteos
('Leche entera 1 L',         'Leche pasteurizada, conservar entre 2 y 8 °C',   60,  20, 'Disponible', 8, 26),
('Yogur natural 1 kg',       'Yogur entero sin azúcar',                        12,  15, 'Disponible', 8, 27),
('Queso muzzarella 1 kg',    'Horma de muzzarella para fraccionar',            25,   8, 'Disponible', 8, 28),
('Manteca 200 g',            'Manteca con sal',                                30,  10, 'Disponible', 8, 29),
('Crema de leche 500 ml',    'Crema doble para cocina',                        20,   6, 'Disponible', 8, 30);

-- PEDIDOS
INSERT INTO Pedido (fecha_creacion, fecha_inicio_picking, fecha_entrega, estado, recogido, ci_cliente, ci_operario) VALUES
('2026-09-25 08:30:00', '2026-09-25 08:50:00', '2026-09-26 12:00:00', 'Completado', TRUE,  '42345678', '45678901'),
('2026-09-26 10:15:00', '2026-09-26 09:55:00', '2026-09-27 15:00:00', 'En Picking', FALSE, '43456789', '45678901'),
('2026-09-27 09:00:00', NULL,                  '2026-09-28 11:00:00', 'Pendiente',  FALSE, '44567890', NULL),
('2026-09-28 09:00:00', NULL,                  '2026-09-29 11:00:00', 'Pendiente',  FALSE, '44567890', NULL);

-- TIENE (detalle de cada pedido; recogido = ya se retiró en el picking)
INSERT INTO Tiene (id_pedido, id_producto, Cantidad, recogido) VALUES
(1, 1, 5, TRUE),
(1, 2, 2, TRUE),
(1, 4, 10, TRUE),
(2, 3, 1, TRUE),
(2, 5, 4, TRUE),
(2, 6, 2, FALSE),
(3, 7, 3, FALSE),
(3, 8, 10, FALSE),
(4, 9, 2, FALSE),
(4, 10, 5, FALSE);

-- REGISTROS DEL OPERARIO (movimientos de stock de los pedidos 1 y 2)
INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad) VALUES
('45678901', 1, '2026-09-25 09:00:00', 'P01-E01-N01', 'Retiro', 1, 5),
('45678901', 2, '2026-09-25 09:05:00', 'P01-E01-N02', 'Retiro', 1, 2),
('45678901', 4, '2026-09-25 09:10:00', 'P02-E01-N01', 'Retiro', 1, 10),
('45678901', 3, '2026-09-26 10:00:00', 'P01-E02-N01', 'Retiro', 2, 1),
('45678901', 5, '2026-09-26 10:05:00', 'P02-E01-N02', 'Retiro', 2, 4);

-- NOTIFICACIONES
INSERT INTO Notificacion (tipo, mensaje, fecha, id_producto, id_zona) VALUES
('Stock bajo', 'El stock del producto se encuentra cerca del minimo', '2026-09-29 08:00:00', 3, 1),
('Picking', 'El pedido 2 esta listo para realizar picking', '2026-09-29 09:00:00', NULL, 2),
('Stock', 'Producto disponible para picking', '2026-09-29 09:30:00', 5, 2);

-- SENSORES: cadena de frío en la Zona F (2 a 8 °C) y ambiente en la Zona A
INSERT INTO Sensor (codigo, id_zona, temp_min, temp_max, hum_min, hum_max) VALUES
('SNS-F01', 6,  2.00,  8.00, 40.00, 70.00),
('SNS-A01', 1, 15.00, 25.00, 30.00, 60.00);

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
