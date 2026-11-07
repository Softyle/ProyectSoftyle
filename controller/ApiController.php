<?php
/* =====================================================
   CONTROLADOR - API SIMULADA DE GESTIÓN LOGÍSTICA
   Simula la recepción de una orden de salida desde un
   cliente externo. Recibe JSON y responde JSON.

   POST index.php?c=api&a=recibirPedido
   Content-Type: application/json
     { "nombre_cliente": "Lucas Fernandez", "sku": "SKU-1",
       "cantidad": 3, "zona_destino": "Zona C" }
     El SKU puede escribirse "SKU-1" o con el código de la lista de productos "P0001".
     La zona de destino es la zona donde está guardado el producto (su ubicación
     asignada): si se omite se completa sola y si se envía otra zona se rechaza (422).

   Respuestas:
     201 aceptado          -> se creó el pedido (Pendiente) y se devuelve su id_picking
     409 rechazado         -> no hay stock disponible suficiente
     202 pendiente_ingreso -> el cliente o el SKU no están registrados todavía
     4xx/5xx error         -> datos inválidos, sin sesión o error del servidor
   Toda recepción (aceptada o no) queda en la tabla API_Recepciones.

   Es una simulación: solo la puede invocar un administrador con sesión
   iniciada (desde el simulador) y con el token CSRF.
===================================================== */
class ApiController extends Controlador
{
    /* Pantalla del simulador: hace de "cliente externo" */
    public function simulador()
    {
        $this->requerirRol('admin');
        $recepciones = new Recepcion();
        $existeApi = $recepciones->existeTabla();
        $productos = (new Producto())->listarNombres();
        $this->vista('admin/simulador', [
            'existeApi' => $existeApi,
            'clientes' => (new Cliente())->listar(),
            'productos' => $productos,
            'disponibles' => (new Producto())->stockDisponible(array_column($productos, 'id_producto')),
            'historial' => $existeApi ? $recepciones->ultimas(20) : [],
        ]);
    }

    public function recibirPedido()
    {
        if (!$this->esPost()) {
            header('Allow: POST');
            $this->json(405, ['estado' => 'error', 'mensaje' => 'Solo se permite POST.']);
        }
        if (!isset($_SESSION['usuario_id']) || $this->rol() !== 'admin') {
            $this->json(401, ['estado' => 'error', 'mensaje' => 'Iniciá sesión como administrador.']);
        }
        if (!$this->csrfValido()) {
            $this->json(403, ['estado' => 'error', 'mensaje' => 'Token del simulador inválido. Recargá la página.']);
        }

        // El cuerpo de la petición es JSON (no un formulario)
        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->json(400, ['estado' => 'error', 'mensaje' => 'Enviá un objeto JSON válido.']);
        }
        $cliente = trim((string)($entrada['nombre_cliente'] ?? ''));
        $sku = strtoupper(trim((string)($entrada['sku'] ?? '')));
        $zona = trim((string)($entrada['zona_destino'] ?? ''));
        $cantidad = filter_var($entrada['cantidad'] ?? null, FILTER_VALIDATE_INT);

        // SKU-12 o P0012 -> producto 12
        $idProducto = 0;
        if (preg_match('/^SKU-([1-9][0-9]*)$/', $sku, $m) || preg_match('/^P0*([1-9][0-9]*)$/', $sku, $m)) {
            $idProducto = (int)$m[1];
        }
        if ($cliente === '' || mb_strlen($cliente) > 100 || mb_strlen($zona) > 100 || $idProducto < 1
            || $cantidad === false || $cantidad < 1 || $cantidad > 1000000) {
            $this->json(422, ['estado' => 'error', 'mensaje' => 'Completá cliente, SKU (por ejemplo SKU-1 o P0001) y una cantidad positiva.']);
        }

        $pedidos = new Pedido();
        try {
            $pedidos->iniciarTransaccion();

            // El producto queda bloqueado: dos pedidos simultáneos no pueden reservar el mismo stock
            $producto = (new Producto())->stockDisponible([$idProducto], true)[$idProducto] ?? null;
            $clientes = (new Cliente())->buscarPorNombre($cliente);
            $ci = count($clientes) === 1 ? $clientes[0]['CI'] : null;

            // El producto ya tiene zona y ubicación asignadas (pasillo, estantería y nivel):
            // la orden solo puede ir a esa zona. Si no se indica, se completa con ella.
            $asignada = $producto ? (new Producto())->buscarConZona($idProducto) : null;
            if ($asignada) {
                if ($zona === '') {
                    $zona = $asignada['zona'];
                } elseif (mb_strtolower($zona) !== mb_strtolower($asignada['zona'])) {
                    $pedidos->deshacerTransaccion();
                    $this->json(422, ['estado' => 'error', 'mensaje' => $asignada['Nombre'] . ' está asignado a ' . $asignada['zona'] . ' ('
                        . $asignada['pasillo'] . '-' . $asignada['estante'] . '-' . $asignada['posicion'] . '): no se puede enviar a "' . $zona . '".']);
                }
                $zona = $asignada['zona'];
            } else {
                // SKU no registrado: la zona tiene que ser una zona activa del depósito
                $zonaValida = $zona === '' ? null : (new Zona())->buscarActivaPorNombre($zona);
                if (!$zonaValida) {
                    $pedidos->deshacerTransaccion();
                    $this->json(422, ['estado' => 'error', 'mensaje' => 'Indicá una zona de destino activa del depósito.']);
                }
                $zona = $zonaValida['nombre'];
            }

            // Por defecto: pendiente de ingreso (cliente o producto no registrados)
            $estado = 'pendiente_ingreso';
            $mensaje = ($producto ? '' : 'El SKU no existe. ') . ($ci ? '' : 'El cliente no está registrado. ') . 'Requiere revisión administrativa.';
            $idPicking = null;
            $http = 202;

            if ($producto && $ci !== null) {
                if ($cantidad > $producto['disponible']) {
                    $estado = 'rechazado';
                    $mensaje = 'Stock disponible insuficiente: hay ' . $producto['disponible'] . ' unidad(es) de ' . $producto['nombre'] . '.';
                    $http = 409;
                } else {
                    $idPicking = $pedidos->insertarConProductos($ci, null, [$idProducto => $cantidad]);
                    $estado = 'aceptado';
                    $mensaje = 'Orden creada. Picking pendiente de asignación.';
                    $http = 201;
                }
            }

            $skuNormalizado = 'SKU-' . $idProducto;
            $recepcion = (new Recepcion())->registrar($cliente, $skuNormalizado, $cantidad, $zona, $estado, $mensaje, $idPicking);
            $pedidos->confirmarTransaccion();

            $this->json($http, [
                'estado' => $estado,
                'mensaje' => $mensaje,
                'id_recepcion' => $recepcion,
                'id_picking' => $idPicking,
                'nombre_cliente' => $cliente,
                'sku' => $skuNormalizado,
                'cantidad' => $cantidad,
                'zona_destino' => $zona,
            ]);
        } catch (Throwable $ex) {
            $pedidos->deshacerTransaccion();
            error_log('PICKFLOW API: ' . $ex->getMessage());
            $this->json(500, ['estado' => 'error', 'mensaje' => 'No se pudo registrar la recepción. Verificá que se importó softyleDEF.sql.']);
        }
    }
}
