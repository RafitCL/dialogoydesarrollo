<?php
require __DIR__ . '/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

if (empty($_SESSION['must_change_password'])) {
    redirect('index.php');
}

$userName = htmlspecialchars((string) ($_SESSION['user_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');

$passwordError = '';
$confirmError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!password_valida($password)) {
        $passwordError = 'La contraseña debe tener mínimo 8 caracteres e incluir al menos 1 mayúscula, 1 número y 1 carácter especial.';
    }

    if ($password !== $confirm) {
        $confirmError = 'Las contraseñas no coinciden.';
    }

    if ($passwordError === '' && $confirmError === '') {
        $update = db()->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $update->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id'   => (int) $_SESSION['user_id'],
        ]);

        unset($_SESSION['must_change_password']);

        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title>Cambiar Contraseña | Panel de Administración</title>
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
        <h1 class="text-center">Cambiar Contraseña</h1>
        <p class="text-center text-gray mb-4">Hola <?= $userName ?>, elige tu nueva contraseña</p>

        <form id="changeForm" action="cambiar_password.php" method="post" novalidate>
          <div class="mb-3">
            <label for="password" class="form-label">Nueva contraseña</label>
            <input
              type="password"
              class="form-control<?= $passwordError !== '' ? ' is-invalid' : '' ?>"
              id="password"
              name="password"
              placeholder="Mínimo 8 caracteres"
              autocomplete="new-password"
              autofocus
            />
            <div class="invalid-feedback" id="passwordError"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></div>
          </div>

          <div class="mb-4">
            <label for="confirm" class="form-label">Confirmar contraseña</label>
            <input
              type="password"
              class="form-control<?= $confirmError !== '' ? ' is-invalid' : '' ?>"
              id="confirm"
              name="confirm"
              placeholder="Repite la contraseña"
              autocomplete="new-password"
            />
            <div class="invalid-feedback" id="confirmError"><?= htmlspecialchars($confirmError, ENT_QUOTES, 'UTF-8') ?></div>
          </div>

          <button type="submit" class="main-btn primary-btn btn-hover w-100 mb-3">Guardar contraseña</button>
        </form>
      </div>
    </div>

    <script>
      document.getElementById("changeForm").addEventListener("submit", function (event) {
        const password = document.getElementById("password");
        const confirm = document.getElementById("confirm");
        const passwordError = document.getElementById("passwordError");
        const confirmError = document.getElementById("confirmError");
        const hasUpper = /[A-Z]/;
        const hasNumber = /[0-9]/;
        const hasSpecial = /[^A-Za-z0-9]/;
        let valid = true;

        password.classList.remove("is-invalid");
        confirm.classList.remove("is-invalid");
        passwordError.textContent = "";
        confirmError.textContent = "";

        if (
          password.value.length < 8 ||
          !hasUpper.test(password.value) ||
          !hasNumber.test(password.value) ||
          !hasSpecial.test(password.value)
        ) {
          password.classList.add("is-invalid");
          passwordError.textContent =
            "La contraseña debe tener mínimo 8 caracteres e incluir al menos 1 mayúscula, 1 número y 1 carácter especial.";
          valid = false;
        }

        if (password.value !== confirm.value) {
          confirm.classList.add("is-invalid");
          confirmError.textContent = "Las contraseñas no coinciden.";
          valid = false;
        }

        if (!valid) {
          event.preventDefault();
        }
      });
    </script>
  </body>
</html>