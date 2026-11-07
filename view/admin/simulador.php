<?php
/* VISTA - Simulador de la API de pedidos: hace de "cliente externo" y envía
   una orden de salida en JSON (js/simulador.js) */
$titulo = 'API simulada de pedidos';
$seccion = 'simulador';
$scripts = ['js/simulador.js'];
$colores = ['aceptado' => 'bg-success', 'rechazado' => 'bg-danger', 'pendiente_ingreso' => 'bg-warning text-dark'];
// Categorías de los productos (opciones del filtro del buscador)
$categorias = array_unique(array_column($productos, 'categoria'));
sort($categorias);
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">INTEGRACIÓN LOGÍSTICA</span>
    <h1>API simulada de pedidos</h1>
</section>

<?php if (!$existeApi) { ?>
    <div class="alert alert-danger" role="alert">Falta la tabla <code>API_Recepciones</code>. Importá <code>softyleDEF.sql</code> en phpMyAdmin.</div>
<?php } ?>

<div class="row g-4 mb-4">
    <section class="col-xl-6">
        <div class="tarjeta h-100">
            <h2 class="tarjeta-titulo">Enviar orden de salida</h2>
            <form id="formApi" class="row g-3" data-url="<?= e(url('api', 'recibirPedido')) ?>" data-csrf="<?= e($csrf) ?>">
                <div class="col-md-6">
                    <label for="cliente" class="form-label">Nombre del cliente</label>
                    <input id="cliente" class="form-control" list="clientes" required maxlength="100" placeholder="Elegí o escribí un cliente">
                    <datalist id="clientes"><?php foreach ($clientes as $c) { ?><option value="<?= e($c['usuario']) ?>"></option><?php } ?></datalist>
                    <small class="text-secondary">Un nombre que no esté registrado queda como "pendiente de ingreso".</small>
                </div>
                <div class="col-md-6">
                    <label for="cantidad" class="form-label">Cantidad</label>
                    <input id="cantidad" class="form-control" type="number" min="1" max="1000000" value="1" required>
                </div>

                <!-- Producto: se busca por nombre o SKU y se puede filtrar por categoría (js/simulador.js) -->
                <div class="col-12">
                    <label for="buscarProductoApi" class="form-label">Producto / SKU</label>
                    <div class="selector-producto" id="selectorProducto">
                        <div class="selector-filtros">
                            <div class="buscador-texto">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" id="buscarProductoApi" class="form-control" placeholder="Buscar por nombre o SKU" autocomplete="off"
                                       role="combobox" aria-expanded="false" aria-controls="listaProductos" aria-autocomplete="list">
                            </div>
                            <select id="categoriaProductoApi" class="form-select" aria-label="Filtrar productos por categoría">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $c) { ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php } ?>
                            </select>
                        </div>
                        <ul id="listaProductos" class="selector-lista" role="listbox" aria-label="Productos" hidden>
                            <?php foreach ($productos as $p) { $id = (int)$p['id_producto']; $libres = (int)($disponibles[$id]['disponible'] ?? 0); ?>
                                <li role="option" aria-selected="false" data-sku="SKU-<?= $id ?>" data-nombre="<?= e($p['Nombre']) ?>"
                                    data-buscar="<?= e(mb_strtolower($p['Nombre'] . ' ' . $p['categoria'] . ' sku-' . $id . ' p' . str_pad($id, 4, '0', STR_PAD_LEFT))) ?>"
                                    data-categoria="<?= e($p['categoria']) ?>" data-zona="<?= e($p['zona']) ?>"
                                    data-ubicacion="<?= e(Ubicacion::detalle($p['pasillo'], $p['estante'], $p['posicion'])) ?>">
                                    <span class="selector-nombre"><strong><?= e($p['Nombre']) ?></strong><small>SKU-<?= $id ?> · <?= e($p['categoria']) ?> · <?= e($p['zona']) ?></small></span>
                                    <span class="badge <?= $libres > 0 ? 'bg-success' : 'bg-secondary' ?>"><?= $libres ?> disp.</span>
                                </li>
                            <?php } ?>
                            <li id="sinProductos" class="selector-vacio" hidden>Ningún producto coincide con la búsqueda.</li>
                        </ul>
                        <input type="hidden" id="producto">
                    </div>
                    <div id="productoInvalido" class="invalid-feedback">Elegí un producto de la lista.</div>
                </div>

                <div class="col-12">
                    <label for="zona" class="form-label">Zona de destino</label>
                    <input id="zona" class="form-control" readonly placeholder="Elegí un producto">
                    <small id="ubicacionProducto" class="text-secondary">Es la zona donde está guardado el producto: no se puede cambiar.</small>
                </div>
                <div class="col-12">
                    <button id="enviar" class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Enviar Pedido</button>
                </div>
            </form>
        </div>
    </section>
    <section class="col-xl-6">
        <div class="tarjeta h-100">
            <h2 class="tarjeta-titulo">Respuesta del servidor</h2>
            <div id="resultado" aria-live="polite">
                <p id="sinRespuesta" class="text-secondary mb-0">Enviá una orden para ver la respuesta JSON de la API.</p>
                <div id="respuesta" hidden>
                    <p class="mb-2">Código HTTP: <span id="codigo" class="badge"></span></p>
                    <pre id="json" class="respuesta-json"></pre>
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('pedido', 'index', ['estado' => 'Pendiente'])) ?>">Ver pedidos pendientes</a>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Últimas recepciones</h2>
    <p class="tarjeta-texto">Los rechazados y los pendientes de ingreso quedan en el historial de la API, no como órdenes de picking.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Recepción</th><th>Fecha</th><th>Cliente</th><th>SKU</th><th>Cantidad</th><th>Destino</th><th>Estado</th><th>Picking</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $r) { ?>
                <tr>
                    <td>#<?= (int)$r['id_recepcion'] ?></td>
                    <td class="text-nowrap"><?= e(fecha($r['fecha_recepcion'], 'd/m H:i')) ?></td>
                    <td><?= e($r['nombre_cliente']) ?></td>
                    <td><?= e($r['sku']) ?></td>
                    <td><?= (int)$r['cantidad'] ?></td>
                    <td><?= e($r['zona_destino']) ?></td>
                    <td><span class="badge <?= $colores[$r['estado']] ?? 'bg-secondary' ?>" title="<?= e($r['mensaje']) ?>"><?= e($r['estado']) ?></span></td>
                    <td><?= $r['id_picking'] === null ? '—' : '<a href="' . e(url('pedido', 'validar', ['id' => $r['id_picking']])) . '">#' . (int)$r['id_picking'] . '</a>' ?></td>
                </tr>
            <?php } if (!$historial) { ?>
                <tr><td colspan="8" class="text-center">No hay recepciones registradas.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
