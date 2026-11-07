<?php
/* =====================================================
   CONTROLADOR BASE
   Todos los controladores heredan de esta clase.
   Reúne lo que comparten: mostrar vistas, redirigir,
   responder JSON (AJAX), controlar la sesión y el rol,
   el token CSRF y la subida de archivos.

   Los métodos públicos de cada controlador hijo son sus
   "acciones" (las páginas que se pueden pedir por URL);
   los de esta clase son protegidos y no se pueden pedir.
===================================================== */
abstract class Controlador
{
    /* Muestra una vista de /view. Cada clave de $datos pasa a ser una variable de la vista. */
    protected function vista($vista, array $datos = [])
    {
        $datos['csrf'] = $this->tokenCsrf();
        // Usuario logueado (lo muestra el layout)
        $datos['sesion'] = ['usuario' => (string)($_SESSION['usuario'] ?? ''), 'rol' => $this->rol(), 'foto' => $this->fotoUsuario()];
        extract($datos);
        require RAIZ . '/view/' . $vista . '.php';
    }

    /* Redirige a otra página del sistema y termina */
    protected function redirigir($controlador, $accion = 'index', array $parametros = [])
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }

    /* Respuesta JSON con el código HTTP indicado (llamadas AJAX y API) */
    protected function json($codigoHttp, array $datos)
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ---------- Sesión y roles ---------- */

    protected function ciUsuario()
    {
        return (string)($_SESSION['usuario_id'] ?? '');
    }

    protected function rol()
    {
        return $_SESSION['rol'] ?? '';
    }

    /* Ruta de la foto de perfil del operario logueado, para la barra superior (o null).
       Se busca una sola vez y queda guardada en la sesión; al subir una foto nueva se actualiza. */
    protected function fotoUsuario()
    {
        if ($this->rol() !== 'operario') {
            return null;
        }
        if (!array_key_exists('foto', $_SESSION)) {
            $_SESSION['foto'] = (new Operario())->foto($this->ciUsuario());
        }
        return fotoPerfil($_SESSION['foto']);
    }

    /* Corta la acción si el usuario no inició sesión con alguno de los roles pedidos.
       Con $json = true responde JSON (llamadas AJAX) en vez de ir al login. */
    protected function requerirRol($roles, $json = false)
    {
        if (!isset($_SESSION['usuario_id']) || !in_array($this->rol(), (array)$roles, true)) {
            if ($json) {
                $this->json(401, ['ok' => false, 'mensaje' => 'La sesión venció. Volvé a iniciar sesión.']);
            }
            $this->redirigir('auth', 'login');
        }
    }

    /* Panel principal de cada rol */
    protected function irAlPanel()
    {
        $this->redirigir($this->rol() === 'admin' ? 'admin' : 'picking');
    }

    protected function esPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /* ---------- Token CSRF ----------
       Se envía en los formularios y en las llamadas AJAX: evita que otro sitio
       haga acciones en nombre del usuario logueado. */
    protected function tokenCsrf()
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /* Comprueba el token recibido por POST o en la cabecera X-CSRF-Token */
    protected function csrfValido()
    {
        $recibido = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$recibido);
    }

    /* ---------- Subida de archivos ($_FILES) ----------
       $permitidos: [mime => extensión]. Devuelve ['ok' => true, 'archivo', 'mime', 'tamano']
       o ['ok' => false, 'error' => mensaje]. El tipo se detecta por el contenido, no por la extensión. */
    protected function guardarArchivoSubido(array $archivo, $carpetaDestino, array $permitidos, $maxBytes)
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Seleccioná un archivo.'];
        }
        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE || $archivo['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'El archivo supera el tamaño máximo de ' . round($maxBytes / 1048576, 1) . ' MB.'];
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            return ['ok' => false, 'error' => 'No se pudo subir el archivo. Intentá de nuevo.'];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset($permitidos[$mime])) {
            return ['ok' => false, 'error' => 'Formato no permitido. Formatos aceptados: ' . strtoupper(implode(', ', array_unique($permitidos))) . '.'];
        }
        $carpeta = RAIZ . '/' . $carpetaDestino;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
            return ['ok' => false, 'error' => 'No existe la carpeta de destino en el servidor.'];
        }
        // Nombre aleatorio: evita sobrescribir archivos y que se ejecute código subido
        $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombre)) {
            return ['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor.'];
        }
        return ['ok' => true, 'archivo' => $nombre, 'mime' => $mime, 'tamano' => (int)$archivo['size']];
    }

    /* Borra un archivo subido (si existe) */
    protected function borrarArchivo($carpeta, $archivo)
    {
        $ruta = RAIZ . '/' . $carpeta . '/' . basename((string)$archivo);
        if ($archivo && is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /* Página no encontrada */
    protected function noEncontrado()
    {
        http_response_code(404);
        $this->vista('compartido/no_encontrado');
        exit;
    }
}
