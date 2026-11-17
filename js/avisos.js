/* =====================================================
   PICKFLOW - CAMPANITA DE AVISOS (barra superior)
   Pide los avisos al servidor (index.php?c=aviso&a=listar)
   al abrir la pantalla y cada minuto mientras está a la vista.
   El número rojo indica que hay avisos nuevos desde la última
   vez que se abrió la campanita; ya vistos, queda en gris.
   Lo visto se recuerda en el navegador (localStorage).
   ===================================================== */
$(function () {
    var $avisos = $('#avisos');
    if (!$avisos.length) { return; }

    var $boton = $avisos.find('.btn-avisos');
    var $numero = $avisos.find('.avisos-numero');
    var $lista = $avisos.find('.avisos-lista');
    var CLAVE = 'pickflow-avisos-vistos';
    var actuales = [];

    function leerVistos() {
        try { return JSON.parse(localStorage.getItem(CLAVE)) || {}; } catch (e) { return {}; }
    }
    function guardarVistos() {
        var vistos = {};
        actuales.forEach(function (a) { vistos[a.clave] = a.cantidad; });
        try { localStorage.setItem(CLAVE, JSON.stringify(vistos)); } catch (e) { /* sin almacenamiento: no se recuerda */ }
    }
    // Un aviso es nuevo si no estaba o si ahora hay más que cuando se miró
    function hayNuevos() {
        var vistos = leerVistos();
        return actuales.some(function (a) { return !(a.clave in vistos) || a.cantidad > vistos[a.clave]; });
    }

    function pintar() {
        $lista.empty();
        if (!actuales.length) {
            $lista.append($('<p class="avisos-vacio">').append('<i class="bi bi-check-circle" aria-hidden="true"></i> ', 'Todo al día: no hay nada pendiente.'));
        }
        actuales.forEach(function (a) {
            $lista.append(
                $('<a class="dropdown-item aviso-item">').addClass('aviso-' + a.tono).attr('href', a.url).append(
                    $('<span class="aviso-icono" aria-hidden="true">').append($('<i class="bi">').addClass(a.icono)),
                    $('<span class="aviso-texto">').text(a.texto)
                )
            );
        });
        var total = actuales.length;
        var nuevos = hayNuevos();
        $numero.prop('hidden', total === 0).text(total > 9 ? '9+' : total).toggleClass('visto', !nuevos);
        var etiqueta = total === 0 ? 'Avisos: no hay nada pendiente'
            : 'Avisos: ' + total + (total === 1 ? ' pendiente' : ' pendientes') + (nuevos ? ', hay nuevos' : '');
        $boton.attr({ 'aria-label': etiqueta, title: etiqueta });
    }

    function cargar() {
        $.ajax({ url: $avisos.data('url'), dataType: 'json', cache: false })
            .done(function (r) {
                if (r && r.ok) {
                    actuales = r.avisos || [];
                    pintar();
                }
            })
            .fail(function () {
                // Sin conexión o sesión vencida: se deja lo último que se mostró
                if (!actuales.length) { $lista.html('<p class="avisos-vacio">No se pudieron cargar los avisos.</p>'); }
            });
    }

    // Al abrir la campanita, lo que se ve pasa a estar "visto"
    $avisos.on('shown.bs.dropdown', function () {
        guardarVistos();
        $numero.addClass('visto');
        $boton.attr('aria-label', $boton.attr('aria-label').replace(', hay nuevos', ''));
    });

    cargar();
    setInterval(function () {
        if (document.visibilityState === 'visible') { cargar(); }
    }, 60000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { cargar(); }
    });
});
