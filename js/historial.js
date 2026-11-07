/* =====================================================
   HISTORIAL DEL OPERADOR (jQuery + AJAX)
   Pide el historial al controlador (index.php?c=historial&a=datos) y lo vuelve a pedir
   cada 15 segundos: los estados cambian solos en pantalla.
   ===================================================== */
$(function () {
    var COLOR_ESTADO = { 'Completado': 'bronce', 'En Proceso': 'plata', 'Pendiente': 'gris' };
    var COLOR_MOVIMIENTO = { 'Retiro': 'bg-success', 'Devolución': 'bg-secondary', 'Error de cantidad': 'bg-danger', 'Error de ubicación': 'bg-danger' };

    // Celda de tabla con texto (sin interpretar HTML)
    function celda(texto) {
        return $('<td>').text(texto === null || texto === undefined ? '—' : texto);
    }

    function pintar(r) {
        $('#avisoHistorial').addClass('d-none');
        $('#actualizado').text(r.actualizado);
        $('#resCompletado').text(r.resumen['Completado']);
        $('#resProceso').text(r.resumen['En Proceso']);
        $('#resPendiente').text(r.resumen['Pendiente']);

        var $pedidos = $('#tablaPedidos').empty();
        if (!r.pedidos.length) {
            $pedidos.append('<tr><td colspan="7" class="text-center">Todavía no tenés pedidos asignados.</td></tr>');
        }
        $.each(r.pedidos, function (_, p) {
            var $estado = $('<td>').append($('<span>').addClass('etiqueta-' + COLOR_ESTADO[p.estado_mostrado]).text(p.estado_mostrado));
            if (p.estado === 'Por validar') { $estado.append($('<small class="d-block text-secondary">').text('esperando validación')); }
            $('<tr>').append(
                celda('#' + p.id).css('font-weight', '700'),
                celda(p.cliente),
                $estado,
                celda(p.creado),
                celda(p.cierre),
                celda(p.minutos === null ? null : p.minutos + ' min'),
                celda(p.unidades + ' (' + p.productos + ' productos)')
            ).appendTo($pedidos);
        });

        var $movs = $('#tablaMovimientos').empty();
        if (!r.movimientos.length) {
            $movs.append('<tr><td colspan="6" class="text-center">Todavía no registraste retiros.</td></tr>');
        }
        $.each(r.movimientos, function (_, m) {
            $('<tr>').append(
                celda(m.fecha),
                celda(m.pedido === null ? null : '#' + m.pedido),
                celda(m.producto),
                celda(m.ubicacion),
                $('<td>').append($('<span class="badge">').addClass(COLOR_MOVIMIENTO[m.tipo] || 'bg-secondary').text(m.tipo)),
                celda(m.cantidad)
            ).appendTo($movs);
        });
    }

    function cargar() {
        $.getJSON($('#resumenHistorial').data('url')).done(pintar).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor. Se reintentará automáticamente.' };
            $('#avisoHistorial').removeClass('d-none').text(r.mensaje);
        });
    }

    cargar();
    setInterval(cargar, 15000);
});
