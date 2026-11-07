<?php
/* =====================================================
   MODELO BASE
   Todos los modelos heredan de esta clase: reciben la
   conexión y usan estos métodos para ejecutar consultas
   preparadas (los datos nunca se pegan dentro del SQL).

   $tipos sigue la convención de bind_param:
   i = entero, d = decimal, s = texto.
===================================================== */
abstract class Modelo
{
    protected $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /* Prepara y ejecuta una consulta con sus parámetros */
    protected function preparar($sql, $tipos = '', array $parametros = [])
    {
        $stmt = $this->db->prepare($sql);
        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$parametros);
        }
        $stmt->execute();
        return $stmt;
    }

    /* Todas las filas del resultado (arreglo de arreglos asociativos) */
    protected function filas($sql, $tipos = '', array $parametros = [])
    {
        $stmt = $this->preparar($sql, $tipos, $parametros);
        $resultado = $stmt->get_result();
        $filas = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $filas;
    }

    /* La primera fila o null si no hay resultados */
    protected function fila($sql, $tipos = '', array $parametros = [])
    {
        return $this->filas($sql, $tipos, $parametros)[0] ?? null;
    }

    /* El primer valor de la primera fila (por ejemplo, un COUNT) */
    protected function valor($sql, $tipos = '', array $parametros = [])
    {
        $fila = $this->fila($sql, $tipos, $parametros);
        return $fila === null ? null : reset($fila);
    }

    /* INSERT, UPDATE o DELETE: devuelve la cantidad de filas afectadas */
    protected function ejecutar($sql, $tipos = '', array $parametros = [])
    {
        $stmt = $this->preparar($sql, $tipos, $parametros);
        $afectadas = $stmt->affected_rows;
        $stmt->close();
        return $afectadas;
    }

    /* INSERT: devuelve el id autoincremental generado */
    protected function insertar($sql, $tipos = '', array $parametros = [])
    {
        $this->ejecutar($sql, $tipos, $parametros);
        return $this->db->insert_id;
    }

    /* Transacciones: todo se guarda junto o no se guarda nada */
    public function iniciarTransaccion()
    {
        $this->db->begin_transaction();
    }

    public function confirmarTransaccion()
    {
        $this->db->commit();
    }

    public function deshacerTransaccion()
    {
        try {
            $this->db->rollback();
        } catch (Throwable $ex) {
            // no había transacción abierta
        }
    }
}
