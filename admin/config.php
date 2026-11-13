<?php
declare(strict_types=1);

session_start();

// Configuración de Base de Datos.
// Se leen primero las variables de entorno (AWS/producción) y, si no existen,
// se usan los valores por defecto para desarrollo local / InfinityFree.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'dialogoydesarrollo');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/dialogoydesarrollo');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): string
{
    $message = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $message;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function es_admin(): bool
{
    return is_logged_in() && ($_SESSION['user_rol'] ?? '') === 'admin';
}

function password_valida(string $pass): bool
{
    return mb_strlen($pass) >= 8
        && preg_match('/[A-Z]/', $pass) === 1
        && preg_match('/[0-9]/', $pass) === 1
        && preg_match('/[^A-Za-z0-9]/', $pass) === 1;
}

function sane_html(?string $html): string
{
    $html = (string) $html;
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $html = preg_replace('#<script\b[^>]*/?>#i', '', $html);
    $html = preg_replace('#</?\s*(?:iframe|object|embed|form)\b[^>]*>#i', '', $html);
    $html = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('#\s*(?:href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>]*\1#i', '', $html);
    return trim((string) $html);
}

function render_desarrollo(?string $texto): string
{
    $texto = trim((string) $texto);
    if ($texto === '') {
        return '';
    }

    if (preg_match('/<[a-z][^>]*>/i', $texto)) {
        return sane_html($texto);
    }

    $parrafos = array_values(array_filter(
        preg_split('/\R+/', $texto),
        fn(string $p): bool => trim($p) !== ''
    ));
    if ($parrafos === []) {
        return '';
    }

    $salida = [];
    foreach ($parrafos as $p) {
        $salida[] = '<p class="mb-4">' . htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return implode("\n", $salida);
}
