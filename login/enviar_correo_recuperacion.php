<?php
include_once("../config.php"); // tu conexión PDO
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    // Buscar usuario
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
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
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'maxijlopez2101@gmail.com';  // cambiar
            $mail->Password   = 'oqjo qnnw lmra xtnu'; // cambiar
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('Tinkuy@undav.edu.ar', 'Soporte Tinkuy');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Restablecer contraseña";
            $enlace = "http://localhost/Tinkuy_Desarrollo/login/restablecer_contrasena.php?token=" . $token;
            $mail->Body = "Hola,<br><br>Haz clic en el siguiente enlace para restablecer tu contraseña:<br>
                           <a href='$enlace'>$enlace</a><br><br>
                           Este enlace expira en 1 hora.";

            $mail->send();
            header("Location: index.html?msg=correo_enviado");
        } catch (Exception $e) {
            echo "Error al enviar correo: {$mail->ErrorInfo}";
        }
    } else {
        echo "No existe un usuario con ese correo.";
    }
}
