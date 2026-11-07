/* =====================================================
   SIMULADOR DE LA API DE PEDIDOS (jQuery + AJAX)
   Hace de "cliente externo": envía la orden de salida como
   JSON a la API y muestra el código HTTP y la respuesta.
   ===================================================== */
$(function () {
    var $form = $('#formApi');
    var COLORES = { 201: 'bg-success', 202: 'bg-warning text-dark', 409: 'bg-danger' };

    /* ---------- Buscador de productos (nombre / SKU + categoría) ---------- */
    var $buscar = $('#buscarProductoApi');
    var $categoria = $('#categoriaProductoApi');
    var $lista = $('#listaProductos');
    var $opciones = $lista.find('li[data-sku]');
    var $elegido = $('#producto');
    var marcada = -1; // opción resaltada con el teclado

    function visibles() {
        return $opciones.not('[hidden]');
    }

    function abrir() {
        $lista.prop('hidden', false);
        $buscar.attr('aria-expanded', 'true');
    }

    function cerrar() {
        $lista.prop('hidden', true);
        $buscar.attr('aria-expanded', 'false');
        marcar(-1);
    }

    // Con un producto ya elegido, el campo muestra su nombre: se lista todo
    function filtrar() {
        var texto = $elegido.val() ? '' : $.trim($buscar.val()).toLowerCase();
        var categoria = $categoria.val();
        $opciones.each(function () {
            var $o = $(this);
            var coincide = $o.attr('data-buscar').indexOf(texto) !== -1
                && (categoria === '' || $o.attr('data-categoria') === categoria);
            $o.prop('hidden', !coincide);
        });
        $('#sinProductos').prop('hidden', visibles().length > 0);
        marcar(-1);
    }

    function marcar(indice) {
        var $v = visibles();
        $opciones.removeClass('marcada');
        marcada = indice;
        if (indice >= 0 && indice < $v.length) {
            var $o = $v.eq(indice).addClass('marcada');
            $o[0].scrollIntoView({ block: 'nearest' });
        }
    }

    // La zona de destino es la que el producto ya tiene asignada (no se elige)
    function elegir($o) {
        $opciones.attr('aria-selected', 'false');
        $o.attr('aria-selected', 'true');
        $elegido.val($o.attr('data-sku'));
        $buscar.val($o.attr('data-nombre') + ' (' + $o.attr('data-sku') + ')');
        $('#zona').val($o.attr('data-zona'));
        $('#ubicacionProducto').text('Ubicación asignada: ' + $o.attr('data-ubicacion') + '.');
        $('#productoInvalido').removeClass('d-block');
        $buscar.removeClass('is-invalid');
        cerrar();
    }

    function limpiarEleccion() {
        $elegido.val('');
        $opciones.attr('aria-selected', 'false');
        $('#zona').val('');
        $('#ubicacionProducto').text('Es la zona donde está guardado el producto: no se puede cambiar.');
    }

    $buscar.on('focus click', function () {
        filtrar();
        abrir();
    });

    $buscar.on('input', function () {
        limpiarEleccion();
        filtrar();
        abrir();
    });

    $categoria.on('change', function () {
        filtrar();
        abrir();
    });

    $buscar.on('keydown', function (ev) {
        var $v = visibles();
        if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            abrir();
            marcar(Math.min(marcada + 1, $v.length - 1));
        } else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            marcar(Math.max(marcada - 1, 0));
        } else if (ev.key === 'Enter' && !$lista.prop('hidden')) {
            ev.preventDefault();
            if (marcada >= 0) {
                elegir($v.eq(marcada));
            } else if ($v.length === 1) {
                elegir($v.eq(0));
            }
        } else if (ev.key === 'Escape') {
            cerrar();
        }
    });

    // mousedown (y no click) para que se elija antes de que el campo pierda el foco
    $opciones.on('mousedown', function (ev) {
        ev.preventDefault();
        elegir($(this));
    });

    // Clic fuera del buscador: se cierra la lista
    $(document).on('mousedown', function (ev) {
        if (!$(ev.target).closest('#selectorProducto').length) {
            cerrar();
        }
    });

    /* ---------- Envío de la orden ---------- */
    $form.on('submit', function (ev) {
        ev.preventDefault();
        if (!$elegido.val()) {
            $buscar.addClass('is-invalid').trigger('focus');
            $('#productoInvalido').addClass('d-block');
            return;
        }
        var $boton = $('#enviar').prop('disabled', true);
        $('#sinRespuesta').prop('hidden', true);
        $('#respuesta').prop('hidden', false);
        $('#json').text('Procesando...');
        $.ajax({
            url: $form.data('url'),
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            headers: { 'X-CSRF-Token': $form.data('csrf') },
            data: JSON.stringify({
                nombre_cliente: $('#cliente').val(),
                sku: $elegido.val(),
                cantidad: Number($('#cantidad').val()),
                zona_destino: $('#zona').val()
            })
        }).always(function (a, textStatus, c) {
            // En done() la respuesta viene en "a"; en fail() viene en a.responseJSON
            var xhr = textStatus === 'success' ? c : a;
            var cuerpo = xhr.responseJSON || { estado: 'error', mensaje: 'No fue posible conectar con la API.' };
            $('#codigo').attr('class', 'badge ' + (COLORES[xhr.status] || 'bg-secondary')).text('HTTP ' + (xhr.status || 'sin respuesta'));
            $('#json').text(JSON.stringify(cuerpo, null, 2));
            $boton.prop('disabled', false);
        });
    });
});
