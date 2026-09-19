<?php
/**
 * manage_reservations.php - Servicio de Gestión de Reservas
 * POST: action = list | cancel
 *   - list:   user_id
 *   - cancel: user_id, reservation_id
 */
require __DIR__ . '/db.php';

$input   = get_input();
$action  = $input['action'] ?? 'list';
$user_id = (int) ($input['user_id'] ?? 0);

if ($user_id <= 0) {
    json_response(false, 'Falta el usuario.');
}

if ($action === 'cancel') {
    $reservation_id = (int) ($input['reservation_id'] ?? 0);

    $stmt = $conn->prepare('SELECT flight_id FROM Reservations WHERE reservation_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $reservation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        json_response(false, 'Reserva no encontrada.');
    }
    $flight_id = $result->fetch_assoc()['flight_id'];
    $stmt->close();

    $del = $conn->prepare('DELETE FROM Reservations WHERE reservation_id = ?');
    $del->bind_param('i', $reservation_id);
    $del->execute();
    $del->close();

    $upd = $conn->prepare('UPDATE Flights SET seats_available = seats_available + 1 WHERE flight_id = ?');
    $upd->bind_param('i', $flight_id);
    $upd->execute();
    $upd->close();

    json_response(true, 'Reserva cancelada.');
}

// action === 'list' (por defecto)
$sql = 'SELECT r.reservation_id, r.status, r.reservation_date,
               f.flight_id, f.airline, f.origin, f.destination,
               f.departure_date, f.return_date, f.price
        FROM Reservations r
        JOIN Flights f ON f.flight_id = r.flight_id
        WHERE r.user_id = ?
        ORDER BY r.reservation_date DESC';

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

echo json_encode(['success' => true, 'reservations' => $reservations]);
$stmt->close();
