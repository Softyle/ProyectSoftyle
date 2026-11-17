<?php
/* =====================================================
   CONTROLADOR - PICKING DEL OPERARIO
   Panel "Mis pedidos", detalle del pedido y hoja de ruta.
   Cada retiro se confirma por AJAX (jQuery) sin recargar:

   GET  escanear  -> qué producto es el código de barras leído
                     y dónde está guardado
   POST confirmar -> doble verificación: el código de barras
                     (producto correcto) y la estantería/nivel
                     (lugar correcto); valida la cantidad,
                     descuenta el stock y registra el retiro
   POST finalizar -> todos los ítems recogidos: el pedido
                     pasa a "Por validar" (lo revisa el admin)
   GET  estado    -> estado actual del pedido y del stock
                     (actualización silenciosa de la pantalla)
===================================================== */
class PickingController extends Controlador
{
    const ACCIONES_AJAX = ['confirmar', 'finalizar', 'estado', 'escanear'];

    public function __construct()
    {
        $this->requerirRol('operario', in_array($_GET['a'] ?? '', self::ACCIONES_AJAX, true));
    }

    /* Panel del operario: resumen y pedidos asignados */
    public function index()
    {
        $ci = $this->ciUsuario();
        $modelo = new Picking();
        $pendientes = $modelo->contarPorEstado($ci, 'Pendiente');
        $enPicking = $modelo->contarPorEstado($ci, 'En Picking');
        $this->vista('operario/dashboard', [
            'pendientes' => $pendientes,
            'enPicking' => $enPicking,
            'completados' => $modelo->contarPorEstado($ci, 'Completado'),
            'asignados' => $pendientes + $enPicking,
            'pedidos' => $modelo->pedidosAsignados($ci),
        ]);
    }

    /* Detalle del pedido antes de comenzar la preparación */
    public function pedido()
    {
        $idPedido = (int)($_GET['id'] ?? 0);
        $pedido = (new Picking())->detalle($idPedido, $this->ciUsuario());
        if (!$pedido) {
            $this->redirigir('picking');
        }
        $this->vista('operario/pedido', ['idPedido' => $idPedido, 'pedido' => $pedido]);
    }

    /* Hoja de ruta: productos ordenados con su ubicación exacta (refrigerada al final, RNE-8) */
    public function hojaRuta()
    {
        $idPedido = (int)($_GET['id'] ?? 0);
        $ci = $this->ciUsuario();
        $modelo = new Picking();
        // Al abrir la hoja de ruta, un pedido Pendiente pasa a En Picking
        $modelo->iniciar($idPedido, $ci);
        $items = $modelo->hojaDeRuta($idPedido, $ci);
        if (!$items) {
            $this->redirigir('picking');
        }
        if (!in_array($items[0]['estado'], ['En Picking', 'Pendiente'], true)) {
            // Un pedido "Por validar" o "Completado" ya no se puede recoger
            $this->redirigir('picking', 'pedido', ['id' => $idPedido]);
        }
        $this->vista('operario/picking', [
            'idPedido' => $idPedido,
            'items' => $items,
            'cliente' => $items[0]['cliente'],
            'documentos' => (new Documento())->porProductos(array_column($items, 'id_producto')),
            // Lote que hay que sacar primero (el que vence antes) y si hay mercadería vencida
            'lotes' => (new Lote())->aRetirar(array_column($items, 'id_producto')),
            'totalItems' => count($items),
            'recogidos' => count(array_filter($items, fn($x) => (bool)$x['recogido'])),
            'totalUnidades' => array_sum(array_map(fn($x) => (int)$x['Cantidad'], $items)),
        ]);
    }

    /* AJAX GET: estado del pedido, progreso y stock de cada ítem */
    public function estado()
    {
        $idPedido = (int)($_GET['id_pedido'] ?? 0);
        $modelo = new Picking();
        $estado = $this->estadoPropio($modelo, $idPedido);
        $this->json(200, ['ok' => true, 'estado' => $estado, 'progreso' => $modelo->progreso($idPedido), 'items' => $modelo->estadoItems($idPedido)]);
    }

