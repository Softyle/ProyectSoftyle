<?php
/* VISTA - Alta de operario */
$titulo = 'Agregar operario';
$seccion = 'operarios';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('operario')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a operarios</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">PERSONAL</span>
    <h1>Agregar operario</h1>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" action="<?= e(url('operario', 'agregar')) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="mb-3"><label class="form-label" for="ci">CI (8 dígitos)</label><input id="ci" name="ci" maxlength="8" inputmode="numeric" class="form-control" value="<?= e($form['ci']) ?>" required></div>
        <div class="mb-3"><label class="form-label" for="usuario">Usuario / nombre</label><input id="usuario" name="usuario" class="form-control" value="<?= e($form['usuario']) ?>" required></div>
        <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required></div>
        <div class="mb-3"><label class="form-label" for="password">Contraseña</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
        <div class="mb-4"><label class="form-label" for="hora_ingreso">Hora de ingreso</label><input id="hora_ingreso" type="time" name="hora_ingreso" value="<?= e($form['hora_ingreso']) ?>" class="form-control" required></div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-person-plus"></i> Crear operario</button>
    </form>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
