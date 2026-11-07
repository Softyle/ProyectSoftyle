<?php
/* =====================================================
   CONTROLADOR - PEDIDOS (administrador)
   Lista con filtros y reporte, asignación a operarios,
   generación de pedidos y validación de tareas.
===================================================== */
class PedidoController extends Controlador
{
    const FILTROS = ['Todos', 'Pendiente', 'En Picking', 'Por validar', 'Completado'];

    public function __construct()
    {
        // La validación por AJAX responde JSON si la sesión venció
        $this->requerirRol('admin', ($_GET['a'] ?? '') === 'procesarValidacion');
    }

    /* Lista de pedidos (filtrable por estado desde las tarjetas del dashboard) */
    public function index()
    {
        $estado = $_GET['estado'] ?? 'Todos';
        if (!in_array($estado, self::FILTROS, true)) {
            $estado = 'Todos';
        }
        $this->vista('admin/pedidos', [
            'estado' => $estado,
            'filtros' => self::FILTROS,
            'pedidos' => (new Pedido())->listar($estado),
            'operarios' => (new Operario())->listarActivos(),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    /* Asigna un pedido pendiente a un operario activo */
    public function asignar()
    {
        $estado = in_array($_POST['estado'] ?? '', self::FILTROS, true) ? $_POST['estado'] : 'Todos';
        $volver = fn($clave, $texto) => $this->redirigir('pedido', 'index', ['estado' => $estado, $clave => $texto]);

        if (!$this->esPost() || !$this->csrfValido()) {
            $volver('error', 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.');
        }
        $idPedido = (int)($_POST['pedido_id'] ?? 0);
        $ciOperario = trim((string)($_POST['operario_ci'] ?? ''));
        if ($idPedido <= 0 || $ciOperario === '') {
            $volver('error', 'Seleccioná un operario para realizar la asignación.');
        }
        if (!(new Operario())->esActivo($ciOperario)) {
            $volver('error', 'El operario seleccionado no está activo.');
        }
        // Solo se asignan pedidos pendientes que todavía no tengan operario
        if (!(new Pedido())->asignar($idPedido, $ciOperario)) {
            $volver('error', 'El pedido ya fue asignado, no está pendiente o no existe.');
        }
        $volver('mensaje', 'Pedido #' . $idPedido . ' asignado correctamente.');
    }

    /* Generación de pedidos: cliente, productos y cantidades. Se valida el stock
       disponible y el pedido entra como "Pendiente" hasta que se asigne. */
    public function nuevo()
    {
        $productos = (new Producto())->listarConZona();
        $clientes = (new Cliente())->listar();
        $error = '';
        $lineasForm = [['id_producto' => '', 'cantidad' => 1]];
        $clienteForm = '';
        $fechaForm = '';

        if ($this->esPost()) {
            $clienteForm = trim((string)($_POST['ci_cliente'] ?? ''));
            $fechaForm = trim((string)($_POST['fecha_entrega'] ?? ''));
            $idsPost = (array)($_POST['producto'] ?? []);
            $cantidadesPost = (array)($_POST['cantidad'] ?? []);

            // Se juntan las líneas del mismo producto (en Tiene no puede repetirse)
            $lineas = [];
            $lineasForm = [];
            foreach ($idsPost as $i => $idProducto) {
                $idProducto = (int)$idProducto;
                $cantidad = filter_var($cantidadesPost[$i] ?? null, FILTER_VALIDATE_INT);
                $lineasForm[] = ['id_producto' => $idProducto ?: '', 'cantidad' => $cantidad === false ? '' : $cantidad];
                if ($idProducto <= 0) {
                    continue;
                }
                if ($cantidad === false || $cantidad < 1) {
                    $error = 'Cada producto debe tener una cantidad mayor a cero.';
                    break;
                }
                $lineas[$idProducto] = ($lineas[$idProducto] ?? 0) + $cantidad;
            }
            if (!$lineasForm) {
                $lineasForm = [['id_producto' => '', 'cantidad' => 1]];
            }

            // Fecha programada de entrega (opcional): la usa el cronograma de despachos
            $fechaEntrega = null;
            if (!$error && $fechaForm !== '') {
                $fecha = DateTime::createFromFormat('Y-m-d\TH:i', $fechaForm);
                if (!$fecha) {
                    $error = 'La fecha de entrega no es válida.';
                } elseif ($fecha < new DateTime('-1 minute')) {
                    $error = 'La fecha de entrega no puede ser anterior a hoy.';
                } else {
                    $fechaEntrega = $fecha->format('Y-m-d H:i:s');
                }
            }

            if (!$error && !$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif (!$error && !in_array($clienteForm, array_column($clientes, 'CI'), true)) {
                $error = 'Seleccioná un cliente.';
            } elseif (!$error && !$lineas) {
                // RNE-7: no pueden existir pedidos vacíos
                $error = 'Agregá al menos un producto al pedido.';
            } elseif (!$error) {
                try {
                    $resultado = (new Pedido())->crear($clienteForm, $fechaEntrega, $lineas);
                    if ($resultado['ok']) {
                        $this->redirigir('pedido', 'index', ['estado' => 'Pendiente',
                            'mensaje' => 'Pedido #' . $resultado['id'] . ' creado. Queda pendiente hasta que lo asignes a un operario.']);
                    }
                    $error = 'Stock insuficiente: ' . implode('; ', $resultado['faltantes']) . '.';
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW pedido nuevo: ' . $ex->getMessage());
                    $error = 'No se pudo guardar el pedido. Intentá de nuevo.';
                }
            }
        }

        $this->vista('admin/pedido_nuevo', [
            'clientes' => $clientes,
            'productos' => $productos,
            'disponibles' => (new Producto())->stockDisponible(array_keys($productos)),
            'error' => $error,
            'lineasForm' => $lineasForm,
            'clienteForm' => $clienteForm,
            'fechaForm' => $fechaForm,
        ]);
    }

    /* Validación de tareas: lo solicitado contra lo retirado. Las acciones se
       envían por AJAX a procesarValidacion (js/validar_pedido.js). */
    public function validar()
    {
        $idPedido = (int)($_GET['id'] ?? 0);
        $modelo = new Pedido();
        [$pedido, $items] = $modelo->detalle($idPedido);
        if (!$pedido) {
            $this->redirigir('pedido', 'index', ['error' => 'Pedido no encontrado.']);
        }
        $this->vista('admin/pedido_validar', [
            'idPedido' => $idPedido,
            'pedido' => $pedido,
            'items' => $items,
            'movimientos' => (new Movimiento())->porPedido($idPedido),
            'porValidar' => $pedido['estado'] === 'Por validar',
            'coinciden' => Pedido::todoCoincide($items),
        ]);
    }

    /* AJAX (POST): accion=confirmar -> Completado y logros del operario;
                    accion=devolver  -> los productos marcados vuelven a la hoja de ruta.
       Responde siempre JSON con el pedido y sus ítems actualizados. */
    public function procesarValidacion()
    {
        if (!$this->esPost()) {
            $this->json(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
        }
        if (!$this->csrfValido()) {
            $this->json(403, ['ok' => false, 'mensaje' => 'La sesión de la página venció. Recargá la página.']);
        }
        $idPedido = (int)($_POST['id_pedido'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $modelo = new Pedido();
        [$pedido, $items] = $modelo->detalle($idPedido);
        if (!$pedido) {
            $this->json(404, ['ok' => false, 'mensaje' => 'El pedido no existe.']);
        }
        if ($pedido['estado'] !== 'Por validar') {
            $this->responderValidacion($idPedido, 409, false, 'Este pedido ya no está esperando validación (estado: ' . $pedido['estado'] . ').');
        }

        if ($accion === 'confirmar') {
            if (!Pedido::todoCoincide($items)) {
                $this->responderValidacion($idPedido, 422, false, 'No se puede completar: hay productos sin recoger o con cantidades distintas a las solicitadas. Devolvelos al picking.');
            }
            if (!$modelo->completar($idPedido)) {
                $this->responderValidacion($idPedido, 409, false, 'No se pudo completar el pedido. Actualizá la página.');
            }
            // Gamificación: el operario puede haber ganado insignias con este pedido
            (new Logro())->actualizar($pedido['ci_operario']);
            $this->responderValidacion($idPedido, 200, true, 'Pedido #' . $idPedido . ' validado y marcado como Completado.');
        }

        if ($accion === 'devolver') {
            $seleccion = array_map('intval', (array)($_POST['devolver'] ?? []));
            if (!$seleccion) {
                $this->responderValidacion($idPedido, 422, false, 'Marcá al menos un producto para devolver al picking.');
            }
            try {
                $modelo->devolverAlPicking($idPedido, $seleccion, $items);
                $this->responderValidacion($idPedido, 200, true, 'Pedido #' . $idPedido . ' devuelto al operario para corregir el picking. El stock de los productos devueltos se repuso.');
            } catch (Throwable $ex) {
                error_log('PICKFLOW validación (devolver): ' . $ex->getMessage());
                $this->responderValidacion($idPedido, 409, false, $ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudo devolver el pedido al picking.');
            }
        }

        $this->json(400, ['ok' => false, 'mensaje' => 'Acción desconocida.']);
    }

    /* Respuesta JSON con el estado actualizado del pedido (js/validar_pedido.js repinta la pantalla) */
    private function responderValidacion($idPedido, $codigo, $ok, $mensaje)
    {
        [$pedido, $items] = (new Pedido())->detalle($idPedido);
        $this->json($codigo, [
            'ok' => $ok,
            'mensaje' => $mensaje,
            'estado' => $pedido['estado'] ?? null,
            'todo_coincide' => Pedido::todoCoincide($items),
            'items' => array_map(fn($it) => [
                'id_producto' => (int)$it['id_producto'],
                'producto' => $it['Nombre'],
                'ubicacion' => $it['ubicacion'],
                'solicitada' => (int)$it['solicitada'],
                'retirada' => (int)$it['retirada'],
                'errores' => (int)$it['errores'],
                'coincide' => $it['coincide'],
            ], $items),
            'movimientos' => array_map(fn($m) => [
                'fecha' => fecha($m['fecha_hora'], 'd/m/Y H:i:s'),
                'tipo' => $m['Tipo_Registro'],
                'producto' => $m['Nombre'],
                'cantidad' => $m['cantidad'] === null ? null : (int)$m['cantidad'],
                'ubicacion' => $m['Ubicacion_producto'],
                'operario' => $m['operario'],
            ], (new Movimiento())->porPedido($idPedido)),
        ]);
    }
}
