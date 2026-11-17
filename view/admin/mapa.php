<?php
/* VISTA - Mapa del depósito visto desde arriba.
   Cada zona dibuja sus pasillos (la franja por donde se camina) y las estanterías
   que hay a lo largo de cada uno. Al elegir un pasillo o una estantería se abre,
   debajo del plano, el detalle con los niveles y los productos (con su foto).
   Una zona, un pasillo o una estantería quedan en rojo si guardan un producto en
   su stock mínimo. Las zonas salen de la base: al crear o borrar una zona el mapa cambia solo. */
$titulo = 'Mapa del depósito';
$seccion = 'mapa';
$scripts = ['js/mapa.js'];
$espaciosLibres = $pagina === $totalPaginas ? $zonasPorPagina - count($zonas) : 0;
// Al entrar se abre el primer pasillo con un producto para reponer (si hay)
$pasilloInicial = '';
foreach ($zonas as $z) {
    foreach ($z['pasillos'] as $codigo => $pa) {
        if ($pa['alertas'] > 0 && $pasilloInicial === '') {
            $pasilloInicial = 'z' . (int)$z['id_zona'] . '-' . $codigo;
        }
    }
}
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">CENTRO LOGÍSTICO</span>
        <h1>Mapa del depósito</h1>
        <p>El depósito visto desde arriba. Pasá por una zona y tocá un pasillo o una estantería para ver qué hay guardado en cada nivel. Lo que está en rojo tiene productos en su stock mínimo.</p>
    </div>
    <a href="<?= e(url('logistica', 'index', ['tipo' => 'zonas'])) ?>" class="btn btn-outline-primary"><i class="bi bi-grid-3x3-gap"></i> Gestionar zonas</a>
</section>

<div class="mapa-leyenda" aria-label="Referencias del mapa">
    <span><i class="bi bi-snow" aria-hidden="true"></i> Refrigerada</span>
    <span><i class="bi bi-sun" aria-hidden="true"></i> Seca</span>
    <span><i class="bi bi-boxes" aria-hidden="true"></i> Voluminosa</span>
    <span><span class="leyenda-estante" aria-hidden="true"></span> Estantería</span>
    <span><span class="leyenda-camino" aria-hidden="true"></span> Pasillo</span>
    <span class="leyenda-alerta"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Producto en stock mínimo</span>
    <span class="ms-auto text-secondary"><?= $totalZonas ?> zona(s) · página <?= $pagina ?> de <?= $totalPaginas ?></span>
</div>

