/* =====================================================
   NUEVA RECEPCIÓN (view/compartido/recepcion_form.php)
   - Escanear (js/escaner.js) o buscar por nombre/código:
     agrega el producto a la lista, o va a su fila si ya está.
   - Al elegir una orden de compra se cargan los productos
     que faltan recibir, con esa cantidad.
   - Los productos refrigerados piden la fecha de vencimiento.
   El catálogo viene en la página: buscar no consulta al servidor.
   ===================================================== */
$(function () {
    var $form = $('#formRecepcion');
    if (!$form.length) { return; }

    var productos = $form.data('productos') || [];
    var ordenes = $form.data('ordenes') || [];
    var hoy = $form.data('hoy');
    var urlAlta = $form.data('url-alta');
    var porId = {};
    var porCodigo = {};
    productos.forEach(function (p) { porId[p.id] = p; if (p.codigo) { porCodigo[p.codigo] = p; } });
    var $lineas = $('#lineasRecepcion');
    var molde = document.getElementById('moldeLinea');
    var contador = 0;

    function normalizar(t) { return String(t).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    function aviso(texto, tipo) {
        var $a = $('#avisoRecepcion');
        $a.attr('class', 'small mt-2 ' + (tipo === 'error' ? 'text-danger' : (tipo === 'ok' ? 'text-success' : 'text-secondary')));
        if (typeof texto === 'string') { $a.text(texto); } else { $a.empty().append(texto); }
    }

    function actualizarResumen() {
        var filas = $lineas.children('.linea-recepcion');
        var unidades = 0;
        filas.each(function () { unidades += parseInt($(this).find('[data-campo=cantidad]').val(), 10) || 0; });
        $('#sinLineas').prop('hidden', filas.length > 0);
        $('#resumenRecepcion').text(filas.length ? filas.length + ' producto' + (filas.length === 1 ? '' : 's') + ' · ' + unidades + ' unidades' : '');
        $form.find('[type=submit]').prop('disabled', filas.length === 0);
    }

    /* Agrega una fila con el producto. datos: {cantidad, lote, vencimiento} */
    function agregarLinea(p, datos) {
        datos = datos || {};
        var i = contador++;
        var $l = $(document.importNode(molde.content, true).firstElementChild);
        $l.attr('data-producto', p.id);
        $l.find('.linea-nombre strong').text(p.nombre);
        $l.find('.linea-nombre small').text(p.ubicacion + (p.codigo ? ' · ' + p.codigo : ''));
        if (p.foto) {
            var img = document.createElement('img');
            img.src = p.foto; img.alt = ''; img.referrerPolicy = 'no-referrer';
            img.onerror = function () { img.remove(); };
            $l.find('.foto-producto').append(img);
        }
        $l.find('.btn-quitar').attr('aria-label', 'Quitar ' + p.nombre);
        $l.find('[data-campo]').each(function () {
            var campo = this.getAttribute('data-campo');
            this.name = 'lineas[' + i + '][' + campo + ']';
            this.id = 'linea-' + i + '-' + campo;
            $(this).closest('div').find('label').attr('for', this.id);
        });
        $l.find('[data-campo=id_producto]').val(p.id);
        $l.find('[data-campo=cantidad]').val(datos.cantidad || 1);
        $l.find('[data-campo=lote]').val(datos.lote || '');
        var $vence = $l.find('[data-campo=vencimiento]').attr('min', hoy).val(datos.vencimiento || '');
        if (p.refrigerado) {
            $vence.prop('required', true);
            $l.find('.etiqueta-vence').removeClass('text-secondary fw-normal').addClass('text-danger').text('(obligatorio: refrigerado)');
        }
        $lineas.append($l);
        actualizarResumen();
        return $l;
    }

    /* Producto elegido (por escáner o búsqueda): si ya está en la lista, va a esa fila */
    function elegir(p) {
        var $existente = $lineas.children('[data-producto="' + p.id + '"]').last();
        var $l;
        if ($existente.length) {
            $l = $existente;
            aviso(p.nombre + ' ya está en la lista: corregí la cantidad o agregalo otra vez si es de otro lote.', 'info');
            var $otro = $('<button type="button" class="btn btn-link btn-sm p-0 ms-1">Agregar otro lote</button>').on('click', function () {
                var $n = agregarLinea(p); resaltar($n); aviso('');
            });
            $('#avisoRecepcion').append($otro);
        } else {
            $l = agregarLinea(p);
            aviso('Agregado: ' + p.nombre + '.', 'ok');
        }
        resaltar($l);
    }

    function resaltar($l) {
        $l[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $l.addClass('linea-resaltada');
        setTimeout(function () { $l.removeClass('linea-resaltada'); }, 1400);
        $l.find('[data-campo=cantidad]').trigger('focus').trigger('select');
    }

    /* ---------- Escanear ---------- */
    $('#btnEscanearRecepcion').on('click', function () {
        Escaner.abrir(function (codigo) {
            var p = porCodigo[codigo];
            if (p) { elegir(p); return; }
            var texto = $('<span>').text('El código ' + codigo + ' no es de ningún producto del catálogo. ');
            if (urlAlta) { texto.append($('<a>').attr('href', urlAlta).text('Darlo de alta en Productos')); }
            else { texto.append('Avisale al administrador para que lo dé de alta.'); }
            aviso(texto, 'error');
        });
    });

    /* ---------- Buscar por nombre o código ---------- */
    var $buscar = $('#buscarRecepcion');
    var $resultados = $('#resultadosRecepcion');
    var marcada = -1;

    function cerrarResultados() { $resultados.prop('hidden', true).empty(); $buscar.attr('aria-expanded', 'false'); marcada = -1; }

    function mostrarResultados() {
        var q = normalizar($buscar.val().trim());
        $resultados.empty();
        if (q.length < 2) { cerrarResultados(); return; }
        var encontrados = productos.filter(function (p) {
            return normalizar(p.nombre).indexOf(q) !== -1 || (p.codigo && p.codigo.indexOf(q) === 0);
        }).slice(0, 8);
        if (!encontrados.length) {
            $resultados.append($('<li class="selector-vacio">').text('Ningún producto coincide.'));
        }
        encontrados.forEach(function (p, i) {
            $resultados.append($('<li role="option" tabindex="-1">').attr({ 'data-sku': p.id, id: 'resRec-' + i })
                .append($('<span class="selector-nombre">').append($('<strong>').text(p.nombre), $('<small>').text(p.ubicacion + (p.codigo ? ' · ' + p.codigo : '')))));
        });
        $resultados.prop('hidden', false);
        $buscar.attr('aria-expanded', 'true');
        marcada = -1;
    }

    function elegirResultado($li) {
        var p = porId[$li.data('sku')];
        if (!p) { return; }
        cerrarResultados();
        $buscar.val('');
        elegir(p);
    }

    $buscar.on('input', mostrarResultados);
    $buscar.on('keydown', function (ev) {
        var $items = $resultados.children('[data-sku]');
        if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
            ev.preventDefault();
            if (!$items.length) { return; }
            marcada = (marcada + (ev.key === 'ArrowDown' ? 1 : -1) + $items.length) % $items.length;
            $items.removeClass('marcada').eq(marcada).addClass('marcada');
            $buscar.attr('aria-activedescendant', $items.eq(marcada).attr('id'));
        } else if (ev.key === 'Enter') {
            ev.preventDefault();
            // Un código entero escrito o pegado: se busca exacto
            var exacto = porCodigo[$buscar.val().replace(/\D/g, '')];
            if (exacto) { cerrarResultados(); $buscar.val(''); elegir(exacto); return; }
            if ($items.length) { elegirResultado($items.eq(marcada >= 0 ? marcada : 0)); }
        } else if (ev.key === 'Escape') {
            cerrarResultados();
        }
    });
    $resultados.on('mousedown', '[data-sku]', function (ev) { ev.preventDefault(); elegirResultado($(this)); });
    $buscar.on('blur', function () { setTimeout(cerrarResultados, 150); });

    /* ---------- Filas ---------- */
    $lineas.on('click', '.btn-quitar', function () {
        $(this).closest('.linea-recepcion').remove();
        actualizarResumen();
    });
    $lineas.on('input', '[data-campo=cantidad]', actualizarResumen);
    $lineas.on('input change', 'input', function () {
        this.setCustomValidity('');
        $(this).removeClass('is-invalid').closest('.linea-recepcion').find('.linea-mensaje').text('');
    });

    /* ---------- Orden de compra ---------- */
    var $orden = $('#id_orden');
    var $proveedor = $('#id_proveedor');

    function aplicarOrden(cargarLineas) {
        var orden = ordenes.filter(function (o) { return String(o.id) === $orden.val(); })[0];
        $proveedor.prop('disabled', !!orden);
        if (!orden) { return; }
        $proveedor.val(String(orden.id_proveedor));
        if (!cargarLineas) { return; }
        var hayCargadas = $lineas.children().length > 0;
        if (hayCargadas && !confirm('¿Reemplazar los productos de la lista por los que faltan recibir de la orden #' + orden.id + '?')) { return; }
        $lineas.empty();
        orden.lineas.forEach(function (l) { if (porId[l.id_producto]) { agregarLinea(porId[l.id_producto], { cantidad: l.cantidad }); } });
        var n = orden.lineas.length;
        aviso((n === 1 ? 'Se cargó el producto que falta' : 'Se cargaron los ' + n + ' productos que faltan') + ' de la orden #' + orden.id
            + '. Revisá las cantidades y completá lote y vencimiento.', 'ok');
        actualizarResumen();
    }
    $orden.on('change', function () { aplicarOrden(true); });

    /* ---------- Envío ---------- */
    $form.on('submit', function (ev) {
        // El proveedor deshabilitado no se envía: lo define la orden en el servidor
        var primero = null;
        $lineas.find('input[required], [data-campo=cantidad], [data-campo=vencimiento]').each(function () {
            var msj = '';
            var campo = this.getAttribute('data-campo');
            if (campo === 'cantidad' && !(parseInt(this.value, 10) >= 1)) { msj = 'Poné una cantidad (1 o más).'; }
            if (campo === 'vencimiento' && this.required && !this.value) { msj = 'Este producto es refrigerado: poné la fecha de vencimiento.'; }
            if (campo === 'vencimiento' && this.value && this.value < hoy) { msj = 'Esa fecha ya pasó: la mercadería vencida no se recibe.'; }
            if (msj) {
                $(this).addClass('is-invalid').closest('.linea-recepcion').find('.linea-mensaje').text(msj);
                primero = primero || this;
            }
        });
        if (!$lineas.children().length) {
            ev.preventDefault();
            aviso('Agregá al menos un producto: escanealo o buscalo por nombre.', 'error');
            return;
        }
        if (primero) {
            ev.preventDefault();
            primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
            primero.focus();
            return;
        }
        $form.find('[type=submit]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Guardando...');
    });

    /* ---------- Inicio: lo que ya estaba cargado (si hubo un error al guardar) ---------- */
    ($form.data('lineas') || []).forEach(function (l) {
        if (porId[l.id_producto]) { agregarLinea(porId[l.id_producto], l); }
    });
    if ($lineas.children().length === 0 && $orden.val() !== '0') { aplicarOrden(true); } else { aplicarOrden(false); }
    actualizarResumen();
});
