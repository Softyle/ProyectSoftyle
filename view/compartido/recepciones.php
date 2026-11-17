<?php
/* VISTA - Recepciones de mercadería (administrador: todas; operario: las suyas) */
$titulo = 'Recepción de mercadería';
$seccion = 'recepcion';
require RAIZ . '/view/layout/cabecera.php';
$esAdminVista = $sesion['rol'] === 'admin';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta"><?= $esAdminVista ? 'ABASTECIMIENTO' : 'DEPÓSITO' ?></span>
        <h1>Recepción de mercadería</h1>
        <p>Lo que entra al depósito: escaneás cada producto, ponés la cantidad, el lote y el vencimiento, y el stock sube solo.</p>
    </div>
    <a href="<?= e(url('recepcion', 'nueva')) ?>" class="btn btn-success btn-lg"><i class="bi bi-plus-circle"></i> Nueva recepción</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>

<?php if ($resumenVencimientos && ($resumenVencimientos['vencidos'] || $resumenVencimientos['por_vencer'])) { ?>
    <div class="aviso-validar aviso-vencimiento" role="status">
        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
        <span><strong><?= $resumenVencimientos['vencidos'] ?></strong> lotes vencidos · <strong><?= $resumenVencimientos['por_vencer'] ?></strong> vencen en los próximos <?= Lote::DIAS_AVISO ?> días</span>
        <a class="btn btn-sm btn-danger ms-auto" href="<?= e(url('recepcion', 'vencimientos', ['dias' => Lote::DIAS_AVISO])) ?>">Ver vencimientos</a>
    </div>
<?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Recepción</th><th>Proveedor</th><th>Productos</th><th>Recibió</th><th></th></tr></thead>
            <tbody>
            <?php if (!$recepciones) { ?>
                <tr><td colspan="5" class="text-center">Todavía no hay recepciones<?= $esAdminVista ? '' : ' hechas por vos' ?>. Tocá «Nueva recepción» cuando llegue mercadería.</td></tr>
            <?php } ?>
            <?php foreach ($recepciones as $r) { ?>
                <tr>
                    <td><a href="<?= e(url('recepcion', 'ver', ['id' => $r['id_recepcion']])) ?>"><strong>#<?= (int)$r['id_recepcion'] ?></strong></a>
                        <small class="d-block text-secondary"><?= e(fecha($r['fecha'])) ?></small></td>
                    <td><?= $r['proveedor'] ? e($r['proveedor']) : '<span class="text-secondary">Sin proveedor</span>' ?>
                        <small class="d-block text-secondary"><?= $r['id_orden'] ? 'Orden #' . (int)$r['id_orden'] : 'Sin orden de compra' ?><?= $r['remito'] ? ' · remito ' . e($r['remito']) : '' ?></small></td>
                    <td><?= (int)$r['productos'] ?> · <?= (int)$r['unidades'] ?> u.</td>
                    <td><?= e($r['persona']) ?></td>
                    <td class="text-end"><a href="<?= e(url('recepcion', 'ver', ['id' => $r['id_recepcion']])) ?>" class="btn btn-sm btn-outline-secondary" aria-label="Ver la recepción #<?= (int)$r['id_recepcion'] ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
