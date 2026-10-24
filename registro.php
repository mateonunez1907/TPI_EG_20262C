<?php

session_start();
date_default_timezone_set("America/Argentina/Cordoba");

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/correo.php";
require_once __DIR__ . "/cuentas.php";

if (empty($_SESSION["tokenRegistro"])) {
    $_SESSION["tokenRegistro"] = bin2hex(random_bytes(32));
}

$registroExitoso = $_SESSION["registroExitoso"] ?? false;
unset($_SESSION["registroExitoso"]);

$nombre = "";
$mail = "";
$errores = [];

try {
    eliminarCuentasVencidas($conexion);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nombre = is_string($_POST["nombre"] ?? null)
            ? trim($_POST["nombre"]) : "";

        $mail = is_string($_POST["mail"] ?? null)
            ? strtolower(trim($_POST["mail"])) : "";

        $clave = is_string($_POST["clave"] ?? null)
            ? $_POST["clave"] : "";

        $repetirClave = is_string($_POST["repetirClave"] ?? null)
            ? $_POST["repetirClave"] : "";

        $token = $_POST["token"] ?? "";

        if (!is_string($token)
            || !hash_equals($_SESSION["tokenRegistro"], $token)) {
            $errores[] = "El formulario venció. Volvé a intentarlo.";
        }

        if (mb_strlen($nombre, "UTF-8") < 2
            || mb_strlen($nombre, "UTF-8") > 100) {
            $errores[] = "El nombre debe tener entre 2 y 100 caracteres.";
        }

        if (strlen($mail) > 254
            || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Ingresá un correo electrónico válido.";
        }

        if (mb_strlen($clave, "UTF-8") < 8
            || mb_strlen($clave, "UTF-8") > 64) {
            $errores[] = "La contraseña debe tener entre 8 y 64 caracteres.";
        }

        if ($clave !== $repetirClave) {
            $errores[] = "Las contraseñas no coinciden.";
        }

        if (empty($errores)) {
            $sql = "SELECT codUsuario
                    FROM usuarios
                    WHERE mailUsuario = ?";

            $consulta = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($consulta, "s", $mail);
            mysqli_stmt_execute($consulta);

            $resultado = mysqli_stmt_get_result($consulta);
            $usuarioExistente = mysqli_fetch_assoc($resultado);

            mysqli_stmt_close($consulta);

            if ($usuarioExistente) {
                $errores[] = "Ya existe una cuenta con ese correo electrónico.";

            } else {
                $claveProtegida = password_hash(
                    $clave,
                    PASSWORD_ARGON2ID
                );

                $tokenVerificacion = bin2hex(random_bytes(32));
                $tokenProtegido = hash("sha256", $tokenVerificacion);

                $fechaRegistro = date("Y-m-d H:i:s");

                $fechaLimite = date(
                    "Y-m-d H:i:s",
                    time() + 24 * 60 * 60
                );

                mysqli_begin_transaction($conexion);

                // El tipo se fija en PHP. Este formulario crea pasajeros.
                $sql = "INSERT INTO usuarios
                            (nombreUsuario, mailUsuario, claveUsuario,
                             tipoUsuario, tokenVerificacion,
                             fechaRegistro, fechaLimiteVerificacion)
                        VALUES (?, ?, ?, 'pasajero', ?, ?, ?)";

                $consulta = mysqli_prepare($conexion, $sql);

                mysqli_stmt_bind_param(
                    $consulta,
                    "ssssss",
                    $nombre,
                    $mail,
                    $claveProtegida,
                    $tokenProtegido,
                    $fechaRegistro,
                    $fechaLimite
                );

                mysqli_stmt_execute($consulta);
                mysqli_stmt_close($consulta);

                $enlace = "http://localhost:8888/verificar_correo.php?token="
                    . $tokenVerificacion;

                $mensaje = "Hola, " . $nombre . ".\n\n"
                    . "Para verificar tu cuenta de ViajAR, abrí este enlace:\n\n"
                    . $enlace . "\n\n"
                    . "El enlace es válido durante 24 horas.\n"
                    . "Si no solicitaste esta cuenta, podés ignorar este correo.";

                $enviado = enviarCorreo(
                    $mail,
                    "Verificá tu cuenta de ViajAR",
                    $mensaje
                );

                if ($enviado) {
                    mysqli_commit($conexion);

                    $_SESSION["registroExitoso"] = true;

                    $_SESSION["tokenRegistro"] = bin2hex(
                        random_bytes(32)
                    );

                    mysqli_close($conexion);

                    header("Location: registro.php");
                    exit;

                } else {
                    mysqli_rollback($conexion);

                    $errores[] = "No pudimos enviar el correo. Intentá registrarte nuevamente.";
                }
            }
        }
    }

} catch (mysqli_sql_exception $error) {
    mysqli_rollback($conexion);
    error_log($error->getMessage());

    if ((int) $error->getCode() === 1062) {
        $errores[] = "Ya existe una cuenta con ese correo electrónico.";

    } else {
        http_response_code(500);

        $errores[] = "No pudimos completar el registro. Intentá nuevamente.";
    }
}

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Crear cuenta</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >
</head>

