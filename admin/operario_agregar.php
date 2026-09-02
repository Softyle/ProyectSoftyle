<?php
session_start(); require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../login.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $nombre=isset($_POST['nombre'])?trim($_POST['nombre']):''; $usuario=isset($_POST['usuario'])?trim($_POST['usuario']):''; $password=isset($_POST['password'])?$_POST['password']:'';
 if($nombre===''||$usuario===''||$password===''){ $error='Completá todos los campos.'; }
 elseif(strlen($password)<4){$error='La contraseña debe tener al menos 4 caracteres.';}
 else{
  $hash=password_hash($password,PASSWORD_DEFAULT); $rol='operario'; $estado=1;
  $stmt=$conexion->prepare("INSERT INTO usuarios (nombre,usuario,password,rol,estado) VALUES (?,?,?,?,?)"); $stmt->bind_param("ssssi",$nombre,$usuario,$hash,$rol,$estado);
  if($stmt->execute()){ $stmt->close(); header('Location: operarios.php?mensaje='.urlencode('Operario agregado correctamente.')); exit; }
  $error=($stmt->errno==1062)?'Ese usuario ya existe.':'No se pudo crear el operario: '.$stmt->error; $stmt->close();
 }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Agregar operario | Softyle</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="../CSS/sistema.css"></head><body class="sistema-body"><nav class="sistema-navbar"><div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Panel administrativo</small></div></div><a href="operarios.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a></nav><main class="admin-container"><a href="operarios.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver a operarios</a><section class="dashboard-header"><span class="dashboard-label">PERSONAL</span><h1>Agregar operario</h1><p>Registrá un nuevo operario.</p></section><?php if($error!==''){?><div class="alert alert-danger"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8');?></div><?php }?><div class="form-sistema-card"><form method="POST"><div class="mb-3"><label class="form-label">Nombre completo</label><input type="text" name="nombre" class="form-control" required></div><div class="mb-3"><label class="form-label">Usuario</label><input type="text" name="usuario" class="form-control" required></div><div class="mb-4"><label class="form-label">Contraseña</label><input type="password" name="password" class="form-control" required></div><button type="submit" class="btn-picking"><i class="bi bi-person-plus"></i> Crear operario</button></form></div></main></body></html>
