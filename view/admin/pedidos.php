<?php
/* VISTA - Lista de pedidos con reporte (DataTables: Excel, PDF, CSV e imprimir) */
$titulo = 'Pedidos' . ($estado !== 'Todos' ? ' - ' . $estado : '');
$seccion = 'pedidos';
$estilos = ['https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.css'];
$scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
    'https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.js',
    'js/reporte_pedidos.js',
];
$textosFiltro = ['Todos' => 'Todos', 'Pendiente' => 'Pendientes', 'En Picking' => 'En picking', 'Por validar' => 'Por validar', 'Completado' => 'Completados'];
$clasesEstado = ['Completado' => 'activo', 'En Picking' => 'en-proceso', 'Por validar' => 'pendiente', 'Pendiente' => 'pendiente'];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">GESTIÓN</span>
        <h1><?= e($titulo) ?></h1>
        <p>Consultá pedidos, asigná pendientes a operarios y revisá quién completó cada pedido.</p>
    </div>
    <a class="btn btn-success" href="<?= e(url('pedido', 'nuevo')) ?>"><i class="bi bi-plus-circle"></i> Nuevo pedido</a>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<nav class="filtros" aria-label="Filtrar por estado">
    <?php foreach ($filtros as $f) { ?>
        <a class="<?= $estado === $f ? 'activo' : '' ?>" href="<?= e($f === 'Todos' ? url('pedido') : url('pedido', 'index', ['estado' => $f])) ?>" <?= $estado === $f ? 'aria-current="page"' : '' ?>><?= e($textosFiltro[$f]) ?></a>
    <?php } ?>
</nav>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <!-- La tabla se convierte en reporte con js/reporte_pedidos.js (DataTables) -->
        <table class="table align-middle" id="tablaPedidos" style="width:100%"
               data-titulo="Reporte de pedidos<?= $estado !== 'Todos' ? ' - ' . e($estado) : '' ?>"
               data-usuario="<?= e($sesion['usuario']) ?>">
            <thead><tr><th>Pedido</th><th>Cliente</th><th>Operario</th><th>Productos</th><th>Estado</th><th>Fecha</th><th class="no-exportar">Acción</th></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p) { ?>
                <tr>
                    <td data-order="<?= (int)$p['id'] ?>"><strong>#<?= (int)$p['id'] ?></strong></td>
                    <td><?= e($p['cliente']) ?></td>
                    <td data-exportar="<?= e($p['operario'] ?: 'Sin asignar') ?>">
                        <?php if ($p['operario']) { ?>
                            <strong><?= e($p['operario']) ?></strong>
                            <?php if ($p['estado'] === 'Completado') { ?><br><small class="text-muted">Completado por este operario</small><?php } ?>
                        <?php } else { ?>
                            <span class="text-muted">Sin asignar</span>
                        <?php } ?>
                    </td>
                    <td><?= (int)$p['total_productos'] ?></td>
                    <td><span class="estado <?= $clasesEstado[$p['estado']] ?? 'pendiente' ?>"><?= e($p['estado']) ?></span></td>
                    <td data-order="<?= e($p['fecha']) ?>"><?= e(fecha($p['fecha'])) ?></td>
                    <td style="min-width:230px">
                        <?php if ($p['estado'] === 'Pendiente' && empty($p['ci_operario'])) { ?>
                            <form method="POST" action="<?= e(url('pedido', 'asignar')) ?>" class="d-flex gap-2">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="estado" value="<?= e($estado) ?>">
                                <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                <select name="operario_ci" class="form-select form-select-sm" required aria-label="Operario para el pedido <?= (int)$p['id'] ?>">
                                    <option value="">Elegir operario</option>
                                    <?php foreach ($operarios as $op) { ?>
                                        <option value="<?= e($op['CI']) ?>"><?= e($op['usuario']) ?></option>
                                    <?php } ?>
                                </select>
                                <button class="btn btn-primary btn-sm" type="submit">Asignar</button>
                            </form>
                        <?php } elseif (!empty($p['ci_operario']) && in_array($p['estado'], ['Pendiente', 'En Picking'], true)) { ?>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <?php if ($p['estado'] === 'Pendiente') { ?>
                                    <span class="text-success"><i class="bi bi-person-check"></i> Asignado</span>
                                <?php } else { ?>
                                    <span class="text-muted">En preparación</span>
                                <?php } ?>
                                <form method="POST" action="<?= e(url('pedido', 'quitar')) ?>"
                                      onsubmit="return confirm(<?= e(json_encode('¿Quitarle el pedido #' . (int)$p['id'] . ' a ' . $p['operario'] . '? Queda pendiente para asignarlo de nuevo' . ($p['estado'] === 'En Picking' ? ' y lo que ya retiró vuelve al stock.' : '.'), JSON_UNESCAPED_UNICODE)) ?>);">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="estado" value="<?= e($estado) ?>">
                                    <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-person-dash"></i> Quitar al operario</button>
                                </form>
                            </div>
                        <?php } elseif ($p['estado'] === 'Por validar') { ?>
                            <a class="btn btn-warning btn-sm" href="<?= e(url('pedido', 'validar', ['id' => $p['id']])) ?>"><i class="bi bi-clipboard-check"></i> Validar</a>
                        <?php } elseif ($p['estado'] === 'Completado') { ?>
                            <span class="text-success"><i class="bi bi-check-circle"></i> Finalizado</span> <a class="small" href="<?= e(url('pedido', 'validar', ['id' => $p['id']])) ?>">Ver detalle</a>
                        <?php } else { ?>
                            <span class="text-muted">En preparación</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
