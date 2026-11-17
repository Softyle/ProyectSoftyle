<?php
/* =====================================================
   MODELO - CONTEO DE INVENTARIO
   Se elige una zona y se cuenta lo que hay en cada lugar.
   Al empezar se guarda una foto del stock del sistema;
   al cerrar, las diferencias se ajustan solas y quedan en
   el historial de cada producto.

   El ajuste se hace por diferencia (contado - sistema al
   empezar) sobre el stock de ese momento: si mientras se
   contaba salió un pedido, ese retiro no se pierde.
===================================================== */
class Conteo extends Modelo
{
    /* Crea el conteo de la zona con el stock actual de cada producto. Lanza RuntimeException
       si la zona ya tiene un conteo abierto o no tiene productos. */
    public function crear($idZona, $ciCreador, $ciAsignado, $ciego, $observaciones)
    {
        $this->iniciarTransaccion();
        try {
            $this->fila("SELECT id_zona FROM Zona WHERE id_zona = ? FOR UPDATE", 'i', [$idZona]);
            if ((int)$this->valor("SELECT COUNT(*) FROM Conteo WHERE id_zona = ? AND estado = 'En curso'", 'i', [$idZona]) > 0) {
                throw new RuntimeException('Esa zona ya tiene un conteo en curso: terminalo o cancelalo antes de empezar otro.');
            }
            $id = $this->insertar("INSERT INTO Conteo (id_zona, fecha_inicio, estado, ciego, ci_creador, ci_asignado, observaciones)
                                   VALUES (?, NOW(), 'En curso', ?, ?, ?, ?)",
                'iisss', [$idZona, $ciego ? 1 : 0, $ciCreador, $ciAsignado !== '' ? $ciAsignado : null, $observaciones !== '' ? $observaciones : null]);
            $productos = $this->ejecutar("INSERT INTO Conteo_Detalle (id_conteo, id_producto, stock_sistema)
                                          SELECT ?, p.id_producto, p.stock_actual FROM Producto p
                                          JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                                          WHERE u.id_zona = ?", 'ii', [$id, $idZona]);
            if ($productos === 0) {
                throw new RuntimeException('Esa zona no tiene productos para contar.');
            }
            $this->confirmarTransaccion();
            return $id;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Conteos con su avance. Con $ci (operario), solo los que tiene asignados. */
    public function listar($ci = '')
    {
        return $this->filas("SELECT c.id_conteo, c.fecha_inicio, c.fecha_cierre, c.estado, c.ciego, z.nombre AS zona, z.tipo,
                                    cr.usuario AS creador, asg.usuario AS asignado,
                                    COUNT(d.id_producto) AS productos,
                                    COALESCE(SUM(d.cantidad_contada IS NOT NULL), 0) AS contados,
                                    COALESCE(SUM(d.cantidad_contada IS NOT NULL AND d.cantidad_contada <> d.stock_sistema), 0) AS diferencias
                             FROM Conteo c
                             JOIN Zona z ON z.id_zona = c.id_zona
                             JOIN Persona cr ON cr.CI = c.ci_creador
                             LEFT JOIN Persona asg ON asg.CI = c.ci_asignado
                             LEFT JOIN Conteo_Detalle d ON d.id_conteo = c.id_conteo
                             WHERE (? = '' OR c.ci_asignado = ?)
                             GROUP BY c.id_conteo, c.fecha_inicio, c.fecha_cierre, c.estado, c.ciego, z.nombre, z.tipo, cr.usuario, asg.usuario
                             ORDER BY c.estado <> 'En curso', c.id_conteo DESC
                             LIMIT 100", 'ss', [$ci, $ci]);
    }

    public function contarEnCurso($ci = '')
    {
        try {
            return (int)$this->valor("SELECT COUNT(*) FROM Conteo WHERE estado = 'En curso' AND (? = '' OR ci_asignado = ?)", 'ss', [$ci, $ci]);
        } catch (mysqli_sql_exception $ex) {
            return 0;
        }
    }

    public function buscar($id)
    {
        return $this->fila("SELECT c.*, z.nombre AS zona, z.tipo, cr.usuario AS creador, asg.usuario AS asignado
                            FROM Conteo c
                            JOIN Zona z ON z.id_zona = c.id_zona
                            JOIN Persona cr ON cr.CI = c.ci_creador
                            LEFT JOIN Persona asg ON asg.CI = c.ci_asignado
                            WHERE c.id_conteo = ?", 'i', [$id]);
    }

    /* Productos del conteo en el orden del recorrido (pasillo, estante, nivel) */
    public function detalle($id)
    {
        return $this->filas("SELECT d.id_producto, d.stock_sistema, d.cantidad_contada, d.fecha_conteo,
                                    p.Nombre, p.codigo_barras, IF(p.foto_oculta, NULL, p.foto) AS foto, p.stock_actual,
                                    u.pasillo, u.estante, u.posicion
                             FROM Conteo_Detalle d
                             JOIN Producto p ON p.id_producto = d.id_producto
                             JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             WHERE d.id_conteo = ?
                             ORDER BY u.pasillo, u.estante, u.posicion, p.Nombre", 'i', [$id]);
    }

    /* Guarda lo contado de un producto (null = borrar lo contado). Solo con el conteo en curso. */
    public function guardar($idConteo, $idProducto, $cantidad)
    {
        $existe = (int)$this->valor("SELECT COUNT(*) FROM Conteo_Detalle d JOIN Conteo c ON c.id_conteo = d.id_conteo
                                     WHERE d.id_conteo = ? AND d.id_producto = ? AND c.estado = 'En curso'", 'ii', [$idConteo, $idProducto]) === 1;
        if ($existe) {
            $this->ejecutar("UPDATE Conteo_Detalle SET cantidad_contada = ?, fecha_conteo = IF(? IS NULL, NULL, NOW())
                             WHERE id_conteo = ? AND id_producto = ?", 'iiii', [$cantidad, $cantidad, $idConteo, $idProducto]);
        }
        return $existe;
    }

    /* Cierra el conteo y ajusta el stock de los productos contados con diferencia.
       Lo que no se contó no se toca. Devuelve [ajustados, sobrantes, faltantes]. */
    public function cerrar($idConteo, $ci)
    {
        $this->iniciarTransaccion();
        try {
            $conteo = $this->fila("SELECT c.estado, z.nombre AS zona FROM Conteo c JOIN Zona z ON z.id_zona = c.id_zona
                                   WHERE c.id_conteo = ? FOR UPDATE", 'i', [$idConteo]);
            if (!$conteo || $conteo['estado'] !== 'En curso') {
                throw new RuntimeException('Este conteo ya no está en curso.');
            }
            $lotes = new Lote();
            $historial = new HistorialStock();
            $resumen = ['ajustados' => 0, 'sobrantes' => 0, 'faltantes' => 0];
            foreach ($this->filas("SELECT id_producto, stock_sistema, cantidad_contada FROM Conteo_Detalle
                                   WHERE id_conteo = ? AND cantidad_contada IS NOT NULL AND cantidad_contada <> stock_sistema",
                         'i', [$idConteo]) as $d) {
                $diferencia = (int)$d['cantidad_contada'] - (int)$d['stock_sistema'];
                $actual = (int)$this->valor("SELECT stock_actual FROM Producto WHERE id_producto = ? FOR UPDATE", 'i', [$d['id_producto']]);
                $nuevo = max(0, $actual + $diferencia);
                $this->ejecutar("UPDATE Producto SET stock_actual = ? WHERE id_producto = ?", 'ii', [$nuevo, $d['id_producto']]);
                $lotes->ajustarAStock((int)$d['id_producto']);
                $historial->registrar((int)$d['id_producto'], 'Ajuste de conteo', $nuevo - $actual, $nuevo, $ci,
                    'Conteo #' . $idConteo . ' de la ' . $conteo['zona'] . ': había ' . $d['stock_sistema'] . ' en el sistema y se contaron ' . $d['cantidad_contada']);
                $resumen['ajustados']++;
                $resumen[$diferencia > 0 ? 'sobrantes' : 'faltantes'] += abs($diferencia);
            }
            $this->ejecutar("UPDATE Conteo SET estado = 'Cerrado', fecha_cierre = NOW() WHERE id_conteo = ?", 'i', [$idConteo]);
            $this->confirmarTransaccion();
            return $resumen;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    public function cancelar($idConteo)
    {
        return $this->ejecutar("UPDATE Conteo SET estado = 'Cancelado', fecha_cierre = NOW() WHERE id_conteo = ? AND estado = 'En curso'", 'i', [$idConteo]) === 1;
    }

    /* Le saca el conteo al operario: lo sigue el administrador. Lo ya contado se conserva. */
    public function quitarAsignado($idConteo)
    {
        return $this->ejecutar("UPDATE Conteo SET ci_asignado = NULL WHERE id_conteo = ? AND estado = 'En curso' AND ci_asignado IS NOT NULL", 'i', [$idConteo]) === 1;
    }
}
