<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

header("Cache-Control: no-store");

$mail = "";
$errores = [];
$sesionCerrada = ($_GET["salida"] ?? "") === "1";

try {
    if (isset($_SESSION["codUsuario"])) {
        $usuarioSesion = obtenerUsuarioSesion($conexion);

        if ($usuarioSesion) {
            mysqli_close($conexion);

            header("Location: mi_cuenta.php");
            exit;
        }

        unset($_SESSION["codUsuario"]);
    }

    eliminarCuentasVencidas($conexion);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $mail = is_string($_POST["mail"] ?? null)
            ? strtolower(trim($_POST["mail"])) : "";

        $clave = is_string($_POST["clave"] ?? null)
            ? $_POST["clave"] : "";

        $token = $_POST["token"] ?? "";

        if (!is_string($token)
            || !hash_equals($_SESSION["tokenSesion"], $token)) {
            $errores[] = "El formulario venció. Volvé a intentarlo.";
        }

        if (strlen($mail) > 254
            || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Ingresá un correo electrónico válido.";
        }

        if (mb_strlen($clave, "UTF-8") < 8
            || mb_strlen($clave, "UTF-8") > 64) {
            $errores[] = "La contraseña debe tener entre 8 y 64 caracteres.";
        }

        if (empty($errores)) {
            $sql = "SELECT codUsuario, claveUsuario, tipoUsuario,
                           correoVerificado, estadoCEO, activo
                    FROM usuarios
                    WHERE mailUsuario = ?";

            $consulta = mysqli_prepare($conexion, $sql);

            mysqli_stmt_bind_param($consulta, "s", $mail);
            mysqli_stmt_execute($consulta);

            $resultado = mysqli_stmt_get_result($consulta);
            $usuario = mysqli_fetch_assoc($resultado);

            mysqli_stmt_close($consulta);

            if (!$usuario
                || !password_verify($clave, $usuario["claveUsuario"])) {
                $errores[] = "El correo o la contraseña son incorrectos.";

            } elseif ((int) $usuario["activo"] !== 1) {
                $errores[] = "La cuenta no está habilitada.";

            } elseif ((int) $usuario["correoVerificado"] !== 1) {
                $errores[] = "Primero verificá tu correo con el enlace que te enviamos.";

            } elseif (!in_array(
                $usuario["tipoUsuario"],
                ["pasajero", "ceo", "administrador"],
                true
            )) {
                $errores[] = "La cuenta no tiene un tipo de usuario válido.";

            } elseif ($usuario["tipoUsuario"] === "ceo"
                && $usuario["estadoCEO"] !== "aprobado") {
                $errores[] = "La cuenta de CEO todavía no está autorizada por administración.";

            } else {
                // Cambiamos el identificador al iniciar sesión.
                if (!session_regenerate_id(true)) {
                    http_response_code(500);

                    $errores[] = "No pudimos iniciar sesión. Intentá nuevamente.";

                } else {
                    $_SESSION = [];

                    $_SESSION["codUsuario"] = (int) $usuario["codUsuario"];

                    $_SESSION["tokenSesion"] = bin2hex(
                        random_bytes(32)
                    );

                    mysqli_close($conexion);

                    header("Location: mi_cuenta.php");
                    exit;
                }
            }
        }
    }

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);

    $errores[] = "No pudimos iniciar sesión. Intentá nuevamente más tarde.";
}

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Iniciar sesión</title>

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
                        <a class="nav-link text-white" href="registro.php">
                            Crear cuenta
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active"
                           href="iniciar_sesion.php"
                           aria-current="page">
                            Iniciar sesión
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
                    Iniciar sesión
                </li>

            </ol>
        </nav>

        <div class="mx-auto" style="max-width: 600px;">

            <h1>Iniciar sesión</h1>

            <?php if ($sesionCerrada): ?>

                <p class="alert alert-success" role="status">
                    Cerraste sesión correctamente.
                </p>

            <?php endif; ?>

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

            <form method="post" action="iniciar_sesion.php">

                <input
                    type="hidden"
                    name="token"
                    value="<?php echo textoSeguro($_SESSION["tokenSesion"]); ?>"
                >

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
                        autocomplete="username"
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
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button type="submit" class="btn btn-primary">
                    Ingresar
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>

            </form>

            <p class="mt-4">
                ¿Todavía no tenés cuenta?
                <a href="registro.php">Registrate</a>.
            </p>

        </div>

    </main>

    <footer class="bg-light border-top py-4">

        <div class="container">

            <nav aria-label="Mapa del sitio">

                <h2 class="h6">Mapa del sitio</h2>

                <a href="index.php" class="me-3">Inicio</a>
                <a href="vuelos.php" class="me-3">Vuelos</a>
                <a href="registro.php" class="me-3">Crear cuenta</a>
                <a href="iniciar_sesion.php">Iniciar sesión</a>

            </nav>

            <p class="mt-3 mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>

        </div>

    </footer>

</body>
</html>