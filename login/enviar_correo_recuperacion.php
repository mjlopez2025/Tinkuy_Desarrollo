<?php
include_once("../config.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Establecer cabecera JSON
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$email = trim($_POST['email']);

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

// Buscar usuario
try {
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'No existe un usuario con ese correo.']);
        exit;
    }

    // Generar token
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Guardar en BD
    $stmt = $conn->prepare("UPDATE usuarios 
                            SET reset_token = :token, reset_expires = :expira 
                            WHERE id = :id");
    $stmt->execute([
        ':token' => $token,
        ':expira' => $expira,
        ':id' => $usuario['id']
    ]);

    // Enviar email
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'maxijlopez2101@gmail.com';
    $mail->Password   = 'oqjo qnnw lmra xtnu';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('Tinkuy@undav.edu.ar', 'Soporte Tinkuy');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Restablecer contraseña - Tinkuy";
    $enlace = "http://localhost:8000/login/restablecer_contrasena.php?token=" . $token;
    $mail->Body = "
        <h2>Restablecer contraseña</h2>
        <p>Hola,</p>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <p><a href='$enlace' style='background-color: #3085d6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Restablecer contraseña</a></p>
        <p>O copia este enlace en tu navegador:<br>$enlace</p>
        <p><strong>Este enlace expira en 1 hora.</strong></p>
        <br>
        <p>Saludos,<br>Equipo Tinkuy - UNDAV</p>
    ";

    $mail->send();
    
    // ÉXITO - Devolver JSON
    echo json_encode([
        'success' => true, 
        'message' => 'Se ha enviado un enlace para restablecer tu contraseña a tu correo electrónico.'
    ]);

} catch (Exception $e) {
    // ERROR - Devolver JSON con el error
    echo json_encode([
        'success' => false, 
        'message' => 'Error al enviar el correo: ' . $e->getMessage()
    ]);
}

exit;
?>