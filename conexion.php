<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$rutaConfiguracion = __DIR__ . "/config/local.php";

if (!file_exists($rutaConfiguracion)) {
    http_response_code(500);
    exit("Falta crear el archivo config/local.php.");
}

require $rutaConfiguracion;

try {
    $conexion = mysqli_connect(
        $servidor,
        $usuario,
        $clave,
        $baseDatos,
        $puerto
    );

    mysqli_set_charset($conexion, "utf8mb4");

} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());

    http_response_code(500);
    exit("No se pudo establecer la conexión con la base de datos.");
}