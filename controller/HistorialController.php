<?php
/* =====================================================
   CONTROLADOR - HISTORIAL DEL OPERADOR
   Trazabilidad del usuario logueado: todos sus pedidos y
   sus movimientos de stock. La vista pide los datos a
   "datos" y los actualiza cada 15 segundos. El estado se
   calcula en el momento a partir de la base de datos:
     Completado               -> Completado (bronce)
     En Picking / Por validar -> En Proceso (plata)
     Pendiente                -> Pendiente (gris)
===================================================== */
class HistorialController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('operario', ($_GET['a'] ?? '') === 'datos');
    }

    public function index()
    {
        $this->vista('operario/historial');
    }

    public function datos()
    {
        $ci = $this->ciUsuario();
        try {
            $pedidos = [];
            $resumen = ['Completado' => 0, 'En Proceso' => 0, 'Pendiente' => 0];
            foreach ((new Pedido())->historialOperario($ci) as $f) {
                $resumen[$f['estado_mostrado']]++;
                $pedidos[] = [
                    'id' => (int)$f['id_pedido'],
                    'estado' => $f['estado'],
                    'estado_mostrado' => $f['estado_mostrado'],
                    'cliente' => $f['cliente'],
                    'creado' => fecha($f['fecha_creacion']),
                    'cierre' => $f['estado'] === 'Completado' && $f['fecha_entrega'] ? fecha($f['fecha_entrega']) : null,
                    'minutos' => $f['minutos_picking'] === null ? null : (int)$f['minutos_picking'],
                    'unidades' => (int)$f['unidades'],
                    'productos' => (int)$f['productos'],
                ];
            }
            $movimientos = array_map(fn($f) => [
                'fecha' => fecha($f['fecha_hora']),
                'pedido' => $f['id_pedido'] === null ? null : (int)$f['id_pedido'],
                'producto' => $f['producto'],
                'ubicacion' => $f['Ubicacion_producto'],
                'tipo' => $f['Tipo_Registro'],
                'cantidad' => $f['cantidad'] === null ? null : (int)$f['cantidad'],
            ], (new Movimiento())->porOperario($ci, 100));

            $this->json(200, [
                'ok' => true,
                'actualizado' => date('H:i:s'),
                'resumen' => $resumen,
                'pedidos' => $pedidos,
                'movimientos' => $movimientos,
            ]);
        } catch (Throwable $ex) {
            error_log('PICKFLOW historial: ' . $ex->getMessage());
            $this->json(500, ['ok' => false, 'mensaje' => 'No se pudo cargar el historial.']);
        }
    }
}
