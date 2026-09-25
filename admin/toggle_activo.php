<?php
require __DIR__ . '/auth.php';
header('Content-Type: application/json');

$tabla = $_POST['tabla'] ?? '';
$id    = (int) ($_POST['id'] ?? 0);
$tablasPermitidas = ['noticia' => 'noticias', 'reportaje' => 'reportajes', 'boletin' => 'boletines', 'podcast' => 'podcasts', 'video' => 'videos'];

if (!isset($tablasPermitidas[$tabla]) || $id < 1) {
    echo json_encode(['ok' => false]);
    exit;
}

$tablaReal = $tablasPermitidas[$tabla];

$stmt = db()->prepare("SELECT activo FROM `$tablaReal` WHERE id = :id");
$stmt->execute(['id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    echo json_encode(['ok' => false]);
    exit;
}

$nuevo = (int) $r['activo'] === 1 ? 0 : 1;
$upd = db()->prepare("UPDATE `$tablaReal` SET activo = :a WHERE id = :id");
$upd->execute(['a' => $nuevo, 'id' => $id]);

echo json_encode(['ok' => true, 'activo' => $nuevo]);