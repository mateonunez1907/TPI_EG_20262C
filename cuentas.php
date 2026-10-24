<?php

function textoSeguro($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

function eliminarCuentasVencidas($conexion) {
    $ahora = date("Y-m-d H:i:s");

    $sql = "DELETE FROM usuarios
            WHERE correoVerificado = 0
              AND fechaLimiteVerificacion <= ?";

    $consulta = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($consulta, "s", $ahora);
    mysqli_stmt_execute($consulta);
    mysqli_stmt_close($consulta);
}