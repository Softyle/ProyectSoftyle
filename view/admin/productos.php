<?php
/* VISTA - Catálogo de productos */
$titulo = 'Productos';
$seccion = 'productos';
$scripts = ['js/productos.js'];
// Categorías que tienen productos (opciones del filtro)
$categorias = array_unique(array_column($productos, 'categoria'));
sort($categorias);
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">INVENTARIO</span>
        <h1>Productos</h1>
        <p>Gestioná los productos almacenados en el depósito.</p>
    </div>
    <a href="<?= e(url('producto', 'agregar')) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Agregar producto</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div class="tarjeta-tabla">
    <!-- Búsqueda en la lista (js/productos.js): por nombre o código, y por categoría -->
    <form class="buscador-tabla" role="search" onsubmit="return false;">
        <div class="buscador-texto">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="buscarProducto" class="form-control" placeholder="Buscar por nombre o código" aria-label="Buscar producto por nombre o código">
        </div>
        <select id="filtroCategoria" class="form-select" aria-label="Filtrar por categoría">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c) { ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php } ?>
        </select>
        <span id="cantidadProductos" class="buscador-cantidad" aria-live="polite"><?= count($productos) ?> productos</span>
    </form>
    <div class="table-responsive">
        <table class="table align-middle" id="tablaProductos">
            <thead><tr><th>Producto</th><th>Código</th><th>Categoría</th><th>Ubicación</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$productos) { ?>
                <tr><td colspan="6" class="text-center">No hay productos registrados.</td></tr>
            <?php } ?>
            <?php foreach ($productos as $p) { ?>
                <tr data-nombre="<?= e(mb_strtolower($p['nombre'] . ' ' . $p['codigo'])) ?>" data-categoria="<?= e($p['categoria']) ?>">
                    <td><strong><?= e($p['nombre']) ?></strong></td>
                    <td><?= e($p['codigo']) ?></td>
                    <td><?= e($p['categoria']) ?></td>
                    <td><?= e($p['ubicacion']) ?></td>
                    <td>
                        <?php if ((int)$p['stock'] <= (int)$p['minimo']) { ?>
                            <span class="badge bg-danger" title="En el stock mínimo (<?= (int)$p['minimo'] ?>): necesita reposición"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> <?= (int)$p['stock'] ?></span>
                        <?php } else { ?>
                            <span class="badge bg-success"><?= (int)$p['stock'] ?></span>
                        <?php } ?>
                        <small class="text-secondary">mín. <?= (int)$p['minimo'] ?></small>
                    </td>
                    <td class="text-nowrap">
                        <a href="<?= e(url('producto', 'documentos', ['id' => $p['id']])) ?>" class="btn btn-sm btn-outline-secondary" title="Documentación técnica" aria-label="Documentación técnica"><i class="bi bi-file-earmark-text"></i><?= (int)$p['documentos'] > 0 ? ' ' . (int)$p['documentos'] : '' ?></a>
                        <a href="<?= e(url('producto', 'editar', ['id' => $p['id']])) ?>" class="btn btn-sm btn-primary" title="Editar" aria-label="Editar <?= e($p['nombre']) ?>"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= e(url('producto', 'eliminar')) ?>" class="d-inline" onsubmit="return confirm('¿Seguro que querés eliminar este producto?');">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" aria-label="Eliminar <?= e($p['nombre']) ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
                <tr id="sinResultados" hidden><td colspan="6" class="text-center text-secondary">Ningún producto coincide con la búsqueda.</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
