<?php
/* VISTA - Pantalla de conteo (js/conteo.js)
   Cada cantidad se guarda al momento. En el conteo ciego el operario
   no ve el stock del sistema. El administrador cierra y ajusta. */
$titulo = 'Conteo #' . (int)$conteo['id_conteo'];
$seccion = 'conteo';
$scripts = ['js/vendor/html5-qrcode.min.js', 'js/escaner.js', 'js/conteo.js'];
$enCurso = $conteo['estado'] === 'En curso';
$total = count($items);
$contados = count(array_filter($items, fn($i) => $i['cantidad_contada'] !== null));
$conDiferencia = array_filter($items, fn($i) => $i['cantidad_contada'] !== null && (int)$i['cantidad_contada'] !== (int)$i['stock_sistema']);
$claseEstado = ['En curso' => 'en-proceso', 'Cerrado' => 'completado', 'Cancelado' => ''];
require RAIZ . '/view/layout/cabecera.php';
?>
<a href="<?= e(url('conteo')) ?>" class="volver"><i class="bi bi-arrow-left"></i> Volver a conteos</a>
<section class="encabezado">
    <span class="encabezado-etiqueta">CONTEO DE INVENTARIO #<?= (int)$conteo['id_conteo'] ?></span>
    <h1><?= e($conteo['zona']) ?> <small class="text-secondary fs-5"><?= e($conteo['tipo']) ?></small></h1>
    <p>Empezó el <?= e(fecha($conteo['fecha_inicio'])) ?> · cuenta <?= e($conteo['asignado'] ?: $conteo['creador']) ?>
        · <span class="estado <?= $claseEstado[$conteo['estado']] ?? '' ?>"><?= e($conteo['estado']) ?></span>
        <?= $conteo['fecha_cierre'] ? ' el ' . e(fecha($conteo['fecha_cierre'])) : '' ?></p>
    <?php if ($conteo['observaciones']) { ?><p class="text-secondary"><?= e($conteo['observaciones']) ?></p><?php } ?>
</section>

