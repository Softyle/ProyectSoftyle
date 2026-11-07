/* =====================================================
   Confirmar o devolver un pedido sin recargar la página:
   se envía al controlador (index.php?c=pedido&a=procesarValidacion) y con la respuesta se
   actualizan el estado, los ítems y los movimientos.
   ===================================================== */
$(function () {
    var $form = $('#formValidacion');
    var PEDIDO = $form.data('pedido');
    var CSRF = $form.data('csrf');
    var MENSAJES_CONFIRMACION = {
        confirmar: '¿Confirmás que los productos retirados coinciden con lo solicitado?',
        devolver: 'Los productos marcados vuelven a la hoja de ruta del operario y se repone su stock. ¿Continuar?'
    };

    function avisar(texto, tipo) {
        $('#avisoValidacion').removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + tipo).text(texto);
    }

    function celda(texto) {
        return $('<td>').text(texto === null || texto === undefined ? '—' : texto);
    }

    // Repinta la pantalla con el pedido actualizado que devolvió el servidor
    function pintar(r) {
        var porValidar = r.estado === 'Por validar';
        $('#estadoPedido').text(r.estado);
        $('.col-devolver').toggleClass('d-none', !porValidar);
        $('#accionesValidacion').toggleClass('d-none', !porValidar);
        $('[data-accion="confirmar"]').prop('disabled', !r.todo_coincide);

        var $items = $('#tablaItems').empty();
        $.each(r.items, function (_, it) {
            var $check = $('<input class="form-check-input" type="checkbox" name="devolver[]">')
                .val(it.id_producto).prop('checked', !it.coincide)
                .attr('aria-label', 'Devolver ' + it.producto + ' al picking');
            $('<tr>').toggleClass('table-warning', !it.coincide).append(
                $('<td class="col-devolver">').toggleClass('d-none', !porValidar).append($check),
                $('<td>').append($('<strong>').text(it.producto)),
                celda(it.ubicacion),
                celda(it.solicitada),
                celda(it.retirada),
                $('<td>').append(it.errores > 0 ? $('<span class="badge bg-warning text-dark">').text(it.errores) : '0'),
                $('<td>').append($('<span class="badge">').addClass(it.coincide ? 'bg-success' : 'bg-danger').text(it.coincide ? 'Coincide' : 'Revisar'))
            ).appendTo($items);
        });

        var $movs = $('#tablaMovimientos').empty();
        if (!r.movimientos.length) {
            $movs.append('<tr><td colspan="6" class="text-center">Todavía no hay movimientos para este pedido.</td></tr>');
        }
        $.each(r.movimientos, function (_, m) {
            $('<tr>').append(celda(m.fecha).addClass('text-nowrap'), celda(m.tipo), celda(m.producto), celda(m.cantidad), celda(m.ubicacion), celda(m.operario)).appendTo($movs);
        });
    }

    // Cada botón del formulario envía su acción por AJAX
    $form.on('click', 'button[data-accion]', function (ev) {
        ev.preventDefault();
        var accion = $(this).data('accion');
        if (!confirm(MENSAJES_CONFIRMACION[accion])) {
            return;
        }
        var $botones = $form.find('button').prop('disabled', true);
        var datos = $form.serializeArray();
        datos.push({ name: 'accion', value: accion }, { name: 'id_pedido', value: PEDIDO });

        $.ajax({
            url: $form.data('url'),
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-Token': CSRF },
            data: $.param(datos)
        }).done(function (r) {
            pintar(r);
            avisar(r.mensaje, 'success');
        }).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor. Intentá de nuevo.' };
            if (r.items) { pintar(r); }
            avisar(r.mensaje, 'danger');
        }).always(function () {
            $botones.prop('disabled', false);
            $('[data-accion="confirmar"]').prop('disabled', $('#tablaItems tr.table-warning').length > 0);
        });
    });
});
