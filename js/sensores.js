/* =====================================================
   MONITOR DE SENSORES (jQuery + AJAX)
   Consulta al controlador (index.php?c=sensor&a=datos) cada 5 segundos y dibuja una
   tarjeta por sensor con un gráfico SVG de temperatura.
   ===================================================== */
$(function () {
    var INTERVALO = 5000;
    var temporizador = null;
    var NS = 'http://www.w3.org/2000/svg';

    /* Gráfico de línea de la temperatura con la franja del rango permitido */
    function grafico(sensor) {
        var ancho = 300, alto = 120, margen = 6;
        var valores = $.map(sensor.serie, function (l) { return l.temperatura; });
        var minimo = Math.min.apply(null, valores.concat(sensor.rango_temp[0])) - 1;
        var maximo = Math.max.apply(null, valores.concat(sensor.rango_temp[1])) + 1;
        var y = function (v) { return alto - margen - (v - minimo) * (alto - 2 * margen) / (maximo - minimo); };
        var x = function (i) { return margen + i * (ancho - 2 * margen) / Math.max(1, valores.length - 1); };

        var svg = document.createElementNS(NS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + ancho + ' ' + alto);
        svg.setAttribute('class', 'sensor-grafico');
        svg.setAttribute('preserveAspectRatio', 'none'); // ocupa todo el ancho de la tarjeta
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Temperatura de las últimas ' + valores.length + ' lecturas de ' + sensor.codigo);

        // Franja verde = rango permitido
        var franja = document.createElementNS(NS, 'rect');
        franja.setAttribute('x', 0);
        franja.setAttribute('width', ancho);
        franja.setAttribute('y', y(sensor.rango_temp[1]));
        franja.setAttribute('height', y(sensor.rango_temp[0]) - y(sensor.rango_temp[1]));
        franja.setAttribute('fill', 'rgba(24,169,87,0.12)');
        svg.appendChild(franja);

        // Línea de la temperatura
        var puntos = $.map(valores, function (v, i) { return x(i).toFixed(1) + ',' + y(v).toFixed(1); }).join(' ');
        var linea = document.createElementNS(NS, 'polyline');
        linea.setAttribute('points', puntos);
        linea.setAttribute('fill', 'none');
        linea.setAttribute('stroke', '#3265B7');
        linea.setAttribute('stroke-width', '2');
        svg.appendChild(linea);

        // Puntos fuera de rango en rojo
        $.each(valores, function (i, v) {
            if (v < sensor.rango_temp[0] || v > sensor.rango_temp[1]) {
                var c = document.createElementNS(NS, 'circle');
                c.setAttribute('cx', x(i));
                c.setAttribute('cy', y(v));
                c.setAttribute('r', 3.5);
                c.setAttribute('fill', '#dc3545');
                svg.appendChild(c);
            }
        });
        return svg;
    }

    function tarjeta(s) {
        var alerta = s.estado !== 'Normal';
        var $col = $('<div class="col-md-6">');
        var $card = $('<section class="sensor-card">').toggleClass('en-alerta', alerta).appendTo($col);
        var $cab = $('<div class="d-flex justify-content-between align-items-start gap-2">').appendTo($card);
        var $titulo = $('<div>').appendTo($cab);
        $('<h2 class="h5 mb-0">').text(s.zona + ' · ' + s.tipo_zona)
            .prepend(s.tipo_zona === 'Refrigerada' ? '<i class="bi bi-snow" aria-hidden="true"></i> ' : '<i class="bi bi-thermometer-half" aria-hidden="true"></i> ')
            .appendTo($titulo);
        $('<small class="text-secondary">').text('Sensor ' + s.codigo + ' · ' + s.hora).appendTo($titulo);
        $('<span class="badge">').addClass(alerta ? 'bg-danger' : 'bg-success').text(s.estado).appendTo($cab);

        var $valores = $('<div class="d-flex gap-4 my-3">').appendTo($card);
        $('<div>').append(
            $('<div class="sensor-valor">').text(s.temperatura.toFixed(1) + ' °C').toggleClass('text-danger', s.temperatura < s.rango_temp[0] || s.temperatura > s.rango_temp[1]),
            $('<small class="text-secondary">').text('Rango: ' + s.rango_temp[0] + ' a ' + s.rango_temp[1] + ' °C')
        ).appendTo($valores);
        $('<div>').append(
            $('<div class="sensor-valor">').text(s.humedad.toFixed(0) + ' %').toggleClass('text-danger', s.humedad < s.rango_hum[0] || s.humedad > s.rango_hum[1]),
            $('<small class="text-secondary">').text('Humedad: ' + s.rango_hum[0] + ' a ' + s.rango_hum[1] + ' %')
        ).appendTo($valores);

        $card.append(grafico(s));
        $('<small class="text-secondary">').text('Últimas ' + s.serie.length + ' lecturas · ' + s.alertas_24h + ' fuera de rango en 24 h').appendTo($card);
        return $col;
    }

    function cargar() {
        $.getJSON($('#sensores').data('url')).done(function (r) {
            $('#avisoSensores').addClass('d-none');
            $('#actualizado').text(r.actualizado);
            var $lista = $('#sensores').empty();
            if (!r.sensores.length) {
                $lista.append($('<div class="col-12">').append($('<div class="tarjeta text-center">').text('No hay sensores activos.')));
            }
            $.each(r.sensores, function (_, s) { $lista.append(tarjeta(s)); });

            var $alertas = $('#tablaAlertas').empty();
            if (!r.alertas.length) {
                $alertas.append('<tr><td colspan="2" class="text-center">Sin alertas registradas.</td></tr>');
            }
            $.each(r.alertas, function (_, a) {
                $('<tr>').append($('<td class="text-nowrap">').text(a.fecha), $('<td>').text(a.mensaje)).appendTo($alertas);
            });
        }).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor. Se reintentará automáticamente.' };
            $('#avisoSensores').removeClass('d-none').text(r.mensaje);
        });
    }

    // Pausar / reanudar el monitoreo (por ejemplo, para leer un valor con calma)
    $('#btnPausa').on('click', function () {
        if (temporizador) {
            clearInterval(temporizador);
            temporizador = null;
            $(this).html('<i class="bi bi-play-fill"></i> Reanudar');
        } else {
            cargar();
            temporizador = setInterval(cargar, INTERVALO);
            $(this).html('<i class="bi bi-pause-fill"></i> Pausar');
        }
    });

    cargar();
    temporizador = setInterval(cargar, INTERVALO);
});
