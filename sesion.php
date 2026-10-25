<?php

date_default_timezone_set("America/Argentina/Cordoba");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        "use_strict_mode" => 1,
        "cookie_httponly" => true,
        "cookie_samesite" => "Lax",
        "cookie_secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off"
    ]);
}

if (empty($_SESSION["tokenSesion"])) {
    $_SESSION["tokenSesion"] = bin2hex(random_bytes(32));
}

function cerrarSesion() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $datosCookie = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $datosCookie["path"],
            "domain" => $datosCookie["domain"],
            "secure" => $datosCookie["secure"],
            "httponly" => $datosCookie["httponly"],
            "samesite" => $datosCookie["samesite"]
        ]);
    }

    session_destroy();
}

function obtenerUsuarioSesion($conexion) {
    $codUsuario = filter_var(
        $_SESSION["codUsuario"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($codUsuario === false) {
        return null;
    }

    $sql = "SELECT codUsuario, nombreUsuario, mailUsuario,
                   tipoUsuario, codAerolinea, correoVerificado,
                   estadoCEO, activo
            FROM usuarios
            WHERE codUsuario = ?";

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($consulta, "i", $codUsuario);
    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);
    $usuario = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($consulta);

    if (!$usuario
        || (int) $usuario["activo"] !== 1
        || (int) $usuario["correoVerificado"] !== 1
        || !in_array(
            $usuario["tipoUsuario"],
            ["pasajero", "ceo", "administrador"],
            true
        )) {
        return null;
    }

    if ($usuario["tipoUsuario"] === "ceo"
        && $usuario["estadoCEO"] !== "aprobado") {
        return null;
    }

    return $usuario;
}

function exigirUsuario($conexion) {
    $usuario = obtenerUsuarioSesion($conexion);

    if (!$usuario) {
        if (isset($_SESSION["codUsuario"])) {
            cerrarSesion();
        }

        header("Location: iniciar_sesion.php");
        exit;
    }

    return $usuario;
}