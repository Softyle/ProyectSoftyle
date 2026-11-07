<?php
/* =====================================================
   MODELO - CLIENTE
===================================================== */
class Cliente extends Modelo
{
    /* Clientes para los selectores (CI y nombre de usuario) */
    public function listar()
    {
        return $this->filas("SELECT c.CI, p.usuario FROM Cliente c INNER JOIN Persona p ON p.CI = c.CI ORDER BY p.usuario");
    }

    /* CI de los clientes con ese nombre de usuario (lo usa la API de pedidos) */
    public function buscarPorNombre($nombre)
    {
        return $this->filas("SELECT c.CI FROM Cliente c INNER JOIN Persona p ON p.CI = c.CI WHERE p.usuario = ?", 's', [$nombre]);
    }
}
