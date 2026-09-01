<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ==========================================
   DATOS TEMPORALES
   Más adelante vendrán desde MySQL
========================================== */

$pedidosPendientes = 24;
$productos = 186;
$operariosActivos = 12;
$pedidosCompletados = 86;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de administración | SOFTYLE</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- CSS DEL SISTEMA -->

    <link
        rel="stylesheet"
        href="../CSS/sistema.css"
    >

</head>


<body class="sistema-body">


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="sistema-navbar">


    <div class="navbar-brand-sistema">

        <div class="navbar-logo">

            <i class="bi bi-box-seam"></i>

        </div>


        <div>

            <strong>SOFTYLE</strong>

            <small>
                Panel administrativo
            </small>

        </div>

    </div>



    <div class="navbar-user">


        <div class="user-info">

            <span>
                Administrador
            </span>

            <strong>

                <?php

                echo isset($_SESSION['usuario'])
                    ? htmlspecialchars($_SESSION['usuario'])
                    : "Administrador";

                ?>

            </strong>

        </div>


        <a
            href="../logout.php"
            class="btn-logout"
            title="Cerrar sesión"
        >

            <i class="bi bi-box-arrow-right"></i>

        </a>

    </div>


</nav>



<!-- =====================================================
     CONTENIDO PRINCIPAL
===================================================== -->

<main class="admin-container">


    <!-- ENCABEZADO -->

    <section class="dashboard-header">

        <span class="dashboard-label">
            PANEL DE ADMINISTRACIÓN
        </span>


        <h1>
            Panel de control
        </h1>


        <p>
            Gestioná pedidos, productos y operarios
            desde un solo lugar.
        </p>

    </section>



    <!-- =================================================
         ESTADÍSTICAS
    ================================================== -->

    <section class="admin-stats">


        <!-- PEDIDOS PENDIENTES -->

        <div class="admin-stat-card">

            <i class="bi bi-clipboard-check"></i>


            <div>

                <span>
                    Pedidos pendientes
                </span>


                <strong>
                    <?php echo $pedidosPendientes; ?>
                </strong>

            </div>

        </div>



        <!-- PRODUCTOS -->

        <div class="admin-stat-card">

            <i class="bi bi-box-seam"></i>


            <div>

                <span>
                    Productos
                </span>


                <strong>
                    <?php echo $productos; ?>
                </strong>

            </div>

        </div>



        <!-- OPERARIOS -->

        <div class="admin-stat-card">

            <i class="bi bi-people"></i>


            <div>

                <span>
                    Operarios activos
                </span>


                <strong>
                    <?php echo $operariosActivos; ?>
                </strong>

            </div>

        </div>



        <!-- PEDIDOS COMPLETADOS -->

        <div class="admin-stat-card">

            <i class="bi bi-check-circle"></i>


            <div>

                <span>
                    Pedidos completados
                </span>


                <strong>
                    <?php echo $pedidosCompletados; ?>
                </strong>

            </div>

        </div>


    </section>



    <!-- =================================================
         MENÚ DE ADMINISTRACIÓN
    ================================================== -->

    <section class="admin-menu">


        <div class="section-title">

            <h2>
                Gestión del sistema
            </h2>


            <p>
                Seleccioná una opción para administrar
                el funcionamiento del depósito.
            </p>

        </div>



        <div class="admin-menu-grid">


            <!-- =========================================
                 PEDIDOS
            ========================================== -->

            <a
                href="pedidos.php"
                class="admin-menu-card"
            >


                <i class="bi bi-clipboard-check"></i>


                <div>

                    <strong>
                        Pedidos
                    </strong>


                    <span>
                        Consultar y gestionar pedidos de picking.
                    </span>

                </div>


                <i class="bi bi-chevron-right arrow"></i>


            </a>



            <!-- =========================================
                 PRODUCTOS
            ========================================== -->

            <a
                href="productos.php"
                class="admin-menu-card"
            >


                <i class="bi bi-box-seam"></i>


                <div>

                    <strong>
                        Productos
                    </strong>


                    <span>
                        Gestionar inventario, productos y ubicaciones.
                    </span>

                </div>


                <i class="bi bi-chevron-right arrow"></i>


            </a>



            <!-- =========================================
                 OPERARIOS
            ========================================== -->

            <a
                href="operarios.php"
                class="admin-menu-card"
            >


                <i class="bi bi-people"></i>


                <div>

                    <strong>
                        Operarios
                    </strong>


                    <span>
                        Administrar usuarios, perfiles y asignaciones.
                    </span>

                </div>


                <i class="bi bi-chevron-right arrow"></i>


            </a>


        </div>


    </section>


</main>



<!-- JAVASCRIPT -->

<script src="../JS/sistema.js"></script>


</body>

</html>