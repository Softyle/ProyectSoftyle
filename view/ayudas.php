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

/* Foto de un producto: una foto subida a uploads/productos/ o el enlace (https) a la foto
   que publica una tienda. Devuelve la dirección para el <img> o null si no tiene. */
function fotoProducto($foto)
{
    $foto = (string)$foto;
    if (preg_match('#^https://[^\s"<>]+$#i', $foto)) {
        return $foto;
    }
    return preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $foto) ? 'uploads/productos/' . $foto : null;
}

/* Miniatura de un producto. Si no tiene foto, o la foto no carga (por ejemplo, si la tienda
   cambió el enlace), queda a la vista el ícono de caja que está debajo de la imagen.
   Con foto es un botón: al tocarlo se ve en grande (visor de js/fotos.js). */
function miniaturaProducto($foto, $nombre = '', $clase = '')
{
    $src = fotoProducto($foto);
    $icono = '<i class="bi bi-box-seam" aria-hidden="true"></i>';
    if (!$src) {
        return '<span class="foto-producto ' . e($clase) . '">' . $icono . '</span>';
    }
    return '<button type="button" class="foto-producto foto-ampliable ' . e($clase) . '" data-nombre="' . e($nombre) . '" aria-label="Ver la foto de ' . e($nombre) . ' en grande">'
        . $icono . '<img src="' . e($src) . '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()"></button>';
}

/* Ruta de un archivo propio (css, js) con su versión según la fecha de modificación.
   Así el navegador descarga la hoja nueva cada vez que cambia, sin tener que forzar la recarga. */
function recurso($ruta)
{
    $archivo = __DIR__ . '/../' . $ruta;
    return strpos($ruta, '://') === false && is_file($archivo) ? $ruta . '?v=' . filemtime($archivo) : $ruta;
}
