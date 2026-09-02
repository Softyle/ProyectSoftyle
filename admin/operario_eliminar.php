<?php
session_start(); require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../login.php'); exit; }
$id=isset($_GET['id'])?(int)$_GET['id']:0;
if($id>0){$stmt=$conexion->prepare("DELETE FROM usuarios WHERE id=? AND rol='operario'");$stmt->bind_param('i',$id);if($stmt->execute()){$mensaje='Operario eliminado correctamente.';}else{$mensaje='No se pudo eliminar el operario: '.$stmt->error;}$stmt->close();}else{$mensaje='Operario inválido.';}
header('Location: operarios.php?mensaje='.urlencode($mensaje));exit;
?>
