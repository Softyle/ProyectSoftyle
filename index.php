<?php
// index.php
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Softyle | Picking Asistido</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="CSS/estilos.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top softyle-navbar">

        <div class="container-fluid px-lg-5">

            <a
                class="navbar-brand d-flex align-items-center gap-2"
                href="#inicio">

                <img src="logo.png" alt="Softyle" class="logo-img">



            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal"
                aria-expanded="false"
                aria-label="Abrir menú">

                <span class="navbar-toggler-icon"></span>

            </button>


            <div
                class="collapse navbar-collapse"
                id="menuPrincipal">

                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="#inicio">
                            Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="#proyecto">
                            Sobre el Proyecto
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="#operativa">
                            Info. Operativa
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="#galeria">
                            Galería
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">

                        <a
                            class="btn btn-contacto"
                            href="#contacto">
                            Contacto
                        </a>

                    </li>
                    <li class="nav-item">
                        <a href="login.php" class="btn-login-nav">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Ingresar al sistema
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>

    <section
        id="inicio"
        class="hero-section">

        <div class="container position-relative">

            <div class="row align-items-center min-vh-100">

                <div class="col-lg-7 hero-content">

                    <div class="section-kicker green-text">
                        SISTEMA DE ORGANIZACIÓN LOGÍSTICA
                    </div>


                    <h1 class="hero-title">

                        Del depósito caótico al
                        <span>picking asistido.</span>

                    </h1>


                    <p class="hero-description">

                        Un sistema que organiza el inventario y muestra
                        rutas claras para optimizar el almacenamiento
                        y la preparación de pedidos.

                    </p>


                    <div class="d-flex flex-wrap gap-3">

                        <a
                            href="#proyecto"
                            class="btn btn-primary-softyle">
                            Conocé el proyecto
                        </a>


                        <a
                            href="#recorrido"
                            class="btn btn-outline-softyle">
                            Ver cómo funciona
                        </a>

                    </div>

                </div>


                <!-- Tarjeta flotante -->

                <div class="col-lg-5">

                    <div class="picking-card">

                        <div class="picking-card-header">

                            <span>
                                Picking asistido
                            </span>

                            <span class="status-dot"></span>

                        </div>


                        <div class="picking-label">
                            UBICACIÓN
                        </div>


                        <div class="location-code">
                            B-01-09
                        </div>


                        <button
                            class="btn btn-confirmar w-100"
                            type="button"
                            id="confirmarPicking">
                            Confirmar recolección
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section
        id="proyecto"
        class="light-section">

        <div class="container">

            <div class="section-heading">

                <div class="section-kicker blue-text">
                    SOBRE EL PROYECTO
                </div>

                <h2>
                    Historia y objetivos
                </h2>

                <p>
                    El problema que resolvemos y la forma en que
                    pensamos la solución.
                </p>

            </div>


            <div class="row g-4">

                <div class="col-md-6">

                    <div class="info-card h-100">

                        <span class="tag tag-orange">
                            EL PROBLEMA
                        </span>


                        <h3>
                            El depósito caótico
                        </h3>


                        <p>
                            Pedidos extraviados, tiempos de picking
                            elevados y errores humanos por falta de
                            organización espacial.
                        </p>


                        <div class="card-line"></div>

                        <ul class="simple-list">

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Ubicaciones difíciles de encontrar
                            </li>

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Demoras en la preparación
                            </li>

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Errores de almacenamiento
                            </li>

                        </ul>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="info-card h-100">

                        <span class="tag tag-green">
                            LA SOLUCIÓN
                        </span>


                        <h3>
                            Picking asistido
                        </h3>


                        <p>
                            Flujo de rutas claras, organización por
                            zonas y digitalización de cada movimiento
                            de mercadería.
                        </p>


                        <div class="card-line"></div>

                        <ul class="simple-list">

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Rutas de picking optimizadas
                            </li>

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Organización por zonas
                            </li>

                            <li>
                                <i class="bi bi-arrow-right"></i>
                                Seguimiento de pedidos
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section
        id="recorrido"
        class="dark-section">

        <div class="container">

            <div class="section-heading dark-heading">

                <div class="section-kicker blue-text">
                    RECORRIDO DE INNOVACIÓN
                </div>

                <h2>
                    Organización por zonas
                </h2>

                <p>
                    Así se distribuye el depósito y se arma una
                    orden de picking.
                </p>

            </div>

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="zone-card">

                        <span class="zone-dot cyan"></span>

                        <h3>
                            Refrigerado
                        </h3>

                        <p>
                            Control de temperatura y espacios
                            particulares.
                        </p>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="zone-card">

                        <span class="zone-dot orange"></span>

                        <h3>
                            Seco
                        </h3>

                        <p>
                            Almacenamiento general de productos
                            de alta rotación.
                        </p>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="zone-card">

                        <span class="zone-dot green"></span>

                        <h3>
                            Voluminosos
                        </h3>

                        <p>
                            Bultos grandes, accesos amplios y
                            espacios especiales.
                        </p>

                    </div>

                </div>

            </div>

            <div class="process-container mt-4">

                <div class="process-item">
                    <i class="bi bi-box-seam"></i>
                    <span>Orden asignada</span>
                </div>


                <div class="process-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>


                <div class="process-item">
                    <i class="bi bi-signpost-2"></i>
                    <span>Ruta de picking</span>
                </div>


                <div class="process-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>


                <div class="process-item">
                    <i class="bi bi-check2-square"></i>
                    <span>Checklist</span>
                </div>


                <div class="process-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>


                <div class="process-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Orden completada</span>
                </div>

            </div>

        </div>

    </section>

    <section
        id="operativa"
        class="light-section">

        <div class="container">

            <div class="section-heading">

                <div class="section-kicker blue-text">
                    INFORMACIÓN OPERATIVA
                </div>

                <h2>
                    Protocolos, guías y perfiles
                </h2>

                <p>
                    Todo lo que un operador necesita saber antes
                    de empezar a trabajar.
                </p>

            </div>


            <div class="row g-3">

                <div class="col-lg-4">

                    <div class="info-card operational-card">

                        <span class="tag tag-blue">
                            01 · ALMACENAMIENTO
                        </span>

                        <h3>
                            Protocolos de ingreso
                        </h3>

                        <ul class="protocol-list">

                            <li>
                                <i class="bi bi-plus"></i>
                                Verificación de remito
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Control de rotulado
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Registro de ingreso
                            </li>

                        </ul>

                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="info-card operational-card">

                        <span class="tag tag-blue">
                            02 · PICKING
                        </span>

                        <h3>
                            Guía de picking
                        </h3>

                        <ul class="protocol-list">

                            <li>
                                <i class="bi bi-plus"></i>
                                Lectura de ubicación
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Confirmación de cantidad
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Checklist final
                            </li>

                        </ul>

                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="info-card operational-card">

                        <span class="tag tag-blue">
                            03 · ROLES
                        </span>

                        <h3>
                            Horarios y perfiles
                        </h3>

                        <ul class="protocol-list">

                            <li>
                                <i class="bi bi-plus"></i>
                                Administrador
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Operador
                            </li>

                            <li>
                                <i class="bi bi-plus"></i>
                                Plazos de entrega
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section
        id="galeria"
        class="light-section gallery-section">

        <div class="container">

            <div class="section-heading">

                <div class="section-kicker blue-text">
                    GALERÍA MULTIMEDIA
                </div>

                <h2>
                    Maqueta y mockups
                </h2>

                <p>
                    Fotos y videos de la maqueta en escala, más
                    las interfaces diseñadas en Figma.
                </p>

            </div>


            <div class="row g-3">

                <?php

                $galeria = [
                    [
                        "nombre" => "Login",
                        "imagen" => "img/login.jpg"
                    ],
                    [
                        "nombre" => "Órdenes",
                        "imagen" => "img/ordenes.jpg"
                    ],
                    [
                        "nombre" => "Picking",
                        "imagen" => "img/picking.jpg"
                    ],
                    [
                        "nombre" => "Checklist",
                        "imagen" => "img/checklist.jpg"
                    ],
                    [
                        "nombre" => "Recursos",
                        "imagen" => "img/recursos.jpg"
                    ],
                    [
                        "nombre" => "Maqueta física",
                        "imagen" => "img/maqueta.jpg"
                    ]
                ];

                foreach ($galeria as $item):
                ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="gallery-card">

                            <div class="gallery-image">

                                <img
                                    src="<?= htmlspecialchars($item['imagen']) ?>"
                                    alt="<?= htmlspecialchars($item['nombre']) ?>"
                                    onerror="this.style.display='none'; this.parentElement.classList.add('image-placeholder');">

                                <div class="placeholder-content">

                                    <i class="bi bi-image"></i>

                                    <span>
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </span>

                                </div>

                            </div>


                            <div class="gallery-footer">

                                <span>
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </span>

                                <small>
                                    UI
                                </small>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </section>

    <section
        id="contacto"
        class="light-section contact-section">

        <div class="container">

            <div class="section-heading">

                <div class="section-kicker blue-text">
                    CONTACTO
                </div>

                <h2>
                    Soporte técnico y equipo
                </h2>

                <p>
                    Formulario de contacto técnico o dudas
                    sobre la solución.
                </p>

            </div>


            <div class="row g-4">

                <div class="col-lg-7">

                    <form
                        action="contacto.php"
                        method="POST"
                        class="contact-form"
                        id="contactForm">

                        <div class="mb-3">

                            <label for="nombre">
                                NOMBRE
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                placeholder="Tu nombre"
                                required
                                minlength="2">

                        </div>


                        <div class="mb-3">

                            <label for="correo">
                                CORREO ELECTRÓNICO
                            </label>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                class="form-control"
                                placeholder="tu@email.com"
                                required>

                        </div>


                        <div class="mb-3">

                            <label for="mensaje">
                                MENSAJE
                            </label>

                            <textarea
                                id="mensaje"
                                name="mensaje"
                                class="form-control"
                                placeholder="Escribí tu mensaje..."
                                rows="5"
                                required
                                minlength="10"></textarea>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary-softyle">
                            Enviar mensaje
                        </button>

                    </form>

                </div>

                <div class="col-lg-5">

                    <div class="team-card">

                        <div class="team-title">
                            EQUIPO
                        </div>

                        <h3>
                            Softyle
                        </h3>


                        <div class="team-title">
                            CONTACTO
                        </div>

                        <p>
                            softyle@institucion.edu.uy
                        </p>


                        <div class="team-title">
                            UBICACIÓN
                        </div>

                        <p>
                            Montevideo, Uruguay
                        </p>


                        <div class="team-decoration">

                            <i class="bi bi-box-seam"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <footer class="softyle-footer">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-md-6">

                    <strong>
                        Softyle
                    </strong>

                    <span class="footer-separator">
                        |
                    </span>

                    Picking asistido

                </div>


                <div class="col-md-6 text-md-end mt-2 mt-md-0">

                    Proyecto de innovación logística

                </div>

            </div>

        </div>

    </footer>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

    <script src="JS/script.js"></script>

</body>

</html>