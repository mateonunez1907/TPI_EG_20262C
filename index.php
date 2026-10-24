<?php

date_default_timezone_set("America/Argentina/Cordoba");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ViajAR - Inicio</title>

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
                        <a class="nav-link active"
                           href="index.php"
                           aria-current="page">
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
                        <a class="nav-link text-white" href="#sobre-viajar">
                            Sobre ViajAR
                        </a>
                    </li>

                </ul>

            </div>
        </nav>
    </header>

    <main id="contenido" class="flex-grow-1">

        <section class="bg-light border-bottom py-5"
                 aria-labelledby="titulo-principal">

            <div class="container">

                <h1 id="titulo-principal" class="text-primary">
                    Tu próximo viaje comienza en ViajAR
                </h1>

                <p class="lead">
                    Una plataforma para buscar vuelos y organizar tus viajes.
                </p>

                <a href="vuelos.php" class="btn btn-primary">
                    Ver vuelos disponibles
                </a>

                <a href="registro.php" class="btn btn-outline-primary">
                    Crear cuenta
                </a>

            </div>
        </section>

        <section id="sobre-viajar"
                 class="container py-5"
                 aria-labelledby="titulo-nosotros">

            <h2 id="titulo-nosotros">Sobre ViajAR</h2>

            <p>
                ViajAR tiene como objetivo reunir en un mismo lugar
                la información de vuelos de distintas aerolíneas
                y facilitar la reserva de pasajes.
            </p>

            <p>
                Buscamos ofrecer una experiencia sencilla,
                con información clara para organizar cada viaje.
            </p>

        </section>

    </main>

    <footer class="bg-light border-top py-4">

        <div class="container">

            <nav aria-label="Mapa del sitio">

                <h2 class="h6">Mapa del sitio</h2>

                <ul class="list-unstyled">

                    <li>
                        <a href="index.php">Inicio</a>
                    </li>

                    <li>
                        <a href="vuelos.php">Vuelos</a>
                    </li>

                    <li>
                        <a href="registro.php">Crear cuenta</a>
                    </li>

                    <li>
                        <a href="#sobre-viajar">Sobre ViajAR</a>
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