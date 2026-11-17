/* =====================================================
   PICKFLOW - APP INSTALABLE (sin jQuery: también va en el login)
   1. Registra el service worker (sw.js).
   2. Muestra los botones "Instalar la app" (.btn-instalar-app)
      cuando el navegador permite instalarla:
        - Android / Chrome / Edge: abre el cartel de instalación.
        - iPhone / iPad (Safari): explica cómo agregarla a la
          pantalla de inicio, porque ahí no hay cartel automático.
      Si ya se abrió como app instalada, los botones no aparecen.
   ===================================================== */
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('sw.js').catch(function (error) {
                console.warn('PICKFLOW: no se pudo registrar la app instalable.', error);
            });
        });
    }

    var instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var esIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var cartel = null; // evento beforeinstallprompt guardado para usarlo al tocar el botón

    function botones() { return document.querySelectorAll('.btn-instalar-app'); }
    function mostrarBotones(visible) {
        botones().forEach(function (b) { b.hidden = !visible; });
    }

    window.addEventListener('beforeinstallprompt', function (evento) {
        evento.preventDefault();
        cartel = evento;
        if (!instalada) { mostrarBotones(true); }
    });
    window.addEventListener('appinstalled', function () {
        cartel = null;
        mostrarBotones(false);
    });

    function instruccionesIOS() {
        var aviso = document.getElementById('avisoInstalarIOS');
        if (!aviso) {
            aviso = document.createElement('div');
            aviso.id = 'avisoInstalarIOS';
            aviso.className = 'aviso-instalar-ios';
            aviso.setAttribute('role', 'dialog');
            aviso.setAttribute('aria-modal', 'true');
            aviso.setAttribute('aria-labelledby', 'avisoInstalarIOSTitulo');
            aviso.innerHTML = '<div class="aviso-instalar-caja">'
                + '<h2 id="avisoInstalarIOSTitulo">Instalar PICKFLOW en el iPhone</h2>'
                + '<ol><li>Tocá el botón <strong>Compartir</strong> <i class="bi bi-box-arrow-up" aria-hidden="true"></i> de Safari (abajo o arriba de la pantalla).</li>'
                + '<li>Elegí <strong>Agregar a inicio</strong> <i class="bi bi-plus-square" aria-hidden="true"></i>.</li>'
                + '<li>Tocá <strong>Agregar</strong>. PICKFLOW queda en la pantalla de inicio como una app.</li></ol>'
                + '<button type="button" class="btn btn-success w-100">Entendido</button></div>';
            aviso.addEventListener('click', function (e) {
                if (e.target === aviso || e.target.tagName === 'BUTTON') { aviso.hidden = true; }
            });
            document.body.appendChild(aviso);
        }
        aviso.hidden = false;
        aviso.querySelector('button').focus();
    }

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('.btn-instalar-app');
        if (!boton) { return; }
        if (cartel) {
            cartel.prompt();
            cartel.userChoice.then(function () { cartel = null; mostrarBotones(false); });
        } else if (esIOS) {
            instruccionesIOS();
        }
    });
    document.addEventListener('keydown', function (e) {
        var aviso = document.getElementById('avisoInstalarIOS');
        if (e.key === 'Escape' && aviso && !aviso.hidden) { aviso.hidden = true; }
    });

    // En el iPhone no hay evento de instalación: el botón se muestra si todavía no está instalada
    document.addEventListener('DOMContentLoaded', function () {
        if (esIOS && !instalada) { mostrarBotones(true); }
    });
})();
