<?php
/* VISTA - Gestión de operarios */
$titulo = 'Operarios';
$seccion = 'operarios';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">PERSONAL</span>
        <h1>Operarios</h1>
        <p>Gestioná los operarios que trabajan en el depósito.</p>
    </div>
    <a href="<?= e(url('operario', 'agregar')) ?>" class="btn btn-success"><i class="bi bi-person-plus"></i> Agregar operario</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$operarios) { ?>
                <tr><td colspan="4" class="text-center">No hay operarios registrados.</td></tr>
            <?php } ?>
            <?php foreach ($operarios as $o) { ?>
                <tr>
                    <td><strong><?= e($o['nombre']) ?></strong></td>
                    <td><?= e($o['usuario']) ?></td>
                    <td><?= strtolower($o['estado']) === 'activo' ? '<span class="estado activo">Activo</span>' : '<span class="estado">Inactivo</span>' ?></td>
                    <td class="text-nowrap">
                        <a href="<?= e(url('operario', 'perfil', ['id' => $o['id']])) ?>" class="btn btn-sm btn-info" title="Ver perfil" aria-label="Ver perfil de <?= e($o['nombre']) ?>"><i class="bi bi-person"></i></a>
                        <a href="<?= e(url('operario', 'editar', ['id' => $o['id']])) ?>" class="btn btn-sm btn-primary" title="Editar" aria-label="Editar a <?= e($o['nombre']) ?>"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= e(url('operario', 'eliminar')) ?>" class="d-inline" onsubmit="return confirm('¿Seguro que querés eliminar este operario?');">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= e($o['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" aria-label="Eliminar a <?= e($o['nombre']) ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
