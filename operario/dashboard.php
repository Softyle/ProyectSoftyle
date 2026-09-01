<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operario') {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Panel del Operario | Softyle</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- CSS -->
    <link
        rel="stylesheet"
        href="../CSS/sistema.css"
    >
</head>

<body class="sistema-body">

    <!-- ==============================
         NAVBAR
         ============================== -->

    <nav class="sistema-navbar">

        <div class="navbar-brand-sistema">
            <div class="navbar-logo">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>
                <strong>SOFTYLE</strong>
                <small>Sistema de Picking</small>
            </div>
        </div>

        <div class="navbar-user">

            <div class="user-info">
                <span>Hola,</span>
                <strong>
                    <?= htmlspecialchars($usuario) ?>
                </strong>
            </div>

            <a
                href="../logout.php"
                class="btn-logout"
                aria-label="Cerrar sesión"
            >
                <i class="bi bi-box-arrow-right"></i>
            </a>

        </div>

    </nav>


    <!-- ==============================
         CONTENIDO
         ============================== -->

    <main class="dashboard-container">

        <!-- Encabezado -->

        <section class="dashboard-header">

            <div>
                <span class="dashboard-label">
                    PANEL DEL OPERARIO
                </span>

                <h1>
                    Preparación de pedidos
                </h1>

                <p>
                    Seleccioná un pedido para comenzar el picking.
                </p>
            </div>

        </section>


        <!-- ==============================
             RESUMEN
             ============================== -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon blue">
                    <i class="bi bi-clipboard-check"></i>
                </div>

                <div>
                    <span>Pedidos asignados</span>
                    <strong>3</strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div>
                    <span>Completados</span>
                    <strong>8</strong>
                </div>

            </div>

        </section>


        <!-- ==============================
             PEDIDOS
             ============================== -->

        <section class="pedidos-section">

            <div class="section-title">

                <div>
                    <h2>Pedidos pendientes</h2>

                    <p>
                        Pedidos listos para ser preparados.
                    </p>
                </div>

            </div>


            <!-- Pedido 1 -->

            <article class="pedido-card">

                <div class="pedido-top">

                    <div>
                        <span class="pedido-label">
                            PEDIDO
                        </span>

                        <h3>#1048</h3>
                    </div>

                    <span class="estado pendiente">
                        Pendiente
                    </span>

                </div>


                <div class="pedido-info">

                    <div>
                        <i class="bi bi-box"></i>

                        <span>
                            <strong>12</strong>
                            productos
                        </span>
                    </div>


                    <div>
                        <i class="bi bi-geo-alt"></i>

                        <span>
                            Depósito principal
                        </span>
                    </div>

                </div>


                <a
                    href="picking.php?id=1048"
                    class="btn-picking"
                >
                    Comenzar picking
                    <i class="bi bi-arrow-right"></i>
                </a>

            </article>


            <!-- Pedido 2 -->

            <article class="pedido-card">

                <div class="pedido-top">

                    <div>
                        <span class="pedido-label">
                            PEDIDO
                        </span>

                        <h3>#1049</h3>
                    </div>

                    <span class="estado pendiente">
                        Pendiente
                    </span>

                </div>


                <div class="pedido-info">

                    <div>
                        <i class="bi bi-box"></i>

                        <span>
                            <strong>7</strong>
                            productos
                        </span>
                    </div>


                    <div>
                        <i class="bi bi-geo-alt"></i>

                        <span>
                            Depósito principal
                        </span>
                    </div>

                </div>


                <a
                    href="picking.php?id=1049"
                    class="btn-picking"
                >
                    Comenzar picking
                    <i class="bi bi-arrow-right"></i>
                </a>

            </article>


            <!-- Pedido 3 -->

            <article class="pedido-card">

                <div class="pedido-top">

                    <div>
                        <span class="pedido-label">
                            PEDIDO
                        </span>

                        <h3>#1050</h3>
                    </div>

                    <span class="estado en-proceso">
                        En proceso
                    </span>

                </div>


                <div class="pedido-info">

                    <div>
                        <i class="bi bi-box"></i>

                        <span>
                            <strong>15</strong>
                            productos
                        </span>
                    </div>


                    <div>
                        <i class="bi bi-geo-alt"></i>

                        <span>
                            Depósito principal
                        </span>
                    </div>

                </div>


                <a
                    href="picking.php?id=1050"
                    class="btn-picking secundario"
                >
                    Continuar picking
                    <i class="bi bi-arrow-right"></i>
                </a>

            </article>

        </section>

    </main>


    <script src="../JS/sistema.js"></script>

</body>

</html>