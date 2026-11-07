<?php
/* =====================================================
   CONTROLADOR - SITIO PÚBLICO
   Página institucional y formulario de contacto, en
   español o inglés (textos en view/publico/idiomas).
===================================================== */
class InicioController extends Controlador
{
    const ARCHIVO_MENSAJES = 'uploads/mensajes/mensajes.txt'; // carpeta sin acceso desde el navegador

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
            }
        }

        $this->vista('publico/contacto', ['idioma' => $this->idioma(), 'errores' => $errores, 'nombre' => $nombre, 'correo' => $correo]);
    }

    /* Página no encontrada (rutas o acciones que no existen) */
    public function noEncontrada()
    {
        $this->noEncontrado();
    }
}
