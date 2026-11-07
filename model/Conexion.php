<?php
/* =====================================================
   MODELO - CONEXIÓN A MYSQL
   Una sola conexión mysqli para todo el sistema: la primera
   vez que un modelo la pide se crea, y después se reutiliza
   (patrón Singleton). Así una transacción abarca a todos
   los modelos que participan en una misma operación.

   En el servidor de producción se cambian estos datos por
   los del usuario de MySQL creado para la aplicación.
===================================================== */
class Conexion
{
    const SERVIDOR = 'localhost';
    const PUERTO = 3306;   // si MySQL/MariaDB usa otro puerto, cambiarlo acá y poner SERVIDOR = '127.0.0.1'
    const USUARIO = 'root';
    const PASSWORD = '';
    const BASE_DATOS = 'softyle';

    private static $conexion = null;

    public static function obtener()
    {
        if (self::$conexion === null) {
            // Con PHP 8.1+ los errores de MySQL llegan como excepción (mysqli_sql_exception)
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $conexion = new mysqli(self::SERVIDOR, self::USUARIO, self::PASSWORD, self::BASE_DATOS, self::PUERTO);
            } catch (mysqli_sql_exception $ex) {
                error_log('PICKFLOW: error de conexión MySQL: ' . $ex->getMessage());
                http_response_code(503);
                exit('No se pudo conectar con la base de datos. Verificá que MySQL esté iniciado.');
            }
            $conexion->set_charset('utf8mb4');
            // Hora de Uruguay también para MySQL: NOW() y CURDATE() no dependen de la zona del servidor
            $conexion->query("SET time_zone = '-03:00'");
            self::$conexion = $conexion;
        }
        return self::$conexion;
    }
}
