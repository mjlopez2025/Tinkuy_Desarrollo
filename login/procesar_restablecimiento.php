<?php
include_once("../config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'];
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirmar'];

    if ($pass1 !== $pass2) {
        die("Las contraseñas no coinciden.");
    }

    $stmt = $conn->prepare("SELECT id, reset_expires FROM usuarios WHERE reset_token = :token");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();

    if (!$usuario || strtotime($usuario['reset_expires']) < time()) {
        die("Token inválido o expirado.");
    }

    // Actualizar contraseña
    $hash = password_hash($pass1, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios 
                            SET password = :pass, reset_token=NULL, reset_expires=NULL 
                            WHERE id = :id");
    $stmt->execute([
        ':pass' => $hash,
        ':id' => $usuario['id']
    ]);

    header("Location: index.html?msg=correo_enviado");
}
