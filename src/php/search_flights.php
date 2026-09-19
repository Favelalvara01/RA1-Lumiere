<?php
/**
 * search_flights.php - Servicio de Búsqueda de Vuelos
 * POST: origin, destination (cualquiera de los dos puede ir vacío para
 * traer todos los vuelos que coincidan con el campo que sí se llenó).
 */
require __DIR__ . '/db.php';

$input       = get_input();
$origin      = trim($input['origin'] ?? '');
$destination = trim($input['destination'] ?? '');

$sql = 'SELECT * FROM Flights WHERE 1=1';
$types = '';
$params = [];

if ($origin !== '') {
    $sql .= ' AND origin LIKE ?';
    $types .= 's';
    $params[] = '%' . $origin . '%';
}
if ($destination !== '') {
    $sql .= ' AND destination LIKE ?';
    $types .= 's';
    $params[] = '%' . $destination . '%';
}
$sql .= ' ORDER BY departure_date ASC';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$flights = [];
while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

echo json_encode(['success' => true, 'flights' => $flights]);
$stmt->close();
