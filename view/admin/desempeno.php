<?php
/* VISTA - Desempeño de operarios (gamificación): ranking e insignias */
$titulo = 'Desempeño de operarios';
$seccion = 'desempeno';
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">GAMIFICACIÓN Y EFICIENCIA</span>
    <h1>Desempeño de operarios</h1>
    <p>Ranking por pedidos completados y precisión, con las insignias que ganó cada operario.</p>
</section>

<div class="tarjeta-tabla">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Puesto</th><th>Operario</th><th>Pedidos completados</th><th>Unidades</th><th>Precisión</th><th>Tiempo promedio</th><th>Insignias</th></tr></thead>
            <tbody>
            <?php foreach ($ranking as $r) {
                $puesto = (int)$r['posicion'];
                $colorPuesto = [1 => 'oro', 2 => 'plata', 3 => 'bronce'][$puesto] ?? 'gris'; ?>
                <tr>
                    <td><span class="etiqueta-<?= $colorPuesto ?>"><?= $puesto ?>º</span></td>
                    <td>
                        <strong><?= e($r['operario']) ?></strong>
                        <?php if (strtolower($r['Estado']) !== 'activo') { ?><span class="badge bg-secondary">Inactivo</span><?php } ?>
                        <br><a class="small" href="<?= e(url('operario', 'perfil', ['id' => $r['ci_operario']])) ?>">Ver perfil</a>
                    </td>
                    <td><?= (int)$r['pedidos_completados'] ?></td>
                    <td><?= (int)$r['unidades_recogidas'] ?></td>
                    <td><?= $r['precision_pct'] === null ? '—' : e($r['precision_pct']) . '%' ?>
                        <small class="d-block text-secondary"><?= (int)$r['retiros'] ?> retiros, <?= (int)$r['errores'] ?> errores</small></td>
                    <td><?= $r['minutos_promedio'] === null ? '—' : e($r['minutos_promedio']) . ' min' ?></td>
                    <td>
                        <?php foreach ($insignias[$r['ci_operario']] ?? [] as $i) { ?>
                            <span class="etiqueta-<?= e(strtolower($i['nivel'])) ?> mb-1" title="<?= e($i['nombre'] . ' (' . $i['nivel'] . ') - ' . fecha($i['fecha_obtenido'], 'd/m/Y')) ?>">
                                <i class="bi <?= e($i['icono']) ?>" aria-hidden="true"></i> <?= e($i['nombre']) ?>
                            </span>
                        <?php } ?>
                        <small class="d-block text-secondary"><?= count($insignias[$r['ci_operario']] ?? []) ?> de <?= $totalLogros ?></small>
                    </td>
                </tr>
            <?php } if (!$ranking) { ?>
                <tr><td colspan="7" class="text-center">No hay operarios registrados.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<p class="nota">Precisión = retiros correctos sobre retiros + errores de cantidad o de ubicación. Tiempo = desde que el operario abre la hoja de ruta hasta su último retiro, en pedidos completados.</p>
<?php require RAIZ . '/view/layout/pie.php'; ?>
