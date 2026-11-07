<?php
/* =====================================================
   MODELO - USUARIO (tabla Persona)
   Datos para el inicio de sesión de administradores y
   operarios.
===================================================== */
class Usuario extends Modelo
{
    /* Usuario con su contraseña (hash), rol y estado de operario */
    public function buscarPorUsuario($usuario)
    {
        return $this->fila("SELECT p.CI, p.usuario, p.`contraseña` AS password, p.Rol, o.Estado
                            FROM Persona p LEFT JOIN Operario o ON o.CI = p.CI
                            WHERE p.usuario = ? LIMIT 1", 's', [$usuario]);
    }

    /* Guarda un nuevo hash de contraseña */
    public function cambiarPassword($ci, $hash)
    {
        return $this->ejecutar("UPDATE Persona SET `contraseña` = ? WHERE CI = ?", 'ss', [$hash, $ci]);
    }
}
