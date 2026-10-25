<?php

require_once __DIR__ . "/sesion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    header("Allow: POST");

    exit("Para salir, usá el botón Cerrar sesión del sitio.");
}

$token = $_POST["token"] ?? "";

if (!is_string($token)
    || !hash_equals($_SESSION["tokenSesion"], $token)) {
    http_response_code(403);

    exit("El formulario venció. Volvé al sitio e intentá nuevamente.");
}

cerrarSesion();

header("Location: iniciar_sesion.php?salida=1");
exit;