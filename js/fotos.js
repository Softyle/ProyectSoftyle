/* =====================================================
   VISOR DE FOTOS DE PRODUCTOS
   Toda miniatura con la clase .foto-ampliable (lista de productos,
   mapa, picking y formulario) abre su foto en grande al tocarla
   o al hacerle clic. El visor es la ventana #visorFoto de pie.php.
   ===================================================== */
$(function () {
    var visor = document.getElementById('visorFoto');
    if (!visor) { return; }
    var modal = bootstrap.Modal.getOrCreateInstance(visor);

    function abrir(el) {
        var img = $(el).find('img').get(0);
        if (!img || !img.getAttribute('src')) { return; }   // sin foto (o no cargó): no hay nada para ampliar
        var nombre = $(el).data('nombre') || 'Foto del producto';
        $('#visorFotoTitulo').text(nombre);
        $('#visorFotoImagen').attr({ src: img.getAttribute('src'), alt: 'Foto de ' + nombre });
        modal.show();
    }

    $(document).on('click', '.foto-ampliable', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();   // en el mapa, que tocar la foto no elija también la estantería
        abrir(this);
    });
    // La vista previa del formulario no es un <button>: se abre también con Enter o la barra espaciadora
    $(document).on('keydown', '.foto-ampliable:not(button)', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); abrir(this); }
    });
    visor.addEventListener('hidden.bs.modal', function () { $('#visorFotoImagen').attr('src', ''); });
});