    /* AJAX GET: el operario leyó un código de barras. Responde qué producto es, dónde se guarda
       y si es el del paso que está preparando ($_GET['id_producto'], opcional). Solo consulta: no
       registra nada; el error queda registrado recién si confirma el retiro con el producto equivocado. */
    public function escanear()
    {
        $idPedido = (int)($_GET['id_pedido'] ?? 0);
        $idEsperado = (int)($_GET['id_producto'] ?? 0);
        $codigo = CodigoBarras::limpiar($_GET['codigo'] ?? '');
        $modelo = new Picking();
        $this->estadoPropio($modelo, $idPedido);
        if ($codigo === '') {
            $this->json(422, ['ok' => false, 'mensaje' => 'No se leyó ningún código. Probá de nuevo.']);
        }

        $producto = (new Producto())->buscarPorCodigoBarras($codigo);
        if (!$producto) {
            // No está en el depósito: se busca igual en las bases públicas para decirle al operario qué tiene en la mano
            $externo = CodigoBarras::valido($codigo) ? CatalogoExterno::buscar($codigo) : null;
            $origen = PrefijoGS1::frase($codigo);
            $this->json(404, ['ok' => false, 'codigo' => $codigo,
                'mensaje' => 'El código ' . $codigo . ' no corresponde a ningún producto del depósito.'
                    . ($externo ? ' Por el código es ' . $externo['nombre'] . '.' : '')
                    . ($origen ? ' ' . $origen : '')
                    . ' Revisá que hayas leído la etiqueta correcta o avisale al administrador para que lo cargue.']);
        }

        $idProducto = (int)$producto['id_producto'];
        $ubicacion = $producto['zona'] . ', ' . $producto['pasillo'] . '-' . $producto['estante'] . '-' . $producto['posicion'];
        $item = $modelo->itemEnPedido($idPedido, $idProducto);
        $esEste = $idEsperado > 0 && $idEsperado === $idProducto;

        if ($esEste) {
            $mensaje = 'Producto correcto: es ' . $producto['Nombre'] . '. Se guarda en ' . $ubicacion . '.';
        } elseif ($item) {
            $mensaje = 'Esto es ' . $producto['Nombre'] . ' (' . $ubicacion . '). Está en el pedido'
                . ($idEsperado > 0 ? ', pero en otro paso' : '')
                . ($item['recogido'] ? ' y ya lo recogiste.' : '.');
        } else {
            $mensaje = 'Esto es ' . $producto['Nombre'] . ', que se guarda en ' . $ubicacion . '. No está en este pedido: devolvelo a su lugar.';
        }

        $this->json(200, [
            'ok' => true,
            'codigo' => $codigo,
            'id_producto' => $idProducto,
            'nombre' => $producto['Nombre'],
            'ubicacion' => $ubicacion,
            'en_pedido' => (bool)$item,
            'recogido' => $item ? (bool)$item['recogido'] : false,
            'es_este' => $esEste,
            'mensaje' => $mensaje,
        ]);
    }

