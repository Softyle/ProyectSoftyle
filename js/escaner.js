/* =====================================================
   LECTOR DE CÓDIGOS DE BARRAS (librería html5-qrcode)
   Escaner.abrir(alLeer) abre la ventana #modalEscaner y,
   cuando obtiene un código, la cierra y llama a alLeer(codigo).

   - Cámara en vivo: los navegadores solo dan acceso a la
     cámara en un "contexto seguro" (HTTPS o localhost).
   - Foto: funciona siempre; el celular abre la cámara y la
     imagen se analiza en el navegador, no se sube a ningún lado.
   - Número escrito a mano: por si las barras están dañadas.
   ===================================================== */
window.Escaner = (function () {
    var modalEl = document.getElementById('modalEscaner');
    if (!modalEl || typeof Html5Qrcode === 'undefined') {
        return { abrir: function () { alert('El lector de códigos no está disponible en esta página.'); } };
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var FORMATOS = [
        Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8,
        Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E, Html5QrcodeSupportedFormats.CODE_128
    ];
    var config = { formatsToSupport: FORMATOS, verbose: false, experimentalFeatures: { useBarCodeDetectorIfSupported: true } };
    var camaraDisponible = window.isSecureContext && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    var lectorCamara = null;
    var arrancando = null;      // promesa mientras la cámara se está prendiendo
    var abierta = false;        // si la ventana está a la vista
    var alLeer = null;

    function estado(texto, tipo) {
        $('#estadoEscaner').attr('class', 'small mt-2 ' + (tipo === 'error' ? 'text-danger' : 'text-secondary')).text(texto || '');
    }

    /* Apaga la cámara. Si todavía se está prendiendo, espera a que termine para apagarla:
       si no, quedaría encendida con la ventana cerrada. */
    function detenerCamara() {
        return (arrancando || Promise.resolve()).then(function () {
            if (lectorCamara && lectorCamara.isScanning) { return lectorCamara.stop(); }
        }).catch(function () {});
    }

    /* Entrega el código leído y cierra la ventana (la cámara se apaga sola por el evento hidden) */
    function entregar(codigo) {
        var limpio = String(codigo).replace(/\D/g, '');
        if (!limpio) {
            estado('Ese código no tiene números. Probá de nuevo.', 'error');
            return;
        }
        var callback = alLeer;
        alLeer = null;
        // Mientras la ventana todavía se está abriendo, Bootstrap ignora el cierre: se cierra al terminar de abrir
        if (abierta) { modal.hide(); }
        else { modalEl.addEventListener('shown.bs.modal', function () { modal.hide(); }, { once: true }); }
        if (callback) { callback(limpio); }
    }

    function iniciarCamara() {
        if (arrancando) { return; }
        if (!lectorCamara) { lectorCamara = new Html5Qrcode('lectorCamara', config); }
        if (lectorCamara.isScanning) { return; }
        estado('Abriendo la cámara...');
        arrancando = lectorCamara.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 260, height: 120 } },
            function (texto) { if (alLeer) { entregar(texto); } }, function () {})
            .then(function () { estado(''); })
            .catch(function () {
                $('#bloqueCamara').addClass('d-none');
                $('#sinCamara').removeClass('d-none').text('No se pudo usar la cámara (quizás no diste permiso). Sacale una foto al código o escribí el número.');
                estado('');
            })
            .then(function () {
                arrancando = null;
                // Se cerró la ventana mientras se prendía: se apaga
                if (!abierta) { detenerCamara(); }
            });
    }

    /* Foto del código: se analiza la imagen elegida (no hace falta esperar a la cámara en vivo) */
    $('#fotoCodigo').on('change', function () {
        var archivo = this.files && this.files[0];
        this.value = '';
        if (!archivo) { return; }
        estado('Leyendo la foto...');
        new Html5Qrcode('lectorArchivo', config).scanFile(archivo, false).then(entregar).catch(function () {
            estado('No se encontró un código de barras en la foto. Probá más cerca, con buena luz y las barras derechas.', 'error');
        });
    });

    $('#btnCodigoManual').on('click', function () { entregar($('#codigoManual').val()); });
    $('#codigoManual').on('keydown', function (ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); entregar($(this).val()); }
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        abierta = true;
        if (camaraDisponible) { iniciarCamara(); }
    });
    modalEl.addEventListener('hidden.bs.modal', function () {
        abierta = false;
        alLeer = null;
        detenerCamara();
    });

    return {
        abrir: function (callback) {
            alLeer = callback;
            $('#codigoManual').val('');
            estado('');
            $('#bloqueCamara').toggleClass('d-none', !camaraDisponible);
            $('#sinCamara').toggleClass('d-none', camaraDisponible)
                .text('La cámara en vivo solo funciona si la página se abre por HTTPS. Sacale una foto al código o escribí el número.');
            modal.show();
        }
    };
})();
