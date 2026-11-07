<?php
/* =====================================================
   CONTROLADOR - AUTENTICACIÓN
   Inicio de sesión con control de roles (Administrador
   y Operario) y cierre de sesión.
===================================================== */
class AuthController extends Controlador
{
    public function login()
    {
        // Si ya hay sesión, va directo a su panel
        if (isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
            $this->irAlPanel();
        }

        $error = '';
        $usuarioForm = '';
        if ($this->esPost()) {
            $usuarioForm = trim((string)($_POST['usuario'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $error = $this->autenticar($usuarioForm, $password);
        }
        $this->vista('publico/login', ['error' => $error, 'usuarioForm' => $usuarioForm]);
    }

    /* Valida usuario y contraseña. Si son correctos inicia la sesión y redirige;
       si no, devuelve el mensaje de error. */
    private function autenticar($usuario, $password)
    {
        if ($usuario === '' || $password === '') {
            return 'Completá usuario y contraseña.';
        }
        $modelo = new Usuario();
        $datos = $modelo->buscarPorUsuario($usuario);
        if (!$datos) {
            return 'Usuario o contraseña incorrectos.';
        }

        $correcta = password_verify($password, $datos['password']) || hash_equals((string)$datos['password'], $password);
        $rol = strtolower((string)$datos['Rol']);
        if ($rol === 'administrador') {
            $rol = 'admin';
        }
        if ($rol === 'operario' && $datos['Estado'] !== null && strtolower($datos['Estado']) !== 'activo') {
            return 'Este usuario está inactivo.';
        }
        if (!$correcta) {
            return 'Usuario o contraseña incorrectos.';
        }
        if (!in_array($rol, ['admin', 'operario'], true)) {
            return 'Este usuario no tiene acceso al sistema.';
        }

        // Si la contraseña estaba guardada en texto plano, se reemplaza por un hash seguro
        if (!password_verify($password, $datos['password'])) {
            $modelo->cambiarPassword($datos['CI'], password_hash($password, PASSWORD_DEFAULT));
        }

        session_regenerate_id(true); // nuevo id de sesión al ingresar
        $_SESSION['usuario_id'] = $datos['CI'];
        $_SESSION['usuario'] = $datos['usuario'];
        $_SESSION['rol'] = $rol;
        $this->irAlPanel();
    }

    public function salir()
    {
        $_SESSION = [];
        session_destroy();
        $this->redirigir('auth', 'login');
    }
}
