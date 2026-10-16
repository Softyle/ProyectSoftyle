<?php
session_start();
require_once '../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'operario') { header('Location: ../login.php'); exit; }

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$operario_id = (string)$_SESSION['usuario_id'];

$stmt=$conexion->prepare("
SELECT p.id_pedido AS id,p.estado,p.fecha_creacion,p.fecha_entrega,
       pc.usuario AS cliente, pc.direccion,
       COALESCE(SUM(t.Cantidad),0) AS total_productos,
       COUNT(DISTINCT t.id_producto) AS productos_diferentes
FROM Pedido p
INNER JOIN Cliente c ON c.CI=p.ci_cliente
INNER JOIN Persona pc ON pc.CI=c.CI
LEFT JOIN Tiene t ON t.id_pedido=p.id_pedido
WHERE p.id_pedido=? AND p.ci_operario=?
GROUP BY p.id_pedido,p.estado,p.fecha_creacion,p.fecha_entrega,pc.usuario,pc.direccion");
$stmt->bind_param('is',$pedido_id,$operario_id); $stmt->execute(); $res=$stmt->get_result();
if($res->num_rows!==1){header('Location: dashboard.php');exit;}
$pedido=$res->fetch_assoc(); $stmt->close();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pedido #<?=$pedido_id?> | SOFTYLE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="../CSS/sistema.css">
<style>
.detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}.clean-card{background:#fff;border:1px solid #e8edf4;border-radius:16px;padding:24px;box-shadow:0 4px 16px rgba(20,45,80,.05)}
.info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-top:20px}.info-item{padding:16px;background:#f7f9fc;border-radius:12px}.info-item span{font-size:12px;color:#718096;display:block;margin-bottom:4px}
.big-status{display:inline-block;padding:8px 13px;border-radius:20px;background:#e8f1ff;color:#2459b3;font-weight:700}.go-picking{display:flex;justify-content:center;align-items:center;gap:8px;text-decoration:none;background:#152d52;color:#fff;padding:14px;border-radius:11px;font-weight:700;margin-top:20px}
@media(max-width:800px){.detail-grid,.info-grid{grid-template-columns:1fr}}
</style></head><body class="sistema-body">
<nav class="sistema-navbar"><div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Sistema de Picking</small></div></div><a href="dashboard.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a></nav>
<main class="dashboard-container"><a href="dashboard.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver a mis pedidos</a>
<section class="dashboard-header mt-3"><span class="dashboard-label">DETALLE DEL PEDIDO</span><h1>Pedido #<?=$pedido_id?></h1><p>Revisá la información antes de comenzar la preparación.</p></section>
<div class="detail-grid">
<section class="clean-card"><div class="d-flex justify-content-between align-items-center"><h2 class="h4 m-0">Información del pedido</h2><span class="big-status"><?=htmlspecialchars($pedido['estado'],ENT_QUOTES,'UTF-8')?></span></div>
<div class="info-grid">
<div class="info-item"><span>Cliente</span><strong><?=htmlspecialchars($pedido['cliente'],ENT_QUOTES,'UTF-8')?></strong></div>
<div class="info-item"><span>Fecha de creación</span><strong><?=date('d/m/Y H:i',strtotime($pedido['fecha_creacion']))?></strong></div>
<div class="info-item"><span>Productos diferentes</span><strong><?=(int)$pedido['productos_diferentes']?></strong></div>
<div class="info-item"><span>Unidades totales</span><strong><?=(int)$pedido['total_productos']?></strong></div>
</div>
<?php if($pedido['estado']!=='Completado'):?><a href="picking.php?id=<?=$pedido_id?>" class="go-picking"><?=$pedido['estado']==='En Picking'?'Continuar picking':'Comenzar picking'?> <i class="bi bi-arrow-right"></i></a><?php endif;?>
</section>
<aside class="clean-card"><h2 class="h5">Entrega</h2><p class="text-secondary mb-1">Dirección del cliente</p><strong><?=htmlspecialchars($pedido['direccion']?:'Sin dirección registrada',ENT_QUOTES,'UTF-8')?></strong><hr><p class="text-secondary mb-1">Estado</p><strong><?=htmlspecialchars($pedido['estado'],ENT_QUOTES,'UTF-8')?></strong></aside>
</div></main></body></html>
