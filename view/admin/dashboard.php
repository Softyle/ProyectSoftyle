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

<?php if ($vencimientos['vencidos'] > 0 || $vencimientos['por_vencer'] > 0) { ?>
    <div class="aviso-validar aviso-vencimiento" role="status">
        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
        <span>
            <?php if ($vencimientos['vencidos'] > 0) { ?><strong><?= $vencimientos['vencidos'] ?> <?= $vencimientos['vencidos'] === 1 ? 'lote vencido' : 'lotes vencidos' ?></strong> para dar de baja<?php } ?>
            <?= $vencimientos['vencidos'] > 0 && $vencimientos['por_vencer'] > 0 ? ' · ' : '' ?>
            <?php if ($vencimientos['por_vencer'] > 0) { ?><strong><?= $vencimientos['por_vencer'] ?> <?= $vencimientos['por_vencer'] === 1 ? 'lote vence' : 'lotes vencen' ?></strong> en los próximos <?= Lote::DIAS_AVISO ?> días<?php } ?>
        </span>
        <a class="btn btn-sm btn-danger ms-auto" href="<?= e(url('recepcion', 'vencimientos', ['dias' => Lote::DIAS_AVISO])) ?>">Ver vencimientos</a>
    </div>
<?php } ?>
<?php if ($conteosEnCurso > 0) { ?>
    <div class="aviso-validar aviso-conteo" role="status">
        <i class="bi bi-clipboard-data" aria-hidden="true"></i>
        <span><strong><?= $conteosEnCurso ?> <?= $conteosEnCurso === 1 ? 'conteo de inventario' : 'conteos de inventario' ?></strong> en curso</span>
        <a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('conteo')) ?>">Ver conteos</a>
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
                <a class="resumen-enlace" href="<?= e(url('compra', 'reposicion')) ?>">Reponer stock <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
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

<!-- Accesos rápidos: se arman con la misma lista del menú lateral ($menuLateral, de cabecera.php),
     así siempre tienen las mismas opciones y grupos. Se saltea "Panel de control" porque es esta página. -->
<?php
$iconosGrupo = ['General' => 'bi-bar-chart-line', 'Pedidos' => 'bi-list-check', 'Centro logístico' => 'bi-grid-3x3-gap',
                'Personal' => 'bi-people', 'Control ambiental' => 'bi-thermometer-snow', 'Abastecimiento' => 'bi-truck'];
?>
<section class="accesos-grupos mt-3" aria-label="Accesos rápidos">
    <?php foreach ($menuLateral as $grupo => $opciones) {
        $opciones = array_filter($opciones, fn($o) => $o[0] !== $seccion);
        if (!$opciones) {
            continue;
        }
    ?>
        <div class="tarjeta tarjeta-accesos">
            <h2 class="tarjeta-titulo"><i class="bi <?= e($iconosGrupo[$grupo] ?? 'bi-grid') ?>" aria-hidden="true"></i> <?= e($grupo) ?></h2>
            <div class="accesos">
                <?php foreach ($opciones as [$clave, $controlador, $accion, $parametros, $icono, $texto]) { ?>
                    <a href="<?= e(url($controlador, $accion, $parametros)) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($texto) ?></a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
