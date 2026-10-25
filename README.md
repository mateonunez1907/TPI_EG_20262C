# ViajAR

Trabajo práctico de Entornos Gráficos.
Universidad Tecnológica Nacional, Facultad Regional Rosario.

Plataforma para consultar vuelos y gestionar reservas de pasajes aéreos.

## Tecnologías utilizadas

- PHP con soporte para Argon2id.
- MySQL.
- HTML y JavaScript.
- Bootstrap 5.3.8.
- PHPMailer 7.1.1.

Las pruebas locales utilizan PHP 8.3.30 y MAMP.

## Ejecución local

1. Instalar MAMP.
2. Seleccionar la carpeta del repositorio como Document root.
3. Iniciar Apache y MySQL.
4. Abrir phpMyAdmin y ejecutar sql/base_datos.sql.
5. Copiar config/ejemplo.php como config/local.php.
6. Completar los datos de conexión con MySQL.
7. Para cargar ejemplos en una base vacía, ejecutar una sola vez
   sql/datos_prueba.sql.
8. Configurar el correo siguiendo el apartado siguiente.
9. Abrir http://localhost:8888/.

Los puertos utilizados son 8888 para Apache y 8889 para MySQL.
Bootstrap se carga mediante internet.

## Configuración del correo

Los correos se envían mediante Gmail y PHPMailer.

1. Activar la verificación en dos pasos en la cuenta de Google.
2. Crear una contraseña de aplicación para ViajAR.
3. Copiar config/correo.ejemplo.php como config/correo.local.php.
4. Completar el correo y la contraseña de aplicación en la copia.

Se utiliza smtp.gmail.com, puerto 465 y cifrado SMTPS.
Las configuraciones locales con claves están excluidas de Git.

Los enlaces de verificación apuntan a http://localhost:8888/.
Deben abrirse en la computadora que ejecuta MAMP.

## Archivos principales

- index.php: inicio y menú según exista una sesión.
- vuelos.php: buscador y listado paginado de vuelos.
- detalle_vuelo.php: detalle y tarifa del vuelo.
- registro.php: registro de pasajeros.
- verificar_correo.php: verificación del correo.
- iniciar_sesion.php: formulario de acceso.
- cerrar_sesion.php: cierre de sesión mediante un formulario.
- mi_cuenta.php: consulta de los datos de la cuenta autenticada.
- sesion.php: configuración y funciones de las sesiones.
- cuentas.php: funciones compartidas de las cuentas.
- conexion.php: conexión con MySQL.
- correo.php: envío de correos.
- config/ejemplo.php: configuración de conexión de ejemplo.
- config/correo.ejemplo.php: configuración de correo de ejemplo.
- sql/base_datos.sql: creación de la base y sus tablas.
- sql/datos_prueba.sql: datos ficticios para pruebas.
- librerias/PHPMailer: librería externa para enviar correos.

## Funcionalidades implementadas

- Inicio, navegación y mapa del sitio en el pie de página.
- Listado de vuelos con información de su aerolínea desde MySQL.
- Búsqueda por origen, destino, fecha y cantidad de pasajeros.
- Coincidencias parciales para las ciudades.
- Validación de filtros en el navegador y en PHP.
- Conservación de filtros y opción para limpiar la búsqueda.
- Paginación con dos vuelos por página.
- Detalle del vuelo y cálculo de la tarifa por cantidad de pasajeros.
- Comprobación de disponibilidad.
- Envío de correos mediante Gmail y PHPMailer.
- Registro de pasajeros con validación de datos.
- Contraseñas protegidas mediante password_hash y Argon2id.
- Control de correos duplicados.
- Verificación del correo con un enlace de un solo uso válido por 24 horas.
- Cancelación del registro si falla el envío del correo.
- Inicio y cierre de sesión.
- Acceso restringido a Mi cuenta.
- Renovación del identificador de sesión al ingresar.
- Comprobación de cuenta activa y correo verificado.
- Comprobación de autorización para las cuentas de CEO.
- Nueva consulta de permisos cada vez que se accede a Mi cuenta.
- Menú de inicio con opciones según exista una sesión.

Los vuelos deben estar activos, pertenecer a una aerolínea activa,
salir después del día actual y tener asientos suficientes.

La cantidad de pasajeros debe ser un entero entre 1 y 10.
El importe del detalle utiliza la tarifa publicada.
La aplicación de promociones se incorporará más adelante.

Las cuentas sin verificar vencen a las 24 horas.
Su limpieza se ejecuta al acceder al registro, verificación o inicio de sesión.
Las cuentas ya verificadas se conservan.

## Librerías externas

PHPMailer: https://github.com/PHPMailer/PHPMailer

Se conserva su archivo LICENSE dentro de librerias/PHPMailer.