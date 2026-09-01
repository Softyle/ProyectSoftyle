<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Eliminar producto | Softyle</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
            <small>Panel administrativo</small>
        </div>

    </div>

</nav>


<main class="admin-container">

    <div class="form-sistema-card text-center">

        <i
            class="bi bi-check-circle"
            style="font-size: 50px;"
        ></i>

        <h2 class="mt-3">
            Acción preparada
        </h2>

        <p>
            El producto seleccionado está preparado para ser eliminado
            cuando se conecte la base de datos.
        </p>

        <a
            href="productos.php"
            class="btn-picking"
        >
            Volver a productos
        </a>

    </div>

</main>

</body>
</html>