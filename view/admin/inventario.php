<?php
/* VISTA - Inventario por zona: stock, reservas y alertas de reposición */
$titulo = 'Stock por zona';
$seccion = 'inventario';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">INVENTARIO</span>
    <h1>Stock por zona</h1>
    <p>Consultá el stock disponible en cada zona del depósito y los productos que necesitan reposición.</p>
</section>

<?php if ($totalAlertas > 0) { ?>
    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div><strong><?= $totalAlertas ?> producto(s)</strong> llegaron al stock mínimo y necesitan reposición.
            <a href="<?= e(url('logistica', 'inventario', ['filtro' => 'Alertas'])) ?>" class="alert-link">Ver solo alertas</a></div>
    </div>
<?php } ?>

<nav class="filtros" aria-label="Filtrar zonas">
    <?php foreach ($filtros as $f) { ?>
        <a class="<?= $filtro === $f ? 'activo' : '' ?>" href="<?= e(url('logistica', 'inventario', ['filtro' => $f])) ?>">
            <?= $f === 'Alertas' ? '<i class="bi bi-exclamation-triangle"></i> Solo alertas' : e($f) ?>
        </a>
    <?php } ?>
</nav>

<?php if (!$zonasVisibles) { ?>
    <div class="tarjeta text-center">No hay zonas para este filtro.</div>
<?php } ?>

<?php foreach ($zonasVisibles as $z) {
    $ocupacion = (int)$z['capacidad_total'] > 0 ? min(100, round((int)$z['stock_total'] * 100 / (int)$z['capacidad_total'])) : 0;
    $icono = $iconosZona[strtolower($z['tipo'])] ?? 'bi-grid-3x3-gap';
?>
    <section class="tarjeta-tabla mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="tarjeta-titulo mb-1"><i class="bi <?= $icono ?>" aria-hidden="true"></i> <?= e($z['nombre']) ?>
                    <span class="badge bg-secondary"><?= e($z['tipo']) ?></span>
                    <?php if (strcasecmp($z['estado'], 'Activa') !== 0) { ?><span class="badge bg-warning text-dark"><?= e($z['estado']) ?></span><?php } ?>
                </h2>
                <small class="text-secondary"><?= e($z['deposito']) ?> · <?= (int)$z['productos'] ?> producto(s) · <?= (int)$z['stock_total'] ?> de <?= (int)$z['capacidad_total'] ?> unidades</small>
            </div>
            <?php if ((int)$z['alertas'] > 0) { ?><span class="badge bg-danger"><?= (int)$z['alertas'] ?> alerta(s)</span><?php } ?>
        </div>
        <div class="progress mb-3" role="progressbar" aria-label="Ocupación de <?= e($z['nombre']) ?>" aria-valuenow="<?= $ocupacion ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar <?= $ocupacion >= 90 ? 'bg-danger' : '' ?>" style="width: <?= $ocupacion ?>%"></div>
        </div>
        <?php if (!$z['items']) { ?>
            <p class="text-secondary mb-0">Esta zona no tiene productos asignados.</p>
        <?php } else { ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Producto</th><th>Categoría</th><th>Ubicación</th><th>Stock</th><th>Reservado</th><th>Mínimo</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($z['items'] as $p) { ?>
                        <tr class="<?= $p['alerta'] ? 'table-danger' : '' ?>">
                            <td><strong><?= e($p['Nombre']) ?></strong></td>
                            <td><?= e($p['categoria']) ?></td>
                            <td class="text-nowrap"><?= e($p['pasillo'] . '-' . $p['estante'] . '-' . $p['posicion']) ?><?php if (Ubicacion::esSectorPiso($p['estante'])) { ?> <span class="etiqueta-piso">Piso</span><?php } ?></td>
                            <td><strong><?= (int)$p['stock_actual'] ?></strong></td>
                            <td><?= (int)$p['reservado'] ?></td>
                            <td><?= (int)$p['stock_minimo'] ?></td>
                            <td><?= $p['alerta']
                                    ? '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Reponer</span>'
                                    : '<span class="badge bg-success">OK</span>' ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </section>
<?php } ?>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Últimas notificaciones del sistema</h2>
    <p class="tarjeta-texto">Se generan automáticamente cuando un producto llega a su stock mínimo.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Mensaje</th></tr></thead>
            <tbody>
            <?php foreach ($notificaciones as $n) { ?>
                <tr>
                    <td class="text-nowrap"><?= e(fecha($n['fecha'])) ?></td>
                    <td><?= e($n['tipo']) ?></td>
                    <td><?= e($n['producto'] ?? '—') ?></td>
                    <td><?= e($n['mensaje']) ?></td>
                </tr>
            <?php } if (!$notificaciones) { ?>
                <tr><td colspan="4" class="text-center">No hay notificaciones.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
