/* =====================================================
   PANTALLA DE CONTEO (view/compartido/conteo.php)
   - Escanear (js/escaner.js): busca la fila del producto;
     con "Cada lectura suma 1" suma una unidad.
   - Cada cantidad se guarda por AJAX al momento.
   - El administrador ve la diferencia con el sistema.
   ===================================================== */
$(function () {
    var $c = $('#conteo');
    if (!$c.length || $c.data('en-curso') !== 1) { return; }

    var idConteo = $c.data('id');
    var csrf = $c.data('csrf');
    var urlGuardar = $c.data('url-guardar');
    var verSistema = $c.data('ver-sistema') === 1;
    var $filas = $c.find('tbody tr[data-producto]');
    var total = $filas.length;
    var temporizadores = {};
    var pendientes = 0;

    function aviso(texto, tipo) {
        $('#avisoConteo').removeClass('d-none alert-success alert-danger alert-info').addClass('alert-' + (tipo || 'info')).text(texto);
    }

    function actualizarProgreso() {
        var contados = $filas.filter('.fila-contada').length;
        var diferencias = 0;
        $filas.filter('.fila-contada').each(function () {
            var s = $(this).data('sistema');
            if (s !== '' && parseInt($(this).find('.inp-contado').val(), 10) !== parseInt(s, 10)) { diferencias++; }
        });
        $('#cantContados').text(contados);
        $('#barraConteo').css('width', (total ? Math.round(contados * 100 / total) : 0) + '%')
            .parent().attr('aria-valuenow', contados);
        $('#resumenCierre').text(contados + ' de ' + total + ' contados · ' + diferencias + ' con diferencia');
        aplicarFiltro();
    }

    function marcarEstado($fila, estado) {
        var iconos = { guardando: 'spinner-border spinner-border-sm text-secondary', ok: 'bi bi-check2 text-success', error: 'bi bi-exclamation-triangle text-danger', vacio: 'bi bi-dash' };
        $fila.find('.estado-guardado').html('<i class="' + iconos[estado] + '"></i>');
    }

    function mostrarDiferencia($fila) {
        if (!verSistema) { return; }
        var valor = $fila.find('.inp-contado').val();
        var $celda = $fila.find('.celda-diferencia').empty();
        if (valor === '') { return; }
        var dif = parseInt(valor, 10) - parseInt($fila.data('sistema'), 10);
        $celda.append(dif === 0
            ? $('<span class="text-success"><i class="bi bi-check-circle" aria-label="coincide"></i></span>')
            : $('<span class="badge">').addClass(dif > 0 ? 'bg-info' : 'bg-danger').text(dif > 0 ? '+' + dif : dif));
    }

    function guardar($fila) {
        var id = $fila.data('producto');
        var valor = $fila.find('.inp-contado').val().trim();
        if (valor !== '' && !(/^\d+$/.test(valor))) { marcarEstado($fila, 'error'); return; }
        $fila.toggleClass('fila-contada', valor !== '');
        mostrarDiferencia($fila);
        actualizarProgreso();
        marcarEstado($fila, 'guardando');
        pendientes++;
        $.ajax({ url: urlGuardar, method: 'POST', dataType: 'json', headers: { 'X-CSRF-Token': csrf },
                 data: { id_conteo: idConteo, id_producto: id, cantidad: valor } })
            .done(function () { marcarEstado($fila, valor === '' ? 'vacio' : 'ok'); })
            .fail(function (xhr) {
                marcarEstado($fila, 'error');
                aviso((xhr.responseJSON && xhr.responseJSON.mensaje) || 'No se pudo guardar. Revisá la conexión: lo vuelvo a intentar cuando cambies el número.', 'danger');
            })
            .always(function () { pendientes--; });
    }

    /* Se guarda un momento después de dejar de escribir (o al salir del campo) */
    $c.on('input', '.inp-contado', function () {
        var $fila = $(this).closest('tr');
        var id = $fila.data('producto');
        clearTimeout(temporizadores[id]);
        temporizadores[id] = setTimeout(function () { delete temporizadores[id]; guardar($fila); }, 700);
    });
    $c.on('change', '.inp-contado', function () {
        var $fila = $(this).closest('tr');
        clearTimeout(temporizadores[$fila.data('producto')]);
        delete temporizadores[$fila.data('producto')];
        guardar($fila);
    });

    /* ---------- Escanear ---------- */
    $('#btnEscanearConteo').on('click', function () {
        Escaner.abrir(function (codigo) {
            var $fila = $filas.filter(function () { return String($(this).data('codigo')) === codigo; }).first();
            if (!$fila.length) {
                aviso('El código ' + codigo + ' no es de ningún producto de esta zona. Si está acá, anotalo aparte y avisale al administrador.', 'danger');
                return;
            }
            var $inp = $fila.find('.inp-contado');
            if ($('#sumarLectura').is(':checked')) {
                $inp.val((parseInt($inp.val(), 10) || 0) + 1);
                guardar($fila);
                aviso($fila.data('nombre') + ': ' + $inp.val() + ' contados. Escaneá el próximo.', 'success');
            } else {
                aviso($fila.data('nombre') + ': escribí cuántos hay.', 'info');
            }
            $fila.removeClass('d-none')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $fila.addClass('linea-resaltada');
            setTimeout(function () { $fila.removeClass('linea-resaltada'); }, 1400);
            if (!$('#sumarLectura').is(':checked')) { $inp.trigger('focus').trigger('select'); }
        });
    });

    function aplicarFiltro() {
        var solo = $('#soloFaltan').is(':checked');
        $filas.each(function () { $(this).toggleClass('d-none', solo && $(this).hasClass('fila-contada')); });
    }
    $('#soloFaltan').on('change', aplicarFiltro);

    /* Antes de cerrar: que no quede nada guardándose y avisar lo que falta contar */
    $('#formCerrarConteo').on('submit', function (ev) {
        Object.keys(temporizadores).forEach(function (id) {
            clearTimeout(temporizadores[id]);
            delete temporizadores[id];
            guardar($filas.filter('[data-producto="' + id + '"]'));
        });
        if (pendientes > 0) {
            ev.preventDefault();
            alert('Esperá un segundo: todavía se están guardando cantidades. Volvé a tocar el botón.');
            return;
        }
        var faltan = $filas.not('.fila-contada').length;
        var texto = faltan > 0
            ? 'Faltan contar ' + faltan + ' productos: esos no se ajustan. ¿Cerrar igual y ajustar el stock de los contados?'
            : '¿Cerrar el conteo y ajustar el stock de los productos con diferencia?';
        if (!confirm(texto)) { ev.preventDefault(); }
    });

    window.addEventListener('beforeunload', function (ev) {
        if (pendientes > 0) { ev.preventDefault(); ev.returnValue = ''; }
    });
});
