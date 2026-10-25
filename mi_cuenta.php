<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuentas.php";

header("Cache-Control: no-store");

try {
    $usuario = exigirUsuario($conexion);

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);

    exit("No pudimos consultar tu cuenta. Intentá nuevamente más tarde.");
}

mysqli_close($conexion);

$nombresTipos = [
    "pasajero" => "Pasajero",
    "ceo" => "CEO de aerolínea",
    "administrador" => "Administrador"
];

$tipoCuenta = $nombresTipos[$usuario["tipoUsuario"]];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Mi cuenta</title>

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

                <ul class="navbar-nav flex-row flex-wrap align-items-center gap-3">

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
                           href="mi_cuenta.php"
                           aria-current="page">
                            Mi cuenta
                        </a>
                    </li>

                    <li class="nav-item">

                        <form method="post" action="cerrar_sesion.php">

                            <input
                                type="hidden"
                                name="token"
                                value="<?php echo textoSeguro($_SESSION["tokenSesion"]); ?>"
                            >

                            <button type="submit"
                                    class="btn btn-outline-light btn-sm">
                                Cerrar sesión
                            </button>

                        </form>

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
                    Mi cuenta
                </li>

            </ol>
        </nav>

        <h1>Mi cuenta</h1>

        <p class="lead">
            Hola, <?php echo textoSeguro($usuario["nombreUsuario"]); ?>.
        </p>

        <section class="card my-4" aria-labelledby="titulo-datos">

            <div class="card-body">

                <h2 id="titulo-datos" class="h4">
                    Datos de tu cuenta
                </h2>

                <dl class="row mb-0">

                    <dt class="col-sm-4">Nombre y apellido</dt>

                    <dd class="col-sm-8">
                        <?php echo textoSeguro($usuario["nombreUsuario"]); ?>
                    </dd>

                    <dt class="col-sm-4">Correo electrónico</dt>

                    <dd class="col-sm-8">
                        <?php echo textoSeguro($usuario["mailUsuario"]); ?>
                    </dd>

                    <dt class="col-sm-4">Tipo de cuenta</dt>

                    <dd class="col-sm-8">
                        <?php echo textoSeguro($tipoCuenta); ?>
                    </dd>

                    <dt class="col-sm-4">Correo verificado</dt>

                    <dd class="col-sm-8">Sí</dd>

                </dl>

            </div>

        </section>

        <a href="vuelos.php" class="btn btn-primary">
            Buscar vuelos
        </a>

        <a href="index.php" class="btn btn-outline-secondary">
            Volver al inicio
        </a>

    </main>

    <footer class="bg-light border-top py-4">

        <div class="container">

            <nav aria-label="Mapa del sitio">

                <h2 class="h6">Mapa del sitio</h2>

                <a href="index.php" class="me-3">Inicio</a>
                <a href="vuelos.php" class="me-3">Vuelos</a>
                <a href="mi_cuenta.php">Mi cuenta</a>

            </nav>

            <p class="mt-3 mb-0">
                &copy; <?php echo date("Y"); ?> ViajAR
            </p>

        </div>

    </footer>

</body>
</html>