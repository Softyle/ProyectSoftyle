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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="<?= e(recurso('js/menu.js')) ?>"></script>
<?php foreach ($scripts ?? [] as $script) { ?>
<script src="<?= e(recurso($script)) ?>"></script>
<?php } ?>
</body>
</html>
