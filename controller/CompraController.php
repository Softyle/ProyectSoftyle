<?php
/* =====================================================
   CONTROLADOR - REPOSICIÓN Y ÓRDENES DE COMPRA (administrador)
   reposicion -> qué productos hay que pedir y cuánto;
                 con los elegidos se arma una orden por proveedor
   index      -> órdenes de compra y su estado
   ver        -> detalle para imprimir o mandar por correo
   cancelar   -> anula una orden que todavía no llegó
   La mercadería que llega se recibe en Recepción.
===================================================== */
class CompraController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $estado = (string)($_GET['estado'] ?? '');
        if (!in_array($estado, ['', 'Abiertas', 'Pendiente', 'Parcial', 'Recibida', 'Cancelada'], true)) {
            $estado = '';
        }
        $this->vista('admin/compras', [
            'ordenes' => (new OrdenCompra())->listar($estado),
            'estado' => $estado,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    /* GET: productos para reponer. POST: crea las órdenes con lo elegido (una por proveedor). */
    public function reposicion()
    {
        $modelo = new OrdenCompra();
        $error = '';
        if ($this->esPost()) {
            $error = $this->crearOrdenes($modelo);
        }
        $this->vista('admin/reposicion', [
            'productos' => $modelo->reposicion(),
            'proveedores' => (new Proveedor())->activos(),
            'error' => $error,
            'diasCobertura' => OrdenCompra::DIAS_COBERTURA,
        ]);
    }

    /* Agrupa los productos elegidos por proveedor y crea una orden para cada uno */
    private function crearOrdenes(OrdenCompra $modelo)
    {
        if (!$this->csrfValido()) {
            return 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
        }
        $elegidos = array_map('intval', (array)($_POST['elegido'] ?? []));
        $cantidades = (array)($_POST['cantidad'] ?? []);
        $proveedoresProducto = (array)($_POST['proveedor'] ?? []);
        $activos = array_column((new Proveedor())->activos(), 'id_proveedor');
        $porProveedor = [];
        foreach ($elegidos as $idProducto) {
            $cantidad = filter_var($cantidades[$idProducto] ?? null, FILTER_VALIDATE_INT);
            $idProveedor = (int)($proveedoresProducto[$idProducto] ?? 0);
            if ($cantidad === false || $cantidad < 1 || $cantidad > 100000) {
                return 'Revisá las cantidades: tienen que ser números enteros mayores que cero.';
            }
            if (!in_array($idProveedor, array_map('intval', $activos), true)) {
                return 'Hay productos elegidos sin proveedor. Elegí a quién se le pide cada uno.';
            }
            $porProveedor[$idProveedor][$idProducto] = $cantidad;
        }
        if (!$porProveedor) {
            return 'Marcá al menos un producto para pedir.';
        }
        $fechaEsperada = '';
        $ids = [];
        try {
            foreach ($porProveedor as $idProveedor => $lineas) {
                $proveedor = (new Proveedor())->buscar($idProveedor);
                $fechaEsperada = date('Y-m-d', strtotime('+' . (int)$proveedor['dias_entrega'] . ' days'));
                $ids[] = $modelo->crear($idProveedor, $lineas, $this->ciUsuario(), $fechaEsperada, '');
                // Si eligió otro proveedor para el producto y no tenía ninguno, queda como su proveedor habitual
                foreach (array_keys($lineas) as $idProducto) {
                    (new Producto())->asignarProveedorSiNoTiene($idProducto, $idProveedor);
                }
            }
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW orden de compra: ' . $ex->getMessage());
            return 'No se pudo crear la orden de compra. Intentá de nuevo.';
        }
        $texto = count($ids) === 1 ? 'Se creó la orden de compra #' . $ids[0] . '.' : 'Se crearon ' . count($ids) . ' órdenes de compra (#' . implode(', #', $ids) . '), una por proveedor.';
        $this->redirigir('compra', 'index', ['mensaje' => $texto . ' Cuando llegue la mercadería, recibila desde Recepción de mercadería.']);
    }

    public function ver()
    {
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new OrdenCompra();
        $orden = $modelo->buscar($id);
        if (!$orden) {
            $this->redirigir('compra', 'index', ['error' => 'Orden no encontrada.']);
        }
        $this->vista('admin/compra_ver', [
            'orden' => $orden,
            'lineas' => $modelo->detalle($id),
            'recepciones' => $modelo->recepciones($id),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
        ]);
    }

    public function cancelar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido() || $id <= 0) {
            $this->redirigir('compra', 'index', ['error' => 'Orden inválida.']);
        }
        (new OrdenCompra())->cancelar($id)
            ? $this->redirigir('compra', 'index', ['mensaje' => 'La orden #' . $id . ' quedó cancelada.'])
            : $this->redirigir('compra', 'index', ['error' => 'La orden #' . $id . ' ya no se puede cancelar (llegó completa o ya estaba cancelada).']);
    }
}