<div class="mapa-plano" id="planoDeposito" data-inicial="<?= e($pasilloInicial) ?>">
    <?php foreach ($zonas as $i => $z) {
        $tipo = strtolower($z['tipo']);
        $alertas = (int)$z['alertas'];
        $ocupacion = (int)$z['capacidad_total'] > 0 ? min(100, round((int)$z['stock_total'] * 100 / (int)$z['capacidad_total'])) : 0;
        $idZona = 'z' . (int)$z['id_zona'];
    ?>
        <?php if ($i === 3) { ?><div class="mapa-pasillo" aria-hidden="true"><span>PASILLO CENTRAL</span></div><?php } ?>
        <section class="mapa-zona zona-<?= e($tipo) ?> <?= $alertas > 0 ? 'zona-alerta' : '' ?>" aria-label="<?= e($z['nombre'] . ($alertas > 0 ? ', con productos en stock mínimo' : '')) ?>">
            <header class="mapa-zona-cabecera">
                <div>
                    <h2><i class="bi <?= $iconosZona[$tipo] ?? 'bi-grid-3x3-gap' ?>" aria-hidden="true"></i> <?= e($z['nombre']) ?></h2>
                    <small><?= e($z['tipo']) ?> · <?= e($z['deposito']) ?> · <?= (int)$z['productos'] ?> producto(s)</small>
                </div>
                <?php if ($alertas > 0) { ?>
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Reponer <?= $alertas ?></span>
                <?php } elseif (strcasecmp($z['estado'], 'Activa') !== 0) { ?>
                    <span class="badge bg-warning text-dark"><?= e($z['estado']) ?></span>
                <?php } ?>
            </header>
            <div class="mapa-ocupacion" title="<?= (int)$z['stock_total'] ?> de <?= (int)$z['capacidad_total'] ?> unidades">
                <div class="progress" role="progressbar" aria-label="Ocupación de <?= e($z['nombre']) ?>" aria-valuenow="<?= $ocupacion ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?= $ocupacion ?>%"></div>
                </div>
                <small><?= $ocupacion ?> % ocupado</small>
            </div>

            <?php if (!$z['pasillos']) { ?>
                <p class="mapa-vacia">Sin ubicaciones cargadas.</p>
            <?php } else { ?>
                <div class="planta" aria-label="Plano de <?= e($z['nombre']) ?>">
                    <?php foreach ($z['pasillos'] as $codigoPasillo => $pa) {
                        $idPasillo = $idZona . '-' . $codigoPasillo;
                    ?>
                        <div class="planta-pasillo">
                            <div class="planta-estantes">
                                <?php foreach ($pa['estantes'] as $codigoEstante => $es) { ?>
                                    <button type="button" class="planta-estante <?= $es['alertas'] ? 'con-alerta' : '' ?> <?= $es['productos'] ? '' : 'vacia' ?>"
                                            data-pasillo="<?= e($idPasillo) ?>" data-estante="<?= e($codigoEstante) ?>" aria-controls="detalleMapa"
                                            title="<?= e(Ubicacion::esSectorPiso($codigoEstante) ? 'Sector de piso' : 'Estantería ' . $codigoEstante) ?>: <?= (int)$es['productos'] ?> producto(s)">
                                        <span><?= e(Ubicacion::esSectorPiso($codigoEstante) ? 'Piso' : $codigoEstante) ?></span>
                                        <small><?= (int)$es['productos'] ?></small>
                                    </button>
                                <?php } ?>
                            </div>
                            <button type="button" class="planta-camino <?= $pa['alertas'] ? 'con-alerta' : '' ?>" data-pasillo="<?= e($idPasillo) ?>" aria-controls="detalleMapa">
                                <span><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Pasillo <?= e($codigoPasillo) ?></span>
                                <small><?= (int)$pa['productos'] ?> producto(s)<?= $pa['alertas'] ? ' · ' . (int)$pa['alertas'] . ' para reponer' : '' ?></small>
                            </button>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </section>
    <?php } ?>

    <?php for ($j = 0; $j < $espaciosLibres; $j++) { ?>
        <?php if (count($zonas) + $j === 3) { ?><div class="mapa-pasillo" aria-hidden="true"><span>PASILLO CENTRAL</span></div><?php } ?>
        <a class="mapa-libre" href="<?= e(url('logistica', 'index', ['tipo' => 'zonas'])) ?>">
            <i class="bi bi-plus-square-dotted" aria-hidden="true"></i>
            <span>Espacio libre</span>
            <small>Creá una zona nueva y aparece acá</small>
        </a>
    <?php } ?>
    <div class="mapa-entrada" aria-hidden="true"><i class="bi bi-truck"></i> ENTRADA Y ANDÉN DE CARGA</div>
</div>

<?php if ($totalPaginas > 1) { ?>
    <nav class="mapa-paginas" aria-label="Páginas del mapa">
        <a class="btn btn-outline-primary <?= $pagina <= 1 ? 'disabled' : '' ?>" href="<?= e(url('logistica', 'mapa', ['pagina' => $pagina - 1])) ?>" <?= $pagina <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>><i class="bi bi-chevron-left"></i> Anterior</a>
        <?php for ($n = 1; $n <= $totalPaginas; $n++) { ?>
            <a class="btn <?= $n === $pagina ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('logistica', 'mapa', ['pagina' => $n])) ?>" <?= $n === $pagina ? 'aria-current="page"' : '' ?>>Página <?= $n ?></a>
        <?php } ?>
        <a class="btn btn-outline-primary <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>" href="<?= e(url('logistica', 'mapa', ['pagina' => $pagina + 1])) ?>" <?= $pagina >= $totalPaginas ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Siguiente <i class="bi bi-chevron-right"></i></a>
    </nav>
<?php } ?>

<!-- Detalle del pasillo elegido: lo completa js/mapa.js con la plantilla correspondiente -->
<section class="mapa-detalle" id="detalleMapa" aria-live="polite" tabindex="-1">
    <p class="mapa-detalle-vacio"><i class="bi bi-hand-index" aria-hidden="true"></i> Tocá un pasillo o una estantería del plano para ver qué hay guardado.</p>
</section>

<?php foreach ($zonas as $z) {
    foreach ($z['pasillos'] as $codigoPasillo => $pa) {
        $idPasillo = 'z' . (int)$z['id_zona'] . '-' . $codigoPasillo;
?>
    <template id="detalle-<?= e($idPasillo) ?>">
        <header class="mapa-detalle-cabecera">
            <div>
                <h2><?= e($z['nombre']) ?> · Pasillo <?= e($codigoPasillo) ?></h2>
                <small><?= e($z['tipo']) ?> · <?= (int)$pa['productos'] ?> producto(s) en <?= count($pa['estantes']) ?> estantería(s)</small>
            </div>
            <button type="button" class="btn-close" data-cerrar-detalle aria-label="Cerrar el detalle"></button>
        </header>
        <div class="detalle-estantes">
            <?php foreach ($pa['estantes'] as $codigoEstante => $es) {
                // Como se ve la estantería de frente: el nivel más alto arriba
                $niveles = $es['niveles'];
                krsort($niveles);
            ?>
                <article class="detalle-estante <?= $es['alertas'] ? 'con-alerta' : '' ?>" data-estante="<?= e($codigoEstante) ?>">
                    <h3><?= e(Ubicacion::esSectorPiso($codigoEstante) ? 'Sector de piso' : 'Estantería ' . $codigoEstante) ?></h3>
                    <ol class="detalle-niveles">
                        <?php foreach ($niveles as $codigoNivel => $productos) { ?>
                            <li class="detalle-nivel">
                                <span class="nivel-codigo"><?= e($codigoNivel) ?></span>
                                <div class="nivel-contenido">
                                    <?php if (!$productos) { ?>
                                        <span class="nivel-libre">Libre</span>
                                    <?php } ?>
                                    <?php foreach ($productos as $p) { ?>
                                        <div class="detalle-producto <?= $p['alerta'] ? 'producto-alerta' : '' ?>">
                                            <?= miniaturaProducto($p['foto'], $p['Nombre']) ?>
                                            <div class="detalle-producto-texto">
                                                <strong><?= e($p['Nombre']) ?></strong>
                                                <small><?= e($codigoPasillo . '-' . $codigoEstante . '-' . $codigoNivel) ?> · mínimo <?= (int)$p['stock_minimo'] ?></small>
                                            </div>
                                            <span class="detalle-producto-cantidad">
                                                <?php if ($p['alerta']) { ?><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span class="visually-hidden">En stock mínimo:</span><?php } ?>
                                                <?= (int)$p['stock_actual'] ?> u.
                                            </span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </li>
                        <?php } ?>
                    </ol>
                </article>
            <?php } ?>
        </div>
    </template>
<?php }
} ?>

<?php require RAIZ . '/view/layout/pie.php'; ?>
