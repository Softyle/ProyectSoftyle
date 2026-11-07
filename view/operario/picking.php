<?php
/* VISTA - Hoja de ruta / picking asistido.
   Cada retiro se confirma por AJAX (js/picking.js) sin recargar la página. */
$titulo = 'Picking #' . $idPedido;
$seccion = 'mis-pedidos';
$scripts = ['js/picking.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<div class="picking-contenedor" id="hojaRuta"
     data-pedido="<?= $idPedido ?>" data-total="<?= $totalItems ?>" data-csrf="<?= e($csrf) ?>"
     data-url-confirmar="<?= e(url('picking', 'confirmar')) ?>" data-url-finalizar="<?= e(url('picking', 'finalizar')) ?>"
     data-url-estado="<?= e(url('picking', 'estado')) ?>" data-url-panel="<?= e(url('picking')) ?>">

    <a href="<?= e(url('picking', 'pedido', ['id' => $idPedido])) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver al pedido</a>
    <section class="encabezado">
        <span class="encabezado-etiqueta">HOJA DE RUTA</span>
        <h1>Pedido #<?= $idPedido ?></h1>
        <p>Cliente: <strong><?= e($cliente) ?></strong> · <?= $totalUnidades ?> unidades en total</p>
    </section>

    <!-- Mensajes de las acciones (se completan por AJAX) -->
    <div id="aviso" class="alert d-none" role="status" aria-live="polite"></div>

    <section class="progreso-card">
        <div class="progreso-top"><span>Productos recogidos</span><strong><span id="cantRecogidos"><?= $recogidos ?></span> de <?= $totalItems ?></strong></div>
        <div class="progress" role="progressbar" aria-label="Progreso del picking" aria-valuemin="0" aria-valuemax="<?= $totalItems ?>" aria-valuenow="<?= $recogidos ?>">
            <div class="progress-bar" id="barraProgreso" style="width: <?= round($recogidos * 100 / $totalItems) ?>%"></div>
        </div>
    </section>

    <section class="indicaciones-card">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div>
            <strong>Cómo confirmar cada retiro</strong>
            <p>Andá a la ubicación indicada, escribí el código de la estantería (por ejemplo <em>P01-E01-N01</em>) y la cantidad que retiraste. El sistema te avisa al instante si la estantería o la cantidad no coinciden. La zona refrigerada siempre queda para el final. Los productos voluminosos están en <strong>sectores de piso</strong> (estante E00).</p>
        </div>
    </section>

    <?php foreach ($items as $i => $item) {
        $idProducto = (int)$item['id_producto'];
        $refrigerado = strcasecmp($item['tipo_zona'], 'Refrigerada') === 0;
    ?>
        <article class="producto-picking <?= $item['recogido'] ? 'recogido' : '' ?> <?= $refrigerado ? 'refrigerado' : '' ?>" id="item-<?= $idProducto ?>" data-producto="<?= $idProducto ?>">
            <div class="d-flex justify-content-between align-items-center">
                <span class="producto-numero">PASO <?= $i + 1 ?> DE <?= $totalItems ?></span>
                <span class="badge bg-success badge-recogido"><i class="bi bi-check-circle-fill"></i> Recogido</span>
            </div>
            <h2><?= e($item['producto']) ?></h2>

            <div class="ubicacion-box">
                <i class="bi <?= $refrigerado ? 'bi-snow' : (Ubicacion::esSectorPiso($item['estante']) ? 'bi-bounding-box' : 'bi-geo-alt-fill') ?>" aria-hidden="true"></i>
                <div>
                    <span><?= e(mb_strtoupper($item['zona'] . ' · ' . $item['tipo_zona'])) ?></span>
                    <strong><?= e($item['pasillo'] . '-' . $item['estante'] . '-' . $item['posicion']) ?></strong>
                    <span><?= e(mb_strtoupper(Ubicacion::detalle($item['pasillo'], $item['estante'], $item['posicion']))) ?></span>
                </div>
            </div>

            <div class="cantidad-box">
                <div><span>Cantidad a retirar</span><strong><?= (int)$item['Cantidad'] ?> unidades</strong></div>
                <div class="text-end"><span>Stock en sistema</span>
                    <strong class="stock-actual <?= (int)$item['stock_actual'] <= (int)$item['stock_minimo'] ? 'text-danger' : '' ?>"><?= (int)$item['stock_actual'] ?></strong></div>
            </div>

            <?php if (!empty($documentos[$idProducto])) { ?>
                <p class="small mb-3"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Documentación:
                    <?php foreach ($documentos[$idProducto] as $d) { ?>
                        <a href="<?= e(url('documento', 'ver', ['id' => $d['id_documento']])) ?>" target="_blank" rel="noopener"><?= e($d['tipo_documento']) ?></a>
                    <?php } ?>
                </p>
            <?php } ?>

            <form class="form-retiro" novalidate>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label small fw-semibold" for="ubic-<?= $idProducto ?>">Código de estantería</label>
                        <input class="form-control inp-ubicacion" id="ubic-<?= $idProducto ?>" name="ubicacion" autocomplete="off" autocapitalize="characters" placeholder="P01-E01-N01" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-semibold" for="cant-<?= $idProducto ?>">Cantidad retirada</label>
                        <input class="form-control inp-cantidad" id="cant-<?= $idProducto ?>" name="cantidad" type="number" inputmode="numeric" min="1" required>
                    </div>
                </div>
                <div class="invalid-feedback d-block mensaje-item mb-2" aria-live="polite"></div>
                <button type="submit" class="btn-confirmar-picking"><i class="bi bi-check-circle"></i> Confirmar retiro</button>
            </form>
        </article>
    <?php } ?>

    <button type="button" id="btnFinalizar" class="btn-confirmar-picking btn-finalizar mt-2" <?= $recogidos < $totalItems ? 'disabled' : '' ?>>
        <i class="bi bi-send-check"></i> Terminar picking y enviar a validación
    </button>
    <p class="text-secondary small mt-2" id="ayudaFinalizar"><?= $recogidos < $totalItems ? 'Se habilita cuando confirmes todos los productos.' : 'Todos los productos están recogidos.' ?></p>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
