<?php
/* =====================================================
   MODELO - PRODUCTO
   Catálogo de productos, stock disponible y alertas de
   reposición.
===================================================== */
class Producto extends Modelo
{
    /* Productos con código, categoría, ubicación y cantidad de documentos técnicos */
    public function listar()
    {
        return $this->filas("SELECT p.id_producto AS id, p.Nombre AS nombre, CONCAT('P', LPAD(p.id_producto, 4, '0')) AS codigo,
                                    c.nombre AS categoria,
                                    CONCAT(z.nombre, ' / ', u.pasillo, '-', u.estante, '-', u.posicion) AS ubicacion,
                                    p.stock_actual AS stock, p.stock_minimo AS minimo,
                                    (SELECT COUNT(*) FROM Documento_Producto d WHERE d.id_producto = p.id_producto) AS documentos
                             FROM Producto p
                             JOIN Categoria c ON c.id_categoria = p.id_categoria
                             JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             JOIN Zona z ON z.id_zona = u.id_zona
                             ORDER BY p.id_producto DESC");
    }

    public function contar()
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Producto");
    }

    public function buscar($id)
    {
        return $this->fila("SELECT * FROM Producto WHERE id_producto = ?", 'i', [$id]);
    }

    /* Producto con su zona y su ubicación física (documentación y API de pedidos) */
    public function buscarConZona($id)
    {
        return $this->fila("SELECT p.id_producto, p.Nombre, z.nombre AS zona, u.pasillo, u.estante, u.posicion FROM Producto p
                            INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion INNER JOIN Zona z ON z.id_zona = u.id_zona
                            WHERE p.id_producto = ?", 'i', [$id]);
    }

    public function crear($nombre, $descripcion, $stock, $stockMinimo, $idCategoria, $idUbicacion)
    {
        return $this->insertar("INSERT INTO Producto (Nombre, descripcion, stock_actual, stock_minimo, estado_producto, id_categoria, id_ubicacion)
                                VALUES (?, ?, ?, ?, 'Disponible', ?, ?)",
            'ssiiii', [$nombre, $descripcion, $stock, $stockMinimo, $idCategoria, $idUbicacion]);
    }

    public function actualizar($id, $nombre, $descripcion, $stock, $stockMinimo, $estado, $idCategoria, $idUbicacion)
    {
        return $this->ejecutar("UPDATE Producto SET Nombre = ?, descripcion = ?, stock_actual = ?, stock_minimo = ?, estado_producto = ?,
                                       id_categoria = ?, id_ubicacion = ?
                                WHERE id_producto = ?",
            'ssiisiii', [$nombre, $descripcion, $stock, $stockMinimo, $estado, $idCategoria, $idUbicacion, $id]);
    }

    /* Veces que el producto aparece en pedidos o en movimientos de stock */
    public function usos($id)
    {
        return (int)$this->valor("SELECT (SELECT COUNT(*) FROM Tiene WHERE id_producto = ?) + (SELECT COUNT(*) FROM Registra WHERE id_producto = ?)",
            'ii', [$id, $id]);
    }

    /* Borra el producto y su documentación. Las notificaciones viejas se conservan
       sin el vínculo al producto. Devuelve true si el producto existía. */
    public function eliminar($id)
    {
        $this->iniciarTransaccion();
        try {
            $this->ejecutar("UPDATE Notificacion SET id_producto = NULL WHERE id_producto = ?", 'i', [$id]);
            $this->ejecutar("DELETE FROM Documento_Producto WHERE id_producto = ?", 'i', [$id]);
            $borrado = $this->ejecutar("DELETE FROM Producto WHERE id_producto = ?", 'i', [$id]) === 1;
            $this->confirmarTransaccion();
            return $borrado;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    public function categorias()
    {
        return $this->filas("SELECT id_categoria, nombre FROM Categoria ORDER BY nombre");
    }

    /* Productos con su zona, indexados por id (selector del pedido nuevo) */
    public function listarConZona()
    {
        $productos = [];
        foreach ($this->filas("SELECT p.id_producto, p.Nombre, p.estado_producto, z.nombre AS zona, z.tipo FROM Producto p
                               INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion INNER JOIN Zona z ON z.id_zona = u.id_zona
                               ORDER BY p.Nombre") as $fila) {
            $productos[(int)$fila['id_producto']] = $fila;
        }
        return $productos;
    }

    /* Productos con su categoría, zona y ubicación física asignadas (simulador de la API) */
    public function listarNombres()
    {
        return $this->filas("SELECT p.id_producto, p.Nombre, c.nombre AS categoria, z.nombre AS zona, u.pasillo, u.estante, u.posicion
                             FROM Producto p
                             INNER JOIN Categoria c ON c.id_categoria = p.id_categoria
                             INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             INNER JOIN Zona z ON z.id_zona = u.id_zona
                             ORDER BY p.Nombre");
    }

    /* Stock disponible para nuevos pedidos = stock físico - unidades reservadas.
       Reservado = cantidades de pedidos Pendiente / En Picking cuyo ítem todavía no se
       recogió (lo ya recogido ya se descontó del stock físico, RNE-11).
       Devuelve [id_producto => ['nombre', 'stock_actual', 'reservado', 'disponible']].
       Con $bloquear = true bloquea las filas de Producto (usar dentro de una transacción). */
    public function stockDisponible(array $idsProducto, $bloquear = false)
    {
        $ids = array_values(array_unique(array_map('intval', $idsProducto)));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $tipos = str_repeat('i', count($ids));

        $datos = [];
        foreach ($this->filas("SELECT id_producto, Nombre, stock_actual FROM Producto WHERE id_producto IN ($marcas)"
            . ($bloquear ? ' FOR UPDATE' : ''), $tipos, $ids) as $fila) {
            $datos[(int)$fila['id_producto']] = [
                'nombre' => $fila['Nombre'],
                'stock_actual' => (int)$fila['stock_actual'],
                'reservado' => 0,
                'disponible' => (int)$fila['stock_actual'],
            ];
        }

        foreach ($this->filas("SELECT t.id_producto, COALESCE(SUM(t.Cantidad), 0) AS reservado
                               FROM Tiene t
                               INNER JOIN Pedido pe ON pe.id_pedido = t.id_pedido
                               WHERE t.id_producto IN ($marcas) AND t.recogido = FALSE AND pe.estado IN ('Pendiente', 'En Picking')
                               GROUP BY t.id_producto", $tipos, $ids) as $fila) {
            $id = (int)$fila['id_producto'];
            if (isset($datos[$id])) {
                $datos[$id]['reservado'] = (int)$fila['reservado'];
                $datos[$id]['disponible'] = max(0, $datos[$id]['stock_actual'] - (int)$fila['reservado']);
            }
        }
        return $datos;
    }

    /* Prioridad Oro del dashboard: zonas con productos en el stock mínimo (RNE-6) */
    public function criticosPorZona()
    {
        return $this->filas("SELECT z.nombre AS zona, z.tipo, COUNT(*) AS productos,
                                    GROUP_CONCAT(CONCAT(p.Nombre, ' (', p.stock_actual, '/', p.stock_minimo, ')') ORDER BY p.Nombre SEPARATOR ', ') AS detalle
                             FROM Producto p
                             INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion INNER JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE p.stock_actual <= p.stock_minimo
                             GROUP BY z.id_zona, z.nombre, z.tipo ORDER BY z.nombre");
    }

    /* Productos del inventario con su ubicación y las unidades reservadas por pedidos sin recoger */
    public function inventario()
    {
        return $this->filas("SELECT p.id_producto, p.Nombre, p.stock_actual, p.stock_minimo, p.estado_producto, c.nombre AS categoria,
                                    u.id_zona, u.pasillo, u.estante, u.posicion,
                                    COALESCE((SELECT SUM(t.Cantidad) FROM Tiene t INNER JOIN Pedido pe ON pe.id_pedido = t.id_pedido
                                              WHERE t.id_producto = p.id_producto AND t.recogido = FALSE
                                                AND pe.estado IN ('Pendiente', 'En Picking')), 0) AS reservado
                             FROM Producto p
                             INNER JOIN Categoria c ON c.id_categoria = p.id_categoria
                             INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
                             ORDER BY (p.stock_actual <= p.stock_minimo) DESC, p.Nombre");
    }
}
