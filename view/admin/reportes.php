<?php
/* VISTA - Reportes avanzados para el cliente (administrador).
   Cada tarjeta muestra el resultado de una consulta y, desplegable, el SQL que la generó.
   Los reportes van en dos grupos (Pedidos y depósito / Abastecimiento) y cada tabla
   se puede bajar en Excel, PDF o CSV (js/reportes.js con DataTables). */
$titulo = 'Reportes';
$seccion = 'reportes';
$estilos = ['https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.css'];
$scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
    'https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.js',
    'js/reportes.js',
];
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

// Reportes agrupados, numerados en orden
$grupos = [];
foreach ($reportes as $i => $r) {
    $r['numero'] = $i + 1;
    $grupos[$r['grupo']][] = $r;
}
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">CONSULTAS AVANZADAS</span>
        <h1>Reportes</h1>
        <p>Información del depósito para la toma de decisiones. Cada reporte se puede bajar en Excel, PDF o CSV e incluye la consulta SQL que lo genera.</p>
    </div>
    <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir todos</button>
</section>

<!-- Índice: salta directo a cada reporte -->
<?php foreach ($grupos as $grupo => $lista) { ?>
    <p class="reportes-grupo-indice"><?= e($grupo) ?></p>
    <nav class="filtros filtros-reportes" aria-label="Reportes de <?= e(mb_strtolower($grupo)) ?>">
        <?php foreach ($lista as $r) { ?>
            <a href="#reporte-<?= e($r['clave']) ?>"><?= $r['numero'] ?>. <?= e($r['titulo']) ?></a>
        <?php } ?>
    </nav>
<?php } ?>

<?php foreach ($grupos as $grupo => $lista) { ?>
    <h2 class="reportes-grupo"><?= e($grupo) ?></h2>
    <div class="reportes">
        <?php foreach ($lista as $r) { ?>
            <article class="tarjeta-tabla reporte" id="reporte-<?= e($r['clave']) ?>">
                <header class="reporte-cabecera">
                    <span class="reporte-icono"><i class="bi <?= e($r['icono']) ?>" aria-hidden="true"></i></span>
                    <div>
                        <h3><?= $r['numero'] ?>. <?= e($r['titulo']) ?></h3>
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
                        <table class="table align-middle tabla-reporte" data-titulo="<?= e($r['titulo']) ?>" data-archivo="reporte_<?= e(str_replace('-', '_', $r['clave'])) ?>"
                               data-usuario="<?= e($sesion['usuario']) ?>">
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
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
