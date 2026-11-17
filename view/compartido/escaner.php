<?php
/* VISTA PARCIAL - Ventana del lector de códigos de barras (js/escaner.js).
   Tres formas de leer: cámara en vivo (solo con HTTPS o localhost),
   una foto del código, o el número escrito a mano. */
?>
<div class="modal fade" id="modalEscaner" tabindex="-1" aria-labelledby="tituloEscaner" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="tituloEscaner"><i class="bi bi-upc-scan" aria-hidden="true"></i> Leer código de barras</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="bloqueCamara" class="d-none">
                    <div id="lectorCamara" class="lector-camara"></div>
                    <p class="small text-secondary mt-2 mb-3">Apuntá la cámara a las barras, de forma que entren enteras en el recuadro.</p>
                </div>
                <p id="sinCamara" class="alert alert-info small d-none">La cámara en vivo solo funciona si la página se abre por HTTPS. Sacale una foto al código o escribí el número.</p>

                <label class="btn btn-primary w-100 mb-3" for="fotoCodigo"><i class="bi bi-camera" aria-hidden="true"></i> Sacar foto del código</label>
                <input type="file" id="fotoCodigo" accept="image/*" capture="environment" class="d-none">
                <div id="lectorArchivo" class="d-none"></div>

                <label class="form-label small fw-semibold" for="codigoManual">O escribí el número que está debajo de las barras</label>
                <div class="input-group">
                    <input id="codigoManual" class="form-control" inputmode="numeric" autocomplete="off" placeholder="7790001234567">
                    <button type="button" class="btn btn-outline-primary" id="btnCodigoManual">Usar</button>
                </div>
                <div id="estadoEscaner" class="small mt-2" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>
