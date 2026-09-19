<?php
/**
 * auth.php - Servicio de Autenticación
 * Acciones soportadas (POST):
 *   - action=register  -> username, password, email
 *   - action=login     -> username, password
 */
require __DIR__ . '/db.php';
session_start();

$input  = get_input();
$action = $input['action'] ?? '';

if ($action === 'register') {
    $user  = trim($input['username'] ?? '');
    $pass  = $input['password'] ?? '';
    $email = trim($input['email'] ?? '');

    if ($user === '' || $pass === '' || $email === '') {
        json_response(false, 'Todos los campos son obligatorios.');
    }

    $hashed = password_hash($pass, PASSWORD_BCRYPT);

    $stmt = $conn->prepare('INSERT INTO Users (username, password, email) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $user, $hashed, $email);

    if ($stmt->execute()) {
        json_response(true, 'Registro exitoso. Ahora puedes iniciar sesión.');
    } else {
        if ($conn->errno === 1062) { // entrada duplicada
            json_response(false, 'El usuario o el correo ya están registrados.');
        }
        json_response(false, 'Error al registrar: ' . $conn->error);
    }
    $stmt->close();
}

if ($action === 'login') {
    $user = trim($input['username'] ?? '');
    $pass = $input['password'] ?? '';

    $stmt = $conn->prepare('SELECT user_id, username, password FROM Users WHERE username = ?');
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id']  = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            json_response(true, 'Inicio de sesión exitoso.', [
                'user_id'  => $row['user_id'],
                'username' => $row['username']
            ]);
        } else {
            json_response(false, 'Contraseña incorrecta.');
        }
    } else {
        json_response(false, 'El usuario no existe.');
    }
    $stmt->close();
}

json_response(false, 'Acción no reconocida.');
