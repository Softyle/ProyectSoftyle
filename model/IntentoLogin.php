<?php
/* =====================================================
   MODELO - INTENTOS DE INICIO DE SESIÓN
   Frena a quien prueba contraseñas una tras otra:
   - 5 intentos fallidos del mismo usuario desde la misma IP, o
   - 20 intentos fallidos desde la misma IP (con cualquier usuario)
   en los últimos 15 minutos bloquean el ingreso hasta que pase el tiempo.
   Al entrar bien se borran los fallos de ese usuario en esa IP.
   Si la tabla todavía no existe (base sin actualizar), no frena nada.
===================================================== */
class IntentoLogin extends Modelo
{
    const MINUTOS = 15;
    const MAX_USUARIO = 5;
    const MAX_IP = 20;

    /* Minutos que faltan para poder volver a intentar (0 = puede intentar) */
    public function minutosDeEspera($usuario, $ip)
    {
        try {
            $f = $this->fila("SELECT SUM(usuario = ?) AS del_usuario, COUNT(*) AS de_la_ip, MIN(fecha) AS primero,
                                     MIN(CASE WHEN usuario = ? THEN fecha END) AS primero_usuario
                              FROM Intento_Login
                              WHERE ip = ? AND fecha > NOW() - INTERVAL " . self::MINUTOS . " MINUTE",
                'sss', [$usuario, $usuario, $ip]);
        } catch (mysqli_sql_exception $ex) {
            return 0;
        }
        if ((int)$f['del_usuario'] >= self::MAX_USUARIO) {
            $desde = $f['primero_usuario'];
        } elseif ((int)$f['de_la_ip'] >= self::MAX_IP) {
            $desde = $f['primero'];
        } else {
            return 0;
        }
        // Se libera cuando el intento más viejo de la ventana cumple los 15 minutos
        return max(1, (int)ceil((strtotime($desde) + self::MINUTOS * 60 - time()) / 60));
    }

    public function registrarFallo($usuario, $ip)
    {
        try {
            $this->ejecutar("INSERT INTO Intento_Login (usuario, ip, fecha) VALUES (?, ?, NOW())", 'ss', [mb_substr($usuario, 0, 100), $ip]);
            // Limpieza: lo de más de un día ya no sirve
            $this->ejecutar("DELETE FROM Intento_Login WHERE fecha < NOW() - INTERVAL 1 DAY");
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW intentos de login: ' . $ex->getMessage());
        }
    }

    public function limpiar($usuario, $ip)
    {
        try {
            $this->ejecutar("DELETE FROM Intento_Login WHERE usuario = ? AND ip = ?", 'ss', [$usuario, $ip]);
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW intentos de login: ' . $ex->getMessage());
        }
    }
}
