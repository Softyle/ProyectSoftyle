<?php
/* VISTA - Mis logros: galería de insignias (gamificación) */
$titulo = 'Mis logros';
$seccion = 'logros';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">GAMIFICACIÓN Y EFICIENCIA</span>
    <h1>Mis logros</h1>
    <p>Obtuviste <strong><?= $obtenidos ?> de <?= count($logros) ?></strong> insignias. Se ganan por precisión, velocidad y volumen de picking.</p>
</section>

<section class="indicadores" aria-label="Mis estadísticas de picking">
    <div class="indicador"><i class="bi bi-bullseye"></i><div><span>Precisión</span><strong><?= $est['precision_pct'] === null ? '—' : e($est['precision_pct']) . '%' ?></strong></div></div>
    <div class="indicador"><i class="bi bi-stopwatch"></i><div><span>Tiempo promedio por pedido</span><strong><?= $est['minutos_promedio'] === null ? '—' : e($est['minutos_promedio']) . ' min' ?></strong></div></div>
    <div class="indicador"><i class="bi bi-box-seam"></i><div><span>Unidades recogidas</span><strong><?= (int)$est['unidades_recogidas'] ?></strong></div></div>
    <div class="indicador indicador-verde"><i class="bi bi-check-circle"></i><div><span>Pedidos completados</span><strong><?= (int)$est['pedidos_completados'] ?></strong></div></div>
</section>
<p class="nota mb-4">Precisión = retiros correctos sobre el total de intentos (retiros + errores de cantidad o de ubicación). Tiempo = desde que abrís la hoja de ruta hasta tu último retiro.</p>

<?php
// Las insignias se muestran agrupadas por categoría (vienen ordenadas desde el modelo)
$categorias = [];
foreach ($logros as $l) {
    $categorias[$l['categoria']][] = $l;
}
$iconosCategoria = ['Volumen' => 'bi-box-seam', 'Precisión' => 'bi-bullseye', 'Velocidad' => 'bi-lightning-charge'];
?>
<?php foreach ($categorias as $categoria => $insignias) { $ganadas = count(array_filter($insignias, fn($i) => $i['obtenido'])); ?>
<h2 class="insignias-grupo">
    <i class="bi <?= $iconosCategoria[$categoria] ?? 'bi-award' ?>" aria-hidden="true"></i> <?= e($categoria) ?>
    <span><?= $ganadas ?> de <?= count($insignias) ?></span>
</h2>
<section class="galeria-insignias" aria-label="Insignias de <?= e(mb_strtolower($categoria)) ?>">
    <?php foreach ($insignias as $l) { ?>
        <article class="insignia nivel-<?= e(strtolower($l['nivel'])) ?> <?= $l['obtenido'] ? '' : 'bloqueada' ?>">
            <div class="icono"><i class="bi <?= e($l['icono']) ?>" aria-hidden="true"></i></div>
            <h3><?= e($l['nombre']) ?></h3>
            <p><?= e($l['descripcion']) ?></p>
            <span class="etiqueta-<?= e(strtolower($l['nivel'])) ?>"><?= e($l['nivel']) ?> · <?= e($l['categoria']) ?></span>
            <?php if ($l['obtenido']) { ?>
                <p class="mt-2 mb-0 small"><i class="bi bi-check-circle-fill text-success"></i> Obtenida el <?= e(fecha($l['fecha_obtenido'], 'd/m/Y')) ?></p>
            <?php } else { ?>
                <div class="progress mt-2" role="progressbar" aria-label="Avance hacia <?= e($l['nombre']) ?>" aria-valuenow="<?= (int)$l['progreso'] ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?= (int)$l['progreso'] ?>%"></div>
                </div>
                <p class="mt-1 mb-0 small"><?= e($l['detalle']) ?></p>
            <?php } ?>
        </article>
    <?php } ?>
</section>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
