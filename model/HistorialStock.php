<?php
/* =====================================================
   MODELO - HISTORIAL DE STOCK (tabla Movimiento_Stock)
   Todo lo que cambia el stock fuera del picking:
   recepciones, ajustes de conteo, bajas por vencimiento
   y cambios hechos a mano en el producto.
   (Los retiros y devoluciones del picking están en Registra.)
===================================================== */
class HistorialStock extends Modelo
{
    /* $cantidad: positiva si entra mercadería, negativa si sale */
    public function registrar($idProducto, $tipo, $cantidad, $stockResultante, $ci, $detalle = '')
    {
        return $this->insertar("INSERT INTO Movimiento_Stock (id_producto, fecha, tipo, cantidad, stock_resultante, ci_persona, detalle)
                                VALUES (?, NOW(), ?, ?, ?, ?, ?)",
            'isiiss', [$idProducto, $tipo, $cantidad, $stockResultante, $ci, $detalle !== '' ? mb_substr($detalle, 0, 255) : null]);
    }

    /* Historial completo de un producto: estos movimientos más los retiros y devoluciones del picking,
       del más nuevo al más viejo */
    public function porProducto($idProducto, $limite = 200)
    {
        return $this->filas("SELECT * FROM (
                                 SELECT m.fecha, m.tipo, m.cantidad, m.stock_resultante, m.detalle, pe.usuario AS persona
                                 FROM Movimiento_Stock m JOIN Persona pe ON pe.CI = m.ci_persona
                                 WHERE m.id_producto = ?
                                 UNION ALL
                                 SELECT r.fecha_hora, r.Tipo_Registro,
                                        IF(r.Tipo_Registro = 'Devolución', r.cantidad, -r.cantidad), NULL,
                                        CONCAT('Pedido #', r.id_pedido, ' · ', r.Ubicacion_producto), pe.usuario
                                 FROM Registra r JOIN Persona pe ON pe.CI = r.ci_operario
                                 WHERE r.id_producto = ? AND r.Tipo_Registro IN ('Retiro', 'Devolución')
                             ) h ORDER BY h.fecha DESC LIMIT ?", 'iii', [$idProducto, $idProducto, $limite]);
    }
}