<body class="d-flex flex-column min-vh-100">

    <a href="#contenido" class="visually-hidden-focusable">
        Ir al contenido principal
    </a>

    <header>
        <nav class="navbar navbar-dark bg-primary"
             aria-label="Navegación principal">

            <div class="container d-flex flex-wrap gap-3">

                <a class="navbar-brand fw-bold" href="index.php">
                    ViajAR
                </a>

                <ul class="navbar-nav flex-row flex-wrap gap-3">

                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-white" href="vuelos.php">
                            Vuelos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active"
                           href="registro.php"
                           aria-current="page">
                            Crear cuenta
                        </a>
                    </li>

                </ul>

            </div>
        </nav>
    </header>

    <main id="contenido" class="container py-5 flex-grow-1">

        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    <a href="index.php">Inicio</a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    Crear cuenta
                </li>

            </ol>
        </nav>

        <div class="mx-auto" style="max-width: 600px;">

            <h1>Crear cuenta de pasajero</h1>

            <?php if ($registroExitoso): ?>

                <div class="alert alert-success" role="status">

                    <h2 class="h5">Revisá tu correo</h2>

                    <p class="mb-0">
                        Te enviamos un enlace para verificar tu cuenta.
                        Tenés 24 horas para usarlo.
                        Revisá también la carpeta de spam.
                    </p>

                </div>

                <a href="index.php" class="btn btn-primary">
                    Volver al inicio
                </a>

            <?php else: ?>

                <?php if (!empty($errores)): ?>

                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">

                            <?php foreach ($errores as $error): ?>
                                <li>
                                    <?php echo textoSeguro($error); ?>
                                </li>
                            <?php endforeach; ?>

                        </ul>
                    </div>

                <?php endif; ?>

                <p>
                    Completá tus datos.
                    Todos los campos son obligatorios.
                </p>

                <form method="post" action="registro.php">

                    <input
                        type="hidden"
                        name="token"
                        value="<?php echo textoSeguro($_SESSION["tokenRegistro"]); ?>"
                    >

                    <div class="mb-3">

                        <label for="nombre" class="form-label">
                            Nombre y apellido
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="form-control"
                            minlength="2"
                            maxlength="100"
                            autocomplete="name"
                            required
                            value="<?php echo textoSeguro($nombre); ?>"
                        >

                    </div>

                    <div class="mb-3">

                        <label for="mail" class="form-label">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="mail"
                            name="mail"
                            class="form-control"
                            maxlength="254"
                            autocomplete="email"
                            required
                            value="<?php echo textoSeguro($mail); ?>"
                        >

                    </div>

                    <div class="mb-3">

                        <label for="clave" class="form-label">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="clave"
                            name="clave"
                            class="form-control"
                            minlength="8"
                            maxlength="64"
                            autocomplete="new-password"
                            aria-describedby="ayuda-clave"
                            required
                        >

                        <div id="ayuda-clave" class="form-text">
                            Usá entre 8 y 64 caracteres.
                        </div>

                    </div>

                    <div class="mb-3">

                        <label for="repetirClave" class="form-label">
                            Repetir contraseña
                        </label>

                        <input
                            type="password"
                            id="repetirClave"
                            name="repetirClave"
                            class="form-control"
                            minlength="8"
                            maxlength="64"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <p class="text-secondary">
                        Después del registro te enviaremos un enlace
                        para verificar tu correo.
                    </p>

                    <button type="submit" class="btn btn-primary">
                        Crear cuenta
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                </form>

                <script>
                    const campoClave = document.getElementById("clave");
                    const campoRepetir = document.getElementById("repetirClave");

                    function compararClaves() {
                        const coinciden = campoClave.value === campoRepetir.value;

                        campoRepetir.setCustomValidity(
                            coinciden ? "" : "Las contraseñas no coinciden."
                        );
                    }

                    campoClave.addEventListener("input", compararClaves);
                    campoRepetir.addEventListener("input", compararClaves);
                </script>

            <?php endif; ?>

        </div>

    </main>

    <footer class="bg-light border-top py-4">

        <div class="container">

            <nav aria-label="Mapa del sitio">

                <h2 class="h6">Mapa del sitio</h2>

                <a href="index.php" class="me-3">Inicio</a>
                <a href="vuelos.php" class="me-3">Vuelos</a>
                <a href="registro.php">Crear cuenta</a>

            </nav>

            <p class="mt-3 mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>

        </div>

    </footer>

</body>
</html>