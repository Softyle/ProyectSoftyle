<?php
/* =====================================================
   CONEXION A MYSQL - SOFTYLE
   Para WAMP/XAMPP: normalmente root sin contraseña.
   Si tu compañero usa otros datos, cambialos aqui.
===================================================== */

$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$base_datos = "softyle";

$conexion = new mysqli($servidor, $usuario_db, $password_db, $base_datos);

if ($conexion->connect_errno) {
    die("Error de conexión con MySQL: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");
?>
