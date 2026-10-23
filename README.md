# ViajAR

Trabajo práctico de Entornos Gráficos.
Universidad Tecnológica Nacional, Facultad Regional Rosario.

El proyecto consiste en una plataforma para consultar vuelos
y gestionar reservas de pasajes aéreos.

## Tecnologías utilizadas

- PHP.
- MySQL.
- HTML.
- Bootstrap 5.3.8.

## Ejecución local

1. Instalar MAMP.
2. Seleccionar la carpeta del repositorio como Document root.
3. Iniciar Apache y MySQL desde MAMP.
4. Abrir phpMyAdmin y ejecutar el contenido de sql/base_datos.sql.
5. Copiar config/ejemplo.php como config/local.php.
6. Ajustar los datos de conexión en config/local.php.
7. Para cargar ejemplos en una base vacía, ejecutar una sola vez
   el contenido de sql/datos_prueba.sql desde phpMyAdmin.
8. Abrir http://localhost:8888/ en el navegador.

La configuración inicial utiliza los puertos predeterminados
de MAMP: 8888 para Apache y 8889 para MySQL.

Bootstrap se carga mediante internet.

## Archivos principales

- index.php: página de inicio.
- vuelos.php: listado de vuelos disponibles.
- conexion.php: conexión con MySQL.
- config/ejemplo.php: configuración de conexión de ejemplo.
- config/local.php: configuración local, excluida de Git.
- sql/base_datos.sql: creación de la base de datos y sus tablas.
- sql/datos_prueba.sql: aerolíneas y vuelos ficticios para pruebas.
- README.md: documentación del proyecto.
- .gitignore: archivos excluidos del seguimiento de Git.

## Funcionalidades implementadas

- Página de inicio y presentación de ViajAR.
- Navegación entre las páginas disponibles.
- Mapa del sitio en el pie de página.
- Año del pie generado mediante PHP.
- Base de datos con tablas de aerolíneas y vuelos.
- Conexión de PHP con MySQL.
- Listado de vuelos obtenido desde la base de datos.
- Visualización de recorrido, aerolínea, fecha, horario,
  precio por pasajero y asientos disponibles.
- Mensaje cuando no existen vuelos disponibles.

El listado muestra vuelos activos de aerolíneas activas,
con asientos disponibles y fecha de salida posterior al día actual.