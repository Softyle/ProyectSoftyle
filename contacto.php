<?php

// =====================================================
// CONTACTO.PHP
// Procesamiento del formulario
// =====================================================


// Solo permitir solicitudes POST

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php#contacto");

    exit;

}


// =====================================================
// OBTENER DATOS
// =====================================================
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$mensaje = isset($_POST['mensaje']) ? $_POST['mensaje'] : '';

// =====================================================
// VALIDACIONES
// =====================================================

$errores = [];


if ($nombre === "") {

    $errores[] =
        "El nombre es obligatorio.";

}


if (
    strlen($nombre) < 2
) {

    $errores[] =
        "El nombre debe tener al menos 2 caracteres.";

}


if (
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {

    $errores[] =
        "El correo electrónico no es válido.";

}


if (
    strlen($mensaje) < 10
) {

    $errores[] =
        "El mensaje debe tener al menos 10 caracteres.";

}


// =====================================================
// SI HAY ERRORES
// =====================================================

if (!empty($errores)) {

    ?>

    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Error | Softyle</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="CSS/estilos.css"
        >

    </head>


    <body>

        <div class="container py-5">

            <div class="alert alert-danger">

                <h4>
                    No pudimos enviar el mensaje
                </h4>


                <ul class="mb-0">

                    <?php foreach ($errores as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>


            <a
                href="index.php#contacto"
                class="btn btn-primary-softyle"
            >
                Volver al formulario
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;

}


// =====================================================
// SEGURIDAD
// =====================================================

$nombreSeguro =
    htmlspecialchars(
        $nombre,
        ENT_QUOTES,
        "UTF-8"
    );


$correoSeguro =
    htmlspecialchars(
        $email,
        ENT_QUOTES,
        "UTF-8"
    );


$mensajeSeguro =
    htmlspecialchars(
        $mensaje,
        ENT_QUOTES,
        "UTF-8"
    );


// =====================================================
// GUARDAR MENSAJE
// =====================================================

// Archivo donde se almacenan los mensajes.

$archivo =
    __DIR__ . "/mensajes.txt";


$fecha =
    date("Y-m-d H:i:s");


$registro =
    "========================================\n" .
    "Fecha: " . $fecha . "\n" .
    "Nombre: " . $nombre . "\n" .
    "Correo: " . $email . "\n" .
    "Mensaje:\n" . $mensaje . "\n" .
    "========================================\n\n";


file_put_contents(
    $archivo,
    $registro,
    FILE_APPEND | LOCK_EX
);


// =====================================================
// RESPUESTA
// =====================================================

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mensaje enviado | Softyle</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="CSS/estilos.css"
    >

</head>


<body>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="info-card text-center">

                <div
                    class="mb-3"
                    style="font-size:50px;color:#20c95a;"
                >

                    ✓

                </div>


                <h2>
                    ¡Mensaje enviado!
                </h2>


                <p>

                    Gracias
                    <strong>
                        <?= $nombreSeguro ?>
                    </strong>.

                    Recibimos correctamente tu mensaje.

                </p>


                <p>

                    Te responderemos en:

                    <strong>
                        <?= $correoSeguro ?>
                    </strong>

                </p>


                <a
                    href="index.php"
                    class="btn btn-primary-softyle mt-3"
                >
                    Volver al sitio
                </a>

            </div>

        </div>

    </div>

</div>


</body>

</html>