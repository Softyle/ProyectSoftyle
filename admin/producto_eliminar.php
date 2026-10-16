<?php
session_start();
require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $conexion->prepare("DELETE FROM Producto WHERE id_producto = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { $mensaje = 'Producto eliminado correctamente.'; } else { $mensaje = 'No se pudo eliminar el producto: ' . $stmt->error; }
    $stmt->close();
} else { $mensaje = 'Producto inválido.'; }
header('Location: productos.php?mensaje=' . urlencode($mensaje)); exit;
?>
