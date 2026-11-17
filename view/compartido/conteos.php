<?php
/* VISTA - Conteos de inventario (administrador: todos; operario: los asignados) */
$titulo = 'Conteo de inventario';
$seccion = 'conteo';
$esAdminVista = $sesion['rol'] === 'admin';
$claseEstado = ['En curso' => 'en-proceso', 'Cerrado' => 'completado', 'Cancelado' => ''];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado encabezado-con-acciones">
    <div>
        <span class="encabezado-etiqueta"><?= $esAdminVista ? 'ABASTECIMIENTO' : 'DEPÓSITO' ?></span>
        <h1><?= $esAdminVista ? 'Conteo de inventario' : 'Conteos de inventario' ?></h1>
        <p><?= $esAdminVista
            ? 'Se cuenta lo que hay en una zona y se compara con el sistema. Al cerrar el conteo, las diferencias se ajustan solas.'
            : 'Las zonas que el administrador te pidió contar. Escaneá cada producto y anotá cuántos hay.' ?></p>
    </div>
    <?php if ($esAdminVista) { ?>
        <a href="<?= e(url('conteo', 'nuevo')) ?>" class="btn btn-success btn-lg"><i class="bi bi-plus-circle"></i> Nuevo conteo</a>
    <?php } ?>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<?php if (!$conteos) { ?>
    <div class="vacio"><i class="bi bi-clipboard-data" aria-hidden="true"></i><strong><?= $esAdminVista ? 'Todavía no hay conteos' : 'No tenés conteos asignados' ?></strong>
        <div><?= $esAdminVista ? 'Empezá uno con «Nuevo conteo»: elegís la zona y quién la cuenta.' : 'Cuando el administrador te asigne una zona para contar, aparece acá.' ?></div></div>
<?php } else { ?>
<div class="lista-pedidos">
    <?php foreach ($conteos as $c) {
        $total = (int)$c['productos'];
        $hechos = (int)$c['contados'];
        $enCurso = $c['estado'] === 'En curso';
    ?>
        <article class="pedido-fila <?= $enCurso ? 'en-picking' : '' ?>">
            <div><span class="dato-etiqueta">CONTEO</span><h3>#<?= (int)$c['id_conteo'] ?></h3><span class="dato-etiqueta"><?= e(fecha($c['fecha_inicio'])) ?></span></div>
            <div><span class="dato-etiqueta">ZONA</span><div><strong><?= e($c['zona']) ?></strong> <small class="text-secondary"><?= e($c['tipo']) ?></small></div>
                <div class="dato-etiqueta mt-1">Cuenta: <?= e($c['asignado'] ?: $c['creador']) ?><?= $c['ciego'] ? ' · conteo ciego' : '' ?></div></div>
            <div>
                <span class="estado <?= $claseEstado[$c['estado']] ?? '' ?>"><?= e($c['estado']) ?></span>
                <span class="resumen-progreso" title="<?= $hechos ?> de <?= $total ?> productos contados"><span style="width: <?= $total ? round($hechos * 100 / $total) : 0 ?>%"></span></span>
                <div class="dato-etiqueta"><?= $hechos ?> de <?= $total ?> contados<?= $esAdminVista && (int)$c['diferencias'] > 0 ? ' · <span class="text-danger">' . (int)$c['diferencias'] . ' con diferencia</span>' : '' ?></div>
            </div>
            <a class="btn <?= $enCurso ? 'btn-primary' : 'btn-outline-secondary' ?> btn-lg" href="<?= e(url('conteo', 'contar', ['id' => $c['id_conteo']])) ?>"><?= $enCurso ? 'Contar' : 'Ver' ?> <i class="bi bi-arrow-right"></i></a>
        </article>
    <?php } ?>
</div>
<?php } ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
