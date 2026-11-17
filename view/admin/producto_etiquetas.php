<?php
/* VISTA - Hoja de etiquetas con el código de barras de cada producto (para imprimir y pegar en la estantería) */
$titulo = 'Etiquetas de productos';
$seccion = 'productos';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('producto')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a productos</a>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta">INVENTARIO</span>
        <h1>Etiquetas de productos</h1>
        <p>Código de barras de cada producto y dónde se guarda, ordenados por zona. Imprimilas y pegalas en la estantería o en la caja: el operario las lee con el celular durante el picking.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
</section>

<div class="etiquetas">
    <?php foreach ($productos as $p) {
        $svg = CodigoBarras::svg((string)$p['codigo_barras']);
    ?>
        <article class="etiqueta">
            <strong class="etiqueta-nombre"><?= e($p['Nombre']) ?></strong>
            <span class="etiqueta-ubicacion"><?= e($p['zona'] . ' · ' . $p['pasillo'] . '-' . $p['estante'] . '-' . $p['posicion']) ?></span>
            <?php if ($svg !== '') { ?>
                <?= $svg /* SVG generado por CodigoBarras: solo dígitos, sin datos del usuario */ ?>
            <?php } elseif ((string)$p['codigo_barras'] !== '') { ?>
                <span class="etiqueta-numero"><?= e($p['codigo_barras']) ?></span>
                <small class="text-secondary">Código de fábrica: leelo del paquete.</small>
            <?php } else { ?>
                <small class="text-danger">Sin código de barras</small>
            <?php } ?>
        </article>
    <?php } ?>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
