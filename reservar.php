<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

header("Cache-Control: no-store");

function consultarVueloReserva($conexion, $codVuelo, $pasajeros, $bloquear = false) {
    $sql = "SELECT v.codVuelo, v.origenVuelo, v.destinoVuelo,
                   v.fechaSalidaVuelo, v.horaSalidaVuelo,
                   v.precioVuelo, v.asientosDisponibles,
                   v.activo AS vueloActivo,
                   a.nombreAerolinea, a.activo AS aerolineaActiva,
                   v.precioVuelo * ? AS importeTotal
            FROM vuelos AS v
            INNER JOIN aerolineas AS a
                ON v.codAerolinea = a.codAerolinea
            WHERE v.codVuelo = ?";

    if ($bloquear) {
        $sql .= " FOR UPDATE";
    }

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($consulta, "ii", $pasajeros, $codVuelo);
    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);
    $vuelo = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($consulta);

    return $vuelo;
}

function vueloReservable($vuelo, $pasajeros) {
    return $vuelo
        && (int) $vuelo["vueloActivo"] === 1
        && (int) $vuelo["aerolineaActiva"] === 1
        && $vuelo["fechaSalidaVuelo"] > date("Y-m-d")
        && (int) $vuelo["asientosDisponibles"] >= $pasajeros;
}

$errores = [];
$vuelo = null;
$codVuelo = false;
$pasajeros = false;

$esPost = $_SERVER["REQUEST_METHOD"] === "POST";

try {
    $usuario = exigirUsuario($conexion);

    if ($usuario["tipoUsuario"] !== "pasajero") {
        http_response_code(403);

        exit("Solo las cuentas de pasajero pueden crear reservas.");
    }

    $datos = $_GET;

    if ($esPost) {
        $token = $_POST["tokenReserva"] ?? "";
        $formulario = $_SESSION["formularioReserva"] ?? null;

        if (!is_string($token)
            || !is_array($formulario)
            || !is_string($formulario["tokenReserva"] ?? null)
            || !hash_equals($formulario["tokenReserva"], $token)
            || $formulario["codUsuario"] !== (int) $usuario["codUsuario"]) {

            $errores[] = "El formulario venció. Volvé a elegir el vuelo.";

        } else {
            // Recuperamos los datos que se mostraron en el resumen.
            $datos = $formulario;
        }
    }

    if (empty($errores)) {
        $codVuelo = filter_var(
            $datos["codVuelo"] ?? "",
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $pasajeros = filter_var(
            $datos["pasajeros"] ?? "1",
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 10]]
        );

        if ($codVuelo === false || $pasajeros === false) {
            $errores[] = "El vuelo o la cantidad de pasajeros no son válidos.";
        }
    }

    if (empty($errores)) {
        if ($esPost) {
            mysqli_begin_transaction($conexion);

            // Bloqueamos el vuelo mientras registramos la reserva.
            $vuelo = consultarVueloReserva(
                $conexion,
                $codVuelo,
                $pasajeros,
                true
            );

            $codUsuario = (int) $usuario["codUsuario"];
            $tokenProtegido = hash("sha256", $token);

            // Evitamos crear otra reserva si se repite el envío.
            $sql = "SELECT codReserva
                    FROM reservas
                    WHERE tokenReserva = ? AND codUsuario = ?";

            $consulta = mysqli_prepare($conexion, $sql);

            mysqli_stmt_bind_param(
                $consulta,
                "si",
                $tokenProtegido,
                $codUsuario
            );

            mysqli_stmt_execute($consulta);

            $resultado = mysqli_stmt_get_result($consulta);
            $existente = mysqli_fetch_assoc($resultado);

            mysqli_stmt_close($consulta);

            if ($existente) {
                mysqli_commit($conexion);

                $_SESSION["reservaCreada"] = (int) $existente["codReserva"];

                mysqli_close($conexion);

                header("Location: mis_reservas.php");
                exit;
            }

            if (!vueloReservable($vuelo, $pasajeros)) {
                $errores[] = "El vuelo ya no tiene disponibilidad para esta reserva.";

            } elseif ($vuelo["precioVuelo"] !== $formulario["precioUnitario"]) {
                $errores[] = "El precio cambió. Revisá el nuevo importe antes de confirmar.";

            } else {
                $hoy = date("Y-m-d");

                $sql = "UPDATE vuelos
                        SET asientosDisponibles = asientosDisponibles - ?
                        WHERE codVuelo = ? AND activo = 1
                          AND fechaSalidaVuelo > ?
                          AND asientosDisponibles >= ?";

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

                $actualizados = mysqli_stmt_affected_rows($consulta);

                mysqli_stmt_close($consulta);

                if ($actualizados !== 1) {
                    $errores[] = "No pudimos reservar los asientos. Intentá nuevamente.";

                } else {
                    $precioUnitario = $vuelo["precioVuelo"];
                    $importeTotal = $vuelo["importeTotal"];
                    $fechaReserva = date("Y-m-d H:i:s");

                    $sql = "INSERT INTO reservas
                                (codUsuario, codVuelo, cantidadPasajeros,
                                 precioUnitario, importeTotal, fechaReserva,
                                 tokenReserva)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";

                    $consulta = mysqli_prepare($conexion, $sql);

                    mysqli_stmt_bind_param(
                        $consulta,
                        "iiissss",
                        $codUsuario,
                        $codVuelo,
                        $pasajeros,
                        $precioUnitario,
                        $importeTotal,
                        $fechaReserva,
                        $tokenProtegido
                    );

                    mysqli_stmt_execute($consulta);

                    $codReserva = mysqli_insert_id($conexion);

                    mysqli_stmt_close($consulta);
                    mysqli_commit($conexion);

                    $_SESSION["reservaCreada"] = (int) $codReserva;

                    mysqli_close($conexion);

                    header("Location: mis_reservas.php");
                    exit;
                }
            }

            mysqli_rollback($conexion);

        } else {
            $vuelo = consultarVueloReserva(
                $conexion,
                $codVuelo,
                $pasajeros
            );
        }

        if (!vueloReservable($vuelo, $pasajeros) && empty($errores)) {
            $errores[] = "El vuelo no está disponible para esa cantidad de pasajeros.";
        }
    }

} catch (mysqli_sql_exception $error) {
    mysqli_rollback($conexion);

    error_log($error->getMessage());

    http_response_code(500);

    $vuelo = null;
    $errores[] = "No pudimos procesar la reserva. Intentá nuevamente.";
}

