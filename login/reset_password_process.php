<?php
header('Content-Type: application/json');
include_once("../config.php");

// Establecer conexión
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$token = $conn->real_escape_string($data['token']);
$new_password = $conn->real_escape_string($data['new_password']);

// Verificar token y que no haya expirado
$query = "SELECT id FROM usuarios WHERE reset_token = '$token' AND reset_expires > NOW()";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o expirado. Por favor solicita un nuevo enlace.']);
    exit;
}

$user = $result->fetch_assoc();

// Hashear la nueva contraseña (usando password_hash)
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Actualizar contraseña y limpiar token
$update = "UPDATE usuarios SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE id = {$user['id']}";
if ($conn->query($update)) {
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
}

$conn->close();
?>