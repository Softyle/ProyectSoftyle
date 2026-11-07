<?php
/* =====================================================
   CONTROLADOR - OPERARIOS Y HOJAS DE RUTA (administrador)
   Visualización dinámica de los operarios activos y del
   estado de sus hojas de ruta. La vista pide los datos a
   la acción "datos" cada 10 segundos (jQuery + AJAX).
===================================================== */
class SeguimientoController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin', ($_GET['a'] ?? '') === 'datos');
    }

    public function index()
    {
        $this->vista('admin/seguimiento');
    }

    /* JSON: operarios activos y el avance de sus pedidos */
    public function datos()
    {
        try {
            $operarios = [];
            foreach ((new Operario())->activosConTotales() as $fila) {
                $operarios[$fila['CI']] = [
                    'ci' => $fila['CI'],
                    'usuario' => $fila['usuario'],
                    'foto' => fotoPerfil($fila['foto_perfil']),
                    'hora_ingreso' => substr((string)$fila['hora_ingreso'], 0, 5),
                    'completados' => (int)$fila['completados'],
                    'completados_hoy' => (int)$fila['completados_hoy'],
                    'pedidos' => [],
                ];
            }
            $pedidos = new Pedido();
            foreach ($pedidos->hojasEnCurso() as $fila) {
                if (!isset($operarios[$fila['ci_operario']])) {
                    continue; // operario inactivo
                }
                $operarios[$fila['ci_operario']]['pedidos'][] = [
                    'id' => (int)$fila['id_pedido'],
                    'estado' => $fila['estado'],
                    'cliente' => $fila['cliente'],
                    'total' => (int)$fila['total'],
                    'recogidos' => (int)$fila['recogidos'],
                    'inicio' => $fila['fecha_inicio_picking'] ? fecha($fila['fecha_inicio_picking'], 'H:i') : null,
                    'entrega' => $fila['fecha_entrega'] ? fecha($fila['fecha_entrega'], 'd/m H:i') : null,
                ];
            }
            $this->json(200, [
                'ok' => true,
                'actualizado' => date('H:i:s'),
                'sin_asignar' => $pedidos->sinAsignar(),
                'operarios' => array_values($operarios),
            ]);
        } catch (Throwable $ex) {
            error_log('PICKFLOW seguimiento: ' . $ex->getMessage());
            $this->json(500, ['ok' => false, 'mensaje' => 'No se pudieron cargar los datos.']);
        }
    }
}
