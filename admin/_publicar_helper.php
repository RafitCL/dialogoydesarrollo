<?php

function subir_foto(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se seleccionó la imagen.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato de imagen no válido. Usa JPG, PNG, WEBP o GIF.');
    }

    if ($file['size'] > 300 * 1024) {
        throw new RuntimeException('La imagen supera el tamaño máximo de 300 KB. Comprime la foto e inténtalo de nuevo.');
    }

    $ext = $allowed[$mime];
    $nombre = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destino = dirname(__DIR__) . '/uploads/' . $nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return 'uploads/' . $nombre;
}

function subir_pdf(array $archivo): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se seleccionó el archivo PDF.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);

    if ($mime !== 'application/pdf') {
        throw new RuntimeException('El archivo debe ser un PDF.');
    }

    if ($archivo['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('El PDF supera el tamaño máximo de 10 MB.');
    }

    $nombre = 'pdf_' . bin2hex(random_bytes(8)) . '.pdf';
    $destino = dirname(__DIR__) . '/uploads/' . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar el archivo PDF.');
    }

    return 'uploads/' . $nombre;
}

function guardar_foto(string $tipoEntidad, int $entidadId, string $ruta, string $descripcion = '', int $orden = 0): void
{
    $col = match ($tipoEntidad) {
        'boletin'  => 'boletin_id',
        'noticia'  => 'noticia_id',
        default    => 'reportaje_id',
    };
    $imagen = null;
    if ($ruta !== '' && is_file(dirname(__DIR__) . '/' . $ruta)) {
        $imagen = file_get_contents(dirname(__DIR__) . '/' . $ruta);
    }
    $stmt = db()->prepare("INSERT INTO fotos (`$col`, url_foto, imagen, orden, descripcion) VALUES (:id, :f, :img, :o, :d)");
    $stmt->execute([
        'id' => $entidadId,
        'f'  => $ruta,
        'img'=> $imagen,
        'o'  => $orden,
        'd'  => $descripcion !== '' ? $descripcion : null,
    ]);
}

function autor_nombre_completo(array $a): string
{
    if (!empty($a['es_nickname']) && !empty($a['nickname'])) {
        return (string) $a['nickname'];
    }
    return trim(($a['nombres'] ?? '') . ' ' . ($a['ap_paterno'] ?? '') . ' ' . ($a['ap_materno'] ?? ''));
}

function flash_success_redirect(string $url): void
{
    flash_set('success', 'Registro guardado correctamente.');
    redirect($url);
}
