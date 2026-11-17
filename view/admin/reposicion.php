<?php
/* VISTA - Reposición: productos que hay que pedir, agrupados por proveedor.
   Con los marcados se crea una orden de compra por proveedor (js/reposicion.js). */
$titulo = 'Reposición';
$seccion = 'reposicion';
$scripts = ['js/reposicion.js'];
$grupos = [];
foreach ($productos as $p) {
    $grupos[$p['proveedor'] ?? ''][] = $p;
}
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
        <h1>Reposición</h1>
        <p>Productos que conviene pedir ahora, con la cantidad sugerida. Marcá los que quieras y se arma una orden de compra por proveedor.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('compra')) ?>" class="btn btn-outline-primary"><i class="bi bi-receipt"></i> Órdenes de compra</a>
        <a href="<?= e(url('proveedor')) ?>" class="btn btn-outline-secondary"><i class="bi bi-truck"></i> Proveedores</a>
    </div>
</section>

<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<details class="tarjeta explicacion mb-3">
    <summary><i class="bi bi-info-circle" aria-hidden="true"></i> ¿Cómo se calcula?</summary>
    <ul class="mb-0 mt-2 small">
        <li><strong>Disponible</strong>: el stock menos lo reservado por pedidos que todavía no se recogieron.</li>
        <li><strong>Venta por día</strong>: lo que salió en el picking en los últimos 30 días, dividido 30.</li>
        <li>Un producto aparece cuando lo disponible más lo que ya está <strong>en camino</strong> no alcanza para cubrir el mínimo más lo que se vende mientras llega el pedido (los días de entrega del proveedor).</li>
        <li><strong>Sugerido</strong>: lo que falta para tener el mínimo más <?= (int)$diasCobertura ?> días de venta (como mínimo, el doble del stock mínimo). Podés cambiarlo.</li>
    </ul>
</details>

<?php if (!$productos) { ?>
    <div class="vacio"><i class="bi bi-check2-circle" aria-hidden="true"></i><strong>No hace falta reponer nada</strong><div>Todos los productos tienen stock suficiente (contando lo que ya está pedido).</div></div>
<?php } else { ?>
<form method="POST" action="<?= e(url('compra', 'reposicion')) ?>" id="formReposicion">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php foreach ($grupos as $nombreProveedor => $filas) { $sinProveedor = $nombreProveedor === ''; ?>
        <div class="tarjeta-tabla mb-3 grupo-reposicion">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="tarjeta-titulo mb-0"><i class="bi <?= $sinProveedor ? 'bi-question-circle' : 'bi-truck' ?>" aria-hidden="true"></i>
                    <?= $sinProveedor ? 'Sin proveedor asignado' : e($nombreProveedor) ?></h2>
                <span class="text-secondary small"><?= count($filas) ?> producto<?= count($filas) === 1 ? '' : 's' ?><?= !$sinProveedor && $filas[0]['dias_entrega'] !== null ? ' · entrega en ' . (int)$filas[0]['dias_entrega'] . ' día' . ((int)$filas[0]['dias_entrega'] === 1 ? '' : 's') : '' ?></span>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto btn-marcar-grupo">Marcar todos</button>
            </div>
            <?php if ($sinProveedor) { ?><p class="small text-secondary mt-2 mb-0">Elegí a quién pedirle cada uno; queda como su proveedor habitual.</p><?php } ?>
            <div class="table-responsive mt-2">
                <table class="table align-middle tabla-reposicion">
                    <thead><tr><th class="col-check"><span class="visually-hidden">Pedir</span></th><th>Producto</th><th>Disponible</th><th>En camino</th><th>Venta por día</th><th>Pedir</th><?= $sinProveedor ? '<th>Proveedor</th>' : '' ?></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $p) { $id = (int)$p['id_producto']; $tieneProveedor = !$sinProveedor && $p['proveedor_activo']; ?>
                        <tr class="<?= $p['urgente'] ? 'fila-urgente' : '' ?>">
                            <td class="col-check"><input class="form-check-input chk-pedir" type="checkbox" name="elegido[]" value="<?= $id ?>" id="pedir-<?= $id ?>" <?= $tieneProveedor ? 'checked' : '' ?> aria-label="Pedir <?= e($p['Nombre']) ?>"></td>
                            <td><label for="pedir-<?= $id ?>" class="producto-con-foto mb-0"><?= miniaturaProducto($p['foto'], $p['Nombre']) ?><span><strong><?= e($p['Nombre']) ?></strong><small class="d-block text-secondary"><?= e($p['categoria']) ?></small></span></label></td>
                            <td class="text-nowrap">
                                <span class="badge <?= $p['urgente'] ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int)$p['disponible'] ?></span>
                                <small class="text-secondary">mín. <?= (int)$p['stock_minimo'] ?></small>
                                <?php if ((int)$p['reservado'] > 0) { ?><small class="d-block text-secondary"><?= (int)$p['reservado'] ?> reservadas</small><?php } ?>
                            </td>
                            <td><?= (int)$p['en_camino'] > 0 ? (int)$p['en_camino'] : '<span class="text-secondary">—</span>' ?></td>
                            <td><?= $p['venta_diaria'] > 0 ? e(number_format($p['venta_diaria'], 1, ',', '')) : '<span class="text-secondary">sin ventas</span>' ?></td>
                            <td><input type="number" class="form-control form-control-sm inp-cantidad-pedir" name="cantidad[<?= $id ?>]" min="1" max="100000" value="<?= (int)$p['sugerido'] ?>" aria-label="Cantidad a pedir de <?= e($p['Nombre']) ?>"></td>
                            <?php if ($sinProveedor) { ?>
                                <td>
                                    <select class="form-select form-select-sm sel-proveedor" name="proveedor[<?= $id ?>]" aria-label="Proveedor de <?= e($p['Nombre']) ?>">
                                        <option value="0">Elegí...</option>
                                        <?php foreach ($proveedores as $v) { ?><option value="<?= (int)$v['id_proveedor'] ?>"><?= e($v['nombre']) ?></option><?php } ?>
                                    </select>
                                </td>
                            <?php } else { ?>
                                <input type="hidden" name="proveedor[<?= $id ?>]" value="<?= (int)$p['id_proveedor'] ?>">
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>
    <div class="barra-accion">
        <span id="resumenPedir" aria-live="polite"></span>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-receipt"></i> Crear órdenes de compra</button>
    </div>
</form>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
