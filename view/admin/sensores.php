<?php
/* VISTA - Monitor de sensores: cadena de frío en tiempo real (js/sensores.js cada 5 segundos) */
$titulo = 'Monitor de sensores';
$seccion = 'sensores';
$scripts = ['js/sensores.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">CONTROL AMBIENTAL</span>
    <h1>Monitor de sensores</h1>
    <p>Temperatura y humedad en tiempo real para asegurar la cadena de frío. Los datos son simulados por la API del sistema.</p>
</section>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="en-vivo" aria-live="polite"><span class="punto-vivo" aria-hidden="true"></span> Última lectura: <strong id="actualizado">cargando...</strong></span>
    <a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('sensor', 'reporte')) ?>"><i class="bi bi-file-earmark-bar-graph"></i> Reporte y exportación</a>
    <button type="button" id="btnPausa" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pause-fill"></i> Pausar</button>
</div>
<div id="avisoSensores" class="alert alert-danger d-none" role="alert"></div>

<div id="sensores" class="row g-3 mb-4" data-url="<?= e(url('sensor', 'datos')) ?>"></div>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Alertas de cadena de frío</h2>
    <p class="tarjeta-texto">Se registran cuando una zona refrigerada sale del rango permitido.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Detalle</th></tr></thead>
            <tbody id="tablaAlertas"><tr><td colspan="2">Cargando...</td></tr></tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
