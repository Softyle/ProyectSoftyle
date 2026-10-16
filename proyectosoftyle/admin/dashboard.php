<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$r = $conexion->query("SELECT COUNT(*) AS total FROM Pedido WHERE estado = 'Pendiente'");
$pedidosPendientes = 0;
if ($r) { $fila = $r->fetch_assoc(); $pedidosPendientes = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM Producto");
$productos = 0;
if ($r) { $fila = $r->fetch_assoc(); $productos = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM Operario WHERE Estado = 'Activo'");
$operariosActivos = 0;
if ($r) { $fila = $r->fetch_assoc(); $operariosActivos = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM Pedido WHERE estado = 'Completado'");
$pedidosCompletados = 0;
if ($r) { $fila = $r->fetch_assoc(); $pedidosCompletados = (int)$fila['total']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de administración | SOFTYLE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../CSS/sistema.css">
</head>
<body class="sistema-body">
<nav class="sistema-navbar">
<div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Panel administrativo</small></div></div>
<div class="navbar-user"><div class="user-info"><span>Administrador</span><strong><?php echo htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8'); ?></strong></div><a href="../logout.php" class="btn-logout" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a></div>
</nav>
<main class="admin-container">
<section class="dashboard-header"><span class="dashboard-label">PANEL DE ADMINISTRACIÓN</span><h1>Panel de control</h1><p>Gestioná pedidos, productos y operarios desde un solo lugar.</p></section>
<section class="admin-stats">
<a href="pedidos.php?estado=Pendiente" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer"><i class="bi bi-clipboard-check"></i><div><span>Pedidos pendientes</span><strong><?php echo $pedidosPendientes; ?></strong></div></a>
<a href="productos.php" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer"><i class="bi bi-box-seam"></i><div><span>Productos</span><strong><?php echo $productos; ?></strong></div></a>
<a href="operarios.php" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer"><i class="bi bi-people"></i><div><span>Operarios activos</span><strong><?php echo $operariosActivos; ?></strong></div></a>
<a href="pedidos.php?estado=Completado" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer"><i class="bi bi-check-circle"></i><div><span>Pedidos completados</span><strong><?php echo $pedidosCompletados; ?></strong></div></a>
</section>

</main>
<script src="../JS/sistema.js"></script>
</body>
</html>
