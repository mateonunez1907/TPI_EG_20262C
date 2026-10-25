<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

header("Cache-Control: no-store");

$reservas = [];
$porPagina = 5;

$pagina = filter_var(
    $_GET["pagina"] ?? "1",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($pagina === false) {
    $pagina = 1;
}

try {
    $usuario = exigirUsuario($conexion);

    if ($usuario["tipoUsuario"] !== "pasajero") {
        http_response_code(403);

        exit("Esta sección corresponde a las cuentas de pasajero.");
    }

    $codUsuario = (int) $usuario["codUsuario"];

    $consulta = mysqli_prepare(
        $conexion,
        "SELECT COUNT(*) AS total FROM reservas WHERE codUsuario = ?"
    );

    mysqli_stmt_bind_param($consulta, "i", $codUsuario);
    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);
    $fila = mysqli_fetch_assoc($resultado);

    $total = (int) $fila["total"];

    mysqli_stmt_close($consulta);

    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $desde = ($pagina - 1) * $porPagina;

    $sql = "SELECT r.codReserva, r.fechaReserva, r.cantidadPasajeros,
                   r.precioUnitario, r.importeTotal, r.estadoReserva,
                   v.origenVuelo, v.destinoVuelo,
                   v.fechaSalidaVuelo, v.horaSalidaVuelo,
                   a.nombreAerolinea
            FROM reservas AS r
            INNER JOIN vuelos AS v ON r.codVuelo = v.codVuelo
            INNER JOIN aerolineas AS a
                ON v.codAerolinea = a.codAerolinea
            WHERE r.codUsuario = ?
            ORDER BY r.fechaReserva DESC, r.codReserva DESC
            LIMIT ? OFFSET ?";

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param(
        $consulta,
        "iii",
        $codUsuario,
        $porPagina,
        $desde
    );

    mysqli_stmt_execute($consulta);
    $resultado = mysqli_stmt_get_result($consulta);

    while ($reserva = mysqli_fetch_assoc($resultado)) {
        $reservas[] = $reserva;
    }

    mysqli_stmt_close($consulta);

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);

    exit("No pudimos consultar tus reservas. Intentá nuevamente.");
}

mysqli_close($conexion);

$reservaCreada = $_SESSION["reservaCreada"] ?? null;
unset($_SESSION["reservaCreada"]);

$estados = [
    "pendiente" => "Pendiente de pago",
    "confirmada" => "Confirmada",
    "cancelada" => "Cancelada"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Mis reservas</title>

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
            <a class="text-white" href="mi_cuenta.php">Mi cuenta</a>
            <a class="text-white" href="mis_reservas.php" aria-current="page">
                Mis reservas
            </a>

        </nav>
    </header>

    <main id="contenido" class="container py-5 flex-grow-1">

        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="mi_cuenta.php">Mi cuenta</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    Mis reservas
                </li>
            </ol>
        </nav>

        <h1>Mis reservas</h1>

        <?php if ($reservaCreada): ?>

            <p class="alert alert-success" role="status">
                La reserva N.º <?php echo (int) $reservaCreada; ?>
                quedó registrada.
            </p>

        <?php endif; ?>

        <a href="vuelos.php" class="btn btn-primary mb-4">
            Buscar vuelos
        </a>

        <?php if (empty($reservas)): ?>

            <p class="alert alert-info">
                Todavía no tenés reservas.
            </p>

        <?php else: ?>

            <p>
                <?php echo $total; ?> reserva(s).
                Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>.
            </p>

            <?php foreach ($reservas as $reserva): ?>

                <article class="card mb-3">
                    <div class="card-body">

                        <h2 class="h4">
                            Reserva N.º <?php echo (int) $reserva["codReserva"]; ?>
                        </h2>

                        <p class="fs-5">
                            <?php echo textoSeguro($reserva["origenVuelo"]); ?>
                            → <?php echo textoSeguro($reserva["destinoVuelo"]); ?>
                        </p>

                        <p>
                            Aerolínea:
                            <?php echo textoSeguro($reserva["nombreAerolinea"]); ?>
                        </p>

                        <p>
                            Salida:
                            <?php echo date("d/m/Y", strtotime($reserva["fechaSalidaVuelo"])); ?>
                            a las
                            <?php echo textoSeguro(substr($reserva["horaSalidaVuelo"], 0, 5)); ?>
                        </p>

                        <p>
                            Pasajeros:
                            <?php echo (int) $reserva["cantidadPasajeros"]; ?>
                        </p>

                        <p>
                            Precio por pasajero:
                            $ <?php echo number_format((float) $reserva["precioUnitario"], 2, ",", "."); ?>
                        </p>

                        <p class="fw-bold">
                            Total:
                            $ <?php echo number_format((float) $reserva["importeTotal"], 2, ",", "."); ?>
                        </p>

                        <p>
                            <span class="badge bg-secondary">
                                <?php echo textoSeguro($estados[$reserva["estadoReserva"]]); ?>
                            </span>
                        </p>

                        <p class="text-secondary mb-0">
                            Registrada el
                            <?php echo date("d/m/Y H:i", strtotime($reserva["fechaReserva"])); ?>
                        </p>

                    </div>
                </article>

            <?php endforeach; ?>

            <nav class="d-flex gap-3 mt-4" aria-label="Páginas de reservas">

                <?php if ($pagina > 1): ?>
                    <a
                        class="btn btn-outline-primary"
                        href="mis_reservas.php?pagina=<?php echo $pagina - 1; ?>"
                    >
                        Anterior
                    </a>
                <?php endif; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a
                        class="btn btn-outline-primary"
                        href="mis_reservas.php?pagina=<?php echo $pagina + 1; ?>"
                    >
                        Siguiente
                    </a>
                <?php endif; ?>

            </nav>

        <?php endif; ?>

    </main>

    <footer class="bg-light border-top py-4">
        <nav class="container" aria-label="Mapa del sitio">

            <h2 class="h6">Mapa del sitio</h2>

            <a href="index.php" class="me-3">Inicio</a>
            <a href="vuelos.php" class="me-3">Vuelos</a>
            <a href="mi_cuenta.php" class="me-3">Mi cuenta</a>
            <a href="mis_reservas.php">Mis reservas</a>

            <p class="mt-3 mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>

        </nav>
    </footer>

</body>
</html>