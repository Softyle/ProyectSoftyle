/* =====================================================
   FORMULARIO DE PRODUCTO
   "Leer del paquete": usa el lector (js/escaner.js) para
   completar el código de barras del producto.
   Con el código leído o escrito se consulta al servidor
   (producto/consultarCodigo):
   - si ya lo tiene otro producto, se avisa con un enlace a ese producto
   - si Open Food Facts conoce el producto, se completa el nombre
   - si no hay datos, se pide escribir el nombre a mano
   ===================================================== */
$(function () {
    var $codigo = $('#codigo_barras');
    var $info = $('#infoCodigo');
    var ultimoConsultado = '';
    var consulta = null;

    function aviso(tipo, texto) {
        $info.attr('class', 'info-codigo mt-2 ' + tipo).text(texto);
        return $info;
    }

    /* ---------- Ubicación libre según la categoría ----------
       Al dar de alta, cuando se elige la categoría se propone la primera ubicación libre
       de la zona donde ya está guardada esa categoría. Si la persona eligió una a mano,
       no se la cambia. */
    var $ubicacion = $('#id_ubicacion');
    var zonasCategoria = $ubicacion.data('zonas-categoria') || {};
    var ubicacionElegidaAMano = $ubicacion.data('elegir') !== 1;

    function proponerUbicacion() {
        if (ubicacionElegidaAMano) { return; }
        var zonas = zonasCategoria[$('#id_categoria').val()] || [];
        for (var i = 0; i < zonas.length; i++) {
            var $libre = $ubicacion.find('option[data-zona="' + zonas[i] + '"]').first();
            if ($libre.length) { $ubicacion.val($libre.val()); return; }
        }
        $ubicacion.prop('selectedIndex', 0);   // categoría sin zona propia: la primera libre
    }
    $('#id_categoria').on('change', proponerUbicacion);
    $ubicacion.on('change', function () { ubicacionElegidaAMano = true; });
    proponerUbicacion();

    /* ---------- Foto del producto ---------- */
    var $vista = $('#vistaFoto');
    var $archivo = $('#foto_archivo');
    var $enlace = $('#foto_url');
    var LADO_MAXIMO = 900;   // px: alcanza para verla bien y pesa poco

    function mostrarFoto(src) {
        $vista.find('img').remove();
        if (src) {
            $('<img alt="Vista previa de la foto" referrerpolicy="no-referrer">')
                .on('error', function () { $(this).remove(); })
                .attr('src', src).appendTo($vista);
        }
    }

    /* Dibuja la imagen (foto o cuadro de la cámara) achicada: las del celular pesan varios MB */
    function achicar(origen, ancho, alto) {
        var escala = Math.min(1, LADO_MAXIMO / Math.max(ancho, alto));
        var lienzo = document.createElement('canvas');
        lienzo.width = Math.round(ancho * escala);
        lienzo.height = Math.round(alto * escala);
        lienzo.getContext('2d').drawImage(origen, 0, 0, lienzo.width, lienzo.height);
        return lienzo;
    }

    /* Deja la foto lista en el campo de archivo del formulario (se sube al guardar) y la muestra */
    function dejarEnFormulario(lienzo) {
        $('#borrar_foto').prop('checked', false);
        $enlace.val('');
        marcarFoto();
        lienzo.toBlob(function (blob) {
            if (blob && window.DataTransfer) {
                try {
                    var dt = new DataTransfer();
                    dt.items.add(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
                    $archivo[0].files = dt.files;
                } catch (e) { /* navegador viejo: se sube la original (solo si vino de "Subir foto") */ }
            }
            mostrarFoto(lienzo.toDataURL('image/jpeg', 0.85));
        }, 'image/jpeg', 0.85);
    }

    function procesarArchivo(original) {
        if (!original) { return; }
        var url = URL.createObjectURL(original);
        var img = new Image();
        img.onload = function () {
            URL.revokeObjectURL(url);
            dejarEnFormulario(achicar(img, img.width, img.height));
        };
        img.onerror = function () { URL.revokeObjectURL(url); mostrarFoto(''); };
        img.src = url;
    }

    // "Subir foto": una imagen que ya está en la compu o en el celular
    $archivo.on('change', function () { procesarArchivo(this.files && this.files[0]); });
    // Cámara del celular (si la cámara en vivo no está disponible): la foto pasa al campo del formulario
    $('#foto_camara').on('change', function () {
        var archivo = this.files && this.files[0];
        this.value = '';
        procesarArchivo(archivo);
    });

    /* ---------- "Sacar foto": cámara en vivo dentro de la app ----------
       Funciona con la cámara de la compu y con las del celular (botón para cambiar).
       Los navegadores solo prestan la cámara en HTTPS: si no se puede, se usa la del celular. */
    var modalCamaraEl = document.getElementById('modalCamaraFoto');
    var video = document.getElementById('videoCamaraFoto');
    var flujo = null;
    var camara = 'environment';   // trasera; "user" = delantera
    var hayCamaraEnVivo = window.isSecureContext && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    function apagarCamara() {
        if (flujo) { flujo.getTracks().forEach(function (pista) { pista.stop(); }); flujo = null; }
        if (video) { video.srcObject = null; }
    }

    function prenderCamara() {
        apagarCamara();
        $('#estadoCamaraFoto').text('Abriendo la cámara...');
        $('#btnDispararFoto').prop('disabled', true);
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: camara }, width: { ideal: 1280 } }, audio: false })
            .then(function (s) {
                flujo = s;
                video.srcObject = s;
                return video.play();
            })
            .then(function () {
                $('#estadoCamaraFoto').text('');
                $('#btnDispararFoto').prop('disabled', false);
            })
            .catch(function () {
                apagarCamara();
                $('#estadoCamaraFoto').text('No se pudo usar la cámara (quizás no diste permiso o la usa otra aplicación). Podés usar "Subir foto".');
            });
    }

    if (modalCamaraEl) {
        var modalCamara = bootstrap.Modal.getOrCreateInstance(modalCamaraEl);
        $('#btnSacarFoto').on('click', function () {
            if (hayCamaraEnVivo) {
                modalCamara.show();
            } else {
                $('#foto_camara')[0].click();   // sin HTTPS: abre la cámara del celular
            }
        });
        modalCamaraEl.addEventListener('shown.bs.modal', prenderCamara);
        modalCamaraEl.addEventListener('hidden.bs.modal', apagarCamara);
        $('#btnCambiarCamara').on('click', function () {
            camara = camara === 'environment' ? 'user' : 'environment';
            prenderCamara();
        });
        $('#btnDispararFoto').on('click', function () {
            if (!video.videoWidth) { return; }
            dejarEnFormulario(achicar(video, video.videoWidth, video.videoHeight));
            modalCamara.hide();
        });
    }

    // Enlace pegado a mano: se muestra si empieza con https://
    $enlace.on('change', function () {
        var valor = $.trim($enlace.val());
        if (/^https:\/\/\S+$/i.test(valor)) {
            $archivo.val('');
            $('#borrar_foto').prop('checked', false);
            mostrarFoto(valor);
        }
    });

    // Oculta o por borrar: la vista previa se ve apagada (al ocultarla sigue guardada)
    function marcarFoto() {
        $vista.toggleClass('foto-quitada', $('#ocultar_foto').prop('checked') || $('#borrar_foto').prop('checked'));
    }
    $('#ocultar_foto, #borrar_foto').on('change', marcarFoto);
    marcarFoto();

    /* Completa nombre, descripción y foto con los datos encontrados */
    function usarDatos(producto) {
        $('#nombre').val(producto.nombre);
        if (producto.descripcion && !$('#descripcion').val()) {
            $('#descripcion').val(producto.descripcion);
        }
        // La foto solo si no eligieron una
        if (producto.foto && !$enlace.val() && !($archivo[0].files && $archivo[0].files.length)) {
            $enlace.val(producto.foto);
            mostrarFoto(producto.foto);
        }
    }

    function consultar(codigo) {
        codigo = String(codigo || '').replace(/\D/g, '');
        if (!codigo || codigo === ultimoConsultado) { return; }
        ultimoConsultado = codigo;
        if (consulta) { consulta.abort(); }
        aviso('cargando', 'Buscando el código ' + codigo + '...');

        consulta = $.getJSON($codigo.data('consulta'), { codigo: codigo })
            .done(function (r) {
                if (r.estado === 'existe') {
                    aviso('error', r.mensaje + ' ').append($('<a>').attr('href', r.editar).text('Ver ese producto'));
                } else if (r.estado === 'encontrado') {
                    if (!$('#nombre').val()) {
                        usarDatos(r.producto);
                        aviso('ok', r.mensaje);
                    } else {
                        // Ya había un nombre escrito: no se pisa sin preguntar
                        aviso('ok', r.mensaje + ' ').append(
                            $('<button type="button" class="btn btn-sm btn-outline-success ms-1">').text('Usar este nombre')
                                .on('click', function () { usarDatos(r.producto); $(this).remove(); })
                        );
                    }
                } else if (r.estado === 'sin_datos') {
                    aviso('neutro', r.mensaje);
                    if (!$('#nombre').val()) { $('#nombre').trigger('focus'); }
                } else {
                    aviso('neutro', r.mensaje);
                }
            })
            .fail(function (xhr, estado) {
                if (estado === 'abort') { return; }
                ultimoConsultado = '';
                var r = xhr.responseJSON;
                aviso('error', r && r.mensaje ? r.mensaje : 'No se pudo consultar el código. Podés seguir completando a mano.');
            });
    }

    $('#btnLeerCodigo').on('click', function () {
        Escaner.abrir(function (codigo) {
            $codigo.val(codigo);
            consultar(codigo);
        });
    });

    // Escrito a mano o con una pistola lectora (que "escribe" el número y un Enter)
    $codigo.on('change', function () { consultar($codigo.val()); });
    $codigo.on('keydown', function (ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); consultar($codigo.val()); }
    });
    $codigo.on('input', function () {
        var limpio = $codigo.val().replace(/\D/g, '');
        if (!limpio) { ultimoConsultado = ''; $info.attr('class', 'info-codigo mt-2').text(''); }
    });
});
