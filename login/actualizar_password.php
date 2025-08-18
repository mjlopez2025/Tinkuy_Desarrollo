<?php
include_once("../config.php");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = file_get_contents('php://input');
    if (strlen($input) > 0 && json_decode($input) !== null) {
        $_POST = json_decode($input, true);
    }

    // Validar campos
    if (empty($_POST['token']) || empty($_POST['email']) || empty($_POST['new-password']) || empty($_POST['confirm-password'])) {
        throw new Exception('Todos los campos son requeridos');
    }

    if ($_POST['new-password'] !== $_POST['confirm-password']) {
        throw new Exception('Las contraseñas no coinciden');
    }

    if (strlen($_POST['new-password']) < 8) {
        throw new Exception('La contraseña debe tener al menos 8 caracteres');
    }

    $conn = connectDB($config_tinkuy);

    // Verificar token y tiempo
    $sql = "SELECT id FROM usuarios WHERE email = :email AND reset_token = :token AND token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':email' => $_POST['email'], ':token' => $_POST['token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Enlace inválido o expirado');
    }

    // Actualizar contraseña
    $newHash = password_hash($_POST['new-password'], PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET password = :password, reset_token = NULL, token_expiry = NULL WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':password' => $newHash, ':email' => $_POST['email']]);

    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>