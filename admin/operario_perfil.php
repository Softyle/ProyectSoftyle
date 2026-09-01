<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* DATOS DE DEMOSTRACIÓN */

$operario = array(
    "id" => $id,
    "nombre" => "Juan Pérez",
    "usuario" => "jperez",
    "estado" => "Activo",
    "pedidos" => 28,
    "tiempo" => "07h 42m",
    "promedio" => "16m 32s"
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

    <title>Perfil del operario | Softyle</title>

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


    <a href="operarios.php" class="btn-logout">

        <i class="bi bi-arrow-left"></i>

    </a>

</nav>


<main class="admin-container">


    <a href="operarios.php" class="volver-link">

        <i class="bi bi-arrow-left"></i>

        Volver a operarios

    </a>


    <section class="perfil-operario">


        <div class="perfil-icon">

            <i class="bi bi-person"></i>

        </div>


        <div class="perfil-info">

            <span class="dashboard-label">

                PERFIL DEL OPERARIO

            </span>


            <h1>

                <?php
                echo htmlspecialchars(
                    $operario['nombre']
                );
                ?>

            </h1>


            <p>

                Usuario:

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $operario['usuario']
                    );
                    ?>

                </strong>

            </p>


            <span class="estado activo">

                <?php
                echo htmlspecialchars(
                    $operario['estado']
                );
                ?>

            </span>

        </div>

    </section>


    <section class="perfil-stats">


        <div class="perfil-stat">

            <i class="bi bi-clipboard-check"></i>

            <span>
                Pedidos completados
            </span>

            <strong>

                <?php
                echo $operario['pedidos'];
                ?>

            </strong>

        </div>


        <div class="perfil-stat">

            <i class="bi bi-clock"></i>

            <span>
                Tiempo trabajado
            </span>

            <strong>

                <?php
                echo $operario['tiempo'];
                ?>

            </strong>

        </div>


        <div class="perfil-stat">

            <i class="bi bi-stopwatch"></i>

            <span>
                Promedio por pedido
            </span>

            <strong>

                <?php
                echo $operario['promedio'];
                ?>

            </strong>

        </div>


    </section>


    <section class="admin-table-card">


        <div class="section-title">

            <h2>
                Historial de trabajo
            </h2>

            <p>
                Registro de jornadas del operario.
            </p>

        </div>


        <div class="table-responsive">

            <table class="table align-middle">


                <thead>

                    <tr>

                        <th>Fecha</th>

                        <th>Inicio</th>

                        <th>Fin</th>

                        <th>Tiempo</th>

                    </tr>

                </thead>


                <tbody>


                    <tr>

                        <td>
                            01/09/2026
                        </td>

                        <td>
                            08:02
                        </td>

                        <td>
                            16:15
                        </td>

                        <td>
                            8h 13m
                        </td>

                    </tr>


                    <tr>

                        <td>
                            31/08/2026
                        </td>

                        <td>
                            08:05
                        </td>

                        <td>
                            16:30
                        </td>

                        <td>
                            8h 25m
                        </td>

                    </tr>


                    <tr>

                        <td>
                            30/08/2026
                        </td>

                        <td>
                            08:00
                        </td>

                        <td>
                            15:58
                        </td>

                        <td>
                            7h 58m
                        </td>

                    </tr>


                </tbody>

            </table>

        </div>

    </section>


</main>

</body>

</html>