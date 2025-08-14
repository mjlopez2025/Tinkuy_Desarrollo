<?php
include_once("../config.php");

// Establecer conexión
$conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar token
$token = $_GET['token'] ?? '';
$stmt = $conn->prepare("SELECT id, email, reset_expires FROM usuarios WHERE reset_token = :token");
$stmt->bindParam(':token', $token);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Token inválido o expirado");
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si el token ha expirado
if (strtotime($user['reset_expires']) < time()) {
    die("El enlace ha expirado. Por favor solicita un nuevo enlace.");
}

// Mostrar formulario para nueva contraseña
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="login.css">
  <title>Restablecer Contraseña | Tinkuy</title>
</head>
<body>
  <div class="container1">
    <div class="logo-container">
      <div class="image-side">
        <img src="../imagenes/logo.png" alt="Logo UNDAV" class="logo-undav">
      </div>
      <div class="tincuy">
        <p style="font-size: 10pt; font-weight: normal;">Tinkuy: Del quechua, "Encuentro" o "Unión armónica". <br>
          En la tradición andina, representa la convergencia de fuerzas complementarias para crear algo superior.</p>
      </div>
    </div>
    <div class="login-container">
      <h1>Restablecer Contraseña</h1>
      <form id="resetForm">
        <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="input-group">
          <label for="email">Tu Correo Electrónico es</label>
          <div class="input-with-icon">
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly required>
            <i class="fas fa-envelope icon"></i>
          </div>
        </div>
        
        <div class="input-group">
          <label for="new-password">Nueva Contraseña</label>
          <div class="input-with-icon">
            <input type="password" id="new-password" name="new-password" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="off">
            <i class="fas fa-lock icon"></i>
            <span class="toggle-password" onclick="togglePassword('new-password')">
              <i class="fas fa-eye"></i>
            </span>
          </div>
          <div class="password-strength-meter">
            <div class="strength-bar"></div>
            <span class="strength-text">Seguridad: <span id="strength-text">Débil</span></span>
          </div>
        </div>
        
        <div class="input-group">
          <label for="confirm-password">Confirmar Contraseña</label>
          <div class="input-with-icon">
            <input type="password" id="confirm-password" name="confirm-password" placeholder="Vuelve a escribir tu contraseña" required autocomplete="off">
            <i class="fas fa-lock icon"></i>
            <span class="toggle-password" onclick="togglePassword('confirm-password')">
              <i class="fas fa-eye"></i>
            </span>
          </div> 
          <span id="password-match-message" class="message"></span>
        </div>
        
        <button type="submit" class="btn-login" id="submitBtn">
          <span class="btn-text">Restablecer Contraseña</span>
        </button>
        <div class="register-link">
          <p>¿Recordaste tu contraseña? <a href="index.html" class="register-anchor">Inicia sesión aquí</a></p>
        </div>
      </form>
    </div>
  </div>
  <p class="footer-text">TINKUY v.1.0 &copy; 2025 - Desarrollado por el Área de Sistemas de la Universidad Nacional de Avellaneda.</p>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const resetForm = document.getElementById('resetForm');
      const passwordInput = document.getElementById('new-password');
      const confirmPasswordInput = document.getElementById('confirm-password');
      const strengthText = document.getElementById('strength-text');
      const strengthBar = document.querySelector('.strength-bar');
      const passwordMatchMessage = document.getElementById('password-match-message');
      const submitBtn = document.getElementById('submitBtn');
      const btnText = submitBtn.querySelector('.btn-text');

      // Mostrar/ocultar contraseña
      window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling.querySelector('i');
        if (field.type === 'password') {
          field.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          field.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      };

      // Medidor de fortaleza
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        strengthBar.parentElement.classList.remove('password-weak', 'password-medium', 'password-strong');
        
        if (strength <= 2) {
          strengthBar.parentElement.classList.add('password-weak');
          strengthText.textContent = 'Débil';
        } else if (strength <= 4) {
          strengthBar.parentElement.classList.add('password-medium');
          strengthText.textContent = 'Media';
        } else {
          strengthBar.parentElement.classList.add('password-strong');
          strengthText.textContent = 'Fuerte';
        }
      });

      // Validar coincidencia de contraseñas
      confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
          passwordMatchMessage.textContent = 'Las contraseñas no coinciden';
          passwordMatchMessage.style.color = '#e74c3c';
        } else {
          passwordMatchMessage.textContent = 'Las contraseñas coinciden';
          passwordMatchMessage.style.color = '#2ecc71';
        }
      });

      // Enviar nueva contraseña
      resetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validaciones
        if (passwordInput.value !== confirmPasswordInput.value) {
          alert('Las contraseñas no coinciden');
          return;
        }
        
        if (passwordInput.value.length < 8) {
          alert('La contraseña debe tener al menos 8 caracteres');
          return;
        }
        
        // Verificar fortaleza de contraseña (al menos nivel medio)
        if (strengthText.textContent === 'Débil') {
          alert('Por favor elige una contraseña más segura');
          return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        
        try {
          const response = await fetch('reset_password_process.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              token: document.getElementById('token').value,
              new_password: passwordInput.value
            })
          });
          
          const data = await response.json();
          
          if (data.success) {
            alert('Contraseña actualizada correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.');
            window.location.href = 'index.html';
          } else {
            alert(data.message || 'Error al actualizar la contraseña');
          }
        } catch (error) {
          console.error('Error:', error);
          alert('Error al conectar con el servidor');
        } finally {
          submitBtn.disabled = false;
          btnText.textContent = 'Restablecer Contraseña';
        }
      });
    });
  </script>
</body>
</html>