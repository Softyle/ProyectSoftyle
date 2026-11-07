<?php
/* =====================================================
   MODELO - PICKING DEL OPERARIO
   Pedidos asignados, hoja de ruta ordenada (la zona
   refrigerada siempre al final, RNE-8) y confirmación de
   cada retiro con descuento de stock (RNE-9 / RNE-11).
   Todas las consultas filtran por el CI del operario.
===================================================== */
class Picking extends Modelo
{
    public function contarPorEstado($ci, $estado)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Pedido WHERE ci_operario = ? AND estado = ?", 'ss', [$ci, $estado]);
    }

    /* Pedidos asignados al operario que todavía no se completaron */
    public function pedidosAsignados($ci)
    {
        return $this->filas("SELECT p.id_pedido AS id, p.estado, p.fecha_creacion AS fecha,
                                    pc.usuario AS cliente,
                                    COUNT(DISTINCT t.id_producto) AS productos_diferentes,
                                    COALESCE(SUM(t.Cantidad), 0) AS total_productos
                             FROM Pedido p
                             INNER JOIN Cliente c ON c.CI = p.ci_cliente
                             INNER JOIN Persona pc ON pc.CI = c.CI
                             LEFT JOIN Tiene t ON t.id_pedido = p.id_pedido
                             WHERE p.ci_operario = ? AND p.estado IN ('Pendiente', 'En Picking', 'Por validar')
                             GROUP BY p.id_pedido, p.estado, p.fecha_creacion, pc.usuario
                             ORDER BY FIELD(p.estado, 'En Picking', 'Pendiente', 'Por validar'), p.fecha_creacion ASC", 's', [$ci]);
    }

    /* Detalle de un pedido del operario (null si no es suyo) */
    public function detalle($idPedido, $ci)
    {
        return $this->fila("SELECT p.id_pedido AS id, p.estado, p.fecha_creacion, p.fecha_entrega,
                                   pc.usuario AS cliente, pc.direccion,
                                   COALESCE(SUM(t.Cantidad), 0) AS total_productos,
                                   COUNT(DISTINCT t.id_producto) AS productos_diferentes
                            FROM Pedido p
                            INNER JOIN Cliente c ON c.CI = p.ci_cliente
                            INNER JOIN Persona pc ON pc.CI = c.CI
                            LEFT JOIN Tiene t ON t.id_pedido = p.id_pedido
                            WHERE p.id_pedido = ? AND p.ci_operario = ?
                            GROUP BY p.id_pedido, p.estado, p.fecha_creacion, p.fecha_entrega, pc.usuario, pc.direccion", 'is', [$idPedido, $ci]);
    }

    /* Al abrir la hoja de ruta, un pedido Pendiente pasa a En Picking y se guarda la hora de inicio */
    public function iniciar($idPedido, $ci)
    {
        return $this->ejecutar("UPDATE Pedido SET estado = 'En Picking', fecha_inicio_picking = COALESCE(fecha_inicio_picking, NOW())
                                WHERE id_pedido = ? AND ci_operario = ? AND estado = 'Pendiente'", 'is', [$idPedido, $ci]);
    }

    /* Hoja de ruta: productos en el orden del recorrido, la zona refrigerada al final (RNE-8) */
    public function hojaDeRuta($idPedido, $ci)
    {
        return $this->filas("SELECT p.id_pedido AS id, p.estado, pc.usuario AS cliente,
                                    pr.id_producto, pr.Nombre AS producto, pr.stock_actual, pr.stock_minimo, t.Cantidad, t.recogido,
                                    u.pasillo, u.estante, u.posicion, z.nombre AS zona, z.tipo AS tipo_zona
                             FROM Pedido p
                             INNER JOIN Cliente c ON c.CI = p.ci_cliente
                             INNER JOIN Persona pc ON pc.CI = c.CI
                             INNER JOIN Tiene t ON t.id_pedido = p.id_pedido
                             INNER JOIN Producto pr ON pr.id_producto = t.id_producto
                             INNER JOIN Ubicacion u ON u.id_ubicacion = pr.id_ubicacion
                             INNER JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE p.id_pedido = ? AND p.ci_operario = ?
                             ORDER BY (z.tipo = 'Refrigerada'), z.nombre, u.pasillo, u.estante, u.posicion, pr.Nombre", 'is', [$idPedido, $ci]);
    }

    /* Estado del pedido si es del operario (null si no existe o no es suyo) */
    public function estadoPedido($idPedido, $ci)
    {
        return $this->valor("SELECT estado FROM Pedido WHERE id_pedido = ? AND ci_operario = ?", 'is', [$idPedido, $ci]);
    }

    /* Progreso del pedido: ítems recogidos / total */
    public function progreso($idPedido)
    {
        $p = $this->fila("SELECT COUNT(*) AS total, COALESCE(SUM(recogido), 0) AS recogidos FROM Tiene WHERE id_pedido = ?", 'i', [$idPedido]);
        return ['total' => (int)$p['total'], 'recogidos' => (int)$p['recogidos']];
    }

    /* Estado de cada ítem y su stock (actualización silenciosa de la hoja de ruta) */
    public function estadoItems($idPedido)
    {
        return array_map(fn($f) => [
            'id_producto' => (int)$f['id_producto'],
            'recogido' => (bool)$f['recogido'],
            'stock_actual' => (int)$f['stock_actual'],
            'stock_bajo' => (int)$f['stock_actual'] <= (int)$f['stock_minimo'],
        ], $this->filas("SELECT t.id_producto, t.recogido, pr.stock_actual, pr.stock_minimo
                         FROM Tiene t INNER JOIN Producto pr ON pr.id_producto = t.id_producto
                         WHERE t.id_pedido = ?", 'i', [$idPedido]));
    }

    /* Ítem a retirar con su ubicación real. FOR UPDATE: bloquea el ítem y el producto
       para que dos confirmaciones simultáneas no descuenten dos veces (usar en transacción). */
    public function itemParaRetiro($idPedido, $idProducto)
    {
        return $this->fila("SELECT t.Cantidad, t.recogido, pr.Nombre, pr.stock_actual, pr.stock_minimo,
                                   u.pasillo, u.estante, u.posicion, z.nombre AS zona
                            FROM Tiene t
                            INNER JOIN Producto pr ON pr.id_producto = t.id_producto
                            INNER JOIN Ubicacion u ON u.id_ubicacion = pr.id_ubicacion
                            INNER JOIN Zona z ON z.id_zona = u.id_zona
                            WHERE t.id_pedido = ? AND t.id_producto = ?
                            FOR UPDATE", 'ii', [$idPedido, $idProducto]);
    }

    /* Descuenta el stock solo si alcanza; devuelve false si cambió mientras tanto */
    public function descontarStock($idProducto, $cantidad)
    {
        return $this->ejecutar("UPDATE Producto SET stock_actual = stock_actual - ? WHERE id_producto = ? AND stock_actual >= ?",
            'iii', [$cantidad, $idProducto, $cantidad]) === 1;
    }

    /* Marca el ítem como recogido y, si era el primer retiro, el pedido queda "En Picking" */
    public function marcarRecogido($idPedido, $idProducto)
    {
        $this->ejecutar("UPDATE Tiene SET recogido = TRUE WHERE id_pedido = ? AND id_producto = ?", 'ii', [$idPedido, $idProducto]);
        $this->ejecutar("UPDATE Pedido SET estado = 'En Picking', fecha_inicio_picking = COALESCE(fecha_inicio_picking, NOW())
                         WHERE id_pedido = ? AND estado = 'Pendiente'", 'i', [$idPedido]);
    }

    /* Todos los ítems recogidos: el pedido pasa a "Por validar" */
    public function finalizar($idPedido, $ci)
    {
        return $this->ejecutar("UPDATE Pedido SET estado = 'Por validar' WHERE id_pedido = ? AND ci_operario = ? AND estado = 'En Picking'",
            'is', [$idPedido, $ci]) === 1;
    }
}
