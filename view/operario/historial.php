<?php
/* VISTA - Historial del operador: la página carga vacía y js/historial.js
   pide los datos y los actualiza cada 15 segundos. */
$titulo = 'Mi historial';
$seccion = 'historial';
$scripts = ['js/historial.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">TRAZABILIDAD</span>
    <h1>Mi historial</h1>
    <p>Todos tus pedidos y retiros de stock. Los estados se actualizan solos desde la base de datos.</p>
</section>

<div id="avisoHistorial" class="alert alert-danger d-none" role="alert"></div>

<section class="row g-3 mb-3" aria-label="Resumen de pedidos" id="resumenHistorial" data-url="<?= e(url('historial', 'datos')) ?>">
    <div class="col-4"><div class="tablero-prioridad prioridad-bronce"><span class="small">Completados</span><p class="resumen-numero" id="resCompletado">–</p></div></div>
    <div class="col-4"><div class="tablero-prioridad prioridad-plata"><span class="small">En proceso</span><p class="resumen-numero" id="resProceso">–</p></div></div>
    <div class="col-4"><div class="tablero-prioridad prioridad-gris"><span class="small">Pendientes</span><p class="resumen-numero" id="resPendiente">–</p></div></div>
</section>
<p class="en-vivo mb-3"><span class="punto-vivo" aria-hidden="true"></span> Última actualización: <strong id="actualizado">cargando...</strong></p>

<section class="tarjeta-tabla mb-4">
    <h2 class="tarjeta-titulo">Mis pedidos</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th>Creado</th><th>Cerrado</th><th>Tiempo de picking</th><th>Unidades</th></tr></thead>
            <tbody id="tablaPedidos"><tr><td colspan="7">Cargando...</td></tr></tbody>
        </table>
    </div>
</section>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Mis retiros de stock</h2>
    <p class="tarjeta-texto">Cada producto que confirmaste en la hoja de ruta, las devoluciones y los errores registrados.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Pedido</th><th>Producto</th><th>Ubicación</th><th>Movimiento</th><th>Cantidad</th></tr></thead>
            <tbody id="tablaMovimientos"><tr><td colspan="6">Cargando...</td></tr></tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