    /* AJAX POST: confirmación del retiro de un producto */
    public function confirmar()
    {
        $this->exigirPostConToken();
        $ci = $this->ciUsuario();
        $idPedido = (int)($_POST['id_pedido'] ?? 0);
        $idProducto = (int)($_POST['id_producto'] ?? 0);
        $codigoLeido = CodigoBarras::limpiar($_POST['codigo'] ?? '');
        $ubicacionIngresada = trim((string)($_POST['ubicacion'] ?? ''));
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $modelo = new Picking();
        $movimientos = new Movimiento();
        $estado = $this->estadoPropio($modelo, $idPedido);

        if ($ubicacionIngresada === '' || $cantidad === false || $cantidad < 1) {
            $this->json(422, ['ok' => false, 'campo' => $ubicacionIngresada === '' ? 'ubicacion' : 'cantidad',
                'mensaje' => 'Ingresá el código de la estantería y la cantidad que retiraste.']);
        }
        if (!in_array($estado, ['Pendiente', 'En Picking'], true)) {
            $this->json(409, ['ok' => false, 'mensaje' => 'Este pedido está "' . $estado . '" y ya no admite retiros.']);
        }

        try {
            $modelo->iniciarTransaccion();
            $item = $modelo->itemParaRetiro($idPedido, $idProducto);
            if (!$item) {
                $modelo->deshacerTransaccion();
                $this->json(404, ['ok' => false, 'mensaje' => 'Ese producto no pertenece al pedido.']);
            }
            if ($item['recogido']) {
                $modelo->deshacerTransaccion();
                $this->json(409, ['ok' => false, 'mensaje' => 'Este producto ya fue recogido.', 'progreso' => $modelo->progreso($idPedido)]);
            }

            $ubicacionReal = $item['pasillo'] . '-' . $item['estante'] . '-' . $item['posicion'];
            // 1) ¿Es el producto correcto? (doble verificación: el código de barras del paquete)
            if ((string)$item['codigo_barras'] !== '') {
                if ($codigoLeido === '') {
                    $modelo->deshacerTransaccion();
                    $this->json(422, ['ok' => false, 'campo' => 'codigo',
                        'mensaje' => 'Primero escaneá el código de barras del producto para confirmar que es el correcto.']);
                }
                if ($codigoLeido !== (string)$item['codigo_barras']) {
                    $movimientos->registrarError($ci, $idProducto, $ubicacionReal, 'Error de producto', $idPedido, $cantidad);
                    $modelo->confirmarTransaccion();
                    $leido = (new Producto())->buscarPorCodigoBarras($codigoLeido);
                    $this->json(422, ['ok' => false, 'campo' => 'codigo',
                        'mensaje' => 'Producto equivocado: ' . ($leido ? 'leíste ' . $leido['Nombre'] : 'el código ' . $codigoLeido . ' no es de ningún producto')
                            . ', pero este paso es ' . $item['Nombre'] . '.']);
                }
            }
            // 2) ¿Está en la estantería y el nivel correctos?
            if (self::normalizarUbicacion($ubicacionIngresada) !== self::normalizarUbicacion($ubicacionReal)) {
                $movimientos->registrarError($ci, $idProducto, $ubicacionReal, 'Error de ubicación', $idPedido, $cantidad);
                $modelo->confirmarTransaccion();
                $this->json(422, ['ok' => false, 'campo' => 'ubicacion',
                    'mensaje' => 'Ubicación incorrecta: confirmaste "' . $ubicacionIngresada . '", pero el producto está en ' . $item['zona'] . ', ' . $ubicacionReal . '.']);
            }
            // 3) ¿Quiere retirar más de lo que hay en stock?
            if ($cantidad > (int)$item['stock_actual']) {
                $movimientos->registrarError($ci, $idProducto, $ubicacionReal, 'Error de cantidad', $idPedido, $cantidad);
                $modelo->confirmarTransaccion();
                $this->json(409, ['ok' => false, 'campo' => 'cantidad',
                    'mensaje' => 'No hay stock suficiente: querés retirar ' . $cantidad . ' y quedan ' . (int)$item['stock_actual'] . ' unidades. Avisale al administrador.']);
            }
            // 4) ¿Coincide con lo solicitado?
            if ($cantidad !== (int)$item['Cantidad']) {
                $movimientos->registrarError($ci, $idProducto, $ubicacionReal, 'Error de cantidad', $idPedido, $cantidad);
                $modelo->confirmarTransaccion();
                $this->json(422, ['ok' => false, 'campo' => 'cantidad',
                    'mensaje' => 'La cantidad no coincide: el pedido solicita ' . (int)$item['Cantidad'] . ' unidades y confirmaste ' . $cantidad . '.']);
            }

            // Todo correcto: se descuenta el stock (RNE-9 / RNE-11) y se registra el movimiento
            if (!$modelo->descontarStock($idProducto, $cantidad)) {
                $modelo->deshacerTransaccion();
                $this->json(409, ['ok' => false, 'campo' => 'cantidad', 'mensaje' => 'El stock cambió mientras confirmabas. Volvé a intentarlo.']);
            }
            // Sale primero el lote que vence antes (FEFO)
            (new Lote())->consumir($idProducto, $cantidad, (int)$item['stock_actual']);
            try {
                $movimientos->registrar($ci, $idProducto, $ubicacionReal, 'Retiro', $idPedido, $cantidad);
            } catch (mysqli_sql_exception $ex) {
                // La clave de Registra incluye la fecha en segundos: si hubo un error del mismo
                // producto en este mismo segundo, se espera un segundo y se reintenta una vez.
                if ($ex->getCode() !== 1062) {
                    throw $ex;
                }
                sleep(1);
                $movimientos->registrar($ci, $idProducto, $ubicacionReal, 'Retiro', $idPedido, $cantidad);
            }
            $modelo->marcarRecogido($idPedido, $idProducto);
            $modelo->confirmarTransaccion();
        } catch (Throwable $ex) {
            $modelo->deshacerTransaccion();
            error_log('PICKFLOW picking confirmar: ' . $ex->getMessage());
            $this->json(500, ['ok' => false, 'mensaje' => 'Error del servidor. No se modificó el stock; intentá de nuevo.']);
        }

        $nuevoStock = (int)$item['stock_actual'] - $cantidad;
        $this->json(200, [
            'ok' => true,
            'mensaje' => 'Retiro confirmado: ' . $cantidad . ' u. de ' . $item['Nombre'] . '. Stock actualizado a ' . $nuevoStock . ' unidades.',
            'id_producto' => $idProducto,
            'stock_actual' => $nuevoStock,
            'stock_bajo' => $nuevoStock <= (int)$item['stock_minimo'],
            'progreso' => $modelo->progreso($idPedido),
        ]);
    }