<?php if ($mensaje !== '') { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>

<div id="conteo" data-id="<?= (int)$conteo['id_conteo'] ?>" data-csrf="<?= e($csrf) ?>" data-url-guardar="<?= e(url('conteo', 'guardar')) ?>"
     data-en-curso="<?= $enCurso ? '1' : '0' ?>" data-ver-sistema="<?= $verSistema ? '1' : '0' ?>">

    <section class="progreso-card">
        <div class="progreso-top"><span>Productos contados</span><strong><span id="cantContados"><?= $contados ?></span> de <?= $total ?></strong></div>
        <div class="progress" role="progressbar" aria-label="Avance del conteo" aria-valuemin="0" aria-valuemax="<?= $total ?>" aria-valuenow="<?= $contados ?>">
            <div class="progress-bar" id="barraConteo" style="width: <?= $total ? round($contados * 100 / $total) : 0 ?>%"></div>
        </div>
    </section>

    <?php if ($enCurso) { ?>
        <section class="indicaciones-card">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>
                <strong>Cómo contar</strong>
                <p>Andá lugar por lugar (están en el orden del recorrido). Escaneá el producto: con «Cada lectura suma 1» activado, cada vez que lo escaneás se suma una unidad. También podés escribir el total. Si un lugar está vacío, poné 0. Todo se guarda solo.</p>
            </div>
        </section>
        <div class="barra-conteo">
            <button type="button" class="btn btn-primary btn-lg" id="btnEscanearConteo"><i class="bi bi-upc-scan" aria-hidden="true"></i> Escanear producto</button>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="sumarLectura" checked>
                <label class="form-check-label" for="sumarLectura">Cada lectura suma 1</label>
            </div>
            <div class="form-check form-switch mb-0 ms-md-auto">
                <input class="form-check-input" type="checkbox" role="switch" id="soloFaltan">
                <label class="form-check-label" for="soloFaltan">Ver solo los que faltan</label>
            </div>
        </div>
        <div id="avisoConteo" class="alert d-none mt-2" role="status" aria-live="polite"></div>
    <?php } ?>

    <div class="tarjeta-tabla mt-3">
        <div class="table-responsive">
            <table class="table align-middle tabla-conteo">
                <thead><tr><th>Lugar</th><th>Producto</th><?= $verSistema ? '<th>Sistema</th>' : '' ?><th>Contado</th><?= $verSistema ? '<th>Diferencia</th>' : '' ?></tr></thead>
                <tbody>
                <?php foreach ($items as $i) {
                    $id = (int)$i['id_producto'];
                    $contado = $i['cantidad_contada'];
                    $dif = $contado !== null ? (int)$contado - (int)$i['stock_sistema'] : null;
                ?>
                    <tr id="fila-<?= $id ?>" data-producto="<?= $id ?>" data-codigo="<?= e($i['codigo_barras']) ?>" data-nombre="<?= e($i['Nombre']) ?>"
                        data-sistema="<?= $verSistema ? (int)$i['stock_sistema'] : '' ?>" class="<?= $contado !== null ? 'fila-contada' : '' ?>">
                        <td class="text-nowrap"><strong class="codigo"><?= e($i['pasillo'] . '-' . $i['estante'] . '-' . $i['posicion']) ?></strong></td>
                        <td><div class="producto-con-foto"><?= miniaturaProducto($i['foto'], $i['Nombre']) ?><span><strong><?= e($i['Nombre']) ?></strong><small class="d-block text-secondary"><?= e($i['codigo_barras']) ?></small></span></div></td>
                        <?php if ($verSistema) { ?><td class="celda-sistema" data-etiqueta="Sistema"><?= (int)$i['stock_sistema'] ?></td><?php } ?>
                        <td class="celda-contado">
                            <?php if ($enCurso) { ?>
                                <div class="input-group input-group-sm contador">
                                    <input type="number" class="form-control inp-contado" min="0" max="1000000" inputmode="numeric" value="<?= $contado !== null ? (int)$contado : '' ?>" placeholder="—" aria-label="Cantidad contada de <?= e($i['Nombre']) ?>">
                                    <span class="input-group-text estado-guardado" aria-hidden="true"><i class="bi <?= $contado !== null ? 'bi-check2 text-success' : 'bi-dash' ?>"></i></span>
                                </div>
                            <?php } else { ?>
                                <?= $contado !== null ? (int)$contado : '<span class="text-secondary">No se contó</span>' ?>
                            <?php } ?>
                        </td>
                        <?php if ($verSistema) { ?>
                            <td class="celda-diferencia"><?php if ($dif !== null && $dif !== 0) { ?><span class="badge <?= $dif > 0 ? 'bg-info' : 'bg-danger' ?>"><?= $dif > 0 ? '+' . $dif : $dif ?></span><?php } elseif ($dif === 0) { ?><span class="text-success"><i class="bi bi-check-circle" aria-label="coincide"></i></span><?php } ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($enCurso && $esAdmin) { ?>
    <div class="tarjeta mt-3 cierre-conteo">
        <h2 class="tarjeta-titulo"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Cerrar el conteo</h2>
        <p class="tarjeta-texto">Al cerrar, el stock de cada producto contado con diferencia se ajusta a lo que se contó (y queda en su historial). Los productos sin contar no se tocan.
            Si mientras se contaba salió algún pedido, ese retiro se respeta.</p>
        <p id="resumenCierre" class="fw-semibold"><?= $contados ?> de <?= $total ?> contados · <?= count($conDiferencia) ?> con diferencia</p>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="<?= e(url('conteo', 'cerrar')) ?>" id="formCerrarConteo">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$conteo['id_conteo'] ?>">
                <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check2-all"></i> Cerrar y ajustar el stock</button>
            </form>
            <?php if (!empty($conteo['ci_asignado'])) { ?>
                <form method="POST" action="<?= e(url('conteo', 'quitar')) ?>"
                      onsubmit="return confirm(<?= e(json_encode('¿Quitarle el conteo a ' . $conteo['asignado'] . '? Lo seguís vos y lo que ya contó se conserva.', JSON_UNESCAPED_UNICODE)) ?>);">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$conteo['id_conteo'] ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-lg"><i class="bi bi-person-dash"></i> Quitar al operario</button>
                </form>
            <?php } ?>
            <form method="POST" action="<?= e(url('conteo', 'cancelar')) ?>" onsubmit="return confirm('¿Cancelar el conteo? Lo contado se descarta y el stock no se toca.');">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$conteo['id_conteo'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-lg"><i class="bi bi-x-circle"></i> Cancelar conteo</button>
            </form>
        </div>
    </div>
<?php } elseif ($enCurso) { ?>
    <p class="nota">Cuando termines, avisale al administrador: él revisa las diferencias y cierra el conteo.</p>
<?php } ?>
<?php require RAIZ . '/view/compartido/escaner.php'; ?>
<?php require RAIZ . '/view/layout/pie.php'; ?>
