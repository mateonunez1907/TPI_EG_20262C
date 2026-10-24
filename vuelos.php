<?php
date_default_timezone_set("America/Argentina/Cordoba");
require_once __DIR__ . "/conexion.php";

function textoSeguro($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

// Leemos los filtros. Si no se enviaron, usamos valores iniciales.
$origen = $_GET["origen"] ?? "";
$destino = $_GET["destino"] ?? "";
$fecha = $_GET["fecha"] ?? "";
$pasajerosTexto = $_GET["pasajeros"] ?? "1";

$errores = [];
$consulta = null;
$vuelos = null;

// Mostramos dos vuelos por página para probar con los tres ejemplos.
$vuelosPorPagina = 2;
$totalVuelos = 0;
$totalPaginas = 1;

$pagina = filter_var(
    $_GET["pagina"] ?? "1",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($pagina === false) {
    $pagina = 1;
}

$hoy = date("Y-m-d");
$manana = date("Y-m-d", strtotime("+1 day"));

// Evitamos recibir listas donde esperamos un único valor.
if (!is_string($origen) || !is_string($destino)
    || !is_string($fecha) || !is_string($pasajerosTexto)) {
    $errores[] = "Los datos de la búsqueda no son válidos.";
    $origen = "";
    $destino = "";
    $fecha = "";
    $pasajerosTexto = "1";
}

$origen = trim($origen);
$destino = trim($destino);
$fecha = trim($fecha);
$pasajerosTexto = trim($pasajerosTexto);

if (mb_strlen($origen) > 100 || mb_strlen($destino) > 100) {
    $errores[] = "El origen y el destino pueden tener hasta 100 caracteres.";
}

// Validamos la cantidad también en PHP.
$pasajeros = filter_var(
    $pasajerosTexto,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 10]]
);

if ($pasajeros === false) {
    $errores[] = "La cantidad de pasajeros debe ser un número entero entre 1 y 10.";
}

// La fecha es opcional, pero debe ser real y posterior a hoy.
if ($fecha !== "") {
    $fechaValida = false;

    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $fecha)) {
        $partesFecha = explode("-", $fecha);

        $fechaValida = checkdate(
            (int) $partesFecha[1],
            (int) $partesFecha[2],
            (int) $partesFecha[0]
        );
    }

    if (!$fechaValida) {
        $errores[] = "Ingresá una fecha válida.";
    } elseif ($fecha <= $hoy) {
        $errores[] = "La fecha de salida debe ser posterior al día de hoy.";
    }
}

