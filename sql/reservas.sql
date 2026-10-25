USE viajar;
CREATE TABLE IF NOT EXISTS reservas (
  codReserva INT AUTO_INCREMENT PRIMARY KEY,
  codUsuario INT NOT NULL,
  codVuelo INT NOT NULL,
  cantidadPasajeros TINYINT UNSIGNED NOT NULL,
  precioUnitario DECIMAL(10, 2) NOT NULL,
  importeTotal DECIMAL(12, 2) NOT NULL,
  fechaReserva DATETIME NOT NULL,
  estadoReserva ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  tokenReserva CHAR(64) NOT NULL UNIQUE,
  FOREIGN KEY (codUsuario) REFERENCES usuarios(codUsuario),
  FOREIGN KEY (codVuelo) REFERENCES vuelos(codVuelo)
) ENGINE = InnoDB;