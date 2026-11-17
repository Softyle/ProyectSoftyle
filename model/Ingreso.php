<?php
/* =====================================================
   MODELO - RECEPCIÓN DE MERCADERÍA (Recepcion_Mercaderia)
   Lo que entra al depósito: sube el stock de cada producto,
   crea un lote con su vencimiento, lo anota en el historial
   y, si vino por una orden de compra, la va completando.
   (No confundir con model/Recepcion.php, que guarda los
   pedidos que llegan por la API simulada.)
===================================================== */
class Ingreso extends Modelo
{
    /* Registra la recepción completa en una sola transacción: o se guarda todo o nada.
       $lineas: [['id_producto', 'cantidad', 'lote', 'vencimiento'], ...]
       Devuelve el número de recepción. Lanza RuntimeException si la orden ya no admite recepciones. */
    public function registrar($ci, $idProveedor, $idOrden, $remito, $observaciones, array $lineas)
    {
        $this->iniciarTransaccion();
        try {
            if ($idOrden) {
                $orden = $this->fila("SELECT id_proveedor, estado FROM Orden_Compra WHERE id_orden = ? FOR UPDATE", 'i', [$idOrden]);
                if (!$orden || !in_array($orden['estado'], OrdenCompra::ESTADOS_ABIERTOS, true)) {
                    throw new RuntimeException('La orden de compra #' . $idOrden . ' ya no está abierta. Actualizá la página.');
                }
                $idProveedor = (int)$orden['id_proveedor'];
            }
            $id = $this->insertar("INSERT INTO Recepcion_Mercaderia (fecha, id_proveedor, id_orden, remito, ci_persona, observaciones)
                                   VALUES (NOW(), ?, ?, ?, ?, ?)",
                'iisss', [$idProveedor ?: null, $idOrden ?: null, $remito !== '' ? $remito : null, $ci, $observaciones !== '' ? $observaciones : null]);

            $lotes = new Lote();
            $historial = new HistorialStock();
            foreach ($lineas as $l) {
                $idProducto = (int)$l['id_producto'];
                $cantidad = (int)$l['cantidad'];
                $stock = $this->valor("SELECT stock_actual FROM Producto WHERE id_producto = ? FOR UPDATE", 'i', [$idProducto]);
                if ($stock === null) {
                    throw new RuntimeException('Uno de los productos ya no existe. Actualizá la página.');
                }
                $nuevo = (int)$stock + $cantidad;
                $this->ejecutar("UPDATE Producto SET stock_actual = ? WHERE id_producto = ?", 'ii', [$nuevo, $idProducto]);
                $lotes->crear($idProducto, $id, $l['lote'], $l['vencimiento'], $cantidad);
                $historial->registrar($idProducto, 'Recepción', $cantidad, $nuevo, $ci,
                    'Recepción #' . $id . ($l['lote'] !== '' ? ' · lote ' . $l['lote'] : '')
                    . ($l['vencimiento'] !== '' ? ' · vence el ' . fecha($l['vencimiento'], 'd/m/Y') : '')
                    . ($remito !== '' ? ' · remito ' . $remito : ''));
                if ($idOrden) {
                    $this->ejecutar("UPDATE Orden_Compra_Detalle SET cantidad_recibida = cantidad_recibida + ? WHERE id_orden = ? AND id_producto = ?",
                        'iii', [$cantidad, $idOrden, $idProducto]);
                }
            }

            // La orden queda Recibida si llegó todo lo pedido; si no, Parcial
            if ($idOrden) {
                $falta = (int)$this->valor("SELECT COUNT(*) FROM Orden_Compra_Detalle WHERE id_orden = ? AND cantidad_recibida < cantidad_pedida", 'i', [$idOrden]);
                $this->ejecutar("UPDATE Orden_Compra SET estado = ? WHERE id_orden = ?", 'si', [$falta === 0 ? 'Recibida' : 'Parcial', $idOrden]);
            }
            $this->confirmarTransaccion();
            return $id;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Últimas recepciones. Con $ci, solo las de esa persona (el operario ve las suyas). */
    public function recientes($limite = 30, $ci = '')
    {
        return $this->filas("SELECT r.id_recepcion, r.fecha, r.remito, r.id_orden, v.nombre AS proveedor, pe.usuario AS persona,
                                    COUNT(l.id_lote) AS productos, COALESCE(SUM(l.cantidad_recibida), 0) AS unidades
                             FROM Recepcion_Mercaderia r
                             JOIN Persona pe ON pe.CI = r.ci_persona
                             LEFT JOIN Proveedor v ON v.id_proveedor = r.id_proveedor
                             LEFT JOIN Lote l ON l.id_recepcion = r.id_recepcion
                             WHERE (? = '' OR r.ci_persona = ?)
                             GROUP BY r.id_recepcion, r.fecha, r.remito, r.id_orden, v.nombre, pe.usuario
                             ORDER BY r.id_recepcion DESC LIMIT ?", 'ssi', [$ci, $ci, $limite]);
    }

    public function buscar($id)
    {
        return $this->fila("SELECT r.*, v.nombre AS proveedor, pe.usuario AS persona
                            FROM Recepcion_Mercaderia r
                            JOIN Persona pe ON pe.CI = r.ci_persona
                            LEFT JOIN Proveedor v ON v.id_proveedor = r.id_proveedor
                            WHERE r.id_recepcion = ?", 'i', [$id]);
    }

    /* Productos de la recepción (un lote por línea) */
    public function lineas($id)
    {
        return $this->filas("SELECT l.*, p.Nombre, p.codigo_barras, IF(p.foto_oculta, NULL, p.foto) AS foto,
                                    z.nombre AS zona, u.pasillo, u.estante, u.posicion
                             FROM Lote l
                             JOIN Producto p ON p.id_producto = l.id_producto
                             JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE l.id_recepcion = ? ORDER BY l.id_lote", 'i', [$id]);
    }

    /* Catálogo para el formulario (se busca por nombre o por código de barras sin ir al servidor) */
    public function catalogo()
    {
        return $this->filas("SELECT p.id_producto AS id, p.Nombre AS nombre, p.codigo_barras AS codigo, IF(p.foto_oculta, NULL, p.foto) AS foto,
                                    p.id_proveedor AS proveedor, c.nombre AS categoria, z.tipo AS tipo_zona,
                                    CONCAT(z.nombre, ' · ', u.pasillo, '-', u.estante, '-', u.posicion) AS ubicacion
                             FROM Producto p
                             JOIN Categoria c ON c.id_categoria = p.id_categoria
                             JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             JOIN Zona z ON z.id_zona = u.id_zona
                             ORDER BY p.Nombre");
    }
}
