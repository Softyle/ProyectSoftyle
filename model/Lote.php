<?php
/* =====================================================
   MODELO - LOTES Y VENCIMIENTOS
   Cada recepción de mercadería crea un lote por producto,
   con su número de lote y su fecha de vencimiento.

   El stock del producto (Producto.stock_actual) sigue
   siendo el total. Los lotes dicen de qué fecha es cada
   parte; lo que no tiene lote es el stock que había antes
   de empezar a registrar lotes ("sin lote").

   Regla FEFO (First Expired, First Out): al retirar, sale
   primero lo que vence antes. Lo vencido no se retira
   para pedidos: hay que darlo de baja.
===================================================== */
class Lote extends Modelo
{
    // Días antes del vencimiento en que se empieza a avisar
    const DIAS_AVISO = 7;
    const DIAS_ATENCION = 30;

    /* Orden de salida: primero lo que vence antes, los lotes sin fecha después */
    const ORDEN_FEFO = 'l.fecha_vencimiento IS NULL, l.fecha_vencimiento, l.id_lote';

    public function crear($idProducto, $idRecepcion, $codigoLote, $vencimiento, $cantidad)
    {
        return $this->insertar("INSERT INTO Lote (id_producto, id_recepcion, codigo_lote, fecha_vencimiento, cantidad_recibida, cantidad_actual, fecha_ingreso)
                                VALUES (?, ?, ?, ?, ?, ?, NOW())",
            'iissii', [$idProducto, $idRecepcion, $codigoLote !== '' ? $codigoLote : null, $vencimiento !== '' ? $vencimiento : null, $cantidad, $cantidad]);
    }

