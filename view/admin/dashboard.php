<?php
/* VISTA - Panel de control del administrador
   Indicadores de solo lectura, tablero de prioridades y accesos rápidos. */
$titulo = 'Panel de control';
$seccion = 'admin';
require RAIZ . '/view/layout/cabecera.php';
?>

<!-- Indicadores: solo informan, no son enlaces -->
<section class="indicadores" aria-label="Resumen del depósito">
    <div class="indicador"><i class="bi bi-clipboard-check" aria-hidden="true"></i><div><span>Pedidos pendientes</span><strong><?= $pedidosPendientes ?></strong></div></div>
    <div class="indicador"><i class="bi bi-box-seam" aria-hidden="true"></i><div><span>Productos</span><strong><?= $productos ?></strong></div></div>
    <div class="indicador"><i class="bi bi-people" aria-hidden="true"></i><div><span>Operarios activos</span><strong><?= $operariosActivos ?></strong></div></div>
    <div class="indicador indicador-verde"><i class="bi bi-check-circle" aria-hidden="true"></i><div><span>Pedidos completados</span><strong><?= $pedidosCompletados ?></strong></div></div>
</section>

<?php if ($pedidosPorValidar > 0) { ?>
    <div class="aviso-validar" role="status">
        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
        <span><strong><?= $pedidosPorValidar ?> <?= $pedidosPorValidar === 1 ? 'pedido espera' : 'pedidos esperan' ?></strong> tu validación</span>
        <a class="btn btn-sm btn-warning ms-auto" href="<?= e(url('pedido', 'index', ['estado' => 'Por validar'])) ?>">Validar</a>
    </div>
<?php } ?>

<!-- Tablero de prioridades: Oro (crítico), Plata (en proceso), Bronce (completado).
     Si una no tiene nada, queda chica: solo el título y el contador en 0. -->
<section class="row g-3" aria-label="Tablero de prioridades">
    <div class="col-lg-4">
        <article class="resumen resumen-oro<?= $prioridadOro ? '' : ' resumen-vacia' ?>">
            <header class="resumen-cabecera">
                <span class="etiqueta-oro">Oro</span>
                <h2>Stock crítico</h2>
                <span class="resumen-cantidad"><?= count($prioridadOro) ?></span>
            </header>
            <?php if (!$prioridadOro) { ?>
                <p class="visually-hidden">Todas las zonas tienen stock suficiente.</p>
            <?php } else { ?>
                <ul class="resumen-lista">
                    <?php foreach ($prioridadOro as $o) { ?>
                        <li><span class="resumen-dato"><?= e($o['zona']) ?></span><span class="resumen-texto"><?= e($o['detalle']) ?></span></li>
                    <?php } ?>
                </ul>
                <a class="resumen-enlace" href="<?= e(url('logistica', 'inventario', ['filtro' => 'Alertas'])) ?>">Ver inventario <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <?php } ?>
        </article>
    </div>
    <div class="col-lg-4">
        <article class="resumen resumen-plata<?= $prioridadPlata ? '' : ' resumen-vacia' ?>">
            <header class="resumen-cabecera">
                <span class="etiqueta-plata">Plata</span>
                <h2>En picking ahora</h2>
                <span class="resumen-cantidad"><?= count($prioridadPlata) ?></span>
            </header>
            <?php if (!$prioridadPlata) { ?>
                <p class="visually-hidden">No hay pedidos en preparación.</p>
            <?php } else { ?>
                <ul class="resumen-lista">
                    <?php foreach ($prioridadPlata as $p) { $porcentaje = $p['total'] > 0 ? round($p['recogidos'] * 100 / $p['total']) : 0; ?>
                        <li>
                            <span class="resumen-fila"><a href="<?= e(url('pedido', 'validar', ['id' => $p['id_pedido']])) ?>">Pedido #<?= (int)$p['id_pedido'] ?></a><small><?= e($p['operario']) ?></small></span>
                            <span class="resumen-progreso" title="<?= (int)$p['recogidos'] ?> de <?= (int)$p['total'] ?> productos"><span style="width: <?= $porcentaje ?>%"></span></span>
                            <small class="resumen-texto"><?= (int)$p['recogidos'] ?> de <?= (int)$p['total'] ?> productos</small>
                        </li>
                    <?php } ?>
                </ul>
                <a class="resumen-enlace" href="<?= e(url('seguimiento')) ?>">Ver seguimiento <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <?php } ?>
        </article>
    </div>
    <div class="col-lg-4">
        <article class="resumen resumen-bronce<?= $prioridadBronce ? '' : ' resumen-vacia' ?>">
            <header class="resumen-cabecera">
                <span class="etiqueta-bronce">Bronce</span>
                <h2>Completados · 30 días</h2>
                <span class="resumen-cantidad"><?= count($prioridadBronce) ?></span>
            </header>
            <?php if (!$prioridadBronce) { ?>
                <p class="visually-hidden">Sin pedidos completados en los últimos 30 días.</p>
            <?php } else { ?>
                <ul class="resumen-lista">
                    <?php foreach (array_slice($prioridadBronce, 0, 3) as $b) { ?>
                        <li><span class="resumen-fila"><a href="<?= e(url('pedido', 'validar', ['id' => $b['id_pedido']])) ?>">Pedido #<?= (int)$b['id_pedido'] ?></a><small><?= e($b['operario']) ?> · <?= e(fecha($b['fecha_entrega'], 'd/m H:i')) ?></small></span></li>
                    <?php } ?>
                </ul>
                <a class="resumen-enlace" href="<?= e(url('pedido', 'index', ['estado' => 'Completado'])) ?>">Ver completados <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <?php } ?>
        </article>
    </div>
