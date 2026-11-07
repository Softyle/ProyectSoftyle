<?php
/* =====================================================
   CONTROLADOR - DESCARGA DE DOCUMENTACIÓN TÉCNICA
   Entrega un archivo de uploads/documentos/ solo a usuarios
   con sesión iniciada (administrador u operario). La carpeta
   no se puede abrir directamente desde el navegador.
===================================================== */
class DocumentoController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol(['admin', 'operario']);
    }

    public function ver()
    {
        $doc = (new Documento())->buscar((int)($_GET['id'] ?? 0));
        $ruta = $doc ? RAIZ . '/' . Documento::CARPETA . '/' . basename($doc['archivo']) : '';
        if (!$doc || !is_file($ruta)) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }
        // Se muestra en el navegador (PDF o imagen) con el nombre original
        $nombre = str_replace(['"', "\r", "\n"], '', $doc['nombre_original']);
        header('Content-Type: ' . $doc['mime']);
        header('Content-Length: ' . filesize($ruta));
        header('Content-Disposition: inline; filename="' . $nombre . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0');
        readfile($ruta);
        exit;
    }
}
