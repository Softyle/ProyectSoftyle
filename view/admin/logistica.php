<?php
/* VISTA - Gestión del centro logístico: zonas o ubicaciones (misma pantalla) */
$titulo = $esZona ? 'Zonas' : 'Ubicaciones';
$seccion = $tipo;
$entidad = $esZona ? 'zona' : 'ubicación';
$urlPagina = url('logistica', 'index', ['tipo' => $tipo]);
$scripts = $esZona ? ['js/zonas.js'] : [];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">CENTRO LOGÍSTICO</span>
    <h1>Gestión de <?= e($titulo) ?></h1>
    <p>Administrá la distribución de tu depósito.</p>
</section>

<nav class="filtros" aria-label="Centro logístico">
    <a class="<?= $esZona ? 'activo' : '' ?>" href="<?= e(url('logistica', 'index', ['tipo' => 'zonas'])) ?>">Zonas</a>
    <a class="<?= !$esZona ? 'activo' : '' ?>" href="<?= e(url('logistica', 'index', ['tipo' => 'ubicaciones'])) ?>">Ubicaciones</a>
    <a href="<?= e(url('producto')) ?>">Productos</a>
</nav>

<?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php } ?>
<?php if ($mensaje) { ?><div class="alert alert-success" role="status"><?= e($mensaje) ?></div><?php } ?>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><?= $editar ? 'Editar' : 'Agregar' ?> <?= e($entidad) ?></h2>
            <form method="post" action="<?= e($urlPagina) ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'guardar' ?>">
                <input type="hidden" name="id" value="<?= (int)($editar[$clave] ?? 0) ?>">
                <?php foreach ($campos as $i => $campo) { ?>
                    <div class="mb-3">
                        <label class="form-label" for="<?= e($campo) ?>"><?= e($etiquetas[$i]) ?></label>
                        <?php if (in_array($campo, ['id_deposito', 'id_zona'], true)) { ?>
                            <select class="form-select" name="<?= e($campo) ?>" id="<?= e($campo) ?>" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($opciones[$campo] as $o) { ?>
                                    <option value="<?= e($o[$campo]) ?>" <?= (string)($valoresForm[$campo] ?? '') === (string)$o[$campo] ? 'selected' : '' ?>><?= e($o['nombre']) ?></option>
                                <?php } ?>
                            </select>
                        <?php } elseif ($esZona && $campo === 'nombre') {
                            // "Zona" + una letra. Los nombres que ya usa otra zona se avisan al escribir (js/zonas.js)
                            $usados = array_column(array_filter($filas, fn($z) => (int)$z['id_zona'] !== (int)($editar['id_zona'] ?? 0)), 'nombre'); ?>
                            <input class="form-control" id="nombre" name="nombre" type="text" maxlength="6" autocomplete="off"
                                   pattern="[Zz]ona [A-Za-z]" title='Escribí "Zona" y una letra, por ejemplo Zona G.' placeholder="Zona G"
                                   data-usados="<?= e(json_encode(array_values($usados))) ?>"
                                   value="<?= e($valoresForm['nombre'] ?? '') ?>" required>
                            <small class="text-secondary">Formato: "Zona" y una letra (Zona A, Zona B…). No se puede repetir.</small>
                        <?php } elseif ($campo === 'tipo') { ?>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($tiposZona as $t) { ?>
                                    <option value="<?= e($t) ?>" <?= ($valoresForm['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php } ?>
                            </select>
                        <?php } elseif ($campo === 'disponible') { ?>
                            <select class="form-select" name="disponible" id="disponible">
                                <option value="1" <?= ($valoresForm && !$valoresForm['disponible']) ? '' : 'selected' ?>>Sí</option>
                                <option value="0" <?= ($valoresForm && !$valoresForm['disponible']) ? 'selected' : '' ?>>No</option>
                            </select>
                        <?php } elseif (isset($formatosUbicacion[$campo])) { [$regex, $mensajeFormato, $ejemplo] = $formatosUbicacion[$campo]; ?>
                            <!-- Código con formato fijo: se pasa a mayúsculas mientras se escribe -->
                            <input class="form-control codigo" id="<?= e($campo) ?>" name="<?= e($campo) ?>" type="text" maxlength="3"
                                   pattern="<?= e(trim($regex, '/^$')) ?>" title="<?= e($mensajeFormato) ?>" placeholder="<?= e($ejemplo) ?>"
                                   oninput="this.value = this.value.toUpperCase()" autocomplete="off"
                                   value="<?= e($valoresForm[$campo] ?? '') ?>" required>
                            <small class="text-secondary">Formato: <?= e($ejemplo) ?><?= $campo === 'estante' ? ' · E00 = sector de piso (productos voluminosos)' : '' ?></small>
                        <?php } else { ?>
                            <input class="form-control" id="<?= e($campo) ?>" name="<?= e($campo) ?>"
                                   type="<?= $campo === 'capacidad_total' ? 'number' : 'text' ?>" <?= $campo === 'capacidad_total' ? 'min="0"' : '' ?>
                                   maxlength="100" value="<?= e($valoresForm[$campo] ?? ($campo === 'capacidad_total' ? '0' : '')) ?>" required>
                        <?php } ?>
                    </div>
                <?php } ?>
                <button class="btn btn-success" type="submit"><i class="bi bi-save"></i> Guardar</button>
                <?php if ($editar) { ?><a class="btn btn-outline-secondary" href="<?= e($urlPagina) ?>">Cancelar</a><?php } ?>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="tarjeta-tabla">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><?php foreach ($etiquetas as $etiqueta) { ?><th><?= e($etiqueta) ?></th><?php } ?><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $r) { ?>
                        <tr>
                            <?php foreach ($campos as $campo) { ?>
                                <td><?= e(in_array($campo, ['id_zona', 'id_deposito'], true) ? $r['relacionado'] : ($campo === 'disponible' ? ($r[$campo] ? 'Sí' : 'No') : $r[$campo])) ?>
                                    <?php if ($campo === 'estante' && Ubicacion::esSectorPiso($r[$campo])) { ?><span class="etiqueta-piso">Sector de piso</span><?php } ?></td>
                            <?php } ?>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-primary" href="<?= e(url('logistica', 'index', ['tipo' => $tipo, 'editar' => $r[$clave]])) ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= e($urlPagina) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este registro?');">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$r[$clave] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php } if (!$filas) { ?>
                        <tr><td colspan="6" class="text-center">No hay registros todavía.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
