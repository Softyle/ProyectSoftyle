<?php
/* VISTA - Página no encontrada (404) */
$inicio = $sesion['rol'] === 'admin' ? url('admin') : ($sesion['rol'] === 'operario' ? url('picking') : url());
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Página no encontrada | PICKFLOW</title>
<link rel="icon" href="img/logo3.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(recurso('css/app.css')) ?>">
</head>
<body class="pagina-error">
<main class="tarjeta text-center">
    <img src="<?= e(recurso('img/logo-blanco.png')) ?>" alt="PICKFLOW" class="error-logo">
    <h1>Página no encontrada</h1>
    <p class="text-secondary">La dirección no existe o fue movida.</p>
    <a href="<?= e($inicio) ?>" class="btn btn-primary"><i class="bi bi-house"></i> Volver al inicio</a>
</main>
</body>
</html>
