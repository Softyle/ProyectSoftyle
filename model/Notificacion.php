<?php
/* =====================================================
   MODELO - NOTIFICACIÓN
   Las de stock bajo las crean los triggers de MySQL
   (RNE-6); las de cadena de frío, la API de sensores.
===================================================== */
class Notificacion extends Modelo
{
    /* Últimas notificaciones del sistema con el nombre del producto */
    public function ultimas($limite = 8)
    {
        return $this->filas("SELECT n.tipo, n.mensaje, n.fecha, p.Nombre AS producto
                             FROM Notificacion n LEFT JOIN Producto p ON p.id_producto = n.id_producto
                             ORDER BY n.fecha DESC, n.id_notificacion DESC LIMIT ?", 'i', [$limite]);
    }

    public function ultimasDeTipo($tipo, $limite = 8)
    {
        return $this->filas("SELECT mensaje, fecha FROM Notificacion WHERE tipo = ? ORDER BY fecha DESC, id_notificacion DESC LIMIT ?",
            'si', [$tipo, $limite]);
    }

    public function crearDeZona($tipo, $mensaje, $idZona)
    {
        return $this->insertar("INSERT INTO Notificacion (tipo, mensaje, fecha, id_producto, id_zona) VALUES (?, ?, NOW(), NULL, ?)",
            'ssi', [$tipo, $mensaje, $idZona]);
    }
}
