<?php
/* =====================================================
   CONTROLADOR - PERFIL DEL OPERARIO
   El operario ve y edita sus datos de contacto, cambia
   su contraseña y sube su foto de perfil ($_FILES).
===================================================== */
class PerfilController extends Controlador
{
    const CARPETA_FOTOS = 'uploads/perfiles';

    public function __construct()
    {
        $this->requerirRol('operario');
    }

    public function index()
    {
        $ci = $this->ciUsuario();
        $modelo = new Operario();
        $operario = $modelo->datosPerfil($ci);
        if (!$operario) {
            $this->redirigir('auth', 'salir');
        }
        $mensaje = '';
        $error = '';

        if ($this->esPost()) {
            $accion = $_POST['accion'] ?? '';
            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } elseif ($accion === 'datos') {
                [$mensaje, $error] = $this->guardarDatos($modelo, $ci);
            } elseif ($accion === 'password') {
                [$mensaje, $error] = $this->cambiarPassword($ci, $operario['hash']);
            } elseif ($accion === 'foto') {
                [$mensaje, $error] = $this->subirFoto($modelo, $ci, $operario['foto_perfil']);
            }
            $operario = $modelo->datosPerfil($ci);
        }

        $this->vista('operario/perfil', [
            'operario' => $operario,
            'resumen' => $modelo->resumenPedidos($ci),
            'foto' => fotoPerfil($operario['foto_perfil']),
            'mensaje' => $mensaje,
            'error' => $error,
        ]);
    }

    /* Datos de contacto (validación con expresiones regulares) */
    private function guardarDatos(Operario $modelo, $ci)
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            return ['', 'Ingresá un email válido.'];
        }
        if ($telefono !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $telefono)) {
            return ['', 'El teléfono solo puede tener números, espacios y los símbolos + ( ) - (entre 6 y 20 caracteres).'];
        }
        if (mb_strlen($direccion) > 150) {
            return ['', 'La dirección no puede superar los 150 caracteres.'];
        }
        try {
            $modelo->actualizarContacto($ci, $email, $telefono === '' ? null : $telefono, $direccion === '' ? null : $direccion);
            return ['Tus datos se actualizaron correctamente.', ''];
        } catch (mysqli_sql_exception $ex) {
            return ['', $ex->getCode() === 1062 ? 'Ese email ya está registrado por otro usuario.' : 'No se pudieron guardar los datos.'];
        }
    }

    /* Cambio de contraseña: se pide la actual para confirmar que es el titular */
    private function cambiarPassword($ci, $hashActual)
    {
        $actual = (string)($_POST['password_actual'] ?? '');
        $nueva = (string)($_POST['password_nueva'] ?? '');
        $repetir = (string)($_POST['password_repetir'] ?? '');
        if (!password_verify($actual, $hashActual) && !hash_equals((string)$hashActual, $actual)) {
            return ['', 'La contraseña actual no es correcta.'];
        }
        if (strlen($nueva) < 6) {
            return ['', 'La nueva contraseña debe tener al menos 6 caracteres.'];
        }
        if ($nueva !== $repetir) {
            return ['', 'Las contraseñas nuevas no coinciden.'];
        }
        (new Usuario())->cambiarPassword($ci, password_hash($nueva, PASSWORD_DEFAULT));
        return ['Tu contraseña se cambió correctamente.', ''];
    }

    /* Foto de perfil: JPG, PNG o WEBP de hasta 2 MB */
    private function subirFoto(Operario $modelo, $ci, $fotoAnterior)
    {
        $resultado = $this->guardarArchivoSubido($_FILES['foto'] ?? [], self::CARPETA_FOTOS,
            ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'], 2 * 1024 * 1024);
        if (!$resultado['ok']) {
            return ['', $resultado['error']];
        }
        $modelo->actualizarFoto($ci, $resultado['archivo']);
        $_SESSION['foto'] = $resultado['archivo']; // la barra superior muestra la foto nueva
        // Se borra la foto anterior para no acumular archivos
        $this->borrarArchivo(self::CARPETA_FOTOS, $fotoAnterior);
        return ['Tu foto de perfil se actualizó.', ''];
    }
}
