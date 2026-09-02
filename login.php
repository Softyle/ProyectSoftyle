<?php

session_start();
require_once 'conexion.php';


/* ==========================================
   SI YA HAY UNA SESIÓN INICIADA
========================================== */

if (isset($_SESSION['usuario'])) {

    if (
        isset($_SESSION['rol']) &&
        $_SESSION['rol'] === 'admin'
    ) {

        header('Location: admin/dashboard.php');
        exit;
    }

    if (
        isset($_SESSION['rol']) &&
        $_SESSION['rol'] === 'operario'
    ) {

        header('Location: operario/dashboard.php');
        exit;
    }
}


/* ==========================================
   VARIABLES
========================================== */

$error = '';


/* ==========================================
   PROCESAR FORMULARIO
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $usuario = isset($_POST['usuario'])
        ? trim($_POST['usuario'])
        : '';


    $password = isset($_POST['password'])
        ? $_POST['password']
        : '';


    /* CAMPOS VACÍOS */

    if ($usuario === '' || $password === '') {

        $error = 'Completá usuario y contraseña.';
    } else {


        /* ==================================
           BUSCAR USUARIO EN MYSQL
        ================================== */

        $sql = "
            SELECT
                id,
                nombre,
                usuario,
                password,
                rol,
                estado
            FROM usuarios
            WHERE usuario = ?
            LIMIT 1
        ";


        $stmt = $conexion->prepare($sql);


        /* COMPROBAR CONSULTA */

        if ($stmt === false) {

            $error =
                'Error de MySQL: ' .
                $conexion->error;
        } else {


            /*
             * IMPORTANTE:
             * "s" significa que usuario es STRING
             */

            $stmt->bind_param(
                "s",
                $usuario
            );


            /* EJECUTAR */

            if (!$stmt->execute()) {

                $error =
                    'Error al ejecutar la consulta: ' .
                    $stmt->error;
            } else {


                /* RESULTADO */

                $resultado = $stmt->get_result();


                if ($resultado === false) {

                    $error =
                        'No se pudo obtener el resultado de MySQL.';
                } elseif ($resultado->num_rows === 1) {


                    $datos =
                        $resultado->fetch_assoc();


                    /* ==================================
                       USUARIO INACTIVO
                    ================================== */

                    if ((int)$datos['estado'] !== 1) {

                        $error =
                            'Este usuario está inactivo.';
                    } else {


                        /* ==================================
                           VERIFICAR CONTRASEÑA
                           
                           Usamos crypt() porque tu PHP
                           no tiene password_verify()
                        ================================== */

                        $passwordCorrecta =
                            crypt(
                                $password,
                                $datos['password']
                            ) === $datos['password'];


                        if ($passwordCorrecta) {


                            /* ==================================
                               CREAR SESIÓN
                            ================================== */

                            $_SESSION['usuario_id'] =
                                (int)$datos['id'];


                            $_SESSION['usuario'] =
                                $datos['nombre'];


                            $_SESSION['rol'] =
                                $datos['rol'];


                            /* ==================================
                               REDIRECCIÓN ADMIN
                            ================================== */

                            if ($datos['rol'] === 'admin') {

                                header(
                                    'Location: admin/dashboard.php'
                                );

                                exit;
                            }


                            /* ==================================
                               REDIRECCIÓN OPERARIO
                            ================================== */

                            if ($datos['rol'] === 'operario') {

                                header(
                                    'Location: operario/dashboard.php'
                                );

                                exit;
                            }


                            $error =
                                'El rol del usuario no es válido.';
                        } else {

                            $error =
                                'Usuario o contraseña incorrectos.';
                        }
                    }
                } else {

                    $error =
                        'Usuario o contraseña incorrectos.';
                }
            }


            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Iniciar sesión | Softyle
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- ICONOS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <!-- CSS DEL SISTEMA -->

    <link
        rel="stylesheet"
        href="CSS/sistema.css">

</head>


<body class="login-body">


    <main class="login-container">


        <section class="login-card">


            <!-- LOGO -->

            <div class="login-logo">

                <div class="logo-icon">

                    <i class="bi bi-box-seam"></i>

                </div>


                <h1>
                    SOFTYLE
                </h1>


                <p>
                    Sistema de Picking
                </p>

            </div>


            <!-- TÍTULO -->

            <div class="login-title">

                <h2>
                    Iniciar sesión
                </h2>


                <p>
                    Ingresá para acceder al sistema
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== '') { ?>

                <div
                    class="alert alert-danger login-alert"
                    role="alert">

                    <i class="bi bi-exclamation-circle"></i>

                    <?php
                    echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </div>

            <?php } ?>


            <!-- FORMULARIO -->

            <form
                method="POST"
                action="login.php"
                id="loginForm">


                <!-- USUARIO -->

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


                <!-- CONTRASEÑA -->

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


                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="btn-login"
                    id="loginButton">

                    <span>
                        Ingresar al sistema
                    </span>


                    <i class="bi bi-arrow-right"></i>

                </button>


            </form>


            <!-- INFORMACIÓN -->

            <div class="login-info">

                <i class="bi bi-shield-check"></i>

                <span>
                    Acceso exclusivo para personal autorizado
                </span>

            </div>


            <!-- VOLVER -->

            <a
                href="index.php"
                class="back-home">

                <i class="bi bi-arrow-left"></i>

                Volver al sitio principal

            </a>


        </section>


    </main>


    <!-- JAVASCRIPT -->

    <script src="JS/sistema.js"></script>


</body>

</html>