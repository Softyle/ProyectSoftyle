<?php
/* =====================================================
   LAYOUT DE LA APLICACIÓN - PIE
   Cierra la estructura y carga los scripts comunes
   (Bootstrap para el menú lateral y jQuery para AJAX).
   $scripts: (opcional) scripts extra de la vista, en orden.
===================================================== */
?>
        </main>
    </div>
</div>
<!-- Visor de fotos de productos: se abre al tocar una miniatura (js/fotos.js) -->
<div class="modal fade" id="visorFoto" tabindex="-1" aria-labelledby="visorFotoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="visorFotoTitulo">Foto del producto</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body visor-foto-cuerpo">
                <img id="visorFotoImagen" src="" alt="" referrerpolicy="no-referrer">
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="<?= e(recurso('js/pwa.js')) ?>"></script>
<script src="<?= e(recurso('js/menu.js')) ?>"></script>
<script src="<?= e(recurso('js/avisos.js')) ?>"></script>
<script src="<?= e(recurso('js/fotos.js')) ?>"></script>
<?php foreach ($scripts ?? [] as $script) { ?>
<script src="<?= e(recurso($script)) ?>"></script>
<?php } ?>
</body>
</html>
