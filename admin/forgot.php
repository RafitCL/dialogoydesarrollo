<?php
require __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Ingresa un correo válido, por ejemplo correo@dominio.com');
        redirect('forgot.php');
    }

    $stmt = db()->prepare('SELECT id, nombres, ap_paterno, ap_materno, email FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        flash_set('error', 'No existe una cuenta con ese correo.');
        redirect('forgot.php');
    }

    $newPassword = bin2hex(random_bytes(6));
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = db()->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
    $update->execute([
        'hash' => 'must_change:' . $hash,
        'id'   => (int) $user['id'],
    ]);

    $nombreDestinatario = trim(($user['nombres'] ?? '') . ' ' . ($user['ap_paterno'] ?? '') . ' ' . ($user['ap_materno'] ?? ''));

    $subject = 'Nueva contraseña - Perú Portal';
    $body = 'Hola ' . $nombreDestinatario . ",\n\n"
        . 'Hemos generado una nueva contraseña temporal para tu cuenta:' . "\n\n"
        . 'Correo: ' . $user['email'] . "\n"
        . 'Contraseña temporal: ' . $newPassword . "\n\n"
        . 'Ingresa con ella y el sistema te pedirá que la cambies.' . "\n\n"
        . "Saludos,\nEquipo Perú Portal";
    $headers = 'From: no-reply@dialogoydesarrollo.com.pe' . "\r\n"
        . 'Reply-To: no-reply@dialogoydesarrollo.com.pe' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

    $sent = @mail($email, $subject, $body, $headers);

    if ($sent) {
        flash_set('success', 'Se envió una nueva contraseña a tu correo electrónico.');
    } else {
flash_set('error', 'No se pudo enviar el correo (servidor local sin SMTP). Tu contraseña temporal es: ' . $newPassword);
        }

        redirect('login.php');
    }

$flashError = flash_get('error');
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title>Recuperar Contraseña | Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
      body {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, #0f3460 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .login-card {
        width: 100%;
        max-width: 400px;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
      }
      .login-card .card-body {
        padding: 40px 36px;
      }
      .login-logo {
        width: 56px;
        height: 56px;
        margin: 0 auto 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(74, 108, 247, 0.12);
        border-radius: 14px;
      }
      .login-card h1 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 4px;
      }
      .login-card .form-label {
        font-weight: 600;
        font-size: 0.9rem;
      }
      .login-card .form-control {
        border-radius: 10px;
        padding: 12px 14px;
      }
      .invalid-feedback,
      .alert {
        font-size: 0.85rem;
      }
    </style>
  </head>
  <body>
    <div class="card login-card">
      <div class="card-body">
        <div class="login-logo">
          <img src="assets/images/logo/dyd.png" alt="Logo" height="34" />
        </div>
        <h1 class="text-center">Recuperar Contraseña</h1>
        <p class="text-center text-gray mb-4">Ingresa tu correo y te enviaremos una nueva contraseña</p>

        <?php if ($flashError !== ''): ?>
          <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>

        <form id="forgotForm" action="forgot.php" method="post" novalidate>
          <div class="mb-4">
            <label for="email" class="form-label">Correo electrónico</label>
            <input
              type="email"
              class="form-control"
              id="email"
              name="email"
              placeholder="correo@dominio.com"
              autocomplete="email"
              autofocus
            />
            <div class="invalid-feedback" id="emailError"></div>
          </div>

          <button type="submit" class="main-btn primary-btn btn-hover w-100 mb-3">Enviar nueva contraseña</button>

          <div class="text-center">
            <a href="login.php" class="hover-underline text-sm">Volver a Iniciar Sesión</a>
          </div>
        </form>
      </div>
    </div>

    <script>
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      document.getElementById("forgotForm").addEventListener("submit", function (event) {
        const email = document.getElementById("email");
        const emailError = document.getElementById("emailError");
        let valid = true;

        email.classList.remove("is-invalid");
        emailError.textContent = "";

        if (!emailPattern.test(email.value.trim())) {
          email.classList.add("is-invalid");
          emailError.textContent = "Ingresa un correo válido, por ejemplo correo@dominio.com";
          valid = false;
        }

        if (!valid) {
          event.preventDefault();
        }
      });
    </script>
  </body>
</html>