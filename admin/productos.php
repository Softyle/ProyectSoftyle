<?php
session_start();
require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../login.php'); exit; }
$productos = array();
$resultado = $conexion->query("SELECT id, nombre, codigo, ubicacion, stock FROM productos ORDER BY id DESC");
if ($resultado) { while ($fila = $resultado->fetch_assoc()) { $productos[] = $fila; } }
$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Productos | Softyle</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="../CSS/sistema.css"></head><body class="sistema-body">
<nav class="sistema-navbar"><div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Panel administrativo</small></div></div><a href="dashboard.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a></nav>
<main class="admin-container"><a href="dashboard.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver al panel</a><section class="dashboard-header"><span class="dashboard-label">INVENTARIO</span><h1>Productos</h1><p>Gestioná los productos almacenados en el depósito.</p></section>
<?php if ($mensaje !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<div class="admin-actions"><a href="producto_agregar.php" class="btn-picking"><i class="bi bi-plus-circle"></i> Agregar producto</a></div>
<div class="admin-table-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Producto</th><th>Código</th><th>Ubicación</th><th>Stock</th><th>Acciones</th></tr></thead><tbody>
<?php if (count($productos) === 0) { ?><tr><td colspan="5" class="text-center">No hay productos registrados.</td></tr><?php } ?>
<?php foreach ($productos as $producto) { ?><tr><td><strong><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo htmlspecialchars($producto['codigo'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($producto['ubicacion'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="badge bg-success"><?php echo (int)$producto['stock']; ?></span></td><td><a href="producto_editar.php?id=<?php echo (int)$producto['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a> <a href="producto_eliminar.php?id=<?php echo (int)$producto['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que querés eliminar este producto?');"><i class="bi bi-trash"></i></a></td></tr><?php } ?>
</tbody></table></div></div></main></body></html>
