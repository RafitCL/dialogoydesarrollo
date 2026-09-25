<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    flash_set('error', 'Ingresa tu correo y contraseña.');
    redirect('login.php');
}

$stmt = db()->prepare('SELECT id, nombres, ap_paterno, ap_materno, email, password_hash, rol FROM usuarios WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    flash_set('error', 'Correo o contraseña incorrectos.');
    redirect('login.php');
}

$mustChange = str_starts_with($user['password_hash'], 'must_change:');
$stored = $mustChange ? substr($user['password_hash'], strlen('must_change:')) : $user['password_hash'];

if (!password_verify($password, $stored)) {
    flash_set('error', 'Correo o contraseña incorrectos.');
    redirect('login.php');
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = trim(($user['nombres'] ?? '') . ' ' . ($user['ap_paterno'] ?? '') . ' ' . ($user['ap_materno'] ?? ''));
$_SESSION['user_first_name'] = $user['nombres'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_rol'] = $user['rol'];
$_SESSION['must_change_password'] = $mustChange;

redirect($mustChange ? 'cambiar_password.php' : ($user['rol'] === 'redactor' ? 'publicar.php?tipo=noticia' : 'index.php'));