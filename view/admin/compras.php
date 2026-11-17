<?php
/* VISTA - Órdenes de compra */
$titulo = 'Órdenes de compra';
$seccion = 'compras';
$filtros = ['' => 'Todas', 'Abiertas' => 'Abiertas', 'Recibida' => 'Recibidas', 'Cancelada' => 'Canceladas'];
$claseEstado = ['Pendiente' => 'pendiente', 'Parcial' => 'en-proceso', 'Recibida' => 'completado', 'Cancelada' => ''];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
        <h1>Órdenes de compra</h1>
        <p>Lo que se le pidió a cada proveedor. Cuando llega la mercadería se recibe en Recepción y la orden se completa sola.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('recepcion', 'nueva')) ?>" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-down"></i> Recibir mercadería</a>
        <a href="<?= e(url('compra', 'reposicion')) ?>" class="btn btn-success"><i class="bi bi-cart-plus"></i> Nueva orden desde Reposición</a>
    </div>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<nav class="filtros" aria-label="Filtrar por estado">
    <?php foreach ($filtros as $valor => $texto) { ?>
        <a class="<?= $estado === $valor ? 'activo' : '' ?>" href="<?= e($valor === '' ? url('compra') : url('compra', 'index', ['estado' => $valor])) ?>" <?= $estado === $valor ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
    <?php } ?>
</nav>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Orden</th><th>Proveedor</th><th>Llega</th><th>Recibido</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$ordenes) { ?>
                <tr><td colspan="6" class="text-center">No hay órdenes<?= $estado !== '' ? ' con ese estado' : '' ?>.</td></tr>
            <?php } ?>
            <?php foreach ($ordenes as $o) {
                $abierta = in_array($o['estado'], ['Pendiente', 'Parcial'], true);
                $atrasada = $abierta && $o['fecha_esperada'] && $o['fecha_esperada'] < date('Y-m-d');
                $avance = (int)$o['pedidas'] > 0 ? round((int)$o['recibidas'] * 100 / (int)$o['pedidas']) : 0;
            ?>
                <tr>
                    <td><a href="<?= e(url('compra', 'ver', ['id' => $o['id_orden']])) ?>"><strong>#<?= (int)$o['id_orden'] ?></strong></a>
                        <small class="d-block text-secondary"><?= e(fecha($o['fecha_creacion'], 'd/m/Y')) ?> · <?= e($o['creador']) ?></small></td>
                    <td><?= e($o['proveedor']) ?><small class="d-block text-secondary"><?= (int)$o['productos'] ?> productos · <?= (int)$o['pedidas'] ?> u.</small></td>
                    <td class="text-nowrap <?= $atrasada ? 'text-danger fw-semibold' : '' ?>"><?= $o['fecha_esperada'] ? e(fecha($o['fecha_esperada'], 'd/m/Y')) : '—' ?><?= $atrasada ? ' <i class="bi bi-exclamation-circle" title="Atrasada"></i>' : '' ?></td>
                    <td style="min-width: 130px">
                        <span class="resumen-progreso" title="<?= (int)$o['recibidas'] ?> de <?= (int)$o['pedidas'] ?> unidades"><span style="width: <?= $avance ?>%"></span></span>
                        <small class="text-secondary"><?= (int)$o['recibidas'] ?> de <?= (int)$o['pedidas'] ?></small>
                    </td>
                    <td><span class="estado <?= $claseEstado[$o['estado']] ?? '' ?>"><?= e($o['estado']) ?></span></td>
                    <td class="text-nowrap">
                        <a href="<?= e(url('compra', 'ver', ['id' => $o['id_orden']])) ?>" class="btn btn-sm btn-outline-secondary" title="Ver e imprimir" aria-label="Ver la orden #<?= (int)$o['id_orden'] ?>"><i class="bi bi-eye"></i></a>
                        <?php if ($abierta) { ?>
                            <a href="<?= e(url('recepcion', 'nueva', ['orden' => $o['id_orden']])) ?>" class="btn btn-sm btn-primary" title="Recibir la mercadería" aria-label="Recibir la orden #<?= (int)$o['id_orden'] ?>"><i class="bi bi-box-arrow-in-down"></i></a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
