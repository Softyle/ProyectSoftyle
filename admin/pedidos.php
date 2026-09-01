<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* PEDIDOS DE DEMOSTRACIÓN */

$pedidos = array(

    array(
        "id" => 1048,
        "operario" => "Juan Pérez",
        "estado" => "En proceso",
        "fecha" => "01/09/2026"
    ),

    array(
        "id" => 1047,
        "operario" => "Martín Rodríguez",
        "estado" => "Completado",
        "fecha" => "01/09/2026"
    ),

    array(
        "id" => 1046,
        "operario" => "Sin asignar",
        "estado" => "Pendiente",
        "fecha" => "01/09/2026"
    ),

    array(
        "id" => 1045,
        "operario" => "Juan Pérez",
        "estado" => "Completado",
        "fecha" => "31/08/2026"
    )

);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pedidos | Softyle</title>

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

            <small>
                Panel administrativo
            </small>

        </div>

    </div>


    <a href="dashboard.php" class="btn-logout">

        <i class="bi bi-arrow-left"></i>

    </a>

</nav>


<main class="admin-container">


    <a href="dashboard.php" class="volver-link">

        <i class="bi bi-arrow-left"></i>

        Volver al panel

    </a>


    <section class="dashboard-header">

        <span class="dashboard-label">
            GESTIÓN
        </span>

        <h1>
            Pedidos
        </h1>

        <p>
            Controlá el estado de los pedidos del depósito.
        </p>

    </section>


    <div class="admin-table-card">


        <div class="table-responsive">


            <table class="table align-middle">


                <thead>

                    <tr>

                        <th>Pedido</th>

                        <th>Operario</th>

                        <th>Estado</th>

                        <th>Fecha</th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($pedidos as $pedido) { ?>

                    <tr>


                        <td>

                            <strong>

                                #

                                <?php
                                echo $pedido['id'];
                                ?>

                            </strong>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $pedido['operario']
                            );
                            ?>

                        </td>


                        <td>

                            <?php

                            if ($pedido['estado'] == "Completado") {

                            ?>

                                <span class="estado activo">
                                    Completado
                                </span>

                            <?php

                            } elseif ($pedido['estado'] == "En proceso") {

                            ?>

                                <span class="estado en-proceso">
                                    En proceso
                                </span>

                            <?php

                            } else {

                            ?>

                                <span class="estado">
                                    Pendiente
                                </span>

                            <?php

                            }

                            ?>

                        </td>


                        <td>

                            <?php
                            echo $pedido['fecha'];
                            ?>

                        </td>


                    </tr>

                <?php } ?>


                </tbody>

            </table>

        </div>

    </div>


</main>

</body>

</html>