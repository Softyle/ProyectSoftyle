<?php
/* VISTA - Proveedores */
$titulo = 'Proveedores';
$seccion = 'proveedores';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
        <h1>Proveedores</h1>
        <p>A quién se le compra cada producto y cuánto tarda en llegar un pedido.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('compra', 'reposicion')) ?>" class="btn btn-outline-primary"><i class="bi bi-cart-plus"></i> Reposición</a>
        <a href="<?= e(url('proveedor', 'agregar')) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Agregar proveedor</a>
    </div>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Proveedor</th><th>Contacto</th><th>Entrega</th><th>Productos</th><th>Órdenes abiertas</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$proveedores) { ?>
                <tr><td colspan="6" class="text-center">Todavía no hay proveedores. Agregá el primero.</td></tr>
            <?php } ?>
            <?php foreach ($proveedores as $v) { ?>
                <tr>
                    <td>
                        <strong><?= e($v['nombre']) ?></strong>
                        <?php if (!$v['activo']) { ?><span class="estado ms-1">Inactivo</span><?php } ?>
                        <?php if ($v['rut']) { ?><small class="d-block text-secondary">RUT <?= e($v['rut']) ?></small><?php } ?>
                    </td>
                    <td class="small">
                        <?php if (!$v['contacto'] && !$v['telefono'] && !$v['email']) { ?><span class="text-secondary">Sin datos de contacto</span><?php } ?>
                        <?php if ($v['contacto']) { ?><span class="d-block"><?= e($v['contacto']) ?></span><?php } ?>
                        <?php if ($v['telefono']) { ?><a class="d-block" href="tel:<?= e(preg_replace('/[^\d+]/', '', $v['telefono'])) ?>"><i class="bi bi-telephone" aria-hidden="true"></i> <?= e($v['telefono']) ?></a><?php } ?>
                        <?php if ($v['email']) { ?><a class="d-block" href="mailto:<?= e($v['email']) ?>"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e($v['email']) ?></a><?php } ?>
                    </td>
                    <td class="text-nowrap"><?= (int)$v['dias_entrega'] === 0 ? 'En el día' : (int)$v['dias_entrega'] . ' día' . ((int)$v['dias_entrega'] === 1 ? '' : 's') ?></td>
                    <td><?= (int)$v['productos'] ?></td>
                    <td><?php if ((int)$v['ordenes_abiertas'] > 0) { ?><a href="<?= e(url('compra', 'index', ['estado' => 'Abiertas'])) ?>"><?= (int)$v['ordenes_abiertas'] ?></a><?php } else { ?>0<?php } ?></td>
                    <td class="text-nowrap">
                        <a href="<?= e(url('proveedor', 'editar', ['id' => $v['id_proveedor']])) ?>" class="btn btn-sm btn-primary" title="Editar" aria-label="Editar <?= e($v['nombre']) ?>"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= e(url('proveedor', 'eliminar')) ?>" class="d-inline" onsubmit="return confirm('¿Seguro que querés eliminar este proveedor? Sus productos quedan sin proveedor.');">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int)$v['id_proveedor'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" aria-label="Eliminar <?= e($v['nombre']) ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<p class="nota">El proveedor de cada producto se elige al agregarlo o editarlo en <a href="<?= e(url('producto')) ?>">Productos</a>.</p>
<?php require RAIZ . '/view/layout/pie.php'; ?>
