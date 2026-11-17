-- =====================================================
-- PICKFLOW: límite de intentos de inicio de sesión (2026-11-16)
-- Tabla donde se anotan los intentos fallidos (ver model/IntentoLogin.php).
-- =====================================================
CREATE TABLE IF NOT EXISTS Intento_Login (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    ip      VARCHAR(45)  NOT NULL,
    fecha   DATETIME     NOT NULL,
    INDEX idx_intento_ip_fecha (ip, fecha)
) ENGINE = InnoDB;
