CREATE DATABASE IF NOT EXISTS viajar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE viajar;
-- Aerolíneas disponibles en la plataforma.
CREATE TABLE IF NOT EXISTS aerolineas (
  codAerolinea INT AUTO_INCREMENT PRIMARY KEY,
  nombreAerolinea VARCHAR(100) NOT NULL,
  codigoIATA CHAR(2) NOT NULL UNIQUE,
  descripcionAerolinea VARCHAR(255) NOT NULL,
  codPais CHAR(3) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE = InnoDB;
-- Cada vuelo pertenece a una aerolínea.
CREATE TABLE IF NOT EXISTS vuelos (
  codVuelo INT AUTO_INCREMENT PRIMARY KEY,
  codAerolinea INT NOT NULL,
  origenVuelo VARCHAR(100) NOT NULL,
  destinoVuelo VARCHAR(100) NOT NULL,
  fechaSalidaVuelo DATE NOT NULL,
  horaSalidaVuelo TIME NOT NULL,
  precioVuelo DECIMAL(10, 2) NOT NULL,
  asientosTotales INT NOT NULL,
  asientosDisponibles INT NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (codAerolinea) REFERENCES aerolineas(codAerolinea)
) ENGINE = InnoDB;