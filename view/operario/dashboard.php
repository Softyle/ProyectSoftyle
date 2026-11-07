<?php
/* VISTA - Panel del operario: "Mis pedidos" */
$titulo = 'Mis pedidos';
$seccion = 'mis-pedidos';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">PANEL DEL OPERARIO</span>
    <h1>Mis pedidos</h1>
    <p>Gestioná los pedidos que te fueron asignados y completá el proceso de picking.</p>
</section>

<section class="indicadores">
    <div class="indicador"><i class="bi bi-clipboard-check"></i><div><span>Pedidos asignados</span><strong><?= $asignados ?></strong></div></div>
    <div class="indicador"><i class="bi bi-hourglass-split"></i><div><span>Pendientes</span><strong><?= $pendientes ?></strong></div></div>
    <div class="indicador"><i class="bi bi-box-seam"></i><div><span>En picking</span><strong><?= $enPicking ?></strong></div></div>
    <div class="indicador indicador-verde"><i class="bi bi-check-circle"></i><div><span>Completados</span><strong><?= $completados ?></strong></div></div>
</section>

<section>
    <div class="titulo-seccion">
        <h2>Pedidos asignados</h2>
        <p>Primero aparecen los pedidos que ya están en preparación. Los que terminaste quedan esperando la validación del administrador.</p>
    </div>
    <div class="lista-pedidos">
        <?php if (!$pedidos) { ?>
            <div class="vacio"><i class="bi bi-clipboard-check"></i><strong>No tenés pedidos pendientes</strong><div>Cuando el administrador te asigne uno, aparecerá acá.</div></div>
        <?php } ?>
        <?php foreach ($pedidos as $p) { ?>
            <article class="pedido-fila <?= $p['estado'] === 'En Picking' ? 'en-picking' : '' ?>">
                <div><span class="dato-etiqueta">PEDIDO</span><h3>#<?= (int)$p['id'] ?></h3><span class="dato-etiqueta"><?= e(fecha($p['fecha'])) ?></span></div>
                <div><span class="dato-etiqueta">CLIENTE</span><div><strong><?= e($p['cliente']) ?></strong></div></div>
                <div>
                    <span class="estado <?= $p['estado'] === 'En Picking' ? 'en-proceso' : 'pendiente' ?>"><?= $p['estado'] === 'Por validar' ? 'Esperando validación del administrador' : e($p['estado']) ?></span>
                    <div class="dato-etiqueta mt-2"><?= (int)$p['total_productos'] ?> unidades · <?= (int)$p['productos_diferentes'] ?> productos</div>
                </div>
                <a class="btn btn-primary btn-lg" href="<?= e(url('picking', 'pedido', ['id' => $p['id']])) ?>"><?= $p['estado'] === 'En Picking' ? 'Continuar' : 'Ver pedido' ?> <i class="bi bi-arrow-right"></i></a>
            </article>
        <?php } ?>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
