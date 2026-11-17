<?php
/* VISTA - Nuevo conteo de inventario */
$titulo = 'Nuevo conteo';
$seccion = 'conteo';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('conteo')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a conteos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">ABASTECIMIENTO</span>
    <h1>Nuevo conteo de inventario</h1>
    <p>Se guarda el stock que dice el sistema en este momento y se cuenta lo que hay de verdad en cada lugar de la zona.</p>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" action="<?= e(url('conteo', 'nuevo')) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <fieldset class="mb-3">
            <legend class="form-label">Zona a contar</legend>
            <div class="opciones-zona">
                <?php foreach ($zonas as $z) { $ocupada = (int)$z['en_curso'] > 0; ?>
                    <label class="opcion-zona<?= $ocupada ? ' opcion-deshabilitada' : '' ?>">
                        <input type="radio" class="form-check-input" name="id_zona" value="<?= (int)$z['id_zona'] ?>" <?= (int)$z['id_zona'] === (int)$form['id_zona'] ? 'checked' : '' ?> <?= $ocupada ? 'disabled' : '' ?> required>
                        <span>
                            <strong><?= e($z['nombre']) ?></strong> <small class="text-secondary"><?= e($z['tipo']) ?> · <?= (int)$z['productos'] ?> productos</small>
                            <small class="d-block text-secondary"><?= $ocupada ? 'Ya tiene un conteo en curso' : ($z['ultimo_conteo'] ? 'Último conteo: ' . e(fecha($z['ultimo_conteo'], 'd/m/Y')) : 'Nunca se contó') ?></small>
                        </span>
                    </label>
                <?php } ?>
            </div>
        </fieldset>
        <div class="mb-3">
            <label class="form-label" for="ci_asignado">¿Quién cuenta?</label>
            <select id="ci_asignado" name="ci_asignado" class="form-select">
                <option value="">Lo cuento yo</option>
                <?php foreach ($operarios as $o) { ?>
                    <option value="<?= e($o['CI']) ?>" <?= $o['CI'] === $form['ci_asignado'] ? 'selected' : '' ?>><?= e($o['usuario']) ?></option>
                <?php } ?>
            </select>
            <div class="form-text">El operario lo ve en su menú, en «Conteos de inventario».</div>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="ciego" name="ciego" value="1" <?= $form['ciego'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="ciego">Conteo ciego <small class="text-secondary">(el operario no ve cuántos dice el sistema: cuenta sin dejarse llevar por el número)</small></label>
        </div>
        <div class="mb-4">
            <label class="form-label" for="observaciones">Observaciones <small class="text-secondary fw-normal">(opcional)</small></label>
            <input id="observaciones" name="observaciones" class="form-control" maxlength="255" value="<?= e($form['observaciones']) ?>" placeholder="Por ejemplo: conteo mensual">
        </div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-play-circle"></i> Empezar el conteo</button>
    </form>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
