<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

$codVuelo = filter_var(
    $_GET["codVuelo"] ?? "",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$pasajeros = filter_var(
    $_GET["pasajeros"] ?? "1",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 10]]
);

$volver = $_GET["volver"] ?? "vuelos.php";

if (!is_string($volver)
    || ($volver !== "vuelos.php" && !str_starts_with($volver, "vuelos.php?"))) {
    $volver = "vuelos.php";
}

$vuelo = null;
$usuarioSesion = null;
$mensaje = "";

try {
    if (isset($_SESSION["codUsuario"])) {
        $usuarioSesion = obtenerUsuarioSesion($conexion);
    }

    if ($codVuelo === false || $pasajeros === false) {
        http_response_code(400);

        $mensaje = "El vuelo o la cantidad de pasajeros no son válidos.";

    } else {
        $hoy = date("Y-m-d");

        $sql = "SELECT v.codVuelo, v.origenVuelo, v.destinoVuelo,
                       v.fechaSalidaVuelo, v.horaSalidaVuelo,
                       v.precioVuelo, v.asientosDisponibles,
                       a.nombreAerolinea, a.codigoIATA,
                       a.descripcionAerolinea,
                       v.precioVuelo * ? AS importeTotal
                FROM vuelos AS v
                INNER JOIN aerolineas AS a
                    ON v.codAerolinea = a.codAerolinea
                WHERE v.codVuelo = ?
                  AND v.activo = 1 AND a.activo = 1
                  AND v.fechaSalidaVuelo > ?
                  AND v.asientosDisponibles >= ?";

        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param(
            $consulta,
            "iisi",
            $pasajeros,
            $codVuelo,
            $hoy,
            $pasajeros
        );

        mysqli_stmt_execute($consulta);

        $resultado = mysqli_stmt_get_result($consulta);
        $vuelo = mysqli_fetch_assoc($resultado);

        mysqli_stmt_close($consulta);

        if (!$vuelo) {
            http_response_code(404);

            $mensaje = "El vuelo no está disponible para la cantidad indicada.";
        }
    }

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);

    exit("No pudimos consultar el vuelo. Intentá nuevamente.");
}

mysqli_close($conexion);

$urlReserva = "reservar.php?" . http_build_query([
    "codVuelo" => $codVuelo,
    "pasajeros" => $pasajeros
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Detalle del vuelo</title>

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

    <header class="bg-primary py-3">
        <nav class="container d-flex flex-wrap gap-3"
             aria-label="Navegación principal">

            <a class="text-white fw-bold" href="index.php">ViajAR</a>
            <a class="text-white" href="vuelos.php">Vuelos</a>

            <?php if ($usuarioSesion): ?>

                <a class="text-white" href="mi_cuenta.php">Mi cuenta</a>

            <?php else: ?>

                <a class="text-white" href="iniciar_sesion.php">
                    Iniciar sesión
                </a>

                <a class="text-white" href="registro.php">Crear cuenta</a>

            <?php endif; ?>

        </nav>
    </header>

    <main id="contenido" class="container py-5 flex-grow-1">

        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    <a href="index.php">Inicio</a>
                </li>

                <li class="breadcrumb-item">
                    <a href="<?php echo textoSeguro($volver); ?>">Vuelos</a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    Detalle del vuelo
                </li>

            </ol>
        </nav>

        <h1>Detalle del vuelo</h1>

        <?php if ($mensaje !== ""): ?>

            <p class="alert alert-warning" role="alert">
                <?php echo textoSeguro($mensaje); ?>
            </p>

        <?php else: ?>

            <article class="card my-4">
                <div class="card-body">

                    <h2 class="h4">
                        <?php echo textoSeguro($vuelo["origenVuelo"]); ?>
                        → <?php echo textoSeguro($vuelo["destinoVuelo"]); ?>
                    </h2>

                    <p>Vuelo N.º <?php echo (int) $vuelo["codVuelo"]; ?></p>

                    <dl class="row">

                        <dt class="col-sm-4">Aerolínea</dt>
                        <dd class="col-sm-8">
                            <?php echo textoSeguro($vuelo["nombreAerolinea"]); ?>
                        </dd>

                        <dt class="col-sm-4">Código IATA</dt>
                        <dd class="col-sm-8">
                            <?php echo textoSeguro($vuelo["codigoIATA"]); ?>
                        </dd>

                        <dt class="col-sm-4">Descripción de la aerolínea</dt>
                        <dd class="col-sm-8">
                            <?php echo textoSeguro($vuelo["descripcionAerolinea"]); ?>
                        </dd>

                        <dt class="col-sm-4">Fecha de salida</dt>
                        <dd class="col-sm-8">
                            <?php echo date("d/m/Y", strtotime($vuelo["fechaSalidaVuelo"])); ?>
                        </dd>

                        <dt class="col-sm-4">Hora de salida</dt>
                        <dd class="col-sm-8">
                            <?php echo textoSeguro(substr($vuelo["horaSalidaVuelo"], 0, 5)); ?>
                        </dd>

                        <dt class="col-sm-4">Asientos disponibles</dt>
                        <dd class="col-sm-8">
                            <?php echo (int) $vuelo["asientosDisponibles"]; ?>
                        </dd>

                    </dl>

                </div>
            </article>

            <section class="border rounded p-4 mb-4"
                     aria-labelledby="titulo-tarifa">

                <h2 id="titulo-tarifa" class="h4">Tarifa</h2>

                <p>
                    Precio por pasajero:
                    $ <?php echo number_format((float) $vuelo["precioVuelo"], 2, ",", "."); ?>
                </p>

                <p>
                    Cantidad de pasajeros:
                    <?php echo (int) $pasajeros; ?>
                </p>

                <p class="fs-4 fw-bold">
                    Total:
                    $ <?php echo number_format((float) $vuelo["importeTotal"], 2, ",", "."); ?>
                </p>

                <p>Importes expresados en pesos argentinos.</p>

                <?php if ($usuarioSesion && $usuarioSesion["tipoUsuario"] === "pasajero"): ?>

                    <a href="<?php echo textoSeguro($urlReserva); ?>"
                       class="btn btn-primary">
                        Reservar vuelo
                    </a>

                <?php elseif (!$usuarioSesion): ?>

                    <p>
                        Para reservar, ingresá con tu cuenta de pasajero
                        y volvé a elegir el vuelo.
                    </p>

                    <a href="iniciar_sesion.php" class="btn btn-primary">
                        Iniciar sesión
                    </a>

                <?php else: ?>

                    <p>Las reservas se realizan con una cuenta de pasajero.</p>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <a href="<?php echo textoSeguro($volver); ?>"
           class="btn btn-outline-primary">
            Volver a resultados
        </a>

    </main>

    <footer class="bg-light border-top py-4">
        <nav class="container" aria-label="Mapa del sitio">

            <h2 class="h6">Mapa del sitio</h2>

            <a href="index.php" class="me-3">Inicio</a>
            <a href="vuelos.php" class="me-3">Vuelos</a>
            <a href="mi_cuenta.php" class="me-3">Mi cuenta</a>
            <a href="index.php#sobre-viajar">Sobre ViajAR</a>

            <p class="mt-3 mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>

        </nav>
    </footer>

</body>
</html>