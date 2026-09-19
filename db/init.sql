-- init.sql
-- Este script se ejecuta automáticamente al crear el contenedor de MySQL
-- (Docker monta este archivo en /docker-entrypoint-initdb.d/, y la base
-- de datos indicada en MYSQL_DATABASE ya existe y está seleccionada).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Flights (
    flight_id INT AUTO_INCREMENT PRIMARY KEY,
    airline VARCHAR(80) NOT NULL DEFAULT 'Aerolínea Genérica',
    origin VARCHAR(50) NOT NULL,
    destination VARCHAR(50) NOT NULL,
    departure_date DATE NOT NULL,
    return_date DATE,
    price DECIMAL(10, 2) NOT NULL,
    seats_available INT NOT NULL DEFAULT 50
);

CREATE TABLE IF NOT EXISTS Reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmada',
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id) ON DELETE CASCADE
);

-- Datos de ejemplo para poder probar la búsqueda de vuelos sin capturar nada a mano
INSERT INTO Flights (airline, origin, destination, departure_date, return_date, price, seats_available) VALUES
('Aeroméxico',      'Ciudad Juarez', 'Ciudad de Mexico', '2026-10-05', '2026-10-12', 2450.00, 40),
('Volaris',         'Ciudad Juarez', 'Guadalajara',      '2026-10-06', NULL,        1890.50, 35),
('Viva Aerobus',    'Chihuahua',     'Ciudad de Mexico', '2026-10-08', '2026-10-15', 2100.00, 20),
('Aeroméxico',      'Ciudad de Mexico', 'Cancun',        '2026-11-01', '2026-11-08', 3200.00, 15),
('Volaris',         'Monterrey',     'Tijuana',          '2026-10-20', NULL,        1650.00, 50),
('Viva Aerobus',    'Ciudad Juarez', 'Monterrey',        '2026-10-10', '2026-10-14', 1490.00, 30);
