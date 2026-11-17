<?php
/* =====================================================
   CONTROLADOR - PROVEEDORES (administrador)
   Alta, modificación y baja de los proveedores. A cada
   producto se le asigna su proveedor habitual desde el
   formulario del producto.
===================================================== */
class ProveedorController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $this->vista('admin/proveedores', [
            'proveedores' => (new Proveedor())->listar(),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    public function agregar()
    {
        $form = ['nombre' => '', 'rut' => '', 'contacto' => '', 'telefono' => '', 'email' => '', 'dias_entrega' => 2, 'activo' => true];
        $error = '';
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $error = $this->validar($form);
            if (!$error) {
                (new Proveedor())->crear($form);
                $this->redirigir('proveedor', 'index', ['mensaje' => 'Proveedor agregado.']);
            }
        }
        $this->vista('admin/proveedor_form', ['titulo' => 'Agregar proveedor', 'form' => $form, 'id' => 0, 'error' => $error, 'productos' => []]);
    }

    public function editar()
    {
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new Proveedor();
        $actual = $modelo->buscar($id);
        if (!$actual) {
            $this->redirigir('proveedor', 'index', ['error' => 'Proveedor no encontrado.']);
        }
        $form = $actual;
        $error = '';
        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $error = $this->validar($form, $id);
            if (!$error) {
                $modelo->actualizar($id, $form);
                $this->redirigir('proveedor', 'index', ['mensaje' => 'Proveedor modificado.']);
            }
        }
        $this->vista('admin/proveedor_form', ['titulo' => 'Editar proveedor', 'form' => $form, 'id' => $id, 'error' => $error,
            'productos' => $modelo->productos($id)]);
    }

    /* Si ya tiene órdenes o recepciones no se borra (se perdería la historia): se desactiva */
    public function eliminar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido() || $id <= 0) {
            $this->redirigir('proveedor', 'index', ['error' => 'Proveedor inválido.']);
        }
        $modelo = new Proveedor();
        if ($modelo->usos($id) > 0) {
            $this->redirigir('proveedor', 'index', ['error' => 'No se puede eliminar: el proveedor tiene órdenes o recepciones registradas. Podés desactivarlo desde Editar.']);
        }
        $modelo->eliminar($id)
            ? $this->redirigir('proveedor', 'index', ['mensaje' => 'Proveedor eliminado. Sus productos quedaron sin proveedor asignado.'])
            : $this->redirigir('proveedor', 'index', ['error' => 'El proveedor no existe.']);
    }

    private function datosFormulario()
    {
        $texto = fn($campo, $largo) => mb_substr(trim((string)($_POST[$campo] ?? '')), 0, $largo);
        return [
            'nombre' => $texto('nombre', 100),
            'rut' => preg_replace('/\D/', '', $texto('rut', 20)),
            'contacto' => $texto('contacto', 100),
            'telefono' => $texto('telefono', 20),
            'email' => $texto('email', 100),
            'dias_entrega' => filter_var($_POST['dias_entrega'] ?? null, FILTER_VALIDATE_INT),
            'activo' => !empty($_POST['activo']),
        ];
    }

    private function validar(array $f, $id = 0)
    {
        if (!$this->csrfValido()) {
            return 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
        }
        if ($f['nombre'] === '') {
            return 'Escribí el nombre del proveedor.';
        }
        if ((new Proveedor())->existeNombre($f['nombre'], $id)) {
            return 'Ya hay un proveedor con ese nombre.';
        }
        if ($f['rut'] !== '' && strlen($f['rut']) !== 12) {
            return 'El RUT tiene 12 números (o dejalo vacío).';
        }
        if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo no es válido.';
        }
        if ($f['dias_entrega'] === false || $f['dias_entrega'] < 0 || $f['dias_entrega'] > 90) {
            return 'Los días de entrega van de 0 a 90.';
        }
        return '';
    }
}
