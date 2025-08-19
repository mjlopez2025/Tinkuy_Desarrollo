<?php
include_once("../config.php");

if (!isset($_GET['token'])) {
    die("Token no válido.");
}

$token = $_GET['token'];
$stmt = $conn->prepare("SELECT id, reset_expires FROM usuarios WHERE reset_token = :token");
$stmt->execute([':token' => $token]);
$usuario = $stmt->fetch();

if (!$usuario || strtotime($usuario['reset_expires']) < time()) {
    die("Enlace inválido o expirado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="container1">
    <div class="form-box">
      <h2>Restablecer Contraseña</h2>
      <form action="procesar_restablecimiento.php" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="input-group">
          <label for="password">Nueva contraseña</label>
          <input type="password" id="password" name="password" placeholder="Ingresa tu nueva contraseña" required minlength="8">
        </div>

        <div class="input-group">
          <label for="confirmar">Repetir contraseña</label>
          <input type="password" id="confirmar" name="confirmar" placeholder="Repite tu contraseña" required minlength="8">
        </div>

        <button type="submit" class="btn">Actualizar contraseña</button>
      </form>
    </div>
  </div>
</body>
</html>
