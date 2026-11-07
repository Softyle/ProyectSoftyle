<?php
/* =====================================================
   CONTROLADOR - PRODUCTOS (administrador)
   Catálogo con asignación obligatoria de ubicación y stock
   mínimo, y documentación técnica de cada producto
   (hojas de seguridad y manuales de manipulación).
===================================================== */
class ProductoController extends Controlador
{
    const FORMATOS_DOCUMENTO = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    const MAX_BYTES_DOCUMENTO = 2 * 1024 * 1024; // igual al upload_max_filesize de WAMP

    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $this->vista('admin/productos', [
            'productos' => (new Producto())->listar(),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    public function agregar()
    {
        $error = '';
        $form = ['nombre' => '', 'descripcion' => '', 'stock' => 0, 'stock_minimo' => 0, 'id_categoria' => 0, 'id_ubicacion' => 0];
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $error = $this->validar($form);
            if (!$error) {
                try {
                    (new Producto())->crear($form['nombre'], $form['descripcion'], $form['stock'], $form['stock_minimo'], $form['id_categoria'], $form['id_ubicacion']);
                    $this->redirigir('producto', 'index', ['mensaje' => 'Producto agregado correctamente.']);
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW producto agregar: ' . $ex->getMessage());
                    $error = 'No se pudo guardar. Verificá la categoría y la ubicación elegidas.';
                }
            }
        }
        $this->vista('admin/producto_form', [
            'titulo' => 'Agregar producto',
            'producto' => $form + ['estado_producto' => 'Disponible'],
            'esNuevo' => true,
            'error' => $error,
            'categorias' => (new Producto())->categorias(),
            'ubicaciones' => (new Ubicacion())->disponibles(),
        ]);
    }

    public function editar()
    {
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new Producto();
        $actual = $modelo->buscar($id);
        if (!$actual) {
            $this->redirigir('producto', 'index', ['error' => 'Producto no encontrado.']);
        }
        $form = [
            'nombre' => $actual['Nombre'], 'descripcion' => $actual['descripcion'] ?? '',
            'stock' => (int)$actual['stock_actual'], 'stock_minimo' => (int)$actual['stock_minimo'],
            'estado_producto' => $actual['estado_producto'],
            'id_categoria' => (int)$actual['id_categoria'], 'id_ubicacion' => (int)$actual['id_ubicacion'],
        ];
        $error = '';
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $form['estado_producto'] = trim((string)($_POST['estado_producto'] ?? 'Disponible'));
            $error = $this->validar($form);
            if (!$error && $form['estado_producto'] === '') {
                $error = 'Completá correctamente todos los campos (el stock y el mínimo no pueden ser negativos).';
            }
            if (!$error) {
                try {
                    $modelo->actualizar($id, $form['nombre'], $form['descripcion'], $form['stock'], $form['stock_minimo'],
                        $form['estado_producto'], $form['id_categoria'], $form['id_ubicacion']);
                    $this->redirigir('producto', 'index', ['mensaje' => 'Producto modificado correctamente.']);
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW producto editar: ' . $ex->getMessage());
                    $error = 'No se pudo guardar. Verificá la categoría y la ubicación elegidas.';
                }
            }
        }
        $this->vista('admin/producto_form', [
            'titulo' => 'Editar producto',
            'id' => $id,
            'producto' => $form,
            'esNuevo' => false,
            'error' => $error,
            'categorias' => $modelo->categorias(),
            // Ubicaciones disponibles, más la que el producto ya tiene asignada
            'ubicaciones' => (new Ubicacion())->disponibles((int)$actual['id_ubicacion']),
        ]);
    }

