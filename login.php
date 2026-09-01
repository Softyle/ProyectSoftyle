
<?php
session_start();

// Si ya hay una sesión iniciada, redirigir según el tipo de usuario
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'operario') {
        header('Location: operario/dashboard.php');
        exit;
    }
}

$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Usuarios de prueba
    if ($usuario === 'admin' && $password === '1234') {

        $_SESSION['usuario'] = 'Administrador';
        $_SESSION['rol'] = 'admin';

        header('Location: admin/dashboard.php');
        exit;
    } elseif ($usuario === 'operario' && $password === '1234') {

        $_SESSION['usuario'] = 'Operario';
        $_SESSION['rol'] = 'operario';

        header('Location: operario/dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Softyle</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Iconos Bootstrap -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS del sistema -->
    <link rel="stylesheet" href="CSS/sistema.css">
</head>

<body class="login-body">

    <main class="login-container">

        <section class="login-card">

            <!-- Logo -->
            <div class="login-logo">
                <div class="logo-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <h1>SOFTYLE</h1>
                <p>Sistema de Picking</p>
            </div>

            <div class="login-title">
                <h2>Iniciar sesión</h2>
                <p>Ingresá para acceder al sistema</p>
            </div>

            <!-- Mensaje de error -->
            <?php if ($error): ?>
                <div class="alert alert-danger login-alert" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" action="login.php" id="loginForm">

                <div class="form-group">
                    <label for="usuario">
                        <i class="bi bi-person"></i>
                        Usuario
                    </label>

                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        class="form-control sistema-input"
                        placeholder="Ingresá tu usuario"
                        autocomplete="username"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="bi bi-lock"></i>
                        Contraseña
                    </label>

                    <div class="password-container">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control sistema-input"
                            placeholder="Ingresá tu contraseña"
                            autocomplete="current-password"
                            required>

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginButton">
                    <span>Ingresar al sistema</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            <!-- Información -->
            <div class="login-info">
                <i class="bi bi-shield-check"></i>
                <span>Acceso exclusivo para personal autorizado</span>
            </div>

            <!-- Volver a la web -->
            <a href="index.php" class="back-home">
                <i class="bi bi-arrow-left"></i>
                Volver al sitio principal
            </a>

        </section>

    </main>

    <!-- JavaScript -->
    <script src="JS/sistema.js"></script>

</body>

</html>