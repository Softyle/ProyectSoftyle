<?php
/* VISTA - Sitio público institucional de PICKFLOW.
   Los textos salen de idiomas/es.php o idiomas/en.php según $idioma (lo elige el controlador). */
$t = require __DIR__ . '/idiomas/' . $idioma . '.php';
?>
<!DOCTYPE html>
<html lang="<?= e($t['html_lang']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($t['meta_titulo']) ?></title>
<meta name="description" content="<?= e($t['meta_descripcion']) ?>">
<link rel="icon" href="img/logo3.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(recurso('css/sitio.css')) ?>">
</head>
<body>

<!-- ===== NAVEGACIÓN ===== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top sitio-nav" aria-label="Navegación principal">
    <div class="container">
        <a class="navbar-brand" href="#inicio"><img src="<?= e(recurso('img/logo-blanco.png')) ?>" alt="Pickflow" class="sitio-logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="<?= e($t['abrir_menu']) ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#inicio"><?= e($t['nav_inicio']) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#proyecto"><?= e($t['nav_proyecto']) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#operativa"><?= e($t['nav_operativa']) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#galeria"><?= e($t['nav_galeria']) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#contacto"><?= e($t['nav_contacto']) ?></a></li>
                <!-- Cambio de idioma: en español muestra la bandera de Estados Unidos (EN); en inglés, la de Uruguay (ES).
                     Al hacer clic se conserva la sección en la que estaba (#contacto, etc.) -->
                <li class="nav-item">
                    <a class="nav-link idioma-link" href="<?= e(url('inicio', 'index', ['lang' => $t['idioma_otro']])) ?>"
                       hreflang="<?= e($t['idioma_otro']) ?>" lang="<?= e($t['idioma_otro']) ?>" title="<?= e($t['idioma_cambiar']) ?>"
                       aria-label="<?= e($t['idioma_cambiar']) ?>" onclick="this.href += location.hash">
                        <img src="<?= e(recurso($t['idioma_otro_bandera'])) ?>" alt="" class="idioma-bandera" width="24" height="16">
                        <span><?= e($t['idioma_otro_texto']) ?></span>
                    </a>
                </li>
                <li class="nav-item ms-lg-2"><a href="<?= e(url('auth', 'login')) ?>" class="btn btn-ingresar"><i class="bi bi-box-arrow-in-right"></i> <?= e($t['nav_ingresar']) ?></a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== PORTADA ===== -->
<header id="inicio" class="portada">
    <div class="container">
        <div class="row align-items-center g-5 portada-fila">
            <div class="col-lg-7">
                <span class="kicker kicker-verde"><?= e($t['portada_kicker']) ?></span>
                <h1 class="portada-titulo"><?= e($t['portada_titulo']) ?> <span><?= e($t['portada_resaltado']) ?></span></h1>
                <p class="portada-texto"><?= e($t['portada_texto']) ?></p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#proyecto" class="btn btn-verde"><?= e($t['btn_conocenos']) ?></a>
                    <a href="#recorrido" class="btn btn-contorno"><?= e($t['btn_recorrido']) ?></a>
                </div>
            </div>

            <!-- Tarjeta de ejemplo de la hoja de ruta -->
            <div class="col-lg-5">
                <div class="tarjeta-picking">
                    <div class="tarjeta-picking-cabecera"><span><?= e($t['tarjeta_picking']) ?></span><span class="punto-estado" aria-hidden="true"></span></div>
                    <div class="tarjeta-picking-etiqueta"><?= e($t['tarjeta_ubicacion']) ?></div>
                    <div class="tarjeta-picking-codigo">P02-E01-N01</div>
                    <div class="btn-confirmar confirmada"><i class="bi bi-check-circle" aria-hidden="true"></i> <?= e($t['tarjeta_confirmada']) ?></div>
                </div>
            </div>
        </div>
    </div>
</header>

<main>
    <!-- ===== SOBRE EL PROYECTO ===== -->
    <section id="proyecto" class="seccion">
        <div class="container">
            <div class="seccion-titulo">
                <span class="kicker"><?= e($t['proyecto_kicker']) ?></span>
                <h2><?= e($t['proyecto_titulo']) ?></h2>
                <p><?= e($t['proyecto_texto']) ?></p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <article class="tarjeta-info tarjeta-problema h-100">
                        <span class="etiqueta etiqueta-naranja"><?= e($t['problema_etiqueta']) ?></span>
                        <h3><?= e($t['problema_titulo']) ?></h3>
                        <p><?= e($t['problema_texto']) ?></p>
                        <ul class="lista-simple">
                            <li><i class="bi bi-x-circle"></i> <?= e($t['problema_1']) ?></li>
                            <li><i class="bi bi-x-circle"></i> <?= e($t['problema_2']) ?></li>
                            <li><i class="bi bi-x-circle"></i> <?= e($t['problema_3']) ?></li>
                        </ul>
                    </article>
                </div>
                <div class="col-md-6">
                    <article class="tarjeta-info tarjeta-solucion h-100">
                        <span class="etiqueta etiqueta-verde"><?= e($t['solucion_etiqueta']) ?></span>
                        <h3><?= e($t['solucion_titulo']) ?></h3>
                        <p><?= e($t['solucion_texto']) ?></p>
                        <ul class="lista-simple">
                            <li><i class="bi bi-check-circle"></i> <?= e($t['solucion_1']) ?></li>
                            <li><i class="bi bi-check-circle"></i> <?= e($t['solucion_2']) ?></li>
                            <li><i class="bi bi-check-circle"></i> <?= e($t['solucion_3']) ?></li>
                        </ul>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== RECORRIDO ===== -->
    <section id="recorrido" class="seccion seccion-oscura">
        <div class="container">
            <div class="seccion-titulo">
                <span class="kicker kicker-celeste"><?= e($t['recorrido_kicker']) ?></span>
                <h2><?= e($t['recorrido_titulo']) ?></h2>
                <p><?= e($t['recorrido_texto']) ?></p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <article class="tarjeta-zona zona-celeste">
                        <i class="bi bi-snow" aria-hidden="true"></i>
                        <h3><?= e($t['zona_refrigerado']) ?></h3>
                        <p><?= e($t['zona_refrigerado_texto']) ?></p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="tarjeta-zona zona-naranja">
                        <i class="bi bi-sun" aria-hidden="true"></i>
                        <h3><?= e($t['zona_seco']) ?></h3>
                        <p><?= e($t['zona_seco_texto']) ?></p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="tarjeta-zona zona-verde">
                        <i class="bi bi-boxes" aria-hidden="true"></i>
                        <h3><?= e($t['zona_voluminosos']) ?></h3>
                        <p><?= e($t['zona_voluminosos_texto']) ?></p>
                    </article>
                </div>
            </div>

            <ol class="proceso">
                <li><span class="proceso-numero">1</span><i class="bi bi-box-seam" aria-hidden="true"></i><span><?= e($t['proceso_1']) ?></span></li>
                <li><span class="proceso-numero">2</span><i class="bi bi-signpost-2" aria-hidden="true"></i><span><?= e($t['proceso_2']) ?></span></li>
                <li><span class="proceso-numero">3</span><i class="bi bi-check2-square" aria-hidden="true"></i><span><?= e($t['proceso_3']) ?></span></li>
                <li><span class="proceso-numero">4</span><i class="bi bi-check-circle" aria-hidden="true"></i><span><?= e($t['proceso_4']) ?></span></li>
            </ol>
        </div>
    </section>

    <!-- ===== INFORMACIÓN OPERATIVA ===== -->
    <section id="operativa" class="seccion">
        <div class="container">
            <div class="seccion-titulo">
                <span class="kicker"><?= e($t['operativa_kicker']) ?></span>
                <h2><?= e($t['operativa_titulo']) ?></h2>
                <p><?= e($t['operativa_texto']) ?></p>
            </div>
            <div class="row g-3">
                <div class="col-lg-4">
                    <article class="tarjeta-info tarjeta-operativa h-100">
                        <div class="operativa-cabecera">
                            <span class="operativa-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                            <span class="etiqueta etiqueta-azul"><?= e($t['op1_etiqueta']) ?></span>
                        </div>
                        <h3><?= e($t['op1_titulo']) ?></h3>
                        <p class="operativa-resumen"><?= e($t['op1_resumen']) ?></p>
                        <ol class="lista-protocolo">
                            <li><div><strong><?= e($t['op1_1']) ?></strong><small><?= e($t['op1_1_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op1_2']) ?></strong><small><?= e($t['op1_2_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op1_3']) ?></strong><small><?= e($t['op1_3_texto']) ?></small></div></li>
                        </ol>
                    </article>
                </div>
                <div class="col-lg-4">
                    <article class="tarjeta-info tarjeta-operativa h-100">
                        <div class="operativa-cabecera">
                            <span class="operativa-icono"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                            <span class="etiqueta etiqueta-azul"><?= e($t['op2_etiqueta']) ?></span>
                        </div>
                        <h3><?= e($t['op2_titulo']) ?></h3>
                        <p class="operativa-resumen"><?= e($t['op2_resumen']) ?></p>
                        <ol class="lista-protocolo">
                            <li><div><strong><?= e($t['op2_1']) ?></strong><small><?= e($t['op2_1_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op2_2']) ?></strong><small><?= e($t['op2_2_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op2_3']) ?></strong><small><?= e($t['op2_3_texto']) ?></small></div></li>
                        </ol>
                    </article>
                </div>
                <div class="col-lg-4">
                    <article class="tarjeta-info tarjeta-operativa h-100">
                        <div class="operativa-cabecera">
                            <span class="operativa-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
                            <span class="etiqueta etiqueta-azul"><?= e($t['op3_etiqueta']) ?></span>
                        </div>
                        <h3><?= e($t['op3_titulo']) ?></h3>
                        <p class="operativa-resumen"><?= e($t['op3_resumen']) ?></p>
                        <ol class="lista-protocolo">
                            <li><div><strong><?= e($t['op3_1']) ?></strong><small><?= e($t['op3_1_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op3_2']) ?></strong><small><?= e($t['op3_2_texto']) ?></small></div></li>
                            <li><div><strong><?= e($t['op3_3']) ?></strong><small><?= e($t['op3_3_texto']) ?></small></div></li>
                        </ol>
                    </article>
                </div>

                <!-- Estados por los que pasa un pedido en el sistema -->
                <div class="col-12">
                    <article class="tarjeta-info tarjeta-estados">
                        <div class="estados-encabezado">
                            <span class="operativa-icono"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
                            <div>
                                <h3><?= e($t['estados_titulo']) ?></h3>
                                <p><?= e($t['estados_texto']) ?></p>
                            </div>
                        </div>
                        <ol class="estados-pedido">
                            <li><span class="estado-punto estado-pendiente"></span><div><strong><?= e($t['estado_1']) ?></strong><small><?= e($t['estado_1_texto']) ?></small></div></li>
                            <li><span class="estado-punto estado-picking"></span><div><strong><?= e($t['estado_2']) ?></strong><small><?= e($t['estado_2_texto']) ?></small></div></li>
                            <li><span class="estado-punto estado-validar"></span><div><strong><?= e($t['estado_3']) ?></strong><small><?= e($t['estado_3_texto']) ?></small></div></li>
                            <li><span class="estado-punto estado-completado"></span><div><strong><?= e($t['estado_4']) ?></strong><small><?= e($t['estado_4_texto']) ?></small></div></li>
                        </ol>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== GALERÍA ===== -->
    <section id="galeria" class="seccion seccion-gris">
        <div class="container">
            <div class="seccion-titulo">
                <span class="kicker"><?= e($t['galeria_kicker']) ?></span>
                <h2><?= e($t['galeria_titulo']) ?></h2>
                <p><?= e($t['galeria_texto']) ?></p>
            </div>
            <div class="row g-3">
                <?php foreach ($galeria as $item) { $nombreItem = $t['galeria_nombres'][$item['nombre']] ?? $item['nombre']; ?>
                    <div class="col-sm-6 col-lg-4">
                        <figure class="tarjeta-galeria">
                            <div class="galeria-imagen">
                                <img src="<?= e($item['imagen']) ?>" alt="<?= e($nombreItem) ?>" loading="lazy"
                                     onerror="this.style.display='none'; this.parentElement.classList.add('sin-imagen');">
                                <div class="galeria-reemplazo"><i class="bi bi-image"></i><span><?= e($nombreItem) ?></span></div>
                            </div>
                            <figcaption><span><?= e($nombreItem) ?></span><small>UI</small></figcaption>
                        </figure>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- ===== CONTACTO ===== -->
    <section id="contacto" class="seccion">
        <div class="container">
            <div class="seccion-titulo">
                <span class="kicker"><?= e($t['contacto_kicker']) ?></span>
                <h2><?= e($t['contacto_titulo']) ?></h2>
                <p><?= e($t['contacto_texto']) ?></p>
            </div>
            <div class="row g-4">
                <div class="col-lg-7">
                    <form action="<?= e(url('inicio', 'contacto')) ?>" method="POST" class="formulario-contacto" id="contactForm"
                          data-error-nombre="<?= e($t['js_nombre_invalido']) ?>" data-error-mensaje="<?= e($t['js_mensaje_corto']) ?>">
                        <div class="mb-3">
                            <label for="nombre"><?= e($t['form_nombre']) ?></label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="<?= e($t['form_nombre_ph']) ?>" required minlength="2">
                        </div>
                        <div class="mb-3">
                            <label for="correo"><?= e($t['form_correo']) ?></label>
                            <input type="email" id="correo" name="correo" class="form-control" placeholder="<?= e($t['form_correo_ph']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="mensaje"><?= e($t['form_mensaje']) ?></label>
                            <textarea id="mensaje" name="mensaje" class="form-control" placeholder="<?= e($t['form_mensaje_ph']) ?>" rows="5" required minlength="10"></textarea>
                        </div>
                        <button type="submit" class="btn btn-verde"><?= e($t['form_enviar']) ?> <i class="bi bi-send"></i></button>
                    </form>
                </div>
                <div class="col-lg-5">
                    <aside class="tarjeta-equipo">
                        <div class="equipo-dato"><span><?= e($t['equipo']) ?></span><h3>Pickflow</h3></div>
                        <div class="equipo-dato"><span><?= e($t['equipo_contacto']) ?></span><p><i class="bi bi-envelope"></i> pickflow@soporte.com.uy</p></div>
                        <div class="equipo-dato"><span><?= e($t['equipo_ubicacion']) ?></span><p><i class="bi bi-geo-alt"></i> Maldonado 2125</p></div>
                        <div class="equipo-dato">
                            <span><?= e($t['equipo_redes']) ?></span>
                            <ul class="equipo-redes">
                                <?php foreach ($redes as [$red, $icono, $enlace]) { ?>
                                    <li><a href="<?= e($enlace) ?>" target="_blank" rel="noopener" aria-label="<?= e(sprintf($t['equipo_redes_aria'], $red)) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i><span><?= e($red) ?></span></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <i class="bi bi-box-seam equipo-decoracion" aria-hidden="true"></i>
                    </aside>
                </div>

                <!-- Ubicación de la institución educativa -->
                <div class="col-12">
                    <div class="mapa-institucion">
                        <iframe src="https://maps.google.com/maps?q=<?= e(rawurlencode($institucion['mapa'])) ?>&amp;z=15&amp;hl=<?= e($t['html_lang']) ?>&amp;output=embed"
                                title="<?= e(sprintf($t['mapa_titulo'], $institucion['nombre'])) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ===== PIE ===== -->
<footer class="sitio-pie">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= e(recurso('img/logo-blanco.png')) ?>" alt="PICKFLOW" class="sitio-logo">
            <span class="pie-separador">|</span> <?= e($t['pie_lema']) ?>
        </div>
        <div><?= e($t['pie_texto']) ?></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(recurso('js/script.js')) ?>"></script>
</body>
</html>
