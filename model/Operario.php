<?php
/* =====================================================
   MODELO - OPERARIO (tablas Persona + Operario)
   Alta, baja y modificación de operarios, su perfil y
   los datos del seguimiento en tiempo real.
===================================================== */
class Operario extends Modelo
{
    /* Todos los operarios (para la gestión de personal) */
    public function listar()
    {
        return $this->filas("SELECT p.CI AS id, p.usuario AS nombre, p.usuario, o.Estado AS estado
                             FROM Persona p JOIN Operario o ON o.CI = p.CI
                             WHERE p.Rol = 'Operario' ORDER BY p.CI DESC");
    }

    /* Operarios activos: a ellos se les pueden asignar pedidos */
    public function listarActivos()
    {
        return $this->filas("SELECT o.CI, p.usuario FROM Operario o INNER JOIN Persona p ON p.CI = o.CI
                             WHERE o.Estado = 'Activo' ORDER BY p.usuario");
    }

    public function esActivo($ci)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Operario WHERE CI = ? AND Estado = 'Activo'", 's', [$ci]) === 1;
    }

    public function contarActivos()
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Operario WHERE Estado = 'Activo'");
    }

    /* CI de todos los operarios (para otorgar logros antes del ranking) */
    public function todosLosCi()
    {
        return array_column($this->filas("SELECT CI FROM Operario"), 'CI');
    }

    public function buscar($ci)
    {
        return $this->fila("SELECT p.CI AS id, p.usuario AS nombre, p.usuario, p.foto_perfil, o.Estado AS estado, o.hora_ingreso
                            FROM Persona p JOIN Operario o ON o.CI = p.CI
                            WHERE p.CI = ? AND p.Rol = 'Operario'", 's', [$ci]);
    }

    /* Alta: Persona y Operario se guardan juntos (si algo falla no queda a medias).
       Un CI, usuario o email repetido llega como excepción con código 1062. */
    public function crear($ci, $usuario, $email, $hash, $horaIngreso)
    {
        $this->iniciarTransaccion();
        try {
            $this->ejecutar("INSERT INTO Persona (CI, usuario, email, `contraseña`, Rol) VALUES (?, ?, ?, ?, 'Operario')",
                'ssss', [$ci, $usuario, $email, $hash]);
            $this->ejecutar("INSERT INTO Operario (CI, Estado, hora_ingreso) VALUES (?, 'Activo', ?)", 'ss', [$ci, $horaIngreso]);
            $this->confirmarTransaccion();
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Modificación: si $hash es null se conserva la contraseña actual */
    public function actualizar($ci, $usuario, $hash, $estado)
    {
        $this->iniciarTransaccion();
        try {
            if ($hash !== null) {
                $this->ejecutar("UPDATE Persona SET usuario = ?, `contraseña` = ? WHERE CI = ? AND Rol = 'Operario'", 'sss', [$usuario, $hash, $ci]);
            } else {
                $this->ejecutar("UPDATE Persona SET usuario = ? WHERE CI = ? AND Rol = 'Operario'", 'ss', [$usuario, $ci]);
            }
            $this->ejecutar("UPDATE Operario SET Estado = ? WHERE CI = ?", 'ss', [$estado, $ci]);
            $this->confirmarTransaccion();
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    /* Pedidos y movimientos de stock asociados (si tiene, no se puede borrar) */
    public function cantidadRegistros($ci)
    {
        return (int)$this->valor("SELECT (SELECT COUNT(*) FROM Pedido WHERE ci_operario = ?) + (SELECT COUNT(*) FROM Registra WHERE ci_operario = ?)",
            'ss', [$ci, $ci]);
    }

    public function eliminar($ci)
    {
        $this->iniciarTransaccion();
        try {
            $this->ejecutar("DELETE FROM Operario WHERE CI = ?", 's', [$ci]);
            $this->ejecutar("DELETE FROM Persona WHERE CI = ? AND Rol = 'Operario'", 's', [$ci]);
            $this->confirmarTransaccion();
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    public function pedidosCompletados($ci)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Pedido WHERE ci_operario = ? AND estado = 'Completado'", 's', [$ci]);
    }

    /* Jornadas de trabajo: primer y último movimiento de stock de cada día */
    public function jornadas($ci)
    {
        return $this->filas("SELECT DATE(fecha_hora) AS fecha, MIN(TIME(fecha_hora)) AS hora_inicio, MAX(TIME(fecha_hora)) AS hora_fin,
                                    TIMEDIFF(MAX(TIME(fecha_hora)), MIN(TIME(fecha_hora))) AS tiempo
                             FROM Registra WHERE ci_operario = ?
                             GROUP BY DATE(fecha_hora) ORDER BY fecha DESC LIMIT 20", 's', [$ci]);
    }

    /* ---------- Perfil propio del operario ---------- */

    public function datosPerfil($ci)
    {
        return $this->fila("SELECT p.CI, p.usuario, p.email, p.telefono, p.direccion, p.foto_perfil, p.`contraseña` AS hash,
                                   o.Estado, o.hora_ingreso
                            FROM Persona p INNER JOIN Operario o ON o.CI = p.CI
                            WHERE p.CI = ?", 's', [$ci]);
    }

    public function actualizarContacto($ci, $email, $telefono, $direccion)
    {
        return $this->ejecutar("UPDATE Persona SET email = ?, telefono = ?, direccion = ? WHERE CI = ?",
            'ssss', [$email, $telefono, $direccion, $ci]);
    }

    public function actualizarFoto($ci, $archivo)
    {
        return $this->ejecutar("UPDATE Persona SET foto_perfil = ? WHERE CI = ?", 'ss', [$archivo, $ci]);
    }

    /* Archivo de la foto de perfil (o null si no tiene) */
    public function foto($ci)
    {
        return $this->valor("SELECT foto_perfil FROM Persona WHERE CI = ?", 's', [$ci]);
    }

    /* Resumen de pedidos del operario por estado */
    public function resumenPedidos($ci)
    {
        return $this->fila("SELECT SUM(estado = 'Completado') AS completados,
                                   SUM(estado IN ('Pendiente', 'En Picking')) AS activos,
                                   SUM(estado = 'Por validar') AS por_validar
                            FROM Pedido WHERE ci_operario = ?", 's', [$ci]);
    }

    /* ---------- Seguimiento en tiempo real (administrador) ---------- */

    /* Operarios activos con sus pedidos completados (hoy y en total) */
    public function activosConTotales()
    {
        return $this->filas("SELECT o.CI, p.usuario, p.foto_perfil, o.hora_ingreso,
                                    SUM(pe.estado = 'Completado' AND DATE(pe.fecha_entrega) = CURDATE()) AS completados_hoy,
                                    SUM(pe.estado = 'Completado') AS completados
                             FROM Operario o
                             INNER JOIN Persona p ON p.CI = o.CI
                             LEFT JOIN Pedido pe ON pe.ci_operario = o.CI
                             WHERE o.Estado = 'Activo'
                             GROUP BY o.CI, p.usuario, p.foto_perfil, o.hora_ingreso
                             ORDER BY p.usuario");
    }
}
