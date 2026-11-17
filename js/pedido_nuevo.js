/* =====================================================
   NUEVO PEDIDO (jQuery)
   Formulario dinámico: en cada línea se elige primero la
   categoría y después el producto (solo los de esa
   categoría). Agregar/quitar líneas y avisar si se pide
   más de lo disponible.
   ===================================================== */
$(function () {
    var $lineas = $('#lineas');

    // Lista completa de productos, tomada del primer selector (viene agrupada por categoría)
    var catalogo = [];
    $lineas.find('.sel-producto').first().find('option[value!=""]').each(function () {
        catalogo.push({
            id: this.value,
            texto: this.textContent,
            disponible: this.getAttribute('data-disponible'),
            categoria: this.getAttribute('data-categoria')
        });
    });

    /* Deja en el selector de productos solo los de la categoría elegida */
    function llenarProductos($linea, seleccionado) {
        var categoria = $linea.find('.sel-categoria').val();
        // El selector nunca se deshabilita: si no, no se envía y los productos
        // quedarían desparejados con sus cantidades
        var $sel = $linea.find('.sel-producto').empty();
        if (!categoria) {
            $sel.append($('<option value="">').text('Primero elegí la categoría'));
            return;
        }
        $sel.append($('<option value="">').text('Elegí un producto...'));
        catalogo.forEach(function (p) {
            if (p.categoria === categoria) {
                $sel.append($('<option>').val(p.id).attr('data-disponible', p.disponible).text(p.texto)
                    .prop('selected', String(seleccionado) === p.id));
            }
        });
    }

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

    // Líneas que ya venían cargadas (por ejemplo, si hubo un error al guardar)
    $lineas.find('.linea-pedido').each(function () {
        var $l = $(this);
        llenarProductos($l, $l.find('.sel-producto').val());
    });

    // Molde para las líneas nuevas: sin nada elegido (se quita el "selected" que venga del servidor,
    // porque clone() copia los atributos y no lo elegido)
    var plantilla = $lineas.find('.linea-pedido').first().clone();
    plantilla.find('option').removeAttr('selected');
    plantilla.find('.sel-categoria').val('');
    plantilla.find('input').attr('value', 1).val(1).removeClass('is-invalid');
    plantilla.find('.aviso-stock').text('');
    llenarProductos(plantilla, '');

    $('#agregarLinea').on('click', function () {
        var $nueva = plantilla.clone();
        $lineas.append($nueva);
        $nueva.find('.sel-categoria').trigger('focus');
    });
    $lineas.on('click', '.btn-quitar', function () {
        if ($lineas.find('.linea-pedido').length > 1) {
            $(this).closest('.linea-pedido').remove();
        } else {
            var $l = $(this).closest('.linea-pedido');
            $l.find('.sel-categoria').val('');
            llenarProductos($l, '');
            $l.find('input').val(1);
            revisarLinea($l);
        }
    });
    $lineas.on('change', '.sel-categoria', function () {
        var $l = $(this).closest('.linea-pedido');
        llenarProductos($l, '');
        revisarLinea($l);
        if ($(this).val()) { $l.find('.sel-producto').trigger('focus'); }
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