$mostrarFormulario = vueloReservable($vuelo, $pasajeros);

if ($mostrarFormulario) {
    $_SESSION["formularioReserva"] = [
        "tokenReserva" => bin2hex(random_bytes(32)),
        "codUsuario" => (int) $usuario["codUsuario"],
        "codVuelo" => $codVuelo,
        "pasajeros" => $pasajeros,
        "precioUnitario" => $vuelo["precioVuelo"]
    ];
}

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Crear reserva</title>

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
            <a class="text-white" href="mis_reservas.php">Mis reservas</a>

        </nav>
    </header>

    <main id="contenido" class="container py-5 flex-grow-1">

        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="vuelos.php">Vuelos</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    Crear reserva
                </li>
            </ol>
        </nav>

        <h1>Crear reserva</h1>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo textoSeguro($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($mostrarFormulario): ?>

            <section class="card my-4" aria-labelledby="titulo-resumen">
                <div class="card-body">

                    <h2 id="titulo-resumen" class="h4">
                        Resumen de la reserva
                    </h2>

                    <p class="fs-5">
                        <?php echo textoSeguro($vuelo["origenVuelo"]); ?>
                        → <?php echo textoSeguro($vuelo["destinoVuelo"]); ?>
                    </p>

                    <p>
                        Aerolínea:
                        <?php echo textoSeguro($vuelo["nombreAerolinea"]); ?>
                    </p>

                    <p>
                        Salida:
                        <?php echo date("d/m/Y", strtotime($vuelo["fechaSalidaVuelo"])); ?>
                        a las
                        <?php echo textoSeguro(substr($vuelo["horaSalidaVuelo"], 0, 5)); ?>
                    </p>

                    <p>Pasajeros: <?php echo (int) $pasajeros; ?></p>

                    <p>
                        Precio por pasajero:
                        $ <?php echo number_format((float) $vuelo["precioVuelo"], 2, ",", "."); ?>
                    </p>

                    <p class="fs-4 fw-bold">
                        Total:
                        $ <?php echo number_format((float) $vuelo["importeTotal"], 2, ",", "."); ?>
                    </p>

                    <p>
                        Al confirmar, tu reserva quedará pendiente de pago.
                    </p>

                    <form method="post" action="reservar.php">

                        <input
                            type="hidden"
                            name="tokenReserva"
                            value="<?php echo textoSeguro($_SESSION["formularioReserva"]["tokenReserva"]); ?>"
                        >

                        <button type="submit" class="btn btn-primary">
                            Confirmar reserva
                        </button>

                        <a href="vuelos.php" class="btn btn-outline-secondary">
                            Volver a vuelos
                        </a>

                    </form>

                </div>
            </section>

        <?php else: ?>

            <a href="vuelos.php" class="btn btn-primary">
                Volver a vuelos
            </a>

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