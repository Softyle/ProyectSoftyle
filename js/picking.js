/* =====================================================
   HOJA DE RUTA / PICKING ASISTIDO (jQuery + AJAX)
   Confirma cada retiro sin recargar la página, termina el
   picking y cada 15 segundos consulta en silencio el stock
   y el estado del pedido (por ejemplo, si el administrador
   devolvió un producto al picking).
   ===================================================== */
$(function () {
    var $hoja = $('#hojaRuta');
    var PEDIDO = $hoja.data('pedido');
    var TOTAL = $hoja.data('total');
    var CSRF = $hoja.data('csrf');
    var URL_CONFIRMAR = $hoja.data('url-confirmar');
    var URL_FINALIZAR = $hoja.data('url-finalizar');
    var URL_ESTADO = $hoja.data('url-estado');
    var URL_PANEL = $hoja.data('url-panel');
    var temporizador = null;

    /* Mensaje general de la página (verde = correcto, rojo = error) */
    function avisar(texto, tipo) {
        $('#aviso').removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + tipo).text(texto);
    }

    /* Actualiza la barra de progreso y el botón de finalizar */
    function pintarProgreso(progreso) {
        $('#cantRecogidos').text(progreso.recogidos);
        $('#barraProgreso').css('width', Math.round(progreso.recogidos * 100 / TOTAL) + '%')
            .parent().attr('aria-valuenow', progreso.recogidos);
        var completo = progreso.recogidos >= TOTAL;
        $('#btnFinalizar').prop('disabled', !completo);
        $('#ayudaFinalizar').text(completo ? 'Todos los productos están recogidos.' : 'Se habilita cuando confirmes todos los productos.');
    }

    /* Confirmación de retiro de un producto */
    $('.form-retiro').on('submit', function (ev) {
        ev.preventDefault();
        var $form = $(this);
        var $item = $form.closest('.producto-picking');
        var $boton = $form.find('button');
        var $mensaje = $form.find('.mensaje-item');
        $form.find('.is-invalid').removeClass('is-invalid');
        $mensaje.text('');
        $boton.prop('disabled', true);

        $.ajax({
            url: URL_CONFIRMAR,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-Token': CSRF },
            data: {
                id_pedido: PEDIDO,
                id_producto: $item.data('producto'),
                ubicacion: $form.find('.inp-ubicacion').val(),
                cantidad: $form.find('.inp-cantidad').val()
            }
        }).done(function (r) {
            $item.removeClass('con-error').addClass('recogido');
            $item.find('.stock-actual').text(r.stock_actual).toggleClass('text-danger', r.stock_bajo);
            avisar(r.mensaje + (r.stock_bajo ? ' Atención: el producto quedó en su stock mínimo.' : ''), 'success');
            pintarProgreso(r.progreso);
            // Lleva al siguiente producto pendiente
            var $siguiente = $('.producto-picking:not(.recogido)').first();
            if ($siguiente.length) { $siguiente.find('.inp-ubicacion').trigger('focus'); }
            else { $('#btnFinalizar').trigger('focus'); }
        }).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor. Revisá la red e intentá de nuevo.' };
            $item.addClass('con-error');
            $mensaje.text(r.mensaje);
            if (r.campo === 'ubicacion') { $form.find('.inp-ubicacion').addClass('is-invalid').trigger('focus'); }
            if (r.campo === 'cantidad') { $form.find('.inp-cantidad').addClass('is-invalid').trigger('focus'); }
            if (r.progreso) { pintarProgreso(r.progreso); }
            if (xhr.status === 409 && !r.campo) { refrescar(); }
            avisar(r.mensaje, 'danger');
        }).always(function () {
            $boton.prop('disabled', false);
        });
    });

    /* Terminar el picking: el pedido pasa a "Por validar" */
    $('#btnFinalizar').on('click', function () {
        var $b = $(this).prop('disabled', true);
        $.ajax({
            url: URL_FINALIZAR, method: 'POST', dataType: 'json',
            headers: { 'X-CSRF-Token': CSRF },
            data: { id_pedido: PEDIDO }
        }).done(function (r) {
            clearInterval(temporizador);
            avisar(r.mensaje + ' Volviendo a tus pedidos...', 'success');
            setTimeout(function () { window.location.href = URL_PANEL; }, 2500);
        }).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor.' };
            avisar(r.mensaje, 'danger');
            if (r.progreso) { pintarProgreso(r.progreso); } else { $b.prop('disabled', false); }
        });
    });

    /* Actualización silenciosa del stock y del estado del pedido */
    function refrescar() {
        $.getJSON(URL_ESTADO, { id_pedido: PEDIDO }).done(function (r) {
            $.each(r.items, function (_, it) {
                var $item = $('#item-' + it.id_producto);
                $item.toggleClass('recogido', it.recogido);
                $item.find('.stock-actual').text(it.stock_actual).toggleClass('text-danger', it.stock_bajo);
            });
            pintarProgreso(r.progreso);
            if (r.estado !== 'En Picking' && r.estado !== 'Pendiente') {
                clearInterval(temporizador);
                avisar('El pedido ahora está "' + r.estado + '". Ya no admite retiros.', 'warning');
                $('.form-retiro button, #btnFinalizar').prop('disabled', true);
            }
        });
    }
    temporizador = setInterval(refrescar, 15000);
});
