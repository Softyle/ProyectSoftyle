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
    // Foto del producto (el navegador la achica antes de subirla: js/producto_form.js)
    const CARPETA_FOTOS = 'uploads/productos';
    const FORMATOS_FOTO = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    const MAX_BYTES_FOTO = 2 * 1024 * 1024;

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
        $form = ['nombre' => '', 'descripcion' => '', 'stock' => 0, 'stock_minimo' => 0, 'id_categoria' => 0, 'id_ubicacion' => 0, 'codigo_barras' => '', 'foto' => '', 'id_proveedor' => 0];
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $error = $this->validar($form);
            $foto = '';
            if (!$error) {
                $foto = $this->resolverFoto('', $error);
            }
            if (!$error) {
                try {
                    $mensaje = 'Producto agregado correctamente.';
                    // No quedaba ninguna ubicación libre: se crea una zona nueva y el producto va a su primer lugar
                    if ($form['ubicacion_nueva']) {
                        $zona = $this->crearZonaParaCategoria($form['id_categoria']);
                        $form['id_ubicacion'] = $zona['id_ubicacion'];
                        $mensaje .= ' Como no quedaban ubicaciones libres, se creó la ' . $zona['nombre'] . ' (pasillo ' . $zona['pasillo']
                            . ', ' . $zona['ubicaciones'] . ' ubicaciones) y el producto quedó en ' . $zona['pasillo'] . '-E01-N01.';
                    }
                    $idNuevo = (new Producto())->crear($form['nombre'], $form['descripcion'], $form['stock'], $form['stock_minimo'], $form['id_categoria'], $form['id_ubicacion'], $form['codigo_barras'], $foto);
                    $this->guardarExtras($idNuevo, $form, 0, true);
                    $this->redirigir('producto', 'index', ['mensaje' => $mensaje]);
                } catch (RuntimeException $ex) {
                    $this->borrarFotoLocal($foto);
                    $error = 'No se pudo crear una zona nueva: ' . $ex->getMessage();
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW producto agregar: ' . $ex->getMessage());
                    $this->borrarFotoLocal($foto);
                    $error = 'No se pudo guardar. Verificá la categoría y la ubicación elegidas.';
                }
            }
            // Si hay que corregir algo, el enlace escrito no se pierde (una foto subida sí: hay que elegirla de nuevo)
            $form['foto'] = $form['foto_url'];
        }
        $this->vista('admin/producto_form', [
            'titulo' => 'Agregar producto',
            'producto' => $form + ['estado_producto' => 'Disponible'],
            'esNuevo' => true,
            'error' => $error,
            'categorias' => (new Producto())->categorias(),
            'ubicaciones' => (new Ubicacion())->disponibles(),
            'zonasCategoria' => (new Producto())->zonasPorCategoria(),
            'proximaZona' => (new Ubicacion())->proximaZona(),
            'proveedores' => (new Proveedor())->activos(),
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
            'codigo_barras' => (string)($actual['codigo_barras'] ?? ''),
            'foto' => (string)($actual['foto'] ?? ''),
            'foto_oculta' => !empty($actual['foto_oculta']),
            'id_proveedor' => (int)($actual['id_proveedor'] ?? 0),
        ];
        $error = '';
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $form['estado_producto'] = trim((string)($_POST['estado_producto'] ?? 'Disponible'));
            $error = $this->validar($form, $id);
            if (!$error && $form['estado_producto'] === '') {
                $error = 'Completá correctamente todos los campos (el stock y el mínimo no pueden ser negativos).';
            }
            $fotoAnterior = (string)($actual['foto'] ?? '');
            $foto = $fotoAnterior;
            if (!$error) {
                $foto = $this->resolverFoto($fotoAnterior, $error);
            }
            if (!$error) {
                try {
                    $modelo->actualizar($id, $form['nombre'], $form['descripcion'], $form['stock'], $form['stock_minimo'],
                        $form['estado_producto'], $form['id_categoria'], $form['id_ubicacion'], $form['codigo_barras'], $foto,
                        $foto !== '' && $form['foto_oculta']);
                    $this->guardarExtras($id, $form, (int)$actual['stock_actual'], false);
                    // Una foto subida que se reemplazó o se borró ya no se usa: se borra del disco
                    if ($foto !== $fotoAnterior) {
                        $this->borrarFotoLocal($fotoAnterior);
                    }
                    $this->redirigir('producto', 'index', ['mensaje' => 'Producto modificado correctamente.']);
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW producto editar: ' . $ex->getMessage());
                    if ($foto !== $fotoAnterior) {
                        $this->borrarFotoLocal($foto);
                    }
                    $error = 'No se pudo guardar. Verificá la categoría y la ubicación elegidas.';
                }
            }
            $form['foto'] = $form['foto_url'] !== '' ? $form['foto_url'] : $fotoAnterior;
        }
        $this->vista('admin/producto_form', [
            'titulo' => 'Editar producto',
            'id' => $id,
            'producto' => $form,
            'esNuevo' => false,
            'error' => $error,
            'categorias' => $modelo->categorias(),
            // Ubicaciones libres, más la que el producto ya tiene asignada
            'ubicaciones' => (new Ubicacion())->disponibles((int)$actual['id_ubicacion']),
            'zonasCategoria' => $modelo->zonasPorCategoria(),
            'proximaZona' => null,
            'proveedores' => (new Proveedor())->activos((int)($actual['id_proveedor'] ?? 0)),
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
            $this->redirigir('producto', 'index', ['error' => 'No se puede eliminar: el producto figura en pedidos, recepciones, órdenes de compra o conteos. Podés cambiar su estado a "No disponible" desde Editar.']);
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
            // "nueva": no quedaba ninguna ubicación libre y se crea una zona al guardar
            'ubicacion_nueva' => ($_POST['id_ubicacion'] ?? '') === 'nueva',
            // Solo los dígitos: se acepta con espacios o guiones, como viene impreso en el paquete
            'codigo_barras' => CodigoBarras::limpiar($_POST['codigo_barras'] ?? ''),
            // Enlace a la foto que publica una tienda (o la que encontró Open Food Facts)
            'foto_url' => trim((string)($_POST['foto_url'] ?? '')),
            // Ocultar: la foto se guarda pero no se muestra hasta destildar la casilla
            'foto_oculta' => !empty($_POST['ocultar_foto']),
            'id_proveedor' => (int)($_POST['id_proveedor'] ?? 0),
        ];
    }

    /* Foto que queda guardada en el producto:
       - si se marcó "Borrar la foto": ninguna (para solo dejar de mostrarla está "Ocultar la foto")
       - si se subió o se sacó una foto: el archivo nuevo en uploads/productos/
       - si se escribió un enlace https: ese enlace
       - si no: la que ya tenía
       Si algo falla, deja el motivo en $error. */
    private function resolverFoto($actual, &$error)
    {
        if (!empty($_POST['borrar_foto'])) {
            return '';
        }
        $archivo = $_FILES['foto_archivo'] ?? [];
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $subida = $this->guardarArchivoSubido($archivo, self::CARPETA_FOTOS, self::FORMATOS_FOTO, self::MAX_BYTES_FOTO);
            if (!$subida['ok']) {
                $error = 'Foto: ' . $subida['error'];
                return $actual;
            }
            return $subida['archivo'];
        }
        $enlace = trim((string)($_POST['foto_url'] ?? ''));
        if ($enlace !== '' && $enlace !== $actual) {
            if (strlen($enlace) > 255 || fotoProducto($enlace) === null || !filter_var($enlace, FILTER_VALIDATE_URL)) {
                $error = 'El enlace de la foto tiene que empezar con https:// (copiá la dirección de la imagen).';
                return $actual;
            }
            return $enlace;
        }
        return $actual;
    }

    /* Después de guardar el producto: su proveedor habitual y, si el stock cambió a mano,
       el registro en el historial (los lotes se ajustan si quedaron con más de lo que hay) */
    private function guardarExtras($id, array $form, $stockAnterior, $esNuevo)
    {
        (new Producto())->asignarProveedor($id, $form['id_proveedor']);
        $diferencia = (int)$form['stock'] - (int)$stockAnterior;
        if ($diferencia !== 0) {
            (new HistorialStock())->registrar($id, $esNuevo ? 'Stock inicial' : 'Ajuste manual', $diferencia, (int)$form['stock'], $this->ciUsuario(),
                $esNuevo ? 'Stock con el que se dio de alta el producto' : 'Cambio de stock desde Editar producto');
            if ($diferencia < 0) {
                (new Lote())->ajustarAStock($id);
            }
        }
    }

    /* Historial de stock del producto: recepciones, retiros, ajustes y bajas, y sus lotes */
    public function historial()
    {
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new Producto();
        $producto = $modelo->buscarConZona($id);
        if (!$producto) {
            $this->redirigir('producto', 'index', ['error' => 'Producto no encontrado.']);
        }
        $this->vista('admin/producto_historial', [
            'producto' => $producto + $modelo->buscar($id),
            'movimientos' => (new HistorialStock())->porProducto($id),
            'lotes' => (new Lote())->porProducto($id),
        ]);
    }

    /* Zona nueva para cuando el depósito se llenó: del mismo tipo y depósito que la zona donde ya está
       esa categoría (por ejemplo, refrigerada para un lácteo). Si la categoría es nueva, una zona seca. */
    private function crearZonaParaCategoria($idCategoria)
    {
        $referencia = (new Producto())->zonasPorCategoria()[(int)$idCategoria][0] ?? ['tipo' => 'Seca', 'id_deposito' => 0];
        return (new Ubicacion())->crearZonaNueva($referencia['tipo'], $referencia['id_deposito']);
    }

    /* Borra del disco una foto subida (los enlaces a fotos de tiendas no se tocan) */
    private function borrarFotoLocal($foto)
    {
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', (string)$foto)) {
            $this->borrarArchivo(self::CARPETA_FOTOS, $foto);
        }
    }

    /* AJAX del formulario: qué se sabe de un código de barras recién leído.
       GET codigo (y id si se está editando ese producto). Responde:
       - existe:      ya lo tiene un producto de PICKFLOW (con su ubicación)
       - encontrado:  no está en PICKFLOW, pero Open Food Facts conoce el producto
       - sin_datos:   código válido, pero no hay datos: se completa a mano */
    public function consultarCodigo()
    {
        $codigo = CodigoBarras::limpiar($_GET['codigo'] ?? '');
        $idActual = (int)($_GET['id'] ?? 0);
        if (!CodigoBarras::valido($codigo)) {
            $this->json(422, ['ok' => false, 'estado' => 'invalido',
                'mensaje' => 'El código ' . $codigo . ' no es válido: tiene que tener 8, 12 o 13 dígitos y el último es el verificador.']);
        }

        $existente = (new Producto())->buscarPorCodigoBarras($codigo);
        if ($existente && (int)$existente['id_producto'] !== $idActual) {
            $this->json(200, ['ok' => true, 'estado' => 'existe', 'codigo' => $codigo,
                'mensaje' => 'Este código ya es de ' . $existente['Nombre'] . ' (' . $existente['zona'] . ', '
                    . $existente['pasillo'] . '-' . $existente['estante'] . '-' . $existente['posicion'] . ').',
                'editar' => url('producto', 'editar', ['id' => (int)$existente['id_producto']])]);
        }
        if ($existente) {
            $this->json(200, ['ok' => true, 'estado' => 'mismo', 'codigo' => $codigo, 'mensaje' => 'Es el código que ya tiene este producto.']);
        }

        // Bases públicas gratuitas (alimentos, perfumería, mascotas, productos en general y UPCitemdb)
        $datos = CatalogoExterno::buscar($codigo);
        $origen = PrefijoGS1::frase($codigo);   // el país de registro sale del propio código, siempre
        if ($datos) {
            $this->json(200, ['ok' => true, 'estado' => 'encontrado', 'codigo' => $codigo, 'producto' => $datos, 'origen' => $origen,
                'mensaje' => 'Encontramos este producto en ' . $datos['fuente'] . ': ' . $datos['nombre'] . '. '
                    . ($origen ? $origen . ' ' : '') . 'Revisá los datos antes de guardar.']);
        }
        $this->json(200, ['ok' => true, 'estado' => 'sin_datos', 'codigo' => $codigo, 'origen' => $origen,
            'mensaje' => 'Código válido y libre. ' . ($origen ? $origen . ' ' : '')
                . ($datos === false
                    ? 'No pudimos consultar las bases de productos en este momento: completá el nombre a mano.'
                    : 'Ninguna base pública gratuita tiene este producto (Open Food Facts, Open Beauty Facts, Open Pet Food Facts, Open Products Facts y UPCitemdb): completá el nombre a mano.')]);
    }

    /* Hoja de etiquetas para imprimir: el código de barras de cada producto y dónde se guarda */
    public function etiquetas()
    {
        $this->vista('admin/producto_etiquetas', ['productos' => (new Producto())->etiquetas()]);
    }

    private function validar(array $f, $idProducto = 0)
    {
        if (!$this->csrfValido()) {
            return 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
        }
        $ubicacionNueva = !empty($f['ubicacion_nueva']);
        if ($f['nombre'] === '' || $f['stock'] < 0 || $f['stock_minimo'] < 0 || $f['id_categoria'] <= 0 || ($f['id_ubicacion'] <= 0 && !$ubicacionNueva)) {
            return 'Completá correctamente todos los campos (el stock y el mínimo no pueden ser negativos).';
        }
        // Un lugar, un producto: la ubicación tiene que estar libre (la propia, al editar, cuenta como libre)
        $ubicaciones = new Ubicacion();
        if ($ubicacionNueva) {
            if ($idProducto > 0 || $ubicaciones->disponibles()) {
                return 'Todavía hay ubicaciones libres: elegí una de la lista.';
            }
        } elseif (!$ubicaciones->estaLibre($f['id_ubicacion'], $idProducto)) {
            return 'Esa ubicación ya la ocupa otro producto (o no está habilitada). Elegí una libre de la lista.';
        }
        if ($f['id_proveedor'] && !(new Proveedor())->buscar($f['id_proveedor'])) {
            return 'El proveedor elegido no existe.';
        }
        // El código de barras es opcional: vacío, se usa el código interno del producto
        if ($f['codigo_barras'] !== '') {
            if (!CodigoBarras::valido($f['codigo_barras'])) {
                return 'El código de barras no es válido: tiene que tener 8, 12 o 13 dígitos y el último es el verificador. Revisá que lo hayas copiado entero.';
            }
            if ((new Producto())->existeCodigoBarras($f['codigo_barras'], $idProducto)) {
                return 'Ese código de barras ya lo tiene otro producto.';
            }
        }
        return '';
    }
}
