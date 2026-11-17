<?php
/* VISTA - Vencimientos: lotes vencidos (para dar de baja) y por vencer */
$titulo = 'Vencimientos';
$seccion = 'vencimientos';
$opcionesDias = [$diasAviso => 'Próximos ' . $diasAviso . ' días', 15 => '15 días', 30 => '30 días', 60 => '60 días', 90 => '90 días'];
$vencidos = array_filter($lotes, fn($l) => (int)$l['dias'] < 0);
$porVencer = array_filter($lotes, fn($l) => (int)$l['dias'] >= 0);
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
        <h1>Vencimientos</h1>
        <p>Lotes que ya vencieron o vencen pronto. Lo vencido se da de baja; lo que vence pronto sale primero en el picking.</p>
    </div>
    <a href="<?= e(url('recepcion', 'nueva')) ?>" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-down"></i> Nueva recepción</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<nav class="filtros" aria-label="Plazo">
    <?php foreach ($opcionesDias as $valor => $texto) { ?>
        <a class="<?= $dias === $valor ? 'activo' : '' ?>" href="<?= e(url('recepcion', 'vencimientos', ['dias' => $valor])) ?>" <?= $dias === $valor ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
    <?php } ?>
</nav>

<?php if (!$lotes) { ?>
    <div class="vacio"><i class="bi bi-calendar2-check" aria-hidden="true"></i><strong>Nada vence en este plazo</strong><div>Los lotes se cargan con su fecha al recibir la mercadería.</div></div>
<?php } ?>

<?php foreach ([['Vencidos', $vencidos, true], ['Por vencer', $porVencer, false]] as [$tituloGrupo, $grupo, $sonVencidos]) {
    if (!$grupo) { continue; } ?>
    <div class="tarjeta-tabla mb-3">
        <h2 class="tarjeta-titulo"><i class="bi <?= $sonVencidos ? 'bi-exclamation-octagon text-danger' : 'bi-hourglass-split' ?>" aria-hidden="true"></i> <?= e($tituloGrupo) ?> (<?= count($grupo) ?>)</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th>Ubicación</th><th>Lote</th><th>Unidades</th><th>Vence</th><?= $sonVencidos ? '<th></th>' : '' ?></tr></thead>
                <tbody>
                <?php foreach ($grupo as $l) { $d = (int)$l['dias']; ?>
                    <tr>
                        <td><div class="producto-con-foto"><?= miniaturaProducto($l['foto'], $l['Nombre']) ?><span><a href="<?= e(url('producto', 'historial', ['id' => $l['id_producto']])) ?>"><strong><?= e($l['Nombre']) ?></strong></a><small class="d-block text-secondary"><?= e($l['categoria']) ?></small></span></div></td>
                        <td class="text-nowrap"><?= e($l['zona']) ?> · <?= e($l['pasillo'] . '-' . $l['estante'] . '-' . $l['posicion']) ?></td>
                        <td><?= $l['codigo_lote'] ? '<span class="codigo">' . e($l['codigo_lote']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                        <td><?= (int)$l['cantidad_actual'] ?></td>
                        <td class="text-nowrap">
                            <?= e(fecha($l['fecha_vencimiento'], 'd/m/Y')) ?>
                            <span class="badge <?= $d < 0 ? 'bg-danger' : ($d <= $diasAviso ? 'bg-warning text-dark' : 'bg-light text-dark border') ?>">
                                <?= $d < 0 ? 'hace ' . abs($d) . ' día' . (abs($d) === 1 ? '' : 's') : ($d === 0 ? 'hoy' : ($d === 1 ? 'mañana' : 'en ' . $d . ' días')) ?>
                            </span>
                        </td>
                        <?php if ($sonVencidos) { ?>
                            <td class="text-end">
                                <form method="POST" action="<?= e(url('recepcion', 'vencimientos', ['dias' => $dias])) ?>" onsubmit="return confirm('¿Dar de baja <?= (int)$l['cantidad_actual'] ?> unidades vencidas de <?= e(addslashes($l['Nombre'])) ?>? Se descuentan del stock.');">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="id_lote" value="<?= (int)$l['id_lote'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Dar de baja</button>
                                </form>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
