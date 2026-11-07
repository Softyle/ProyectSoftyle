<?php
/* VISTA - Validación de tareas / detalle del pedido.
   Las acciones se envían por AJAX (js/validar_pedido.js): la página no se recarga
   y el stock, el estado y los movimientos se actualizan solos. */
$titulo = ($porValidar ? 'Validar pedido #' : 'Pedido #') . $idPedido;
$seccion = 'pedidos';
$scripts = ['js/validar_pedido.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('pedido')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a pedidos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta"><?= $porValidar ? 'VALIDACIÓN DE TAREAS' : 'DETALLE DEL PEDIDO' ?></span>
    <h1>Pedido #<?= $idPedido ?></h1>
    <p>Cliente: <strong><?= e($pedido['cliente']) ?></strong> · Operario: <strong><?= e($pedido['operario'] ?? 'Sin asignar') ?></strong> · Estado: <strong id="estadoPedido"><?= e($pedido['estado']) ?></strong></p>
</section>

<!-- Resultado de las acciones (lo completa js/validar_pedido.js) -->
<!-- Si todo coincide no se muestra aviso: la columna "Resultado" de la tabla ya lo indica -->
<div id="avisoValidacion" class="alert <?= $porValidar && !$coinciden ? 'alert-warning' : 'd-none' ?>" role="status" aria-live="polite">
    <?php if ($porValidar && !$coinciden) { ?>
        <i class="bi bi-exclamation-triangle-fill"></i> Hay diferencias entre lo solicitado y lo retirado. Devolvé los productos marcados al picking.
    <?php } ?>
</div>

<form id="formValidacion" data-pedido="<?= $idPedido ?>" data-csrf="<?= e($csrf) ?>" data-url="<?= e(url('pedido', 'procesarValidacion')) ?>">
    <div class="tarjeta-tabla mb-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th class="col-devolver <?= $porValidar ? '' : 'd-none' ?>">Devolver</th><th>Producto</th><th>Ubicación</th><th>Solicitado</th><th>Retirado</th><th>Errores del operario</th><th>Resultado</th></tr></thead>
                <tbody id="tablaItems">
                <?php foreach ($items as $it) { ?>
                    <tr class="<?= $it['coincide'] ? '' : 'table-warning' ?>">
                        <td class="col-devolver <?= $porValidar ? '' : 'd-none' ?>"><input class="form-check-input" type="checkbox" name="devolver[]" value="<?= (int)$it['id_producto'] ?>" <?= $it['coincide'] ? '' : 'checked' ?> aria-label="Devolver <?= e($it['Nombre']) ?> al picking"></td>
                        <td><strong><?= e($it['Nombre']) ?></strong></td>
                        <td><?= e($it['ubicacion']) ?></td>
                        <td><?= (int)$it['solicitada'] ?></td>
                        <td><?= (int)$it['retirada'] ?></td>
                        <td><?= (int)$it['errores'] > 0 ? '<span class="badge bg-warning text-dark">' . (int)$it['errores'] . '</span>' : '0' ?></td>
                        <td><?= $it['coincide'] ? '<span class="badge bg-success">Coincide</span>' : '<span class="badge bg-danger">Revisar</span>' ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="accionesValidacion" class="d-flex flex-wrap gap-2 mb-4 <?= $porValidar ? '' : 'd-none' ?>">
        <button type="submit" data-accion="confirmar" class="btn btn-success btn-lg" <?= $coinciden ? '' : 'disabled' ?>>
            <i class="bi bi-check2-all"></i> Confirmar y marcar Completado
        </button>
        <button type="submit" data-accion="devolver" class="btn btn-outline-danger btn-lg">
            <i class="bi bi-arrow-counterclockwise"></i> Devolver marcados al picking
        </button>
    </div>
</form>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Movimientos de stock del pedido</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Cantidad</th><th>Ubicación</th><th>Operario</th></tr></thead>
            <tbody id="tablaMovimientos">
            <?php foreach ($movimientos as $m) { ?>
                <tr>
                    <td class="text-nowrap"><?= e(fecha($m['fecha_hora'], 'd/m/Y H:i:s')) ?></td>
                    <td><?= e($m['Tipo_Registro']) ?></td>
                    <td><?= e($m['Nombre']) ?></td>
                    <td><?= $m['cantidad'] === null ? '—' : (int)$m['cantidad'] ?></td>
                    <td><?= e($m['Ubicacion_producto']) ?></td>
                    <td><?= e($m['operario']) ?></td>
                </tr>
            <?php } if (!$movimientos) { ?>
                <tr><td colspan="6" class="text-center">Todavía no hay movimientos para este pedido.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
