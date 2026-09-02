<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$r = $conexion->query("SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'Pendiente'");
$pedidosPendientes = 0;
if ($r) { $fila = $r->fetch_assoc(); $pedidosPendientes = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM productos");
$productos = 0;
if ($r) { $fila = $r->fetch_assoc(); $productos = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'operario' AND estado = 1");
$operariosActivos = 0;
if ($r) { $fila = $r->fetch_assoc(); $operariosActivos = (int)$fila['total']; }

$r = $conexion->query("SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'Completado'");
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
<div class="admin-stat-card"><i class="bi bi-clipboard-check"></i><div><span>Pedidos pendientes</span><strong><?php echo $pedidosPendientes; ?></strong></div></div>
<div class="admin-stat-card"><i class="bi bi-box-seam"></i><div><span>Productos</span><strong><?php echo $productos; ?></strong></div></div>
<div class="admin-stat-card"><i class="bi bi-people"></i><div><span>Operarios activos</span><strong><?php echo $operariosActivos; ?></strong></div></div>
<div class="admin-stat-card"><i class="bi bi-check-circle"></i><div><span>Pedidos completados</span><strong><?php echo $pedidosCompletados; ?></strong></div></div>
</section>
<section class="admin-menu"><div class="section-title"><h2>Gestión del sistema</h2><p>Seleccioná una opción para administrar el funcionamiento del depósito.</p></div>
<div class="admin-menu-grid">
<a href="pedidos.php" class="admin-menu-card"><i class="bi bi-clipboard-check"></i><div><strong>Pedidos</strong><span>Consultar y gestionar pedidos de picking.</span></div><i class="bi bi-chevron-right arrow"></i></a>
<a href="productos.php" class="admin-menu-card"><i class="bi bi-box-seam"></i><div><strong>Productos</strong><span>Gestionar inventario, productos y ubicaciones.</span></div><i class="bi bi-chevron-right arrow"></i></a>
<a href="operarios.php" class="admin-menu-card"><i class="bi bi-people"></i><div><strong>Operarios</strong><span>Administrar usuarios, perfiles y asignaciones.</span></div><i class="bi bi-chevron-right arrow"></i></a>
</div></section>
</main>
<script src="../JS/sistema.js"></script>
</body>
</html>
