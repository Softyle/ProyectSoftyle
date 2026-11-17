<?php
/* =====================================================
   MODELO - ZONA
   Zonas del depósito según la naturaleza de los productos.
===================================================== */
class Zona extends Modelo
{
    // Tipos de zona de la letra del proyecto
    const TIPOS = ['Refrigerada', 'Seca', 'Voluminosa'];

    public function listar()
    {
        return $this->filas("SELECT id_zona, nombre, tipo FROM Zona ORDER BY nombre");
    }

    /* Nombres permitidos: "Zona A" ... "Zona Z" (una letra del alfabeto) */
    public static function nombresPosibles()
    {
        return array_map(fn($letra) => 'Zona ' . $letra, range('A', 'Z'));
    }

    /* true si otra zona ya usa ese nombre */
    public function existeNombre($nombre, $excluirId)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Zona WHERE nombre = ? AND id_zona <> ?", 'si', [$nombre, $excluirId]) > 0;
    }

    public function buscarActivaPorNombre($nombre)
    {
        return $this->fila("SELECT nombre FROM Zona WHERE nombre = ? AND estado = 'Activa'", 's', [$nombre]);
    }

    /* Para la gestión del centro logístico: zonas con el nombre de su depósito */
    public function listarConDeposito()
    {
        return $this->filas("SELECT z.*, d.nombre AS relacionado FROM Zona z JOIN Deposito d ON d.id_deposito = z.id_deposito ORDER BY z.id_zona DESC");
    }

    public function buscar($id)
    {
        return $this->fila("SELECT * FROM Zona WHERE id_zona = ?", 'i', [$id]);
    }

    public function crear(array $v)
    {
        return $this->insertar("INSERT INTO Zona (nombre, tipo, capacidad_total, estado, id_deposito) VALUES (?, ?, ?, ?, ?)",
            'ssisi', [$v['nombre'], $v['tipo'], (int)$v['capacidad_total'], $v['estado'], (int)$v['id_deposito']]);
    }

    public function actualizar($id, array $v)
    {
        return $this->ejecutar("UPDATE Zona SET nombre = ?, tipo = ?, capacidad_total = ?, estado = ?, id_deposito = ? WHERE id_zona = ?",
            'ssisii', [$v['nombre'], $v['tipo'], (int)$v['capacidad_total'], $v['estado'], (int)$v['id_deposito'], $id]);
    }

    public function eliminar($id)
    {
        return $this->ejecutar("DELETE FROM Zona WHERE id_zona = ?", 'i', [$id]);
    }

    /* Para elegir qué zona contar: zonas con productos, cuándo se contaron por última vez
       y si ya tienen un conteo abierto */
    public function listarConProductos()
    {
        return $this->filas("SELECT z.id_zona, z.nombre, z.tipo, COUNT(p.id_producto) AS productos,
                                    (SELECT MAX(c.fecha_cierre) FROM Conteo c WHERE c.id_zona = z.id_zona AND c.estado = 'Cerrado') AS ultimo_conteo,
                                    (SELECT COUNT(*) FROM Conteo c WHERE c.id_zona = z.id_zona AND c.estado = 'En curso') AS en_curso
                             FROM Zona z
                             JOIN Ubicacion u ON u.id_zona = z.id_zona
                             JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
                             GROUP BY z.id_zona, z.nombre, z.tipo
                             ORDER BY z.nombre");
    }

    /* Inventario: zonas con cantidad de productos, stock total y alertas */
    public function conTotales()
    {
        return $this->filas("SELECT z.id_zona, z.nombre, z.tipo, z.capacidad_total, z.estado, d.nombre AS deposito,
                                    COUNT(p.id_producto) AS productos,
                                    COALESCE(SUM(p.stock_actual), 0) AS stock_total,
                                    COALESCE(SUM(p.stock_actual <= p.stock_minimo), 0) AS alertas
                             FROM Zona z
                             INNER JOIN Deposito d ON d.id_deposito = z.id_deposito
                             LEFT JOIN Ubicacion u ON u.id_zona = z.id_zona
                             LEFT JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
                             GROUP BY z.id_zona, z.nombre, z.tipo, z.capacidad_total, z.estado, d.nombre
                             ORDER BY d.nombre, z.nombre");
    }
}
