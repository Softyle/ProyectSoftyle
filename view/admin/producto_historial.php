<?php
/* VISTA - Historial de stock de un producto y sus lotes */
$titulo = 'Historial de ' . $producto['Nombre'];
$seccion = 'productos';
$enLotes = array_sum(array_column($lotes, 'cantidad_actual'));
$sinLote = max(0, (int)$producto['stock_actual'] - $enLotes);
$iconos = ['Recepción' => 'bi-box-arrow-in-down text-success', 'Retiro' => 'bi-box-arrow-up', 'Devolución' => 'bi-arrow-counterclockwise',
           'Ajuste de conteo' => 'bi-clipboard-data', 'Baja por vencimiento' => 'bi-calendar2-x text-danger',
           'Ajuste manual' => 'bi-pencil', 'Stock inicial' => 'bi-flag'];
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('producto')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a productos</a>
<section class="encabezado encabezado-con-acciones">
    <div class="producto-con-foto">
        <?= miniaturaProducto($producto['foto_oculta'] ? null : $producto['foto'], $producto['Nombre'], 'foto-picking') ?>
        <div>
            <span class="encabezado-etiqueta">HISTORIAL DE STOCK</span>
            <h1><?= e($producto['Nombre']) ?></h1>
            <p class="mb-0"><?= e($producto['zona'] . ' · ' . $producto['pasillo'] . '-' . $producto['estante'] . '-' . $producto['posicion']) ?>
                · stock <strong><?= (int)$producto['stock_actual'] ?></strong> (mín. <?= (int)$producto['stock_minimo'] ?>)</p>
        </div>
    </div>
    <a href="<?= e(url('producto', 'editar', ['id' => $producto['id_producto']])) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Editar producto</a>
</section>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Lotes en el depósito</h2>
            <?php if (!$lotes && $sinLote === 0) { ?>
                <p class="tarjeta-texto mb-0">No hay stock.</p>
            <?php } ?>
            <ol class="lista-lotes">
                <?php foreach ($lotes as $l) { $d = $l['dias'] !== null ? (int)$l['dias'] : null; ?>
                    <li class="<?= $l['vencido'] ? 'lote-vencido' : '' ?>">
                        <span><strong><?= (int)$l['cantidad_actual'] ?> u.</strong> <?= $l['codigo_lote'] ? '· lote <span class="codigo">' . e($l['codigo_lote']) . '</span>' : '' ?></span>
                        <span class="text-nowrap"><?php if ($l['fecha_vencimiento']) { ?>vence <?= e(fecha($l['fecha_vencimiento'], 'd/m/Y')) ?>
                            <span class="badge <?= $d < 0 ? 'bg-danger' : ($d <= Lote::DIAS_AVISO ? 'bg-warning text-dark' : 'bg-light text-dark border') ?>"><?= $d < 0 ? 'vencido' : ($d === 0 ? 'hoy' : 'en ' . $d . ' d') ?></span>
                        <?php } else { ?><span class="text-secondary">sin vencimiento</span><?php } ?></span>
                    </li>
                <?php } ?>
                <?php if ($sinLote > 0) { ?>
                    <li><span><strong><?= $sinLote ?> u.</strong> sin lote</span><span class="text-secondary small">stock anterior a los lotes o ajustes</span></li>
                <?php } ?>
            </ol>
            <?php if ($lotes) { ?><p class="nota mb-0">En el picking sale primero el lote que vence antes. Lo vencido se da de baja en <a href="<?= e(url('recepcion', 'vencimientos')) ?>">Vencimientos</a>.</p><?php } ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="tarjeta-tabla">
            <h2 class="tarjeta-titulo"><i class="bi bi-clock-history" aria-hidden="true"></i> Movimientos</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Fecha</th><th>Movimiento</th><th>Cantidad</th><th>Quedó</th></tr></thead>
                    <tbody>
                    <?php if (!$movimientos) { ?>
                        <tr><td colspan="4" class="text-center text-secondary">Todavía no hay movimientos registrados.</td></tr>
                    <?php } ?>
                    <?php foreach ($movimientos as $m) { $cant = (int)$m['cantidad']; ?>
                        <tr>
                            <td class="text-nowrap small"><?= e(fecha($m['fecha'], 'd/m/Y H:i')) ?></td>
                            <td><i class="bi <?= $iconos[$m['tipo']] ?? 'bi-arrow-left-right' ?>" aria-hidden="true"></i> <strong><?= e($m['tipo']) ?></strong>
                                <small class="d-block text-secondary"><?= e($m['detalle']) ?> · <?= e($m['persona']) ?></small></td>
                            <td class="fw-semibold <?= $cant > 0 ? 'text-success' : 'text-danger' ?>"><?= $cant > 0 ? '+' . $cant : $cant ?></td>
                            <td><?= $m['stock_resultante'] !== null ? (int)$m['stock_resultante'] : '<span class="text-secondary">—</span>' ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
