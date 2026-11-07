<?php
/* VISTA - Reporte de lecturas de sensores (DataTables: Excel, PDF, CSV e imprimir).
   Arriba, un resumen por sensor del período elegido; abajo, cada lectura con su estado. */
$titulo = 'Reporte de sensores';
$seccion = 'sensores-reporte';
$estilos = ['https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.css'];
$scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
    'https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-html5-3.1.2/b-print-3.1.2/datatables.min.js',
    'js/reporte_sensores.js',
];
$nombreSensor = 'Todos los sensores';
foreach ($sensores as $s) {
    if ((int)$s['id_sensor'] === $idSensor) {
        $nombreSensor = $s['zona'] . ' (' . $s['codigo'] . ')';
    }
}
$periodo = fecha($desde, 'd/m/Y') . ' al ' . fecha($hasta, 'd/m/Y');
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">CONTROL AMBIENTAL</span>
        <h1>Reporte de sensores</h1>
        <p>Lecturas de temperatura y humedad guardadas por la API de sensores, para revisar y exportar.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('sensor')) ?>"><i class="bi bi-thermometer-snow"></i> Ver monitor en vivo</a>
</section>

<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?> Se muestran las últimas 24 horas.</div><?php } ?>

<form class="tarjeta mb-3" method="GET" action="index.php">
    <input type="hidden" name="c" value="sensor">
    <input type="hidden" name="a" value="reporte">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label small" for="sensor">Sensor</label>
            <select class="form-select" id="sensor" name="sensor">
                <option value="0">Todos los sensores</option>
                <?php foreach ($sensores as $s) { ?>
                    <option value="<?= (int)$s['id_sensor'] ?>" <?= (int)$s['id_sensor'] === $idSensor ? 'selected' : '' ?>><?= e($s['zona'] . ' · ' . $s['tipo_zona'] . ' (' . $s['codigo'] . ')') ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="desde">Desde</label>
            <input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>" required>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="hasta">Hasta</label>
            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" required>
        </div>
        <div class="col-12 col-md-2">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="alertas" name="alertas" value="1" <?= $soloAlertas ? 'checked' : '' ?>>
                <label class="form-check-label small" for="alertas">Solo fuera de rango</label>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Ver</button>
        </div>
    </div>
</form>

<section class="tarjeta-tabla mb-3">
    <h2 class="tarjeta-titulo"><i class="bi bi-clipboard-data" aria-hidden="true"></i> Resumen del <?= e($periodo) ?></h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Sensor</th><th>Lecturas</th><th>Temperatura mín. / prom. / máx.</th><th>Humedad mín. / prom. / máx.</th><th>Fuera de rango</th></tr></thead>
            <tbody>
            <?php foreach ($resumen as $r) { $lecturasSensor = (int)$r['lecturas']; $fuera = (int)$r['fuera_rango']; ?>
                <tr>
                    <td><strong><?= e($r['zona']) ?></strong> · <?= e($r['tipo_zona']) ?><br><small class="text-secondary"><?= e($r['codigo']) ?> · rango <?= e((float)$r['temp_min'] . ' a ' . (float)$r['temp_max']) ?> °C, <?= e((float)$r['hum_min'] . ' a ' . (float)$r['hum_max']) ?> %</small></td>
                    <td><?= $lecturasSensor ?></td>
                    <?php if ($lecturasSensor === 0) { ?>
                        <td colspan="3" class="text-secondary">Sin lecturas en este período.</td>
                    <?php } else { ?>
                        <td><?= e((float)$r['temp_minima'] . ' / ' . (float)$r['temp_promedio'] . ' / ' . (float)$r['temp_maxima']) ?> °C</td>
                        <td><?= e((float)$r['hum_minima'] . ' / ' . (float)$r['hum_promedio'] . ' / ' . (float)$r['hum_maxima']) ?> %</td>
                        <td><span class="estado <?= $fuera ? 'alerta' : 'activo' ?>"><?= $fuera ?> (<?= round($fuera * 100 / $lecturasSensor, 1) ?> %)</span></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($recortado) { ?>
    <div class="alert alert-warning" role="status">Hay más de <?= (int)$limite ?> lecturas en este período: se muestran las <?= (int)$limite ?> más recientes. Elegí un período más corto o un solo sensor para verlas todas.</div>
<?php } ?>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <!-- La tabla se convierte en reporte con js/reporte_sensores.js (DataTables) -->
        <table class="table align-middle" id="tablaSensores" style="width:100%"
               data-titulo="Reporte de sensores - <?= e($nombreSensor) ?><?= $soloAlertas ? ' (solo fuera de rango)' : '' ?>"
               data-periodo="<?= e($periodo) ?>"
               data-usuario="<?= e($sesion['usuario']) ?>">
            <thead><tr><th>Fecha y hora</th><th>Sensor</th><th>Zona</th><th>Temperatura (°C)</th><th>Humedad (%)</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($lecturas as $l) { $normal = $l['estado'] === 'Normal'; ?>
                <tr>
                    <td data-order="<?= e($l['fecha_hora']) ?>"><?= e(fecha($l['fecha_hora'], 'd/m/Y H:i:s')) ?></td>
                    <td><?= e($l['codigo']) ?></td>
                    <td><?= e($l['zona'] . ' · ' . $l['tipo_zona']) ?></td>
                    <td><?= e((float)$l['temperatura']) ?></td>
                    <td><?= e((float)$l['humedad']) ?></td>
                    <td data-exportar="<?= e($l['estado']) ?>"><span class="estado <?= $normal ? 'activo' : 'alerta' ?>"><?= e($l['estado']) ?></span></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