    /* Lotes con mercadería de un producto, en el orden en que tienen que salir */
    public function porProducto($idProducto)
    {
        return $this->filas("SELECT l.*, (l.fecha_vencimiento < CURDATE()) AS vencido, DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias
                             FROM Lote l WHERE l.id_producto = ? AND l.cantidad_actual > 0
                             ORDER BY (l.fecha_vencimiento < CURDATE()) DESC, " . self::ORDEN_FEFO, 'i', [$idProducto]);
    }

    /* Para la hoja de ruta: el lote que hay que sacar primero de cada producto
       (el que vence antes y no está vencido) y cuántas unidades vencidas hay. */
    public function aRetirar(array $idsProducto)
    {
        $ids = array_values(array_unique(array_map('intval', $idsProducto)));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $resultado = [];
        foreach ($this->filas("SELECT l.id_producto, l.codigo_lote, l.fecha_vencimiento, l.cantidad_actual,
                                      (l.fecha_vencimiento < CURDATE()) AS vencido, DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias
                               FROM Lote l WHERE l.id_producto IN ($marcas) AND l.cantidad_actual > 0
                               ORDER BY l.id_producto, " . self::ORDEN_FEFO, str_repeat('i', count($ids)), $ids) as $l) {
            $id = (int)$l['id_producto'];
            $resultado[$id] = $resultado[$id] ?? ['primero' => null, 'vencidas' => 0];
            if ($l['vencido']) {
                $resultado[$id]['vencidas'] += (int)$l['cantidad_actual'];
            } elseif (!$resultado[$id]['primero']) {
                $resultado[$id]['primero'] = $l;
            }
        }
        return $resultado;
    }

    /* Suma de lo que tienen los lotes de un producto */
    private function totalEnLotes($idProducto)
    {
        return (int)$this->valor("SELECT COALESCE(SUM(cantidad_actual), 0) FROM Lote WHERE id_producto = ?", 'i', [$idProducto]);
    }

    /* Descuenta $cantidad de los lotes al retirar mercadería (usar dentro de una transacción).
       $stockAntes es el stock del producto antes del retiro. Orden: los lotes que no vencieron
       (FEFO), después el stock sin lote y, si no alcanza, los vencidos. */
    public function consumir($idProducto, $cantidad, $stockAntes)
    {
        $lotes = $this->filas("SELECT l.id_lote, l.cantidad_actual, (l.fecha_vencimiento < CURDATE()) AS vencido
                               FROM Lote l WHERE l.id_producto = ? AND l.cantidad_actual > 0
                               ORDER BY " . self::ORDEN_FEFO . " FOR UPDATE", 'i', [$idProducto]);
        $sinLote = max(0, (int)$stockAntes - array_sum(array_column($lotes, 'cantidad_actual')));
        $restante = (int)$cantidad;
        $restante = $this->descontarDe(array_filter($lotes, fn($l) => !$l['vencido']), $restante);
        $restante -= min($sinLote, $restante);
        $this->descontarDe(array_filter($lotes, fn($l) => $l['vencido']), $restante);
    }

    /* Mercadería devuelta (por ejemplo, el administrador devuelve un pedido al picking):
       vuelve a los lotes de los que salió primero; lo que sobra queda sin lote. */
    public function devolver($idProducto, $cantidad)
    {
        $restante = (int)$cantidad;
        foreach ($this->filas("SELECT l.id_lote, l.cantidad_recibida - l.cantidad_actual AS espacio
                               FROM Lote l WHERE l.id_producto = ? AND l.cantidad_actual < l.cantidad_recibida
                                 AND (l.fecha_vencimiento IS NULL OR l.fecha_vencimiento >= CURDATE())
                               ORDER BY " . self::ORDEN_FEFO . " FOR UPDATE", 'i', [$idProducto]) as $l) {
            if ($restante <= 0) {
                break;
            }
            $suma = min((int)$l['espacio'], $restante);
            $this->ejecutar("UPDATE Lote SET cantidad_actual = cantidad_actual + ? WHERE id_lote = ?", 'ii', [$suma, $l['id_lote']]);
            $restante -= $suma;
        }
    }

    /* Después de cambiar el stock a mano o por un conteo: si los lotes suman más que el stock,
       se les descuenta la diferencia (primero a los vencidos, después en orden FEFO). */
    public function ajustarAStock($idProducto)
    {
        $stock = (int)$this->valor("SELECT stock_actual FROM Producto WHERE id_producto = ?", 'i', [$idProducto]);
        $sobrante = $this->totalEnLotes($idProducto) - $stock;
        if ($sobrante <= 0) {
            return;
        }
        $lotes = $this->filas("SELECT l.id_lote, l.cantidad_actual FROM Lote l WHERE l.id_producto = ? AND l.cantidad_actual > 0
                               ORDER BY (l.fecha_vencimiento < CURDATE()) DESC, " . self::ORDEN_FEFO . " FOR UPDATE", 'i', [$idProducto]);
        $this->descontarDe($lotes, $sobrante);
    }

    /* Descuenta de los lotes en el orden recibido; devuelve lo que no se pudo descontar */
    private function descontarDe(array $lotes, $restante)
    {
        foreach ($lotes as $l) {
            if ($restante <= 0) {
                break;
            }
            $resta = min((int)$l['cantidad_actual'], $restante);
            $this->ejecutar("UPDATE Lote SET cantidad_actual = cantidad_actual - ? WHERE id_lote = ?", 'ii', [$resta, $l['id_lote']]);
            $restante -= $resta;
        }
        return $restante;
    }

    /* Lotes con mercadería que vencen dentro de $dias días o ya vencieron */
    public function vencimientos($dias)
    {
        return $this->filas("SELECT l.id_lote, l.codigo_lote, l.fecha_vencimiento, l.cantidad_actual, l.fecha_ingreso,
                                    DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias,
                                    p.id_producto, p.Nombre, p.codigo_barras, IF(p.foto_oculta, NULL, p.foto) AS foto, p.stock_actual,
                                    c.nombre AS categoria, z.nombre AS zona, u.pasillo, u.estante, u.posicion
                             FROM Lote l
                             JOIN Producto p ON p.id_producto = l.id_producto
                             JOIN Categoria c ON c.id_categoria = p.id_categoria
                             JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE l.cantidad_actual > 0 AND l.fecha_vencimiento IS NOT NULL
                               AND l.fecha_vencimiento <= CURDATE() + INTERVAL ? DAY
                             ORDER BY l.fecha_vencimiento, p.Nombre", 'i', [$dias]);
    }

    /* Para el panel de control: lotes vencidos y lotes que vencen en los próximos días.
       Si la base todavía no tiene la tabla, devuelve ceros. */
    public function resumen()
    {
        try {
            $f = $this->fila("SELECT COALESCE(SUM(fecha_vencimiento < CURDATE()), 0) AS vencidos,
                                     COALESCE(SUM(fecha_vencimiento >= CURDATE() AND fecha_vencimiento <= CURDATE() + INTERVAL " . self::DIAS_AVISO . " DAY), 0) AS por_vencer
                              FROM Lote WHERE cantidad_actual > 0 AND fecha_vencimiento IS NOT NULL");
            return ['vencidos' => (int)$f['vencidos'], 'por_vencer' => (int)$f['por_vencer']];
        } catch (mysqli_sql_exception $ex) {
            return ['vencidos' => 0, 'por_vencer' => 0];
        }
    }

    /* Saca del stock lo que queda de un lote (por ejemplo, porque venció) y lo deja anotado.
       Devuelve las unidades dadas de baja. Lanza RuntimeException si no hay nada para dar de baja. */
    public function darDeBaja($idLote, $ci, $motivo)
    {
        $this->iniciarTransaccion();
        try {
            $lote = $this->fila("SELECT l.*, p.Nombre FROM Lote l JOIN Producto p ON p.id_producto = l.id_producto
                                 WHERE l.id_lote = ? FOR UPDATE", 'i', [$idLote]);
            if (!$lote || (int)$lote['cantidad_actual'] <= 0) {
                throw new RuntimeException('Ese lote ya no tiene mercadería.');
            }
            $stock = (int)$this->valor("SELECT stock_actual FROM Producto WHERE id_producto = ? FOR UPDATE", 'i', [$lote['id_producto']]);
            $baja = min((int)$lote['cantidad_actual'], $stock);
            $this->ejecutar("UPDATE Lote SET cantidad_actual = 0 WHERE id_lote = ?", 'i', [$idLote]);
            if ($baja > 0) {
                $this->ejecutar("UPDATE Producto SET stock_actual = stock_actual - ? WHERE id_producto = ?", 'ii', [$baja, $lote['id_producto']]);
                (new HistorialStock())->registrar($lote['id_producto'], 'Baja por vencimiento', -$baja, $stock - $baja, $ci,
                    $motivo . ($lote['codigo_lote'] ? ' · lote ' . $lote['codigo_lote'] : '')
                    . ($lote['fecha_vencimiento'] ? ' · vencía el ' . fecha($lote['fecha_vencimiento'], 'd/m/Y') : ''));
            }
            $this->confirmarTransaccion();
            return ['producto' => $lote['Nombre'], 'unidades' => $baja];
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }
}
