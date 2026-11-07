/* =====================================================
   PICKFLOW - CRONOGRAMA DE DESPACHOS (jQuery + AJAX)
   Pide los datos al controlador (index.php?c=cronograma&a=datos) y arma la vista
   por día y por zona, sin recargar la página.
   ===================================================== */
$(function () {
    var $form = $('#filtrosCronograma');
    var URL_API = $form.data('api');

    // Color de prioridad según el estado del pedido
    var COLORES = {
        'Pendiente': 'gris',
        'En Picking': 'plata',
        'Por validar': 'plata',
        'Completado': 'bronce'
    };
    var TEXTOS = {
        'Pendiente': 'Pendiente',
        'En Picking': 'En proceso',
        'Por validar': 'En proceso (por validar)',
        'Completado': 'Completado'
    };

    function avisar(texto, tipo) {
        $('#avisoCronograma').removeClass('d-none alert-danger alert-info').addClass('alert-' + tipo).text(texto);
    }

    // "2026-10-23" -> "viernes 23/10/2026"
    function fechaLarga(iso) {
        var partes = iso.split('-');
        var fecha = new Date(partes[0], partes[1] - 1, partes[2]);
        var dia = fecha.toLocaleDateString('es-UY', { weekday: 'long' });
        return dia + ' ' + partes[2] + '/' + partes[1] + '/' + partes[0];
    }

    // Arma la tarjeta de un pedido (se usa .text() para no insertar HTML de la base)
    function filaPedido(p) {
        var color = COLORES[p.estado] || 'gris';
        var $fila = $('<div class="cronograma-pedido">');
        $('<strong>').text('Pedido #' + p.id).appendTo($fila);
        $('<span>').addClass('etiqueta-' + color).text(TEXTOS[p.estado] || p.estado).appendTo($fila);
        $('<span class="small">').text(p.cliente + ' · ' + p.unidades + ' u. · ' + p.operario).appendTo($fila);
        $('<span class="small text-secondary">').text(p.programado ? '(entrega programada)' : '(sin fecha programada: se usa la de creación)').appendTo($fila);
        $('<span class="ruta-picking w-100">')
            .text('Ruta de picking: ' + p.ruta.join(' → '))
            .prepend('<i class="bi bi-signpost-split" aria-hidden="true"></i> ')
            .appendTo($fila);
        return $fila;
    }

    function pintar(r) {
        var $contenedor = $('#cronograma').empty();
        if (!r.dias.length) {
            $contenedor.append($('<div class="tarjeta text-center">').text('No hay pedidos para despachar en estas fechas.'));
            return;
        }
        $.each(r.dias, function (_, dia) {
            var $dia = $('<section class="cronograma-dia">').toggleClass('es-hoy', dia.fecha === r.hoy);
            var $titulo = $('<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">').appendTo($dia);
            $('<h2 class="h5 mb-0 text-capitalize">').text(fechaLarga(dia.fecha) + (dia.fecha === r.hoy ? ' (hoy)' : '')).appendTo($titulo);
            $('<span class="badge bg-secondary">').text(dia.unidades + ' unidades').appendTo($titulo);

            $.each(dia.zonas, function (_, zona) {
                // Las zonas con algún pedido en curso se pintan plata; si todo está completado, bronce
                var estados = $.map(zona.pedidos, function (p) { return COLORES[p.estado]; });
                var color = estados.indexOf('plata') >= 0 ? 'plata' : (estados.indexOf('gris') >= 0 ? 'gris' : 'bronce');
                var $zona = $('<div class="cronograma-zona">').addClass('prioridad-' + color).appendTo($dia);
                $('<strong>').text(zona.zona + ' · ' + zona.tipo).appendTo($zona);
                $('<span class="small text-secondary ms-2">').text(zona.pedidos.length + ' pedido(s), ' + zona.unidades + ' u.').appendTo($zona);
                $.each(zona.pedidos, function (_, p) { $zona.append(filaPedido(p)); });
            });
            $contenedor.append($dia);
        });
    }

    function cargar() {
        $('#avisoCronograma').addClass('d-none');
        $.getJSON(URL_API, $form.serialize())
            .done(pintar)
            .fail(function (xhr) {
                var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor.' };
                avisar(r.mensaje, 'danger');
            });
    }

    $form.on('submit', function (ev) {
        ev.preventDefault();
        cargar();
    });
    $form.on('change', 'select', cargar);

    cargar();
    // Actualización silenciosa: el cronograma se refresca solo cada 30 segundos
    setInterval(cargar, 30000);
});
