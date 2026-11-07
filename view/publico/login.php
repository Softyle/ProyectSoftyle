<?php
/* VISTA - Inicio de sesión */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión | PICKFLOW</title>
<link rel="icon" href="img/logo3.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(recurso('css/app.css')) ?>">
</head>
<body class="login-body">
<main class="login-contenedor">

    <div class="login-caja">
        <!-- Panel de marca (solo en pantallas grandes) -->
        <section class="login-marca" aria-hidden="true">
            <img src="<?= e(recurso('img/logo-blanco.png')) ?>" alt="" class="login-marca-logo">
            <h2>Del depósito caótico al <span>picking asistido.</span></h2>
            <ul>
                <li><i class="bi bi-signpost-split"></i> Rutas de picking optimizadas</li>
                <li><i class="bi bi-grid-3x3-gap"></i> Organización por zonas</li>
                <li><i class="bi bi-clipboard-check"></i> Seguimiento de pedidos</li>
            </ul>
        </section>

        <section class="login-card">
            <div class="login-logo">
                <img src="<?= e(recurso('img/logo-negro.png')) ?>" alt="PICKFLOW - Flujo inteligente, resultados precisos" class="login-logo-img">
                <p>Sistema de Picking y Logística</p>
            </div>

            <h1 class="login-titulo">Iniciar sesión</h1>

            <?php if ($error !== '') { ?>
                <div class="alert alert-danger login-alerta" role="alert"><i class="bi bi-exclamation-circle"></i> <?= e($error) ?></div>
            <?php } ?>

            <form method="POST" action="<?= e(url('auth', 'login')) ?>" id="loginForm">
                <div class="mb-3">
                    <label class="form-label" for="usuario"><i class="bi bi-person"></i> Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control form-control-lg" placeholder="Ingresá tu usuario" autocomplete="username" value="<?= e($usuarioForm) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password"><i class="bi bi-lock"></i> Contraseña</label>
                    <div class="password-contenedor">
                        <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="Ingresá tu contraseña" autocomplete="current-password" required>
                        <button type="button" class="password-ver" id="togglePassword" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100 btn-login" id="loginButton"><span>Ingresar al sistema</span> <i class="bi bi-arrow-right"></i></button>
            </form>

            <div class="login-info"><i class="bi bi-shield-check"></i> <span>Acceso exclusivo para personal autorizado</span></div>
            <a href="<?= e(url()) ?>" class="login-volver"><i class="bi bi-arrow-left"></i> Volver al sitio principal</a>
        </section>
    </div>
</main>
<script src="<?= e(recurso('js/login.js')) ?>"></script>
</body>
</html>
