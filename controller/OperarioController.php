<?php
/* =====================================================
   CONTROLADOR - OPERARIOS (administrador)
   Alta, baja, modificación y perfil de los operarios.
===================================================== */
class OperarioController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $this->vista('admin/operarios', [
            'operarios' => (new Operario())->listar(),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    public function agregar()
    {
        $error = '';
        $form = ['ci' => '', 'usuario' => '', 'email' => '', 'hora_ingreso' => '08:00'];
        if ($this->esPost()) {
            $form = [
                'ci' => trim((string)($_POST['ci'] ?? '')),
                'usuario' => trim((string)($_POST['usuario'] ?? '')),
                'email' => trim((string)($_POST['email'] ?? '')),
                'hora_ingreso' => (string)($_POST['hora_ingreso'] ?? '08:00'),
            ];
            $password = (string)($_POST['password'] ?? '');

            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif ($form['ci'] === '' || $form['usuario'] === '' || $form['email'] === '' || $password === '') {
                $error = 'Completá todos los campos.';
            } elseif (!preg_match('/^[0-9]{7,8}$/', $form['ci'])) {
                $error = 'La CI debe tener 7 u 8 números, sin puntos ni guion.';
            } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingresá un email válido.';
            } elseif (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $form['hora_ingreso'])) {
                $error = 'La hora de ingreso no es válida.';
            } else {
                try {
                    (new Operario())->crear($form['ci'], $form['usuario'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['hora_ingreso']);
                    $this->redirigir('operario', 'index', ['mensaje' => 'Operario agregado correctamente.']);
                } catch (Throwable $ex) {
                    // 1062 = dato repetido (CI, usuario o email ya registrados)
                    $error = $ex->getCode() === 1062 ? 'Ya existe una persona con esa CI, usuario o email.' : 'No se pudo crear el operario.';
                }
            }
        }
        $this->vista('admin/operario_agregar', ['error' => $error, 'form' => $form]);
    }

    public function editar()
    {
        $ci = trim((string)($_GET['id'] ?? ''));
        $modelo = new Operario();
        $operario = $ci === '' ? null : $modelo->buscar($ci);
        if (!$operario) {
            $this->redirigir('operario', 'index', ['error' => 'Operario no encontrado.']);
        }
        $error = '';
        if ($this->esPost()) {
            $usuario = trim((string)($_POST['usuario'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $estado = in_array($_POST['estado'] ?? '', ['Activo', 'Inactivo'], true) ? $_POST['estado'] : 'Activo';

            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif ($usuario === '') {
                $error = 'El usuario es obligatorio.';
            } else {
                try {
                    $modelo->actualizar($ci, $usuario, $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null, $estado);
                    $this->redirigir('operario', 'index', ['mensaje' => 'Operario modificado correctamente.']);
                } catch (mysqli_sql_exception $ex) {
                    $error = $ex->getCode() === 1062 ? 'Ese usuario ya existe.' : 'No se pudo modificar el operario.';
                }
            }
            $operario['usuario'] = $usuario;
            $operario['estado'] = $estado;
        }
        $this->vista('admin/operario_editar', ['operario' => $operario, 'error' => $error]);
    }

    /* Baja (POST con token). Si tiene pedidos o movimientos se sugiere marcarlo Inactivo. */
    public function eliminar()
    {
        $ci = trim((string)($_POST['id'] ?? ''));
        if (!$this->esPost() || !$this->csrfValido() || $ci === '') {
            $this->redirigir('operario', 'index', ['error' => 'Operario inválido.']);
        }
        $modelo = new Operario();
        if ($modelo->cantidadRegistros($ci) > 0) {
            $this->redirigir('operario', 'index', ['error' => 'No se puede eliminar: el operario tiene pedidos asociados. Podés marcarlo Inactivo.']);
        }
        try {
            $modelo->eliminar($ci);
            $this->redirigir('operario', 'index', ['mensaje' => 'Operario eliminado correctamente.']);
        } catch (Throwable $ex) {
            $this->redirigir('operario', 'index', ['error' => 'No se pudo eliminar el operario.']);
        }
    }

    /* Perfil del operario: pedidos completados y jornadas de trabajo */
    public function perfil()
    {
        $ci = trim((string)($_GET['id'] ?? ''));
        $modelo = new Operario();
        $operario = $ci === '' ? null : $modelo->buscar($ci);
        if (!$operario) {
            $this->redirigir('operario', 'index', ['error' => 'Operario no encontrado.']);
        }
        $pedidos = $modelo->pedidosCompletados($ci);
        $jornadas = $modelo->jornadas($ci);

        // Tiempo trabajado = suma de las jornadas (de "HH:MM:SS" a segundos)
        $totalSegundos = 0;
        foreach ($jornadas as $j) {
            if ($j['tiempo']) {
                [$h, $m, $s] = array_map('intval', explode(':', $j['tiempo']));
                $totalSegundos += $h * 3600 + $m * 60 + $s;
            }
        }
        $this->vista('admin/operario_perfil', [
            'operario' => $operario,
            'pedidos' => $pedidos,
            'jornadas' => $jornadas,
            'tiempo' => floor($totalSegundos / 3600) . 'h ' . str_pad((string)floor(($totalSegundos % 3600) / 60), 2, '0', STR_PAD_LEFT) . 'm',
            'promedio' => ($pedidos > 0 ? round(($totalSegundos / $pedidos) / 60) : 0) . 'm',
        ]);
    }
}
