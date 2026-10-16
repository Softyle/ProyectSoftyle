<?php
session_start();
require_once '../conexion.php';
if(!isset($_SESSION['usuario_id'])||$_SESSION['rol']!=='operario'){header('Location: ../login.php');exit;}
$pedido_id=isset($_GET['id'])?(int)$_GET['id']:0;
$operario_id=(string)$_SESSION['usuario_id'];
$mensaje='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirmar'])){
    $stmt=$conexion->prepare("UPDATE Pedido SET estado='Completado',recogido=TRUE,fecha_entrega=NOW() WHERE id_pedido=? AND ci_operario=? AND estado IN ('Pendiente','En Picking')");
    $stmt->bind_param('is',$pedido_id,$operario_id);$stmt->execute();$ok=$stmt->affected_rows>0;$stmt->close();
    if($ok){header('Location: dashboard.php');exit;}
    $mensaje='No se pudo completar el pedido.';
}

/* Al entrar al picking, el pedido pasa de Pendiente a En Picking */
$stmt=$conexion->prepare("UPDATE Pedido SET estado='En Picking' WHERE id_pedido=? AND ci_operario=? AND estado='Pendiente'");
$stmt->bind_param('is',$pedido_id,$operario_id);$stmt->execute();$stmt->close();

$stmt=$conexion->prepare("
SELECT p.id_pedido AS id,p.estado,pc.usuario AS cliente,
       pr.id_producto,pr.Nombre AS producto,t.Cantidad,
       u.pasillo,u.estante,u.posicion
FROM Pedido p
INNER JOIN Cliente c ON c.CI=p.ci_cliente
INNER JOIN Persona pc ON pc.CI=c.CI
INNER JOIN Tiene t ON t.id_pedido=p.id_pedido
INNER JOIN Producto pr ON pr.id_producto=t.id_producto
INNER JOIN Ubicacion u ON u.id_ubicacion=pr.id_ubicacion
WHERE p.id_pedido=? AND p.ci_operario=?
ORDER BY u.pasillo,u.estante,u.posicion,pr.Nombre");
$stmt->bind_param('is',$pedido_id,$operario_id);$stmt->execute();$res=$stmt->get_result();
$items=[];$cliente='';$estado='';
while($row=$res->fetch_assoc()){$items[]=$row;$cliente=$row['cliente'];$estado=$row['estado'];}
$stmt->close();
if(!$items){header('Location: dashboard.php');exit;}
$totalUnidades=array_sum(array_map(fn($x)=>(int)$x['Cantidad'],$items));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Picking #<?=$pedido_id?> | SOFTYLE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="../CSS/sistema.css">
<style>
.pick-list{display:grid;gap:13px}.pick-item{background:#fff;border:1px solid #e7edf4;border-radius:14px;padding:18px;display:grid;grid-template-columns:1fr auto auto;gap:20px;align-items:center;box-shadow:0 3px 12px rgba(20,45,80,.04)}
.location{background:#eef5ff;color:#2459b3;border-radius:10px;padding:10px 13px;font-weight:700}.qty{font-size:18px;font-weight:800}.summary-pick{background:#fff;border-radius:16px;padding:20px;margin:20px 0;border:1px solid #e8edf4}.complete-btn{width:100%;border:0;background:#18a957;color:#fff;border-radius:11px;padding:14px;font-weight:700;margin-top:20px}
@media(max-width:700px){.pick-item{grid-template-columns:1fr}.location{display:inline-block}}
</style></head><body class="sistema-body">
<nav class="sistema-navbar"><div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Sistema de Picking</small></div></div><a href="dashboard.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a></nav>
<main class="picking-container"><div class="picking-header"><a href="pedido.php?id=<?=$pedido_id?>" class="volver-link"><i class="bi bi-arrow-left"></i> Volver al pedido</a><span class="dashboard-label">PICKING EN CURSO</span><h1>Pedido #<?=$pedido_id?></h1><p>Cliente: <strong><?=htmlspecialchars($cliente,ENT_QUOTES,'UTF-8')?></strong></p></div>
<?php if($mensaje):?><div class="alert alert-danger"><?=htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8')?></div><?php endif;?>
<section class="summary-pick"><div class="d-flex justify-content-between"><div><span class="text-secondary">Productos diferentes</span><h3><?=count($items)?></h3></div><div class="text-end"><span class="text-secondary">Unidades a recoger</span><h3><?=$totalUnidades?></h3></div></div></section>
<section class="pick-list">
<?php foreach($items as $i=>$item):?>
<article class="pick-item"><div><span class="text-secondary small">PRODUCTO <?=($i+1)?></span><h3 class="h5 mb-1"><?=htmlspecialchars($item['producto'],ENT_QUOTES,'UTF-8')?></h3><span class="text-secondary">Código #<?=(int)$item['id_producto']?></span></div><div><span class="text-secondary small d-block">UBICACIÓN</span><span class="location"><i class="bi bi-geo-alt"></i> <?=htmlspecialchars($item['pasillo'].'-'.$item['estante'].'-'.$item['posicion'],ENT_QUOTES,'UTF-8')?></span></div><div><span class="text-secondary small d-block">CANTIDAD</span><span class="qty"><?=(int)$item['Cantidad']?> u.</span></div></article>
<?php endforeach;?>
</section>
<form method="POST" onsubmit="return confirm('¿Confirmás que recogiste todos los productos del pedido?');"><button type="submit" name="confirmar" value="1" class="complete-btn"><i class="bi bi-check-circle"></i> Confirmar pedido completado</button></form>
</main></body></html>
