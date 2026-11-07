/* =====================================================
   PICKFLOW - BÚSQUEDA EN LA LISTA DE PRODUCTOS (jQuery)
   Filtra las filas de la tabla mientras se escribe:
   por nombre o código, y por categoría.
   ===================================================== */
$(function () {
    var $filas = $('#tablaProductos tbody tr[data-nombre]');
    if (!$filas.length) {
        return;
    }

    function filtrar() {
        var texto = $.trim($('#buscarProducto').val()).toLowerCase();
        var categoria = $('#filtroCategoria').val();
        var visibles = 0;

        $filas.each(function () {
            var $fila = $(this);
            // attr() devuelve siempre texto (data() convertiría un nombre como "123" en número)
            var coincide = $fila.attr('data-nombre').indexOf(texto) !== -1
                && (categoria === '' || $fila.attr('data-categoria') === categoria);
            $fila.prop('hidden', !coincide);
            if (coincide) {
                visibles++;
            }
        });

        $('#sinResultados').prop('hidden', visibles > 0);
        $('#cantidadProductos').text(visibles + (visibles === 1 ? ' producto' : ' productos'));
    }

    $('#buscarProducto').on('input', filtrar);
    $('#filtroCategoria').on('change', filtrar);
});
