<?php
/**
 * reserve_flight.php - Servicio de Reserva de Vuelos
 * POST: user_id, flight_id
 * Valida que el vuelo exista y tenga asientos disponibles antes de reservar,
 * y descuenta un asiento disponible (simulando el "pago"/apartado del lugar).
 */
require __DIR__ . '/db.php';

$input     = get_input();
$user_id   = (int) ($input['user_id'] ?? 0);
$flight_id = (int) ($input['flight_id'] ?? 0);

if ($user_id <= 0 || $flight_id <= 0) {
    json_response(false, 'Debes iniciar sesión y elegir un vuelo válido.');
}

// Verificar que el usuario exista
$stmt = $conn->prepare('SELECT user_id FROM Users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    json_response(false, 'Usuario no válido. Inicia sesión de nuevo.');
}
$stmt->close();

// Verificar disponibilidad del vuelo
$stmt = $conn->prepare('SELECT seats_available FROM Flights WHERE flight_id = ? FOR UPDATE');
$conn->begin_transaction();
$stmt->bind_param('i', $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->rollback();
    json_response(false, 'El vuelo seleccionado no existe.');
}

$flight = $result->fetch_assoc();
if ($flight['seats_available'] <= 0) {
    $conn->rollback();
    json_response(false, 'Ya no hay asientos disponibles para este vuelo.');
}
$stmt->close();

// Crear la reserva y descontar el asiento
$stmt = $conn->prepare('INSERT INTO Reservations (user_id, flight_id) VALUES (?, ?)');
$stmt->bind_param('ii', $user_id, $flight_id);

if ($stmt->execute()) {
    $update = $conn->prepare('UPDATE Flights SET seats_available = seats_available - 1 WHERE flight_id = ?');
    $update->bind_param('i', $flight_id);
    $update->execute();
    $update->close();

    $conn->commit();
    json_response(true, 'Reserva realizada con éxito.', ['reservation_id' => $stmt->insert_id]);
} else {
    $conn->rollback();
    json_response(false, 'Error al reservar: ' . $conn->error);
}
$stmt->close();
