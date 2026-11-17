<?php
/* =====================================================
   MODELO - REPOSICIÓN Y ÓRDENES DE COMPRA
   Calcula qué productos hay que reponer y cuánto pedir,
   y guarda las órdenes que se le hacen a cada proveedor.
   Estados de la orden: Pendiente -> Parcial -> Recibida,
   o Cancelada.
===================================================== */
class OrdenCompra extends Modelo
{
    const ESTADOS_ABIERTOS = ['Pendiente', 'Parcial'];
    // Días de venta que tiene que cubrir lo que se pide, además del plazo de entrega
    const DIAS_COBERTURA = 15;
    // Días de historia que se miran para calcular la venta diaria
    const DIAS_HISTORIA = 30;

    /* Productos que hay que reponer, con la cantidad sugerida.
       - venta diaria: lo que se retiró en picking en los últimos 30 días / 30
       - disponible: stock - lo reservado por pedidos sin recoger
       - punto de pedido: el mínimo + lo que se vende mientras llega el pedido
       - sugerido: lo que falta para llegar al mínimo + 15 días de venta (y como mínimo al doble del mínimo),
         descontando lo que ya está pedido y no llegó */
    public function reposicion()
    {
        $filas = $this->filas("SELECT p.id_producto, p.Nombre, p.codigo_barras, IF(p.foto_oculta, NULL, p.foto) AS foto,
                                      p.stock_actual, p.stock_minimo, p.id_proveedor, v.nombre AS proveedor, v.dias_entrega, v.activo AS proveedor_activo,
                                      c.nombre AS categoria,
                                      COALESCE(ret.unidades, 0) AS vendido,
                                      COALESCE(cam.unidades, 0) AS en_camino,
                                      COALESCE(res.unidades, 0) AS reservado
                               FROM Producto p
                               JOIN Categoria c ON c.id_categoria = p.id_categoria
                               LEFT JOIN Proveedor v ON v.id_proveedor = p.id_proveedor
                               LEFT JOIN (SELECT id_producto, SUM(cantidad) AS unidades FROM Registra
                                          WHERE Tipo_Registro = 'Retiro' AND fecha_hora >= NOW() - INTERVAL " . self::DIAS_HISTORIA . " DAY
                                          GROUP BY id_producto) ret ON ret.id_producto = p.id_producto
                               LEFT JOIN (SELECT d.id_producto, SUM(d.cantidad_pedida - d.cantidad_recibida) AS unidades
                                          FROM Orden_Compra_Detalle d JOIN Orden_Compra o ON o.id_orden = d.id_orden
                                          WHERE o.estado IN ('Pendiente', 'Parcial') AND d.cantidad_pedida > d.cantidad_recibida
                                          GROUP BY d.id_producto) cam ON cam.id_producto = p.id_producto
                               LEFT JOIN (SELECT t.id_producto, SUM(t.Cantidad) AS unidades FROM Tiene t
                                          JOIN Pedido pe ON pe.id_pedido = t.id_pedido
                                          WHERE t.recogido = FALSE AND pe.estado IN ('Pendiente', 'En Picking')
                                          GROUP BY t.id_producto) res ON res.id_producto = p.id_producto
                               ORDER BY v.nombre IS NULL, v.nombre, p.Nombre");
        $resultado = [];
        foreach ($filas as $f) {
            $minimo = (int)$f['stock_minimo'];
            $diaria = (int)$f['vendido'] / self::DIAS_HISTORIA;
            $plazo = $f['dias_entrega'] !== null ? (int)$f['dias_entrega'] : 2;
            $disponible = (int)$f['stock_actual'] - (int)$f['reservado'];
            $enCamino = (int)$f['en_camino'];
            $puntoPedido = $minimo + (int)ceil($diaria * $plazo);
            if ($puntoPedido <= 0 || $disponible + $enCamino > $puntoPedido) {
                continue;
            }
            $objetivo = max(2 * $minimo, $minimo + (int)ceil($diaria * ($plazo + self::DIAS_COBERTURA)));
            $f['venta_diaria'] = round($diaria, 1);
            $f['disponible'] = $disponible;
            $f['punto_pedido'] = $puntoPedido;
            $f['sugerido'] = max(1, $objetivo - $disponible - $enCamino);
            $f['urgente'] = $disponible <= $minimo && $enCamino === 0;
            $resultado[] = $f;
        }
        return $resultado;
    }

    /* Crea una orden por proveedor. $lineas: [id_producto => cantidad]. Devuelve el id. */
    public function crear($idProveedor, array $lineas, $ci, $fechaEsperada, $observaciones)
    {
        $this->iniciarTransaccion();
        try {
            $id = $this->insertar("INSERT INTO Orden_Compra (id_proveedor, fecha_creacion, fecha_esperada, estado, ci_creador, observaciones)
                                   VALUES (?, NOW(), ?, 'Pendiente', ?, ?)",
                'isss', [$idProveedor, $fechaEsperada ?: null, $ci, $observaciones !== '' ? $observaciones : null]);
            foreach ($lineas as $idProducto => $cantidad) {
                $this->ejecutar("INSERT INTO Orden_Compra_Detalle (id_orden, id_producto, cantidad_pedida) VALUES (?, ?, ?)",
                    'iii', [$id, (int)$idProducto, (int)$cantidad]);
            }
            $this->confirmarTransaccion();
            return $id;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Órdenes con su proveedor y el avance de lo recibido */
    public function listar($estado = '')
    {
        $filtro = $estado === 'Abiertas' ? "WHERE o.estado IN ('Pendiente', 'Parcial')" : ($estado !== '' ? 'WHERE o.estado = ?' : '');
        $usaParametro = $estado !== '' && $estado !== 'Abiertas';
        return $this->filas("SELECT o.id_orden, o.fecha_creacion, o.fecha_esperada, o.estado, v.nombre AS proveedor, pe.usuario AS creador,
                                    COUNT(d.id_producto) AS productos, COALESCE(SUM(d.cantidad_pedida), 0) AS pedidas,
                                    COALESCE(SUM(LEAST(d.cantidad_recibida, d.cantidad_pedida)), 0) AS recibidas
                             FROM Orden_Compra o
                             JOIN Proveedor v ON v.id_proveedor = o.id_proveedor
                             JOIN Persona pe ON pe.CI = o.ci_creador
                             LEFT JOIN Orden_Compra_Detalle d ON d.id_orden = o.id_orden
                             $filtro
                             GROUP BY o.id_orden, o.fecha_creacion, o.fecha_esperada, o.estado, v.nombre, pe.usuario
                             ORDER BY FIELD(o.estado, 'Pendiente', 'Parcial', 'Recibida', 'Cancelada'), o.id_orden DESC",
            $usaParametro ? 's' : '', $usaParametro ? [$estado] : []);
    }

    public function contarAbiertas()
    {
        try {
            return (int)$this->valor("SELECT COUNT(*) FROM Orden_Compra WHERE estado IN ('Pendiente', 'Parcial')");
        } catch (mysqli_sql_exception $ex) {
            return 0;
        }
    }

    /* La orden con los datos del proveedor */
    public function buscar($id)
    {
        return $this->fila("SELECT o.*, v.nombre AS proveedor, v.email, v.telefono, v.contacto, v.rut, pe.usuario AS creador
                            FROM Orden_Compra o
                            JOIN Proveedor v ON v.id_proveedor = o.id_proveedor
                            JOIN Persona pe ON pe.CI = o.ci_creador
                            WHERE o.id_orden = ?", 'i', [$id]);
    }

    public function detalle($id)
    {
        return $this->filas("SELECT d.id_producto, d.cantidad_pedida, d.cantidad_recibida, p.Nombre, p.codigo_barras,
                                    IF(p.foto_oculta, NULL, p.foto) AS foto
                             FROM Orden_Compra_Detalle d JOIN Producto p ON p.id_producto = d.id_producto
                             WHERE d.id_orden = ? ORDER BY p.Nombre", 'i', [$id]);
    }

    /* Órdenes abiertas con lo que falta recibir de cada producto (formulario de recepción) */
    public function abiertasConPendientes()
    {
        $ordenes = [];
        foreach ($this->filas("SELECT o.id_orden, o.id_proveedor, o.fecha_creacion, o.estado, v.nombre AS proveedor,
                                      d.id_producto, d.cantidad_pedida - d.cantidad_recibida AS falta
                               FROM Orden_Compra o
                               JOIN Proveedor v ON v.id_proveedor = o.id_proveedor
                               JOIN Orden_Compra_Detalle d ON d.id_orden = o.id_orden
                               WHERE o.estado IN ('Pendiente', 'Parcial')
                               ORDER BY o.id_orden DESC") as $f) {
            $id = (int)$f['id_orden'];
            $ordenes[$id] = $ordenes[$id] ?? [
                'id' => $id, 'id_proveedor' => (int)$f['id_proveedor'], 'proveedor' => $f['proveedor'],
                'fecha' => fecha($f['fecha_creacion'], 'd/m/Y'), 'estado' => $f['estado'], 'lineas' => [],
            ];
            if ((int)$f['falta'] > 0) {
                $ordenes[$id]['lineas'][] = ['id_producto' => (int)$f['id_producto'], 'cantidad' => (int)$f['falta']];
            }
        }
        return array_values($ordenes);
    }

    /* Solo se cancela una orden abierta. Devuelve true si se canceló. */
    public function cancelar($id)
    {
        return $this->ejecutar("UPDATE Orden_Compra SET estado = 'Cancelada' WHERE id_orden = ? AND estado IN ('Pendiente', 'Parcial')", 'i', [$id]) === 1;
    }

    /* Recepciones que se hicieron contra la orden */
    public function recepciones($id)
    {
        return $this->filas("SELECT r.id_recepcion, r.fecha, r.remito, pe.usuario AS persona,
                                    (SELECT COALESCE(SUM(l.cantidad_recibida), 0) FROM Lote l WHERE l.id_recepcion = r.id_recepcion) AS unidades
                             FROM Recepcion_Mercaderia r JOIN Persona pe ON pe.CI = r.ci_persona
                             WHERE r.id_orden = ? ORDER BY r.fecha", 'i', [$id]);
    }
}
