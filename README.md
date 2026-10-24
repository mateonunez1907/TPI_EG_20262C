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
- vuelos.php: buscador y listado paginado de vuelos.
- detalle_vuelo.php: información y tarifa del vuelo seleccionado.
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
- Búsqueda por origen, destino, fecha y cantidad de pasajeros.
- Coincidencias parciales para los nombres de las ciudades.
- Validación del formulario en el navegador y en PHP.
- Conservación de los filtros y opción para limpiar la búsqueda.
- Mensaje cuando no existen vuelos para los filtros seleccionados.
- Paginación con dos vuelos por página.
- Botones para avanzar y retroceder conservando los filtros.
- Indicador de página actual y cantidad total de resultados.
- Control de números de página inválidos o fuera de rango.
- Página de detalle de cada vuelo.
- Cálculo de la tarifa publicada por la cantidad de pasajeros.
- Comprobación de disponibilidad al consultar el detalle.
- Regreso a los resultados conservando los filtros y la página.
- Mensajes para códigos inválidos y vuelos no disponibles.

El listado y el detalle muestran vuelos activos de aerolíneas
activas, con fecha de salida posterior al día actual y asientos
suficientes para la cantidad solicitada.

La cantidad de pasajeros debe ser un número entero entre 1 y 10.
Los filtros de origen, destino y fecha son opcionales.

El importe del detalle se calcula con la tarifa publicada.
La aplicación de promociones se incorporará en un avance posterior.