    /* Baja de un producto (POST con token). Si ya figura en pedidos o movimientos
       no se borra: se perdería el historial (y las claves foráneas lo impiden). */
    public function eliminar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido() || $id <= 0) {
            $this->redirigir('producto', 'index', ['error' => 'Producto inválido.']);
        }
        $modelo = new Producto();
        if ($modelo->usos($id) > 0) {
            $this->redirigir('producto', 'index', ['error' => 'No se puede eliminar: el producto figura en pedidos o movimientos de stock. Podés cambiar su estado a "No disponible" desde Editar.']);
        }
        $archivos = (new Documento())->archivosDeProducto($id);
        try {
            $borrado = $modelo->eliminar($id);
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW producto eliminar: ' . $ex->getMessage());
            $this->redirigir('producto', 'index', ['error' => 'No se pudo eliminar el producto.']);
        }
        // Los archivos se borran del disco recién cuando la base confirmó la baja
        foreach ($archivos as $archivo) {
            $this->borrarArchivo(Documento::CARPETA, $archivo);
        }
        $borrado
            ? $this->redirigir('producto', 'index', ['mensaje' => 'Producto eliminado correctamente.'])
            : $this->redirigir('producto', 'index', ['error' => 'El producto no existe.']);
    }

    /* Documentación técnica: subida ($_FILES) y baja de archivos PDF o imágenes */
    public function documentos()
    {
        $idProducto = (int)($_GET['id'] ?? 0);
        $producto = (new Producto())->buscarConZona($idProducto);
        if (!$producto) {
            $this->redirigir('producto', 'index', ['error' => 'Producto no encontrado.']);
        }
        $documentos = new Documento();
        $error = '';

        if ($this->esPost()) {
            $accion = $_POST['accion'] ?? '';
            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif ($accion === 'subir') {
                $error = $this->subirDocumento($idProducto, $documentos);
            } elseif ($accion === 'eliminar') {
                $doc = $documentos->buscar((int)($_POST['id_documento'] ?? 0));
                if ($doc && (int)$doc['id_producto'] === $idProducto) {
                    $documentos->eliminar((int)$doc['id_documento']);
                    $this->borrarArchivo(Documento::CARPETA, $doc['archivo']);
                    $this->redirigir('producto', 'documentos', ['id' => $idProducto, 'mensaje' => 'Documento eliminado.']);
                }
                $error = 'El documento no existe.';
            }
        }

        $this->vista('admin/producto_documentos', [
            'producto' => $producto,
            'documentos' => $documentos->listarPorProducto($idProducto),
            'tiposDocumento' => Documento::TIPOS,
            'maxBytes' => self::MAX_BYTES_DOCUMENTO,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => $error,
        ]);
    }

    /* Valida y guarda el archivo; si sale bien redirige, si no devuelve el error */
    private function subirDocumento($idProducto, Documento $documentos)
    {
        $tipo = $_POST['tipo_documento'] ?? '';
        if (!in_array($tipo, Documento::TIPOS, true)) {
            return 'Elegí el tipo de documento.';
        }
        $subida = $this->guardarArchivoSubido($_FILES['archivo'] ?? [], Documento::CARPETA, self::FORMATOS_DOCUMENTO, self::MAX_BYTES_DOCUMENTO);
        if (!$subida['ok']) {
            return $subida['error'];
        }
        // Nombre original limpio (solo para mostrarlo y al descargar)
        $original = mb_substr(preg_replace('/[^\w .()-]/u', '_', basename($_FILES['archivo']['name'])), 0, 200);
        try {
            $documentos->crear($idProducto, $tipo, $original, $subida['archivo'], $subida['mime'], $subida['tamano']);
        } catch (mysqli_sql_exception $ex) {
            $this->borrarArchivo(Documento::CARPETA, $subida['archivo']);
            error_log('PICKFLOW documentos: ' . $ex->getMessage());
            return 'No se pudo registrar el documento.';
        }
        $this->redirigir('producto', 'documentos', ['id' => $idProducto, 'mensaje' => 'Documento subido correctamente.']);
    }

    /* Datos del formulario de producto */
    private function datosFormulario()
    {
        return [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'stock' => (int)($_POST['stock'] ?? 0),
            'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
            'id_categoria' => (int)($_POST['id_categoria'] ?? 0),
            'id_ubicacion' => (int)($_POST['id_ubicacion'] ?? 0),
        ];
    }

    private function validar(array $f)
    {
        if (!$this->csrfValido()) {
            return 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
        }
        if ($f['nombre'] === '' || $f['stock'] < 0 || $f['stock_minimo'] < 0 || $f['id_categoria'] <= 0 || $f['id_ubicacion'] <= 0) {
            return 'Completá correctamente todos los campos (el stock y el mínimo no pueden ser negativos).';
        }
        return '';
    }
}
