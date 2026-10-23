USE viajar;
-- Datos ficticios para probar el listado.
-- Ejecutar una sola vez sobre las tablas recién creadas.
START TRANSACTION;
INSERT INTO aerolineas (
    codAerolinea,
    nombreAerolinea,
    codigoIATA,
    descripcionAerolinea,
    codPais
  )
VALUES (
    1,
    'Demo Norte',
    'D1',
    'Aerolínea de prueba del proyecto.',
    'ARG'
  ),
  (
    2,
    'Demo Sur',
    'D2',
    'Aerolínea de prueba del proyecto.',
    'ARG'
  );
INSERT INTO vuelos (
    codVuelo,
    codAerolinea,
    origenVuelo,
    destinoVuelo,
    fechaSalidaVuelo,
    horaSalidaVuelo,
    precioVuelo,
    asientosTotales,
    asientosDisponibles
  )
VALUES (
    1,
    1,
    'Rosario',
    'Buenos Aires',
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    '09:00:00',
    85000.00,
    120,
    120
  ),
  (
    2,
    1,
    'Córdoba',
    'Mendoza',
    DATE_ADD(CURDATE(), INTERVAL 10 DAY),
    '15:30:00',
    110000.00,
    180,
    180
  ),
  (
    3,
    2,
    'Buenos Aires',
    'Bariloche',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    '10:15:00',
    145000.00,
    150,
    150
  );
COMMIT;