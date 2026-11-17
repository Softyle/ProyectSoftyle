<?php
/* =====================================================
   CONTROLADOR - SITIO PÚBLICO
   Página institucional y formulario de contacto, en
   español o inglés (textos en view/publico/idiomas).
===================================================== */
class InicioController extends Controlador
{
    const ARCHIVO_MENSAJES = 'uploads/mensajes/mensajes.txt'; // carpeta sin acceso desde el navegador

    // Casilla del equipo que recibe los mensajes de soporte (la misma de la nota de conformación)
    const CORREO_SOPORTE = 'softyletalleresdonbosco@gmail.com';

    const IDIOMAS = ['es', 'en'];

    /* Idioma del sitio público: ?lang=en o ?lang=es lo cambia y queda guardado en la sesión */
    private function idioma()
    {
        $pedido = $_GET['lang'] ?? '';
        if (in_array($pedido, self::IDIOMAS, true)) {
            $_SESSION['idioma'] = $pedido;
        }
        return $_SESSION['idioma'] ?? 'es';
    }

    public function index()
    {
        $this->vista('publico/inicio', [
            'idioma' => $this->idioma(),
            'galeria' => [
                ['nombre' => 'Login', 'imagen' => 'img/login.png'],
                ['nombre' => 'Órdenes', 'imagen' => 'img/ordenes.png'],
                ['nombre' => 'Picking', 'imagen' => 'img/picking.png'],
                ['nombre' => 'Checklist', 'imagen' => 'img/checklist.png'],
                ['nombre' => 'Recursos', 'imagen' => 'img/recursos.png'],
                ['nombre' => 'Maqueta física', 'imagen' => 'img/maqueta.png'],
            ],
            // Redes sociales del equipo de desarrollo: [nombre, ícono de Bootstrap Icons, enlace].
            'redes' => [
                ['GitHub', 'bi-github', 'https://github.com/Softyle/ProyectSoftyle'],
            ],
            // Institución educativa (se muestra con un mapa en la sección de contacto)
            'institucion' => [
                'nombre' => 'Talleres Don Bosco',
                'ciudad' => 'Montevideo, Uruguay',
                'mapa' => 'Talleres Don Bosco, Montevideo, Uruguay', // búsqueda para Google Maps
            ],
        ]);
    }

    /* Procesa el formulario de contacto (solo POST) */
    public function contacto()
    {
        if (!$this->esPost()) {
            header('Location: ' . url() . '#contacto');
            exit;
        }
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $correo = trim((string)($_POST['correo'] ?? ''));
        $mensaje = trim((string)($_POST['mensaje'] ?? ''));

        // Los errores son claves de los archivos de idioma (la vista los muestra en español o inglés)
        $errores = [];
        if (mb_strlen($nombre) < 2) {
            $errores[] = 'err_nombre';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'err_correo';
        }
        if (mb_strlen($mensaje) < 10) {
            $errores[] = 'err_mensaje';
        }

        if (!$errores) {
            $registro = "========================================\n"
                . 'Fecha: ' . date('Y-m-d H:i:s') . "\n"
                . 'Nombre: ' . $nombre . "\n"
                . 'Correo: ' . $correo . "\n"
                . "Mensaje:\n" . $mensaje . "\n"
                . "========================================\n\n";
            if (file_put_contents(RAIZ . '/' . self::ARCHIVO_MENSAJES, $registro, FILE_APPEND | LOCK_EX) === false) {
                $errores[] = 'err_guardar';
            } else {
                // El archivo queda como respaldo; además se avisa por correo al equipo
                $this->enviarCorreoSoporte($nombre, $correo, $mensaje);
            }
        }

        $this->vista('publico/contacto', ['idioma' => $this->idioma(), 'errores' => $errores, 'nombre' => $nombre, 'correo' => $correo]);
    }

    /* Manda el mensaje de soporte a la casilla del equipo con mail() del servidor.
       "Responder" contesta directo a quien escribió (Reply-To). Si el servidor no
       puede enviar correo (por ejemplo WAMP sin servidor de correo), queda en el
       registro de errores y el mensaje igual está guardado en el archivo. */
    private function enviarCorreoSoporte($nombre, $correo, $mensaje)
    {
        $nombreLimpio = trim(preg_replace('/[\r\n]+/', ' ', $nombre)); // evita que se inyecten cabeceras
        $dominio = preg_replace('/^www\./', '', preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
        $asunto = mb_encode_mimeheader('PICKFLOW soporte: mensaje de ' . $nombreLimpio, 'UTF-8');
        $cuerpo = "Nuevo mensaje desde el formulario de soporte de PICKFLOW.\n\n"
            . 'Fecha: ' . date('d/m/Y H:i') . "\n"
            . 'Nombre: ' . $nombreLimpio . "\n"
            . 'Correo: ' . $correo . "\n\n"
            . "Mensaje:\n" . $mensaje . "\n\n"
            . "Para responder, usá \"Responder\": la respuesta le llega a " . $correo . ".\n";
        $cabeceras = 'From: PICKFLOW <noreply@' . $dominio . ">\r\n"
            . 'Reply-To: ' . $correo . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n";

        // Algunos hostings no aceptan el remitente con -f: en ese caso se reintenta sin él
        $enviado = @mail(self::CORREO_SOPORTE, $asunto, $cuerpo, $cabeceras, '-f noreply@' . $dominio)
            || @mail(self::CORREO_SOPORTE, $asunto, $cuerpo, $cabeceras);
        if (!$enviado) {
            error_log('PICKFLOW soporte: no se pudo enviar el correo a ' . self::CORREO_SOPORTE . ' (queda guardado en ' . self::ARCHIVO_MENSAJES . ')');
        }
        return $enviado;
    }

    /* Página no encontrada (rutas o acciones que no existen) */
    public function noEncontrada()
    {
        $this->noEncontrado();
    }
}
