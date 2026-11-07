<?php
/* VISTA - Perfil de un operario (vista del administrador) */
$titulo = 'Perfil del operario';
$seccion = 'operarios';
$foto = fotoPerfil($operario['foto_perfil']);
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('operario')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a operarios</a>

<section class="perfil-cabecera">
    <div class="perfil-foto">
        <?php if ($foto) { ?>
            <img src="<?= e($foto) ?>" alt="Foto de perfil">
        <?php } else { ?>
            <i class="bi bi-person" aria-hidden="true"></i>
        <?php } ?>
    </div>
    <div class="perfil-info">
        <span class="encabezado-etiqueta">PERFIL DEL OPERARIO</span>
        <h1><?= e($operario['nombre']) ?></h1>
        <p>Usuario: <strong><?= e($operario['usuario']) ?></strong></p>
        <?= strtolower($operario['estado']) === 'activo' ? '<span class="estado activo">Activo</span>' : '<span class="estado">Inactivo</span>' ?>
    </div>
</section>

<section class="indicadores indicadores-3">
    <div class="indicador"><i class="bi bi-clipboard-check"></i><div><span>Pedidos completados</span><strong><?= $pedidos ?></strong></div></div>
    <div class="indicador"><i class="bi bi-clock"></i><div><span>Tiempo trabajado</span><strong><?= e($tiempo) ?></strong></div></div>
    <div class="indicador"><i class="bi bi-stopwatch"></i><div><span>Promedio por pedido</span><strong><?= e($promedio) ?></strong></div></div>
</section>

<section class="tarjeta-tabla">
    <h2 class="tarjeta-titulo">Historial de trabajo</h2>
    <p class="tarjeta-texto">Registro de jornadas del operario.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Tiempo</th></tr></thead>
            <tbody>
            <?php if (!$jornadas) { ?>
                <tr><td colspan="4" class="text-center">No hay jornadas registradas.</td></tr>
            <?php } ?>
            <?php foreach ($jornadas as $j) { ?>
                <tr>
                    <td><?= e($j['fecha']) ?></td>
                    <td><?= e($j['hora_inicio']) ?></td>
                    <td><?= $j['hora_fin'] !== null ? e($j['hora_fin']) : 'En curso' ?></td>
                    <td><?= $j['tiempo'] !== null ? e($j['tiempo']) : '-' ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require RAIZ . '/view/layout/pie.php'; ?>