    /* AJAX POST: terminar el picking, el pedido pasa a "Por validar" */
    public function finalizar()
    {
        $this->exigirPostConToken();
        $idPedido = (int)($_POST['id_pedido'] ?? 0);
        $modelo = new Picking();
        $this->estadoPropio($modelo, $idPedido);
        $progreso = $modelo->progreso($idPedido);
        if ($progreso['total'] === 0 || $progreso['recogidos'] < $progreso['total']) {
            $this->json(422, ['ok' => false, 'mensaje' => 'Todavía faltan productos por recoger (' . $progreso['recogidos'] . ' de ' . $progreso['total'] . ').', 'progreso' => $progreso]);
        }
        if (!$modelo->finalizar($idPedido, $this->ciUsuario())) {
            $this->json(409, ['ok' => false, 'mensaje' => 'El pedido ya no está en picking.']);
        }
        $this->json(200, ['ok' => true, 'estado' => 'Por validar',
            'mensaje' => 'Picking terminado. El administrador va a validar el pedido antes de marcarlo como Completado.']);
    }

    /* Las acciones que modifican datos exigen POST y el token CSRF */
    private function exigirPostConToken()
    {
        if (!$this->esPost()) {
            $this->json(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
        }
        if (!$this->csrfValido()) {
            $this->json(403, ['ok' => false, 'mensaje' => 'La sesión de la página venció. Recargá la hoja de ruta.']);
        }
    }

    /* Estado del pedido; corta con 404 si no existe o no es de este operario */
    private function estadoPropio(Picking $modelo, $idPedido)
    {
        $estado = $modelo->estadoPedido($idPedido, $this->ciUsuario());
        if ($estado === null) {
            $this->json(404, ['ok' => false, 'mensaje' => 'El pedido no existe o no está asignado a vos.']);
        }
        return $estado;
    }

    /* Normaliza un código de ubicación para compararlo: "a01 - e01 - p01" -> "A01E01P01" */
    private static function normalizarUbicacion($texto)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$texto));
    }
}
