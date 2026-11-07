<?php
/* VISTA - Resultado del formulario de contacto (en el idioma elegido en el sitio).
   $errores trae claves de los archivos de idioma (err_nombre, err_correo, ...). */
$t = require __DIR__ . '/idiomas/' . $idioma . '.php';
?>
<!DOCTYPE html>
<html lang="<?= e($t['html_lang']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($errores ? $t['res_titulo_error'] : $t['res_titulo_ok']) ?> | PICKFLOW</title>
<link rel="icon" href="img/logo3.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(recurso('css/sitio.css')) ?>">
</head>
<body class="pagina-resultado">
<main class="container">
    <div class="tarjeta-info resultado">
        <?php if ($errores) { ?>
            <div class="resultado-icono error"><i class="bi bi-x-lg"></i></div>
            <h1><?= e($t['res_error']) ?></h1>
            <ul class="text-start">
                <?php foreach ($errores as $error) { ?><li><?= e($t[$error] ?? $error) ?></li><?php } ?>
            </ul>
            <a href="<?= e(url()) ?>#contacto" class="btn btn-verde mt-3"><?= e($t['res_volver_form']) ?></a>
        <?php } else { ?>
            <div class="resultado-icono"><i class="bi bi-check-lg"></i></div>
            <h1><?= e($t['res_ok']) ?></h1>
            <p><?= e($t['res_gracias']) ?> <strong><?= e($nombre) ?></strong><?= e($t['res_recibido']) ?></p>
            <p><?= e($t['res_respuesta']) ?> <strong><?= e($correo) ?></strong></p>
            <a href="<?= e(url()) ?>" class="btn btn-verde mt-3"><?= e($t['res_volver_sitio']) ?></a>
        <?php } ?>
    </div>
</main>
</body>
</html>
