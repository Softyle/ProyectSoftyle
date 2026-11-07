<?php
/* =====================================================
   MODELO - DEPÓSITO
===================================================== */
class Deposito extends Modelo
{
    public function listar()
    {
        return $this->filas("SELECT id_deposito, nombre FROM Deposito ORDER BY nombre");
    }
}
