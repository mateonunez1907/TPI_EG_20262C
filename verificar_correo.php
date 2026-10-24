<?php

date_default_timezone_set("America/Argentina/Cordoba");

header("Referrer-Policy: no-referrer");

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

$token = $_GET["token"] ?? "";
$verificado = false;

$mensaje = "El enlace no es válido, ya fue utilizado o venció.";

try {
    eliminarCuentasVencidas($conexion);

    if (is_string($token)
        && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1) {

        $tokenProtegido = hash("sha256", $token);
        $ahora = date("Y-m-d H:i:s");

        // La condición impide usar dos veces el mismo enlace.
        $sql = "UPDATE usuarios
                SET correoVerificado = 1,
                    tokenVerificacion = NULL,
                    fechaLimiteVerificacion = NULL
                WHERE tokenVerificacion = ?
                  AND correoVerificado = 0
                  AND fechaLimiteVerificacion > ?
                  AND activo = 1";

        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param(
            $consulta,
            "ss",
            $tokenProtegido,
            $ahora
        );

        mysqli_stmt_execute($consulta);

        $verificado = mysqli_stmt_affected_rows($consulta) === 1;

        mysqli_stmt_close($consulta);

        if ($verificado) {
            $mensaje = "Tu correo fue verificado correctamente.";
        }
    }

    if (!$verificado) {
        http_response_code(400);
    }

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);

    $mensaje = "No pudimos verificar tu correo. Intentá nuevamente más tarde.";
}

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Verificación del correo</title>

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

                <li class="breadcrumb-item">
                    <a href="registro.php">Crear cuenta</a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    Verificar correo
                </li>

            </ol>
        </nav>

        <h1>Verificación del correo</h1>

        <p
            class="alert alert-<?php echo $verificado ? "success" : "warning"; ?>"
            role="status"
        >
            <?php echo textoSeguro($mensaje); ?>
        </p>

        <a href="index.php" class="btn btn-primary">
            Volver al inicio
        </a>

        <?php if (!$verificado): ?>

            <a href="registro.php" class="btn btn-outline-secondary">
                Ir al registro
            </a>

        <?php endif; ?>

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