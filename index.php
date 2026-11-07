<?php
/* =====================================================
   CONTROLADOR FRONTAL (único punto de entrada)

   Toda la aplicación entra por este archivo. La URL indica
   qué controlador y qué acción ejecutar:

       index.php?c=producto&a=editar&id=3
                   │           │
                   │           └─ acción (método público del controlador)
                   └─ controlador (controller/ProductoController.php)

   Sin parámetros se muestra la página pública (c=inicio).

   Estructura MVC:
     /model       acceso a la base de datos y consultas SQL
     /view        HTML/PHP de la interfaz
     /controller  formularios, validaciones y sesiones
     /css /js /img /uploads  carpetas auxiliares
===================================================== */

date_default_timezone_set('America/Montevideo');

// Cookie de sesión solo para este sitio y no accesible desde JavaScript.
// Nombre propio (PICKFLOW) para no chocar con otra cookie PHPSESSID del mismo dominio.
session_start([
    'name' => 'PICKFLOW',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

define('RAIZ', __DIR__);

require RAIZ . '/model/Conexion.php';
require RAIZ . '/model/Modelo.php';
require RAIZ . '/view/ayudas.php';
require RAIZ . '/controller/Controlador.php';

// Carga automática: al usar una clase se busca su archivo en /controller o /model
spl_autoload_register(function ($clase) {
    foreach (['controller', 'model'] as $carpeta) {
        $archivo = RAIZ . '/' . $carpeta . '/' . $clase . '.php';
        if (is_file($archivo)) {
            require $archivo;
            return;
        }
    }
});

// Controladores que se pueden pedir por URL
$rutas = [
    'inicio'      => 'InicioController',       // sitio público y contacto
    'auth'        => 'AuthController',         // inicio y cierre de sesión
    'admin'       => 'AdminController',        // panel del administrador
    'pedido'      => 'PedidoController',       // pedidos: lista, asignación, alta y validación
    'producto'    => 'ProductoController',     // catálogo y documentación técnica
    'documento'   => 'DocumentoController',    // descarga protegida de documentos
    'operario'    => 'OperarioController',     // gestión de operarios (administrador)
    'logistica'   => 'LogisticaController',    // zonas, ubicaciones e inventario
    'seguimiento' => 'SeguimientoController',  // operarios y hojas de ruta en tiempo real
    'cronograma'  => 'CronogramaController',   // cronograma de despachos (ambos roles)
    'logro'       => 'LogroController',        // desempeño y logros (gamificación)
    'sensor'      => 'SensorController',       // monitor de sensores
    'api'         => 'ApiController',          // API simulada de pedidos
    'picking'     => 'PickingController',      // panel del operario y hoja de ruta
    'perfil'      => 'PerfilController',       // perfil del operario
    'historial'   => 'HistorialController',    // historial del operario
    'reporte'     => 'ReporteController',      // reportes avanzados para el cliente (administrador)
];

$nombre = $_GET['c'] ?? 'inicio';
$accion = $_GET['a'] ?? 'index';

if (!isset($rutas[$nombre]) || !preg_match('/^[a-zA-Z]+$/', $accion)) {
    $nombre = 'inicio';
    $accion = 'noEncontrada';
}

$controlador = new $rutas[$nombre]();
// Solo se ejecutan métodos públicos: los auxiliares del controlador base son protegidos
if (!is_callable([$controlador, $accion])) {
    $controlador = new InicioController();
    $accion = 'noEncontrada';
}
$controlador->$accion();
