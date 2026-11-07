/* =====================================================
   PICKFLOW - MINIMIZAR EL MENÚ LATERAL (computadora)
   El botón de la barra superior deja el menú solo con
   íconos y el contenido usa todo el ancho. La elección
   se recuerda en el navegador (localStorage).
   ===================================================== */
$(function () {
    var $html = $('html');
    var $boton = $('#btnColapsarMenu');
    var $enlaces = $('#menuLateral .sidebar-nav a, #menuLateral .sidebar-salir');

    function aplicar(colapsado) {
        $html.toggleClass('menu-colapsado', colapsado);
        var texto = colapsado ? 'Expandir menú' : 'Minimizar menú';
        $boton.attr({ 'aria-pressed': colapsado, title: texto, 'aria-label': texto });
        $boton.find('i').attr('class', colapsado ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset');
        // Con el menú minimizado, el nombre de cada opción aparece al pasar el mouse
        $enlaces.each(function () {
            $(this).attr('title', colapsado ? $.trim($(this).text()) : null);
        });
    }

    aplicar($html.hasClass('menu-colapsado'));

    $boton.on('click', function () {
        var colapsado = !$html.hasClass('menu-colapsado');
        aplicar(colapsado);
        try {
            localStorage.setItem('pickflow-menu', colapsado ? 'colapsado' : 'expandido');
        } catch (e) {
            // Sin almacenamiento (modo privado): funciona igual, solo no se recuerda
        }
    });
});
