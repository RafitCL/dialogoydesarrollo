<?php
declare(strict_types=1);

require __DIR__ . '/../../admin/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$st = db()->prepare('SELECT url_foto, imagen FROM fotos WHERE id = :id');
$st->execute(['id' => $id]);
$f = $st->fetch();

if ($f === false || $f['imagen'] === null || $f['imagen'] === '') {
    http_response_code(404);
    exit('Imagen no encontrada');
}

$ext = strtolower(pathinfo((string) $f['url_foto'], PATHINFO_EXTENSION));
$mimes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . strlen($f['imagen']));
header('Cache-Control: public, max-age=604800');

echo $f['imagen'];
exit;