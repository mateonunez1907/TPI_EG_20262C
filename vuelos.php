<?php
date_default_timezone_set("America/Argentina/Cordoba");
require_once __DIR__ . "/conexion.php";

function textoSeguro($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

$hoy = date("Y-m-d");

$sql = "SELECT v.codVuelo, v.origenVuelo, v.destinoVuelo,
               v.fechaSalidaVuelo, v.horaSalidaVuelo,
               v.precioVuelo, v.asientosDisponibles,
               a.nombreAerolinea
        FROM vuelos AS v
        INNER JOIN aerolineas AS a
            ON v.codAerolinea = a.codAerolinea
        WHERE v.activo = 1
          AND a.activo = 1
          AND v.asientosDisponibles > 0
          AND v.fechaSalidaVuelo > ?
        ORDER BY v.fechaSalidaVuelo, v.horaSalidaVuelo, v.codVuelo";

try {
    $consulta = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($consulta, "s", $hoy);
    mysqli_stmt_execute($consulta);
    $vuelos = mysqli_stmt_get_result($consulta);
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit("No se pudieron consultar los vuelos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViajAR - Vuelos disponibles</title>

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
                        <a class="nav-link text-white" href="index.php">
                            Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="vuelos.php"
                           aria-current="page">
                            Vuelos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-white"
                           href="index.php#sobre-viajar">
                            Sobre ViajAR
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
                    Vuelos
                </li>
            </ol>
        </nav>

        <h1>Vuelos disponibles</h1>
        <p>
            Consultá las próximas salidas y los precios por pasajero
            en pesos argentinos.
        </p>

        <?php if (mysqli_num_rows($vuelos) === 0): ?>
            <p class="alert alert-info">
                No hay vuelos disponibles por el momento.
            </p>
        <?php else: ?>
            <div class="row g-4">

                <?php while ($vuelo = mysqli_fetch_assoc($vuelos)): ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100">
                            <div class="card-body">

                                <h2 class="h5">
                                    <?php echo textoSeguro($vuelo["origenVuelo"]); ?>
                                    →
                                    <?php echo textoSeguro($vuelo["destinoVuelo"]); ?>
                                </h2>

                                <p>
                                    <?php echo textoSeguro($vuelo["nombreAerolinea"]); ?>
                                </p>

                                <p class="mb-1">
                                    <strong>Salida:</strong>
                                    <?php echo date("d/m/Y", strtotime($vuelo["fechaSalidaVuelo"])); ?>
                                    a las
                                    <?php echo textoSeguro(substr($vuelo["horaSalidaVuelo"], 0, 5)); ?>
                                </p>

                                <p class="mb-1">
                                    <strong>Precio por pasajero:</strong>
                                    $ <?php echo number_format((float) $vuelo["precioVuelo"], 2, ",", "."); ?>
                                </p>

                                <p class="mb-0">
                                    <strong>Asientos disponibles:</strong>
                                    <?php echo (int) $vuelo["asientosDisponibles"]; ?>
                                </p>

                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>

            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-light border-top py-4">
        <div class="container">
            <nav aria-label="Mapa del sitio">
                <h2 class="h6">Mapa del sitio</h2>

                <ul class="list-unstyled">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="vuelos.php">Vuelos</a></li>
                    <li>
                        <a href="index.php#sobre-viajar">Sobre ViajAR</a>
                    </li>
                </ul>
            </nav>

            <p class="mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>
        </div>
    </footer>

</body>
</html>

<?php
mysqli_stmt_close($consulta);
mysqli_close($conexion);