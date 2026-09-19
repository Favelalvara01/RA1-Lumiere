<?php
/**
 * db.php - Conexión centralizada a MySQL para todos los servicios web.
 *
 * Los valores se toman de variables de entorno (definidas en docker-compose.yml
 * o en la configuración de Google Cloud) para no dejar credenciales fijas en el
 * código. Si no existen, se usan valores por defecto pensados para desarrollo
 * local con Docker.
 */

header('Content-Type: application/json; charset=utf-8');

$servername = getenv('DB_HOST') ?: 'db';
$username   = getenv('DB_USER') ?: 'flight_user';
$password   = getenv('DB_PASS') ?: 'flight_pass';
$dbname     = getenv('DB_NAME') ?: 'flight_reservation';

mysqli_report(MYSQLI_REPORT_OFF); // manejamos los errores nosotros mismos

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

/**
 * Lee el cuerpo de la petición sin importar si llegó como
 * application/x-www-form-urlencoded (form clásico) o application/json (fetch).
 */
function get_input(): array {
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(bool $success, string $message, array $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}
