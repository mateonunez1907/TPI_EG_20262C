// Estas acciones solamente sirven para recorrer los bocetos.
function mostrarAviso(mensaje) {
    const aviso = document.getElementById("aviso");
    aviso.textContent = mensaje;
    aviso.focus();
}

document.querySelectorAll("form").forEach(function (formulario) {
    formulario.addEventListener("submit", function (evento) {
        evento.preventDefault();

        const clave = document.getElementById("clave");
        const repetir = document.getElementById("repetir");

        if (repetir && clave.value !== repetir.value) {
            mostrarAviso("Las contraseñas no coinciden. Revisá los campos.");
            repetir.focus();
            return;
        }

        if (formulario.dataset.destino) {
            window.location.href = formulario.dataset.destino;
        } else {
            mostrarAviso(formulario.dataset.mensaje);
        }
    });
});

document.querySelectorAll("button[data-mensaje]").forEach(function (boton) {
    boton.addEventListener("click", function () {
        mostrarAviso(boton.dataset.mensaje);
    });
});