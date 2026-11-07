<?php
/* VISTA - Detalle del pedido (operario) */
$titulo = 'Pedido #' . $idPedido;
$seccion = 'mis-pedidos';
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('picking')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a mis pedidos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">DETALLE DEL PEDIDO</span>
    <h1>Pedido #<?= $idPedido ?></h1>
    <p>Revisá la información antes de comenzar la preparación.</p>
</section>

<div class="row g-4">
    <section class="col-lg-8">
        <div class="tarjeta h-100">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <h2 class="tarjeta-titulo mb-0">Información del pedido</h2>
                <span class="estado en-proceso"><?= e($pedido['estado']) ?></span>
            </div>
            <div class="datos-grilla">
                <div><span>Cliente</span><strong><?= e($pedido['cliente']) ?></strong></div>
                <div><span>Fecha de creación</span><strong><?= e(fecha($pedido['fecha_creacion'])) ?></strong></div>
                <div><span>Productos diferentes</span><strong><?= (int)$pedido['productos_diferentes'] ?></strong></div>
                <div><span>Unidades totales</span><strong><?= (int)$pedido['total_productos'] ?></strong></div>
            </div>
            <?php if ($pedido['estado'] === 'Por validar') { ?>
                <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-hourglass-split"></i> Terminaste el picking. El administrador está validando que lo retirado coincida con lo solicitado.</div>
            <?php } ?>
            <?php if (in_array($pedido['estado'], ['Pendiente', 'En Picking'], true)) { ?>
                <a href="<?= e(url('picking', 'hojaRuta', ['id' => $idPedido])) ?>" class="btn btn-success btn-lg w-100 mt-4"><?= $pedido['estado'] === 'En Picking' ? 'Continuar picking' : 'Comenzar picking' ?> <i class="bi bi-arrow-right"></i></a>
            <?php } ?>
        </div>
    </section>
    <aside class="col-lg-4">
        <div class="tarjeta h-100">
            <h2 class="tarjeta-titulo">Entrega</h2>
            <p class="text-secondary mb-1">Dirección del cliente</p>
            <strong><?= e($pedido['direccion'] ?: 'Sin dirección registrada') ?></strong>
            <hr>
            <p class="text-secondary mb-1">Estado</p>
            <strong><?= e($pedido['estado']) ?></strong>
        </div>
    </aside>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
