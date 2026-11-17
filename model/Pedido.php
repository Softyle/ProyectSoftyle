<?php
/* =====================================================
   MODELO - PEDIDO (tablas Pedido + Tiene)
   Estados: Pendiente -> En Picking -> Por validar -> Completado
   (el administrador puede devolver ítems al picking).
===================================================== */
class Pedido extends Modelo
{
    const ESTADOS = ['Pendiente', 'En Picking', 'Por validar', 'Completado'];

    public function contarPorEstado($estado)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Pedido WHERE estado = ?", 's', [$estado]);
    }

    /* Lista de pedidos (todos o de un estado). Persona se usa para cliente y para operario. */
    public function listar($estado = 'Todos')
    {
        $sql = "SELECT pe.id_pedido AS id, pe.estado, pe.fecha_creacion AS fecha, pe.ci_operario,
                       cliente.usuario AS cliente, operario.usuario AS operario,
                       COALESCE(SUM(t.Cantidad), 0) AS total_productos
                FROM Pedido pe
                INNER JOIN Cliente c ON c.CI = pe.ci_cliente
                INNER JOIN Persona cliente ON cliente.CI = c.CI
                LEFT JOIN Persona operario ON operario.CI = pe.ci_operario
                LEFT JOIN Tiene t ON t.id_pedido = pe.id_pedido";
        $tipos = '';
        $parametros = [];
        if ($estado !== 'Todos') {
            $sql .= " WHERE pe.estado = ?";
            $tipos = 's';
            $parametros[] = $estado;
        }
        $sql .= " GROUP BY pe.id_pedido, pe.estado, pe.fecha_creacion, pe.ci_operario, cliente.usuario, operario.usuario
                  ORDER BY pe.id_pedido DESC";
        return $this->filas($sql, $tipos, $parametros);
    }

    /* Asigna un pedido pendiente que todavía no tiene operario */
    public function asignar($idPedido, $ciOperario)
    {
        return $this->ejecutar("UPDATE Pedido SET ci_operario = ? WHERE id_pedido = ? AND estado = 'Pendiente' AND ci_operario IS NULL",
            'si', [$ciOperario, $idPedido]) === 1;
    }

    /* Crea un pedido Pendiente con sus productos ($lineas = [id_producto => cantidad]).
       Bloquea los productos y verifica el stock disponible dentro de la transacción,
       para que dos pedidos simultáneos no reserven el mismo stock.
       Devuelve ['ok' => true, 'id' => n] o ['ok' => false, 'faltantes' => [...]]. */
    public function crear($ciCliente, $fechaEntrega, array $lineas)
    {
        $this->iniciarTransaccion();
        try {
            $stock = (new Producto())->stockDisponible(array_keys($lineas), true);
            $faltantes = [];
            foreach ($lineas as $idProducto => $cantidad) {
                if (!isset($stock[$idProducto])) {
                    $faltantes[] = 'un producto que ya no existe';
                } elseif ($cantidad > $stock[$idProducto]['disponible']) {
                    $faltantes[] = $stock[$idProducto]['nombre'] . ' (pediste ' . $cantidad . ', hay ' . $stock[$idProducto]['disponible'] . ' disponibles)';
                }
            }
            if ($faltantes) {
                $this->deshacerTransaccion();
                return ['ok' => false, 'faltantes' => $faltantes];
            }
            $idPedido = $this->insertarConProductos($ciCliente, $fechaEntrega, $lineas);
            $this->confirmarTransaccion();
            return ['ok' => true, 'id' => $idPedido];
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* INSERT del pedido y de sus líneas (la transacción la maneja quien llama) */
    public function insertarConProductos($ciCliente, $fechaEntrega, array $lineas)
    {
        $idPedido = $this->insertar("INSERT INTO Pedido (fecha_creacion, fecha_entrega, estado, recogido, ci_cliente, ci_operario)
                                     VALUES (NOW(), ?, 'Pendiente', FALSE, ?, NULL)", 'ss', [$fechaEntrega, $ciCliente]);
        foreach ($lineas as $idProducto => $cantidad) {
            $this->ejecutar("INSERT INTO Tiene (id_pedido, id_producto, Cantidad) VALUES (?, ?, ?)", 'iii', [$idPedido, $idProducto, $cantidad]);
        }
        return $idPedido;
    }

    /* ---------- Validación de tareas ---------- */

    /* Datos del pedido y de cada ítem: solicitado vs. retirado según Registra.
       Devuelve [pedido, items] o [null, []] si el pedido no existe. */
    public function detalle($idPedido)
    {
        $pedido = $this->fila("SELECT pe.*, pc.usuario AS cliente, po.usuario AS operario
                               FROM Pedido pe
                               INNER JOIN Persona pc ON pc.CI = pe.ci_cliente
                               LEFT JOIN Persona po ON po.CI = pe.ci_operario
                               WHERE pe.id_pedido = ?", 'i', [$idPedido]);
        if (!$pedido) {
            return [null, []];
        }
        $items = $this->filas("SELECT t.id_producto, t.Cantidad AS solicitada, t.recogido, pr.Nombre, pr.stock_actual,
                                      z.nombre AS zona, u.pasillo, u.estante, u.posicion,
                                      COALESCE(SUM(CASE r.Tipo_Registro WHEN 'Retiro' THEN r.cantidad WHEN 'Devolución' THEN -r.cantidad ELSE 0 END), 0) AS retirada,
                                      COALESCE(SUM(r.Tipo_Registro LIKE 'Error%'), 0) AS errores
                               FROM Tiene t
                               INNER JOIN Producto pr ON pr.id_producto = t.id_producto
                               INNER JOIN Ubicacion u ON u.id_ubicacion = pr.id_ubicacion
                               INNER JOIN Zona z ON z.id_zona = u.id_zona
                               LEFT JOIN Registra r ON r.id_pedido = t.id_pedido AND r.id_producto = t.id_producto
                               WHERE t.id_pedido = ?
                               GROUP BY t.id_producto, t.Cantidad, t.recogido, pr.Nombre, pr.stock_actual, z.nombre, u.pasillo, u.estante, u.posicion
                               ORDER BY (z.tipo = 'Refrigerada'), z.nombre, u.pasillo, u.estante, u.posicion", 'i', [$idPedido]);
        foreach ($items as &$it) {
            // Un ítem coincide si fue recogido y lo retirado es igual a lo solicitado
            $it['coincide'] = $it['recogido'] && (int)$it['retirada'] === (int)$it['solicitada'];
            $it['ubicacion'] = Ubicacion::texto($it['zona'], $it['pasillo'], $it['estante'], $it['posicion']);
        }
        unset($it);
        return [$pedido, $items];
    }

    /* true si TODOS los ítems coinciden con lo solicitado */
    public static function todoCoincide(array $items)
    {
        return $items && !array_filter($items, fn($it) => !$it['coincide']);
    }

    /* Por validar -> Completado */
    public function completar($idPedido)
    {
        return $this->ejecutar("UPDATE Pedido SET estado = 'Completado', recogido = TRUE, fecha_entrega = COALESCE(fecha_entrega, NOW())
                                WHERE id_pedido = ? AND estado = 'Por validar'", 'i', [$idPedido]) === 1;
    }

    /* Los productos elegidos vuelven a la hoja de ruta: se repone el stock retirado,
       se registra la devolución y el pedido vuelve a "En Picking".
       Lanza RuntimeException si el pedido cambió de estado mientras tanto. */
    public function devolverAlPicking($idPedido, array $seleccion, array $items)
    {
        $this->iniciarTransaccion();
        try {
            // Se bloquea el pedido para que nadie lo modifique mientras tanto
            $actual = $this->fila("SELECT estado, ci_operario FROM Pedido WHERE id_pedido = ? FOR UPDATE", 'i', [$idPedido]);
            if (!$actual || $actual['estado'] !== 'Por validar') {
                throw new RuntimeException('El pedido cambió de estado. Actualizá la página.');
            }
            $movimientos = new Movimiento();
            foreach ($items as $it) {
                $idProducto = (int)$it['id_producto'];
                if (!in_array($idProducto, $seleccion, true)) {
                    continue;
                }
                $retirada = (int)$it['retirada'];
                if ($retirada > 0) {
                    $this->ejecutar("UPDATE Producto SET stock_actual = stock_actual + ? WHERE id_producto = ?", 'ii', [$retirada, $idProducto]);
                    (new Lote())->devolver($idProducto, $retirada);
                    $movimientos->registrar($actual['ci_operario'], $idProducto, $it['pasillo'] . '-' . $it['estante'] . '-' . $it['posicion'],
                        'Devolución', $idPedido, $retirada);
                }
                $this->ejecutar("UPDATE Tiene SET recogido = FALSE WHERE id_pedido = ? AND id_producto = ?", 'ii', [$idPedido, $idProducto]);
            }
            $this->ejecutar("UPDATE Pedido SET estado = 'En Picking', recogido = FALSE WHERE id_pedido = ?", 'i', [$idPedido]);
            $this->confirmarTransaccion();
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Le saca el pedido al operario (Pendiente o En Picking): vuelve a "Pendiente"
       sin asignar. Lo que ya había retirado vuelve al stock y queda registrado como
       devolución. Devuelve las unidades repuestas.
       Lanza RuntimeException si el pedido no tiene operario o ya pasó a validación. */
    public function quitarOperario($idPedido)
    {
        $this->iniciarTransaccion();
        try {
            $actual = $this->fila("SELECT estado, ci_operario FROM Pedido WHERE id_pedido = ? FOR UPDATE", 'i', [$idPedido]);
            if (!$actual || $actual['ci_operario'] === null || !in_array($actual['estado'], ['Pendiente', 'En Picking'], true)) {
                throw new RuntimeException('Ese pedido ya no está asignado a un operario o ya pasó a validación. Actualizá la página.');
            }
            [, $items] = $this->detalle($idPedido);
            $movimientos = new Movimiento();
            $repuestas = 0;
            foreach ($items as $it) {
                $retirada = (int)$it['retirada'];
                if ($retirada > 0) {
                    $idProducto = (int)$it['id_producto'];
                    $this->ejecutar("UPDATE Producto SET stock_actual = stock_actual + ? WHERE id_producto = ?", 'ii', [$retirada, $idProducto]);
                    (new Lote())->devolver($idProducto, $retirada);
                    $movimientos->registrar($actual['ci_operario'], $idProducto, $it['pasillo'] . '-' . $it['estante'] . '-' . $it['posicion'],
                        'Devolución', $idPedido, $retirada);
                    $repuestas += $retirada;
                }
            }
            $this->ejecutar("UPDATE Tiene SET recogido = FALSE WHERE id_pedido = ?", 'i', [$idPedido]);
            $this->ejecutar("UPDATE Pedido SET estado = 'Pendiente', recogido = FALSE, ci_operario = NULL, fecha_inicio_picking = NULL
                             WHERE id_pedido = ?", 'i', [$idPedido]);
            $this->confirmarTransaccion();
            return $repuestas;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* ---------- Tablero de prioridades del dashboard ---------- */

    /* Plata: pedidos en curso de recolección con su avance */
    public function enPicking()
    {
        return $this->filas("SELECT pe.id_pedido, COALESCE(po.usuario, 'sin asignar') AS operario,
                                    (SELECT COUNT(*) FROM Tiene t WHERE t.id_pedido = pe.id_pedido) AS total,
                                    (SELECT COALESCE(SUM(t.recogido), 0) FROM Tiene t WHERE t.id_pedido = pe.id_pedido) AS recogidos
                             FROM Pedido pe LEFT JOIN Persona po ON po.CI = pe.ci_operario
                             WHERE pe.estado = 'En Picking' ORDER BY pe.id_pedido");
    }

    /* Bronce: pedidos completados en los últimos 30 días */
    public function completadosRecientes()
    {
        return $this->filas("SELECT pe.id_pedido, pe.fecha_entrega, COALESCE(po.usuario, '—') AS operario
                             FROM Pedido pe LEFT JOIN Persona po ON po.CI = pe.ci_operario
                             WHERE pe.estado = 'Completado' AND pe.fecha_entrega >= NOW() - INTERVAL 30 DAY
                             ORDER BY pe.fecha_entrega DESC");
    }

    /* ---------- Seguimiento de operarios ---------- */

    /* Hojas de ruta en curso (pendientes, en picking o esperando validación) */
    public function hojasEnCurso()
    {
        return $this->filas("SELECT pe.id_pedido, pe.estado, pe.ci_operario, pe.fecha_inicio_picking, pe.fecha_entrega, pc.usuario AS cliente,
                                    COUNT(t.id_producto) AS total, COALESCE(SUM(t.recogido), 0) AS recogidos
                             FROM Pedido pe
                             INNER JOIN Persona pc ON pc.CI = pe.ci_cliente
                             INNER JOIN Tiene t ON t.id_pedido = pe.id_pedido
                             WHERE pe.estado IN ('Pendiente', 'En Picking', 'Por validar') AND pe.ci_operario IS NOT NULL
                             GROUP BY pe.id_pedido, pe.estado, pe.ci_operario, pe.fecha_inicio_picking, pe.fecha_entrega, pc.usuario
                             ORDER BY FIELD(pe.estado, 'En Picking', 'Por validar', 'Pendiente'), pe.id_pedido");
    }

    public function sinAsignar()
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Pedido WHERE estado = 'Pendiente' AND ci_operario IS NULL");
    }

    /* ---------- Cronograma de despachos ---------- */

    /* Una fila por pedido y zona: cada zona es una parada del recorrido del pedido.
       La fecha de despacho es la programada (fecha_entrega) o, si no tiene, la de creación.
       Si $ciOperario no es null, solo los pedidos asignados a ese operario. */
    public function cronograma($desde, $hasta, $ciOperario = null)
    {
        $soloOperario = $ciOperario === null ? 0 : 1;
        return $this->filas("SELECT DATE(COALESCE(pe.fecha_entrega, pe.fecha_creacion)) AS fecha,
                                    pe.fecha_entrega IS NOT NULL AS programado,
                                    z.id_zona, z.nombre AS zona, z.tipo AS tipo_zona,
                                    pe.id_pedido, pe.estado, pc.usuario AS cliente,
                                    COALESCE(po.usuario, 'Sin asignar') AS operario,
                                    SUM(t.Cantidad) AS unidades
                             FROM Pedido pe
                             INNER JOIN Persona pc ON pc.CI = pe.ci_cliente
                             LEFT JOIN Persona po ON po.CI = pe.ci_operario
                             INNER JOIN Tiene t ON t.id_pedido = pe.id_pedido
                             INNER JOIN Producto pr ON pr.id_producto = t.id_producto
                             INNER JOIN Ubicacion u ON u.id_ubicacion = pr.id_ubicacion
                             INNER JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE DATE(COALESCE(pe.fecha_entrega, pe.fecha_creacion)) BETWEEN ? AND ?
                               AND (? = 0 OR pe.ci_operario = ?)
                             GROUP BY fecha, programado, z.id_zona, z.nombre, z.tipo, pe.id_pedido, pe.estado, pc.usuario, po.usuario
                             ORDER BY fecha, z.nombre, pe.id_pedido", 'ssis', [$desde, $hasta, $soloOperario, (string)$ciOperario]);
    }

    /* ---------- Historial del operario ---------- */

    /* Pedidos del operario con el estado mostrado, el tiempo de picking y las unidades */
    public function historialOperario($ci)
    {
        return $this->filas("SELECT pe.id_pedido, pe.estado, pc.usuario AS cliente,
                                    CASE pe.estado
                                        WHEN 'Completado' THEN 'Completado'
                                        WHEN 'En Picking' THEN 'En Proceso'
                                        WHEN 'Por validar' THEN 'En Proceso'
                                        ELSE 'Pendiente'
                                    END AS estado_mostrado,
                                    pe.fecha_creacion, pe.fecha_inicio_picking, pe.fecha_entrega,
                                    TIMESTAMPDIFF(MINUTE, pe.fecha_inicio_picking,
                                        (SELECT MAX(r.fecha_hora) FROM Registra r WHERE r.id_pedido = pe.id_pedido AND r.Tipo_Registro = 'Retiro')) AS minutos_picking,
                                    (SELECT SUM(t.Cantidad) FROM Tiene t WHERE t.id_pedido = pe.id_pedido) AS unidades,
                                    (SELECT COUNT(*) FROM Tiene t WHERE t.id_pedido = pe.id_pedido) AS productos
                             FROM Pedido pe
                             INNER JOIN Persona pc ON pc.CI = pe.ci_cliente
                             WHERE pe.ci_operario = ?
                             ORDER BY FIELD(pe.estado, 'En Picking', 'Pendiente', 'Por validar', 'Completado'), pe.fecha_creacion DESC", 's', [$ci]);
    }
}