// Consultamos solo cuando los filtros son válidos.
if (empty($errores)) {
    $origenBusqueda = "%" . $origen . "%";
    $destinoBusqueda = "%" . $destino . "%";

    // Usamos los mismos filtros para contar y para listar.
    $filtrosSql = " FROM vuelos AS v
                    INNER JOIN aerolineas AS a
                        ON v.codAerolinea = a.codAerolinea
                    WHERE v.activo = 1
                      AND a.activo = 1
                      AND v.fechaSalidaVuelo > ?
                      AND v.origenVuelo LIKE ?
                      AND v.destinoVuelo LIKE ?
                      AND (? = '' OR v.fechaSalidaVuelo = ?)
                      AND v.asientosDisponibles >= ?";

    try {
        // Primero contamos todos los vuelos que coinciden.
        $sqlTotal = "SELECT COUNT(*) AS total" . $filtrosSql;
        $consultaTotal = mysqli_prepare($conexion, $sqlTotal);

        mysqli_stmt_bind_param(
            $consultaTotal,
            "sssssi",
            $hoy,
            $origenBusqueda,
            $destinoBusqueda,
            $fecha,
            $fecha,
            $pasajeros
        );

        mysqli_stmt_execute($consultaTotal);
        $resultadoTotal = mysqli_stmt_get_result($consultaTotal);
        $filaTotal = mysqli_fetch_assoc($resultadoTotal);
        $totalVuelos = (int) $filaTotal["total"];

        mysqli_stmt_close($consultaTotal);

        // Redondeamos hacia arriba para calcular la cantidad de páginas.
        $totalPaginas = max(1, (int) ceil($totalVuelos / $vuelosPorPagina));

        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $desplazamiento = ($pagina - 1) * $vuelosPorPagina;

        // LIMIT indica cuántos traer y OFFSET cuántos saltear.
        $sql = "SELECT v.codVuelo, v.origenVuelo, v.destinoVuelo,
                       v.fechaSalidaVuelo, v.horaSalidaVuelo,
                       v.precioVuelo, v.asientosDisponibles,
                       a.nombreAerolinea"
               . $filtrosSql .
               " ORDER BY v.fechaSalidaVuelo, v.horaSalidaVuelo, v.codVuelo
                 LIMIT ? OFFSET ?";

        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param(
            $consulta,
            "sssssiii",
            $hoy,
            $origenBusqueda,
            $destinoBusqueda,
            $fecha,
            $fecha,
            $pasajeros,
            $vuelosPorPagina,
            $desplazamiento
        );

        mysqli_stmt_execute($consulta);
        $vuelos = mysqli_stmt_get_result($consulta);

        // Armamos los enlaces conservando los filtros de búsqueda.
        $enlaceBase = "vuelos.php?" . http_build_query([
            "origen" => $origen,
            "destino" => $destino,
            "fecha" => $fecha,
            "pasajeros" => $pasajeros
        ]) . "&pagina=";

    } catch (mysqli_sql_exception $error) {
        error_log($error->getMessage());
        http_response_code(500);
        exit("No se pudieron consultar los vuelos.");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViajAR - Buscar vuelos</title>

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
                        <a class="nav-link active" href="vuelos.php"
                           aria-current="page">Vuelos</a>
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
                <li class="breadcrumb-item active" aria-current="page">Vuelos</li>
            </ol>
        </nav>

        <h1>Buscar vuelos</h1>
        <p>
            Podés completar solo los filtros que necesites.
            Los precios son por pasajero y están expresados en pesos argentinos.
        </p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger" role="alert">
                <p>Revisá los siguientes datos:</p>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo textoSeguro($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="vuelos.php" method="get"
              class="border rounded p-4 mb-4" aria-label="Buscar vuelos">

            <div class="row g-3">
                <div class="col-md-3">
                    <label for="origen" class="form-label">Origen</label>
                    <input type="text" id="origen" name="origen"
                           class="form-control" maxlength="100"
                           placeholder="Ej.: Rosario"
                           value="<?php echo textoSeguro($origen); ?>">
                </div>

                <div class="col-md-3">
                    <label for="destino" class="form-label">Destino</label>
                    <input type="text" id="destino" name="destino"
                           class="form-control" maxlength="100"
                           placeholder="Ej.: Buenos Aires"
                           value="<?php echo textoSeguro($destino); ?>">
                </div>

                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha de salida</label>
                    <input type="date" id="fecha" name="fecha"
                           class="form-control"
                           min="<?php echo textoSeguro($manana); ?>"
                           value="<?php echo textoSeguro($fecha); ?>">
                </div>

                <div class="col-md-3">
                    <label for="pasajeros" class="form-label">Pasajeros</label>
                    <input type="number" id="pasajeros" name="pasajeros"
                           class="form-control" min="1" max="10" step="1"
                           required
                           value="<?php echo textoSeguro($pasajerosTexto); ?>">
                </div>
            </div>

            <p class="form-text mt-3">
                La fecha de salida debe ser posterior a hoy.
                Podés buscar entre 1 y 10 pasajeros.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Buscar vuelos</button>
                <a href="vuelos.php" class="btn btn-outline-secondary">
                    Limpiar búsqueda
                </a>
            </div>
        </form>

        <?php if (empty($errores)): ?>
            <h2 class="h4">Vuelos disponibles</h2>

            <?php if (mysqli_num_rows($vuelos) === 0): ?>
                <p class="alert alert-info">
                    No hay vuelos disponibles para los filtros seleccionados.
                    Probá con otros datos o limpiá la búsqueda.
                </p>
            <?php else: ?>
                <p>
                    Mostrando
                    <?php echo $desplazamiento + 1; ?>
                    a
                    <?php echo min($desplazamiento + $vuelosPorPagina, $totalVuelos); ?>
                    de
                    <?php echo $totalVuelos; ?> vuelos.
                    Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>.
                </p>

                <p>
                    Cantidad de pasajeros de la búsqueda:
                    <strong><?php echo (int) $pasajeros; ?></strong>.
                </p>

                <div class="row g-4">
                    <?php while ($vuelo = mysqli_fetch_assoc($vuelos)): ?>
                        <?php
                        $enlaceDetalle = "detalle_vuelo.php?" . http_build_query([
                            "codVuelo" => (int) $vuelo["codVuelo"],
                            "pasajeros" => $pasajeros,
                            "volver" => $enlaceBase . $pagina
                        ]);
                        ?>

                        <div class="col-md-6">
                            <article class="card h-100">
                                <div class="card-body">
                                    <h3 class="h5">
                                        <?php echo textoSeguro($vuelo["origenVuelo"]); ?>
                                        →
                                        <?php echo textoSeguro($vuelo["destinoVuelo"]); ?>
                                    </h3>

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

                                    <a class="btn btn-primary mt-3"
                                       href="<?php echo textoSeguro($enlaceDetalle); ?>">
                                        Ver detalle
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="mt-4" aria-label="Páginas de resultados">
                        <ul class="pagination justify-content-center">

                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="<?php echo textoSeguro($enlaceBase . ($pagina - 1)); ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            <?php endif; ?>

                            <li class="page-item active">
                                <span class="page-link" aria-current="page">
                                    <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
                                </span>
                            </li>

                            <?php if ($pagina < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="<?php echo textoSeguro($enlaceBase . ($pagina + 1)); ?>">
                                        Siguiente
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Siguiente</span>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>

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

<?php
if ($consulta !== null) {
    mysqli_stmt_close($consulta);
}

mysqli_close($conexion);