<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';
$error = '';

// Asignar un pedido pendiente a un operario activo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $pedidoId = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
    $operarioCi = trim($_POST['operario_ci'] ?? '');

    if ($pedidoId <= 0 || $operarioCi === '') {
        $error = 'Seleccioná un operario para realizar la asignación.';
    } else {
        // Comprueba que el CI corresponda a un operario activo.
        $stmt = $conexion->prepare("SELECT CI FROM Operario WHERE CI = ? AND Estado = 'Activo'");
        $stmt->bind_param('s', $operarioCi);
        $stmt->execute();
        $esOperarioActivo = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        if (!$esOperarioActivo) {
            $error = 'El operario seleccionado no está activo.';
        } else {
            // Solo permite asignar pedidos pendientes que todavía no tengan operario.
            $stmt = $conexion->prepare("UPDATE Pedido SET ci_operario = ? WHERE id_pedido = ? AND estado = 'Pendiente' AND ci_operario IS NULL");
            $stmt->bind_param('si', $operarioCi, $pedidoId);
            $stmt->execute();
            if ($stmt->affected_rows === 1) {
                $mensaje = 'Pedido #' . $pedidoId . ' asignado correctamente.';
            } else {
                $error = 'El pedido ya fue asignado, no está pendiente o no existe.';
            }
            $stmt->close();
        }
    }
}

// Filtro enviado desde las tarjetas del dashboard.
$estado = $_GET['estado'] ?? 'Todos';
$estadosPermitidos = ['Todos', 'Pendiente', 'En Picking', 'Completado'];
if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'Todos';
}

// Operarios activos disponibles para asignación.
$operarios = [];
$sqlOperarios = "SELECT o.CI, p.usuario FROM Operario o INNER JOIN Persona p ON p.CI = o.CI WHERE o.Estado = 'Activo' ORDER BY p.usuario";
$resOperarios = $conexion->query($sqlOperarios);
if ($resOperarios) {
    while ($fila = $resOperarios->fetch_assoc()) {
        $operarios[] = $fila;
    }
}

// Lista de pedidos. Persona se usa tanto para cliente como para operario.
$pedidos = [];
$sql = "SELECT
            pe.id_pedido AS id,
            pe.estado,
            pe.fecha_creacion AS fecha,
            pe.ci_operario,
            cliente.usuario AS cliente,
            operario.usuario AS operario,
            COALESCE(SUM(t.Cantidad), 0) AS total_productos
        FROM Pedido pe
        INNER JOIN Cliente c ON c.CI = pe.ci_cliente
        INNER JOIN Persona cliente ON cliente.CI = c.CI
        LEFT JOIN Persona operario ON operario.CI = pe.ci_operario
        LEFT JOIN Tiene t ON t.id_pedido = pe.id_pedido";

if ($estado !== 'Todos') {
    $sql .= " WHERE pe.estado = ?";
}

$sql .= " GROUP BY pe.id_pedido, pe.estado, pe.fecha_creacion, pe.ci_operario, cliente.usuario, operario.usuario
          ORDER BY pe.id_pedido DESC";

$stmt = $conexion->prepare($sql);
if ($estado !== 'Todos') {
    $stmt->bind_param('s', $estado);
}
$stmt->execute();
$resultado = $stmt->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $pedidos[] = $fila;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos | Softyle</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../CSS/sistema.css">
</head>
<body class="sistema-body">
<nav class="sistema-navbar">
    <div class="navbar-brand-sistema"><div class="navbar-logo"><i class="bi bi-box-seam"></i></div><div><strong>SOFTYLE</strong><small>Panel administrativo</small></div></div>
    <a href="dashboard.php" class="btn-logout"><i class="bi bi-arrow-left"></i></a>
</nav>
<main class="admin-container">
    <a href="dashboard.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver al panel</a>
    <section class="dashboard-header">
        <span class="dashboard-label">GESTIÓN</span>
        <h1>Pedidos<?php echo $estado !== 'Todos' ? ' - ' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') : ''; ?></h1>
        <p>Consultá pedidos, asigná pendientes a operarios y revisá quién completó cada pedido.</p>
    </section>

    <?php if ($mensaje !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <a class="btn <?php echo $estado === 'Todos' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="pedidos.php">Todos</a>
        <a class="btn <?php echo $estado === 'Pendiente' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="pedidos.php?estado=Pendiente">Pendientes</a>
        <a class="btn <?php echo $estado === 'En Picking' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="pedidos.php?estado=En%20Picking">En picking</a>
        <a class="btn <?php echo $estado === 'Completado' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="pedidos.php?estado=Completado">Completados</a>
    </div>

    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Operario</th><th>Productos</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
                <tbody>
                <?php if (count($pedidos) === 0) { ?>
                    <tr><td colspan="7" class="text-center">No hay pedidos en este estado.</td></tr>
                <?php } ?>
                <?php foreach ($pedidos as $pedido) { ?>
                    <tr>
                        <td><strong>#<?php echo (int)$pedido['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($pedido['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($pedido['operario']) { ?>
                                <strong><?php echo htmlspecialchars($pedido['operario'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if ($pedido['estado'] === 'Completado') { ?><br><small class="text-muted">Completado por este operario</small><?php } ?>
                            <?php } else { ?>
                                <span class="text-muted">Sin asignar</span>
                            <?php } ?>
                        </td>
                        <td><?php echo (int)$pedido['total_productos']; ?></td>
                        <td>
                            <?php if ($pedido['estado'] === 'Completado') { ?>
                                <span class="estado activo">Completado</span>
                            <?php } elseif ($pedido['estado'] === 'En Picking') { ?>
                                <span class="estado en-proceso">En Picking</span>
                            <?php } else { ?>
                                <span class="estado pendiente">Pendiente</span>
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($pedido['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="min-width:230px">
                            <?php if ($pedido['estado'] === 'Pendiente' && empty($pedido['ci_operario'])) { ?>
                                <form method="POST" action="pedidos.php?estado=<?php echo urlencode($estado); ?>" class="d-flex gap-2">
                                    <input type="hidden" name="pedido_id" value="<?php echo (int)$pedido['id']; ?>">
                                    <select name="operario_ci" class="form-select form-select-sm" required>
                                        <option value="">Elegir operario</option>
                                        <?php foreach ($operarios as $op) { ?>
                                            <option value="<?php echo htmlspecialchars($op['CI'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($op['usuario'], ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php } ?>
                                    </select>
                                    <button class="btn btn-primary btn-sm" type="submit" name="asignar" value="1">Asignar</button>
                                </form>
                            <?php } elseif ($pedido['estado'] === 'Pendiente') { ?>
                                <span class="text-success"><i class="bi bi-person-check"></i> Asignado</span>
                            <?php } elseif ($pedido['estado'] === 'Completado') { ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Finalizado</span>
                            <?php } else { ?>
                                <span class="text-muted">En preparación</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
