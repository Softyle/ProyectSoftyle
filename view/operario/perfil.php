<?php
/* VISTA - Perfil del operario: datos de contacto, foto y contraseña */
$titulo = 'Mi perfil';
$seccion = 'perfil';
require RAIZ . '/view/layout/cabecera.php';
?>
<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<section class="perfil-cabecera">
    <div class="perfil-foto">
        <?php if ($foto) { ?>
            <img src="<?= e($foto) ?>" alt="Foto de perfil de <?= e($operario['usuario']) ?>">
        <?php } else { ?>
            <i class="bi bi-person" aria-hidden="true"></i>
        <?php } ?>
    </div>
    <div class="perfil-info">
        <span class="encabezado-etiqueta">MI PERFIL</span>
        <h1><?= e($operario['usuario']) ?></h1>
        <p>CI: <strong><?= e($operario['CI']) ?></strong> · Ingreso: <strong><?= e(substr((string)$operario['hora_ingreso'], 0, 5)) ?></strong></p>
        <span class="estado <?= strtolower($operario['Estado']) === 'activo' ? 'activo' : '' ?>"><?= e($operario['Estado']) ?></span>
    </div>
</section>

<section class="indicadores indicadores-3">
    <div class="indicador indicador-verde"><i class="bi bi-check-circle"></i><div><span>Pedidos completados</span><strong><?= (int)$resumen['completados'] ?></strong></div></div>
    <div class="indicador"><i class="bi bi-box-seam"></i><div><span>Pedidos en curso</span><strong><?= (int)$resumen['activos'] ?></strong></div></div>
    <div class="indicador"><i class="bi bi-hourglass-split"></i><div><span>Esperando validación</span><strong><?= (int)$resumen['por_validar'] ?></strong></div></div>
</section>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="tarjeta h-100">
            <h2 class="tarjeta-titulo">Datos de contacto</h2>
            <form method="POST" action="<?= e(url('perfil')) ?>" novalidate>
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="datos">
                <div class="mb-3">
                    <label class="form-label" for="usuario">Usuario</label>
                    <input class="form-control" id="usuario" value="<?= e($operario['usuario']) ?>" disabled>
                    <small class="text-muted">Tu usuario solo lo puede cambiar el administrador, desde Personal → Operarios.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="100" value="<?= e($operario['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="form-control" type="tel" id="telefono" name="telefono" maxlength="20" value="<?= e($operario['telefono']) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="direccion">Dirección</label>
                    <input class="form-control" id="direccion" name="direccion" maxlength="150" value="<?= e($operario['direccion']) ?>">
                </div>
                <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Guardar datos</button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="tarjeta mb-4">
            <h2 class="tarjeta-titulo">Foto de perfil</h2>
            <form method="POST" action="<?= e(url('perfil')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="foto">
                <div class="mb-3">
                    <label class="form-label" for="foto">Imagen (JPG, PNG o WEBP, hasta 2 MB)</label>
                    <input class="form-control" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Subir foto</button>
            </form>
        </div>

        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Cambiar contraseña</h2>
            <form method="POST" action="<?= e(url('perfil')) ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="password">
                <div class="mb-3">
                    <label class="form-label" for="password_actual">Contraseña actual</label>
                    <input class="form-control" type="password" id="password_actual" name="password_actual" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_nueva">Nueva contraseña (mínimo 6 caracteres)</label>
                    <input class="form-control" type="password" id="password_nueva" name="password_nueva" minlength="6" autocomplete="new-password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_repetir">Repetir nueva contraseña</label>
                    <input class="form-control" type="password" id="password_repetir" name="password_repetir" minlength="6" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Cambiar contraseña</button>
            </form>
        </div>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
