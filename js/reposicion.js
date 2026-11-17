/* =====================================================
   REPOSICIÓN (view/admin/reposicion.php)
   Cuenta los productos marcados, marca o desmarca un
   grupo entero y, al elegir un proveedor para un producto
   que no tenía, lo marca para pedir.
   ===================================================== */
$(function () {
    var $form = $('#formReposicion');
    if (!$form.length) { return; }

    function actualizarResumen() {
        var marcados = $form.find('.chk-pedir:checked');
        var proveedores = {};
        var unidades = 0;
        marcados.each(function () {
            var $fila = $(this).closest('tr');
            proveedores[$fila.find('[name^="proveedor["]').val()] = true;
            unidades += parseInt($fila.find('.inp-cantidad-pedir').val(), 10) || 0;
        });
        var cantProveedores = Object.keys(proveedores).filter(function (p) { return p !== '0'; }).length;
        $('#resumenPedir').text(marcados.length
            ? marcados.length + ' producto' + (marcados.length === 1 ? '' : 's') + ' · ' + unidades + ' unidades · '
              + cantProveedores + (cantProveedores === 1 ? ' orden' : ' órdenes') + ' de compra'
            : 'Marcá los productos que querés pedir');
        $form.find('[type=submit]').prop('disabled', marcados.length === 0);
    }

    $form.on('change input', '.chk-pedir, .inp-cantidad-pedir', actualizarResumen);
    $form.on('change', '.sel-proveedor', function () {
        if ($(this).val() !== '0') { $(this).closest('tr').find('.chk-pedir').prop('checked', true); }
        actualizarResumen();
    });
    $form.on('click', '.btn-marcar-grupo', function () {
        var $chks = $(this).closest('.grupo-reposicion').find('.chk-pedir');
        var todos = $chks.length === $chks.filter(':checked').length;
        $chks.prop('checked', !todos);
        $(this).text(todos ? 'Marcar todos' : 'Desmarcar todos');
        actualizarResumen();
    });
    $form.on('submit', function (ev) {
        var sinProveedor = $form.find('.chk-pedir:checked').closest('tr').find('.sel-proveedor').filter(function () { return $(this).val() === '0'; });
        if (sinProveedor.length) {
            ev.preventDefault();
            alert('Elegí el proveedor de los productos marcados que no tienen uno.');
            sinProveedor.first().trigger('focus');
        }
    });
    actualizarResumen();
});
