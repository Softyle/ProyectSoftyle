<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* DATOS DE DEMOSTRACIÓN */
$producto = array(
    "id" => $id,
    "nombre" => "Caja de tornillos",
    "codigo" => "P-001",
    "ubicacion" => "A-01-03",
    "stock" => 45
);

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $mensaje = "Producto modificado correctamente. Esta función se conectará a la base de datos.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar producto | Softyle</title>

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
            <small>Panel administrativo</small>
        </div>

    </div>

    <a href="productos.php" class="btn-logout">
        <i class="bi bi-arrow-left"></i>
    </a>

</nav>


<main class="admin-container">

    <a href="productos.php" class="volver-link">

        <i class="bi bi-arrow-left"></i>

        Volver a productos

    </a>


    <section class="dashboard-header">

        <span class="dashboard-label">
            INVENTARIO
        </span>

        <h1>Editar producto</h1>

    </section>


    <?php if ($mensaje != "") { ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php } ?>


    <div class="form-sistema-card">

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Nombre
                </label>

                <input
                    type="text"
                    name="nombre"
                    class="form-control"
                    value="<?php echo htmlspecialchars($producto['nombre']); ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label class="form-label">
                    Código
                </label>

                <input
                    type="text"
                    name="codigo"
                    class="form-control"
                    value="<?php echo htmlspecialchars($producto['codigo']); ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label class="form-label">
                    Ubicación
                </label>

                <input
                    type="text"
                    name="ubicacion"
                    class="form-control"
                    value="<?php echo htmlspecialchars($producto['ubicacion']); ?>"
                    required
                >

            </div>


            <div class="mb-4">

                <label class="form-label">
                    Stock
                </label>

                <input
                    type="number"
                    name="stock"
                    class="form-control"
                    min="0"
                    value="<?php echo htmlspecialchars($producto['stock']); ?>"
                    required
                >

            </div>


            <button type="submit" class="btn-picking">

                <i class="bi bi-save"></i>

                Guardar cambios

            </button>

        </form>

    </div>

</main>

</body>
</html>