</section>

<!-- Accesos rápidos (las mismas opciones del menú lateral, agrupadas). Cada tarjeta mide lo que su contenido. -->
<section class="row g-3 mt-1 align-items-start">
    <div class="col-xl-4 col-md-6">
        <div class="tarjeta tarjeta-accesos">
            <h2 class="tarjeta-titulo"><i class="bi bi-list-check"></i> Gestión de pedidos</h2>
                        <div class="accesos">
                <a href="<?= e(url('pedido', 'nuevo')) ?>"><i class="bi bi-plus-circle"></i> Crear pedido</a>
                <a href="<?= e(url('pedido')) ?>"><i class="bi bi-list-check"></i> Ver pedidos</a>
                <a href="<?= e(url('seguimiento')) ?>"><i class="bi bi-person-workspace"></i> Operarios y hojas de ruta</a>
                <a href="<?= e(url('cronograma')) ?>"><i class="bi bi-calendar-week"></i> Cronograma de despachos</a>
                <a href="<?= e(url('api', 'simulador')) ?>"><i class="bi bi-send"></i> Simular pedido externo</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="tarjeta tarjeta-accesos">
            <h2 class="tarjeta-titulo"><i class="bi bi-thermometer-snow"></i> Eficiencia y control ambiental</h2>
                        <div class="accesos">
                <a href="<?= e(url('logro', 'desempeno')) ?>"><i class="bi bi-trophy"></i> Desempeño y logros</a>
                <a href="<?= e(url('sensor')) ?>"><i class="bi bi-thermometer-snow"></i> Monitor de sensores</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12">
        <div class="tarjeta tarjeta-accesos">
            <h2 class="tarjeta-titulo"><i class="bi bi-grid-3x3-gap"></i> Gestión del Centro Logístico</h2>
                        <div class="accesos">
                <a href="<?= e(url('logistica', 'index', ['tipo' => 'zonas'])) ?>"><i class="bi bi-grid-3x3-gap"></i> Gestionar zonas</a>
                <a href="<?= e(url('logistica', 'index', ['tipo' => 'ubicaciones'])) ?>"><i class="bi bi-geo-alt"></i> Gestionar ubicaciones</a>
                <a href="<?= e(url('logistica', 'inventario')) ?>"><i class="bi bi-boxes"></i> Stock por zona</a>
            </div>
        </div>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
