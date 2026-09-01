<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* DATOS DE DEMOSTRACIÓN */
$productos = array(
    array(
        "id" => 1,
        "nombre" => "Caja de tornillos",
        "codigo" => "P-001",
        "ubicacion" => "A-01-03",
        "stock" => 45
    ),
    array(
        "id" => 2,
        "nombre" => "Cable de red",
        "codigo" => "P-002",
        "ubicacion" => "B-02-01",
        "stock" => 32
    ),
    array(
        "id" => 3,
        "nombre" => "Teclado inalámbrico",
        "codigo" => "P-003",
        "ubicacion" => "C-01-05",
        "stock" => 18
    ),
    array(
        "id" => 4,
        "nombre" => "Mouse óptico",
        "codigo" => "P-004",
        "ubicacion" => "C-01-06",
        "stock" => 27
    )
);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos | Softyle</title>

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
            INVENTARIO
        </span>

        <h1>Productos</h1>

        <p>
            Gestioná los productos almacenados en el depósito.
        </p>

    </section>


    <div class="admin-actions">

        <a href="producto_agregar.php" class="btn-picking">

            <i class="bi bi-plus-circle"></i>

            Agregar producto

        </a>

    </div>


    <div class="admin-table-card">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>Producto</th>
                        <th>Código</th>
                        <th>Ubicación</th>
                        <th>Stock</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($productos as $producto) { ?>

                    <tr>

                        <td>

                            <strong>
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </strong>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($producto['codigo']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($producto['ubicacion']); ?>
                        </td>

                        <td>

                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($producto['stock']); ?>
                            </span>

                        </td>

                        <td>

                            <a
                                href="producto_editar.php?id=<?php echo $producto['id']; ?>"
                                class="btn btn-sm btn-primary"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>

                            <a
                                href="producto_eliminar.php?id=<?php echo $producto['id']; ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('¿Seguro que querés eliminar este producto?');"
                            >
                                <i class="bi bi-trash"></i>
                            </a>

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