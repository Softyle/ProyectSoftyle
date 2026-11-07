<?php
/* VISTA - Formulario de producto (agregar y editar) */
$seccion = 'productos';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('producto')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a productos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">INVENTARIO</span>
    <h1><?= e($titulo) ?></h1>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" action="<?= e($esNuevo ? url('producto', 'agregar') : url('producto', 'editar', ['id' => $id])) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="mb-3">
            <label class="form-label" for="nombre">Nombre</label>
            <input id="nombre" name="nombre" class="form-control" value="<?= e($producto['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="descripcion">Descripción</label>
            <input id="descripcion" name="descripcion" class="form-control" value="<?= e($producto['descripcion']) ?>">
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label" for="stock">Stock actual</label>
                <input type="number" id="stock" name="stock" min="0" class="form-control" value="<?= (int)$producto['stock'] ?>" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="stock_minimo">Stock mínimo</label>
                <input type="number" id="stock_minimo" name="stock_minimo" min="0" class="form-control" value="<?= (int)$producto['stock_minimo'] ?>" required>
            </div>
        </div>
        <?php if (!$esNuevo) { ?>
            <div class="mb-3">
                <label class="form-label" for="estado_producto">Estado</label>
                <input id="estado_producto" name="estado_producto" class="form-control" value="<?= e($producto['estado_producto']) ?>">
            </div>
        <?php } ?>
        <div class="mb-3">
            <label class="form-label" for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" class="form-select" required>
                <?php foreach ($categorias as $c) { ?>
                    <option value="<?= (int)$c['id_categoria'] ?>" <?= (int)$c['id_categoria'] === (int)$producto['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label" for="id_ubicacion">Ubicación</label>
            <select id="id_ubicacion" name="id_ubicacion" class="form-select" required>
                <?php foreach ($ubicaciones as $u) { ?>
                    <option value="<?= (int)$u['id_ubicacion'] ?>" <?= (int)$u['id_ubicacion'] === (int)$producto['id_ubicacion'] ? 'selected' : '' ?>><?= e($u['zona'] . ' / ' . $u['pasillo'] . '-' . $u['estante'] . '-' . $u['posicion'] . (Ubicacion::esSectorPiso($u['estante']) ? ' (sector de piso)' : '')) ?></option>
                <?php } ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> <?= $esNuevo ? 'Guardar producto' : 'Guardar cambios' ?></button>
    </form>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
