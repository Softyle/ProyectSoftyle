<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operario') {
    header('Location: ../login.php');
    exit;
}

$pedido_id = $_GET['id'] ?? '1048';
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pedido #<?= htmlspecialchars($pedido_id) ?> | Softyle</title>

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

    <a href="dashboard.php" class="btn-logout">
        <i class="bi bi-arrow-left"></i>
    </a>

</nav>


<main class="dashboard-container">

    <a href="dashboard.php" class="volver-link">
        <i class="bi bi-arrow-left"></i>
        Volver
    </a>

    <span class="dashboard-label">
        DETALLE DEL PEDIDO
    </span>

    <h1 class="pedido-detalle-title">
        Pedido #<?= htmlspecialchars($pedido_id) ?>
    </h1>


    <div class="pedido-detalle-card">

        <div class="pedido-detalle-header">

            <div>
                <span>ESTADO</span>

                <strong class="estado en-proceso">
                    En preparación
                </strong>
            </div>

            <i class="bi bi-box-seam"></i>

        </div>


        <div class="detalle-lista">

            <div>
                <i class="bi bi-box"></i>

                <span>
                    Productos
                    <strong>5</strong>
                </span>
            </div>

            <div>
                <i class="bi bi-person"></i>

                <span>
                    Cliente
                    <strong>Cliente general</strong>
                </span>
            </div>

            <div>
                <i class="bi bi-geo-alt"></i>

                <span>
                    Depósito
                    <strong>Depósito principal</strong>
                </span>
            </div>

        </div>


        <a
            href="picking.php?id=<?= htmlspecialchars($pedido_id) ?>"
            class="btn-picking"
        >
            Comenzar picking
            <i class="bi bi-arrow-right"></i>
        </a>

    </div>

</main>


<script src="../JS/sistema.js"></script>

</body>
</html>
