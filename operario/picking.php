<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operario') {
    header('Location: ../login.php');
    exit;
}

$pedido_id = isset($_GET['id']) ? $_GET['id'] : '1048';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Picking #<?= htmlspecialchars($pedido_id) ?> | Softyle</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="../CSS/sistema.css">
</head>

<body class="sistema-body">

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

    <a href="dashboard.php" class="btn-logout" aria-label="Volver">
        <i class="bi bi-arrow-left"></i>
    </a>

</nav>


<main class="picking-container">

    <div class="picking-header">

        <a href="dashboard.php" class="volver-link">
            <i class="bi bi-arrow-left"></i>
            Volver a pedidos
        </a>

        <span class="dashboard-label">
            PICKING EN CURSO
        </span>

        <h1>
            Pedido #<?= htmlspecialchars($pedido_id) ?>
        </h1>

    </div>


    <!-- PROGRESO -->

    <section class="progreso-card">

        <div class="progreso-top">
            <span>Progreso del pedido</span>

            <strong id="progresoTexto">
                0 / 5
            </strong>
        </div>

        <div class="progress">
            <div
                class="progress-bar"
                id="progressBar"
                role="progressbar"
                style="width: 0%"
            ></div>
        </div>

    </section>


    <!-- PRODUCTO -->

    <section class="producto-picking">

        <span class="producto-numero">
            PRODUCTO 1 DE 5
        </span>

        <h2 id="productoNombre">
            Coca-Cola 1.5L
        </h2>

        <div class="ubicacion-box">

            <i class="bi bi-geo-alt-fill"></i>

            <div>
                <span>UBICACIÓN</span>

                <strong id="productoUbicacion">
                    B-01-09
                </strong>
            </div>

        </div>


        <div class="cantidad-box">

            <span>Cantidad a recoger</span>

            <strong id="productoCantidad">
                4 unidades
            </strong>

        </div>


        <button
            type="button"
            class="btn-confirmar-picking"
            id="confirmarPicking"
        >
            <i class="bi bi-check-circle"></i>

            Confirmar recolección
        </button>

    </section>


    <!-- INDICACIONES -->

    <section class="indicaciones-card">

        <div class="indicaciones-icon">
            <i class="bi bi-info-circle"></i>
        </div>

        <div>
            <strong>Indicaciones</strong>

            <p>
                Verificá el producto y la cantidad antes
                de confirmar la recolección.
            </p>
        </div>

    </section>

</main>


<script src="../JS/sistema.js"></script>

</body>
</html>
