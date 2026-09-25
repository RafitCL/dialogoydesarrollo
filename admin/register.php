<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$nombres = trim($_POST['nombres'] ?? '');
$apPaterno = trim($_POST['ap_paterno'] ?? '');
$apMaterno = trim($_POST['ap_materno'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($nombres === '' || $apPaterno === '' || $apMaterno === '' || $email === '' || $password === '') {
    flash_set('error', 'Todos los campos son obligatorios.');
    redirect('login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', 'El correo electrónico no es válido.');
    redirect('login.php');
}

if (!password_valida($password)) {
    flash_set('error', 'La contraseña debe tener mínimo 8 caracteres e incluir al menos 1 mayúscula, 1 número y 1 carácter especial.');
    redirect('login.php');
}

$stmt = db()->prepare('SELECT id FROM usuarios WHERE email = :email');
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    flash_set('error', 'Ya existe una cuenta con este correo electrónico.');
    redirect('login.php');
}

$stmt = db()->prepare(
    'INSERT INTO usuarios (nombres, ap_paterno, ap_materno, email, password_hash) VALUES (:nombres, :ap_paterno, :ap_materno, :email, :password_hash)'
);
$stmt->execute([
    'nombres' => $nombres,
    'ap_paterno' => $apPaterno,
    'ap_materno' => $apMaterno,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

flash_set('success', 'Cuenta creada correctamente. Ahora puedes iniciar sesión.');
redirect('login.php');