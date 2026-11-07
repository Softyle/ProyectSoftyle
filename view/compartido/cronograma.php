<?php
/* VISTA COMPARTIDA - Cronograma de despachos (administrador y operario).
   La página carga vacía y js/cronograma.js pide los datos por AJAX;
   el controlador ya los devuelve filtrados según el rol. */
$titulo = $esAdmin ? 'Cronograma de despachos' : 'Mi cronograma';
$seccion = 'cronograma';
$scripts = ['js/cronograma.js'];
require RAIZ . '/view/layout/cabecera.php';
?>
<section class="encabezado">
    <span class="encabezado-etiqueta">VISUALIZACIÓN DE OPERACIONES</span>
    <h1>Cronograma de despachos</h1>
    <p><?= $esAdmin
        ? 'Todos los pedidos del depósito, por fecha de despacho y zona de almacenamiento.'
        : 'Tus pedidos asignados, por fecha de despacho y zona donde tenés que recoger.' ?></p>
</section>

<!-- Filtros: al cambiarlos se vuelve a pedir el cronograma sin recargar la página -->
<form id="filtrosCronograma" class="tarjeta mb-3" data-api="<?= e(url('cronograma', 'datos')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small" for="desde">Desde</label>
            <input class="form-control" type="date" id="desde" name="desde" value="<?= e(date('Y-m-d', strtotime('-7 days'))) ?>" required>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="hasta">Hasta</label>
            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e(date('Y-m-d', strtotime('+14 days'))) ?>" required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label small" for="zona">Zona de almacenamiento</label>
            <select class="form-select" id="zona" name="zona">
                <option value="0">Todas las zonas</option>
                <?php foreach ($zonas as $z) { ?>
                    <option value="<?= (int)$z['id_zona'] ?>"><?= e($z['nombre'] . ' (' . $z['tipo'] . ')') ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Ver</button>
        </div>
    </div>
</form>

<!-- Referencia de colores de prioridad -->
<p class="leyenda">
    <span class="etiqueta-gris">Pendiente</span>
    <span class="etiqueta-plata">En proceso</span>
    <span class="etiqueta-bronce">Completado</span>
    <span class="text-secondary">Cada pedido aparece en su fecha de entrega programada; si no tiene, en la fecha en que se creó.</span>
</p>

<div id="avisoCronograma" class="alert d-none" role="status" aria-live="polite"></div>
<div id="cronograma"><p class="text-secondary">Cargando cronograma...</p></div>
<?php require RAIZ . '/view/layout/pie.php'; ?>
