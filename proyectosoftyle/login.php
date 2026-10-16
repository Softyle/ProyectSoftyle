<?php
session_start();
require_once 'conexion.php';
if (isset($_SESSION['usuario'], $_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'admin') { header('Location: admin/dashboard.php'); exit; }
    if ($_SESSION['rol'] === 'operario') { header('Location: operario/dashboard.php'); exit; }
}
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $usuario=trim($_POST['usuario'] ?? ''); $password=$_POST['password'] ?? '';
    if ($usuario==='' || $password==='') $error='Completá usuario y contraseña.';
    else {
        $stmt=$conexion->prepare("SELECT p.CI, p.usuario, p.`contraseña` AS password, p.Rol, o.Estado FROM Persona p LEFT JOIN Operario o ON o.CI=p.CI WHERE p.usuario=? LIMIT 1");
        if(!$stmt) $error='Error de MySQL: '.$conexion->error;
        else { $stmt->bind_param('s',$usuario); $stmt->execute(); $r=$stmt->get_result();
            if($r->num_rows!==1) $error='Usuario o contraseña incorrectos.';
            else { $d=$r->fetch_assoc(); $ok=password_verify($password,$d['password']) || hash_equals((string)$d['password'],(string)$password);
                $rol=strtolower((string)$d['Rol']);
                if($rol==='administrador') $rol='admin';
                if($rol==='operario' && $d['Estado']!==null && strtolower($d['Estado'])!=='activo') $error='Este usuario está inactivo.';
                elseif(!$ok) $error='Usuario o contraseña incorrectos.';
                elseif(!in_array($rol,['admin','operario'],true)) $error='Este usuario no tiene acceso al sistema.';
                else { $_SESSION['usuario_id']=$d['CI']; $_SESSION['usuario']=$d['usuario']; $_SESSION['rol']=$rol; header('Location: '.($rol==='admin'?'admin/dashboard.php':'operario/dashboard.php')); exit; }
            } $stmt->close();
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