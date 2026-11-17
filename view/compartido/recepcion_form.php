<?php
/* VISTA - Nueva recepción de mercadería (js/recepcion.js)
   Se escanea o se busca cada producto; por cada uno se pone la cantidad,
   el lote y el vencimiento. Si viene por una orden de compra, se cargan
   solos los productos que faltan recibir. */
$titulo = 'Nueva recepción';
$seccion = 'recepcion';
$scripts = ['js/vendor/html5-qrcode.min.js', 'js/escaner.js', 'js/recepcion.js'];
require RAIZ . '/view/layout/cabecera.php';
$esAdminVista = $sesion['rol'] === 'admin';
?>
<a href="<?= e(url('recepcion')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a recepciones</a>
<section class="encabezado">
    <span class="encabezado-etiqueta"><?= $esAdminVista ? 'ABASTECIMIENTO' : 'DEPÓSITO' ?></span>
    <h1>Nueva recepción</h1>
    <p>Escaneá cada producto que llegó y completá cuántos son, el lote y la fecha de vencimiento que trae el envase.</p>
</section>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<form method="POST" action="<?= e(url('recepcion', 'nueva')) ?>" id="formRecepcion" class="recepcion-contenedor" novalidate
      data-productos="<?= e(json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
      data-ordenes="<?= e(json_encode($ordenes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
      data-lineas="<?= e(json_encode($form['lineas'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
      data-hoy="<?= e(date('Y-m-d')) ?>"
      data-url-alta="<?= $esAdminVista ? e(url('producto', 'agregar')) : '' ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div class="tarjeta mb-3">
        <h2 class="tarjeta-titulo"><i class="bi bi-truck" aria-hidden="true"></i> Datos de la entrega</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="id_orden">Orden de compra</label>
                <select id="id_orden" name="id_orden" class="form-select" aria-describedby="ayudaOrden">
                    <option value="0">Sin orden de compra</option>
                    <?php foreach ($ordenes as $o) { ?>
                        <option value="<?= (int)$o['id'] ?>" data-proveedor="<?= (int)$o['id_proveedor'] ?>" <?= (int)$o['id'] === (int)$form['id_orden'] ? 'selected' : '' ?>>#<?= (int)$o['id'] ?> · <?= e($o['proveedor']) ?> · <?= e($o['fecha']) ?><?= $o['estado'] === 'Parcial' ? ' (llegó una parte)' : '' ?></option>
                    <?php } ?>
                </select>
                <div class="form-text" id="ayudaOrden">Si la mercadería viene por un pedido, elegilo y se cargan los productos que faltan.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="id_proveedor">Proveedor</label>
                <select id="id_proveedor" name="id_proveedor" class="form-select">
                    <option value="0">Sin especificar</option>
                    <?php foreach ($proveedores as $v) { ?>
                        <option value="<?= (int)$v['id_proveedor'] ?>" <?= (int)$v['id_proveedor'] === (int)$form['id_proveedor'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="remito">N.º de remito o factura</label>
                <input id="remito" name="remito" class="form-control" maxlength="30" autocomplete="off" value="<?= e($form['remito']) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label" for="observaciones">Observaciones</label>
                <input id="observaciones" name="observaciones" class="form-control" maxlength="255" value="<?= e($form['observaciones']) ?>" placeholder="Por ejemplo: llegó una caja golpeada">
            </div>
        </div>
    </div>

    <div class="tarjeta mb-3">
        <h2 class="tarjeta-titulo"><i class="bi bi-box-seam" aria-hidden="true"></i> Productos recibidos</h2>
        <div class="agregar-producto">
            <button type="button" class="btn btn-primary btn-lg" id="btnEscanearRecepcion"><i class="bi bi-upc-scan" aria-hidden="true"></i> Escanear producto</button>
            <div class="selector-producto flex-grow-1">
                <label class="visually-hidden" for="buscarRecepcion">Buscar producto por nombre o código</label>
                <div class="buscador-texto">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="buscarRecepcion" class="form-control" placeholder="o buscalo por nombre o código" autocomplete="off"
                           role="combobox" aria-expanded="false" aria-controls="resultadosRecepcion" aria-autocomplete="list">
                </div>
                <ul id="resultadosRecepcion" class="selector-lista" role="listbox" hidden></ul>
            </div>
        </div>
        <div id="avisoRecepcion" class="small mt-2" role="status" aria-live="polite"></div>

        <div id="lineasRecepcion" class="lineas-recepcion"></div>
        <p id="sinLineas" class="vacio-chico">Todavía no agregaste productos.</p>
    </div>

    <div class="barra-accion">
        <span id="resumenRecepcion" aria-live="polite"></span>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check2-circle"></i> Guardar recepción</button>
    </div>
</form>

<!-- Molde de cada producto recibido (lo copia js/recepcion.js) -->
<template id="moldeLinea">
    <article class="linea-recepcion">
        <div class="linea-cabecera">
            <span class="foto-producto"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
            <div class="linea-nombre">
                <strong></strong>
                <small class="text-secondary"></small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar" aria-label="Quitar"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" data-campo="id_producto">
        <div class="row g-2">
            <div class="col-4 col-md-3">
                <label class="form-label small">Cantidad</label>
                <input type="number" class="form-control" data-campo="cantidad" min="1" max="100000" inputmode="numeric" required>
            </div>
            <div class="col-8 col-md-4">
                <label class="form-label small">Lote <span class="text-secondary fw-normal">(opcional)</span></label>
                <input class="form-control codigo" data-campo="lote" maxlength="30" autocomplete="off" placeholder="L2345">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label small">Vence <span class="text-secondary fw-normal etiqueta-vence">(opcional)</span></label>
                <input type="date" class="form-control" data-campo="vencimiento">
            </div>
        </div>
        <div class="linea-mensaje small text-danger" aria-live="polite"></div>
    </article>
</template>
<?php require RAIZ . '/view/compartido/escaner.php'; ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
