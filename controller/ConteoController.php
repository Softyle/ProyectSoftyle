<?php
/* =====================================================
   CONTROLADOR - CONTEO DE INVENTARIO
   El administrador elige una zona y quién la cuenta.
   El que cuenta (operario o administrador) escanea o
   busca cada producto y anota cuántos hay; cada número
   se guarda al momento (AJAX), así no se pierde nada si
   se corta la conexión. Al final el administrador revisa
   las diferencias y cierra el conteo: el stock se ajusta.

   index    -> conteos (el operario ve los que tiene asignados)
   nuevo    -> (admin) crear el conteo de una zona
   contar   -> pantalla de conteo
   guardar  -> AJAX POST: cantidad contada de un producto
   cerrar   -> (admin) aplica los ajustes
   cancelar -> (admin) descarta el conteo sin tocar el stock
   quitar   -> (admin) le saca el conteo al operario; lo sigue el administrador
===================================================== */
class ConteoController extends Controlador
{
    const SOLO_ADMIN = ['nuevo', 'cerrar', 'cancelar', 'quitar'];

    public function __construct()
    {
        $accion = $_GET['a'] ?? '';
        $this->requerirRol(in_array($accion, self::SOLO_ADMIN, true) ? 'admin' : ['admin', 'operario'], $accion === 'guardar');
    }

