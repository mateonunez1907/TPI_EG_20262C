<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/librerias/PHPMailer/src/Exception.php";
require_once __DIR__ . "/librerias/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/librerias/PHPMailer/src/SMTP.php";

function enviarCorreo($destinatario, $asunto, $mensaje) {
    $rutaConfiguracion = __DIR__ . "/config/correo.local.php";

    if (!file_exists($rutaConfiguracion)) {
        error_log("Falta crear config/correo.local.php.");
        return false;
    }

    require $rutaConfiguracion;

    $correo = new PHPMailer(true);

    try {
        $correo->isSMTP();
        $correo->Host = "smtp.gmail.com";
        $correo->SMTPAuth = true;
        $correo->Username = $correoUsuario;
        $correo->Password = $correoClave;
        $correo->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $correo->Port = 465;
        $correo->CharSet = "UTF-8";
        $correo->Timeout = 15;
        $correo->SMTPDebug = 0;

        $correo->setFrom($correoUsuario, "ViajAR");
        $correo->addAddress($destinatario);

        $correo->isHTML(false);
        $correo->Subject = $asunto;
        $correo->Body = $mensaje;

        $correo->send();
        return true;

    } catch (Exception $error) {
        error_log("No se pudo enviar un correo: " . $error->getMessage());
        return false;
    }
}