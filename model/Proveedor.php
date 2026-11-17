<?php
/* =====================================================
   MODELO - PROVEEDORES
   A quién se le compra cada producto y cuántos días
   tarda en llegar un pedido (sirve para la reposición).
===================================================== */
class Proveedor extends Modelo
{
    /* Proveedores con la cantidad de productos y de órdenes abiertas */
    public function listar()
    {
        return $this->filas("SELECT v.*,
                                    (SELECT COUNT(*) FROM Producto p WHERE p.id_proveedor = v.id_proveedor) AS productos,
                                    (SELECT COUNT(*) FROM Orden_Compra o WHERE o.id_proveedor = v.id_proveedor AND o.estado IN ('Pendiente', 'Parcial')) AS ordenes_abiertas
                             FROM Proveedor v ORDER BY v.activo DESC, v.nombre");
    }

    /* Para los selectores: solo los activos (más el indicado, aunque esté inactivo) */
    public function activos($incluirId = 0)
    {
        return $this->filas("SELECT id_proveedor, nombre, dias_entrega, email FROM Proveedor
                             WHERE activo = TRUE OR id_proveedor = ? ORDER BY nombre", 'i', [$incluirId]);
    }

    public function buscar($id)
    {
        return $this->fila("SELECT * FROM Proveedor WHERE id_proveedor = ?", 'i', [$id]);
    }

    public function existeNombre($nombre, $excluirId = 0)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Proveedor WHERE nombre = ? AND id_proveedor <> ?", 'si', [$nombre, $excluirId]) > 0;
    }

    public function crear(array $d)
    {
        return $this->insertar("INSERT INTO Proveedor (nombre, rut, contacto, telefono, email, dias_entrega, activo) VALUES (?, ?, ?, ?, ?, ?, ?)",
            'sssssii', [$d['nombre'], $d['rut'] ?: null, $d['contacto'] ?: null, $d['telefono'] ?: null, $d['email'] ?: null, $d['dias_entrega'], $d['activo'] ? 1 : 0]);
    }

    public function actualizar($id, array $d)
    {
        return $this->ejecutar("UPDATE Proveedor SET nombre = ?, rut = ?, contacto = ?, telefono = ?, email = ?, dias_entrega = ?, activo = ?
                                WHERE id_proveedor = ?",
            'sssssiii', [$d['nombre'], $d['rut'] ?: null, $d['contacto'] ?: null, $d['telefono'] ?: null, $d['email'] ?: null, $d['dias_entrega'], $d['activo'] ? 1 : 0, $id]);
    }

    /* Veces que figura en órdenes o recepciones (si tiene historia no se borra, se desactiva) */
    public function usos($id)
    {
        return (int)$this->valor("SELECT (SELECT COUNT(*) FROM Orden_Compra WHERE id_proveedor = ?) + (SELECT COUNT(*) FROM Recepcion_Mercaderia WHERE id_proveedor = ?)",
            'ii', [$id, $id]);
    }

    /* Borra el proveedor; sus productos quedan sin proveedor asignado */
    public function eliminar($id)
    {
        $this->iniciarTransaccion();
        try {
            $this->ejecutar("UPDATE Producto SET id_proveedor = NULL WHERE id_proveedor = ?", 'i', [$id]);
            $borrado = $this->ejecutar("DELETE FROM Proveedor WHERE id_proveedor = ?", 'i', [$id]) === 1;
            $this->confirmarTransaccion();
            return $borrado;
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Productos de un proveedor */
    public function productos($id)
    {
        return $this->filas("SELECT id_producto, Nombre, stock_actual, stock_minimo FROM Producto WHERE id_proveedor = ? ORDER BY Nombre", 'i', [$id]);
    }
}
