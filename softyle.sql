CREATE DATABASE IF NOT EXISTS softyle CHARACTER SET utf8 COLLATE utf8_general_ci;
USE softyle;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'operario',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    ubicacion VARCHAR(50) NOT NULL,
    stock INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operario_id INT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
    fecha DATE NOT NULL,
    total_productos INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_pedido_operario FOREIGN KEY (operario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jornadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NULL,
    CONSTRAINT fk_jornada_operario FOREIGN KEY (operario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuarios de prueba. Contraseña de ambos: 1234
INSERT INTO usuarios (nombre, usuario, password, rol, estado)
SELECT 'Administrador', 'admin', '$2y$12$.P1ntmA/hlQhnqX/48f2Eed9kqyLOgHYEKq59bbkz.xSy1gpVcCiC', 'admin', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario='admin');

INSERT INTO usuarios (nombre, usuario, password, rol, estado)
SELECT 'Juan Pérez', 'jperez', '$2y$12$.P1ntmA/hlQhnqX/48f2Eed9kqyLOgHYEKq59bbkz.xSy1gpVcCiC', 'operario', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario='jperez');

INSERT INTO usuarios (nombre, usuario, password, rol, estado)
SELECT 'Martín Rodríguez', 'mrodriguez', '$2y$12$.P1ntmA/hlQhnqX/48f2Eed9kqyLOgHYEKq59bbkz.xSy1gpVcCiC', 'operario', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario='mrodriguez');

INSERT INTO usuarios (nombre, usuario, password, rol, estado)
SELECT 'Santiago López', 'slopez', '$2y$12$.P1ntmA/hlQhnqX/48f2Eed9kqyLOgHYEKq59bbkz.xSy1gpVcCiC', 'operario', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario='slopez');

INSERT INTO productos (nombre,codigo,ubicacion,stock)
SELECT 'Caja de tornillos','P-001','A-01-03',45 
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo='P-001');

INSERT INTO productos (nombre,codigo,ubicacion,stock)
SELECT 'Cable de red','P-002','B-02-01',32 
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo='P-002');

INSERT INTO productos (nombre,codigo,ubicacion,stock)
SELECT 'Teclado inalámbrico','P-003','C-01-05',18 
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo='P-003');

INSERT INTO productos (nombre,codigo,ubicacion,stock)
SELECT 'Mouse óptico','P-004','C-01-06',27
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo='P-004');

INSERT INTO pedidos (operario_id,estado,fecha,total_productos)
SELECT id,'Pendiente','2026-09-02',12 FROM usuarios WHERE usuario='jperez' 
AND NOT EXISTS (SELECT 1 FROM pedidos WHERE id=1048);

INSERT INTO pedidos (id,operario_id,estado,fecha,total_productos)
SELECT 1049,id,'Pendiente','2026-09-02',7 FROM usuarios WHERE usuario='jperez' 
AND NOT EXISTS (SELECT 1 FROM pedidos WHERE id=1049);

INSERT INTO pedidos (id,operario_id,estado,fecha,total_productos)
SELECT 1050,id,'En proceso','2026-09-02',15 FROM usuarios WHERE usuario='jperez' 
AND NOT EXISTS (SELECT 1 FROM pedidos WHERE id=1050);

INSERT INTO jornadas (operario_id,fecha,hora_inicio,hora_fin)
SELECT id,'2026-09-01','08:02:00','16:15:00' FROM usuarios WHERE usuario='jperez'
 AND NOT EXISTS (SELECT 1 FROM jornadas WHERE operario_id=id AND fecha='2026-09-01');
 
INSERT INTO jornadas (operario_id,fecha,hora_inicio,hora_fin)
SELECT id,'2026-08-31','08:05:00','16:30:00' FROM usuarios WHERE usuario='jperez' 
AND NOT EXISTS (SELECT 1 FROM jornadas WHERE operario_id=id AND fecha='2026-08-31');