    public function index()
    {
        $esAdmin = $this->rol() === 'admin';
        $this->vista('compartido/conteos', [
            'conteos' => (new Conteo())->listar($esAdmin ? '' : $this->ciUsuario()),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    public function nuevo()
    {
        $form = ['id_zona' => (int)($_GET['zona'] ?? 0), 'ci_asignado' => '', 'ciego' => true, 'observaciones' => ''];
        $error = '';
        $operarios = (new Operario())->listarActivos();
        if ($this->esPost()) {
            $form = [
                'id_zona' => (int)($_POST['id_zona'] ?? 0),
                'ci_asignado' => (string)($_POST['ci_asignado'] ?? ''),
                'ciego' => !empty($_POST['ciego']),
                'observaciones' => mb_substr(trim((string)($_POST['observaciones'] ?? '')), 0, 255),
            ];
            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif (!(new Zona())->buscar($form['id_zona'])) {
                $error = 'Elegí la zona que se va a contar.';
            } elseif ($form['ci_asignado'] !== '' && !in_array($form['ci_asignado'], array_column($operarios, 'CI'), true)) {
                $error = 'Elegí un operario activo (o dejalo para contarlo vos).';
            } else {
                try {
                    $id = (new Conteo())->crear($form['id_zona'], $this->ciUsuario(), $form['ci_asignado'], $form['ciego'], $form['observaciones']);
                    $this->redirigir('conteo', 'contar', ['id' => $id]);
                } catch (RuntimeException $ex) {
                    $error = $ex->getMessage();
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW conteo nuevo: ' . $ex->getMessage());
                    $error = 'No se pudo crear el conteo. Intentá de nuevo.';
                }
            }
        }
        $this->vista('admin/conteo_nuevo', [
            'form' => $form,
            'error' => $error,
            'zonas' => (new Zona())->listarConProductos(),
            'operarios' => $operarios,
        ]);
    }

    public function contar()
    {
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new Conteo();
        $conteo = $this->conteoPermitido($modelo, $id);
        if (!$conteo) {
            $this->redirigir('conteo', 'index', ['error' => 'Ese conteo no existe o no está asignado a vos.']);
        }
        $esAdmin = $this->rol() === 'admin';
        $this->vista('compartido/conteo', [
            'conteo' => $conteo,
            'items' => $modelo->detalle($id),
            'esAdmin' => $esAdmin,
            // En el conteo ciego el operario no ve el stock del sistema; el administrador siempre lo ve
            'verSistema' => $esAdmin || !$conteo['ciego'],
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    /* AJAX POST: guarda la cantidad contada de un producto (vacío = borrar lo contado) */
    public function guardar()
    {
        if (!$this->esPost() || !$this->csrfValido()) {
            $this->json(403, ['ok' => false, 'mensaje' => 'La sesión venció. Actualizá la página.']);
        }
        $id = (int)($_POST['id_conteo'] ?? 0);
        $modelo = new Conteo();
        $conteo = $this->conteoPermitido($modelo, $id);
        if (!$conteo) {
            $this->json(404, ['ok' => false, 'mensaje' => 'Ese conteo no existe o no está asignado a vos.']);
        }
        if ($conteo['estado'] !== 'En curso') {
            $this->json(409, ['ok' => false, 'mensaje' => 'Este conteo ya está ' . mb_strtolower($conteo['estado']) . '. Actualizá la página.']);
        }
        $texto = trim((string)($_POST['cantidad'] ?? ''));
        $cantidad = $texto === '' ? null : filter_var($texto, FILTER_VALIDATE_INT);
        if ($cantidad === false || ($cantidad !== null && ($cantidad < 0 || $cantidad > 1000000))) {
            $this->json(422, ['ok' => false, 'mensaje' => 'Escribí un número entero, 0 o más.']);
        }
        if (!$modelo->guardar($id, (int)($_POST['id_producto'] ?? 0), $cantidad)) {
            $this->json(404, ['ok' => false, 'mensaje' => 'Ese producto no es de esta zona.']);
        }
        $this->json(200, ['ok' => true]);
    }

    public function cerrar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido()) {
            $this->redirigir('conteo', 'contar', ['id' => $id, 'error' => 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.']);
        }
        try {
            $r = (new Conteo())->cerrar($id, $this->ciUsuario());
        } catch (RuntimeException $ex) {
            $this->redirigir('conteo', 'contar', ['id' => $id, 'error' => $ex->getMessage()]);
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW conteo cerrar: ' . $ex->getMessage());
            $this->redirigir('conteo', 'contar', ['id' => $id, 'error' => 'No se pudo cerrar el conteo. No se modificó el stock.']);
        }
        $texto = $r['ajustados'] === 0
            ? 'Conteo #' . $id . ' cerrado: todo coincidía con el sistema, no hubo que ajustar nada.'
            : 'Conteo #' . $id . ' cerrado: se ajustó el stock de ' . $r['ajustados'] . ' producto' . ($r['ajustados'] === 1 ? '' : 's')
              . ' (' . $r['sobrantes'] . ' unidades de más y ' . $r['faltantes'] . ' de menos). Quedó anotado en el historial de cada uno.';
        $this->redirigir('conteo', 'contar', ['id' => $id, 'mensaje' => $texto]);
    }

    public function cancelar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido()) {
            $this->redirigir('conteo', 'index', ['error' => 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.']);
        }
        (new Conteo())->cancelar($id)
            ? $this->redirigir('conteo', 'index', ['mensaje' => 'Conteo #' . $id . ' cancelado. El stock no se modificó.'])
            : $this->redirigir('conteo', 'index', ['error' => 'Ese conteo ya no está en curso.']);
    }

    /* Le saca el conteo al operario asignado: lo sigue contando el administrador */
    public function quitar()
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$this->esPost() || !$this->csrfValido()) {
            $this->redirigir('conteo', 'index', ['error' => 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.']);
        }
        (new Conteo())->quitarAsignado($id)
            ? $this->redirigir('conteo', 'contar', ['id' => $id, 'mensaje' => 'Conteo #' . $id . ' quitado al operario: ahora lo seguís vos. Lo que ya se contó se conserva.'])
            : $this->redirigir('conteo', 'index', ['error' => 'Ese conteo ya no está en curso o no tiene un operario asignado.']);
    }

    /* El conteo, si el usuario puede verlo: el administrador todos, el operario solo los suyos */
    private function conteoPermitido(Conteo $modelo, $id)
    {
        $conteo = $modelo->buscar($id);
        if (!$conteo || ($this->rol() !== 'admin' && $conteo['ci_asignado'] !== $this->ciUsuario())) {
            return null;
        }
        return $conteo;
    }
}
