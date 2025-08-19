<?php
// Fuerza el código de estado HTTP primero
http_response_code(500); // Valor por defecto si algo falla

// Headers esenciales
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Limpieza agresiva de buffer (borra cualquier salida previa)
while (ob_get_level()) ob_end_clean();

// Incluir dependencias
require_once(__DIR__.'/../config.php');
require __DIR__.'/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Función para respuesta consistente
function sendResponse($success, $message, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

try {
    // 1. Validación estricta del método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Método no permitido', 405);
    }

    // 2. Obtener y validar input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendResponse(false, 'No se recibieron datos', 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, 'Datos JSON inválidos', 400);
    }

    // 3. Validar campos requeridos
    if (empty($data['username']) || empty($data['email'])) {
        sendResponse(false, 'Usuario y email son requeridos', 400);
    }

    // 4. Conexión a BD con verificación explícita
    if (!$conn) {
        sendResponse(false, 'Error de conexión a la base de datos', 500);
    }

    // 5. Consulta segura
    $sql = "SELECT COUNT(*) as count FROM usuarios 
            WHERE usuario = :username AND email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':username' => trim($data['username']),
        ':email' => trim($data['email'])
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || $result['count'] == 0) {
        sendResponse(false, 'Credenciales inválidas', 404);
    }

    // 6. Generación de token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 7. Guardar token
    $updateSql = "UPDATE usuarios 
                  SET reset_token = :token, reset_expires = :expiry 
                  WHERE usuario = :username AND email = :email";
    $stmt = $conn->prepare($updateSql);
    $updateResult = $stmt->execute([
        ':token' => $token,
        ':expiry' => $expiry,
        ':username' => trim($data['username']),
        ':email' => trim($data['email'])
    ]);

    if (!$updateResult || $stmt->rowCount() == 0) {
        sendResponse(false, 'Error al actualizar credenciales', 500);
    }

    // 8. Configuración PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mjlopez@undav.edu.ar'; 
        $mail->Password = 'Mailen13082019'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 2525;
        $mail->CharSet = 'UTF-8';

        // 🚫 IMPORTANTE: no mostrar debug en pantalla
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        // Enviar logs al error_log en vez de pantalla
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer [nivel $level]: $str");
        };

        $resetLink = "https://".$_SERVER['HTTP_HOST']."/login/nueva_password.html?token=$token&email=".urlencode($data['email']);
        
        $mail->setFrom('no-reply@undav.edu.ar', 'Sistema Tinkuy');
        $mail->addAddress($data['email'], $data['username']);
        $mail->Subject = 'Restablecer contraseña - Tinkuy';
        
        $mail->isHTML(true);
        $mail->Body = "Hola {$data['username']},<br><br>".
                      "Para restablecer tu contraseña, haz clic <a href=\"$resetLink\">aquí</a>.<br><br>".
                      "Este enlace expirará en 1 hora.";
        $mail->AltBody = "Hola {$data['username']},\n\n".
                         "Para restablecer tu contraseña, visita este enlace:\n".
                         "$resetLink\n\n".
                         "El enlace es válido por 1 hora.";

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->send();
        error_log("Correo enviado exitosamente a: ".$data['email']); 

        sendResponse(true, 'Se ha enviado un enlace de recuperación a tu correo.');
    } catch (Exception $mailException) {
        error_log('Error PHPMailer: '.$mailException->getMessage());
        sendResponse(false, 'Error al enviar el correo de recuperación', 500);
    }

} catch (PDOException $e) {
    error_log('PDO Exception: '.$e->getMessage());
    sendResponse(false, 'Error de base de datos', 500);
} catch (Exception $e) {
    error_log('General Exception: '.$e->getMessage());
    sendResponse(false, $e->getMessage(), $e->getCode() ?: 500);
}
