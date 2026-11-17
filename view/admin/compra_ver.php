<?php
/* VISTA - Detalle de una orden de compra: para imprimir o mandar por correo al proveedor */
$titulo = 'Orden de compra #' . (int)$orden['id_orden'];
$seccion = 'compras';
$abierta = in_array($orden['estado'], ['Pendiente', 'Parcial'], true);
$claseEstado = ['Pendiente' => 'pendiente', 'Parcial' => 'en-proceso', 'Recibida' => 'completado', 'Cancelada' => ''];

// Texto del correo: el administrador lo manda desde su propio correo (mailto)
$cuerpo = "Hola" . ($orden['contacto'] ? ' ' . $orden['contacto'] : '') . ",\n\nLes hacemos el siguiente pedido (orden de compra #" . (int)$orden['id_orden'] . "):\n\n";
foreach ($lineas as $l) {
    $cuerpo .= '- ' . (int)$l['cantidad_pedida'] . ' u. de ' . $l['Nombre'] . ($l['codigo_barras'] ? ' (código ' . $l['codigo_barras'] . ')' : '') . "\n";
}
$cuerpo .= ($orden['fecha_esperada'] ? "\nLo necesitamos para el " . fecha($orden['fecha_esperada'], 'd/m/Y') . ".\n" : '') . "\nGracias.\nPICKFLOW";
$mailto = 'mailto:' . rawurlencode((string)$orden['email']) . '?subject=' . rawurlencode('Orden de compra #' . (int)$orden['id_orden']) . '&body=' . rawurlencode($cuerpo);
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('compra')) ?>" class="volver no-imprimir"><i class="bi bi-arrow-left"></i> Volver a órdenes de compra</a>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">ORDEN DE COMPRA</span>
        <h1>#<?= (int)$orden['id_orden'] ?> · <?= e($orden['proveedor']) ?></h1>
        <p>Creada el <?= e(fecha($orden['fecha_creacion'])) ?> por <?= e($orden['creador']) ?><?= $orden['fecha_esperada'] ? ' · llega el ' . e(fecha($orden['fecha_esperada'], 'd/m/Y')) : '' ?>
            · <span class="estado <?= $claseEstado[$orden['estado']] ?? '' ?>"><?= e($orden['estado']) ?></span></p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-imprimir">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <?php if ($abierta) { ?>
            <?php if ($orden['email']) { ?>
                <a href="<?= e($mailto) ?>" class="btn btn-outline-primary"><i class="bi bi-envelope"></i> Mandar por correo</a>
            <?php } ?>
            <a href="<?= e(url('recepcion', 'nueva', ['orden' => $orden['id_orden']])) ?>" class="btn btn-success"><i class="bi bi-box-arrow-in-down"></i> Recibir mercadería</a>
        <?php } ?>
    </div>
</section>

<?php if ($abierta && !$orden['email']) { ?>
    <p class="alert alert-info small no-imprimir">Para mandarla por correo desde acá, cargale el correo al proveedor en <a href="<?= e(url('proveedor', 'editar', ['id' => $orden['id_proveedor']])) ?>">Proveedores</a>.</p>
<?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Producto</th><th>Código</th><th>Pedido</th><th>Recibido</th></tr></thead>
            <tbody>
            <?php foreach ($lineas as $l) { $completo = (int)$l['cantidad_recibida'] >= (int)$l['cantidad_pedida']; ?>
                <tr>
                    <td><div class="producto-con-foto"><?= miniaturaProducto($l['foto'], $l['Nombre']) ?><strong><?= e($l['Nombre']) ?></strong></div></td>
                    <td class="text-nowrap"><?= e($l['codigo_barras']) ?></td>
                    <td><strong><?= (int)$l['cantidad_pedida'] ?></strong></td>
                    <td class="<?= $completo ? 'text-success' : '' ?>"><?= (int)$l['cantidad_recibida'] ?><?= $completo ? ' <i class="bi bi-check-circle-fill" aria-label="completo"></i>' : '' ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($recepciones) { ?>
    <div class="tarjeta mt-3">
        <h2 class="tarjeta-titulo"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Recepciones de esta orden</h2>
        <ul class="lista-simple">
            <?php foreach ($recepciones as $r) { ?>
                <li><a href="<?= e(url('recepcion', 'ver', ['id' => $r['id_recepcion']])) ?>">Recepción #<?= (int)$r['id_recepcion'] ?></a>
                    <small class="text-secondary"><?= e(fecha($r['fecha'])) ?> · <?= (int)$r['unidades'] ?> unidades · <?= e($r['persona']) ?><?= $r['remito'] ? ' · remito ' . e($r['remito']) : '' ?></small></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<?php if ($abierta) { ?>
    <form method="POST" action="<?= e(url('compra', 'cancelar')) ?>" class="mt-3 no-imprimir" onsubmit="return confirm('¿Cancelar la orden #<?= (int)$orden['id_orden'] ?>? Lo que falta recibir deja de contar como «en camino».');">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$orden['id_orden'] ?>">
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Cancelar la orden</button>
    </form>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
