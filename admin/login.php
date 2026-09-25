<?php
require __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$flashError = flash_get('error');
$flashSuccess = flash_get('success');
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title>Iniciar Sesión | Panel de Administración</title>
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
        <h1 class="text-center">Iniciar Sesión</h1>
        <p class="text-center text-gray mb-4">Ingresa tus credenciales para continuar</p>

        <?php if ($flashError !== ''): ?>
          <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>

        <?php if ($flashSuccess !== ''): ?>
          <div class="alert alert-success py-2" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
        <?php endif; ?>

        <form id="loginForm" action="login_action.php" method="post" novalidate>
          <div class="mb-3">
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

          <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <input
              type="password"
              class="form-control"
              id="password"
              name="password"
              placeholder="Mínimo 8 caracteres"
              autocomplete="current-password"
            />
            <div class="invalid-feedback" id="passwordError"></div>
          </div>

          <button type="submit" class="main-btn primary-btn btn-hover w-100 mb-2">Iniciar Sesión</button>

          <div class="text-center">
            <a href="forgot.php" class="hover-underline text-sm">¿Olvidaste tu contraseña?</a>
          </div>
        </form>
      </div>
    </div>

    <script>
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      document.getElementById("loginForm").addEventListener("submit", function (event) {
        const email = document.getElementById("email");
        const password = document.getElementById("password");
        const emailError = document.getElementById("emailError");
        const passwordError = document.getElementById("passwordError");
        let valid = true;

        email.classList.remove("is-invalid");
        password.classList.remove("is-invalid");
        emailError.textContent = "";
        passwordError.textContent = "";

        if (!emailPattern.test(email.value.trim())) {
          email.classList.add("is-invalid");
          emailError.textContent = "Ingresa un correo válido, por ejemplo correo@dominio.com";
          valid = false;
        }

        if (password.value.length < 8) {
          password.classList.add("is-invalid");
          passwordError.textContent = "La contraseña debe tener al menos 8 caracteres.";
          valid = false;
        }

        if (!valid) {
          event.preventDefault();
        }
      });
    </script>
  </body>
</html>