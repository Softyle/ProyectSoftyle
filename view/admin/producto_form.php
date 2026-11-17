<?php
/* VISTA - Formulario de producto (agregar y editar) */
$seccion = 'productos';
$scripts = ['js/vendor/html5-qrcode.min.js', 'js/escaner.js', 'js/producto_form.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('producto')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a productos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">INVENTARIO</span>
    <h1><?= e($titulo) ?></h1>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta tarjeta-formulario">
    <form method="POST" enctype="multipart/form-data" action="<?= e($esNuevo ? url('producto', 'agregar') : url('producto', 'editar', ['id' => $id])) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <!-- El código va primero: al leerlo se buscan los datos del producto -->
        <div class="mb-3">
            <label class="form-label" for="codigo_barras">Código de barras</label>
            <div class="input-group">
                <input id="codigo_barras" name="codigo_barras" class="form-control" inputmode="numeric" autocomplete="off"
                       value="<?= e($producto['codigo_barras']) ?>" placeholder="Opcional: el número que está debajo de las barras" aria-describedby="ayudaCodigo"
                       data-consulta="<?= e(url('producto', 'consultarCodigo', $esNuevo ? [] : ['id' => $id])) ?>">
                <button type="button" class="btn btn-outline-primary" id="btnLeerCodigo"><i class="bi bi-upc-scan" aria-hidden="true"></i> Leer del paquete</button>
            </div>
            <div class="form-text" id="ayudaCodigo">
                <?= $esNuevo ? 'Escaneá el envase y completamos el nombre si encontramos el producto. ' : '' ?>Si lo dejás vacío, el sistema le asigna un código interno (empieza con 20) que podés imprimir desde <a href="<?= e(url('producto', 'etiquetas')) ?>">Etiquetas</a>.
            </div>
            <div id="infoCodigo" class="info-codigo mt-2" role="status" aria-live="polite"></div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="nombre">Nombre</label>
            <input id="nombre" name="nombre" class="form-control" value="<?= e($producto['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="descripcion">Descripción</label>
            <input id="descripcion" name="descripcion" class="form-control" value="<?= e($producto['descripcion']) ?>">
        </div>
        <?php
        $fotoActual = (string)($producto['foto'] ?? '');
        $srcFoto = fotoProducto($fotoActual);
        $esEnlace = strncasecmp($fotoActual, 'https://', 8) === 0;
        ?>
        <div class="mb-3">
            <span class="form-label d-block">Foto</span>
            <div class="foto-editor">
                <span class="foto-producto foto-grande foto-ampliable" id="vistaFoto" role="button" tabindex="0" data-nombre="<?= e($producto['nombre'] ?: 'Foto del producto') ?>" aria-label="Ver la foto en grande"><i class="bi bi-box-seam" aria-hidden="true"></i><?php if ($srcFoto) { ?><img src="<?= e($srcFoto) ?>" alt="Foto de <?= e($producto['nombre']) ?>" referrerpolicy="no-referrer" onerror="this.remove()"><?php } ?></span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSacarFoto"><i class="bi bi-camera" aria-hidden="true"></i> Sacar foto</button>
                        <label class="btn btn-outline-secondary btn-sm mb-0" for="foto_archivo"><i class="bi bi-upload" aria-hidden="true"></i> Subir foto</label>
                    </div>
                    <input type="file" id="foto_archivo" name="foto_archivo" accept="image/*" class="visually-hidden">
                    <!-- Respaldo de "Sacar foto" cuando la cámara en vivo no está disponible: abre la cámara del celular -->
                    <input type="file" id="foto_camara" accept="image/*" capture="environment" class="visually-hidden" tabindex="-1" aria-hidden="true">
                    <input id="foto_url" name="foto_url" class="form-control form-control-sm mt-2" inputmode="url" autocomplete="off"
                           value="<?= e($esEnlace ? $fotoActual : '') ?>" placeholder="o pegá el enlace de la foto (https://...)" aria-label="Enlace de la foto">
                    <?php if (!$esNuevo && $srcFoto) { ?>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="ocultar_foto" name="ocultar_foto" value="1" <?= !empty($producto['foto_oculta']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ocultar_foto">Ocultar la foto <small class="text-secondary">(no se muestra en la lista, el mapa ni el picking; queda guardada y la volvés a mostrar destildando)</small></label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="borrar_foto" name="borrar_foto" value="1">
                            <label class="form-check-label text-danger" for="borrar_foto">Borrar la foto <small>(se elimina del producto)</small></label>
                        </div>
                    <?php } ?>
                    <div class="form-text">"Sacar foto" usa la cámara de la compu o del celular; la foto se achica sola antes de subirla.</div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label" for="stock">Stock actual</label>
                <input type="number" id="stock" name="stock" min="0" class="form-control" value="<?= (int)$producto['stock'] ?>" required<?= $esNuevo ? '' : ' aria-describedby="ayudaStock"' ?>>
                <?php if (!$esNuevo) { ?>
                    <div class="form-text" id="ayudaStock">Para mercadería que llega usá <a href="<?= e(url('recepcion', 'nueva')) ?>">Recepción</a> (guarda el lote y el vencimiento). Un cambio acá queda en el historial como ajuste manual.</div>
                <?php } ?>
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
        <div class="mb-3">
            <label class="form-label" for="id_proveedor">Proveedor habitual</label>
            <select id="id_proveedor" name="id_proveedor" class="form-select" aria-describedby="ayudaProveedor">
                <option value="0">Sin proveedor asignado</option>
                <?php foreach ($proveedores as $v) { ?>
                    <option value="<?= (int)$v['id_proveedor'] ?>" <?= (int)$v['id_proveedor'] === (int)($producto['id_proveedor'] ?? 0) ? 'selected' : '' ?>><?= e($v['nombre']) ?></option>
                <?php } ?>
            </select>
            <div class="form-text" id="ayudaProveedor">A quién se le pide cuando hay que reponerlo. Los proveedores se cargan en <a href="<?= e(url('proveedor')) ?>">Proveedores</a>.</div>
        </div>
        <div class="mb-4">
            <label class="form-label" for="id_ubicacion">Ubicación</label>
            <?php if (!$ubicaciones && $proximaZona) { ?>
                <!-- No queda ningún lugar libre: al guardar se crea una zona nueva -->
                <select id="id_ubicacion" name="id_ubicacion" class="form-select" required aria-describedby="ayudaUbicacion">
                    <option value="nueva" selected><?= e($proximaZona['nombre'] . ' (nueva) / ' . $proximaZona['pasillo'] . '-E01-N01') ?></option>
                </select>
                <div class="alert alert-info small mt-2 mb-0" id="ayudaUbicacion">
                    <i class="bi bi-info-circle" aria-hidden="true"></i> Todas las ubicaciones están ocupadas. Al guardar se crea la
                    <strong><?= e($proximaZona['nombre']) ?></strong> con el pasillo <?= e($proximaZona['pasillo']) ?> y 15 ubicaciones libres
                    (del mismo tipo que la zona de la categoría elegida), y el producto queda en la primera.
                </div>
            <?php } elseif (!$ubicaciones) { ?>
                <select id="id_ubicacion" name="id_ubicacion" class="form-select" required disabled><option value="">Sin lugar</option></select>
                <div class="alert alert-warning small mt-2 mb-0">No quedan ubicaciones libres ni letras para crear otra zona. Liberá una ubicación desde <a href="<?= e(url('logistica', 'index', ['tipo' => 'ubicaciones'])) ?>">Ubicaciones</a>.</div>
            <?php } else { ?>
                <select id="id_ubicacion" name="id_ubicacion" class="form-select" required aria-describedby="ayudaUbicacion"
                        data-zonas-categoria="<?= e(json_encode(array_map(fn($zs) => array_column($zs, 'id_zona'), $zonasCategoria))) ?>"
                        data-elegir="<?= $esNuevo && (int)$producto['id_ubicacion'] === 0 ? '1' : '0' ?>">
                    <?php foreach ($ubicaciones as $u) { ?>
                        <option value="<?= (int)$u['id_ubicacion'] ?>" data-zona="<?= (int)$u['id_zona'] ?>" <?= (int)$u['id_ubicacion'] === (int)$producto['id_ubicacion'] ? 'selected' : '' ?>><?= e($u['zona'] . ' / ' . $u['pasillo'] . '-' . $u['estante'] . '-' . $u['posicion'] . (Ubicacion::esSectorPiso($u['estante']) ? ' (sector de piso)' : '')) ?></option>
                    <?php } ?>
                </select>
                <div class="form-text" id="ayudaUbicacion">Solo aparecen las ubicaciones libres<?= $esNuevo ? '. Al elegir la categoría se propone una en la zona donde ya está guardada; podés cambiarla' : ' y la que el producto ya tiene' ?>.</div>
            <?php } ?>
        </div>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> <?= $esNuevo ? 'Guardar producto' : 'Guardar cambios' ?></button>
    </form>
</div>
<?php require RAIZ . '/view/compartido/escaner.php'; ?>

<!-- Cámara en vivo para sacarle la foto al producto (js/producto_form.js) -->
<div class="modal fade" id="modalCamaraFoto" tabindex="-1" aria-labelledby="tituloCamaraFoto" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="tituloCamaraFoto"><i class="bi bi-camera" aria-hidden="true"></i> Sacar foto del producto</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <video id="videoCamaraFoto" class="camara-foto" playsinline muted aria-label="Vista de la cámara"></video>
                <p class="small text-secondary mt-2 mb-0" id="estadoCamaraFoto" role="status" aria-live="polite"></p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="btnCambiarCamara"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Cambiar cámara</button>
                <button type="button" class="btn btn-primary" id="btnDispararFoto" disabled><i class="bi bi-camera-fill" aria-hidden="true"></i> Sacar foto</button>
            </div>
        </div>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
