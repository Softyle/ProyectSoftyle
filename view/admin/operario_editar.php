<?php
/* VISTA - Modificación de operario */
$titulo = 'Editar operario';
$seccion = 'operarios';
$esActivo = strtolower($operario['estado']) === 'activo';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('operario')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a operarios</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">PERSONAL</span>
    <h1>Editar operario</h1>
    <p>Modificá los datos del operario.</p>
</section>

<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" action="<?= e(url('operario', 'editar', ['id' => $operario['id']])) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="mb-3"><label class="form-label" for="usuario">Usuario / nombre</label><input type="text" id="usuario" name="usuario" class="form-control" value="<?= e($operario['usuario']) ?>" required><small class="text-muted">Es el nombre con el que el operario entra al sistema y el que se muestra en la aplicación.</small></div>
        <div class="mb-3">
            <label class="form-label" for="password">Nueva contraseña <small>(opcional)</small></label>
            <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
            <small class="text-muted">Dejá vacío para conservar la actual.</small>
        </div>
        <div class="mb-4">
            <label class="form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="Activo" <?= $esActivo ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= $esActivo ? '' : 'selected' ?>>Inactivo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Guardar cambios</button>
    </form>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
