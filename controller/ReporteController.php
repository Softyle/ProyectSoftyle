<?php
/* =====================================================
   CONTROLADOR - REPORTES (administrador)
   Ejecuta las consultas avanzadas del modelo Reporte y
   las muestra junto con el SQL de cada una.
===================================================== */
class ReporteController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $this->vista('admin/reportes', ['reportes' => (new Reporte())->todos()]);
    }
}
