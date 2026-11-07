/* =====================================================
   NUEVO PEDIDO (jQuery)
   Formulario dinámico: agregar/quitar líneas de productos
   y avisar si se pide más de lo disponible.
   ===================================================== */
$(function () {
    var $lineas = $('#lineas');
    var plantilla = $lineas.find('.linea-pedido').first().clone();
    plantilla.find('select').val('');
    plantilla.find('input').val(1);
    plantilla.find('.aviso-stock').text('');

    function revisarLinea($linea) {
        var $opcion = $linea.find('.sel-producto option:selected');
        var disponible = parseInt($opcion.data('disponible'), 10);
        var cantidad = parseInt($linea.find('.inp-cantidad').val(), 10) || 0;
        var $aviso = $linea.find('.aviso-stock');
        if ($opcion.val() && cantidad > disponible) {
            $aviso.text('Solo hay ' + disponible + ' unidades disponibles de este producto.');
            $linea.find('.inp-cantidad').addClass('is-invalid');
        } else {
            $aviso.text('');
            $linea.find('.inp-cantidad').removeClass('is-invalid');
        }
    }

    $('#agregarLinea').on('click', function () {
        var $nueva = plantilla.clone();
        $lineas.append($nueva);
        $nueva.find('select').trigger('focus');
    });
    $lineas.on('click', '.btn-quitar', function () {
        if ($lineas.find('.linea-pedido').length > 1) {
            $(this).closest('.linea-pedido').remove();
        } else {
            var $l = $(this).closest('.linea-pedido');
            $l.find('select').val('');
            $l.find('input').val(1);
            revisarLinea($l);
        }
    });
    $lineas.on('change input', '.sel-producto, .inp-cantidad', function () {
        revisarLinea($(this).closest('.linea-pedido'));
    });
    $('#formPedido').on('submit', function (ev) {
        $lineas.find('.linea-pedido').each(function () { revisarLinea($(this)); });
        if ($lineas.find('.is-invalid').length) {
            ev.preventDefault();
            $lineas.find('.is-invalid').first().trigger('focus');
        }
    });
});
