<?php
/* VISTA - Detalle de una recepción de mercadería */
$titulo = 'Recepción #' . (int)$recepcion['id_recepcion'];
$seccion = 'recepcion';
require RAIZ . '/view/layout/cabecera.php';
$unidades = array_sum(array_column($lineas, 'cantidad_recibida'));
?>
<a href="<?= e(url('recepcion')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a recepciones</a>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">RECEPCIÓN DE MERCADERÍA</span>
        <h1>Recepción #<?= (int)$recepcion['id_recepcion'] ?></h1>
        <p><?= e(fecha($recepcion['fecha'])) ?> · recibió <?= e($recepcion['persona']) ?>
            · <?= $recepcion['proveedor'] ? e($recepcion['proveedor']) : 'sin proveedor' ?>
            <?php if ($recepcion['id_orden']) { ?> · <?= $sesion['rol'] === 'admin' ? '<a href="' . e(url('compra', 'ver', ['id' => $recepcion['id_orden']])) . '">orden #' . (int)$recepcion['id_orden'] . '</a>' : 'orden #' . (int)$recepcion['id_orden'] ?><?php } ?>
            <?= $recepcion['remito'] ? ' · remito ' . e($recepcion['remito']) : '' ?></p>
    </div>
    <a href="<?= e(url('recepcion', 'nueva')) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Otra recepción</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> <?= e($mensaje) ?></div><?php } ?>
<?php if ($recepcion['observaciones']) { ?><p class="alert alert-light border"><strong>Observaciones:</strong> <?= e($recepcion['observaciones']) ?></p><?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Producto</th><th>Guardar en</th><th>Cantidad</th><th>Lote</th><th>Vence</th></tr></thead>
            <tbody>
            <?php foreach ($lineas as $l) { ?>
                <tr>
                    <td><div class="producto-con-foto"><?= miniaturaProducto($l['foto'], $l['Nombre']) ?><span><strong><?= e($l['Nombre']) ?></strong><small class="d-block text-secondary"><?= e($l['codigo_barras']) ?></small></span></div></td>
                    <td class="text-nowrap"><?= e($l['zona']) ?> · <strong><?= e($l['pasillo'] . '-' . $l['estante'] . '-' . $l['posicion']) ?></strong></td>
                    <td><?= (int)$l['cantidad_recibida'] ?><?= (int)$l['cantidad_actual'] < (int)$l['cantidad_recibida'] ? '<small class="d-block text-secondary">quedan ' . (int)$l['cantidad_actual'] . '</small>' : '' ?></td>
                    <td><?= $l['codigo_lote'] ? '<span class="codigo">' . e($l['codigo_lote']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                    <td class="text-nowrap"><?= $l['fecha_vencimiento'] ? e(fecha($l['fecha_vencimiento'], 'd/m/Y')) : '<span class="text-secondary">—</span>' ?></td>
                </tr>
            <?php } ?>
            </tbody>
            <tfoot><tr><th colspan="2">Total</th><th colspan="3"><?= $unidades ?> unidades</th></tr></tfoot>
        </table>
    </div>
</div>
<p class="nota">Guardá cada producto en su lugar (columna «Guardar en»). Al retirar para un pedido, la hoja de ruta indica qué lote sacar primero.</p>
<?php require RAIZ . '/view/layout/pie.php'; ?>
