/* =====================================================
   MAPA DEL DEPÓSITO (vista desde arriba)
   Al tocar un pasillo o una estantería del plano se muestra,
   debajo, el detalle de ese pasillo: cada estantería con sus
   niveles y los productos que guarda. Si se tocó una estantería,
   queda resaltada en el detalle.
   El detalle de cada pasillo viene armado desde el servidor en
   un <template id="detalle-zN-P01">.
   ===================================================== */
$(function () {
    var $plano = $('#planoDeposito');
    var $detalle = $('#detalleMapa');
    var vacio = $detalle.html();

    function mostrar(idPasillo, estante, desplazar) {
        var plantilla = document.getElementById('detalle-' + idPasillo);
        if (!plantilla) { return; }
        $detalle.empty().append(plantilla.content.cloneNode(true)).addClass('abierto');

        // Marca en el plano lo elegido
        $plano.find('.seleccionado').removeClass('seleccionado');
        $plano.find('.planta-camino[data-pasillo="' + idPasillo + '"]').addClass('seleccionado')
            .closest('.mapa-zona').addClass('seleccionado');
        if (estante) {
            $plano.find('.planta-estante[data-pasillo="' + idPasillo + '"][data-estante="' + estante + '"]').addClass('seleccionado');
            var $estante = $detalle.find('.detalle-estante[data-estante="' + estante + '"]').addClass('resaltada');
            if ($estante.length) {
                // En pantallas chicas las estanterías se desplazan de costado: se lleva la elegida a la vista
                var $fila = $detalle.find('.detalle-estantes');
                $fila.scrollLeft($fila.scrollLeft() + $estante.position().left - 8);
            }
        }
        if (desplazar) {
            var arriba = $detalle.offset().top - 80;
            if (arriba > $(window).scrollTop() + $(window).height() - 160 || arriba < $(window).scrollTop()) {
                $('html, body').animate({ scrollTop: arriba }, 250);
            }
            $detalle.trigger('focus');
        }
    }

    $plano.on('click', '.planta-camino, .planta-estante', function () {
        mostrar($(this).data('pasillo'), $(this).data('estante') || '', true);
    });

    $detalle.on('click', '[data-cerrar-detalle]', function () {
        $plano.find('.seleccionado').removeClass('seleccionado');
        $detalle.removeClass('abierto').html(vacio);
    });

    // Al entrar se abre el primer pasillo con algo para reponer
    var inicial = $plano.data('inicial');
    if (inicial) { mostrar(inicial, '', false); }
});
