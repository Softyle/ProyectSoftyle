<?php
/* =====================================================
   MODELO - RECEPCIONES DE LA API (tabla API_Recepciones)
   Cada orden que llega por la API simulada queda
   registrada, se acepte o no.
===================================================== */
class Recepcion extends Modelo
{
    /* true si la base ya tiene la tabla de la API */
    public function existeTabla()
    {
        return (int)$this->valor("SELECT COUNT(*) FROM information_schema.TABLES
                                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'api_recepciones'") > 0;
    }

    public function ultimas($limite = 20)
    {
        return $this->filas("SELECT id_recepcion, nombre_cliente, sku, cantidad, zona_destino, estado, mensaje, id_picking, fecha_recepcion
                             FROM API_Recepciones ORDER BY id_recepcion DESC LIMIT ?", 'i', [$limite]);
    }

    public function registrar($cliente, $sku, $cantidad, $zona, $estado, $mensaje, $idPicking)
    {
        return $this->insertar("INSERT INTO API_Recepciones (nombre_cliente, sku, cantidad, zona_destino, estado, mensaje, id_picking)
                                VALUES (?, ?, ?, ?, ?, ?, ?)", 'ssisssi', [$cliente, $sku, $cantidad, $zona, $estado, $mensaje, $idPicking]);
    }
}
