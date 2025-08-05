<?php
session_start();

// Limpia las variables de sesión
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit();
?>
