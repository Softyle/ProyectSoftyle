/* =====================================================
   OPERARIOS Y HOJAS DE RUTA (jQuery + AJAX)
   Pide los datos al controlador cada 10 segundos y arma
   una tarjeta por operario, sin recargar la página.
   ===================================================== */
$(function () {
    var $lista = $('#operarios');
    var URL_DATOS = $lista.data('url');
    var URL_PEDIDO = $lista.data('pedido'); // index.php?c=pedido&a=validar
    var COLORES = { 'Pendiente': 'bg-warning text-dark', 'En Picking': 'bg-primary', 'Por validar': 'bg-info text-dark' };

    /* Arma la tarjeta de un operario (se usa .text() para no insertar HTML de la base) */
    function tarjeta(op) {
        var $col = $('<div class="col-md-6 col-xl-4">');
        var $card = $('<section class="tarjeta tarjeta-operario h-100">').appendTo($col);
        var $cab = $('<div class="d-flex align-items-center gap-2 mb-2">').appendTo($card);
        if (op.foto) {
            $('<img class="foto-operario">').attr({ src: op.foto, alt: 'Foto de ' + op.usuario }).appendTo($cab);
        } else {
            $('<span class="foto-operario" aria-hidden="true"><i class="bi bi-person"></i></span>').appendTo($cab);
        }
        var $nom = $('<div>').appendTo($cab);
        $('<h2 class="h6 mb-0">').text(op.usuario).appendTo($nom);
        $('<small class="text-secondary">').text('Ingreso ' + op.hora_ingreso + ' · ' + op.completados_hoy + ' completado(s) hoy · ' + op.completados + ' en total').appendTo($nom);

        if (!op.pedidos.length) {
            $('<p class="text-secondary small mb-0 mt-2">').text('Sin hojas de ruta en curso.').appendTo($card);
            return $col;
        }
        $.each(op.pedidos, function (_, p) {
            var pct = p.total ? Math.round(p.recogidos * 100 / p.total) : 0;
            var enlace = URL_PEDIDO + '&id=' + p.id;
            var $p = $('<div class="border-top pt-2 mt-2">').appendTo($card);
            var $linea = $('<div class="d-flex justify-content-between align-items-center gap-2">').appendTo($p);
            $('<a class="fw-bold text-decoration-none">').attr('href', enlace).text('Pedido #' + p.id).appendTo($linea);
            $('<span class="badge">').addClass(COLORES[p.estado] || 'bg-secondary').text(p.estado).appendTo($linea);
            $('<small class="text-secondary d-block">').text(p.cliente + (p.inicio ? ' · inició ' + p.inicio : '') + (p.entrega ? ' · entrega ' + p.entrega : '')).appendTo($p);
            var $barra = $('<div class="progress mt-1" role="progressbar">').attr({ 'aria-label': 'Avance del pedido ' + p.id, 'aria-valuenow': pct, 'aria-valuemin': 0, 'aria-valuemax': 100 }).appendTo($p);
            $('<div class="progress-bar">').css('width', pct + '%').appendTo($barra);
            $('<small>').text(p.recogidos + ' de ' + p.total + ' productos recogidos').appendTo($p);
            if (p.estado === 'Por validar') {
                $('<a class="btn btn-sm btn-warning mt-1 d-block">').attr('href', enlace).text('Validar pedido').appendTo($p);
            }
        });
        return $col;
    }

    function cargar() {
        $.getJSON(URL_DATOS).done(function (r) {
            $('#errorCarga').addClass('d-none');
            $('#actualizado').text(r.actualizado);
            $lista.empty();
            if (!r.operarios.length) {
                $lista.append($('<div class="col-12">').append($('<div class="tarjeta text-center">').text('No hay operarios activos.')));
            }
            $.each(r.operarios, function (_, op) { $lista.append(tarjeta(op)); });
            $('#sinAsignar').toggleClass('d-none', r.sin_asignar === 0)
                .text(r.sin_asignar + ' pedido(s) pendiente(s) sin asignar');
        }).fail(function (xhr) {
            var r = xhr.responseJSON || { mensaje: 'Sin conexión con el servidor.' };
            $('#errorCarga').removeClass('d-none').text(r.mensaje + ' Se reintentará automáticamente.');
        });
    }
    cargar();
    setInterval(cargar, 10000);
});
