<?php
/* =====================================================
   FUNCIONES DE AYUDA PARA LAS VISTAS
   Pequeñas funciones que usan las vistas al armar el HTML.
===================================================== */

/* Escapa texto para mostrarlo en HTML (evita inyección de código) */
function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/* Arma la dirección de una página del sistema.
   url('producto', 'editar', ['id' => 3]) -> index.php?c=producto&a=editar&id=3 */
function url($controlador = 'inicio', $accion = 'index', array $parametros = [])
{
    $ruta = ['c' => $controlador];
    if ($accion !== 'index') {
        $ruta['a'] = $accion;
    }
    return 'index.php?' . http_build_query($ruta + $parametros, '', '&', PHP_QUERY_RFC3986);
}

/* Fecha de MySQL con formato uruguayo (por defecto 23/10/2026 14:30) */
function fecha($valor, $formato = 'd/m/Y H:i')
{
    return $valor ? date($formato, strtotime($valor)) : '';
}

/* Ruta pública de una foto de perfil (o null si no tiene) */
function fotoPerfil($archivo)
{
    return $archivo ? 'uploads/perfiles/' . rawurlencode(basename($archivo)) : null;
}

/* Ruta de un archivo propio (css, js) con su versión según la fecha de modificación.
   Así el navegador descarga la hoja nueva cada vez que cambia, sin tener que forzar la recarga. */
function recurso($ruta)
{
    $archivo = __DIR__ . '/../' . $ruta;
    return strpos($ruta, '://') === false && is_file($archivo) ? $ruta . '?v=' . filemtime($archivo) : $ruta;
}
