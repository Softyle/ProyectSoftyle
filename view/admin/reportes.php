<?php
/* VISTA - Reportes avanzados para el cliente (administrador).
   Cada tarjeta muestra el resultado de una consulta y, desplegable, el SQL que la generó. */
$titulo = 'Reportes';
$seccion = 'reportes';
require RAIZ . '/view/layout/cabecera.php';

/* Las fechas de MySQL se muestran con formato uruguayo; los vacíos, con una raya */
$formatearCelda = function ($valor) {
    if ($valor === null || $valor === '') {
        return '—';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', (string)$valor)) {
        return fecha($valor, strlen($valor) > 10 ? 'd/m/Y H:i' : 'd/m/Y');
    }
    return (string)$valor;
};
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">CONSULTAS AVANZADAS</span>
        <h1>Reportes</h1>
        <p>Información del depósito para la toma de decisiones. Cada reporte incluye la consulta SQL que lo genera.</p>
    </div>
    <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir reportes</button>
</section>

<!-- Índice: salta directo a cada reporte -->
<nav class="filtros filtros-reportes" aria-label="Lista de reportes">
    <?php foreach ($reportes as $i => $r) { ?>
        <a href="#reporte-<?= e($r['clave']) ?>"><?= $i + 1 ?>. <?= e($r['titulo']) ?></a>
    <?php } ?>
</nav>

<div class="reportes">
    <?php foreach ($reportes as $i => $r) { ?>
        <article class="tarjeta-tabla reporte" id="reporte-<?= e($r['clave']) ?>">
            <header class="reporte-cabecera">
                <span class="reporte-icono"><i class="bi <?= e($r['icono']) ?>" aria-hidden="true"></i></span>
                <div>
                    <h2><?= $i + 1 ?>. <?= e($r['titulo']) ?></h2>
                    <p><?= e($r['descripcion']) ?></p>
                </div>
                <span class="reporte-cantidad"><?= count($r['filas']) ?> <?= count($r['filas']) === 1 ? 'fila' : 'filas' ?></span>
            </header>

            <?php if ($r['error'] !== '') { ?>
                <div class="alert alert-danger mx-3" role="alert"><?= e($r['error']) ?></div>
            <?php } elseif (!$r['filas']) { ?>
                <p class="reporte-vacio"><i class="bi bi-inbox" aria-hidden="true"></i> No hay datos para este reporte todavía.</p>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><?php foreach (array_keys($r['filas'][0]) as $columna) { ?><th><?= e($columna) ?></th><?php } ?></tr></thead>
                        <tbody>
                        <?php foreach ($r['filas'] as $fila) { ?>
                            <tr><?php foreach ($fila as $valor) { ?><td><?= e($formatearCelda($valor)) ?></td><?php } ?></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <details class="reporte-sql">
                <summary><i class="bi bi-code-slash" aria-hidden="true"></i> Consulta SQL</summary>
                <pre><code><?= e($r['sql']) ?></code></pre>
            </details>
        </article>
    <?php } ?>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
