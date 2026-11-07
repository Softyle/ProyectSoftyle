<?php
/* VISTA - Documentación técnica de un producto (subida de archivos) */
$titulo = 'Documentación de ' . $producto['Nombre'];
$seccion = 'productos';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('producto')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a productos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">DOCUMENTACIÓN TÉCNICA</span>
    <h1><?= e($producto['Nombre']) ?></h1>
    <p>Hojas de seguridad y manuales de manipulación de carga. El operario los ve en su hoja de ruta.</p>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta mb-4">
    <h2 class="tarjeta-titulo">Subir documento</h2>
    <form method="POST" action="<?= e(url('producto', 'documentos', ['id' => $producto['id_producto']])) ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="accion" value="subir">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxBytes ?>">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="tipo_documento">Tipo de documento</label>
                <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                    <option value="">Seleccionar...</option>
                    <?php foreach ($tiposDocumento as $t) { ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php } ?>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="archivo">Archivo (PDF, JPG, PNG o WEBP, hasta 2 MB)</label>
                <input class="form-control" type="file" id="archivo" name="archivo" accept=".pdf,image/jpeg,image/png,image/webp" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success mt-3"><i class="bi bi-upload"></i> Subir documento</button>
    </form>
</div>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Tipo</th><th>Archivo</th><th>Tamaño</th><th>Subido</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($documentos as $d) { ?>
                <tr>
                    <td><?= e($d['tipo_documento']) ?></td>
                    <td><i class="bi <?= $d['mime'] === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?>" aria-hidden="true"></i> <?= e($d['nombre_original']) ?></td>
                    <td><?= e(number_format($d['tamano_bytes'] / 1024, 0, ',', '.')) ?> KB</td>
                    <td class="text-nowrap"><?= e(fecha($d['fecha_subida'])) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-primary" href="<?= e(url('documento', 'ver', ['id' => $d['id_documento']])) ?>" target="_blank" rel="noopener" title="Ver" aria-label="Ver documento"><i class="bi bi-eye"></i></a>
                        <form method="POST" action="<?= e(url('producto', 'documentos', ['id' => $producto['id_producto']])) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este documento?');">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_documento" value="<?= (int)$d['id_documento'] ?>">
                            <button class="btn btn-sm btn-danger" type="submit" title="Eliminar" aria-label="Eliminar documento"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } if (!$documentos) { ?>
                <tr><td colspan="5" class="text-center">Este producto todavía no tiene documentación.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
