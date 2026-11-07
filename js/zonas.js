/* =====================================================
   PICKFLOW - NOMBRE DE ZONA (formulario de zonas)
   Solo acepta "Zona" y una letra (Zona A ... Zona Z).
   Mientras se escribe lo deja con el formato correcto
   ("zona g" -> "Zona G") y avisa si ya existe.
   El servidor vuelve a validar todo al guardar.
   ===================================================== */
$(function () {
    var $nombre = $('#nombre[data-usados]');
    if (!$nombre.length) {
        return;
    }
    var usados = JSON.parse($nombre.attr('data-usados'));

    $nombre.on('input', function () {
        var m = $.trim(this.value).match(/^zona\s*([a-z])$/i);
        if (m) {
            this.value = 'Zona ' + m[1].toUpperCase();
        }
        var repetido = m && usados.indexOf(this.value) !== -1;
        this.setCustomValidity(repetido ? 'Ya existe la ' + this.value + '.' : '');
        $(this).toggleClass('is-invalid', !!repetido);
    });
});
