<?php
include_once("../config.php");
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Modo desarrollo - cambia a false en producción
$modo_desarrollo = false;

try {
    // 1. Establecer conexión PDO
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'];
    $email = $data['email'];

    // 3. Verificar usuario y correo
    $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE usuario = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user['email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'El correo no coincide con el usuario registrado']);
        exit;
    }

    // 4. Generar y guardar token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("UPDATE usuarios SET reset_token = :token, reset_expires = :expires WHERE id = :id");
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':expires', $expires);
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();

    // 5. Configurar URL base
    $base_url = ($_SERVER['HTTP_HOST'] == 'localhost:8000') 
        ? 'http://localhost:8000/login' 
        : 'http://172.16.1.58/Tinkuy/login';

    $reset_link = "$base_url/reset_password.php?token=$token";

    // 6. Modo desarrollo: Guardar el correo en archivo
    if ($modo_desarrollo) {
        file_put_contents("debug_email.html", "
        <html>
        <head>
            <title>Restablecimiento de contraseña - DEBUG</title>
        </head>
        <body>
            <h1>Correo de prueba (modo desarrollo)</h1>
            <p>En producción esto sería enviado a: $email</p>
            <hr>
            <p>Enlace de reset: <a href='$reset_link'>$reset_link</a></p>
            <p>Token: $token</p>
            <p>Expira: $expires</p>
        </body>
        </html>
        ");
        
        echo json_encode([
            'success' => true,
            'message' => 'Modo desarrollo: Correo guardado en debug_email.html',
            'reset_link' => $reset_link
        ]);
        exit;
    }

    // 7. Configuración PHPMailer PARA PRODUCCIÓN
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP principal
        $mail->isSMTP();
        $mail->Host = 'mail.undav.edu.ar'; // Servidor oficial UNDAV
        $mail->SMTPAuth = true;
        $mail->Username = 'sudocu@undav.edu.ar';
        $mail->Password = 'tu_contraseña'; 
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
        $mail->Port = 25;
        $mail->Timeout = 15;
        
        // Opciones SSL
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Configurar email
        $mail->setFrom('sudocu@undav.edu.ar', 'Sistema Tinkuy');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Restablecimiento de contraseña - Tinkuy";
        
        // Cuerpo del mensaje
        $mail->Body = "
        <html>
        <head>
            <title>Restablecimiento de contraseña</title>
        </head>
        <body>
            <p>Hemos recibido una solicitud para restablecer tu contraseña en Tinkuy.</p>
            <p>Para continuar, haz clic en el siguiente enlace:</p>
            <p><a href='$reset_link'>$reset_link</a></p>
            <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
            <p>El enlace expirará en 1 hora.</p>
            <p>Atentamente,<br>Equipo de Tinkuy</p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Para restablecer tu contraseña, visita: $reset_link";

        // Intento de conexión SMTP
        if(!$mail->smtpConnect()) {
            throw new Exception('Error SMTP: '.$mail->ErrorInfo);
        }

        // Enviar correo
        if($mail->send()) {
            echo json_encode(['success' => true, 'message' => 'Correo enviado con éxito']);
        } else {
            throw new Exception('Error al enviar: '.$mail->ErrorInfo);
        }

    } catch (Exception $e) {
        // Intento con configuración alternativa si falla la principal
        try {
            $mail->Host = '172.16.1.25'; // IP alternativa del servidor
            $mail->Port = 587; // Puerto alternativo
            
            if($mail->send()) {
                echo json_encode(['success' => true, 'message' => 'Correo enviado (usando servidor alternativo)']);
            } else {
                throw new Exception('Error alternativo: '.$mail->ErrorInfo);
            }
        } catch (Exception $altException) {
            throw new Exception('Falló configuración principal y alternativa: '.$altException->getMessage());
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el proceso: ' . $e->getMessage(),
        'debug' => [
            'smtp_error' => isset($mail) ? $mail->ErrorInfo : null,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>