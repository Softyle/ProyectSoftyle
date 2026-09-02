<?php
session_start();
require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: productos.php?error=' . urlencode('Producto inválido.')); exit; }
$stmt = $conexion->prepare("SELECT id, nombre, codigo, ubicacion, stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id); $stmt->execute(); $resultado = $stmt->get_result();
if ($resultado->num_rows !== 1) { $stmt->close(); header('Location: productos.php?error=' . urlencode('Producto no encontrado.')); exit; }
$producto = $resultado->fetch_assoc(); $stmt->close();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    if ($nombre === '' || $codigo === '' || $ubicacion === '' || $stock < 0) { $error = 'Completá correctamente todos los campos.'; }
    else {
        $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, codigo = ?, ubicacion = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("sssii", $nombre, $codigo, $ubicacion, $stock, $id);
        if ($stmt->execute()) { $stmt->close(); header('Location: productos.php?mensaje=' . urlencode('Producto modificado correctamente.')); exit; }
        $error = ($stmt->errno == 1062) ? 'El código del producto ya existe.' : 'No se pudo modificar el producto: ' . $stmt->error;
        $stmt->close();
    }
    $producto['nombre']=$nombre; $producto['codigo']=$codigo; $producto['ubicacion']=$ubicacion; $producto['stock']=$stock;
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Editar producto | Softyle</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="../CSS/sistema.css"></head><body class="sistema-body"><nav class="sistema-navbar"><div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Panel administrativo</small></div></div><a href="productos.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a></nav><main class="admin-container"><a href="productos.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver a productos</a><section class="dashboard-header"><span class="dashboard-label">INVENTARIO</span><h1>Editar producto</h1></section><?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?><div class="form-sistema-card"><form method="POST"><div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="mb-3"><label class="form-label">Código</label><input type="text" name="codigo" class="form-control" value="<?php echo htmlspecialchars($producto['codigo'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="mb-3"><label class="form-label">Ubicación</label><input type="text" name="ubicacion" class="form-control" value="<?php echo htmlspecialchars($producto['ubicacion'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="mb-4"><label class="form-label">Stock</label><input type="number" name="stock" class="form-control" min="0" value="<?php echo (int)$producto['stock']; ?>" required></div><button type="submit" class="btn-picking"><i class="bi bi-save"></i> Guardar cambios</button></form></div></main></body></html>
