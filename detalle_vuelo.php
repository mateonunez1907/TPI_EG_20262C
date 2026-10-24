<?php
date_default_timezone_set("America/Argentina/Cordoba");
require_once __DIR__ . "/conexion.php";

function textoSeguro($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

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

// Solo permitimos volver al listado de nuestro sitio.
$volver = $_GET["volver"] ?? "vuelos.php";

if (!is_string($volver)
    || ($volver !== "vuelos.php" && !str_starts_with($volver, "vuelos.php?"))) {
    $volver = "vuelos.php";
}

$hoy = date("Y-m-d");
$vuelo = null;
$mensaje = "";

if ($codVuelo === false) {
    http_response_code(400);
    $mensaje = "El código del vuelo no es válido.";

} elseif ($pasajeros === false) {
    http_response_code(400);
    $mensaje = "La cantidad de pasajeros debe ser un número entero entre 1 y 10.";

} else {
    $sql = "SELECT v.codVuelo, v.origenVuelo, v.destinoVuelo,
                   v.fechaSalidaVuelo, v.horaSalidaVuelo,
                   v.precioVuelo, v.asientosDisponibles,
                   a.nombreAerolinea, a.codigoIATA,
                   a.descripcionAerolinea
            FROM vuelos AS v
            INNER JOIN aerolineas AS a
                ON v.codAerolinea = a.codAerolinea
            WHERE v.codVuelo = ?
              AND v.activo = 1
              AND a.activo = 1
              AND v.fechaSalidaVuelo > ?
              AND v.asientosDisponibles >= ?";

    try {
        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param(
            $consulta,
            "isi",
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
            $mensaje = "El vuelo no está disponible para la cantidad de pasajeros indicada.";
        } else {
            $importeTotal = (float) $vuelo["precioVuelo"] * $pasajeros;
        }

    } catch (mysqli_sql_exception $error) {
        error_log($error->getMessage());
        http_response_code(500);
        exit("No se pudo consultar el detalle del vuelo.");
    }
}

mysqli_close($conexion);
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

    <header>
        <nav class="navbar navbar-dark bg-primary"
             aria-label="Navegación principal">
            <div class="container d-flex flex-wrap gap-3">
                <a class="navbar-brand fw-bold" href="index.php">ViajAR</a>

                <ul class="navbar-nav flex-row gap-3">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="vuelos.php">Vuelos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white"
                           href="index.php#sobre-viajar">Sobre ViajAR</a>
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
                        →
                        <?php echo textoSeguro($vuelo["destinoVuelo"]); ?>
                    </h2>

                    <p class="text-secondary">
                        Vuelo N.º <?php echo (int) $vuelo["codVuelo"]; ?>
                    </p>

                    <dl class="row mb-0">
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
                    <strong>Precio por pasajero:</strong>
                    $ <?php echo number_format((float) $vuelo["precioVuelo"], 2, ",", "."); ?>
                </p>

                <p>
                    <strong>Cantidad de pasajeros:</strong>
                    <?php echo (int) $pasajeros; ?>
                </p>

                <p class="fs-5 mb-1">
                    <strong>Importe de la tarifa:</strong>
                    $ <?php echo number_format($importeTotal, 2, ",", "."); ?>
                </p>

                <p class="text-secondary mb-0">
                    Importes expresados en pesos argentinos.
                </p>
            </section>
        <?php endif; ?>

        <a class="btn btn-outline-primary"
           href="<?php echo textoSeguro($volver); ?>">
            Volver a resultados
        </a>

    </main>

    <footer class="bg-light border-top py-4">
        <div class="container">
            <nav aria-label="Mapa del sitio">
                <h2 class="h6">Mapa del sitio</h2>
                <ul class="list-unstyled">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="vuelos.php">Vuelos</a></li>
                    <li><a href="index.php#sobre-viajar">Sobre ViajAR</a></li>
                </ul>
            </nav>

            <p class="mb-0">&copy; <?php echo date("Y"); ?> ViajAR</p>
        </div>
    </footer>

</body>
</html>
