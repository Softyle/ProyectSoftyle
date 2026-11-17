<?php
/* =====================================================
   CONTROLADOR - AVISOS (campanita de la barra superior)
   listar -> AJAX (GET): los avisos del usuario en JSON.
   js/avisos.js lo pide al abrir cada pantalla y cada
   minuto mientras la pestaña está a la vista.
===================================================== */
class AvisoController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol(['admin', 'operario'], true);
    }

    public function listar()
    {
        $this->json(200, ['ok' => true, 'avisos' => (new Aviso())->para($this->rol(), $this->ciUsuario())]);
    }
}
