<?php
/* =====================================================
   CONTROLADOR - GAMIFICACIÓN
   Administrador: ranking de desempeño de los operarios.
   Operario: galería de sus insignias por precisión,
   velocidad y volumen de picking.
===================================================== */
class LogroController extends Controlador
{
    /* Mis logros (operario). Al entrar se otorgan los logros alcanzados. */
    public function index()
    {
        $this->requerirRol('operario');
        $ci = $this->ciUsuario();
        $modelo = new Logro();
        $modelo->actualizar($ci);
        $estadisticas = $modelo->estadisticas($ci);
        $logros = $modelo->galeria($ci, $estadisticas);
        $this->vista('operario/logros', [
            'est' => $estadisticas,
            'logros' => $logros,
            'obtenidos' => count(array_filter($logros, fn($l) => $l['obtenido'])),
        ]);
    }

    /* Desempeño de operarios (administrador): ranking por pedidos completados y precisión */
    public function desempeno()
    {
        $this->requerirRol('admin');
        $modelo = new Logro();
        // Antes de mostrar el ranking se otorgan los logros pendientes de todos los operarios
        foreach ((new Operario())->todosLosCi() as $ci) {
            $modelo->actualizar($ci);
        }
        $this->vista('admin/desempeno', [
            'ranking' => $modelo->ranking(),
            'insignias' => $modelo->insigniasPorOperario(),
            'totalLogros' => $modelo->total(),
        ]);
    }
}
