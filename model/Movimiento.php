<?php
/* =====================================================
   MODELO - MOVIMIENTOS DE STOCK (tabla Registra)
   Retiros, devoluciones y errores de picking. Sirven
   para la trazabilidad y para medir la precisión.
===================================================== */
class Movimiento extends Modelo
{
    /* Registra un movimiento con la fecha y hora actual */
    public function registrar($ci, $idProducto, $ubicacion, $tipo, $idPedido, $cantidad)
    {
        return $this->ejecutar("INSERT INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad)
                                VALUES (?, ?, NOW(), ?, ?, ?, ?)", 'sissii', [$ci, $idProducto, $ubicacion, $tipo, $idPedido, $cantidad]);
    }

    /* Intento fallido de picking. INSERT IGNORE: si el mismo error se repite en el
       mismo segundo (la clave incluye la fecha) no rompe la operación. */
    public function registrarError($ci, $idProducto, $ubicacion, $tipo, $idPedido, $cantidad)
    {
        return $this->ejecutar("INSERT IGNORE INTO Registra (ci_operario, id_producto, fecha_hora, Ubicacion_producto, Tipo_Registro, id_pedido, cantidad)
                                VALUES (?, ?, NOW(), ?, ?, ?, ?)", 'sissii', [$ci, $idProducto, $ubicacion, $tipo, $idPedido, $cantidad]);
    }

    /* Historial de movimientos de un pedido */
    public function porPedido($idPedido)
    {
        return $this->filas("SELECT r.fecha_hora, r.Tipo_Registro, r.cantidad, r.Ubicacion_producto, pr.Nombre, po.usuario AS operario
                             FROM Registra r INNER JOIN Producto pr ON pr.id_producto = r.id_producto
                             INNER JOIN Persona po ON po.CI = r.ci_operario
                             WHERE r.id_pedido = ? ORDER BY r.fecha_hora DESC", 'i', [$idPedido]);
    }

    /* Últimos movimientos de un operario */
    public function porOperario($ci, $limite = 100)
    {
        return $this->filas("SELECT r.fecha_hora, r.id_pedido, pr.Nombre AS producto, r.Ubicacion_producto, r.Tipo_Registro, r.cantidad
                             FROM Registra r
                             INNER JOIN Producto pr ON pr.id_producto = r.id_producto
                             WHERE r.ci_operario = ?
                             ORDER BY r.fecha_hora DESC
                             LIMIT ?", 'si', [$ci, $limite]);
    }
}
