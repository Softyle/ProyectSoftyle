/* =====================================================
   PICKFLOW - SERVICE WORKER (app instalable)
   Va en la raíz del sitio para poder atender todas las páginas.

   - Las pantallas del sistema siempre se piden al servidor
     (tienen datos del depósito y de la sesión: no se guardan).
     Si no hay conexión, se muestra offline.html.
   - Los archivos que no cambian (estilos, scripts, imágenes,
     íconos y fuentes) se guardan en el teléfono: la app abre
     más rápido y gasta menos datos. Se usa lo guardado y,
     por detrás, se actualiza.
   - Los pedidos AJAX (index.php?...), los formularios (POST)
     y los archivos subidos (uploads/) van siempre al servidor.

   Al cambiar este archivo, subir VERSION: se borra lo guardado antes.
   ===================================================== */
const VERSION = 'pickflow-v1';
const PAGINA_SIN_CONEXION = 'offline.html';
const PRECARGA = [PAGINA_SIN_CONEXION, 'img/app/icono-192.png', 'img/logo-blanco.png'];

// Sitios externos de donde vienen Bootstrap, jQuery, íconos, fuentes y librerías
const EXTERNOS = ['cdn.jsdelivr.net', 'code.jquery.com', 'fonts.googleapis.com', 'fonts.gstatic.com',
                  'cdnjs.cloudflare.com', 'cdn.datatables.net', 'unpkg.com'];

self.addEventListener('install', (evento) => {
    evento.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECARGA)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(claves.filter((c) => c !== VERSION).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

function esEstatico(url) {
    if (url.origin === self.location.origin) {
        return !url.pathname.includes('/uploads/') && /\.(css|js|png|jpe?g|svg|webp|gif|ico|woff2?|ttf)$/i.test(url.pathname);
    }
    return EXTERNOS.includes(url.hostname);
}

/* Usa lo guardado si existe y lo actualiza por detrás; si no está, lo baja y lo guarda */
async function guardadoYActualizar(pedido) {
    const cache = await caches.open(VERSION);
    const guardado = await cache.match(pedido);
    const deRed = fetch(pedido).then(async (respuesta) => {
        if (respuesta && (respuesta.ok || respuesta.type === 'opaque')) {
            await borrarVersionesViejas(cache, pedido.url);
            await cache.put(pedido, respuesta.clone());
        }
        return respuesta;
    }).catch(() => guardado || Response.error());
    return guardado || deRed;
}

/* Los archivos propios se piden con ?v=fecha (recurso() en view/ayudas.php):
   al llegar una versión nueva se borra la anterior para no juntar copias */
async function borrarVersionesViejas(cache, direccion) {
    const nueva = new URL(direccion);
    if (nueva.origin !== self.location.origin || !nueva.search) {
        return;
    }
    for (const pedido of await cache.keys()) {
        const vieja = new URL(pedido.url);
        if (vieja.pathname === nueva.pathname && vieja.search !== nueva.search) {
            await cache.delete(pedido);
        }
    }
}

self.addEventListener('fetch', (evento) => {
    const pedido = evento.request;
    if (pedido.method !== 'GET') {
        return;
    }
    const url = new URL(pedido.url);

    // Pantallas: siempre del servidor; sin conexión, la página de aviso
    if (pedido.mode === 'navigate') {
        evento.respondWith(fetch(pedido).catch(() => caches.match(PAGINA_SIN_CONEXION)));
        return;
    }
    if (esEstatico(url)) {
        evento.respondWith(guardadoYActualizar(pedido));
    }
    // Todo lo demás (AJAX, API) lo maneja el navegador como siempre
});
