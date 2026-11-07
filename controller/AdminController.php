<?php
/* =====================================================
   CONTROLADOR - PANEL DEL ADMINISTRADOR
   Indicadores generales y tablero de prioridades:
   Oro    (crítico)    -> zonas con productos en el stock mínimo (RNE-6)
   Plata  (en proceso) -> pedidos en curso de recolección
   Bronce (completado) -> pedidos completados en los últimos 30 días
===================================================== */
class AdminController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $pedidos = new Pedido();
        $this->vista('admin/dashboard', [
            'pedidosPendientes' => $pedidos->contarPorEstado('Pendiente'),
            'pedidosCompletados' => $pedidos->contarPorEstado('Completado'),
            'pedidosPorValidar' => $pedidos->contarPorEstado('Por validar'),
            'productos' => (new Producto())->contar(),
            'operariosActivos' => (new Operario())->contarActivos(),
            'prioridadOro' => (new Producto())->criticosPorZona(),
            'prioridadPlata' => $pedidos->enPicking(),
            'prioridadBronce' => $pedidos->completadosRecientes(),
        ]);
    }
}
