<?php
require __DIR__ . '/auth.php';
header('Content-Type: application/json');

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = db()->prepare('SELECT es_destacado FROM reportajes WHERE id = :id');
$stmt->execute(['id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    echo json_encode(['ok' => false]);
    exit;
}

$nuevo = (int) $r['es_destacado'] === 1 ? 0 : 1;

if ($nuevo === 1) {
    db()->exec('UPDATE reportajes SET es_destacado = 0');
}

$stmt = db()->prepare('UPDATE reportajes SET es_destacado = :d WHERE id = :id');
$stmt->execute(['d' => $nuevo, 'id' => $id]);

echo json_encode(['ok' => true, 'es_destacado' => $nuevo]);
