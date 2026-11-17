<?php
/* VISTA - Generación de pedidos (formulario dinámico con jQuery) */
$titulo = 'Nuevo pedido';
$seccion = 'pedido-nuevo';
$scripts = ['js/pedido_nuevo.js'];
require RAIZ . '/view/layout/cabecera.php';

/* Productos agrupados por categoría (las categorías en orden alfabético) */
$porCategoria = [];
foreach ($productos as $id => $p) {
    $porCategoria[$p['categoria']][$id] = $p;
}
$sinTildes = fn($t) => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n~']);
uksort($porCategoria, fn($a, $b) => strcmp($sinTildes($a), $sinTildes($b)));

/* Opciones de categoría. js/pedido_nuevo.js filtra el selector de productos
   según la categoría elegida en cada línea. */
$opcionesCategorias = function ($seleccionada) use ($porCategoria) {
    $html = '<option value="">Elegí una categoría...</option>';
    foreach ($porCategoria as $categoria => $lista) {
        $html .= '<option value="' . e($categoria) . '"' . ($seleccionada === $categoria ? ' selected' : '') . '>'
            . e($categoria . ' (' . count($lista) . ')') . '</option>';
    }
    return $html;
};

/* Opciones de productos agrupadas por categoría (sin JavaScript se ven todas juntas) */
$opcionesProductos = function ($seleccionado) use ($porCategoria, $disponibles) {
    $html = '<option value="">Elegí un producto...</option>';
    foreach ($porCategoria as $categoria => $lista) {
        $html .= '<optgroup label="' . e($categoria) . '">';
        foreach ($lista as $id => $p) {
            $disp = $disponibles[$id]['disponible'] ?? 0;
            $html .= '<option value="' . $id . '" data-disponible="' . $disp . '" data-categoria="' . e($categoria) . '"'
                . ((int)$seleccionado === $id ? ' selected' : '') . '>'
                . e($p['Nombre'] . ' — ' . $p['zona'] . ' (' . $p['tipo'] . ') — disponibles: ' . $disp) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
};
?>
<a href="<?= e(url('pedido')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a pedidos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">GESTIÓN DE PEDIDOS</span>
    <h1>Nuevo pedido</h1>
</section>

<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta">
    <form method="POST" action="<?= e(url('pedido', 'nuevo')) ?>" id="formPedido">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="ci_cliente">Cliente</label>
                <select class="form-select" id="ci_cliente" name="ci_cliente" required>
                    <option value="">Seleccionar cliente...</option>
                    <?php foreach ($clientes as $c) { ?>
                        <option value="<?= e($c['CI']) ?>" <?= $clienteForm === $c['CI'] ? 'selected' : '' ?>><?= e($c['usuario']) ?> (CI <?= e($c['CI']) ?>)</option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="fecha_entrega">Entrega programada <small class="text-secondary">(opcional)</small></label>
                <input class="form-control" type="datetime-local" id="fecha_entrega" name="fecha_entrega" value="<?= e($fechaForm) ?>">
            </div>
        </div>

        <h2 class="subtitulo">Productos</h2>
        <div id="lineas">
            <?php foreach ($lineasForm as $linea) {
                $categoriaLinea = $linea['id_producto'] !== '' ? ($productos[(int)$linea['id_producto']]['categoria'] ?? '') : '';
            ?>
                <div class="row g-2 align-items-end mb-2 linea-pedido">
                    <div class="col-12 col-md-3">
                        <label class="form-label small">Categoría</label>
                        <select class="form-select sel-categoria" aria-label="Categoría del producto"><?= $opcionesCategorias($categoriaLinea) ?></select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label small">Producto</label>
                        <select class="form-select sel-producto" name="producto[]" required><?= $opcionesProductos($linea['id_producto']) ?></select>
                    </div>
                    <div class="col-8 col-md-3">
                        <label class="form-label small">Cantidad</label>
                        <input class="form-control inp-cantidad" type="number" name="cantidad[]" min="1" value="<?= e($linea['cantidad']) ?>" required>
                    </div>
                    <div class="col-4 col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100 btn-quitar" title="Quitar producto" aria-label="Quitar producto"><i class="bi bi-trash"></i></button>
                    </div>
                    <div class="col-12"><small class="aviso-stock text-danger" aria-live="polite"></small></div>
                </div>
            <?php } ?>
        </div>
        <button type="button" class="btn btn-outline-primary mb-4" id="agregarLinea"><i class="bi bi-plus-lg"></i> Agregar otro producto</button>

        <div><button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check2-circle"></i> Crear pedido</button></div>
    </form>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
