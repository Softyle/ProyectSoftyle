<?php
/* VISTA - Formulario de proveedor (agregar y editar) */
$seccion = 'proveedores';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('proveedor')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a proveedores</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
    <h1><?= e($titulo) ?></h1>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" action="<?= e($id ? url('proveedor', 'editar', ['id' => $id]) : url('proveedor', 'agregar')) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="row g-3 mb-3">
            <div class="col-sm-8">
                <label class="form-label" for="nombre">Nombre</label>
                <input id="nombre" name="nombre" class="form-control" maxlength="100" value="<?= e($form['nombre']) ?>" required>
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="rut">RUT <small class="text-secondary fw-normal">(opcional)</small></label>
                <input id="rut" name="rut" class="form-control" inputmode="numeric" maxlength="15" value="<?= e($form['rut'] ?? '') ?>" placeholder="12 números">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-4">
                <label class="form-label" for="contacto">Persona de contacto</label>
                <input id="contacto" name="contacto" class="form-control" maxlength="100" value="<?= e($form['contacto'] ?? '') ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="telefono">Teléfono</label>
                <input id="telefono" name="telefono" type="tel" class="form-control" maxlength="20" value="<?= e($form['telefono'] ?? '') ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="email">Correo</label>
                <input id="email" name="email" type="email" class="form-control" maxlength="100" value="<?= e($form['email'] ?? '') ?>">
            </div>
        </div>
        <div class="row g-3 mb-4 align-items-end">
            <div class="col-sm-4">
                <label class="form-label" for="dias_entrega">Días de entrega</label>
                <input id="dias_entrega" name="dias_entrega" type="number" min="0" max="90" class="form-control" value="<?= e((string)$form['dias_entrega']) ?>" required aria-describedby="ayudaDias">
            </div>
            <div class="col-sm-8">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= !empty($form['activo']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo <small class="text-secondary">(los inactivos no aparecen para pedir)</small></label>
                </div>
            </div>
            <div class="col-12"><div class="form-text mt-0" id="ayudaDias">Cuánto tarda en llegar un pedido desde que se lo hacés. Con esto la reposición calcula cuándo y cuánto pedir.</div></div>
        </div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Guardar</button>
    </form>
</div>

<?php if ($productos) { ?>
    <div class="tarjeta tarjeta-formulario mt-3">
        <h2 class="tarjeta-titulo"><i class="bi bi-box-seam" aria-hidden="true"></i> Productos de este proveedor (<?= count($productos) ?>)</h2>
        <ul class="lista-simple">
            <?php foreach ($productos as $p) { ?>
                <li><a href="<?= e(url('producto', 'editar', ['id' => $p['id_producto']])) ?>"><?= e($p['Nombre']) ?></a>
                    <small class="<?= (int)$p['stock_actual'] <= (int)$p['stock_minimo'] ? 'text-danger fw-semibold' : 'text-secondary' ?>">stock <?= (int)$p['stock_actual'] ?> · mín. <?= (int)$p['stock_minimo'] ?></small></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
