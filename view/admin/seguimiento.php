<?php
/* VISTA - Operarios y hojas de ruta (se actualiza sola cada 10 segundos con js/seguimiento.js) */
$titulo = 'Operarios y hojas de ruta';
$seccion = 'seguimiento';
$scripts = ['js/seguimiento.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">SEGUIMIENTO EN TIEMPO REAL</span>
    <h1>Operarios y hojas de ruta</h1>
    <p>Operarios activos y el avance de cada pedido. Se actualiza automáticamente cada 10 segundos.</p>
</section>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="en-vivo" aria-live="polite"><span class="punto-vivo" aria-hidden="true"></span> Última actualización: <strong id="actualizado">cargando...</strong></span>
    <a id="sinAsignar" class="btn btn-sm btn-outline-warning ms-auto d-none" href="<?= e(url('pedido', 'index', ['estado' => 'Pendiente'])) ?>"></a>
</div>
<div id="errorCarga" class="alert alert-danger d-none" role="alert"></div>
<div id="operarios" class="row g-3" data-url="<?= e(url('seguimiento', 'datos')) ?>" data-pedido="<?= e(url('pedido', 'validar')) ?>"></div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
