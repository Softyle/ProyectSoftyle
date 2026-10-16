<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'operario') {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'] ?? '';
$id = (string)$_SESSION['usuario_id'];

function contarPedidos($conexion, $id, $estado) {
    $stmt = $conexion->prepare("SELECT COUNT(*) total FROM Pedido WHERE ci_operario=? AND estado=?");
    $stmt->bind_param('ss', $id, $estado);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

$pendientes = contarPedidos($conexion, $id, 'Pendiente');
$enPicking = contarPedidos($conexion, $id, 'En Picking');
$completados = contarPedidos($conexion, $id, 'Completado');
$asignados = $pendientes + $enPicking;

$stmt = $conexion->prepare("
    SELECT p.id_pedido AS id, p.estado, p.fecha_creacion AS fecha,
           pc.usuario AS cliente,
           COUNT(DISTINCT t.id_producto) AS productos_diferentes,
           COALESCE(SUM(t.Cantidad),0) AS total_productos
    FROM Pedido p
    INNER JOIN Cliente c ON c.CI=p.ci_cliente
    INNER JOIN Persona pc ON pc.CI=c.CI
    LEFT JOIN Tiene t ON t.id_pedido=p.id_pedido
    WHERE p.ci_operario=? AND p.estado IN ('Pendiente','En Picking')
    GROUP BY p.id_pedido,p.estado,p.fecha_creacion,pc.usuario
    ORDER BY FIELD(p.estado,'En Picking','Pendiente'), p.fecha_creacion ASC
");
$stmt->bind_param('s',$id);
$stmt->execute();
$pedidos=$stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel del Operario | SOFTYLE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../CSS/sistema.css">
<style>
.op-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:24px 0 32px}
.op-stat{background:#fff;border-radius:16px;padding:18px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 16px rgba(20,45,80,.06);border:1px solid #eef2f7}
.op-stat .ico{width:46px;height:46px;border-radius:12px;background:#eef5ff;display:flex;align-items:center;justify-content:center;font-size:21px;color:#2563eb}
.op-stat strong{font-size:25px;display:block;color:#102a4c}.op-stat span{font-size:13px;color:#607089}
.order-list{display:grid;gap:16px}.order-row{background:#fff;border-radius:16px;padding:20px;display:grid;grid-template-columns:1.2fr 1fr 1fr auto;align-items:center;gap:20px;border:1px solid #e8edf4;box-shadow:0 4px 16px rgba(20,45,80,.05)}
.order-row h3{margin:3px 0 0;font-size:21px}.muted{color:#718096;font-size:13px}.badge-op{display:inline-block;padding:7px 11px;border-radius:20px;font-size:12px;font-weight:700}.pend{background:#fff4d8;color:#956500}.pick{background:#e8f1ff;color:#2459b3}
.action-op{display:inline-flex;align-items:center;gap:8px;text-decoration:none;background:#152d52;color:white;padding:11px 16px;border-radius:10px;font-weight:600;white-space:nowrap}
.empty-op{background:#fff;border:1px dashed #cad5e2;border-radius:16px;text-align:center;padding:45px 20px;color:#607089}.empty-op i{font-size:38px;display:block;margin-bottom:10px}
@media(max-width:900px){.op-stats{grid-template-columns:repeat(2,1fr)}.order-row{grid-template-columns:1fr}.action-op{justify-content:center}}
</style>
</head>
<body class="sistema-body">
<nav class="sistema-navbar">
<div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Sistema de Picking</small></div></div>
<div class="navbar-user"><div class="user-info"><span>Operario</span><strong><?=htmlspecialchars($usuario,ENT_QUOTES,'UTF-8')?></strong></div><a href="../logout.php" class="btn-logout" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a></div>
</nav>
<main class="dashboard-container">
<section class="dashboard-header"><span class="dashboard-label">PANEL DEL OPERARIO</span><h1>Mis pedidos</h1><p>Gestioná los pedidos que te fueron asignados y completá el proceso de picking.</p></section>

<section class="op-stats">
<div class="op-stat"><div class="ico"><i class="bi bi-clipboard-check"></i></div><div><span>Pedidos asignados</span><strong><?=$asignados?></strong></div></div>
<div class="op-stat"><div class="ico"><i class="bi bi-hourglass-split"></i></div><div><span>Pendientes</span><strong><?=$pendientes?></strong></div></div>
<div class="op-stat"><div class="ico"><i class="bi bi-box-seam"></i></div><div><span>En picking</span><strong><?=$enPicking?></strong></div></div>
<div class="op-stat"><div class="ico"><i class="bi bi-check-circle"></i></div><div><span>Completados</span><strong><?=$completados?></strong></div></div>
</section>

<section class="pedidos-section">
<div class="section-title"><div><h2>Pedidos asignados</h2><p>Primero aparecen los pedidos que ya están en preparación.</p></div></div>
<div class="order-list">
<?php if($pedidos->num_rows===0): ?>
<div class="empty-op"><i class="bi bi-clipboard-check"></i><strong>No tenés pedidos pendientes</strong><div>Cuando el administrador te asigne uno, aparecerá acá.</div></div>
<?php endif; ?>
<?php while($p=$pedidos->fetch_assoc()): ?>
<article class="order-row">
<div><span class="muted">PEDIDO</span><h3>#<?=(int)$p['id']?></h3><span class="muted"><?=htmlspecialchars(date('d/m/Y H:i',strtotime($p['fecha'])),ENT_QUOTES,'UTF-8')?></span></div>
<div><span class="muted">CLIENTE</span><div><strong><?=htmlspecialchars($p['cliente'],ENT_QUOTES,'UTF-8')?></strong></div></div>
<div><span class="badge-op <?=$p['estado']==='En Picking'?'pick':'pend'?>"><?=htmlspecialchars($p['estado'],ENT_QUOTES,'UTF-8')?></span><div class="muted mt-2"><?=(int)$p['total_productos']?> unidades · <?=(int)$p['productos_diferentes']?> productos</div></div>
<a class="action-op" href="pedido.php?id=<?=(int)$p['id']?>"><?=$p['estado']==='En Picking'?'Continuar':'Ver pedido'?> <i class="bi bi-arrow-right"></i></a>
</article>
<?php endwhile; ?>
</div>
</section>
</main>
<script src="../JS/sistema.js"></script>
</body></html>
