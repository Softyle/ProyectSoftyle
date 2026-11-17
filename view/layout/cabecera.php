<?php
/* =====================================================
   LAYOUT DE LA APLICACIÓN - CABECERA
   Estructura común de todas las pantallas internas:
     - aside: barra lateral con las opciones del rol
       (en celulares y tablets se abre con el botón ☰)
     - barra superior con el título y el usuario
     - <main>: el contenido de cada vista

   Variables que define cada vista antes de incluirla:
     $titulo   título de la pestaña y de la barra superior
     $seccion  opción del menú que se marca como activa
     $estilos  (opcional) hojas de estilo extra
===================================================== */
$esAdmin = $sesion['rol'] === 'admin';
$panel = $esAdmin ? url('admin') : url('picking');

// Opciones del menú lateral: [sección, controlador, acción, parámetros, ícono, texto]
// (nombres con prefijo "menu" / "m" para no pisar las variables de la vista)
$menuLateral = $esAdmin ? [
    'General' => [
        ['admin', 'admin', 'index', [], 'bi-speedometer2', 'Panel de control'],
        ['reportes', 'reporte', 'index', [], 'bi-bar-chart-line', 'Reportes'],
    ],
    'Pedidos' => [
        ['pedidos', 'pedido', 'index', [], 'bi-list-check', 'Ver pedidos'],
        ['pedido-nuevo', 'pedido', 'nuevo', [], 'bi-plus-circle', 'Crear pedido'],
        ['seguimiento', 'seguimiento', 'index', [], 'bi-person-workspace', 'Operarios y hojas de ruta'],
        ['cronograma', 'cronograma', 'index', [], 'bi-calendar-week', 'Cronograma de despachos'],
        ['simulador', 'api', 'simulador', [], 'bi-send', 'Simular pedido externo'],
    ],
    'Centro logístico' => [
        ['productos', 'producto', 'index', [], 'bi-box-seam', 'Productos'],
        ['mapa', 'logistica', 'mapa', [], 'bi-map', 'Mapa del depósito'],
        ['inventario', 'logistica', 'inventario', [], 'bi-boxes', 'Stock por zona'],
        ['zonas', 'logistica', 'index', ['tipo' => 'zonas'], 'bi-grid-3x3-gap', 'Zonas'],
        ['ubicaciones', 'logistica', 'index', ['tipo' => 'ubicaciones'], 'bi-geo-alt', 'Ubicaciones'],
    ],
    'Abastecimiento' => [
        ['recepcion', 'recepcion', 'index', [], 'bi-box-arrow-in-down', 'Recepción de mercadería'],
        ['vencimientos', 'recepcion', 'vencimientos', [], 'bi-calendar2-x', 'Vencimientos'],
        ['conteo', 'conteo', 'index', [], 'bi-clipboard-data', 'Conteo de inventario'],
        ['reposicion', 'compra', 'reposicion', [], 'bi-cart-plus', 'Reposición'],
        ['compras', 'compra', 'index', [], 'bi-receipt', 'Órdenes de compra'],
        ['proveedores', 'proveedor', 'index', [], 'bi-truck', 'Proveedores'],
    ],
    'Personal' => [
        ['operarios', 'operario', 'index', [], 'bi-people', 'Operarios'],
        ['desempeno', 'logro', 'desempeno', [], 'bi-trophy', 'Desempeño y logros'],
    ],
    'Control ambiental' => [
        ['sensores', 'sensor', 'index', [], 'bi-thermometer-snow', 'Monitor de sensores'],
        ['sensores-reporte', 'sensor', 'reporte', [], 'bi-file-earmark-bar-graph', 'Reporte de sensores'],
    ],
] : [
    'Mi trabajo' => [
        ['mis-pedidos', 'picking', 'index', [], 'bi-clipboard-check', 'Mis pedidos'],
        ['cronograma', 'cronograma', 'index', [], 'bi-calendar-week', 'Mi cronograma'],
        ['historial', 'historial', 'index', [], 'bi-clock-history', 'Mi historial'],
        ['logros', 'logro', 'index', [], 'bi-trophy', 'Mis logros'],
    ],
    'Depósito' => [
        ['recepcion', 'recepcion', 'index', [], 'bi-box-arrow-in-down', 'Recepción de mercadería'],
        ['conteo', 'conteo', 'index', [], 'bi-clipboard-data', 'Conteos de inventario'],
    ],
    'Cuenta' => [
        ['perfil', 'perfil', 'index', [], 'bi-person-circle', 'Mi perfil'],
    ],
];
$seccion = $seccion ?? '';
$iniciales = mb_strtoupper(mb_substr($sesion['usuario'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> | PICKFLOW</title>
<link rel="icon" href="img/logo3.png">
<?php require RAIZ . '/view/layout/app_instalable.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<?php foreach ($estilos ?? [] as $hoja) { ?>
<link rel="stylesheet" href="<?= e(recurso($hoja)) ?>">
<?php } ?>
<link rel="stylesheet" href="<?= e(recurso('css/app.css')) ?>">
<!-- Si el usuario minimizó el menú, se aplica antes de dibujar la página (sin parpadeo) -->
<script>try { if (localStorage.getItem('pickflow-menu') === 'colapsado') document.documentElement.classList.add('menu-colapsado'); } catch (e) {}</script>
</head>
<body class="app">
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
<div class="app-layout">

    <!-- BARRA LATERAL: fija en pantallas grandes, panel deslizable en celulares -->
    <aside class="app-sidebar offcanvas-xl offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
        <div class="sidebar-marca">
            <a href="<?= e($panel) ?>" title="Ir al panel principal">
                <img src="<?= e(recurso('img/logo-blanco.png')) ?>" alt="PICKFLOW - ir al panel principal" class="sidebar-logo">
                <span>
                    <strong>PICKFLOW</strong>
                    <small><?= $esAdmin ? 'Panel administrativo' : 'Sistema de Picking' ?></small>
                </span>
            </a>
            <button type="button" class="btn-cerrar-menu d-xl-none" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar menú">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Opciones del sistema">
            <?php foreach ($menuLateral as $menuGrupo => $menuOpciones) { ?>
                <p class="sidebar-grupo"><?= e($menuGrupo) ?></p>
                <ul>
                    <?php foreach ($menuOpciones as [$mClave, $mControlador, $mAccion, $mParametros, $mIcono, $mTexto]) { $mActiva = $mClave === $seccion; ?>
                        <li>
                            <a href="<?= e(url($mControlador, $mAccion, $mParametros)) ?>" class="<?= $mActiva ? 'activa' : '' ?>" <?= $mActiva ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= $mIcono ?>" aria-hidden="true"></i><span><?= e($mTexto) ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </nav>

        <div class="sidebar-pie">
            <!-- Aparece solo si el navegador permite instalar PICKFLOW como app (js/pwa.js) -->
            <button type="button" class="sidebar-salir sidebar-instalar btn-instalar-app" hidden><i class="bi bi-phone" aria-hidden="true"></i><span>Instalar la app</span></button>
            <a href="<?= e(url('auth', 'salir')) ?>" class="sidebar-salir"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Cerrar sesión</span></a>
        </div>
    </aside>

    <div class="app-principal">
        <!-- BARRA SUPERIOR -->
        <header class="app-topbar">
            <button type="button" class="btn-menu d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <!-- En computadora: minimiza o expande el menú lateral (js/menu.js) -->
            <button type="button" class="btn-menu btn-colapsar-menu d-none d-xl-inline-flex" id="btnColapsarMenu" aria-controls="menuLateral" aria-pressed="false" title="Minimizar menú" aria-label="Minimizar menú">
                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            </button>
            <a href="<?= e($panel) ?>" class="topbar-logo d-xl-none" title="Ir al panel principal"><img src="<?= e(recurso('img/logo-icono.png')) ?>" alt="PICKFLOW"></a>
            <?php if ($seccion === 'admin') { ?>
                <!-- Solo el panel de control lleva el título arriba; el resto de las pantallas ya lo muestra en su encabezado -->
                <div class="topbar-titulo"><?= e($titulo) ?></div>
            <?php } ?>
            <div class="topbar-usuario">
                <!-- Campanita: lo que hay que atender (js/avisos.js la completa y la actualiza cada minuto) -->
                <div class="dropdown avisos" id="avisos" data-url="<?= e(url('aviso', 'listar')) ?>">
                    <button type="button" class="btn-menu btn-avisos" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Avisos" title="Avisos">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="avisos-numero" hidden></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end avisos-panel">
                        <p class="avisos-titulo">Avisos</p>
                        <div class="avisos-lista" aria-live="polite"><p class="avisos-vacio">Cargando…</p></div>
                    </div>
                </div>
                <?php if ($esAdmin) { ?>
                    <span class="usuario-chip"><span class="avatar" aria-hidden="true"><?= e($iniciales) ?></span><span class="usuario-datos"><strong><?= e($sesion['usuario']) ?></strong><small>Administrador</small></span></span>
                <?php } else { ?>
                    <!-- El operario entra a su perfil tocando su nombre -->
                    <a href="<?= e(url('perfil')) ?>" class="usuario-chip" title="Ver mi perfil"><?php if (!empty($sesion['foto'])) { ?><img src="<?= e($sesion['foto']) ?>" alt="" class="avatar avatar-foto"><?php } else { ?><span class="avatar" aria-hidden="true"><?= e($iniciales) ?></span><?php } ?><span class="usuario-datos"><strong><?= e($sesion['usuario']) ?></strong><small>Operario</small></span></a>
                <?php } ?>
            </div>
        </header>

        <main id="contenido" class="app-contenido">
